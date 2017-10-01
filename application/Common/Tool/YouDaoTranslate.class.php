<?php
namespace Common\Tool;
class YouDaoTranslate
{
    public static function translate($d,$from="en", $to = 'zh-CHS' )
    {

//        $url = "http://fanyi.youdao.com/";
        $header[] = "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8";
        $header[] = "Connection: keep-alive";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Host: fanyi.youdao.com";
        $header[] = "Upgrade-Insecure-Requests: 1";
        $header[] = "User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36";
        $collect = new ICollector("fanyi.youdao.com" , $header);

//        $d = 'translate';
        $u = 'fanyideskweb';
        $f = time()*1000;
        $c = "rY0D^0'nM0}g5Mm1z%1G4";

        $sign = md5($u.$d.$f.$c);

        $url = "http://fanyi.youdao.com/translate_o?smartresult=dict&smartresult=rule&sessionFrom=null";
        $post_data = [
            "i"=>$d,
            "from"=>$from,
            'to'=>$to,
            'smartresult'=>'dict',
            'doctype'=>'json',
            'version'=>'2.1',
            'keyfrom'=>'fanyi.web',
            'action'=>'FY_BY_CL1CKBUTTON',
            'typoResult'=>'true',
            'client'=>'fanyideskweb',
            'salt'=>$f,
            'sign'=>$sign
        ];
        $post_data = http_build_query($post_data);
        echo $post_data;
        $rs = $collect->get_post($url , $post_data);
//        $rs = utf8_encode($rs);
        $rs = json_decode($rs, true);

//        header( 'Content-Type:text/html;charset=utf-8 ');
        return $rs['translateResult'][0][0]['tgt'];
        /*
        echo $q = 'Loading CSV into MySQL table with PHP';
        $appkey = "0c5699e1974debfc";
        $secret_key = "MzqGMAKuR3lPg1r7WE3LN7LHmPIbUoBo";
        $salt = time();
        $sign = md5($appkey . $q  . $salt .$secret_key);

        $post_data = [
            'q'=> $q,
            'from'=>$from,
            'to'=>$to,
            'appKey'=>$appkey,
            'salt'=>$salt,
            'sign'=>$sign,
        ];
        $post_data = http_build_query($post_data);

        $collect = new ICollector();
        $rs = $collect->get_post('http://openapi.youdao.com/api' , $post_data);

        echo md5("fanyideskweb"."dict".'Loading CSV into MySQL table with PHP'."1501481083416");die;
        return json_decode($rs , true);
        */


    }
}
