<?php
/**
 * 功能: 采集器
 * 作者: ysz QQ:395373668
 * 时间: 2017/06/04 09:44
 * 文件: ICollector.class.php
 * 工具: PhpStorm
 */
namespace Common\Tool;

use Think\Log;

class ICollector {

    public $cookie_file = false;
    public $url = false;
    public $fetch_content = false;
    public $header = false;
    /**
     * ICollector constructor.
     */
    public function __construct($domain = false,$header = false)
    {
        if($domain && !$this->cookie_file){
            $cookie_file = C('COLLECT_COOKIE_FILES').$domain."_cookie.txt";
            $dir = dirname($cookie_file);
            if(!is_dir($dir)){
                mkdir($dir, 0755, true);
            }
            $this->cookie_file = $cookie_file;

        }
        Log::info($this->cookie_file);
        if($header){
            $this->header = $header;
        }
        return;

        if(!$this->cookie_file && $domain){
            $destination = C("COLLECT_DOWN_PATH");
            if (!is_dir($destination)) {
                mkdir($destination, 0755, true);
            }
            $this->cookie_file = $destination.$domain ;
            if(!file_exists($this->cookie_file)){
                file_put_contents($this->cookie_file,'');
            }
            Log::info($this->cookie_file);
        }
    }

    /**
     * 功能: 设置cookie
     * @param $file
     */
    public function set_cookie($file){
        $this->cookie_file = $file;
    }

    /**
     * 功能: 获取页面内容
     */
    public function fetch($url , $post_data = null){
        $this->url = $url;
        // 初始化一个 cURL 对象
        $curl = curl_init();
        // 设置你需要抓取的URL
        curl_setopt($curl, CURLOPT_URL, $url);
        // 设置header
        if($this->header){
            curl_setopt ( $curl, CURLOPT_HTTPHEADER, $this->header);

        }else{
            curl_setopt($curl, CURLOPT_HEADER, false);
        }

        // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if($this->cookie_file){
            curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookie_file); //使用上面获取的cookies
            curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie_file);
        }

        if(strpos($url,"https") !== false){
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        if(!empty($post_data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        }

        // 运行cURL，请求网页
        $data = curl_exec($curl);
        $this->fetch_content = $data;
        // 关闭URL请求
        curl_close($curl);

        return $data;
    }

    public function get_post($url , $data){
        $rs = $this->fetch($url , $data);
        return $rs;
    }

    public function download($content = false , $file_name = false){

        if(!$content && !$this->fetch_content){
            Log::info("下载空数据");
            return ;
        }
        if(empty($content)){
            $content = $this->fetch_content;
        }
        $file_name = $file_name ? $file_name : date('ymd_H_i_s').".html";
        $destination = C("COLLECT_DOWN_PATH");
        Log::info('$destination:'.$destination);
        Log::info('$log_dir==>'.$destination);
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        $destination = $destination.date('Ymd').'/';
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        $destination .= $file_name;
        file_put_contents($destination,$content);
        return $destination;
    }
}
