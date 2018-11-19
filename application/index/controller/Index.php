<?php

namespace app\index\controller;

use app\index\model\Forecast;
use app\index\model\Mode;
use app\index\model\Source;

class Index
{
    public function index()
    {

        $list = Source::order('id desc')->limit(3)->select();
        $params['list'] = $list;
        $result = $this->test($list[0]['periods']);
        // dump($result['forecast']);exit;
        $flag = true;
        foreach ($result['forecast'] as $key => $value) {
            if ($value > 27 || $value < 3) {
                $flag = false;
            }
        }
        $params['forecast'] = '';
        $params['sum1'] = '';
        $params['sum2'] = '';
        if ($flag) {
            $params['forecast'] = $result['forecast'];
            $params['sum1'] = $result['sum1'];
            $params['sum2'] = $result['sum2'];
        }

        $echat = Source::all(function ($query) {
            $query->order('id desc')->limit(100);
        });
        $arr = [];
        foreach ($echat as $key => $value) {
            $arr[] = $value->sum;
        }
        $params['arr'] = implode(',', $arr);
        $yAxis = [];
        for ($i = 0; $i <= 27; $i++) {
            $yAxis[] = $i;
        }
        $xAxis = [];
        for ($i = 0; $i <= 100; $i++) {
            $xAxis[] = $i;
        }
        $params['xAxis'] = implode(',', $xAxis);
        $params['yAxis'] = implode(',', $yAxis);
        return view('index', $params);
    }

    private function test($periods)
    {
        $source1 = Source::get(['periods' => $periods]);
        $source2 = Source::get(['periods' => $periods - 1]);
        $source3 = Source::get(['periods' => $periods - 2]);
        $arr1 = [$source1->num1, $source1->num2, $source1->num3];
        $arr2 = [$source2->num1, $source2->num2, $source2->num3];
        $arr3 = [$source3->num1, $source3->num2, $source3->num3];
        $sum1 = $arr1[0] + $arr2[2] + $arr3[2];
        $sum2 = $arr1[2] + $arr2[2] + $arr3[0];
        // $sum1 = $arr1[1] + $arr2[1] + $arr3[1];
        // $sum2 = $arr2[0] + $arr2[1] + $arr2[2];
        $total = $sum1 + $sum2;
        // $total = $arr1[1] + $arr2[0] + $arr2[2] * 2 + $arr2[1] + $arr3[1];

        $arr = array_unique(array_merge($arr1, $arr2, $arr3));
        sort($arr);
        $comb = combination($arr, 2);
        $result = [];
        foreach ($comb as $v) {
            $sum = $v[0] + $v[1];
            $result[] = $total - $sum;
        }
        $forecast = array_unique($result);
        $max = max($forecast);
        $min = min($forecast);
        $forecast = [];
        for ($i = $min; $i <= $max; $i++) {
            $forecast[] = $i;
        }

        for ($i = -2; $i <= 2; $i++) {
            $sum = $total + $i;
            if ($sum > 0 && $sum < 27) {
                $forecast[] = $sum;
            }
        }

        $forecast = array_unique($forecast);
        sort($forecast);

        return ['forecast' => $forecast, 'sum1' => $sum1, 'sum2' => $sum2];
    }

    public function caiji($value = '')
    {
        $curl = Mode::getCurl();
        $html = $curl->response;

        // 采集规则
        $rules = [
            'title' => ['.xy2820131227_l', 'text'],
            'lid' => ['#panel>tr:nth-child(7) td:nth-child(6)', 'text'],
            'time' => ['#panel>tr:nth-child(7) td:nth-child(2)', 'text'],
        ];
        $data = \QL\QueryList::Query($html, $rules)->data;

        $title = trimall($data[0]['title']);

        $params = [];
        $params['periods'] = mb_substr($title, 8, 6);

        $params['num1'] = mb_substr($title, 20, 1);

        $params['num2'] = mb_substr($title, 22, 1);

        $params['num3'] = mb_substr($title, 24, 1);

        $params['sum'] = $params['num1'] + $params['num2'] + $params['num3'];
        $hasOne = Source::get(['periods' => $params['periods']]);
        if (!$hasOne) {
            $source = new Source($params);
            $source->save();
        }
        $fenxi = new Fenxi();
        $fenxi->test2();
        // 如果最近连错两个，停止投注
//        if(Forecast::getLastWrongTimes() == 2){
//            cache('switch',false);
//        }else{
//            // 模式4如果错误，停止投注
//            if(!Forecast::getLastStatus('mode4')){
//                cache('switch',true);
//                cache('times',0);
//            }
//        }


//        if(cache('times')>4){
//            cache('switch',false);
//        }else{
//            cache('switch',true);
//        }
//        cache('switch',false);
        // 自动开奖
        $lid = substr(trim($data[0]['lid']), 13, 7);
        if (cache('lid') != $lid && cache('switch')) {

//            if(!cache('times')){
//                cache('times',1);
//            }else{
//                $times = cache('times');
//                $times ++;
//                cache('times',$times);
//            }
            $modeFunc = 'mode' . cache('mode');
            if (method_exists(new Mode(), $modeFunc)) {
                $modeData = Mode::$modeFunc();
                $time = '2018-' . $data[0]['time'];
                $curl->setHeader('Referer', 'http://www.pceggs.com/play/pg28Insert.aspx?LID=' . $lid);
                $arr = [
                    'CTIME' => $time,
                    'ALLSMONEY' => $modeData['touzhu'],
                    'isdb_p' => '0',
                    'SMONEYSUM' => $modeData['sumMoney'],
                    'SMONEYY' => 'ALD',
                    'Cheat' => 'CAE',
                    '_' => time() * 1000,
                ];

                $result = $curl->get('http://www.pceggs.com/play/pg28Insert_ajax.ashx?LID=' . $lid . '&' . http_build_query($arr));
                if (json_decode($result->response)->status == 1) {
                    cache('lid', $lid);
                    cache('switch',false);
                }
            }
        }

    }

}
