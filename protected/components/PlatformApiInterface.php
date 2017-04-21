<?php
/**
 * @desc Platform API Interface
 * @author Gordon
 * @since 2015-06-02
 */
interface PlatformApiInterface  {
    
    /**
     * @desc 设置请求参数
     */
    public function setRequest();

    /**
     * @desc 获取请求
     */
    public function getRequest();

    /**
     * @desc 发送请求
     */
    public function sendRequest();
     
    /**
     * @desc 获取响应信息
     */
    public function getResponse();
    
}

