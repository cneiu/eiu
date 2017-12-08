<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\util;


use eiu\components\Component;


/**
 * 杂项组件
 *
 * @package eiu\core\service\event
 */
class UtilComponent extends Component
{
    /**
     * 计算两点之间距离
     *
     * @param double $lon1 原点经度
     * @param double $lat1 原点纬度
     * @param double $lon2 目标点经度
     * @param double $lat2 目标点纬度
     *
     * @return double 单位为公里的距离
     */
    public static function gps_calculateDistance($lon1, $lat1, $lon2, $lat2)
    {
        $theta = $lon1 - $lon2;
        $dist  = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist  = acos($dist);
        $dist  = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        
        return ($miles * 1.609344);
    }
    
    /**
     * 根据原点求目标点
     *
     * @param double $lon1    原点经度
     * @param double $lat1    原点纬度
     * @param        $linDistance
     * @param int    $bearing 方向角度（0-360）
     *
     * @return array 经度/纬度
     * @internal param float $distance 距离（公里）
     */
    public static function gps_calculateDerivedPosition($lon1, $lat1, $linDistance, $bearing)
    {
        $lon1 = deg2rad($lon1);
        $lat1 = deg2rad($lat1);
        
        $distance = $linDistance / 6371;
        $bearing  = deg2rad($bearing);
        
        $lat2 = asin(sin($lat1) * cos($distance) + cos($lat1) * sin($distance) * cos($bearing));
        
        $lat = asin(sin($lat1) * cos($linDistance / 6371) + cos($lat1) * sin($linDistance / 6371) * cos($bearing));
        $lon = $lon1 + atan2((sin($bearing) * sin($linDistance / 6371) * cos($lat1)), (cos($linDistance / 6371) - sin($lat1) * sin($lat2)));
        
        return [rad2deg($lon), rad2deg($lat)];
    }
    
    /**
     * 递归生成树结构
     *
     * @param array  $data         data array 数据
     * @param string $parentId     parent value 父节点ID
     * @param string $parent_name  parent index name 父级字段名
     * @param string $self_name    self index name 主键字段名
     * @param string $childrenName 子字段名
     * @param        $asName
     *
     * @return array
     */
    public static function tree(array $data, $parentId, $parent_name, $self_name, $childrenName = '_children', $asName = [])
    {
        $tree = [];
        
        foreach ($data as $index => $d)
        {
            if (!isset($data[$index][$parent_name]) || !isset($data[$index][$self_name]))
            {
                return [];
            }
            
            if ($asName)
            {
                foreach ($asName as $key => $field)
                {
                    $data[$index][$key] = $data[$index][$field];
                }
            }
            
            if ($data[$index][$parent_name] == $parentId)
            {
                $children = static::tree($data, $data[$index][$self_name], $parent_name, $self_name, $childrenName, $asName);
                // set a trivial key
                if (!empty($children))
                {
                    $data[$index][$childrenName] = $children;
                }
                
                $tree[] = $data[$index];
            }
        }
        
        return $tree;
    }
    
    /**
     * 生成 UUID V4
     *
     * @return string
     */
    public static function uuid()
    {
        $string    = \random_bytes(16);
        $string[6] = \chr(\ord($string[6]) & 0x0f | 0x40);
        $string[8] = \chr(\ord($string[8]) & 0x3f | 0x80);
        
        return \vsprintf('%s%s-%s-%s-%s-%s%s%s', \str_split(\bin2hex($string), 4));
    }
    
    /**
     * 时间差计算
     *
     * @param $date1
     * @param $date2
     *
     * @return float
     */
    public static function date_distance($date1, $date2)
    {
        $Date_List_a1 = explode("-", $date1);
        
        $Date_List_a2 = explode("-", $date2);
        
        $d1 = mktime(0, 0, 0, $Date_List_a1[1], $Date_List_a1[2], $Date_List_a1[0]);
        
        $d2 = mktime(0, 0, 0, $Date_List_a2[1], $Date_List_a2[2], $Date_List_a2[0]);
        
        return round(($d1 - $d2) / 3600 / 24);
    }
    
    /**
     * 随机BASE64加密
     *
     * 字符串混淆方式加密
     *
     * @param string
     *
     * @return string
     */
    public static function randomBase64Encode($Param)
    {
        if (empty($Param))
        {
            return null;
        }
        
        if (is_numeric($Param))
        {
            $Param = '_KEY_' . $Param;
        }
        
        $rand_char = [
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u',
            'v', 'w', 'x', 'y', 'z', 0, 1, 2, 3, 4, 5, 6, 7, 8, 9,
        ];
        
        $Param_temp = null;
        
        for ($i = 0; $i < strlen($Param); $i++)
        {
            $Param_temp .= $Param[$i] . $rand_char[array_rand($rand_char)];
        }
        
        return base64_encode($Param_temp);
    }
    
    /**
     * 随机BASE64解密
     *
     * encode加密的逆转
     *
     * @param string
     *
     * @return string
     */
    public static function randomBase64Decode($Param)
    {
        if (empty($Param))
        {
            return null;
        }
        
        $Param = base64_decode($Param);
        
        $Param_temp = null;
        
        for ($i = 0; $i < strlen($Param); $i++)
        {
            $Param_temp .= $i % 2 ? '' : $Param[$i];
        }
        
        $Param_temp = str_replace('_KEY_', '', $Param_temp);
        
        return $Param_temp;
    }
    
    /**
     * 异或混淆加解密
     *
     * @param string $string 需要加解密的字符串
     * @param string $key    密钥
     *
     * @return mixed
     */
    public static function xorStr($string, $key)
    {
        $result = "";
        $j      = 0;
        
        for ($i = 0; $i < strlen($string); $i++)
        {
            $a      = self::_getCharcode($string, $i);
            $b      = $a ^ self::_getCharcode($key, $j);
            $result .= self::_fromCharCode($b);
            
            $j++;
            
            if ($j == strlen($key))
            {
                $j = 0;
            }
        }
        
        return $result;
    }
    
    /**
     * PHP replacement for JavaScript charCodeAt.
     *
     * @access private
     *
     * @param mixed $str
     * @param mixed $i
     *
     * @return string
     */
    private static function _getCharcode($str, $i)
    {
        return self::_uniord(substr($str, $i, 1));
    }
    
    /**
     * Multi byte ord function.
     *
     * @access private
     *
     * @param mixed $c
     *
     * @return mixed
     */
    private static function _uniord($c)
    {
        $h = ord($c{0});
        if ($h <= 0x7F)
        {
            return $h;
        }
        else if ($h < 0xC2)
        {
            return false;
        }
        else if ($h <= 0xDF)
        {
            return ($h & 0x1F) << 6 | (ord($c{1}) & 0x3F);
        }
        else if ($h <= 0xEF)
        {
            return ($h & 0x0F) << 12 | (ord($c{1}) & 0x3F) << 6 | (ord($c{2}) & 0x3F);
        }
        else if ($h <= 0xF4)
        {
            return ($h & 0x0F) << 18 | (ord($c{1}) & 0x3F) << 12 | (ord($c{2}) & 0x3F) << 6 | (ord($c{3}) & 0x3F);
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Gets character from code.
     *
     * @access private
     * @return string
     */
    private static function _fromCharCode()
    {
        $output = '';
        $chars  = func_get_args();
        foreach ($chars as $char)
        {
            $output .= chr((int)$char);
        }
        
        return $output;
    }
}