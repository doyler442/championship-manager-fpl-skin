/* Pure presentation rules, shared by the browser and isolated tests. */
(function(root){
  'use strict';
  const dayKey=(ms,timezone)=>new Intl.DateTimeFormat('en-CA',{timeZone:timezone,year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date(ms));
  function initialStory(stories,readIds,lastId,timezone,now=Date.now()){
    const unread=stories.filter(s=>!readIds.has(String(s.id)));
    const today=dayKey(now,timezone);
    const todayStories=stories.filter(s=>Number(s.sortTs)>0&&dayKey(Number(s.sortTs)*1000,timezone)===today);
    const current=todayStories.filter(s=>!readIds.has(String(s.id)));
    const oldest=list=>list.slice().sort((a,b)=>(Number(a.sortTs)||0)-(Number(b.sortTs)||0))[0];
    if(todayStories.length)return (oldest(current)||oldest(todayStories))?.id||null;
    return (oldest(unread)||stories.find(s=>s.id===lastId)||stories[0])?.id||null;
  }
  function isReadEdition(story,days,firstSeen,timezone){
    if(!story||!(Number(story.sortTs)>0))return true;
    const storyMs=Number(story.sortTs)*1000,day=dayKey(storyMs,timezone);
    if(Array.isArray(days))return days.includes(day);
    // Legacy flags predate edition tracking. A story redated after its first
    // observed calendar day must not inherit that earlier day's read flag.
    return !Number(firstSeen)||storyMs<=Number(firstSeen)||day===dayKey(Number(firstSeen),timezone);
  }
  function finances(history,eventId){
    if(!history)return [['Financial data','Not available for this gameweek']];
    const money=v=>v==null||!Number.isFinite(Number(v))?'—':'£'+(Number(v)/10).toFixed(1)+'m';
    const rows=[['Financial snapshot',eventId?'Gameweek '+eventId:'Latest revealed gameweek'],['Squad Value',money(history.value)],['Money in Bank',money(history.bank)]];
    if(history.value!=null&&history.bank!=null)rows.push(['Total Value',money(Number(history.value)+Number(history.bank))]);
    rows.push(['Gameweek Transfers',history.event_transfers??'—'],['Transfer Points Cost',history.event_transfers_cost==null?'—':history.event_transfers_cost+' pts']);
    return rows;
  }
  function leagueZones(index,count){return {after:(index===0||index===2)&&index<count-1,before:index===count-1&&count>1&&index-1!==0&&index-1!==2};}
  const positionLabel=position=>({GKP:'GK',DEF:'DEF',MID:'MID',FWD:'FWD'}[position]||'—');
  // Approximate reference length: at most 13% of pitch height in any direction.
  function limitArrow(vector,width,height){
    const length=Math.hypot(vector[0]*width/100,vector[1]*height/100),scale=length?Math.min(1,height*.13/length):1;
    return vector.map(n=>n*scale);
  }
  function arrowVector(dx,dy,width,height){
    if(!width||!height||Math.hypot(dx,dy)<8)return null;
    const angle=Math.round(Math.atan2(dy,dx)/(Math.PI/4))*Math.PI/4,length=Math.min(Math.hypot(dx,dy),height*.13);
    return [Math.cos(angle)*length/width*100,Math.sin(angle)*length/height*100].map(n=>Math.round(n*100)/100);
  }
  function fullPlayerName(player){return [player?.firstName,player?.lastName].filter(Boolean).join(' ').trim()||player?.name||'';}
  function playerFileName(player){return player?.lastName&&player?.firstName?player.lastName+', '+player.firstName:fullPlayerName(player);}
  function restoreTodayUnread(stories,ids,timezone,now=Date.now()){
    const today=dayKey(now,timezone),restored=new Set(ids);
    stories.filter(s=>Number(s.sortTs)>0&&dayKey(Number(s.sortTs)*1000,timezone)===today).forEach(s=>restored.delete(String(s.id)));
    return restored;
  }
  function fixtureSides(manager,row){
    return row.home?{first:manager||{},second:row.opponent||{},firstScore:Number(row.forPoints)||0,secondScore:Number(row.againstPoints)||0}:{first:row.opponent||{},second:manager||{},firstScore:Number(row.againstPoints)||0,secondScore:Number(row.forPoints)||0};
  }
  function awardHistory(payload,season){
    const records=(payload?.months||[]).filter(m=>m.winner).map(m=>({date:m.label||'',award:'Manager of the Month',winner:m.winner}));
    if(payload?.seasonFinal&&payload?.seasonChampion)records.push({date:season,award:'League Winner',winner:payload.seasonChampion});
    return records;
  }
  const rules={dayKey,initialStory,isReadEdition,finances,leagueZones,positionLabel,arrowVector,limitArrow,fullPlayerName,fixtureSides,awardHistory,playerFileName,restoreTodayUnread};
  if(typeof module!=='undefined')module.exports=rules;else root.CMRules=rules;
})(typeof window!=='undefined'?window:globalThis);
