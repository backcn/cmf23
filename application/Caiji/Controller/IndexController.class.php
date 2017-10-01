<?php
/**
 * 功能: 采集器
 * 作者: ysz QQ:395373668
 * 时间: 2017/06/04 09:24
 * 文件: IndexController.class.php
 * 工具: PhpStorm
 */
namespace Caiji\Controller;

use Caiji\Enum\StatckOverFlowStatusEnum;
use Caiji\Lib\StackoVerFlow;
use Common\Controller\AdminbaseController;
use Common\Controller\HomebaseController;
use Common\Tool\GoogleTranslate;
use Common\Tool\ICollector;
use Common\Tool\ParserDom;
use Common\Tool\YouDaoTranslate;
use Think\Log;

class IndexController extends HomebaseController{
    const StackOverFlowDomain = 'stackoverflow.com';
    const StackOverFlowHome = "https://stackoverflow.com";
    const StackOverFLowTagLink = "https://stackoverflow.com/questions/tagged/";

    public $stack_over_flow = false;
    public $stack_collector = false;
    public $m_stackoverflow = false;
    public $m_stackoverflow_answer = false;
    public $m_stackoverflow_answer_comment = false;
    public $m_stackoverflow_question_comment = false;
    public $m_stackoverflow_tag = false;

    private static $StackOverHeader = [
        ':authority'=>'stackoverflow.com',
        '::path'=>'',
        ':scheme'=>'https',
        'accept'=>'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'accept-encoding'=>'gzip, deflate, sdch, br',
        'accept-language'=>'zh-CN,zh;q=0.8',
        'referer'=>'https://stackoverflow.com/',
        'upgrade-insecure-requests'=>'1',
        'user-agent'=>'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
    ];
    private static $log_file = false;

    public $replace_index = 0;
    public $stack_id = 0;
    public $override_data =[];

    public function __construct()
    {
        parent::__construct();

        if(!self::$log_file){
            self::$log_file = 'caiji_index_'.date('Ymd').".log";
        }

        /**
         *  初始化
         */
        $this->stack_over_flow = new StackoVerFlow();
        $this->stack_collector = new ICollector(self::StackOverFlowDomain,self::$StackOverHeader);
        $this->m_stackoverflow = M('stackoverflow');
        $this->m_stackoverflow_answer = M('stackoverflow_answer');
        $this->m_stackoverflow_answer_comment = M('stackoverflow_answer_comment');
        $this->m_stackoverflow_question_comment = M('stackoverflow_question_comment');
        $this->m_stackoverflow_tag = M('stackoverflow_tag');

    }

    public function index(){
        echo "call Caiji index";
    }

    /**
     * 功能: 1.获取tags 热门tag 列表
     */
    public function get_tags_link(){
        $page = empty(I('get.page')) ? 1 : I('get.page');

        $stack = new StackoVerFlow();
        $tag_list = $stack->get_tags_from_stack($page);
        Log::info(var_export($tag_list,true));
        foreach ($tag_list as $tag){
            $link = self::StackOverFLowTagLink.$tag;
            $is_exist = $this->m_stackoverflow_tag->where(['link'=>$link])->find();
            Log::info(var_export($is_exist,true));
            if(!$is_exist){
                $this->m_stackoverflow_tag->add([
                    'link'          =>  $link,
                    'tag'           =>  $tag,
                    'count'         =>  0,
                    'update_time'   =>  get_date_time(),
                ]);
                Log::info($this->m_stackoverflow->getLastSql());
            }
        }
    }

