<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\abstracts\model;


use eiu\abstracts\Module;
use eiu\components\database\DatabaseComponent as db;
use eiu\components\util\UtilComponent;
use eiu\core\application\Application as App;
use eiu\core\service\event\EventProvider;


abstract class Model extends Module
{
    /**
     * 数据库
     *
     * @var db
     */
    private $db;
    
    /**
     * 查询解析器
     *
     * @var QueryResolver
     */
    private $qr;
    
    /**
     * 结构解析器
     *
     * @var StructResolver
     */
    private $sr;
    
    /**
     * 表
     *
     * @var string
     */
    protected static $table = '';
    
    /**
     * 表名称
     *
     * @var string
     */
    protected static $name = '';
    
    /**
     * 字段前缀
     *
     * @var string
     */
    protected static $prefix = '';
    
    /**
     * 主键
     *
     * @var string
     */
    protected static $primaryKey = '';
    
    /**
     * 树形模型的父字段名
     *
     * @var string
     */
    protected static $parentField = '';
    
    /**
     * 显示标签字段
     *
     * @var string
     */
    protected static $labelField = '';
    
    /**
     * 字段结构
     *
     * @var array
     */
    protected static $structs = [];
    
    /**
     * 视图
     *
     * @var array
     */
    protected static $views = [];
    
    /**
     * @var EventProvider
     */
    private $event;
    
    /**
     * Model constructor.
     *
     * @param App           $app
     * @param db            $db
     *
     * @param EventProvider $event
     *
     * @throws ModelErrorException
     */
    public function __construct(App $app, db $db, EventProvider $event)
    {
        parent::__construct($app);
        
        if (empty(static::$table))
        {
            throw new ModelErrorException("The table is undefined.");
        }
        
        $this->db    = $db;
        $this->sr    = new StructResolver($db, $this);
        $this->qr    = new QueryResolver($db, $this);
        $this->event = $event;
        
        if (empty(static::$structs) || !is_array(static::$structs) || is_numeric(key(static::$structs)))
        {
            static::$structs = $this->sr->fieldToStruct();
            
            if (!static::$primaryKey)
            {
                static::$primaryKey = $this->sr->getPrimaryKey();
            }
        }
    }
    
    /**
     * 获取数据库对象
     *
     * @return db
     */
    public function db(): db
    {
        return $this->db;
    }
    
    /**
     * 获取查询解析器
     *
     * @return QueryResolver
     */
    public function queryResolver(): QueryResolver
    {
        return $this->qr;
    }
    
    /**
     * 查询
     *
     * @param array $query
     * @param array $view
     *
     * @return array
     */
    public function select(array $query = [], $view = []): array
    {
        // 获取所有字段
//        if (!isset($query['field']) or !$query['field'])
//        {
//            $query['field'] = $this::getFields();
//        }
//
//        $structs = $this::getStructs();
//
//        foreach ($structs as $field => $config)
//        {
//            if (isset($config['view']['type']) and 'foreignKey' == $config['view']['type'])
//            {
//                if (in_array($field, $query['field']))
//                {
//                    $query['join'][] = $field;
//                }
//            }
//        }
        
        // 如果指定了视图则用指定视图
        if ($view)
        {
            static::$structs = $view;
        }
        
        $sql = $this->qr->parseSelect($query);
        
        if (!$rows = $this->db->query($sql))
        {
            return [];
        }
        
        /*
        $group = [];
        
        if (isset($query['join']) and is_array($query['join']))
        {
            foreach ($query['join'] as $join)
            {
                $_join = $join;
                
                if (0 === stripos($_join, static::$prefix))
                {
                    $_join = substr($_join, strlen(static::$prefix));
                }
                if ($pos = strripos($_join, '_id'))
                {
                    $_join = substr($_join, 0, $pos);
                }
                
                if (!$_join)
                {
                    continue;
                }
                
                $group[$join] = $_join;
            }
            
            foreach ($rows as $index => $row)
            {
                foreach ($row as $key => $value)
                {
                    foreach ($group as $_field => $_group)
                    {
                        if (0 === stripos($key, $_field) and $key !== $_field)
                        {
                            $rows[$index][$group[$_field]][str_replace($_field . '_', '', $key)] = $value;
                            unset($rows[$index][$key]);
                        }
                    }
                }
            }
        }
        */
        
        return $rows;
    }
    
