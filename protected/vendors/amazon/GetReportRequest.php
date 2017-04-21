<?php
class GetReportRequest extends AmazonWebServerAbstract {
	protected $_urlPath              = '';					//请求url path 部分
	protected $_reportId             = '';					//报告ID
	protected $_reportDataReturnType = null;				//接口数据返回类型（默认为空：制表符分隔的、文本文件格式，存在XML格式） Liz|2016/3/18
	
	const MAX_REQUEST_TIMES = 15;	//接口最大请求次数
	const RESUME_RATE_INTERVAL = 60;		//请求恢复间隔 60秒
	const RESUME_RATE_NUM = 1;				//请求恢复每次1个
	
	public function setRequestEntities() {
		$request = new MarketplaceWebService_Model_GetReportRequest();
		$request->setMerchant($this->_merchantID);
		if(isset($this->_marketPlaceID) && !empty($this->_marketPlaceID)) $request->setMarketplace($this->_marketPlaceID);
		$request->setReportId($this->_reportId);
		$request->setReport(@fopen('php://memory', 'rw+'));
		$this->_requestEntities = $request;
	}
	
	public function call() {
		$return = array();
		try {
			if (!$this->requestAble())
				sleep(self::RESUME_RATE_INTERVAL);
			$response = $this->_serviceEntities->getReport($this->_requestEntities);
			$this->_remainTimes--;	//统计接口调用次数
			$fp = $this->_requestEntities->getReport();
			/* file_put_contents("D:/2015code/test_merchant" . $this->_accountID . ".txt", stream_get_contents($fp));
			exit(); */
			$key = 0;

			//接口数据返回格式区分解析	Liz|2016/3/18
			if ($this->_reportDataReturnType == 'xml'){
				$data = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
				while (($line = fgets($fp))) {
					$data .= $line;
				}

/*				$p = xml_parser_create();
				xml_parse_into_struct($p, $data, $vals, $index);
				xml_parser_free($p);
				print_r($vals);
				exit;*/
								
				$data = simplexml_load_string($data);
				//用以下方式可转化为可读性强的数组（所有子项都为数组类型）
				// $json  = json_encode($ob);
				// $configData = json_decode($json, true);	
				$this->response = $data;
			}else{
				while (($line = fgets($fp))) { 
					$rows = explode("\t", $line);
					if ($key == 0) {
						foreach ($rows as $k => $value) {
							$value = trim($value);
							${'index_' . $k} = $value;
						}
					} else {
						foreach ($rows as $k => $value) {
							$value = trim($value);
							$return[$key][${'index_' . $k}] = $value;
						}
					}
					$key++;
				}
				$this->response = $return;
			}

			return true;
		} catch (Exception $e) {
			$this->_errorMessage = $e->getMessage();
			return false;
		}
	}
	
	/**
	 * @desc 设置报告ID
	 * @param unknown $id
	 */
	public function setReportId($id) {
		$this->_reportId = $id;
	}

	/**
	 * @desc 设置数据返回类型
	 * @param string $reportType
	 */
	public function setReturnDataType($reportType) {
		if ($reportType){
			if (in_array($reportType, array(RequestReportRequest::REPORT_TYPE_BROWSE_TREE_DATA)))
				$this->_reportDataReturnType = 'xml';
		}
	}

}