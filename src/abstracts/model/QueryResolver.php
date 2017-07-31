<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\abstracts\model;


use eiu\components\database\DatabaseComponent as db;


class QueryResolver
{
    /**
     * @var Model
     */
    private $_model;
    
    private $_db;
    
    private $_fields;
    
    public function __construct(db $db, Model $model)
    {
        $this->_db    = $db;
        $this->_model = $model;
    }
    
    /**
     * 解析查询结构数组
     *
     * @param array $query
     *
     * @return string
     *
     * @throws ModelErrorException
     */
    public function parseSelect(array $query): string
    {
        // 初始化局部变量
        $tableStr      = $this->_db->setSpecialChar($this->_model->table());
        $distinctStr   = '';
        $joinStr       = '';
        $joinFieldsStr = '';
        $whereStr      = '';
        $groupStr      = '';
        $havingStr     = '';
        $orderStr      = '';
        $limitStr      = '';
        
        // 查询表达式只能使用小写键
        $query = array_change_key_case($query, CASE_LOWER);
        
        // 解析 连接表
        // 优先解析连接表能够读取表字段以便下文进行字段筛选
        if (isset($query['join']) and $query['join'])
        {
            if (is_array($query['join']))
            {
                list($joinFieldsStr, $joinStr) = $this->parseJoin($query['join']);
            }
            else if (is_string($query['join']))
            {
                $joinFieldsStr = '';
                $joinStr       = $query['join'];
            }
        }
        
        // 解析查询结果字段筛选
        if (isset($query['field']) and $query['field'])
        {
            if (is_string($query['field']))
            {
                $fieldStr = $query['field'];
            }
            else if (is_array($query['field']))
            {
                $fieldStr = $this->parseField($query['field']);
            }
            else
            {
                throw new ModelErrorException("Parse invalid \"field\" expression, please use a string or an array.");
            }
        }
        else
        {
            $fieldStr = "{$tableStr}.* ";
        }
        
        $fieldStr .= $joinFieldsStr ? ", $joinFieldsStr" : '';
        
        // 解析排除重复字段值    与GROUP BY功能类似；设置了该值之后，建议不要设置字段筛选。
        if (isset($query['distinct']))
        {
            $distinctStr = 'DISTINCT ' . $this->parseDistinct($query['distinct']);
            
            if ("{$tableStr}.* " === $fieldStr)
            {
                $_fieldStr = '';
            }
            
            $distinctStr .= ', ';
        }
        
        // 解析条件
        if (isset($query['where']) and $query['where'])
        {
            if (is_string($query['where']))
            {
                $whereStr = "WHERE " . $query['where'];
            }
            else if (is_array($query['where']))
            {
                $whereStr = "WHERE " . $this->parseWhere($query['where']);
            }
            else
            {
                throw new ModelErrorException("Parse invalid \"where\" expression, please use a string or an array.");
            }
        }
        
        // 解析分组
        if (isset($query['group']) and $query['group'])
        {
            if (is_string($query['group']))
            {
                $groupStr = $query['group'];
            }
            else if (is_array($query['group']))
            {
                $groupStr = 'GROUP BY ' . $this->parseGroup($query['group']);
            }
            else
            {
                throw new ModelErrorException("Parse invalid \"group\" expression, please use a string or an array.");
            }
        }
        
        // 解析分组结果筛选
        // HAVING 能够对 GROUP BY 之后的分组数据进行筛选
        // 与 where 类似，只是一个在分组前一个在分组后。
        if (isset($query['having']) and $query['having'])
        {
            if (is_string($query['having']))
            {
                $havingStr = $query['having'];
            }
            else if (is_array($query['having']))
            {
                $havingStr = 'HAVING ' . $this->parseWhere($query['having']);
            }
            else
            {
                throw new ModelErrorException("Parse invalid \"having\" expression, please use a string or an array.");
            }
        }
        
        // 解析排序
        if (isset($query['order']) and $query['order'])
        {
            if (is_string($query['order']))
            {
                $orderStr = $query['order'];
            }
            else if (is_array($query['order']))
            {
                $orderStr = 'ORDER BY ' . $this->parseOrder($query['order']);
            }
            else
            {
                throw new ModelErrorException("Parse invalid \"order\" expression, please use a string or an array.");
            }
        }
        
        // 解析游标
        if (isset($query['limit']) and $query['limit'])
        {
            if (is_string($query['limit']))
            {
                $limitStr = $query['limit'];
            }
            else if (is_array($query['limit']))
            {
                $limitStr = 'LIMIT ' . $this->parseLimit($query['limit']);
            }
            else
            {
                throw new ModelErrorException("Parse invalid \"limit\" expression, please use a string or an array.");
            }
        }
        
        return "SELECT {$distinctStr} {$fieldStr} FROM {$tableStr} {$joinStr} {$whereStr} {$groupStr} {$havingStr} {$orderStr} {$limitStr}";
    }
    
