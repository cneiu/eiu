<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\view;


use eiu\core\application\Application;


/**
 * 视图模板类
 */
class TemplateEngine
{
    private static $_ti_base  = null;
    private static $_ti_stack = null;
    private static $_ti_vars  = null;
    /**
     * @var Application
     */
    private $app;
    
    /**
     * 构造方法
     *
     * @param Application $app
     * @param array       $vars 模板变量
     */
    public function __construct(Application $app, array $vars)
    {
        $this->app = $app;
        foreach ($vars as $k => $v)
        {
            $this->$k = $v;
        }
    }
    
    /**
     * 值检查
     *
     * @param $var
     *
     * @return null
     */
    public function v($var)
    {
        return $var ?? null;
    }
    
    /**
     * 返回渲染模板内容
     *
     * @param string $_template_file 模板路径
     *
     * @return string
     */
    public function render(string $_template_file)
    {
        ob_start();
        extract(get_object_vars($this), EXTR_REFS);
        include($_template_file);
        $this->flushBlocks();
        
        return ob_get_clean();
    }
    
    /**
     * 刷新区块
     */
    public function flushBlocks()
    {
        $base =& self::$_ti_base;
        if ($base)
        {
            $stack =& self::$_ti_stack;
            $level =& self::$_ti_vars['_ti_level'];
            
            while ($block = array_pop($stack))
            {
                self::_ti_warning("missing endblock() for startblock('{$block['name']}')", self::_ti_callingTrace(), $block['trace']);
            }
            
            while (ob_get_level() > $level)
            {
                ob_end_flush(); // will eventually trigger bufferCallback
            }
            
            $base  = null;
            $stack = null;
        }
    }
    
    /**
     * 警告信息
     *
     * @param      $message
     * @param      $trace
     * @param null $warning_trace
     */
    private function _ti_warning($message, $trace, $warning_trace = null)
    {
        if (error_reporting() & E_USER_WARNING)
        {
            if (defined('STDIN'))
            {
                // from command line
                $format = "\nWarning: %s in %s on line %d\n";
            }
            else
            {
                // from browser
                $format = "<br />\n<b>Warning</b>:  %s in <b>%s</b> on line <b>%d</b><br />\n";
            }
            
            if (!$warning_trace)
            {
                $warning_trace = $trace;
            }
            
            $s = sprintf($format, $message, $warning_trace[0]['file'], $warning_trace[0]['line']);
            
            if (!self::$_ti_base or self::_ti_inBase($trace))
            {
                echo $s;
            }
            else
            {
                self::$_ti_vars['_ti_after'] .= $s;
            }
        }
    }
    
    /**
     * 是否在基础块中
     *
     * @param $trace
     *
     * @return bool
     */
    private function _ti_inBase($trace)
    {
        return self::_ti_isSameFile($trace, self::$_ti_base['trace']);
    }
    
    /**
     * 是否是文件
     *
     * @param $trace1
     * @param $trace2
     *
     * @return bool
     */
    private function _ti_isSameFile($trace1, $trace2)
    {
        return $trace1 and $trace2 and $trace1[0]['file'] === $trace2[0]['file'] and array_slice($trace1, 1) === array_slice($trace2, 1);
    }
    
    /**
     * 跟踪
     *
     * @return array
     */
    private function _ti_callingTrace()
    {
        $trace = debug_backtrace();
        
        foreach ($trace as $i => $location)
        {
            if ($location['file'] !== __FILE__)
            {
                return array_slice($trace, $i);
            }
        }
    }
    
    /**
     * 定义一个空区块
     *
     * @param string $name 名称
     */
    public function emptyBlock(string $name)
    {
        $trace = self::_ti_callingTrace();
        self::_ti_init($trace);
        self::_ti_insertBlock(self::_ti_newBlock($name, null, $trace));
    }
    
