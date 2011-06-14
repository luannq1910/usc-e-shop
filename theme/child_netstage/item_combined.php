<?php
/*
Template Name: セット販売テンプレート
 * <meta content="charset=UTF-8">
 * @package Welcart
 * @subpackage Net Stage Theme
*/
get_header();

$NSSP = new NS_SetPage();
$NSSP->set_list_per_page( 20 );
$NSSP->set_data();

?>

<div id="content">
<div class="catbox">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<div class="post" id="<?php echo $post->post_name; ?>">
<a name="capture"></a>
<h1 class="pagetitle"><?php the_title(); ?></h1>
<div class="entry">

	<div id="top_area" class="clearfix">
		<div class="product_box">
			<div class="step_title">STEP.1 ヘッド</div>
			<div id="step1" class="product_info <?php echo $NSSP->get_top_info_class('head'); ?>">
				<div class="set_thumbnail"><?php echo $NSSP->get_top_thumb('head'); ?></div>
				<div class="item_name <?php echo $NSSP->get_top_name_class('head'); ?>"><?php echo $NSSP->get_top_itemname('head'); ?></div>
				<div class="item_price">販売価格：<?php echo $NSSP->get_top_itemprice('head'); ?></div>
				<div class="item_cprice">通常価格：<?php echo $NSSP->get_top_itemcprice('head'); ?></div>
				<div class="set_button_box clearfix">
					<div id="step1_change" class="set_button"><?php echo $NSSP->get_top_change_button('head'); ?></div>
					<div id="step1_detail" class="set_button"><?php echo $NSSP->get_top_detail_button('head'); ?></div>
				</div>
			</div>
		</div>
		<div class="product_box">
			<div class="step_title">STEP.2 シャフト</div>
			<div id="step2" class="product_info <?php echo $NSSP->get_top_info_class('shuft'); ?>">
				<div class="set_thumbnail"><?php echo $NSSP->get_top_thumb('shuft'); ?></div>
				<div class="item_name <?php echo $NSSP->get_top_name_class('shuft'); ?>"><?php echo $NSSP->get_top_itemname('shuft'); ?></div>
				<div class="item_price">販売価格：<?php echo $NSSP->get_top_itemprice('shuft'); ?></div>
				<div class="item_cprice">通常価格：<?php echo $NSSP->get_top_itemcprice('shuft'); ?></div>
				<div class="set_button_box clearfix">
					<div id="step2_change" class="set_button"><?php echo $NSSP->get_top_change_button('shuft'); ?></div>
					<div id="step2_detail" class="set_button"><?php echo $NSSP->get_top_detail_button('shuft'); ?></div>
				</div>
			</div>
		</div>
		<div class="product_box">
			<div class="step_title">STEP.3 グリップ</div>
			<div id="step3" class="product_info <?php echo $NSSP->get_top_info_class('grip'); ?>">
				<div class="set_thumbnail"><?php echo $NSSP->get_top_thumb('grip'); ?></div>
				<div class="item_name <?php echo $NSSP->get_top_name_class('grip'); ?>"><?php echo $NSSP->get_top_itemname('grip'); ?></div>
				<div class="item_price">販売価格：<?php echo $NSSP->get_top_itemprice('grip'); ?></div>
				<div class="item_cprice">通常価格：<?php echo $NSSP->get_top_itemcprice('grip'); ?></div>
				<div class="set_button_box clearfix">
					<div id="step3_change" class="set_button"><?php echo $NSSP->get_top_change_button('grip'); ?></div>
					<div id="step3_detail" class="set_button"><?php echo $NSSP->get_top_detail_button('grip'); ?></div>
				</div>
			</div>
		</div>
		<div class="amount_box">
			<div id="set_order">
				<div class="amount_label">工賃を含む合計金額</div>
				<div class="prouct_amount"><?php echo $NSSP->get_product_amount(); ?></div>
				<div class="amount_mes"><?php echo $NSSP->get_top_amount_mes(); ?></div>
				<div class="amount_button"><?php echo $NSSP->get_top_amount_button(); ?></div>
			</div>
		</div>
	
	</div><!-- end of top_area -->
	<div id="body_area">
		<h3 class="titlebar"><?php echo $NSSP->body_caption(); ?></h3>
		
		<?php /* リスト表示 */ if( 'head_list' == $NSSP->action || 'shuft_list' == $NSSP->action || 'grip_list' == $NSSP->action || 'default' == $NSSP->action ) : ?>
		<div class="clearfix">
		
		<?php $reco_ob = new wp_query( $NSSP->get_list_query() ); ?>
		<?php if ($reco_ob->have_posts()) : while ($reco_ob->have_posts()) : $reco_ob->the_post(); usces_the_item(); ?>
		<div class="thumbnail_box">
			<form action="#capture" method="post">
			<div class="item_code"><?php usces_the_itemCode(); ?></div>
			<div class="thumimg"><?php usces_the_itemImage($number = 0, $width = 140, $height = 140 ); ?></div>
			<?php NS_the_fantastic4(); ?>
			<div class="item_name"><?php usces_the_itemName(); ?></div>
			<div class="item_price"><?php NS_the_item_pricesCr(); ?></div>
			<div class="select_button_box">
				<input name="head_detail" type="submit" class="select_item_button" value="　" />
				<input name="selected_post_id" type="hidden" value="<?php the_ID(); ?>" />
			</div>
			</form>
		</div>
		
		<?php endwhile; else: ?>
		<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
		<?php endif; wp_reset_query(); ?>
		</div>
		
		<?php /* 詳細表示 */ else : ?>
		
		<?php $NSSP->view_item_detail(); ?>
		
		<?php endif; ?>
	</div>
