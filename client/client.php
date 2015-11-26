<?php
/**
 * 微信开放平台 客户端库
 *
 * @author sskaje http://sskaje.me/
 */


/**
 * App.php访问需要的密钥
 */
defined('SPWX_AUTH_KEY') || define('SPWX_AUTH_KEY', '');
/**
 * App Name
 */
defined('SPWX_APP_NAME') || define('SPWX_APP_NAME', '');
/**
 * API URL
 */
defined('SPWX_API_URL') || define('SPWX_API_URL', 'http://wx.sskaje.me/app.php');

class spWxClient
{
    const MAGIC = 'sskaje';

    public function oauth($action, array $params)
    {
        return $this->call('oauth', $action, $params);
    }

    public function app($action, array $params)
    {
        return $this->call('app', $action, $params);
    }

    protected function call($op, $action, array $params)
    {
        $ts = time();
        $url = SPWX_API_URL;
        $url .= '?app=' . SPWX_APP_NAME;
        $url .= '&ts=' . $ts;
        $url .= '&password=' . $this->sign(SPWX_APP_NAME, $ts);
        $url .= '&op=' . $op;
        $url .= '&action=' . $action;

        $data = 'params=' . urlencode(json_encode($params));

        $ret = $this->post($url, $data);

        return json_decode($ret, true);
    }

    /**
     * Perform http post
     *
     * @param string $data
     * @return mixed
     */
    private function post($url, $data)
    {
        static $curl = null;
        if (!$curl) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_VERBOSE, 0);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_POST, 1);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        return curl_exec($curl);
    }

    protected function sign($app, $ts)
    {
        if (SPWX_AUTH_KEY === '') {
            return '';
        } else {
            return md5(self::MAGIC . SPWX_AUTH_KEY . $app . $ts);
        }
    }
}

# EOF
