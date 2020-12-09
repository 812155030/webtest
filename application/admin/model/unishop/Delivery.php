<?php

namespace app\admin\model\unishop;

use think\Model;
use think\Db;
use think\Exception;
use think\Request;

class Delivery extends Model
{


    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'unishop_delivery';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 是否上架
    const SWITCH_YES = 1;
    const SWITCH_NO = 0;

    // 追加属性
    protected $append = [
        'type_text'
    ];

    public function getTypeList()
    {
        return ['quantity' => __('Quantity'), 'weight' => __('Weight')];
    }

    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    /**
     * 关联配送模板区域及运费
     * @return \think\model\relation\HasMany
     */
    public function rule()
    {
        return $this->hasMany('DeliveryRule','delivery_id','id');
    }

    /**
     * 计费方式
     * @param $value
     * @return array
     */
    public function getMethodAttr($value)
    {
        $method = ['quantity' => '按件数', 'weight' => '按重量'];
        return ['text' => $method[$value], 'value' => $value];
    }

    /**
     * 获取全部
     * @return unied
     */
    public static function getAll()
    {
        $model = new static;
        return $model->select();
    }

    /**
     * 获取列表
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getList()
    {
        return $this->with(['rule'])
            ->order(['sort' => 'asc'])
            ->paginate(15, false, [
                'query' => Request::instance()->request()
            ]);
    }

    /**
     * 运费模板详情
     * @param $delivery_id
     * @return null|static
     * @throws \think\exception\DbException
     */
    public static function detail($delivery_id)
    {
        return self::get($delivery_id, ['rule']);
    }

    /**
     * 保存运费模板
     * @param array $params
     * @return false|int|void
     */
    public static function saveDelivery($params){

        Db::startTrans();
        try{
            $result = self::create($params,true);
            $data['delivery_id'] = $result['id'];
            foreach ($params['first'] as $k => $v){
                $data['area'] = $params['area'][$k];
                $data['first'] = $params['first'][$k];
                $data['first_fee'] = $params['first_fee'][$k];
                $data['additional'] = $params['additional'][$k];
                $data['additional_fee'] = $params['additional_fee'][$k];
                DeliveryRule::create($data,true);
            }
        }catch (Exception $e){
            Db::rollback();
            return false;
        }
        Db::commit();
        return true;
    }

    /**
     * 修改运费模板
     * @param $params
     * @param $ids
     * @return bool
     */
    public static function editDelivery($params,$ids){
        Db::startTrans();
        try{
            $model = new static;
            $model->update($params,['id'=>$ids],true);
            DeliveryRule::where('delivery_id',$ids)->delete();

            $data['delivery_id'] = $ids;
            foreach ($params['first'] as $k => $v){
                $data['area'] = $params['area'][$k];
                $data['first'] = $params['first'][$k];
                $data['first_fee'] = $params['first_fee'][$k];
                $data['additional'] = $params['additional'][$k];
                $data['additional_fee'] = $params['additional_fee'][$k];
                DeliveryRule::create($data,true);
            }
        }catch (Exception $e){
            Db::rollback();
            return false;
        }
        Db::commit();
        return true;
    }



}
