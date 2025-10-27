<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Resources\BankAccountResource;
use App\Models\BankAccount;
use App\Support\BankList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BankAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $accounts = $request->user()
            ->bankAccounts()
            ->latest()
            ->get();

        return BankAccountResource::collection($accounts)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'bank_code'      => ['required', 'string', 'max:20', Rule::in(BankList::codes())],
            'account_number' => ['required', 'digits:10'],
            'account_name'   => ['required', 'string', 'max:255'],
            'is_primary'     => ['sometimes', 'boolean'],
        ]);

        $bankMeta = BankList::find($data['bank_code']);
        $data['bank_name'] = $bankMeta['name'] ?? 'Unknown Bank';

        $this->ensureUniqueConstraint($user->id, null, $data['bank_code'], $data['account_number']);

        $account = $user->bankAccounts()->create($data);

        return (new BankAccountResource($account))->response()->setStatusCode(201);
    }

    public function update(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $user = $request->user();
        $this->authorizeAccount($bankAccount, $user->id);

        $data = $request->validate([
            'bank_code'      => ['required', 'string', 'max:20', Rule::in(BankList::codes())],
            'account_number' => ['required', 'digits:10'],
            'account_name'   => ['required', 'string', 'max:255'],
            'is_primary'     => ['sometimes', 'boolean'],
        ]);

        $bankMeta = BankList::find($data['bank_code']);
        $data['bank_name'] = $bankMeta['name'] ?? 'Unknown Bank';

        $this->ensureUniqueConstraint($user->id, $bankAccount->id, $data['bank_code'], $data['account_number']);

        $bankAccount->update($data);

        return (new BankAccountResource($bankAccount->refresh()))->response();
    }

    public function destroy(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->authorizeAccount($bankAccount, $request->user()->id);

        $bankAccount->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bank_code'      => ['required', 'string', 'max:20', Rule::in(BankList::codes())],
            'account_number' => ['required', 'digits:10'],
        ]);

        if (random_int(1, 5) === 3) {
            return response()->json([
                'message' => 'Unable to verify account at the moment. Please try again shortly.',
            ], 422);
        }

        return response()->json([
            'account_name' => fake()->name(),
            'bank'         => BankList::find($data['bank_code']),
        ]);
    }

    protected function authorizeAccount(BankAccount $bankAccount, int $userId): void
    {
        abort_if($bankAccount->user_id !== $userId, 403, 'You are not allowed to access this bank account.');
    }

    protected function ensureUniqueConstraint(int $userId, ?int $ignoreId, string $bankCode, string $accountNumber): void
    {
        $exists = BankAccount::query()
            ->where('user_id', $userId)
            ->where('bank_code', $bankCode)
            ->where('account_number', $accountNumber)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        throw_if(
            $exists,
            ValidationException::withMessages([
                'account_number' => ['You have already saved this account.'],
            ])
        );
    }
}
