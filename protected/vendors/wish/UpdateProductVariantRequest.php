<?php
/**
 * @desc 更改产品
 *
 */
class UpdateProductVariantRequest extends WishApiAbstract {


    public function setSku($sku)
    {
        $this->request['sku'] = $sku;
        return $this;
    }

    public function setVariantData($variantData)
    {
        $this->request = array_merge($this->request, $variantData);
        return $this;
    }

    public function setEndpoint($endpoint = 'variant/update', $isPost = true)
    {
        parent::setEndpoint($endpoint, $isPost);
    }

    public function setRequest()
    {
        // TODO: Implement setRequest() method.
        return $this;
    }

}