    /**
     * 功能: 2.获取 热门tag链接
     */
    public function get_page_link_list(){
        $tag = M('stackoverflow_tag')->order('page_index asc')->find();

        $collector = new ICollector(self::StackOverFlowDomain,self::$StackOverHeader);
        $tag_link = $tag['link']."?page=".$tag['page_index']."&sort=frequent&pagesize=30";
        $content = $collector->fetch($tag_link);

        $stack = new StackoVerFlow();
        $list = $stack->get_links($content);

        if(empty($list)){
            Log::error("连接为空=====>".$tag['link']."          page=>".$tag['page_index']);
            exit;
        }else{
            Log::info("链接列表===>".var_export($list,true),self::$log_file);
            $stack->set_tag_index_add($tag['id']);
        }

        $m_stackoverflow = M('stackoverflow');
        $m_stackoverflow->startTrans();
        foreach ($list as &$l) {
            $l = self::StackOverFlowHome.$l;

            var_dump($l);

            $exist = $m_stackoverflow->where(['link'=>$l])->find();

            if(!$exist){

                $stack_id = $m_stackoverflow->add([
                    'link'=>$l,
                    'status'=>StatckOverFlowStatusEnum::GetLink,
                    'create_time'=>get_date_time(),
                    'tag_id' => $tag['id'],
                ]);

                Log::info('新增 stack_id==>'.$stack_id);
            }else{
                Log::info("重复链接:".$l);
            }

        }
        $m_stackoverflow->commit();

        var_dump($list);
        $this->success("完成",U('get_page_link_list'),30);
    }

    /**
     * 功能: 3.采集页面
     */
    public function down_load_page(){
        $data = $this->m_stackoverflow->where(['status'=>StatckOverFlowStatusEnum::GetLink])->order("stack_id asc")->find();
//        $data = $this->m_stackoverflow->where(['stack_id'=>564])->find();

        if(empty($data)){
            return;
        }
//        $this->m_stackoverflow->where(['stack_id'=>$data['stack_id']])->save(['status'=>StatckOverFlowStatusEnum::DownLoad]);
        self::_change_status($data['stack_id'] , StatckOverFlowStatusEnum::DownLoadFail);
        print_r($data);


        $content = $this->stack_collector->fetch($data['link']);

        if(strpos($content,'title') === false){
            self::_change_status($data['stack_id'] , StatckOverFlowStatusEnum::DownLoadFail);
            Log::error("页面下载失败：".$data['link']);

            $this->error("失败",U('down_load_page'),30);
            return;
        }
        //下载文件--------------
        self::_change_status($data['stack_id'] , StatckOverFlowStatusEnum::DownLoad);
        $destination = $this->stack_collector->download($content,$data['stack_id'].'.html');
        $this->m_stackoverflow->where(['stack_id'=>$data['stack_id']])->save(['download_file'=>$destination ,'update_time'=>get_date_time()]);
        $this->success("完成",U('down_load_page'),30);
        exit;

        $parse_data = $this->stack_over_flow->parse_content($content);

        if(empty($parse_data['title'])){
            self::_change_status($data['stack_id'] , StatckOverFlowStatusEnum::ParseContentFail);
            Log::error("页面格式化失败：".$data['link'].'     stack_id==>'.$data['stack_id']);

            $this->error("失败",U('down_load_page'),30);
            return;
        }

        $this->m_stackoverflow->where(['stack_id'=>$data['stack_id']])->save([
            'update_time'=>get_date_time(),
            'title'=>$parse_data['title'],
            'question'=>$parse_data['question']['body'],
        ]);

        foreach ($parse_data['question']['comment_list'] as $comment){
            $this->m_stackoverflow_question_comment->add([
                'stack_id' => $data['stack_id'],
                'question_comment' => $comment
            ]);
        }

        //下载文件
        $destination = $this->stack_collector->download($content,$data['stack_id'].'.html');

        $this->m_stackoverflow->where(['stack_id'=>$data['stack_id']])->save(['download_file'=>$destination]);

        //Answer
        foreach ($parse_data['answer'] as $answer){

            $answer_id = $this->m_stackoverflow_answer->add([
                'stack_id' => $data['stack_id'],
                'answer' => $answer['answer_content']
            ]);

            var_dump('answer_id'.$answer_id);
            if($answer_id){
                foreach ($answer['answer_comment_list'] as $answer_comment){
                    $this->m_stackoverflow_answer_comment->add([
                        'answer_id' => $data['stack_id'],
                        'answer_comment' => $answer_comment
                    ]);
                }
            }
        }

        self::_change_status($data['stack_id'],StatckOverFlowStatusEnum::ParseContent);
        Log::info('成功 格式化 页面 id='.$data['stack_id']);

        $this->success("完成",U('down_load_page'),30);

    }

