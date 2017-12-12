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
     * @param 
     * @param array  $data
     * @param int    $expiration
     *
     * @return string
     */
    public function createToken(array $data = [], int $expiration = 3600, $clientIp = null);
    
    /**
     * 验证令牌
     *
     * @return bool
     */
    public function verifyToken();
    
    /**
     * 删除令牌
     *
     * @return mixed
     */
    public function clearToken();
    
    /**
     * 获取令牌数据
     *
     * @return array
     */
    public function getData();
}