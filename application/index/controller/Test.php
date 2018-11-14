<?php
/**
 * Created by PhpStorm.
 * User: ning
 * Date: 2017/9/8
 * Time: 13:56
 */

namespace app\index\controller;

use app\index\model\Mode;
use app\index\model\Source;
use Curl\Curl;
use think\Controller;

class Test extends Controller
{
    public function index()
    {
        $periods = input('periods');
        $source1 = Source::get(['periods' => $periods]);
        $source2 = Source::get(['periods' => $periods - 1]);
        $source3 = Source::get(['periods' => $periods - 2]);
        $arr1 = [$source1->num1, $source1->num2, $source1->num3];
        $arr2 = [$source2->num1, $source2->num2, $source2->num3];
        $arr3 = [$source3->num1, $source3->num2, $source3->num3];
        $total = $arr1[0] + $arr1[2] + $arr2[2] * 2 + $arr3[0] + $arr3[2];
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
        sort($forecast);
        dump($forecast);
    }

    public function index2()
    {
        $periods = input('periods');
        $source1 = Source::get(['periods' => $periods]);
        $source2 = Source::get(['periods' => $periods - 1]);
        $source3 = Source::get(['periods' => $periods - 2]);
        $arr1 = [$source1->num1, $source1->num2, $source1->num3];
        $arr2 = [$source2->num1, $source2->num2, $source2->num3];
        $arr3 = [$source3->num1, $source3->num2, $source3->num3];
        // $total = $arr1[0] + $arr1[2] + $arr2[2] * 2 + $arr3[0] + $arr3[2];
        $total = $arr1[1] + $arr2[0] + $arr2[2] * 2 + $arr2[1] + $arr3[1];

        $arr = array_unique(array_merge($arr1, $arr2, $arr3));
        sort($arr);
        $comb = combination($arr, 2);
        $result = [];
        foreach ($comb as $v) {
            $sum = $v[0] + $v[1];
            $result[] = $total - $sum;
        }
        $forecast = array_unique($result);
        sort($forecast);
        dump($forecast);
    }

