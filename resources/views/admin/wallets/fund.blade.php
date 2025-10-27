<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Manual Wallet Funding</title>
        @vite('resources/css/app.css')
    </head>
    <body class="bg-gray-100 font-sans antialiased">
        <div class="min-h-screen py-10">
            <div class="mx-auto max-w-5xl">
                <div class="mb-6 flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-900">Manual Wallet Funding</h1>
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">&larr; Back to dashboard</a>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-md bg-green-100 p-4 text-green-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-md bg-red-100 p-4 text-red-800">
                        <p class="font-semibold">There were some problems with your input:</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid gap-8 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Balance</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse ($users as $user)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $user->name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">{{ $user->email }}</td>
                                            <td class="px-4 py-3 text-right text-sm text-gray-900">
                                                {{ number_format(optional($user->wallet)->balance ?? 0, 2) }} {{ optional($user->wallet)->currency ?? 'NGN' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-3 text-center text-sm text-gray-600">No users found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                            <h2 class="text-lg font-medium text-gray-900">Record Manual Credit</h2>
                            <form method="POST" action="{{ route('admin.wallets.fund.store') }}" class="mt-4 space-y-4">
                                @csrf

                                <div>
                                    <label for="user_id" class="block text-sm font-medium text-gray-700">Select user</label>
                                    <select id="user_id" name="user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="">Choose a user</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                                    <input type="number" step="0.01" min="0.01" id="amount" name="amount" value="{{ old('amount') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>

                                <div>
                                    <label for="reference" class="block text-sm font-medium text-gray-700">Reference</label>
                                    <input type="text" id="reference" name="reference" value="{{ old('reference') }}" maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700">Description (optional)</label>
                                    <input type="text" id="description" name="description" value="{{ old('description') }}" maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                        Credit wallet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
