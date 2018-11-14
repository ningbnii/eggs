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
     * 模式1的正确性
     * @param $first
     * @param $second
     * @param $third
     * @param $real
     */
    public static function checkModel1($first,$second,$third,$real)
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
        $hasOne = self::get(['source_id'=>$real->id]);
        if(!$hasOne){
            $data = [
                'source_id'=>$real->id,
                'mode1'=>$flag ? 1 : 0
            ];
            (new self())->save($data);
        }

    }
}