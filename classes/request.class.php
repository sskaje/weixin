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
    abstract public function response();
    abstract public function __construct(array $message);

    final public function checkSig()
    {
        if (!defined('SPWX_API_TOKEN')) {
            throw new spWxException('SPWX_API_TOKEN not defined');
        }
        if (!isset($_GET['signature']) || !isset($_GET['timestamp']) || !isset($_GET['nonce'])) {
            throw new spWxException('Missing params');
        }

        $token = SPWX_API_TOKEN;
        $tmpArr = array($token, $_GET['timestamp'], $_GET['nonce']);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $_GET['signature']) {
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
        $this->checkSig();
        echo $this->echostr;
    }
}

/**
 * 默认请求处理
 */
class spWxRequestDefault extends spWxRequest
{
    protected $message;

    public function __construct(array $message)
    {
        $this->message = $message;
    }

    public function response()
    {
        $msg = new spWxResponseText(
            $this->message['from_username'],
            $this->message['to_username']
        );

        if ($this->message['msg_type'] == spWxMessage::REQUEST_TEXT) {
            $msg->setContent('你输入了文本消息，消息内容是：' . $this->message['content']);
        } else if ($this->message['msg_type'] == spWxMessage::REQUEST_IMAGE) {
            $msg->setContent('你发送了一张图片，图片地址是：' . $this->message['pic_url']);
        } else if ($this->message['msg_type'] == spWxMessage::REQUEST_LOCATION) {
            $msg->setContent('你发送了一个坐标，地址是：('.$this->message['latitude'].', '.$this->message['longitude'].')');
        } else if ($this->message['msg_type'] == spWxMessage::REQUEST_URL) {
            $msg->setContent('你发送了一个链接，地址是：' . $this->message['url']);
        } else if ($this->message['msg_type'] == spWxMessage::REQUEST_EVENT) {
            $msg->setContent('你发送了一个事件，类型是：' . $this->message['event']);
        } else {
            $msg->setContent('为什么你会发送这样的消息？');

        }

        echo (string) $msg;
        exit;
    }
}


# EOF