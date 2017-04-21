<?php
class Http_MultiRequest
{
    //Ҫ����ץȡ��url �б�
    private $urls = array();

    //curl ��ѡ��
    private $options;
    
    //���캯��
    function __construct($options = array())
    {
        $this->setOptions($options);
    }

    //����url �б�
    function setUrls($urls)
    {
        $this->urls = $urls;
        return $this;
    }


    //����ѡ��
    function setOptions($options)
    {
        $options[CURLOPT_RETURNTRANSFER] = 1;
        if (isset($options['HTTP_POST'])) 
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['HTTP_POST']);
            unset($options['HTTP_POST']);
        }

        if (!isset($options[CURLOPT_USERAGENT])) 
        {
            $options[CURLOPT_USERAGENT] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1;)';
        }

        if (!isset($options[CURLOPT_FOLLOWLOCATION])) 
        {
            $options[CURLOPT_FOLLOWLOCATION] = 1;
        }

        if (!isset($options[CURLOPT_HEADER]))
        {
            $options[CURLOPT_HEADER] = 0;
        }
        $this->options = $options;
    }

    //����ץȡ���е�����
    function exec()
    {
        if(empty($this->urls) || !is_array($this->urls))
        {
            return false;
        }
        $curl = $data = array();
        $mh = curl_multi_init();
        foreach($this->urls as $k => $v)
        {
            $curl[$k] = $this->addHandle($mh, $v);
        }
        $this->execMulitHandle($mh);
        foreach($this->urls as $k => $v)
        {
            $data[$k] = curl_multi_getcontent($curl[$k]);
            curl_multi_remove_handle($mh, $curl[$k]);
        }
        curl_multi_close($mh);
        return $data;
    }
    
    //ֻץȡһ����ҳ�����ݡ�
    function execOne($url)
    {
        if (empty($url)) {
            return false;
        }
        $ch = curl_init($url);
        $this->setOneOption($ch);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }
    
    //�ڲ���������ĳ��handle ��ѡ��
    private function setOneOption($ch)
    {
        curl_setopt_array($ch, $this->options);
    }

    //���һ���µĲ���ץȡ handle
    private function addHandle($mh, $url)
    {
        $ch = curl_init($url);
        //不直接显示
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        $this->setOneOption($ch);
        curl_multi_add_handle($mh, $ch);
        return $ch;
    }

    //����ִ��(�����д����һ������Ĵ��������ﻹ�ǲ��������д�������д��
    //����һ��С�ļ������ܵ���cupռ��100%, ���ң����ѭ��������10�������
    //����һ�����͵Ĳ���ԭ�����Ĵ������������PHP�ٷ����ĵ��϶��൱�ĳ���
    private function execMulitHandle($mh)
    {
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);
    }
}
?>