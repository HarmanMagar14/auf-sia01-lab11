<?php
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Checking with different methods:\n\n";
echo "getenv('ALGOLIA_APP_ID'): " . var_export(getenv('ALGOLIA_APP_ID'), true) . "\n";
echo "getenv('DB_NAME'): " . var_export(getenv('DB_NAME'), true) . "\n";
echo "\$_ENV['ALGOLIA_APP_ID']: " . var_export($_ENV['ALGOLIA_APP_ID'] ?? 'NOT SET', true) . "\n";
echo "\$_ENV['DB_NAME']: " . var_export($_ENV['DB_NAME'] ?? 'NOT SET', true) . "\n";

echo "\n.env file exists: " . (file_exists('.env') ? 'YES' : 'NO') . "\n";
echo ".env path: " . realpath('.env') . "\n";
echo ".env contents:\n";
echo file_get_contents('.env');

