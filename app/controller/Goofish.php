<?php
namespace app\controller;

use think\Request;
use think\facade\View;
use think\facade\Session;
use app\BaseController;
use app\service\Auth;
use app\service\Data as DataService;
use app\model\Currency as CurrencyModel;
use app\model\Transactions as TransactionsModel;
use app\model\Transactmode as TransactmodeModel;
use app\model\Loan as LoanModel;
use app\model\Goofish as GoofishModel;

class Goofish extends BaseController
{
    private $allowedNextEvent = [
        'CREATE'          => ['ORDER_INCOME', 'CLOSE'],
        'ORDER_INCOME'    => ['ORDER_INCOME', 'ORDER_REFUND', 'SHIP_FEE_OUT', 'PLATFORM_FEE', 'ROLLBACK'],
        'ORDER_REFUND'    => ['ORDER_REFUND', 'SHIP_FEE_RETURN', 'PLATFORM_FEE', 'ROLLBACK', 'CLOSE'],
        'SHIP_FEE_OUT'    => ['ORDER_REFUND', 'SHIP_FEE_OUT', 'SHIP_FEE_RETURN', 'PLATFORM_FEE', 'ROLLBACK', 'CLOSE'],
        'SHIP_FEE_RETURN' => ['ORDER_REFUND', 'SHIP_FEE_RETURN', 'PLATFORM_FEE', 'ROLLBACK', 'CLOSE'],
        'PLATFORM_FEE'    => ['PLATFORM_FEE', 'ROLLBACK', 'CLOSE'],
        'CLOSE'           => ['CLOSED']
    ];

    private $eventMap = [
        'CREATE'          => 'doCreate',
        'ORDER_INCOME'    => 'doOrderIncome',
        'ORDER_REFUND'    => 'doOrderRefund',
        'SHIP_FEE_OUT'    => 'doShipFeeOut',
        'SHIP_FEE_RETURN' => 'doShipFeeReturn',
        'PLATFORM_FEE'    => 'doPlatformFee',
        'ROLLBACK'        => 'doRollback',
        'CLOSE'           => 'doClose',
    ];

    private function doCreate($params, DataService $dbData)
    {
        $this->validate($params, ['orderNo' => ['require', 'alphaNum', 'max:64', 'unique:goofish,order_no']]);
        if (GoofishModel::insertGetId(['order_no' => $params['orderNo'], 'event' => 'CREATE'])) {
            return '插入成功';
        }
        
        return '插入失败';
    }

    private function validateMoney(&$params)
    {
        $this->validate($params, [
            'trustee'  => ['max:60'],
            'name'     => ['max:60'],
            'money'    => ['require', 'float', 'gt:0'],
            'currency' => ['require'],
        ]);

        $params['transactmodeId'] = TransactmodeModel::where('currency_code', '=', $params['currency'])->order('id')->value('id');

        if (!$params['transactmodeId']) {
            throw new \think\exception\ValidateException('交易币种下无有效支付方式');
        }

        if ($params['trustee'] === $params['name']) {
            throw new \think\exception\ValidateException('托管人与资金对象不能相同');
        }
    }

    private function doOrderIncome($params, DataService $dbData)
    {
        $this->validateMoney($params);

        $note = "订单 {$params['orderNo']}（闲鱼售出" . ($params['note'] ? "，{$params['note']}" : '') . '）';
        if ($params['trustee'] && $params['name']) {
            $res1 = $dbData->insertNew("{$params['transactmodeId']}_0", "付款至 {$params['trustee']}（{$note}）", $params['money'], $params['name'], 0);
            $res2 = $dbData->insertNew("0_{$params['transactmodeId']}", "请款自 {$params['name']}（{$note}）", $params['money'], $params['trustee'], 0);
        } else {
            if ($params['trustee']) {
                $res1 = true;
                $res2 = $dbData->insertNew("0_{$params['transactmodeId']}", $note, $params['money'], $params['trustee'], 0);
            }
            
            if ($params['name']) {
                $res1 = $dbData->insertNew("{$params['transactmodeId']}_0", $note, $params['money'], $params['name'], 0);
                $res2 = $dbData->insertNew("0_{$params['transactmodeId']}", $note, $params['money'], null, 0);
            }
        }

        $res3 = GoofishModel::insertGetId([
            'order_no'        => $params['orderNo'],
            'name'            => $params['name'],
            'trustee'         => $params['trustee'],
            'event'           => 'ORDER_INCOME',
            'transactmode_id' => $params['transactmodeId'],
            'money'           => $params['money'],
            'note'            => $params['note'],
            'is_rollback'     => 0
        ]);

        if ($res1 && $res2 && $res3) {
            return '插入成功';
        }
        
        return '插入失败';
    }

