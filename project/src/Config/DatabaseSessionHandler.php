<?php

namespace App\Config;

use PDO;
use SessionHandlerInterface;

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private ?PDO $database = null;

    public function open(string $path, string $name): bool
    {
        $this->database = Database::getInstance();
        return true;
    }

    public function close(): bool
    {
        $this->database = null;
        return true;
    }

    public function read(string $id): string|false
    {
        $statement = $this->database->prepare(
            'SELECT session_data FROM app_sessions WHERE session_id = :session_id AND last_accessed >= :expires_at'
        );
        $statement->execute([
            'session_id' => $id,
            'expires_at' => time() - (int)ini_get('session.gc_maxlifetime')
        ]);

        $data = $statement->fetchColumn();
        return $data === false ? '' : (string)$data;
    }

    public function write(string $id, string $data): bool
    {
        $statement = $this->database->prepare(
            'INSERT INTO app_sessions (session_id, session_data, last_accessed)
             VALUES (:session_id, :session_data, :last_accessed)
             ON CONFLICT (session_id) DO UPDATE SET
                 session_data = EXCLUDED.session_data,
                 last_accessed = EXCLUDED.last_accessed'
        );

        return $statement->execute([
            'session_id' => $id,
            'session_data' => $data,
            'last_accessed' => time()
        ]);
    }

    public function destroy(string $id): bool
    {
        $statement = $this->database->prepare(
            'DELETE FROM app_sessions WHERE session_id = :session_id'
        );
        return $statement->execute(['session_id' => $id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $statement = $this->database->prepare(
            'DELETE FROM app_sessions WHERE last_accessed < :expires_at'
        );
        $statement->execute(['expires_at' => time() - $max_lifetime]);
        return $statement->rowCount();
    }
}
