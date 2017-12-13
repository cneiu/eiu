<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cache\adapter;


use eiu\components\cache\CacheCacheException;


/**
 * SQLite 缓存适配器
 *
 * @package eiu\components\cache\adapter
 */
class Sqlite extends AbstractICacheAdapter
{
    
    /**
     * Cache db file
     *
     * @var string
     */
    protected $db = null;
    
    /**
     * Cache db table
     *
     * @var string
     */
    protected $table = 'eiu_cache';
    
    /**
     * Sqlite DB object
     *
     * @var \PDO|\SQLite3
     */
    protected $sqlite = null;
    
    /**
     * Sqlite DB statement object (either a PDOStatement or SQLite3Stmt object)
     *
     * @var mixed
     */
    protected $statement = null;
    
    /**
     * Database results
     *
     * @var resource
     */
    protected $result;
    
    /**
     * PDO flag
     *
     * @var boolean
     */
    protected $isPdo = false;
    
    /**
     * Constructor
     *
     * Instantiate the cache db object
     *
     * @param  string  $db
     * @param  int     $ttl
     * @param  string  $table
     * @param  boolean $pdo
     *
     * @throws CacheException
     */
    public function __construct($db, $ttl = 0, $table = 'eiu_cache', $pdo = false)
    {
        parent::__construct($ttl);
        
        $this->setDb($db);
        
        $pdoDrivers = (class_exists('Pdo', false)) ? \PDO::getAvailableDrivers() : [];
        if (!class_exists('Sqlite3', false) && !in_array('sqlite', $pdoDrivers))
        {
            throw new CacheCacheException('SQLite is not available');
        }
        else if (($pdo) && !in_array('sqlite', $pdoDrivers))
        {
            $pdo = false;
        }
        else if ((!$pdo) && !class_exists('Sqlite3', false))
        {
            $pdo = true;
        }
        
        if ($pdo)
        {
            $this->sqlite = new \PDO('sqlite:' . $this->db);
            $this->isPdo  = true;
        }
        else
        {
            $this->sqlite = new \SQLite3($this->db);
        }
        
        if (null !== $table)
        {
            $this->setTable($table);
        }
    }
    
    /**
     * 获取数据库文件
     *
     * @return string
     */
    public function getDb()
    {
        return $this->db;
    }
    
    /**
     * 设置数据库文件
     *
     * @param  string $db
     *
     * @throws CacheException
     * @return Sqlite
     */
    public function setDb($db)
    {
        $this->db = $db;
        $dir      = dirname($this->db);
        
        // If the database file doesn't exist, create it.
        if (!file_exists($this->db))
        {
            if (is_writable($dir))
            {
                touch($db);
            }
            else
            {
                throw new CacheException('That cache db file and/or directory is not writable');
            }
        }
        
        // Check the permissions, access the database and check for the cache table.
        if (!is_writable($dir) || !is_writable($this->db))
        {
            throw new CacheException('That cache db file and/or directory is not writable');
        }
        
        if (!class_exists('Sqlite3', false) && !class_exists('Pdo', false))
        {
            throw new CacheException('Neither SQLite3 or PDO are available');
        }
        
        return $this;
    }
    
    /**
     * 获取数据表
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }
    
    /**
     * 设置数据表
     *
     * @param  string $table
     *
     * @return Sqlite
     */
    public function setTable($table)
    {
        $this->table = addslashes($table);
        $this->checkTable();
        
        return $this;
    }
    
    /**
     * 获取指定缓存过期时间
     *
     * @param  string $id
     *
     * @return int
     */
    public function getItemTtl($id)
    {
        $ttl = 0;
        
        // Determine if the value already exists.
        $rows = [];
        
        $this->prepare('SELECT * FROM "' . $this->table . '" WHERE "id" = :id')
            ->bindParams(['id' => sha1($id)])
            ->execute();
        
        if ($this->isPdo)
        {
            $rows = $this->statement->fetchAll(\PDO::FETCH_ASSOC);
        }
        else
        {
            while (($row = $this->result->fetchArray(SQLITE3_ASSOC)) != false)
            {
                $rows[] = $row;
            }
        }
        
        // If the value is found, check expiration and return.
        if (count($rows) > 0)
        {
            $cacheValue = $rows[0];
            $ttl        = $cacheValue['ttl'];
        }
        
        return $ttl;
    }
    
