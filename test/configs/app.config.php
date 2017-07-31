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
    
    /*
     * --------------------------------------------------------------------
     * 基础配置
     * --------------------------------------------------------------------
     */
    // 运行模式(debug|production)
    'RUN_MODE'           => 'debug',
    
    // 字符集
    'CHARSET'            => 'UTF-8',
    
    // 时区
    'TIMEZONE'           => 'Asia/Shanghai',
    
    // 本位币
    'MONETARY'           => 'zh_CN',
    
    // 算法密钥(必须32位, 生产环境下请谨慎更改!)
    'KEY'                => 'cd1b7c1ee49e2c9e90502016b07223bb',
    
    // 是否对输出页面进行压缩
    'OUTPUT_COMPRESS'    => false,
    
    
    /*
     * --------------------------------------------------------------------
     * 错误日志配置
     * --------------------------------------------------------------------
     */
    // EMERGENCY = 0;	紧急
    // ALERT     = 1;	警报
    // CRITICAL  = 2;	重要
    // ERROR     = 3;	错误
    // WARNING   = 4;	警告
    // NOTICE    = 5;	注意
    // INFO      = 6;	信息
    // DEBUG     = 7;	调试
    
    // 日志记录阈值
    'LOG_THRESHOLD'      => [0, 1, 2, 3, 4, 5, 6, 7],
    
    // 日志存储路径
    'LOG_PATH'           => APP_DATA . 'logs',
    
    // 日志扩展名
    'LOG_FILE_EXTENSION' => '.log',
    
    // 日志日期格式
    'LOG_DATE_FORMAT'    => 'Y-m-d H:i:s',
    
    // 异常模板路径
    'ERROR_VIEWS_PATH'   => VIEW_PATH . 'error',
    
    
    /*
     * --------------------------------------------------------------------
     * COOKIE 相关配置
     * --------------------------------------------------------------------
     */
    // 过期时间
    'COOKIE_EXPIRE'      => time() + 3600,
    
    // 域
    'COOKIE_DOMAIN'      => '',
    
    // 路径
    'COOKIE_PATH'        => '/',
    
    // 是否 HTTPS 方式
    'COOKIE_SECURE'      => false,
    
    // 只运行 HTTP HTTPS 方式,不允许 JS 方式
    'COOKIE_HTTP_ONLY'   => false,
];