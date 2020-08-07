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

/**
 * 员工管理
 * Class Personnel
 * @package app\work\controller
 */
class Personnel extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
        protected $table = 'Personnel';

    /**
     * 员工列表
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
        $this->title = '员工列表';
        $query = $this->_query($this->table)->like('name')->equal('d_id');
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
        $this->department = Db::name('Department')->where(['is_deleted' => '0'])->order('id desc')->select();
        foreach ($data as &$vo) {
            list($vo['team']) = [[]];
            foreach ($this->department as $team) if ($team['id'] === $vo['d_id']) $vo['team'] = $team;
        }
    }

    /**
     * 添加员工
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加员工';
        $this->_form($this->table, 'form');
    }

    /**
     * 编辑员工
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑员工';
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
     * 删除员工
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete($this->table);
    }

    /**
     * ajax获取部门下的人员
     */
    public function get_dep_per()
    {
        $d_id = $this->request->post('d_id');
        $list = Db::table('personnel')->where('d_id', $d_id)->where('is_deleted', 0)->order('id desc')->select();
        return json(['personnel' => $list]);
    }

}