    /**
     * 执行SQL语句
     *
     * @throws CacheException
     * @return void
     */
    protected function execute()
    {
        if (null === $this->statement)
        {
            throw new CacheException('The database statement resource is not currently set');
        }
        
        $this->result = $this->statement->execute();
    }
    
    /**
     * 绑定查询参数
     *
     * @param  array $params
     *
     * @return Sqlite
     */
    protected function bindParams($params)
    {
        foreach ($params as $dbColumnName => $dbColumnValue)
        {
            ${$dbColumnName} = $dbColumnValue;
            $this->statement->bindParam(':' . $dbColumnName, ${$dbColumnName});
        }
        
        return $this;
    }
    
    /**
     * 预查询
     *
     * @param  string $sql
     *
     * @return Sqlite
     */
    protected function prepare($sql)
    {
        $this->statement = $this->sqlite->prepare($sql);
        
        return $this;
    }
    
    /**
     * 写入一个缓存
     *
     * @param  string $id
     * @param  mixed  $value
     * @param  int    $ttl
     *
     * @return Sqlite
     */
    public function saveItem($id, $value, $ttl = null)
    {
        // Determine if the value already exists.
        $rows = [];
        
        $this->prepare('SELECT * FROM "' . $this->table . '" WHERE "id" = :id')
            ->bindParams(['id' => sha1($id)])
            ->execute();
        
        if ($this->isPdo)
        {
            $rows = $this->statement->fetchAll(\PDO::FETCH_ASSOC);
        }
        else
        {
            while (($row = $this->result->fetchArray(SQLITE3_ASSOC)) != false)
            {
                $rows[] = $row;
            }
        }
        
        // If the value doesn't exist, save the new value.
        if (count($rows) == 0)
        {
            $sql    = 'INSERT INTO "' . $this->table .
                      '" ("id", "start", "ttl", "value") VALUES (:id, :start, :ttl, :value)';
            $params = [
                'id'    => sha1($id),
                'start' => time(),
                'ttl'   => (null !== $ttl) ? (int)$ttl : $this->ttl,
                'value' => serialize($value),
            ];
            // Else, update it.
        }
        else
        {
            $sql    = 'UPDATE "' . $this->table .
                      '" SET "start" = :start, "ttl" = :ttl, "value" = :value WHERE "id" = :id';
            $params = [
                'start' => time(),
                'ttl'   => (null !== $ttl) ? (int)$ttl : $this->ttl,
                'value' => serialize($value),
                'id'    => sha1($id),
            ];
        }
        
        // Save value
        $this->prepare($sql)
            ->bindParams($params)
            ->execute();
        
        return $this;
    }
    
    /**
     * 获取指定缓存
     *
     * @param  string $id
     *
     * @return mixed
     */
    public function getItem($id)
    {
        $value = false;
        
        // Determine if the value already exists.
        $rows = [];
        
        $this->prepare('SELECT * FROM "' . $this->table . '" WHERE "id" = :id')
            ->bindParams(['id' => sha1($id)])
            ->execute();
        
        if ($this->isPdo)
        {
            $rows = $this->statement->fetchAll(\PDO::FETCH_ASSOC);
        }
        else
        {
            while (($row = $this->result->fetchArray(SQLITE3_ASSOC)) != false)
            {
                $rows[] = $row;
            }
        }
        
        // If the value is found, check expiration and return.
        if (count($rows) > 0)
        {
            $cacheValue = $rows[0];
            if (($cacheValue['ttl'] == 0) || ((time() - $cacheValue['start']) <= $cacheValue['ttl']))
            {
                $value = unserialize($cacheValue['value']);
            }
            else
            {
                $this->deleteItem($id);
            }
        }
        
        return $value;
    }
    
