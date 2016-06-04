<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }

    function json_stringify($obj) {
        return preg_replace_callback("/\\\u([0-9a-f]{4})/i", function($r) { return iconv('UCS-2BE', 'UTF-8', pack('H4', $r[1])); }, json_encode($obj));
    }

    function getRandChar($length){
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;
 
        for($i=0;$i<$length;$i++){
            $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
 
        return $str;
    }

    function ucAuthCode($str, $operation = 'DECODE', $key = '', $expiry = 0) {

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

    function checkLogin($password="") {
        global $loginPassword,$key,$expiry;
        if ($_COOKIE["token"]) {
            if (ucAuthCode($_COOKIE["token"],"DECODE",$key)===$loginPassword) {
                return true;
            }
        }
        if ($password===$loginPassword) {
            return ucAuthCode($password,"ENCODE",$key,$expiry);
        }
        return false;
    }

    function curl_get_contents($url,$timeout=5,$method='get',$post_fields=array(),$reRequest=3,$referer="") { //封装 curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt($ch, CURLOPT_REFERER, $referer);
        if (strpos($method,'post')>-1) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$post_fields);
        }
        if (strpos($method,'WithHeader')>-1) {
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, false);
        }
        $output = curl_exec($ch);
        if (curl_errno($ch)==0) {
            if (strpos($method,'WithHeader')>-1) {
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $header = substr($output, 0, $headerSize);
                $body = substr($output, $headerSize);
                return array($header,$body,$output);
            } else {
                return $output;
            }
        } else {
            if ($reRequest) {
                $reRequest--;
                return curl_get_contents($url,$timeout,$method,$post_fields,$reRequest);
            } else {
                return false;
            }
        }
    }