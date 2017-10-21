<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\abstracts\model;


use eiu\components\database\DatabaseComponent as db;


/**
 * 结构解析器
 *
 * Class StructResolver
 *
 * @package eiu\abstracts\model
 */
class StructResolver
{
    /**
     * 模型
     *
     * @var Model
     */
    private $model;
    
    /**
     * 数据库
     *
     * @var db
     */
    private $db;
    
    /**
     * 表
     *
     * @var string
     */
    private $table;
    
    /**
     * 主键
     *
     * @var string
     */
    private static $primaryKey = '';
    
    /**
     * 默认配置
     *
     * @var array
     */
    private static $defaults = [
        'unique'  => false,
        'notnull' => false,
        'primary' => false,
        'autoinc' => false,
        'create'  => true,
        'update'  => true,
        'created' => false,
        'updated' => false,
        'deleted' => false,
        'default' => null
    ];
    
    /**
     * StructResolver constructor.
     *
     * @param db    $db
     * @param Model $model
     */
    public function __construct(db $db, Model $model)
    {
        $this->db    = $db;
        $this->model = $model;
        $this->table = $this->model->table();
    }
    
    /**
     * 将字段数组转换为结构数组
     *
     * @param array $fields
     *
     * @return array
     */
    public function fieldToStruct(array $fields = []): array
    {
        if (!$fields)
        {
            $fields = $this->db->getFields($this->table);
        }
        
        $prefix = $this->model->prefix();
        
        return static::initFields($fields, $prefix);
    }
    
    /**
     * 初始化字段属性
     *
     * @param array  $fields
     * @param string $prefix
     * @param bool   $skipDefaults
     *
     * @return mixed
     */
    public static function initFields(array $fields, string $prefix, bool $skipDefaults = true)
    {
        foreach ($fields as $field => $config)
        {
            if ($skipDefaults)
            {
                foreach ($config as $index => $value)
                {
                    // 去除默认值
                    if (in_array($index, array_keys(self::$defaults)) and $value == self::$defaults[$index])
                    {
                        unset($fields[$field][$index]);
                    }
                }
            }
            
            foreach (self::$defaults as $_index => $value)
            {
                if (!in_array($_index, array_keys($config)))
                {
                    $fields[$field][$_index] = $value;
                }
            }
            
            // 设置附加配置
            if (!isset($config['text']))
            {
                if ($field == $prefix . 'id')
                {
                    $fields[$field]['text'] = 'ID';
                }
                else
                {
                    $fields[$field]['text'] = ucwords(str_replace([$prefix, '_'], ['', ' '], $field));
                }
            }
            
            // 类型转换
            if (0 === stripos(strtolower($config['type']), 'varchar'))
            {
                $fields[$field]['type']   = 'string';
                $fields[$field]['length'] = str_replace(['varchar(', ')'], '', strtolower($config['type']));
            }
            else if (0 === stripos(strtolower($config['type']), 'int'))
            {
                $fields[$field]['type']   = 'integer';
                $fields[$field]['length'] = str_replace(['int(', ')'], '', strtolower($config['type']));
            }
            else if (0 === stripos(strtolower($config['type']), 'tinyint'))
            {
                $fields[$field]['type']   = 'tinyint';
                $fields[$field]['length'] = str_replace(['tinyint(', ')'], '', strtolower($config['type']));
            }
            else if (0 === stripos(strtolower($config['type']), 'text'))
            {
                $fields[$field]['type']   = 'text';
                $fields[$field]['length'] = str_replace(['text(', ')'], '', strtolower($config['type']));
            }
            
            // 设置自动属性, 自动时间戳
            if ("{$prefix}created" == $field)
            {
                $fields[$field]['create']  = false;
                $fields[$field]['created'] = true;
            }
            else if ("{$prefix}updated" == $field)
            {
                $fields[$field]['update']  = false;
                $fields[$field]['updated'] = true;
            }
            else if ("{$prefix}deleted" == $field)
            {
                $fields[$field]['create']  = false;
                $fields[$field]['deleted'] = true;
            }
            
            // 主键
            if (!self::$primaryKey and $config['primary'])
            {
                self::$primaryKey = $field;
                
                if ($config['autoinc'])
                {
                    $fields[$field]['create'] = false;
                    $fields[$field]['update'] = false;
                }
                else
                {
                    $fields[$field]['update'] = false;
                }
            }
        }
        
        return $fields;
    }
    
    /**
     * 格式化字段
     *
     * @param array $struct
     * @param       $prefix
     *
     * @return array
     */
    public static function formatFields(array $struct, $prefix)
    {
        $result = [];
        
        foreach ($struct as $index => $config)
        {
            if (isset($config['length']))
            {
                $config['length'] = (int)$config['length'];
            }
            
            if (isset($config['primary']) and $config['primary'] and $config['autoinc'])
            {
                // 自增主键处理
                $config['primary'] = true;
                $config['autoinc'] = (bool)($config['autoinc'] ?? true);
                $config['notnull'] = false;
                $config['unique']  = false;
                $config['create']  = false;
                $config['update']  = false;
                $config['created'] = false;
                $config['updated'] = false;
                $config['deleted'] = false;
                $config['type']    = 'integer';
            }
            else
            {
                $config['autoinc'] = (bool)($config['autoinc'] ?? false);
                $config['primary'] = (bool)($config['primary'] ?? false);
                $config['notnull'] = (bool)($config['notnull'] ?? false);
                $config['unique']  = (bool)($config['unique'] ?? false);
                $config['create']  = (bool)($config['create'] ?? false);
                $config['update']  = (bool)($config['update'] ?? false);
                $config['created'] = false;
                $config['updated'] = false;
                $config['deleted'] = false;
                
                
                // 设置自动属性, 自动时间戳
                $field = $config['name'];

                if ("{$prefix}created" == $field)
                {
                    $config['create']  = false;
                    $config['update']  = false;
                    $config['created'] = true;
                }
                else if ("{$prefix}updated" == $field)
                {
                    $config['create']  = false;
                    $config['update']  = false;
                    $config['updated'] = true;
                }
                else if ("{$prefix}deleted" == $field)
                {
                    $config['create']  = false;
                    $config['update']  = false;
                    $config['deleted'] = true;
                }
                
                if (isset($config['default']) and isset($config['type']))
                {
                    switch ($config['type'])
                    {
                        case 'integer':
                        case 'tinyint':
                            if (is_string($config['default']))
                            {
                                if (strlen($config['default']))
                                {
                                    $config['default'] = (int)$config['default'];
                                }
                                else
                                {
                                    $config['default'] = null;
                                }
                            }
                            break;
                        
                        case 'float':
                            if (strlen($config['default']))
                            {
                                $config['default'] = (float)$config['default'];
                            }
                            break;
                        
                        case 'boolean':
                            $config['default'] = (bool)$config['default'];
                            break;
                    }
                }
            }
            $result[$config['name']] = $config;
        }
        
        unset($struct);
        
        return $result;
    }
    
    /**
     * 获取主键
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        return self::$primaryKey;
    }
    
    /**
     * 将 SQL 表结构字符串转换为结构数组
     *
     * @param string $sql
     *
     * @return array
     */
    public function SQLToStruct(string $sql): array
    {
        return [];
    }
    
    /**
     * 将结构转换为 SQL 表结构字符串
     *
     * @param array $structs
     *
     * @return string
     */
    public function structToSQL(array $structs): string
    {
        return null;
    }
}