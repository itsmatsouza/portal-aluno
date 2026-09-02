<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Database;

use mysqli;
use RuntimeException;

class Connection
{
    private mysqli $connection;

    public function __construct(array $config)
    {
        $this->connection = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database'],
            (int) $config['port']
        );

        if ($this->connection->connect_errno) {
            throw new RuntimeException(
                'Erro ao conectar ao banco de dados: ' .
                $this->connection->connect_error
            );
        }

        $this->connection->set_charset('utf8mb4');
    }

    public function getConnection(): mysqli
    {
        return $this->connection;
    }
}