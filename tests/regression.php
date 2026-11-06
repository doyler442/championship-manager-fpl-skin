<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('FPL_TESTING', true);
putenv('FPL_CACHE_DIR=' . sys_get_temp_dir() . '/fpl-regression-' . bin2hex(random_bytes(6)));
require dirname(__DIR__) . '/api.php';
$checks=0;
function check(bool $ok,string $message): void {global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
try {
    $original=$config;
    $original['userEntryId']=101;$original['coinTossWinners']=['2026-09'=>101];
    $other=apply_league_override($original,'12345');
    check($other['leagueId']===12345 && $other['userEntryId']===0 && $other['coinTossWinners']===[], 'Different league clears league-specific preferences');
    check($original['leagueId']===$config['leagueId'] && $original['userEntryId']===101, 'Visitor choice must not mutate deployment settings');
    check(apply_league_override($original,(string)$original['leagueId'])===$original, 'Same league preserves host preferences');
    foreach(['0','-1','1e4','01','2147483648','123\n',[],123,null,'https://example.com'] as $invalid){
        $rejected=false;try{apply_league_override($original,$invalid);}catch(InvalidArgumentException $e){$rejected=true;}
        check($rejected,'Malformed visitor league must be rejected');
    }
    $a = cache_file('standings');
    $config['leagueId']++;
    check($a !== cache_file('standings'), 'Cache must be separated by league');
    $config['leagueId']--;
    $config['season']='2027/28';
    check($a !== cache_file('standings'), 'Cache must be separated by season');
    $config['season']='2026/27';
    cached_value('outage-test',60,fn()=>['data'=>['score'=>7],'source'=>'LIVE FPL']);
    touch(cache_file('outage-test'),time()-120);clearstatcache();
    $stale=cached_value('outage-test',60,function(){throw new RuntimeException('Offline');});
    check($stale['source']==='STALE CACHE' && $stale['data']['score']===7 && isset($stale['cachedAt']), 'Outage must use labelled, bounded real cache');
    touch(cache_file('outage-test'),time()-86401);clearstatcache();$thrown=false;
    try{cached_value('outage-test',60,function(){throw new RuntimeException('Offline');});}catch(RuntimeException $e){$thrown=true;}
    check($thrown,'Expired stale cache must fail instead of inventing data');
    $events=[['id'=>1,'deadline_time'=>'2026-09-01T12:00:00Z','finished'=>true],['id'=>2,'deadline_time'=>'2026-09-08T12:00:00Z','finished'=>false,'is_current'=>true]];
    $managers=[['entry'=>101,'player_name'=>'Test A','entry_name'=>'Test Club A','rank'=>1,'matches_played'=>1],['entry'=>102,'player_name'=>'Test B','entry_name'=>'Test Club B','rank'=>2,'matches_played'=>1]];
    $matches=[['id'=>1,'event'=>1,'entry_1_entry'=>101,'entry_2_entry'=>102,'entry_1_points'=>50,'entry_2_points'=>40],['id'=>2,'event'=>2,'entry_1_entry'=>101,'entry_2_entry'=>102,'entry_1_points'=>0,'entry_2_points'=>100]];
    cached_value('bootstrap',0,fn()=>['data'=>['events'=>$events,'elements'=>[],'teams'=>[]],'source'=>'LIVE FPL']);
    cached_value('standings',0,fn()=>['data'=>['league'=>['name'=>'Regression only','scoring'=>'h'],'standings'=>['results'=>$managers],'new_entries'=>['results'=>[]]],'source'=>'LIVE FPL']);
    cached_value('matches:all',0,fn()=>['data'=>['results'=>$matches],'source'=>'LIVE FPL']);
    $table=league_payload()['managers'];
    check($table[0]['pointsAgainst']===40 && $table[0]['pointsDifference']===10,'Points against/difference must come from completed fixtures');
    $award=awards_payload()['months'][0];
    $byId=array_column($award['table'],null,'entry');
    check($byId[101]['played']===1 && $byId[101]['h2hPoints']===3 && $byId[101]['fplPoints']===50,'Only finished gameweeks count in awards');
    check($byId[102]['h2hPoints']===0 && $byId[102]['fplPoints']===40,'Live scores must not affect monthly awards');
    check($award['final']===false && $award['winner']===null,'Unfinished month must not declare a winner');
    $matches[0]['entry_2_points']=50;
    cached_value('matches:all',0,fn()=>['data'=>['results'=>$matches],'source'=>'LIVE FPL']);
    $byId=array_column(awards_payload()['months'][0]['table'],null,'entry');
    check($byId[101]['h2hPoints']===1 && $byId[102]['h2hPoints']===1,'A draw awards one point each');
    $events[1]['finished']=true;
    $matches[1]['entry_1_points']=0;$matches[1]['entry_2_points']=0;
    cached_value('bootstrap',0,fn()=>['data'=>['events'=>$events,'elements'=>[],'teams'=>[]],'source'=>'LIVE FPL']);
    cached_value('matches:all',0,fn()=>['data'=>['results'=>$matches],'source'=>'LIVE FPL']);
    $award=awards_payload()['months'][0];
    check($award['final']===true && $award['coinTossRequired']===true && $award['winner']===null,'A final dead heat requires a recorded coin toss');
    $config['coinTossWinners']['2026-09']=102;
    check(awards_payload()['months'][0]['winner']['entry']===102,'Configured tied winner must be respected');
    cached_value('bootstrap',0,fn()=>['data'=>['events'=>[],'elements'=>[],'teams'=>[]],'source'=>'LIVE FPL']);
    cached_value('standings',0,fn()=>['data'=>['league'=>['scoring'=>'h'],'standings'=>['results'=>[]],'new_entries'=>['results'=>[]]],'source'=>'LIVE FPL']);
    cached_value('matches:all',0,fn()=>['data'=>['results'=>[]],'source'=>'LIVE FPL']);
    check(awards_payload()['months']===[] && league_payload()['managers']===[],'Empty preseason must be handled');
    echo "PASS: {$checks} regression checks\n";
} finally {
    foreach(glob($CACHE_DIR.'/*')?:[] as $file)unlink($file);
    rmdir($CACHE_DIR);
}
