<?php
declare(strict_types=1);

// Local configuration is server-side only. Public defaults deliberately select the demo league.
$defaults = [
    'leagueId' => 86140, 'userEntryId' => 0, 'season' => '2026/27',
    'timezone' => 'Europe/Dublin', 'pollMs' => 60000, 'coinTossWinners' => [],
    'staleMaxSeconds' => 86400, 'maxLeaguePages' => 100,
];
$explicitConfig = getenv('FPL_CONFIG_FILE');
$configPath = $explicitConfig ?: __DIR__ . '/config.local.json';
$config = $defaults;
if ($explicitConfig || is_file($configPath)) {
    $raw = @file_get_contents($configPath);
    if ($raw === false) throw new RuntimeException('Cannot read local configuration');
    $local = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($local) || array_is_list($local)) throw new RuntimeException('Configuration must be a JSON object');
    $config = array_replace($defaults, $local);
}
foreach (['leagueId', 'userEntryId', 'pollMs', 'staleMaxSeconds', 'maxLeaguePages'] as $key) {
    if (!is_int($config[$key])) throw new RuntimeException('Configuration integer required: ' . $key);
}
if ($config['leagueId'] < 1 || $config['userEntryId'] < 0 || $config['pollMs'] < 30000
    || $config['staleMaxSeconds'] < 0 || $config['staleMaxSeconds'] > 604800
    || $config['maxLeaguePages'] < 1 || $config['maxLeaguePages'] > 1000) throw new RuntimeException('Configuration value out of range');
if (!is_string($config['season']) || !preg_match('~^\d{4}/\d{2}$~', $config['season'])) throw new RuntimeException('Invalid season');
if (!is_string($config['timezone']) || !in_array($config['timezone'], DateTimeZone::listIdentifiers(), true)) throw new RuntimeException('Invalid timezone');
if (!is_array($config['coinTossWinners'])) throw new RuntimeException('Invalid coinTossWinners');

// A visitor override is request-local and cannot update the deployment settings.
function apply_league_override(array $settings, mixed $value): array {
    if (!is_string($value) || !preg_match('/^[1-9][0-9]{0,9}$/D', $value) || (int)$value > 2147483647) {
        throw new InvalidArgumentException('Enter a valid numeric H2H league ID.');
    }
    $id = (int)$value;
    if ($id !== $settings['leagueId']) {
        $settings['userEntryId'] = 0;
        $settings['coinTossWinners'] = [];
    }
    $settings['leagueId'] = $id;
    return $settings;
}
if (array_key_exists('league', $_GET)) $config = apply_league_override($config, $_GET['league']);

// No cache or private snapshot is written into the public source tree.
$CACHE_DIR = getenv('FPL_CACHE_DIR') ?: sys_get_temp_dir() . '/fpl-manager-' . substr(hash('sha256', __DIR__), 0, 16);
if (!is_dir($CACHE_DIR) && !@mkdir($CACHE_DIR, 0700, true) && !is_dir($CACHE_DIR)) throw new RuntimeException('Cannot create runtime cache');
if (!is_writable($CACHE_DIR)) throw new RuntimeException('Runtime cache must be writable');

function cache_file(string $key): string {
    global $CACHE_DIR, $config;
    $namespace = 'public-v0.1.0:' . $config['season'] . ':' . $config['leagueId'];
    return $CACHE_DIR . '/' . hash('sha256', $namespace . ':' . $key) . '.json';
}

function cached_value(string $key, int $ttlSeconds, callable $loader): mixed {
    global $config;
    $file = cache_file($key);
    $cached = null;
    $age = PHP_INT_MAX;
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $cached = $raw !== false ? json_decode($raw, true) : null;
        $age = max(0, time() - (int)filemtime($file));
        if ($cached !== null && $age < $ttlSeconds) return $cached;
    }
    try {
        $value = $loader();
    } catch (Throwable $e) {
        if (is_array($cached) && $config['staleMaxSeconds'] > 0 && $age <= $config['staleMaxSeconds']) {
            $cached['source'] = 'STALE CACHE';
            $cached['warning'] = 'FPL unavailable; showing previously fetched data';
            $cached['cachedAt'] = gmdate('c', (int)filemtime($file));
            return $cached;
        }
        throw $e;
    }
    // A failed/unavailable loader must not overwrite a useful previous response.
    if (is_array($value) && ($value['source'] ?? '') === 'UNAVAILABLE') return $value;
    $tmp = tempnam(dirname($file), '.fpl-');
    if ($tmp !== false) {
        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json !== false && file_put_contents($tmp, $json, LOCK_EX) !== false) @rename($tmp, $file);
        if (is_file($tmp)) @unlink($tmp);
    }
    return $value;
}
