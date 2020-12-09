<?php

namespace app\admin\model\unishop;

use think\Model;

class OrderExtend extends Model
{

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'unishop_order_extend';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 关联订单表
     * @return Model|\think\model\relation\HasOne
     */
    public function order()
    {
        return $this->hasOne('order', 'id', 'order_id');
    }

}