    /**
     * 功能：4.格式化
     */
    public function parse_content(){


        $data = $this->m_stackoverflow->where(['status'=>StatckOverFlowStatusEnum::DownLoad])->order("stack_id asc")->find();
        if(empty($data)){
            $this->error("无数据");
            exit;
        }
        $this->m_stackoverflow->where(['stack_id'=>$data['stack_id']])->save(['status'=>StatckOverFlowStatusEnum::ParseContentFail , 'update_time'=>get_date_time()]);
        $file = SITE_PATH.$data['download_file'];

        $url = "http://robot.codingerror.cn/".$data['download_file'];
        echo "<p><a href='{$url}' target='_blank'>{$url}</a></p>";
        $file_content = file_get_contents($file);
        /*
        $stack_over_flow = new StackoVerFlow($file_content);
        $content = $stack_over_flow->get_content($url);
        */

        $parse_dom = new ParserDom($file_content);
        $has_accept = $parse_dom->find("span.vote-accepted-on");


        $title_down = $parse_dom->find('a.question-hyperlink');
        if(empty($title_down[0])){
            $this->error("DOM解析异常",U('parse_content'),5);
        }
        $title = $title_down[0]->innerHtml();
        /*
        $you_dao = new YouDaoTranslate();
        $title  = $you_dao->translate($title);
        */
        $this->m_stackoverflow->where(['stack_id'=>$data['stack_id']])->save(['title'=>$title]);
        $title = GoogleTranslate::translate($title);
        $this->m_stackoverflow->where(['stack_id'=>$data['stack_id']])->save(['title_cn'=>$title]);

        $post_text = $parse_dom->find(".post-text");
        if(empty($post_text[0])){
            $this->error("DOM解析异常",U('parse_content'),5);
        }
        $question = $post_text[0]->innerHtml();
        $this->m_stackoverflow->where(['stack_id'=>$data['stack_id']])->save(['question'=>$question]);
//        $question_cn = self::_parse_translate_data($question);
//        $this->m_stackoverflow->where(['stack_id'=>$data['stack_id']])->save(['question_cn'=>$question_cn]);
        if(empty($post_text[1])){
            $this->error("DOM解析异常",U('parse_content'),5);
        }
        $answer = $post_text[1]->innerHtml();
//        $answer_cn = self::_parse_translate_data($answer);

        $answer_id = $this->m_stackoverflow_answer->where(['stack_id'=>$data['stack_id']])->getField('answer_id');
        if(empty($answer_id)){
            $this->m_stackoverflow_answer
                ->add([
                'answer'=>$answer ,
                'stack_id'=>$data['stack_id'],
//                'answer_cn'=>$answer_cn,
                'update_time'=>get_date_time(),
            ]);
        }else{
            $this->m_stackoverflow_answer
                ->where(['answer_id'=>$answer_id])
                ->save([
                'answer'=>$answer ,
                'stack_id'=>$data['stack_id'],
//                'answer_cn'=>$answer_cn,
                'update_time'=>get_date_time(),
            ]);
        }

        if(empty($has_accept)){
            $this->m_stackoverflow->where(['stack_id'=>$data['stack_id']])->save(['status'=>StatckOverFlowStatusEnum::NoAccept , 'update_time'=>get_date_time()]);
        }else{
            unlink($file);
            $this->m_stackoverflow->where(['stack_id'=>$data['stack_id']])->save(['status'=>StatckOverFlowStatusEnum::ParseContent , 'update_time'=>get_date_time(),'download_file'=>null]);
        }

        $this->success("完成",U('parse_content'),5);
    }

