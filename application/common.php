<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function trimall($str){
    $qian=array(" ","　","\t","\n","\r");
    return str_replace($qian, '', $str);
}

// 组合
function combination($a, $m) {
    $r = array();

    $n = count($a);
    if ($m <= 0 || $m > $n) {
        return $r;
    }

    for ($i=0; $i<$n; $i++) {
        $t = array($a[$i]);
        if ($m == 1) {
            $r[] = $t;
        } else {
            $b = array_slice($a, $i+1);
            $c = combination($b, $m-1);
            foreach ($c as $v) {
                $r[] = array_merge($t, $v);
            }
        }
    }

    return $r;
}

function ch2arr($str)
{
    $length = mb_strlen($str, 'utf-8');
    $array = [];
    for ($i=0; $i<$length; $i++)
        $array[] = mb_substr($str, $i, 1, 'utf-8');
    return $array;
}

/**
 * @Notes: 获取尾数
 * @Author: chenning[296720094@qq.com]
 * @Date: 2018/11/14
 * @Time: 17:00
 * @param $num
 * @return mixed
 */
function getMantissa($num){
    return array_reverse(str_split($num))[0];
}

/**
 * @Notes: 获取组合类型
 * @Author: chenning[296720094@qq.com]
 * @Date: 2018/11/14
 * @Time: 17:05
 * @param $result
 * @return string
 */
function getForecastType($result)
{
    $forecast = '';
    if ($result < 14) {
        $forecast .= '小';
    } else {
        $forecast .= '大';
    }
    if ($result % 2 == 0) {
        $forecast .= '双';
    } else {
        $forecast .= '单';
    }
    return $forecast;
}