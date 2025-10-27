<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGiftcardRequest;
use App\Http\Requests\Admin\UpdateGiftcardRequest;
use App\Models\Giftcard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GiftcardController extends Controller
{
    /**
     * Curated list of supported countries (value => ISO alpha-2).
     */
    private const COUNTRY_OPTIONS = [
        ['label' => 'Nigeria', 'value' => 'NG'],
        ['label' => 'Ghana', 'value' => 'GH'],
        ['label' => 'Kenya', 'value' => 'KE'],
        ['label' => 'South Africa', 'value' => 'ZA'],
        ['label' => 'United States', 'value' => 'US'],
        ['label' => 'United Kingdom', 'value' => 'GB'],
        ['label' => 'Canada', 'value' => 'CA'],
        ['label' => 'United Arab Emirates', 'value' => 'AE'],
    ];

    /**
     * Nominal card values that operators typically support.
     */
    private const VALUE_OPTIONS = [25, 50, 100, 150, 200, 250, 500];

    public function index(): Response
    {
        $giftcards = Giftcard::query()
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function (Giftcard $giftcard) {
                return [
                    'id' => $giftcard->id,
                    'name' => $giftcard->name,
                    'rate' => (float) $giftcard->rate,
                    'available_regions' => $giftcard->available_regions ?? [],
                    'available_values' => $giftcard->available_values ?? [],
                    'image_url' => $giftcard->image_url,
                    'created_at' => $giftcard->created_at?->toIso8601String(),
                ];
            });

        return Inertia::render('Admin/Giftcards/Index', [
            'giftcards' => $giftcards,
            'countries' => self::COUNTRY_OPTIONS,
            'valueOptions' => self::VALUE_OPTIONS,
            'meta' => [
                'title' => 'Admin Giftcards',
                'description' => 'Manage catalogue rates, availability, and artwork for each giftcard.',
                'current' => 'giftcards',
            ],
        ]);
    }

    public function store(StoreGiftcardRequest $request): RedirectResponse
    {
        $giftcard = new Giftcard();
        $this->persistGiftcard($giftcard, $request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.giftcards.index')
            ->with('flash.success', 'Giftcard created successfully.');
    }

    public function update(UpdateGiftcardRequest $request, Giftcard $giftcard): RedirectResponse
    {
        $this->persistGiftcard($giftcard, $request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.giftcards.index')
            ->with('flash.success', 'Giftcard updated successfully.');
    }

    public function destroy(Giftcard $giftcard): RedirectResponse
    {
        if ($giftcard->image_path) {
            Storage::disk('public')->delete($giftcard->image_path);
        }

        $giftcard->delete();

        return redirect()
            ->route('admin.giftcards.index')
            ->with('flash.success', 'Giftcard deleted.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistGiftcard(Giftcard $giftcard, array $payload, ?UploadedFile $image = null): void
    {
        $giftcard->name = $payload['name'];
        $giftcard->rate = $payload['rate'];
        $giftcard->available_regions = $this->normalizeRegions($payload['available_regions'] ?? []);
        $giftcard->available_values = $this->normalizeValues($payload['available_values'] ?? []);

        if ($image) {
            if ($giftcard->image_path) {
                Storage::disk('public')->delete($giftcard->image_path);
            }

            $giftcard->image_path = $image->store('giftcards', 'public');
        }

        $giftcard->save();
    }

    /**
     * @param  array<int, string>  $regions
     * @return array<int, string>
     */
    private function normalizeRegions(array $regions): array
    {
        return collect($regions)
            ->filter(fn ($region) => filled($region))
            ->map(fn ($region) => strtoupper((string) $region))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string|int|float>  $values
     * @return array<int, int>
     */
    private function normalizeValues(array $values): array
    {
        return collect($values)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();
    }
}
