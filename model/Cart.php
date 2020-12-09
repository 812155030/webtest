<?php

namespace addons\unishop\model;


use think\Model;

/**
 * Class Cart 购物车
 * @package addons\unishop\model
 */
class Cart extends Model
{
    // 表名
    protected $name = 'unishop_cart';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    //是否选中
    const CHOOSE_ON = 1; // 是
    const CHOOSE_OFF = 0; // 否

    /**
     * 关联产品
     */
    public function product(){
        return $this->hasOne('product', 'id', 'product_id');
    }
}