    /**
     * 模板初始化
     *
     * @param $trace
     */
    private function _ti_init($trace)
    {
        $base =& self::$_ti_base;
        
        if ($base and !self::_ti_inBaseOrChild($trace))
        {
            self::flushblocks(); // will set $base to null
        }
        
        if (!$base)
        {
            $base = [//
                     'trace'    => $trace,  //
                     'filters'  => null,    // purely for compile
                     'children' => [],      //
                     'start'    => 0,       // purely for compile
                     'end'      => null,
            ];
            
            self::$_ti_vars['_ti_level'] = ob_get_level();
            self::$_ti_stack             = [];
            self::$_ti_vars['_ti_hash']  = [];
            self::$_ti_vars['_ti_end']   = null;
            self::$_ti_vars['_ti_after'] = '';
            
            ob_start([$this, '_ti_bufferCallback']);
        }
    }
    
    /**
     * 是否在基础块或子块中
     *
     * @param $trace
     *
     * @return bool
     */
    private function _ti_inBaseOrChild($trace)
    {
        $base_trace = self::$_ti_base['trace'];
        
        return $trace and $base_trace and self::_ti_isSubtrace(array_slice($trace, 1), $base_trace) and $trace[0]['file'] === $base_trace[count($base_trace) - count($trace)]['file'];
    }
    
