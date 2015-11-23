<?php


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
            #$msg->setContent('你发送了一个坐标，地址是：('.$this->message['geo']['latitude'].', '.$this->message['geo']['longitude'].')');

            $msg = new spWxResponseText(
                $this->message['from_username'],
                $this->message['to_username']
            );
            $msg->setContent("我猜你是想知道我在哪儿？\n我还是给你我的联系方式吧。\n\n" . MHUtil::getContactMessage());

            echo (string) $msg;
            exit;
            #} else if ($this->message['msg_type'] == spWxMessage::REQUEST_IMAGE) {
            #$msg->setContent('你发送了一张图片，图片地址是：' . $this->message['pic_url']);
            #} else if ($this->message['msg_type'] == spWxMessage::REQUEST_URL) {
            #$msg->setContent('你发送了一个链接，地址是：' . $this->message['link']['url']);
        } else {

            $msg = new spWxResponseText(
                $this->message['from_username'],
                $this->message['to_username']
            );
            $msg->setContent("我不懂你在说什么啊\n\n" . MHUtil::getMenuMessage());
            echo (string) $msg;
            exit;
        }



    }
}


class MHRequestEvent extends spWxRequest
{
    protected $event;
    protected $eventKey;
    protected $message;

    public function __construct(array $message)
    {
        $this->message = $message;
        $this->event = $message['event']['event'];
        $this->eventKey = $message['event']['eventKey'];
    }

    public function response()
    {
        $msg = new spWxResponseText(
            $this->message['from_username'],
            $this->message['to_username']
        );

        if ($this->event == 'subscribe') {
            # add user
            MHUser::add($this->message['from_username'], date('Y-m-d H:i:s', $this->message['create_time']));
            MHUser::setState($this->message['from_username'], MHState::LEVELTEST);

            $message = "欢迎来到迷糊酒铺，先回答几个问题看看自己对葡萄酒有多少了解吧！\n\n（如果您着急买醉，请回复“0”跳过此问题。）\n\n";

            $message .= MHLevelTest::getQuestion(0);

            $msg->setContent($message);

            echo (string) $msg;
            exit;
        }


    }
}

class MHRequestText extends spWxRequest
{
    protected $message;
    public function __construct(array $message)
    {
        $this->message = $message;
    }

    public function response()
    {
        $user = MHUser::get($this->message['from_username']);
        if (!$user) {
            MHUser::add($this->message['from_username'], date('Y-m-d H:i:s', $this->message['create_time']));
            $state = 0;
            $level = 0;
        } else {
            $state = $user['state'];
            $level = $user['level'];
        }
        # Level Test
        if ($state == MHState::LEVELTEST) {
            $input = strtoupper(trim($this->message['Content']));
            if ($level == 0 && $input == '0') {
                MHUser::setState($this->message['from_username'], MHState::ROOT);

                $msg = new spWxResponseText(
                    $this->message['from_username'],
                    $this->message['to_username']
                );

                $msg->setContent(MHUtil::getMenuMessage());

                echo (string) $msg;
                exit;
            }

            $question_info = MHLevelTest::getQuestionFull($level);
            if (in_array($input, $question_info['w'])) {
                $msg = new spWxResponseText(
                    $this->message['from_username'],
                    $this->message['to_username']
                );

                $message = '';
                if ($input == $question_info['a']) {
                    # level passed
                    $message .= $question_info['mp'];
                    MHUser::incrLevel($this->message['from_username']);

                    $next_level_Q = MHLevelTest::getQuestion($level + 1);
                    if ($next_level_Q) {
                        $message .= "\n\n\n" . $next_level_Q;
                    } else {
                        MHUser::setState($this->message['from_username'], MHState::ROOT);
                    }

                } else {
                    # level failed
                    MHUser::setState($this->message['from_username'], MHState::ROOT);
                    $message .= $question_info['mf'];
                }

                $msg->setContent($message);

                echo (string) $msg;
                exit;
            } else {
                # no response to wrong input
            }

        } else if ($state == MHState::ROOT) {
            $input = trim($this->message['Content']);

            if ($input == 1) {
                # 名酒酒单
            } else if ($input == 2) {
                # 精品酒酒单
            } else if ($input == 3) {
                # 近期活动
            } else if ($input == 4) {
                # 近期促销
            } else if ($input == 5) {
                # 联系我们
            }

        }



    }
}


