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
 * 数据库配置
 * --------------------------------------------------------------------
 */

return [
    
    // 数据库驱动
    'DRIVER'       => 'MYSQL',
    
    // MYSQL 驱动
    'MYSQL_DRIVER' => [
        
        // 数据库字符集
        'CHARSET'     => 'utf8',
        
        // 使用持久连接
        'PCONNECT'    => false,
        
        // 使用分布式数据库模式
        'DEPLOY'      => false,
        
        // 使用读写分离模式
        'RW_SEPARATE' => false,
        
        // 数据库连接池
        // 单数据库模式将使用数据库连接池中的第一个数据库连接作为主连接
        // 分布式数据库模式将使用数据库连接池中的第一个数据库连接作为写入服务器
        'SERVERS'     => [
            [
                'HOST' => (PHP_OS == 'Linux') ? '127.0.0.1' : '127.0.0.1',
                'PORT' => '3306',
                'NAME' => (PHP_OS == 'Linux') ? 'power_assistant' : 'power_assistant',
                'USER' => (PHP_OS == 'Linux') ? 'root' : 'root',
                'PASS' => (PHP_OS == 'Linux') ? 'jushanyuan888' : '',
            ],
        ],
    ],
];