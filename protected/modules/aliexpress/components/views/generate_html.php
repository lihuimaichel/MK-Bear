<?php
$html = '';
if (sizeof($data) > 0) {
	$sku = isset($list['sku']) ? $list['sku'] : '';
	switch($data['attribute_showtype_value']) {
		case 'check_box':
			$valueList = $data['value_list'];
			$html = '<ul class="attributeValueList">' . "\n";
			$jsScript = '';
			$checkedAttribute = '';
			$attributeID = $data['attribute_id'];
			$jsScript .= '_skuAttributes[' . $attributeID . '] = {' ."\n".
					'attribute_id:' . $attributeID. ",\n" .
					'attribute_spec:' . $data['attribute_spec'] . ",\n" .
					'attribute_customized_name:\'' . $data['attribute_customized_name'] . "',\n" .
					'attribute_customized_pic:\'' . $data['attribute_customized_pic'] . "',\n" .
					'attribute_key_attribute:' . $data['attribute_key_attribute'] . ",\n" .
					'attribute_required:' . $data['attribute_required'] . ",\n" .
					'attribute_name:\'' . $data['attribute_name_english'] . '(' . $data['attribute_name_Chinese'] . ')\'' .
					"};\n";
			$jsScript .= '_skuAttributes[' . $attributeID . '].value_list = new Array;';
			foreach ($valueList as $key => $list) {
			    if (!isset($list['attribute_value_id'])) continue;
				$html .= '<li>' . "\n";
				$checkedAttribute = '';
				$attributeValueID = $list['attribute_value_id'];
				$attributeValueName = $list['attribute_value_en_name'] . '(' . $list['attribute_value_cn_name'] . ')';
				if ($type == 'sku') {
					$sku = isset($list['sku']) ? $list['sku'] : '';
					$jsScript .= "_skuAttributes[$attributeID].value_list[$attributeValueID] = {attribute_value_id: $attributeValueID," .
					"attribute_value_name:  '$attributeValueName', " .
					"attribute_value_sku: '$sku'};\n";
				}
				$selected = isset($list['selected']) ? $list['selected'] : false;
				if ($selected) {
					$checkedAttribute = ' checked="checked"';
					$jsScript .= "collectSelected($attributeID, $attributeValueID, true);\n";
				}
				$html .= '<input attr_id="' . $attributeID . '" id="value_id_' . $attributeValueID . '" type="checkbox" value="' . $attributeValueID . '"' . $checkedAttribute;
				if ($type == 'sku') {
					$html .= ' onchange="collectSelected(' . $attributeID . ', this)"';
					$html .= ' name="sku_attributes[' . $attributeID . '][]"';
				} else {
					$html .= ' onchange="findSubAttributes(this,' . $attributeID . ')"';
					$html .= ' name="common_attributes[' . $attributeID . '][]"';
				}
				$html .= " />\n";
				$html .= '<label for="value_id_' . $attributeValueID . '">' . $attributeValueName . '</label>' . "\n";
				//if (data.attribute_name_english == 'Color')
				//html += '<div style="display:inline-block;border:1px #ccc solid;width:10px;height:10px;background:'+list[i].attribute_value_en_name+';"></div>' + "\n";
				$html .= '</li>' . "\n";
			}
			$html .= '</ul>' . "\n";
			if ($jsScript != '' && $type == 'sku')
				$html .= '<script type="text/javascript">' . $jsScript . '</script>' . "\n";
			break;
		case 'group_item':
			break;
		case 'group_table':
			break;
		case 'input':
			$value = isset($data['value_list']) && !is_array($data['value_list']) ? $data['value_list']: '';
			$html = '<input children="0" name="common_attributes[' . $data['attribute_id'] . ']" type="text" value="' . $value . '" size="32" />';
			break;
		case 'interval':
			break;
		case 'list_box':
			$jsScript = '';
			$checkedAttribute = '';
			$attributeID = $data['attribute_id'];
			$jsScript .= '_skuAttributes[' . $attributeID . '] = {' ."\n".
					'attribute_id:' . $attributeID. ",\n" .
					'attribute_spec:' . $data['attribute_spec'] . ",\n" .
					'attribute_customized_name:\'' . $data['attribute_customized_name'] . "',\n" .
					'attribute_customized_pic:\'' . $data['attribute_customized_pic'] . "',\n" .
					'attribute_key_attribute:' . $data['attribute_key_attribute'] . ",\n" .
					'attribute_required:' . $data['attribute_required'] . ",\n" .
					'attribute_name:\'' . $data['attribute_name_english'] . '(' . $data['attribute_name_Chinese'] . ')\'' .
					"};\n";
			$jsScript .= '_skuAttributes[' . $attributeID . '].value_list = new Array;';
			$valueList = $data['value_list'];
			$html .= '<select';
			if ($type == 'sku') {
				$html .= ' name="sku_attributes[' . $data['attribute_id'] . '][]"';
				$html .= ' onchange="collectSelected(' . $data['attribute_id'] . ', this)"';
			} else {
				$html .= ' name="common_attributes[' . $data['attribute_id'] . '][]"';
				$html .= ' onchange="findSubAttributes(this, ' . $data['attribute_id'] . ')"';				
			}
			if ($data['attribute_name_Chinese'] == '品牌'){
			    $html .= ' id="ajunBrandSelect"';
			}
			$html .= '>' . "\n";
			$html .= '<option value="">' . Yii::t('system', 'Please Select') . '</option>' . "\n";
			$customValueHtml = '';
			foreach ($valueList as $list) {
			    if (!isset($list['attribute_value_id'])) continue;
				$attributeValueID = $list['attribute_value_id'];
				$attributeValueName = $list['attribute_value_en_name'] . '(' . $list['attribute_value_cn_name'] . ')';
				$jsScript .= "_skuAttributes[$attributeID].value_list[$attributeValueID] = {attribute_value_id: $attributeValueID," .
				"attribute_value_name:  '$attributeValueName', " .
				"attribute_value_sku: '$sku'};\n";
				if (isset($list['selected']) && $list['selected'])
					$jsScript .= "collectSelected($attributeID, $attributeValueID, true);\n";
				$selected = isset($list['selected']) && $list['selected'] ? ' selected="selected"' : '';
				$html .= '<option' . $selected . ' children="' . $list['attribute_children'] . '" value="' . $list['attribute_value_id'] . '">' . $list['attribute_value_en_name'] . '(' . $list['attribute_value_cn_name'] . ')' . '</option>' . "\n";
				//是否为自定义属性值
				if (isset($list['value_name']))
					$customValueHtml = '&nbsp;&nbsp;<input type="text" name="common_attributes_custom_value[' . $attributeValueID . '][' . $attributeValueID . ']" value="' . $list['value_name'] . '" class="sub_attr_' . $attributeID . '" size="32" />';
			}
			$html .= '</select>' . "\n";
			$html .= $customValueHtml;
			if ($jsScript != '' && $type == 'sku')
				$html .= '<script type="text/javascript">' . $jsScript . '</script>' . "\n";
			break;
	}
	echo $html;
}