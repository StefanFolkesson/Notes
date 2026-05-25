<?php

declare(strict_types=1);

function getDatabase(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dataDirectory = __DIR__ . '/../data';
    if (!is_dir($dataDirectory)) {
        mkdir($dataDirectory, 0700, true);
    }

    $databasePath = $dataDirectory . '/notes.sqlite';
    $isNewDatabase = !file_exists($databasePath);
    $pdo = new PDO('sqlite:' . $databasePath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($isNewDatabase && !chmod($databasePath, 0600)) {
        throw new RuntimeException('Could not set secure permissions on SQLite database file.');
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS notes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );

    return $pdo;
}