    /**
     * 功能：5.翻译
     */
    public function translate_content(){
        $m_stackoverflow = M('stackoverflow sof');

        $data = $m_stackoverflow
            ->field('sof.stack_id ,sofa.answer_id ,sof.question ,sofa.answer')
            ->join(get_table("stackoverflow_answer") ." sofa on sof.stack_id = sofa.stack_id")
            ->where([
                'sof.status' => ['in' , [StatckOverFlowStatusEnum::ParseContent , StatckOverFlowStatusEnum::NoAccept]],
                'sofa.answer_id' => ['exp', 'is not null']
            ])
            ->order('sof.stack_id asc')
            ->find();

        if(empty($data)){
            $this->error("无数据");
            exit;
        }else{
            self::_change_status($data['stack_id'] , StatckOverFlowStatusEnum::TranslateIng);
        }

        $question = $data['question'];
        $answer = $data['answer'];

        if(!empty($question)){
            $question_cn = self::_parse_translate_data($question);

            if(!empty($question_cn)){
                    //self::_change_status($data['stack_id'] , StatckOverFlowStatusEnum::Translate);
		$this->m_stackoverflow->where(['stack_id'=>$data['stack_id']])->save(['status'=>StatckOverFlowStatusEnum::Translate, 'question_cn'=>$question_cn , 'update_time'=>get_date_time()]);
            }
        }

        if(!empty($answer)){
            $answer_cn = self::_parse_translate_data($answer);

            if(!empty($answer_cn)){
                M('stackoverflow_answer')->where(['answer_id'=>$data['answer_id']])->save(['answer_cn'=>$answer_cn , 'update_time'=>get_date_time()]);
            }else{
                self::_change_status($data['stack_id'] , StatckOverFlowStatusEnum::TranslateFail);
            }
        }

        $this->success("完成",U('translate_content'),30);
    }

    public function get_data(){
        /*
        $content = 'sfss<a href="">sdfsdf</a>';
        $patten = '/<a(.*)>(.*)<\/a>/iUs';
        $a = '22222';
        $content = preg_replace_callback($patten,function($matches){
            $t = '';
            if(strpos($matches[0],'</a>')){
                $t = 'a';
            }
            echo '-'.$t.'-';
            return "<{$t}>".$matches[1]."</{$t}>";
        },$content);

        echo $content;die;
        */

        $m_stackoverflow = M('stackoverflow s');
        $data = $m_stackoverflow
            ->field('sa.answer_cn')
            ->join("__STACKOVERFLOW_ANSWER__ sa on s.stack_id = sa.stack_id")
            ->where(['s.status'=>StatckOverFlowStatusEnum::DownLoad])
            ->order("s.stack_id asc")
            ->find();

        $this->assign('answer_cn' ,$data['answer_cn']);
        $this->display();
    }

    /**
     * 功能: 更新 状态
     * @param $stack_id
     * @param $status
     */
    private function _change_status($stack_id , $status){
        $this->m_stackoverflow->where(['stack_id'=>$stack_id])->save(['status'=>$status, 'update_time'=>get_date_time()]);
    }


    /**
     * 功能: 翻译
     */
    public function translate_page(){

//        $d = $this->m_stackoverflow->where(['status'=>StatckOverFlowStatusEnum::ParseContent])->find();
//        print_r($d['question']);
//        print_r($d['question_cn']);

        $data = $this->m_stackoverflow->where(['status'=>StatckOverFlowStatusEnum::ParseContent])->find();
//        $data = $this->m_stackoverflow->where(['stack_id'=>'564'])->find();
        var_dump($data['link']);

        if(empty($data)){
            return;
        }


        $this->stack_id = $data['stack_id'];

        $title = parse_special_html($data['title']);
        $question = parse_special_html($data['question']);

        //标题
        $title_cn = GoogleTranslate::translate($title);
        var_dump($title_cn);

        //内容
        $question_cn = self::_parse_translate_data($question);
        var_dump($question_cn);

        //回答
//        $answer = $this->m_stackoverflow_answer->where(['stack_id'=>$data['stack_id']])->select();
//
//        $answer_contents = array_column($answer,'answer');
//        Log::info(var_export($answer,true));
//
//        foreach ($answer_contents as &$item){
//            $item = self::_parse_translate_data($item);
//        }
//
//        var_dump($answer);

        $this->success("完成",U('translate_page'),99999);
        exit;

        $this->m_stackoverflow
            ->where(['stack_id'=>$data['stack_id']])
            ->save([
                'title_cn'=>$title_cn,
                'question_cn'=>$question_cn,
                'update_time'=>get_date_time(),
                'status'=>StatckOverFlowStatusEnum::Success
            ]);

    }

