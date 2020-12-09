<?php

namespace addons\unishop\model;


use addons\unishop\extend\Hashids;
use addons\unishop\extend\Snowflake;
use think\Hook;
use think\Model;
use traits\model\SoftDelete;

/**
 * 收货地址模型
 * Class Favorite
 * @package addons\unishop\model
 */
class Order extends Model
{
    use SoftDelete;
    protected $deleteTime = 'deletetime';

    // 表名
    protected $name = 'unishop_order';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 隐藏属性
    protected $hidden = [
        'id',
        'user_id'
    ];

    // 支付类型
    const PAY_ONLINE = 1; // 在线支付
    const PAY_OFFLINE = 2; // 线下支付 或 货到付款
    const PAY_WXPAY = 3; // 微信支付
    const PAY_ALIPAY = 4; // 支付宝支付

    // 订单状态
    const STATUS_NORMAL = 1; // 正常
    const STATUS_CANCEL = 0; // 用户取消订单
    const STATUS_REFUND = -1; // 申请售后

    // 申请售后状态 0=无,1=申请中,2=通过(让用户发货),3=通过,4=拒绝
    const REFUND_STATUS_NONE = 0;
    const REFUND_STATUS_APPLY = 1;
    const REFUND_STATUS_DELIVERY = 2;
    const REFUND_STATUS_AGREE = 3;
    const REFUND_STATUS_REFUSE = 4;

    // 是否支付
    const PAID_NO = 0; // 否

    // 是否发货 delivered
    const DELIVERED_NO = 0; // 否

    // 是否评论
    const COMMENTED_NO = 0; // 否

    // 是否收货
    const RECEIVED_NO = 0; // 否


    // 订单类型
    const TYPE_ALL = 0; // 全部
    const TYPE_PAY = 1; // 待付款
    const TYPE_DELIVES = 2; // 待发货
    const TYPE_RECEIVE = 3; // 待发货
    const TYPE_COMMENT = 4; // 待评价
    const TYPE_REFUND = 5; // 售后
    const TYPE_REFUSE = 6; // 拒绝退款
    const TYPE_OFF = 9; // 订单关闭


    /**
     * 格式化时间
     * @param $value
     * @return false|string
     */
    public function getCreatetimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 格式化时间 paidtime
     * @return false|int|string
     */
    public function getPaidtimeAttr($value, $data)
    {
        return $data['have_paid'] > 0 ? date('Y-m-d H:i:s', $data['have_paid']) : 0;
    }

    /**
     * 格式化时间 deliveredtime
     * @return false|int|string
     */
    public function getDeliveredtimeAttr($value, $data)
    {
        return $data['have_delivered'] > 0 ? date('Y-m-d H:i:s', $data['have_delivered']) : 0;
    }

    /**
     * 格式化时间 receivedtime
     * @return false|int|string
     */
    public function getReceivedtimeAttr($value, $data)
    {
        return $data['have_received'] > 0 ? date('Y-m-d H:i:s', $data['have_received']) : 0;
    }

    /**
     * 格式化时间 commentedtime
     * @return false|int|string
     */
    public function getCommentedtimeAttr($value, $data)
    {
        return $data['have_commented'] > 0 ? date('Y-m-d H:i:s', $data['have_commented']) : 0;
    }

    /**
     * 支付类型
     */
    public function getPayTypeTextAttr($value, $data)
    {
        switch ($data['pay_type']) {
            case self::PAY_ONLINE:
                return __('Online');
                break;
            case self::PAY_OFFLINE:
                return __('Offline');
                break;
            case self::PAY_WXPAY:
                return __('wxPay');
                break;
            case self::PAY_ALIPAY:
                return __('aliPay');
                break;
        }
    }

    /**
     * 加密订单id
     * @param $value
     * @param $data
     * @return string
     */
    public function getOrderIdAttr($value, $data)
    {
        return Hashids::encodeHex($data['id']);
    }

