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
class OrderProduct extends Model
{
    // 表名
    protected $name = 'unishop_order_product';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 隐藏属性
    protected $hidden = [
        'user_id',
        'order_id',
        'product_id'
    ];

    protected $append = [
        'order_product_id'
    ];

    public function getIdAttr($value, $data)
    {
        return Hashids::encodeHex($data['product_id']);
    }

    public function getOrderProductIdAttr($value, $data)
    {
        return $data['id'];
    }

    /**
     * 关联商品信息
     */
    public function product()
    {
        return $this->hasOne('product', 'id', 'product_id');
    }
}
