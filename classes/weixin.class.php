<?php
/**
 * 微信公众平台消息api框架
 *
 * @author sskaje http://sskaje.me/
 */

/**
 * 微信类
 */
class spWeixin
{
    /**
     * 创建 spWxApp 对象
     *
     * @return \spWxApp
     */
    static public function App()
    {
        return new spWxApp(SPWX_APP_ID, SPWX_APP_SECRET);
    }

    /**
     * 运行Message API
     *
     * @return int
     * @throws \spWxException
     */
    static public function Api()
    {
        return spWxMessage::MessageAPI();
    }

    /**
     * 创建 spWxOAuth 对象
     *
     * @return \spWxOAuth
     */
    static public function OAuth()
    {
        return new spWxOAuth(SPWX_APP_ID, SPWX_APP_SECRET);
    }


}

/**
 * 微信入口类
 */
class spWxMessage
{
    /**
     * 消息API
     *
     */
    static public function MessageAPI()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (isset($_GET['echostr'])) {
                # ping/pong
                return self::ProcessRequest(
                    self::REQUEST_VALIDATE,
                    new spWxRequestValidationObject(array(
                        'echostr'   =>  $_GET['echostr'],
                    ))
                );
            }
        } else {
            $input = spWxTransport::Input();

            $postObj = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);

            $message = array();

            $message['from_username']   = (string) $postObj->FromUserName;
            $message['to_username']     = (string) $postObj->ToUserName;
            $message['create_time']     = (int)    $postObj->CreateTime;
            $message['msg_id']          = (int)    $postObj->MsgId;
            $message['msg_type']        = (string) $postObj->MsgType;

            switch ($message['msg_type']) {
            case self::REQUEST_TEXT:        # 文本类型消息
                $message['content']     = (string) $postObj->Content;

                $object = new spWxRequestTextObject($message);
                break;

            case self::REQUEST_IMAGE:       # 图片类型消息
                $message['pic_url']     = (string) $postObj->PicUrl;
                $message['media_id']    = (string) $postObj->MediaId;

                $object = new spWxRequestImageObject($message);
                break;

            case self::REQUEST_LOCATION:    # 地理位置类型消息
                $message         +=   array(
                    'latitude'          => (string) $postObj->Location_X,
                    'longitude'         => (string) $postObj->Location_Y,
                    'scale'             => (int)    $postObj->Scale,
                    'label'             => (string) $postObj->Label,
                );

                $object = new spWxRequestLocationObject($message);
                break;

            case self::REQUEST_URL:          # 链接消息(old)
            case self::REQUEST_LINK:         # 链接消息
                $message        += array(
                    'title'             => (string) $postObj->Title,
                    'description'       => (string) $postObj->Description,
                    'url'               => (string) $postObj->Url,
                );
                $object = new spWxRequestLinkObject($message);
                break;

            case self::REQUEST_EVENT:        # 事件消息
                $message       += array(
                    'event'             => (string) $postObj->Event,
                    'event_key'         => (string) $postObj->EventKey,
                );
                $object = new spWxRequestEventObject($message);
                break;

            case self::REQUEST_VOICE:        # 语音消息
                $message       +=  array(
                    'media_id'          => (string) $postObj->MediaId,
                    'format'            => (string) $postObj->Format,
                    'recognition'       => (string) $postObj->Recognition,
                );
                $object = new spWxRequestVoiceObject($message);
                break;

            case self::REQUEST_VIDEO:        # 视频
            case self::REQUEST_SHORTVIDEO:   # 短视频
                $message       +=  array(
                    'media_id'          => (string) $postObj->MediaId,
                    'thumb_media_id'    => (string) $postObj->ThumbMediaId,
                );
                $object = new spWxRequestVideoObject($message);
                break;

            default:
                throw new spWxException('Invalid Message Type');
            }

            return self::ProcessRequest($message['msg_type'], $object);
        }

        return 0;
    }

    const REQUEST_VALIDATE   = -1;
    const REQUEST_TEXT       = 'text';
    const REQUEST_IMAGE      = 'image';
    const REQUEST_LOCATION   = 'location';
    const REQUEST_URL        = 'url';
    const REQUEST_EVENT      = 'event';
    const REQUEST_VOICE      = 'voice';
    const REQUEST_VIDEO      = 'video';
    const REQUEST_SHORTVIDEO = 'shortvideo';
    const REQUEST_LINK       = 'link';

    const RESPONSE_PLAIN     = 'spWxResponsePlain';
    const RESPONSE_TEXT      = 'spWxResponseText';
    const RESPONSE_IMAGE     = 'spWxResponseImage';
    const RESPONSE_VOICE     = 'spWxResponseVoice';
    const RESPONSE_VIDEO     = 'spWxResponseVideo';
    const RESPONSE_MUSIC     = 'spWxResponseMusic';
    const RESPONSE_NEWS      = 'spWxResponseNews';


    /**
     * 处理消息
     *
     * @param string                $message_type
     * @param spWxRequestObjectBase $message_object
     * @throws spWxException
     */
    static protected function ProcessRequest($message_type, spWxRequestObjectBase $message_object)
    {
        $class = self::$MessageHandlers[$message_type];
        if (empty($class) || !class_exists($class)) {
            throw new spWxException('Invalid message handler');
        }

        $object = new $class($message_object);
        $object->checkSig();
        return $object->response();
    }

    /**
     * 消息处理类
     *
     * @var array
     */
    static private $MessageHandlers = array(
        self::REQUEST_VALIDATE  =>  'spWxRequestTokenValidation',
        self::REQUEST_TEXT      =>  '',
        self::REQUEST_IMAGE     =>  '',
        self::REQUEST_LOCATION  =>  '',
        self::REQUEST_URL       =>  '',
        self::REQUEST_EVENT     =>  '',
        self::REQUEST_VOICE     =>  '',
        self::REQUEST_VIDEO     =>  '',
    );

    /**
     * 注册消息处理方法
     *
     * @param string $message_type
     * @param string $class
     */
    static public function RegisterHandler($message_type, $class)
    {
        if (isset(self::$MessageHandlers[$message_type]) && class_exists($class) && is_subclass_of($class, 'spWxRequest')) {
            self::$MessageHandlers[$message_type] = $class;
        }
    }
}



