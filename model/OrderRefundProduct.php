<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/1/6
 * Time: 11:25 下午
 */


namespace addons\unishop\model;

use addons\unishop\extend\Hashids;
use think\Model;

/**
 * 订单商品表
 * Class OrderExtend
 * @package addons\unishop\model
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

    // 隐藏属性
    protected $hidden = [
        'order_id',
    ];


}
