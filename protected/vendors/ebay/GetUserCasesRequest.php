<?php
/**
 * @desc 获取用户提交的case
 * @author lihy
 *
 */
class GetUserCasesRequest extends EbayResolutionCaseApiAbstract{
	/**@var string 每次请求订单个数*/
	public $_EntriesPerPage = 200;
	
	/**@var string 请求的页数*/
	public $_PageNumber = 1;
	
	public $_verb = 'getUserCases';
	
	
	
	
	public function setRequest(){
		$request = array(
				'paginationInput'=>array(
									'entriesPerPage'=>$this->_EntriesPerPage,
									'pageNumber'=>$this->_PageNumber
								)
		);
		
		$this->request = $request;
		return $this;
	}
}