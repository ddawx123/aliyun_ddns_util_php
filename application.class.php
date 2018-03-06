<?php
/**
 * 阿里云DDNS操作类
 * @package DingStudio/CloudCompute
 * @subpackage Utils/AliDDNS
 * @copyright 2012-2018 DingStudio All Rights Reserved
 */

class AliDDNS
{
    private $accessKeyId; //访问公钥
    private $accessSecret; //访问密钥
    private $subhostname; //子域名
    private $rootdomain;
    private $ipUrl; //公网IP检测接口
    private static $_instance  = null; //实例化空间

    /**
     * 构造函数入口
     * @param string $ak Access Key Input
     * @param string $sk Secret Key Input
     * @param string $sh SubHostname Input
     * @param string $ipUrl Public IP Addr API
     */
    private function __construct($ak, $sk, $dm, $sh, $ipUrl)
    {
        $this->accessKeyId = $ak;
        $this->accessSecret = $sk;
        $this->rootdomain = $dm;
        $this->subhostname = $sh;
        $this->ipUrl = $ipUrl;
    }

    /**
     * 统一实例入口
     * @param string $ak Access Key Input
     * @param string $sk Secret Key Input
     * @param string $sh SubHostname Input
     * @param string $ipUrl Public IP Addr API
     * @return instance Instance Object
     */
    public static function getInstance($ak, $sk, $dm, $sh, $ipUrl)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($ak, $sk, $dm, $sh, $ipUrl);
        }
        return self::$_instance;
    }

    /**
     * 使用子域名作为关键词拉取并筛选记录，获取记录ID
     * @return integer Domain Record ID
     */
    public function DescribeDomainRecords()
    {
        $requestParams = array(
            "Action"    =>  "DescribeDomainRecords",
            "DomainName"    =>  $this->rootdomain,
            "KeyWord"   =>  $this->subhostname
        );
        $val = $this->requestAli($requestParams);
        $detail = json_decode($val);
        foreach ($detail->DomainRecords->Record as $subhost) {
            if ($subhost->RR == $this->subhostname) {
                return $subhost->RecordId;
            }
        }
        return null;
        //$this->outPut(json_encode($detail->DomainRecords->Record));
    }

    /**
     * 变更指定记录ID的解析配置
     * @param integer $record_id 记录ID
     * @return null
     */
    public function UpdateDomainRecord($record_id)
    {
        $ip = $this->ip();
        $requestParams = array(
            "Action"        =>  "UpdateDomainRecord",
            "RecordId"      =>  $record_id,
            "RR"            =>  $this->subhostname,
            "Type"          =>  "A",
            "Value"         =>  $ip,
        );
        $val =  $this->requestAli($requestParams);
        $result = json_decode($val);
        $result->request_ipaddr = $ip;
        $json = json_encode($result);
        $this->outPut($json);
    }

    /**
     * 构造Http请求并调用发起请求操作
     * @param array $requestParams 请求参数
     * @return string 服务端响应数据
     */
    private function requestAli($requestParams)
    {
        $publicParams = array(
            "Format"        =>  "JSON",
            "Version"       =>  "2015-01-09",
            "AccessKeyId"   =>  $this->accessKeyId,
            "Timestamp"     =>  date("Y-m-d\TH:i:s\Z"),
            "SignatureMethod"   =>  "HMAC-SHA1",
            "SignatureVersion"  =>  "1.0",
            "SignatureNonce"    =>  substr(md5(rand(1, 99999999)), rand(1, 9), 14),
        );

        $params = array_merge($publicParams, $requestParams);
        $params['Signature'] =  $this->sign($params, $this->accessSecret);
        $uri = http_build_query($params);
        $url = 'http://alidns.aliyuncs.com/?'.$uri;
        return $this->curl($url);
    }


    /**
     * 使用第三方接口获取本机公网IP
     * @return string IP地址
     */
    private function ip()
    {
        $ip = $this->curl($this->ipUrl);
        $ip = json_decode($ip, true);
        return $ip['client_ip'];
    }

    /**
     * 通讯参数签名函数
     * @param string $params 明文参数
     * @param string $accessSecret 密钥
     * @param string $method 请求方式
     * @return string 签名后的base64编码内容
     */
    private function sign($params, $accessSecret, $method = "GET")
    {
        ksort($params);
        $stringToSign = strtoupper($method).'&'.$this->percentEncode('/').'&';

        $tmp = "";
        foreach ($params as $key => $val) {
            $tmp .= '&'.$this->percentEncode($key).'='.$this->percentEncode($val);
        }
        $tmp = trim($tmp, '&');
        $stringToSign = $stringToSign.$this->percentEncode($tmp);

        $key  = $accessSecret.'&';
        $hmac = hash_hmac("sha1", $stringToSign, $key, true);

        return base64_encode($hmac);
    }


    /**
     * 字符串格式化工具函数
     * @param string $value 待处理字符串
     * @return string $en 处理后的字符串
     */
    private function percentEncode($value = null)
    {
        $en = urlencode($value);
        $en = str_replace("+", "%20", $en);
        $en = str_replace("*", "%2A", $en);
        $en = str_replace("%7E", "~", $en);
        return $en;
    }

    /**
     * Http请求实现过程
     * @param string $url 请求地址
     * @return string $result 响应内容
     */
    private function curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        $result = curl_exec($ch);
        return $result;
    }

    /**
     * 输出信息
     * @param string $msg 信息内容
     */
    private function outPut($msg)
    {
        echo $msg.PHP_EOL;
    }
}
