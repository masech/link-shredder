<?php

namespace Lish;

/**
 * Layer for inserting and finding data in the database.
 */
class LinkRepository
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function find($ids)
    {
        $data = [];
        
        if (empty($ids)) {
            return $data;
        }

        $stmt = $this->pdo->prepare("SELECT link FROM links WHERE id = ?");
        foreach ($ids as $id) {
            $stmt->execute([$id]);
            $data[] = $stmt->fetchColumn();
        }

        return $data;
    }

    /**
     * @param string $link
     */
    public function insert($link)
    {
        $stmt = $this->pdo->prepare("INSERT INTO links (link) VALUES (?)");
        $stmt->execute([$link]);
    }

    /**
     * @return string
     */
    public function getLastId()
    {
        return $this->pdo->lastInsertId();
    }
}
