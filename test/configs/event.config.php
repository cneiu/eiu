<?php

/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */

use app\modules\services\sys\SyncService;
use eiu\core\application\Application;


defined('APP_ENTRY') or exit('Access denied');

/**
 * 项目配置项
 */
return [
    
    /********************************
     *         系统流程事件          *
     *******************************/
    
    // 开始应用 > 之后
    'kernel.begin'                 => function ($app)
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'OPTIONS')
        {
            $app->view->text('');
            $app->output->render();
            exit;
        }
    },
    
    // 解析路由 > 之后
    'router.parseUrl.after'        => function ($app)
    {
        //
    },
    
    // 解析控制器 > 之后
    'router.parseController.after' => function ($app)
    {
        //
    },
    
    // 生成请求包装 > 之后
    'router.makeRequest.after'     => function (Application $app)
    {
        // 登录认证
        $app->call('app\modules\services\sys\AuthService@checkLogin');
        
        // 缓存检查
        $app->call('app\modules\services\sys\CacheService@checkAndOutput');
    },
    
    // 控制器执行 > 之后
    'controller.execute.after'     => function ($app)
    {
        //
    },
    
    // 输出渲染 > 之后
    'output.after'                 => function ($app)
    {
        //
    },
    
    // 结束应用 > 之后
    'kernel.over'                  => function ($app)
    {
        //
    },
    
    /********************************
     *         数据模型事件          *
     *******************************/
    
    // 插入之前
    'model.insert.begin'           => function ($app, $model, $data, $sql)
    {
        //
    },
    
    // 插入之后
    'model.insert.begin'           => function ($app, $model, $data, $sql)
    {
        // 写同步日志
        $this->app->build(SyncService::class)->writeLog($model, 'C', [], $data, $sql);
    },
    
    // 更新之前
    'model.update.begin'           => function ($app, $model, $data, $sql)
    {
        //
    },
    
    // 更新之后
    'model.update.begin'           => function ($app, $model, $query, $data, $sql)
    {
        // 写同步日志
        $this->app->build(SyncService::class)->writeLog($model, 'U', $query, $data, $sql);
    },
    
    // 删除之前
    'model.delete.begin'           => function ($app, $model, $query, $sql)
    {
        // 写同步日志
        $this->app->build(SyncService::class)->writeLog($model, 'D', $query, [], $sql);
    },
    
    // 删除之后
    'model.delete.after'           => function ($app, $model, $query, $sql)
    {
        //
    },
];