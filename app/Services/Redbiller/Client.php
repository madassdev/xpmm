<?php

namespace App\Services\Redbiller;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Client
{
    protected string $host;
    protected string $key;
    protected int $timeout;
    protected int $retries;

    public function __construct(protected array $cfg = [])
    {
        $this->cfg     = $cfg ?: config('redbiller');
        $env           = $this->cfg['env'] ?? 'test';
        $this->host    = $this->cfg['hosts'][$env] ?? $this->cfg['hosts']['test'];
        $this->key     = (string) ($this->cfg['private_key'] ?? '');
        $this->timeout = (int) ($this->cfg['http']['timeout'] ?? 25);
        $this->retries = (int) ($this->cfg['http']['retries'] ?? 2);
    }

    protected function http()
    {
        return Http::baseUrl($this->host)
            ->timeout($this->timeout)
            ->acceptJson()
            ->withHeaders([
                'Private-Key'  => $this->key,
                'Content-Type' => 'application/json',
            ])
            ->retry($this->retries, 300, throw: false);
    }

    protected function buildPath(string $tpl, string $version): string
    {
        return str_replace('{v}', $version, $tpl);
    }

    public function get(string $path, array $query = []): array
    {
        $resp = $this->http()->get($path, $query);

        return [
            'ok'      => $resp->successful(),
            'status'  => $resp->status(),
            'json'    => $resp->json(),
            'body'    => $resp->body(),
            'headers' => $resp->headers(),
        ];
    }

    public function post(string $path, array $payload = []): array
    {
        $resp = $this->http()->post($path, $payload);

        if (!$resp->successful()) {
            Log::warning('redbiller.http_error', [
                'path' => $path, 'status' => $resp->status(), 'body' => $resp->body()
            ]);
        }

        return [
            'ok'      => $resp->successful(),
            'status'  => $resp->status(),
            'json'    => $resp->json(),
            'body'    => $resp->body(),
            'headers' => $resp->headers(),
        ];
    }

    public function path(string $area, string $name): string
    {
        $tpl = $this->cfg['endpoints'][$area][$name] ?? null;
        if (!$tpl) throw new \RuntimeException("Unknown Redbiller endpoint: {$area}.{$name}");

        $version = $this->cfg['versions'][$area] ?? '1.0';
        return $this->buildPath($tpl, $version);
    }
}
