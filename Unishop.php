<?php

namespace addons\unishop;

use app\common\library\Menu;
use think\Addons;
use think\Loader;

/**
 * 插件
 */
class unishop extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        $menu = [
            0 =>
                [
                    'name' => 'unishop',
                    'title' => 'uniShop移动商城',
                    'icon' => 'fa fa-font-awesome',
                    'remark' => '',
                    'sublist' =>
                        [
                            0 =>
                                [
                                    'name' => 'unishop/ads',
                                    'title' => '广告图管理',
                                    'remark' => '',
                                    'icon' => 'fa fa-buysellads',
                                    'sublist' =>
                                        [
                                            0 =>
                                                [
                                                    'name' => 'unishop/ads/index',
                                                    'title' => '查看',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            1 =>
                                                [
                                                    'name' => 'unishop/ads/add',
                                                    'title' => '添加',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            2 =>
                                                [
                                                    'name' => 'unishop/ads/edit',
                                                    'title' => '编辑',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            3 =>
                                                [
                                                    'name' => 'unishop/ads/del',
                                                    'title' => '删除',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            4 =>
                                                [
                                                    'name' => 'unishop/ads/multi',
                                                    'title' => '批量更新',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                        ],
                                ],
                            1 =>
                                [
                                    'name' => 'unishop/category',
                                    'title' => '分类管理',
                                    'remark' => '注意：产品只支持二级分类。',
                                    'icon' => 'fa fa-align-justify',
                                    'sublist' =>
                                        [
                                            0 =>
                                                [
                                                    'name' => 'unishop/category/index',
                                                    'title' => '查看',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            1 =>
                                                [
                                                    'name' => 'unishop/category/del',
                                                    'title' => '删除',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            2 =>
                                                [
                                                    'name' => 'unishop/category/edit',
                                                    'title' => '修改',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            3 =>
                                                [
                                                    'name' => 'unishop/category/add',
                                                    'title' => '添加',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            4 =>
                                                [
                                                    'name' => 'unishop/category/multi',
                                                    'title' => '批量操作',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                        ],
                                ],
                            2 =>
                                [
                                    'name' => 'unishop/product',
                                    'title' => '产品管理',
                                    'remark' => '',
                                    'icon' => 'fa fa-product-hunt',
                                    'sublist' =>
                                        [
                                            0 =>
                                                [
                                                    'name' => 'unishop/product/index',
                                                    'title' => '查看',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            1 =>
                                                [
                                                    'name' => 'unishop/product/add',
                                                    'title' => '添加',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            2 =>
                                                [
                                                    'name' => 'unishop/product/edit',
                                                    'title' => '编辑',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            3 =>
                                                [
                                                    'name' => 'unishop/product/del',
                                                    'title' => '删除',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            4 =>
                                                [
                                                    'name' => 'unishop/product/multi',
                                                    'title' => '批量更新',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            5 =>
                                                [
                                                    'name' => 'unishop/product/recyclebin',
                                                    'title' => '回收站',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            6 =>
                                                [
                                                    'name' => 'unishop/product/destroy',
                                                    'title' => '真是删除',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            7 =>
                                                [
                                                    'name' => 'unishop/product/restore',
                                                    'title' => '还原',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            8 =>
                                                [
                                                    'name' => 'unishop/product/copy',
                                                    'title' => '复制',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                        ],
                                ],
                            3 =>
                                [
                                    'name' => 'unishop/delivery',
                                    'title' => '运费模板',
                                    'remark' => '',
                                    'icon' => 'fa fa-delicious',
                                    'sublist' =>
                                        [
                                            0 =>
                                                [
                                                    'name' => 'unishop/delivery/index',
                                                    'title' => '查看',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            1 =>
                                                [
                                                    'name' => 'unishop/delivery/add',
                                                    'title' => '添加',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            2 =>
                                                [
                                                    'name' => 'unishop/delivery/edit',
                                                    'title' => '编辑',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            3 =>
                                                [
                                                    'name' => 'unishop/delivery/del',
                                                    'title' => '删除',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            4 =>
                                                [
                                                    'name' => 'unishop/delivery/multi',
                                                    'title' => '批量更新',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                        ],
                                ],
                            4 =>
                                [
                                    'name' => 'unishop/config',
                                    'title' => '系统配置',
                                    'remark' => '更新配置缓存不会立即生效，如需立即生效请清空缓存。',
                                    'icon' => 'fa fa-certificate',
                                    'sublist' =>
                                        [
                                            0 =>
                                                [
                                                    'name' => 'unishop/config/index',
                                                    'title' => '查看',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            1 =>
                                                [
                                                    'name' => 'unishop/config/add',
                                                    'title' => '添加',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            2 =>
                                                [
                                                    'name' => 'unishop/config/edit',
                                                    'title' => '编辑',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            3 =>
                                                [
                                                    'name' => 'unishop/config/del',
                                                    'title' => '删除',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            4 =>
                                                [
                                                    'name' => 'unishop/config/multi',
                                                    'title' => '批量更新',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                        ],
                                ],
                            5 =>
                                [
                                    'name' => 'unishop/market',
                                    'title' => '营销中心',
                                    'remark' => '',
                                    'icon' => 'fa fa-list',
                                    'sublist' =>
                                        [
                                            0 =>
                                                [
                                                    'name' => 'unishop/market/coupon',
                                                    'title' => '优惠券管理',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-gratipay',
                                                    'sublist' =>
                                                        [
                                                            0 =>
                                                                [
                                                                    'name' => 'unishop/market/coupon/index',
                                                                    'title' => '查看',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            1 =>
                                                                [
                                                                    'name' => 'unishop/market/coupon/add',
                                                                    'title' => '添加',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            2 =>
                                                                [
                                                                    'name' => 'unishop/market/coupon/edit',
                                                                    'title' => '编辑',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            3 =>
                                                                [
                                                                    'name' => 'unishop/market/coupon/del',
                                                                    'title' => '删除',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            4 =>
                                                                [
                                                                    'name' => 'unishop/market/coupon/multi',
                                                                    'title' => '批量更新',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            5 =>
                                                                [
                                                                    'name' => 'unishop/market/coupon/recyclebin',
                                                                    'title' => '回收站',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            6 =>
                                                                [
                                                                    'name' => 'unishop/market/coupon/destroy',
                                                                    'title' => '真实删除',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            7 =>
                                                                [
                                                                    'name' => 'unishop/market/coupon/restore',
                                                                    'title' => '还原',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                        ],
                                                ],
                                            1 =>
                                                [
                                                    'name' => 'unishop/market/flash_sale',
                                                    'title' => '秒杀管理',
                                                    'remark' => '1，归档结束会把商品的真实售量和剩余数量同步到对应商品。
2，已归档、已开始、上架状态的秒杀信息不能够修改。
3，商品列表下架的商品也可以参与秒杀，建议复制一份商品专门提供给秒杀使用。
4，秒杀进行中的商品可以单个下架。
5，必须启动redis才能使用秒杀功能',
                                                    'icon' => 'fa fa-flag',
                                                    'sublist' =>
                                                        [
                                                            0 =>
                                                                [
                                                                    'name' => 'unishop/market/flash_sale/index',
                                                                    'title' => '查看',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            1 =>
                                                                [
                                                                    'name' => 'unishop/market/flash_sale/add',
                                                                    'title' => '添加',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            2 =>
                                                                [
                                                                    'name' => 'unishop/market/flash_sale/edit',
                                                                    'title' => '编辑',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            3 =>
                                                                [
                                                                    'name' => 'unishop/market/flash_sale/del',
                                                                    'title' => '删除',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            4 =>
                                                                [
                                                                    'name' => 'unishop/market/flash_sale/multi',
                                                                    'title' => '批量更新',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            5 =>
                                                                [
                                                                    'name' => 'unishop/market/flash_sale/recyclebin',
                                                                    'title' => '回收站',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            6 =>
                                                                [
                                                                    'name' => 'unishop/market/flash_sale/destroy',
                                                                    'title' => '真实删除',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            7 =>
                                                                [
                                                                    'name' => 'unishop/market/flash_sale/restroy',
                                                                    'title' => '还原',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                            8 =>
                                                                [
                                                                    'name' => 'unishop/market/flash_product/multi',
                                                                    'title' => '更新秒杀产品状态',
                                                                    'icon' => 'fa fa-circle-o',
                                                                ],
                                                        ],
                                                ],
                                        ],
                                ],
                            6 =>
                                [
                                    'name' => 'unishop/order',
                                    'title' => '订单管理',
                                    'remark' => '1，货到付款默认支付状态为已支付，请留意发快递的时候选择收付模式。',
                                    'icon' => 'fa fa-print',
                                    'sublist' =>
                                        [
                                            0 =>
                                                [
                                                    'name' => 'unishop/order/delivery',
                                                    'title' => '物流管理',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            1 =>
                                                [
                                                    'name' => 'unishop/order/multi',
                                                    'title' => '批量更新',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            2 =>
                                                [
                                                    'name' => 'unishop/order/restore',
                                                    'title' => '还原',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            3 =>
                                                [
                                                    'name' => 'unishop/order/destroy',
                                                    'title' => '真实删除',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            4 =>
                                                [
                                                    'name' => 'unishop/order/del',
                                                    'title' => '删除',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            5 =>
                                                [
                                                    'name' => 'unishop/order/edit',
                                                    'title' => '编辑',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            6 =>
                                                [
                                                    'name' => 'unishop/order/recyclebin',
                                                    'title' => '回收站',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            7 =>
                                                [
                                                    'name' => 'unishop/order/index',
                                                    'title' => '查看',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            8 =>
                                                [
                                                    'name' => 'unishop/order/product',
                                                    'title' => '商品管理',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            9 =>
                                                [
                                                    'name' => 'unishop/order/refund',
                                                    'title' => '退货',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                        ],
                                ],
                            7 =>
                                [
                                    'name' => 'unishop/evaluate',
                                    'title' => '商品评价管理',
                                    'remark' => '',
                                    'icon' => 'fa fa-commenting',
                                    'sublist' =>
                                        [
                                            0 =>
                                                [
                                                    'name' => 'unishop/evaluate/index',
                                                    'title' => '查看',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            1 =>
                                                [
                                                    'name' => 'unishop/evaluate/recyclebin',
                                                    'title' => '回收站',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            2 =>
                                                [
                                                    'name' => 'unishop/evaluate/add',
                                                    'title' => '添加',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            3 =>
                                                [
                                                    'name' => 'unishop/evaluate/edit',
                                                    'title' => '编辑',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            4 =>
                                                [
                                                    'name' => 'unishop/evaluate/del',
                                                    'title' => '删除',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            5 =>
                                                [
                                                    'name' => 'unishop/evaluate/destroy',
                                                    'title' => '真实删除',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            6 =>
                                                [
                                                    'name' => 'unishop/evaluate/restore',
                                                    'title' => '还原',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                            7 =>
                                                [
                                                    'name' => 'unishop/evaluate/multi',
                                                    'title' => '批量更新',
                                                    'remark' => '',
                                                    'icon' => 'fa fa-circle-o',
                                                    'sublist' =>
                                                        [
                                                        ],
                                                ],
                                        ],
                                ],
                        ],
                ],
        ];
        Menu::create($menu);
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        Menu::delete('unishop');
        return true;
    }

    /**
     * 插件启用方法
     * @return bool
     */
    public function enable()
    {
        Menu::enable('unishop');
        return true;
    }

    /**
     * 插件禁用方法
     * @return bool
     */
    public function disable()
    {
        Menu::disable('unishop');
        return true;
    }


    public function appInit()
    {
        Loader::addNamespace('Hashids', ADDON_PATH . 'unishop' . DS . 'library' . DS . 'Hashids' . DS);
        Loader::addNamespace('Godruoyi\Snowflake', ADDON_PATH . 'unishop' . DS . 'library' . DS . 'Godruoyi' . DS . 'Snowflake' . DS);
        Loader::addNamespace('Yansongda', ADDON_PATH . 'epay' . DS . 'library' . DS . 'Yansongda' . DS);
    }
}
