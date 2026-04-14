<?php
require_once 'vendor/autoload.php';

// Configuration values
$config = [
    'ALGOLIA_APP_ID' => 'MGJ3CMU153',
    'ALGOLIA_SEARCH_KEY' => '5a24e8a7d196c7d9a5ec38c917e2acb0',
    'ALGOLIA_ADMIN_KEY' => '',
    'ALGOLIA_INDEX' => 'movies',
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'moviedb',
    'DB_USER' => 'root',
    'DB_PASS' => '',
];

echo "Configuration values:\n\n";
foreach ($config as $key => $value) {
    echo "$key: " . var_export($value, true) . "\n";
}

