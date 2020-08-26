<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\work\controller;

use library\Controller;
use think\Db;
use library\tools\Csv;

/**
 * 租借管理
 * Class BorrowRepay
 * @package app\work\controller
 */
class BorrowRepay extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'BorrowRepay';

    /**
     * 租借列表
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index()
    {
        $this->title = '租借列表';
        $query = $this->_query($this->table)->like('g_name,g_code')->dateBetween('start_time,end_time');
        $query->where(['is_deleted' => '0'])->order('id desc')->page();
    }

    /**
     * 数据列表处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _index_page_filter(&$data)
    {
        $personnel = Db::name('Personnel')->whereIn('id', array_unique(array_column($data, 'per_id')))->select();
        $department = Db::name('Department')->whereIn('id', array_unique(array_column($personnel, 'd_id')))->select();
        foreach ($data as &$vo) {
            list($vo['personnel']) = [[]];
            foreach ($personnel as $per) {
                if ($per['id'] === $vo['per_id']) {
                    foreach ($department as $dep) {
                        if ($dep['id'] === $per['d_id']) {
                            $per['department'] = $dep;
                        }
                    }
                    $vo['personnel'] = $per;
                }
            }
        }
    }

    /**
     * 工具租借详情
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function detail()
    {
        if ($this->request->isPost()) {
            $code = $this->request->post('code');
            $goods_info = Db::name('Goods')->where('code', trim($code))->where('is_deleted', 0)->find();
            if (!empty($goods_info)) {
                $goods_info['c_name'] = Db::name('GoodsCate')->where('id', $goods_info['c_id'])->value('name');
                if ($goods_info['status'] == 2) {
                    $borrow_repay = Db::name($this->table)->where(['g_id' => $goods_info['id']])->order('id desc')->find();
                    $borrow_repay['per_name'] = Db::name('Personnel')->where('id', $borrow_repay['per_id'])->value('name');
                    $goods_info['borrow_repay'] = $borrow_repay;
                }else{
                    $goods_info['borrow_repay'] = [];
                    $personnel = Db::name('Personnel')->where('is_deleted', 0)->select();
                    $goods_info['personnel'] = $personnel;
                    $department = Db::name('Department')->where('is_deleted', 0)->select();
                    $goods_info['department'] = $department;
                }
                $this->fetch('detail', ['vo' => $goods_info]);
            }else{
                $this->error('暂无此编码工具信息，请检查后重试！');
            }
        }else{
            $this->fetch();
        }
    }

    /**
     * 租借
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function lease()
    {
        $data = $this->request->post();
        if (!empty($data['mark'])) {
            session('lease_mark', $data['mark']);
        }
        if (!empty($data['d_id'])) {
            session('lease_d_id', $data['d_id']);
            unset($data['d_id']);
        }
        if (!empty($data['per_id'])) {
            session('lease_per_id', $data['per_id']);
        }
        $res = Db::table('borrow_repay')->insert($data);
        $res1 = Db::table('goods')->where('id', $data['g_id'])->update(['status' => 2, 'use_num' => Db::raw('use_num+1')]);
        $url = url('@admin') . '#' . url('work/borrow_repay/detail') . '?spm=m-' . rand();
//        $url = url('@admin') . '#' . url('work/borrow_repay/detail'). '?spm=' . $this->request->get('spm');
        $this->success('操作成功', $url);
    }

    /**
     * 归还
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function lease_back()
    {
        $data = $this->request->post();
        $status_ = $data['status_'];
        unset($data['status_']);
        $res = Db::table('borrow_repay')->where('id', $data['id'])->update($data);
        $res1 = Db::table('goods')->where('id', $data['g_id'])->update(['status' => 1, 'status_' => $status_]);
        $url = url('@admin') . '#' . url('work/borrow_repay/detail') . '?spm=m-' . rand();
        $this->success('操作成功', $url);
    }

    /**
     * 编辑租借
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑租借';
        $this->_form($this->table, 'form');
    }

    /**
     * 表单数据处理
     * @param array $data
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function _form_filter(&$data)
    {
        if ($this->request->isGet()) {
            $this->personnel = Db::name('Personnel')->where('is_deleted', 0)->order('id desc')->select();
            $this->goods = Db::name('Goods')->where('is_deleted', 0)->order('id desc')->select();
        }
    }

    /**
     * 删除租借
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete($this->table);
    }

    /**
     * 导出
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function export()
    {
        $query = $this->_query($this->table)->like('g_name,g_code')->dateBetween('start_time,end_time');
        $list = $query->where(['is_deleted' => '0'])->order('id desc')->page(false,false);

        $Csv = new Csv();
        $header = ['工具名称','工具编码','租借人','部门','租借时间','归还时间','备注'];
        $rules = ['g_name','g_code','personnel.name','personnel.department.name','start_time','end_time','mark'];
        $Csv->header('租借列表.csv',$header);
        $Csv->body($list['list'], $rules);
    }

    /**
     * 数据列表处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _export_page_filter(&$data)
    {
        $personnel = Db::name('Personnel')->whereIn('id', array_unique(array_column($data, 'per_id')))->select();
        $department = Db::name('Department')->whereIn('id', array_unique(array_column($personnel, 'd_id')))->select();
        foreach ($data as &$vo) {
            list($vo['personnel']) = [[]];
            foreach ($personnel as $per) {
                if ($per['id'] === $vo['per_id']) {
                    foreach ($department as $dep) {
                        if ($dep['id'] === $per['d_id']) {
                            $per['department'] = $dep;
                        }
                    }
                    $vo['personnel'] = $per;
                }
            }
            $vo['start_time'] = $vo['start_time'] . "\t";
            $vo['end_time'] = $vo['end_time'] . "\t";
        }
    }

}