<template>
  <DashboardLayout>
    <div class="grid grid-cols-12 gap-6">
      <!-- Left Column (8 cols) -->
      <div class="col-span-12 lg:col-span-8 space-y-6">
        <StatsCard>
          <BalanceSummary />
        </StatsCard>

        <StatsCard>
          <div class="flex items-center gap-4">
            <ServiceCard title="Bills" />
            <ServiceCard title="Transfers" />
            <ServiceCard title="Virtual Cards" />
            <ServiceCard title="Bet top-up" />
            <ServiceCard title="Giftcards" />
          </div>
        </StatsCard>

        <StatsCard>
          <div>
            <div class="flex items-center justify-between mb-4">
              <h4 class="font-semibold">My Wallets</h4>
              <div class="flex items-center gap-2">
                <button class="px-3 py-1 border rounded">Filter</button>
                <button class="px-3 py-1 border rounded">USD</button>
              </div>
            </div>

            <div class="space-y-3">
              <WalletCard name="Bitcoin" code="BTC" balance="$7,864.55" small="4.79 BTC" price="$112,298.23" change="+2.17%" :positive="true" icon="/_btc.svg" />
              <WalletCard name="Ethereum" code="ETH" balance="$3,547.12" small="27.33 ETH" price="$1,643.32" change="-0.17%" :positive="false" icon="/_eth.svg" />
              <WalletCard name="USD Coin" code="USDC" balance="$11,245.98" small="24,991.07 USDC" price="$0.45" change="+1.80%" :positive="true" icon="/_usdc.svg" />
            </div>
          </div>
        </StatsCard>

        <StatsCard>
          <BillsNav/>
        </StatsCard>

        <StatsCard>
          <div class="text-center py-12">
            <img src="/_empty_rewards.svg" alt="empty" class="mx-auto w-28 h-28 opacity-60" />
            <div class="mt-6 text-gray-600">No Rewards & Bonuses</div>
            <div class="text-sm text-gray-400 mt-2">You haven't made earned any rewards or bonuses from your referrals.</div>
          </div>
        </StatsCard>
      </div>

      <!-- Right Column (4 cols) -->
      <div class="col-span-12 lg:col-span-4 space-y-6">
        <StatsCard>
          <div class="">
            <div class=""></div>
            <h4 class="font-semibold">Quick Actions</h4>
            <div class="flex items-center justify-between">
              <button class="px-2 py-2 border rounded">Deposit</button>
              <button class="px-2 py-2 border rounded">Withdraw</button>
              <button class="px-2 py-2 border rounded">Trade</button>
            </div>
          </div>
        </StatsCard>

        <StatsCard>
          <div class="text-center py-12">
            <img src="/_empty_tx.svg" alt="empty" class="mx-auto w-28 h-28 opacity-60" />
            <div class="mt-6 text-gray-600">No Recent Transactions</div>
            <div class="text-sm text-gray-400 mt-2">You haven't made any transactions. This is where your most recent transaction will show</div>
          </div>
        </StatsCard>

        <StatsCard>
          <div>
            <div class="text-sm text-gray-500">My Portfolio</div>
            <div class="text-2xl font-bold mt-2">₦0.00</div>
            <div class="grid grid-cols-3 gap-3 mt-4">
              <div class="bg-white rounded-lg p-3 text-center border">
                <div class="text-xs text-gray-400">BTC</div>
                <div class="font-semibold">1,518.33</div>
                <div class="text-xs text-gray-400">1,518.27 NGN</div>
              </div>
              <div class="bg-white rounded-lg p-3 text-center border">
                <div class="text-xs text-gray-400">ETH</div>
                <div class="font-semibold">1,518.33</div>
                <div class="text-xs text-gray-400">1,518.27 NGN</div>
              </div>
              <div class="bg-white rounded-lg p-3 text-center border">
                <div class="text-xs text-gray-400">USDT</div>
                <div class="font-semibold">1,518.33</div>
                <div class="text-xs text-gray-400">1,518.27 NGN</div>
              </div>
            </div>
          </div>
        </StatsCard>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import StatsCard from '@/Components/StatsCard.vue'
import ServiceCard from '@/Components/ServiceCard.vue'
import WalletCard from '@/Components/WalletCard.vue'
import BalanceSummary from '@/Components/BalanceSummary.vue'
import BillsNav from '@/Components/BillsNav.vue'
</script>
