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
  const style=document.createElement('style');
  style.textContent=`#fpl-league-dialog{width:min(480px,calc(100vw - 24px));max-height:calc(100dvh - 24px);overflow:auto;margin:auto;padding:0;border:3px outset #bbb;background:#dedede;color:#14142a;box-shadow:0 12px 60px #000a;font:15px/1.5 Tahoma,Arial,sans-serif;z-index:10000}#fpl-league-dialog::backdrop{background:#000b}#fpl-league-dialog h2{margin:0;padding:12px 16px;background:#180064;color:#fff;font-size:19px}#fpl-league-dialog form{padding:16px}#fpl-league-dialog p{margin:0 0 12px}#fpl-league-dialog label{display:block;font-weight:bold}#fpl-league-dialog input{display:block;width:100%;padding:10px;margin:5px 0 12px;border:2px inset #aaa;background:#fff;color:#111;font:16px Arial,sans-serif;cursor:text}#fpl-league-dialog .league-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}#fpl-league-dialog button{padding:9px 13px;min-height:42px;border:2px outset #bbb;background:#c5c5c5;color:#111;font:14px Tahoma,Arial,sans-serif;cursor:pointer}#fpl-league-dialog button[type=submit]{background:#220077;color:#fff}#fpl-league-dialog button:disabled{opacity:.6;cursor:wait}#fpl-league-dialog [role=status]{color:#8a140d;min-height:1.5em}#fpl-league-dialog[data-theme=keegan]{border-color:#b8860b;background:#101d1c;color:#fff7bf}#fpl-league-dialog[data-theme=keegan] h2{background:#946800}#fpl-league-dialog[data-theme=keegan] [role=status]{color:#ffe08c}`;
  document.head.appendChild(style);
  const dialog=document.createElement('dialog');dialog.id='fpl-league-dialog';dialog.dataset.theme=script.dataset.app;dialog.setAttribute('aria-labelledby','fpl-league-title');
  dialog.innerHTML='<h2 id="fpl-league-title">Choose H2H League</h2><form><p id="fpl-current-league">Using the site default league.</p><label for="fpl-league-input">League ID or FPL league link</label><input id="fpl-league-input" type="text" autocomplete="off" spellcheck="false" aria-describedby="fpl-league-help fpl-league-status"><p id="fpl-league-help">Use the number from your head-to-head league’s FPL link, or paste the full link. No FPL password is needed.</p><p>Your choice stays in this browser. Changing league returns you to manager selection.</p><p id="fpl-league-status" role="status" aria-live="polite"></p><div class="league-actions"><button type="submit">Use league</button><button type="button" data-default>Site default</button><button type="button" data-cancel>Cancel</button></div></form>';
  const game=document.getElementById('game');game.appendChild(dialog);dialog.setAttribute('aria-modal','true');
  // Use the same 800×600 screen, header, photo and panel geometry as the CM menus.
  const cmScreen=document.createElement('div');cmScreen.className='cm-league-screen';
  const photo=document.createElement('div');photo.className='setup-photo';
  const sidebar=document.querySelector('#setupCreditsScreen .setup-sidebar').cloneNode(true);sidebar.removeAttribute('aria-hidden');
  const title=document.createElement('div');title.className='setup-title';
  const form=dialog.querySelector('form'),panel=document.createElement('div');panel.className='cm-league-panel';
  panel.appendChild(dialog.querySelector('h2'));
  const actions=form.querySelector('.league-actions');
  for(const child of [...form.children])if(child!==actions)panel.appendChild(child);
  form.prepend(panel);cmScreen.append(photo,sidebar,title,form);dialog.appendChild(cmScreen);
  style.textContent+=`#fpl-league-dialog[data-theme=cm]{position:absolute;inset:0;width:800px;height:600px;margin:0;max-width:none;max-height:none;padding:0;border:0;background:#000;box-shadow:0 0 0 1px #5f5f5f;overflow:hidden;font-family:Tahoma,Verdana,Arial,sans-serif}#fpl-league-dialog[data-theme=cm]::backdrop{background:transparent}#fpl-league-dialog .cm-league-screen{position:relative;width:800px;height:600px;transform-origin:top left;color:#eee}#fpl-league-dialog[data-theme=cm] form{position:absolute;inset:0;padding:0}#fpl-league-dialog .cm-league-panel{position:absolute;left:106px;right:14px;top:92px;height:404px;padding:14px 18px;overflow:auto;background:rgba(7,10,48,.72);border:2px solid #243de0;box-shadow:inset 0 0 0 1px #ffffff2e}#fpl-league-dialog[data-theme=cm] h2{padding:0;margin:0 0 14px;background:none;color:#eff500;font-size:24px;line-height:1.2;text-shadow:2px 2px #111}#fpl-league-dialog[data-theme=cm] p{margin:0 0 14px;font-size:12px;line-height:1.45;color:#eee}#fpl-league-dialog[data-theme=cm] label{font-size:13px;color:#eff500}#fpl-league-dialog[data-theme=cm] input{margin:8px 0 16px;padding:9px 10px;background:rgba(0,0,35,.7);color:#fff;border:2px inset #aaa;font:14px Tahoma,Verdana,Arial,sans-serif;outline-offset:2px}#fpl-league-dialog[data-theme=cm] input:focus-visible{outline:2px solid #eff500}#fpl-league-dialog[data-theme=cm] [role=status]{color:#eff500}#fpl-league-dialog[data-theme=cm] .league-actions{position:absolute;left:106px;right:14px;bottom:8px;height:43px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:2px;margin:0}#fpl-league-dialog[data-theme=cm] button{padding:0;min-height:0;border:2px solid #ddd;border-right-color:#555;border-bottom-color:#555;background:linear-gradient(#c0c0c0,#969696);color:#eee;font:700 16px Tahoma,Verdana,Arial,sans-serif;text-shadow:1px 1px #666}#fpl-league-dialog[data-theme=cm] button:hover{filter:brightness(1.08)}`;
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
