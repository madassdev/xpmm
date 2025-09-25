<?php

namespace App\Services\Redbiller;

use App\Models\User;
use App\Models\VirtualAccount;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class VAService
{
    protected array $cfg;
    protected string $host;
    protected string $key;
    protected string $version;

    public function __construct(?array $cfg = null)
    {
        $this->cfg     = $cfg ?: config('redbiller_va');
        $env           = $this->cfg['env'] ?? 'test';
        $this->host    = $this->cfg['hosts'][$env] ?? $this->cfg['hosts']['test'];
        $this->key     = (string) ($this->cfg['private_key'] ?? '');
        $this->version = (string) ($this->cfg['version'] ?? '1.0');
    }

    protected function http()
    {
        return Http::baseUrl($this->host)
            ->timeout($this->cfg['http']['timeout'] ?? 25)
            ->acceptJson()
            ->withHeaders(['Private-Key'=>$this->key,'Content-Type'=>'application/json'])
            ->retry($this->cfg['http']['retries'] ?? 2, 300, throw: false);
    }

    protected function path(string $key): string
    {
        $tpl = $this->cfg['endpoints'][$key] ?? null;
        if (!$tpl) throw new \RuntimeException("Unknown VA endpoint: {$key}");
        return str_replace('{v}', $this->version, $tpl);
    }

    protected function getByPath(array $json, string $path, $default = null)
    {
        return Arr::get($json, $path, $default);
    }

    public function getOrCreateVA(User $user): VirtualAccount
    {
        if ($va = VirtualAccount::where('user_id', $user->id)->first()) {
            return $va;
        }

        // Build payload from config mapping so you don't recompile to tweak keys
        $payload = $this->cfg['payload']['create_va'] ?? [];
        // Reasonable defaults if mapping is empty:
        $payload += [
            'reference' => 'VA-'.Str::padLeft((string)$user->id, 8, '0'),
            'customer'  => [
                'name'  => $user->name ?? ('User '.$user->id),
                'email' => $user->email,
                'phone' => $user->phone ?? null,
            ],
        ];

        $res  = $this->http()->post($this->path('create_va'), $payload);
        if (!$res->successful()) {
            throw new \RuntimeException('VA_CREATE_FAILED: '.$res->status().' '.$res->body());
        }

        $json = $res->json() ?? [];
        $map  = $this->cfg['response']['va'];
        $acct = $this->getByPath($json, $map['account_number'] ?? 'account_number');
        $bank = $this->getByPath($json, $map['bank_name']      ?? 'bank_name');

        if (!$acct) throw new \RuntimeException('VA_CREATE_FAILED: account_number missing');

        return VirtualAccount::create([
            'user_id'        => $user->id,
            'provider'       => 'redbiller',
            'account_number' => (string) $acct,
            'bank_name'      => $bank ? (string) $bank : null,
            'status'         => 'ACTIVE',
            'meta'           => $json,
        ]);
    }
}
