<?php
/**
 * @desc 订单运输方式模型
 * @author zhangF
 *
 */
class ShipType extends SystemsModel {
	//运送类型
	const CODE_COMMON  = 'cm';//普通
	const CODE_GHXB = 'ghxb';//挂号
	const CODE_EXPRESS = 'kd';//快递
	const CODE_ZE = 'ghxb_cusl';//中英专线
	const CODE_CE = 'cm_cesl';//中欧专线
	const CODE_EUB  = 'eub';//e邮宝
	const CODE_BE = 'ghxb_post_be';//比利时邮政
	const CODE_BE_UK = 'ghxb_uk_be';//比利时挂号优先
	const CODE_BE_TPP = 'ghxb_tpparcels_be';//比利时挂号
	const CODE_FU_CM = 'cm_fuzhou';//福州小包
	const CODE_FU_GHXB = 'ghxb_fuzhou';//福州挂号
	const CODE_DHL_GH = 'ghxb_dhl';//敦豪全邮通-挂号
	const CODE_DHL_XB = 'cm_dhl';//敦豪全邮通-小包
	const CODE_DHL_XB_DE = 'cm_de_dhl';
// 	public $CODE_EUB_GIFT = 'eub_gift';//eub分包送礼
	
	const CODE_SF_GHXB = 'ghxb_sf';//顺丰挂号
	const CODE_SF_CM = 'cm_sf';//顺丰小包
	
	const CODE_DHLS = 'dhl';//敦豪全邮通类型包裹
}
?>