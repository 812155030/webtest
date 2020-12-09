<?php

namespace addons\unishop\behavior;

use addons\unishop\extend\Ali;
use addons\unishop\extend\Hashids;
use addons\unishop\extend\Wechat;
use addons\unishop\model\Address;
use addons\unishop\model\Config;
use addons\unishop\model\DeliveryRule as DeliveryRuleModel;
use addons\unishop\model\OrderProduct;
use addons\unishop\model\Product;
use app\admin\model\unishop\Coupon;
use think\Db;
use think\Exception;

/**
 * 订单相关行为
 * Class Order
 * @package addons\unishop\behavior
 */
class Order
{
    /**
     * 创建订单之后
     * 行为一：根据订单减少商品库存 增加"已下单未支付数量"
     * 行为二：如果选了购物车的就删除购物车的信息
     * @param array $params 商品属性
     * @param array $extra [specNumber] => ['spec1' => 'number1','spec2' => 'number2']
     */
    public function createOrderAfter(&$params, $extra)
    {
        // 行为一
        $key = 0;
        $productExtend = new \addons\unishop\extend\Product;
        $prefix = \think\Config::get('database.prefix');

        if (Config::isPessimism()) {
            // 悲观锁
            foreach ($extra['specNumber'] as $spec => $number) {
                $result = 0;
                if (is_numeric($spec) && $params[$key]['use_spec'] == Product::SPEC_OFF) {
                    $result = Db::execute('UPDATE ' . $prefix . "unishop_product SET no_buy_yet = no_buy_yet+{$number}, real_sales = real_sales+{$number}, stock = stock-{$number} WHERE id = {$params[$key]['id']}");
                } else if ($params[$key]['use_spec'] == Product::SPEC_ON) {
                    $info = $productExtend->getBaseData($params[$key], $spec);

                    // mysql<5.7.13时用
                    //if (mysql < 5.7.13) {
                    $spec = str_replace(',', '","', $spec);
                    $search = '"stock":"' . $info['stock'] . '","value":["' . $spec . '"]';
                    $stock = $info['stock'] - $number;
                    $replace = '"stock":\"' . $stock . '\","value":["' . $spec . '"]';
                    $sql = 'UPDATE ' . $prefix . "unishop_product SET no_buy_yet = no_buy_yet+{$number}, stock = stock-{$number}, real_sales = real_sales+{$number} ,`specTableList` = REPLACE(specTableList,'$search','$replace') WHERE id = {$params[$key]['id']}";
                    $result = Db::execute($sql);
                    //}

                    //下面语句直接操作JSON
                    //if (mysql >= 5.7.13) {
                    //$info['stock'] -= $number;
                    //$result = Db::execute("UPDATE fa_unishop_product SET no_buy_yet = no_buy_yet+{$number}, real_sales = real_sales+{$number}, stock = stock-{$number},specTableList = JSON_REPLACE(specTableList, '$[{$info['key']}].stock', {$info['stock']}) WHERE id = {$params[$key]['id']}");
                    //}
                }
                if ($result == 0) { // 锁生效
                    throw new Exception('下单失败,请重试');
                }
                $key++;
            }
        } else {
            // 乐观锁
            foreach ($extra['specNumber'] as $spec => $number) {
                $result = 0;
                if (is_numeric($spec) && $params[$key]['use_spec'] == Product::SPEC_OFF) {
                    $result = Db::execute('UPDATE ' . $prefix . "unishop_product SET no_buy_yet = no_buy_yet+{$number}, real_sales = real_sales+{$number}, stock = stock-{$number} WHERE id = {$params[$key]['id']} AND stock = {$params[$key]['stock']}");
                } else if ($params[$key]['use_spec'] == Product::SPEC_ON) {
                    $info = $productExtend->getBaseData($params[$key], $spec);

                    // mysql<5.7.13时用
                    //if (mysql < 5.7.13) {
                    $spec = str_replace(',', '","', $spec);
                    $search = '"stock":"' . $info['stock'] . '","value":["' . $spec . '"]';
                    $stock = $info['stock'] - $number;
                    $replace = '"stock":\"' . $stock . '\","value":["' . $spec . '"]';
                    $sql = 'UPDATE ' . $prefix . "unishop_product SET no_buy_yet = no_buy_yet+{$number}, real_sales = real_sales+{$number}, stock = stock-{$number},`specTableList` = REPLACE(specTableList,'$search','$replace') WHERE id = {$params[$key]['id']} AND stock = {$params[$key]['stock']}";
                    $result = Db::execute($sql);
                    //}

                    //下面语句直接操作JSON
                    //if (mysql >= 5.7.13) {
                    //$info['stock'] -= $number;
                    //$result = Db::execute("UPDATE fa_unishop_product SET no_buy_yet = no_buy_yet+{$number}, real_sales = real_sales+{$number}, stock = stock-{$number},specTableList = JSON_REPLACE(specTableList, '$[{$info['key']}].stock', {$info['stock']}) WHERE id = {$params[$key]['id']} AND stock = {$params[$key]['stock']}");
                    //}
                }
                if ($result == 0) { // 锁生效
                    throw new Exception('下单失败,请重试');
                }
                $key++;
            }
        }

        // 行为二
        if (!empty($extra['cart'])) {
            $cart = $extra['cart'];
            Db::execute('DELETE FROM ' . $prefix . "unishop_cart WHERE id IN ($cart) AND user_id = $extra[userId]");
        }

        // More ...
    }


