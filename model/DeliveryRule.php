<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2019-07-14
 * Time: 22:45
 */

namespace addons\unishop\model;

use addons\unishop\model\Delivery as DeliveryModel;
use think\Model;

class DeliveryRule extends Model
{

    // 表名
    protected $name = 'unishop_delivery_rule';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 获取地区的运费配送列表
     * @param $cityId
     * @return array [list:运费模板， status:1=有,0=没]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDelivetyByArea($cityId)
    {
        $prefix = \think\Config::get('database.prefix');
        $data = $this->alias('DR')
            ->join($prefix.DeliveryModel::TABLE_NAME . ' D', 'DR.delivery_id = D.id')
            //->where("D.switch = " . DeliveryModel::SWITCH_YES . " AND find_in_set($cityId,DR.area)")
            ->where("D.switch = " . DeliveryModel::SWITCH_YES)
            ->field('D.id,D.name,D.type,D.min,DR.first,DR.first_fee,DR.additional,DR.additional_fee,DR.area')
            ->order(['min' => SORT_ASC])
            ->cache(10)
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            foreach ($data as &$delivery) {
                if (!in_array($cityId, explode(',', $delivery['area']))) {
                    $delivery['name'] = $delivery['name']. '(收货地址不在配送范围)';
                }
                unset($delivery['area']);
            }
        }
        $status = $data ? 1 : 0;
        $data = $data ? $data : [['name'=>'收货地址不在配送范围']];
        return [
            'list' => $data,
            'status' => $status
        ];
    }

    /**
     * 是否在配送范围内
     * @param int $cityId 城市id
     * @param int $deliveryId 配送方式id
     * @return int|string
     * @throws \think\Exception
     */
    public function cityInScopeOfDelivery($cityId, $deliveryId) {
        $prefix = \think\Config::get('database.prefix');
        return $this->alias('DR')
            ->join($prefix.DeliveryModel::TABLE_NAME . ' D', 'DR.delivery_id = D.id')
            ->where("D.id = $deliveryId AND find_in_set($cityId,DR.area) AND D.switch = " . DeliveryModel::SWITCH_YES)
            ->field('D.id,D.name,D.type,D.min,DR.first,DR.first_fee,DR.additional,DR.additional_fee')
            ->find();
    }

}
