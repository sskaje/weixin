<?php
/**
 * API Token
 */
define('SPWX_API_TOKEN',  '');
/**
 * APP ID
 */
define('SPWX_APP_ID',     '');
/**
 * APP Secret
 */
define('SPWX_APP_SECRET', '');
/**
 * APP encodingAESKey
 */
define('SPWX_APP_AESKEY', '');

/**
 * APP Redis
 */
define('SPWX_REDIS_HOST', '127.0.0.1');
define('SPWX_REDIS_PORT', 6379);

/**
 * Menu Management Class
 */
define('SPWX_MENU_CLASS', '');

/**
 * Request Handler Classes
 *
 */
$GLOBALS['REQUEST_HANDLERS'] = [
    spWxMessage::REQUEST_TEXT     => 'MHRequestDefault',
    spWxMessage::REQUEST_URL      => 'MHRequestDefault',
    spWxMessage::REQUEST_LOCATION => 'MHRequestDefault',
    spWxMessage::REQUEST_IMAGE    => 'MHRequestDefault',
    spWxMessage::REQUEST_VOICE    => 'MHRequestDefault',
    spWxMessage::REQUEST_VIDEO    => 'MHRequestDefault',
    spWxMessage::REQUEST_EVENT    => 'MHRequestDefault',
];


class MHRequestDefault extends spWxRequest
{
    protected $message;

    public function __construct(array $message)
    {
        $this->message = $message;
    }

    public function response()
    {

        if ($this->message['msg_type'] == spWxMessage::REQUEST_LOCATION) {
            #$msg->setContent('你发送了一个坐标，地址是：('.$this->message['latitude'].', '.$this->message['longitude'].')');

            $msg = new spWxResponseText(
                $this->message['from_username'],
                $this->message['to_username']
            );
            $msg->setContent("我猜你是想知道我在哪儿？\n我还是给你我的联系方式吧。\n\n");

            echo (string) $msg;
            exit;
            #} else if ($this->message['msg_type'] == spWxMessage::REQUEST_IMAGE) {
            #$msg->setContent('你发送了一张图片，图片地址是：' . $this->message['pic_url']);
            #} else if ($this->message['msg_type'] == spWxMessage::REQUEST_URL) {
            #$msg->setContent('你发送了一个链接，地址是：' . $this->message['url']);
        } else {

            $msg = new spWxResponseText(
                $this->message['from_username'],
                $this->message['to_username']
            );
            $msg->setContent("我不懂你在说什么啊\n\n" );
            echo (string) $msg;
            exit;
        }
    }
}

# EOF