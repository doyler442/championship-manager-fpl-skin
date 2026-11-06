<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

const FPL_BASE = 'https://fantasy.premierleague.com/api';
const APP_UA = 'Fantasy Premier League Manager/3.0';
const BBC_FOOTBALL_RSS = 'http://newsrss.bbc.co.uk/rss/sportonline_uk_edition/football/rss.xml';
const GUARDIAN_FOOTBALL_RSS = 'https://www.theguardian.com/football/rss';
const SKY_FOOTBALL_SITEMAP = 'https://www.skysports.com/sitemap_news_football.xml';

try {
    require __DIR__ . '/runtime.php';
} catch (InvalidArgumentException $e) {
    respond(400, ['error'=>$e->getMessage()]);
} catch (Throwable $e) {
    respond(503, ['error' => 'Configuration unavailable. Check the server configuration and CONFIGURATION.md.']);
}

function respond(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function clean_text(mixed $value): string {
    $s = (string)($value ?? '');
    // Strip common emoji/pictographic ranges and variation/joiner marks.
    $s = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $s) ?? $s;
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    return trim($s);
}

function num_or_null(mixed $v): ?float {
    if ($v === null || $v === '') return null;
    return is_numeric($v) ? (float)$v : null;
}

function http_json(string $url, int $timeout = 9): array {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: ' . APP_UA],
            CURLOPT_ENCODING => '',
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        $ch = null;
        if ($body === false || $err) throw new RuntimeException('FPL request failed: ' . ($err ?: 'unknown error'));
        if ($status < 200 || $status >= 300) throw new RuntimeException("FPL HTTP {$status}", $status);
        $json = json_decode((string)$body, true);
        if (!is_array($json)) throw new RuntimeException('FPL returned invalid JSON');
        return $json;
    }

    throw new RuntimeException('PHP cURL is required');
}

function get_bootstrap(): array {
    return cached_value('bootstrap', 300, fn() => ['data' => http_json(FPL_BASE . '/bootstrap-static/'), 'source' => 'LIVE FPL']);
}

function get_standings_raw(): array {
    global $config;
    return cached_value('standings', 60, function() use ($config) {
        $leagueId = $config['leagueId'];
        $all = null;
        for ($page = 1; $page <= $config['maxLeaguePages']; $page++) {
            $data = http_json(FPL_BASE . "/leagues-h2h/{$leagueId}/standings/?page_standings={$page}&page_new_entries={$page}");
            if (!isset($data['league']) || ($data['league']['scoring'] ?? '') !== 'h') throw new RuntimeException('A readable FPL head-to-head league is required');
            if ($all === null) $all = $data;
            else foreach (['standings', 'new_entries'] as $section) {
                $all[$section]['results'] = array_merge($all[$section]['results'] ?? [], $data[$section]['results'] ?? []);
            }
            if (empty($data['standings']['has_next']) && empty($data['new_entries']['has_next'])) {
                foreach (['standings', 'new_entries'] as $section) $all[$section]['has_next'] = false;
                return ['data' => $all, 'source' => 'LIVE FPL'];
            }
        }
        throw new RuntimeException('League exceeds maxLeaguePages; increase the limit in local configuration');
    });
}

function get_matches_raw(?int $event = null): array {
    global $config;
    return cached_value('matches:' . ($event ?: 'all'), $event ? 45 : 300, function() use ($config, $event) {
        $leagueId = $config['leagueId'];
        $all = [];
        for ($page = 1; $page <= $config['maxLeaguePages']; $page++) {
            $url = FPL_BASE . "/leagues-h2h-matches/league/{$leagueId}/?page={$page}";
            if ($event) $url .= '&event=' . $event;
            $data = http_json($url);
            $all = array_merge($all, $data['results'] ?? []);
            if (empty($data['has_next'])) return ['data' => ['results' => $all], 'source' => 'LIVE FPL'];
        }
        throw new RuntimeException('Fixture list exceeds maxLeaguePages; increase the limit in local configuration');
    });
}

function get_entry(int $entryId): array {
    return cached_value("entry:{$entryId}", 300, function() use ($entryId) {
        try { return ['data' => http_json(FPL_BASE . "/entry/{$entryId}/"), 'source' => 'LIVE FPL']; }
        catch (Throwable $e) { return ['data' => null, 'source' => 'UNAVAILABLE', 'error' => $e->getMessage()]; }
    });
}

function get_picks(int $entryId, int $eventId): array {
    return cached_value("picks:{$entryId}:{$eventId}", 60, function() use ($entryId, $eventId) {
        try { return ['data' => http_json(FPL_BASE . "/entry/{$entryId}/event/{$eventId}/picks/"), 'source' => 'LIVE FPL']; }
        catch (Throwable $e) { return ['data' => null, 'source' => 'UNAVAILABLE', 'error' => $e->getMessage(), 'status' => $e->getCode() ?: null]; }
    });
}

function get_live(int $eventId): array {
    return cached_value("live:{$eventId}", 30, function() use ($eventId) {
        try { return ['data' => http_json(FPL_BASE . "/event/{$eventId}/live/"), 'source' => 'LIVE FPL']; }
        catch (Throwable $e) { return ['data' => null, 'source' => 'UNAVAILABLE', 'error' => $e->getMessage()]; }
    });
}

function get_element_summary(int $playerId): array {
    return cached_value("element-summary:{$playerId}", 300, function() use ($playerId) {
        try { return ['data' => http_json(FPL_BASE . "/element-summary/{$playerId}/"), 'source' => 'LIVE FPL']; }
        catch (Throwable $e) { return ['data' => ['fixtures'=>[], 'history'=>[], 'history_past'=>[]], 'source' => 'UNAVAILABLE', 'error' => $e->getMessage()]; }
    });
}

function http_text(string $url, string $accept = '*/*', int $timeout = 10): array {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => ['Accept: ' . $accept, 'User-Agent: ' . APP_UA],
            CURLOPT_ENCODING => '',
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        $ch = null;
        if ($body === false || $err) throw new RuntimeException('News request failed: ' . ($err ?: 'unknown error'));
        if ($status < 200 || $status >= 400) throw new RuntimeException("News HTTP {$status}", $status);
        return ['body'=>(string)$body,'status'=>$status];
    }

    throw new RuntimeException('PHP cURL is required');
}

function news_host_allowed(string $url, array $domains): bool {
    $host = strtolower((string)parse_url($url, PHP_URL_HOST));
    if ($host === '') return false;
    foreach ($domains as $domain) {
        $domain = strtolower($domain);
        if ($host === $domain || str_ends_with($host, '.' . $domain)) return true;
    }
    return false;
}

