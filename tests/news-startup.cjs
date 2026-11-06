const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright'),assert=require('node:assert/strict');
(async()=>{const b=await chromium.launch({...(process.env.CHROME_PATH?{executablePath:process.env.CHROME_PATH}:{}),headless:true});try{const p=await b.newPage({timezoneId:'Pacific/Auckland'});await p.goto(process.env.CM_PREVIEW_URL||'http://127.0.0.1:9393/');await p.keyboard.press('Escape');await p.waitForFunction(()=>BOOT_READY&&MANAGERS.length);
const startup=await p.evaluate(async()=>{selectedEntry=MANAGERS[0].entry;APP_TIMEZONE='Europe/Dublin';const now=Date.parse(CMRules.dayKey(Date.now(),APP_TIMEZONE)+'T12:00:00Z')/1000,old=now-86400;const stories=[{id:'edition-new',sortTs:now,title:'November latest'},{id:'edition-early',sortTs:now-300,title:'November earliest'},{id:'edition-oct',sortTs:old,title:'October read story'}].map(s=>({...s,cat:'messages',time:CMRules.dayKey(s.sortTs*1000,APP_TIMEZONE),body:[]}));localStorage.setItem(readStorageKey(),JSON.stringify(stories.map(s=>s.id)));localStorage.setItem(seenStorageKey(),JSON.stringify(Object.fromEntries(stories.map(s=>[s.id,old*1000]))));localStorage.removeItem(readEditionKey());localStorage.setItem('cm-read-repair:v070:'+newsStorageScope(),JSON.stringify({day:'prior-repair'}));const original=api;api=async(route,params)=>route==='manager-news'?{stories,timezone:APP_TIMEZONE,manager:MANAGERS[0]}:original(route,params);await startAsManager(selectedEntry);return {id:selectedId,read:[...getReadIds()],alert:!document.getElementById('newsAlert').hidden,bg:CM_BG_MAIN_REQUEST};});
assert.equal(startup.id,'edition-early');assert.equal(startup.read.includes('edition-new'),false);assert.equal(startup.read.includes('edition-oct'),true);assert.equal(startup.alert,true);await p.locator('#newsAlert').click();assert.deepEqual(await p.evaluate(()=>({id:selectedId,bg:CM_BG_MAIN_REQUEST,alert:!document.getElementById('newsAlert').hidden})),{id:'edition-new',bg:startup.bg,alert:false});
await p.evaluate(async()=>{showSetupScreen();await startAsManager(selectedEntry);});assert.equal(await p.evaluate(()=>selectedId),'edition-early');
// Continue reaches the latest headline without cycling to older unread items.
const continueState=await p.evaluate(async()=>{
  const base=Date.parse(CMRules.dayKey(Date.now(),APP_TIMEZONE)+'T12:00:00Z')/1000;
  const story=(id,sortTs)=>({id,sortTs,title:id,cat:'messages',body:[]});
  window.continueStories=[story('continue-latest',base),story('continue-early',base-300),story('continue-old',base-86400)];
  window.continueRefreshes=0;
  const original=api;
  api=async(route,params)=>{
    if(route!=='manager-news')return original(route,params);
    continueRefreshes++;
    await new Promise(resolve=>setTimeout(resolve,450));
    return {stories:continueStories,timezone:APP_TIMEZONE,manager:MANAGERS[0]};
  };
  showSetupScreen();
  await startAsManager(selectedEntry);
  return {base,bg:CM_BG_MAIN_REQUEST};
});
const alert=p.locator('#newsAlert');
const button=p.locator('#continueGame');
const continueResult=()=>p.evaluate(()=>({id:selectedId,fetches:continueRefreshes,bg:CM_BG_MAIN_REQUEST}));
const clickContinue=async()=>{
  await button.click();
  await p.waitForFunction(()=>!CONTINUING);
  return continueResult();
};
assert.equal(await p.evaluate(()=>selectedId),'continue-early');
assert.equal(await alert.isVisible(),true);
assert.deepEqual(await clickContinue(),{id:'continue-latest',fetches:1,bg:continueState.bg});
assert.equal(await alert.isVisible(),false);
await button.click();
await p.locator('#continueGame.loading').waitFor();
assert.equal(await button.innerText(),'Please\nWait');
assert.equal(await p.locator('.continue-wait').evaluate(el=>getComputedStyle(el).color),'rgb(255, 255, 255)');
assert.equal(await button.evaluate(el=>getComputedStyle(el,'::after').content),'none');
await p.waitForFunction(()=>!CONTINUING);
assert.equal(await button.innerText(),'Continue\nGame');
assert.deepEqual(await continueResult(),{id:'continue-latest',fetches:2,bg:continueState.bg});
assert.equal(await p.evaluate(()=>getReadIds().has('continue-old')),false);
assert.equal(await alert.isVisible(),false);
await p.evaluate(base=>{
  continueStories=[
    {id:'continue-arrival-latest',sortTs:base+60,title:'New arrival latest',cat:'messages',body:[]},
    {id:'continue-arrival-early',sortTs:base+30,title:'New arrival first',cat:'messages',body:[]},
    ...continueStories
  ];
},continueState.base);
assert.deepEqual(await clickContinue(),{id:'continue-arrival-early',fetches:3,bg:continueState.bg});
assert.equal(await alert.isVisible(),true);
await alert.click();
assert.deepEqual(await continueResult(),{id:'continue-arrival-latest',fetches:3,bg:continueState.bg});
assert.equal(await alert.isVisible(),false);
assert.deepEqual(await clickContinue(),{id:'continue-arrival-latest',fetches:4,bg:continueState.bg});
assert.equal(await p.evaluate(()=>getReadIds().has('continue-old')),false);
console.log('PASS: Continue stays at the latest headline, reads new arrivals once each and shows white Please Wait text during refresh.');
console.log('PASS: NZ browser + UK game date; legacy October flags do not skip November; alert moves to newer November without a photo change; re-entry after reading all November starts at its oldest story.');}finally{await b.close();}})().catch(e=>{console.error(e);process.exitCode=1});
