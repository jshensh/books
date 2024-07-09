<?php
namespace app\controller;

use think\Request;
use think\facade\View;
use think\facade\Db;
use app\BaseController;
use app\service\Auth;
use app\model\Currency as CurrencyModel;

class Currency extends BaseController
{
    public function index(Request $request)
    {
        $msg = '';
        try {
            if ($request->isPost()) {
                $params = array_values($request->post('currency', []));

                if (is_array($params)) {
                    foreach ($params as &$param) {
                        $result = $this->validate($param, 'app\validate\Currency');
                        $param = array_filter($param, function($var) {
                            return in_array($var, ['code', 'name', 'scale', 'symbol', 'unit_name', 'sortid'], true);
                        }, ARRAY_FILTER_USE_KEY);
                    }
                    unset($param);
                }

                DB::table('currency')->delete(true);
                DB::table('currency')->insertAll($params);
                $msg = '币种更新成功';
            }
        } catch (\think\exception\ValidateException $e) {
            $msg = "数据错误，{$e->getMessage()}";
        }
        View::assign('currency', CurrencyModel::order('sortid')->select());
        View::assign('msg', $msg);
        return View::fetch();
    }
}