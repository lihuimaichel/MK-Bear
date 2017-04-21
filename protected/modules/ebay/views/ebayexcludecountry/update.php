<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>

<script type="text/javascript">
function checkCurrencyCon(obj){
	$(obj).parent("td").next("td").find("input[type=checkbox]").attr("checked", !!$(obj).attr("checked"));
};
</script>

<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'ebay_exclude_shiping_country_add',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => Yii::app()->createUrl('ebay/ebayexcludecountry/savedata'),
        'htmlOptions' => array(        
            'class' 	=> 'pageForm', 
			'target'	=> 'dialog',
			'onsubmit'	=>	'return validateCallback(this, dialogAjaxDone)'      
        )
    ));
    ?>
    <div class="pageFormContent" layoutH="56"> 
    	<div class="row">
			<?php echo CHtml::label("站点", "site_id"); ?>
                <select id="site_id" name="site_id"  disabled="disabled">
                    <?php foreach($listingSite as $key=>$site):?>
                    	<option value="<?php echo $key;?>" <?php if($key == $siteID){echo "selected";}?>><?php echo $site;?></option>
                    <?php endforeach;?>
                </select>
		</div>
		<div class="row">
			<?php echo CHtml::label("账号", "account_id"); ?>
            <select id="account_id" name="account_id">
                <?php foreach($accounts as $key=>$account):?>
                  <option value="<?php echo $key;?>" <?php if($key == $accountID){echo "selected";}?>><?php echo $account;?></option>
                <?php endforeach;?>
            </select>
		</div>
		
		<input type="hidden" name="site_id" value="<?php echo $siteID;?>"/>
    	<input type="hidden" name="id" value="<?php echo $id;?>"/>
    	
		<table class="table" width="96%">
		<thead>
			<tr>
				<th width="30">所属大洲</th>
				<th><input type="checkbox" class="checkboxCtrl" group="code3" /></th>
			</tr>
		</thead>
		<tbody>
			<?php if($excludeShippingLocation):?>
			<?php foreach ($excludeShippingLocation as $con=>$countrys):?>
			<tr>
				<td><?php echo $con;?> <input type="checkbox" class="checkCurrencyCon" onclick="checkCurrencyCon(this)" onpropertychange="checkCurrencyCon(this)"/></td>
				<td>
				<?php foreach ($countrys as $country):?>
					<span style="width: 160px;height:40px;line-height:24px;display:inline-block;">
			
	            		<input id="continents_<?php echo $con;?>_<?php echo $country['code'];?>" type="checkbox" name="code[<?php echo $country['code'];?>]" <?php if(in_array($country['code'], $selectedCountry)):?> checked<?php endif;?> value='<?php echo $country['name'];?>'>
						<label for="continents_<?php echo $con;?>_<?php echo $country['code'];?>" style="float: right;"><?php echo $country['name'];?></label>
	            		
						
						
					</span>
				<?php endforeach;?>
				</td>
			</tr>
			<?php endforeach;?>
			<?php endif;?>
		</tbody>
	</table>
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit"><?php echo Yii::t('system', 'Save')?></button>                     
                    </div>
                </div>
            </li>
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel')?></button></div></div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>