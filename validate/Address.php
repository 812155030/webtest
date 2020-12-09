<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2019/11/5
 * Time: 11:00 下午
 */

namespace addons\unishop\validate;

use think\Validate;

class Address extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'id' => 'require|integer',
        'name' => 'require|max:30',
        'mobile' => 'require|number|max:20',
        'address' => 'require|max:255',
        'is_default' => 'integer',
        'province_id' => 'require',
        'city_id' => 'require',
        'area_id' => 'require',
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'name.required' => '名字必须填写',
        'name.max' => '名字不能大于30字',
        'mobile.required' => '电话号码必填',
        'mobile.number' => '电话号码必须为数字',
        'mobile.max' => '电话号码不能大于20字',
        'address.required' => '地址不能为空',
        'address.max' => '地址不能超过255字',
        'is_default.integer' => '默认地址格式不对',
        'province_id.require' => '请选择省份',
        'city_id.require' => '请选择城市',
        'area_id.require' => '请选择地区',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['name', 'mobile', 'address', 'is_default', 'province_id', 'city_id', 'area_id'],
        'edit' => ['id', 'name', 'mobile', 'address', 'is_default', 'province_id', 'city_id', 'area_id'],
    ];

}
