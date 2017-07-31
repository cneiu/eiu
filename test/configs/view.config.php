<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */
defined('APP_ENTRY') or exit('Access denied');

/**
 * 视图配置项
 */
return [
    
    // 生成静态文件扩展名
    'VIEW_BUILD_EXTENSION' => '.html',
    
    // 全局模板变量
    'VIEW_VARS'            => [
        
        // 站点名称
        'SITE_NAME'        => '我的网站',
        
        // 管理站点名称
        'SITE_ADMIN_NAME'  => '管理后台', //
        
        // 描述
        'SITE_DESCRIPTION' => '这是一个有点意思的网站',
        
        // 关键字
        'SITE_KEYWORDS'    => '网站、程序',
    ],
    
    // 生成静态文件目录（默认站点根）
    'VIEW_BUILD_DIR'       => './',

];