    /**
     * 检查是否符合创建订单的条件
     * 条件1：商品是否存在
     * 条件2：商品库存情况
     * 条件3：收货地址是否在范围内
     * 条件4：是否使用优惠券，优惠券能否可用
     * @param array $params
     * @param array $extra
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public function createOrderBefore(&$params, $extra)
    {

        $specs = explode(',', $extra['spec']);
        foreach ($specs as &$spec) {
            $spec = str_replace('|', ',', $spec);
        }
        $numbers = explode(',', $extra['number']);
        $productIds = explode(',', $extra['product_id']);

        if (count($specs) !== count($numbers) || count($specs) !== count($productIds)) {
            throw new Exception(__('Parameter error'));
        }

        // 订单价格
        $orderPrice = 0;

        // 条件一
        $products = [];
        foreach ($productIds as $key => &$productId) {
            $productId = Hashids::decodeHex($productId);
            $products[$key] = Db::name('unishop_product')
                ->where(['id' => $productId, 'switch' => Product::SWITCH_ON])
                ->lock(Config::isPessimism()) // Todo 是否使用悲观锁
                ->find();
            if (!$products[$key]) {
                throw new Exception(__('There are not exist or Offline'));
            }
        }
        if (count($products) == 0 || count($productIds) != count($products)) {
            throw new Exception(__('There are offline product'));
        }
        // 从购物车下单多个商品时，有同一个商品的不同规格导致减库存问题
        if (count($productIds) > 0) {
            $reduceStock = [];
            foreach ($products as $key => $value) {
                if (!isset($reduceStock[$value['id']])) {
                    $reduceStock[$value['id']] = $numbers[$key];
                } else {
                    $products[$key]['stock'] -= $reduceStock[$value['id']];
                    $reduceStock[$value['id']] += $numbers[$key];
                }
            }
        }

        // 条件二
        foreach ($products as $key => $product) {
            $productInfo = (new \addons\unishop\extend\Product())->getBaseData($product, $specs[$key] ? $specs[$key] : '');
            if ($productInfo['stock'] < $numbers[$key]) {
                throw new Exception(__('Insufficient inventory，%s pieces left', $productInfo['stock']));
            }
            $orderPrice = bcadd($orderPrice, bcmul($productInfo['sales_price'], $numbers[$key], 2), 2);
            $baseProductInfo[] = $productInfo;
        }

        // 条件三
        $delivery = (new DeliveryRuleModel())->cityInScopeOfDelivery($extra['city_id'], $extra['delivery_id']);
        if (!$delivery) {
            throw new Exception(__('Your receiving address is not within the scope of delivery'));
        } else {
            if ($delivery['min'] > array_sum($numbers)) {
                throw new Exception(__('You must purchase at least %s item to use this shipping method', $delivery['min']));
            }
        }
        $address = (new Address)->where(['id' => $extra['address_id'], 'user_id' => $extra['userId']])->find();
        if (!$address) {
            throw new Exception(__('Address not exist'));
        }

        // 条件四
        if ($extra['coupon_id']) {
            $coupon = Coupon::get($extra['coupon_id']);
            if ($coupon['switch'] == Coupon::SWITCH_OFF || $coupon['deletetime'] || $coupon['starttime'] > time() || $coupon['endtime'] < time()) {
                throw new Exception('此优惠券不可用');
            }
            // 至少消费多少钱
            if ($coupon['least'] > $orderPrice) {
                throw new Exception('选中的优惠券不满足使用条件');
            }
        } else {
            $coupon = [];
        }

        $params = [$products, $delivery, $coupon, $baseProductInfo, $address, $orderPrice, $specs, $numbers];
    }

    /**
     * 支付成功
     * 行为一：更改订单的支付状态，更新支付时间。
     * 行为二：减少商品的已下单但未支付的数量
     * @param $params
     * @param $extra
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function paidSuccess(&$params, $extra)
    {
        $order = &$params;
        $order->have_paid = time();// 更新支付时间为当前时间
        $order->pay_type = $extra['pay_type'];
        $order->save();

        $orderProductModel = new OrderProduct();
        $orderProducts = $orderProductModel
            ->with('product')
            ->where(['order_id' => $order->id])
            ->select();

        foreach ($orderProducts as $product) {
            if (isset($product->product)) {
                $product->product->no_buy_yet -= $product->number;
                $product->product->save();
            }
        }

        // More ...
    }

    /**
     * 支付失败
     * @param $params
     */
    public function paidFail(&$params)
    {
        $order = &$params;
        $order->have_paid = \addons\unishop\model\Order::PAID_NO;
        $order->save();

        // More ...
    }

    /**
     * 订单退款
     * 行为一：退款
     * @param array $params 订单数据
     */
    public function orderRefund(&$params)
    {
        $order = &$params;

        // 行为一
        switch ($order['pay_type']) {
            case \addons\unishop\model\Order::PAY_WXPAY:
                $app = Wechat::initEasyWechat('payment');
                $result = $app->refund->byOutTradeNumber($params['out_trade_no'], $params['out_trade_no'], bcmul($params['total_price'], 100), bcmul($params['refund']['amount'], 100), [
                    // 可在此处传入其他参数，详细参数见微信支付文档
                    'refund_desc' => $params['refund']['reason_type'],
                ]);
                break;
            case \addons\unishop\model\Order::PAY_ALIPAY:
                $alipay = Ali::initAliPay();
                $order = [
                    'out_trade_no' => $params['out_trade_no'],
                    'refund_amount' => $params['total_price'],
                ];
                $result = $alipay->refund($order);
                break;
        }

        // More ...
    }
}
