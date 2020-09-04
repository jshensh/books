<?php
namespace app\loan\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\facade\Session;
use think\facade\Config;

use app\loan\model\Transactions;
use app\loan\model\Transactmode;
use app\loan\model\Loan;

class Index extends Controller
{
    public function index()
    {
        $data = array_map(
            function($v) {
                $v['minT'] = date('Y-m-d H:i:s', $v['minT']);
                $v['maxT'] = date('Y-m-d H:i:s', $v['maxT']);
                return $v;
            },
            Loan::group('name')
                ->field([
                    'name',
                    'sum(money)' => 'all',
                    'min(t)' => 'minT',
                    'max(t)' => 'maxT'
                ])
                ->order('all')
                ->select()
                ->toArray()
        );
        $this->assign('data', $data);
        return $this->fetch();
    }

    private function ucAuthCode($str, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        $ckey_length = 4;

        $key = md5($key);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($str, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);

        $str = $operation == 'DECODE' ? base64_decode(substr($str, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($str.$keyb), 0, 16).$str;
        $str_length = strlen($str);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for($a = $j = $i = 0; $i < $str_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($str[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if($operation == 'DECODE') {
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                if (!$expiry) {
                    return substr($result, 26);
                } else {
                    return array(substr($result, 26),(int)(substr($result, 0, 10)));
                }
            } else {
                return '';
            }
        } else {
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }

    public function detail($name = '', Request $request)
    {
        if (!$name) {
            return redirect('/loan');
        }

        if ($request->post('delete') === 'true') {
            $sum = Loan::where('loan.name', '=', $name)->sum('money');
            $transactions = new Transactions;
            $transactions->save([
                'transactmode_id' => 1,
                'money'           => -$sum,
                'txt'             => "{$name}销账",
                't'               => time(),
            ]);
            Loan::where('loan.name', '=', $name)->delete();
            return redirect('/loan');
        }

        if ($request->post('shareTime')) {
            $shareTime = $request->post('shareTime');
            if (is_numeric($shareTime) && $shareTime > 0) {
                $key = Config::get('system.loanshare_key');
                if (!$key) {
                    return json(["status" => "error"]);
                }
                $token = urlencode($this->ucAuthCode($name, "ENCODE", $key, $shareTime * 60));
                return json(["status" => "success", "link" => $request->root(true) . "/loanShare/{$token}"]);
            }
            return json(["status" => "error"]);
        }

        $data = Loan::with('transactmode')
                    ->where('loan.name', '=', $name)
                    ->order('loan.t')
                    ->select();

        if (!$data) {
            return redirect('/loan');
        }

        $this->assign('data', $data);
        $this->assign('name', $name);
        return $this->fetch();
    }
}
