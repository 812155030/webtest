<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/5/4
 * Time: 3:15 PM
 */


namespace tests\unishop\controller;


use addons\unishop\controller\Product;
use addons\unishop\extend\PhpunitFunctionCustomize;
use addons\unishop\model\Coupon;
use addons\unishop\model\Favorite;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    use PhpunitFunctionCustomize;


    /**
     * @test
     */
    public function lists()
    {
        $pagesize = 2;
        $contents = $this->request(Product::class, 'lists',[
            'page' => 1,
            'pagesize' => $pagesize
        ], 'get');

        $this->assertIsArray($contents);
        $this->assertArrayHasKey('code', $contents);
        $this->assertArrayHasKey('msg', $contents);
        $this->assertArrayHasKey('data', $contents);
        if (empty($contents['data'])) {
            $this->assertEmpty($contents['data']);
        } else {
            $this->assertLessThanOrEqual($pagesize, count($contents['data']));
            foreach ($contents['data'] as $item) {
                $this->assertIsArray($item);
                $this->assertArrayHasKey('title', $item);
                $this->assertArrayHasKey('image', $item);
                $this->assertArrayHasKey('sales_price', $item);
                $this->assertArrayHasKey('sales', $item);
                $this->assertArrayHasKey('product_id', $item);
                $this->assertNotEmpty($item['product_id']);
            }
        }

        return $contents['data'][0] ?? [];
    }


    /**
     * @test
     * @depends lists
     */
    public function detail(array $product)
    {
        if (empty($product)) {
            $this->assertEmpty($product);
        } else {
            $this->assertIsArray($product);

            $contents = $this->request(Product::class, 'detail', ['id' => $product['product_id']], 'get');
            $this->assertIsArray($contents);
            $this->assertArrayHasKey('code', $contents);
            $this->assertArrayHasKey('msg', $contents);
            $this->assertArrayHasKey('data', $contents);

            if (empty($contents['data'])) {
                $this->assertEmpty($contents['data']);
            } else {
                $data = $contents['data'];
                $this->assertIsArray($data);
                $this->assertIsString($data['title']);
                $this->assertIsString($data['image']);
                $this->assertGreaterThanOrEqual(0, $data['market_price']);
                $this->assertGreaterThanOrEqual(0, $data['sales_price']);
                $this->assertGreaterThanOrEqual(0, $data['sales']);
                $this->assertGreaterThanOrEqual(0, $data['stock']);
                $this->assertGreaterThanOrEqual(0, $data['look']);
                $this->assertGreaterThanOrEqual(0, $data['use_spec']);
                $this->assertLessThanOrEqual(1, $data['use_spec']);
                $this->assertEquals(\addons\unishop\model\Product::SWITCH_ON, $data['switch']);
                $this->assertIsArray($data['evaluate_data']);
                $this->assertArrayHasKey('count', $data['evaluate_data']);
                $this->assertArrayHasKey('avg', $data['evaluate_data']);
                $this->assertIsArray($data['coupon']);
                if (count($data['coupon']) > 0) {
                    foreach ($data['coupon'] as $coupon) {
                        $this->assertArrayHasKey('title', $coupon);
                        $this->assertEquals(Coupon::SWITCH_ON, $coupon['switch']);
                        $this->assertArrayHasKey('title', $coupon);
                        $this->assertGreaterThanOrEqual(0, $coupon['least']);
                        $this->assertGreaterThanOrEqual(0, $coupon['value']);
                        $this->assertGreaterThan($coupon['value'], $coupon['least']);
                        $this->assertGreaterThan(time(), $coupon['endtime']);
                        $this->assertLessThan(time(), $coupon['starttime']);
                    }
                }
                $this->assertGreaterThanOrEqual(0, $data['cart_num']);
                $this->assertNotEmpty($data['product_id']);
                $this->assertIsArray($data['images_text']);
                $this->assertGreaterThan(0, count($data['images_text']));
                $this->assertIsArray($data['spec_list']);
                foreach ($data['spec_list'] as $specList) {
                    $this->assertNotEmpty($specList['name']);
                    $this->assertIsArray($specList['child']);
                    $this->assertGreaterThan(0, count($specList['child']));
                }

                $this->assertIsArray($data['spec_table_list']);
                foreach ($data['spec_table_list'] as $spec) {
                    $this->assertArrayHasKey('image', $spec);
                    $this->assertArrayHasKey('market_price', $spec);
                    $this->assertArrayHasKey('sales', $spec);
                    $this->assertArrayHasKey('sales_price', $spec);
                    $this->assertArrayHasKey('stock', $spec);
                    $this->assertArrayHasKey('value', $spec);
                    $this->assertIsArray($spec['value']);
                    $this->assertGreaterThan(0, count($spec['value']));
                }

            }

        }
    }

    /**
     * @test
     * @depends lists
     */
    public function favorite(array $product)
    {
        if (empty($product)) {
            $this->assertEmpty($product);
        } else {
            $contents = $this->request(Product::class, 'favorite', ['id' => $product['product_id']], 'get');
            $this->assertIsArray($contents);
            $this->assertArrayHasKey('code', $contents);
            if ($contents['code'] == 401) {
                $this->userLogin();
                $contents = $this->request(Product::class, 'favorite', ['id' => $product['product_id']], 'get');
                $this->assertEquals(1, $contents['code']);
            }
        }

        return $product;
    }

    /**
     * @test
     * @depends favorite
     */
    public function favoriteList(array $product)
    {
        $contents = $this->request(Product::class, 'favoriteList', [
            'page'=> 1,
            'pagesize' => 2
        ], 'get');

        $this->assertIsArray($contents);
        $this->assertEquals(1, $contents['code']);
        $this->assertIsArray($contents['data']);
        if (empty($contents['data'])) {
            $this->assertEmpty($contents['data']);
        } else {
            foreach ($contents['data'] as $item) {
                $this->assertNotEmpty($item['id']);
                $this->assertNotEmpty($item['user_id']);
                $this->assertIsArray($item['product']);
                $this->assertNotEmpty($item['product']['image']);
                $this->assertNotEmpty($item['product']['title']);
                $this->assertNotEmpty($item['product']['product_id']);
                $this->assertGreaterThanOrEqual(0, floatval($item['product']['sales_price']));
                $this->assertGreaterThanOrEqual(0, floatval($item['product']['market_price']));
                $this->assertArrayHasKey('status', $item);  // status表示商品是否失效
            }
        }

    }

    /**
     * @test
     * @depends lists
     */
    public function evaluate(array $product)
    {
        if (count($product) == 0) {
            return;
        }
        $contents = $this->request(Product::class, 'evaluate', [
            'page'=> 1,
            'pagesize' => 2,
            'product_id' => $product['product_id']
        ], 'get');

        $this->assertIsArray($contents);
        $this->assertSame(1, $contents['code']);
        $this->assertIsArray($contents['data']);

        if (empty($contents['data'])) {
            $this->assertEmpty($contents['data']);
        } else {
            foreach ($contents['data'] as $item) {
                $this->assertNotEmpty($item['username']);
                $this->assertNotEmpty($item['avatar']);
                $this->assertNotEmpty($item['id']);
                $this->assertNotEmpty($item['comment']);
                $this->assertNotEmpty($item['createtime_text']);
            }
        }
    }

    public static function tearDownAfterClass()
    {
        $user = (new self)->userLogin()['data'];
        Favorite::destroy(['user_id' => $user['user_id']]);
    }
}
