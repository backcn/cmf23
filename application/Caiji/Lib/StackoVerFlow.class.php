<?php
/**
 * 功能: StackoVerFlow 页面分析
 * 作者: ysz QQ:395373668
 * 时间: 2017/06/04 11:42
 * 文件: StackoVerFlow.class.php
 * 工具: PhpStorm
 */
namespace Caiji\Lib;

use Caiji\Enum\StatckOverFlowStatusEnum;
use Common\Tool\ICollector;
use Think\Log;

class StackoVerFlow{

    public $url = false;
    public $content = false;

    public $web_data = false;

    public $title = false;

    public $question = false;

    public $answer_list = false;

    public $stack_id = false;

    /**
     * StackoVerFlow constructor.
     */
    public function __construct($content=false,$url=false)
    {
        if(!$content){
            $this->content = $content;
            $this->url = $url;

            if($url){
                self::_init();
            }
        }


    }

    public function get_content($url = null)
    {
        $this->url = empty($url) ? $this->url : $url;
        $collctor = new ICollector();
        $this->content = $collctor->fetch($this->url);
        return $this->content;
    }

    /***
     * 功能: 初始化
     */
    private function _init(){
        $this->stack_id = M('stackoverflow')->where(['link'=>$this->url])->find();
    }

    /**
     * 功能: 获取标题
     */
    public function pase_title(){
        $title = self::_preg_match('itemprop="title name" content="(.*)"' , 1);

        if($title){
            $this->web_data['title'] = $title;
        }else{
            Log::error($this->url."标题获取失败");
        }
    }

    /**
     * 功能: 获取问题头
     */
    public function pase_question(){
        $patten = '<div class="question"(.*)<div id="answers';
        $question_body = self::_preg_match($patten,1);

        $patten = '<div class="post-text" itemprop="text">(.*)<\/div>(.*)<div class="post-taglist">';
        $this->web_data['question']['body'] = self::_preg_match($patten,1,$question_body);

        $patten = '<span class="comment-copy">(.*)<\/span';
        $this->web_data['question']['comment_list'] = self::_preg_match_all($patten,1,$question_body);

        Log::info(var_export($this->web_data,true));
    }

    /**
     * 功能: 获取回答列表
     */
    public function pase_answer_list(){
        $patten = 'class="answer"(.*)<a name="';
        $td_list = self::_preg_match_all($patten,1);

        $answer = [];
        foreach ($td_list as $td){
            //回答主体
            $patten = '<div class="post-text" itemprop="text">(.*)<\/div';
            $answer_content = self::_preg_match($patten,1,$td);

            $patten = '<span class="comment-copy">(.*)<\/span>';
            $answer_comments_list = self::_preg_match_all($patten,1,$td);

            $answer[] = [
                'answer_content'        => $answer_content,
                'answer_comment_list'   => $answer_comments_list
            ];
        }

        $this->web_data['answer'] = $answer;

        Log::info(var_export($this->web_data,true));
    }


    /**
     * 功能: 单一正则匹配
     * @param $pattern
     * @param int $index
     * @return bool
     */
    private function _preg_match($pattern,$index=0 , $content = false){
        if(!$content) $content = $this->content;

        if(preg_match('/'.$pattern.'/iUs',$content,$matches)){
            return $matches[$index];
        }else{
            return false;
        }
    }

    /**
     * 功能: 循环正则匹配
     * @param $pattern
     */
    private function _preg_match_all($pattern,$index = false,$content = false){
        if(!$content) $content = $this->content;

        preg_match_all('/'.$pattern.'/iUs',$content,$matches);

        if(!$index){
            return $matches;
        }

        return $matches[$index];

    }

    /**
     * 功能: 检查内容完整
     */
    private function _check_content(){
        if($this->content) return true;
        return false;
    }

    /**
     * 功能: 返回全部结构
     * @return bool
     */
    public function get_web_data(){
        return $this->web_data;
    }

    /**
     * 功能: 执行 格式化页面内容
     */
    public function parse_content($content = false){
        if($content) $this->content = $content;
        $this->pase_title();
        $this->pase_question();
        $this->pase_answer_list();

        return $this->web_data;
    }

    /** TODO
     * 功能: 内容保存到数据库
     */
    public function save_to_mysql(){
        if(!$this->stack_id) return;

        $m_stackoverflow = M('stackoverflow');
        $m_stackoverflow->startTrans() ;

        $stack_id = $m_stackoverflow
            ->where(['stack_id'=>$this->stack_id])
            ->svae([
            'status'=> StatckOverFlowStatusEnum::ParseContent,
            'update_time'=> get_date_time(),
            'title' => $this->get_title(),
            'question' => $this->get_question(),
        ]);

        if($stack_id === false){
            $m_stackoverflow->rollback();
        }


        M('stackoverflow_answer')->add([
            'answer'=>get_date_time(),
            'stack_id' =>1
        ]);
        $m_stackoverflow->commit();

        echo "aa";
    }


    /**
     * 功能: 返回标题
     * @return mixed
     */
    public function get_title(){
        return $this->web_data['title'];
    }

    /**
     * 功能: 返回 问题内容
     * @return mixed
     */
    public function get_question(){
        return $this->web_data['question']['body'];
    }

    /**
     * 功能: 返回 问题 补充
     * @return mixed
     */
    public function get_question_comment(){
        return $this->web_data['question']['comment_list'];
    }

    /**
     * 功能: 返回答案
     * @return mixed
     */
    public function get_answer(){
        return $this->web_data['answer'];
    }

    /**
     * 功能: 获取Tag列表
     * 开发者:ysz
     */
    public function get_tags_from_stack($page = false){

        if(!$page)$page =1 ;

        $url = "https://stackoverflow.com/tags?page={$page}&tab=popular";

        $collector = new ICollector();

        $html = $collector->fetch($url);

        $patten = 'class="post-tag"(.*)rel="tag">(.*)<\/a>';
        $tag_list = self::_preg_match_all($patten,false,$html);

        foreach ($tag_list[2] as &$item) {
            $item = strip_tags($item);
        }

        return $tag_list[2];
    }

    /**
     * 功能: 获取
     * @param $tag_page
     */
    public function get_links($content){

        
        $patten = '<div class="summary">(.*)<h3><a href="(.*)" class="question-hyperlink"';
        $list = self::_preg_match_all($patten,false,$content);


        return $list[2];
    }

    /**
     * 功能: 自增tag采集到第几页
     * 开发者:ysz
     * @param $tag_id
     */
    public function set_tag_index_add($tag_id){
        $m_stackoverflow_tag = M('stackoverflow_tag');
        $m_stackoverflow_tag->where(['id'=>$tag_id])->save(['update_time'=>get_date_time(),'page_index'=>['exp','page_index+1']]);
    }

    /**
     * 功能: 自减tag采集到第几页
     * 开发者:ysz
     * @param $tag_id
     */
    public function set_tag_index_dec($tag_id){
        M('stackoverflow_tag')->where(['id'=>$tag_id])->save(['update_time'=>get_date_time(),'page_index'=>['exp','page_index-1']]);
    }
}