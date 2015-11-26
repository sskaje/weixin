<?php
/**
 * 微信开放平台 客户端库
 *
 * @author sskaje http://sskaje.me/
 */

class spWxClient
{
    const MAGIC = 'sskaje';

    protected $app_url = 'http://wx.sskaje.me/app.php';

    protected $app_name;
    protected $app_key;

    public function __construct($app_name, $app_key, $app_url='')
    {
        $this->app_name = $app_name;
        $this->app_key  = $app_key;

        if ($app_url) {
            $this->app_url = $app_url;
        }
    }

    /**
     * 调用oauth接口
     *
     * @param string $action
     * @param array  $params
     * @return mixed
     */
    public function oauth($action, array $params)
    {
        return $this->call('oauth', $action, $params);
    }

    /**
     * 调用app接口
     *
     * @param string $action
     * @param array  $params
     * @return mixed
     */
    public function app($action, array $params)
    {
        return $this->call('app', $action, $params);
    }

    /**
     * 远程调用
     *
     * @param string $op
     * @param string $action
     * @param array  $params
     * @return mixed
     */
    protected function call($op, $action, array $params)
    {
        $ts = time();
        $url = $this->app_url;
        $url .= '?app=' . $this->app_name;
        $url .= '&ts=' . $ts;
        $url .= '&password=' . $this->sign($this->app_name, $ts);
        $url .= '&op=' . $op;
        $url .= '&action=' . $action;

        $data = 'params=' . urlencode(json_encode($params));

        $ret = $this->post($url, $data);

        return json_decode($ret, true);
    }

    /**
     * Perform http post
     *
     * @param string $url
     * @param string $data
     * @return mixed
     */
    protected function post($url, $data)
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

    /**
     * 签名
     *
     * @param string $app
     * @param string $ts
     * @return string
     */
    protected function sign($app, $ts)
    {
        if ($this->app_key === '') {
            return '';
        } else {
            return md5(self::MAGIC . $this->app_key . $app . $ts);
        }
    }
}

# EOF
