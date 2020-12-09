<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/2/9
 * Time: 6:29 PM
 */


namespace addons\unishop\model;


use addons\unishop\extend\Hashids;
use think\Model;

class FlashProduct extends Model
{
    // 表名
    protected $name = 'unishop_flash_product';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 已上架
    const SWITCH_ON = 1; // 是
    const SWITCH_OFF = 0; // 否

    // 隐藏属性
    protected $hidden = [
        'flash_id',
        'product_id',
        'id'
    ];

    // 追加属性
    protected $append = [
        'flash_product_id'
    ];

    public function getFlashProductIdAttr($value, $data) {
        return Hashids::encodeHex($data['product_id']);
    }

    /**
     * 关联到商品表
     */
    public function product()
    {
        return $this->belongsTo('product', 'product_id', 'id')->field('id,title,sales_price,market_price,image');
    }
}
