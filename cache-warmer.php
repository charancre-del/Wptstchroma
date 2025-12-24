<?php
/**
 * Cache Warmer Script
 * 
 * Parses a sitemap and requests each URL to warm up the cache.
 * 
 * Usage:
 *   php cache-warmer.php <sitemap_url>
 *   php cache-warmer.php https://example.com/sitemap.xml
 *   php cache-warmer.php https://example.com/sitemap_index.xml
 * 
 * Options:
 *   --delay=<ms>       Delay between requests in milliseconds (default: 100)
 *   --timeout=<sec>    Request timeout in seconds (default: 30)
 *   --user-agent=<ua>  Custom user agent string
 *   --verbose          Show detailed output for each request
 *   --dry-run          Parse sitemap but don't make requests
 */

// Configuration defaults
$config = [
    'delay' => 100,           // Delay between requests in ms
    'timeout' => 30,          // Request timeout in seconds
    'user_agent' => 'CacheWarmer/1.0 (+https://chromaela.com)',
    'verbose' => false,
    'dry_run' => false,
];

// Parse command line arguments
$args = array_slice($argv, 1);
$sitemap_url = null;

foreach ($args as $arg) {
    if (strpos($arg, '--delay=') === 0) {
        $config['delay'] = (int) substr($arg, 8);
    } elseif (strpos($arg, '--timeout=') === 0) {
        $config['timeout'] = (int) substr($arg, 10);
    } elseif (strpos($arg, '--user-agent=') === 0) {
        $config['user_agent'] = substr($arg, 13);
    } elseif ($arg === '--verbose') {
        $config['verbose'] = true;
    } elseif ($arg === '--dry-run') {
        $config['dry_run'] = true;
    } elseif (strpos($arg, '--') !== 0) {
        $sitemap_url = $arg;
    }
}

// Validate sitemap URL
if (empty($sitemap_url)) {
    echo "Usage: php cache-warmer.php <sitemap_url> [options]\n";
    echo "\nOptions:\n";
    echo "  --delay=<ms>       Delay between requests in milliseconds (default: 100)\n";
    echo "  --timeout=<sec>    Request timeout in seconds (default: 30)\n";
    echo "  --user-agent=<ua>  Custom user agent string\n";
    echo "  --verbose          Show detailed output for each request\n";
    echo "  --dry-run          Parse sitemap but don't make requests\n";
    exit(1);
}

/**
 * Fetch URL content with cURL
 */
function fetch_url($url, $config, $warm_cache = false)
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => $config['timeout'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => $config['user_agent'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_ENCODING => 'gzip, deflate',
    ]);

    // For cache warming, we want a full page load
    if ($warm_cache) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ]);
    }

    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'content' => $response,
        'http_code' => $info['http_code'],
        'total_time' => $info['total_time'],
        'size' => $info['size_download'],
        'error' => $error,
    ];
}

/**
 * Parse sitemap XML and extract URLs
 */
function parse_sitemap($xml_content)
{
    $urls = [];
    $sitemaps = [];

    // Suppress XML parsing warnings
    libxml_use_internal_errors(true);

    $xml = simplexml_load_string($xml_content);

    if ($xml === false) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        return ['urls' => [], 'sitemaps' => [], 'error' => 'Failed to parse XML'];
    }

    // Register namespaces
    $namespaces = $xml->getNamespaces(true);

    // Check if this is a sitemap index
    if (isset($xml->sitemap)) {
        foreach ($xml->sitemap as $sitemap) {
            $sitemaps[] = (string) $sitemap->loc;
        }
    }

    // Check for regular sitemap URLs
    if (isset($xml->url)) {
        foreach ($xml->url as $url) {
            $urls[] = [
                'loc' => (string) $url->loc,
                'lastmod' => isset($url->lastmod) ? (string) $url->lastmod : null,
                'priority' => isset($url->priority) ? (float) $url->priority : null,
            ];
        }
    }

    return ['urls' => $urls, 'sitemaps' => $sitemaps, 'error' => null];
}

/**
 * Format bytes to human readable
 */
function format_bytes($bytes)
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

/**
 * Format time duration
 */
function format_duration($seconds)
{
    if ($seconds >= 3600) {
        return sprintf('%d:%02d:%02d', floor($seconds / 3600), floor(($seconds % 3600) / 60), $seconds % 60);
    } elseif ($seconds >= 60) {
        return sprintf('%d:%02d', floor($seconds / 60), $seconds % 60);
    }
    return number_format($seconds, 2) . 's';
}

// Start processing
echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                      CACHE WARMER v1.0                           ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "📍 Sitemap: $sitemap_url\n";
echo "⏱️  Delay: {$config['delay']}ms | Timeout: {$config['timeout']}s\n";
if ($config['dry_run']) {
    echo "🔍 DRY RUN MODE - No requests will be made\n";
}
echo "\n";