class spWxApp
{
    private $app_id;
    private $app_secret;

    public function __construct($app_id, $app_secret)
    {
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
    }

    protected function delAccessToken()
    {
        $token_cache_key = 'app_access_token';
        return spWxCache::del($token_cache_key);
    }

    protected function getAccessToken()
    {
        $token_cache_key = 'app_access_token';

        $token = spWxCache::get($token_cache_key);
        if (!$token) {
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->app_id.'&secret='.$this->app_secret;

            $ret = spWxHttpUtil::http_get($url);

            $token = $ret['access_token'];
            $expire = $ret['expires_in'] - 300;
            spWxCache::set($token_cache_key, $token, $expire);
        }

        return $token;
    }

    /**
     * 获取回调IP
     *
     * @return mixed
     */
    public function get_callback_ip()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=' . $this->getAccessToken();
        $result = spWxHttpUtil::http_get($url);

        if (isset($result['errcode']) && $result['errcode'] == '40001') {
            $this->delAccessToken();
            return $this->get_callback_ip();
        }

        return $result;
    }

    /**
     * 提交菜单
     *
     * @param \spWxMenu $class
     * @return mixed
     */
    public function menu_create(spWxMenu $class)
    {
        $json = $class->create();

        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->getAccessToken();
        $result = spWxHttpUtil::http_post($url, $json);

        if (isset($result['errcode']) && $result['errcode'] == '40001') {
            $this->delAccessToken();
            return $this->createMenu($class);
        }

        return $result;
    }

    /**
     * 获取菜单
     *
     * @return mixed
     */
    public function menu_get()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token=' . $this->getAccessToken();
        $result = spWxHttpUtil::http_get($url);

        if (isset($result['errcode']) && $result['errcode'] == '40001') {
            $this->delAccessToken();
            return $this->menu_get();
        }

        return $result;
    }
    /**
     * 删除菜单
     *
     * @return mixed
     */
    public function menu_delete()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . $this->getAccessToken();
        $result = spWxHttpUtil::http_get($url);

        if (isset($result['errcode']) && $result['errcode'] == '40001') {
            $this->delAccessToken();
            return $this->menu_delete();
        }

        return $result;
    }

    /**
     * 创建QR Code
     *
     * @param string $action_name
     * @param string $action_info
     * @param int    $expire_seconds
     * @return mixed
     */
    public function qrcode_create($action_name, $action_info, $expire_seconds=0)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $this->getAccessToken();
        $data = [
            'action_name'   =>  $action_name,
            'action_info'   =>  $action_info,
        ];
        if ($expire_seconds) {
            $data['expire_seconds'] = intval($expire_seconds);
        }
        $result = spWxHttpUtil::http_post($url, $data);

        if (isset($result['errcode']) && $result['errcode'] == '40001') {
            $this->delAccessToken();
            return $this->qrcode_create($action_name, $action_info, $expire_seconds);
        }

        return $result;
    }

    /**
     * 返回二维码链接
     *
     * @param string $ticket
     * @return string
     */
    public function qrcode_url($ticket)
    {
        return 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($ticket);
    }

    /**
     * 短链接
     *
     * @param string $long_url
     * @param string $action
     * @return mixed
     */
    public function shorturl($long_url, $action='long2short')
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/shorturl?access_token=' . $this->getAccessToken();

        $data = ['action'=>$action, 'long_url'=>$long_url];

        $result = spWxHttpUtil::http_post($url, $data);

        if (isset($result['errcode']) && $result['errcode'] == '40001') {
            $this->delAccessToken();
            return $this->shorturl($long_url, $data);
        }

        return $result;
    }

    /**
     * 客服消息
     *
     * @param string $openid
     * @param string $type
     * @param array  $data
     * @return mixed
     */
    public function sendMessage($openid, $type, array $data)
    {
        $send = [];
        $send['touser'] = $openid;
        $send['msgtype'] = $type;
        $send += $data;

        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $this->getAccessToken();

        $result = spWxHttpUtil::http_post($url, $send);

        if (isset($result['errcode']) && $result['errcode'] == '40001') {
            $this->delAccessToken();
            return $this->sendMessage($openid, $type, $data);
        }

        file_put_contents('/tmp/wx_notice', $url . "\n" . var_export($send, true) . "\n\n" . var_export($result, true) . "\n\n\n", FILE_APPEND);

        return $result;
    }

    /**
     * 取OpenID列表
     *
     * @return array
     * @throws \Exception
     */
    public function user_getlist()
    {
        $access_token = $this->getAccessToken();

        $openids = [];

        $next_openid = '';
        do {
            RETRY:
            $url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=".urlencode($access_token)."&next_openid=" . $next_openid;
            $ret = spWxHttpUtil::http_get($url);
            if (isset($ret['errcode'])) {
                if ($ret['errcode'] == 40001) {
                    $this->delAccessToken();
                    $access_token = $this->getAccessToken();

                    goto RETRY;
                } else {
                    throw new Exception($ret['errmsg'], $ret['errcode']);
                }
            }

            if (!empty($ret['data']['openid'])) {
                $openids = array_merge($openids, $ret['data']['openid']);
            }

            $next_openid = $ret['next_openid'];

        } while ($next_openid);

        return $openids;
    }

    /**
     * 单条用户信息
     *
     * @param string $openid
     * @return bool|mixed
     * @throws \Exception
     */
    public function user_info($openid)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $this->getAccessToken()
               . '&openid=' . $openid;

        $result = spWxHttpUtil::http_get($url);

        if (isset($result['errcode']) && $result['errcode'] == '40001') {
            $this->delAccessToken();
            return $this->user_info($openid);
        }

        return $result;
    }

    /**
     * 批量取用户信息
     *
     * @param array $openids
     * @return mixed
     * @throws \Exception
     */
    public function user_info_batch(array $openids)
    {
        $user_list = [
            'user_list' =>  [],
        ];

        foreach ($openids as $openid) {
            $user_list['user_list'][] = [
                'openid'    =>  $openid,
                'lang'      =>  'zh-CN',
            ];
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token=' . $this->getAccessToken();

        $result = spWxHttpUtil::http_post($url, $user_list);
        if (isset($result['errcode']) && $result['errcode'] == 40001) {

            $this->delAccessToken();
            return $this->user_info_batch($openids);
        }

        return $result['user_info_list'];
    }

    /**
     * JS SDK ticket
     *
     * @param string $request_url
     * @param string $type             jsapi, wx_card
     * @return bool|mixed|string
     * @throws \spWxException
     */
    public function getJSSDKTicket($request_url = '', $type='jsapi')
    {
        $token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type='.$type.'&access_token='.$token;

        $cache_key = 'wx:ticket:'.$type.':' . md5($url);

        $jsapi_ticket = spWxCache::get($cache_key);
        $jsapi_ticket = json_decode($jsapi_ticket, true);

        if(!$jsapi_ticket) {
            $jsapi_ticket = spWxHttpUtil::http_get($url);

            if (isset($jsapi_ticket['errcode']) && $jsapi_ticket['errcode'] == '40001') {
                $this->delAccessToken();
                return $this->getJsapiTicket($request_url);
            }

            if (!isset($jsapi_ticket['ticket']) || !isset($jsapi_ticket['expires_in'])) {
                throw new spWxException('服务器故障，请稍后重试', 9210201);
            }
            spWxCache::set($cache_key, json_encode($jsapi_ticket), $jsapi_ticket['expires_in'] - 300);
        }

        $noncestr  = md5(uniqid('sskaje', true));

        $jsapi_ticket['noncestr'] = $noncestr;
        $jsapi_ticket['timestamp'] = time();

        $signstr = 'jsapi_ticket='.$jsapi_ticket['ticket'].'&noncestr='.$jsapi_ticket['noncestr'].'&timestamp='.$jsapi_ticket['timestamp'];
        if(empty($request_url)) {
            $signstr .= '&url=' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            $signstr .= '&url=' . $request_url;
        }
        $jsapi_ticket['signature'] = sha1($signstr);
        $jsapi_ticket['signstr'] = $signstr;
        $jsapi_ticket['appid'] = $this->app_id;

        return $jsapi_ticket;
    }


################################################
#
#   TODO: 素材管理
#
################################################

    public function material_add($type, $is_temp=true)
    {
        if ($is_temp) {
            $url = 'https://api.weixin.qq.com/cgi-bin/media/upload';
        } else {
            $url = 'https://api.weixin.qq.com/cgi-bin/material/add_material';
        }

        $url .= '?type='.$type.'&access_token=' . $this->getAccessToken();

    }

    public function material_get($media_id, $is_temp=true)
    {
        if ($is_temp) {
            $url = 'https://api.weixin.qq.com/cgi-bin/media/get';
            $url .= '?media_id='.$media_id.'&access_token=' . $this->getAccessToken();

            $result = spWxHttpUtil::http_get($url);
        } else {
            $url = 'https://api.weixin.qq.com/cgi-bin/material/get_material';
            $url .= '?access_token=' . $this->getAccessToken();

            $result = spWxHttpUtil::http_post($url, ['media_id'=>$media_id]);
        }

        if (isset($result['errcode']) && $result['errcode'] == 40001) {

            $this->delAccessToken();
            return $this->material_get($media_id, $is_temp);
        }

        # 这个接口好奇葩...

    }

    public function material_del($media_id)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/material/del_material?access_token=' . $this->getAccessToken();
        $data = ['media_id'=>$media_id];
    }

}

abstract class spWxMenu
{
    abstract public function create();
}

# EOF
