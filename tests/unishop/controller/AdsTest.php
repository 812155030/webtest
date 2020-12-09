<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/4/29
 * Time: 9:20 PM
 */


namespace tests\unishop\controller;

use addons\unishop\controller\Ads;
use addons\unishop\extend\PhpunitFunctionCustomize;
use PHPUnit\Framework\TestCase;

/**
 * @requires PHP >= 7.1
 * @requires extension mysqli
 */
class AdsTest extends TestCase
{
    use PhpunitFunctionCustomize;

    public function testIndex()
    {
        $contents = $this->request(Ads::class,'index');

        $this->assertIsArray($contents);
        $this->assertArrayHasKey('code', $contents);
        $this->assertArrayHasKey('data', $contents);
        $this->assertEquals(1, $contents['code']);

        if (empty($contents['data'])) {
            $this->assertEmpty($contents['data']);
        } else {
            foreach ($contents['data'] as $ad) {
                $this->assertArrayHasKey('id', $ad);
                $this->assertArrayHasKey('image', $ad);
                $this->assertArrayHasKey('product_id', $ad);
                $this->assertArrayHasKey('background', $ad);
                $this->assertArrayHasKey('status', $ad);
                break;
            }
        }
    }
}
