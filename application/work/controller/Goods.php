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
 * 物品管理
 * Class Goods
 * @package app\work\controller
 */
class Goods extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
        protected $table = 'Goods';

    /**
     * 物品管理
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
        $this->title = '物品列表';
        $query = $this->_query($this->table)->like('name,code')->equal('c_id');
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
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $this->goodscate = Db::name('GoodsCate')->where(['is_deleted' => '0'])->order('id desc')->select();
        foreach ($data as &$vo) {
            list($vo['team']) = [[]];
            foreach ($this->goodscate as $cate) if ($cate['id'] === $vo['c_id']) $vo['cate'] = $cate;
            //设置图片名称
            $imageName = 'goodsbarcode'."_".$vo['id'].'.png';
            $code_png_url = '/upload/barcode/'.$imageName;
            if (!file_exists($code_png_url)) {
                $barcode = $generator->getBarcode($vo['code'], $generator::TYPE_CODE_128);
                $this->base64topng($imageName,$barcode);
            }
            $vo['code_png'] = $code_png_url;
        }
    }

    /**
     * base64保存为图片
     */
    public function base64topng($imageName,$image){
        //设置图片保存路径
        $path = "../public/upload/barcode";

        //判断目录是否存在 不存在就创建
        if (!is_dir($path)){
            mkdir($path,0777,true);
        }

        //图片路径
        $imageSrc= $path."/". $imageName;

        //生成文件夹和图片
        $r = file_put_contents($imageSrc, $image);
        if (!$r) {
            return false;
        }else {
            return true;
        }
    }

    /**
     * 添加物品
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加物品';
        $this->_form($this->table, 'form');
    }

    /**
     * 编辑物品
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑物品';
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
            $this->goodscate = Db::name('GoodsCate')->where(['is_deleted' => '0'])->order('id desc')->select();
        }
    }

    /**
     * 删除物品
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete($this->table);
    }

    /**
     * 导入物品
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function import()
    {
        $file = request()->file('file');
        $info = $file->move('../public/upload/tmp');
        $fiele_name = explode('.', $info->getInfo()['name'])[0];
        if ($info) {
            // 成功上传后 获取上传信息
            $path = '../public/upload/tmp/' . $info->getSaveName();

            $type = 'Excel2007';//设置为Excel5代表支持2003或以下版本，Excel2007代表2007版
            $xlsReader = \PHPExcel_IOFactory::createReader($type);
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($path);
            // 数据
            $data = $Sheets->getSheet(0)->toArray();
            array_shift($data);
            foreach ($data as $value) {
                $new_data[] = [
                    'name' => $value[1],
                    'code' => $value[2],
                    'c_id' => $value[3],
                ];
            }
            if (isset($new_data) && !empty($new_data)) {
                $res = Db::name('Goods')->insertAll($new_data);
            }else{
                $res = false;
            }
            if ($res) {
                return json(['code' => 1, 'msg' => '导入成功']);
            }else{
                return json(['code' => 0, 'msg' => '导入失败']);
            }
        } else {
            // 上传失败获取错误信息
            $data = ['code' => 0, 'msg' => $file->getError()];
            return json($data);
        }
    }

}