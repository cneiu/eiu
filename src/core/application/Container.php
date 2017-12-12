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
use ReflectionClass;
use ReflectionParameter;


/**
 * 核心容器
 */
class Container implements ArrayAccess, IContainer

{
    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    protected static $instance;
    
    /**
     * An array of the types that have been resolved.
     *
     * @var array
     */
    protected $resolved = [];
    
    /**
     * The container's bindings.
     *
     * @var array
     */
    protected $bindings = [];
    
    /**
     * The container's method bindings.
     *
     * @var array
     */
    protected $methodBindings = [];
    
    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected $instances = [];
    
    /**
     * The registered type aliases.
     *
     * @var array
     */
    protected $aliases = [];
    
    /**
     * The registered aliases keyed by the abstract name.
     *
     * @var array
     */
    protected $abstractAliases = [];
    
    /**
     * The extension closures for services.
     *
     * @var array
     */
    protected $extenders = [];
    
    /**
     * All of the registered tags.
     *
     * @var array
     */
    protected $tags = [];
    
    /**
     * The stack of concretions currently being built.
     *
     * @var array
     */
    protected $buildStack = [];
    
    /**
     * The parameter override stack.
     *
     * @var array
     */
    protected $with = [];
    
    /**
     * The contextual binding map.
     *
     * @var array
     */
    public $contextual = [];
    
    /**
     * All of the registered rebound callbacks.
     *
     * @var array
     */
    protected $reboundCallbacks = [];
    
    /**
     * All of the global resolving callbacks.
     *
     * @var array
     */
    protected $globalResolvingCallbacks = [];
    
    /**
     * All of the global after resolving callbacks.
     *
     * @var array
     */
    protected $globalAfterResolvingCallbacks = [];
    
    /**
     * All of the resolving callbacks by class type.
     *
     * @var array
     */
    protected $resolvingCallbacks = [];
    
    /**
     * All of the after resolving callbacks by class type.
     *
     * @var array
     */
    protected $afterResolvingCallbacks = [];
    
    /**
     * 定义一个上下文绑定
     *
     * @param  string $concrete
     *
     * @return \eiu\core\application\ContextualBindingBuilder
     */
    public function when($concrete)
    {
        return new ContextualBindingBuilder($this, $this->getAlias($concrete));
    }
    