    /**
     * 删除指定缓存
     *
     * @param  string $id
     *
     * @return Sqlite
     */
    public function deleteItem($id)
    {
        $this->prepare('DELETE FROM "' . $this->table . '" WHERE "id" = :id')
            ->bindParams(['id' => sha1($id)])
            ->execute();
        
        return $this;
    }
    
    /**
     * 判断指定缓存是否存在
     *
     * @param  string $id
     *
     * @return boolean
     */
    public function hasItem($id)
    {
        $result = false;
        
        // Determine if the value already exists.
        $rows = [];
        
        $this->prepare('SELECT * FROM "' . $this->table . '" WHERE "id" = :id')
            ->bindParams(['id' => sha1($id)])
            ->execute();
        
        if ($this->isPdo)
        {
            $rows = $this->statement->fetchAll(\PDO::FETCH_ASSOC);
        }
        else
        {
            while (($row = $this->result->fetchArray(SQLITE3_ASSOC)) != false)
            {
                $rows[] = $row;
            }
        }
        
        // If the value is found, check expiration and return.
        if (count($rows) > 0)
        {
            $cacheValue = $rows[0];
            $result     = (($cacheValue['ttl'] == 0) || ((time() - $cacheValue['start']) <= $cacheValue['ttl']));
        }
        
        return $result;
    }
    
    /**
     * 清除所有缓存
     *
     * @return Sqlite
     */
    public function clear()
    {
        $this->query('DELETE FROM "' . $this->table . '"');
        
        return $this;
    }
    
    /**
     * 执行SQL查询
     *
     * @param  string $sql
     *
     * @throws CacheException
     * @return void
     */
    public function query($sql)
    {
        if ($this->isPdo)
        {
            $sth = $this->sqlite->prepare($sql);
            
            if (!($sth->execute()))
            {
                throw new CacheException($sth->errorCode() . ': ' . $sth->errorInfo());
            }
            else
            {
                $this->result = $sth;
            }
        }
        else
        {
            if (!($this->result = $this->sqlite->query($sql)))
            {
                throw new CacheException('' . $this->sqlite->lastErrorCode() . ': ' . $this->sqlite->lastErrorMsg() . '');
            }
        }
    }
    
    /**
     * 销毁缓存器
     *
     * @return Sqlite
     */
    public function destroy()
    {
        $this->query('DELETE FROM "' . $this->table . '"');
        if (file_exists($this->db))
        {
            unlink($this->db);
        }
        
        return $this;
    }
    
    /**
     * 检查表是否存在
     *
     * @return void
     */
    protected function checkTable()
    {
        $tables = [];
        $sql    = "SELECT name FROM sqlite_master WHERE type IN ('table', 'ttemplateate') AND name NOT LIKE 'sqlite_%' " .
                  "UNION ALL SELECT name FROM sqlite_temp_master WHERE type IN ('table','templateplate') ORDER BY 1";
        
        if ($this->isPdo)
        {
            $sth = $this->sqlite->prepare($sql);
            $sth->execute();
            $result = $sth;
            while (($row = $result->fetch(\PDO::FETCH_ASSOC)) != false)
            {
                $tables[] = $row['name'];
            }
        }
        else
        {
            $result = $this->sqlite->query($sql);
            
            while (($row = $result->fetchArray(SQLITE3_ASSOC)) != false)
            {
                $tables[] = $row['name'];
            }
        }
        
        // If the cache table doesn't exist, create it.
        if (!in_array($this->table, $tables))
        {
            $sql = 'CREATE TABLE IF NOT EXISTS "' . $this->table .
                   '" ("id" VARCHAR PRIMARY KEY NOT NULL UNIQUE, "start" INTEGER, "ttl" INTEGER, "value" BLOB, "time" INTEGER)';
            
            if ($this->isPdo)
            {
                $sth = $this->sqlite->prepare($sql);
                $sth->execute();
            }
            else
            {
                $this->sqlite->query($sql);
            }
        }
    }
    
}
