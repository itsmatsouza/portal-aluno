<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Database;

use PDO;
use RuntimeException;

class MigrationRunner
{
    private PDO $db;
    private string $migrationsPath;

    public function __construct(PDO $db, string $migrationsPath)
    {
        $this->db = $db;
        $this->migrationsPath = rtrim($migrationsPath, '/');
    }

    public function run(): void
    {
        $this->createMigrationsTable();

        $files = glob($this->migrationsPath . '/*.sql');

        if ($files === false) {
            throw new RuntimeException(
                'Não foi possível localizar as migrations.'
            );
        }

        sort($files, SORT_STRING);

        foreach ($files as $file) {
            $migration = basename($file);

            if ($this->alreadyExecuted($migration)) {
                continue;
            }

            $this->executeMigration($file, $migration);
        }
    }

    private function createMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                migration VARCHAR(255) NOT NULL,
                executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (id),
                UNIQUE KEY uq_migrations_migration (migration)
            ) ENGINE=InnoDB
              DEFAULT CHARSET=utf8mb4
              COLLATE=utf8mb4_unicode_ci
        ";

        $this->db->exec($sql);
    }

    private function alreadyExecuted(string $migration): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id
             FROM migrations
             WHERE migration = :migration
             LIMIT 1'
        );

        $stmt->execute([
            'migration' => $migration
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function executeMigration(
        string $file,
        string $migration
    ): void {
        $sql = file_get_contents($file);

        if ($sql === false) {
            throw new RuntimeException(
                "Não foi possível ler a migration: {$migration}"
            );
        }

        try {
            $this->db->beginTransaction();

            /*
             * As migrations atuais podem conter mais de uma
             * instrução SQL. PDO não possui equivalente direto
             * ao multi_query() do MySQLi.
             *
             * Por isso, dividimos as instruções pelo delimitador ;
             */
            $statements = $this->splitSqlStatements($sql);

            foreach ($statements as $statement) {
                if (trim($statement) === '') {
                    continue;
                }

                $this->db->exec($statement);
            }

            $stmt = $this->db->prepare(
                'INSERT INTO migrations (migration)
                 VALUES (:migration)'
            );

            $stmt->execute([
                'migration' => $migration
            ]);

            $this->db->commit();

        } catch (\Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw new RuntimeException(
                "Erro na migration {$migration}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function splitSqlStatements(string $sql): array
    {
        return preg_split(
            '/;\s*(?=(?:[^\'"]|\'[^\']*\'|"[^"]*")*$)/',
            $sql
        ) ?: [];
    }
}