<?php

/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/8
 * Time: 15:07
 */
class JoomUpdateProductRequest extends JoomApiAbstract
{

    public function setListingId($listingId)
    {
        $this->request['id'] = $listingId;
        return $this;
    }

    public function setListingData($listingData)
    {
        $this->request = array_merge($this->request, $listingData);
        return $this;
    }

    public function setEndpoint($endpoint  ='product/update', $isPost = true)
    {
        return parent::setEndpoint($endpoint, $isPost);
    }

    public function setRequest()
    {
        return $this;
    }
}