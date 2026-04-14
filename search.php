<?php
/**
 * Movie Search API Endpoint
 * Handles search queries from the frontend and returns results from Algolia
 */

require_once 'vendor/autoload.php';

use Algolia\AlgoliaSearch\SearchClient;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ─────────────────────────────────────────
// Configuration
// ─────────────────────────────────────────
define('ALGOLIA_APP_ID',     'MGJ3CMU153');
define('ALGOLIA_SEARCH_KEY', '5a24e8a7d196c7d9a5ec38c917e2acb0');
define('ALGOLIA_INDEX',      'movies');

// ─────────────────────────────────────────
// Get Search Parameters
// ─────────────────────────────────────────
$query   = isset($_GET['q']) ? trim($_GET['q']) : '';
$page    = isset($_GET['page']) ? (int)$_GET['page'] : 0;
$filters = isset($_GET['filters']) ? $_GET['filters'] : '';
$limit   = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 20;

// ─────────────────────────────────────────
// Validate Input
// ─────────────────────────────────────────
if (empty($query) && empty($filters)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Either query parameter "q" or filters are required',
        'success' => false
    ]);
    exit(1);
}

// ─────────────────────────────────────────
// Search Algolia
// ─────────────────────────────────────────
try {
    $client = SearchClient::create(ALGOLIA_APP_ID, ALGOLIA_SEARCH_KEY);
    $index  = $client->initIndex(ALGOLIA_INDEX);

    // Build search options
    $options = [
        'page'              => $page,
        'hitsPerPage'       => $limit,
        'attributesToRetrieve' => ['objectID', 'title', 'genre', 'overview', 'vote_average', 'original_language', 'release_date', 'release_timestamp', 'poster_url'],
    ];

    // Apply filters if provided (e.g., "genre:Action" or "release_timestamp >= 1577836800")
    if (!empty($filters)) {
        $options['filters'] = $filters;
    }

    // Execute search
    $results = $index->search($query, $options);

    // Format response
    $response = [
        'success' => true,
        'query'   => $query,
        'hits'    => $results['hits'],
        'nbHits'  => $results['nbHits'],
        'page'    => $results['page'],
        'nbPages' => $results['nbPages'],
        'hitsPerPage' => $results['hitsPerPage'],
    ];

    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => $e->getMessage(),
        'success' => false
    ]);
    exit(1);
}
