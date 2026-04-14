<?php
/**
 * Movie Records Sync Script
 * Syncs moviedb table records to Algolia index
 */

require_once 'vendor/autoload.php';

use Algolia\AlgoliaSearch\SearchClient;

// ─────────────────────────────────────────
// Configuration
// ─────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_NAME',     'moviedb');
define('DB_USER',     'root');
define('DB_PASS',     '');

define('ALGOLIA_APP_ID',    'MGJ3CMU153');
define('ALGOLIA_ADMIN_KEY', '');
define('ALGOLIA_INDEX',     'movies');

define('BATCH_SIZE', 1000);

// ─────────────────────────────────────────
// Database Connection
// ─────────────────────────────────────────
function getDbConnection(): PDO {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

// ─────────────────────────────────────────
// Fetch Movies — matches your exact columns
// ─────────────────────────────────────────
function fetchMovies(PDO $pdo): Generator {
    $sql = "
        SELECT
            id,
            release_date,
            title,
            overview,
            vote_average,
            original_language,
            genre,
            poster_url
        FROM moviedb
        ORDER BY id ASC
    ";

    $stmt = $pdo->query($sql);

    while ($row = $stmt->fetch()) {
        yield [
            'objectID'          => (string) $row['id'],
            'title'             => $row['title'],
            'overview'          => $row['overview'],
            'genre'             => $row['genre'],
            'vote_average'      => (float)  $row['vote_average'],
            'original_language' => $row['original_language'],
            'poster_url'        => $row['poster_url'],
            'release_date'      => $row['release_date'],
            // Unix timestamp version for Algolia date filtering/sorting
            'release_timestamp' => $row['release_date']
                                    ? strtotime($row['release_date'])
                                    : null,
        ];
    }
}

// ─────────────────────────────────────────
// Configure Algolia Index Settings
// ─────────────────────────────────────────
function configureIndex($index): void {
    $index->setSettings([
        // Fields users can search through
        'searchableAttributes' => [
            'title',           // highest priority
            'genre',
            'overview',
            'original_language',
        ],
        // Fields usable as filters/facets in InstantSearch UI
        'attributesForFaceting' => [
            'genre',
            'original_language',
            'vote_average',
            'release_date',
            'release_timestamp',
        ],
        // Default ranking: highest rated + most recent first
        'customRanking' => [
            'desc(vote_average)',
            'desc(release_timestamp)',
        ],
        // Attributes to return in search results
        'attributesToRetrieve' => [
            'title',
            'overview',
            'genre',
            'vote_average',
            'original_language',
            'poster_url',
            'release_date',
            'release_timestamp',
        ],
    ]);

    echo "✅ Index settings configured.\n";
}

// ─────────────────────────────────────────
// Sync Records to Algolia in Batches
// ─────────────────────────────────────────
function syncToAlgolia(Generator $movies): void {
    $client = SearchClient::create(ALGOLIA_APP_ID, ALGOLIA_ADMIN_KEY);
    $index  = $client->initIndex(ALGOLIA_INDEX);

    configureIndex($index);

    $batch      = [];
    $total      = 0;
    $batchNum   = 0;

    echo "\nStarting sync...\n";
    echo str_repeat('─', 40) . "\n";

    foreach ($movies as $record) {
        $batch[] = $record;

        if (count($batch) >= BATCH_SIZE) {
            $batchNum++;
            echo "📦 Pushing batch #{$batchNum} → " . count($batch) . " records\n";
            $index->saveObjects($batch);
            $total += count($batch);
            $batch  = [];
        }
    }

    // Push remaining records
    if (!empty($batch)) {
        $batchNum++;
        echo "📦 Pushing final batch #{$batchNum} → " . count($batch) . " records\n";
        $index->saveObjects($batch);
        $total += count($batch);
    }

    echo str_repeat('─', 40) . "\n";
    echo "✅ Sync complete! Total records pushed: {$total}\n";
}

// ─────────────────────────────────────────
// Main
// ─────────────────────────────────────────
try {
    echo "🔌 Connecting to MySQL (db: " . DB_NAME . ")...\n";
    $pdo = getDbConnection();
    echo "✅ Connected.\n";

    echo "🔍 Fetching from table: moviedb\n";
    $movies = fetchMovies($pdo);

    echo "🚀 Syncing to Algolia index: " . ALGOLIA_INDEX . "\n";
    syncToAlgolia($movies);

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Algolia error: " . $e->getMessage() . "\n";
    exit(1);
}