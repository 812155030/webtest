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
class OrderRefund extends Model
{
    // 表名
    protected $name = 'unishop_order_refund';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 隐藏属性
    protected $hidden = [
        'user_id',
        'order_id',
    ];

    // 货物状态 0=未收到,1=已收到
    const UNRECEIVED = 0;
    const RECEIVED = 1;

    // 服务类型 0=我要退款(无需退货),1=我要退货退款,2=换货
    const TYPE_REFUND_NORETURN = 0;
    const TYPE_REFUND_RETURN = 1;
    const TYPE_EXCHANGE = 2;


}
