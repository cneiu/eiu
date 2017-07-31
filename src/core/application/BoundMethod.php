<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\application;


use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionMethod;


/**
 * 方法绑定器
 *
 * @package eiu\core\application
 */
class BoundMethod
{
    /**
     * 调用闭包或方法并注入依赖
     *
     * @param  \eiu\core\application\Container $container
     * @param  callable|string                 $callback 闭包函数或"类名@方法名"形式字符串
     * @param  array                           $parameters
     * @param  string|null                     $defaultMethod
     *
     * @return mixed
     */
    public static function call($container, $callback, array $parameters = [], $defaultMethod = null)
    {
        if (static::isCallableWithAtSign($callback) || $defaultMethod)
        {
            return static::callClass($container, $callback, $parameters, $defaultMethod);
        }
        
        return static::callBoundMethod($container, $callback, function () use ($container, $callback, $parameters) {
            return call_user_func_array(
                $callback, static::getMethodDependencies($container, $callback, $parameters)
            );
        }
        );
    }
    
    /**
     * 是否"类名@方法名"形式字符串调用
     *
     * @param  mixed $callback
     *
     * @return bool
     */
    protected static function isCallableWithAtSign($callback)
    {
        return is_string($callback) && strpos($callback, '@') !== false;
    }
    
    /**
     * 调用"类名@方法名"形式方法
     *
     * @param  \eiu\core\application\Container $container
     * @param  string                          $target
     * @param  array                           $parameters
     * @param  string|null                     $defaultMethod
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected static function callClass($container, $target, array $parameters = [], $defaultMethod = null)
    {
        $segments = explode('@', $target);
        
        // We will assume an @ sign is used to delimit the class name from the method
        // name. We will split on this @ sign and then build a callable array that
        // we can pass right back into the "call" method for dependency binding.
        $method = count($segments) == 2 ? $segments[1] : $defaultMethod;
        
        if (is_null($method))
        {
            throw new InvalidArgumentException('Method not provided.');
        }
        
        return static::call(
            $container, [$container->make($segments[0]), $method], $parameters
        );
    }
    
    /**
     * 调用已绑定方法
     *
     * @param  \eiu\core\application\Container $container
     * @param  callable                        $callback
     * @param  mixed                           $default
     *
     * @return mixed
     */
    protected static function callBoundMethod($container, $callback, $default)
    {
        if (!is_array($callback))
        {
            return $default instanceof Closure ? $default() : $default;
        }
        
        // Here we need to turn the array callable into a Class@method string we can use to
        // examine the container and see if there are any method bindings for this given
        // method. If there are, we can call this method binding callback immediately.
        $method = static::normalizeMethod($callback);
        
        if ($container->hasMethodBinding($method))
        {
            return $container->callMethodBinding($method, $callback[0]);
        }
        
        return $default instanceof Closure ? $default() : $default;
    }
    
    /**
     * 生成"类名@方法名"形式方法字符串
     *
     * @param  callable $callback
     *
     * @return string
     */
    protected static function normalizeMethod($callback)
    {
        $class = is_string($callback[0]) ? $callback[0] : get_class($callback[0]);
        
        return "{$class}@{$callback[1]}";
    }
    
    /**
     * 获取给定方法的依赖
     *
     * @param  \eiu\core\application\ContainerContract
     * @param  callable|string $callback
     * @param  array           $parameters
     *
     * @return array
     */
    protected static function getMethodDependencies($container, $callback, array $parameters = [])
    {
        $dependencies = [];
        
        $reflection = static::getCallReflector($callback);
        
        foreach ($reflection->getParameters() as $parameter)
        {
            static::addDependencyForCallParameter($container, $parameter, $parameters, $dependencies);
        }
        
        $needParameters = ($reflection->getNumberOfParameters() - count($dependencies));
        
        return array_merge($dependencies, array_pad($parameters, $needParameters, null));
    }
    
    /**
     * 根据给定回调获取相应的反射实例
     *
     * @param  callable|string $callback
     *
     * @return \ReflectionFunctionAbstract
     */
    protected static function getCallReflector($callback)
    {
        if (is_string($callback) && strpos($callback, '::') !== false)
        {
            $callback = explode('::', $callback);
        }
        
        return is_array($callback) ? new ReflectionMethod($callback[0], $callback[1]) : new ReflectionFunction($callback);
    }
    
    /**
     * 为给定的回调参数生成依赖
     *
     * @param  \eiu\core\application\Container $container
     * @param  \ReflectionParameter            $parameter
     * @param  array                           $parameters
     * @param  array                           $dependencies
     *
     * @return mixed
     */
    protected static function addDependencyForCallParameter($container, $parameter, array &$parameters, &$dependencies)
    {
        if (array_key_exists($parameter->name, $parameters))
        {
            $dependencies[] = $parameters[$parameter->name];
            
            unset($parameters[$parameter->name]);
        }
        else if ($parameter->getClass())
        {
            $dependencies[] = $container->make($parameter->getClass()->name);
        }
        else if ($parameter->isDefaultValueAvailable())
        {
            $dependencies[] = $parameter->getDefaultValue();
        }
    }
}
