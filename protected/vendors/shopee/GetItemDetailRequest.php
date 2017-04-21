<?php

/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/8
 * Time: 15:04
 */
class GetItemDetailRequest extends ShopeeApiAbstract
{

    public function setItemId($itemId)
    {
        $this->request['item_id'] = (int)$itemId;
        return $this;
    }

    public function setRequest()
    {
        $this->setEndpoint('item/get', $isPost = true);
        return $this;
    }

}