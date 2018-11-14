<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/14
 * Time: 14:49
 */

namespace app\index\model;


use Curl\Curl;

class Mode
{
    public static $curl;

    public function __construct()
    {
        self::$curl = self::getCurl();
    }

    /**
     * 获取模式1的判断组合类型
     */
    public static function getMode1Type($first,$second,$third)
    {

        $firstNum = $first->num3 + $second->num3;
        $secondNum = $second->num2 + $third->num2;
        $thirdNum = $third->num3;
        // 尾数求和
        $sum = getMantissa($firstNum) + getMantissa($secondNum) + getMantissa($thirdNum);
        // 判断组合类型
        $type = getForecastType($sum);
        return $type;
    }
    /**
     * @Notes: 模式1
     * @Author: chenning[296720094@qq.com]
     * @Date: 2018/11/14
     * @Time: 14:49
     */
    public static function mode1()
    {
        $list = Source::getLastThreeRecord();
        $type = self::getMode1Type($list[0],$list[1],$list[2]);
        // 获取组合投注模式
        $touzhu = self::getCombination($type);
        // 计算总的投注金额
        $sumMoney = self::getSumMoney($touzhu);
        return [
            'touzhu' => $touzhu,
            'sumMoney' => $sumMoney
        ];
    }

    /**
     * @Notes: 模式2
     * @Author: chenning[296720094@qq.com]
     * @Date: 2018/11/14
     * @Time: 15:09
     */
    public static function mode2()
    {

    }

    /**
     * @Notes: 获取组合投注模式
     * @Author: chenning[296720094@qq.com]
     * @Date: 2018/11/14
     * @Time: 17:13
     * @param $type
     */
    public static function getCombination($type)
    {
        $modeArr = [
            '大单' => 1,
            '小单' => 11,
            '大双' => 13,
            '小双' => 12,
        ];
        $modeUrl = 'http://www.pceggs.com/play/pg28mode.aspx?mode=' . $modeArr[$type];
        $modeData = self::$curl->get($modeUrl);
        $touzhu = $modeData->response;
        return $touzhu;
    }

    /**
     * @Notes: 获取总的投注金额
     * @Author: chenning[296720094@qq.com]
     * @Date: 2018/11/14
     * @Time: 17:40
     * @param $touzhu
     */
    public static function getSumMoney($touzhu)
    {
        $touzhuArr = explode(',',$touzhu);
        $sumMoney = 0;
        foreach ($touzhuArr as &$v){
            if($v){
                $sumMoney += $v;
            }
        }
        return $sumMoney;
    }

    /**
     * @Notes: 获取curl
     * @Author: chenning[296720094@qq.com]
     * @Date: 2018/11/14
     * @Time: 17:15
     */
    public static function getCurl()
    {
        $cookie = cache('cookie');
        if (!$cookie) {
            $cookie = 'CC9C4B54DAAAB691D18791F050C95DFD8EEF2A2FB67ECFA2C56CE75BECF08402D4583CB5469533EB2E186A0C85000CA1A7810BE50F6B5906E7C2A577B47D12B017B4C048427891AF2C6BAFEE376344C3F56740F077115FDD10053AE3DE23FB20FBA342A1C20D6E60DAD736B0266B32D72088B51BD4F9059CC1CA46EA01B1489FFA64F7D9';
            cache('cookie', $cookie);
        }

        $curl = new Curl();
        $curl->setCookie('.ADWASPX7A5C561934E_PCEGGS', $cookie);
        $curl->get('http://www.pceggs.com/play/pxya.aspx');
        $responseHeaders = $curl->response_headers;

        foreach ($responseHeaders as $key => $value) {
            if (strpos($value, 'Set-Cookie') !== false) {
                $str = trim(substr($value, 11));
                $arr = explode(';', $str);
                foreach ($arr as $key => $v) {
                    $temp = explode('=', trim($v));
                    if ($temp[0] == '.ADWASPX7A5C561934E_PCEGGS') {
                        cache('cookie', $temp[1]);
                    }
                }
            }
        }

        return $curl;
    }
}