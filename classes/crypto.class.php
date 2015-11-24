<?php

/**
 * 微信传输加密相关方法
 * 微信SDK里使用了MCRYPT_RIJNDAEL_128,使用了128 bits的blocksize和256 bits的key, 即AES256
 * 但是具体实现上,长度短的时候,openssl的解密会出错...
 *
 * @author sskaje
 */
class spWxCrypto
{
    private $aes_key;
    private $app_id;
    private $cipher = 'aes-256-cbc';
    private $blocksize = 32;

    public function __construct($encodingAESKey, $appId)
    {
        $this->aes_key = base64_decode($encodingAESKey);
        $this->app_id = $appId;
    }

    /**
     * 加密数据
     *
     * @param string $text
     * @return string
     */
    public function encrypt($text)
    {
        $random = $this->random_string();
        $text = $random . pack("N", strlen($text)) . $text . $this->app_id;
        $text = $this->pkcs7_pad($text);
        return base64_encode($this->aes_encrypt($text));
    }

    /**
     * 解密数据
     *
     * @param string $data
     * @return bool|string
     */
    public function decrypt($data)
    {
        $data = base64_decode($data);
        $data = $this->aes_decrypt($data);
        $data = $this->pkcs7_unpad($data);
        $data = substr($data, 16); # skip random string

        $length_bytes = substr($data, 0, 4);
        $length_array = unpack('N', $length_bytes);
        $length = $length_array[1];

        $xml = substr($data, 4, $length);
        if (substr($data, 4+$length) != $this->app_id) {
            return false;
        }

        return $xml;
    }

    /**
     * 按 PKCS#7 封装数据
     *
     * @param string $data
     * @return string
     */
    public function pkcs7_pad($data)
    {
        $length = strlen($data);
        $mod = $this->blocksize - $length % $this->blocksize;
        $padding = '';
        if ($mod < $this->blocksize) {
            $padding = str_repeat(chr($mod), $mod);
        }

        return $data . $padding;
    }

    /**
     * 按 PKCS#7 去除Padding
     *
     * @param $data
     * @return string
     */
    public function pkcs7_unpad($data)
    {
        $length = strlen($data);
        if (!isset($data[0]) || !isset($data[$this->blocksize-1]) || $length % $this->blocksize != 0) {
            return $data;
        }

        $last_char = ord(substr($data, -1));

        return substr($data, 0, - $last_char);
    }

    /**
     * 获取IV
     *
     * @return string
     */
    private function getIV()
    {
        return substr($this->aes_key, 0, 16);
    }

    /**
     * 初始化 mcrypt
     *
     * @return resource
     */
    private function mcrypt_init()
    {
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        mcrypt_generic_init($module, $this->aes_key, $this->getIV());

        return $module;
    }

    /**
     * 销毁 mcrypt
     *
     * @param resource $module
     */
    private function mcrypt_deinit($module)
    {
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
    }

    /**
     * aes 加密
     * aes 256 cbc
     *
     * @param $data
     * @return string
     */
    public function aes_encrypt($data)
    {
        $module = $this->mcrypt_init();
        $encrypted = mcrypt_generic($module, $data);
        $this->mcrypt_deinit($module);
        return $encrypted;
    }

    /**
     * aes 解密
     * aes 256 cbc
     *
     * @param string $data
     * @return string
     */
    public function aes_decrypt($data)
    {
        $module = $this->mcrypt_init();
        $decrypted = mdecrypt_generic($module, $data);
        $this->mcrypt_deinit($module);
        return $decrypted;
    }

    /**
     * aes加密
     * 数据长度短时 openssl 实现兼容性有问题
     *
     * @param $data
     * @return string
     */
    public function aes_encrypt_1($data)
    {
        return openssl_encrypt($data, $this->cipher, $this->aes_key, OPENSSL_RAW_DATA, $this->getIV());
    }

    /**
     * aes解密
     * 数据长度短时 openssl 实现兼容性有问题
     *
     * @param $data
     * @return string
     */
    public function aes_decrypt_1($data)
    {
        return openssl_decrypt($data, $this->cipher, $this->aes_key, OPENSSL_RAW_DATA, $this->getIV());
    }

    /**
     * 随机生成16位字符串
     * @return string 生成的字符串
     */
    public function random_string()
    {
        $str = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        return substr(str_shuffle($str), 0, 16);
    }
}

# EOF
