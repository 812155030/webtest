<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/2/9
 * Time: 6:18 PM
 */


namespace addons\unishop\controller;


use addons\unishop\extend\Hashids;
use addons\unishop\extend\Redis;
use addons\unishop\model\Address as AddressModel;
use addons\unishop\model\Area;
use addons\unishop\model\Config;
use addons\unishop\model\DeliveryRule as DeliveryRuleModel;
use addons\unishop\model\Evaluate;
use addons\unishop\model\FlashProduct;
use addons\unishop\model\FlashSale;
use addons\unishop\model\Product;
use think\Db;
use think\Exception;
use think\Hook;
use think\Loader;

/**
 * 秒杀相关接口
 * Class Flash
 * @package addons\unishop\controller
 */
class Flash extends Base
{
    protected $noNeedLogin = ['index', 'navbar', 'product', 'productDetail'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 首页秒杀信息接口
     */
    public function index()
    {
        $flashSaleModel = new FlashSale();
        $hour = strtotime(date('Y-m-d H:00:00'));
        $flash = $flashSaleModel
            ->where('endtime', '>=', $hour)
            ->where([
                'switch' => FlashSale::SWITCH_YES,
                'status' => FlashSale::STATUS_NO,
            ])
            ->with([
                'product' => function ($query) {
                    //$query->with('product')->where(['switch' => FlashProduct::SWITCH_ON]);
                    $query->alias('fp')->join('unishop_product p', 'fp.product_id = p.id')
                        ->field('fp.id,fp.flash_id,fp.product_id,p.image,p.title,p.sales_price')
                        ->where([
                            'fp.switch' => FlashProduct::SWITCH_ON,
                            'p.deletetime' => NULL
                        ]);
                }
            ])
            ->order('starttime ASC')
            ->find();


        if ($flash) {
            $flash = $flash->toArray();
            foreach ($flash['product'] as &$product) {
                $product['image'] = Config::getImagesFullUrl($product['image']);
            }

            // 寻找下一场的倒计时
            $nextFlash = $flashSaleModel
                ->where('starttime', '>', $hour)
                ->where([
                    'switch' => FlashSale::SWITCH_YES,
                    'status' => FlashSale::STATUS_NO,
                ])
                ->order("starttime ASC")
                ->cache(10)
                ->find();

            $flash['starttime'] = $nextFlash['starttime'];

            $flash['countdown'] = FlashSale::countdown($flash['starttime']);
        }

        $this->success('', $flash);
    }

    /**
     * 获取秒杀时间段
     */
    public function navbar()
    {
        $flashSaleModel = new FlashSale();
        $flash = $flashSaleModel
            ->where('endtime', '>', time())
            ->where([
                'switch' => FlashSale::SWITCH_YES,
                'status' => FlashSale::STATUS_NO
            ])
            ->field('id,starttime,title,introdution,endtime')
            ->order('starttime ASC')
            ->cache(2)
            ->select();

        $this->success('', $flash);
    }

    /**
     * 获取秒杀的产品列表
     */
    public function product()
    {
        $flash_id = $this->request->request('flash_id', 0);
        $page = $this->request->request('page', 1);
        $pagesize = $this->request->request('pagesize', 15);

        $flash_id = Hashids::decodeHex($flash_id);
        $productModel = new FlashProduct();
        $products = $productModel
            ->with('product')
            ->where(['flash_id' => $flash_id, 'switch' => FlashProduct::SWITCH_ON])
            ->limit(($page - 1) * $pagesize, $pagesize)
            ->cache(2)
            ->select();

        foreach ($products as &$product) {
            $product['sold'] = $product['sold'] > $product['number'] ? $product['number'] : $product['sold'];
        }

        $this->success('', $products);
    }


    /**
     * 获取产品数据
     */
    public function productDetail()
    {
        $productId = $this->request->get('id');
        $productId = \addons\unishop\extend\Hashids::decodeHex($productId);
        $flashId = $this->request->get('flash_id');
        $flashId = \addons\unishop\extend\Hashids::decodeHex($flashId);

        try {

            $productModel = new Product();
            $data = $productModel->where(['id' => $productId])->cache(true, 20, 'flashProduct')->find();
            if (!$data) {
                $this->error(__('Product not exist'));
            }

            // 真实浏览量加一
            $data->real_look++;
            $data->look++;
            $data->save();

            //服务
            $server = explode(',', $data->server);
            $configServer = json_decode(Config::getByName('server')['value'],true);
            $serverValue = [];
            foreach ($server as $k => $v) {
                if (isset($configServer[$v])) {
                    $serverValue[] = $configServer[$v];
                }
            }
            $data->server = count($serverValue) ? implode(' · ', $serverValue) : '';

            // 默认没有收藏
            $data->favorite = false;

            // 评价
            $data['evaluate_data'] = (new Evaluate)->where(['product_id' => $productId])
                ->field('COUNT(*) as count, IFNULL(CEIL(AVG(rate)/5*100),0) as avg')
                ->cache(true, 20, 'flashEvaluate')->find();

            $redis = new Redis();
            $flash['starttime'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'starttime');
            $flash['endtime'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'endtime');
            $flash['sold'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'sold');
            $flash['number'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'number');
            $flash['sold'] = $flash['sold'] > $flash['number'] ? $flash['number'] : $flash['sold'];
            $flash['text'] = $flash['starttime'] > time() ? '距开始:' : '距结束:';

            // 秒杀类型不加载优惠券、促销活动、是否已收藏、评价等等，会影响返回速度
            $targetTime = $flash['starttime'] > time() ? $flash['starttime'] : $flash['endtime'];
            $flash['countdown'] = FlashSale::countdown($targetTime);
            $data['coupon'] = [];

            // 秒杀数据
            $data['flash'] = $flash;

            $data->append(['images_text', 'spec_list', 'spec_table_list'])->toArray();

            // 购物车数量
            $data['cart_num'] = (new \addons\unishop\model\Cart)->where(['user_id' => $this->auth->id])->count();

            $this->success('', $data);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

    }


    /**
     * 创建订单
     */
    public function createOrder()
    {
        $productId = $this->request->post('id', 0);
        $flashId = $this->request->post('flash_id', 0);
        $flashId = \addons\unishop\extend\Hashids::decodeHex($flashId);
        $productId = \addons\unishop\extend\Hashids::decodeHex($productId);

        try {

            $redis = new Redis();
            $sold = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'sold');
            $number = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'number');
            $switch = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'switch');
            $starttime = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'starttime');
            $endtime = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'endtime');

            //判断是否开始或结束
            if (time() < $starttime) {
                $this->error(__('Activity not started'));
            }
            if ($endtime < time()) {
                $this->error(__('Activity ended'));
            }

            // 截流
            if ($sold >= $number) {
                $this->error(__('Item sold out'));
            }
            if ($switch == FlashSale::SWITCH_NO || $switch == false) {
                $this->error(__('Item is off the shelves'));
            }

            $product = (new Product)->where(['id' => $productId, 'deletetime' => null])->find();
            /** 产品基础数据 **/
            $spec = $this->request->post('spec', '');
            $productData[0] = $product->getDataOnCreateOrder($spec);

            if (!$productData) {
                $this->error(__('Product not exist'));
            }
            $productData[0]['image'] = Config::getImagesFullUrl($productData[0]['image']);
            $productData[0]['sales_price'] = round($productData[0]['sales_price'], 2);
            $productData[0]['market_price'] = round($productData[0]['market_price'], 2);

            /** 默认地址 **/
            $address = AddressModel::get(['user_id' => $this->auth->id, 'is_default' => AddressModel::IS_DEFAULT_YES]);
            if ($address) {
                $area = (new Area)->whereIn('id', [$address->province_id, $address->city_id, $address->area_id])->column('name', 'id');
                $address = $address->toArray();
                $address['province']['name'] = $area[$address['province_id']];
                $address['city']['name'] = $area[$address['city_id']];
                $address['area']['name'] = $area[$address['area_id']];
            }

            /** 运费数据 **/
            $cityId = $address['city_id'] ? $address['city_id'] : 0;
            $delivery = (new DeliveryRuleModel())->getDelivetyByArea($cityId);
            $msg = '';
