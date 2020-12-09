<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/4/15
 * Time: 3:29 PM
 */


namespace addons\unishop\behavior;

use addons\unishop\extend\Hashids;
use addons\unishop\extend\Redis;
use addons\unishop\model\Address;
use addons\unishop\model\Config;
use addons\unishop\model\DeliveryRule as DeliveryRuleModel;
use addons\unishop\model\FlashProduct;
use addons\unishop\model\FlashSale;
use addons\unishop\model\Product;
use app\admin\model\unishop\Coupon;
use think\Db;
use think\Exception;

/**
 * 秒杀订单相关行为
 * Class OrderFlash
 * @package addons\unishop\behavior
 */
class OrderFlash
{

    /**
     * 检查是否符合创建订单的条件
     * 条件1：判断是否卖完秒杀量和是否下架
     * 条件2：商品是否存在
     * 条件3：收货地址是否在范围内
     * 条件4：商品库存情况
     * @param array $params
     * @param array $extra
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public function createOrderBefore(&$params, $extra)
    {
        // 条件一
        $numbers = explode(',', $extra['number']);
        $redis = new Redis();
        $totalNumber = $redis->handler->hGet('flash_sale_' . $extra['flash_id'] . '_' . $extra['product_id'], 'number');
        $totalSold = $redis->handler->hGet('flash_sale_' . $extra['flash_id'] . '_' . $extra['product_id'], 'sold');
        $switch = $redis->handler->hGet('flash_sale_' . $extra['flash_id'] . '_' . $extra['product_id'], 'switch');
        $starttime = $redis->handler->hGet('flash_sale_' . $extra['flash_id'] . '_' . $extra['product_id'], 'starttime');
        $endtime = $redis->handler->hGet('flash_sale_' . $extra['flash_id'] . '_' . $extra['product_id'], 'endtime');

        //判断是否开始或结束
        if (time() < $starttime) {
            $this->error(__('Activity not started'));
        }
        if ($endtime < time()) {
            $this->error(__('Activity ended'));
        }
        // 截流
        if ($totalSold >= $totalNumber) {
            throw new Exception(__('Item sold out'));
        }
        if ($switch == FlashSale::SWITCH_NO) {
            throw new Exception(__('Item is off the shelves'));
        }

        // 条件二
        $products = Db::name('unishop_product')->where(['id' => $extra['product_id']])->select();
        if (!$products) {
            throw new Exception(__('Product not exist'));
        }
        $specs = explode(',', $extra['spec']);
        foreach ($specs as &$spec) {
            $spec = str_replace('|', ',', $spec);
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
        $orderPrice = 0;
        foreach ($products as $key => $product) {
            $productInfo = (new \addons\unishop\extend\Product())->getBaseData($product, $specs[$key] ? $specs[$key] : '');
            $sold = $redis->handler->hIncrBy('flash_sale_' . $extra['flash_id'] . '_' . $extra['product_id'], 'sold', $numbers[$key]);
            if ($totalNumber < $sold) {
                $redis->handler->hIncrBy('flash_sale_' . $extra['flash_id'] . '_' . $extra['product_id'], 'sold', -$numbers[$key]);
                throw new Exception(__('Insufficient inventory，%s pieces left', $totalNumber - $totalSold));
            }
            $orderPrice = bcadd($orderPrice, bcmul($productInfo['sales_price'], $numbers[$key], 2), 2);
            $baseProductInfo[] = $productInfo;
        }


        // 没有优惠券
        $coupon = [];

        $params = [$products, $delivery, $coupon, $baseProductInfo, $address, $orderPrice, $specs, $numbers];
    }

    /**
     * 创建订单之后
     * 行为一：更新秒杀商品销售量
     * 行为二：增加商品的下单未付款数量
     * @param array $params 商品属性
     * @param array $extra
     */
    public function createOrderAfter(&$params, $extra)
    {
        // 行为一
        (new FlashProduct)->where(['flash_id' => $extra['flash_id'], 'product_id' => $extra['product_id']])->setInc('sold', $extra['number']);

        // 行为二
        (new Product)->where(['id' => $extra['product_id']])->setInc('no_buy_yet', $extra['number']);

    }


}
