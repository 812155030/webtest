<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/4/29
 * Time: 9:20 PM
 */


namespace tests\unishop\controller;

use addons\unishop\controller\Category;
use addons\unishop\extend\PhpunitFunctionCustomize;
use PHPUnit\Framework\TestCase;

/**
 * @requires PHP >= 7.1
 * @requires extension mysqli
 */
class CategoryTest extends TestCase
{
    use PhpunitFunctionCustomize;

    /**
     * @test
     */
    public function all()
    {
        $contents = $this->request(Category::class, 'all');
        $this->assertIsArray($contents);
        $this->assertArrayHasKey('code', $contents);
        $this->assertArrayHasKey('data', $contents);
        $this->assertEquals(1, $contents['code']);

        if (empty($contents['data'])) {
            $this->assertEmpty($contents['data']);
        } else {
            foreach ($contents['data'] as $item) {
                $this->assertArrayHasKey('id', $item);
                $this->assertArrayHasKey('name', $item);
                $this->assertArrayHasKey('pid', $item);
                $this->assertArrayHasKey('type', $item);
                $this->assertSame('product', $item['type']);
                break;
            }
        }

    }

    /**
     * @test
     */
    public function menu()
    {
        $contents = $this->request(Category::class, 'menu');
        $this->assertIsArray($contents);
        $this->assertArrayHasKey('code', $contents);
        $this->assertArrayHasKey('data', $contents);
        $this->assertEquals(1, $contents['code']);

        if (empty($contents['data'])) {
            $this->assertEmpty($contents['data']);
        } else {
            foreach ($contents['data'] as $item) {
                $this->assertArrayHasKey('id', $item);
                $this->assertArrayHasKey('name', $item);
                $this->assertArrayHasKey('type', $item);
                $this->assertArrayHasKey('image', $item);
                $this->assertSame('index', $item['flag']);
                break;
            }
        }
    }
}
