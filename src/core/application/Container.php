<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\application;


use ArrayAccess;
use Closure;
use Exception;
use ReflectionClass;
use ReflectionParameter;


/**
 * 核心容器
 */
class Container implements ArrayAccess, IContainer
{
    /**
     * 当前容器实例
     *
     * @var static
     */
    protected static $instance;
    
    /**
     * 绑定上下文
     *
     * @var array
     */
    public $contextual = [];
    
    /**
     * 已实例化实体的数组
     *
     * @var array
     */
    protected $resolved = [];
    
    /**
     * 容器绑定数组
     *
     * 存储提供服务的回调函数
     *
     * @var array
     */
    protected $bindings = [];
    
    /**
     * 绑定方法数组
     *
     * @var array
     */
    protected $methodBindings = [];
    
    /**
     * 容器共享实例数组
     *
     * 存储共享实例（单例）
     *
     * @var array
     */
    protected $instances = [];
    
    /**
     * 扩展闭包
     *
     * @var array
     */
    protected $extenders = [];
    
    /**
     * 当前上下文堆栈
     *
     * @var array
     */
    protected $buildStack = [];
    
    /**
     * 重写堆栈参数
     *
     * @var array
     */
    protected $with = [];
    
    /**
     * 获取应用实例
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance))
        {
            static::$instance = new static;
        }
        
        return static::$instance;
    }
    
    /**
     * 设置应用实例
     *
     * @param  IContainer|null $container
     *
     * @return IContainer|Container
     */
    public static function setInstance(IContainer $container = null)
    {
        return static::$instance = $container;
    }
    
    /**
     * 方法是否已绑定
     *
     * @param  string $method
     *
     * @return bool
     */
    public function hasMethodBinding($method)
    {
        return isset($this->methodBindings[$method]);
    }
    
    /**
     * 绑定方法
     *
     * @param  string   $method
     * @param  \Closure $callback
     *
     * @return void
     */
    public function bindMethod($method, $callback)
    {
        $this->methodBindings[$method] = $callback;
    }
    
    /**
     * 调用方法绑定
     *
     * @param  string $method
     * @param  mixed  $instance
     *
     * @return mixed
     */
    public function callMethodBinding($method, $instance)
    {
        return call_user_func($this->methodBindings[$method], $instance, $this);
    }
    
    /**
     * 增加上下文绑定
     *
     * @param  string          $concrete
     * @param  string          $abstract
     * @param  \Closure|string $implementation
     *
     * @return void
     */
    public function addContextualBinding($concrete, $abstract, $implementation)
    {
        $this->contextual[$concrete][$abstract] = $implementation;
    }
    
    /**
     * 绑定抽象类型(如果不存在)
     *
     * @param  string               $abstract
     * @param  \Closure|string|null $concrete
     * @param  bool                 $shared
     *
     * @return void
     */
    public function bindIf($abstract, $concrete = null, $shared = false)
    {
        if (!$this->bound($abstract))
        {
            $this->bind($abstract, $concrete, $shared);
        }
    }
    
    /**
     * 是否已绑定
     *
     * @param  string $abstract
     *
     * @return bool
     */
    public function bound($abstract)
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
    
    /**
     * 绑定抽象类型
     *
     * @param  string|array         $abstract
     * @param  \Closure|string|null $concrete
     * @param  bool                 $shared
     *
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        unset($this->instances[$abstract]);
        
        if (is_null($concrete))
        {
            $concrete = $abstract;
        }
        
        // If the factory is not a Closure, it means it is just a class name which is
        // bound into this container to the abstract type and we will just wrap it
        // up inside its own Closure to give us more convenience when extending.
        if (!$concrete instanceof Closure)
        {
            $concrete = $this->getClosure($abstract, $concrete);
        }
        
        $this->bindings[$abstract] = compact('concrete', 'shared');
        
        if ($this->resolved($abstract))
        {
            $this->make($abstract);
        }
    }
    
    /**
     * 获取一个闭包来实例化实体类型
     *
     * @param  string $abstract
     * @param  string $concrete
     *
     * @return \Closure
     */
    protected function getClosure($abstract, $concrete)
    {
        /**
         * @param Container $container
         * @param array     $parameters
         *
         * @return mixed
         */
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete)
            {
                return $container->build($concrete);
            }
            
