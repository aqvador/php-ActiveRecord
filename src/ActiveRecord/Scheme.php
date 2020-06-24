<?php
/**
 * Project: aqvador\activeRecord
 * User: achelnokov
 * Date: 24.06.2020г.
 * Time: 22:15
 */

namespace aqvador\ActiveRecord;

use PDO;

class Scheme
{
    const PREFIX = '.scheme';
    protected $dir = __DIR__.'/schemes/';
    private $tableName;
    private $cache;
    private $expire;
    private $file;

    public function __construct($tableName, $cache = true, $expire = 600)
    {
        $this->tableName = $tableName;
        $this->expire = $expire;
        is_dir($this->dir) or mkdir($this->dir, '0755', true);
        $this->file = $this->dir.$this->tableName.self::PREFIX;
        is_file($this->file) or touch($this->file);
    }

    /**
     * @param $pdo PDO
     *
     * @return array
     */
    public function getScheme(PDO $pdo)
    {
        if ($this->cache && ($scheme = $this->getSchemeCache())) {
            return $scheme;
        }
        return $this->getSchemeSql($pdo);
    }

    protected function getSchemeSql($pdo)
    {
        $scheme = $pdo->query('SHOW COLUMNS FROM '.$this->tableName)->fetchAll();
        return $this->setScheme(array_column($scheme, 'Type', 'Field'));
    }

    /**
     * @return bool|array
     */
    protected function getSchemeCache()
    {
        $scheme = unserialize(file_get_contents($this->dir.$this->tableName.self::PREFIX));
        if ($scheme) {
            return $scheme->expire < time() ? false : $scheme->data;
        }
        return false;
    }

    protected function setScheme($scheme)
    {
        file_put_contents($this->file, serialize((object)['data' => $scheme, 'expire' => time() + $this->expire]));
        return $scheme;
    }
}