<?php
/**
 * 微信响应管理
 *
 * @author sskaje http://sskaje.me/
 */

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
        return $this;
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
class spWxResponsePlain extends spWxResponse
{
    protected $msg_type = 'text';
    protected $message = '';
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }
    protected function getMessage()
    {
        return $this->message;
    }

    public function __toString()
    {
        return $this->message;
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
        return $this;
    }
    protected function getMessage()
    {
        return "<Content><![CDATA[{$this->content}]]></Content>";
    }
}
/**
 * 图片消息响应
 *
 */
class spWxResponseImage extends spWxResponse
{
    protected $msg_type = 'image';
    protected $media_id = '';

    public function setMediaId($media_id)
    {
        $this->media_id = $media_id;
        return $this;
    }

    protected function getMessage()
    {
        return <<<MUSIC
<Image>
<MediaId><![CDATA[{$this->media_id}]]></MediaId>
</Image>
MUSIC;

    }
}

/**
 * 语音消息响应
 *
 */
class spWxResponseVoice extends spWxResponse
{
    protected $msg_type = 'voice';
    protected $media_id = '';

    public function setMediaId($media_id)
    {
        $this->media_id = $media_id;
        return $this;
    }

    protected function getMessage()
    {
        return <<<MUSIC
<Voice>
<MediaId><![CDATA[{$this->media_id}]]></MediaId>
</Voice>
MUSIC;

    }
}

/**
 * 视频消息响应
 *
 */
class spWxResponseVideo extends spWxResponse
{
    protected $msg_type = 'video';
    protected $media_id = '';
    protected $title = '';
    protected $description = '';

    public function setMediaId($media_id)
    {
        $this->media_id = $media_id;
        return $this;
    }
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    protected function getMessage()
    {
        return <<<MUSIC
<Video>
<MediaId><![CDATA[{$this->media_id}]]></MediaId>
<Title><![CDATA[{$this->title}]]></Title>
<Description><![CDATA[{$this->description}]]></Description>
</Video>
MUSIC;

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
        return $this;
    }
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
    public function setMusicUrl($music_url)
    {
        $this->music_url = $music_url;
        return $this;
    }
    public function setHQMusicUrl($hq_music_url)
    {
        $this->hq_music_url = $hq_music_url;
        return $this;
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
        return $this;
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


# EOF