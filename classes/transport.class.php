<?php
/**
 * 微信api协议传输封装
 * 明文 / AES 256 CBC
 *
 * @author sskaje
 */

/**
 * 协议传输层
 */
class spWxTransport
{
    /**
     * 获取输入
     *
     * @return bool|string
     */
    static public function Input()
    {
        if (isset($_GET['encrypt_type']) && $_GET['encrypt_type'] != 'raw') {
            $input = spWxTransportEncrypted::Input();
        } else {
            $input = spWxTransportPlain::Input();
        }

        spWxLogger::Log(
            'spWxTransport::Input',
            [
                'QueryString' => $_SERVER['QUERY_STRING'],
                'Input'       => $input,
            ]
        );

        return $input;
    }

    /**
     * 输出
     *
     * @param \spWxResponse $response
     */
    static public function Output(spWxResponse $response)
    {
        if (isset($_GET['encrypt_type']) && $_GET['encrypt_type']) {
            $output = spWxTransportEncrypted::Output($response);
        } else {
            $output = spWxTransportPlain::Output($response);
        }


        spWxLogger::Log(
            'spWxTransport::Output',
            [
                'output'    => $output,
            ]
        );

        echo $output;
        exit;
    }
}

/**
 * 明文传输
 */
class spWxTransportPlain
{
    /**
     * 获取输入
     *
     * @return string
     */
    static public function Input()
    {
        return file_get_contents('php://input');
    }

    /**
     * 输出
     *
     * @param \spWxResponse $response
     * @return string
     */
    static public function Output(spWxResponse $response)
    {
        return (string) $response;
    }
}

/**
 * AES 256 CBC 加密传输
 */
class spWxTransportEncrypted
{
    /**
     * 计算加密签名
     * @param string $encrypt_msg
     * @return string
     */
    static public function GetSignature($encrypt_msg)
    {
        $array = array($encrypt_msg, SPWX_API_TOKEN, $_GET['timestamp'], $_GET['nonce']);
        sort($array, SORT_STRING);
        $str = implode($array);

        return sha1($str);
    }

    /**
     * 比对加密签名
     *
     * @param string $encrypt_msg
     * @return bool
     * @throws \spWxException
     */
    static public function CheckSignature($encrypt_msg)
    {
        if (self::GetSignature($encrypt_msg) === $_GET['msg_signature']) {
            return true;
        } else {
            throw new spWxException('Invalid Signature');
        }
    }

    /**
     * 获取加密算法对象
     *
     * @return \spWxCrypto
     */
    static public function GetCrypto()
    {
        return new spWxCrypto(SPWX_APP_AESKEY, SPWX_APP_ID);
    }

    /**
     * 获取输入
     *
     * @return bool|string
     * @throws \spWxException
     */
    static public function Input()
    {
        $input = file_get_contents('php://input');

        $postObj = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);

        $encrypted = (string) $postObj->Encrypt;

        self::CheckSignature($encrypted);

        $crypto = self::GetCrypto();
        $decrypted = $crypto->decrypt($encrypted);

        return $decrypted;
    }

    /**
     * 输出
     *
     * @param \spWxResponse $response
     * @return string
     */
    static public function Output(spWxResponse $response)
    {
        $crypto = self::GetCrypto();
        $encrypted = $crypto->encrypt((string) $response);
        $signature = self::GetSignature($encrypted);
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];

        return <<<OUTPUT
<xml>
  	<Encrypt><![CDATA[{$encrypted}]]></Encrypt>
	<MsgSignature><![CDATA[{$signature}]]></MsgSignature>
	<TimeStamp>{$timestamp}</TimeStamp>
	<Nonce><![CDATA[{$nonce}]]></Nonce>
</xml>
OUTPUT;
    }
}

# EOF
