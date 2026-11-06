/* Shared visitor-only H2H selector. No server configuration is written. */
(() => {
  'use strict';
  const script=document.currentScript;
  const key='fpl-h2h-choice:v1:'+script.dataset.app+':'+new URL('.',location.href).pathname;
  let selected=0;
  try { const raw=localStorage.getItem(key);if(raw&&/^[1-9]\d{0,9}$/.test(raw)&&Number(raw)<=2147483647)selected=Number(raw); } catch (_) {}
  function parse(value){
    const text=String(value).trim();
    if(/^[1-9]\d{0,9}$/.test(text)&&Number(text)<=2147483647)return Number(text);
    try {
      const url=new URL(text);
      if(url.protocol==='https:'&&url.hostname==='fantasy.premierleague.com'&&!url.username&&!url.password&&!url.port){
        const match=url.pathname.match(/^\/(?:[a-z]{2}\/)?leagues\/([1-9]\d{0,9})\/(?:standings|matches)\/h\/?$/);
        if(match&&Number(match[1])<=2147483647)return Number(match[1]);
      }
    } catch (_) {}
    throw new Error('Enter a numeric H2H league ID or its official FPL head-to-head league link. A joining code will not work.');
  }
  function decorate(target){
    const url=new URL(target,location.href);
    if(selected)url.searchParams.set('league',String(selected));
    return url.pathname+url.search;
  }
  const dialog=document.createElement('dialog');dialog.id='fpl-league-dialog';dialog.dataset.theme=script.dataset.app;dialog.setAttribute('aria-labelledby','fpl-league-title');
  dialog.innerHTML='<h2 id="fpl-league-title">Choose H2H League</h2><form><p id="fpl-current-league">Using the site default league.</p><label for="fpl-league-input">League ID or FPL league link</label><input id="fpl-league-input" type="text" autocomplete="off" spellcheck="false" aria-describedby="fpl-league-help fpl-league-status"><p id="fpl-league-help">Use the number from your head-to-head league’s FPL link, or paste the full link. No FPL password is needed.</p><p>Your choice stays in this browser. Changing league returns you to manager selection.</p><p id="fpl-league-status" role="status" aria-live="polite"></p><div class="league-actions"><button type="submit">Use league</button><button type="button" data-default>Site default</button><button type="button" data-cancel>Cancel</button></div></form>';
  const game=document.getElementById('game');game.appendChild(dialog);dialog.setAttribute('aria-modal','true');
  // Use the same 800×600 screen, header, photo and panel geometry as the CM menus.
  const cmScreen=document.createElement('div');cmScreen.className='cm-league-screen';
  const photo=document.createElement('div');photo.className='setup-photo';
  const sidebar=document.querySelector('#setupCreditsScreen .setup-sidebar').cloneNode(true);sidebar.removeAttribute('aria-hidden');
  const title=document.createElement('div');title.className='setup-title';
  const form=dialog.querySelector('form'),panel=document.createElement('div');panel.className='cm-league-panel';
  const heading=dialog.querySelector('h2');
  heading.className='setup-heading';
  const actions=form.querySelector('.league-actions');
  for(const child of [...form.children])if(child!==actions)panel.appendChild(child);
  actions.classList.add('setup-bottom');
  actions.querySelectorAll('button').forEach(button=>button.className='setup-btn');
  form.prepend(panel);
  cmScreen.append(photo,sidebar,title,heading,form);
  dialog.appendChild(cmScreen);
  let previousFocus=null,inertSiblings=[];
  function openScreen(){
    if(dialog.open)return;
    previousFocus=document.activeElement;
    inertSiblings=[...game.children].filter(el=>el!==dialog).map(el=>[el,el.inert]);
    for(const [el] of inertSiblings)el.inert=true;
    dialog.show();window.updateCMSetupArrows?.();
  }
  dialog.addEventListener('close',()=>{
    for(const [el,wasInert] of inertSiblings)el.inert=wasInert;
    inertSiblings=[];window.onCMLeagueClose?.();previousFocus?.focus({preventScroll:true});
  });
  if(selected)dialog.querySelector('#fpl-current-league').textContent='Selected league '+selected;
  const input=dialog.querySelector('input'),status=dialog.querySelector('[role=status]'),submit=dialog.querySelector('[type=submit]'),reset=dialog.querySelector('[data-default]');
  let controller=null,generation=0;
  function cancel(){generation++;controller?.abort();dialog.close();}
  dialog.querySelector('[data-cancel]').addEventListener('click',cancel);
  dialog.addEventListener('cancel',()=>{generation++;controller?.abort();});
  async function apply(id){
    controller?.abort();controller=new AbortController();const request=++generation;
    submit.disabled=reset.disabled=true;status.textContent='Checking league…';
    const url=new URL('./api.php',location.href);url.searchParams.set('route','league-check');if(id)url.searchParams.set('league',String(id));
    try{
      const response=await fetch(url,{cache:'no-store',signal:controller.signal});const payload=await response.json();
      if(!response.ok)throw new Error(payload.error||'That league is unavailable. Check the ID and try again.');
      if(request!==generation)return;
      try{if(id)localStorage.setItem(key,String(id));else localStorage.removeItem(key);}catch(_){throw new Error('This browser cannot save the league choice. Enable site storage and try again.');}
      // A full reload discards old manager, scores, in-flight requests and navigation state.
      const next=new URL(location.href);next.searchParams.delete('entry');next.searchParams.delete('league');location.replace(next.href);
    }catch(error){if(request===generation&&error.name!=='AbortError')status.textContent=error.message||'League unavailable. Please try again.';}
    finally{if(request===generation)submit.disabled=reset.disabled=false;}
  }
  dialog.querySelector('form').addEventListener('submit',e=>{e.preventDefault();try{apply(parse(input.value));}catch(error){status.textContent=error.message;}});
  reset.addEventListener('click',()=>apply(0));
  document.addEventListener('keydown',e=>{
    if(!dialog.open)return;
    e.stopImmediatePropagation();
    if(e.key==='Escape'){e.preventDefault();cancel();}
    if(e.key==='Tab'){
      const fields=[...dialog.querySelectorAll('input,button')].filter(el=>!el.disabled);
      const first=fields[0],last=fields[fields.length-1];
      if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus({preventScroll:true});}
      else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus({preventScroll:true});}
    }
  },true);
  window.FPLLeague={parse,decorate,get selectedId(){return selected;},close(){if(!dialog.open)return Promise.resolve();return new Promise(resolve=>{dialog.addEventListener('close',resolve,{once:true});cancel();});},open(){window.onCMLeagueOpen?.();generation++;controller?.abort();submit.disabled=reset.disabled=false;input.value=selected?String(selected):'';status.textContent='';title.textContent=document.querySelector('#setupScreen .setup-title').textContent;const source=document.querySelector('.setup-overlay.active .setup-photo')||document.querySelector('#setupScreen .setup-photo');photo.style.backgroundImage=getComputedStyle(source).backgroundImage;openScreen();input.focus({preventScroll:true});},setCurrent(name,id){dialog.querySelector('#fpl-current-league').textContent=(name||'H2H League')+' '+id+(selected?'':' (site default)');document.querySelectorAll('[data-current-league]').forEach(el=>el.textContent=name||'H2H League');}};
  document.addEventListener('click',e=>{if(e.target.closest('[data-change-league]'))FPLLeague.open();});
})();
