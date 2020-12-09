<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2019-07-14
 * Time: 22:45
 */

namespace app\admin\model\unishop;

use think\Model;

class DeliveryRule extends Model
{

    // 表名
    protected $name = 'unishop_delivery_rule';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';


    protected $append = ['area_content'];

    static $regionAll;
    static $regionTree;

    /**
     * 可配送区域
     * @param $value
     * @param $data
     * @return string
     */
    public function getAreaContentAttr($value, $data)
    {
        // 当前区域记录转换为数组
        $regionIds = explode(',', $data['area']);

        if (count($regionIds) === 373) return '全国';

        // 所有地区
        if (empty(self::$regionAll)) {
            self::$regionAll = Area::getCacheAll();
            self::$regionTree = Area::getCacheTree();
        }
        // 将当前可配送区域格式化为树状结构
        $alreadyTree = [];
        foreach ($regionIds as $regionId)
            $alreadyTree[self::$regionAll[$regionId]['pid']][] = $regionId;
        $str = '';
        foreach ($alreadyTree as $provinceId => $citys) {
            $str .= self::$regionTree[$provinceId]['name'];
            if (count($citys) !== count(self::$regionTree[$provinceId]['city'])) {
                $cityStr = '';
                foreach ($citys as $cityId)
                    $cityStr .= self::$regionTree[$provinceId]['city'][$cityId]['name'];
                $str .= ' (<span class="am-link-muted">' . mb_substr($cityStr, 0, -1, 'utf-8') . '</span>)';
            }
            $str .= '、';
        }
        return mb_substr($str, 0, -1, 'utf-8');
    }


}
