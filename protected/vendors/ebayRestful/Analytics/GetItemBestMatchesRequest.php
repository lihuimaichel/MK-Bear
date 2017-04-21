<?php 
/**
* @desc Item Best Matches转化率分析
*
* ItemBestMatches服务支持以下参数组合：
  Seller ID (从Token中获取)+ dateRange 
  Seller ID + siteIds + dateRange 
  Seller ID + siteIds + Category L1,L2,L3,Leaf + dateRange 
  Seller ID + listingIds + dateRange
请注意：在Category L1,L2,L3或Leaf有多个存在的情况下，我们只取最后一级。比如参数中有L1和L2，则我们只取L2；参数中有L2和L3，则我们只取L3
* 
* @author yangsh
* @since 2017-03-15
*/
class Analytics_GetItemBestMatchesRequest extends EbayRestfulApiAbstract {
	
    /**@var string YYYYMMDD..YYYYMMDD*/
    protected $_dateRange = null;

    /**@var int 类目1 id*/
    protected $_categoryL1 = null;

    /**@var int 类目2 id*/
    protected $_categoryL2 = null;

    /**@var int 类目3 id*/
    protected $_categoryL3 = null;

    /**@var int 类目叶子级id*/
    protected $_categoryLeaf = null;    

    /**@var string 0|3|71*/
    protected $_siteIds = null;

    /**@var string 123231|123432432|2343243*/
    protected $_listingIds = null;

    /**@var int 分页的第几页，默认是1*/
    public $_pageNum = 1;

    /**@var int 分页的每页大小，默认是25，最大值是500，最小值是1*/
    public $_pageSize = 25;

    /**@var int 总页数**/
    public $_pageCount = 1;

    /**
     * @desc 初始化对象
     */
    public function __construct() {
        parent::__construct();
        $this->_urlObject = new EbayRestfulUrl($this->ebayRestfulKeys['gccbtapiUrl'],'','gccbtapi','v1','item_best_matches','');
        $this->_requestObject = new HttpRequestImpl(null,null,HttpRequestImpl::HTTP_GET,1800);
    }

    /**
     * Getter for dateRange
     * @return
     */
    public function getDateRange(){
        return $this->_dateRange;
    }
    
    /**
     * Setter for dateRange
     * @param string dateRange
     * @return self
     */
    public function setDateRange($dateRange){
        $this->_dateRange = $dateRange;
        return $this;
    }
    
    /**
     * Getter for categoryL1
     * @return int
     */
    public function getCategoryL1(){
        return $this->_categoryL1;
    }
    
    /**
     * Setter for categoryL1
     * @param int categoryL1
     * @return self
     */
    public function setCategoryL1($categoryL1){
        $this->_categoryL1 = $categoryL1;
        return $this;
    }

    /**
     * Getter for categoryL2
     * @return int
     */
    public function getCategoryL2(){
        return $this->_categoryL2;
    }
    
    /**
     * Setter for categoryL2
     * @param int categoryL2
     * @return self
     */
    public function setCategoryL2($categoryL2){
        $this->_categoryL2 = $categoryL2;
        return $this;
    }
    
    /**
     * Getter for categoryL3
     * @return int
     */
    public function getCategoryL3(){
        return $this->_categoryL3;
    }
    
    /**
     * Setter for categoryL3
     * @param int categoryL3
     * @return self
     */
    public function setCategoryL3($categoryL3){
        $this->_categoryL3 = $categoryL3;
        return $this;
    }

    /**
     * Getter for categoryLeaf
     * @return int
     */
    public function getCategoryLeaf(){
        return $this->_categoryLeaf;
    }
    
    /**
     * Setter for categoryLeaf
     * @param int $categoryLeaf
     * @return self
     */
    public function setCategoryLeaf($categoryLeaf){
        $this->_categoryLeaf = $categoryLeaf;
        return $this;
    }

    /**
     * Getter for siteIds
     * @return string
     */
    public function getSiteIds(){
        return $this->_siteIds;
    }
    
    /**
     * Setter for siteIds
     * @param array $siteIds
     * @return self
     */
    public function setSiteIds($siteIds){
        $this->_siteIds = implode('|',$siteIds);
        return $this;
    }
    
    /**
     * Getter for listingIds
     * @return string
     */
    public function getListingIds(){
        return $this->_listingIds;
    }
    
    /**
     * Setter for listingIds
     * @param array $listingIds
     * @return self
     */
    public function setListingIds($listingIds){
        $this->_listingIds = implode('|',$listingIds);
        return $this;
    }
    
    /**
     * @desc 设置请求参数
     */
    public function setRequest() {
        $request = array();
        if ($this->_dateRange) {
            $request['dateRange'] = $this->_dateRange;
        }
        if ($this->_categoryL1) {
            $request['categoryL1'] = $this->_categoryL1;
        }
        if ($this->_categoryL2) {
            $request['categoryL2'] = $this->_categoryL2;
        }
        if ($this->_categoryL3) {
            $request['categoryL3'] = $this->_categoryL3;
        }
        if ($this->_categoryLeaf) {
            $request['categoryLeaf'] = $this->_categoryLeaf;
        }
        if ($this->_siteIds) {
            $request['siteIds'] = $this->_siteIds;
        }                                
        if ($this->_listingIds) {
            $request['listingIds'] = $this->_listingIds;
        }
        if ($this->_pageNum) {
            $request['pageNum'] = $this->_pageNum;
        }  
        if ($this->_pageSize) {
            $request['pageSize'] = $this->_pageSize;
        }                
        $this->_requestObject->requestData = $request;
        return $this;
    }   

    /**
     * @desc 设置http头信息
     */
    public function setHeaders(){
        parent::setHeaders();
        $headers = array(
            'Content-Type'   => 'application/x-www-form-urlencoded',
        ) + $this->_requestObject->requestHeaders;
        $this->_requestObject->requestHeaders = $headers;
    }   

    /**
     * @desc 判断交互是否成功
     */
    public function getIfSuccess(){
        return $this->_responseObject->statusCode != 200
             || isset($this->_responseObject->responseData->error_code)
             || isset($this->_responseObject->responseData->errorMessage)
              ? false : true;
    }

    /**
     * @desc 获取响应编码
     * @return string
     */
    public function getErrorCode(){
        return $this->_errorCode = isset($this->_responseObject->responseData->error_code) ? $this->_responseObject->responseData->error_code : '';
    }
    
    /**
     * @desc 获取失败信息
     * @return string 
     */
    public function getErrorMsg(){
        //curl error
        $this->_errorMsg = empty($this->_responseObject->error) ? '' : trim($this->_responseObject->error);
        //response error message
        if(isset($this->_responseObject->responseData->errorMessage)
             && !empty($this->_responseObject->responseData->errorMessage->error)) {
            foreach ($this->_responseObject->responseData->errorMessage->error as $value) {
                if(isset($value->longMessage)) {
                    $this->_errorMsg .= $value->longMessage.' ';
                } else if(isset($value->message)) {
                    $this->_errorMsg .= $value->message.' ';
                }
            }
        }        
        return $this->_errorMsg;
    }      

}

