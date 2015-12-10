<?php
/**
 * 工具类定义
 *
 * @author sskaje http://sskaje.me/
 */

/**
 * Cache类定义
 * 使用Redis
 */
class spWxCache
{
    static private function redis_init()
    {
        static $redis = null;
        if (empty($redis) || !$redis->ping()) {
            $redis = new Redis();
            $redis->connect(SPWX_REDIS_HOST, SPWX_REDIS_PORT);
            $redis->setOption(Redis::OPT_PREFIX, SPWX_REDIS_PREFIX);
        }

        return $redis;
    }

    /**
     * set
     *
     *
     * @param string $key
     * @param mixed  $val
     * @param int    $expire
     * @return bool
     */
    static public function set($key, $val, $expire=0)
    {
        $redis = self::redis_init();
        $ret = $redis->set($key, $val);
        if ($ret && $expire) {
            $redis->expire($key, $expire);
        }
        return $ret;
    }

    /**
     * get
     *
     * @param string $key
     * @return bool|string
     */
    static public function get($key)
    {
        $redis = self::redis_init();
        return $redis->get($key);
    }

    /**
     * add
     * key存在时返回失败
     *
     * @param string $key
     * @param mixed  $val
     * @param int    $expire
     * @return bool
     */
    static public function add($key, $val, $expire=3600)
    {
        $redis = self::redis_init();
        $ret = $redis->setnx($key, $val);
        if ($ret && $expire) {
            $redis->expire($key, $expire);
        }
        return $ret;
    }

    /**
     * del
     *
     *
     * @param string $key
     * @return int
     */
    static public function del($key)
    {
        $redis = self::redis_init();
        return $redis->del($key);
    }
}

/**
 * HTTP客户端类定义
 *
 */
class spWxHttpUtil
{

    static private function http_init()
    {
        $ch = null;
        if (empty($ch)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Autohello');

        }

        return $ch;
    }

    static public function http_get($url)
    {
        $ch = self::http_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_POST, 0);

        $ret = curl_exec($ch);

        return json_decode($ret, true);
    }

    static public function http_post($url, array $data)
    {
        $ch = self::http_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPGET, 0);

        $ret = curl_exec($ch);

        return json_decode($ret, true);
    }
}

/**
 * 用户状态
 */
class spWxUserStatus
{
    static public function Get($openid)
    {
        return (int) spWxCache::get('userstatus:' . $openid);
    }

    static public function Set($openid, $status, $expire=600)
    {
        return spWxCache::set('userstatus:'.  $openid, (int) $status, (int) $expire);
    }
}

/**
 * 请求转发器
 * 把当前来的API请求转发到其他地址
 */
class spWxRequestForwarder
{
    static protected $GET_FIELDS = [
        'signature',
        'timestamp',
        'nonce',
        'encrypt_type',
        'msg_signature',
        'echostr',
    ];

    static public function Forward($url)
    {
        if (strpos($url, '?')) {
            $url .= '&';
        } else {
            $url .= '?';
        }

        foreach (self::$GET_FIELDS as $f) {
            if (isset($_GET[$f])) {
                $url .= $f . '=' . urlencode($_GET[$f]) . '&';
            }
        }

        $post = spWxTransport::Input();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type'=>$_SERVER['HTTP_CONTENT_TYPE']]);
        $s = curl_exec($ch);
        $i = curl_getinfo($ch);
        curl_close($ch);


        spWxLogger::Log(
            'spWxRequestForwarder',
            [
                'url'    => $url,
                'data'   => $post,
                'result' => $s,
                'info'   => $i,
            ]
        );

        return $s;
    }
}

/**
 * Logger
 */
class spWxLogger
{
    static public function Log($msg, $var)
    {
        $logmsg = date("Y-m-d H:i:s\n============\n");
        $logmsg .= trim($msg) . "\n============\n\n";
        $logmsg .= var_export($var, true) . "\n\n============\n\n";

        $file = defined('SPWX_LOG_FILE') &&
                (
                    (is_file(SPWX_LOG_FILE) && is_writable(SPWX_LOG_FILE)) ||
                    is_writable(dirname(SPWX_LOG_FILE))
                )
            ? SPWX_LOG_FILE : '/tmp/wx.log';

        return file_put_contents(
            $file,
            $logmsg,
            FILE_APPEND
        );
    }

    static public function LogHttpGet($url, $result)
    {
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $call_from = self::BuildCallFromBT($bt);

        $msg = self::BuildMSGFromBT($bt) . ' Http GET';
        self::Log(
            $msg,
            [
                'call_from' =>  $call_from,
                'url'       =>  $url,
                'result'    =>  $result,
            ]
        );
    }

    static public function LogHttpPost($url, $data, $result)
    {
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $call_from = self::BuildCallFromBT($bt);

        $msg = self::BuildMSGFromBT($bt) . ' Http POST';
        self::Log(
            $msg,
            [
                'call_from' =>  $call_from,
                'url'       =>  $url,
                'data'      =>  $data,
                'result'    =>  $result,
            ]
        );
    }

    static protected function BuildMSGFromBT($bt)
    {
        return (isset($bt[1]['class']) ? $bt[1]['class'] . '::' : '') . $bt[1]['function'];
    }

    static protected function BuildCallFromBT($bt)
    {
        return $bt[0]['file'] . ':' . $bt[0]['line'] ;
    }

}

# EOF