    /**
     * 插入
     *
     * @param array $data
     *
     * @return bool|int
     */
    public function insert(array $data)
    {
        $pk = static::getStruct(static::$primaryKey);
        
        // 生成主键
        if ('string' == $pk['type'] and !isset($data[$pk['name']]))
        {
            $data[$pk['name']] = UtilComponent::uuid();
        }
        
        $sql = $this->qr->parseInsert($data);
        
        // 开始事务
        $this->db()->begin();
        
        $this->event->fire('model.insert.begin', [$this, $data, $sql]);
        
        if (!$this->db->exec($sql))
        {
            // 回滚事务
            $this->db()->rollBack();
        }
        
        $this->event->fire('model.insert.after', [$this, $data, $sql]);
        
        // 提交事务
        $this->db()->commit();
        
        return $this->db->getInsertId() ?: true;
    }
    
    /**
     * 更新
     *
     * @param array $data
     * @param array $query
     *
     * @return int
     */
    public function update(array $data, array $query): int
    {
        $sql = $this->qr->parseUpdate($data, $query);
        
        // 开始事务
        $this->db()->begin();
        
        $this->event->fire('model.update.begin', [$this, $query, $data, $sql]);
        
        if (!$result = $this->db->exec($sql))
        {
            // 回滚事务
            $this->db()->rollBack();
        }
        
        $this->event->fire('model.update.after', [$this, $query, $data, $sql]);
        
        // 提交事务
        $this->db()->commit();
        
        return $result >= 0 ? true : false;
    }
    
    /**
     * 删除
     *
     * @param array $query
     *
     * @return int
     */
    public function delete(array $query = []): int
    {
        $sql = $this->qr->parseDelete($query);
        
        // 开始事务
        $this->db()->begin();
        
        $this->event->fire('model.delete.begin', [$this, $query, $sql]);
        
        if (!$result = $this->db->exec($sql))
        {
            // 回滚事务
            $this->db()->rollBack();
        }
        
        $this->event->fire('model.delete.after', [$this, $query, $sql]);
        
        // 提交事务
        $this->db()->commit();
        
        return $result >= 0 ? true : false;
    }
    
    /**
     * 查询一个值
     *
     * @param array  $query
     * @param string $field
     *
     * @return mixed|null
     */
    public function field(array $query = [], string $field)
    {
        if (!$result = $this->first($query))
        {
            return null;
        }
        
        return $result[$field] ?? null;
    }
    
    /**
     * 查询一行
     *
     * @param array $query
     *
     * @return array
     */
    public function first(array $query = []): array
    {
        return ($rows = $this->select($query)) ? current($rows) : [];
    }
    
    /**
     * 计数
     *
     * @param    array $query 查询条件
     *
     * @return int
     * @throws ModelErrorException
     */
    public function count(array $query = [])
    {
        $field = static::$primaryKey ?: key(static::$structs);
        
        $query['field']['_COUNT'] = 'COUNT(F{' . $field . '})';
        
        return ($result = $this->first($query)) ? $result['_COUNT'] : 0;
    }
    
    /**
     * 求和
     *
     * @param    string $field 字段
     * @param    array  $query 条件
     *
     * @return    integer
     */
    public function sum(string $field, array $query = [])
    {
        $query['field']['_SUM'] = 'SUM(F{' . $field . '})';
        
        return ($result = $this->first($query)) ? $result['_SUM'] : 0;
    }
    
    /**
     * 平均值
     *
     * @param    string $field 字段
     * @param    array  $query 查询条件
     *
     * @return    integer
     */
    public function avg(string $field, array $query = [])
    {
        $query['field']['_AVG'] = 'AVG(F{' . $field . '})';
        
        return ($result = $this->first($query)) ? $result['_AVG'] : 0;
    }
    
