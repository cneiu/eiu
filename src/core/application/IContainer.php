<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\application;


use Closure;


interface IContainer
{
    /**
     * 是否已绑定
     *
     * @param  string $abstract
     *
     * @return bool
     */
    public function bound($abstract);
    
    /**
     * 注册一个抽象类型
     *
     * @param  string|array         $abstract 抽象类型
     * @param  \Closure|string|null $concrete 实体类型
     * @param  bool                 $shared
     *
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false);
    
    /**
     * 注册一个抽象类型（如果尚未注册）
     *
     * @param  string               $abstract 抽象类型
     * @param  \Closure|string|null $concrete 实体类型
     * @param  bool                 $shared
     *
     * @return void
     */
    public function bindIf($abstract, $concrete = null, $shared = false);
    
    /**
     * 扩展抽象类型
     *
     * @param  string   $abstract 抽象类型
     * @param  \Closure $closure  闭包函数
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function extend($abstract, Closure $closure);
    
    /**
     * 注册一个共享的实例化对象
     *
     * @param  string $abstract 抽象类型
     * @param  mixed  $instance 实体对象
     *
     * @return void
     */
    public function instance($abstract, $instance);
    
    /**
     * 获取一个闭包来实例化抽象类型
     *
     * @param  string $abstract 抽象类型
     *
     * @return \Closure
     */
    public function factory($abstract);
    
    /**
     * 实例化抽象类型
     *
     * @param  string $abstract 抽象类型
     *
     * @return mixed
     */
    public function make($abstract);
    
    /**
     * 调用一个回调函数并注入回调函数的参数依赖关系
     *
     * @param  callable|string $callback      回调函数
     * @param  array           $parameters    参数
     * @param  string|null     $defaultMethod 默认方法
     *
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null);
    
    /**
     * 是否已实例化
     *
     * @param  string $abstract 抽象类型
     *
     * @return bool
     */
    public function resolved($abstract);
}