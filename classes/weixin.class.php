<?php
/**
 * 微信公众平台消息api框架
 *
 * @author sskaje http://sskaje.me/
 */

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
                self::ProcessRequest(
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
            $message['create_time']     = (int) $postObj->CreateTime;
            $message['msg_id']          = (int) $postObj->MsgId;
            $message['msg_type']        = (string) $postObj->MsgType;

            if ($message['msg_type'] == self::REQUEST_TEXT) {
                $message['content']     = (string) $postObj->Content;
            } else if ($message['msg_type'] == self::REQUEST_IMAGE) {
                $message['pic_url']     = (string) $postObj->PicUrl;
            } else if ($message['msg_type'] == self::REQUEST_LOCATION) {
                $message['geo']         =   array(
                    'latitude'          => (string) $postObj->Location_X,
                    'longitude'         => (string) $postObj->Location_Y,
                    'scale'             => (int) $postObj->Scale,
                    'label'             => (string) $postObj->Label,
                );
            } else if ($message['msg_type'] == self::REQUEST_URL) {
                $message['link']        = array(
                    'title'             => (string) $postObj->Title,
                    'description'       => (string) $postObj->Description,
                    'url'               => (string) $postObj->Url,
                );
            } else if ($message['msg_type'] == self::REQUEST_EVENT) {
                $message['event']       = array(
                    'event'             => (string) $postObj->Event,
                    'event_key'         => (string) $postObj->EventKey,
                );
            } else if ($message['msg_type'] == self::REQUEST_VOICE) {
                $message['voice']       =  array(
                    'media_id'          => (string) $postObj->MediaId,
                    'format'            => (string) $postObj->Format,
                    'recognition'       => (string) $postObj->Recognition,
                );
            } else if ($message['msg_type'] == self::REQUEST_VIDEO) {
                $message['video']       =  array(
                    'media_id'          => (string) $postObj->MediaId,
                    'thumb_media_id'    => (string) $postObj->ThumbMediaId,
                );
            } else {
                throw new SPException('Invalid Message Type');
            }

            self::ProcessRequest($message['msg_type'], $message);
        }
    }

    const REQUEST_VALIDATE  = -1;
    const REQUEST_TEXT      = 'text';
    const REQUEST_IMAGE     = 'image';
    const REQUEST_LOCATION  = 'location';
    const REQUEST_URL       = 'url';
    const REQUEST_EVENT     = 'event';
    const REQUEST_VOICE     = 'voice';
    const REQUEST_VIDEO     = 'video';
    const REQUEST_SHORTVIDEO= 'shortvideo';
    const REQUEST_LINK      = 'link';

    /**
     * 处理消息
     *
     * @param string $message_type
     * @param array $message
     * @throws SPException
     */
    static protected function ProcessRequest($message_type, array $message)
    {
        $class = self::$MessageHandlers[$message_type];
        if (empty($class) || !class_exists($class)) {
            throw new SPException('Invalid message handler');
        }

        $object = new $class($message);
        $object->response();
        exit;
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


class spWeixin
{
    static public function App()
    {
        return new spWxApp(SPWX_APP_ID, SPWX_APP_SECRET);
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
            throw new SPException('bad class');
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

class spWxCache
{
    static protected $key_prefix = 'spwx:';

    static protected function formatKey($key)
    {
        return self::$key_prefix . $key;
    }

    static private function redis_init()
    {
        static $redis = null;
        if (empty($redis) || !$redis->ping()) {
            $redis = new Redis();
            $redis->connect(SPWX_REDIS_HOST, SPWX_REDIS_PORT);
        }

        return $redis;
    }

    static public function set($key, $val, $expire=0)
    {
        $redis = self::redis_init();
        $key = self::formatKey($key);
        $redis->set($key, $val);
        if ($expire) {
            $redis->expire($key, $expire);
        }
    }

    static public function get($key)
    {
        $redis = self::redis_init();
        $key = self::formatKey($key);
        return $redis->get($key);
    }
}


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

if (!class_exists('SPException')) {
    class SPException extends Exception {}
}

# EOF
