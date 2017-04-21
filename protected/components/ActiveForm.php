<?php

/**
 * ActiveForm Class
 * 
 * @package Application.components
 * @auther Bob <zunfengke@gmail.com>
 */
class ActiveForm extends CActiveForm {
    
    /**
	 * Renders a text field for a model attribute.
	 * This method is a wrapper of {@link CHtml::activeTextField}.
	 * Please check {@link CHtml::activeTextField} for detailed information
	 * about the parameters for this method.
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $htmlOptions additional HTML attributes.
	 * @return string the generated input field
	 */
	public function textField($model, $attribute, $htmlOptions=array())
	{   
        if (! empty($htmlOptions['hint']) && ! isset($htmlOptions['onfocus'])) {
            $htmlOptions['onfocus'] = "$.hint(this);";
        } else {
            $htmlOptions['onfocus'] = "this.select();";
        }
        
        $incSubSpan = '';
        if (! empty($htmlOptions['inc_sub_size']) ) {
            $incSubSpan = $this->textFieldSpanIncAndSub($model, $attribute, $htmlOptions);
        } 
		return CHtml::activeTextField($model, $attribute, $htmlOptions).$incSubSpan;
	}
    
    /**
     * list buttons
     * 
     * @param type $model
     * @param type $attribute
     * @param type $data
     * @param type $htmlOptions
     */
    public function listButton($model, $attribute, $data, $htmlOptions = array()) {
        return Html::activeListButton($model, $attribute, $data, $htmlOptions);
    }

        /**
     * text field span inc and sub
     * 
     * @param type $model
     * @param type $attribute
     * @param type $htmlOptions
     * @return string
     */
    public function textFieldSpanIncAndSub($model, $attribute, $htmlOptions=array() ) {
        $target = Yii::app()->request->getParam('target'); 
        $className = ucfirst(get_class($model));
        $span = CHtml::openTag('span', array( 'class' => 'left mr5 mt2'));
        //start 如果已手动设置text框id,则直接取此id,否则根据model等条件取,ethan hu 2014.06.20
        if(isset($htmlOptions['id']) && !empty($htmlOptions['id'])){
        	$id = $htmlOptions['id'];
        }else{
        	//防止$attribute是数组时出错
        	$replace = array('['=>'_',']['=>'_',']'=>'');
        	$id = strtr($attribute,$replace);
        }
        //end
        
        $span .= CHtml::link('', "javascript:void(0)", array( 
            'class'         => 't11 db',
            'id'            => $className . '_' . $id . '_incBtn',//update by ethan
            'onMouseDown'   => "spanMouseDown(this)",
            'onMouseOut'    => 'spanMouseUp(this)',
            'onMouseUp'     => 'spanMouseUp(this)',           
        ));
        $span .= CHtml::link('', "javascript:void(0)", array( 
            'class' => 't12 db',
            'id'    => $className . '_' . $id . '_subBtn',
            'onMouseDown'   => "spanMouseDown(this)",
            'onMouseOut'    => 'spanMouseUp(this)',
            'onMouseUp'     => 'spanMouseUp(this)',
        ));
        $span .= CHtml::closeTag('span');      
        
        return $span;
    }
    
}

?>
