<?php

namespace addons\unishop\controller;

use app\common\controller\Api;

class Category extends Api
{

    protected $noNeedLogin = ['all','menu','inlist'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \addons\unishop\model\Category();
    }


    /**
     * 全部分类数据
     */
    public function all(){
        $all = $this->model
            ->where('type','product')
            ->where('status','normal')
            ->field('id,name,pid,image,type,flag,weigh')
            ->order('weigh ASC')
            ->cache(20)
            ->select();
        if ($all) {
            $all = collection($all)->toArray();
        }
        $this->success('',$all);
    }


    /**
     * 首页广告下面的分类
     */
    public function menu()
    {
        $list = $this->model
            ->where('flag','index')
            ->where('status','normal')
            ->cache(20)
            ->select();
        if ($list) {
            $list = collection($list)->toArray();
        }
        $this->success('菜单',$list);
    }



}
