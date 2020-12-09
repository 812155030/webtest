<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2019/10/27
 * Time: 5:37 下午
 */


namespace addons\unishop\controller;

use addons\unishop\extend\Hashids;
use addons\unishop\model\Config;
use addons\unishop\model\Product;
use addons\unishop\model\Cart as CartModel;
use think\Exception;

/**
 * 购物车接口
 * Class Cart
 * @package addons\unishop\controller
 */
class Cart extends Base
{
    /**
     * 允许频繁访问的接口
     * @var array
     */
    protected $frequently = ['number_change', 'choose_change'];

    /**
     * 获取购物车列表
     *
     * @ApiTitle    (获取购物车列表)
     * @ApiSummary  (获取购物车列表)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="{'cart_id'='int','choose'='int','image'='string','isset'='bool','nowPrice'='float','number'='int','oldPrice'='float','spec'='string','title'='string'",description="扩展数据返回")
     * @ApiReturn   ({
    'code':'1',
    'mesg':'返回成功'
    'data':[
    0:{
    cart_id: 3
    choose: 1
    image: "/uploads/20190715/1aa2c058d4dd7f0d64edb992d9aeccee.jpeg"
    isset: true
    nowPrice: "122"
    number: 2
    oldPrice: "122"
    spec: "深空灰,64G"
    title: "苹果X",
    stock:12
    }]
    })
     */
    public function index()
    {
        $carts = (new CartModel)->where(['user_id' => $this->auth->id])
            ->with([
                'product' => function ($query) {
                    $query->field(['id', 'image', 'title', 'specTableList','sales','market_price','sales_price','stock','use_spec', 'switch']);
                }
            ])
            ->order(['createtime' => 'desc'])
            ->select();
        if (!$carts) {
            $this->success('', []);
        }

        $data = [];
        $productExtend = new \addons\unishop\extend\Product;

        foreach ($carts as $item) {
            $oldProduct = json_decode($item['snapshot'], true);
            $oldData = $productExtend->getBaseData($oldProduct, $item['spec'] ?? '');

            if (empty($item['product'])) {
                $tempData = $oldData;
                $tempData['isset'] = false; // 失效
                $tempData['title'] = $oldProduct['title'];
                $tempData['choose'] = 0;
            } else {
                $productData = $item['product']->getData();
                $tempData = $productExtend->getBaseData($productData, $item['spec'] ?? '');
                $tempData['title'] = $item['product']['title'];
                $tempData['choose'] = $item['choose']; //是否选中

                $tempData['isset'] = true;
                if ($productData['switch'] == Product::SWITCH_OFF) {
                    $tempData['isset'] = false; // 失效
                    $tempData['choose'] = 0;
                }
            }

            $tempData['cart_id'] = $item['id'];
            $tempData['spec'] = $item['spec'];
            $tempData['number'] = $item['number'];

            $tempData['image'] = Config::getImagesFullUrl($oldData['image']);
            $tempData['oldPrice'] = round($oldData['sales_price'], 2);
            $tempData['nowPrice'] = round($tempData['sales_price'], 2);

            $tempData['product_id'] = Hashids::encodeHex($item['product_id']);

            $data[] = $tempData;
        }

        $this->success('', $data);
    }


    /**
     * 添加
     */
    public function add()
    {
        $id = $this->request->get('id', 0);

        $id = \addons\unishop\extend\Hashids::decodeHex($id);

        $product = (new Product)->where(['id' => $id, 'switch' => Product::SWITCH_ON])->find();
        if (!$product) {
            $this->error('产品不存在或已下架');
        }

        $spec = $this->request->get('spec', '');
        $productBase = (new \addons\unishop\extend\Product())->getBaseData($product->getData(), $spec);
        if (!$productBase['stock'] || $productBase['stock'] <= 0) {
            $this->error('库存不足');
        }

        $user_id = $this->auth->id;
        $cartModel = new \addons\unishop\model\Cart();
        $cartModel->where(['user_id' => $user_id, 'product_id' => $id]);
        $spec && $cartModel->where('spec', $spec);
        $oldCart = $cartModel->find();

        if ($oldCart) {
            $this->error('商品已存在购物车');
//            $oldCart->number++;
//            $result = $oldCart->save();
        } else {
            $cartModel->user_id = $user_id;
            $cartModel->product_id = $id;
            $spec && $cartModel->spec = $spec;
            $cartModel->number = 1;
            $cartModel->snapshot = json_encode($product->getData(), true);
            $result = $cartModel->save();
        }

        if ($result) {
            $this->success('添加成功', 1);
        } else {
            $this->error('添加失败');
        }
    }

    /**
     * 删除
     */
    public function delete()
    {
        $id = $this->request->post('id', 0);
        $userId = $this->auth->id;
        $result = CartModel::destroy(function ($query) use ($id, $userId) {
            $query->whereIn('id', $id)->where(['user_id' => $userId]);
        });
        if ($result) {
            $this->success('删除成功', 1);
        } else {
            $this->error('删除失败', 0);
        }
    }

    /**
     * 修改购物车数量
     * @ApiTitle    (获取购物车列表)
     * @ApiSummary  (获取购物车列表)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     */
    public function number_change()
    {
        $cart_id = $this->request->get('id', 0);
        $number = $this->request->get('number', 1);
        $cart = CartModel::get(['id' => $cart_id, 'user_id' => $this->auth->id]);
        if (empty($cart)) {
            $this->error('此商品不存在购物车');
        }
        $cart->number = $number;
        $result = $cart->save();
        if ($result) {
            $this->success('更改成功', $number);
        } else {
            $this->error('更改失败', $number);
        }
    }

    /**
     * 修改购物车选中状态
     * @ApiTitle    (修改购物车选中状态)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     */
    public function choose_change()
    {
        $trueArr = $this->request->post('trueArr', false);
        $falseArr = $this->request->post('falseArr', false);
        $user_id = $this->auth->id;
        try {
            $cart = new CartModel();
            if ($trueArr) {
                $cart->save(['choose' => CartModel::CHOOSE_ON], function ($query) use ($user_id, $trueArr) {
                    $query->where('user_id', $user_id)->where('id', 'IN', $trueArr);
                });
            }
            if ($falseArr) {
                $cart->save(['choose' => CartModel::CHOOSE_OFF], function ($query) use ($user_id, $falseArr) {
                    $query->where('user_id', $user_id)->where('id', 'IN', $falseArr);
                });
            }
        } catch (Exception $e) {
            $this->error('更新失败', 0);
        }
        $this->success('', 1);
    }

}
