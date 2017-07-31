<?php
defined('APP_ENTRY') or exit('Access denied');

/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */

/*
 * --------------------------------------------------------------------
 * 缓存配置表
 * --------------------------------------------------------------------
 */

return [
    
    // 适配器
    'adapter' => [
        // 定义适配器
        'cacheFile' => [
            // 适配器类型
            'type' => 'file',
            // 存储路径
            'dir'  => APP_DATA . 'cache',
        ],
    ],
    
    // 路由
    'router'  => [
        // 路径 => [缓存适配器, 缓存周期]
        'test/unit/user/index/ab/c' => ['cacheFile', 300],
    ],

];