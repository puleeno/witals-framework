<?php

declare(strict_types=1);

namespace Witals\Framework\Context;

class DatabaseLayoutStorage implements LayoutStorage
{
    protected array $cache = [];

    public function __construct(
        protected string $table = 'context_layouts',
        protected ?object $db = null,
    ) {}

    public function get(string $type, string $identifier): ?array
    {
        $key = $this->key($type, $identifier);

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        if ($this->db === null) {
            return null;
        }

        $row = $this->db->query(
            "SELECT block_tree FROM {$this->table} WHERE context_type = ? AND context_id = ? LIMIT 1",
            [$type, $identifier]
        );

        if ($row === null || empty($row->block_tree)) {
            return null;
        }

        $tree = json_decode($row->block_tree, true);
        $this->cache[$key] = $tree;

        return $tree;
    }

    public function set(string $type, string $identifier, array $blockTree): void
    {
        $key = $this->key($type, $identifier);
        $this->cache[$key] = $blockTree;

        if ($this->db === null) {
            return;
        }

        $json = json_encode($blockTree);

        if ($this->has($type, $identifier)) {
            $this->db->query(
                "UPDATE {$this->table} SET block_tree = ?, updated_at = NOW() WHERE context_type = ? AND context_id = ?",
                [$json, $type, $identifier]
            );
        } else {
            $this->db->query(
                "INSERT INTO {$this->table} (context_type, context_id, block_tree, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
                [$type, $identifier, $json]
            );
        }
    }

    public function delete(string $type, string $identifier): void
    {
        $key = $this->key($type, $identifier);
        unset($this->cache[$key]);

        if ($this->db === null) {
            return;
        }

        $this->db->query(
            "DELETE FROM {$this->table} WHERE context_type = ? AND context_id = ?",
            [$type, $identifier]
        );
    }

    public function has(string $type, string $identifier): bool
    {
        $key = $this->key($type, $identifier);

        if (isset($this->cache[$key])) {
            return true;
        }

        if ($this->db === null) {
            return false;
        }

        $row = $this->db->query(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE context_type = ? AND context_id = ?",
            [$type, $identifier]
        );

        return ($row->cnt ?? 0) > 0;
    }

    protected function key(string $type, string $identifier): string
    {
        return $type . '::' . $identifier;
    }
}