    /**
     * 解析 JOIN 语句
     *
     * @param array $fields
     *
     * @return array
     */
    private function parseJoin(array $fields = []): array
    {
        $join = [];
        $list = [];
        
        foreach ($this->_model->getStructs() as $field => $config)
        {
            if (!in_array($field, $fields))
            {
                continue;
            }
            
            if (isset($config['view']['type']) and 'foreignKey' == $config['view']['type'])
            {
                // 目标表、目标外键、目标查询的字段
                $table   = $this->_db->setSpecialChar($config['view']['table']);
                $tableAS = $this->_db->setSpecialChar("{$field}_" . $config['view']['table']);
                $target  = $this->_db->setSpecialChar($config['view']['field']);
                $value   = $this->_db->setSpecialChar($field);
                $sTable  = $this->_db->setSpecialChar($this->_model::table());
                $join[]  = "LEFT JOIN {$table} AS {$tableAS} ON {$tableAS}.{$target}={$sTable}.{$value}";
                
                if (isset($config['view']['fields']) and is_array($config['view']['fields']))
                {
                    if ($config['view']['labelField'])
                    {
                        $list[] = "{$sTable}.{$value}";
                    }
                    
                    foreach ($config['view']['fields'] as $_field)
                    {
                        // 连接字段写入全局字段
                        $this->_fields[] = $_field;
                        
                        $list[] = $tableAS . '.' . $this->_db->setSpecialChar($_field) . ' AS ' . "`{$field}_{$_field}`";
                    }
                }
                else
                {
                    $list[] = $table . '.*';
                }
            }
        }
        
        return [implode(',', $list), implode(' ', $join)];
    }
    
    /**
     * 解析字段
     *
     * @param array $fields
     *
     * @return string
     * @throws ModelErrorException
     *
     */
    private function parseField(array $fields = []): string
    {
        // array way
        $result = [];
        
        $table = $this->_db->setSpecialChar($this->_model::table());
        
        foreach ($fields as $asName => $field)
        {
            $asName = trim($asName);
            $field  = trim($field);
            
            // 虚拟字段
            $struct = $this->_model->getStruct($field);
            
            if (isset($struct['virtual']) and $struct['virtual'])
            {
                continue;
            }
            
            // 无别名
            if (is_numeric($asName))
            {
                $_field   = "{$table}." . $this->_db->setSpecialChar($field);
                $result[] = $this->_model->hasField($field) ? $_field : $field;
            }
            else
            {
                // 剔除非字符串字段设置
                settype($asName, 'string');
                
                // 判断值是否包含字段
                if (preg_match_all('/\F{([a-z0-9._]*)\}/i', $field, $preg))
                {
                    // 提取表达式中的字段及包含字段的化括号以备后文批量替换
                    $exp_fields_old = $preg[0];
                    
                    // 提取表达式中的字段
                    $exp_fields_new = $preg[1];
                    
                    // 临时字段组
                    $old_fields_tmp = [];
                    
                    // 循环处理字段，可能存在多个字段AS一个字段。
                    foreach ($exp_fields_new as $_field)
                    {
                        // 解析字段
                        if (!$this->_model->hasField($_field))
                        {
                            throw new ModelErrorException("The \"{$table}.{$_field}\" field is undefined in the model config.");
                        }
                        
                        $old_fields_tmp[] = "{$table}." . $this->_db->setSpecialChar($_field);
                    }
                    
                    $fields_str = str_replace($exp_fields_old, $old_fields_tmp, $field);
                }
                else
                {
                    // 解析字段
                    if (!$this->_model->hasField($field))
                    {
                        throw new ModelErrorException("The \"{$field}\" field is undefined in the model config.");
                    }
                    
                    // 复制
                    $fields_str = $this->_db->setSpecialChar($field);
                }
                
                $result[] = $fields_str . ' AS ' . $this->_db->setSpecialChar($asName);
            }
        }
        
        return implode(', ', $result) ?: '*';
    }
    
