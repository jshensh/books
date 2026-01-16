<?php
namespace app\controller;

use think\Request;
use think\facade\View;
use think\facade\Session;
use app\BaseController;
use app\service\Auth;
use app\service\Data as DataService;
use app\model\Loan as LoanModel;
use app\model\Transactions as TransactionsModel;
use app\model\Transactmode as TransactmodeModel;
use app\model\TransferRequest as TransferRequestModel;

class Longbridge extends BaseController
{
    public function index(Request $request, DataService $dbData)
    {
        $detail = [];
        $msg = '';
        
        if ($request->isPost()) {
            try {
                if ($request->file('pdf')) {
                    $this->validate($request->file(), ['pdf' => ['file', 'fileMime' => 'application/pdf']]);
                    $password = str_replace('"', '\\"', $request->post('password'));
                    $password = $password ? "--password={$password} " : '';
                    exec("/usr/bin/qpdf {$password}--decrypt " . $request->file('pdf')->getPathname() . ' ' . $request->file('pdf')->getPathname() . '2');

                    if (!file_exists($request->file('pdf')->getPathname() . '2')) {
                        throw new \Exception('文件密码错误');
                    }
                    
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($request->file('pdf')->getPathname() . '2');
                    $text = str_replace("\t", "", $pdf->getText());
                    // preg_match('/(\d{4}\.\d{2}\.\d{2})\\n综合账户⽇结单/s', $text, $date);
                    // dump($date);
                    $text = preg_replace('/\n[^\n]*?Page \d+ of \d+\n\d{4}\.\d{2}\.\d{2}\n综合账户⽇结单\n/s', '', $text);
                    $text = str_replace('⼊', '入', $text);
                    // preg_match_all('/  (.*?)([\-\.\d%]+|N\/A) +([\-\.\d,%]+|N\/A) +([\-\.\d,%]+|N\/A) +([\-\.\d,%]+|N\/A) +([\-\.\d,%]+|N\/A) +([\-\.\d,%]+|N\/A) +([\-\.\d,%]+|N\/A) +([\-\.\d,%]+|N\/A) +([\-\.\d,%]+|N\/A)\n/s', substr($text, 0, strpos($text, '下单时间')), $holding);
                    // dump($holding);
                    preg_match_all('/(?<order_date>\d{4}\.\d{2}\.\d{2}) (\d{4}\.\d{2}\.\d{2}) +(?<id>OS\d+) +(?<direction>.*?) +(?<instrument>.*?)(?: +|\n)(?<quantity>[\.\d,]+) +(?<price>[\.\d,]+) +(?<transcation_amount>[\.\d,]+) +(?<change_amount>[\-\.\d,]+)\n下单时间 成交时间数量 平均价格\n(?<order_time>[\d:]{8}) EST/s', $text, $detail, PREG_SET_ORDER);
                    $detail = array_map(function($v) {
                        return array_filter($v, function($k) {
                            return is_string($k);
                        },ARRAY_FILTER_USE_KEY);
                    }, $detail);
                    preg_match_all('/OS\d+/', $text, $id);
                    
                    unlink($request->file('pdf')->getPathname() . '2');
                    
                    if (count($id[0]) !== count($detail)) {
                        // dump($text);
                        throw new \Exception('日结单解析失败');
                    }
                } else {
                    $this->validate($request->post(), ['__token__' => 'token', 'realCommandJson' => ['require']]);
                    
                    $params = json_decode($request->post('realCommandJson'), 1);
                    if (is_array($params)) {
                        foreach ($params as &$param) {
                            $result = $this->validate($param, 'app\validate\Transfer');
                            $param = array_filter($param, function($v, $k) {
                                return in_array($k, ['mode', 'txt', 'money', 'name', 'isFrozen'], true);
                            }, ARRAY_FILTER_USE_BOTH);
                        }
                        unset($param);
                    }

                    if (!$params) {
                        $msg = '插入失败';
                    } else {
                        $insertResult = ['insertIds' => ['loan' => [], 'transactions' => []], 'originTodayStatementData' => []];
                        foreach ($params as $param) {
                            $tmp = $dbData->insertNew($param['mode'], $param["txt"], $param["money"], $param["name"], $param["isFrozen"]);
                            $insertResult['insertIds']['loan'] = array_merge($insertResult['insertIds']['loan'], $tmp['insertIds']['loan']);
                            $insertResult['insertIds']['transactions'] = array_merge($insertResult['insertIds']['transactions'], $tmp['insertIds']['transactions']);
                            // originTodayStatementData 需要保留同币种最旧数据
                            $insertResult['originTodayStatementData'] = array_merge($tmp['originTodayStatementData'], $insertResult['originTodayStatementData']);
                        }

                        if ($insertResult) {
                            $insertResult['postData'] = $request->post();
                            Session::set('longbridgeRollback', json_encode($insertResult));
                            $msg = '插入成功 <a href="?rollback=true">撤销</a>';
                        } else {
                            $msg = '插入失败';
                        }
                    }
                }
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                $detail = [];
            } catch (\think\Exception $e) {
                $msg = $e->getMessage();
                $detail = [];
            }
        } else {
            if ($request->get("rollback")) {
                $data = json_decode(Session::get('longbridgeRollback'), 1);
                if ($data && $dbData->doRollback($data)) {
                    $msg = '撤销成功';
                    Session::delete('longbridgeRollback');
                    $originPostData = $data['postData'];
                } else {
                    $msg = '撤销失败';
                }
            }
        }

        View::assign('msg', $msg);
        View::assign('loanUser', LoanModel::distinct('name')->column('name'));
        View::assign('transactmode', TransactmodeModel::where('currency_code', 'in', ['USD', 'HKD'])->column('*', 'id'));
        View::assign('detail', $detail);
        return View::fetch();
    }
}