<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */

/**
 * @namespace
 */
namespace eiu\components\image\Effect;

use eiu\components\image\AbstractEditObject;
use eiu\components\image\Color;

/**
 * Abstract effect class
 *
 * @category   Pop
 * @package    eiu\components\image
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
abstract class AbstractEffect extends AbstractEditObject implements EffectInterface
{

    /**
     * Get the blend between 2 colors
     *
     * @param  Color\ColorInterface $color1
     * @param  Color\ColorInterface $color2
     * @param  int                  $tween
     * @throws Exception
     * @return array
     */
    protected function getBlend(Color\ColorInterface $color1, Color\ColorInterface $color2, $tween)
    {
        if (!($color1 instanceof Color\Rgb)) {
            $color1 = $color1->toRgb();
        }
        if (!($color2 instanceof Color\Rgb)) {
            $color2 = $color2->toRgb();
        }

        $blend = ['r' => [], 'g' => [], 'b' => []];

        $r1 = $color1->getR();
        $g1 = $color1->getG();
        $b1 = $color1->getB();

        $r2 = $color2->getR();
        $g2 = $color2->getG();
        $b2 = $color2->getB();

        $rTotal = $r2 - $r1;
        $gTotal = $g2 - $g1;
        $bTotal = $b2 - $b1;

        for ($i = 0; $i <= $tween; $i++) {
            $blend['r'][] = round($this->calculateSteps($i, $r1, $rTotal, $tween));
            $blend['g'][] = round($this->calculateSteps($i, $g1, $gTotal, $tween));
            $blend['b'][] = round($this->calculateSteps($i, $b1, $bTotal, $tween));
        }

        return $blend;
    }

    /**
     * Calculate the steps between two points
     *
     * @param  int $curStep
     * @param  int $start
     * @param  int $end
     * @param  int $totalSteps
     * @return int
     */
    protected function calculateSteps($curStep, $start, $end, $totalSteps)
    {
        return ($end * ($curStep / $totalSteps)) + $start;
    }

}