    /**
     * 功能:
     */
    public function translate_answer(){
        $data = M('stackoverflow_answer sa')
            ->field('sa.*,s.link')
            ->join('__STACKOVERFLOW__ s on s.stack_id = sa.stack_id' )
            ->find();
        var_dump($data);

        $answer_cn = self::_parse_translate_data($data['answer']);

        var_dump($answer_cn);
        $this->success("成功",U('translate_answer'),15);
    }
    /**
     * 功能: 格式化数据并翻译
     * 开发者:ysz
     */
    private function _parse_translate_data($content){
        //替换 顺序
        $this->replace_index = 0;

        //原内容
        $this->override_data = [];

        $replace_div = [];

        //a
        $patten = "/<a(.*)>(.*)<\/a>/iUs";
        preg_match_all($patten,$content,$a_arr);
        $a_arr = $a_arr[0];

        if(!empty($a_arr)){
            $this->override_data = array_merge($this->override_data,$a_arr);
            $content = self::_preg_replace($patten,$content);
        }

        //pre code
        $content = preg_replace('/<pre(.*)>/iUs','<pre>',$content);
        $patten = "/<pre><code>(.*)<\/code><\/pre>/iUs";
        preg_match_all($patten,$content,$pre_codes);
        $pre_codes = $pre_codes[0];

        if(!empty($pre_codes)){
            $this->override_data = array_merge($this->override_data,$pre_codes);
            $content = self::_preg_replace($patten,$content);
        }



        //code
        $patten = '/<code>(.*)<\/code>/iUs';
        preg_match_all($patten,$content,$code_arr);
        $code_arr = $code_arr[0];

        if(!empty($code_arr)){
            $this->override_data = array_merge($this->override_data,$code_arr);
            $content = self::_preg_replace($patten,$content);
        }

        //需要翻译的DOM
        $patten = [
            '/<p>(.*)<\/p>/iUs',
            '/<h1>(.*)<\/h1>/iUs',
            '/<h2>(.*)<\/h2>/iUs',
        ];

        foreach ($patten as $p){
            preg_match_all($p,$content,$p_arr);
            $p_arr = $p_arr[1];

            if($p_arr){
                $content = preg_replace_callback($patten,function($matches){
                    $dom = 'p';
                    $html = strtolower($matches[0]);
                    if(strpos($html , '</h1>')){
                        $dom = 'h1';
                    }else if(strpos($html , '</h1>')){
                        $dom = 'h2';
                    }
                    $translate = GoogleTranslate::translate($matches[1]);
                    return "<{$dom}>".$translate."</{$dom}>";
                },$content);
            }
        }



        return self::_override_data($content,$this->override_data);
    }


    /**
     * 功能:
     * 开发者:ysz
     */
    private function _preg_replace($patten,$content){

        return preg_replace_callback($patten,function(){
            return '{'.($this->replace_index++).'}';
        },$content);

    }

    /**
     * 功能: 覆盖原数据
     * 开发者:ysz
     * @param $patten
     * @param $array
     */
    private function _override_data($content,$array,$patten=false){
        if(!$patten){
            $patten = '/{(\d+)}/iUs';
        }

        $this->override_data = $array;

        return preg_replace_callback($patten,function($match){
            $i = $match[1];
            Log::info('i=====>'.$i);
            return $this->override_data[$i];
        },$content);
    }





















