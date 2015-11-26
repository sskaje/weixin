<?php
/**
 * 微信开放平台OAuth
 *
 * @author sskaje http://sskaje.me/
 */

/**
 * 微信OAuth
 */
class spWxOAuth
{
    private $app_id;
    private $app_secret;

    public function __construct($app_id, $app_secret)
    {
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
    }

    /**
     * 微信授权登录链接
     *
     * @param string   $redirect_uri
     * @param bool     $qrlogin         是否二维码登录
     * @param string   $scope           snsapi_login, snsapi_base, snsapi_userinfo  网页应用只有 snsapi_login, 微信内嵌用 base/userinfo
     * @param string & $state
     * @return string
     */
    public function getLoginUrl($redirect_uri, $qrlogin=false, $scope='snsapi_login', & $state=null)
    {
        if (!isset($state)) {
            $state = md5(uniqid(rand(), true));
        }
        if ($qrlogin) {
            $url = 'https://open.weixin.qq.com/connect/qrconnect';
        } else {
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        }

        $url .= '?appid=' . $this->app_id . '&redirect_uri=' . urlencode($redirect_uri)
               . '&response_type=code&scope='.$scope.'&state=' . $state . '#wechat_redirect';

        return $url;
    }

    /**
     * 使用code获取Access Token
     *
     * @param string $code
     * @return mixed
     */
    public function getAccessToken($code)
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->app_id . '&secret=' . $this->app_secret .
               '&code=' . $code . '&grant_type=authorization_code';

        $response = spWxHttpUtil::http_get($url);

        /*
         *  "access_token":"ACCESS_TOKEN",
    "expires_in":7200,
    "refresh_token":"REFRESH_TOKEN",
    "openid":"OPENID",
    "scope":"SCOPE",
    "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
         */

        return $response;
    }

    /**
     * 取RefreshToken
     *
     * @param $refresh_token
     * @return mixed
     */
    public function refreshToken($refresh_token)
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=' . $this->app_id . '&grant_type=refresh_token&refresh_token=' . $refresh_token;
        $response = spWxHttpUtil::http_get($url);

        /*
         *  "access_token":"ACCESS_TOKEN",
   "expires_in":7200,
   "refresh_token":"REFRESH_TOKEN",
   "openid":"OPENID",
   "scope":"SCOPE"
         */

        return $response;
    }

    /**
     * 使用access token 和 openid 获取用户信息
     *
     * @param string $token
     * @param string $openid
     * @return mixed
     */
    public function getUserInfo($token, $openid)
    {
        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $token . '&openid=' . $openid . '&lang=zh_CN';

        $response = spWxHttpUtil::http_get($url);

        return $response;
    }

    /**
     * 检验授权凭证（access_token）是否有效
     *
     * @param string $access_token
     * @param string $openid
     * @return mixed
     */
    public function checkAccessToken($access_token, $openid)
    {
        $url = 'https://api.weixin.qq.com/sns/auth?access_token='.$access_token.'&openid=' . $openid;

        return spWxHttpUtil::http_get($url);
    }
}

# EOF
