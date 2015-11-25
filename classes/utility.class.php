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
     * @param string $val
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
     * @param string $val
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

# EOF