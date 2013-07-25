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
        $object->checkSig();
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

/**
 * 请求基类
 *
 */
abstract class spWxRequest
{
    abstract public function response();
    abstract public function __construct(array $message);

    final public function checkSig()
    {
        if (!defined('SPWX_API_TOKEN')) {
            throw new SPException('SPWX_API_TOKEN not defined');
        }
        if (!isset($_GET['signature']) || !isset($_GET['timestamp']) || !isset($_GET['nonce'])) {
            throw new SPException('Missing params');
        }

        $token = SPWX_API_TOKEN;
        $tmpArr = array($token, $_GET['timestamp'], $_GET['nonce']);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $_GET['signature']) {
            return true;
        } else {
            throw new SPException('Invalid Signature');
        }
    }
}

/**
 * No response on requests
 *
 */
class spWxRequestMute extends spWxRequest
{
    public function __construct(array $message)
    {
        # pass
    }

    public function response()
    {
        # pass
    }
}

/**
 * Token 验证消息
 */
class spWxRequestTokenValidation extends spWxRequest
{
    private $echostr;

    public function __construct(array $message)
    {
        $this->echostr   = $message['echostr'];
    }

    public function response()
    {
        echo $this->echostr;
    }
}

/**
 * 微信响应结构
 */
abstract class spWxResponse
{
    protected $from_username;
    protected $to_username;
    protected $create_time;
    protected $msg_type;
    protected $func_flag = 0;


    public function __construct($from_username, $to_username)
    {
        $this->from_username = $from_username;
        $this->to_username = $to_username;
        $this->create_time = time();
    }

    public function setFuncFlag($func_flag)
    {
        $this->func_flag = (int) $func_flag;
    }

    abstract protected function getMessage();

    public function __toString()
    {
        $message = $this->getMessage();

        $string = <<<MESSAGE
<xml>
<ToUserName><![CDATA[{$this->from_username}]]></ToUserName>
<FromUserName><![CDATA[{$this->to_username}]]></FromUserName>
<CreateTime>{$this->create_time}</CreateTime>
<MsgType><![CDATA[{$this->msg_type}]]></MsgType>
{$message}
<FuncFlag>{$this->func_flag}</FuncFlag>
</xml>
MESSAGE;

        return $string;
    }
}

/**
 * 微信文本消息响应
 */
class spWxResponseText extends spWxResponse
{
    protected $msg_type = 'text';
    protected $content = '';
    public function setContent($content)
    {
        $this->content = $content;
    }
    protected function getMessage()
    {
        return "<Content><![CDATA[{$this->content}]]></Content>";
    }
}

/**
 * 微信音乐消息响应
 *
 */
class spWxResponseMusic extends spWxResponse
{
    protected $msg_type = 'music';
    protected $title = '';
    protected $description = '';
    protected $music_url = '';
    protected $hq_music_url = '';

    public function setTitle($title)
    {
        $this->title = $title;
    }
    public function setDescription($description)
    {
        $this->description = $description;
    }
    public function setMusicUrl($music_url)
    {
        $this->music_url = $music_url;
    }
    public function setHQMusicUrl($hq_music_url)
    {
        $this->hq_music_url = $hq_music_url;
    }
    protected function getMessage()
    {
        return <<<MUSIC
<Music>
<Title><![CDATA[{$this->title}]]></Title>
<Description><![CDATA[{$this->description}]]></Description>
<MusicUrl><![CDATA[{$this->music_url}]]></MusicUrl>
<HQMusicUrl><![CDATA[{$this->hq_music_url}]]></HQMusicUrl>
</Music>
MUSIC;

    }
}

/**
 * 微信图文消息响应
 *
 */
class spWxResponseNews extends spWxResponse
{
    protected $msg_type = 'news';
    protected $articles = array();
    protected $article_count = 0;

    public function addArticle($title, $description, $pic_url, $url)
    {
        $this->articles[] = array(
            'title'         =>  $title,
            'description'   =>  $description,
            'pic_url'       =>  $pic_url,
            'url'           =>  $url,
        );

        ++$this->article_count;
    }
    protected function getMessage()
    {
        $message = "<ArticleCount>{$this->article_count}</ArticleCount>";
        $message .= '<Articles>';
        foreach ($this->articles as $article) {
            $message .= <<<ARTICLE
<item>
<Title><![CDATA[{$article['title']}]]></Title>
<Description><![CDATA[{$article['description']}]]></Description>
<PicUrl><![CDATA[{$article['pic_url']}]]></PicUrl>
<Url><![CDATA[{$article['url']}]]></Url>
</item>
ARTICLE;

        }
        $message .= '</Articles>';

        return $message;
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

    public function api_getAccessToken()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->app_id.'&secret='.$this->app_secret;

        var_dump(file_get_contents($url));
    }

}

if (!class_exists('SPException')) {
    class SPException extends Exception {}
}

# EOF