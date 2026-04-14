# Movie Sync Script - Setup & Usage

## Overview
This script iterates through all movie records in MySQL and syncs them to Algolia Search index in batches.

## Prerequisites
- PHP 7.4+
- Composer
- MySQL server with `movies_db` database
- Algolia account with app credentials

## Setup Steps

### 1. Install Dependencies
```bash
composer install
```

### 2. Configure Environment
Update `.env` with your credentials:
```env
DB_HOST=localhost
DB_NAME=movies_db
DB_USER=root
DB_PASS=secret
ALGOLIA_APP_ID=your_app_id
ALGOLIA_ADMIN_KEY=your_admin_key
ALGOLIA_INDEX=movies
```

### 3. Create Database Table (if not exists)
```sql
CREATE TABLE IF NOT EXISTS movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    genre VARCHAR(100),
    director VARCHAR(255),
    cast TEXT,
    release_year INT,
    rating DECIMAL(2,1),
    synopsis TEXT,
    poster_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_year (release_year)
);
```

### 4. Run the Sync Script
```bash
php sync.php
```

## Script Features
- **Batch Processing**: Uploads 1000 records per batch for efficiency
- **Memory Efficient**: Uses PHP Generators to avoid loading all records at once
- **Smart Indexing**: Configures Algolia index with:
  - Searchable: title, director, cast, genre, synopsis
  - Facetable: genre, release_year, rating
  - Ranking: by rating and release year
- **Error Handling**: Comprehensive exception handling for DB and Algolia errors
- **Progress Logging**: Real-time feedback on batch processing

## Script Flow
1. Connect to MySQL database
2. Fetch movies in streaming fashion
3. Transform DB records to Algolia format
4. Push records in batches of 1000
5. Display sync statistics

## Notes
- The script uses `objectID` = movie `id` for record tracking
- `cast` field is split from CSV to array
- `created_at` is converted to Unix timestamp for Algolia filtering
- Existing records in Algolia are automatically updated by `saveObjects()`
