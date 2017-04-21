<?php
/**
 * @desc 根据path查询图片信息
 * @author hanxy
 * @since 2016-12-22
 */
class QueryPhotoBankImageByPathsRequest extends AliexpressApiAbstract{ 
    
	/**@var array 设置图片名称*/
	public $_paths	= ''; 
	
    public function setApiMethod(){
        $this->_apiMethod = 'api.queryPhotoBankImageByPaths';
    }
   
    public function setRequest(){
        $request = array(
                'paths' => '["'.$this->_paths.'"]',
        );
        $this->request = $request;
        return $this;
    }
    
    /**
     * @desc 设置图片名称
     * @param array $pathsArr
     */
    public function setPaths($pathsArr){
    	$this->_paths = implode('","', $pathsArr);
    }
    
}