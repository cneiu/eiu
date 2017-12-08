<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\captcha;

/**
 * 验证码接口
 *
 * Interface ICaptchaBuilderInterface
 *
 * @package eiu\components\captcha
 */
interface ICaptchaBuilderInterface
{
    /**
     * 创建验证图片
     *
     * @return mixed
     */
    public function create();
    
    /**
     * 将验证码图片保存到指定路径
     *
     * @param string $filename 物理路径
     * @param int    $quality  清晰度
     *
     * @return mixed
     */
    public function save($filename, $quality);
    
    /**
     * 获取验证码图片
     *
     * @param int $quality 清晰度
     *
     * @return mixed
     */
    public function output($quality);
    
    /**
     * 获取验证码内容
     *
     * @return mixed
     */
    public function getText();
}