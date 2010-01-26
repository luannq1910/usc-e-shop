<?php
$status = $this->action_status;
$message = $this->action_message;
$this->action_status = 'none';
$this->action_message = '';

$divide_item = $this->options['divide_item'];
$itemimg_anchor_rel = $this->options['itemimg_anchor_rel'];
$fukugo_category_orderby = $this->options['fukugo_category_orderby'];
$fukugo_category_order = $this->options['fukugo_category_order'];
$usces_pref = empty($this->options['province']) ? array() : $this->options['province'];
$settlement_path = $this->options['settlement_path'];
$province = '';
for($i=1; $i<count($usces_pref); $i++){
	$province .= $usces_pref[$i] . "\n";
}
$province = trim($province);

?>
<script type="text/javascript">
jQuery(function($){
<?php if($status == 'success'){ ?>
			$("#anibox").animate({ backgroundColor: "#ECFFFF" }, 2000);
<?php }else if($status == 'caution'){ ?>
			$("#anibox").animate({ backgroundColor: "#FFF5CE" }, 2000);
<?php }else if($status == 'error'){ ?>
			$("#anibox").animate({ backgroundColor: "#FFE6E6" }, 2000);
<?php } ?>

	$("#aAdditionalURLs").click(function () {
		$("#AdditionalURLs").toggle();
	});
});

function toggleVisibility(id) {
   var e = document.getElementById(id);
   if(e.style.display == 'block')
	  e.style.display = 'none';
   else
	  e.style.display = 'block';
}
</script>
<div class="wrap">
<div class="usces_admin">
<h2>Welcart Shop システム設定<?php //echo __('Welcart shop system setup','usces'); ?></h2>
<div id="aniboxStatus" class="<?php echo $status; ?>">
	<div id="anibox" class="clearfix">
		<img src="<?php echo USCES_PLUGIN_URL; ?>/images/list_message_<?php echo $status; ?>.gif" />
		<div class="mes" id="info_massage"><?php echo $message; ?></div>
	</div>
</div>
<form action="" method="post" name="option_form" id="option_form">
<input name="usces_option_update" type="submit" class="button" value="設定を更新" />
<div id="poststuff" class="metabox-holder">

<div class="postbox">
<h3 class="hndle"><span>システム設定</span></h3>
<div class="inside">
<table class="form_table">
	<tr height="50">
	    <th><a style="cursor:pointer;" onclick="toggleVisibility('ex_province');">都道府県</a></th>
		<td width="150"><textarea name="province" cols="30" rows="10"><?php echo $province; ?></textarea></td>
	    <td><div id="ex_province" class="explanation">販売対象地区（都道府県）。改行して1行に一つずつ記入します。<br />初期値は全都道府県が設定してあります。</div></td>
	</tr>
</table>
<table class="form_table">
	<tr height="50">
	    <th><a style="cursor:pointer;" onclick="toggleVisibility('ex_divide_item');">表示モード</a></th>
		<?php $checked = $divide_item == 1 ? ' checked="checked"' : ''; ?>
		<td width="10"><input name="divide_item" type="checkbox" id="divide_item" value="<?php echo $divide_item; ?>"<?php echo $checked; ?> /></td>
		<td width="300"><label for="divide_item">ループ表示の際、商品を分離して表示する</label></td>
	    <td><div id="ex_divide_item" class="explanation">ショップにて、複数の投稿が表示されるループ表示の際、商品データを表示させるかどうかを設定します。</div></td>
	</tr>
</table>
<table class="form_table">
	<tr height="50">
	    <th><a style="cursor:pointer;" onclick="toggleVisibility('ex_itemimg_anchor_rel');">rel属性</a></th>
		<td width="30">rel="</td>
		<td width="100"><input name="itemimg_anchor_rel" id="itemimg_anchor_rel" type="text" value="<?php echo $itemimg_anchor_rel; ?>" /></td>
		<td width="10">"</td>
	    <td><div id="ex_itemimg_anchor_rel" class="explanation">商品詳細ページにて、Lightboxなどプラグインを利用してイメージを表示させるためのアンカータグ用rel属性を指定します。</div></td>
	</tr>
</table>
<table class="form_table">
	<tr height="50">
	    <th><a style="cursor:pointer;" onclick="toggleVisibility('ex_fcat_orderby');">複合カテゴリーソート項目</a></th>
		<td width="10"><select name="fukugo_category_orderby" id="fukugo_category_orderby">
		    <option value="ID"<?php if($fukugo_category_orderby == 'ID') echo ' selected="selected"'; ?>>カテゴリーID</option>
		    <option value="name"<?php if($fukugo_category_orderby == 'name') echo ' selected="selected"'; ?>>カテゴリー名</option>
		</select></td>
	    <td><div id="ex_fcat_orderby" class="explanation">複合カテゴリー検索ページで表示するカテゴリーにおいて、ソートする対象を選択します。</div></td>
	</tr>
	<tr height="50">
	    <th><a style="cursor:pointer;" onclick="toggleVisibility('ex_fcat_order');">複合カテゴリーソート順</a></th>
		<td width="10"><select name="fukugo_category_order" id="fukugo_category_order">
		    <option value="ASC"<?php if($fukugo_category_order == 'ASC') echo ' selected="selected"'; ?>>昇順</option>
		    <option value="DESC"<?php if($fukugo_category_order == 'DESC') echo ' selected="selected"'; ?>>降順</option>
		</select></td>
	    <td><div id="ex_fcat_order" class="explanation">複合カテゴリー検索ページで表示するカテゴリーにおいて、ソート順を選択します。</div></td>
	</tr>
</table>
<table class="form_table">
	<tr height="50">
	    <th><a style="cursor:pointer;" onclick="toggleVisibility('ex_settlement_path');">決済モジュールパス</a></th>
		<td><input name="settlement_path" type="text" id="settlement_path" value="<?php echo $settlement_path; ?>" size="60" /></td>
	    <td><div id="ex_settlement_path" class="explanation">決済モジュールの設置場所を指定します。初期値はサンプルと同じ場所ですが自動アップグレードの時に削除されてしまいます。これを避けるにはモジュールをプラグインフォルダの外に設置します。</div></td>
	</tr>
</table>
</div>
</div><!--postbox-->


</div><!--poststuff-->



<input name="usces_option_update" type="submit" class="button" value="設定を更新" />
<input type="hidden" id="post_ID" name="post_ID" value="<?php echo USCES_CART_NUMBER ?>" />
</form>
</div><!--usces_admin-->
</div><!--wrap-->