// Fetch and parse sitemap
echo "📥 Fetching sitemap...\n";
$result = fetch_url($sitemap_url, $config);

if ($result['http_code'] !== 200) {
    echo "❌ Failed to fetch sitemap (HTTP {$result['http_code']})\n";
    if ($result['error']) {
        echo "   Error: {$result['error']}\n";
    }
    exit(1);
}

$parsed = parse_sitemap($result['content']);

if ($parsed['error']) {
    echo "❌ {$parsed['error']}\n";
    exit(1);
}

// Collect all URLs (handle sitemap index)
$all_urls = $parsed['urls'];

if (!empty($parsed['sitemaps'])) {
    echo "📚 Found sitemap index with " . count($parsed['sitemaps']) . " sitemaps\n\n";

    foreach ($parsed['sitemaps'] as $index => $child_sitemap) {
        echo "  📄 Parsing sitemap " . ($index + 1) . "/" . count($parsed['sitemaps']) . ": " . basename($child_sitemap) . "\n";

        $child_result = fetch_url($child_sitemap, $config);

        if ($child_result['http_code'] === 200) {
            $child_parsed = parse_sitemap($child_result['content']);
            if (!$child_parsed['error']) {
                $all_urls = array_merge($all_urls, $child_parsed['urls']);
                echo "     Found " . count($child_parsed['urls']) . " URLs\n";
            }
        } else {
            echo "     ⚠️  Failed to fetch (HTTP {$child_result['http_code']})\n";
        }

        usleep($config['delay'] * 1000);
    }
    echo "\n";
}

$total_urls = count($all_urls);
echo "🔗 Total URLs found: $total_urls\n\n";

if ($total_urls === 0) {
    echo "❌ No URLs found in sitemap\n";
    exit(1);
}

if ($config['dry_run']) {
    echo "URLs that would be warmed:\n";
    echo str_repeat('-', 70) . "\n";
    foreach ($all_urls as $url) {
        echo "  " . $url['loc'] . "\n";
    }
    echo str_repeat('-', 70) . "\n";
    echo "\n✅ Dry run complete. Found $total_urls URLs.\n";
    exit(0);
}

// Warm cache for each URL
echo "🔥 Starting cache warm-up...\n";
echo str_repeat('─', 70) . "\n";

$stats = [
    'success' => 0,
    'failed' => 0,
    'total_time' => 0,
    'total_size' => 0,
    'start_time' => microtime(true),
];

foreach ($all_urls as $index => $url) {
    $current = $index + 1;
    $progress = str_pad("[$current/$total_urls]", 12);
    $short_url = strlen($url['loc']) > 50 ? '...' . substr($url['loc'], -47) : $url['loc'];

    echo "$progress $short_url ";

    $result = fetch_url($url['loc'], $config, true);

    if ($result['http_code'] >= 200 && $result['http_code'] < 400) {
        $stats['success']++;
        $stats['total_time'] += $result['total_time'];
        $stats['total_size'] += $result['size'];

        $time_str = number_format($result['total_time'] * 1000, 0) . 'ms';
        $size_str = format_bytes($result['size']);

        echo "✅ {$result['http_code']} ({$time_str}, {$size_str})\n";

        if ($config['verbose']) {
            echo "     Time: {$result['total_time']}s | Size: {$result['size']} bytes\n";
        }
    } else {
        $stats['failed']++;
        echo "❌ {$result['http_code']}";
        if ($result['error']) {
            echo " - {$result['error']}";
        }
        echo "\n";
    }

    // Delay between requests
    if ($index < $total_urls - 1) {
        usleep($config['delay'] * 1000);
    }
}

$stats['end_time'] = microtime(true);
$stats['elapsed'] = $stats['end_time'] - $stats['start_time'];

// Print summary
echo str_repeat('─', 70) . "\n";
echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                          SUMMARY                                 ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "📊 Results:\n";
echo "   ✅ Successful: {$stats['success']}\n";
echo "   ❌ Failed: {$stats['failed']}\n";
echo "   📦 Total data: " . format_bytes($stats['total_size']) . "\n";
echo "\n";
echo "⏱️  Timing:\n";
echo "   Total elapsed: " . format_duration($stats['elapsed']) . "\n";
echo "   Avg response: " . ($stats['success'] > 0 ? number_format(($stats['total_time'] / $stats['success']) * 1000, 0) . 'ms' : 'N/A') . "\n";
echo "\n";

if ($stats['failed'] === 0) {
    echo "🎉 Cache warm-up completed successfully!\n";
} else {
    echo "⚠️  Cache warm-up completed with {$stats['failed']} failures.\n";
}

echo "\n";
exit($stats['failed'] > 0 ? 1 : 0);