    /**
     * 最小值
     *
     * @param    string $field 字段
     * @param    array  $query 查询条件
     *
     * @return integer
     */
    public function min(string $field, array $query = [])
    {
        $query['field']['_MIN'] = 'MIN(F{' . $field . '})';
        
        return ($result = $this->first($query)) ? $result['_MIN'] : 0;
    }
    
    /**
     * 最大值
     *
     * @param    string $field 字段
     * @param    array  $query 查询条件
     *
     * @return integer
     */
    public function max(string $field, array $query = [])
    {
        $query['field']['_MAX'] = 'MAX(F{' . $field . '})';
        
        return ($result = $this->first($query)) ? $result['_MAX'] : 0;
    }
    
    /**
     * 获取主键字段名
     *
     * @return string
     * @throws ModelErrorException
     */
    public static function pk(): string
    {
        if (!static::$primaryKey)
        {
            throw new ModelErrorException("The model file is undefined primary key field.");
        }
        
        return static::$primaryKey;
    }
    
    /**
     * 获取模型绑定表名
     *
     * @return string
     */
    public static function table(): string
    {
        return static::$table;
    }
    
    /**
     * 表名称
     *
     * @return string
     */
    public static function name(): string
    {
        return static::$name;
    }
    
    /**
     * 字段前缀
     *
     * @return string
     */
    public static function prefix(): string
    {
        return static::$prefix;
    }
    
    /**
     * 获取所有字段名
     *
     * @return array
     */
    public static function getFields(): array
    {
        return array_keys(static::$structs);
    }
    
    /**
     * 获取所有字段结构
     *
     * @return array
     */
    public static function getStructs(): array
    {
        return static::$structs;
    }
    
    /**
     * 获取所有视图
     *
     * @return array
     */
    public static function getViews(): array
    {
        return static::$views;
    }
    
    /**
     * 字段是否存在
     *
     * @param $fieldName
     *
     * @return bool
     */
    public static function hasField($fieldName)
    {
        return in_array($fieldName, array_keys(static::$structs));
    }
    
    /**
     * 视图是否存在
     *
     * @param $viewName
     *
     * @return bool
     */
    public static function hasView($viewName)
    {
        return in_array($viewName, static::$views);
    }
    
    /**
     * 获取指定字段结构
     *
     * @param string $fieldName
     *
     * @return array
     * @throws ModelErrorException
     */
    public static function getStruct(string $fieldName): array
    {
        return static::$structs[$fieldName] ?? [];
    }
    
    /**
     * 获取指定视图
     *
     * @param string $viewName   视图名称
     * @param bool   $fullConfig 是否完整字段属性
     *
     * @return array
     */
    public static function getView(string $viewName, bool $fullConfig = false): array
    {
        if (!isset(static::$views[$viewName]))
        {
            return static::$structs;
        }
        
        if (!$fullConfig)
        {
            return static::$views[$viewName];
        }
        
        $view = static::$views[$viewName];
        
        if (isset($view['struct']))
        {
            foreach ($view['struct'] as $field => $config)
            {
                if (!isset($config['virtual']) || !$config['virtual'])
                {
                    foreach (static::$structs[$field] as $item => $prop)
                    {
                        if (!isset($config[$item]))
                        {
                            $view['struct'][$field][$item] = $prop;
                        }
                    }
                }
            }
        }
        
        return $view;
    }
    
    /**
     * 获取父级字段名
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function getParentField()
    {
        if (!isset(static::$parentField) or !static::$parentField)
        {
            return null;
        }
        
        return static::$parentField;
    }
    
    /**
     * 获取显示标签字段名
     *
     * @return string
     * @throws \Exception
     */
    public static function getLabelField()
    {
        if (!isset(static::$labelField) or !static::$labelField)
        {
            throw new \Exception("模型未定义父字段");
        }
        
        return static::$labelField;
    }
}