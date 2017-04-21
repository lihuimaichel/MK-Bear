<?php
/**
 * @desc 图片银行列表分页查询
 * @author liuj
 * @since 2016-05-25
 */
class ListImagePaginationRequest extends AliexpressApiAbstract{ 

    
    /** @var string 图片组ID **/
    protected $_groupId = null;
    protected $_locationType = null;

    /** @var int 图片总数 **/
    public $_totalItem = 1;

    /** @var int 页数 **/
    public $_page = 1;

    /** @var int 每页条数 **/
    public $_pageSize = 100;
    
    /**
     * @desc 设置图片组id
     * @param integer $groupId
     */
    public function setgroupId($groupId){
        $this->_groupId = $groupId;
    }
    
    /**
     * @desc 设置组的类型
     * @param integer $groupId
     */
    public function setlocationType($locationType){
        $this->_locationType = $locationType;
    }
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.listImagePagination';
    }
    
    /**
     * @desc 设置page size
     * @param unknown $size
     */
    public function setPageSize($size) {
           $this->_pageSize = $size;
    }
    
    /**
     * @desc 设置page
     * @param unknown $page
     */
    public function setPage($page) {
           $this->_page = $page;
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        
        $request = array();
        $request['pageSize'] = $this->_pageSize;
        $request['currentPage'] = $this->_page;
        if (!is_null($this->_groupId)){
                $request['groupId'] = $this->_groupId;
                //$request['locationType'] = 'unGroup';
                $request['locationType'] = 'subGroup';
        } else {
                $request['locationType'] = 'allGroup';
        }
        $this->request = $request;
        return $this;
    }
}