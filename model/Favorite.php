<?php

namespace addons\unishop\model;


use addons\unishop\extend\Hashids;
use think\Model;

/**
 * Class Favorite 收藏表
 * @package addons\unishop\model
 */
class Favorite extends Model
{
    // 表名
    protected $name = 'unishop_favorite';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;

    protected $hidden = [
        'product_id'
    ];

    /**
     * 关联商品表
     */
    public function product()
    {
        return $this->hasOne('product', 'id', 'product_id')->field('id,image,title,sales_price,market_price');
    }
}