    /**
     * 解析 DISTINCT 语句
     *
     * @param    array $fields
     *
     * @return string
     *
     * @throws ModelErrorException
     */
    private function parseDistinct(array $fields)
    {
        $distinctArr = [];
        
        foreach ($fields as $field)
        {
            $field = trim($field);
            
            if (!$this->_model->hasField($field))
            {
                throw new ModelErrorException("The \"{$field}\" field is undefined in the model config.");
            }
            
            $distinctArr[] = $this->_db->setSpecialChar($field);
        }
        
        return implode(', ', $distinctArr);
    }
    
    /**
     * 解析 WHERE 语句
     *
     * @param mixed $expression
     *
     * @return string
     * @throws ModelErrorException
     */
    private function parseWhere(array $expression)
    {
        // 多条件分组连接符
        $Connector = (isset($expression['_logic']) and ('OR' == strtoupper($expression['_logic']))) ? ' OR ' : ' AND ';
        
        // 多条件分组查询
        if (is_numeric(key($expression)))
        {
            $whereGroups = [];
            
            foreach ($expression as $exp)
            {
                if ($exp = $this->parseWhereDo($exp))
                {
                    $whereGroups[] = $exp;
                }
            }
            
            $whereStr = implode($Connector, $whereGroups);
        }
        else // 单条件查询
        {
            $whereStr = $this->parseWhereDo($expression);
        }
        
        if (!$whereStr)
        {
            throw new ModelErrorException("Parse the where expression is fail.");
        }
        
        return $whereStr;
    }
    
    /**
     * 解析 WHERE 语句
     *
     * @param array $expression 表达式
     *
     * @return string
     * @throws ModelErrorException
     */
    private function parseWhereDo(array $expression)
    {
        // 多条件分组连接符
        $Connector = (isset($expression['_logic']) and ('OR' == strtoupper($expression['_logic']))) ? ' OR ' : ' AND ';
        unset($expression['_logic']);
        
        $where_condition = [];
        
        foreach ($expression as $field => $value)
        {
            if ($this->_model->hasField(trim($field)))
            {
                $field = $this->_db->setSpecialChar($field);
            }
            
            // 值比较表达式
            if (!is_array($value))
            {
                $new_value = $this->parseValue($value);
                
                if ('undefined' === $new_value)
                {
                    continue;
                }
                
                // 处理连接符
                $cc = ' = ';
                
                if (is_string($new_value) and false === strpos($new_value, '\''))
                {
                    $cc = '';
                }
                
                $table = $this->_db->setSpecialChar($this->_model->table());
                
                $where_condition[] = "{$table}.{$field}" . $cc . $new_value;
            }
            else
            {
                $symbol = [
                    '=', '!=', '>', '>=', '<', '<=', 'IS', 'IS NOT', 'NOT LIKE', 'LIKE', 'REGEXP', 'RLIKE', 'NOTREGEXP',
                    'NOT RLIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN',
                ];
                
                foreach ($value as $field_key => $field_val)
                {
                    $field_key = trim(strtoupper($field_key));
                    
                    // 运算符必须是预设运算符
                    if (in_array($field_key, $symbol))
                    {
                        if (in_array($field_key, ['IN', 'NOT IN']))
                        {
                            if (!is_array($field_val))
                            {
                                $field_val = [$field_val];
                            }
                            
                            foreach ($field_val as $_index => $_item)
                            {
                                $field_val[$_index] = $this->parseValue($field_val[$_index]);
                            }
                            
                            if (empty($field_val))
                            {
                                continue;
                            }
                            
                            $field_val = '(' . implode(', ', $field_val) . ')';
                        }
                        else if (in_array($field_key, ['BETWEEN', 'NOT BETWEEN']))
                        {
                            if (!is_array($field_val) or (count($field_val) != 2) or empty($field_val))
                            {
                                throw new ModelErrorException("Field \"{$field}\" is between type, the value must be an array of length 2.");
                            }
                            
                            $field_val = $this->parseValue($field_val[0]) . ' AND ' . $this->parseValue($field_val[1]);
                        }
                        else
                        {
                            //							if (0 == strlen($field_val))
                            //								continue;
                            
                            $field_val = $this->parseValue($field_val);
                        }
                        
                        $table             = $this->_db->setSpecialChar($this->_model->table());
                        $where_condition[] = "{$table}.{$field} {$field_key} {$field_val}";
                    }
                }
            }
        }
        
        if ($where_condition)
        {
            return '(' . implode($Connector, $where_condition) . ')';
        }
        
        return '';
    }
    
