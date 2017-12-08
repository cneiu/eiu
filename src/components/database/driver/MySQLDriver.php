<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\database\driver;


use eiu\components\database\DatabaseException;
use eiu\components\database\IDatabaseDriver;
use eiu\core\application\Application;
use eiu\core\service\logger\Logger;
use eiu\core\service\logger\LoggerProvider;
use PDO;
use PDOException;


/**
 * 数据库组件
 *
 * 数据库操作组件
 */
class MySQLDriver implements IDatabaseDriver
{
    /**
     * 查询字符串
     *
     * @var null
     */
    static private $_query_str = null;
    /**
     * PDO 描述
     *
     * @var \PDOStatement
     */
    static private $_PDOStatement = null;
    /**
     * 事务计数
     *
     * @var int
     */
    static private $_trans_times = 0;
    /**
     * 当前连接 ID
     *
     * @var int
     */
    static private $_linkID = 0;
    /**
     * 连接池
     *
     * @var null
     */
    static private $_links = null;
    /**
     * 连接切换
     *
     * @var bool
     */
    static private $_switch_connect = false;
    /**
     * @var Application
     */
    private $app;
    /**
     * @var LoggerProvider|Logger
     */
    private $logger;
    /**
     * 当前连接池
     *
     * @var \PDO
     */
    private $_link;
    /**
     * 服务器列表
     *
     * @var array
     */
    private $_config_servers;
    
    /**
     * 是否持久连接
     *
     * @var bool
     */
    private $_config_pconnect;
    
    /**
     * 是否分布式
     *
     * @var bool
     */
    private $_config_deploy;
    
    /**
     * 是否读写分离
     *
     * @var bool
     */
    private $_config_rw_separate;
    
    /**
     * 字符集
     *
     * @var string
     */
    private $_config_charset;
    
    /**
     * MySQLDriver constructor.
     *
     * @param Application $app
     *
     * @throws DatabaseException
     *
     */
    public function __construct(Application $app)
    {
        $this->app    = $app;
        $this->logger = $app['logger'];
        $config       = $app['config']['DB']['MYSQL_DRIVER'];
        
        if (empty($config['SERVERS']))
        {
            throw new DatabaseException("Servers list is undefined.");
        }
        
        $this->_config_servers     = $config['SERVERS'];
        $this->_config_pconnect    = $config['PCONNECT'];
        $this->_config_deploy      = $config['DEPLOY'];
        $this->_config_rw_separate = $config['RW_SEPARATE'];
        $this->_config_charset     = $config['CHARSET'];
    }
    
    /**
     * 增加连接
     *
     * 增加连接到当前连接池
     *
     * @param    array $config 数据库服务器配置信息
     *
     * @return    int 当前连接池总和
     */
    public function add_connect(array $config)
    {
        $this->_config_servers[] = $config;
        
        return count($this->_config_servers) - 1;
    }
    
    /**
     * 切换连接
     *
     * 切换一个连接作为当前主连接
     *
     * @param    int $linkID 连接编号
     *
     * @return    bool
     */
    public function switch_connect($linkID)
    {
        if (!isset($this->_config_servers[$linkID]))
            return false;
        
        $this->_link           = $this->connect($this->_config_servers[$linkID], $linkID);
        self::$_switch_connect = true;
        
        $this->logger->info("Switch server success, link id is \"$linkID\".");
        
        return true;
    }
    
    /**
     * 连接数据库服务器
     *
     * @param    array $config  数据库服务器配置信息
     * @param    int   $linkNum 连接编号
     *
     * @return object
     * @throws DatabaseException
     */
    public function connect(array $config, int $linkNum = 0)
    {
        if (!isset(self::$_links[$linkNum]))
        {
            // query params
            $params = [];
            
            // long connect ?
            $params[PDO::ATTR_PERSISTENT] = !!$this->_config_pconnect;
            
            // connect string
            $dsn = 'mysql';
            $dsn .= ':host=' . $config['HOST'];
            $dsn .= ';port=' . $config['PORT'];
            $dsn .= ';dbname=' . $config['NAME'];
            $usr = $config['USER'];
            $pwd = $config['PASS'];
            
            try
            {
                self::$_links[$linkNum] = new PDO($dsn, $usr, $pwd, $params);
            }
            catch (PDOException $e)
            {
                throw new DatabaseException('Connect error, ' . $e->getMessage());
            }
            
            // set charset
            /** @var \PDO $link */
            $link = self::$_links[$linkNum];
            $link->exec('SET NAMES \'' . $this->_config_charset . '\'');
        }
        
        $this->logger->info("Connect to database server {$config['HOST']}:{$config['PORT']} success, link id is \"$linkNum\".");
        
        return self::$_links[$linkNum];
    }
    
    /**
     * 充值连接
     *
     * 恢复默认连接为当前主连接
     */
    public function reset_connect()
    {
        self::$_switch_connect = false;
    }
    
    /**
     * 获取当前连接编号
     *
     * @return int
     */
    public function getConnectID()
    {
        return self::$_linkID;
    }
    
