<?php

namespace addons\unishop\model;


use think\Model;

/**
 * 收货地址模型
 * Class Favorite
 * @package addons\unishop\model
 */
class Address extends Model
{
    // 表名
    protected $name = 'unishop_address';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 是否为默认地址？
    const IS_DEFAULT_YES = 1; //是
    const IS_DEFAULT_NO = 0;  //否

    public function province()
    {
        return $this->belongsTo('area', 'province_id', 'id');
    }

    public function city()
    {
        return $this->belongsTo('area', 'city_id', 'id');
    }

    public function area()
    {
        return $this->belongsTo('area', 'area_id', 'id');
    }
}
