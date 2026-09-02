<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Database;

use mysqli;
use RuntimeException;

class MigrationRunner
{
    private mysqli $db;
    private string $migrationsPath;

    public function __construct(mysqli $db, string $migrationsPath)
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

        if (!$this->db->query($sql)) {
            throw new RuntimeException(
                'Erro ao criar tabela migrations: ' .
                $this->db->error
            );
        }
    }

    private function alreadyExecuted(string $migration): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM migrations WHERE migration = ? LIMIT 1'
        );

        if (!$stmt) {
            throw new RuntimeException(
                'Erro ao preparar consulta de migrations: ' .
                $this->db->error
            );
        }

        $stmt->bind_param('s', $migration);
        $stmt->execute();

        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;

        $stmt->close();

        return $exists;
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

        if (!$this->db->begin_transaction()) {
            throw new RuntimeException(
                'Não foi possível iniciar a transação.'
            );
        }

        try {
            if (!$this->db->multi_query($sql)) {
                throw new RuntimeException(
                    "Erro na migration {$migration}: " .
                    $this->db->error
                );
            }

            do {
                if ($result = $this->db->store_result()) {
                    $result->free();
                }
            } while ($this->db->more_results() && $this->db->next_result());

            if ($this->db->errno) {
                throw new RuntimeException(
                    "Erro na migration {$migration}: " .
                    $this->db->error
                );
            }

            $stmt = $this->db->prepare(
                'INSERT INTO migrations (migration) VALUES (?)'
            );

            if (!$stmt) {
                throw new RuntimeException(
                    'Erro ao registrar migration: ' .
                    $this->db->error
                );
            }

            $stmt->bind_param('s', $migration);
            $stmt->execute();
            $stmt->close();

            $this->db->commit();

        } catch (\Throwable $e) {

            $this->db->rollback();

            throw $e;
        }
    }
}