    private function doOrderRefund($params, DataService $dbData)
    {
        $this->validateMoney($params);

        $note = "订单 {$params['orderNo']}（闲鱼退款" . ($params['note'] ? "，{$params['note']}" : '') . '）';
        if ($params['trustee'] && $params['name']) {
            $res1 = $dbData->insertNew("{$params['transactmodeId']}_0", "付款至 {$params['name']}（{$note}）", $params['money'], $params['trustee'], 0);
            $res2 = $dbData->insertNew("0_{$params['transactmodeId']}", "请款自 {$params['trustee']}（{$note}）", $params['money'], $params['name'], 0);
        } else {
            if ($params['trustee']) {
                $res1 = true;
                $res2 = $dbData->insertNew("{$params['transactmodeId']}_0", $note, $params['money'], $params['trustee'], 0);
            }
            
            if ($params['name']) {
                $res1 = $dbData->insertNew("{$params['transactmodeId']}_0", $note, $params['money'], null, 0);
                $res2 = $dbData->insertNew("0_{$params['transactmodeId']}", $note, $params['money'], $params['name'], 0);
            }
        }

        $res3 = GoofishModel::insertGetId([
            'order_no'        => $params['orderNo'],
            'name'            => $params['name'],
            'trustee'         => $params['trustee'],
            'event'           => 'ORDER_REFUND',
            'transactmode_id' => $params['transactmodeId'],
            'money'           => $params['money'],
            'note'            => $params['note'],
            'is_rollback'     => 0
        ]);

        if ($res1 && $res2 && $res3) {
            return '插入成功';
        }
        
        return '插入失败';
    }

    private function doFee($event, $note, $params, DataService $dbData)
    {
        if ($params['trustee'] && $params['name']) {
            $res1 = $dbData->insertNew("{$params['transactmodeId']}_0", "付款至 {$params['name']}（{$note}）", $params['money'], $params['trustee'], 0);
            $res2 = $dbData->insertNew("0_{$params['transactmodeId']}", "请款自 {$params['trustee']}（{$note}）", $params['money'], $params['name'], 0);
        } else {
            if ($params['trustee']) {
                $res1 = true;
                $res2 = $dbData->insertNew("{$params['transactmodeId']}_0", $note, $params['money'], $params['trustee'], 0);
            }
            
            if ($params['name']) {
                $res1 = $dbData->insertNew("{$params['transactmodeId']}_0", $note, $params['money'], null, 0);
                $res2 = $dbData->insertNew("0_{$params['transactmodeId']}", $note, $params['money'], $params['name'], 0);
            }
        }

        $res3 = GoofishModel::insertGetId([
            'order_no'        => $params['orderNo'],
            'name'            => $params['name'],
            'trustee'         => $params['trustee'],
            'event'           => $event,
            'transactmode_id' => $params['transactmodeId'],
            'money'           => $params['money'],
            'note'            => $params['note'],
            'is_rollback'     => 0
        ]);

        if ($res1 && $res2 && $res3) {
            return '插入成功';
        }
        
        return '插入失败';
    }

    private function doShipFeeOut($params, DataService $dbData)
    {
        $this->validateMoney($params);
        $note = "订单 {$params['orderNo']}（发货运费" . ($params['note'] ? "，{$params['note']}" : '') . '）';
        return $this->doFee('SHIP_FEE_OUT', $note, $params, $dbData);
    }

    private function doShipFeeReturn($params, DataService $dbData)
    {
        $this->validateMoney($params);
        $note = "订单 {$params['orderNo']}（退货运费 " . ($params['note'] ? "，{$params['note']}" : '') . '）';
        return $this->doFee('SHIP_FEE_RETURN', $note, $params, $dbData);
    }