class MHUtil
{
    static public function getMySQL()
    {
        $pdo = new pdo('mysql://127.0.0.1/mihu', 'root', '123123123');

        return $pdo;
    }

    static public function getMenuMessage()
    {
        $message = '欢迎来到迷糊酒铺
输入编号查询信息
1 索取名酒酒单
2 索取精品酒酒单
3 近期酒会活动
4 近期促销活动
5 联系我们';
        return $message;
    }

    static public function getContactMessage()
    {
        $message = '电话：010-80220991
QQ： 12025556
邮箱：12025556@qq.com
淘宝店铺：http://mihu9.taobao.com/';
        return $message;
    }
}

class MHUser
{
    static public function add($open_username, $datetime)
    {
        $db = MHUtil::getMySQL();
        $stmt = $db->prepare('INSERT INTO `mh_user` SET `open_username`=?, `timeline`=?, `status`=1 ON DUPLICATE UPDATE `status`=1, `timeline`=?');
        $stmt->execute(array($open_username, $datetime, $datetime));

        return true;
    }

    static public function remove($open_username, $datetime)
    {
        $db = MHUtil::getMySQL();
        $stmt = $db->prepare('INSERT INTO `mh_user` SET `open_username`=?, `timeline`=?, `status`=0 ON DUPLICATE UPDATE `status`=0, `timeline`=?');
        $stmt->execute(array($open_username, $datetime, $datetime));

        return true;
    }

    static public function get($open_username)
    {
        $db = MHUtil::getMySQL();
        $stmt = $db->prepare('SELECT * FROM `mh_user` WHERE `open_username`=?');
        $stmt->execute(array($open_username));
        $row = $stmt->fetch();
        return $row;
    }

    static public function setState($open_username, $state)
    {
        $db = MHUtil::getMySQL();
        $stmt = $db->prepare('UPDATE `mh_user` SET `state`=? WHERE `open_username`=?');
        $stmt->execute(array($state, $open_username));
        return $stmt->rowCount();
    }

    static public function incrLevel($open_username)
    {
        $db = MHUtil::getMySQL();
        $stmt = $db->prepare('UPDATE `mh_user` SET `level`=`level`+1 WHERE `open_username`=?');
        $stmt->execute(array($open_username));
        return $stmt->rowCount();
    }
}

class MHState
{
    const ROOT = 0;
    const LEVELTEST = 1;
}

class MHLevelTest
{
    protected $questions = array(
        0   =>  array(
            'q' =>  "干红的意思是？\n\n\nA 干涩的红葡萄酒   B 不甜的红葡萄酒\n",
            'a' =>  'B',
            'w'  =>  array('A', 'B'),
            'mf' =>  '您的葡萄酒等级：还是去喝农夫山泉吧',
            'mp' =>  '答对了！第二题：',
        ),
        1   =>  array(
            'q' =>  "Merlot 美乐 是什么？\n\n\nA 一种酿造工艺   B 一个葡萄酒产区  C 一个葡萄品种  D一个葡萄酒品牌\n",
            'a' =>  'C',
            'w' =>  array('A', 'B', 'C', 'D'),
            'mf' =>  '您的葡萄酒等级：还是去喝农夫山泉吧',
            'mp' =>  '答对了！第三题：',
        ),


    );


    static public function getQuestion($level)
    {
        if (isset(self::$questions[$level])) {
            return self::$questions[$level]['q'];
        } else {
            return '据说您好像已经无敌了？';
        }
    }

    static public function getQuestionFull($level)
    {
        if (isset(self::$questions[$level])) {
            return self::$questions[$level];
        } else {
            return false;
        }
    }

    static public function getAnswer($level)
    {
        if (isset(self::$questions[$level])) {
            return self::$questions[$level]['a'];
        } else {
            return false;
        }
    }

    static public function getPassTip($level)
    {
        if (isset(self::$questions[$level])) {
            return self::$questions[$level]['mp'];
        } else {
            return false;
        }
    }

    static public function getFailTip($level)
    {
        if (isset(self::$questions[$level])) {
            return self::$questions[$level]['mf'];
        } else {
            return false;
        }

    }
}

