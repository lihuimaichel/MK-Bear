<?php

/**
 * @desc 获取Xml请求
 * @author Gordon
 * @since 2015-06-02
 */
class XmlGenerator {

    public $xml;
    
    public $indent;
    
    public $stack = array();
    
    /**
     * @desc xml头信息
     * @param string $indent
     */
    public function XmlWriter($indent = '  ') {
        $this->indent = $indent;
        ob_clean();
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        return $this;
    }

    /**
     * @desc 添加必要元素信息
     * @param string $element
     * @param array $attributes
     */
    public function push($element, $attributes = array()) {
        $this->_indent();
        $this->xml .= '<' . $element;
        foreach ($attributes as $key => $value) {
            $this->xml .= ' ' . $key . '="' . htmlentities($value) . '"';
        }
        $this->xml .= ">\n";
        $this->stack[] = $element;
        return $this;
    }

    /**
     * @desc 属性
     * @param string $element
     * @param string $content
     * @param array $attributes
     */
    public function element($element, $content, $attributes = array()) {
        $this->_indent();
        $this->xml .= '<' . $element;
        foreach ($attributes as $key => $value) {
            $this->xml .= ' ' . $key . '="' . htmlentities($value) . '"';
        }
        $this->xml .= '>' . htmlentities($content) . '</' . $element . '>' . "\n";
        
        return $this;
    }

    public function emptyelement($element, $attributes = array()) {
        $this->_indent();
        $this->xml .= '<' . $element;
        foreach ($attributes as $key => $value) {
            $this->xml .= ' ' . $key . '="' . htmlentities($value) . '"';
        }
        $this->xml .= " />\n";
        
        return $this;
    }

    public function pop() {
        $element = array_pop($this->stack);
        $this->_indent();
        if( $element ){
            $this->xml .= "</$element>\n";
        }
        return $this;
    }

    /**
     * @desc 获取最终xml
     */
    public function getXml() {
        return $this->xml;
    }

    /**
     * @desc 缩进
     */
    protected function _indent() {
        for ($i = 0, $j = count($this->stack); $i < $j; $i++) {
            $this->xml .= $this->indent;
        }
        return $this;
    }
    
    /**
     * @desc 组件xml
     * @param array $filterarray
     * @param type $tag
     * @param array $attributes
     */
    public function buildXMLFilter($filterarray, $tag = '', $attributes = array()) {
        $this->xml .= $this->_buildXMLFilter($filterarray, $tag, $attributes);
        return $this;
    }

    
    public function _buildXMLFilter($filterarray, $tag = '', $attributes = array()) {
        $xmlfilter = "";
        foreach ($filterarray as $key => $value) {
            if ($tag) {
                $key = $tag;
            }
            if ( isset($attributes[$key]) ) {
                $attribute = ' ' . $attributes[$key]['name'] . '="' . $attributes[$key]['value'] . '" ';
            } else {
                $attribute = "";
            }
            if (is_array($value)) {
                $xmlfilter .= " <$key $attribute>\n" . $this->_buildXMLFilter($value, '', $attributes) . "</$key>\n";
            } else {
                if (intval($key) != 0 || $key === 0) {
                    $xmlfilter .= $value;
                } else {
                    $xmlfilter .= " <$key $attribute>$value</$key>\n";
                }
            }
        }
        return $xmlfilter;
    }
    
    // ==== lihy add in the 2016-01-20 =====
	public function buildXMLFilterMulti($filterarray, $tag = '', $attributes = array()) {
        $this->xml .= $this->_buildXMLFilterMulti($filterarray, $tag, $attributes);
        return $this;
    }
    
    public function _buildXMLFilterMulti($filterarray, $tag = '', $attributes = array()) {
    	$xmlfilter = "";
    	foreach ($filterarray as $key => $value) {
    		if ($tag) {
    			$key = $tag;
    		}
    		if ( isset($attributes[$key]) ) {
    			$attribute = ' ' . $attributes[$key]['name'] . '="' . $attributes[$key]['value'] . '" ';
    		} else {
    			$attribute = "";
    		}
    		if (is_array($value)) {
    			if(isset($value[0])){//以数字索引的
    				$xmlfilter .= $this->_buildXMLFilterMulti($value, $key, $attributes) ;
    			}else{
    				$xmlfilter .= " <$key $attribute>\n" . $this->_buildXMLFilterMulti($value, '', $attributes) . "</$key>\n";
    			}
    			
    		} else {
    			if (intval($key) != 0 || $key === 0) {
    				$xmlfilter .= $value;
    			} else {
    				$xmlfilter .= " <$key $attribute>$value</$key>\n";
    			}
    		}
    	}
    	return $xmlfilter;
    }
    // ======= add end =============
    
    // ==== hanxy add in the 2016-12-16 =====
    public function buildXMLFilterMultiNew($filterarray, $tag = '', $attributes = array()) {
        $this->xml .= $this->_buildXMLFilterMultiNew($filterarray, $tag, $attributes);
        return $this;
    }
    
    public function _buildXMLFilterMultiNew($filterarray, $tag = '', $attributes = array()) {
        $xmlfilter = "";
        foreach ($filterarray as $key => $value) {
            if ($tag) {
                $key = $tag;
            }
            if ( isset($attributes[$key]) ) {
                $attribute = ' ' . $attributes[$key]['name'] . '="' . $attributes[$key]['value'] . '" ';
            } else {
                $attribute = "";
            }
            if (is_array($value)) {
                if(isset($value[0])){//以数字索引的
                    $xmlfilter .= " <$key $attribute>\n";
                    $xmlfilter .= $this->_buildXMLFilterMultiNew($value, 'sku', $attributes) ;
                    $xmlfilter .= "</$key>\n";
                }else{
                    $xmlfilter .= " <$key $attribute>\n" . $this->_buildXMLFilterMultiNew($value, '', $attributes) . "</$key>\n";
                }
                
            } else {
                if (intval($key) != 0 || $key === 0) {
                    $xmlfilter .= $value;
                } else {
                    $xmlfilter .= " <$key $attribute>$value</$key>\n";
                }
            }
        }
        return $xmlfilter;
    }
    // ======= add end =============
}
?>