    /**
     * 解析值
     *
     * @param $value
     *
     * @return mixed
     */
    private function parseValue($value)
    {
        if (is_bool($value) or is_integer($value))
        {
            $value = (int)$value;
        }
        else if (is_null($value))
        {
            $value = "NULL";
        }
        else if (is_string($value))
        {
            $value = addslashes($value);
            
            if (0 === strlen($value))
            {
                $value = "''";
            }
            else if (preg_match_all('/\F{([a-z0-9._]*)\}/i', $value, $parse_result))
            {
                // 记录表达式，用于下文还原表达式。
                $exp = $value;
                
                // 提取表达式中的字段及包含字段的化括号以备后文批量替换
                $exp_fields_old = $parse_result[0];
                
                // 提取表达式中的字段
                $exp_fields_new = $parse_result[1];
                
                // 循环处理字段
                $old_fields_tmp = [];
                
                foreach ($exp_fields_new as $_field)
                {
                    // 解析字段
                    if ($this->_model->hasField($_field))
                    {
                        $_field = $this->_db->setSpecialChar($_field);
                    }
                    
                    $old_fields_tmp[] = $this->_db->setSpecialChar($_field);
                }
                
                // 还原表达式
                $value = str_replace($exp_fields_old, $old_fields_tmp, $exp);
            }
            else
            {
                $value = "'$value'";
            }
        }
        
        return $value;
    }
    
    /**
     * 解析 GROUP 语句
     *
     * @param    mixed $groups
     *
     * @return string
     * @throws ModelErrorException
     */
    private function parseGroup(array $groups)
    {
        $groupArr = [];
        
        foreach ($groups as $group)
        {
            $group = trim($group);
            
            if (!$this->_model->hasField($group))
            {
                throw new ModelErrorException("Parse group: the \"{$group}\" field is undefined in the model config.");
            }
            
            $groupArr[] = $this->_db->setSpecialChar($group);
        }
        
        return implode(', ', $groupArr);
    }
    
    /**
     * 解析 ORDER 语句
     *
     * @param array $orders
     *
     * @return string
     * @throws ModelErrorException
     */
    private function parseOrder(array $orders)
    {
        $orderArr = [];
        
        foreach ($orders as $field => $dir)
        {
            if (!$this->_model->hasField($field))
            {
                throw new ModelErrorException("Parse order: the \"{$field}\" field is undefined in the model config.");
            }
            
            if (!is_string($dir))
            {
                continue;
            }
            
            $field      = $this->_db->setSpecialChar($field);
            $order      = strtoupper($dir) == 'DESC' ? 'DESC' : 'ASC';
            $table = $this->_db->setSpecialChar($this->_model->table());
            $orderArr[] = "{$table}.{$field}" . ' ' . $order;
        }
        
        return implode(', ', $orderArr);
    }
    
