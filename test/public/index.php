<?php
/**
 * 项目入口文件
 *
 * 定义运行环境并启动框架
 *
 * @author        BOBBY
 * @link          https://cneiu.com/eiu/
 */


/*
 *---------------------------------------------------------------
 * 应用开始
 *---------------------------------------------------------------
 */
define('EIU_START', microtime(true));


/*
 *---------------------------------------------------------------
 * 项目目录
 *---------------------------------------------------------------
 *
 * 设置项目所在的目录
 */
defined('APP_PATH') or define('APP_PATH', realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);


/*
 *---------------------------------------------------------------
 * 其他常量定义
 *---------------------------------------------------------------
 *
 * STDIN CLI     命令行模式
 * DS            目录分隔符
 * APP_ENTRY     入口文件
 * APP_DATA      数据目录（可写）
 * APP_CACHE     缓存目录（可写）
 * VIEW_PATH     模板目录
 * MEDIA_PATH    媒体文件目录(JPG、PDF、xls...)
 * STATIC_URL    静态文件访问路径(JS、CSS、IMAGE...)
 * MEDIA_URL     媒体文件访问路径(JS、CSS、IMAGE...)
 */
defined('STDIN') and chdir(dirname(__FILE__));
define('DS', DIRECTORY_SEPARATOR);

// 路径相关
define('APP_ENTRY', __FILE__);
define('APP_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/');
define('APP_MODULE', APP_PATH . 'modules' . DS);
define('APP_DATA', APP_PATH . 'data' . DS);
define('APP_CACHE', APP_DATA . 'cache' . DS);
define('VIEW_PATH', APP_PATH . 'templates' . DS);
define('MEDIA_PATH', APP_PATH . 'public' . DS . 'media' . DS);
define('PUBLIC_PATH', APP_PATH . 'public' . DS);
define('STATIC_URL', APP_URL . 'static/');
define('MEDIA_URL', APP_URL . 'media/');

// 文件权限相关
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

// 文件读写相关
define('FOPEN_READ', 'rb');
define('FOPEN_READ_WRITE', 'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb');
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b');
define('FOPEN_WRITE_CREATE', 'ab');
define('FOPEN_READ_WRITE_CREATE', 'a+b');
define('FOPEN_WRITE_CREATE_STRICT', 'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');


/*
 *---------------------------------------------------------------
 * 自动加载
 *---------------------------------------------------------------
 */
file_exists("../../vendor/autoload.php") or exit("Please run \"composer init\"");

require_once "../../vendor/autoload.php";


/*
 *---------------------------------------------------------------
 * 创建应用程序
 *---------------------------------------------------------------
 */
$app = new \eiu\core\application\Application();


/*
 *---------------------------------------------------------------
 * 绑定核心处理器
 *---------------------------------------------------------------
 */
$app->bind(\eiu\core\application\IKernel::class, \eiu\core\application\HttpKernel::class, true);


/*
 *---------------------------------------------------------------
 * 运行应用程序
 *---------------------------------------------------------------
 */
$app->make(eiu\core\application\IKernel::class)->handle();