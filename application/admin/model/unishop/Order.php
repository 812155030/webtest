<?php

namespace app\admin\model\unishop;

use think\Model;
use traits\model\SoftDelete;

class Order extends Model
{

    use SoftDelete;

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'unishop_order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 订单状态
    const STATUS_NORMAL = 1; // 正常
    const STATUS_CANCEL = 0; // 用户取消订单
    const STATUS_REFUND = -1; // 申请售后

    // 追加属性
    protected $append = [
        'pay_type_text',
        'status_text'
    ];

    public function getPayTypeList()
    {
        return ['1' => __('Online'), '2' => __('Offline'), '3' => __('wxPay'), '4' => __('aliPay')];
    }

    public function getStatusList()
    {
        return ['-1' => __('Refund'), '0' => __('Cancel'), '1' => __('Normal')];
    }

    public function getRefundStatusList()
    {
        return ['0' => __('None'), '1' => __('Apply'),'2' => __('Pass and left User delivery') , '3' => __('Pass'), '4' => __('Refuse')];
    }

    public function getPayTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_type']) ? $data['pay_type'] : '');
        $list = $this->getPayTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setHavePaidAttr($value)
    {
        return $value === '' ? 0 : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setHaveDeliveredAttr($value)
    {
        return $value === '' ? 0 : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setHaveCommentedAttr($value)
    {
        return $value === '' ? 0 : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setHaveReceivedAttr($value)
    {
        return $value === '' ? 0 : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    /**
     * 关联用户
     */
    public function user(){
        return $this->hasOne('user', 'id', 'user_id');
    }

    /**
     * 关联订单扩展
     */
    public function extend(){
        return $this->belongsTo('orderExtend', 'id', 'order_id');
    }

    /**
     * 关联订单商品
     */
    public function product(){
        return $this->hasMany('orderProduct', 'order_id', 'id');
    }

    /**
     * 关联评价信息
     */
    public function evaluate()
    {
        return $this->hasMany('evaluate', 'order_id', 'id');
    }

    /**
     * 关联退货信息
     */
    public function refund()
    {
        return $this->hasOne('orderRefund', 'order_id', 'id');
    }

    public function refundProduct()
    {
        return $this->hasMany('orderRefundProduct', 'order_id', 'id');
    }
}
