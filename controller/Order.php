<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2019/11/9
 * Time: 10:00 下午
 */


namespace addons\unishop\controller;

use addons\unishop\extend\Hashids;
use addons\unishop\model\Area;
use addons\unishop\model\Config;
use addons\unishop\model\Evaluate;
use addons\unishop\model\Product;
use app\admin\model\unishop\Coupon as CouponModel;
use addons\unishop\model\DeliveryRule as DeliveryRuleModel;
use addons\unishop\model\OrderRefund;
use app\admin\model\unishop\OrderRefundProduct;
use think\Db;
use think\Exception;
use addons\unishop\model\Address as AddressModel;
use think\Hook;
use think\Loader;

/**
 * 订单相关接口
 * Class Order
 * @package addons\unishop\controller
 */
class Order extends Base
{

    /**
     * 允许频繁访问的接口
     * @var array
     */
    protected $frequently = ['getorders'];

    protected $noNeedLogin = ['count'];

    /**
     * 创建订单
     */
    public function create()
    {
        $productId = $this->request->post('id', 0);

        try {
            $user_id = $this->auth->id;

            // 单个商品
            if ($productId) {
                $productId = \addons\unishop\extend\Hashids::decodeHex($productId);
                $product = (new Product)->where(['id' => $productId, 'switch' => Product::SWITCH_ON, 'deletetime' => null])->find();
                /** 产品基础数据 **/
                $spec = $this->request->post('spec', '');
                $productData[0] = $product->getDataOnCreateOrder($spec);
            } else {
                // 多个商品
                $cart = $this->request->post('cart');
                $carts = (new \addons\unishop\model\Cart)
                    ->whereIn('id', $cart)
                    ->with(['product'])
                    ->order(['id' => 'desc'])
                    ->select();
                foreach ($carts as $cart) {
                    if ($cart->product instanceof Product) {
                        $productData[] = $cart->product->getDataOnCreateOrder($cart->spec ? $cart->spec : '', $cart->number);
                    }
                }
            }

            if (empty($productData) || !$productData) {
                $this->error(__('Product not exist'));
            }

            /** 默认地址 **/
            $address = (new AddressModel)->where(['user_id' => $user_id, 'is_default' => AddressModel::IS_DEFAULT_YES])->find();
            if ($address) {
                $area = (new Area)->whereIn('id', [$address->province_id, $address->city_id, $address->area_id])->column('name', 'id');
                $address = $address->toArray();
                $address['province']['name'] = $area[$address['province_id']];
                $address['city']['name'] = $area[$address['city_id']];
                $address['area']['name'] = $area[$address['area_id']];
            }


            /** 可用优惠券 **/
            $coupon = CouponModel::all(function ($query) {
                $time = time();
                $query
                    ->where(['switch' => CouponModel::SWITCH_ON])
                    ->where('starttime', '<', $time)
                    ->where('endtime', '>', $time);
            });
            if ($coupon) {
                $coupon = collection($coupon)->toArray();
            }


            /** 运费数据 **/
            $cityId = $address['city_id'] ? $address['city_id'] : 0;
            $delivery = (new DeliveryRuleModel())->getDelivetyByArea($cityId);

            foreach ($productData as &$product) {
                $product['image'] = Config::getImagesFullUrl($product['image']);
                $product['sales_price'] = round($product['sales_price'], 2);
                $product['market_price'] = round($product['market_price'], 2);
            }

            $this->success('', [
                'product' => $productData,
                'address' => $address,
                'coupon' => $coupon,
                'delivery' => $delivery['list']
            ]);

        } catch (Exception $e) {
            $this->error($e->getMessage(), false);
        }
    }

