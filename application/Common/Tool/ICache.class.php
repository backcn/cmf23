<?php
/**
 * @开发工具: PhpStorm
 * @文件名: ICache.class.php
 * @类功能: 缓存类
 * @开发者: 于孙作
 * @开发时间: 2017/06/13
 * @版本: version 1.0
 */
namespace Common\Tool;
use Think\Log;

class ICache {

    public static $redis = false;

    public function __construct()
    {
        self::init_redis();
    }

    public static function init_redis(){

        if(!self::$redis){
            try{
                self::$redis = new Redis();
                $host = "127.0.0.1";
                $port = 6379;
                self::$redis->pconnect($host,$port,1);

            }catch (Exception $e){
                Log::error("初始化Redis错误===>".var_export($e,true));
            }
        }
    }

    public static function set_array($rs,$key){}
}