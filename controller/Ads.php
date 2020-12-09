<?php

namespace addons\unishop\controller;

use app\common\controller\Api;

/**
 * 广告接口
 * Class Ads
 * @package addons\unishop\controller
 */
class Ads extends Api
{

    protected $noNeedLogin = ['index'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 测试方法
     *
     * @ApiTitle    (广告列表)
     * @ApiSummary  (测试描述信息)
     * @ApiMethod   (GET)
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="[{
    'id': 1,
    'image': '/h5/static/temp/banner3.jpg',
    'product_id': 1,
    'background': 'rgb(203, 87, 60)',
    'position': 0,
    'status': 1,
    'weigh': 1,
    'createtime': 1561122209,
    'updatetime': 1571558218
    }]", description="扩展数据返回")
     * @ApiReturn   ({
    'code':'1',
    'mesg':'返回成功'
     * })
     */
    public function index()
    {
        $ads = \addons\unishop\model\Ads::where('status', 1)->cache('ads-index', 20)->select();
        $this->success('广告列表', $ads);
    }

}