    /**
     * 解析 LIMIT 语句
     *
     * @param    array $limit
     *
     * @return    string
     */
    private function parseLimit($limit)
    {
        if (count($limit) === 1)
        {
            array_unshift($limit, 0);
        }
        
        list($start, $size) = $limit;
        
        $size = $size ? ', ' . trim($size) : '';
        
        return $start . $size;
    }
    
    /**
     * 解析插入、更新字段数组
     *
     * @param array  $fields
     * @param string $action
     *
     * @return array
     *
     * @throws ModelErrorException
     * @throws ModelMessageException
     */
    private function parseExecuteFields(array $fields, string $action): array
    {
        $structs = $this->_model->getStructs();
        
        // 非空字段检查
        if ('create' == $action)
        {
            foreach ($structs as $field => $struct)
            {
                // 跳过虚拟字段
                if (isset($struct['virtual']) and $struct['virtual'])
                {
                    continue;
                }
                
                // 跳过可为空
                if (!isset($struct['notnull']) or !$struct['notnull'] or !isset($fields[$field]) or !is_scalar($fields[$field]))
                {
                    continue;
                }
                
                // 使用默认值填充
                if (!$fields[$field] and isset($struct['default']) and strlen($struct['default']))
                {
                    $fields[$field] = $struct['default'];
                    continue;
                }
            }
        }
        
        // 配置条件过滤
        foreach ($fields as $field => $value)
        {
            $field = trim($field);
            
            // 过滤非预设字段
            if (!$this->_model->hasField($field))
            {
                unset($fields[$field]);
                continue;
            }
            
            $struct = $structs[$field];
            
            // 跳过禁止插入、更新字段
            if (isset($struct[$action]) and !$struct[$action])
            {
                unset($fields[$field]);
                continue;
            }
            
            // 值类型强制转换
            if (isset($struct['type']))
            {
                if (is_array($value) or is_object($value))
                {
                    trigger_error("{$action} field \"{$field}\" value cannot be array or object.", E_USER_ERROR);
                }
                
                switch ($struct['type'])
                {
                    case 'integer':
                    case 'int':
                        $fields[$field] = is_null($value) ? null : (int)$value;
                        break;
                    
                    case 'boolean':
                    case 'bool':
                        $fields[$field] = (bool)$value;
                        break;
                    
                    case 'float':
                        $fields[$field] = (float)$value;
                        break;
                    
                    case 'double':
                        $fields[$field] = (double)$value;
                        break;
                    
                    default:
                        $fields[$field] = (string)$value;
                }
            }
        }
        
        // 自动时间戳
        $auto = ('create' == $action) ? 'created' : 'updated';
        
        foreach ($structs as $field => $struct)
        {
            if (isset($struct[$auto]) and $struct[$auto])
            {
                $fields[$field] = time();
            }
        }
        
        if (empty($fields))
        {
            throw new ModelErrorException('All field validate fail, no effective field.');
        }
        
        return $fields;
    }
    
    /**
     * 解析插入语句
     *
     * @param array $data
     *
     * @return string
     */
    public function parseInsert(array $data)
    {
        $tableStr = $this->_db->setSpecialChar($this->_model->table());
        $keys     = [];
        $values   = [];
        
        if (!$expression = $this->parseExecuteFields($data, 'create'))
        {
            return false;
        }
        
        foreach ($expression as $k => $v)
        {
            if (!$this->_model->hasField($k))
            {
                continue;
            }
            $k        = $this->_db->setSpecialChar($k);
            $keys[]   = $k;
            $values[] = $this->parseValue($v);
        }
        
        $keys   = implode(', ', $keys);
        $values = implode(', ', $values);
        
        $setStr = ' (' . $keys . ') VALUES (' . $values . ') ';
        
        return 'INSERT INTO ' . $tableStr . $setStr;
    }
    
