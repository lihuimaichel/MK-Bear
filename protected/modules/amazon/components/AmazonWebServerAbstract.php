<?php
abstract class AmazonWebServerAbstract extends AmazonApiAbstract {
	/**
	 * @desc 设置请求服务对象实例
	 */
	protected function setServiceEntities() {
		$config = array (
				'ServiceURL' => $this->_serviceUrl,
				'ProxyHost' => $this->_proxyHost,
				'ProxyPort' => $this->_proxyPort,
				'ProxyUsername' => $this->_proxyUserName,
				'ProxyPassword' => $this->_proxyPassword,
				'MaxErrorRetry' => 3,
		);
		$service = new MarketplaceWebService_Client(
				$this->_accessKeyID,
				$this->_secretAccessKey,
				$config,
				$this->_appName,
				$this->_appVersion
		);
		$this->_serviceEntities = $service;
	}	
}