/* Shared green menus, functional news scrolling and personal tactics annotations. */
(() => {
  'use strict';
  const game=document.getElementById('game');
  let popup=null,owner=null;
  function closeMenu(restore=false){
    popup?.remove();popup=null;
    owner?.setAttribute('aria-expanded','false');
    if(restore)owner?.focus({preventScroll:true});owner=null;
  }
  function menu(anchor,items){
    if(owner===anchor){closeMenu(true);return;}
    closeMenu();owner=anchor;anchor.setAttribute('aria-expanded','true');
    popup=document.createElement('div');popup.className='cm-popup';popup.id='cm-popup';popup.setAttribute('role','menu');popup.tabIndex=-1;
    const current=popup;
    for(const item of items){
      if(item.separator){const line=document.createElement('div');line.setAttribute('role','separator');popup.append(line);continue;}
      const button=document.createElement('button');button.type='button';button.textContent=item.label;button.title=item.label;button.disabled=!!item.disabled;
      button.setAttribute('role',item.checked===undefined?'menuitem':'menuitemradio');
      if(item.checked!==undefined)button.setAttribute('aria-checked',String(item.checked));
      button.addEventListener('click',async()=>{closeMenu(true);try{await item.action();}catch(error){console.error('Menu action failed',error);}});
      popup.append(button);
    }
    game.append(popup);
    const g=game.getBoundingClientRect(),a=anchor.getBoundingClientRect(),scale=g.width/800;
    const width=anchor.closest('.sidebar')?210:Math.min(280,Math.max(145,a.width/scale));popup.style.width=width+'px';
    let x=(a.left-g.left)/scale,y=(a.bottom-g.top)/scale;
    if(anchor.closest('.sidebar')){x=92;y=(a.top-g.top)/scale;}
    popup.style.left=Math.max(0,Math.min(800-width,x))+'px';
    popup.style.top=Math.max(0,Math.min(600-popup.offsetHeight,y))+'px';
    if(anchor.matches(':focus-visible'))current.querySelector('[aria-checked=true],button:not(:disabled)')?.focus({preventScroll:true});else current.focus({preventScroll:true});
  }
  document.addEventListener('pointerdown',e=>{if(popup&&!popup.contains(e.target)&&!owner?.contains(e.target))closeMenu();});
  document.addEventListener('keydown',e=>{
    if(!popup)return;
    const buttons=[...popup.querySelectorAll('button:not(:disabled)')],i=buttons.indexOf(document.activeElement);
    if(['ArrowDown','ArrowUp','Home','End'].includes(e.key)){
      e.preventDefault();e.stopImmediatePropagation();
      const next=e.key==='Home'?0:e.key==='End'?buttons.length-1:i<0?(e.key==='ArrowDown'?0:buttons.length-1):(i+(e.key==='ArrowDown'?1:-1)+buttons.length)%buttons.length;
      buttons[next]?.focus({preventScroll:true});
    }else if(e.key==='Escape'){e.preventDefault();e.stopImmediatePropagation();closeMenu(true);}
    else if(e.key==='Tab')closeMenu();
  },true);
  window.addEventListener('resize',()=>closeMenu());
  // Keep selects as the source of truth; the green menu uses their existing change handlers.
  const selectMenu=select=>menu(select,[...select.options].map(option=>({label:option.textContent,checked:option.value===select.value,disabled:option.disabled,action:()=>{select.value=option.value;select.dispatchEvent(new Event('change',{bubbles:true}));}})));
  document.addEventListener('pointerdown',e=>{const select=e.target.closest('select');if(select&&!select.disabled){e.preventDefault();select.focus();selectMenu(select);}});
  document.addEventListener('keydown',e=>{
    const select=e.target.closest('select');
    if(select&&!select.disabled&&['ArrowDown','ArrowUp',' ','Enter'].includes(e.key)){e.preventDefault();selectMenu(select);}
  });
  function trigger(element,label){element.tabIndex=0;element.setAttribute('role','button');element.setAttribute('aria-haspopup','menu');element.setAttribute('aria-expanded','false');if(label)element.setAttribute('aria-label',label);element.classList.add('cm-menu-trigger');element.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();element.click();}});}
  const manager=document.querySelector('.sidebar .manager'),options=document.querySelector('.sidebar .options');
  trigger(manager,'Manager pages');trigger(options,'Game options');
  const continueButton=document.getElementById('continueGame');continueButton.tabIndex=0;continueButton.setAttribute('role','button');continueButton.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();continueButton.click();}});
  window.openCMManagerMenu=anchor=>{
    if(!selectedEntry)return;
    const team=managerByEntry(selectedEntry)?.teamName||'Team';
    menu(anchor,[
      ...[[team+' Squad','squad'],['Next Match','next'],['Fixtures','fixtures'],['Finances & Info','info']].map(([label,tab])=>({label,action:()=>showClub(selectedEntry,'news',{tab})})),
      {label:'Tactics',action:()=>showTactics(selectedEntry)},
      {separator:true},
      {label:'News',action:async()=>{await loadNews(selectedEntry,{quiet:true,focus:true});showNews();}},
      {label:'Transfers',action:()=>showClub(selectedEntry,'news',{tab:'transfers'})}
    ]);
  };
  window.openCMOptions=anchor=>menu(anchor,[
    {label:'Change Manager',action:()=>showSetupManagerScreen(true)},
    {label:'Change League',action:()=>FPLLeague.open()},
    {separator:true},{label:'Setup Menu',action:()=>showSetupScreen()}
  ]);
  // Both readers use the same wheel, track, thumb and keyboard behaviour.
  function readerScrollbar(bar,list,state){
    const thumb=bar.querySelector('.scroll-thumb');thumb.tabIndex=0;
    for(const [key,value] of Object.entries({role:'scrollbar','aria-label':state.label,'aria-controls':list.id,'aria-orientation':'vertical','aria-valuemin':'0'}))thumb.setAttribute(key,value);
    const move=delta=>{state.set(Math.max(0,Math.min(Math.max(0,state.count()-pageSize),state.get()+delta)));state.render();};
    for(const area of [bar,list])area.addEventListener('wheel',e=>{e.preventDefault();move(Math.sign(e.deltaY));},{passive:false});
    bar.querySelectorAll('.scroll-arrow').forEach((arrow,i)=>arrow.onclick=()=>move(i?1:-1));
    thumb.addEventListener('keydown',e=>{if(['ArrowDown','ArrowUp','PageDown','PageUp','Home','End'].includes(e.key)){e.preventDefault();move(e.key==='Home'?-state.count():e.key==='End'?state.count():(e.key.includes('Down')?1:-1)*(e.key.startsWith('Page')?pageSize:1));}});
    bar.addEventListener('pointerdown',e=>{
      if(e.target.closest('.scroll-arrow'))return;
      if(e.target!==thumb){move(e.clientY<thumb.getBoundingClientRect().top?-pageSize:pageSize);return;}
      e.preventDefault();thumb.focus({preventScroll:true});thumb.setPointerCapture(e.pointerId);
      const start=e.clientY,initial=state.get(),scale=game.getBoundingClientRect().width/800,travel=59-parseFloat(thumb.style.height),max=Math.max(0,state.count()-pageSize);
      const drag=ev=>{if(travel>0){state.set(Math.max(0,Math.min(max,Math.round(initial+(ev.clientY-start)/scale/travel*max))));state.render();}};
      const stop=()=>{thumb.removeEventListener('pointermove',drag);thumb.removeEventListener('pointerup',stop);thumb.removeEventListener('pointercancel',stop);};
      thumb.addEventListener('pointermove',drag);thumb.addEventListener('pointerup',stop);thumb.addEventListener('pointercancel',stop);
    });
  }
  readerScrollbar(document.querySelector('.scrollbar'),listEl,{label:'News list',get:()=>offset,set:v=>offset=v,count:()=>visible.length,render});
  readerScrollbar(document.querySelector('.media-scrollbar'),document.getElementById('mediaList'),{label:'Media list',get:()=>MEDIA_OFFSET,set:v=>MEDIA_OFFSET=v,count:()=>MEDIA_VISIBLE.length,render:renderMedia});
  // Pointer focus never adds a browser ring; keyboard focus remains visible.
  document.addEventListener('pointerdown',()=>game.dataset.input='pointer',true);
  document.addEventListener('keydown',()=>game.dataset.input='keyboard',true);
  // Drawing changes only local annotations; a click still opens the player's file.
  let removeMode=false,arrowsVisible=true,suppressClick=false,activeDrag=null;
  window.cmAnnotating=false;
  const pitch=document.getElementById('tacticsPitch'),note=document.getElementById('tacticsNote');
  function arrowKey(){return 'cm-tactics-arrows:v2:'+location.pathname+':'+APP_SEASON+':'+(LEAGUE_PAYLOAD?.league?.id||FPLLeague.selectedId||'default')+':'+TACTICS_ENTRY;}
  function readArrows(){
    try{
      const key=arrowKey(),current=localStorage.getItem(key),value=JSON.parse(current||localStorage.getItem(key.replace(':v2:',':v1:'))||'{}');
      if(!value||typeof value!=='object'||Array.isArray(value))return {};
      if(current===null)for(const id of Object.keys(value)){const d=value[id];if(Array.isArray(d)&&d.length===2&&d.every(n=>[-1,0,1].includes(n)))value[id]=d.map(n=>n*13);}
      return value;
    }catch(_){return {};}
  }
  function saveArrows(value){try{localStorage.setItem(arrowKey(),JSON.stringify(value));note.textContent='';return true;}catch(_){note.textContent='Browser storage unavailable — arrow was not saved';return false;}}
  window.resetCMAnnotation=()=>{removeMode=false;note.textContent='';};
  const edit=document.getElementById('tacticsEdit');trigger(edit,'Edit arrows');
  edit.addEventListener('click',()=>menu(edit,[
    {label:'Draw arrows',checked:!removeMode,action:()=>{removeMode=false;}},
    {label:'Remove arrow',checked:removeMode,action:()=>{removeMode=true;}},
    {separator:true},{label:'Clear arrows',action:()=>{if(saveArrows({}))window.renderCMArrows();}}
  ]));
  const controls=document.querySelectorAll('.tactics-controls .tactics-control');
  trigger(controls[0],'Tactics pages');controls[0].addEventListener('click',()=>menu(controls[0],[{label:'Revealed formation',action:()=>{removeMode=false;}},{label:'Squad',action:()=>showClub(TACTICS_ENTRY,'news')}]));
  trigger(controls[1],'Tactics view');controls[1].addEventListener('click',()=>menu(controls[1],[{label:'Arrows',checked:arrowsVisible,action:()=>{arrowsVisible=!arrowsVisible;window.renderCMArrows();}}]));
  function playerOrigin(player){
    const field=pitch.getBoundingClientRect(),dot=player.querySelector('.pitch-dot').getBoundingClientRect();
    return {x:((dot.left+dot.width/2)-field.left)/field.width*100,y:((dot.top+dot.height/2)-field.top)/field.height*100};
  }
  function bounded(origin,vector){
    const field=pitch.getBoundingClientRect();vector=CMRules.limitArrow(vector,field.width,field.height);
    const scales=[1];
    for(const [p,d] of [[origin.x,vector[0]],[origin.y,vector[1]]])if(d)scales.push((d>0?97-p:3-p)/d);
    const scale=Math.max(0,Math.min(...scales));return vector.map(v=>v*scale);
  }
  function removeArrow(id){const arrows=readArrows();delete arrows[id];if(saveArrows(arrows))window.renderCMArrows();}
  pitch.addEventListener('pointerdown',e=>{
    const player=e.target.closest('.pitch-player');if(!player||e.button!==0||removeMode)return;
    e.preventDefault();player.focus({preventScroll:true});suppressClick=false;
    player.setPointerCapture(e.pointerId);
    const field=pitch.getBoundingClientRect(),origin=playerOrigin(player);
    activeDrag={id:player.dataset.player,x:e.clientX,y:e.clientY,origin,vector:null};
    const move=ev=>{
      if(!activeDrag)return;
      const vector=CMRules.arrowVector(ev.clientX-activeDrag.x,ev.clientY-activeDrag.y,field.width,field.height);
      if(!vector)return;
      activeDrag.vector=bounded(origin,vector);arrowsVisible=true;pitch.classList.add('dragging');
      window.renderCMArrows({id:activeDrag.id,vector:activeDrag.vector});
    };
    const stop=ev=>{
      if(!activeDrag)return;
      const drag=activeDrag;activeDrag=null;cleanup();
      if(ev.type==='pointerup'&&drag.vector){const arrows=readArrows();arrows[drag.id]=drag.vector;saveArrows(arrows);suppressClick=true;}
      window.renderCMArrows();
    };
    const cancel=ev=>{if(ev.key==='Escape'){ev.preventDefault();suppressClick=true;stop({type:'pointercancel'});}};
    function cleanup(){pitch.classList.remove('dragging');player.removeEventListener('pointermove',move);player.removeEventListener('pointerup',stop);player.removeEventListener('pointercancel',stop);player.removeEventListener('lostpointercapture',stop);document.removeEventListener('keydown',cancel,true);}
    player.addEventListener('pointermove',move);player.addEventListener('pointerup',stop);player.addEventListener('pointercancel',stop);player.addEventListener('lostpointercapture',stop);document.addEventListener('keydown',cancel,true);
  });
  pitch.addEventListener('click',e=>{
    const player=e.target.closest('.pitch-player');if(!player)return;
    if(suppressClick){suppressClick=false;return;}
    if(removeMode){removeArrow(player.dataset.player);return;}
    showPlayer(Number(player.dataset.player),TACTICS_SQUAD?.event?.id||null);
  });
  window.renderCMArrows=(preview=null)=>{
    pitch.querySelectorAll('.pitch-player').forEach(player=>{
      player.tabIndex=0;player.setAttribute('role','button');
      player.setAttribute('aria-label',(player.querySelector('.pitch-name')?.textContent||'Player')+'. Click for profile; drag to draw arrow.');
      player.title='Click for profile. Drag for arrow. Shift + arrow key or numpad direction draws; Delete removes.';
      player.onkeydown=e=>{
        if(e.key==='Enter'||e.key===' '){e.preventDefault();player.click();return;}
        if(e.key==='Delete'||e.key==='Backspace'){e.preventDefault();removeArrow(player.dataset.player);return;}
        const cardinal={ArrowUp:[0,-13],ArrowDown:[0,13],ArrowLeft:[-13,0],ArrowRight:[13,0]};
        const numpad={Numpad1:[-13,13],Numpad2:[0,13],Numpad3:[13,13],Numpad4:[-13,0],Numpad6:[13,0],Numpad7:[-13,-13],Numpad8:[0,-13],Numpad9:[13,-13]};
        const vector=(e.shiftKey&&cardinal[e.key])||numpad[e.code];
        if(vector){e.preventDefault();const arrows=readArrows();arrows[player.dataset.player]=bounded(playerOrigin(player),vector);arrowsVisible=true;if(saveArrows(arrows))window.renderCMArrows();}
      };
    });
    pitch.querySelector('.pitch-arrows')?.remove();if(!arrowsVisible)return;
    const ns='http://www.w3.org/2000/svg',svg=document.createElementNS(ns,'svg');svg.classList.add('pitch-arrows');svg.setAttribute('viewBox','0 0 100 100');svg.setAttribute('preserveAspectRatio','none');svg.setAttribute('aria-hidden','true');
    const arrows=readArrows();if(preview)arrows[preview.id]=preview.vector;
    for(const player of pitch.querySelectorAll('.pitch-player')){
      let d=arrows[player.dataset.player];if(!Array.isArray(d)||d.length!==2||!d.every(n=>typeof n==='number'&&Number.isFinite(n)&&Math.abs(n)<=100))continue;
      const origin=playerOrigin(player),vector=bounded(origin,d),x=origin.x,y=origin.y,ex=x+vector[0],ey=y+vector[1];
      const line=document.createElementNS(ns,'path');line.setAttribute('d',`M ${x} ${y} L ${ex} ${ey}`);line.setAttribute('stroke','#fff');line.setAttribute('stroke-width','.45');line.setAttribute('stroke-dasharray','1 .6');line.setAttribute('fill','none');svg.append(line);
      const field=pitch.getBoundingClientRect(),ratio=field.width/field.height,angle=Math.atan2(vector[1],vector[0]*ratio),size=2;
      const head=document.createElementNS(ns,'path');head.setAttribute('d',`M ${ex-size*Math.cos(angle-.6)/ratio} ${ey-size*Math.sin(angle-.6)} L ${ex} ${ey} L ${ex-size*Math.cos(angle+.6)/ratio} ${ey-size*Math.sin(angle+.6)}`);head.setAttribute('stroke','#fff');head.setAttribute('stroke-width','.45');head.setAttribute('fill','none');svg.append(head);
    }
    pitch.prepend(svg);
  };
})();
