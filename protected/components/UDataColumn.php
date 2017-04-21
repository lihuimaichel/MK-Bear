<?php

/**
 * UDataColumn class file.
 *
 * @author Bob<zunfengke@gmail.com>
 */
class UDataColumn extends CDataColumn {

    /**
     * Renders the header cell content.
     * This method will render a link that can trigger the sorting if the column is sortable.
     */
    protected function renderHeaderCellContent() {
        if ($this->name !== null && $this->header === null) {
            if ($this->grid->dataProvider instanceof CActiveDataProvider) {               
                echo CHtml::encode($this->grid->dataProvider->model->getAttributeLabel($this->name));
            } else {
                echo CHtml::encode($this->name);
            }               
        }
        else
            parent::renderHeaderCellContent();
    }
    
    /**
	 * Renders the header cell.
	 */
	public function renderHeaderCell()
	{            
        $attributePairs = $this->grid->dataProvider->model->orderOptions();
        $attributes = array_keys($attributePairs);
		$this->headerHtmlOptions['id'] = $this->id;
		//add by ethan 2014.10.14 start
		/**
		@desc:因dwz在生成列表时给td自动增加宽度，导致字段过多时会挤在一起，不美观;
		固这里改成先给相关表头增加宽度属性，dwx生成时根据字段所设置的宽度给td增加宽度，没设置的再自动innerWidth()获取
		 */
		$this->headerHtmlOptions['style'] = '';
		//只给表头增加width属性，别的颜色啥的过滤掉
		if(isset($this->htmlOptions['style'])){
			$style = explode(';',$this->htmlOptions['style']);
			foreach ($style as $val){
				$arr = explode(':',$val);
				foreach ($arr as $v){
					if($v=='width'){
						$this->headerHtmlOptions['style'] .="width:".$arr[1].";";
					}
				}
			}
		}
		//add by ethan 2014.10.14 end
        if ( in_array($this->name, $attributes) ) {
            if ( $this->grid->enableSorting && $this->sortable && $this->name!==null ) {
                $this->headerHtmlOptions['orderField'] = $this->name;
                $this->headerHtmlOptions['style'] .= "text-decoration:underline;";
                if ( Yii::app()->request->getParam('orderField') == $this->name ) {
                    $direction = Yii::app()->request->getParam('orderDirection');                                    
                    $this->headerHtmlOptions['class'] = strtolower($direction); 
                }                          
            } 
        } 
        
		echo CHtml::openTag('th',$this->headerHtmlOptions);
		$this->renderHeaderCellContent();
		echo "</th>";
	}

}