</div><!-- end of entry -->
</div><!-- end of post -->
<?php endwhile; endif; ?>
<?php usces_p($NSSP->product); ?>
<?php usces_p($NSSP->action); ?>
</div><!-- end of catbox -->
</div><!-- end of content -->

<?php get_footer(); ?>

<?php

class NS_SetPage
{
	var $action;
	var $product;
	var $body_caption;
	var $posts_per_page;
	
	function __construct(){
		$this->set_action();
	}

	function set_action(){
		if( isset($_POST['head_list']) )
			$this->action = 'head_list';
		elseif( isset($_POST['head_detail']) )
			$this->action = 'head_detail';
		elseif( isset($_POST['shuft_list']) )
			$this->action = 'shuft_list';
		elseif( isset($_POST['shuft_detail']) )
			$this->action = 'shuft_detail';
		elseif( isset($_POST['grip_list']) )
			$this->action = 'grip_list';
		elseif( isset($_POST['grip_detail']) )
			$this->action = 'grip_detail';
//		elseif( isset($_POST['select_item']) )
//			$this->action = 'select_item';
//		elseif( isset($_POST['enter_item']) )
//			$this->action = 'enter_item';
		elseif( isset($_POST['decide']) )
			$this->action = 'decide';
		else
			$this->action = 'default';
	}
	
	function set_data(){
		$this->get_session();
		
		switch( $this->action ){
			case 'head_list':
				$this->body_caption = 'ヘッド一覧';

				break;
			case 'head_detail':
				$this->body_caption = 'ヘッド詳細';
				$this->product['head']['post_id'] = isset($_POST['selected_post_id']) ? (int)$_POST['selected_post_id'] : NULL;

				break;
			case 'shuft_list':
				$this->body_caption = 'シャフト一覧';
			
				break;
			case 'shuft_detail':
				$this->body_caption = 'シャフト詳細';
				$this->product['shuft']['post_id'] = isset($_POST['selected_post_id']) ? (int)$_POST['selected_post_id'] : NULL;

				break;
			case 'grip_list':
				$this->body_caption = 'グリップ一覧';
			
				break;
			case 'grip_detail':
				$this->body_caption = 'グリップ詳細';
				$this->product['grip']['post_id'] = isset($_POST['selected_post_id']) ? (int)$_POST['selected_post_id'] : NULL;

				break;
//			case 'select_item':
//				$this->body_caption = '商品詳細';
//			
//				break;
//			case 'enter_item':
//			
//				break;
			case 'decide':
			
				break;
			default:
				$this->body_caption = 'ヘッド一覧';
		}
		$this->get_session();
	}

