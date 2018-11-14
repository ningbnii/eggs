<?php
/**
 * Created by PhpStorm.
 * User: ning
 * Date: 2018/11/13
 * Time: 22:17
 */

namespace app\index\controller;

use app\index\model\Source;

class Fenxi
{
    public function test1()
    {
        $list  = Source::order('id desc')->select();
        $arr   = [];
        $count = count($list);
        foreach ($list as $key => $value) {
            try {
                if ($key < $count - 2) {

                    if ($value->periods - 1 != $list[$key + 1]->periods) {
                        $arr[] = $value->id;
                    }
                }
            } catch (\Exception $e) {
                dump($value);exit;
            }

        }
        dump($arr);

    }
}
