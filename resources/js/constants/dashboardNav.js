const resolveRoute = (name, fallback) => {
  if (typeof route === 'function' && name) {
    try {
      return route(name)
    } catch (error) {
      console.warn(`[dashboard-nav] Missing route: ${name}`)
    }
  }
  return fallback
}

export const getDashboardNavSections = () => {
  const sections = [
    {
      label: 'General',
      items: [
        { key: 'overview', label: 'Overview', icon: 'LayoutDashboard', route: { name: 'dashboard', fallback: '/dashboard' } },
        { key: 'giftcards', label: 'Giftcards', icon: 'Gift', route: { name: 'giftcards.index', fallback: '/gift-cards' } },
        { key: 'wallets', label: 'Wallets', icon: 'Wallet', route: { name: 'wallets.index', fallback: '/wallets' } },
        { key: 'cards', label: 'Cards', icon: 'CreditCard', route: { name: 'cards.index', fallback: '/cards' } },
        { key: 'referrals', label: 'Referrals', icon: 'Users', route: { name: 'referrals.index', fallback: '/referrals' } },
        { key: 'txns', label: 'Transactions', icon: 'ReceiptText', route: { name: 'transactions.index', fallback: '/transactions' } },
      ],
    },
    {
      label: 'Utilities',
      items: [
        { key: 'bills', label: 'Bills', icon: 'Receipt', route: { name: 'bills.index', fallback: '/bills' } },
        { key: 'transfer', label: 'Transfer', icon: 'Send', route: { name: 'transfer.index', fallback: '/transfer' } },
        { key: 'bet', label: 'Bet Top-up', icon: 'TicketPercent', route: { name: 'betting.index', fallback: '/betting-topup' } },
      ],
    },
  ]

  return sections.map((section) => ({
    ...section,
    items: section.items.map((item) => ({
      ...item,
      href: resolveRoute(item.route?.name, item.route?.fallback ?? '#'),
    })),
  }))
}