function parse_rss_news(string $xmlText, array $allowedDomains, int $limit = 80): array {
    if (!function_exists('simplexml_load_string')) throw new RuntimeException('PHP SimpleXML is not available');
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xmlText, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($xml === false || !isset($xml->channel)) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        $message = $errors ? trim((string)$errors[0]->message) : 'Could not parse RSS';
        throw new RuntimeException($message);
    }

    $stories = [];
    foreach ($xml->channel->item as $item) {
        $title = clean_text(html_entity_decode((string)$item->title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $link = trim((string)$item->link);
        $pubDate = trim((string)$item->pubDate);
        $description = clean_text(html_entity_decode(strip_tags((string)$item->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($title === '' || $link === '' || !news_host_allowed($link, $allowedDomains)) continue;
        $ts = $pubDate !== '' ? strtotime($pubDate) : false;
        $stories[] = [
            'title'=>$title,
            'url'=>$link,
            'publishedAt'=>$ts !== false ? gmdate('c', $ts) : null,
            'summary'=>$description,
        ];
        if (count($stories) >= $limit) break;
    }
    return $stories;
}

function parse_sky_football_sitemap(string $xmlText, int $limit = 120): array {
    if (!function_exists('simplexml_load_string')) throw new RuntimeException('PHP SimpleXML is not available');
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xmlText, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($xml === false) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        $message = $errors ? trim((string)$errors[0]->message) : 'Could not parse Sky football sitemap';
        throw new RuntimeException($message);
    }
    $ns = $xml->getNamespaces(true);
    $newsNs = $ns['news'] ?? 'http://www.google.com/schemas/sitemap-news/0.9';
    $stories = [];
    foreach ($xml->url as $urlNode) {
        $link = trim((string)$urlNode->loc);
        if ($link === '' || !news_host_allowed($link, ['skysports.com'])) continue;
        $news = $urlNode->children($newsNs);
        $title = clean_text(html_entity_decode((string)($news->news->title ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $date = trim((string)($news->news->publication_date ?? ''));
        $keywords = clean_text((string)($news->news->keywords ?? ''));
        if ($title === '') continue;
        $ts = $date !== '' ? strtotime($date) : false;
        $stories[] = [
            'title'=>$title,
            'url'=>$link,
            'publishedAt'=>$ts !== false ? gmdate('c', $ts) : null,
            'summary'=>$keywords,
        ];
        if (count($stories) >= $limit) break;
    }
    usort($stories, fn($a,$b)=>strcmp((string)($b['publishedAt'] ?? ''),(string)($a['publishedAt'] ?? '')));
    return $stories;
}

function current_pl_news_terms(): array {
    $terms = [
        'premier league','champions league','uefa champions league','europa league','uefa europa league',
        'conference league','uefa conference league','super cup'
    ];
    try {
        $bootstrap = get_bootstrap()['data'] ?? [];
        foreach ($bootstrap['teams'] ?? [] as $team) {
            $name = strtolower(clean_text($team['name'] ?? ''));
            if ($name !== '') $terms[] = $name;
            $short = strtolower(clean_text($team['short_name'] ?? ''));
            if ($short !== '' && strlen($short) > 3) $terms[] = $short;
            $aliases = match ($name) {
                'manchester united' => ['man utd','man united'],
                'manchester city' => ['man city'],
                'tottenham hotspur' => ['tottenham','spurs'],
                'wolverhampton wanderers' => ['wolves','wolverhampton'],
                'nottingham forest' => ['nottm forest','forest'],
                'newcastle united' => ['newcastle'],
                'west ham united' => ['west ham'],
                'brighton and hove albion', 'brighton & hove albion' => ['brighton'],
                'afc bournemouth', 'bournemouth' => ['bournemouth'],
                'crystal palace' => ['crystal palace','palace'],
                'aston villa' => ['aston villa','villa'],
                'leeds united' => ['leeds'],
                'sunderland' => ['sunderland'],
                default => [],
            };
            $terms = array_merge($terms, $aliases);
        }
    } catch (Throwable $e) {}
    return array_values(array_unique(array_filter(array_map('strtolower', $terms))));
}

function filter_fpl_relevant_news(array $stories): array {
    $terms = current_pl_news_terms();
    $out = [];
    $seen = [];
    foreach ($stories as $story) {
        $hay = strtolower(clean_text(($story['title'] ?? '') . ' ' . ($story['summary'] ?? '')));
        $relevant = false;
        foreach ($terms as $term) {
            if ($term !== '' && str_contains($hay, $term)) { $relevant = true; break; }
        }
        if (!$relevant) continue;
        $key = strtolower(clean_text($story['title'] ?? ''));
        if ($key === '' || isset($seen[$key])) continue;
        $seen[$key] = true;
        $out[] = $story;
        if (count($out) >= 40) break;
    }
    return $out;
}

function news_source_config(string $source): ?array {
    return match ($source) {
        'bbc' => [
            'id'=>'bbc','name'=>'BBC SPORT','home'=>'https://www.bbc.com/sport/football',
            'url'=>BBC_FOOTBALL_RSS,'type'=>'rss','domains'=>['bbc.co.uk','bbc.com']
        ],
        'sky' => [
            'id'=>'sky','name'=>'SKY SPORTS','home'=>'https://www.skysports.com/football',
            'url'=>SKY_FOOTBALL_SITEMAP,'type'=>'sky-sitemap','domains'=>['skysports.com']
        ],
        'guardian' => [
            'id'=>'guardian','name'=>'THE GUARDIAN','home'=>'https://www.theguardian.com/football',
            'url'=>GUARDIAN_FOOTBALL_RSS,'type'=>'rss','domains'=>['theguardian.com']
        ],
        default => null,
    };
}

function get_news_source(string $source): array {
    $cfg = news_source_config($source);
    if (!$cfg) throw new InvalidArgumentException('News source not available');
    $file = cache_file('news:' . $source . ':pl-europe');
    $ttl = 300;

    if (is_file($file) && (time() - (int)filemtime($file) < $ttl)) {
        $raw = @file_get_contents($file);
        $cached = $raw !== false ? json_decode($raw, true) : null;
        if (is_array($cached) && isset($cached['stories'])) {
            $cached['delivery'] = 'CACHE';
            return $cached;
        }
    }

    try {
        $remote = http_text($cfg['url'], 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8', 12);
        $allStories = $cfg['type'] === 'sky-sitemap'
            ? parse_sky_football_sitemap($remote['body'])
            : parse_rss_news($remote['body'], $cfg['domains']);
        $stories = filter_fpl_relevant_news($allStories);
        $payload = [
            'sourceId'=>$cfg['id'],
            'sourceName'=>$cfg['name'],
            'sourceUrl'=>$cfg['home'],
            'focus'=>'PREMIER LEAGUE + EUROPE',
            'fetchedAt'=>gmdate('c'),
            'stories'=>$stories,
            'delivery'=>'LIVE',
        ];
        if (is_dir(dirname($file)) && is_writable(dirname($file))) {
            @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
        }
        return $payload;
    } catch (Throwable $e) {
        if (is_file($file)) {
            $raw = @file_get_contents($file);
            $cached = $raw !== false ? json_decode($raw, true) : null;
            if (is_array($cached) && isset($cached['stories'])) {
                $cached['delivery'] = 'STALE CACHE';
                $cached['warning'] = $e->getMessage();
                return $cached;
            }
        }
        throw $e;
    }
}

function timezone_obj(): DateTimeZone {
    global $config;
    try { return new DateTimeZone((string)($config['timezone'] ?? 'Europe/Dublin')); }
    catch (Throwable $e) { return new DateTimeZone('Europe/Dublin'); }
}

function event_month_key(?array $event): ?string {
    if (!$event || empty($event['deadline_time'])) return null;
    try {
        $dt = new DateTimeImmutable((string)$event['deadline_time']);
        return $dt->setTimezone(timezone_obj())->format('Y-m');
    } catch (Throwable $e) { return null; }
}

function month_label(string $key): string {
    [$year, $month] = array_map('intval', explode('-', $key));
    $dt = new DateTimeImmutable(sprintf('%04d-%02d-15 12:00:00', $year, $month), timezone_obj());
    return strtoupper($dt->format('F Y'));
}

function deadline_ts(?array $event): int {
    if (!$event || empty($event['deadline_time'])) return PHP_INT_MAX;
    try { return (new DateTimeImmutable((string)$event['deadline_time']))->getTimestamp(); }
    catch (Throwable $e) { return PHP_INT_MAX; }
}

function event_is_finished(?array $event): bool {
    return is_array($event) && (!empty($event['finished']) || !empty($event['data_checked']));
}

function events_by_id(array $events): array {
    $out = [];
    foreach ($events as $event) {
        $id = (int)($event['id'] ?? 0);
        if ($id > 0) $out[$id] = $event;
    }
    return $out;
}

function latest_finished_event_id(array $events): int {
    $latest = 0;
    foreach ($events as $event) {
        if (event_is_finished($event)) $latest = max($latest, (int)($event['id'] ?? 0));
    }
    return $latest;
}

function active_event(array $events, int $now): ?array {
    foreach ($events as $event) {
        if (!empty($event['is_current']) && !event_is_finished($event)) return $event;
    }
    $candidates = array_values(array_filter($events, fn($event) =>
        !event_is_finished($event)
        && !empty($event['deadline_time'])
        && deadline_ts($event) <= $now
    ));
    usort($candidates, fn($a,$b)=>(int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0));
    return $candidates[0] ?? null;
}

function latest_revealed_event(array $events): ?array {
    $now = time();
    $passed = array_values(array_filter($events, fn($e) => !empty($e['deadline_time']) && deadline_ts($e) <= $now));
    usort($passed, fn($a, $b) => ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0)));
    return $passed[0] ?? null;
}

function current_display_event(array $events): ?array {
    foreach ($events as $e) if (!empty($e['is_current'])) return $e;
    foreach ($events as $e) if (!empty($e['is_next'])) return $e;
    return latest_revealed_event($events) ?? ($events[0] ?? null);
}

function normalize_players(array $bootstrap): array {
    $teams = [];
    foreach ($bootstrap['teams'] ?? [] as $t) $teams[(int)$t['id']] = $t;
    $types = [];
    foreach ($bootstrap['element_types'] ?? [] as $t) $types[(int)$t['id']] = $t;
    $out = [];
    foreach ($bootstrap['elements'] ?? [] as $p) {
        $teamId = (int)($p['team'] ?? 0);
        $posId = (int)($p['element_type'] ?? 0);
        $out[] = [
            'id' => (int)($p['id'] ?? 0),
            'code' => $p['code'] ?? null,
            'webName' => clean_text($p['web_name'] ?? ''),
            'firstName' => clean_text($p['first_name'] ?? ''),
            'lastName' => clean_text($p['second_name'] ?? ''),
            'name' => clean_text($p['web_name'] ?? trim(($p['first_name'] ?? '') . ' ' . ($p['second_name'] ?? ''))),
            'teamId' => $teamId,
            'club' => $teams[$teamId]['short_name'] ?? (string)$teamId,
            'clubName' => clean_text($teams[$teamId]['name'] ?? ''),
            'positionId' => $posId,
            'position' => $types[$posId]['singular_name_short'] ?? ([1=>'GKP',2=>'DEF',3=>'MID',4=>'FWD'][$posId] ?? ''),
            'status' => $p['status'] ?? 'a',
            'news' => clean_text($p['news'] ?? ''),
            'chanceThis' => $p['chance_of_playing_this_round'] ?? null,
            'chanceNext' => $p['chance_of_playing_next_round'] ?? null,
            'price' => isset($p['now_cost']) && is_numeric($p['now_cost']) ? ((float)$p['now_cost'] / 10) : null,
            'costChangeEvent' => isset($p['cost_change_event']) ? ((float)$p['cost_change_event'] / 10) : 0.0,
            'costChangeStart' => isset($p['cost_change_start']) ? ((float)$p['cost_change_start'] / 10) : 0.0,
            'totalPoints' => (int)($p['total_points'] ?? 0),
            'eventPoints' => (int)($p['event_points'] ?? 0),
            'form' => (float)($p['form'] ?? 0),
            'pointsPerGame' => (float)($p['points_per_game'] ?? 0),
            'selectedBy' => (float)($p['selected_by_percent'] ?? 0),
            'transfersInEvent' => (int)($p['transfers_in_event'] ?? 0),
            'transfersOutEvent' => (int)($p['transfers_out_event'] ?? 0),
            'epThis' => (float)($p['ep_this'] ?? 0),
            'epNext' => (float)($p['ep_next'] ?? 0),
            'positionFull' => clean_text($types[$posId]['singular_name'] ?? ([1=>'Goalkeeper',2=>'Defender',3=>'Midfielder',4=>'Forward'][$posId] ?? '')),
            'minutes' => (int)($p['minutes'] ?? 0),
            'starts' => (int)($p['starts'] ?? 0),
            'goals' => (int)($p['goals_scored'] ?? 0),
            'assists' => (int)($p['assists'] ?? 0),
            'cleanSheets' => (int)($p['clean_sheets'] ?? 0),
            'goalsConceded' => (int)($p['goals_conceded'] ?? 0),
            'ownGoals' => (int)($p['own_goals'] ?? 0),
            'penaltiesSaved' => (int)($p['penalties_saved'] ?? 0),
            'penaltiesMissed' => (int)($p['penalties_missed'] ?? 0),
            'yellowCards' => (int)($p['yellow_cards'] ?? 0),
            'redCards' => (int)($p['red_cards'] ?? 0),
            'saves' => (int)($p['saves'] ?? 0),
            'bonus' => (int)($p['bonus'] ?? 0),
            'bps' => (int)($p['bps'] ?? 0),
            'influence' => (float)($p['influence'] ?? 0),
            'creativity' => (float)($p['creativity'] ?? 0),
            'threat' => (float)($p['threat'] ?? 0),
            'ictIndex' => (float)($p['ict_index'] ?? 0),
            'expectedGoals' => (float)($p['expected_goals'] ?? 0),
            'expectedAssists' => (float)($p['expected_assists'] ?? 0),
            'expectedGoalInvolvements' => (float)($p['expected_goal_involvements'] ?? 0),
            'expectedGoalsConceded' => (float)($p['expected_goals_conceded'] ?? 0),
            'defensiveContribution' => (int)($p['defensive_contribution'] ?? 0),
            'dreamteamCount' => (int)($p['dreamteam_count'] ?? 0),
        ];
    }
    return $out;
}

function normalize_managers(array $standingsRaw): array {
    $byId = [];
    $add = function(array $raw, bool $fromStandings = false) use (&$byId) {
        $entry = (int)($raw['entry'] ?? $raw['id'] ?? 0);
        if (!$entry) return;
        $prior = $byId[$entry] ?? [];
        $fullName = clean_text($raw['player_name'] ?? trim(($raw['player_first_name'] ?? '') . ' ' . ($raw['player_last_name'] ?? '')) ?: ($prior['managerName'] ?? ''));
        $byId[$entry] = [
            'entry' => $entry,
            'teamName' => clean_text($raw['entry_name'] ?? $prior['teamName'] ?? "ENTRY {$entry}"),
            'managerName' => $fullName ?: ($prior['managerName'] ?? 'UNKNOWN MANAGER'),
            'joinedTime' => $raw['joined_time'] ?? $prior['joinedTime'] ?? null,
            'rank' => (int)($raw['rank'] ?? $raw['rank_sort'] ?? $prior['rank'] ?? 0) ?: null,
            'played' => (int)($raw['matches_played'] ?? $raw['played'] ?? $prior['played'] ?? 0),
            'won' => (int)($raw['matches_won'] ?? $raw['won'] ?? $prior['won'] ?? 0),
            'drawn' => (int)($raw['matches_drawn'] ?? $raw['drawn'] ?? $prior['drawn'] ?? 0),
            'lost' => (int)($raw['matches_lost'] ?? $raw['lost'] ?? $prior['lost'] ?? 0),
            'pointsFor' => (int)($raw['points_for'] ?? $raw['event_total'] ?? $prior['pointsFor'] ?? 0),
            'pointsAgainst' => (int)($raw['points_against'] ?? $prior['pointsAgainst'] ?? 0),
            'h2hPoints' => (int)($raw['total'] ?? $raw['points'] ?? $prior['h2hPoints'] ?? 0),
            'fromStandings' => $fromStandings || ($prior['fromStandings'] ?? false),
        ];
    };
    foreach ($standingsRaw['new_entries']['results'] ?? [] as $r) $add($r, false);
    foreach ($standingsRaw['standings']['results'] ?? [] as $r) $add($r, true);
    $out = array_values($byId);
    usort($out, function($a, $b) {
        if ($a['rank'] && $b['rank']) return $a['rank'] <=> $b['rank'];
        if ($a['rank']) return -1;
        if ($b['rank']) return 1;
        if ($a['joinedTime'] && $b['joinedTime']) return strcmp((string)$a['joinedTime'], (string)$b['joinedTime']);
        return strcasecmp((string)$a['teamName'], (string)$b['teamName']);
    });
    return $out;
}

function normalize_match(array $m): array {
    $homeId = (int)($m['entry_1_entry'] ?? $m['entry_1'] ?? $m['home_entry'] ?? $m['home'] ?? 0);
    $awayId = (int)($m['entry_2_entry'] ?? $m['entry_2'] ?? $m['away_entry'] ?? $m['away'] ?? 0);
    return [
        'id' => $m['id'] ?? (($m['event'] ?? 0) . "-{$homeId}-{$awayId}"),
        'event' => (int)($m['event'] ?? 0),
        'homeId' => $homeId,
        'awayId' => $awayId,
        'homeName' => clean_text($m['entry_1_name'] ?? $m['home_name'] ?? ''),
        'awayName' => clean_text($m['entry_2_name'] ?? $m['away_name'] ?? ''),
        'homeManager' => clean_text($m['entry_1_player_name'] ?? ''),
        'awayManager' => clean_text($m['entry_2_player_name'] ?? ''),
        'homePoints' => num_or_null($m['entry_1_points'] ?? $m['home_points'] ?? $m['score_1'] ?? null),
        'awayPoints' => num_or_null($m['entry_2_points'] ?? $m['away_points'] ?? $m['score_2'] ?? null),
        'homeWin' => (int)($m['entry_1_win'] ?? 0) === 1,
        'awayWin' => (int)($m['entry_2_win'] ?? 0) === 1,
        'draw' => (int)($m['entry_1_draw'] ?? $m['entry_2_draw'] ?? 0) === 1,
        'raw' => $m,
    ];
}

function league_payload(): array {
    $standingsWrap = get_standings_raw();
    $bootstrapWrap = get_bootstrap();
    $managers = normalize_managers($standingsWrap['data'] ?? []);
    $events = $bootstrapWrap['data']['events'] ?? [];
    // FPL standings can report zero points_against; calculate Ag/Diff from completed fixtures.
    $eventMap = events_by_id($events);
    $totals = [];
    $matchSource = 'UNAVAILABLE';
    try {
        $matchWrap = get_matches_raw();
        $matchSource = $matchWrap['source'];
        foreach ($matchWrap['data']['results'] ?? [] as $raw) {
            if (!empty($raw['is_knockout']) || !empty($raw['is_bye'])) continue;
            $match = normalize_match($raw);
            if (!event_is_finished($eventMap[$match['event']] ?? null) || $match['homePoints'] === null || $match['awayPoints'] === null) continue;
            foreach ([[$match['homeId'], $match['homePoints'], $match['awayPoints']], [$match['awayId'], $match['awayPoints'], $match['homePoints']]] as [$id, $for, $against]) {
                $totals[$id] ??= ['for'=>0, 'against'=>0, 'played'=>0];
                $totals[$id]['for'] += (int)$for;
                $totals[$id]['against'] += (int)$against;
                $totals[$id]['played']++;
            }
        }
    } catch (Throwable $e) { /* Show unknown totals if the full fixture list is unavailable. */ }
    foreach ($managers as &$manager) {
        $t = $totals[$manager['entry']] ?? null;
        $complete = $t !== null && $t['played'] === $manager['played'];
        $manager['pointsAgainst'] = $complete ? $t['against'] : ($manager['played'] === 0 ? 0 : null);
        $manager['pointsDifference'] = $complete ? $t['for'] - $t['against'] : ($manager['played'] === 0 ? 0 : null);
    }
    unset($manager);
    return [
        'league' => $standingsWrap['data']['league'] ?? ['id' => ($GLOBALS['config']['leagueId'] ?? 0)],
        'managers' => $managers,
        'standingsReady' => count($standingsWrap['data']['standings']['results'] ?? []) > 0,
        'entrantCount' => count($managers),
        'currentEvent' => array_values(array_filter($events, fn($e) => !empty($e['is_current'])))[0] ?? null,
        'nextEvent' => array_values(array_filter($events, fn($e) => !empty($e['is_next'])))[0] ?? null,
        'displayEvent' => current_display_event($events),
        'latestRevealedEvent' => latest_revealed_event($events),
        'latestFinishedEvent' => (($lfid = latest_finished_event_id($events)) > 0 ? (($emap = events_by_id($events))[$lfid] ?? null) : null),
        'source' => (($standingsWrap['source'] ?? '') === 'LIVE FPL' && ($bootstrapWrap['source'] ?? '') === 'LIVE FPL' && $matchSource === 'LIVE FPL') ? 'LIVE FPL' : 'STALE DATA',
    ];
}

function fixtures_payload(int $eventId): array {
    $league = league_payload();
    $bootstrapWrap = get_bootstrap();
    $matchesWrap = get_matches_raw($eventId);
    $event = null;
    foreach ($bootstrapWrap['data']['events'] ?? [] as $e) if ((int)($e['id'] ?? 0) === $eventId) { $event = $e; break; }
    $byManager = [];
    foreach ($league['managers'] as $m) $byManager[(int)$m['entry']] = $m;
    $matches = [];
    foreach ($matchesWrap['data']['results'] ?? [] as $raw) {
        $m = normalize_match($raw);
        $m['home'] = $byManager[$m['homeId']] ?? ['entry'=>$m['homeId'],'teamName'=>$m['homeName'],'managerName'=>$m['homeManager'] ?: 'UNKNOWN MANAGER'];
        $m['away'] = $byManager[$m['awayId']] ?? ['entry'=>$m['awayId'],'teamName'=>$m['awayName'],'managerName'=>$m['awayManager'] ?: 'UNKNOWN MANAGER'];
        $m['status'] = !empty($event['finished']) ? 'FT' : (!empty($event['is_current']) ? 'LIVE' : 'PREVIEW');
        $matches[] = $m;
    }
    return ['event'=>$event,'matches'=>$matches,'source'=>$matchesWrap['source'] ?? 'FPL','fixturesReady'=>count($matches)>0];
}


function competition_schedule_payload(int $entryId): array {
    $league = league_payload();
    $bootstrapWrap = get_bootstrap();
    $allMatchesWrap = get_matches_raw(null);
    $events = $bootstrapWrap['data']['events'] ?? [];
    $eventMap = events_by_id($events);
    $byManager = [];
    foreach ($league['managers'] as $m) $byManager[(int)$m['entry']] = $m;
    $manager = $byManager[$entryId] ?? ['entry'=>$entryId,'teamName'=>"ENTRY {$entryId}",'managerName'=>'UNKNOWN MANAGER'];
    $schedule = [];
    foreach ($allMatchesWrap['data']['results'] ?? [] as $raw) {
        $m = normalize_match($raw);
        if ($m['homeId'] !== $entryId && $m['awayId'] !== $entryId) continue;
        $home = $m['homeId'] === $entryId;
        $oppId = $home ? $m['awayId'] : $m['homeId'];
        $event = $eventMap[(int)$m['event']] ?? null;
        $status = event_is_finished($event) ? 'FT' : (!empty($event['is_current']) ? 'LIVE' : 'PREVIEW');
        $schedule[] = [
            'event'=>(int)$m['event'],
            'deadlineTime'=>$event['deadline_time'] ?? null,
            'competitionName'=>clean_text($league['league']['name'] ?? 'H2H League'),
            'home'=>$home,
            'opponent'=>$byManager[$oppId] ?? ['entry'=>$oppId,'teamName'=>$home?$m['awayName']:$m['homeName'],'managerName'=>$home?$m['awayManager']:$m['homeManager']],
            'forPoints'=>$home?$m['homePoints']:$m['awayPoints'],
            'againstPoints'=>$home?$m['awayPoints']:$m['homePoints'],
            'status'=>$status,
        ];
    }
    usort($schedule, fn($a,$b)=>$a['event'] <=> $b['event']);
    return ['manager'=>$manager,'schedule'=>$schedule,'source'=>$allMatchesWrap['source'] ?? 'FPL'];
}


function manager_transfers_payload(int $entryId): array {
    global $config;
    $league = league_payload();
    $bootstrapWrap = get_bootstrap();
    $events = $bootstrapWrap['data']['events'] ?? [];
    $eventMap = [];
    foreach ($events as $e) $eventMap[(int)($e['id'] ?? 0)] = $e;

    $manager = null;
    foreach ($league['managers'] as $m) if ((int)($m['entry'] ?? 0) === $entryId) { $manager = $m; break; }
    $manager ??= ['entry'=>$entryId,'teamName'=>"ENTRY {$entryId}",'managerName'=>'UNKNOWN MANAGER'];

    $players = [];
    foreach (normalize_players($bootstrapWrap['data'] ?? []) as $p) $players[(int)$p['id']] = $p;

    $wrap = get_entry_transfers($entryId);
    $rows = [];
    $now = time();
    foreach (($wrap['data'] ?? []) as $tr) {
        $eventId = (int)($tr['event'] ?? 0);
        $event = $eventMap[$eventId] ?? null;
        // Never expose a transfer for a Gameweek whose deadline has not passed.
        if ($event && deadline_ts($event) > $now) continue;

        $inId = (int)($tr['element_in'] ?? 0);
        $outId = (int)($tr['element_out'] ?? 0);
        $in = $players[$inId] ?? ['id'=>$inId,'name'=>"PLAYER {$inId}",'club'=>'','clubName'=>''];
        $out = $players[$outId] ?? ['id'=>$outId,'name'=>"PLAYER {$outId}",'club'=>'','clubName'=>''];
        $rows[] = [
            'event'=>$eventId,
            'time'=>$tr['time'] ?? null,
            'playerIn'=>$in,
            'playerOut'=>$out,
            'inCost'=>isset($tr['element_in_cost']) && is_numeric($tr['element_in_cost']) ? ((float)$tr['element_in_cost']/10) : null,
            'outCost'=>isset($tr['element_out_cost']) && is_numeric($tr['element_out_cost']) ? ((float)$tr['element_out_cost']/10) : null,
        ];
    }
    usort($rows, function($a,$b){
        $ta = strtotime((string)($a['time'] ?? '')) ?: 0;
        $tb = strtotime((string)($b['time'] ?? '')) ?: 0;
        return ($tb <=> $ta) ?: ((int)$b['event'] <=> (int)$a['event']);
    });
    return [
        'manager'=>$manager,
        'season'=>$config['season'] ?? '2026/27',
        'transfers'=>$rows,
        'source'=>$wrap['source'] ?? 'FPL',
    ];
}


function league_transfers_payload(): array {
    $league = league_payload();
    $rows = [];
    $season = $GLOBALS['config']['season'] ?? '2026/27';
    foreach (($league['managers'] ?? []) as $m) {
        $entry = (int)($m['entry'] ?? 0);
        if (!$entry) continue;
        try { $payload = manager_transfers_payload($entry); }
        catch (Throwable $e) { continue; }
        foreach (($payload['transfers'] ?? []) as $tr) {
            $tr['entry'] = $entry;
            $tr['managerName'] = clean_text($m['managerName'] ?? '');
            $tr['teamName'] = clean_text($m['teamName'] ?? '');
            $rows[] = $tr;
        }
    }
    usort($rows, function($a,$b){
        $ev = ((int)($b['event'] ?? 0)) <=> ((int)($a['event'] ?? 0));
        if ($ev !== 0) return $ev;
        return (strtotime((string)($b['time'] ?? '')) ?: 0) <=> (strtotime((string)($a['time'] ?? '')) ?: 0);
    });
    return ['leagueName'=>clean_text($league['league']['name'] ?? 'H2H League'),'season'=>$season,'transfers'=>$rows,'source'=>$league['source'] ?? 'FPL'];
}

function squad_payload(int $entryId, ?int $requestedEvent = null): array {
    $bootstrapWrap = get_bootstrap();
    $events = $bootstrapWrap['data']['events'] ?? [];
    $revealEvent = null;
    if ($requestedEvent !== null) foreach ($events as $e) if ((int)($e['id'] ?? 0) === $requestedEvent) { $revealEvent = $e; break; }
    if ($requestedEvent === null) $revealEvent = latest_revealed_event($events);
    $league = league_payload();
    $manager = null;
    foreach ($league['managers'] as $m) if ((int)$m['entry'] === $entryId) { $manager = $m; break; }
    $manager ??= ['entry'=>$entryId,'teamName'=>"ENTRY {$entryId}",'managerName'=>'UNKNOWN MANAGER'];
    if (!$revealEvent || deadline_ts($revealEvent) > time()) return ['manager'=>$manager,'revealed'=>false,'event'=>$revealEvent,'players'=>[],'message'=>'SQUAD AWAITING RELEASE'];
    $picksWrap = get_picks($entryId, (int)$revealEvent['id']);
    if (!is_array($picksWrap['data']['picks'] ?? null)) return ['manager'=>$manager,'revealed'=>false,'event'=>$revealEvent,'players'=>[],'message'=>'SQUAD NOT PUBLICLY AVAILABLE YET','source'=>$picksWrap['source'] ?? 'UNAVAILABLE'];
    $playerMap = [];
    foreach (normalize_players($bootstrapWrap['data']) as $p) $playerMap[(int)$p['id']] = $p;
    $liveWrap = get_live((int)$revealEvent['id']);
    $livePoints = [];
    $liveStats = [];
    foreach ($liveWrap['data']['elements'] ?? [] as $e) {
        $eid = (int)($e['id'] ?? 0);
        $stats = is_array($e['stats'] ?? null) ? $e['stats'] : [];
        $livePoints[$eid] = (int)($stats['total_points'] ?? 0);
        $liveStats[$eid] = [
            'minutes'=>(int)($stats['minutes'] ?? 0),
            'goals'=>(int)($stats['goals_scored'] ?? 0),
            'assists'=>(int)($stats['assists'] ?? 0),
            'cleanSheets'=>(int)($stats['clean_sheets'] ?? 0),
            'goalsConceded'=>(int)($stats['goals_conceded'] ?? 0),
            'ownGoals'=>(int)($stats['own_goals'] ?? 0),
            'penaltiesSaved'=>(int)($stats['penalties_saved'] ?? 0),
            'penaltiesMissed'=>(int)($stats['penalties_missed'] ?? 0),
            'yellowCards'=>(int)($stats['yellow_cards'] ?? 0),
            'redCards'=>(int)($stats['red_cards'] ?? 0),
            'saves'=>(int)($stats['saves'] ?? 0),
            'bonus'=>(int)($stats['bonus'] ?? 0),
            'defensiveContribution'=>(int)($stats['defensive_contribution'] ?? 0),
            'totalPoints'=>(int)($stats['total_points'] ?? 0),
        ];
    }
    $picks = [];
    foreach ($picksWrap['data']['picks'] as $pick) {
        $id = (int)($pick['element'] ?? 0);
        $base = $playerMap[$id] ?? ['id'=>$id,'name'=>"PLAYER {$id}",'position'=>'','club'=>''];
        $picks[] = array_merge($base, [
            'gwPoints' => $livePoints[$id] ?? ($base['eventPoints'] ?? 0),
            'liveStats' => $liveStats[$id] ?? [],
            'pickPosition' => (int)($pick['position'] ?? 0),
            'multiplier' => (int)($pick['multiplier'] ?? 0),
            'captain' => !empty($pick['is_captain']),
            'viceCaptain' => !empty($pick['is_vice_captain']),
            'starter' => (int)($pick['position'] ?? 99) <= 11,
        ]);
    }
    return ['manager'=>$manager,'revealed'=>true,'event'=>$revealEvent,'players'=>$picks,'entryHistory'=>$picksWrap['data']['entry_history'] ?? null,'chip'=>$picksWrap['data']['active_chip'] ?? null,'source'=>$picksWrap['source'] ?? 'FPL'];
}

function player_profile_payload(int $playerId, ?int $eventId = null): array {
    $bootstrapWrap = get_bootstrap();
    $player = null;
    foreach (normalize_players($bootstrapWrap['data'] ?? []) as $p) {
        if ((int)($p['id'] ?? 0) === $playerId) { $player = $p; break; }
    }
    if (!$player) return ['player'=>null,'history'=>[],'fixtures'=>[],'source'=>$bootstrapWrap['source'] ?? 'FPL'];

    $summaryWrap = get_element_summary($playerId);
    $history = $summaryWrap['data']['history'] ?? [];
    $fixtures = $summaryWrap['data']['fixtures'] ?? [];

    $currentEventPoints = null;
    if ($eventId !== null && $eventId > 0) {
        $liveWrap = get_live($eventId);
        foreach ($liveWrap['data']['elements'] ?? [] as $e) {
            if ((int)($e['id'] ?? 0) === $playerId) {
                $currentEventPoints = (int)($e['stats']['total_points'] ?? 0);
                break;
            }
        }
    }
    if ($currentEventPoints !== null) $player['eventPoints'] = $currentEventPoints;

    $cleanHistory = [];
    foreach ($history as $h) {
        $cleanHistory[] = [
            'event'=>(int)($h['round'] ?? 0),
            'points'=>(int)($h['total_points'] ?? 0),
            'minutes'=>(int)($h['minutes'] ?? 0),
            'goals'=>(int)($h['goals_scored'] ?? 0),
            'assists'=>(int)($h['assists'] ?? 0),
            'cleanSheets'=>(int)($h['clean_sheets'] ?? 0),
            'bonus'=>(int)($h['bonus'] ?? 0),
            'bps'=>(int)($h['bps'] ?? 0),
            'value'=>isset($h['value']) && is_numeric($h['value']) ? ((float)$h['value']/10) : null,
            'selected'=>(int)($h['selected'] ?? 0),
            'transfersBalance'=>(int)($h['transfers_balance'] ?? 0),
        ];
    }
    usort($cleanHistory, fn($a,$b)=>(int)$a['event'] <=> (int)$b['event']);

    return [
        'player'=>$player,
        'history'=>$cleanHistory,
        'fixtures'=>$fixtures,
        'event'=>$eventId,
        'source'=>(($bootstrapWrap['source'] ?? '') === 'LIVE FPL' && ($summaryWrap['source'] ?? '') === 'LIVE FPL') ? 'LIVE FPL' : 'STALE DATA',
    ];
}

function medical_payload(int $entryId): array {
    $squad = squad_payload($entryId);
    if (empty($squad['revealed'])) return array_merge($squad, ['issues'=>[]]);
    $issues = [];
    foreach ($squad['players'] as $p) if (!empty($p['status']) && $p['status'] !== 'a') $issues[] = [
        'id'=>$p['id'],'name'=>$p['name'],'club'=>$p['club'],'position'=>$p['position'],'status'=>$p['status'],'news'=>$p['news'],'chanceThis'=>$p['chanceThis'],'chanceNext'=>$p['chanceNext']
    ];
    return ['manager'=>$squad['manager'],'event'=>$squad['event'],'revealed'=>true,'issues'=>$issues,'source'=>$squad['source'] ?? 'FPL'];
}

function awards_payload(): array {
    global $config;
    $bootstrapWrap = get_bootstrap();
    $league = league_payload();
    $allMatchesWrap = get_matches_raw(null);
    $events = $bootstrapWrap['data']['events'] ?? [];
    $matches = array_map('normalize_match', $allMatchesWrap['data']['results'] ?? []);
    $groups = [];
    foreach ($events as $ev) {
        $key = event_month_key($ev);
        if (!$key) continue;
        $groups[$key] ??= [];
        $groups[$key][] = $ev;
    }
    ksort($groups);
    $months = [];
    foreach ($groups as $key => $monthEvents) {
        $finishedMonthIds = array_flip(array_map(
            fn($e)=>(int)$e['id'],
            array_values(array_filter($monthEvents, fn($e)=>event_is_finished($e)))
        ));
        $monthMatches = array_values(array_filter($matches, fn($m)=>isset($finishedMonthIds[(int)$m['event']])));
        $stats = [];
        foreach ($league['managers'] as $m) $stats[(int)$m['entry']] = ['entry'=>(int)$m['entry'],'managerName'=>$m['managerName'],'teamName'=>$m['teamName'],'h2hPoints'=>0,'fplPoints'=>0,'won'=>0,'drawn'=>0,'lost'=>0,'played'=>0];
        foreach ($monthMatches as $m) {
            if (!$m['homeId'] || !$m['awayId'] || $m['homePoints'] === null || $m['awayPoints'] === null) continue;
            $stats[$m['homeId']] ??= ['entry'=>$m['homeId'],'managerName'=>$m['homeManager'] ?: 'UNKNOWN','teamName'=>$m['homeName'],'h2hPoints'=>0,'fplPoints'=>0,'won'=>0,'drawn'=>0,'lost'=>0,'played'=>0];
            $stats[$m['awayId']] ??= ['entry'=>$m['awayId'],'managerName'=>$m['awayManager'] ?: 'UNKNOWN','teamName'=>$m['awayName'],'h2hPoints'=>0,'fplPoints'=>0,'won'=>0,'drawn'=>0,'lost'=>0,'played'=>0];
            $h =& $stats[$m['homeId']]; $a =& $stats[$m['awayId']];
            $h['played']++; $a['played']++; $h['fplPoints'] += (int)$m['homePoints']; $a['fplPoints'] += (int)$m['awayPoints'];
            if ($m['homePoints'] > $m['awayPoints']) { $h['h2hPoints'] += 3; $h['won']++; $a['lost']++; }
            elseif ($m['awayPoints'] > $m['homePoints']) { $a['h2hPoints'] += 3; $a['won']++; $h['lost']++; }
            else { $h['h2hPoints']++; $a['h2hPoints']++; $h['drawn']++; $a['drawn']++; }
            unset($h, $a);
        }
        $table = array_values($stats);
        usort($table, fn($a,$b)=>($b['h2hPoints'] <=> $a['h2hPoints']) ?: ($b['fplPoints'] <=> $a['fplPoints']) ?: strcasecmp($a['managerName'],$b['managerName']));
        $finishedWeeks = count(array_filter($monthEvents, fn($e)=>!empty($e['finished']) || !empty($e['data_checked'])));
        $weeksRemaining = max(0, count($monthEvents) - $finishedWeeks);
        $monthFinal = count($monthEvents)>0 && count(array_filter($monthEvents, fn($e)=>!empty($e['finished']) || !empty($e['data_checked']))) === count($monthEvents);
        $winner = null; $coinTossRequired = false;
        if ($monthFinal && $table) {
            $top = $table[0];
            $tied = array_values(array_filter($table, fn($r)=>$r['h2hPoints']===$top['h2hPoints'] && $r['fplPoints']===$top['fplPoints']));
            if (count($tied) === 1) $winner = $tied[0];
            elseif (count($tied) > 1) {
                $configured = (int)($config['coinTossWinners'][$key] ?? 0);
                foreach ($tied as $r) if ((int)$r['entry'] === $configured) { $winner = $r; break; }
                if (!$winner) $coinTossRequired = true;
            }
        }
        $deadlinePassed = count(array_filter($monthEvents, fn($e)=>deadline_ts($e)<=time())) > 0;
        $months[] = ['key'=>$key,'label'=>month_label($key),'gameweeks'=>array_map(fn($e)=>(int)$e['id'],$monthEvents),'finishedWeeks'=>$finishedWeeks,'weeksRemaining'=>$weeksRemaining,'final'=>$monthFinal,'active'=>$deadlinePassed&&!$monthFinal,'upcoming'=>!$deadlinePassed,'table'=>$table,'winner'=>$winner,'coinTossRequired'=>$coinTossRequired];
    }
    $seasonFinal = count($events)>0 && count(array_filter($events, fn($e)=>!empty($e['finished']) || !empty($e['data_checked']))) === count($events);
    $seasonChampion = null;
    if ($seasonFinal && !empty($league['standingsReady'])) {
        foreach ($league['managers'] as $m) if ((int)($m['rank'] ?? 0) === 1) { $seasonChampion = $m; break; }
        $seasonChampion ??= $league['managers'][0] ?? null;
    }
    return ['timezone'=>$config['timezone'] ?? 'Europe/Dublin','rule'=>'WIN 3 / DRAW 1 / LOSS 0; TIE: MONTH FPL POINTS; STILL TIED: LITERAL COIN TOSS','months'=>$months,'seasonFinal'=>$seasonFinal,'seasonChampion'=>$seasonChampion,'source'=>$allMatchesWrap['source'] ?? 'FPL'];
}

function team_of_week_payload(int $eventId): array {
    $bootstrapWrap = get_bootstrap();
    $league = league_payload();
    $liveWrap = get_live($eventId);
    if (!is_array($liveWrap['data']['elements'] ?? null)) return ['event'=>$eventId,'ready'=>false,'message'=>'LIVE PLAYER POINTS NOT AVAILABLE'];
    $pointMap = [];
    foreach ($liveWrap['data']['elements'] as $e) $pointMap[(int)$e['id']] = (int)($e['stats']['total_points'] ?? 0);
    $playerMap = [];
    foreach (normalize_players($bootstrapWrap['data']) as $p) $playerMap[(int)$p['id']] = $p;
    $candidateMap = [];
    foreach ($league['managers'] as $manager) {
        $picks = get_picks((int)$manager['entry'], $eventId);
        foreach ($picks['data']['picks'] ?? [] as $pick) {
            if ((int)($pick['position'] ?? 99) > 11) continue;
            $id = (int)($pick['element'] ?? 0);
            if (!isset($playerMap[$id])) continue;
            if (!isset($candidateMap[$id])) $candidateMap[$id] = array_merge($playerMap[$id], ['points'=>$pointMap[$id] ?? 0,'owners'=>[]]);
            $candidateMap[$id]['owners'][] = ['entry'=>(int)$manager['entry'],'teamName'=>$manager['teamName'],'managerName'=>$manager['managerName']];
        }
    }
    $byPos = ['GKP'=>[],'DEF'=>[],'MID'=>[],'FWD'=>[]];
    foreach ($candidateMap as $p) if (isset($byPos[$p['position']])) $byPos[$p['position']][] = $p;
    foreach ($byPos as &$arr) usort($arr, fn($a,$b)=>($b['points'] <=> $a['points']) ?: ($b['totalPoints'] <=> $a['totalPoints']));
    unset($arr);
    $best = null;
    for ($d=3;$d<=5;$d++) for ($m=2;$m<=5;$m++) for ($f=1;$f<=3;$f++) {
        if ($d+$m+$f !== 10) continue;
        if (count($byPos['GKP'])<1 || count($byPos['DEF'])<$d || count($byPos['MID'])<$m || count($byPos['FWD'])<$f) continue;
        $team = array_merge([$byPos['GKP'][0]], array_slice($byPos['DEF'],0,$d), array_slice($byPos['MID'],0,$m), array_slice($byPos['FWD'],0,$f));
        $score = array_sum(array_map(fn($p)=>(int)$p['points'],$team));
        if (!$best || $score > $best['score']) $best = ['formation'=>"{$d}-{$m}-{$f}",'score'=>$score,'players'=>$team];
    }
    return $best ? array_merge(['event'=>$eventId,'ready'=>true],$best) : ['event'=>$eventId,'ready'=>false,'message'=>'NOT ENOUGH REVEALED STARTING PLAYERS'];
}

function scout_payload(): array {
    $b = get_bootstrap();
    $players = normalize_players($b['data']);
    usort($players, fn($a,$b)=>($b['form'] <=> $a['form']) ?: ($b['totalPoints'] <=> $a['totalPoints']));
    return ['source'=>$b['source'],'players'=>$players];
}


function get_entry_transfers(int $entryId): array {
    return cached_value("entry-transfers:{$entryId}", 120, function() use ($entryId) {
        try {
            return ['data' => http_json(FPL_BASE . "/entry/{$entryId}/transfers/"), 'source' => 'LIVE FPL'];
        } catch (Throwable $e) {
            return ['data' => [], 'source' => 'UNAVAILABLE', 'error' => $e->getMessage()];
        }
    });
}

function cm_ordinal(int $n): string {
    $mod100 = $n % 100;
    if ($mod100 >= 11 && $mod100 <= 13) return $n . 'th';
    return $n . match ($n % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' };
}

function cm_time_label(int|string|null $value = null): string {
    try {
        if ($value === null) $dt = new DateTimeImmutable('now', timezone_obj());
        elseif (is_int($value) || ctype_digit((string)$value)) $dt = (new DateTimeImmutable('@' . (string)$value))->setTimezone(timezone_obj());
        else $dt = (new DateTimeImmutable((string)$value))->setTimezone(timezone_obj());
    } catch (Throwable $e) {
        $dt = new DateTimeImmutable('now', timezone_obj());
    }
    $hour = (int)$dt->format('G');
    $part = $hour < 12 ? 'AM' : ($hour < 17 ? 'PM' : 'EVE');
    return $dt->format('D') . ' ' . cm_ordinal((int)$dt->format('j')) . ' ' . $dt->format('M') . ' ' . $part;
}

function cm_story(string $id, string $cat, int $sortTs, string $title, array $body, ?string $timeLabel = null, array $extra = []): array {
    return array_merge([
        'id'=>$id,
        'cat'=>$cat,
        'sortTs'=>$sortTs,
        'time'=>$timeLabel ?: cm_time_label($sortTs),
        'title'=>$title,
        'headline'=>$title,
        'body'=>array_values(array_filter(array_map('clean_text',$body))),
        'kind'=>'generated',
    ], $extra);
}

function cm_pl_team_terms(array $bootstrap): array {
    $terms = [];
    foreach ($bootstrap['teams'] ?? [] as $team) {
        $name = strtolower(clean_text($team['name'] ?? ''));
        $short = strtolower(clean_text($team['short_name'] ?? ''));
        if ($name !== '') $terms[] = $name;
        if ($short !== '' && strlen($short) > 3) $terms[] = $short;
        $aliases = match ($name) {
            'manchester united' => ['man utd','man united'],
            'manchester city' => ['man city'],
            'tottenham hotspur' => ['tottenham','spurs'],
            'wolverhampton wanderers' => ['wolves','wolverhampton'],
            'nottingham forest' => ['nottm forest','forest'],
            'newcastle united' => ['newcastle'],
            'west ham united' => ['west ham'],
            'brighton and hove albion', 'brighton & hove albion' => ['brighton'],
            'afc bournemouth', 'bournemouth' => ['bournemouth'],
            'crystal palace' => ['crystal palace','palace'],
            'aston villa' => ['aston villa','villa'],
            'leeds united' => ['leeds'],
            default => [],
        };
        $terms = array_merge($terms, $aliases);
    }
    $terms[] = 'premier league';
    return array_values(array_unique(array_filter($terms, fn($t)=>strlen((string)$t) >= 4)));
}

function cm_squad_terms(array $squadPlayers): array {
    $terms = [];
    foreach ($squadPlayers as $p) {
        foreach ([$p['name'] ?? '', $p['webName'] ?? '', $p['lastName'] ?? ''] as $raw) {
            $term = strtolower(clean_text($raw));
            if (strlen($term) >= 4) $terms[] = $term;
        }
    }
    return array_values(array_unique($terms));
}

function cm_text_contains_any(string $haystack, array $terms): array {
    $hits = [];
    foreach ($terms as $term) if ($term !== '' && str_contains($haystack, $term)) $hits[] = $term;
    return $hits;
}

function cm_news_tokens(string $text): array {
    $text = strtolower(clean_text($text));
    $text = preg_replace('/[^a-z0-9\s]+/u', ' ', $text) ?? $text;
    $stop = array_flip(['the','a','an','and','or','to','for','of','in','on','at','with','from','after','before','latest','news','live','report','reports','says','say','set','could','would','will']);
    $tokens = [];
    foreach (preg_split('/\s+/', $text) ?: [] as $t) if (strlen($t) >= 3 && !isset($stop[$t])) $tokens[$t] = true;
    return array_keys($tokens);
}

function cm_news_similar(string $a, string $b): bool {
    $ta = cm_news_tokens($a); $tb = cm_news_tokens($b);
    if (!$ta || !$tb) return strtolower(clean_text($a)) === strtolower(clean_text($b));
    $ia = array_intersect($ta, $tb);
    $union = array_unique(array_merge($ta, $tb));
    return count($union) > 0 && (count($ia) / count($union)) >= 0.55;
}

function cm_external_news(array $bootstrap, array $squadPlayers, int $now): array {
    $teamTerms = cm_pl_team_terms($bootstrap);
    $squadTerms = cm_squad_terms($squadPlayers);
    $injuryWords = ['injury','injured','fitness','doubt','doubtful','ruled out','sidelined','suspended','suspension','ban','banned'];
    $transferWords = ['transfer','sign','signs','signed','signing','deal','bid','move','moves','join','joins','joined','loan','release','released','sell','sold'];
    $candidates = [];

    foreach (['bbc','sky','guardian'] as $sourceId) {
        try {
            $feed = get_news_source($sourceId);
            $sourceName = clean_text($feed['sourceName'] ?? strtoupper($sourceId));
            foreach ($feed['stories'] ?? [] as $story) {
                $title = clean_text($story['title'] ?? '');
                if ($title === '') continue;
                $summary = clean_text($story['summary'] ?? '');
                $hay = strtolower($title . ' ' . $summary);
                $teamHits = cm_text_contains_any($hay, $teamTerms);
                $squadHits = cm_text_contains_any($hay, $squadTerms);
                if (!$teamHits && !$squadHits) continue; // strict Premier League / manager relevance

                $ts = $now;
                if (!empty($story['publishedAt'])) {
                    $parsed = strtotime((string)$story['publishedAt']);
                    if ($parsed !== false) $ts = $parsed;
                }
                $ageHours = max(0, ($now - $ts) / 3600);
                if ($ageHours > 72) continue;

                $injury = (bool)cm_text_contains_any($hay, $injuryWords);
                $transfer = (bool)cm_text_contains_any($hay, $transferWords);
                $score = 10;
                $score += min(22, count($teamHits) * 4);
                if ($squadHits) $score += 45 + min(15, count($squadHits) * 5);
                if ($injury) $score += $squadHits ? 22 : 7;
                if ($transfer) $score += 12;
                $score += max(0, 18 - (int)floor($ageHours / 3));
                if ($sourceId === 'bbc') $score += 2;

                $cat = ($squadHits && $injury) ? 'injuries' : ($squadHits ? 'messages' : 'competitions');
                $body = [];
                if ($summary !== '') {
                    if ((function_exists('mb_strlen') ? mb_strlen($summary) : strlen($summary)) > 260) $summary = rtrim(function_exists('mb_substr') ? mb_substr($summary, 0, 257) : substr($summary, 0, 257)) . '...';
                    $body[] = $summary;
                }
                $body[] = $sourceName;
                $candidates[] = cm_story(
                    'real-' . $sourceId . '-' . substr(hash('sha256', $title . ($story['url'] ?? '')), 0, 16),
                    $cat,
                    $ts,
                    $title,
                    $body,
                    cm_time_label($ts),
                    [
                        'kind'=>'real-news',
                        'sourceName'=>$sourceName,
                        'url'=>(string)($story['url'] ?? ''),
                        'relevanceScore'=>$score,
                        'squadRelated'=>(bool)$squadHits,
                        'transferRelated'=>$transfer,
                    ]
                );
            }
        } catch (Throwable $e) {
            // One unavailable publisher should never stop the manager feed.
        }
    }

    usort($candidates, fn($a,$b)=>(($b['relevanceScore']??0) <=> ($a['relevanceScore']??0)) ?: (($b['sortTs']??0) <=> ($a['sortTs']??0)));
    $selected = [];
    foreach ($candidates as $candidate) {
        $duplicate = false;
        foreach ($selected as $prior) if (cm_news_similar((string)$candidate['title'], (string)$prior['title'])) { $duplicate=true; break; }
        if ($duplicate) continue;
        $selected[] = $candidate;
        if (count($selected) >= 7) break;
    }
    return $selected;
}


function cm_chip_label(?string $chip): string {
    return match (strtolower(clean_text($chip ?? ''))) {
        'wildcard' => 'Wildcard',
        'freehit' => 'Free Hit',
        'bboost' => 'Bench Boost',
        '3xc' => 'Triple Captain',
        default => clean_text($chip ?? ''),
    };
}

function cm_rival_manager_news(array $managers, int $currentEntryId, array $playerMap, ?array $activeEvent, int $latestFinished, int $now): array {
    // Rival information is only taken from a squad that has already been revealed
    // by an FPL deadline. We never use a future Gameweek here.
    $eventId = $activeEvent ? (int)($activeEvent['id'] ?? 0) : $latestFinished;
    if ($eventId <= 0) return [];

    $candidates = [];
    foreach ($managers as $rival) {
        $rivalId = (int)($rival['entry'] ?? 0);
        if (!$rivalId || $rivalId === $currentEntryId) continue;
        $rivalManager = clean_text($rival['managerName'] ?? 'Manager');
        $rivalTeam = clean_text($rival['teamName'] ?? ('ENTRY ' . $rivalId));

        // Revealed squad, injury flags and chip use.
        try {
            $picksWrap = get_picks($rivalId, $eventId);
            $pickData = is_array($picksWrap['data'] ?? null) ? $picksWrap['data'] : [];
            $picks = is_array($pickData['picks'] ?? null) ? $pickData['picks'] : [];
            $chip = clean_text($pickData['active_chip'] ?? '');
            if ($chip !== '') {
                $chipLabel = cm_chip_label($chip);
                $candidates[] = cm_story(
                    'rival-chip-' . $eventId . '-' . $rivalId,
                    'competitions',
                    $now - 1500,
                    $rivalManager . ' plays ' . $chipLabel,
                    [ $rivalManager . ' has used the ' . $chipLabel . ' for ' . $rivalTeam . ' in Gameweek ' . $eventId . '.' ],
                    null,
                    ['kind'=>'rival-news','rivalEntry'=>$rivalId,'priority'=>100]
                );
            }

            foreach ($picks as $pick) {
                $pid = (int)($pick['element'] ?? 0);
                if (!$pid || !isset($playerMap[$pid])) continue;
                $p = $playerMap[$pid];
                $status = strtolower(clean_text($p['status'] ?? 'a'));
                if ($status === '' || $status === 'a') continue;
                $chance = $p['chanceThis'] ?? null;
                $severity = match ($status) { 'i' => 30, 's' => 28, 'u' => 24, 'd' => 12, default => 8 };
                if ($chance !== null && is_numeric($chance)) $severity += max(0, (100 - (int)$chance) / 5);
                $severity += min(12, ((float)($p['selectedBy'] ?? 0)) / 4);
                $chanceText = ($chance !== null && is_numeric($chance)) ? (' They are currently rated at ' . (int)$chance . '% to play this round.') : '';
                $body = [ $rivalTeam . ' manager ' . $rivalManager . ' faces an availability concern over ' . clean_text($p['name']) . '.' . $chanceText ];
                if (clean_text($p['news'] ?? '') !== '') $body[] = clean_text($p['news']);
                $candidates[] = cm_story(
                    'rival-injury-' . $eventId . '-' . $rivalId . '-' . $pid,
                    'injuries',
                    $now - 1800,
                    clean_text($p['name']) . ' fitness concern for ' . $rivalManager,
                    $body,
                    null,
                    ['kind'=>'rival-news','rivalEntry'=>$rivalId,'playerId'=>$pid,'priority'=>(int)round(70 + $severity)]
                );
            }
        } catch (Throwable $e) {}

        // Transfers become league gossip only once that Gameweek's squad is
        // public. This prevents pre-deadline moves from being exposed.
        try {
            $tw = get_entry_transfers($rivalId);
            $rows = is_array($tw['data'] ?? null) ? $tw['data'] : [];
            $eventRows = array_values(array_filter($rows, fn($tr)=>(int)($tr['event'] ?? 0) === $eventId));
            if ($eventRows) {
                $ins=[]; $outs=[]; $ts=0;
                foreach ($eventRows as $tr) {
                    $inId=(int)($tr['element_in'] ?? 0); $outId=(int)($tr['element_out'] ?? 0);
                    if (isset($playerMap[$inId])) $ins[] = clean_text($playerMap[$inId]['name']);
                    if (isset($playerMap[$outId])) $outs[] = clean_text($playerMap[$outId]['name']);
                    try { $ts=max($ts,(new DateTimeImmutable((string)($tr['time'] ?? 'now')))->getTimestamp()); } catch (Throwable $e) {}
                }
                if (!$ts) $ts = $now - 2100;
                $n=count($eventRows);
                $title = ($n===1 && !empty($ins[0])) ? ($rivalManager . ' moves for ' . $ins[0]) : ($rivalManager . ' makes ' . $n . ' changes');
                $body=[];
                if ($ins) $body[] = $rivalManager . ' brought ' . implode(', ', array_values(array_unique($ins))) . ' into ' . $rivalTeam . ' for Gameweek ' . $eventId . '.';
                if ($outs) $body[] = 'Leaving the squad: ' . implode(', ', array_values(array_unique($outs))) . '.';
                $candidates[] = cm_story(
                    'rival-transfer-' . $eventId . '-' . $rivalId,
                    'competitions',
                    $ts,
                    $title,
                    $body,
                    cm_time_label($ts),
                    ['kind'=>'rival-news','rivalEntry'=>$rivalId,'priority'=>62 + min(12,$n*3)]
                );
            }
        } catch (Throwable $e) {}
    }

    // Surface at most two rival stories at once. Prefer chips, serious injuries,
    // and then transfers. Avoid two separate stories about the same injured player.
    usort($candidates, fn($a,$b)=>(($b['priority']??0) <=> ($a['priority']??0)) ?: (($b['sortTs']??0) <=> ($a['sortTs']??0)));
    $selected=[]; $seenPlayers=[];
    foreach ($candidates as $candidate) {
        $pid=(int)($candidate['playerId'] ?? 0);
        if ($pid && isset($seenPlayers[$pid])) continue;
        if ($pid) $seenPlayers[$pid]=true;
        $selected[]=$candidate;
        if (count($selected)>=2) break;
    }
    return $selected;
}

function cm_editorial_news_mix(array $stories, int $limit = 30): array {
    // First remove duplicate IDs and put the raw feed into recency order.
    usort($stories, fn($a,$b)=>(($b['sortTs']??0) <=> ($a['sortTs']??0)) ?: strcmp((string)($a['id']??''),(string)($b['id']??'')));
    $seen=[]; $sorted=[];
    foreach ($stories as $st) {
        $id=(string)($st['id'] ?? '');
        if ($id==='' || isset($seen[$id])) continue;
        $seen[$id]=true; $sorted[]=$st;
    }

    // The visible CM inbox should remain about the user's FPL world. In the
    // first five rows, allow at most two publisher stories whenever there are
    // enough generated/private-league stories to fill the remaining places.
    $front=[]; $deferred=[]; $realCount=0;
    foreach ($sorted as $st) {
        $isReal=(($st['kind'] ?? '') === 'real-news');
        if (count($front) < 5) {
            if ($isReal && $realCount >= 2) { $deferred[]=$st; continue; }
            $front[]=$st;
            if ($isReal) $realCount++;
        } else {
            $deferred[]=$st;
        }
    }
    if (count($front) < 5 && $deferred) {
        while (count($front) < 5 && $deferred) $front[] = array_shift($deferred);
    }
    $out=array_merge($front,$deferred);
    return array_slice($out,0,$limit);
}

function table_through_event(array $managers, array $matches, int $throughEvent): array {
    $stats = [];
    foreach ($managers as $m) {
        $entry = (int)($m['entry'] ?? 0);
        if (!$entry) continue;
        $stats[$entry] = [
            'entry'=>$entry,
            'managerName'=>$m['managerName'] ?? 'UNKNOWN MANAGER',
            'teamName'=>$m['teamName'] ?? ('ENTRY ' . $entry),
            'played'=>0,'won'=>0,'drawn'=>0,'lost'=>0,'pointsFor'=>0,'pointsAgainst'=>0,'h2hPoints'=>0,
        ];
    }
    foreach ($matches as $m) {
        if ((int)($m['event'] ?? 0) > $throughEvent) continue;
        if (($m['homePoints'] ?? null) === null || ($m['awayPoints'] ?? null) === null) continue;
        $home = (int)($m['homeId'] ?? 0); $away = (int)($m['awayId'] ?? 0);
        if (!$home || !$away) continue;
        foreach ([$home,$away] as $entry) {
            if (!isset($stats[$entry])) $stats[$entry] = ['entry'=>$entry,'managerName'=>'UNKNOWN MANAGER','teamName'=>'UNKNOWN TEAM','played'=>0,'won'=>0,'drawn'=>0,'lost'=>0,'pointsFor'=>0,'pointsAgainst'=>0,'h2hPoints'=>0];
        }
        $hp=(int)$m['homePoints']; $ap=(int)$m['awayPoints'];
        $stats[$home]['played']++; $stats[$away]['played']++;
        $stats[$home]['pointsFor'] += $hp; $stats[$home]['pointsAgainst'] += $ap;
        $stats[$away]['pointsFor'] += $ap; $stats[$away]['pointsAgainst'] += $hp;
        if ($hp > $ap) { $stats[$home]['won']++; $stats[$home]['h2hPoints'] += 3; $stats[$away]['lost']++; }
        elseif ($ap > $hp) { $stats[$away]['won']++; $stats[$away]['h2hPoints'] += 3; $stats[$home]['lost']++; }
        else { $stats[$home]['drawn']++; $stats[$away]['drawn']++; $stats[$home]['h2hPoints']++; $stats[$away]['h2hPoints']++; }
    }
    $table = array_values($stats);
    usort($table, fn($a,$b)=>($b['h2hPoints'] <=> $a['h2hPoints']) ?: ($b['pointsFor'] <=> $a['pointsFor']) ?: strcasecmp((string)$a['teamName'],(string)$b['teamName']));
    foreach ($table as $i=>&$r) $r['rank']=$i+1;
    unset($r);
    return $table;
}

function manager_streak(int $entryId, array $matches, int $throughEvent): array {
    $own = array_values(array_filter($matches, function($m) use ($entryId,$throughEvent) {
        return (int)($m['event'] ?? 0) <= $throughEvent
            && (($m['homePoints'] ?? null) !== null) && (($m['awayPoints'] ?? null) !== null)
            && ((int)($m['homeId'] ?? 0) === $entryId || (int)($m['awayId'] ?? 0) === $entryId);
    }));
    usort($own, fn($a,$b)=>(int)$a['event'] <=> (int)$b['event']);
    $seq=[];
    foreach ($own as $m) {
        $home=(int)$m['homeId']===$entryId; $mine=(int)($home?$m['homePoints']:$m['awayPoints']); $theirs=(int)($home?$m['awayPoints']:$m['homePoints']);
        $seq[]=$mine>$theirs?'W':($mine<$theirs?'L':'D');
    }
    if (!$seq) return ['winning'=>0,'losing'=>0,'unbeaten'=>0];
    $winning=0; for($i=count($seq)-1;$i>=0 && $seq[$i]==='W';$i--)$winning++;
    $losing=0; for($i=count($seq)-1;$i>=0 && $seq[$i]==='L';$i--)$losing++;
    $unbeaten=0; for($i=count($seq)-1;$i>=0 && $seq[$i]!=='L';$i--)$unbeaten++;
    return ['winning'=>$winning,'losing'=>$losing,'unbeaten'=>$unbeaten];
}

function manager_news_payload(int $entryId): array {
    $league = league_payload();
    $managers = $league['managers'] ?? [];
    $manager = null;
    foreach ($managers as $m) if ((int)($m['entry'] ?? 0) === $entryId) { $manager=$m; break; }
    if (!$manager) respond(404,['error'=>'Manager not found in league']);

    $bootstrapWrap = get_bootstrap();
    $bootstrap = $bootstrapWrap['data'] ?? [];
    $events = $bootstrap['events'] ?? [];
    $eventMap = events_by_id($events);
    $players = normalize_players($bootstrap);
    $playerMap=[]; foreach($players as $p)$playerMap[(int)$p['id']]=$p;
    $matchesWrap = get_matches_raw(null);
    $matches = array_map('normalize_match', $matchesWrap['data']['results'] ?? []);
    $stories=[];
    $now=time();
    $leagueName=clean_text($league['league']['name'] ?? 'League');
    $managerName=clean_text($manager['managerName'] ?? 'Manager');
    $teamName=clean_text($manager['teamName'] ?? 'Team');
    $latestFinished = latest_finished_event_id($events);
    $activeEvent = active_event($events, $now);
    $managerSquadPlayers = [];
    try {
        $managerSquad = squad_payload($entryId);
        $managerSquadPlayers = is_array($managerSquad['players'] ?? null) ? $managerSquad['players'] : [];
    } catch (Throwable $e) {}

    // The News screen follows the current football moment. Future 0-0 H2H
    // fixtures must never be mistaken for completed draws/results.
    $finishedMatches = array_values(array_filter($matches, function($m) use ($eventMap) {
        $eventId=(int)($m['event'] ?? 0);
        return isset($eventMap[$eventId])
            && event_is_finished($eventMap[$eventId])
            && (($m['homePoints'] ?? null) !== null)
            && (($m['awayPoints'] ?? null) !== null);
    }));

    // Current live Gameweek: show the manager's current H2H matchup, not a
    // premature next-Gameweek preview.
    if ($activeEvent) {
        $activeId=(int)($activeEvent['id'] ?? 0);
        $currentMine=null;
        foreach($matches as $m) {
            if((int)($m['event'] ?? 0)!==$activeId) continue;
            if((int)($m['homeId'] ?? 0)===$entryId || (int)($m['awayId'] ?? 0)===$entryId){$currentMine=$m;break;}
        }
        if($currentMine){
            $home=(int)$currentMine['homeId']===$entryId;
            $myPts=(int)($home?($currentMine['homePoints']??0):($currentMine['awayPoints']??0));
            $oppPts=(int)($home?($currentMine['awayPoints']??0):($currentMine['homePoints']??0));
            $oppName=clean_text($home?($currentMine['awayName']?:'Opponent'):($currentMine['homeName']?:'Opponent'));
            if($myPts===0 && $oppPts===0){
                $title=$teamName . ' face ' . $oppName . ' in Gameweek ' . $activeId;
                $body=[ 'Gameweek ' . $activeId . ' of ' . $leagueName . ' is underway, with ' . $teamName . ' facing ' . $oppName . '.' ];
            } elseif($myPts>$oppPts){
                $title=$teamName . ' lead ' . $oppName . ' in Gameweek ' . $activeId;
                $body=[ $teamName . ' currently lead ' . $oppName . ' ' . $myPts . '-' . $oppPts . ' in Gameweek ' . $activeId . ' of ' . $leagueName . '.' ];
            } elseif($myPts<$oppPts){
                $title=$teamName . ' trail ' . $oppName . ' in Gameweek ' . $activeId;
                $body=[ $teamName . ' currently trail ' . $oppName . ' ' . $myPts . '-' . $oppPts . ' in Gameweek ' . $activeId . ' of ' . $leagueName . '.' ];
            } else {
                $title=$teamName . ' level with ' . $oppName . ' in Gameweek ' . $activeId;
                $body=[ $teamName . ' are currently level with ' . $oppName . ' at ' . $myPts . '-' . $oppPts . ' in Gameweek ' . $activeId . ' of ' . $leagueName . '.' ];
            }
            $stories[]=cm_story('live-'.$activeId.'-'.$entryId,'messages',$now,$title,$body,cm_time_label($now));
        }
    }

    // Find the next future deadline. A deadline reminder only appears within
    // 48 hours and only once the preceding Gameweek has finished (or before GW1).
    $future=array_values(array_filter($events,fn($e)=>deadline_ts($e)>$now));
    usort($future,fn($a,$b)=>deadline_ts($a)<=>deadline_ts($b));
    $nextDeadline=$future[0]??null;
    if($nextDeadline){
        $nextId=(int)($nextDeadline['id'] ?? 0);
        $deadline=deadline_ts($nextDeadline);
        $delta=max(0,$deadline-$now);
        $hours=(int)ceil($delta/3600);
        $previousComplete=($nextId===1 || $latestFinished >= $nextId-1);
        if($previousComplete && $hours<=48){
            $when=$hours<24?($hours . ' hour' . ($hours===1?'':'s')):((int)ceil($hours/24) . ' day' . ((int)ceil($hours/24)===1?'':'s'));
            $dt=(new DateTimeImmutable('@'.$deadline))->setTimezone(timezone_obj());
            $stories[]=cm_story('deadline-'.$nextId,'messages',$now-120,
                'Gameweek ' . $nextId . ' deadline approaches',[
                    $teamName . ' manager ' . $managerName . ' has been reminded that the Gameweek ' . $nextId . ' deadline is approaching.',
                    'The deadline is ' . $dt->format('l j F \a\t H:i') . ' and is approximately ' . $when . ' away.'
                ],cm_time_label($now));
        }
    }

    // Latest completed Gameweek. Only FPL events explicitly marked finished or
    // data-checked can create final results, league records, tables or streaks.
    $latestEvent=$latestFinished;
    if($latestEvent>0){
        $eventMatches=array_values(array_filter($finishedMatches,fn($m)=>(int)$m['event']===$latestEvent));
        $mine=null; foreach($eventMatches as $m) if((int)$m['homeId']===$entryId||(int)$m['awayId']===$entryId){$mine=$m;break;}
        if($mine){
            $home=(int)$mine['homeId']===$entryId; $myPts=(int)($home?$mine['homePoints']:$mine['awayPoints']); $oppPts=(int)($home?$mine['awayPoints']:$mine['homePoints']);
            $oppName=clean_text($home?($mine['awayName']?:'Opponent'):($mine['homeName']?:'Opponent'));
            $result=$myPts>$oppPts?'win':($myPts<$oppPts?'defeat':'draw');
            $title=$result==='win'?($teamName . ' defeat ' . $oppName):($result==='defeat'?($teamName . ' beaten by ' . $oppName):($teamName . ' draw with ' . $oppName));
            $stories[]=cm_story('result-'.$latestEvent.'-'.$entryId,'messages',$now-900,$title,[
                $teamName . ' recorded a ' . $myPts . '-' . $oppPts . ' ' . $result . ' against ' . $oppName . ' in Gameweek ' . $latestEvent . '.',
                'The result has been added to the ' . $leagueName . ' standings.'
            ]);
        }

        $best=null;
        foreach($eventMatches as $m){
            foreach([['id'=>$m['homeId'],'name'=>$m['homeName'],'manager'=>$m['homeManager'],'pts'=>$m['homePoints']],['id'=>$m['awayId'],'name'=>$m['awayName'],'manager'=>$m['awayManager'],'pts'=>$m['awayPoints']]] as $r){
                if($best===null||(int)$r['pts']>(int)$best['pts'])$best=$r;
            }
        }
        if($best){
            $stories[]=cm_story('topscore-'.$latestEvent,'competitions',$now-1800,
                clean_text($best['manager'] ?: $best['name']) . ' records highest Gameweek score',[
                    clean_text($best['name']) . ' recorded the highest score in ' . $leagueName . ' in Gameweek ' . $latestEvent . ' with ' . (int)$best['pts'] . ' points.'
                ]);
        }

        $big=null;
        foreach($eventMatches as $m){$margin=abs((int)$m['homePoints']-(int)$m['awayPoints']); if($margin>0&&($big===null||$margin>$big['margin']))$big=['m'=>$m,'margin'=>$margin];}
        if($big){
            $m=$big['m']; $homeWin=(int)$m['homePoints']>(int)$m['awayPoints']; $winner=clean_text($homeWin?$m['homeName']:$m['awayName']); $loser=clean_text($homeWin?$m['awayName']:$m['homeName']); $wp=(int)($homeWin?$m['homePoints']:$m['awayPoints']); $lp=(int)($homeWin?$m['awayPoints']:$m['homePoints']);
            $stories[]=cm_story('bigwin-'.$latestEvent,'competitions',$now-2100,$winner . ' record biggest win',[ $winner . ' recorded the biggest winning margin of Gameweek ' . $latestEvent . ', defeating ' . $loser . ' ' . $wp . '-' . $lp . '.' ]);
        }

        $currentTable=table_through_event($managers,$finishedMatches,$latestEvent);
        $prevTable=table_through_event($managers,$finishedMatches,max(0,$latestEvent-1));
        $newLeader=$currentTable[0]??null; $oldLeader=$prevTable[0]??null;
        if($newLeader){
            $leaderChanged = $latestEvent > 1 && $oldLeader && (int)$newLeader['entry'] !== (int)$oldLeader['entry'];
            $leaderTitle = $leaderChanged
                ? (clean_text($newLeader['managerName']) . ' moves top of ' . $leagueName)
                : (clean_text($newLeader['managerName']) . ' leads ' . $leagueName . ' after Gameweek ' . $latestEvent);
            $leaderBody = $leaderChanged
                ? (clean_text($newLeader['teamName']) . ' have moved to the top of ' . $leagueName . ' following Gameweek ' . $latestEvent . '.')
                : (clean_text($newLeader['teamName']) . ' sit top of ' . $leagueName . ' after Gameweek ' . $latestEvent . '.');
            $stories[]=cm_story('leader-'.$latestEvent,'competitions',$now-2400,$leaderTitle,[
                $leaderBody,
                'They have ' . (int)$newLeader['h2hPoints'] . ' points from ' . (int)$newLeader['played'] . ' matches.'
            ]);
        }

        $streak=manager_streak($entryId,$finishedMatches,$latestEvent);
        if($streak['winning']>=3){
            $stories[]=cm_story('streak-win-'.$latestEvent.'-'.$entryId,'competitions',$now-2700,$teamName . ' make it ' . $streak['winning'] . ' wins in a row',[ $teamName . ' have now won ' . $streak['winning'] . ' consecutive matches in ' . $leagueName . '.' ]);
        } elseif($streak['losing']>=3){
            $stories[]=cm_story('streak-loss-'.$latestEvent.'-'.$entryId,'competitions',$now-2700,$teamName . ' suffer ' . $streak['losing'] . 'th straight defeat',[ $teamName . ' have now lost ' . $streak['losing'] . ' consecutive matches in ' . $leagueName . '.' ]);
        } elseif($streak['unbeaten']>=4){
            $stories[]=cm_story('streak-unbeaten-'.$latestEvent.'-'.$entryId,'competitions',$now-2700,$teamName . ' extend unbeaten run',[ $teamName . ' are now unbeaten in ' . $streak['unbeaten'] . ' matches in ' . $leagueName . '.' ]);
        }
    }

    // Preview the next H2H opponent only after the immediately preceding
    // Gameweek has finished. This prevents GW2 previews appearing during GW1.
    if($nextDeadline){
        $nextId=(int)($nextDeadline['id'] ?? 0);
        $previousComplete=($nextId===1 || $latestFinished >= $nextId-1);
        if($previousComplete){
            try{
                $fx=fixtures_payload($nextId); $nextMine=null;
                foreach($fx['matches']??[] as $m)if((int)$m['homeId']===$entryId||(int)$m['awayId']===$entryId){$nextMine=$m;break;}
                if($nextMine){$home=(int)$nextMine['homeId']===$entryId; $opp=clean_text($home?$nextMine['away']['teamName']:$nextMine['home']['teamName']);
                    $stories[]=cm_story('next-'.$nextId.'-'.$entryId,'messages',$now-1200,$teamName . ' prepare to face ' . $opp,[ $teamName . ' will face ' . $opp . ' in Gameweek ' . $nextId . ' of ' . $leagueName . '.' ]);
                }
            }catch(Throwable $e){}
        }
    }

    // Squad-specific injuries and price changes.
    try{
        $squadIds=[];
        foreach($managerSquadPlayers as $p)$squadIds[(int)$p['id']]=$p;
        foreach($squadIds as $pid=>$p){
            if(!empty($p['status'])&&$p['status']!=='a'){
                $chance=$p['chanceThis']; $chanceText=$chance===null?'':(' They are currently rated at ' . (int)$chance . '% to play this round.');
                $stories[]=cm_story('injury-'.$pid,'injuries',$now-3000,clean_text($p['name']) . ' fitness concern for ' . $managerName,[ $managerName . ' faces an availability concern after ' . clean_text($p['name']) . ' was flagged by Fantasy Premier League.' . $chanceText, clean_text($p['news']??'') ]);
            }
            $chg=(float)($p['costChangeEvent']??0);
            if(abs($chg)>=0.099){
                $direction=$chg>0?'rises':'falls';
                $stories[]=cm_story('price-'.$pid.'-'.$latestEvent,'messages',$now-3300,clean_text($p['name']) . ' price ' . $direction,[ clean_text($p['name']) . "'s Fantasy Premier League value has " . ($chg>0?'risen':'fallen') . ' to £' . number_format((float)$p['price'],1) . 'm this gameweek.', clean_text($p['name']) . ' is currently part of ' . $teamName . "'s squad." ]);
            }
        }
    }catch(Throwable $e){}

    // Public manager transfer history. Only reveal transfers up to the current
    // or latest completed Gameweek; never surface future placeholder events.
    try{
        $tw=get_entry_transfers($entryId); $trs=is_array($tw['data']??null)?$tw['data']:[]; $groups=[];
        $maxTransferEvent=$activeEvent?(int)($activeEvent['id']??0):$latestFinished;
        foreach($trs as $tr){$ev=(int)($tr['event']??0); if(!$ev || ($maxTransferEvent>0 && $ev>$maxTransferEvent))continue; $groups[$ev][]=$tr;}
        krsort($groups); $shown=0;
        foreach($groups as $ev=>$rows){
            if($shown>=2)break; $ins=[];$outs=[];$ts=0;
            foreach($rows as $tr){$inId=(int)($tr['element_in']??0);$outId=(int)($tr['element_out']??0); if(isset($playerMap[$inId]))$ins[]=clean_text($playerMap[$inId]['name']); if(isset($playerMap[$outId]))$outs[]=clean_text($playerMap[$outId]['name']); try{$ts=max($ts,(new DateTimeImmutable((string)($tr['time']??'now')))->getTimestamp());}catch(Throwable $e){}}
            $n=count($rows); if(!$ts)$ts=$now-3600-$shown*120;
            $title=$n===1?($teamName . ' make transfer'):($teamName . ' make ' . $n . ' changes');
            $body=[]; if($ins)$body[]=$managerName . ' brought in ' . implode(', ',$ins) . ' ahead of Gameweek ' . $ev . '.'; if($outs)$body[]='Leaving the squad: ' . implode(', ',$outs) . '.';
            $stories[]=cm_story('transfer-'.$entryId.'-'.$ev,'messages',$ts,$title,$body,cm_time_label($ts)); $shown++;
        }
    }catch(Throwable $e){}

    // Selected rival-manager gossip: revealed injuries, transfers and chip use.
    // These stories are shared across manager perspectives so the private league
    // feels like one common CM world rather than fourteen isolated dashboards.
    try {
        foreach (cm_rival_manager_news($managers, $entryId, $playerMap, $activeEvent, $latestFinished, $now) as $rivalStory) $stories[] = $rivalStory;
    } catch (Throwable $e) {}

    // Manager of the Month: awards_payload now counts only finished Gameweeks.
    try{
        $aw=awards_payload(); $candidate=null;
        foreach(array_reverse($aw['months']??[]) as $m){if(!empty($m['winner'])||(!empty($m['active']) && !empty($m['finishedWeeks']))){$candidate=$m;break;}}
        if($candidate){
            if(!empty($candidate['winner'])){
                $w=$candidate['winner']; $stories[]=cm_story('award-'.$candidate['key'],'competitions',$now-3900,clean_text($w['managerName']) . ' wins ' . clean_text($candidate['label']) . ' award',[ clean_text($w['managerName']) . ' has won the ' . clean_text($candidate['label']) . ' Manager of the Month award in ' . $leagueName . '.', clean_text($w['teamName']) . ' collected ' . (int)$w['h2hPoints'] . ' head-to-head points during the month.' ]);
            }elseif(!empty($candidate['table'][0])&&($candidate['table'][0]['played']??0)>0){$top=$candidate['table'][0];$stories[]=cm_story('award-race-'.$candidate['key'],'competitions',$now-3900,clean_text($top['managerName']) . ' leads ' . clean_text($candidate['label']) . ' award race',[ clean_text($top['managerName']) . ' currently leads the ' . clean_text($candidate['label']) . ' Manager of the Month standings with ' . (int)$top['h2hPoints'] . ' points.', (int)$candidate['weeksRemaining'] . ' gameweek' . ((int)$candidate['weeksRemaining']===1?'':'s') . ' remain in the monthly race.' ]);}
        }
    }catch(Throwable $e){}

    // Blend a small, deduplicated selection of recent real Premier League news
    // into the same CM feed. Stories concerning the signed-in manager's squad
    // are promoted into Messages / Injuries & Bans; other PL stories sit under
    // Competitions. Generated league stories remain the backbone of the feed.
    try {
        foreach (cm_external_news($bootstrap, $managerSquadPlayers, $now) as $realStory) $stories[] = $realStory;
    } catch (Throwable $e) {}

    $out = cm_editorial_news_mix($stories, 30);
    return [
        'manager'=>$manager,
        'leagueName'=>$leagueName,
        'timezone'=>$GLOBALS['config']['timezone'] ?? 'Europe/Dublin',
        'activeEvent'=>$activeEvent,
        'latestFinishedEvent'=>$latestFinished,
        'stories'=>$out,
        'source'=>(($matchesWrap['source']??'')==='LIVE FPL'&&($bootstrapWrap['source']??'')==='LIVE FPL')?'LIVE FPL':'STALE DATA',
        'generatedAt'=>gmdate('c'),
    ];
}

function media_payload(): array {
    $league = league_payload();
    $bootstrapWrap = get_bootstrap();
    $events = $bootstrapWrap['data']['events'] ?? [];
    $latestFinished = latest_finished_event_id($events);
    $managers = $league['managers'] ?? [];
    $leagueName = clean_text($league['league']['name'] ?? 'League');
    $now = time();
    $stories = [];

    $add = function(string $id, string $outletId, string $outlet, int $sortTs, string $title, array $body) use (&$stories) {
        $stories[] = [
            'id'=>$id,'outletId'=>$outletId,'outlet'=>$outlet,'sortTs'=>$sortTs,
            'time'=>cm_time_label($sortTs),'title'=>clean_text($title),
            'body'=>array_values(array_filter(array_map('clean_text',$body)))
        ];
    };

    $matches = [];
    try { $matches = array_map('normalize_match', get_matches_raw(null)['data']['results'] ?? []); } catch (Throwable $e) {}
    if ($latestFinished > 0) {
        $eventMatches = array_values(array_filter($matches, fn($m)=>(int)($m['event'] ?? 0)===$latestFinished && ($m['homePoints'] ?? null)!==null && ($m['awayPoints'] ?? null)!==null));
        $table = table_through_event($managers, $matches, $latestFinished);
        if (!empty($table[0])) {
            $leader=$table[0];
            $add('media-times-leader-'.$latestFinished,'times','TOUCHLINE TIMES',$now-600,
                clean_text($leader['teamName']) . ' set the pace after Gameweek ' . $latestFinished,
                [clean_text($leader['managerName']) . "'s side lead " . $leagueName . ' with ' . (int)$leader['h2hPoints'] . ' points from ' . (int)$leader['played'] . ' matches.',
                 'Their FPL points total is ' . (int)$leader['pointsFor'] . ', the first tie-break used in the table.']);
        }
        $best=null; $big=null;
        foreach ($eventMatches as $m) {
            foreach ([['name'=>$m['homeName'],'manager'=>$m['homeManager'],'pts'=>$m['homePoints']],['name'=>$m['awayName'],'manager'=>$m['awayManager'],'pts'=>$m['awayPoints']]] as $r) if($best===null||(int)$r['pts']>(int)$best['pts'])$best=$r;
            $margin=abs((int)$m['homePoints']-(int)$m['awayPoints']); if($margin>0&&($big===null||$margin>$big['margin']))$big=['m'=>$m,'margin'=>$margin];
        }
        if($best)$add('media-times-score-'.$latestFinished,'times','TOUCHLINE TIMES',$now-900,
            clean_text($best['name']) . ' top the scoring charts',
            [clean_text($best['manager'] ?: $best['name']) . ' posted the highest total of Gameweek ' . $latestFinished . ' with ' . (int)$best['pts'] . ' FPL points.']);
        if($big){$m=$big['m'];$hw=(int)$m['homePoints']>(int)$m['awayPoints'];$winner=clean_text($hw?$m['homeName']:$m['awayName']);$loser=clean_text($hw?$m['awayName']:$m['homeName']);$wp=(int)($hw?$m['homePoints']:$m['awayPoints']);$lp=(int)($hw?$m['awayPoints']:$m['homePoints']);
            $add('media-dugout-big-'.$latestFinished,'dugout','THE DUGOUT',$now-1200,$winner . ' enjoy statement victory',[$winner . ' produced the biggest winning margin of the round, beating ' . $loser . ' ' . $wp . '-' . $lp . '.','The result is already being talked about as one of the early markers of the season.']);
        }
    }

    try {
        $tr = league_transfers_payload();
        $recent = array_slice($tr['transfers'] ?? [],0,5);
        foreach($recent as $i=>$row){
            $manager=clean_text($row['managerName'] ?? 'Manager'); $team=clean_text($row['teamName'] ?? 'Team');
            $in=clean_text($row['playerIn']['name'] ?? ''); $out=clean_text($row['playerOut']['name'] ?? ''); $ev=(int)($row['event'] ?? 0);
            $ts=strtotime((string)($row['time'] ?? '')) ?: ($now-1500-$i*180);
            $add('media-dugout-transfer-'.$ev.'-'.($row['entry']??0).'-'.$i,'dugout','THE DUGOUT',$ts,
                $manager . ($in!==''?' moves for '.$in:' reshapes '.$team),
                [($in!==''?$in.' arrives at '.$team:'A new signing arrives at '.$team) . ($out!==''?' with '.$out.' making way.':'.'), 'The move was revealed after the Gameweek ' . $ev . ' deadline.']);
        }
    } catch (Throwable $e) {}

    // The Moon is intentionally playful and speculative, but its stories stay inside the fantasy-football world.
    $moonPool=[];
    foreach($managers as $m){
        $entry=(int)($m['entry']??0); if(!$entry)continue;
        $streak=manager_streak($entry,$matches,$latestFinished);
        $moonPool[]=['m'=>$m,'streak'=>$streak];
    }
    usort($moonPool,fn($a,$b)=>max($b['streak'])<=>max($a['streak']));
    if(!empty($moonPool[0])){
        $m=$moonPool[0]['m'];$st=$moonPool[0]['streak'];$mn=clean_text($m['managerName']??'Manager');$tn=clean_text($m['teamName']??'Team');
        if(($st['winning']??0)>=2){$add('media-moon-streak-'.$latestFinished,'moon','THE MOON',$now-720,$mn . ' plotting title charge?',[$tn . ' have won ' . (int)$st['winning'] . ' in a row and The Moon can exclusively reveal that confidence is "very high" around the imaginary training ground.','Whether that means anything at all will become clear next Gameweek.']);}
        elseif(($st['losing']??0)>=2){$add('media-moon-crisis-'.$latestFinished,'moon','THE MOON',$now-720,'CRISIS? Pressure mounts on ' . $mn,[$tn . ' have lost ' . (int)$st['losing'] . ' consecutive league matches.','The Moon understands that absolutely nobody has been sacked, but that has not stopped us putting CRISIS in capital letters.']);}
        else {$add('media-moon-watch-'.$latestFinished,'moon','THE MOON',$now-720,$mn . ' keeps rivals guessing',[$tn . ' remain one of the league’s harder sides to read.','Sources close to The Moon — mainly the league table and a calculator — say another tactical surprise may be coming.']);}
    }
    if(count($managers)>1){
        $idx=($latestFinished + count($managers)) % count($managers); $m=$managers[$idx];
        $add('media-moon-rumour-'.$latestFinished,'moon','THE MOON',$now-1800,'Mystery surrounds ' . clean_text($m['teamName'] ?? 'league side'),[clean_text($m['managerName'] ?? 'The manager') . ' has refused to confirm whether the next captaincy decision has already been made.','The Moon has therefore chosen to describe the situation as a “selection mystery”.']);
    }

    usort($stories,fn($a,$b)=>(int)$b['sortTs']<=>(int)$a['sortTs']);
    return ['leagueName'=>$leagueName,'latestFinishedEvent'=>$latestFinished,'stories'=>array_slice($stories,0,24),'generatedAt'=>gmdate('c')];
}

function app_state(): array {
    global $config;
    $league = league_payload();
    $bootstrapWrap = get_bootstrap();
    $events = $bootstrapWrap['data']['events'] ?? [];
    $user = null;
    foreach ($league['managers'] as $m) if ((int)$m['entry'] === (int)($config['userEntryId'] ?? 0)) { $user = $m; break; }
    $current = array_values(array_filter($events, fn($e)=>!empty($e['is_current'])))[0] ?? null;
    $next = array_values(array_filter($events, fn($e)=>!empty($e['is_next'])))[0] ?? null;
    return [
        'leagueId'=>(int)($config['leagueId'] ?? 0),'userEntryId'=>(int)($config['userEntryId'] ?? 0),'season'=>$config['season'] ?? '2026/27','timezone'=>$config['timezone'] ?? 'Europe/Dublin','pollMs'=>(int)($config['pollMs'] ?? 60000),
        'leagueName'=>clean_text($league['league']['name'] ?? ''),'user'=>$user,'displayEvent'=>current_display_event($events),'currentEvent'=>$current,'nextEvent'=>$next,'latestRevealedEvent'=>latest_revealed_event($events),'source'=>$league['source'],'now'=>gmdate('c')
    ];
}

// Local regression tests load functions without dispatching a web request.
if (defined('FPL_TESTING') && FPL_TESTING) return;

try {
    $route = trim((string)($_GET['route'] ?? ''), '/');
    switch ($route) {
        case 'league-check':
            try {
                $wrap = get_standings_raw();
                if (($wrap['source'] ?? '') !== 'LIVE FPL') throw new RuntimeException('Stale league');
                $league = $wrap['data']['league'];
                respond(200, ['leagueId'=>(int)$league['id'], 'leagueName'=>clean_text($league['name'] ?? 'H2H League')]);
            } catch (Throwable $e) {
                respond(422, ['error'=>'Could not read that H2H league. Check its numeric ID and that it is available without signing in. Classic and Draft leagues are not supported.']);
            }

        case 'state': respond(200, app_state());
        case 'league': respond(200, league_payload());
        case 'players':
            $b = get_bootstrap();
            respond(200, ['source'=>$b['source'],'players'=>normalize_players($b['data']),'teams'=>$b['data']['teams'] ?? [],'events'=>$b['data']['events'] ?? []]);
        case 'scout': respond(200, scout_payload());
        case 'news':
            $source = strtolower(trim((string)($_GET['source'] ?? 'bbc')));
            if (!news_source_config($source)) respond(400,['error'=>'News source not available']);
            respond(200, get_news_source($source));
        case 'manager-news': respond(200, manager_news_payload((int)($_GET['entry'] ?? ($config['userEntryId'] ?? 0))));
        case 'media': respond(200, media_payload());
        case 'medical': respond(200, medical_payload((int)($_GET['entry'] ?? ($config['userEntryId'] ?? 0))));
        case 'player-profile':
            $player = (int)($_GET['id'] ?? 0); if (!$player) respond(400,['error'=>'id is required']); $event = isset($_GET['event']) && $_GET['event'] !== '' ? (int)$_GET['event'] : null; respond(200,player_profile_payload($player,$event));
        case 'awards': respond(200, awards_payload());
        case 'fixtures':
            $event = (int)($_GET['event'] ?? 0); if (!$event) respond(400,['error'=>'event is required']); respond(200,fixtures_payload($event));
        case 'competition-schedule':
            $entry = (int)($_GET['entry'] ?? ($config['userEntryId'] ?? 0)); respond(200,competition_schedule_payload($entry));
        case 'manager-transfers':
            $entry = (int)($_GET['entry'] ?? ($config['userEntryId'] ?? 0)); respond(200,manager_transfers_payload($entry));
        case 'league-transfers': respond(200, league_transfers_payload());
        case 'squad':
            $entry = (int)($_GET['entry'] ?? ($config['userEntryId'] ?? 0)); $event = isset($_GET['event']) && $_GET['event'] !== '' ? (int)$_GET['event'] : null; respond(200,squad_payload($entry,$event));
        case 'live':
            $event = (int)($_GET['event'] ?? 0); if (!$event) respond(400,['error'=>'event is required']); respond(200,get_live($event));
        case 'team-of-week':
            $event = (int)($_GET['event'] ?? 0); if (!$event) respond(400,['error'=>'event is required']); respond(200,team_of_week_payload($event));
        case 'health':
            respond(200,['ok'=>true,'runtime'=>'PHP','phpVersion'=>PHP_VERSION,'leagueId'=>(int)($config['leagueId'] ?? 0),'timezone'=>$config['timezone'] ?? 'Europe/Dublin','curl'=>function_exists('curl_init')]);
        default: respond(404,['error'=>'API route not found']);
    }
} catch (Throwable $e) {
    respond(503, ['error'=>'Live data is temporarily unavailable. Please retry shortly; check server configuration if this persists.']);
}