    private function doPlatformFee($params, DataService $dbData)
    {
        $this->validateMoney($params);
        $note = "订单 {$params['orderNo']} 闲鱼服务费";
        return $this->doFee('PLATFORM_FEE', $note, $params, $dbData);
    }

    private function doRollback($params, DataService $dbData)
    {
        $lastRow = GoofishModel::where('order_no', '=', $params['orderNo'])
            ->where('is_rollback', '=', '0')
            ->whereNotIn('event', ['CREATE', 'ROLLBACK', 'CLOSE'])
            ->order(['id' => 'desc'])
            ->limit(1)
            ->find();

        if (!$lastRow) {
            return '没有可以回滚的记录';
        }

        $note = "订单 {$params['orderNo']}（" . ($lastRow->note ? "{$lastRow->note} 回滚" : ($lastRow->event === 'PLATFORM_FEE' ? '闲鱼服务费 回滚' : '回滚')) . '）';

        switch ($lastRow->event) {
            case 'ORDER_INCOME':
                if ($lastRow->trustee && $lastRow->name) {
                    $res1 = $dbData->insertNew("0_{$lastRow->transactmode_id}", "请款自 {$lastRow->trustee}（{$note}）", $lastRow->money, $lastRow->name, 0);
                    $res2 = $dbData->insertNew("{$lastRow->transactmode_id}_0", "付款至 {$lastRow->name}（{$note}）", $lastRow->money, $lastRow->trustee, 0);
                } else {
                    if ($lastRow->trustee) {
                        $res1 = true;
                        $res2 = $dbData->insertNew("{$lastRow->transactmode_id}_0", $note, $lastRow->money, $lastRow->trustee, 0);
                    }
                    
                    if ($lastRow->name) {
                        $res1 = $dbData->insertNew("{$lastRow->transactmode_id}_0", $note, $lastRow->money, null, 0);
                        $res2 = $dbData->insertNew("0_{$lastRow->transactmode_id}", $note, $lastRow->money, $lastRow->name, 0);
                    }
                }
                break;
            case 'ORDER_REFUND':
                if ($lastRow->trustee && $lastRow->name) {
                    $res1 = $dbData->insertNew("0_{$lastRow->transactmode_id}", "请款自 {$lastRow->name}（{$note}）", $lastRow->money, $lastRow->trustee, 0);
                    $res2 = $dbData->insertNew("{$lastRow->transactmode_id}_0", "付款至 {$lastRow->trustee}（{$note}）", $lastRow->money, $lastRow->name, 0);
                } else {
                    if ($lastRow->trustee) {
                        $res1 = true;
                        $res2 = $dbData->insertNew("0_{$lastRow->transactmode_id}", $note, $lastRow->money, $lastRow->trustee, 0);
                    }
                    
                    if ($lastRow->name) {
                        $res1 = $dbData->insertNew("0_{$lastRow->transactmode_id}", $note, $lastRow->money, null, 0);
                        $res2 = $dbData->insertNew("{$lastRow->transactmode_id}_0", $note, $lastRow->money, $lastRow->name, 0);
                    }
                }
                break;
            case 'PLATFORM_FEE':
            case 'SHIP_FEE_OUT':
            case 'SHIP_FEE_RETURN':
                if ($lastRow->trustee && $lastRow->name) {
                    $res1 = $dbData->insertNew("0_{$lastRow->transactmode_id}", "请款自 {$lastRow->name}（{$note}）", $lastRow->money, $lastRow->trustee, 0);
                    $res2 = $dbData->insertNew("{$lastRow->transactmode_id}_0", "付款至 {$lastRow->trustee}（{$note}）", $lastRow->money, $lastRow->name, 0);
                } else {
                    if ($lastRow->trustee) {
                        $res1 = true;
                        $res2 = $dbData->insertNew("0_{$lastRow->transactmode_id}", $note, $lastRow->money, $lastRow->trustee, 0);
                    }
                    
                    if ($lastRow->name) {
                        $res1 = $dbData->insertNew("0_{$lastRow->transactmode_id}", $note, $lastRow->money, null, 0);
                        $res2 = $dbData->insertNew("{$lastRow->transactmode_id}_0", $note, $lastRow->money, $lastRow->name, 0);
                    }
                }
                break;
        }

        $lastRow->is_rollback = 1;
        $res3 = $lastRow->save();

        $res4 = GoofishModel::insertGetId([
            'order_no'        => $params['orderNo'],
            'event'           => 'ROLLBACK',
            'note'            => "#{$lastRow->id} $lastRow->created_at 回滚",
            'is_rollback'     => 0
        ]);

        if ($res1 && $res2 && $res3 && $res4) {
            return '插入成功';
        }
        
        return '插入失败';
    }

