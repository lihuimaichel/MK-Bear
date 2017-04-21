<!DOCTYPE center PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="content-type" content="text/html;charset=utf-8">
	<title>JD订单手动拉取程序</title>
</head>
<body>
<center>
	<div style="text-align:left;width:800px;height:200px;">
		<?php if(empty($type)){?>
		<div style="width: 100%;height:300px;margin-top:120px;font-weight:800">
		<h1>
			<a href="<?php echo Yii::app()->createUrl("jd/jdorder/getorder/type/1")?>" target="__blank">
			轻轻点击我，跑去拉取该死的JD订单吧，O(∩_∩)O哈哈~
			</a>
		</h1>
		</div>
		<?php }else{?>
		<form action="" method="post">
			<span><?php if(!empty($message)){echo $message;}?></span>
			<br/>
			<br/>
			<br/>
			accountID: <select name="account_id">
				<?php foreach ($accountList as $key=>$account):?>
				<option value="<?php echo $key;?>" <?php if($key == $account_id) echo "selected";?>><?php echo $account;?></option>
				<?php endforeach;?>
			</select>
			<BR/>
			orderID: <textarea rows="5" cols="40" name="order_id"><?php echo $order_id;?></textarea>（JD平台上的订单ID，每个订单ID用半角逗号(,)隔开）
			<BR/>
			<BR/>
			<BR/>
			<input type="submit" value="拉取订单" style="margin-left:300px;"/>
		</form>
		<?php }?>
	</div>
</center>
</body>
</html>