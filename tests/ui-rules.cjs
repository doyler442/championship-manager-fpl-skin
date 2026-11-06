const assert=require('node:assert/strict');
const {initialStory,finances,leagueZones,positionLabel,arrowVector,limitArrow}=require('../ui-rules.js');
const now=Date.parse('2026-10-31T11:30:00Z');
const stories=[{id:'read',sortTs:now/1000},{id:'old',sortTs:now/1000-86400},{id:'today',sortTs:now/1000-200}];
assert.equal(initialStory(stories,new Set(['read']),null,'Pacific/Auckland',now),'today');
assert.equal(initialStory(stories,new Set(['read','today']),null,'Pacific/Auckland',now),'today');
assert.equal(initialStory(stories,new Set(['read','today','old']),'today','Pacific/Auckland',now),'today');
assert.equal(initialStory([],new Set(),null,'Pacific/Auckland',now),null);
// Around midnight, the calendar date belongs to the configured league timezone.
assert.equal(initialStory([{id:'yesterday',sortTs:now/1000-3600},{id:'today',sortTs:now/1000}],new Set(),null,'Pacific/Auckland',now),'today');
assert.deepEqual(finances({value:1023,bank:7,event_transfers:2,event_transfers_cost:4},8),[['Financial snapshot','Gameweek 8'],['Squad Value','£102.3m'],['Money in Bank','£0.7m'],['Total Value','£103.0m'],['Gameweek Transfers',2],['Transfer Points Cost','4 pts']]);
assert.equal(finances({value:1000},8).find(r=>r[0]==='Money in Bank')[1],'—');
assert.equal(finances(null,8)[0][0],'Financial data');
assert.deepEqual([0,1,2,3].map(i=>leagueZones(i,4)),[{after:true,before:false},{after:false,before:false},{after:true,before:false},{after:false,before:false}]);
assert.deepEqual(leagueZones(0,1),{after:false,before:false});
assert.deepEqual(leagueZones(9,10),{after:false,before:true});
assert.equal(initialStory([{id:'new',sortTs:now/1000},{id:'early',sortTs:now/1000-200},{id:'old',sortTs:now/1000-86400}],new Set(),null,'Pacific/Auckland',now),'early');
assert.deepEqual(['GKP','DEF','MID','FWD'].map(positionLabel),['GK','DEF','MID','FWD']);
assert.equal(positionLabel('D'),'—');
assert.equal(arrowVector(2,2,300,400),null);
assert.deepEqual(arrowVector(0,-80,300,400),[0,-13]);
const diagonal=arrowVector(60,-60,300,400);assert(diagonal[0]>0&&diagonal[1]<0);assert(Math.abs(diagonal[0]*3+diagonal[1]*4)<.1);
console.log('PASS: news selection, timezone, financial units/missing data and nonduplicated table separators');

for(let angle=0;angle<Math.PI*2;angle+=Math.PI/4){const v=arrowVector(Math.cos(angle)*600,Math.sin(angle)*600,300,400);assert(Math.hypot(v[0]*3,v[1]*4)<=52.05);const old=limitArrow([Math.cos(angle)*90,Math.sin(angle)*90],300,400);assert(Math.hypot(old[0]*3,old[1]*4)<=52.0001);}
const {fullPlayerName,fixtureSides,awardHistory}=require('../ui-rules.js');
assert.equal(fullPlayerName({firstName:'Full',lastName:'Name',name:'Short'}),'Full Name');assert.equal(fullPlayerName({name:'Fallback'}),'Fallback');assert.equal(fullPlayerName(null),'');
const self={teamName:'Selected'},opponent={teamName:'Opponent'},row={home:false,opponent,forPoints:12,againstPoints:21};assert.deepEqual(fixtureSides(self,row),{first:opponent,second:self,firstScore:21,secondScore:12});assert.equal(fixtureSides(self,{...row,home:true}).first,self);
const winner={managerName:'Test manager',teamName:'Test team'},awards={months:[{label:'August',winner},{label:'September',winner:null}],seasonChampion:winner};
assert.deepEqual(awardHistory(awards,'2026/27'),[{date:'August',award:'Manager of the Month',winner}]);assert.equal(awardHistory({...awards,seasonFinal:true},'2026/27').at(-1).award,'League Winner');assert.equal(awardHistory({seasonFinal:false,seasonChampion:winner},'2026/27').length,0);
console.log('PASS: full names, football home/away order and named monthly/final league awards');

const {playerFileName,restoreTodayUnread}=require('../ui-rules.js');
assert.equal(playerFileName({firstName:'Lee',lastName:'Allen'}),'Allen, Lee');
assert.deepEqual([...restoreTodayUnread(stories,new Set(['today','old']), 'Pacific/Auckland',now)],['old']);
assert.equal(initialStory(stories,new Set(['today','read']), 'today','Pacific/Auckland',now),'today');

const {isReadEdition}=require('../ui-rules.js');
const ukNow=Date.parse('2026-11-01T07:00:00Z'),seenYesterday=Date.parse('2026-10-31T20:00:00Z');
const redated={id:'stable-generated-id',sortTs:ukNow/1000};
assert.equal(isReadEdition(redated,undefined,seenYesterday,'Europe/Dublin'),false);
assert.equal(isReadEdition(redated,['2026-10-31'],seenYesterday,'Europe/Dublin'),false);
assert.equal(isReadEdition(redated,['2026-11-01'],seenYesterday,'Europe/Dublin'),true);
assert.equal(isReadEdition({...redated,sortTs:seenYesterday/1000},undefined,ukNow,'Europe/Dublin'),true);
assert.equal(isReadEdition(redated,undefined,ukNow+60000,'Europe/Dublin'),true);
const ukStories=[{id:'latest',sortTs:ukNow/1000},{id:'early',sortTs:ukNow/1000-3600},{id:'oct',sortTs:seenYesterday/1000}];
assert.equal(initialStory(ukStories,new Set(ukStories.map(s=>s.id)),'latest','Europe/Dublin',ukNow),'early');
// NZ has crossed midnight here; the configured UK calendar still controls today.
const boundary=Date.parse('2026-10-31T12:30:00Z');
assert.equal(require('../ui-rules.js').dayKey(boundary,'Europe/Dublin'),'2026-10-31');
assert.equal(require('../ui-rules.js').dayKey(boundary,'Pacific/Auckland'),'2026-11-01');
console.log('PASS: daily story editions, legacy read dates and oldest-today fallback across UK/NZ midnight');
