<?php
/**
 * @desc 获取分类属性
 * @author wx
 * @since 2015-09-12
 */
class GetCategoryAttributeRequest extends AliexpressApiAbstract{ 
    
    /**@var integer 分类ID*/
    protected $_cateId = null;
    
    /**@var json string 类目子属性路径*/
    protected $_parentAttrValueList = null;
    
    /**
     * @desc 设置分类id
     * @param integer $cateId
     */
    public function setCateId($cateId){
        $this->_cateId = $cateId;
    }
    
    /**
     * @desc 设置分类id
     * @param json string $parentAttrValueList
     */
    public function setParentAttrValueList( $parentAttrValueList ){
    	$this->_parentAttrValueList = $parentAttrValueList;
    }
    
    public function setApiMethod(){
        $this->_apiMethod = 'getChildAttributesResultByPostCateIdAndPath';
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
               'cateId' => $this->_cateId
        );
        
        if( $this->_parentAttrValueList ){
        	$request['parentAttrValueList'] = $this->_parentAttrValueList;
        }
       
        $this->request = $request;
        return $this;
    }
}