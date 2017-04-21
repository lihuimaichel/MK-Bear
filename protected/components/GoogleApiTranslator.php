<?php
/**
 * google translation data class
 *
 * @author Bob <zunfengke@gmail.com>
 */
final class GoogleApiTranslator {
   
    /**
     * request base url
     * 
     * @var string  
     */
    //public $url = "http://translate.google.com/translate_t";
    public $url = "http://translate.google.cn/";
    
    /**
     * translate content
     * 
     * @var string 
     */
    public $text = "";
    
    /**
     * response content 
     * 
     * @var string 
     */
    public $out = "";
    
    /**
     * source language
     * 
     * @var string 
     */
    public $lang_src = "";
    
    /**
     * target language 
     * 
     * @var string 
     */
    public $lang_des = "";

    /**
     * set text
     * 
     * @param string $text
     */
    public function setText($text) {
        $this->text = urlencode(stripslashes($text));
    }

    /**
     * set language
     * 
     * @param string $lang_src
     * @param string  $lang_des
     */
    public function setLang($lang_src, $lang_des) {
        $this->lang_src = $lang_src;
        $this->lang_des = $lang_des;
    }

    /**
     * translate content
     * 
     * @return string 
     */
    public function translate() {
        $this->out = "";
        $response = $this->execute();
        preg_match('/\s+id="?result_box"?\s+[^>]*>(.+<\/span>)(?=<\/div><\/div>)/', $response, $match);
        $result = strip_tags($match[1], '<br>');
        $result = str_replace('<br>', '\r', $result);
        $result = iconv("GBK","utf-8",$result);
        //$result = mb_convert_encoding($result, "UTF-8", "GBK");
        $this->out = $result;
        return $this->out;
    }

    /**
     * execute request url  
     * @return string $response
     */
    public function execute() {
        $response = '';       
        if ($this->url != "" && $this->text != "" && $this->lang_src != "" && $this->lang_des != "") {
            $ch = curl_init($this->url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            $post = array(
                'hl=zh-CN', 
                'langpair=' . $this->lang_src . '|' . $this->lang_des,
                'ie=UTF-8',
                'text='.$this->text,
                //'text='.urlencode(mb_convert_encoding($this->text, 'UTF-8', 'GB2312'))
            );
            
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $post));

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $response = "";
            }               
            curl_close($ch);
        }

        return $response;
    }   
}
