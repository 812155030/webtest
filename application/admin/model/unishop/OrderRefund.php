<?php

namespace app\admin\model\unishop;

use think\Model;

/**
 * 分类模型
 */
class OrderRefund extends Model
{
    // 表名
    protected $name = 'unishop_order_refund';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public function getReceivingStatusTextAttr()
    {
        return ['0' => '未收到', '1' => '已收到'];
    }

    public function getServiceTypeTextAttr()
    {
        return ['0' => '我要退款(无需退货)', '1' => '我要退货退款', '2' => '换货'];
    }

}
