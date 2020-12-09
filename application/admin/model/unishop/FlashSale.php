<?php

namespace app\admin\model\unishop;

use addons\unishop\model\Product;
use think\Db;
use think\Exception;
use think\Model;
use traits\model\SoftDelete;


class FlashSale extends Model
{

    use SoftDelete;

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'unishop_flash_sale';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
        'starttime_text',
        'endtime_text',
        'current_state'
    ];

    // 已归档
    const STATUS_YES = 1; // 是
    const STATUS_NO = 0; // 否

    // 已上架
    const SWITCH_YES = 1; // 是
    const SWITCH_NO = 0; // 否

    /**
     * 获取当前状态
     */
    public function getCurrentStateAttr($value, $data)
    {
        $time = time();
        switch (true) {
            case $data['starttime'] > $time:
                $result = __('Not started');
                break;
            case $data['starttime'] <= $time && $time < $data['endtime']:
                $result = __('On going');
                break;
            case $time >= $data['endtime']:
                $result = __('Has ended');
                break;
            default:
                $result = __('Nothing');
        }
        return $result;
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStarttimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['starttime']) ? $data['starttime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getEndtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['endtime']) ? $data['endtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setStarttimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setEndtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    /**
     * 关联产品
     * @return \think\model\relation\HasMany
     */
    public function product()
    {
        return $this->hasMany('flashProduct', 'flash_id', 'id');
    }

    /**
     * 判断能不能修改
     * 已归档、已开始、上架状态的秒杀不能够修改。
     */
    public function checkItCanEdit()
    {
        if ($this['switch'] == self::SWITCH_YES || $this['status'] == self::STATUS_YES || $this['starttime'] < time()) {
            throw new Exception('已归档、已开始、上架状态的秒杀信息不能够修改。');
        }
        return true;
    }

    /**
     * 归档减库存
     */
    public function activityFiled($params, $specNumber)
    {
        $productExtend = new \addons\unishop\extend\Product;
        $key = 0;
        $prefix = \think\Config::get('database.prefix');
        foreach ($specNumber as $spec => $number) {
            $result = 0;
            if (is_numeric($spec) && $params[$key]['use_spec'] == Product::SPEC_OFF) {
                $result = Db::execute("UPDATE fa_unishop_product SET stock = stock-{$number}, real_sales = real_sales+{$number} WHERE id = {$params[$key]['id']}");
            } else if ($params[$key]['use_spec'] == Product::SPEC_ON) {
                $info = $productExtend->getBaseData($params[$key], $spec);

                // mysql<5.7.13时用
                //if (mysql < 5.7.13) {
                $spec = str_replace(',', '","', $spec);
                $search = '"stock":"' . $info['stock'] . '","value":["' . $spec . '"]';
                $stock = $info['stock'] - $number;
                $replace = '"stock":\"' . $stock . '\","value":["' . $spec . '"]';
                $sql = 'UPDATE ' . $prefix . "unishop_product SET stock = stock-{$number}, real_sales = real_sales+{$number}, `specTableList` = REPLACE(specTableList,'$search','$replace') WHERE id = {$params[$key]['id']}";
                $result = Db::execute($sql);
                //}

                //下面语句直接操作JSON
                //if (mysql >= 5.7.13) {
                //$info['stock'] -= $number;
                //$result = Db::execute("UPDATE fa_unishop_product SET stock = stock-{$number}, real_sales = real_sales+{$number}, specTableList = JSON_REPLACE(specTableList, '$[{$info['key']}].stock', {$info['stock']}) WHERE id = {$params[$key]['id']}");
                //}
            }
            if ($result == 0) { // 锁生效
                throw new Exception('失败');
            }
            $key++;
        }
    }
}
