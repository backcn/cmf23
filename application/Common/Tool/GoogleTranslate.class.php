<?php
namespace Common\Tool;
/**
 * GoogleTranslate.class.php
 *
 * Class to talk with Google Translator for free.
 *
 * @package PHP Google Translate Free;
 * @category Translation
 * @author Adrián Barrio Andrés
 * @author Paris N. Baltazar Salguero <sieg.sb@gmail.com>
 * @copyright 2016 Adrián Barrio Andrés
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License 3.0
 * @version 2.0
 * @link https://statickidz.com/
 */

/**
 * Main class GoogleTranslate
 *
 * @package GoogleTranslate
 *
 */
class GoogleTranslate
{
    /** 所有语言可选
     * @var array
     */
    public static $all_languages = [
        "sq"=>"阿尔巴尼亚语",
        "ar"=>"阿拉伯语",
        "am"=>"阿姆哈拉语",
        "az"=>"阿塞拜疆语",
        "ga"=>"爱尔兰语",
        "et"=>"爱沙尼亚语",
        "eu"=>"巴斯克语",
        "be"=>"白俄罗斯语",
        "bg"=>"保加利亚语",
        "is"=>"冰岛语",
        "pl"=>"波兰语",
        "bs"=>"波斯尼亚语",
        "fa"=>"波斯语",
        "af"=>"布尔语(南非荷兰语)",
        "da"=>"丹麦语",
        "de"=>"德语",
        "ru"=>"俄语",
        "fr"=>"法语",
        "tl"=>"菲律宾语",
        "fi"=>"芬兰语",
        "fy"=>"弗里西语",
        "km"=>"高棉语",
        "ka"=>"格鲁吉亚语",
        "gu"=>"古吉拉特语",
        "kk"=>"哈萨克语",
        "ht"=>"海地克里奥尔语",
        "ko"=>"韩语",
        "ha"=>"豪萨语",
        "nl"=>"荷兰语",
        "ky"=>"吉尔吉斯语",
        "gl"=>"加利西亚语",
        "ca"=>"加泰罗尼亚语",
        "cs"=>"捷克语",
        "kn"=>"卡纳达语",
        "co"=>"科西嘉语",
        "hr"=>"克罗地亚语",
        "ku"=>"库尔德语",
        "la"=>"拉丁语",
        "lv"=>"拉脱维亚语",
        "lo"=>"老挝语",
        "lt"=>"立陶宛语",
        "lb"=>"卢森堡语",
        "ro"=>"罗马尼亚语",
        "mg"=>"马尔加什语",
        "mt"=>"马耳他语",
        "mr"=>"马拉地语",
        "ml"=>"马拉雅拉姆语",
        "ms"=>"马来语",
        "mk"=>"马其顿语",
        "mi"=>"毛利语",
        "mn"=>"蒙古语",
        "bn"=>"孟加拉语",
        "my"=>"缅甸语",
        "hmn"=>"苗语",
        "xh"=>"南非科萨语",
        "zu"=>"南非祖鲁语",
        "ne"=>"尼泊尔语",
        "no"=>"挪威语",
        "pa"=>"旁遮普语",
        "pt"=>"葡萄牙语",
        "ps"=>"普什图语",
        "ny"=>"齐切瓦语",
        "ja"=>"日语",
        "sv"=>"瑞典语",
        "sm"=>"萨摩亚语",
        "sr"=>"塞尔维亚语",
        "st"=>"塞索托语",
        "si"=>"僧伽罗语",
        "eo"=>"世界语",
        "sk"=>"斯洛伐克语",
        "sl"=>"斯洛文尼亚语",
        "sw"=>"斯瓦希里语",
        "gd"=>"苏格兰盖尔语",
        "ceb"=>"宿务语",
        "so"=>"索马里语",
        "tg"=>"塔吉克语",
        "te"=>"泰卢固语",
        "ta"=>"泰米尔语",
        "th"=>"泰语",
        "tr"=>"土耳其语",
        "cy"=>"威尔士语",
        "ur"=>"乌尔都语",
        "uk"=>"乌克兰语",
        "uz"=>"乌兹别克语",
        "es"=>"西班牙语",
        "iw"=>"希伯来语",
        "el"=>"希腊语",
        "haw"=>"夏威夷语",
        "sd"=>"信德语",
        "hu"=>"匈牙利语",
        "sn"=>"修纳语",
        "hy"=>"亚美尼亚语",
        "ig"=>"伊博语",
        "it"=>"意大利语",
        "yi"=>"意第绪语",
        "hi"=>"印地语",
        "su"=>"印尼巽他语",
        "id"=>"印尼语",
        "jw"=>"印尼爪哇语",
        "en"=>"英语",
        "yo"=>"约鲁巴语",
        "vi"=>"越南语",
        "zh-CN"=>"中文"
    ];

    /**
     * Retrieves the translation of a text
     *
     * @param string $source
     *            Original language of the text on notation xx. For example: es, en, it, fr...
     * @param string $target
     *            Language to which you want to translate the text in format xx. For example: es, en, it, fr...
     * @param string $text
     *            Text that you want to translate
     *
     * @return string a simple string with the translation of the text in the target language
     */
    public static function translate($text,$source="en", $target = 'zh-CN' )
    {
        // Request translation
        $response = self::requestTranslation($source, $target, $text);

        // Get translation text
        // $response = self::getStringBetween("onmouseout=\"this.style.backgroundColor='#fff'\">", "</span></div>", strval($response));

        // Clean translation
        $translation = self::getSentencesFromJSON($response);

        return $translation;
    }

    /**
     * Internal function to make the request to the translator service
     *
     * @internal
     *
     * @param string $source
     *            Original language taken from the 'translate' function
     * @param string $target
     *            Target language taken from the ' translate' function
     * @param string $text
     *            Text to translate taken from the 'translate' function
     *
     * @return object[] The response of the translation service in JSON format
     */
    protected static function requestTranslation($source, $target, $text)
    {

        // Google translate URL
        $url = "https://translate.google.cn/translate_a/single?client=at&dt=t&dt=ld&dt=qca&dt=rm&dt=bd&dj=1&hl=es-ES&ie=UTF-8&oe=UTF-8&inputm=2&otf=2&iid=1dd3b944-fa62-4b55-b330-74909a99969e";

        $fields = array(
            'sl' => urlencode($source),
            'tl' => urlencode($target),
            'q' => urlencode($text)
        );

        if(strlen($fields['q'])>=5000){}
//            throw new \Exception("Maximum number of characters exceeded: 5000");
        
        // URL-ify the data for the POST
        $fields_string = "";
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }

        rtrim($fields_string, '&');

        // Open connection
        $ch = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');

        // Execute post
        $result = curl_exec($ch);

        // Close connection
        curl_close($ch);

        return $result;
    }

    /**
     * Dump of the JSON's response in an array
     *
     * @param string $json
     *            The JSON object returned by the request function
     *
     * @return string A single string with the translation
     */
    protected static function getSentencesFromJSON($json)
    {
        $sentencesArray = json_decode($json, true);
        $sentences = "";

        foreach ($sentencesArray["sentences"] as $s) {
            $sentences .= isset($s["trans"]) ? $s["trans"] : '';
        }

        return $sentences;
    }
}
