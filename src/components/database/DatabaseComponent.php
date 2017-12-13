<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\database;


use eiu\components\Component;
use eiu\components\database\driver\MySQLDriver;
use eiu\core\application\Application;
use eiu\core\service\config\ConfigProvider;
use eiu\core\service\logger\LoggerProvider;


/**
 * Class EventProvider
 *
 * @package eiu\core\service\event
 */
class DatabaseComponent extends Component implements IDatabaseDriver
{
    /**
     * @var IDatabaseDriver
     */
    private $driver = null;
    
    /**
     * 是否事务中
     *
     * @var bool
     */
    private $transferred = false;
    
    /**
     * SessionComponent constructor.
     *
     * @param Application     $app
     * @param ConfigProvider  $config
     * @param LoggerProvider  $logger
     * @param IDatabaseDriver $driver
     *
     * @throws DatabaseException
     */
    public function __construct(Application $app, ConfigProvider $config, LoggerProvider $logger, IDatabaseDriver $driver)
    {
        parent::__construct($app);
    
        $this->driver = $driver;

        $app->instance(__CLASS__, $this);
        
        $logger->info(__CLASS__ . " is called");
    }
    
    /**
     * 连接数据库服务器
     *
     * @param    array $config  数据库服务器配置信息
     * @param    int   $linkNum 连接编号
     *
     * @return    object
     *
     */
    public function connect(array $config, int $linkNum = 0)
    {
        return $this->driver->connect($config, $linkNum);
    }
    
    /**
     * 执行 SQL 更新语句
     *
     * @param    string $sql SQL 语句
     *
     * @return int
     * @throws DatabaseException
     */
    public function exec(string $sql)
    {
        try
        {
            return $this->driver->exec($sql);
        }
        catch (DatabaseException $e)
        {
            throw $e;
        }
    }
    
    /**
     * 执行 SQL 查询语句
     *
     * @param    string $sql         SQL 语句
     * @param    bool   $fetchNumber 是否数字索引
     *
     * @return array
     * @throws DatabaseException
     */
    public function query(string $sql, bool $fetchNumber = false)
    {
        try
        {
            return $this->driver->query($sql, $fetchNumber);
        }
        catch (DatabaseException $e)
        {
            throw $e;
        }
    }
    
    /**
     * 开始事务
     *
     * @return bool
     * @throws DatabaseException
     */
    public function begin()
    {
        if (!$this->transferred)
        {
            $this->transferred = true;
            
            try
            {
                $this->driver->begin();
            }
            catch (DatabaseException $e)
            {
                throw $e;
            }
        }
        
        return $this->transferred;
    }
    
    /**
     * 提交事务
     *
     * @return bool
     * @throws DatabaseException
     */
    public function commit()
    {
        if ($this->transferred)
        {
            $this->transferred = false;
            
            try
            {
                $this->driver->commit();
            }
            catch (DatabaseException $e)
            {
                throw $e;
            }
        }
        
        return $this->transferred;
    }
    
    /**
     * 回滚事务
     *
     * @return bool
     * @throws DatabaseException
     */
    public function rollback()
    {
        if ($this->transferred)
        {
            $this->transferred = false;
            
            try
            {
                $this->driver->rollback();
            }
            catch (DatabaseException $e)
            {
                throw $e;
            }
        }
        
        return $this->transferred;
    }
    
    /**
     * 是否事务中
     *
     * @return bool
     */
    public function transferred()
    {
        return $this->transferred;
    }
    
    /**
     * 获取表信息
     *
     * @return array
     * @throws DatabaseException
     */
    public function getTables(): array
    {
        try
        {
            return $this->driver->getTables();
        }
        catch (DatabaseException $e)
        {
            throw $e;
        }
    }
    
    /**
     * 获取字段信息
     *
     * @param    string $table 表名称
     *
     * @return array
     * @throws DatabaseException
     */
    public function getFields(string $table): array
    {
        try
        {
            return $this->driver->getFields($table);
        }
        catch (DatabaseException $e)
        {
            throw $e;
        }
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
            return $this->driver->getStatus();
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
        return $this->driver->getSql();
    }
    
    /**
     * 获取最近 INSERT 的主键值
     *
     * @return    integer
     */
    public function getInsertId()
    {
        return $this->driver->getInsertId();
    }
    
    /**
     * 关闭连接
     */
    public function close()
    {
        $this->driver->close();
    }
    
    /**
     * 返回驱动
     *
     * @return mixed
     */
    public function driver()
    {
        return $this->driver;
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
        return $this->driver->setSpecialChar($str);
    }
}