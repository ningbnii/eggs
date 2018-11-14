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
    /**
     * @Notes: 分析数据是否完整
     * @Author: chenning[296720094@qq.com]
     * @Date: 2018/11/14
     * @Time: 19:43
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
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
