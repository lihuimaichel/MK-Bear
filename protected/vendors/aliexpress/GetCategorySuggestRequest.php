<?php
/**
 * @desc 根据关键字获取建议分类
 * @author wx
 * @since 2015-09-12
 */
class GetCategorySuggestRequest extends AliexpressApiAbstract{ 
    
    /**@var String 关键词*/
    protected $_keyword = null;
    
    
    /**
     * @desc 设置分类id
     * @param string $keyword
     */
    public function setKeyword($keyword){
        $this->_keyword = $keyword;
    }
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.recommendCategoryByKeyword';
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
               'keyword' => $this->_keyword
        );
       
        $this->request = $request;
        return $this;
    }
}