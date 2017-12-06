<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\abstracts\controller;


use eiu\abstracts\Module;
use eiu\core\application\Application;
use eiu\core\service\view\ViewProvider;


abstract class Controller extends Module implements IController
{
    /**
     * @var ViewProvider
     */
    private $view;
    
    /**
     * constructor
     *
     * @param Application  $app
     * @param ViewProvider $view
     */
    public function __construct(Application $app, ViewProvider $view)
    {
        parent::__construct($app);
        
        $this->view = $view;
    }
    
    /**
     * 输出视图
     *
     * 输出一个视图模板
     *
     * @param string $page   模板文件
     * @param array  $vars   模板变量
     * @param bool   $return 是否返回视图, 返回后不会输出
     *
     * @return string
     */
    protected function view(string $page, array $vars = [], bool $return = false)
    {
        foreach ($vars as $k => $v)
        {
            $this->view->$k = $v;
        }
        
        return $this->view->display($page, $return);
    }
    
    /**
     * 输出文本
     *
     * 输出一个文本消息
     *
     * @param string $text        文本内容
     * @param int    $status_code 状态代码
     * @param string $header_type 输出头类型
     * @param string $charset     字符集
     *
     * @return string
     */
    protected function text(string $text, int $status_code = 200, string $header_type = 'html', string $charset = null)
    {
        return $this->view->text($text, $status_code, $header_type, $charset);
    }
    
    /**
     * 输出 JSON 字符串
     *
     * @param mixed  $object      输出对象
     * @param int    $status_code 状态代码
     * @param string $header_type 输出头类型
     * @param string $charset     字符集
     *
     * @return string
     */
    protected function json($object, int $status_code = 200, string $header_type = 'json', string $charset = null)
    {
        return $this->text(json_encode($object), $status_code, $header_type, $charset);
    }
    
    /**
     * 输出 SUCCESS 字符串
     *
     * 如果附加消息为空则自动使用 $this->get_message() 的内容填充
     *
     * @param bool   $success     是否成功
     * @param mixed  $message     附加消息
     * @param int    $status_code 状态代码
     * @param string $header_type 输出头类型
     * @param string $charset     字符集
     *
     * @return string
     */
    protected function success(bool $success = true, $message = null, int $status_code = 200, string $header_type = 'json', string $charset = null)
    {
        if (is_null($message))
        {
            $message = $this->getMessage();
        }
        
        return $this->json(['success' => $success, 'message' => $message], $status_code, $header_type, $charset);
    }
    
    /**
     * 输出一个 404 消息
     *
     * @param string $message
     *
     * @return string
     */
    protected function notFound(string $message = 'Not Found!')
    {
        return $this->json($message, 404);
    }
}