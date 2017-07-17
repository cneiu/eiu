<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\database;


/**
 * SQLite 驱动
 */
class SQLiteDriver implements IDatabaseDriver
{
    
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
        // TODO: Implement connect() method.
    }
    
    /**
     * 执行 SQL 更新语句
     *
     * @param    string $sql SQL 语句
     *
     * @return    int 受影响行数
     */
    public function exec(string $sql)
    {
        // TODO: Implement exec() method.
    }
    
    /**
     * 执行 SQL 查询语句
     *
     * @param    string $sql   SQL 语句
     * @param    bool   $index 是否数字索引
     *
     * @return    array
     */
    public function query(string $sql, bool $index = false)
    {
        // TODO: Implement query() method.
    }
    
    /**
     * 开始事务
     *
     * @return    bool
     */
    public function begin()
    {
        // TODO: Implement begin() method.
    }
    
    /**
     * 提交事务
     *
     * @return    bool
     */
    public function commit()
    {
        // TODO: Implement commit() method.
    }
    
    /**
     * 回滚事务
     *
     * @return    bool
     */
    public function rollBack()
    {
        // TODO: Implement rollBack() method.
    }
    
    /**
     * 获取表信息
     *
     * @return    array
     */
    public function getTables()
    {
        // TODO: Implement getTables() method.
    }
    
    /**
     * 获取字段信息
     *
     * @param    string $table 表名称
     *
     * @return    array
     */
    public function getFields(string $table)
    {
        // TODO: Implement getFields() method.
    }
    
    /**
     * 获取表状态
     *
     * return array
     */
    public function getStatus()
    {
        // TODO: Implement getStatus() method.
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
        // TODO: Implement getSql() method.
    }
    
    /**
     * 获取最近 INSERT 的主键值
     *
     * @return    integer
     */
    public function getInsertId()
    {
        // TODO: Implement getInsertId() method.
    }
    
    /**
     * 关闭连接
     */
    public function close()
    {
        // TODO: Implement close() method.
    }
    
    /**
     * 返回驱动
     *
     * @return mixed
     */
    public function driver()
    {
        // TODO: Implement driver() method.
    }
}