    /**
     * 提交订单
     */
    public function submit()
    {
        $data = $this->request->post();
        try {
            $validate = Loader::validate('\\addons\\unishop\\validate\\Order');
            if (!$validate->check($data, [], 'submit')) {
                throw new Exception($validate->getError());
            }

            Db::startTrans();

            // 判断创建订单的条件
            if (empty(Hook::get('create_order_before'))) {
                Hook::add('create_order_before', 'addons\\unishop\\behavior\\Order');
            }
            // 减少商品库存，增加"已下单未支付数量"
            if (empty(Hook::get('create_order_after'))) {
                Hook::add('create_order_after', 'addons\\unishop\\behavior\\Order');
            }

            $orderModel = new \addons\unishop\model\Order();
            $result = $orderModel->createOrder($this->auth->id, $data);

            Db::commit();

            $this->success('', $result);

        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), false);
        }
    }

    /**
     * 获取运费模板
     */
    public function getDelivery()
    {
        $cityId = $this->request->get('city_id', 0);
        $delivery = (new DeliveryRuleModel())->getDelivetyByArea($cityId);
        $this->success('', $delivery['list']);
    }

    /**
     * 获取订单信息
     */
    public function getOrders()
    {
        // 0=全部,1=待付款,2=待发货,3=待收货,4=待评价,5=售后
        $type = $this->request->get('type', 0);
        $page = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', 10);
        try {

            $orderModel = new \addons\unishop\model\Order();
            $result = $orderModel->getOrdersByType($this->auth->id, $type, $page, $pagesize);
            $this->success('', $result);

        } catch (Exception $e) {

            $this->error($e->getMessage());

        }

    }

    /**
     * 取消订单
     * 未支付的订单才叫取消，已支付的叫退货
     */
    public function cancel()
    {
        $order_id = $this->request->get('order_id', 0);
        $order_id = \addons\unishop\extend\Hashids::decodeHex($order_id);

        $orderModel = new \addons\unishop\model\Order();
        $order = $orderModel->where(['id' => $order_id, 'user_id' => $this->auth->id])->find();

        if (!$order) {
            $this->error(__('Order not exist'));
        }

        switch ($order['status']) {
            case \addons\unishop\model\Order::STATUS_REFUND:
                $this->error('此订单已退款，无法取消');
                break;
            case \addons\unishop\model\Order::STATUS_CANCEL:
                $this->error('此订单已取消, 无需再取消');
                break;
        }

        if ($order['have_paid'] != \addons\unishop\model\Order::PAID_NO) {
            $this->error('此订单已支付，无法取消');
        }

        if ($order['status'] == \addons\unishop\model\Order::STATUS_NORMAL && $order['have_paid'] == \addons\unishop\model\Order::PAID_NO) {
            $order->status = \addons\unishop\model\Order::STATUS_CANCEL;
            $order->save();
            $this->success('取消成功', true);
        }
    }

    /**
     * 删除订单
     * 只能删除已取消或已退货的订单
     */
    public function delete()
    {
        $order_id = $this->request->get('order_id', 0);
        $order_id = \addons\unishop\extend\Hashids::decodeHex($order_id);

        $orderModel = new \addons\unishop\model\Order();
        $order = $orderModel->where(['id' => $order_id, 'user_id' => $this->auth->id])->find();

        if (!$order) {
            $this->error(__('Order not exist'));
        }

        if ($order['status'] == \addons\unishop\model\Order::STATUS_NORMAL) {
            $this->error('只能删除已取消或已退货的订单');
        }

        if ($order['status'] == \addons\unishop\model\Order::STATUS_REFUND && $order['refund_status'] == \addons\unishop\model\Order::REFUND_STATUS_APPLY) {
            $this->error('订单退款中，不可删除订单');
        }

        $order->delete();
        $this->success('删除成功', true);
    }

    /**
     * 确认收货
     */
    public function received()
    {
        $order_id = $this->request->get('order_id', 0);
        $order_id = \addons\unishop\extend\Hashids::decodeHex($order_id);

        $orderModel = new \addons\unishop\model\Order();
        $order = $orderModel->where(['id' => $order_id, 'user_id' => $this->auth->id])->find();

        if (!$order) {
            $this->error(__('Order not exist'));
        }

        if ($order->have_delivered == 0) {
            $this->error('未发货，不能确认收货');
        }

        $order->have_received = time();
        $order->save();
        $this->success('已确认收货', true);

    }

    /**
     * 发表评论
     */
    public function comment()
    {
        $rate = $this->request->post('rate', 5);
        $anonymous = $this->request->post('anonymous', 0);
        $comment = $this->request->post('comment');
        $order_id = $this->request->post('order_id', 0);
        $order_id = \addons\unishop\extend\Hashids::decodeHex($order_id);
        $product_id = $this->request->post('product_id');
        $product_id = \addons\unishop\extend\Hashids::decodeHex($product_id);

        $orderProductModel = new \addons\unishop\model\OrderProduct();
        $orderProduct = $orderProductModel->where(['product_id' => $product_id, 'order_id' => $order_id, 'user_id' => $this->auth->id])->find();

        $orderModel = new \addons\unishop\model\Order();
        $order = $orderModel->where(['id' => $order_id, 'user_id' => $this->auth->id])->find();

        if (!$orderProduct || !$order) {
            $this->error(__('Order not exist'));
        }
        if ($order->have_received == $orderModel::RECEIVED_NO) {
            $this->error(__('未收货，不可评价'));
        }

        $result = false;
        try {

            $evaluate = new Evaluate();
            $evaluate->user_id = $this->auth->id;
            $evaluate->order_id = $order_id;
            $evaluate->product_id = $product_id;
            $evaluate->rate = $rate;
            $evaluate->anonymous = $anonymous;
            $evaluate->comment = $comment;
            $evaluate->spec = $orderProduct->spec;
            $result = $evaluate->save();

            if ($result) {
                $order->have_commented = time();
                $order->save();
            }

        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        if ($result !== false) {
            $this->success(__('Thanks for the evaluation'));
        } else {
            $this->error(__('Evaluation failure'));
        }

    }

    /**
     * 获取订单数量
     */
    public function count()
    {
        if (!$this->auth->isLogin()) {
            $this->error('');
        }
        $order = new \addons\unishop\model\Order();

        $list = $order
            ->where([
                'user_id' => $this->auth->id,
            ])
            ->where('status', '<>', \addons\unishop\model\Order::STATUS_CANCEL)
            ->where(function ($query) {
                $query
                    ->whereOr([
                        'have_paid' => \addons\unishop\model\Order::PAID_NO,
                        'have_delivered' => \addons\unishop\model\Order::DELIVERED_NO,
                        'have_received' => \addons\unishop\model\Order::RECEIVED_NO,
                        'have_commented' => \addons\unishop\model\Order::COMMENTED_NO
                    ])
                    ->whereOr('refund_status', '>', \addons\unishop\model\Order::REFUND_STATUS_NONE);
            })
            ->field('have_paid,have_delivered,have_received,have_commented,refund_status,had_refund')
            ->select();

        $data = [
            'unpaid' => 0,
            'undelivered' => 0,
            'unreceived' => 0,
            'uncomment' => 0,
            'refund' => 0
        ];
        foreach ($list as $item) {
            switch (true) {
                case $item['have_paid'] > 0 && $item['have_delivered'] > 0 && $item['have_received'] > 0 && $item['have_commented'] == 0 && $item['refund_status'] == 0:
                    $data['uncomment']++;
                    break;
                case $item['have_paid'] > 0 && $item['have_delivered'] > 0 && $item['have_received'] == 0 && $item['have_commented'] == 0 && $item['refund_status'] == 0:
                    $data['unreceived']++;
                    break;
                case $item['have_paid'] > 0 && $item['have_delivered'] == 0 && $item['have_received'] == 0 && $item['have_commented'] == 0 && $item['refund_status'] == 0:
                    $data['undelivered']++;
                    break;
                case $item['have_paid'] == 0 && $item['have_delivered'] == 0 && $item['have_received'] == 0 && $item['have_commented'] == 0 && $item['refund_status'] == 0:
                    $data['unpaid']++;
                    break;
                case $item['refund_status'] > 0 && $item['had_refund'] == 0 && $item['refund_status'] != 3:
                    $data['refund']++;
                    break;

            }
        }

        $this->success('', $data);
    }

    /**
     * 订单详情细节
     */
    public function detail()
    {
        $order_id = $this->request->get('order_id', 0);
        $order_id = \addons\unishop\extend\Hashids::decodeHex($order_id);

        try {
            $orderModel = new \addons\unishop\model\Order();
            $order = $orderModel
                ->with([
                    'products' => function ($query) {
                        $query->field('id,order_id,image,number,price,spec,title,product_id');
                    },
                    'extend' => function ($query) {
                        $query->field('id,order_id,address_id,address_json,express_number');
                    },
                    'evaluate' => function ($query) {
                        $query->field('id,order_id,product_id');
                    }
                ])
                ->where(['id' => $order_id, 'user_id' => $this->auth->id])->find();

            if ($order) {
                $order = $order->append(['state', 'paidtime', 'deliveredtime', 'receivedtime', 'commentedtime', 'pay_type_text', 'refund_status_text'])->toArray();

                // 快递单号
                $order['express_number'] = $order['extend']['express_number'];

                // 送货地址
                $address = json_decode($order['extend']['address_json'], true);
                $area = (new \addons\unishop\model\Area())
                    ->whereIn('id', [$address['province_id'], $address['city_id'], $address['area_id']])
                    ->column('name', 'id');
                $delivery['username'] = $address['name'];
                $delivery['mobile'] = $address['mobile'];
                $delivery['address'] = $area[$address['province_id']] . ' ' . $area[$address['city_id']] . ' ' . $area[$address['area_id']] . ' ' . $address['address'];
                $order['delivery'] = $delivery;

                // 是否已评论
                $evaluate = array_column($order['evaluate'], 'product_id');
                foreach ($order['products'] as &$product) {
                    $product['image'] = Config::getImagesFullUrl($product['image']);
                    if (in_array($product['id'], $evaluate)) {
                        $product['evaluate'] = true;
                    } else {
                        $product['evaluate'] = false;
                    }
                }

                unset($order['evaluate']);
                unset($order['extend']);
            }

            $this->success('', $order);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

    }

    /**
     * 申请售后信息
     */
    public function refundInfo()
    {
        $order_id = $this->request->post('order_id');
        $order_id = \addons\unishop\extend\Hashids::decodeHex($order_id);

        $orderModel = new \addons\unishop\model\Order();
        $order = $orderModel
            ->with([
                'products' => function ($query) {
                    $query->field('id,order_id,image,number,price,spec,title,product_id,(1) as choose');
                },
                'refund',
                'refundProducts'
            ])
            ->field('id,status,total_price,delivery_price,have_commented,have_delivered,have_paid,have_received,refund_status')
            ->where(['id' => $order_id, 'user_id' => $this->auth->id])->find();

        if (!$order) {
            $this->error(__('Order not exist'));
        }

        $order = $order->append(['refund_status_text'])->toArray();

        foreach ($order['products'] as &$product) {
            $product['image'] = Config::getImagesFullUrl($product['image']);
            $product['choose'] = 0;

            // 如果是已提交退货的全选
            if ($order['status'] == \addons\unishop\model\Order::STATUS_REFUND) {
                foreach ($order['refund_products'] as $refundProduct) {
                    if ($product['order_product_id'] == $refundProduct['order_product_id']) {
                        $product['choose'] = 1;
                    }
                }
            }
        }

        unset($order['refund_products']);

        $this->success('', $order);
    }


    /**
     * 申请售后
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function refund()
    {
        $order_id = $this->request->post('order_id');
        $order_id = Hashids::decodeHex($order_id);
        $orderModel = new \addons\unishop\model\Order();
        $order = $orderModel->where(['id' => $order_id, 'user_id' => $this->auth->id])->find();

        if (!$order) {
            $this->error(__('Order not exist'));
        }
        if ($order['have_paid'] == 0) {
            $this->error(__('订单未支付，可直接取消，无需申请售后'));
        }

        $amount = $this->request->post('amount', 0);
        $serviceType = $this->request->post('service_type');
        $receivingStatus = $this->request->post('receiving_status');
        $reasonType = $this->request->post('reason_type');
        $refundExplain = $this->request->post('refund_explain');
        $orderProductId = $this->request->post('order_product_id');

        if (!$orderProductId) {
            $this->error(__('Please select goods'));
        }
        if (!in_array($receivingStatus, [OrderRefund::UNRECEIVED, OrderRefund::RECEIVED])) {
            $this->error(__('Please select goods status'));
        }
        if (!in_array($serviceType, [OrderRefund::TYPE_REFUND_NORETURN, OrderRefund::TYPE_REFUND_RETURN, OrderRefund::TYPE_EXCHANGE])) {
            $this->error(__('Please select service type'));
        }
        if (in_array($serviceType, [OrderRefund::TYPE_REFUND_NORETURN, OrderRefund::TYPE_REFUND_RETURN]) && $order['total_price'] > 0) {
            if (!$amount) {
                $this->error(__('Please fill in the refund amount'));
            }
        }

        try {
            Db::startTrans();

            $orderRefund = new OrderRefund();
            $orderRefund->user_id = $this->auth->id;
            $orderRefund->order_id = $order_id;
            $orderRefund->receiving_status = $receivingStatus;
            $orderRefund->service_type = $serviceType;
            $orderRefund->reason_type = $reasonType;
            $orderRefund->amount = $amount;
            $orderRefund->refund_explain = $refundExplain;
            $orderRefund->save();

            $productIdArr = explode(',', $orderProductId);
            $refundProduct = [];
            foreach ($productIdArr as $orderProductId) {
                $tmp['order_product_id'] = $orderProductId;
                $tmp['order_id'] = $order_id;
                $tmp['user_id'] = $this->auth->id;
                $tmp['refund_id'] = $orderRefund['id'];
                $tmp['createtime'] = time();
                $refundProduct[] = $tmp;
            }
            (new OrderRefundProduct)->insertAll($refundProduct);

            $order->status = \addons\unishop\model\Order::STATUS_REFUND;
            $order->refund_status = \addons\unishop\model\Order::REFUND_STATUS_APPLY;
            $order->save();

            Db::commit();
            $this->success(__('Commit'), 1);
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 售后发货
     */
    public function refundDelivery()
    {
        $orderId = $this->request->post('order_id');
        $expressNumber = $this->request->post('express_number');

        if (!$expressNumber) {
            $this->error(__('Please fill in the express number'));
        }

        $orderId = Hashids::decodeHex($orderId);
        $orderModel = new \addons\unishop\model\Order();
        $order = $orderModel
            ->where(['id' => $orderId, 'user_id' => $this->auth->id])
            ->with(['refund'])->find();

        if (!$order || !$order->refund) {
            $this->error(__('Order not exist'));
        }
        try {
            Db::startTrans();

            $order->refund->express_number = $expressNumber;

            $order->refund_status = \addons\unishop\model\Order::REFUND_STATUS_APPLY;

            if ($order->refund->save() && $order->save()) {
                Db::commit();
                $this->success('', 1);
            } else {
                throw new Exception(__('Operation failed'));
            }

        } catch (Exception $e) {
            Db::rollback();
            $this->success($e->getMessage());
        }


    }

}
