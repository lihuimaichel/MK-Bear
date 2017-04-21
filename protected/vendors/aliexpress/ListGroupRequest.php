<?php
/**
 * @desc 获取图片分组信息
 * @author liuj
 * @since 2016-05-25
 */
class ListGroupRequest extends AliexpressApiAbstract{ 

    
    /** @var string 图片组ID **/
    protected $_groupId = null;
    
    
    /**
     * @desc 设置图片组id
     * @param integer $groupId
     */
    public function setgroupId($groupId){
        $this->_groupId = $groupId;
    }
    
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.listGroup';
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array();
        if (!is_null($this->_groupId))
                $request['groupId'] = $this->_groupId;
        $this->request = $request;
        return $this;
    }
}