<?php
/**
 * 功能: StatckOverFlow 采集状态
 * 作者: ysz QQ:395373668
 * 时间: 2017/06/05 23:38
 * 文件: StatckOverFlowStatusEnum.class.php
 * 工具: PhpStorm
 */
namespace Caiji\Enum;
class StatckOverFlowStatusEnum {
    const GetLink = 10 ;            //采集链接入库
    const DownLoad = 20 ;           //准备下载页面内容
    const DownLoadIng = 25;         //正在下载页面内容
    const DownLoadFail = 30;        //下载失败
    const ParseContent = 40 ;       //分析页面结构
    const ParseContentFail = 50 ;   //分析页面结构失败
    const NoAccept = 60 ;           //无最佳答案
    const Translate = 70 ;          //准备翻译内容
    const TranslateIng = 80 ;       //正在翻译内容
    const TranslateFail = 90 ;      //翻译失败
    const Success = 100 ;           //采集成功


    public static $stack_over_flow_status = [
        self::GetLink           => "采集链接入库",
        self::DownLoad          => "准备下载页面内容",
        self::DownLoadIng       => "正在下载页面内容",
        self::DownLoadFail      => "下载页面失败",
        self::ParseContent      => "准备分析页面结构",
        self::ParseContentFail  => "页面分析失败",
        self::Translate         => "准备翻译内容",
        self::TranslateIng      => "正在翻译内容",
        self::TranslateFail     => "翻译失败",
        self::Success           => "采集成功",
    ];
}