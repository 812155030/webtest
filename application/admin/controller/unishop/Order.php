<?php

namespace app\admin\controller\unishop;

use app\admin\model\unishop\Area;
use app\admin\model\unishop\OrderRefund;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Hook;

/**
 * 订单管理
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{
    /**
     * 是否是关联查询
     */
    protected $relationSearch = true;

    /**
     * Order模型对象
     * @var \app\admin\model\unishop\Order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\unishop\Order;
        $this->view->assign("payTypeList", $this->model->getPayTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("refundStatusList", $this->model->getRefundStatusList());
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->alias('order')
                ->join('user', 'user.id = order.user_id')
                ->where($where)
                ->count();

            $list = $this->model
                ->alias('order')
                ->join('user', 'user.id = order.user_id')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field('order.*,user.username')
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as &$item) {
                $item['id'] = (string)$item['id']; // 整形数字太大js会失准
                $item['user'] = [];
                $item['user']['username'] = $item['username'] ? $item['username'] : __('Tourist');

                $item['have_paid_status'] = $item['have_paid'];
                $item['have_delivered_status'] = $item['have_delivered'];
                $item['have_received_status'] = $item['have_received'];
                $item['have_commented_status'] = $item['have_commented'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 生成查询所需要的条件,排序方式
     * @param mixed $searchfields 快速查询的字段
     * @param boolean $relationSearch 是否关联查询
     * @return array
     */
    protected function buildparams($searchfields = null, $relationSearch = null)
    {
        $searchfields = is_null($searchfields) ? $this->searchFields : $searchfields;
        $relationSearch = is_null($relationSearch) ? $this->relationSearch : $relationSearch;
        $search = $this->request->get("search", '');
        $filter = $this->request->get("filter", '');
        $op = $this->request->get("op", '', 'trim');
        $sort = $this->request->get("sort", "id");
        $order = $this->request->get("order", "DESC");
        $offset = $this->request->get("offset", 0);
        $limit = $this->request->get("limit", 0);
        $filter = (array)json_decode($filter, true);
        $op = (array)json_decode($op, true);
        $filter = $filter ? $filter : [];
        $where = [];
        $tableName = '';
        if ($relationSearch) {
            if (!empty($this->model)) {
                $name = \think\Loader::parseName(basename(str_replace('\\', '/', get_class($this->model))));
                $tableName = '' . $name . '.';
            }
            $sortArr = explode(',', $sort);
            foreach ($sortArr as $index => & $item) {
                $item = stripos($item, ".") === false ? $tableName . trim($item) : $item;
            }
            unset($item);
            $sort = implode(',', $sortArr);
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $where[] = [$tableName . $this->dataLimitField, 'in', $adminIds];
        }
        if ($search) {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v) {
                $v = stripos($v, ".") === false ? $tableName . $v : $v;
            }
            unset($v);
            $where[] = [implode("|", $searcharr), "LIKE", "%{$search}%"];
        }
        foreach ($filter as $k => $v) {
            // 搜索订单状态
            if (in_array($k, ['have_paid_status', 'have_delivered_status', 'have_received_status', 'have_commented_status'])) {
                switch ($k) {
                    case 'have_paid_status':
                        $k = 'have_paid';
                        break;
                    case 'have_delivered_status':
                        $k = 'have_delivered';
                        break;
                    case 'have_received_status':
                        $k = 'have_received';
                        break;
                    case 'have_commented_status':
                        $k = 'have_commented';
                        break;
                }
                $v == 0 ? ($op[$k] = '=') : ($op[$k] = '>');
                $v = 0;
            }


            $sym = isset($op[$k]) ? $op[$k] : '=';
            if (stripos($k, ".") === false) {
                $k = $tableName . $k;
            }
            $v = !is_array($v) ? trim($v) : $v;
            $sym = strtoupper(isset($op[$k]) ? $op[$k] : $sym);
            switch ($sym) {
                case '=':
                case '<>':
                    $where[] = [$k, $sym, (string)$v];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                case 'LIKE %...%':
                case 'NOT LIKE %...%':
                    $where[] = [$k, trim(str_replace('%...%', '', $sym)), "%{$v}%"];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$k, $sym, intval($v)];
                    break;
                case 'FINDIN':
                case 'FINDINSET':
                case 'FIND_IN_SET':
                    $where[] = "FIND_IN_SET('{$v}', " . ($relationSearch ? $k : '`' . str_replace('.', '`.`', $k) . '`') . ")";
                    break;
                case 'IN':
                case 'IN(...)':
                case 'NOT IN':
                case 'NOT IN(...)':
                    $where[] = [$k, str_replace('(...)', '', $sym), is_array($v) ? $v : explode(',', $v)];
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'BETWEEN' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'BETWEEN' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, $sym, $arr];
                    break;
                case 'RANGE':
                case 'NOT RANGE':
                    $v = str_replace(' - ', ',', $v);
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'RANGE' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'RANGE' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, str_replace('RANGE', 'BETWEEN', $sym) . ' time', $arr];
                    break;
                case 'LIKE':
                case 'LIKE %...%':
                    $where[] = [$k, 'LIKE', "%{$v}%"];
                    break;
                case 'NULL':
                case 'IS NULL':
                case 'NOT NULL':
                case 'IS NOT NULL':
                    $where[] = [$k, strtolower(str_replace('IS ', '', $sym))];
                    break;
                default:
                    break;
            }
        }
        $where = function ($query) use ($where) {
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    call_user_func_array([$query, 'where'], $v);
                } else {
                    $query->where($v);
                }
            }
        };
        return [$where, $sort, $order, $offset, $limit];
    }


    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }

                    $updatetime = $this->request->post('updatetime');
                    // 乐观锁
                    $result = $this->model->allowField(true)->save($params, ['id' => $ids, 'updatetime' => $updatetime]);
                    if (!$result) {
                        throw new Exception(__('Data had been update before saved, close windows and do it again'));
                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


    /**
     * 物流管理
     */
    public function delivery($ids = null)
    {
        $row = $this->model->get($ids, ['extend']);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $result = false;
            Db::startTrans();
            try {
                $express_number = $this->request->post('express_number');
                $have_delivered = $express_number ? time() : 0;
                $res1 = $row->allowField(true)->save(['have_delivered' => $have_delivered]);
                $res2 = $row->extend->allowField(true)->save(['express_number' => $express_number]);
                if ($res1 && $res2) {
                    $result = true;
                } else {
                    throw new Exception(__('No rows were updated'));
                }
                Db::commit();
            } catch (ValidateException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success();
            } else {
                $this->error(__('No rows were updated'));
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $address = json_decode($row->extend->address_json,true);
        if ($address) {
            $area = (new Area)->whereIn('id',[$address['province_id'],$address['city_id'],$address['area_id']])->column('name', 'id');
            $row['addressText'] = $area[$address['province_id']].$area[$address['city_id']].$area[$address['area_id']].' '.$address['address'];
            $row['address'] = $address;
        }

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 商品管理
     */
    public function product($ids = null)
    {
        if ($this->request->isPost()) {
            $this->success();
        }
        $row = $this->model->get($ids, ['product','evaluate']);
        $this->view->assign('product', $row->product);
        $evaluate = [];
        foreach ($row->evaluate as $key => $item) {
            $evaluate[$item['product_id']] = $item;
        }

        $this->view->assign('order', $row);
        $this->view->assign('evaluate', $evaluate);
        return $this->view->fetch();
    }

    /**
     * 退货管理
     */
    public function refund($ids = null)
    {
        $row = $this->model->get($ids, ['refund']);
        if ($row['status'] != \app\admin\model\unishop\Order::STATUS_REFUND) {
            $this->error(__('This order is not returned'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {

                    // 退款
                    if($params['refund_action'] == 1) {
                        $params['had_refund'] = time();
                        Hook::add('order_refund', 'addons\\unishop\\behavior\\Order');
                    }

                    $updatetime = $this->request->post('updatetime');
                    // 乐观锁
                    $result = $this->model->allowField(true)->save($params, ['id' => $ids, 'updatetime' => $updatetime]);
                    if (!$result) {
                        throw new Exception(__('Data had been update before saved, close windows and do it again'));
                    }

                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    Hook::listen('order_refund', $row);
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $products = $row->product;
        $refundProducts = $row->refundProduct;
        foreach ($products as &$product) {
            $product['choose'] = 0;
            foreach ($refundProducts as $refundProduct) {
                if ($product['id'] == $refundProduct['order_product_id']) {
                    $product['choose'] = 1;
                }
            }
        }
        if ($row->refund) {
            $refund = $row->refund->append(['receiving_status_text', 'service_type_text'])->toArray();
        } else {
            $refund = [
                'service_type' => 0,
                'express_number' => -1,
                'receiving_status_text' => -1,
                'receiving_status' => -1,
                'service_type_text' => -1,
                'amount' => -1,
                'reason_type' => -1,
                'refund_explain' => -1,
            ];
        }
        $this->view->assign('row', $row);
        $this->view->assign('product', $products);
        $this->view->assign('refund', $refund);
        return $this->view->fetch();
    }

    /**
     * 回收站
     */
    public function recyclebin()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->onlyTrashed()
                ->alias('order')
                ->join('user', 'user.id = order.user_id')
                ->where($where)
                ->count();

            $list = $this->model
                ->onlyTrashed()
                ->alias('order')
                ->join('user', 'user.id = order.user_id')
                ->where($where)
                ->field('order.*,user.username')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as &$item) {
                $item['id'] = (string)$item['id'];
                $item['user'] = [];
                $item['user']['username'] = $item['username'] ? $item['username'] : __('Tourist');

                $item['have_paid_status'] = $item['have_paid'];
                $item['have_delivered_status'] = $item['have_delivered'];
                $item['have_received_status'] = $item['have_received'];
                $item['have_commented_status'] = $item['have_commented'];
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}
