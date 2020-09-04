<?php
namespace app\common\service;

class UcAuthCode
{
    private static function do($str, $operation = 'DECODE', $key = '', $expiry = 0)
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

    public static function encode($str, $key = '', $expiry = 0)
    {
        return self::do($str, 'ENCODE', $key, $expiry);
    }

    public static function decode($str, $key = '', $expiry = 0)
    {
        return self::do($str, 'DECODE', $key, $expiry);
    }
}