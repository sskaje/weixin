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
 * App.php访问需要的密钥
 */
define('SPWX_AUTH_KEY',   '');

/**
 * 日志文件
 *
 */
define('SPWX_LOG_FILE',     '/tmp/wx.example.log');

/**
 * APP Redis
 */
define('SPWX_REDIS_HOST',    '127.0.0.1');
define('SPWX_REDIS_PORT',    6379);
define('SPWX_REDIS_PREFIX', 'spwx:');

/**
 * Menu Management Class
 */
define('SPWX_MENU_CLASS', 'MHMenu');

/**
 * Request Handler Classes
 *
 */
$GLOBALS['REQUEST_HANDLERS'] = [
    spWxMessage::REQUEST_TEXT       => 'MHRequestDefault',
    spWxMessage::REQUEST_LINK       => 'MHRequestDefault',
    spWxMessage::REQUEST_LOCATION   => 'MHRequestDefault',
    spWxMessage::REQUEST_IMAGE      => 'MHRequestDefault',
    spWxMessage::REQUEST_VOICE      => 'MHRequestDefault',
    spWxMessage::REQUEST_VIDEO      => 'MHRequestDefault',
    spWxMessage::REQUEST_SHORTVIDEO => 'MHRequestDefault',
    spWxMessage::REQUEST_EVENT      => 'MHRequestDefault',
];

/**
 * Default Request Handler
 */
class MHRequestDefault extends spWxRequest
{
    const STATUS_TTL = 600;

    const STATUS_DEFAULT             = 0;
    protected function status_set($status)
    {
        spWxUserStatus::Set($this->message->from_username, $status, self::STATUS_TTL);
    }

    protected function status_get()
    {
        return (int) spWxUserStatus::Get($this->message->from_username);
    }

    protected function status_reset()
    {
        $this->status_set(self::STATUS_DEFAULT);
    }

    protected function reply_text($text)
    {
        spWxTransport::Output(
            $this->createMessage(spWxMessage::RESPONSE_TEXT)
                ->setContent($text)
        );
    }

    public function response()
    {
        if ($this->message->msg_type == spWxMessage::REQUEST_LOCATION) {
            $this->reply_text("我猜你是想知道我在哪儿？\n我还是给你我的联系方式吧。\n\n");
        } else if ($this->message->msg_type == spWxMessage::REQUEST_IMAGE) {
            $this->reply_text('你发送了一张图片，图片地址是：' . $this->message->pic_url);
        } else if ($this->message->msg_type == spWxMessage::REQUEST_LINK) {
            $this->reply_text('你发送了一个链接，地址是：' . $this->message->url);
        } else {
            $this->reply_text("我不懂你在说什么啊\n\n" );
        }
    }
}

/**
 * Menu definitions
 *
 * @usage php cli.php example menu_create
 */
class MHMenu extends spWxMenu
{
    public function create()
    {
        return  [
            "button" => [
                [
                    "name"  =>  "Button",
                    "sub_button"    =>  [
                        [
                            "type" => "view",
                            "name" => "我的提问",
                            "url"  => "http://sskaje.me/",
                        ],
                    ]
                ],
                [
                    "type" => "view",
                    "name" => "Click ME",
                    "url"  => "http://sskaje.me/",
                ],
                [
                    "name"       => "Menu",
                    "sub_button" => [
                        [
                            "type" => "click",
                            "name" => "Event",
                            "key"  => "SHOW_WX_CHEMIWEB",
                        ],
                        [
                            "type" => "view",
                            "name" => "Link1",
                            "url"  => "http://sskaje.me/",
                        ],
                    ],
                ],
            ],
        ];
    }
}


# EOF