	function get_session(){
	
//		$_SESSION['nsset']['product']['head']['post_id']
//		$_SESSION['nsset']['product']['head']['sku']
//		$_SESSION['nsset']['product']['shuft']
//		$_SESSION['nsset']['product']['grip']
//		$_SESSION['nsset']['product']

	}
		
	function set_session(){
	
//		$_SESSION['nsset']['product']['head']['post_id']
//		$_SESSION['nsset']['product']['head']['sku']
//		$_SESSION['nsset']['product']['shuft']
//		$_SESSION['nsset']['product']['grip']
//		$_SESSION['nsset']['product']

	}
		
	function set_list_per_page( $per_page ){
		$this->posts_per_page = $per_page;
	}

	function get_list_query(){
		switch( $this->action ){
			case 'shuft_list':
				$cat_item = usces_get_cat_id( 'itemshuft' );
				$cat_set = usces_get_cat_id( 'availableset' );
				$category__and = array($cat_item, $cat_set);
				break;
			case 'grip_list':
				$cat_item = usces_get_cat_id( 'itemgrip' );
				$cat_set = usces_get_cat_id( 'availableset' );
				$category__and = array($cat_item, $cat_set);
				break;
			case 'head_list':
			default:
				$cat_item = usces_get_cat_id( 'itemhead' );
				$cat_set = usces_get_cat_id( 'availableset' );
				$category__and = array($cat_item, $cat_set);
		}
		$page = get_query_var( 'page' );
		$paged = empty($page) ? 1 : $page;
		$offset = $this->posts_per_page * ($paged - 1) + 1;
		
		$query = array(
			'category__and'		=> $category__and,
			'posts_per_page'	=> $this->posts_per_page, 
			'paged'				=> $paged, 
			'offset'			=> $offset, 
			'post_status'		=> 'publish'
		);
		return $query;
	}
	
	function get_top_info_class( $type ){
		$res = !empty($this->product[$type]['post_id']) ? 'gray' : 'white';
		return $res;
	}
	
	function get_top_name_class( $type ){
		$focus = $this->get_top_focus();
		switch( $type ){
			case 'head':
				$res = 'focused';
				break;
			case 'shuft':
				$res = ( 'shuft' == $focus || 'grip' == $focus || 'amount' == $focus ) ? 'focused' : '';
				break;
			case 'grip':
				$res = ( 'grip' == $focus || 'amount' == $focus ) ? 'focused' : '';
				break;
		}
		return $res;
	}
	
	function get_top_thumb( $type ){
		$res = !empty($this->product[$type]['post_id']) ? usces_get_itemImage( $this->product[$type]['post_id'], 0, 60, 60) : '';
		return $res;
	}

	function get_top_itemname( $type ){
		global $usces;
		$res = !empty($this->product[$type]['post_id']) ? $usces->getItemName($this->product[$type]['post_id']) : '選択してください';
		return $res;
	}

	function get_top_itemprice( $type ){
		if( empty($this->product[$type]['post_id']) || empty($this->product[$type]['sku']) )
			return;
			
		global $usces;
		$res = usces_get_item_price($this->product[$type]['post_id'], $this->product[$type]['sku']);
		return $usces->get_currency($res, true, false);
	}

	function get_top_itemcprice( $type ){
		if( empty($this->product[$type]['post_id']) || empty($this->product[$type]['sku']) )
			return;
			
		global $usces;
		$res = usces_get_item_cprice($this->product[$type]['post_id'], $this->product[$type]['sku']);
		return $usces->get_currency($res, true, false);
	}

