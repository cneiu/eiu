<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\jwt\Claim;


use eiu\components\jwt\Claim;
use eiu\components\jwt\ValidationData;


/**
 * Validatable claim that checks if value is lesser or equals to the given data
 */
class LesserOrEqualsTo extends Basic implements Claim, Validatable
{
    /**
     * {@inheritdoc}
     */
    public function validate(ValidationData $data)
    {
        if ($data->has($this->getName()))
        {
            return $this->getValue() <= $data->get($this->getName());
        }
        
        return true;
    }
}
