<?php
/**
 * @desc 上传图片到ebay
 * @author Gordon
 * @since 2015-06-02
 */
class UploadSiteHostedPicturesRequest extends EbayApiAbstract{
    public $_verb = "UploadSiteHostedPictures";
    private $_picPath;
    private $_imgName;
    private $_firstPart = "";
    private $_secondPart = "";
    public function setRequest(){
		if(file_exists($this->_picPath)){
	    	$picData = file_get_contents($this->_picPath);
	    	$boundary = "MIME_boundary";
	    	$CRLF = "\r\n";
	    	$firstPart   = "--" . $boundary . $CRLF;
	    	$firstPart  .= 'Content-Disposition: form-data; name="XML Payload"' . $CRLF;
	    	$firstPart  .= 'Content-Type: text/xml;charset=utf-8' . $CRLF . $CRLF;
	    	
	    	$secondPart = $CRLF."--" . $boundary . $CRLF;
	    	$secondPart .= 'Content-Disposition: form-data; name="dummy"; filename="dummy"' . $CRLF;
	    	$secondPart .= "Content-Transfer-Encoding: binary" . $CRLF;
	    	$secondPart .= "Content-Type: application/octet-stream" . $CRLF . $CRLF;
	    	$secondPart .= $picData;
	    	$secondPart .= $CRLF;
	    	$secondPart .= "--" . $boundary . "--" . $CRLF;
	    	$this->_firstPart = $firstPart;
	    	$this->_secondPart = $secondPart;
	    	$requestArr = array(
	    			'RequesterCredentials' => array(
	    					'eBayAuthToken' => $this->getToken()
	    			),
	    			'PictureName' => $this->_imgName,
	    	);
	    	
	    	$this->request = $requestArr;
	    	$this->_boundary = $boundary;
		}else{
			$this->request = array();
		}
    	return $this;
    }
    
    public function setPicPath($picPath){
    	$this->_picPath = $picPath;
    	return $this;
    }
    
    public function setImgName($imgName){
    	$this->_imgName = $imgName;
    	return $this;
    }
    
    /**
     * @desc 获取上传到eBay图片地址
     * @return boolean
     */
    public function getEbayHostPic(){
    	if($this->getIfSuccess()){
    		return $this->getResponse()->SiteHostedPictureDetails->FullURL;
    	}
    	return false;
    }
    
    /**
     * @desc 将请求参数转化为Xml
     */
    public function getRequestXmlBody(){
    	$xmlGeneration = new XmlGenerator();
    	$xmlbody = $xmlGeneration->XmlWriter()->push($this->getXmlRequestHeader(), array('xmlns' => $this->_xmlsn))
					    	->buildXMLFilterMulti($this->getRequest())
					    	->pop()
					    	->getXml();
    	$xmlbody = $this->_firstPart.$xmlbody.$this->_secondPart;
    	return $xmlbody;
    }
}