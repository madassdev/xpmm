<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCryptoAssetRequest;
use App\Http\Requests\Admin\UpdateCryptoAssetRequest;
use App\Models\CryptoAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CryptoAssetController extends Controller
{
    private const SALE_TYPES = [
        ['label' => 'Buy', 'value' => 'buy'],
        ['label' => 'Sell', 'value' => 'sell'],
    ];

    public function index(): Response
    {
        $assets = CryptoAsset::query()
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function (CryptoAsset $asset) {
                return [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'symbol' => $asset->symbol,
                    'rate' => (float) $asset->rate,
                    'wallet_address' => $asset->wallet_address,
                    'sale_type' => $asset->sale_type,
                    'sale_type_label' => ucfirst($asset->sale_type),
                    'charges' => (float) $asset->charges,
                    'logo_url' => $asset->logo_url,
                    'is_active' => (bool) $asset->is_active,
                    'created_at' => $asset->created_at?->toIso8601String(),
                ];
            });

        $totalAssets = CryptoAsset::count();
        $activeAssets = CryptoAsset::where('is_active', true)->count();
        $buyCount = CryptoAsset::where('sale_type', 'buy')->count();
        $sellCount = CryptoAsset::where('sale_type', 'sell')->count();

        return Inertia::render('Admin/CryptoExchange/Index', [
            'assets' => $assets,
            'stats' => [
                'totalAssets' => $totalAssets,
                'activeAssets' => $activeAssets,
                'buyCount' => $buyCount,
                'sellCount' => $sellCount,
                'averageRate' => (float) CryptoAsset::avg('rate') ?? 0,
                'averageFees' => (float) CryptoAsset::avg('charges') ?? 0,
            ],
            'saleTypes' => self::SALE_TYPES,
            'meta' => [
                'title' => 'Admin Crypto Exchange',
                'description' => 'Configure supported crypto assets, pricing, and settlement wallets.',
                'current' => 'crypto-exchange',
            ],
        ]);
    }

    public function store(StoreCryptoAssetRequest $request): RedirectResponse
    {
        $asset = new CryptoAsset();
        $this->persistAsset($asset, $request->validated(), $request->file('logo'));

        return redirect()
            ->route('admin.crypto-exchange.index')
            ->with('flash.success', 'Crypto asset created successfully.');
    }

    public function update(UpdateCryptoAssetRequest $request, CryptoAsset $cryptoAsset): RedirectResponse
    {
        $this->persistAsset($cryptoAsset, $request->validated(), $request->file('logo'));

        return redirect()
            ->route('admin.crypto-exchange.index')
            ->with('flash.success', 'Crypto asset updated successfully.');
    }

    public function destroy(CryptoAsset $cryptoAsset): RedirectResponse
    {
        if ($cryptoAsset->logo_path) {
            Storage::disk('public')->delete($cryptoAsset->logo_path);
        }

        $cryptoAsset->delete();

        return redirect()
            ->route('admin.crypto-exchange.index')
            ->with('flash.success', 'Crypto asset deleted.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistAsset(CryptoAsset $asset, array $payload, ?UploadedFile $logo = null): void
    {
        $asset->name = $payload['name'];
        $asset->symbol = strtoupper($payload['symbol']);
        $asset->rate = $payload['rate'];
        $asset->wallet_address = $payload['wallet_address'];
        $asset->sale_type = $payload['sale_type'];
        $asset->charges = $payload['charges'];
        $asset->is_active = (bool) $payload['is_active'];

        if ($logo) {
            if ($asset->logo_path) {
                Storage::disk('public')->delete($asset->logo_path);
            }

            $asset->logo_path = $logo->store('crypto-assets', 'public');
        }

        $asset->save();
    }
}

