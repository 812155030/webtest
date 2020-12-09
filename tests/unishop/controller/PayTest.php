<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/5/6
 * Time: 12:39 AM
 */


namespace tests\unishop\controller;


use addons\unishop\controller\Pay;
use addons\unishop\extend\PhpunitFunctionCustomize;
use PHPUnit\Framework\TestCase;

class PayTest extends TestCase
{
    use PhpunitFunctionCustomize;

    /**
     * @test
     */
    public function platform()
    {
        return [
            ['APP-PLUS'],
            ['H5'],
            ['MP-WEIXIN'],
            ['MP-ALIPAY'],
            ['MP-BAIDU'],
            ['MP-TOUTIAO']
        ];
    }

    /**
     * @test
     * @dataProvider  platform
     */
    public function getPayType($platform)
    {
        $contents = $this->request(Pay::class, 'getPayType', [
            'header' => [
                'platform' => $platform
            ]
        ]);
        $this->assertSame(1, $contents['code']);
        $this->assertIsArray($contents['data']);
        $this->assertArrayHasKey('alipay', $contents['data']);
        $this->assertArrayHasKey('alipay', $contents['data']);
        $this->assertArrayHasKey('alipay', $contents['data']);
    }

    /**
     * @test
     */
    public function offline()
    {

    }
}