	function get_top_change_button( $type ){
		$focus = $this->get_top_focus();
		switch( $type ){
			case 'head':
				$res = '<input name="head_list" type="submit" class="change_button" value="　" />';
				break;
			case 'shuft':
				if( 'shuft' == $focus || 'grip' == $focus || 'amount' == $focus ){
					$res = '<input name="shuft_list" type="submit"  class="change_button" value="　" />';
				}else{
					$res = '<input name="shuft_list" type="button"  class="change_button_dis" value="　" disabled="disabled" />';
				}
				break;
			case 'grip':
				if( 'grip' == $focus || 'amount' == $focus ){
					$res = '<input name="grip_list" type="submit" class="change_button" value="　" />';
				}else{
					$res = '<input name="grip_list" type="button"  class="change_button_dis" value="　" disabled="disabled" />';
				}
				break;
		}
		return $res;
	}

	function get_top_detail_button( $type ){
		$focus = $this->get_top_focus();
		switch( $type ){
			case 'head':
				$res = '<input name="head_detail" type="submit" class="detail_button" value="　" />';
				break;
			case 'shuft':
				if( 'shuft' == $focus || 'grip' == $focus || 'amount' == $focus ){
					$res = '<input name="shuft_detail" type="submit"  class="detail_button" value="　" />';
				}else{
					$res = '<input name="shuft_detail" type="button"  class="detail_button_dis" value="　" disabled="disabled" />';
				}
				break;
			case 'grip':
				if( 'grip' == $focus || 'amount' == $focus ){
					$res = '<input name="grip_detail" type="submit" class="detail_button" value="　" />';
				}else{
					$res = '<input name="grip_detail" type="button"  class="detail_button_dis" value="　" disabled="disabled" />';
				}
				break;
		}
		return $res;
	}

	function get_product_amount(){
		
	}

	function get_top_amount_mes(){
		$focus = $this->get_top_focus();
		$res = ( 'amount' == $focus ) ? 'kouchin' : '※パーツ構成が確定していません。';
		return $res;
	}
	
	function get_top_amount_button(){
		$focus = $this->get_top_focus();
		if( 'amount' == $focus ){
			$res = '<input name="decide" type="submit" class="decide_button" value="　" />';
		}else{
			$res = '<input name="decide" type="button"  class="decide_button_dis" value="　" disabled="disabled" />';
		}
		return $res;
	}

	function get_top_focus(){
		if( empty($this->product['head']['post_id']) && empty($this->product['shuft']['post_id']) && empty($this->product['grip']['post_id'])){
			$res = 'head';
		}elseif( !empty($this->product['head']['post_id']) && empty($this->product['shuft']['post_id']) ){
			$res = 'shuft';
		}elseif( !empty($this->product['shuft']['post_id']) && empty($this->product['grid']['post_id'])){
			$res = 'grip';
		}else{
			$res = 'amount';
		}
		return $res;
	}
	
	function body_caption(){
		return $this->body_caption;
	}

	function view_item_detail(){
?>







<?php
	}

	
}

function usces_get_itemImage( $post_id, $number = 0, $width = 60, $height = 60 ) {
	global $usces;

	$code =  get_post_custom_values('_itemCode', $post_id);
	if(!$code) return false;
	
	$name = get_post_custom_values('_itemName', $post_id);
	
	$pictids = $usces->get_pictids($code[0]);
	$html = wp_get_attachment_image( $pictids[$number], array($width, $height), false );

	return $html;
}

function usces_get_item_price($post_id, $sku){
	global $usces;
	$field = get_post_meta($post_id, '_isku_'.$sku, true);
	$skus = unserialize($field);
	return $skus['price'];
}

function usces_get_item_cprice($post_id, $sku){
	global $usces;

}
?>