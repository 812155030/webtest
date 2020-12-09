<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2019/11/5
 * Time: 11:00 下午
 */

namespace addons\unishop\validate;

use think\Validate;

class Order extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'product_id' => 'require',
        'number' => 'require',
        'city_id' => 'require|integer',
        'delivery_id' => 'require|integer',
        'remark' => 'max:250',
        'address_id' => 'require',
        'flash_id' => 'require',
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'product_id.required' => '产品编号不能为空',
        'number.require' => '商品数量不能为空',
        'city_id.require' => '收货地址不能为空',
        'city_id.integer' => '收货地址格式错误',
        'delivery_id.require' => '请选择配送方式',
        'delivery_id.integer' => '配送方式格式错误',
        'remark.max' => '备注不能超过250个文字',
        'address_id.require' => '请选择收货地址',
        'flash_id.require' => '秒杀id不能为空',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'submit'  => ['product_id', 'number', 'city_id', 'address_id', 'delivery_id', 'remark'], // 创建订单
        'submitFlash'  => ['product_id', 'number', 'city_id', 'address_id', 'delivery_id', 'remark', 'flash_id'], // 秒杀创建订单
    ];

}
