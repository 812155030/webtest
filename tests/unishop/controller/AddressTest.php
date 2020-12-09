<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/5/5
 * Time: 6:24 PM
 */


namespace tests\unishop\controller;


use addons\unishop\controller\Address;
use addons\unishop\extend\PhpunitFunctionCustomize;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    use PhpunitFunctionCustomize;

    public function addressDataProvider()
    {
        return [
            [
                'name' => 'unishop1',
                'mobile' => self::$mobile,
                'address' => 'unishop1 address',
                'is_default' => 1,
                'province_id' => 1,
                'city_id' => 2,
                'area_id' => 3
            ],
            [
                'name' => 'unishop2',
                'mobile' => self::$mobile,
                'address' => 'unishop2 address',
                'is_default' => 1,
                'province_id' => 1964,
                'city_id' => 1988,
                'area_id' => 1991
            ],
            [
                'name' => 'unishop3',
                'mobile' => self::$mobile,
                'address' => 'unishop3 address',
                'is_default' => 1,
                'province_id' => 1964,
                'city_id' => 2017,
                'area_id' => 2024
            ],
        ];
    }


    /**
     * @test
     */
    public function all()
    {
        $this->userLogin();
        $contents = $this->request(Address::class, 'all', [
            'page' => 1,
            'pagesize' => 3
        ]);
        $this->assertIsArray($contents);
        $this->assertEquals(1, $contents['code']);
        $this->assertIsArray($contents['data']);

        if (empty($contents['data'])) {
            $this->assertEmpty($contents['data']);
        }

    }

    /**
     * @test
     * @dataProvider addressDataProvider
     */
    public function add($name, $mobile, $address, $is_default, $province_id, $city_id, $area_id)
    {
        $contents = $this->request(Address::class, 'add', [
            'name' => $name,
            'mobile' => $mobile,
            'address' => $address,
            'is_default' => $is_default,
            'province_id' => $province_id,
            'city_id' => $city_id,
            'area_id' => $area_id,
        ]);
        $this->assertIsArray($contents);
        $this->assertSame(1, $contents['code']);
    }

    /**
     * @test
     */
    public function allAgaint()
    {
        $contents = $this->request(Address::class, 'all', [
            'page' => 1,
            'pagesize' => 3
        ]);
        $this->assertIsArray($contents);
        $this->assertEquals(1, $contents['code']);
        $this->assertNotEmpty($contents['data']);

        foreach ($contents['data'] as $item) {
            $this->assertNotEmpty($item['id']);
            $this->assertNotEmpty($item['name']);
            $this->assertNotEmpty($item['mobile']);
            $this->assertNotEmpty($item['address']);
            $this->assertNotEmpty($item['province_id']);
            $this->assertNotEmpty($item['city_id']);
            $this->assertNotEmpty($item['area_id']);
            $this->assertGreaterThanOrEqual(0,$item['is_default']);
            $this->assertNotEmpty($item['province']['name']);
            $this->assertNotEmpty($item['city']['name']);
            $this->assertNotEmpty($item['area']['name']);
        }

        return $contents['data'][0];
    }


    /**
     * @test
     * @depends allAgaint
     */
    public function info(array $address)
    {
        $contents = $this->request(Address::class, 'info', [
            'id' => $address['id']
        ], 'get');

        $this->assertNotEmpty($contents);
        $this->assertEquals(1, $contents['code']);
        $this->assertEquals($address['user_id'], $contents['data']['user_id']);
        $this->assertEquals($address['id'], $contents['data']['id']);
        $this->assertNotEmpty($contents['data']['name']);
        $this->assertNotEmpty($contents['data']['mobile']);
        $this->assertNotEmpty($contents['data']['address']);
        $this->assertNotEmpty($contents['data']['province_id']);
        $this->assertNotEmpty($contents['data']['city_id']);
        $this->assertNotEmpty($contents['data']['area_id']);
        $this->assertEquals($address['is_default'], $contents['data']['is_default']);

        return $contents['data'];
    }

    /**
     * @test
     * @depends info
     */
    public function edit(array $address)
    {
        $address['name'] .= '-edit';
        $contents = $this->request(Address::class, 'edit', $address);

        $this->assertEquals(1, $contents['code']);
        $this->assertTrue($contents['data']);

        return $address;
    }



    /**
     * @test
     * @depends edit
     */
    public function delete(array $address)
    {
        $contents = $this->request(Address::class, 'delete', ['id' => $address['id']], 'get');
        $this->assertEquals(1, $contents['code']);
        $this->assertEquals(1, $contents['data']);

        $contents = $this->request(Address::class, 'delete', ['id' => $address['id']], 'get');
        $this->assertEquals(0, $contents['data']);
    }

    /**
     * @test
     */
    public function area()
    {
        $pid = 0;
        for ($i = 0; $i < 3; $i++) {
            $contents = $this->request(Address::class, 'area', ['pid' => $pid], 'get');

            $this->assertEquals(1, $contents['code']);
            $this->assertIsArray($contents['data']);
            $this->assertGreaterThan(0, count($contents['data']));
            $index = mt_rand(0, count($contents['data'])-1);
            $this->assertArrayHasKey('label', $contents['data'][$index]);
            $this->assertNotEmpty($contents['data'][$index]['id']);
            $this->assertArrayHasKey('pid', $contents['data'][$index]);
            $this->assertArrayHasKey('value', $contents['data'][$index]);
            $pid = $contents['data'][$index]['id'];
        }

    }

    public static function tearDownAfterClass()
    {
        $mobile = self::$mobile;
        \addons\unishop\model\Address::destroy(function($query) use ($mobile) {
            $query->where(['mobile' => $mobile]);
        });
    }
}
