<?php
/**
 * @desc xml刊登分类模版
 * @author qzz
 * @since 2016-12-27
 */
class ProductTypeTemplatesRequest extends PriceministerApiAbstract{
    public $_action = "producttypetemplate";
	public $_version = "2015-02-02";
	public $_urlPath = "stock_ws";

    public $_alias = null;
    public $_scope = null;
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array();
        if($this->_alias){
            $request['alias'] = $this->_alias;
        }
        if($this->_scope){
            $request['scope'] = $this->_scope;
        }
        $this->request = $request;
        return $this;
    }

    public function setAlias($alias){
        $this->_alias = $alias;
        return $this;
    }
    public function setScope($scope){
        $this->_scope = $scope;
        return $this;
    }
}