//            if ($delivery['status'] == 0) {
//                $msg = __('Your receiving address is not within the scope of delivery');
//            }

            $redis = new Redis();
            //$flash['starttime'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'starttime');
            //$flash['endtime'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'endtime');
            $flash['sold'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'sold');
            $flash['number'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'number');
            $flash['sold'] = $flash['sold'] > $flash['number'] ? $flash['number'] : $flash['sold'];
            //$flash['text'] = $flash['starttime'] > time() ? '距开始:' : '距结束:';

            // 秒杀类型不加载优惠券、促销活动、是否已收藏、评价等等，会影响返回速度
            //$targetTime = $flash['starttime'] > time() ? $flash['starttime'] : $flash['endtime'];
            //$flash['countdown'] = FlashSale::countdown($targetTime);

            $this->success($msg, [
                'product' => $productData,
                'address' => $address,
                'delivery' => $delivery['list'],
                'flash' => $flash
            ]);

        } catch (Exception $e) {
            $this->error($e->getMessage(), false);
        }
    }


    /**
     * 提交订单
     */
    public function submitOrder()
    {
        $data = $this->request->post();
        try {
            $validate = Loader::validate('\\addons\\unishop\\validate\\Order');
            if (!$validate->check($data, [], 'submitFlash')) {
                throw new Exception($validate->getError());
            }

            Db::startTrans();

            // 判断创建订单的条件
            if (empty(Hook::get('create_order_before'))) { // 由于自动化测试的时候会注册多个同名行为
                Hook::add('create_order_before', 'addons\\unishop\\behavior\\OrderFlash');
            }
            if (empty(Hook::get('create_order_after'))) {
                Hook::add('create_order_after', 'addons\\unishop\\behavior\\OrderFlash');
            }

            $data['flash_id'] = Hashids::decodeHex($data['flash_id']);
            $data['product_id'] = Hashids::decodeHex($data['product_id']);
            $orderModel = new \addons\unishop\model\Order();
            $result = $orderModel->createOrder($this->auth->id, $data);

            Db::commit();

            $this->success('', $result);

        } catch (Exception $e) {

            Db::rollback();
            $this->error($e->getMessage(), false);
        }
    }

}
