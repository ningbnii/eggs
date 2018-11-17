<?php
/**
 * Created by PhpStorm.
 * User: ning
 * Date: 2018/11/13
 * Time: 22:17
 */

namespace app\index\controller;

use app\index\model\Forecast;
use app\index\model\Source;
use think\Db;
use think\Request;

class Fenxi
{
    public function index()
    {
        $this->test2();
        $list = Db::name('forecast')->alias('f')
            ->field('s.periods,mode1,mode2,mode3,mode4')
            ->join('source s','s.id=f.source_id','left')
            ->order('f.id desc')
            ->select();

        return view('index',['list'=>$list,'switch'=>cache('switch'),'mode'=>cache('mode')]);
    }
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

    /**
     * 各个模式的判断是否正确
     */
    public function test2()
    {
        $source = Source::all();
        $count = count($source);
        $data= [];
        foreach ($source as $k=>$v){
            if($k<$count-3){
                $data[] =Forecast::checkMode($source[$k+2],$source[$k+1],$source[$k],$source[$k+3]);
            }
        }
        $forecast = new Forecast();
        $forecast->saveAll($data);
    }


    public function setMode()
    {
        if(Request::instance()->isPost()){
            $mode = input('mode');
            cache('mode',$mode);
        }
    }

}
