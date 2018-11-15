<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/14
 * Time: 19:40
 */

namespace app\index\model;


use think\Model;

class Forecast extends Model
{
    /**
     * @Notes: 检查各种模式的正确性
     * @Author: chenning[296720094@qq.com]
     * @Date: 2018/11/15
     * @Time: 14:35
     * @param $first
     * @param $second
     * @param $third
     * @param $real
     */
    public static function checkMode($first,$second,$third,$real)
    {
        $hasOne = self::get(['source_id'=>$real->id]);
        $data = [];
        if(!$hasOne){
            $data = [
                'source_id'=>$real->id,
                'mode1'=>self::checkMode1($first,$second,$third,$real),
                'mode2'=>self::checkMode2($first,$second,$third,$real)
            ];
        }
        return $data;
    }
    /**
     * 模式1的正确性
     * @param $first
     * @param $second
     * @param $third
     * @param $real
     */
    public static function checkMode1($first,$second,$third,$real)
    {
        // 预测的组合
        $forecast = ch2arr(Mode::getMode1Type($first,$second,$third));
        // 判断实际值是不是包含在组合中
        $shiji = ch2arr(getForecastType($real->sum));
        $flag = false;
        foreach ($shiji as $v){
            if(in_array($v,$forecast)){
                $flag = true;
            }
        }
        return $flag ? 1 : 0;
    }

    /**
     * @Notes: 模式2的正确性
     * @Author: chenning[296720094@qq.com]
     * @Date: 2018/11/15
     * @Time: 14:29
     * @param $first
     * @param $second
     * @param $third
     * @param $real
     */
    public static function checkMode2($first,$second,$third,$real)
    {
        $forecast = ch2arr(Mode::getMode2Type($first,$second,$third));
        // 判断实际值是不是包含在组合中
        $shiji = ch2arr(getForecastType($real->sum));
        $flag = false;
        foreach ($shiji as $v){
            if(in_array($v,$forecast)){
                $flag = true;
            }
        }
        return $flag ? 1 : 0;
    }
}