            return $container->makeWith($concrete, $parameters);
        };
    }
    
    /**
     * 实例化实体类型
     *
     * @param  string $concrete
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function build($concrete)
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
        if ($concrete instanceof Closure)
        {
            return $concrete($this, $this->getLastParameterOverride());
        }
        
        $reflector = new ReflectionClass($concrete);
        
        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface of Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
        if (!$reflector->isInstantiable())
        {
            return $this->notInstantiable($concrete);
        }
        
        $this->buildStack[] = $concrete;
        
        $constructor = $reflector->getConstructor();
        
        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (is_null($constructor))
        {
            array_pop($this->buildStack);
            
            return new $concrete;
        }
        
        $dependencies = $constructor->getParameters();
        
        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        $instances = $this->resolveDependencies(
            $dependencies
        );
        
        array_pop($this->buildStack);
        
        return $reflector->newInstanceArgs($instances);
    }
    
    /**
     * 获取最后的参数覆盖
     *
     * @return array
     */
    protected function getLastParameterOverride()
    {
        return count($this->with) ? end($this->with) : [];
    }
    
    /**
     * 抛出类型无法实例化异常
     *
     * @param  string $concrete
     *
     * @throws Exception
     */
    protected function notInstantiable($concrete)
    {
        if (!empty($this->buildStack))
        {
            $previous = implode(', ', $this->buildStack);
            
            $message = "Target [$concrete] is not instantiable while building [$previous].";
        }
        else
        {
            $message = "Target [$concrete] is not instantiable.";
        }
        
        throw new Exception($message);
    }
    
    /**
     * 解析依赖
     *
     * @param  array $dependencies
     *
     * @return array
     */
    protected function resolveDependencies(array $dependencies)
    {
        $results = [];
        
        foreach ($dependencies as $dependency)
        {
            // If this dependency has a override for this particular build we will use
            // that instead as the value. Otherwise, we will continue with this run
            // of resolutions and let reflection attempt to determine the result.
            if ($this->hasParameterOverride($dependency))
            {
                $results[] = $this->getParameterOverride($dependency);
                
                continue;
            }
            
            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we will just bomb out with an error since we have no-where to go.
            $results[] = is_null($class = $dependency->getClass()) ? $this->resolvePrimitive($dependency) : $this->resolveClass($dependency);
        }
        
        return $results;
    }
    
    /**
     * 是否存在参数覆盖
     *
     * @param  \ReflectionParameter $dependency
     *
     * @return bool
     */
    protected function hasParameterOverride($dependency)
    {
        return array_key_exists(
            $dependency->name, $this->getLastParameterOverride()
        );
    }
    
    /**
     * 获取参数覆盖
     *
     * @param  \ReflectionParameter $dependency
     *
     * @return mixed
     */
    protected function getParameterOverride($dependency)
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }
    
    /**
     * 解析其他类型依赖
     *
     * @param  \ReflectionParameter $parameter
     *
     * @return mixed
     *
     * @throws Exception
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if (!is_null($concrete = $this->findInContextualBindings('$' . $parameter->name)))
        {
            return $concrete instanceof Closure ? $concrete($this) : $concrete;
        }
        
        if ($parameter->isDefaultValueAvailable())
        {
            return $parameter->getDefaultValue();
        }
        
        $this->unresolvablePrimitive($parameter);
        
        return null;
    }
    
    /**
     * 从绑定上下文查找实体类型
     *
     * @param  string $abstract
     *
     * @return string|null
     */
    protected function findInContextualBindings($abstract)
    {
        if (isset($this->contextual[end($this->buildStack)][$abstract]))
        {
            return $this->contextual[end($this->buildStack)][$abstract];
        }
        
        return null;
    }
    
    /**
     * 抛出依赖无法解析异常
     *
     * @param  \ReflectionParameter $parameter
     *
     * @return void
     *
     * @throws Exception
     */
    protected function unresolvablePrimitive(ReflectionParameter $parameter)
    {
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";
        
        throw new Exception($message);
    }
    
    /**
     * 解析依赖参数
     *
     * @param  \ReflectionParameter $parameter
     *
     * @return mixed
     *
     * @throws Exception
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try
        {
            return $this->make($parameter->getClass()->name);
        }
            
            // If we can not resolve the class instance, we will check to see if the value
            // is optional, and if it is we will return the optional parameter value as
            // the value of the dependency, similarly to how we do this with scalars.
        catch (Exception $e)
        {
            if ($parameter->isOptional())
            {
                return $parameter->getDefaultValue();
            }
            
            throw $e;
        }
    }
    
    /**
     * 解析抽象类型
     *
     * @param  string $abstract
     *
     * @return mixed
     */
    public function make($abstract)
    {
        return $this->resolve($abstract);
    }
    
    /**
     * 解析抽象类型
     *
     * @param  string $abstract
     * @param  array  $parameters
     *
     * @return mixed
     */
    protected function resolve($abstract, $parameters = [])
    {
        $needsContextualBuild = !empty($parameters) || !is_null($this->findInContextualBindings($abstract));
        
        // If an instance of the type is currently being managed as a singleton we'll
        // just return an existing instance instead of instantiating new instances
        // so the developer can keep using the same objects instance every time.
        if (isset($this->instances[$abstract]) && !$needsContextualBuild)
        {
            return $this->instances[$abstract];
        }
        
        $this->with[] = $parameters;
        
        $concrete = $this->getConcrete($abstract);
        
        // We're ready to instantiate an instance of the concrete type registered for
        // the binding. This will instantiate the types, as well as resolve any of
        // its "nested" dependencies recursively until all have gotten resolved.
        if ($this->isBuildable($concrete, $abstract))
        {
            $object = $this->build($concrete);
        }
        else
        {
            $object = $this->make($concrete);
        }
        
        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on each subsequent request for it.
        if ($this->isShared($abstract) && !$needsContextualBuild)
        {
            $this->instances[$abstract] = $object;
        }
        
        // Before returning, we will also set the resolved flag to "true" and pop off
        // the parameter overrides for this build. After those two things are done
        // we will be ready to return back the fully constructed class instance.
        $this->resolved[$abstract] = true;
        
        array_pop($this->with);
        
        return $object;
    }
    
    /**
     * 通过抽象类型获取实体类型
     *
     * @param  string $abstract
     *
     * @return mixed   $concrete
     */
    protected function getConcrete($abstract)
    {
        if (!is_null($concrete = $this->findInContextualBindings($abstract)))
        {
            return $concrete;
        }
        
        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
        if (isset($this->bindings[$abstract]))
        {
            return $this->bindings[$abstract]['concrete'];
        }
        
        return $abstract;
    }
    
    /**
     * 实体类型是否可实例化
     *
     * @param  mixed  $concrete
     * @param  string $abstract
     *
     * @return bool
     */
    protected function isBuildable($concrete, $abstract)
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }
    
    /**
     * 是否共享类型
     *
     * @param  string $abstract
     *
     * @return bool
     */
    public function isShared($abstract)
    {
        return isset($this->instances[$abstract]) || (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared'] === true);
    }
    
    /**
     * 解析抽象类型并覆盖参数
     *
     * @param  string $abstract
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function makeWith($abstract, array $parameters)
    {
        return $this->resolve($abstract, $parameters);
    }
    
    /**
     * 是否已实例化
     *
     * @param  string $abstract
     *
     * @return bool
     */
    public function resolved($abstract)
    {
        return isset($this->resolved[$abstract]) || isset($this->instances[$abstract]);
    }
    
    /**
     * 扩展抽象类型
     *
     * @param  string   $abstract
     * @param  \Closure $closure
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function extend($abstract, Closure $closure)
    {
        if (isset($this->instances[$abstract]))
        {
            $this->instances[$abstract] = $closure($this->instances[$abstract], $this);
            
            $this->make($abstract);
        }
        else
        {
            $this->extenders[$abstract][] = $closure;
            
            if ($this->resolved($abstract))
            {
                $this->make($abstract);
            }
        }
    }
    
    /**
     * 注册一个共享实例
     *
     * @param  string $abstract
     * @param  mixed  $instance
     *
     * @return void
     */
    public function instance($abstract, $instance)
    {
        $isBound = $this->bound($abstract);
        
        $this->instances[$abstract] = $instance;
        
        if ($isBound)
        {
            $this->make($abstract);
        }
    }
    
    /**
     * 调用闭包并自动注入依赖
     *
     * @param  \Closure $callback
     * @param  array    $parameters
     *
     * @return \Closure
     */
    public function wrap(Closure $callback, array $parameters = [])
    {
        return function () use ($callback, $parameters) {
            return $this->call($callback, $parameters);
        };
    }
    
    /**
     * 调用闭包或类方法(Closure / class@method)并自动注入依赖
     *
     * @param  callable|string $callback
     * @param  array           $parameters
     * @param  string|null     $defaultMethod
     *
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        return BoundMethod::call($this, $callback, $parameters, $defaultMethod);
    }
    
    /**
     * 获取一个闭包实例化抽象类型
     *
     * @param  string $abstract
     *
     * @return \Closure
     */
    public function factory($abstract)
    {
        return function () use ($abstract) {
            return $this->make($abstract);
        };
    }
    
    /**
     * 获取所有绑定
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }
    
    /**
     * 清除一个实例
     *
     * @param  string $abstract
     *
     * @return void
     */
    public function forgetInstance($abstract)
    {
        unset($this->instances[$abstract]);
    }
    
    /**
     * 清除所有实例
     *
     * @return void
     */
    public function forgetInstances()
    {
        $this->instances = [];
    }
    
    /**
     * 清除所有绑定和实例
     *
     * @return void
     */
    public function flush()
    {
        $this->resolved  = [];
        $this->bindings  = [];
        $this->instances = [];
    }
    
    /**
     * Determine if a given offset exists.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->bound($key);
    }
    
    /**
     * Get the value at a given offset.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->make($key);
    }
    
    /**
     * Set the value at a given offset.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->bind(
            $key, $value instanceof Closure ? $value : function () use ($value) {
            return $value;
        }
        );
    }
    
    /**
     * Unset the value at a given offset.
     *
     * @param  string $key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
    }
    
    /**
     * Dynamically access container services.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }
    
    /**
     * Dynamically set container services.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }
}