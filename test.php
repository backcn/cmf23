<?php
/**
 * @开发工具: PhpStorm
 * @文件名: test.php
 * @类功能:
 * @开发者: 于孙作
 * @开发时间: 2017/06/12
 * @版本: version 1.0
 */

$redis = new Redis();

$host = '127.0.0.1';
$port = 6379;
$redis->connect($host, $port);

try{
    $redis->select(0);
    for($i=0 ;$i< 1000000000000;$i++){
        $redis->set('a'.$i,"asfdfsfsdfdsffsssssssssssssssssssssssssssssasfdfsfsdfdsffsssssssssssssssssssssssssssssasfdfsfsdfdsffsssssssssssssssssssssssssssssasfdfsfsdfdsffsssssssssssssssssssssssssssssasfdfsfsdfdsffsssssssssssssssssssssssssssss".$i);
    }

}catch (Exception $e){
    print_r($e);
}

