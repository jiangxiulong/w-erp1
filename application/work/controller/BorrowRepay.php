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
     * 租借管理
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
     * 添加租借
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加租借';
        $this->_form($this->table, 'form');
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
            $this->department = Db::name('Department')->where(['is_deleted' => '0'])->order('id desc')->select();
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
        $header = ['物品名称','物品编码','租借人','部门','租借时间','归还时间','备注'];
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