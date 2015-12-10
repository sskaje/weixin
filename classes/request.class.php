<?php
/**
 * 微信请求处理
 *
 * @author sskaje http://sskaje.me/
 */

/**
 * 请求基类
 *
 */
abstract class spWxRequest
{
    /**
     * @var spWxRequestObject|spWxRequestValidationObject|spWxRequestTextObject|spWxRequestImageObject|spWxRequestLocationObject|spWxRequestLinkObject|spWxRequestEventObject|spWxRequestVoiceObject|spWxRequestVideoObject
     */
    protected $message;

    public function __construct(spWxRequestObjectBase $message)
    {
        $this->message = $message;
    }

    abstract public function response();

    /**
     * @param string $type
     * @return spWxResponse|spWxResponsePlain|spWxResponseText|spWxResponseImage|spWxResponseVoice|spWxResponseVideo|spWxResponseMusic|spWxResponseNews
     * @throws \spWxException
     */
    final protected function createMessage($type)
    {
        if (!is_subclass_of($type, 'spWxResponse')) {
            throw new spWxException('Invalid response type : ' . $type);
        }

        return new $type(
            $this->message->from_username,
            $this->message->to_username
        );
    }

    /**
     * 检查签名
     * @return bool
     * @throws \spWxException
     */
    final public function checkSignature()
    {
        if (!defined('SPWX_API_TOKEN')) {
            throw new spWxException('SPWX_API_TOKEN not defined');
        }
        if (!isset($_GET['signature']) || !isset($_GET['timestamp']) || !isset($_GET['nonce'])) {
            throw new spWxException('Missing params');
        }

        $token = SPWX_API_TOKEN;
        $tmpArr = array($token, $_GET['timestamp'], $_GET['nonce']);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr === $_GET['signature']) {
            return true;
        } else {
            throw new spWxException('Invalid Signature');
        }
    }
}

/**
 * No response on requests
 *
 */
class spWxRequestMute extends spWxRequest
{

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
    /**
     * @var spWxRequestValidationObject
     */
    protected $message;

    public function response()
    {
        $msg = new spWxResponsePlain('', '');
        $msg->setMessage($this->message->echostr);
        spWxTransport::Output($msg);
    }
}

/**
 * 默认请求处理
 */
class spWxRequestDefault extends spWxRequest
{
    /**
     * @var \spWxRequestObject
     */
    protected $message;

    public function response()
    {
        $msg = $this->createMessage(spWxMessage::RESPONSE_TEXT);

        if ($this->message->msg_type == spWxMessage::REQUEST_TEXT) {
            $msg->setContent('你输入了文本消息，消息内容是：' . $this->message->content);
        } else if ($this->message->msg_type == spWxMessage::REQUEST_IMAGE) {
            $msg->setContent('你发送了一张图片，图片地址是：' . $this->message->pic_url);
        } else if ($this->message->msg_type == spWxMessage::REQUEST_LOCATION) {
            $msg->setContent('你发送了一个坐标，地址是：('.$this->message->latitude.', '.$this->message->longitude.')');
        } else if ($this->message->msg_type == spWxMessage::REQUEST_LINK) {
            $msg->setContent('你发送了一个链接，地址是：' . $this->message->url);
        } else if ($this->message->msg_type == spWxMessage::REQUEST_EVENT) {
            $msg->setContent('你发送了一个事件，类型是：' . $this->message->event);
        } else {
            $msg->setContent('为什么你会发送这样的消息？');
        }

        spWxTransport::Output($msg);
    }
}

/**
 * Class spWxRequestObjectBase
 */
class spWxRequestObjectBase
{
    public function __construct(array $message)
    {
        foreach ($message as $k=>$v) {
            $this->$k = $v;
        }
    }

    public function __get($key)
    {
        return $this->$key;
    }

    public function __set($key, $val)
    {
        # pass
    }
    public function __isset($key)
    {
        return property_exists($this, $key);
    }
    public function __unset($key)
    {
        # pass
    }
}

class spWxRequestValidationObject extends spWxRequestObjectBase
{
    protected $echostr;
}

class spWxRequestObject extends spWxRequestObjectBase
{
    protected $from_username;
    protected $to_username;
    protected $create_time;
    protected $msg_id;
    protected $msg_type;
}

class spWxRequestTextObject extends spWxRequestObject
{
    protected $content;
}

class spWxRequestImageObject extends spWxRequestObject
{
    protected $pic_url;
    protected $media_id;
}

class spWxRequestLocationObject extends spWxRequestObject
{
    protected $latitiude;
    protected $longitude;
    protected $scale;
    protected $label;
}
class spWxRequestLinkObject extends spWxRequestObject
{
    protected $title;
    protected $description;
    protected $url;
}
class spWxRequestEventObject extends spWxRequestObject
{
    protected $event;
    protected $event_key;
}

class spWxRequestVoiceObject extends spWxRequestObject
{
    protected $media_id;
    protected $format;
    protected $recognition;
}
class spWxRequestVideoObject extends spWxRequestObject
{
    protected $media_id;
    protected $thumb_media_id;
}

/**
 * 请求代理接口, 把请求转发到新的URL里
 */
abstract class spWxRequestProxy extends spWxRequest
{
    /**
     * 转发URL
     *
     * @var string
     */
    protected $url;

    final public function response()
    {
        echo spWxRequestForwarder::Forward($this->url);
        exit;
    }
}


# EOF