    /**
     * 是否子跟踪
     *
     * @param $trace1
     * @param $trace2
     *
     * @return bool
     */
    private function _ti_isSubtrace($trace1, $trace2)
    { // is trace1 a subtrace of trace2
        $len1 = count($trace1);
        $len2 = count($trace2);
        
        if ($len1 > $len2)
        {
            return false;
        }
        
        for ($i = 0; $i < $len1; $i++)
        {
            if ($trace1[$len1 - 1 - $i] !== $trace2[$len2 - 1 - $i])
            {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 插入块
     *
     * @param $block
     */
    private function _ti_insertBlock($block)
    { // at this point, $block is done being modified
        $base         =& self::$_ti_base;
        $stack        =& self::$_ti_stack;
        $hash         =& self::$_ti_vars['_ti_hash'];
        $end          =& self::$_ti_vars['_ti_end'];
        $block['end'] = $end = ob_get_length();
        $name         = $block['name'];
        
        if ($stack or self::_ti_inBase($block['trace']))
        {
            $block_anchor = ['start' => $block['start'], 'end' => $end, 'block' => $block];
            
            if ($stack)
            {
                // nested block
                $stack[count($stack) - 1]['children'][] =& $block_anchor;
            }
            else
            {
                // top-level block in base
                $base['children'][] =& $block_anchor;
            }
            
            $hash[$name] =& $block_anchor; // same reference as children array
        }
        else if (isset($hash[$name]))
        {
            if (self::_ti_isSameFile($hash[$name]['block']['trace'], $block['trace']))
            {
                self::_ti_warning("cannot define another block called '$name'", self::_ti_callingTrace(), $block['trace']);
            }
            else
            {
                // top-level block in a child view; override the base's block
                $hash[$name]['block'] = $block;
            }
        }
    }
    
    /**
     * 新区块
     *
     * @param $name
     * @param $filters
     * @param $trace
     *
     * @return array
     */
    private function _ti_newBlock($name, $filters, $trace)
    {
        $base  =& self::$_ti_base;
        $stack =& self::$_ti_stack;
        
        while ($block = end($stack))
        {
            if (self::_ti_isSameFile($block['trace'], $trace))
            {
                break;
            }
            else
            {
                array_pop($stack);
                self::_ti_insertBlock($block);
                self::_ti_warning("missing endblock() for startblock('{$block['name']}')", self::_ti_callingTrace(), $block['trace']);
            }
        }
        
        if ($base['end'] === null and !self::_ti_inBase($trace))
        {
            $base['end'] = ob_get_length();
        }
        
        if ($filters)
        {
            if (is_string($filters))
            {
                $filters = preg_split('/\s*[,|]\s*/', trim($filters));
            }
            else if (!is_array($filters))
            {
                $filters = [$filters];
            }
            
            foreach ($filters as $i => $f)
            {
                if ($f and !is_callable($f))
                {
                    self::_ti_warning(is_array($f) ? "filter " . implode('::', $f) . " is not defined" : "filter '$f' is not defined", $trace);
                    $filters[$i] = null;
                }
            }
        }
        
        return [
            'name' => $name, 'trace' => $trace, 'filters' => $filters, 'children' => [], 'start' => ob_get_length(),
        ];
    }
    
    /**
     * 开始区块定义
     *
     * @param string $name 名称
     * @param null   $filters
     */
    public function startBlock(string $name, $filters = null)
    {
        $trace = self::_ti_callingTrace();
        self::_ti_init($trace);
        $stack   =& self::$_ti_stack;
        $stack[] = self::_ti_newBlock($name, $filters, $trace);
    }
    
    /**
     * 结束区块定义
     *
     * @param string $name
     */
    public function endBlock(string $name)
    {
        $trace = self::_ti_callingTrace();
        
        self::_ti_init($trace);
        
        $stack =& self::$_ti_stack;
        
        if ($stack)
        {
            $block = array_pop($stack);
            
            if ($name and $name != $block['name'])
            {
                self::_ti_warning("startblock('{$block['name']}') does not match endblock('$name')", $trace);
            }
            
            self::_ti_insertBlock($block);
        }
        else
        {
            self::_ti_warning($name ? "orphan endblock('$name')" : "orphan endblock()", $trace);
        }
    }
    
    /**
     * 继承父区块
     */
    public function superBlock()
    {
        if (self::$_ti_stack)
        {
            echo self::getsuperblock();
        }
        else
        {
            self::_ti_warning("superblock() call must be within a block", self::_ti_callingTrace());
        }
    }
    
    /**
     * 获取父区块
     *
     * @return string
     */
    public function getSuperBlock()
    {
        $stack =& self::$_ti_stack;
        
        if ($stack)
        {
            $hash  =& self::$_ti_vars['_ti_hash'];
            $block = end($stack);
            
            if (isset($hash[$block['name']]))
            {
                return implode(self::_ti_compile($hash[$block['name']]['block'], ob_get_contents()));
            }
        }
        else
        {
            self::_ti_warning("getsuperblock() call must be within a block", self::_ti_callingTrace());
        }
        
        return '';
    }
    
    /**
     * 编译模板
     *
     * @param $block
     * @param $buffer
     *
     * @return array
     */
    private function _ti_compile($block, $buffer)
    {
        $parts = [];
        $previ = $block['start'];
        
        foreach ($block['children'] as $child_anchor)
        {
            $parts[] = substr($buffer, $previ, $child_anchor['start'] - $previ);
            $parts   = array_merge($parts, self::_ti_compile($child_anchor['block'], $buffer));
            $previ   = $child_anchor['end'];
        }
        
        if ($previ != $block['end'])
        {
            // could be a big buffer, so only do substr if necessary
            $parts[] = substr($buffer, $previ, $block['end'] - $previ);
        }
        
        if ($block['filters'])
        {
            $s = implode($parts);
            
            foreach ($block['filters'] as $filter)
            {
                if ($filter)
                {
                    $s = call_user_func($filter, $s);
                }
            }
            
            return [$s];
        }
        
        return $parts;
    }
    
    /**
     * 定义基础快
     */
    public function baseBlock()
    {
        self::_ti_init(self::_ti_callingTrace());
    }
    
    /**
     * 缓冲回调
     *
     * @param $buffer
     *
     * @return string
     */
    private function _ti_bufferCallback($buffer)
    {
        $base  =& self::$_ti_base;
        $stack =& self::$_ti_stack;
        $end   =& self::$_ti_vars['_ti_end'];
        $after =& self::$_ti_vars['_ti_after'];
        
        if ($base)
        {
            while ($block = array_pop($stack))
            {
                self::_ti_insertBlock($block);
                self::_ti_warning("missing endblock() for startblock('{$block['name']}')", self::_ti_callingTrace(), $block['trace']);
            }
            
            if ($base['end'] === null)
            {
                $base['end'] = strlen($buffer);
                $end         = null; // todo: more explanation
                // means there were no blocks other than the base's
            }
            
            $parts = self::_ti_compile($base, $buffer);
            // remove trailing whitespace from end
            $i         = count($parts) - 1;
            $parts[$i] = rtrim($parts[$i]);
            // if there are child view blocks, preserve output after last one
            
            if ($end !== null)
            {
                $parts[] = substr($buffer, $end);
            }
            
            // for error messages
            $parts[] = $after;
            
            return implode($parts);
        }
        else
        {
            return '';
        }
    }
}