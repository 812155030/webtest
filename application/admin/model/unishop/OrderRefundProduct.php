<?php

namespace app\admin\model\unishop;

use think\Model;

/**
 * 分类模型
 */
class OrderRefundProduct extends Model
{
    // 表名
    protected $name = 'unishop_order_refund_product';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
}
