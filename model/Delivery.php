<?php

namespace addons\unishop\model;

use think\Model;

class Delivery extends Model
{

    // 表名
    protected $name = 'unishop_delivery';
    const TABLE_NAME = 'unishop_delivery';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 是否上架
    const SWITCH_YES = 1;
    const SWITCH_NO = 0;

    /**
     * 关联配送模板区域及运费
     * @return \think\model\relation\HasMany
     */
    public function rule()
    {
        return $this->hasMany('DeliveryRule','delivery_id','id');
    }

    /**
     * 运费算法
     * @param Delivery $delivery
     * @param int $number
     * @return int
     */
    public static function algorithm($delivery, $number) {
        $deliveryPrice = 0;
        $delivery['first'] = $delivery['first'] == 0 ? 1 : $delivery['first'];
        $delivery['additional'] = $delivery['additional'] == 0 ? 1 : $delivery['additional'];
        for ($i = 0; $i < $number; ) {
            if ($i === 0) {
                $deliveryPrice = bcadd($delivery['first_fee'], $deliveryPrice, 2);
                $i += $delivery['first'];
            } else {
                $deliveryPrice = bcadd($delivery['additional_fee'], $deliveryPrice, 2);
                $i += $delivery['additional'];
            }
        }
        return $deliveryPrice;
    }
}
