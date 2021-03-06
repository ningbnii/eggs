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
                'mode1'=>self::getForecastType($first,$second,$third,$real,1),
                'mode2'=>self::getForecastType($first,$second,$third,$real,2),
                'mode3'=>self::getForecastType($first,$second,$third,$real,3),
                'mode4'=>self::getForecastType4($first,$real),
            ];
        }
        return $data;
    }
    /**
     * 模式的正确性
     * @param $first
     * @param $second
     * @param $third
     * @param $real
     */
    public static function getForecastType($first,$second,$third,$real,$mode)
    {
        switch ($mode){
            case 1:
                $forecast = ch2arr(Mode::getMode1Type($first,$second,$third));
                break;
            case 2:
                $forecast = ch2arr(Mode::getMode2Type($first,$second,$third));
                break;
            case 3:
                $forecast = ch2arr(Mode::getMode3Type($first,$second,$third));
                break;
        }
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
     * @Notes: 杀5余正确性
     * @Author: chenning[296720094@qq.com]
     * @Date: 2018/11/16
     * @Time: 13:07
     */
    public static function getForecastType4($first,$real)
    {
        $remainder = $first->sum % 4;
        $shiji = $real->sum % 5;
        return $remainder == $shiji ? 0 : 1;
    }

    /**
     * @Notes: 上一期模式的正确性
     * @Author: chenning[296720094@qq.com]
     * @Date: 2018/11/16
     * @Time: 15:45
     */
    public static function getLastStatus($mode)
    {
        $data = self::order('id desc')->find();
        return $data->$mode;
    }

    public static function getLastWrongTimes()
    {
        $list = self::order('id desc')->limit(2)->select();
        $wrong = 0;
        foreach ($list as $v){
            if($v->mode4 == 0){
                $wrong ++;
            }
        }
        return $wrong;
    }
}