<?php

namespace app\index\model;

/**
 * Created by PhpStorm.
 * User: ning
 * Date: 2017/9/8
 * Time: 11:55
 */
use think\Model;

class Source extends Model
{
    protected function getDanshuangAttr($value, $data)
    {
        if ($data['sum'] % 2 == 0) {
            return '双';
        } else {
            return '单';
        }
    }

    protected function getDaxiaoAttr($value, $data)
    {
        if ($data['sum'] <= 13) {
            return '小';
        } else {
            return '大';
        }
    }

    protected function getZhongbianAttr($value, $data)
    {
        if ($data['sum'] <= 17 && $data['sum'] >= 10) {
            return '中';
        } else {
            return '边';
        }
    }

    /**
     * 获取预测
     * @param $first
     * @param $second
     * @param $third
     * @return string
     */
    public static function getForecast($first,$second,$third)
    {
        $one = $first->num1 + $second->num2 + $third->num1;
        $two = $first->num3 + $second->num2 + $third->num3;
        $three = $second->sum;
        $result = array_reverse(str_split($one))[0] + array_reverse(str_split($two))[0] + array_reverse(str_split($three))[0];
        return self::getForecastType($result);
    }

    /**
     *
     */
    public static function getForecastType($result)
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

    /**
     * @Notes: 获取最新的三条记录
     * @Author: chenning[296720094@qq.com]
     * @Date: 2018/11/14
     * @Time: 16:54
     */
    public static function getLastThreeRecord()
    {
        $list = Source::order('id desc')->limit(3)->select();
        return $list;
    }
}