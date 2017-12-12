<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\auth;

interface IAuthAdapter
{
    /**
     * 创建令牌
     *
     * @param array $data
     * @param int   $expiration
     *
     * @param
     *
     * @return string
     */
    public function create(array $data = [], int $expiration = 3600, $clientIp = null);
    
    /**
     * 刷新令牌
     *
     * @return string
     */
    public function refresh();
    
    /**
     * 验证令牌
     *
     * @return bool
     */
    public function verify();
    
    /**
     * 删除令牌
     *
     * @return mixed
     */
    public function clear();
    
    /**
     * 获取令牌数据
     *
     * @return array
     */
    public function data();
}