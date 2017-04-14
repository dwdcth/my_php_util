<?php
class OssPolicy
{

private $oss_bucket ;
private $oss_host;
private $oss_appid;
private $oss_appsecret;
private $oss_expire ;//过期时间
/**
 * OssPolicy constructor.
 * @param $bucket 阿里云oss的bucket
 * @param $host 对应bucket的host
 * @param $appId app_id
 * @param $appSecret app_secret
 * @param int $expire 过期时间
 */
public function __construct($bucket,$host,$appId,$appSecret,$expire=900) {
    $this->oss_expire = $expire;
    $this->oss_bucket = $bucket;
    $this->oss_host = $host;
    $this->oss_appid = $appId;
    $this->oss_appsecret = $appSecret;
}

private function gmt_iso8601($time) {
    $dtStr = date("c", $time); //格式为2016-12-27T09:10:11+08:00
    $mydatetime = new DateTime($dtStr);
    $expiration = $mydatetime->format(DateTime::ISO8601); //格式为2016-12-27T09:12:32+0800
    $pos = strpos($expiration, '+');
    $expiration = substr($expiration, 0, $pos);//格式为2016-12-27T09:12:32
    return $expiration."Z";
}

/**
 * @function getPolicy 获取policy
 * @author 
 * @version 1.0
 * @date
 * @param $dir 上传目录
 * @param $maxSize 最大文件大小 单位M
 * @param int $expireTime 过期时间
 * @return $array policy
 */
public function getPolicy($dir,$maxSize=100,$expireTime=null){
    $expireTime = isset($expireTime) ? $expireTime : $this->oss_expire;
    $end = time() + $expireTime;
    $expiration = $this->gmt_iso8601($end);

    $conditions = [];
    $conditions[] = array(0=>'content-length-range', 1=>0, 2=>1024*1024*$maxSize); // 最大文件大小.用户可以自己设置 100M

    $start = array(0=>'starts-with', 1=>'$key', 2=>$dir); //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
    $conditions[] = $start;

    $arr = array('expiration'=>$expiration,'conditions'=>$conditions);
    $policy = json_encode($arr);
    $base64_policy = base64_encode($policy);
    $string_to_sign = $base64_policy;
    $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->oss_appsecret, true));

    $response = array();
    $response['accessid'] = $this->oss_appid;
    $response['host'] = $this->oss_host;
    $response['policy'] = $base64_policy;
    $response['signature'] = $signature;
    $response['expire'] = $end;
    $response['bucket'] = $this->oss_bucket;
    $response['dir'] = $dir;  //这个参数是设置用户上传指定的前缀

    return $response;
}
}