    /**
     * 功能: 获取stack 列表链接-
     */
    public function get_stack_link_list(){
        $stack = new StackoVerFlow();
        $tag_list = $stack->get_tags_from_stack();
        foreach ($tag_list as $tag){
            $link = self::StackOverFLowTagLink.$tag."?page=1&sort=frequent&pagesize=30";
            $is_exist = M('stackoverflow_tag')->where(['link'=>$link])->find();
            if(!$is_exist){
                M('stackoverflow_tag')->add([
                    'link'          =>  $link,
                    'tag'           =>  $tag,
                    'count'         =>  0,
                    'update_time'   =>  get_date_time(),
                ]);
            }
        }
    }

    /**
     * 功能: 获取 tag 页面 链接
     */
    public function get_link_list(){
        $tag = M('stackoverflow_tag')->order('rand()')->find();
        $link = $tag['link'];

        var_dump($link);

        $collector = new ICollector();
        $content = $collector->fetch($link);
        $collector->download();

        $stack = new StackoVerFlow();

        $list = $stack->get_links($tag['link']);

        foreach ($list as &$l) {
            $l = self::StackOverFlowHome.$l;

            var_dump($l);

            $exist = M('stackoverflow')->where(['link'=>$l])->find();

            if(!$exist){
                $html = $collector->fetch($l);

                if($html){
                    $parse_data = $stack->parse_content($html);

                    
                    $stack_id = M('stackoverflow')->add([
                        'link'=>$l,
                        'status'=>StatckOverFlowStatusEnum::ParseContent,
                        'create_time'=>get_date_time(),
                        'title'=>$parse_data['title'],
                        'question'=>$parse_data['question']['body'],

                    ]);

                    if($stack_id){

                        foreach ($parse_data['question']['comment_list'] as $comment){
                            M('stackoverflow_question_comment')->add([
                                'stack_id' => $stack_id,
                                'question_comment' => $comment
                            ]);
                        }

                        $destination = $collector->download($html,$stack_id.'.html');

                        var_dump($destination);

                        M('stackoverflow')->where(['stack_id'=>$stack_id])->save(['download_file'=>$destination]);

                        //Answer
                        foreach ($parse_data['answer'] as $answer){
                            $answer_id = M('stackoverflow_answer')->add([
                                'stack_id' => $stack_id,
                                'answer' => $answer['answer_content']
                            ]);

                            var_dump('answer_id'.$answer_id);
                            if($answer_id){
                                foreach ($answer['answer_comment_list'] as $answer_comment){
                                    M('stackoverflow_answer_comment')->add([
                                        'answer_id' => $stack_id,
                                        'answer_comment' => $answer_comment
                                    ]);
                                }
                            }
                        }
                    }
                }
            }else{
                Log::info("重复链接:".$l);
            }

        }

        var_dump($list);
    }

    /**
     * 功能: 下载 并 格式化 内容
     */
    public function download_pase_stack(){


    }

    /**
     * 功能: 翻译 stack
     */
    public function translate_stack(){

    }


    /**
     * 功能: 测试
     * 作者: ysz QQ:395373668
     */
    public function test(){

        /**
         * Google翻译
         */
/*
        $google_translate  = new GoogleTranslate();
        $response_text = $google_translate->translate("Hello");
        var_dump($response_text);
*/

        /**
         *  采集器
         */
//        $collector = new ICollector();
        $url = "https://stackoverflow.com/questions/38096225/automatically-accept-all-sdk-licences";
////        $url = "http://baidu.com";
//        $response_html = $collector->fetch($url);
//        var_dump($response_html);
//        $collector->download();

        $file = __ROOT__."data/collector/170604_10_49_48.html";
//        $stack = new StackoVerFlow(file_get_contents($file),$url);

//        $data = $stack->parse_content();
//        $stack->save_to_mysql();

    }


}
