<?php

namespace addons\unishop\controller;

use addons\unishop\extend\Hashids;
use addons\unishop\model\Config;
use addons\unishop\model\Evaluate;
use addons\unishop\model\Favorite;
use addons\unishop\model\Product as productModel;
use addons\unishop\model\Coupon;
use think\Exception;

class Product extends Base
{
    protected $noNeedLogin = ['detail', 'lists'];

    /**
     * 获取产品数据
     */
    public function detail()
    {
        $productId = $this->request->get('id');
        $productId = \addons\unishop\extend\Hashids::decodeHex($productId);

        try {

            $productModel = new productModel();
            $data = $productModel->where(['id' => $productId])->cache(10)->find();
            if (!$data) {
                $this->error(__('Goods not exist'));
            }
            if ($data['switch'] == productModel::SWITCH_OFF) {
                $this->error(__('Goods are off the shelves'));
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
                ->cache(10)->find();

            //优惠券
            $data->coupon = (new Coupon)->where('endtime', '>', time())
                ->where(['switch' => Coupon::SWITCH_ON])->cache(10)->order('weigh DESC')->select();

            // 是否已收藏
            if ($this->auth->id) {
                $data->favorite = (new Favorite)->where(['user_id' => $this->auth->id, 'product_id' => $productId])->count();
            }

            // 购物车数量
            $data->cart_num = (new \addons\unishop\model\Cart)->where(['user_id' => $this->auth->id])->count();

            // 评价信息
            $evaluate = (new Evaluate)->alias('e')
                ->join('user u', 'e.user_id = u.id')
                ->where(['e.product_id' => $productId, 'toptime' => ['>', Evaluate::TOP_OFF]])
                ->field('u.username,u.avatar,e.*')
                ->order(['toptime' => 'desc', 'createtime' => 'desc'])->select();
            if ($evaluate) {
                $data->evaluate_list = collection($evaluate)->append(['createtime_text'])->toArray();
            }
            $data = $data->append(['images_text', 'spec_list', 'spec_table_list'])->toArray();
            $this->success('', $data);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

    }

    /**
     * 产品列表
     * 注：这里后期需要做缓存
     */
    public function lists()
    {
        $page = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', 20);
        $by = $this->request->get('by', 'weigh');
        $desc = $this->request->get('desc', 'desc');

        $sid = $this->request->get('sid'); // 二级分类Id
        $fid = $this->request->get('fid'); // 一级分类Id

        $productModel = new productModel();

        if ($fid && !$sid) {
            $categoryModel = new \addons\unishop\model\Category();
            $sArr = $categoryModel->where('pid', $fid)->field('id')->select();
            $sArr = array_column($sArr, 'id');
            array_push($sArr, $fid);
            $productModel->where('category_id', 'in', $sArr);
        } else {
            $sid && $productModel->where(['category_id' => $sid]);
        }

        $result = $productModel
            ->where(['switch' => productModel::SWITCH_ON])
            ->page($page, $pagesize)
            ->order($by, $desc)
            ->field('id,title,image,sales_price,sales,real_sales')
            ->select();

        if ($result) {
            $result = collection($result)->toArray();
            $this->success('', $result);
        } else {
            $this->error('没有更多数据');
        }
    }

    /**
     * 收藏
     * @param int $id 产品id
     */
    public function favorite()
    {
        $id = $this->request->get('id', 0);
        $id = \addons\unishop\extend\Hashids::decodeHex($id);

        $user_id = $this->auth->id;
        $favoriteModel = Favorite::get(function ($query) use ($id, $user_id) {
            $query->where(['user_id' => $user_id, 'product_id' => $id]);
        });
        if ($favoriteModel) {
            Favorite::destroy($favoriteModel->id);
        } else {
            $product = productModel::withTrashed()->where(['id' => $id, 'switch' => productModel::SWITCH_ON])->find();
            if (!$product) {
                $this->error('参数错误');
            }
            $favoriteModel = new Favorite();
            $favoriteModel->user_id = $user_id;
            $favoriteModel->product_id = $id;
            $product = $product->getData();
            $data['image'] = $product['image'];
            $data['market_price'] = $product['market_price'];
            $data['product_id'] = Hashids::encodeHex($product['id']);
            $data['sales_price'] = $product['sales_price'];
            $data['title'] = $product['title'];
            $favoriteModel->snapshot = json_encode($data);
            $favoriteModel->save();
        }

        $this->success('', true);
    }


    /**
     * 收藏列表
     */
    public function favoriteList()
    {
        $page = $this->request->get('page', 1);
        $pageSize = $this->request->get('pagesize', 20);

        $list = (new Favorite)->where(['user_id' => $this->auth->id])->with(['product'])->page($page, $pageSize)->select();

        $list = collection($list)->toArray();
        foreach ($list as &$item) {
            if (!empty($item['product'])) {
                $item['status'] = 1;
            } else {
                $item['status'] = 0;
                $item['product'] = json_decode($item['snapshot'],true);
                $image = $item['product']['image'];
                $item['product']['image'] = Config::getImagesFullUrl($image);
            }
            unset($item['snapshot']);
        }

        $this->success('', $list);
    }

    /**
     * 商品评论
     */
    public function evaluate()
    {
        $page = $this->request->get('page', 1);
        $pageSize = $this->request->get('pagesize', 20);
        $productId = $this->request->get('product_id');
        $productId = \addons\unishop\extend\Hashids::decodeHex($productId);

        // 评价信息
        $evaluate = (new Evaluate)->alias('e')
            ->join('user u', 'e.user_id = u.id')
            ->where(['e.product_id' => $productId])
            ->field('u.username,u.avatar,e.*')
            ->order(['toptime' => 'desc', 'createtime' => 'desc'])
            ->page($page, $pageSize)
            ->select();
        if ($evaluate) {
            $evaluate = collection($evaluate)->append(['createtime_text'])->toArray();
        }
        $this->success('', $evaluate);
    }
}
