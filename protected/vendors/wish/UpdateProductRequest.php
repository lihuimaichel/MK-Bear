<?php
/**
 * @desc 更改产品
 *
 */
class UpdateProductRequest extends WishApiAbstract {


    public function setItemId($itemId)
    {
        $this->request['id'] = $itemId;
        return $this;
    }

    public function setProductData($productData)
    {
        $this->request = array_merge($this->request, $productData);

        return $this;
    }

    public function setEndpoint($endpoint = 'product/update', $isPost = true)
    {
        parent::setEndpoint($endpoint, $isPost);
    }

    public function setRequest()
    {
        // TODO: Implement setRequest() method.
        return $this;
    }

}