    private function doClose($params, DataService $dbData)
    {
        $lastRow = GoofishModel::where('order_no', '=', $params['orderNo'])
            ->where('is_rollback', '=', '0')
            ->whereIn('event', ['CREATE', 'ORDER_REFUND', 'SHIP_FEE_OUT', 'SHIP_FEE_RETURN', 'PLATFORM_FEE'])
            ->order(['id' => 'desc'])
            ->limit(1)
            ->find();

        if (!$lastRow) {
            return '没有可以关闭的订单';
        }

        if (GoofishModel::insertGetId(['order_no' => $params['orderNo'], 'event' => 'CLOSE'])) {
            return '插入成功';
        }
        
        return '插入失败';
    }

    public function index(Request $request, DataService $dbData)
    {
        $msg = null;

        try {
            if ($request->isPost()) {
                $this->validate($request->post(), ['__token__' => 'token', 'event' => ['require', 'eq:CREATE']]);
                $msg = call_user_func_array([$this, $this->eventMap[$request->post('event')]], [$request->post(), $dbData]);
            }
        } catch (\think\exception\ValidateException $e) {
            $msg = "数据错误，{$e->getMessage()}";
        }

        $page = (int) $request->get('page');
        $page = $page <= 0 ? 1 : $page;

        $offset = 10;

        $dataCount = GoofishModel::group('order_no')->count();
        $subSql = GoofishModel::where('event', '<>', 'ROLLBACK')
            ->where('is_rollback', '=', '0')
            ->field(['order_no', 'MAX(id) as mid'])
            ->group('order_no')
            ->buildSql();
        $data = GoofishModel::alias('t1')
            ->rightJoin([$subSql => 't2'], 't1.id = t2.mid')
            ->order(['id' => 'desc'])
            ->limit(($page - 1) * $offset, $offset)
            ->select()
            ->toArray();

        foreach ($data as &$row) {
            $row['items'] = GoofishModel::where('order_no', '=', $row['order_no'])
                ->where('event', '=', 'ORDER_INCOME')
                ->where('is_rollback', '=', '0')
                ->order(['id' => 'desc'])
                ->column('note');
        } 

        View::assign(
            'data',
            [
                'data'      => $data,
                'dataCount' => $dataCount,
                'page'      => $page,
                'offset'    => $offset,
                'msg'       => $msg,
            ]
        );
        return View::fetch();
    }

    public function detail(Request $request, $orderNo, DataService $dbData)
    {
        $msg = null;

        if (!GoofishModel::where('order_no', '=', $orderNo)->count()) {
            View::assign('errmsg', '未找到订单');
            return View::fetch('/error');
        }

        try {
            if ($request->isPost()) {
                $this->validate($request->post(), ['__token__' => 'token', 'event' => ['require', 'in:' . implode(',', array_keys($this->eventMap))]]);
                $msg = call_user_func_array([$this, $this->eventMap[$request->post('event')]], [array_merge($request->post(), ['orderNo' => $orderNo]), $dbData]);
            }
        } catch (\think\exception\ValidateException $e) {
            $msg = "数据错误，{$e->getMessage()}";
        }

        $data = GoofishModel::where('order_no', '=', $orderNo)
            ->order('id')
            ->select();

        $currentEvent = GoofishModel::where('order_no', '=', $orderNo)
            ->order(['id' => 'desc'])
            ->where('event', '<>', 'ROLLBACK')
            ->where('is_rollback', '=', '0')
            ->limit(1)
            ->value('event');

        View::assign(
            'data',
            [
                'currency'         => CurrencyModel::column('*', 'code'),
                'transactmode'     => TransactmodeModel::column('*', 'id'),
                'loanUser'         => LoanModel::distinct('name')->column('name'),
                'allowedNextEvent' => $this->allowedNextEvent[$currentEvent],
                'data'             => $data,
                'msg'              => $msg,
            ]
        );
        return View::fetch();
    }
}