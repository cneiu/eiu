<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\auth;

interface IVerifier
{
    /**
     * 创建令牌
     *
     * @param string $key
     * @param int    $exceed
     *
     * @return string
     */
    public function createToken(string $key, int $exceed = 0);
    
    /**
     * 获取令牌
     *
     * @return string
     */
    public function getToken();
}