    /**
     * 开始事务
     *
     * @return    bool
     */
    public function begin()
    {
        $this->init(true);
        
        // rollback support
        if (self::$_trans_times == 0)
        {
            $this->_link->beginTransaction();
        }
        
        self::$_trans_times++;
        
        $this->logger->info("Start an database trans");
        
        return true;
    }
    
    /**
     * 初始化连接
     *
     * @param    bool $master 是否是主数据库
     *
     * @return MySQLDriver
     *
     * @throws DatabaseException
     */
    private function init(bool $master = true)
    {
        if ($this->_link)
        {
            return $this;
        }
        
        if ($this->_config_deploy)
        {
            $this->_link = $this->multi_connect($master);
        }
        else if (!$this->_link)
        {
            $this->_link = $this->connect($this->_config_servers[0], 0);
        }
        
        if (!$this->_link)
        {
            throw new DatabaseException("Connection is not available.");
        }
        
        $this->logger->info('Init database server connect...success');
        
        return $this;
    }
    
    /**
     * 分布式连接
     *
     * 分布式方式连接多个数据库
     *
     * @param bool $master 是否是主数据库
     *
     * @return object
     */
    private function multi_connect(bool $master = false)
    {
        // Reading and writing separation ?
        if ($this->_config_rw_separate)
        {
            // random connect to the servers
            $linkID = $master ? 0 : floor(mt_rand(1, count($this->_config_servers) - 1));
        }
        else
        {
            // random connect to the servers
            $linkID = floor(mt_rand(0, count($this->_config_servers) - 1));
        }
        
        // record link id
        self::$_linkID = $linkID;
        
        return $this->connect($this->_config_servers[$linkID], $linkID);
    }
    
    /**
     * 提交事务
     *
     * @return bool
     * @throws DatabaseException
     */
    public function commit()
    {
        $this->init(true);
        
        if (self::$_trans_times > 0)
        {
            $result             = $this->_link->commit();
            self::$_trans_times = 0;
            
            if (!$result)
            {
                list(, $code, $message) = self::$_PDOStatement->errorInfo();
                $error = "Commit error, {$code}, \"{$message}\"";
                $this->logger->error($error);
                throw new DatabaseException($error, $code);
            }
        }
        
        $this->logger->info("Commit an database trans");
        
        return true;
    }
    
    /**
     * 回滚事务
     *
     * @return bool
     * @throws DatabaseException
     */
    public function rollback()
    {
        $this->init(true);
        
        if (self::$_trans_times > 0)
        {
            $result             = $this->_link->rollback();
            self::$_trans_times = 0;
            
            if (!$result)
            {
                list(, $code, $message) = self::$_PDOStatement->errorInfo();
                $error = "Rollback error, {$code}, \"{$message}\"";
                $this->logger->error($error);
                throw new DatabaseException($error, $code);
            }
        }
        
        $this->logger->info("Rollback an database trans");
        
        return true;
    }
    
    /**
     * 获取字段信息
     *
     * @param    string $table_name 表名称
     *
     * @return array
     * @throws DatabaseException
     */
    public function getFields(string $table_name): array
    {
        $table_name = (0 === strpos($table_name, '`')) ? $table_name : "`$table_name`";
        $sql        = 'DESCRIBE ' . $table_name;
        
        $rows = $this->query($sql);
        
        if (!$rows)
        {
            list(, $code, $message) = self::$_PDOStatement->errorInfo();
            $error = "Query error, {$code}, \"{$message}\"";
            $this->logger->error($error);
            throw new DatabaseException($error, $code);
        }
        
        $fields = [];
        
        if ($rows)
        {
            foreach ($rows as $row)
            {
                $row = array_change_key_case($row, CASE_LOWER);
                
                $default = null;
                
                if (isset($row['default']))
                {
                    if (strlen($row['default']))
                    {
                        $default = $row['default'];
                    }
                }
                else if (isset($row['dflt_value']))
                {
                    if (strlen($row['dflt_value']))
                    {
                        $default = $row['dflt_value'];
                    }
                }
                
                $fields[$row['field']]            = [];
                $fields[$row['field']]['name']    = $row['field'];
                $fields[$row['field']]['type']    = $row['type'];
                $fields[$row['field']]['unique']  = ($row['key'] == 'UNI' or $row['key'] == 'PRI') ? true : false;
                $fields[$row['field']]['notnull'] = !(strtoupper($row['null']) == 'YES');
                $fields[$row['field']]['default'] = $default;
                $fields[$row['field']]['primary'] = $row['key'] == 'PRI';
                $fields[$row['field']]['autoinc'] = (strtolower($row['extra']) == 'auto_increment') ? true : false;
            }
        }
        
        return $fields;
    }
    
