<?php

namespace app\admin\model\unishop;

use think\Model;


class FlashProduct extends Model
{



    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'unishop_flash_product';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];


    /**
     * 关联到产品
     */
    public function product()
    {
        return $this->belongsTo('product', 'product_id', 'id');
    }







}
