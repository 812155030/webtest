<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/5/5
 * Time: 10:38 PM
 */


namespace tests\unishop\controller;


use addons\unishop\controller\Cart;
use addons\unishop\extend\PhpunitFunctionCustomize;
use addons\unishop\model\Product;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    use PhpunitFunctionCustomize;

    /**
     * @test
     */
    public function getProduct()
    {
        $products = (new Product)->where(['switch' => Product::SWITCH_ON])->field('id,specTableList,use_spec')->select();
        if ($products) {
            return collection($products)->append(['spec_table_list'])->toArray();
        }
        return [];
    }

    /**
     * @test
     * @depends getProduct
     */
    public function add(array $products)
    {
        $this->userLogin();
        foreach ($products as $product) {
            $params['id'] = $product['product_id'];
            if ($product['use_spec'] == Product::SPEC_ON) {
                foreach ($product['spec_table_list'] as $row) {
                    $params['spec'] = implode(',', $row['value']);
                    $contents = $this->request(Cart::class, 'add', $params, 'get');
                    $this->assertArrayHasKey('code', $contents);
                    $this->assertArrayHasKey('data', $contents);
                }
            } else {
                $contents = $this->request(Cart::class, 'add', $params, 'get');
                $this->assertArrayHasKey('code', $contents);
                $this->assertArrayHasKey('data', $contents);
            }
        }
    }

    /**
     * @test
     */
    public function index()
    {
        $contents = $this->request(Cart::class, 'index');

        $this->assertSame(1, $contents['code']);
        $this->assertIsArray($contents['data']);

        if (empty($contents['data'])) {
            $this->assertEmpty($contents['data']);
        } else {
            foreach ($contents['data'] as $item) {
                $this->assertGreaterThanOrEqual(0, $item['market_price']);
                $this->assertGreaterThanOrEqual(0, $item['sales_price']);
                $this->assertGreaterThanOrEqual(0, $item['stock']);
                $this->assertGreaterThanOrEqual(0, $item['sales']);
                $this->assertNotEmpty($item['image']);
                $this->assertNotEmpty($item['title']);
                $this->assertGreaterThanOrEqual(0, $item['choose']);
                $this->assertGreaterThanOrEqual(0, $item['isset']);
                $this->assertNotEmpty($item['cart_id']);
                $this->assertArrayHasKey('spec', $item);
                $this->assertNotEmpty($item['number']);
                $this->assertGreaterThanOrEqual(0, $item['oldPrice']);
                $this->assertGreaterThanOrEqual(0, $item['nowPrice']);
                $this->assertNotEmpty($item['product_id']);
            }
        }
        return $contents['data'];
    }

    /**
     * @test
     * @depends index
     */
    public function number_change(array $carts)
    {
        foreach ($carts as $cart) {
            $this->assertGreaterThanOrEqual(1, $cart['stock']);
            $number = mt_rand(1, intval($cart['stock']));
            $contents = $this->request(Cart::class, 'number_change', ['id' => $cart['cart_id'], 'number' => $number], 'get');
            if ($number == $cart['number']) {
                $this->assertEquals(0, $contents['code']);
            } else {
                $this->assertEquals(1, $contents['code']);
                $this->assertEquals($number, $contents['data']);
            }
        }
    }

    /**
     * @test
     * @depends index
     */
    public function choose_change(array $carts)
    {
        if (count($carts) > 0) {
            $arr = implode(',', array_column($carts, 'cart_id'));
            $contents = $this->request(Cart::class, 'choose_change', ['falseArr' => $arr], 'post');
            $this->assertSame(1, $contents['code']);
            $this->assertSame(1, $contents['data']);
            $contents = $this->request(Cart::class, 'choose_change', ['trueArr' => $arr], 'post');
            $this->assertSame(1, $contents['code']);
            $this->assertSame(1, $contents['data']);
        }
    }


    /**
     * @test
     * @depends index
     */
    public function delete(array $carts)
    {
        if (count($carts) > 0) {
            $arr = implode(',', array_column($carts, 'cart_id'));
            $contents = $this->request(Cart::class, 'delete', ['id' => $arr]);
            $this->assertSame(1, $contents['code']);
            $this->assertSame(1, $contents['data']);
        }
    }

}
