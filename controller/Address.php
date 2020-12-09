<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2019/11/5
 * Time: 10:33 下午
 */


namespace addons\unishop\controller;

use \addons\unishop\model\Address as AddressModel;
use addons\unishop\model\Area;
use think\Cache;
use think\Exception;
use think\Loader;
use think\Validate;

/**
 * 收货地址接口
 * Class Address
 * @package addons\unishop\controller
 */
class Address extends Base
{
    /**
     * 允许频繁访问的接口
     * @var array
     */
    protected $frequently = ['area'];

    /**
     * 全部收货地址
     */
    public function all()
    {
        $page = $this->request->post('page', 1);
        $pagesize = $this->request->post('pagesize', 15);

        $data = (new AddressModel())
            ->with([
                'province' => function($query) {$query->field('id,name');},
                'city' => function($query) {$query->field('id,name');},
                'area' => function($query) {$query->field('id,name');}
            ])
            ->where('user_id', $this->auth->id)
            ->order(['is_default' => 'desc', 'id' => 'desc'])
            ->limit(($page - 1) * $pagesize, $pagesize)
            ->select();

        if ($data) {
            $msg = '';
            $data = collection($data)->toArray();
        } else {
            $msg = __('No address');
        }

        $this->success($msg, $data);
    }

    /**
     * 添加收货地址
     */
    public function add()
    {
        $data = $this->request->post();
        try {
            $validate = Loader::validate('\\addons\\unishop\\validate\\Address');
            if (!$validate->check($data, [], 'add')) {
                throw new Exception($validate->getError());
            }

            $data['user_id'] = $this->auth->id;

            $addressModel = new AddressModel();
            if ($data['is_default'] == 1) {
                $addressModel->allowField(true)->save(['is_default' => 0], ['user_id' => $data['user_id']]);
            }

            if ($addressModel->where(['user_id' => $this->auth->id])->count() > 49) {
                throw new Exception('不能添加超过50个地址');
            }

            $addressModel = new AddressModel();
            if (!$addressModel->allowField(true)->save($data)) {
                throw new Exception($addressModel->getError());
            } else {
                $this->success('添加成功', true);
            }
        } catch (Exception $e) {
            $this->error($e->getMessage(), false);
        }

    }

    /**
     * 修改收货地址
     */
    public function edit()
    {
        $data = $this->request->post();
        try {
            new Validate();
            $validate = Loader::validate('\\addons\\unishop\\validate\\Address');
            if (!$validate->check($data, [], 'edit')) {
                throw new Exception($validate->getError());
            }

            $addressModel = new AddressModel();
            $data['user_id'] = $this->auth->id;
            if ($data['is_default'] == 1) {
                $addressModel->allowField(true)->save(['is_default' => 0], ['user_id' => $data['user_id']]);
            }
            $data['updatetime'] = time();
            if (!$addressModel->allowField(true)->save($data,['id' => $data['id'], 'user_id' => $data['user_id']])) {
                throw new Exception($addressModel->getError());
            } else {
                $this->success('修改成功', true);
            }
        } catch (Exception $e) {
            $this->error($e->getMessage(), false);
        }
    }

    /**
     * 删除收货地址
     */
    public function delete()
    {
        $address_id = $this->request->get('id', 0);

        $data = (new AddressModel())
            ->where([
                'id' => $address_id,
                'user_id' => $this->auth->id
            ])
            ->delete();

        if ($data) {
            $this->success('删除成功', 1);
        } else {
            $this->success('没有数据', 0);
        }
    }

    /**
     * 获取地区信息
     */
    public function area()
    {
        $pid = $this->request->get('pid', 1);
        Cache::clear('area_pid_'.$pid);
        if (Cache::has('area_pid_'.$pid)) {
            $area = Cache::get('area_pid_'.$pid);
        } else {
            $areaModel = new Area();
            $area = $areaModel
                ->field('name as label,pid,id,code as value')
                ->where(['pid' => $pid])
                ->order(['pid' => 'asc', 'id' => 'asc'])
                ->select();

            if ($area) {
                $area = collection($area)->toArray();
                Cache::set('area_pid_'.$pid, $area, 60);
            }
        }
        $this->success('', $area);
    }

    /**
     * 获取单个收货地址
     */
    public function info()
    {
        $id = $this->request->get('id');
        $address = (new AddressModel())->where(['id' => $id, 'user_id' => $this->auth->id])->find()->toArray();
        $this->success('', $address);
    }

}
