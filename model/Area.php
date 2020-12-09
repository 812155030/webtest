<?php

namespace addons\unishop\model;


use think\Model;

/**
 * 收货地址模型
 * Class Favorite
 * @package addons\unishop\model
 */
class Area extends Model
{
    // 表名
    protected $name = 'unishop_area';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';


}
