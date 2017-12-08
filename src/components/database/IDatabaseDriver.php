<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\database;

interface IDatabaseDriver
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
    public function connect(array $config, int $linkNum = 0);
    
    /**
     * 执行 SQL 更新语句
     *
     * @param    string $sql SQL 语句
     *
     * @return    int 受影响行数
     */
    public function exec(string $sql);
    
    /**
     * 执行 SQL 查询语句
     *
     * @param    string $sql   SQL 语句
     * @param    bool   $index 是否数字索引
     *
     * @return    array
     */
    public function query(string $sql, bool $index = false);
    
    /**
     * 开始事务
     *
     * @return    bool
     */
    public function begin();
    
    /**
     * 提交事务
     *
     * @return    bool
     */
    public function commit();
    
    /**
     * 回滚事务
     *
     * @return    bool
     */
    public function rollback();
    
    /**
     * 获取表信息
     *
     * @return    array
     */
    public function getTables(): array;
    
    /**
     * 获取字段信息
     *
     * @param    string $table 表名称
     *
     * @return    array
     */
    public function getFields(string $table): array;
    
    /**
     * 获取表状态
     *
     * return array
     */
    public function getStatus();
    
    /**
     * 获取最近执行的 SQL 语句
     *
     * 获取后将不再保留最近执行的 SQL 语句
     *
     * @return    string
     */
    public function getSql();
    
    /**
     * 获取最近 INSERT 的主键值
     *
     * @return    integer
     */
    public function getInsertId();
    
    /**
     * 关闭连接
     */
    public function close();
    
    /**
     * 返回驱动
     *
     * @return mixed
     */
    public function driver();
    
    /**
     * 设置名字包裹字符
     *
     * MYSQL 表、字段名、别名的包裹字符(`)
     *
     * @param $str
     *
     * @return string
     */
    public function setSpecialChar($str): string;
}