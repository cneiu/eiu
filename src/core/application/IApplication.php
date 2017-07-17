<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\application;

interface IApplication extends IContainer
{
    /**
     * 获取版本
     *
     * @return string
     */
    public function version();
    
    /**
     * 注册一个服务
     *
     * @param  \eiu\core\service\Provider|string $provider
     * @param  array                             $options
     * @param  bool                              $force
     *
     * @return \eiu\core\service\Provider
     */
    public function register($provider, $options = [], $force = false);
}
