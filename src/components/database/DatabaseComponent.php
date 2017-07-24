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
use eiu\core\service\logger\Logger;
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
     * @param Application           $app
     * @param ConfigProvider        $config
     * @param LoggerProvider|Logger $logger
     */
    public function __construct(Application $app, ConfigProvider $config, LoggerProvider $logger)
    {
        parent::__construct($app);
        
        switch ($config['db']['DRIVER'])
        {
            case 'MYSQL':
                $this->app->bind(IDatabaseDriver::class, MySQLDriver::class, true);
                $this->driver = new MySQLDriver($app, $config['db']['MYSQL_DRIVER']);
                break;
        }
        
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
     */
    public function exec(string $sql)
    {
        return $this->driver->exec($sql);
    }
    
    /**
     * 执行 SQL 查询语句
     *
     * @param    string $sql         SQL 语句
     * @param    bool   $fetchNumber 是否数字索引
     *
     * @return array
     */
    public function query(string $sql, bool $fetchNumber = false)
    {
        return $this->driver->query($sql, $fetchNumber);
    }
    
    /**
     * 开始事务
     *
     * @return    bool
     */
    public function begin()
    {
        if (!$this->transferred)
        {
            $this->transferred = true;
            
            $this->driver->begin();
        }
        
        return $this->transferred;
    }
    
    /**
     * 提交事务
     *
     * @return    bool
     */
    public function commit()
    {
        if ($this->transferred)
        {
            $this->transferred = false;
            
            $this->driver->commit();
        }
        
        return $this->transferred;
    }
    
    /**
     * 回滚事务
     *
     * @return    bool
     */
    public function rollBack()
    {
        if ($this->transferred)
        {
            $this->transferred = false;
            
            $this->driver->rollBack();
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
     * @return    array
     */
    public function getTables(): array
    {
        return $this->driver->getTables();
    }
    
    /**
     * 获取字段信息
     *
     * @param    string $table 表名称
     *
     * @return    array
     */
    public function getFields(string $table): array
    {
        return $this->driver->getFields($table);
    }
    
    /**
     * 获取表状态
     *
     * return array
     */
    public function getStatus()
    {
        return $this->driver->getStatus();
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