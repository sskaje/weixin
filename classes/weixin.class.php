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
    static public function App()
    {
        return new spWxApp(SPWX_APP_ID, SPWX_APP_SECRET);
    }

    static public function Api()
    {
        return spWxMessage::MessageAPI();
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
                    array(
                        'echostr'   =>  $_GET['echostr'],
                    )
                );
            }
        } else {
            $input = file_get_contents('php://input');

            file_put_contents(
                '/tmp/wxapi.log',
                "{$_SERVER['QUERY_STRING']}\n{$input}\n\n",
                FILE_APPEND
            );

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
                break;

            case self::REQUEST_IMAGE:       # 图片类型消息
                $message['pic_url']     = (string) $postObj->PicUrl;
                $message['media_id']    = (string) $postObj->MediaId;
                break;

            case self::REQUEST_LOCATION:    # 地理位置类型消息
                $message         +=   array(
                    'latitude'          => (string) $postObj->Location_X,
                    'longitude'         => (string) $postObj->Location_Y,
                    'scale'             => (int)    $postObj->Scale,
                    'label'             => (string) $postObj->Label,
                );
                break;

            case self::REQUEST_URL:          # 链接消息(old)
            case self::REQUEST_LINK:         # 链接消息
                $message        += array(
                    'title'             => (string) $postObj->Title,
                    'description'       => (string) $postObj->Description,
                    'url'               => (string) $postObj->Url,
                );
                break;

            case self::REQUEST_EVENT:        # 事件消息
                $message       += array(
                    'event'             => (string) $postObj->Event,
                    'event_key'         => (string) $postObj->EventKey,
                );
                break;

            case self::REQUEST_VOICE:        # 语音消息
                $message       +=  array(
                    'media_id'          => (string) $postObj->MediaId,
                    'format'            => (string) $postObj->Format,
                    'recognition'       => (string) $postObj->Recognition,
                );
                break;

            case self::REQUEST_VIDEO:        # 视频
                $message       +=  array(
                    'media_id'          => (string) $postObj->MediaId,
                    'thumb_media_id'    => (string) $postObj->ThumbMediaId,
                );
                break;

            case self::REQUEST_SHORTVIDEO:   # 短视频
                $message       +=  array(
                    'media_id'          => (string) $postObj->MediaId,
                    'thumb_media_id'    => (string) $postObj->ThumbMediaId,
                );
                break;

            default:
                throw new spWxException('Invalid Message Type');
            }

            return self::ProcessRequest($message['msg_type'], $message);
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

    /**
     * 处理消息
     *
     * @param string $message_type
     * @param array $message
     * @throws spWxException
     */
    static protected function ProcessRequest($message_type, array $message)
    {
        $class = self::$MessageHandlers[$message_type];
        if (empty($class) || !class_exists($class)) {
            throw new spWxException('Invalid message handler');
        }

        $object = new $class($message);
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

    public function createMenu($class)
    {
        if (!is_subclass_of($class, 'spWxMenu')) {
            throw new spWxException('bad class');
        }

        $json = (new $class)->create();

        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . self::getAccessToken();
        $ret = spWxHttpUtil::http_post($url, $json);

        var_dump($ret);

    }

}

abstract class spWxMenu
{
    abstract public function create();
}

# EOF
