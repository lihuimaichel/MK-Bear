<?php
/**
 * @desc Relist下架恢复
 * @author qzz
 * @since 2016-12-21
 */
class RelistItemRequest extends EbayApiAbstract{

    public $_verb = 'RelistItem';
    private $_itemInput = array();

    public function setRequest(){
        $request = array(
            'RequesterCredentials' => array(
                'eBayAuthToken' => $this->getToken(),
            ),
        );
        $request['Item'] = $this->_itemInput;
        $this->request = $request;
        return $this;
    }

    public function setItemID($itemID){
        $this->_itemInput['ItemID'] = $itemID;
        return $this;
    }

    public function setSite($site){
        $this->_itemInput['Site'] = $site;
        return $this;
    }
}