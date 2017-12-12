<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\application;


use eiu\core\service\debug\ExceptionProvider;
use eiu\core\service\event\EventProvider;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\output\OutputProvider;
use eiu\core\service\router\RequestProvider;
use eiu\core\service\router\RouterProvider;


/**
 * HTTP请求处理核心
 *
 * @package eiu\core\application
 */
class HttpKernel implements IKernel
{
    /**
     * The application implementation.
     *
     * @var Application
     */
    protected $app;
    
    /**
     * @var EventProvider
     */
    private $event;
    
    /**
     * @var RouterProvider
     */
    private $router;
    
    /**
     * @var RequestProvider
     */
    private $request;
    
    /**
     * @var OutputProvider
     */
    private $output;
    
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var ExceptionProvider
     */
    private $exception;
    
    /**
     * 创建一个新的HTTP请求处理核心实例
     *
     * @param \eiu\core\application\Container $app
     * @param LoggerProvider                  $logger
     * @param EventProvider                   $event
     * @param RouterProvider                  $router
     * @param RequestProvider                 $request
     * @param OutputProvider                  $output
     * @param ExceptionProvider               $exception
     */
    public function __construct(Container $app, LoggerProvider $logger, EventProvider $event, RouterProvider $router, RequestProvider $request, OutputProvider $output, ExceptionProvider $exception)
    {
        $this->app       = $app;
        $this->event     = $event;
        $this->router    = $router;
        $this->request   = $request;
        $this->output    = $output;
        $this->logger    = $logger;
        $this->exception = $exception;
    }
    
    /**
     * 处理一个请求
     *
     */
    public function handle()
    {
        register_shutdown_function([&$this, 'shutdown']);
        
        // 记录启动时间
        $this->app->timerTick('kernel.begin', defined(APP_ENTRY) ? APP_ENTRY : microtime(true));
        
        // 服务启动
        $this->app->boot();
        
        // 事件 系统服务启动完成
        $this->event->fire('kernel.begin');
        
        // 路由解析
        $pathInfoUri = $this->router->parseUrl();
        
        // 事件 解析URL完成
        $this->event->fire('router.parseUrl.after');
        
        // 控制器解析
        $router = $this->router->parseController($pathInfoUri);
        
        // 事件 解析控制器完成
        $this->event->fire('router.parseController.after');
        
        // 生成请求包装
        $request = $this->request->setRouter($router);
        
        // 事件 生成请求包装完成
        $this->event->fire('router.makeRequest.after');
        
        // 计时器
        $this->app->timerTick('controller.start');
        
        // 执行控制器
        $this->executeController($request);
        
        // 计时器
        $this->app->timerTick('controller.over');
        
        // 事件 控制器执行完成
        $this->event->fire('controller.execute.after');
        
        // 输出渲染
        $this->output->render();
        
        // 事件 输出渲染完成
        $this->event->fire('output.after');
        
        // timer
        $this->app->timerTick('kernel.over');
        
        // 事件 应用结束
        $this->event->fire('kernel.over');
        
        // 遗言
        $this->lastWords();
    }
    
    /**
     * 调用控制器
     *
     * @param RequestProvider $request
     *
     * @return mixed
     */
    public function executeController(RequestProvider $request)
    {
        $class  = substr($request['router']['namespace'] . '\\' . $request['router']['className'], 1);
        $method = $request['router']['method'];
        $params = $request['router']['pathParams'];
        
        return $this->app->call("{$class}@_{$method}", $params);
    }
    
    /**
     * 请求释放
     */
    public function shutdown()
    {
        // 异常检测
        if ($last_error = error_get_last() and ($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING)))
        {
            $this->exception->errorHandler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
        }
        
        // 程序中断未执行页面输出则再次输出
        if (!$this->output->isRendered())
        {
            // 输出渲染
            $this->output->render();
            
            // 遗言
            $this->lastWords();
        }
    }
    
    /**
     * 遗言
     */
    private function lastWords()
    {
        // 性能统计
        $memory       = $this->app->getMemory();
        $totalElapsed = (float)$this->app->timerElapsed('kernel.begin', 'kernel.over', 4);
        $execElapsed  = (float)$this->app->timerElapsed('controller.start', 'controller.over', 4);
        
        // 告别
        $this->logger->info("Total execution time: {$totalElapsed}s, Controller execution time: {$execElapsed}, Memory: {$memory}." . PHP_EOL . PHP_EOL. PHP_EOL);
    }
}