    /**
     * 执行 SQL 查询语句
     *
     * @param    string $sql   SQL 语句
     * @param    bool   $index 是否数字索引
     *
     * @return array
     * @throws DatabaseException
     */
    public function query(string $sql, bool $index = false)
    {
        if (!$this->app->timerIsTick('_DB_QUERY_'))
        {
            $this->app->timerTick('_DB_QUERY_');
        }
        
        // use master server
        if (!self::$_switch_connect)
        {
            $this->init(false);
        }
        
        self::$_query_str = $sql;
        
        if (!empty(self::$_PDOStatement))
        {
            $this->free();
        }
        
        self::$_PDOStatement = $this->_link->prepare($sql);
        
        // return number index ?
        if (!$index)
        {
            self::$_PDOStatement->setFetchMode(PDO::FETCH_ASSOC);
        }
        
        if (false === self::$_PDOStatement)
        {
            list(, $code, $message) = self::$_PDOStatement->errorInfo();
            $error = "Query error, {$code}, \"{$message}\"";
            $this->logger->error($error);
            throw new DatabaseException($error, $code);
        }
        
        $result = self::$_PDOStatement->execute();
        
        if (false === $result)
        {
            list(, $code, $message) = self::$_PDOStatement->errorInfo();
            $error = "Query error, {$code}, \"{$message}\"";
            $this->logger->error($error);
            throw new DatabaseException($error, $code);
        }
        
        $timer = $this->app->timerElapsed('_DB_QUERY_');
        
        $this->app->timerUnsetTick('_DB_QUERY_');
        
        $this->logger->info("Query sql finished, used {$timer}s, \"$sql\"");
        
        // return rows collection
        return self::$_PDOStatement->fetchAll();
    }
    
    /**
     * 释放当前查询
     */
    public function free()
    {
        self::$_PDOStatement = null;
    }
    
    /**
     * 获取表信息
     *
     * @return array
     * @throws DatabaseException
     */
    public function getTables(): array
    {
        if (false === ($result = $this->query('SHOW TABLES')))
        {
            list(, $code, $message) = self::$_PDOStatement->errorInfo();
            $error = "Query error, {$code}, \"{$message}\"";
            $this->logger->error($error);
            throw new DatabaseException($error, $code);
        }
        
        $info = [];
        
        foreach ($result as $key => $val)
        {
            $info[$key] = current($val);
        }
        
        return $info;
    }
    
    /**
     * 执行 SQL 更新语句
     *
     * @param    string $sql SQL 语句
     *
     * @return int 受影响行数
     *
     * @throws DatabaseException
     */
    public function exec(string $sql)
    {
        if (!$this->app->timerIsTick('_DB_EXECUTE_'))
        {
            $this->app->timerTick('_DB_EXECUTE_');
        }
        
        // use master server
        if (!self::$_switch_connect)
        {
            $this->init(true);
        }
        
        // record query string
        self::$_query_str = $sql;
        
        // free history query
        if (!empty(self::$_PDOStatement))
        {
            $this->free();
        }
        
        // before process sql
        self::$_PDOStatement = $this->_link->prepare($sql);
        
        // fail
        if (false === self::$_PDOStatement)
        {
            list(, $code, $message) = self::$_PDOStatement->errorInfo();
            throw new DatabaseException("Execute error, \"{$message}\" in \"{$sql}\"", $code);
        }
        
        // execute
        $result = self::$_PDOStatement->execute();
        
        if (false === $result)
        {
            list(, $code, $message) = self::$_PDOStatement->errorInfo();
            $error = "Execute error, {$code}, \"{$message}\" in \"{$sql}\"";
            $this->logger->error($error);
            throw new DatabaseException($error, $code);
        }
        
        $timer = $this->app->timerElapsed('_DB_EXECUTE_');
        
        $this->app->timerUnsetTick('_DB_EXECUTE_');
        
        $this->logger->info("Execute success, used {$timer}s, \"$sql\"");
        
        // return effect rows number
        return self::$_PDOStatement->rowCount();
    }
    
    /**
     * 获取表状态
     *
     * return array
     */
    public function getStatus()
    {
        try
        {
            return $this->query('SHOW TABLE STATUS');
        }
        catch (DatabaseException $e)
        {
            throw $e;
        }
    }
    
    /**
     * 获取最近执行的 SQL 语句
     *
     * 获取后将不再保留最近执行的 SQL 语句
     *
     * @return    string
     */
    public function getSql()
    {
        $sql              = self::$_query_str;
        self::$_query_str = '';
        
        return $sql;
    }
    
    /**
     * 获取最近 INSERT 的主键值
     *
     * @return    integer
     */
    public function getInsertId()
    {
        return $this->_link->lastInsertId();
    }
    
    /**
     * 返回驱动
     *
     * @return $this
     */
    public function driver()
    {
        return $this;
    }
    
    /**
     * 析构函数
     *
     * 关闭数据库连接
     */
    public function __destruct()
    {
        $this->close();
    }
    
    /**
     * 关闭连接
     */
    public function close()
    {
        $this->_link = null;
    }
    
    /**
     * 设置名字包裹字符
     *
     * MYSQL 表、字段名、别名的包裹字符(`)
     *
     * @param $str
     *
     * @return string
     */
    public function setSpecialChar($str): string
    {
        return '`' . trim($str) . '`';
    }
}