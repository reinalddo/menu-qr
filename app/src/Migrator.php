<?php

declare(strict_types=1);

namespace MenuQr;

use PDO;

/**
 * Applies app/migrations/*.sql in order. Each file holds a single statement:
 * MySQL commits DDL implicitly, so a multi-statement file could stop halfway.
 */
final class Migrator
{
    private const LOCK = 'menuqr_migrations';

    public function __construct(private PDO $pdo, private string $directory)
    {
    }

    /** @return string[] versions applied in this run */
    public function run(): array
    {
        $locked = (int) $this->pdo->query("SELECT GET_LOCK('" . self::LOCK . "', 60)")->fetchColumn();
        if ($locked !== 1) {
            throw new \RuntimeException('Could not acquire the migrations lock');
        }

        try {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS schema_migrations (
                    version VARCHAR(100) NOT NULL PRIMARY KEY,
                    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );

            $applied = $this->pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
            $applied = array_flip($applied);

            $files = glob($this->directory . '/*.sql') ?: [];
            sort($files, SORT_STRING);

            $ran = [];
            foreach ($files as $file) {
                $version = basename($file, '.sql');
                if (isset($applied[$version])) {
                    continue;
                }
                $sql = trim((string) file_get_contents($file));
                if ($sql !== '') {
                    $this->pdo->exec($sql);
                }
                $insert = $this->pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
                $insert->execute([$version]);
                $ran[] = $version;
            }
            return $ran;
        } finally {
            $this->pdo->query("SELECT RELEASE_LOCK('" . self::LOCK . "')");
        }
    }
}