    /**
     * 0=全部,1=待付款,2=待发货,3=待收货,4=待评价,5=售后
     * 获取当前的订单状态
     */
    public function getStateAttr($value, $data)
    {
        switch (true) {
            case $data['have_paid'] == self::PAID_NO && $data['status'] == self::STATUS_NORMAL:
                $state = self::TYPE_PAY;
                break;
            case $data['have_delivered'] == self::DELIVERED_NO && $data['status'] == self::STATUS_NORMAL:
                $state = self::TYPE_DELIVES;
                break;
            case $data['have_received'] == self::RECEIVED_NO && $data['status'] == self::STATUS_NORMAL:
                $state = self::TYPE_RECEIVE;
                break;
            case $data['have_commented'] == self::COMMENTED_NO && $data['status'] == self::STATUS_NORMAL:
                $state = self::TYPE_COMMENT;
                break;
            case $data['status'] == self::STATUS_REFUND && $data['refund_status'] == self::REFUND_STATUS_AGREE: // TODO 申请退款并且已通过同意，则订单为关闭状态
            case $data['status'] == self::STATUS_CANCEL:
                $state = self::TYPE_OFF;
                break;
            case $data['status'] == self::STATUS_REFUND && $data['refund_status'] == self::REFUND_STATUS_REFUSE:
                $state = self::TYPE_REFUSE;
                break;
            case $data['status'] == self::STATUS_REFUND:
                $state = self::TYPE_REFUND;
                break;
            default:
                $state = self::TYPE_ALL;
                break;
        }
        return $state;
    }

    /**
     * 退款状态
     */
    public function getRefundStatusTextAttr($value, $data)
    {
        $name = '';
        if ($data['status'] == self::STATUS_REFUND) {
            switch ($data['refund_status']) {
                case self::REFUND_STATUS_APPLY:
                    $name = '申请中';
                    break;
                case self::REFUND_STATUS_DELIVERY:
                    $name = '通过申请/请发货';
                    break;
                case self::REFUND_STATUS_AGREE:
                    $name = '退款成功';
                    break;
                case self::REFUND_STATUS_REFUSE:
                    $name = '退款失败';
                    break;
            }
        }
        return $name;
    }

    /**
     * 创建订单
     * @param $userId
     * @param $data
     * @return int
     * @throws \Exception
     */
    public function createOrder($userId, $data)
    {
        $data['userId'] = $userId;

        Hook::listen('create_order_before', $params, $data);
        list($products, $delivery, $coupon, $baseProductInfos, $address, $orderPrice, $specs, $numbers) = $params;

        // 获取雪花算法分布式id，方便以后扩展
        $snowflake = new Snowflake();
        $id = $snowflake->id();
        // 优惠费用
        $discountPrice = isset($coupon['value']) ? $coupon['value'] : 0;
        // 订单费用
        //$orderPrice;
        // 运费
        $deliveryPrice = Delivery::algorithm($delivery, $data['number']);
        // 总费用
        $totalPrice = bcadd(bcsub($orderPrice, $discountPrice, 2), $deliveryPrice, 2);

        $out_trade_no = date('Ymd',time()).uniqid().$userId;
        (new self)->save([
            'id' => $id,
            'user_id' => $userId,
            'out_trade_no' => $out_trade_no,
            'order_price' => $orderPrice,
            'discount_price' => $discountPrice,
            'delivery_price' => $deliveryPrice,
            'total_price' => $totalPrice,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'remark' => $data['remark'] ?? '',
            'status' => self::STATUS_NORMAL,
        ]);

        (new OrderExtend)->save([
            'user_id' => $userId,
            'order_id' => $id,
            'coupon_id' => $coupon ? $coupon['id'] : 0,
            'coupon_json' => json_encode($coupon),
            'delivery_id' => $delivery['id'],
            'delivery_json' => json_encode($delivery),
            'address_id' => $address['id'],
            'address_json' => json_encode($address),
        ]);


        $orderProduct = $specNumber = [];
        foreach($products as $key => $product) {
            $orderProduct[] = [
                'user_id' => $userId,
                'order_id' => $id,
                'product_id' => $product['id'],
                'title' => $product['title'],
                'image' => $product['image'],
                'number' => $numbers[$key],
                'spec' => $specs[$key] ?? '',
                'price' => $baseProductInfos[$key]['sales_price'],
                //'product_json' => json_encode($product), // Todo 耗内存，损速度 (考虑去掉)
                'createtime' => time(),
                'updatetime' => time(),
                'flash_id' => $data['flash_id'] ?? 0, // 秒杀id
            ];

            if (!empty($specs[$key])) {
                $specNumber[$specs[$key]] = $numbers[$key];
            } else {
                $specNumber[$key] = $numbers[$key];
            }
        }
        (new OrderProduct)->insertAll($orderProduct);

        $data['specNumber'] = $specNumber;

        Hook::listen('create_order_after', $products, $data);

        return [
            'order_id' => Hashids::encodeHex($id),
            'out_trade_no' => $out_trade_no
        ];
    }

