<?php
namespace Mindwalk;

class DocumentFetchDatabase
{
    private $databasePath;

    /**
     * DocumentFetchDatabase constructor.
     * @param string $databasePath
     * @throws \Exception
     */
    public function __construct($databasePath)
    {
        $this->databasePath = $databasePath;

        $directory = pathinfo($this->databasePath, PATHINFO_DIRNAME);
        if (!@mkdir($directory) && !@is_dir($directory)) {
            throw new \Exception('Failed to create database directory ' . $directory);
        }

        if (!file_exists($this->databasePath)) {
            touch($this->databasePath);
        }
    }

    /**
     * @return array
     */
    private function getDatabase()
    {
        $database = @file_get_contents($this->databasePath);

        if (empty($database)) {
            return [];
        }

        return (array) unserialize($database);
    }

    /**
     * @param array $array
     * @return bool
     */
    private function saveDatabase(array $array)
    {
        return @file_put_contents($this->databasePath, serialize($array)) !== false;
    }

    /**
     * @return bool
     */
    public function truncate()
    {
        return $this->saveDatabase([]);
    }

    /**
     * @param $hash
     * @return bool
     */
    public function store($hash)
    {
        $database = $this->getDatabase();
        $database[$hash] = time();
        return $this->saveDatabase($database);
    }

    /**
     * @param string $hash
     * @return bool
     */
    public function exists($hash)
    {
        $database = $this->getDatabase();
        return isset($database[$hash]);
    }
}