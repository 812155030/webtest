<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/5/6
 * Time: 12:17 AM
 */


namespace tests\unishop\controller;


use addons\unishop\controller\Address;
use addons\unishop\controller\Cart;
use addons\unishop\controller\Order;
use addons\unishop\controller\Pay;
use addons\unishop\controller\Product;
use addons\unishop\extend\Hashids;
use addons\unishop\extend\PhpunitFunctionCustomize;
use addons\unishop\model\Coupon;
use addons\unishop\model\OrderRefund;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    use PhpunitFunctionCustomize;

    /**
     * @test
     */
    public function getProduct()
    {
        $this->userLogin();
        $products = (new \addons\unishop\model\Product)->where(['switch' => \addons\unishop\model\Product::SWITCH_ON])->field('id,specTableList,use_spec')->select();
        if ($products) {
            return collection($products)->append(['spec_table_list'])->toArray();
        }
        return [];
    }

    /**
     * @test
     */
    public function getAddress()
    {
        $contents = $this->request(Address::class, 'add', [
            'name' => 'unishop',
            'mobile' => self::$mobile,
            'address' => 'unishop Address',
            'is_default' => 1,
            'province_id' => 1,
            'city_id' => 2,
            'area_id' => 3,
        ]);
        $this->assertIsArray($contents);
        $this->assertSame(1, $contents['code']);
    }

    /**
     * @test
     * @depends getProduct
     */
    public function addToCartAndCreatingOrder(array $products)
    {
        foreach ($products as $product) {
            $params['id'] = $product['product_id'];
            $params['spec'] = '';
            if ($product['use_spec'] == \addons\unishop\model\Product::SPEC_ON) {
                foreach ($product['spec_table_list'] as $row) {
                    $params['spec'] = implode(',', $row['value']);
                    $contents = $this->request(Cart::class, 'add', $params, 'get');
                    $this->assertArrayHasKey('code', $contents);
                    $this->assertArrayHasKey('data', $contents);

                    $this->create(['id' => $product['product_id'], 'spec' => $params['spec']]);

                }

            } else {
                unset($params['spec']);
                $contents = $this->request(Cart::class, 'add', $params, 'get');
                $this->assertArrayHasKey('code', $contents);
                $this->assertArrayHasKey('data', $contents);

                $this->create(['id' => $product['product_id']]);
            }
        }
    }

    /**
     * @test
     */
    public function getCartList()
    {
        $contents = $this->request(Cart::class, 'index');
        $this->assertIsArray($contents);
        $this->assertSame(1, $contents['code']);
        $this->assertIsArray($contents['data']);
        if (count($contents['data']) > 0) {
            $cartIds = array_column($contents['data'], 'cart_id');
            $this->create(['cart' => implode(',', $cartIds)]);
        }
    }

    public function create($params = [])
    {
        if (!empty($params)) {
            $contents = $this->request(Order::class, 'create', $params);
            //print_r($contents);
            $this->assertIsArray($contents);
            $this->assertArrayHasKey('code', $contents);
            $this->assertArrayHasKey('data', $contents);
            if ($contents['code'] == 1) {
                $this->assertIsArray($contents['data']);
                $this->assertArrayHasKey('product', $contents['data']);
                $this->assertArrayHasKey('address', $contents['data']);
                $this->assertArrayHasKey('coupon', $contents['data']);
                $this->assertArrayHasKey('delivery', $contents['data']);

                // 验证商品
                $total_price = 0;
                $productIdToCreate = $specToCreate = $numberToCreate = [];
                foreach ($contents['data']['product'] as $product) {
                    $this->assertNotEmpty($product['image']);
                    $this->assertGreaterThanOrEqual(0, $product['market_price']);
                    $this->assertGreaterThanOrEqual(0, $product['sales']);
                    $this->assertGreaterThanOrEqual(0, $product['sales_price']);
                    $this->assertGreaterThanOrEqual(0, $product['stock']);
                    $this->assertGreaterThanOrEqual(1, $product['number']);
                    $this->assertNotEmpty($product['title']);
                    $this->assertNotEmpty($product['id']);
                    $this->assertArrayHasKey('spec', $product);
                    $total_price += $product['sales_price'];

                    $productIdToCreate[] = $product['id'];
                    $numberToCreate[] = $product['number'];
                    $specToCreate[] = str_replace(',', '|', $product['spec']);
                }

                // 验证地址
                $address = $contents['data']['address'];
                $this->assertNotEmpty($address['id']);
                $this->assertNotEmpty($address['name']);
                $this->assertNotEmpty($address['mobile']);
                $this->assertNotEmpty($address['province_id']);
                $this->assertNotEmpty($address['city_id']);
                $this->assertNotEmpty($address['area_id']);
                $this->assertNotEmpty($address['province']['name']);
                $this->assertNotEmpty($address['city']['name']);
                $this->assertNotEmpty($address['area']['name']);

                // 验证优惠券
                $coupons = $contents['data']['coupon'];
                $useCouponId = 0;
                if (count($coupons)) {
                    foreach ($coupons as $coupon) {
                        $this->assertNotEmpty($coupon['id']);
                        $this->assertNotEmpty($coupon['title']);
                        $this->assertSame(1, $coupon['switch']);
                        $this->assertGreaterThanOrEqual(0, $coupon['least']);
                        $this->assertGreaterThan($coupon['value'], $coupon['least']);
                        $this->assertGreaterThan($coupon['starttime'], time());
                        $this->assertGreaterThan(time(), $coupon['endtime']);
                        $this->assertEmpty($coupon['deletetime']);
                        if ($total_price > $coupon['least']) {
                            $useCouponId = $coupon['id'];
                        }
                    }
                }

                // 验证物流
                $deliverys = $contents['data']['delivery'];
                if (count($deliverys) > 0) {
                    foreach ($deliverys as $delivery) {
                        $this->assertNotEmpty($delivery['id']);
                        $this->assertNotEmpty($delivery['name']);
                        $this->assertNotEmpty($delivery['type']);
                        $this->assertGreaterThan(0, $delivery['min']);
                        $this->assertGreaterThan(0, $delivery['first']);
                        $this->assertGreaterThan(0, $delivery['additional']);
                        $this->assertGreaterThanOrEqual(0, $delivery['first_fee']);
                        $this->assertGreaterThanOrEqual(0, $delivery['additional_fee']);

                        // 创建订单
                        $this->submit([
                            'city_id' => $address['city_id'],
                            'address_id' => $address['id'],
                            'delivery_id' => $delivery['id'],
                            'coupon_id' => $useCouponId,
                            'remark' => 'unishop_remark',
                            'product_id' => implode(',', $productIdToCreate),
                            'spec' => implode(',', $specToCreate),
                            'number' => implode(',', $numberToCreate),
                        ]);
                    }
                }

            }
        }
    }

    public function submit($params = [])
    {
        $this->userLogin();
        $contents = $this->request(Order::class, 'submit', $params);
        $this->assertIsArray($contents);
        $this->assertArrayHasKey('code', $contents);
        $this->assertArrayHasKey('data', $contents);
        if ($contents['code'] == 1) {
            $this->assertNotEmpty($contents['data']['order_id']);
            $this->assertNotEmpty($contents['data']['out_trade_no']);

            // 测试货到付款
            $contents = $this->request(Pay::class, 'offline', ['order_id' => $contents['data']['order_id']], 'get');
            $this->assertIsArray($contents);
            $this->assertArrayHasKey('code', $contents);
            $this->assertArrayHasKey('data', $contents);
        }
    }

    /**
     * @test
     */
    public function getDelivery($city_id = 3)
    {
        $contents = $this->request(Order::class, 'getDelivery', ['city_id' => $city_id], 'get');
        $this->assertIsArray($contents);
        $this->assertSame(1, $contents['code']);
        $this->assertArrayHasKey('data', $contents);
        if (count($contents['data']) > 0) {
            foreach ($contents['data'] as $delivery) {
                $this->assertNotEmpty($delivery['id']);
                $this->assertNotEmpty($delivery['name']);
                $this->assertNotEmpty($delivery['type']);
                $this->assertGreaterThan(0, $delivery['min']);
                $this->assertGreaterThan(0, $delivery['first']);
                $this->assertGreaterThan(0, $delivery['additional']);
                $this->assertGreaterThanOrEqual(0, $delivery['first_fee']);
                $this->assertGreaterThanOrEqual(0, $delivery['additional_fee']);
            }
        }

    }


    public function getOrdersProvider()
    {
        return [
            [0, 1, 10],
            [1, 1, 10]
        ];
    }

    /**
     * @test
     * @dataProvider getOrdersProvider
     */
    public function getOrders($type, $page, $pagesize)
    {
        $this->userLogin();
        $contents = $this->request(Order::class, 'getOrders', ['type' => $type, 'page' => $page, 'pagesize' => $pagesize], 'get');
        $this->assertIsArray($contents);

        foreach ($contents['data'] as $order) {
            $this->assertNotEmpty($order['out_trade_no']);
            $this->assertGreaterThanOrEqual(0, $order['order_price']);
            $this->assertGreaterThanOrEqual(0, $order['discount_price']);
            $this->assertGreaterThanOrEqual(0, $order['delivery_price']);
            $this->assertGreaterThanOrEqual(0, $order['total_price']);
            $this->assertGreaterThanOrEqual($order['total_price'], $order['order_price']);
            $this->assertTrue(in_array($order['pay_type'], [
                \addons\unishop\model\Order::PAY_ONLINE,
                \addons\unishop\model\Order::PAY_OFFLINE,
                \addons\unishop\model\Order::PAY_WXPAY,
                \addons\unishop\model\Order::PAY_ALIPAY
            ]));
            $this->assertArrayHasKey('remark', $order);
            $this->assertTrue(in_array($order['status'], [
                \addons\unishop\model\Order::STATUS_REFUND,
                \addons\unishop\model\Order::STATUS_CANCEL,
                \addons\unishop\model\Order::STATUS_NORMAL
            ]));
            $this->assertGreaterThanOrEqual(0, $order['have_paid']);
            $this->assertGreaterThanOrEqual(0, $order['have_delivered']);
            $this->assertGreaterThanOrEqual(0, $order['have_received']);
            $this->assertGreaterThanOrEqual(0, $order['have_commented']);
            $this->assertGreaterThanOrEqual(0, $order['refund_status']);
            $this->assertGreaterThanOrEqual(0, $order['had_refund']);
            $this->assertNotEmpty($order['createtime']);

            $this->assertIsArray($order['products']);
            foreach ($order['products'] as $product) {
                $this->assertNotEmpty($product['id']);
                $this->assertNotEmpty($product['title']);
                $this->assertNotEmpty($product['image']);
                $this->assertGreaterThanOrEqual(1, $product['number']);
                $this->assertGreaterThanOrEqual(0, $product['price']);
                $this->assertArrayHasKey('spec', $product);
                $this->assertNotEmpty($product['order_product_id']);
                $this->assertArrayHasKey('evaluate', $product);
                $this->assertArrayHasKey('refund', $product);
            }

            $this->assertArrayHasKey('extend', $order);
            $this->assertArrayHasKey('express_number', $order['extend']);

            $this->assertNotEmpty($order['order_id']);
            $this->assertTrue(in_array($order['state'], [
                \addons\unishop\model\Order::TYPE_ALL,
                \addons\unishop\model\Order::TYPE_PAY,
                \addons\unishop\model\Order::TYPE_DELIVES,
                \addons\unishop\model\Order::TYPE_RECEIVE,
                \addons\unishop\model\Order::TYPE_COMMENT,
                \addons\unishop\model\Order::TYPE_REFUND,
                \addons\unishop\model\Order::TYPE_REFUSE,
                \addons\unishop\model\Order::TYPE_OFF
            ]));

            $this->assertArrayHasKey('refund_status_text', $order);


            if ($order['have_paid'] > 0) {
                // 已支付
                // 改订单改成已发货， 尝试收货,尝试取消订单并删除

                $row = \addons\unishop\model\Order::get(Hashids::decodeHex($order['order_id']), ['extend']);
                if ($row) {
                    $res1 = $row->save(['have_delivered' => time()]);
                    $res2 = $row->extend->save(['express_number' => self::$mobile]);

                    $this->received($order['order_id'], array_column($order['products'], 'id'));
                    $this->cancel($order['order_id']);
                }
            } else {
                // 未支付
                // 尝试收货,尝试取消订单并删除
                $this->received($order['order_id'], array_column($order['products'], 'id'));
                $this->cancel($order['order_id']);
            }
        }

    }

    public function cancel($orderId)
    {
        $contents = $this->request(Order::class, 'cancel', ['order_id' => $orderId], 'get');
        $this->assertIsArray($contents);
        $this->assertArrayHasKey('code', $contents);
        $this->assertArrayHasKey('data', $contents);
        $this->delete($orderId);
    }

    public function delete($orderId)
    {
        $contents = $this->request(Order::class, 'delete', ['order_id' => $orderId], 'get');
        $this->assertIsArray($contents);
        $this->assertArrayHasKey('code', $contents);
        $this->assertArrayHasKey('data', $contents);
    }

    public function received($orderId, $productIds)
    {
        $contents = $this->request(Order::class, 'received', ['order_id' => $orderId], 'get');
        $this->assertIsArray($contents);
        $this->assertArrayHasKey('code', $contents);
        $this->assertArrayHasKey('data', $contents);

        $this->comment($orderId, $productIds);
    }

    public function comment($orderId, $productIds)
    {
        foreach ($productIds as $productId) {
            $contents = $this->request(Order::class, 'comment', [
                'rate' => 5,
                'anonymous' => 1,
                'comment' => 'unishop_comment',
                'order_id' => $orderId,
                'product_id' => $productId
            ]);
            $this->assertIsArray($contents);

            $this->assertArrayHasKey('code', $contents);
            $this->assertArrayHasKey('data', $contents);

            $this->detail($orderId);
            $this->refundInfo($orderId);
        }
    }


    public function detail($orderId)
    {
        $contents = $this->request(Order::class, 'detail', ['order_id' => $orderId], 'get');
        $this->assertIsArray($contents);

        $this->assertSame(1, $contents['code']);
        $this->assertIsArray($contents['data']);

        $order = $contents['data'];
        $this->assertNotEmpty($order['out_trade_no']);
        $this->assertGreaterThanOrEqual(0, $order['order_price']);
        $this->assertGreaterThanOrEqual(0, $order['discount_price']);
        $this->assertGreaterThanOrEqual(0, $order['delivery_price']);
        $this->assertGreaterThanOrEqual(0, $order['total_price']);
        $this->assertGreaterThanOrEqual($order['total_price'], $order['order_price']);
        $this->assertTrue(in_array($order['pay_type'], [
            \addons\unishop\model\Order::PAY_ONLINE,
            \addons\unishop\model\Order::PAY_OFFLINE,
            \addons\unishop\model\Order::PAY_WXPAY,
            \addons\unishop\model\Order::PAY_ALIPAY,
        ]));
        $this->assertArrayHasKey('remark', $order);
        $this->assertArrayHasKey('status', $order);
        $this->assertGreaterThanOrEqual(0, $order['have_paid']);
        $this->assertGreaterThanOrEqual(0, $order['have_delivered']);
        $this->assertGreaterThanOrEqual(0, $order['have_received']);
        $this->assertGreaterThanOrEqual(0, $order['have_commented']);
        $this->assertGreaterThanOrEqual(0, $order['had_refund']);
        $this->assertTrue(in_array($order['refund_status'], [
            \addons\unishop\model\Order::REFUND_STATUS_NONE,
            \addons\unishop\model\Order::REFUND_STATUS_APPLY,
            \addons\unishop\model\Order::REFUND_STATUS_DELIVERY,
            \addons\unishop\model\Order::REFUND_STATUS_AGREE,
            \addons\unishop\model\Order::REFUND_STATUS_REFUSE,
        ]));
        $this->assertNotEmpty($order['createtime']);

        $this->assertIsArray($order['products']);
        foreach ($order['products'] as $product) {
            $this->assertNotEmpty($product['id']);
            $this->assertNotEmpty($product['image']);
            $this->assertGreaterThanOrEqual(1, $product['number']);
            $this->assertGreaterThanOrEqual(0, $product['price']);
            $this->assertArrayHasKey('spec', $product);
            $this->assertNotEmpty($product['title']);
            $this->assertNotEmpty($product['order_product_id']);
            $this->assertArrayHasKey('evaluate', $product);
        }

        $this->assertArrayHasKey('state', $order);
        $this->assertArrayHasKey('paidtime', $order);
        $this->assertArrayHasKey('deliveredtime', $order);
        $this->assertArrayHasKey('receivedtime', $order);
        $this->assertArrayHasKey('commentedtime', $order);
        $this->assertNotEmpty($order['pay_type_text']);
        $this->assertArrayHasKey('refund_status_text', $order);
        $this->assertArrayHasKey('express_number', $order);

        $this->assertIsArray($order['delivery']);
        $this->assertNotEmpty($order['delivery']['username']);
        $this->assertNotEmpty($order['delivery']['mobile']);
        $this->assertNotEmpty($order['delivery']['address']);


    }

    public function refundInfo($orderId)
    {
        $this->userLogin();
        $contents = $this->request(Order::class, 'refundInfo', [
            'order_id' => $orderId
        ]);

        $this->assertIsArray($contents);
        $this->assertArrayHasKey('code', $contents);
        $this->assertIsArray($contents['data']);
        $this->assertArrayHasKey('status', $contents['data']);
        $this->assertArrayHasKey('total_price', $contents['data']);
        $this->assertArrayHasKey('delivery_price', $contents['data']);
        $this->assertArrayHasKey('have_commented', $contents['data']);
        $this->assertArrayHasKey('have_delivered', $contents['data']);
        $this->assertArrayHasKey('have_paid', $contents['data']);
        $this->assertArrayHasKey('have_received', $contents['data']);
        $this->assertArrayHasKey('refund_status', $contents['data']);
        $this->assertArrayHasKey('refund', $contents['data']);
        $this->assertArrayHasKey('refund_status_text', $contents['data']);

        $this->assertIsArray($contents['data']['products']);

        $order_product_id = [];
        foreach ($contents['data']['products'] as $product) {
            $this->assertNotEmpty($product['id']);
            $this->assertNotEmpty($product['image']);
            $this->assertNotEmpty($product['number']);
            $this->assertGreaterThanOrEqual(0, $product['price']);
            $this->assertArrayHasKey('spec', $product);
            $this->assertNotEmpty($product['title']);
            $this->assertArrayHasKey('choose', $product);
            $this->assertNotEmpty($product['order_product_id']);
            $order_product_id[] = $product['order_product_id'];
        }

        $this->refund($orderId, implode(',', $order_product_id));
    }

    public function refund($orderId, $order_product_id)
    {
        $contents = $this->request(Order::class, 'refund', [
            'order_id' => $orderId,
            'amount' => 100,
            'service_type' => OrderRefund::TYPE_REFUND_NORETURN,
            'receiving_status' => OrderRefund::RECEIVED,
            'reason_type' => '其他',
            'refund_explain' => 'unishop',
            'order_product_id' => $order_product_id
        ]);

        $this->assertIsArray($contents);
        $this->assertArrayHasKey('code', $contents);
        $this->assertArrayHasKey('data', $contents);
        $this->assertSame(1, $contents['code']);
        if ($contents['code'] == 1) {
            $this->refundDelivery($orderId);
        }
    }


    public function refundDelivery($orderId)
    {
        $contents = $this->request(Order::class, 'refundDelivery', [
            'order_id' => $orderId,
            'express_number' => 'unishop'
        ]);
        $this->assertIsArray($contents);
        $this->assertArrayHasKey('code', $contents);
        $this->assertArrayHasKey('data', $contents);

    }



    /**
     * @test
     */
    public function countTest()
    {
        $this->userLogin();
        $contents = $this->request(Order::class, 'count');
        $this->assertIsArray($contents);
        $this->assertSame(1, $contents['code']);
        $this->assertIsArray($contents['data']);
        $this->assertGreaterThanOrEqual(0, $contents['data']['unpaid']);
        $this->assertGreaterThanOrEqual(0, $contents['data']['undelivered']);
        $this->assertGreaterThanOrEqual(0, $contents['data']['unreceived']);
        $this->assertGreaterThanOrEqual(0, $contents['data']['uncomment']);
    }

    public static function tearDownAfterClass()
    {
        $user = (new self())->userLogin()['data'];

        \addons\unishop\model\Address::destroy(['user_id' => $user['user_id']]);
        \addons\unishop\model\Cart::destroy(['user_id' => $user['user_id']]);
        \addons\unishop\model\Order::destroy(['user_id' => $user['user_id']], true);
        \addons\unishop\model\OrderProduct::destroy(['user_id' => $user['user_id']]);
        \addons\unishop\model\OrderExtend::destroy(['user_id' => $user['user_id']]);
        \addons\unishop\model\OrderRefund::destroy(['user_id' => $user['user_id']]);
        \addons\unishop\model\OrderRefundProduct::destroy(['user_id' => $user['user_id']]);
        \addons\unishop\model\Cart::destroy(['user_id' => $user['user_id']]);
        \addons\unishop\model\Evaluate::destroy(['user_id' => $user['user_id']], true);

    }
}