    /**
     * 获取我的订单
     * @param int $userId 用户id
     * @param int $state 0=全部,1=待付款,2=待发货,3=待收货,4=待评价,5=售后
     */
    public function getOrdersByType($userId, $state = 0, $page = 1, $pageSize = 10)
    {
        $condition['user_id'] = ['=', $userId];
        switch ($state) {
            case self::TYPE_PAY:
                $condition['have_paid'] = ['=', self::PAID_NO];
                $condition['status'] = ['=', self::STATUS_NORMAL];
                $orderBy = 'createtime';
                break;
            case self::TYPE_DELIVES:
                $condition['have_paid'] = ['>', self::PAID_NO];
                $condition['have_delivered'] = ['=', self::DELIVERED_NO];
                $condition['status'] = ['=', self::STATUS_NORMAL];
                $orderBy = 'have_paid';
                break;
            case self::TYPE_RECEIVE:
                $condition['have_paid'] = ['>', self::PAID_NO];
                $condition['have_delivered'] = ['>', self::DELIVERED_NO];
                $condition['have_received'] = ['=', self::RECEIVED_NO];
                $condition['status'] = ['=', self::STATUS_NORMAL];
                $orderBy = 'have_delivered';
                break;
            case self::TYPE_COMMENT:
                $condition['have_paid'] = ['>', self::PAID_NO];
                $condition['have_delivered'] = ['>', self::DELIVERED_NO];
                $condition['have_received'] = ['>', self::RECEIVED_NO];
                $condition['have_commented'] = ['=', self::COMMENTED_NO];
                $condition['status'] = ['=', self::STATUS_NORMAL];
                $orderBy = 'have_received';
                break;
            case self::TYPE_REFUND:
                $condition['have_paid'] = ['>', self::PAID_NO];
                $condition['status'] = ['=', self::STATUS_REFUND];
                $condition['refund_status'] = ['<>', self::REFUND_STATUS_AGREE];
                $orderBy = 'createtime';
                break;
            default: //全部
                $orderBy = 'createtime';
                break;
        }

        $result = $this
            ->with([
                'products' => function($query) {
                    $query->field('id,title,image,number,price,spec,order_id,product_id');
                },
                'extend' => function($query) {
                    $query->field('order_id,express_number');
                },
                'evaluate' => function($query) {
                    $query->field('id,order_id,product_id');
                },
                'refundProducts' => function($query) {
                    $query->field('id,order_id,order_product_id');
                }
            ])
            ->where($condition)
            ->order([$orderBy => 'desc'])
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();

        foreach ($result as &$item) {
            $item->append(['order_id','state', 'refund_status_text']);
            $item = $item->toArray();

            $evaluate = array_column($item['evaluate'], 'product_id');
            $refundProducts = array_column($item['refund_products'], 'order_product_id');
            unset($item['evaluate']);
            unset($item['refund_products']);

            foreach ($item['products'] as &$product) {
                $product['image'] = Config::getImagesFullUrl($product['image']);
                // 是否已评论
                if (in_array($product['id'], $evaluate)) {
                    $product['evaluate'] = true;
                } else {
                    $product['evaluate'] = false;
                }

                // 是否退货
                if ($item['refund_status'] == self::REFUND_STATUS_AGREE && in_array($product['order_product_id'], $refundProducts)) {
                    $product['refund'] = true;
                } else {
                    $product['refund'] = false;
                }
            }

        }

        return $result;
    }

    /**
     * 关联订单的商品
     */
    public function products()
    {
        return $this->hasMany('orderProduct', 'order_id', 'id');
    }

    /**
     * 关联扩展订单信息
     * @return \think\model\relation\HasOne
     */
    public function extend()
    {
        return $this->hasOne('orderExtend', 'order_id', 'id');
    }

    /**
     * 关联评价
     * @return \think\model\relation\HasOne
     */
    public function evaluate()
    {
        return $this->hasMany('evaluate', 'order_id', 'id');
    }

    /**
     * 关联退货信息
     * @return \think\model\relation\HasMany
     */
    public function refund()
    {
        return $this->hasOne('orderRefund', 'order_id', 'id');
    }

    /**
     * 关联退货的商品
     * @return \think\model\relation\HasMany
     */
    public function refundProducts()
    {
        return $this->hasMany('orderRefundProduct', 'order_id', 'id');
    }
}