    /**
     * 判断给定抽象对象是否存在
     *
     * @param  string $abstract
     *
     * @return bool
     */
    public function bound($abstract)
    {
        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]) ||
               $this->isAlias($abstract);
    }
    
    /**
     * 判断给定抽象对象是否已实例化
     *
     * @param  string $abstract
     *
     * @return bool
     */
    public function resolved($abstract)
    {
        if ($this->isAlias($abstract))
        {
            $abstract = $this->getAlias($abstract);
        }
        
        return isset($this->resolved[$abstract]) ||
               isset($this->instances[$abstract]);
    }
    
    /**
     * 判断给定抽象对象可否共享
     *
     * @param  string $abstract
     *
     * @return bool
     */
    public function isShared($abstract)
    {
        return isset($this->instances[$abstract]) ||
               (isset($this->bindings[$abstract]['shared']) &&
                $this->bindings[$abstract]['shared'] === true);
    }
    
    /**
     * 判断给定字符串是否是抽象对象的别名
     *
     * @param  string $name
     *
     * @return bool
     */
    public function isAlias($name)
    {
        return isset($this->aliases[$name]);
    }
    
    /**
     * 绑定给定抽象对象
     *
     * @param  string|array         $abstract
     * @param  \Closure|string|null $concrete
     * @param  bool                 $shared
     *
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        // If no concrete type was given, we will simply set the concrete type to the
        // abstract type. After that, the concrete type to be registered as shared
        // without being forced to state their classes in both of the parameters.
        $this->dropStaleInstances($abstract);
        
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
        
        // If the abstract type was already resolved in this container we'll fire the
        // rebound listener so that any objects which have already gotten resolved
        // can have their copy of the object updated via the listener callbacks.
        if ($this->resolved($abstract))
        {
            $this->rebound($abstract);
        }
    }
    
    /**
     * 通过给定抽象对象、具体对象获取闭包
     *
     * @param  string $abstract
     * @param  string $concrete
     *
     * @return \Closure
     */
    protected function getClosure($abstract, $concrete)
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete)
            {
                return $container->build($concrete);
            }
            
            return $container->make($concrete, $parameters);
        };
    }
    
    /**
     * 判断给定方法名称是否是容器中已绑定的方法
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
     * 绑定一个闭包方法到容器中
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
     * 调用绑定的闭包方法
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
     * 增加一个上下文绑定到容器中
     *
     * @param  string          $concrete
     * @param  string          $abstract
     * @param  \Closure|string $implementation
     *
     * @return void
     */
    public function addContextualBinding($concrete, $abstract, $implementation)
    {
        $this->contextual[$concrete][$this->getAlias($abstract)] = $implementation;
    }
    
    /**
     * 绑定一个尚未绑定的给定对象
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
     * 绑定一个单例（共享）对象
     *
     * @param  string|array         $abstract
     * @param  \Closure|string|null $concrete
     *
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }
    
    /**
     * 扩展绑定对象
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
        $abstract = $this->getAlias($abstract);
        
        if (isset($this->instances[$abstract]))
        {
            $this->instances[$abstract] = $closure($this->instances[$abstract], $this);
            
            $this->rebound($abstract);
        }
        else
        {
            $this->extenders[$abstract][] = $closure;
            
            if ($this->resolved($abstract))
            {
                $this->rebound($abstract);
            }
        }
    }
    
    /**
     * 注册实例化对象到容器中
     *
     * @param  string $abstract
     * @param  mixed  $instance
     *
     * @return mixed
     */
    public function instance($abstract, $instance)
    {
        $this->removeAbstractAlias($abstract);
        
        $isBound = $this->bound($abstract);
        
        unset($this->aliases[$abstract]);
        
        // We'll check to determine if this type has been bound before, and if it has
        // we will fire the rebound callbacks registered with the container and it
        // can be updated with consuming classes that have gotten resolved here.
        $this->instances[$abstract] = $instance;
        
        if ($isBound)
        {
            $this->rebound($abstract);
        }
        
        return $instance;
    }
    
    /**
     * 移除上下文绑定对象的别名
     *
     * @param  string $searched
     *
     * @return void
     */
    protected function removeAbstractAlias($searched)
    {
        if (!isset($this->aliases[$searched]))
        {
            return;
        }
        
        foreach ($this->abstractAliases as $abstract => $aliases)
        {
            foreach ($aliases as $index => $alias)
            {
                if ($alias == $searched)
                {
                    unset($this->abstractAliases[$abstract][$index]);
                }
            }
        }
    }
    
    /**
     * 为给定抽象对象定义一个分组标签
     *
     * @param  array|string $abstracts
     * @param  array|mixed  ...$tags
     *
     * @return void
     */
    public function tag($abstracts, $tags)
    {
        $tags = is_array($tags) ? $tags : array_slice(func_get_args(), 1);
        
        foreach ($tags as $tag)
        {
            if (!isset($this->tags[$tag]))
            {
                $this->tags[$tag] = [];
            }
            
            foreach ((array)$abstracts as $abstract)
            {
                $this->tags[$tag][] = $abstract;
            }
        }
    }
    
    /**
     * 解析给定标签组中的所有绑定对象
     *
     * @param  string $tag
     *
     * @return array
     */
    public function tagged($tag)
    {
        $results = [];
        
        if (isset($this->tags[$tag]))
        {
            foreach ($this->tags[$tag] as $abstract)
            {
                $results[] = $this->make($abstract);
            }
        }
        
        return $results;
    }
    
    /**
     * 为抽象对象定义一个别名
     *
     * @param  string $abstract
     * @param  string $alias
     *
     * @return void
     */
    public function alias($abstract, $alias)
    {
        $this->aliases[$alias] = $abstract;
        
        $this->abstractAliases[$abstract][] = $alias;
    }
    
    /**
     * 绑定一个新的回调到给定抽象对象的重绑定回调列表中
     *
     * @param  string   $abstract
     * @param  \Closure $callback
     *
     * @return mixed
     */
    public function rebinding($abstract, Closure $callback)
    {
        $this->reboundCallbacks[$abstract = $this->getAlias($abstract)][] = $callback;
        
        if ($this->bound($abstract))
        {
            return $this->make($abstract);
        }
    }
    
    /**
     * 用给实例对象和方法刷新一个抽象对象
     *
     * @param  string $abstract
     * @param  mixed  $target
     * @param  string $method
     *
     * @return mixed
     */
    public function refresh($abstract, $target, $method)
    {
        return $this->rebinding($abstract, function ($app, $instance) use ($target, $method) {
            $target->{$method}($instance);
        });
    }
    
    /**
     * 通过给定抽象对象调用所有重绑定的回调列表
     *
     * @param  string $abstract
     *
     * @return void
     */
    protected function rebound($abstract)
    {
        $instance = $this->make($abstract);
        
        foreach ($this->getReboundCallbacks($abstract) as $callback)
        {
            call_user_func($callback, $this, $instance);
        }
    }
    
    /**
     * 通过给定抽象对象获取重绑定回调列表
     *
     * @param  string $abstract
     *
     * @return array
     */
    protected function getReboundCallbacks($abstract)
    {
        if (isset($this->reboundCallbacks[$abstract]))
        {
            return $this->reboundCallbacks[$abstract];
        }
        
        return [];
    }
    
    /**
     * 通过一个闭包对参数进行依赖注入
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
     * 调用一个闭包或"className@methodName"进行依赖注入
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
     * 通过一个闭包实例化给定抽象对象
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
     * 实例化给定抽象对象
     *
     * @param  string $abstract
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }
    
    /**
     * 实例化给定抽象对象
     *
     * @param  string $abstract
     * @param  array  $parameters
     *
     * @return mixed
     */
    protected function resolve($abstract, $parameters = [])
    {
        $abstract = $this->getAlias($abstract);
        
        $needsContextualBuild = !empty($parameters) || !is_null(
                $this->getContextualConcrete($abstract)
            );
        
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
        
        // If we defined any extenders for this type, we'll need to spin through them
        // and apply them to the object being built. This allows for the extension
        // of services, such as changing configuration or decorating the object.
        foreach ($this->getExtenders($abstract) as $extender)
        {
            $object = $extender($object, $this);
        }
        
        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on each subsequent request for it.
        if ($this->isShared($abstract) && !$needsContextualBuild)
        {
            $this->instances[$abstract] = $object;
        }
        
        $this->fireResolvingCallbacks($abstract, $object);
        
        // Before returning, we will also set the resolved flag to "true" and pop off
        // the parameter overrides for this build. After those two things are done
        // we will be ready to return back the fully constructed class instance.
        $this->resolved[$abstract] = true;
        
        array_pop($this->with);
        
        return $object;
    }
    
    /**
     * 通过给定抽象对象获取具体对象
     *
     * @param  string $abstract
     *
     * @return mixed   $concrete
     */
    protected function getConcrete($abstract)
    {
        if (!is_null($concrete = $this->getContextualConcrete($abstract)))
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
     * 通过给定抽象对象获取绑定的上下文具体对象
     *
     * @param  string $abstract
     *
     * @return string|null
     */
    protected function getContextualConcrete($abstract)
    {
        if (!is_null($binding = $this->findInContextualBindings($abstract)))
        {
            return $binding;
        }
        
        // Next we need to see if a contextual binding might be bound under an alias of the
        // given abstract type. So, we will need to check if any aliases exist with this
        // type and then spin through them and check for contextual bindings on these.
        if (empty($this->abstractAliases[$abstract]))
        {
            return;
        }
        
        foreach ($this->abstractAliases[$abstract] as $alias)
        {
            if (!is_null($binding = $this->findInContextualBindings($alias)))
            {
                return $binding;
            }
        }
    }
    
    /**
     * 通过给定抽象对象在上下文绑定列表中查找具体对象
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
    }
    
    /**
     * 判断给定具体对象、抽象对象是否可实例化
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
     * 实例化具体对象
     *
     * @param  string $concrete
     *
     * @return mixed
     *
     * @throws \Exception
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
     * 解决依赖
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
            $results[] = is_null($dependency->getClass())
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency);
        }
        
        return $results;
    }
    
    /**
     * 判断给定依赖参数是否可覆盖
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
     * 获取最后的参数覆盖
     *
     * @return array
     */
    protected function getLastParameterOverride()
    {
        return count($this->with) ? end($this->with) : [];
    }
    
    /**
     * 解析原始依赖(非注入)项
     *
     * @param  \ReflectionParameter $parameter
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if (!is_null($concrete = $this->getContextualConcrete('$' . $parameter->name)))
        {
            return $concrete instanceof Closure ? $concrete($this) : $concrete;
        }
        
        if ($parameter->isDefaultValueAvailable())
        {
            return $parameter->getDefaultValue();
        }
        
        $this->unresolvablePrimitive($parameter);
    }
    
    /**
     * 解析类依赖项
     *
     * @param  \ReflectionParameter $parameter
     *
     * @return mixed
     *
     * @throws \Exception
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
        catch (\Exception $e)
        {
            if ($parameter->isOptional())
            {
                return $parameter->getDefaultValue();
            }
            
            throw $e;
        }
    }
    
    /**
     * 触发"给定实例对象不能实例化"的异常
     *
     * @param  string $concrete
     *
     * @return void
     *
     * @throws \Exception
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
        
        throw new \Exception($message);
    }
    
    /**
     * 触发"无法注入的依赖项"异常
     *
     * @param  \ReflectionParameter $parameter
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function unresolvablePrimitive(ReflectionParameter $parameter)
    {
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";
        
        throw new \Exception($message);
    }
    
    /**
     * 注册一个新的抽象对象实例化回调
     *
     * @param  string        $abstract
     * @param  \Closure|null $callback
     *
     * @return void
     */
    public function resolving($abstract, Closure $callback = null)
    {
        if (is_string($abstract))
        {
            $abstract = $this->getAlias($abstract);
        }
        
        if (is_null($callback) && $abstract instanceof Closure)
        {
            $this->globalResolvingCallbacks[] = $abstract;
        }
        else
        {
            $this->resolvingCallbacks[$abstract][] = $callback;
        }
    }
    
    /**
     * 注册一个新的抽象对象实例化后的回调
     *
     * @param  string        $abstract
     * @param  \Closure|null $callback
     *
     * @return void
     */
    public function afterResolving($abstract, Closure $callback = null)
    {
        if (is_string($abstract))
        {
            $abstract = $this->getAlias($abstract);
        }
        
        if ($abstract instanceof Closure && is_null($callback))
        {
            $this->globalAfterResolvingCallbacks[] = $abstract;
        }
        else
        {
            $this->afterResolvingCallbacks[$abstract][] = $callback;
        }
    }
    
    /**
     * 触发给定抽象对象实例化事件
     *
     * @param  string $abstract
     * @param  mixed  $object
     *
     * @return void
     */
    protected function fireResolvingCallbacks($abstract, $object)
    {
        $this->fireCallbackArray($object, $this->globalResolvingCallbacks);
        
        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->resolvingCallbacks)
        );
        
        $this->fireAfterResolvingCallbacks($abstract, $object);
    }
    
    /**
     * 触发给定抽象对象实例化后事件
     *
     * @param  string $abstract
     * @param  mixed  $object
     *
     * @return void
     */
    protected function fireAfterResolvingCallbacks($abstract, $object)
    {
        $this->fireCallbackArray($object, $this->globalAfterResolvingCallbacks);
        
        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->afterResolvingCallbacks)
        );
    }
    
    /**
     * 通过给定类型获取所有回调
     *
     * @param  string $abstract
     * @param  object $object
     * @param  array  $callbacksPerType
     *
     * @return array
     */
    protected function getCallbacksForType($abstract, $object, array $callbacksPerType)
    {
        $results = [];
        
        foreach ($callbacksPerType as $type => $callbacks)
        {
            if ($type === $abstract || $object instanceof $type)
            {
                $results = array_merge($results, $callbacks);
            }
        }
        
        return $results;
    }
    
    /**
     * 执行一个回调函数数组
     *
     * @param  mixed $object
     * @param  array $callbacks
     *
     * @return void
     */
    protected function fireCallbackArray($object, array $callbacks)
    {
        foreach ($callbacks as $callback)
        {
            $callback($object, $this);
        }
    }
    
    /**
     * 获取容器所有绑定的对象
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }
    
    /**
     * 获取给定抽象对象的所有别名
     *
     * @param  string $abstract
     *
     * @return string
     *
     * @throws \LogicException
     */
    public function getAlias($abstract)
    {
        if (!isset($this->aliases[$abstract]))
        {
            return $abstract;
        }
        
        if ($this->aliases[$abstract] === $abstract)
        {
            throw new LogicException("[{$abstract}] is aliased to itself.");
        }
        
        return $this->getAlias($this->aliases[$abstract]);
    }
    
    /**
     * 获取给定抽象对象的所有回调
     *
     * @param  string $abstract
     *
     * @return array
     */
    protected function getExtenders($abstract)
    {
        $abstract = $this->getAlias($abstract);
        
        if (isset($this->extenders[$abstract]))
        {
            return $this->extenders[$abstract];
        }
        
        return [];
    }
    
    /**
     * 移除给定抽象对象的所有回调
     *
     * @param  string $abstract
     *
     * @return void
     */
    public function forgetExtenders($abstract)
    {
        unset($this->extenders[$this->getAlias($abstract)]);
    }
    
    /**
     * 丢弃给定抽象对象及其别名
     *
     * @param  string $abstract
     *
     * @return void
     */
    protected function dropStaleInstances($abstract)
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }
    
    /**
     * 丢弃给定抽象对象
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
     * 清空容器实例化列表
     *
     * @return void
     */
    public function forgetInstances()
    {
        $this->instances = [];
    }
    
    /**
     * 清空容器
     *
     * @return void
     */
    public function flush()
    {
        $this->aliases         = [];
        $this->resolved        = [];
        $this->bindings        = [];
        $this->instances       = [];
        $this->abstractAliases = [];
    }
    
    /**
     * 获取容器实例
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
     * 设置容器实例
     *
     * @param  \eiu\core\application\Container|null $container
     *
     * @return static
     */
    public static function setInstance(IContainer $container = null)
    {
        return static::$instance = $container;
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
        $this->bind($key, $value instanceof Closure ? $value : function () use ($value) {
            return $value;
        });
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