    /**
     * 解析插入、更新字段数组
     *
     * @param array $data
     * @param array $query
     *
     * @return array|string
     * @throws ModelErrorException
     */
    public function parseUpdate(array $data, array $query = []): string
    {
        // 初始化局部变量
        $whereStr = '';
        $orderStr = '';
        $limitStr = '';
        
        $tableStr = $this->_db->setSpecialChar($this->_model->table());
        
        $setArr = null;
        
        if (!$expression = $this->parseExecuteFields($data, 'update'))
        {
            return false;
        }
        
        foreach ($expression as $k => $v)
        {
            $k        = $this->_db->setSpecialChar($k);
            $setArr[] = $k . ' = ' . $this->parseValue($v);
        }
        
        if (!$setStr = implode(', ', $setArr))
        {
            return false;
        }
        
        // 解析条件
        if (isset($query['where']) and $query['where'])
        {
            if (is_string($query['where']))
            {
                $whereStr = "WHERE " . $query['where'];
            }
            else if (is_array($query['where']))
            {
                $whereStr = "WHERE " . $this->parseWhere($query['where']);
            }
            else
            {
                throw new ModelErrorException("Parse invalid \"where\" expression, please use a string or an array.");
            }
        }
        
        // 解析排序
        if (isset($query['order']) and $query['order'])
        {
            if (is_string($query['order']))
            {
                $orderStr = $query['order'];
            }
            else if (is_array($query['order']))
            {
                $orderStr = 'ORDER BY ' . $this->parseOrder($query['order']);
            }
            else
            {
                throw new ModelErrorException("Parse invalid \"order\" expression, please use a string or an array.");
            }
        }
        
        // 解析游标
        if (isset($query['limit']) and $query['limit'])
        {
            if (is_string($query['limit']))
            {
                $limitStr = $query['limit'];
            }
            else if (is_array($query['limit']))
            {
                $limitStr = 'LIMIT ' . $this->parseLimit($query['limit']);
            }
            else
            {
                throw new ModelErrorException("Parse invalid \"limit\" expression, please use a string or an array.");
            }
        }
        
        return "UPDATE {$tableStr} SET {$setStr} {$whereStr} {$orderStr} {$limitStr}";
    }
    
    /**
     * 解析删除
     *
     * @param array $query
     *
     * @return string
     * @throws ModelErrorException
     */
    public function parseDelete(array $query = [])
    {
        $limitStr = '';
        $whereStr = '';
        $orderStr = '';
        
        $tableStr = $this->_db->setSpecialChar($this->_model->table());
        
        // 解析条件
        if (isset($query['where']) and $query['where'])
        {
            if (is_string($query['where']))
            {
                $whereStr = "WHERE " . $query['where'];
            }
            else if (is_array($query['where']))
            {
                $whereStr = "WHERE " . $this->parseWhere($query['where']);
            }
            else
            {
                throw new ModelErrorException("Parse invalid \"where\" expression, please use a string or an array.");
            }
        }
        
        // 解析排序
        if (isset($query['order']) and $query['order'])
        {
            if (is_string($query['order']))
            {
                $orderStr = $query['order'];
            }
            else if (is_array($query['order']))
            {
                $orderStr = 'ORDER BY ' . $this->parseOrder($query['order']);
            }
            else
            {
                throw new ModelErrorException("Parse invalid \"order\" expression, please use a string or an array.");
            }
        }
        
        // 解析游标
        if (isset($query['limit']) and $query['limit'])
        {
            if (is_string($query['limit']))
            {
                $limitStr = $query['limit'];
            }
            else if (is_array($query['limit']))
            {
                $limitStr = 'LIMIT ' . $this->parseLimit($query['limit']);
            }
            else
            {
                throw new ModelErrorException("Parse invalid \"limit\" expression, please use a string or an array.");
            }
        }
        
        return "DELETE FROM {$tableStr} {$whereStr} {$orderStr} {$limitStr}";
    }
}