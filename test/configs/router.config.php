<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */
defined('APP_ENTRY') or exit('Access denied');

/**
 * 项目配置项
 */
return [
    // 默认控制器路径
    'DEFAULT_CONTROLLER' => 'admin',
    
    // 默认动作
    'DEFAULT_ACTION'     => 'index',
    
    // 伪装扩展名
    'URL_SUFFIX'         => '',
    
    // URL 生成样式
    // pathinfo|rewrite
    'URL_STYLE'          => 'rewrite',
    
    // 路由别名
    // 通过定义别名让长路径变为短路径
    // 如 ?register/ 指向 ?member/register/
    // 非别名优先级高于别名优先级
    // 使用$this->url()动态生成URL地址请使用完整路径，程序自动生成别名路径
    // 如 $this->url('member/register/') 自动生成 ?register
    'REQUEST_ALIAS'      => [
        'admin_login'  => 'admin/login',//
        'admin_logout' => 'admin/logout',//
    ],
];