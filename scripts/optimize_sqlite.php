<?php
$dbPath = __DIR__ . '/../database/database.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->exec('PRAGMA journal_mode = WAL;');
$pdo->exec('PRAGMA synchronous = NORMAL;');
$pdo->exec('PRAGMA cache_size = 10000;');
$pdo->exec('PRAGMA temp_store = MEMORY;');
$mode = $pdo->query('PRAGMA journal_mode;')->fetchColumn();
$sync = $pdo->query('PRAGMA synchronous;')->fetchColumn();
echo "SQLite Optimized! Journal Mode: {$mode}, Synchronous: {$sync}\n";
