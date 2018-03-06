<?php
require_once(dirname(__FILE__).'/application.class.php');
header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set("UTC");

/**
 * 信息配置区域
 * 请务必根据实际情况填写
 */
$accessKey = ""; //提供AccessKey
$secretKey = ""; //提供SecretKey
$rootdomain = "example.org"; //提供主域名
$subhostname = "www"; //提供子域名

//用于获取公网IP的第三方接口
//如需自定义，请确保您选择的接口返回JSON格式
//并默认返回格式如下：{"client_ip":"218.75.119.142","request_id":1520307132}
//否则您可能需要修改application.class.php实现自定义格式
$public_ip_fetcher = 'http://115.159.116.240/getip.php';

//获取阿里云DNS实例
$alibaba_dns = AliDDNS::getInstance($accessKey, $secretKey, $rootdomain, $subhostname, $public_ip_fetcher);
//绑定 ip 到域名
$rid = $alibaba_dns->DescribeDomainRecords();
if ($rid == null) {
    $alibaba_dns->outPut('{"result":"error","code":500}');
}
$alibaba_dns->UpdateDomainRecord($rid);