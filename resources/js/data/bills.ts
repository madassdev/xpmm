export type ServiceKey = 'airtime' | 'data' | 'tv' | 'electricity' | 'betting'

export const services = [
  { key: 'airtime',     label: 'Airtime',      icon: 'Phone',          color: '#2563EB' }, // blue
  { key: 'data',        label: 'Mobile Data',  icon: 'Signal',         color: '#16A34A' }, // green
  { key: 'tv',          label: 'TV Bills',     icon: 'Tv',             color: '#EA580C' }, // orange
  { key: 'electricity', label: 'Electricity',  icon: 'Zap',            color: '#7C3AED' }, // purple
  { key: 'betting',     label: 'Bet Top-up',   icon: 'Trophy',         color: '#DB2777' }, // pink
]

export const providersByService: Record<ServiceKey, Array<{id:string,name:string,logo:string,color?:string}>> = {
  airtime: [
    { id: 'mtn',     name: 'MTN',     logo: '/img/mtn.png',     color: '#FDE047' },
    { id: 'airtel',  name: 'Airtel',  logo: '/img/airtel.png',  color: '#EF4444' },
    { id: 'glo',     name: 'Glo',     logo: '/img/glo.png',     color: '#22C55E' },
    { id: '9mobile', name: '9mobile', logo: '/img/9mobile.png', color: '#10B981' },
  ],
  data: [
    { id: 'mtn',     name: 'MTN',     logo: '/img/mtn.png',     color: '#FDE047' },
    { id: 'airtel',  name: 'Airtel',  logo: '/img/airtel.png',  color: '#EF4444' },
    { id: 'glo',     name: 'Glo',     logo: '/img/glo.png',     color: '#22C55E' },
    { id: '9mobile', name: '9mobile', logo: '/img/9mobile.png', color: '#10B981' },
  ],
  tv: [
    { id: 'dstv',     name: 'DStv',     logo: '/img/dstv.png',      color: '#2563EB' },
    { id: 'gotv',     name: 'GOtv',     logo: '/img/gotv.png',      color: '#16A34A' },
    { id: 'startimes',name: 'Startimes',logo: '/img/startimes.png', color: '#EA580C' },
  ],
  electricity: [
    { id: 'ikeja', name: 'Ikeja Electric', logo: '/img/ikeja.png', color: '#F97316' },
    { id: 'eko',   name: 'Eko Electric',   logo: '/img/eko.png',   color: '#2563EB' },
    { id: 'ibedc', name: 'IBEDC',          logo: '/img/ibedc.png', color: '#10B981' },
  ],
  betting: [
    { id: 'bet9ja',    name: 'Bet9ja',    logo: '/img/bet9ja.png',    color: '#16A34A' },
    { id: 'betking',   name: 'BetKing',   logo: '/img/betking.png',   color: '#2563EB' },
    { id: 'sportybet', name: 'SportyBet', logo: '/img/sportybet.png', color: '#EF4444' },
  ]
}

export const countryCodes = [
  { label: '🇳🇬', code: '+234' }
]
