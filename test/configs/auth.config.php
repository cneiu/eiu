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
 * 认证系统配置
 * --------------------------------------------------------------------
 */

return [
    
    // 默认认证方式
    'WAY'               => 'jwt',
    
    // JWT 密钥
    'JWT_KEY'           => 'f9F5a7V6s9c4q539',
    
    // 无需登录认证的路径
    'LOGIN_EXEMPT'      => [
        
        // 测试
        'test',
        
        'dev',
        
        // 管理登陆
        'admin/login',
        'admin/logout',
        'admin/setting',
        'admin/verify',
        'admin/index',
        
        // 终端
        'terminal/sync',
    
    ],
    
    // 无需权限认证的路径
    'PERMISSION_EXEMPT' => [
        'admin/logout',
        'admin/not_permission',
        'admin/userinfo',
        'admin/page',
        'admin/navigation',
        'admin/store',
        'admin/state',
    
    ],
];
