export const delay = (ms:number)=> new Promise(res=>setTimeout(res, ms))

export async function mockFetchPlans(providerId:string){
  await delay(500)
  // demo plans (price in NGN)
  return [
    { id: 'p1', name: 'Daily 1GB', price: 350, desc: '24 hours' },
    { id: 'p2', name: 'Weekly 2.5GB', price: 1200, desc: '7 days' },
    { id: 'p3', name: 'Monthly 10GB', price: 3000, desc: '30 days' },
  ]
}

export async function mockLookupSmartcard(providerId:string, smartcard:string){
  await delay(600)
  if (smartcard.length < 6) return { ok:false, error:'Invalid smartcard number' }
  return { ok:true, name:'John Doe', address:'Lagos, NG' }
}

export async function mockMeterLookup(discoId:string, meterNo:string, type:'prepaid'|'postpaid'){
  await delay(600)
  if (meterNo.length < 6) return { ok:false, error:'Invalid meter number' }
  return { ok:true, name:'Adeola A.', address:'Ikeja, Lagos', type }
}
