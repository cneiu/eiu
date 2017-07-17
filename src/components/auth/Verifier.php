<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\auth;


use eiu\core\application\Application;


abstract class Verifier implements IVerifier
{
    private $app;
    
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}