<?php

namespace app\index\controller;

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

        // 自动开奖
        $lid = substr(trim($data[0]['lid']), 13, 7);
        if (cache('lid') != $lid && cache('switch')) {

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

    /**
     * 杀9尾模式
     */
    private function mode1($data, $curl, $lid)
    {
        $time = '2018-' . $data[0]['time'];
        $modeArr = [14, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $lastData = Source::order('id desc')->find();
        $lastSeondData = Source::get($lastData->id - 1);
        $lastDataWei = array_reverse(str_split($lastData->sum))[0];
        $lastSeondDataWei = array_reverse(str_split($lastSeondData->sum))[0];
        if ($lastDataWei + $lastSeondDataWei == 9) {
            cache('right', null);
        }
        $shawei = 9 - array_reverse(str_split($lastData->sum))[0];
        $modeUrl = 'http://www.pceggs.com/play/pg28mode.aspx?mode=' . $modeArr[$shawei];
        $modeData = $curl->get($modeUrl);
        $touzhu = $modeData->response;
        $touzhuArr = explode(',', $touzhu);
        $sumMoney = 0;
        $right = cache('right') ? cache('right') : 0;
        $rate = 1;
        if ($right) {
            $rate = pow(2, $right);
        }
        foreach ($touzhuArr as &$v) {
            if ($v) {
                if ($rate) {
                    $v = intval($v / $rate);
                }
                $sumMoney += $v;
            }
        }
        $touzhu = implode(',', $touzhuArr);
        $curl->setHeader('Referer', 'http://www.pceggs.com/play/pg28Insert.aspx?LID=' . $lid);
        $arr = [
            'CTIME' => $time,
            'ALLSMONEY' => $touzhu,
            'isdb_p' => '0',
            'SMONEYSUM' => $sumMoney,
            'SMONEYY' => 'ALD',
            'Cheat' => 'CAE',
            '_' => time() * 1000,
        ];

        $result = $curl->get('http://www.pceggs.com/play/pg28Insert_ajax.ashx?LID=' . $lid . '&' . http_build_query($arr));
        if (json_decode($result->response)->status == 1) {
            cache('lid', $lid);
            if (!cache('right')) {
                $right = 1;
                cache('right', $right);
            } else {
                $right = cache('right');
                $right++;
                if ($right > 4) {
                    $right = 1;
                }
            }
            cache('right', $right);
        }
    }

    /**
     * 算法模式2
     * @param $data
     * @param $curl
     */
    private function mode2($data, $curl, $lid)
    {
        $time = '2018-' . $data[0]['time'];
        $modeArr = [
            '大单' => 1,
            '小单' => 11,
            '大双' => 13,
            '小双' => 12,
        ];

        $first = Source::order('id desc')->find();
        $second = Source::get($first->id - 1);
        $third = Source::get($first->id - 2);
        $four = Source::get($first->id - 3);
        $lastForecast = Source::getForecast($second, $third, $four);
        // 上一次预测是不是正确，不正确再下注
        $shiji = ch2arr(Source::getForecastType($first->sum));
        $lastForecast = ch2arr($lastForecast);

        $flag = false;
        foreach ($shiji as $v) {
            if (in_array($v, $lastForecast)) {
                $flag = true;
            }
        }

        if (!$flag) {
            $forecast = Source::getForecast($first, $second, $third);
            $modeUrl = 'http://www.pceggs.com/play/pg28mode.aspx?mode=' . $modeArr[$forecast];
            $modeData = $curl->get($modeUrl);
            $touzhu = $modeData->response;
            $touzhuArr = explode(',', $touzhu);
            $sumMoney = 0;
            foreach ($touzhuArr as &$v) {
                if ($v) {
                    $sumMoney += $v;
                }
            }
            $touzhu = implode(',', $touzhuArr);
            $curl->setHeader('Referer', 'http://www.pceggs.com/play/pg28Insert.aspx?LID=' . $lid);
            $arr = [
                'CTIME' => $time,
                'ALLSMONEY' => $touzhu,
                'isdb_p' => '0',
                'SMONEYSUM' => $sumMoney,
                'SMONEYY' => 'ALD',
                'Cheat' => 'CAE',
                '_' => time() * 1000,
            ];

            $result = $curl->get('http://www.pceggs.com/play/pg28Insert_ajax.ashx?LID=' . $lid . '&' . http_build_query($arr));
            if (json_decode($result->response)->status == 1) {
                cache('lid', $lid);
            }
        }

    }

    /**
     * 算法模式3 连投3个，暂停，等错了，再开始投
     * @param $data
     * @param $curl
     */
    private function mode3($data, $curl, $lid)
    {
        cache('mode', 3);
        $time = '2018-' . $data[0]['time'];
        $modeArr = [
            '大单' => 1,
            '小单' => 11,
            '大双' => 13,
            '小双' => 12,
        ];

        $first = Source::order('id desc')->find();
        $second = Source::get($first->id - 1);
        $third = Source::get($first->id - 2);
        $four = Source::get($first->id - 3);
        $lastForecast = Source::getForecast($second, $third, $four);
        // 上一次预测是不是正确，不正确再下注
        $shiji = ch2arr(Source::getForecastType($first->sum));
        $lastForecast = ch2arr($lastForecast);

        // 上次没猜中
        $flag = false;
        foreach ($shiji as $v) {
            if (in_array($v, $lastForecast)) {
                $flag = true; // 上次猜中了
            }
        }
        $times = cache('times') ? cache('times') : 0;

        if (!$flag || $times < 30) {

            $forecast = Source::getForecast($first, $second, $third);
            $modeUrl = 'http://www.pceggs.com/play/pg28mode.aspx?mode=' . $modeArr[$forecast];
            $modeData = $curl->get($modeUrl);
            $touzhu = $modeData->response;
            $touzhuArr = explode(',', $touzhu);
            $sumMoney = 0;
            foreach ($touzhuArr as &$v) {
                if ($v) {
                    $sumMoney += $v;
                }
            }
            $touzhu = implode(',', $touzhuArr);
            $curl->setHeader('Referer', 'http://www.pceggs.com/play/pg28Insert.aspx?LID=' . $lid);
            $arr = [
                'CTIME' => $time,
                'ALLSMONEY' => $touzhu,
                'isdb_p' => '0',
                'SMONEYSUM' => $sumMoney,
                'SMONEYY' => 'ALD',
                'Cheat' => 'CAE',
                '_' => time() * 1000,
            ];

            $result = $curl->get('http://www.pceggs.com/play/pg28Insert_ajax.ashx?LID=' . $lid . '&' . http_build_query($arr));

            if (json_decode($result->response)->status == 1) {
                cache('lid', $lid);

                $times++;
                cache('times', $times);
            }
        }
        if (!$flag) {
            cache('times', 0);
        }

    }

    /**
     * 算法模式3 连投3个，暂停，等错了，再开始投
     * @param $data
     * @param $curl
     */
    private function mode4($data, $curl, $lid)
    {
        cache('mode', 4);
        $time = '2018-' . $data[0]['time'];
        $modeArr = [
            '大单' => 1,
            '小单' => 11,
            '大双' => 13,
            '小双' => 12,
        ];

        $first = Source::order('id desc')->find();
        $second = Source::get($first->id - 1);
        $third = Source::get($first->id - 2);
        $four = Source::get($first->id - 3);
        $lastForecast = $second->num1 * 100 + $third->num1 * 10 + $four->num1 + $second->num3 * 10 + $third->num2 - $four->num2;
        $lastForecast = str_split($lastForecast);
        $lastTotal = 0;
        foreach ($lastForecast as $v) {
            $lastTotal += $v;
        }
        $lastForecast = Source::getForecastType($lastTotal);
        if ($lastForecast == '大单') {
            $lastForecast = '小双';
        }
        if ($lastForecast == '小单') {
            $lastForecast = '大双';
        }
        if ($lastForecast == '大双') {
            $lastForecast = '小单';
        }
        if ($lastForecast == '小双') {
            $lastForecast = '大单';
        }
        // 上一次预测是不是正确，不正确再下注
        $shiji = ch2arr(Source::getForecastType($first->sum));
        $lastForecast = ch2arr($lastForecast);

        // 上次没猜中
        $flag = false;
        foreach ($shiji as $v) {
            if (in_array($v, $lastForecast)) {
                $flag = true; // 上次猜中了
            }
        }
        $times = cache('times') ? cache('times') : 0;

        if ($flag && $times < 3) {
            $forecast = $first->num1 * 100 + $second->num1 * 10 + $third->num1 + $first->num3 * 10 + $second->num2 - $third->num2;
            $forecast = str_split($forecast);
            $lastTotal = 0;
            foreach ($forecast as $v) {
                $lastTotal += $v;
            }
            $forecast = Source::getForecastType($lastTotal);
            if ($forecast == '大单') {
                $forecast = '小双';
            }
            if ($forecast == '小单') {
                $forecast = '大双';
            }
            if ($forecast == '大双') {
                $forecast = '小单';
            }
            if ($forecast == '小双') {
                $forecast = '大单';
            }
            $modeUrl = 'http://www.pceggs.com/play/pg28mode.aspx?mode=' . $modeArr[$forecast];
            $modeData = $curl->get($modeUrl);
            $touzhu = $modeData->response;
            $touzhuArr = explode(',', $touzhu);
            $sumMoney = 0;
            foreach ($touzhuArr as &$v) {
                if ($v) {
                    $sumMoney += $v;
                }
            }
            $touzhu = implode(',', $touzhuArr);
            $curl->setHeader('Referer', 'http://www.pceggs.com/play/pg28Insert.aspx?LID=' . $lid);
            $arr = [
                'CTIME' => $time,
                'ALLSMONEY' => $touzhu,
                'isdb_p' => '0',
                'SMONEYSUM' => $sumMoney,
                'SMONEYY' => 'ALD',
                'Cheat' => 'CAE',
                '_' => time() * 1000,
            ];

            $result = $curl->get('http://www.pceggs.com/play/pg28Insert_ajax.ashx?LID=' . $lid . '&' . http_build_query($arr));
            if (json_decode($result->response)->status == 1) {
                cache('lid', $lid);

                $times++;
                cache('times', $times);
            }
        }
        if (!$flag) {
            cache('mode', 3);
            cache('times', 0);
        }

    }

}