    public function fenxi()
    {
        $all = Source::where(['forecast' => ['neq', '']])->count();
        $right = Source::where(['status' => 1])->count();
        dump($all);
        dump($right);
        dump($right / $all);
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $periods = input('post.periods');
            if (!$periods) {
                $this->redirect('add');
            }
            $data = Source::get(['periods' => $periods]);
            if ($data) {
                $this->redirect('add');
            } else {
                $source = Source::create(input('post.'));
                $this->redirect('add');
            }

        } else {
            return view('add');
        }
    }

    public function setCookie()
    {
        if ($this->request->isPost()) {
            $cookie = input('post.cookie');
            if ($cookie) {
                cache('cookie', $cookie);
            }
            $this->redirect('setCookie');
        } else {
            return view('setCookie');
        }
    }

    public function calcForecast()
    {
        set_time_limit(0);
        $list = Source::all(function ($query) {
            $query->order('id desc');
        });
        $len = count($list);
        for ($i = 1; $i < $len - 2; $i++) {
            $result = $this->getForecast($list[$i]->periods);
            $flag = true;
            foreach ($result['forecast'] as $key => $value) {
                if ($value > 25 || $value < 3) {
                    $flag = false;
                }
            }
            if ($flag) {
                $forecast = $result['forecast'];
                $total = $result['sum1'] + $result['sum2'];

                for ($j = -2; $j <= 2; $j++) {
                    $sum = $total + $j;
                    if ($sum > 0 && $sum < 27) {
                        $forecast[] = $sum;
                    }
                }

                $forecast = array_unique($forecast);
                sort($forecast);
                $data = Source::get($list[$i - 1]->id);
                $data->forecast = implode(',', $forecast);
                $data->sum1 = $result['sum1'];
                $data->sum2 = $result['sum2'];
                $data->status = in_array($data->sum, $forecast) ? 1 : 0;
                $data->save();
            }
        }
    }

    private function getForecast($periods)
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

        return ['forecast' => $forecast, 'sum1' => $sum1, 'sum2' => $sum2];
    }

    public function getDifference()
    {
        $list = Source::all();
        $difference = [];
        $tmp = 0;
        foreach ($list as $key => $value) {
            if ($tmp) {
                $difference[] = abs($value->sum - $tmp);
            }
            $tmp = $value->sum;
        }

        $arr = [];
        foreach ($difference as $key => $value) {
            if (array_key_exists('a' . $value, $arr)) {
                $arr['a' . $value]++;
            } else {
                $arr['a' . $value] = 1;
            }
        }

        dump($arr);
    }

    /**
     * 正确率25%
     */
    public function test3()
    {
        $list = Source::order('id desc')->limit(3)->select();
        if ($list[0]->sum < 10) {
            $second = $list[0]->sum;
        } else {
            $second = substr($list[0]->sum, 1, 1);
        }
        $sum = $list[0]->num2 + $second + $list[2]->num1;
        if ($sum <= 14) {
            if ($sum % 2 == 0) {
                // 小双
                $temp = [0, 2, 4, 6, 8, 10, 12];
            } else {
                // 小单
                $temp = [1, 3, 5, 7, 9, 11, 13];
            }
        } else {
            if ($sum % 2 == 0) {
                // 大双
                $temp = [14, 16, 18, 20, 22, 24, 26];
            } else {
                // 大单
                $temp = [15, 17, 19, 21, 23, 25, 27];
            }
        }
        dump(implode(',', $temp));

    }

    /**
     * 杀尾法，正确率为15%，最高连跪31期
     */
    public function test4()
    {
        $list = Source::order('id desc')->select();
        $arr = [];
        foreach ($list as $k => $v) {
            if ($k > 0 && $k < count($list) - 2) {
                $temp = [$v->num1, $v->num2, $v->num3, $list[$k + 1]->num1, $list[$k + 1]->num2, $list[$k + 1]->num3, $list[$k + 2]->num1, $list[$k + 2]->num2, $list[$k + 2]->num3];
                $zuhe = [];
                foreach ($temp as $k1 => $v1) {
                    foreach ($temp as $k2 => $v2) {
                        if ($k2 > $k1) {
                            $sum = $v1 + $v2;
                            $zuhe[] = (int)array_reverse(str_split($sum))[0];
                        }
                    }
                }
                $temp = array_merge($temp, $zuhe);
                $weishu = [];
                for ($i = 0; $i <= 9; $i++) {
                    $weishu[$i] = 0;
                }
                foreach ($temp as $k1 => $v1) {
                    $weishu[$v1]++;
                }
                $yuce = array_search(max($weishu), $weishu);
                unset($weishu[$yuce]);
                $yuce2 = array_search(max($weishu), $weishu);
                unset($weishu[$yuce2]);
                $yuce3 = array_search(max($weishu), $weishu);
                if ($yuce == array_reverse(str_split($list[$k - 1]->sum))[0] || $yuce2 == array_reverse(str_split($list[$k - 1]->sum))[0] || $yuce3 == array_reverse(str_split($list[$k - 1]->sum))[0]) {
                    $arr[] = [
                        'yuce1' => $yuce,
                        'yuce2' => $yuce2,
                        'yuce3' => $yuce3,
                        'shiji' => $list[$k - 1]->sum,
                    ];
                }
            }
        }
        dump($arr);
    }

    /**
     * 杀尾法
     */
    public function test5()
    {
        $list = Source::order('id desc')->limit(3)->select();

        $arr = [];
        foreach ($list as $k => $v) {
            if ($k == 0) {
                $temp = [$v->num1, $v->num2, $v->num3, $list[$k + 1]->num1, $list[$k + 1]->num2, $list[$k + 1]->num3, $list[$k + 2]->num1, $list[$k + 2]->num2, $list[$k + 2]->num3];
                $zuhe = [];
                foreach ($temp as $k1 => $v1) {
                    foreach ($temp as $k2 => $v2) {
                        if ($k2 > $k1) {
                            $sum = $v1 + $v2;
                            $zuhe[] = (int)array_reverse(str_split($sum))[0];
                        }
                    }
                }
                $temp = array_merge($temp, $zuhe);
                $weishu = [];
                for ($i = 0; $i <= 9; $i++) {
                    $weishu[$i] = 0;
                }
                foreach ($temp as $k1 => $v1) {
                    $weishu[$v1]++;
                }
                $yuce = array_search(max($weishu), $weishu);
                unset($weishu[$yuce]);
                $yuce2 = array_search(max($weishu), $weishu);
                unset($weishu[$yuce2]);
                $yuce3 = array_search(max($weishu), $weishu);
                $arr[] = [
                    'yuce1' => $yuce,
                    'yuce2' => $yuce2,
                    'yuce3' => $yuce3,
                ];
            }
        }

        echo $list[0]->periods + 1 . '期尾数：' . implode(',', $arr[0]);
    }

    public function test6($value = '')
    {
        $list = Source::order('id desc')->limit(1000)->select();
        $arr = [];
        $jishu = 0;
        $tongji = [];
        foreach ($list as $key => $value) {
            if ($key > 0) {
                if (array_reverse(str_split($value->sum))[0] + array_reverse(str_split($list[$key - 1]->sum))[0] == 9) {
                    $arr[] = $jishu;
                    $tongji[] = array_reverse(str_split($value->sum))[0];
                    $jishu = 0;
                }
                $jishu++;
            }
        }
        $params = [];
        $yAxis = [];
        for ($i = 0; $i <= 50; $i++) {
            $yAxis[] = $i;
        }
        $xAxis = [];
        for ($i = 0; $i <= 100; $i++) {
            $xAxis[] = $i;
        }
        $params['xAxis'] = implode(',', $xAxis);
        $params['yAxis'] = implode(',', $yAxis);
        $params['arr'] = $arr;
        $params['switch'] = cache('switch');
        return view('test6', $params);
    }

    public function test7($value = '')
    {
        $list = Source::order('id asc')->select();

        $arr = [];
        for ($i = 1; $i < 10; $i++) {
            $right = 0;
            $wrong = 0;
            $total = 0;
            foreach ($list as $key => $value) {
                if ($key < count($list) - $i && $key % $i == 0) {
                    if (array_reverse(str_split($value->sum))[0] + array_reverse(str_split($list[$key + 1]->sum))[0] == 9) {
                        $wrong++;
                    } else {
                        $right++;
                    }
                    $total++;
                }
            }
            $arr[$i] = [
                'rate' => $right / $total,
                'right' => $right,
                'wrong' => $wrong,
            ];
        }

        $max = array_search(max(array_column($arr, 'rate')), array_column($arr, 'rate'));

        $data = Source::order('id desc')->find();
        $num = ($data->id - 1) % $max;
        return view('test7', ['num' => $num, 'max' => $max]);
    }

    public function test8($value = '')
    {
        $list = Source::order('id asc')->select();

        $arr = [];
        for ($i = 1; $i < 10; $i++) {
            $right = 0;
            $wrong = 0;
            $total = 0;
            foreach ($list as $key => $value) {
                if ($key < count($list) - $i && $key % $i == 0) {
                    if (array_reverse(str_split($value->sum))[0] + array_reverse(str_split($list[$key + 1]->sum))[0] == 9) {
                        $wrong++;
                        if ($i == 8) {
                            dump($value->sum . '-' . $list[$key + 1]->sum);
                        }
                    } else {
                        $right++;
                    }
                    $total++;

                }
            }
            $arr[$i] = [
                'rate' => $right / $total,
                'right' => $right,
                'wrong' => $wrong,
            ];
        }

        dump($arr);

    }

    public function test9($value = '')
    {
        $list = Source::order('id asc')->select();
        $suiji = [2, 3, 5];
        $right = 0;
        $wrong = 0;
        $total = 0;
        foreach ($list as $key => $value) {
            $i = $suiji[$key % 3];
            if ($key < count($list) - $i && $key % $i == 0) {
                if (array_reverse(str_split($value->sum))[0] + array_reverse(str_split($list[$key + 1]->sum))[0] == 9) {
                    $wrong++;
                    dump($value->sum . '-' . $list[$key + 1]->sum);
                } else {
                    $right++;
                }
                $total++;

            }
        }
        dump($right);
        dump($wrong);
        dump($right / $total);
    }

    public function test10($value = '')
    {
        $list = Source::order('id asc')->select();
        $right = 0;
        $wrong = 0;
        $total = 0;
        foreach ($list as $key => $value) {
            if ($key < count($list) - 1) {
                if (array_reverse(str_split($value->sum))[0] + array_reverse(str_split($list[$key + 1]->sum))[0] == 9) {
                    $wrong++;
                    dump($value->sum . '-' . $list[$key + 1]->sum);
                } else {
                    $right++;
                }
                $total++;

            }
        }
        dump($right);
        dump($wrong);
        dump($right / $total);
    }

    public function test11()
    {
        $list = Source::order('id asc')->select();
        $right = 0;
        $wrong = 0;
        foreach ($list as $key => $value) {
            if ($key < count($list) - 2) {
                if ($value->sum + 2 == $list[$key + 1]->sum) {
                    if ($value->sum % 2 == 0 && $list[$key + 2]->sum % 2 == 0) {
                        $right++;
                    } else {
                        $wrong++;
                    }
                    dump($value->sum . '|' . $list[$key + 1]->sum . '|' . $list[$key + 2]->sum);
                }
            }
        }
        dump($right);
        dump($wrong);
    }

    public function test12()
    {
        $num = input('num');
        $list = Source::where(['sum' => $num])->select();
        $arr = [];
        foreach ($list as $v) {
            $data = Source::get($v->id + 1);
            if ($data) {
                $arr[] = $data->sum;
            }
        }

        if ($arr) {
            $temp = array_unique($arr);
            sort($temp);
            $arr1 = [];
            foreach ($temp as $v) {
                $arr1[$v] = 0;
            }
            foreach ($temp as $v) {
                foreach ($arr as $v1) {
                    if ($v1 == $v) {
                        $arr1[$v]++;
                    }
                }
            }
            dump($arr1);
        }

    }

    public function test13()
    {
        $list = Source::order('id desc')->select();
        $wrong = 0;
        $right = 0;
        $arr = [];
        $temp = 0;
        foreach ($list as $key => $value) {
            if ($key > 1 && $key < count($list) - 4) {
                $num1 = array_reverse(str_split($value->num1 + $list[$key + 1]->num1 + $list[$key + 2]->num1))[0];
                $num2 = array_reverse(str_split(str_split($value->sum)[0] + str_split($list[$key + 1]->sum)[0] + $list[$key + 2]->num3 + $list[$key + 3]->num1))[0];
                $num3 = array_reverse(str_split($list[$key + 2]->num2 + $list[$key + 2]->num3 + $list[$key + 3]->num1))[0];
                $sum = $num1 + $num2 + $num3;
                if ($sum % 2 == $list[$key - 1]->sum % 2) {
                    $right++;
                    $arr[] = $temp;
                    $temp = 0;
                } else {
                    $wrong++;
                    $temp++;
                }
            }
        }
        dump(max($arr));
        dump($wrong);
        dump($right);
        dump($right / ($right + $wrong));
    }

    public function test14()
    {
        $all = Source::order('id desc')->select();
        $arr = [];
        for ($i = 100; $i < count($all); $i++) {
            $list = Source::order('id desc')->limit($i)->select();
            $right = 0;
            $wrong = 0;
            foreach ($list as $key => $value) {
                if ($key < count($list) - 1) {
                    $num1 = array_reverse(str_split($value->sum))[0];
                    $num2 = array_reverse(str_split($list[$key + 1]->sum))[0];
                    if ($num1 + $num2 == 9) {
                        $wrong++;
                    } else {
                        $right++;
                    }
                }
            }
            $arr[] = $right / ($wrong + $right);
        }

        dump(implode(',', $arr));
    }

    public function test15()
    {
        $list = Source::order('id desc')->select();
        $right = 0;
        $temp = 0;
        $arr1 = [];
        foreach ($list as $key => $value) {
            if ($key > 1 && $key < count($list) - 3) {
                $arr = [];
                $arr[] = $list[$key]->num1 + $list[$key + 1]->num1 + $list[$key + 2]->num1;
                $arr[] = $list[$key]->num2 + $list[$key + 1]->num2 + $list[$key + 2]->num2;
                $arr[] = $list[$key]->num3 + $list[$key + 1]->num3 + $list[$key + 2]->num3;
                $arr[] = $list[$key]->num1 + $list[$key + 1]->num2 + $list[$key + 2]->num3;
                $arr[] = $list[$key]->num3 + $list[$key + 1]->num2 + $list[$key + 2]->num1;
                $dan = 0;
                $shuang = 0;
                foreach ($arr as $k1 => $v1) {
                    if ($v1 % 2 == 0) {
                        $shuang++;
                    } else {
                        $dan++;
                    }
                }
                if ($dan > $shuang) {
                    if ($list[$key - 1]->sum % 2 == 1) {
                        $right++;
                        $arr1[] = $temp;
                        $temp = 0;
                    } else {
                        $temp++;
                    }
                }
                if ($shuang > $dan) {
                    if ($list[$key - 1]->sum % 2 == 0) {
                        $right++;

                        $arr1[] = $temp;
                        $temp = 0;
                    } else {
                        $temp++;
                    }
                }
            }
        }
        dump($right / count($list));
        dump($arr1);
    }

    public function test16()
    {
        $num1 = input('num1');
        $num2 = input('num2');
        $list = Source::select();
        $arr = [];
        foreach ($list as $key => $value) {
            if ($key < count($list) - 2 && $value->sum == $num1 && $list[$key + 1]->sum == $num2) {
                $arr[] = $list[$key + 2]->sum;
            }
        }
        dump($arr);
        exit;
    }

    public function test17()
    {
        $list = Source::select();
        $right = 0;
        $wrong = [];
        foreach ($list as $key => $value) {
            if ($key < (count($list) - 3) && $list[$key + 1]->sum > $value->sum - 7 && $list[$key + 1]->sum < $value->sum + 7) {
                $right++;
            } else {

                $wrong[] = $value->sum . ',' . $list[$key + 1]->sum;
            }
        }
        dump($right);
        dump($wrong);
    }

    public function test18()
    {
        $list = Source::select();
        $arr = [];
        $right = [];
        $wrong = [];
        foreach ($list as $key => $value) {
            if ($key < count($list) - 4 && $value->periods + 1 == $list[$key + 1]->periods &&
                $value->periods + 2 == $list[$key + 2]->periods &&
                $value->periods + 3 == $list[$key + 3]->periods &&
                $value->periods + 4 == $list[$key + 4]->periods) {
                $sum = intval($list[$key + 4]->num1 . $list[$key + 3]->num3 . $list[$key + 2]->num3 . $list[$key + 1]->num1) + intval($list[$key + 3]->num1 . array_reverse(str_split($list[$key + 4]->sum))[0]);
                $num = intval($sum / 28);
                $arr = str_split($num);
                $total = 0;
                foreach ($arr as $key => $v) {
                    $total += $v;
                }
                $str = '';
                if ($total <= 14) {
                    $str .= '小';
                    if ($total % 2 == 0) {
                        $str .= '双';
                    } else {
                        $str .= '单';
                    }
                } else {
                    $str .= '大';
                    if ($total % 2 == 0) {
                        $str .= '双';
                    } else {
                        $str .= '单';
                    }
                }
                $right[] = [$str, $value->sum];
            }
        }
        dump($right);

    }

    /**
     * 预测
     */
    public function test19()
    {
        $first = Source::order('id desc')->find();
        $second = Source::get($first->id - 1);
        $third = Source::get($first->id - 2);
        $one = $first->num1 + $second->num2 + $third->num1;
        $two = $first->num3 + $second->num2 + $third->num3;
        $three = $second->sum;
        $result = array_reverse(str_split($one))[0] + array_reverse(str_split($two))[0] + array_reverse(str_split($three))[0];
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
        echo $forecast;
    }

    public function test20()
    {
        cache('times',null);
    }

    /**
     * 自动投注
     */
    public function auto()
    {
        $cookie = cache('cookie');
        if (!$cookie) {
            $cookie = 'CFDB0042FF0E06A00BAC9097444DA5713A154D6E414AA0A16C8BB589D7664FDF2A9D2AEA8901036DA185961B18A50381D6B1E8A5FE73D3270FC9A8C4961CCD0A8C0D70CF2D79A3ED06B589E44B15838B4532558E3924BC74930C7B84EAD2BB29E2380F8D8D4D2BB04498F2C302405010854081AFFACF8175CCBD5266309141B40EDDD11A';
            cache('cookie', $cookie);
        }
        $curl = new Curl();
        $curl->setCookie('.ADWASPX7A5C561934E_PCEGGS', $cookie);

        $last = Source::order('id desc')->find();
        $lid = 133738 + $last->periods + 1;
        $lid = 1072604;
        $curl->setHeader('Referer', 'http://www.pceggs.com/play/pg28Insert.aspx?LID=' . $lid);
        $arr = [
            'CTIME' => '2018-11-04 20:00',
            'ALLSMONEY' => '1,,,,,,,,,,,,,,,,,,,,,,,,,,,',
            'isdb_p' => '0',
            'SMONEYSUM' => '1',
            'SMONEYY' => 'ALD',
            'Cheat' => 'CAE',
            '_' => time() * 1000,
        ];

        $result = $curl->get('http://www.pceggs.com/play/pg28Insert_ajax.ashx?LID=' . $lid . '&' . http_build_query($arr));
        dump($result);
        exit;
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
    }

    public function start()
    {
        cache('switch', true);
    }

    public function stop()
    {
        cache('switch', false);
    }

    public function test21()
    {
        cache('mode',1);
        $modeFunc = 'mode'.cache('mode');
        if(method_exists(new Mode(),$modeFunc)){
            $modeData = Mode::$modeFunc();
        }
    }


}
