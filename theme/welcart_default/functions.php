<?php
/**
 * <meta content="charset=UTF-8">
 * @package Welcart
 * @subpackage Welcart Default Theme
 */
if(!defined('USCES_VERSION')) return;

/***********************************************************
* welcart_setup
***********************************************************/
add_action( 'after_setup_theme', 'welcart_setup' );
if ( ! function_exists( 'welcart_setup' ) ):
function welcart_setup() {
	
	load_theme_textdomain( 'uscestheme', TEMPLATEPATH . '/languages' );
	
	register_nav_menus( array(
		'header' => __('Header Navigation', 'usces' ),
		'footer' => __('Footer Navigation', 'usces' ),
	) );
}
endif;

/***********************************************************
* welcart_page_menu_args
***********************************************************/
function welcart_page_menu_args( $args ) {
	$args['show_home'] = true;
	return $args;
}
add_filter( 'wp_page_menu_args', 'welcart_page_menu_args' );

/***********************************************************
* sidebar
***********************************************************/
if ( function_exists('register_sidebar') ) {
	// Area 1, HomeLeft.
	register_sidebar(array(
		'name' => __( 'Home Left', 'uscestheme' ),
		'id' => 'homeleft-widget-area',
		'description' => __( 'home left sidebar widget area', 'uscestheme' ),
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
		'after_widget' => '</li>',
		'before_title' => '<div class="widget_title">',
		'after_title' => '</div>',
	));
	// Area 2, HomeRight.
	register_sidebar(array(
		'name' => __( 'Home Right', 'uscestheme' ),
		'id' => 'homeright-widget-area',
		'description' => __( 'home right sidebar widget area', 'uscestheme' ),
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
		'after_widget' => '</li>',
		'before_title' => '<div class="widget_title">',
		'after_title' => '</div>',
	));
	// Area 3, OtherLeft.
	register_sidebar(array(
		'name' => __( 'Other Left', 'uscestheme' ),
		'id' => 'otherleft-widget-area',
		'description' => __( 'other left sidebar widget area', 'uscestheme' ),
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
		'after_widget' => '</li>',
		'before_title' => '<div class="widget_title">',
		'after_title' => '</div>',
	));
	// Area 4, CartMemberLeft.
	register_sidebar(array(
		'name' => __( 'CartMemberLeft', 'uscestheme' ),
		'id' => 'cartmemberleft-widget-area',
		'description' => __( 'cart or member left sidebar widget area', 'uscestheme' ),
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
		'after_widget' => '</li>',
		'before_title' => '<div class="widget_title">',
		'after_title' => '</div>',
	));
}

/***********************************************************
* widget
***********************************************************/
add_filter('widget_categories_dropdown_args', 'welcart_categories_args');
add_filter('widget_categories_args', 'welcart_categories_args');
function welcart_categories_args( $args ){
	global $usces;
	$ids = $usces->get_item_cat_ids();
	$ids[] = USCES_ITEM_CAT_PARENT_ID;
	$args['exclude'] = $ids;
	return $args;
}
add_filter('getarchives_where', 'welcart_getarchives_where');
function welcart_getarchives_where( $r ){
	$where = "WHERE post_type = 'post' AND post_status = 'publish' AND post_mime_type <> 'item' ";
	return $where;
}
add_filter('widget_tag_cloud_args', 'welcart_tag_cloud_args');
function welcart_tag_cloud_args( $args ){
	global $usces;
	if( 'category' == $args['taxonomy']){
		$ids = $usces->get_item_cat_ids();
		$ids[] = USCES_ITEM_CAT_PARENT_ID;
		$args['exclude'] = $ids;
	}else if( 'post_tag' == $args['taxonomy']){
		$ids = $usces->get_item_post_ids();
		$tobs = wp_get_object_terms($ids, 'post_tag');
		foreach( $tobs as $ob ){
			$tids[] = $ob->term_id;
		}
		$args['exclude'] = $tids;
	}
	return $args;
}

/***********************************************************
* excerpt
***********************************************************/
if ( ! function_exists( 'welcart_assistance_excerpt_length' ) ) {
	function welcart_assistance_excerpt_length( $length ) {
		return 10;
	}
}

if ( ! function_exists( 'welcart_assistance_excerpt_mblength' ) ) {
	function welcart_assistance_excerpt_mblength( $length ) {
		return 40;
	}
}

if ( ! function_exists( 'welcart_excerpt_length' ) ) {
	function welcart_excerpt_length( $length ) {
		return 40;
	}
}
add_filter( 'excerpt_length', 'welcart_excerpt_length' );

if ( ! function_exists( 'welcart_excerpt_mblength' ) ) {
	function welcart_excerpt_mblength( $length ) {
		return 110;
	}
}
add_filter( 'excerpt_mblength', 'welcart_excerpt_mblength' );

if ( ! function_exists( 'welcart_continue_reading_link' ) ) {
	function welcart_continue_reading_link() {
		return ' <a href="'. get_permalink() . '">' . __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'uscestheme' ) . '</a>';
	}
}

if ( ! function_exists( 'welcart_auto_excerpt_more' ) ) {
	function welcart_auto_excerpt_more( $more ) {
		return ' &hellip;' . welcart_continue_reading_link();
	}
}
add_filter( 'excerpt_more', 'welcart_auto_excerpt_more' );

if ( ! function_exists( 'welcart_custom_excerpt_more' ) ) {
	function welcart_custom_excerpt_more( $output ) {
		if ( has_excerpt() && ! is_attachment() ) {
			$output .= welcart_continue_reading_link();
		}
		return $output;
	}
}
add_filter( 'get_the_excerpt', 'welcart_custom_excerpt_more' );

/***********************************************************
* SSL
***********************************************************/
if( $usces->options['use_ssl'] ){
	add_action('init', 'usces_ob_start');
	function usces_ob_start(){
		global $usces;
		if( $usces->use_ssl && ($usces->is_cart_or_member_page($_SERVER['REQUEST_URI']) || $usces->is_inquiry_page($_SERVER['REQUEST_URI'])) )
			ob_start('usces_ob_callback');
	}
	if ( ! function_exists( 'usces_ob_callback' ) ) {
		function usces_ob_callback($buffer){
			global $usces;
			$pattern = array(
				'|(<[^<]*)href=\"'.get_option('siteurl').'([^>]*)\.css([^>]*>)|', 
				'|(<[^<]*)src=\"'.get_option('siteurl').'([^>]*>)|'
			);
			$replacement = array(
				'${1}href="'.USCES_SSL_URL_ADMIN.'${2}.css${3}', 
				'${1}src="'.USCES_SSL_URL_ADMIN.'${2}'
			);
			$buffer = preg_replace($pattern, $replacement, $buffer);
			return $buffer;
		}
	}
}

//kanpari start
/***********************************************************
* Initial setting
***********************************************************/
//define('KANPARI_TOKOFORM', 3223);//�މʓ��e�t�H�[����post_id
define('KANPARI_TOKOFORM', 76);//�މʓ��e�t�H�[����post_id
$kanpari_area = array(
	1 => "���",
	2 => "���Ɂi���˓��C�j",
	3 => "���Ɂi���{�C�j",
	4 => "�a�̎R",
	5 => "���s",
	6 => "����",
	7 => "�W�H"
	);
$kanpari_area_tag = array(
	1 => "osaka",
	2 => "hyogo-setonaikai",
	3 => "hyogo-nihonkai",
	4 => "wakayama",
	5 => "kyoto",
	6 => "fukui",
	7 => "awaji"
	);
$kanpari_location[1] = array( "�I�����Ă�������",
	"���s�G���A�@����͌�",
	"���s�G���A�@���B",
	"���s�G���A�@����`�C�ނ����",
	"���s�G���A�@����`",
	"���΁E���ÃG���A�@�����u��",
	"���΁E���ÃG���A�@�����u��",
	"���΁E���ÃG���A�@���������n",
	"�ݘa�c�E�L�˃G���A�@�L��",
	"�ݘa�c�E�L�˃G���A�@�ݘa�c1",
	"�ݘa�c�E�L�˃G���A�@�ݘa�c2",
	"�򍲖�G���A�@���c�Y���`",
	"�򍲖�G���A�@�c�K���`",
	"�򍲖�G���A�@��񂭂�",
	"�򍲖�G���A�@���싙�`",
	"�򍲖�G���A�@�򍲖�",
	"���E���G���A�@����Ȃ񗢊C����",
	"���E���G���A�@����",
	"���E���G���A�@����m��",
	"���E���G���A�@����",
	"���E���G���A�@�M��",
	"�����G���A�@����",
	"�����G���A�@�Ƃ��ƃp�[�N����",
	"�����G���A�@�J��",
	"�����G���A�@�[��",
	"�����G���A�@���S���t�ꗠ",
	"�����G���A�@�W��",
	"�����G���A�@�W�փ��b�g�n�[�o�["
	);
$kanpari_location[2] = array( "�I�����Ă�������",
	"���`�����G���A�@���t�F�j�b�N�X�`�ނ����",
	"���`�����G���A�@���ɐ�͌�",
	"���`�����G���A�@���{�l",
	"���`�����G���A�@�숰���l",
	"���`�����G���A�@���ɐ�K�ꕶ��",
	"�_�˓����G���A�@�_�˂V�h",
	"�_�˓����G���A�@�_�ˍ`4,5,6,8�h",
	"�_�˓����G���A�@�_�ˋ�`",
	"�_�ː����G���A�@�{��1",
	"�_�ː����G���A�@�{��2",
	"�_�ː����G���A�@�_�ˎs���{���C�Â����",
	"�_�ː����G���A�@�����C��",
	"�_�ː����G���A�@�_�ˎs������C�Â����",
	"�_�ː����G���A�@�������`",
	"�_�ː����G���A�@�A�W���[�����q�`�����q",
	"���Γ����G���A�@�呠�C��",
	"���Γ����G���A�@���΍`",
	"���Γ����G���A�@�V�l���`",
	"���Γ����G���A�@�э苙�`",
	"���Γ����G���A�@���]�`���]",
	"���Γ����G���A�@�]�䃖��",
	"���ΐ����G���A�@���Z���`",
	"���ΐ����G���A�@����",
	"���ΐ����G���A�@���񌩐l����",
	"�d���E�����G���A�@�{���l����",
	"�d���E�����G���A�@���Ð�͌��ꕶ���`�_�|�P�[�\��",
	"�d���E�����G���A�@�����`",
	"�d���E�����G���A�@�ɕۍ`",
	"�P�H�����G���A�@�剖���`",
	"�P�H�����G���A�@�I�`",
	"�P�H�����G���A�@�P�H�s���V���Z���^�[",
	"�P�H�����G���A�@�؏ꋙ�`",
	"�P�H�����G���A�@�Ȏ��i���l�j���`",
	"�P�H�����G���A�@�Ȏ����g�~",
	"�P�H�����G���A�@�����`�L��",
	"�P�H�����G���A�@�Ԋ��l�k����",
	"�P�H�����G���A�@����A�K�ې�",
	"���́E�����G���A�@�〈�`",
	"���́E�����G���A�@���Í`",
	"���́E�����G���A�@��l���`",
	"���́E�����G���A�@�쐣�u��",
	"���́E�����G���A�@�⍪���`",
	"�ԕ�s�G���A�@��z���`",
	"�ԕ�s�G���A�@��z",
	"�ԕ�s�G���A�@���m�@",
	"�ԕ�s�G���A�@���Y",
	"�ԕ�s�G���A�@�Òr",
	"�Ɠ��G���A�@�Ɠ�"
	);
$kanpari_location[3] = array( "�I�����Ă�������",
	"�L���G���A�@�c�����`",
	"�L���G���A�@�Ë��R�`",
	"�L���G���A�@�|��`",
	"�����G���A�@�ĎR�`",
	"�����G���A�@���Z���`",
	"�����G���A�@���Z���`",
	"�����G���A�@���l�`",
	"�����G���A�@�]��",
	"�V����G���A�@�O��",
	"�V����G���A�@�l��`",
	"�V����G���A�@����`",
	"�V����G���A�@���g�`"
	);
$kanpari_location[4] = array( "�I�����Ă�������",
	"�a�̎R�s�k���G���A�@�郖�� ",
	"�a�̎R�s�k���G���A�@�������` ",
	"�a�̎R�s�k���G���A�@��m�Y ",
	"�a�̎R�s�k���G���A�@�a�̎R�k�`������� ",
	"�a�̎R�s�k���G���A�@�I�m��͌�",
	"�a�̎R�s�암�G���A�@�G��� ",
	"�a�̎R�s�암�G���A�@�c�싙�`",
	"�a�̎R�s�암�G���A�@�a�̉Y���` ",
	"�a�̎R�s�암�G���A�@�a�̐�͌�",
	"�a�̎R�s�암�G���A�@�a�̎R�}���[�i�V�e�B ",
	"�C��G���A�@�ˍ�E���Ë��` ",
	"�C��G���A�@������V���c�s�A�[�����h",
	"�C��G���A�@��������� ",
	"�L�c�G���A�@���m�� �n�m�� ",
	"�L�c�G���A�@�L�c��ꕶ�� ",
	"�L�c�G���A�@��Ђ��` ",
	"�L�c�G���A�@���䋙�` ",
	"�L�c�G���A�@��c���`",
	"����E�L��G���A�@�c�����` ",
	"����E�L��G���A�@�������`",
	"����E�L��G���A�@����L�`",
	"����E�L��G���A�@�������` ",
	"�R�ǖk���G���A�@�O���싙�` ",
	"�R�ǖk���G���A�@�ߓދ��` ",
	"�R�ǖk���G���A�@�˒È�E�������` ",
	"�R�Ǔ암�G���A�@������` ",
	"�R�Ǔ암�G���A�@�_�J���` ",
	"�R�Ǔ암�G���A�@�R�ǊC����� ",
	"�R�Ǔ암�G���A�@���D���E�N���g�~",
	"�R�Ǔ암�G���A�@�ԑ�V�g�~",
	"�����G���A�@�����` ",
	"�����G���A�@���Y�E���Y���`",
	"�����G���A�@���Y���` ",
	"�����G���A�@��䋙�` ",
	"�����G���A�@�Y�����`",
	"�����G���A�@�������` ",
	"�����G���A�@�c�Y���`",
	"���l�G���A�@�O�����`",
	"���l�G���A�@������",
	"���l�G���A�@�������l",
	"���l�G���A�@�_�m�����`",
	"��V�G���A�@�쉖�J ",
	"��V�G���A�@�P��ˋ��` ",
	"��V�G���A�@�쓇�E�������`",
	"��V�G���A�@��싙�` ",
	"��V�G���A�@��䋙�` ",
	"���G���A�@�È�̔g�~ ",
	"���G���A�@���` ",
	"���G���A�@�ؖڐ�͌�",
	"�݂Ȃ׃G���A�@��� ",
	"�݂Ȃ׃G���A�@�痢�l  ",
	"�݂Ȃ׃G���A�@�암 ",
	"�݂Ȃ׃G���A�@��E��{�����` ",
	"�c�ӃG���A�@�F�{���` ",
	"�c�ӃG���A�@�ڗǋ��` ",
	"�c�ӃG���A�@�c�ӁE�^���` ",
	"�c�ӃG���A�@��ԍ` ",
	"�c�ӃG���A�@�ՔV�Y�`",
	"���l�k���G���A�@�����l ",
	"���l�k���G���A�@�c�Ӊ���",
	"���l�k���G���A�@���l���� ",
	"���l�k���G���A�@�x�c��͌�",
	"���l�k���G���A�@�������`",
	"���l�k���G���A�@�����A�`",
	"���l�암�G���A�@�s�]�`",
	"���l�암�G���A�@������",
	"���l�암�G���A�@���u��͌�",
	"���l�암�G���A�@�ɌÖ؋��`",
	"�����݃G���A�@�����ݍ`",
	"�����݃G���A�@���a�[",
	"�����݃G���A�@���낵���q�����",
	"�����݃G���A�@���V�Ë��`",
	"�����݃G���A�@�]�Z���`",
	"���{�����G���A�@�a�[�`",
	"���{�����G���A�@���w���`",
	"���{�����G���A�@�c�q�̉Y",
	"���{�����G���A�@�c�����`",
	"���{�����G���A�@�L�c���`",
	"���{�����G���A�@�܍` ��Y���`",
	"���{�����G���A�@����",
	"���{�����G���A�@���{�`",
	"���{�����G���A�@���Y�C������",
	"���{�����G���A�@�Í���͌�",
	"���{�����G���A�@�I�ɑ哇",
	"���{�����G���A�@�c���`",
	"�ߒq���Y�G���A�@�Y�_�p",
	"�ߒq���Y�G���A�@����",
	"�ߒq���Y�G���A�@���n�`",
	"�ߒq���Y�G���A�@���n������l����",
	"�ߒq���Y�G���A�@���Y�`",
	"�ߒq���Y�G���A�@�ߒq��h",
	"�ߒq���Y�G���A�@�F�v��`",
	"�V�{�G���A�@�V�{�`",
	"�V�{�G���A�@�F���͌�"
	);
$kanpari_location[5] = array( "�I�����Ă�������",
	"���O�㐼���G���A�@��",
	"���O�㐼���G���A�@�v���l",
	"���O�㐼���G���A�@����",
	"���O�㐼���G���A�@�[���`",
	"���O�㒆���G���A�@��ΐ�",
	"���O�㒆���G���A�@�Ո��l",
	"���O�㒆���G���A�@����",
	"���O�㒆���G���A�@�Ԑl",
	"���O�㓌���G���A�@�|��",
	"���O�㓌���G���A�@������",
	"���O�㓌���G���A�@���C������",
	"���O�㓌���G���A�@�v�m�E���l",
	"�ɍ��G���A�@����",
	"�ɍ��G���A�@�{���l",
	"�ɍ��G���A�@�V���",
	"�ɍ��G���A�@�ɍ�1",
	"�ɍ��G���A�@�ɍ�2",
	"�ɍ��G���A�@�ɍ�3",
	"�{�Ð����G���A�@�{�V",
	"�{�Ð����G���A�@���]",
	"�{�Ð����G���A�@���u",
	"�{�Ð����G���A�@�]�K",
	"�{�Ð����G���A�@�V����",
	"�{�Ð����G���A�@���q",
	"�{�Ó����G���A�@��E�c��",
	"�{�Ó����G���A�@���̕l",
	"�{�Ó����G���A�@���A",
	"�{�Ó����G���A�@�C�m���",
	"�{�Ó����G���A�@����",
	"�{�Ó����G���A�@�R�ǐ�͌�",
	"���ߐ����G���A�@�˓�",
	"���ߐ����G���A�@����",
	"���ߐ����G���A�@��",
	"���ߐ����G���A�@��N",
	"���ߐ����G���A�@�쑽",
	"���ߓ����G���A�@������",
	"���ߓ����G���A�@�O�{���@",
	"���ߓ����G���A�@���",
	"���ߓ����G���A�@����",
	"���ߓ����G���A�@�쌴",
	"���ߓ����G���A�@�c��",
	"���ߓ����G���A�@�����Y"
	);
$kanpari_location[6] = array( "�I�����Ă�������",
	"���l�k���G���A�@�㐣",
	"���l�k���G���A�@����",
	"���l�k���G���A�@�{��",
	"���l�k���G���A�@���C��g�~",
	"���l�k���G���A�@���C�w�Z��",
	"���l�암�G���A�@�����с`��g�]",
	"���l�암�G���A�@���O��",
	"���l�암�G���A�@���l",
	"���l�암�G���A�@�a�c",
	"�������G���A�@�ԏ�",
	"�������G���A�@�����l",
	"�������G���A�@�����`�{��",
	"���l�G���A�@���",
	"���l�G���A�@���l�`",
	"���l�G���A�@�F�v",
	"���l�G���A�@���[�`���F",
	"���l�G���A�@�u�ρ`���",
	"���l�G���A�@�c�G�`�ޕP",
	"�ዷ�G���A�@�H��",
	"�ዷ�G���A�@���v��",
	"�ዷ�G���A�@����z",
	"�ዷ�G���A�@�V�q",
	"�ዷ�G���A�@����",
	"�ዷ�G���A�@�_�q",
	"�ዷ�G���A�@��_",
	"���l�G���A�@����",
	"���l�G���A�@����",
	"���l�G���A�@�v�X�q",
	"���l�G���A�@��K",
	"���l�G���A�@���l",
	"���l�G���A�@�����l",
	"���l�G���A�@�O��",
	"�։ꐼ���G���A�@����",
	"�։ꐼ���G���A�@����",
	"�։ꐼ���G���A�@�Y��",
	"�։ꐼ���G���A�@��̉Y",
	"�։ꐼ���G���A�@�B�`���q",
	"�։꓌���G���A�@�C��̏���",
	"�։꓌���G���A�@�։�`",
	"�։꓌���G���A�@�։�V�`",
	"�։꓌���G���A�@�ԍ�"
	);
$kanpari_location[7] = array( "�I�����Ă�������",
	"�����`�≮�G���A�@�����̉Y",
	"�����`�≮�G���A�@����",
	"�����`�≮�G���A�@�≮",
	"�����`�≮�G���A�@��a������",
	"�L��`�Y�G���A�@���`",
	"�L��`�Y�G���A�@���",
	"�L��`�Y�G���A�@�Y",
	"�L��`�Y�G���A�@�Y�T���r�[�`",
	"�v�����`�����G���A�@�v����",
	"�v�����`�����G���A�@����",
	"�v�����`�����G���A�@��c",
	"����`�����G���A�@����",
	"����`�����G���A�@����",
	"����`�����G���A�@�u�}",
	"����`�����G���A�@���̂��눤�����h����",
	"����`�����G���A�@����",
	"�����`�F�{�G���A�@�����Y",
	"�����`�F�{�G���A�@���l",
	"�����`�F�{�G���A�@���̑�t",
	"�����`�F�{�G���A�@�x��",
	"�����`�F�{�G���A�@�F�{",
	"�����`�F�{�G���A�@�Ö΍]",
	"�R�ǃG���A�@�V���",
	"�R�ǃG���A�@�R��",
	"�R�ǃG���A�@���΍�",
	"��G���A�@����",
	"��G���A�@���",
	"��G���A�@�y��",
	"��G���A�@����",
	"�m���`�c�K�G���A�@�m��",
	"�m���`�c�K�G���A�@�ۓc",
	"�m���`�c�K�G���A�@������K",
	"�m���`�c�K�G���A�@����l",
	"�m���`�c�K�G���A�@���o��",
	"�m���`�c�K�G���A�@�c�K",
	"���ǁ`�ێR�G���A�@���ǘp",
	"���ǁ`�ێR�G���A�@����",
	"���ǁ`�ێR�G���A�@�ɔ�",
	"���ǁ`�ێR�G���A�@���߉�",
	"���ǁ`�ێR�G���A�@�؏�",
	"���ǁ`�ێR�G���A�@�ێR",
	"�È�`�D���G���A�@�È�",
	"�È�`�D���G���A�@��",
	"�È�`�D���G���A�@�c�쏼��",
	"�È�`�D���G���A�@�ܐF�̕l",
	"�È�`�D���G���A�@����",
	"�È�`�D���G���A�@�D��",
	"�s�u�`���_�G���A�@�s�u",
	"�s�u�`���_�G���A�@�ܓl��",
	"�s�u�`���_�G���A�@���_",
	"�]��`����G���A�@�]��",
	"�]��`����G���A�@����",
	"�]��`����G���A�@�Q��",
	"�]��`����G���A�@����",
	"�]��`����G���A�@�͖�",
	"���Á`�x���G���A�@����",
	"���Á`�x���G���A�@��g",
	"���Á`�x���G���A�@�l�̓�",
	"���Á`�x���G���A�@�x��",
	"寉Y�`�]��G���A�@寉Y",
	"寉Y�`�]��G���A�@���",
	"寉Y�`�]��G���A�@����",
	"寉Y�`�]��G���A�@�]��"
	);
$kanpari_weather = array( "�I�����Ă�������", "����", "�܂�", "�J", "��" );
$kanpari_temperature = array( "�I�����Ă�������", "����", "��⏋��", "���K", "��⊦��", "����" );
$kanpari_tide = array( "�I�����Ă�������", "�咪", "����", "����", "����", "�ᒪ" );
$kanpari_timezone = array( "�I�����Ă�������", "��", "��", "�[��", "��" );

/***********************************************************
* form
***********************************************************/
add_action('template_redirect', 'my_template_redirect');
function my_template_redirect(){
	global $post, $usces;
	if( !is_page() || KANPARI_TOKOFORM != $post->ID )
		return;

	if( !usces_is_login() ) {
		$tokoform = 'postform_top.php';
		if( file_exists(get_stylesheet_directory() . '/wc_templates/member/wc_login_page.php') ){
			include(get_stylesheet_directory() . '/wc_templates/member/wc_login_page.php');
			exit;
		}
	}

	$template_dir = get_stylesheet_directory(). '/';
	if( !file_exists($template_dir . 'postform_top.php') 
		|| !file_exists($template_dir . 'postform.php')
		|| !file_exists($template_dir . 'postform_confirm.php')
		|| !file_exists($template_dir . 'postform_complete.php') )
		return;

	$data = array();
	$area = isset($_REQUEST['area']) ? $_REQUEST['area'] : '';
	$action = isset($_REQUEST['entry_action']) ? $_REQUEST['entry_action'] : '';
	$error_message = my_check_post( $data, $action );

	switch( $action ){
		case 'confirm':
			if( $error_message ){
				include($template_dir . 'postform.php');
			}else{
				if( !my_is_iphone() ) my_file_uploads( $data );
				include($template_dir . 'postform_confirm.php');
			}
			exit;
			break;
		case 'send':
			if( $error_message ){
				include($template_dir . 'postform.php');
			}else{
				if( my_sendmail( $data ) ){
					my_reg_postform( $data );
					include($template_dir . 'postform_complete.php');
				}else{
					include($template_dir . 'postform_senderror.php');
				}
			}
			exit;
			break;
		case 'edit':
		case 'form':
			$member = $usces->get_member();
			if( empty($data['name1']) ) $data['name1'] = $member['name1'];
			if( empty($data['name2']) ) $data['name2'] = $member['name2'];
			if( empty($data['email']) ) $data['email'] = $member['mailaddress1'];
			if( empty($data['zipcode']) ) $data['zipcode'] = $member['zipcode'];
			if( empty($data['pref']) ) $data['pref'] = $member['pref'];
			if( empty($data['address1']) ) $data['address1'] = $member['address1'];
			if( empty($data['address2']) ) $data['address2'] = $member['address2'];
			if( empty($data['address3']) ) $data['address3'] = $member['address3'];
			include($template_dir . 'postform.php');
			exit;
			break;
		default:
			$error_message = '';
			include($template_dir . 'postform_top.php');
			exit;
	}
}

function my_check_post( &$data, $action ){
	$message = '';
	$pre = "<li>";
	$end = "</li>\n";

	$data['name1'] = isset($_POST['name1']) ? trim($_POST['name1']) : '';
	$data['name2'] = isset($_POST['name2']) ? trim($_POST['name2']) : '';
	$data['handle'] = isset($_POST['handle']) ? trim($_POST['handle']) : '';
	$data['email'] = isset($_POST['email']) ? trim($_POST['email']) : '';
	$data['zipcode'] = isset($_POST['zipcode']) ? trim($_POST['zipcode']) : '';
	$data['pref'] = isset($_POST['pref']) ? trim($_POST['pref']) : '';
	$data['address1'] = isset($_POST['address1']) ? trim($_POST['address1']) : '';
	$data['address2'] = isset($_POST['address2']) ? trim($_POST['address2']) : '';
	$data['address3'] = isset($_POST['address3']) ? trim($_POST['address3']) : '';
	$data['area'] = isset($_POST['area']) ? trim($_POST['area']) : '';
	$data['location'] = isset($_POST['location']) ? trim($_POST['location']) : '';
	$data['fishingdate'] = isset($_POST['fishingdate']) ? trim($_POST['fishingdate']) : '';
	$data['weather'] = isset($_POST['weather']) ? trim($_POST['weather']) : '';
	$data['temperature'] = isset($_POST['temperature']) ? trim($_POST['temperature']) : '';
	$data['tide'] = isset($_POST['tide']) ? trim($_POST['tide']) : '';
	$data['timezone'] = isset($_POST['timezone']) ? trim($_POST['timezone']) : '';
	$data['style'] = isset($_POST['style']) ? trim($_POST['style']) : '';
	$data['fishing'] = isset($_POST['fishing']) ? trim($_POST['fishing']) : '';
	$data['usetackle'] = isset($_POST['usetackle']) ? trim($_POST['usetackle']) : '';
	$data['comment'] = isset($_POST['comment']) ? trim($_POST['comment']) : '';
	if( my_is_iphone() ) {
		$data['image1'] = '';
		$data['image2'] = '';
	} else {
		if( $action == 'confirm' ) {
			$data['image1'] = isset($_FILES['image1']['name']) ? trim($_FILES['image1']['name']) : '';
			$data['image2'] = isset($_FILES['image2']['name']) ? trim($_FILES['image2']['name']) : '';
		} else {
			$data['image1'] = isset($_POST['image1']) ? trim($_POST['image1']) : '';
			$data['image2'] = isset($_POST['image2']) ? trim($_POST['image2']) : '';
		}
	}

	if( $action == 'confirm' ) {
		if( '' == $data['name1'] || '' == $data['name2'] ){
			$message .= $pre.'�����O����͂��Ă��������B'.$end;
		}
		if( empty( $data['email'] ) ){
			$message .= $pre.'���[���A�h���X����͂��Ă��������B'.$end;
		}else if( !is_email( $data['email'] ) ){
			$message .= $pre.'���[���A�h���X���s���ł��B'.$end;
		}
		if( empty( $data['zipcode'] ) ){
			$message .= $pre.'�X�֔ԍ�����͂��Ă��������B'.$end;
		}else if( !preg_match('/^[0-9]{3}\-[0-9]{4}$/', $data['zipcode']) ){
			$message .= $pre.'�X�֔ԍ����s���ł��B'.$end;
		}
		if( '--�I��--' == $data['pref'] ){
			$message .= $pre.'�s���{����I�����Ă��������B'.$end;
		}
		if( '' == $data['address1'] ){
			$message .= $pre.'�s�撬���ȉ�����͂��Ă��������B'.$end;
		}
		if( '' == $data['address2'] ){
			$message .= $pre.'�Ԓn����͂��Ă��������B'.$end;
		}
		if( '�I�����Ă�������' == $data['location'] ){
			$message .= $pre.'�ލs�ꏊ��I�����Ă��������B'.$end;
		}
		if( '' == $data['fishingdate'] ){
			$message .= $pre.'�ލs������͂��Ă��������B'.$end;
		}
		if( '�I�����Ă�������' == $data['weather'] ){
			$message .= $pre.'�V�C��I�����Ă��������B'.$end;
		}
		if( '�I�����Ă�������' == $data['temperature'] ){
			$message .= $pre.'�C����I�����Ă��������B'.$end;
		}
		if( '�I�����Ă�������' == $data['timezone'] ){
			$message .= $pre.'���ԑт�I�����Ă��������B'.$end;
		}
		if( '' == $data['style'] ){
			$message .= $pre.'�ނ������͂��Ă��������B'.$end;
		}
		if( '' == $data['fishing'] ){
			$message .= $pre.'�މʂ���͂��Ă��������B'.$end;
		}
		if( '' == $data['comment'] ){
			$message .= $pre.'�ލs���|�[�g����͂��Ă��������B'.$end;
		}
		if( !my_is_iphone() ) {
			if( '' == $data['image1'] ){
				$message .= $pre.'�މʉ摜01��I�����Ă��������B'.$end;
			}else if( !my_image_type_check( $_FILES['image1']['name'] ) ) {
				$message .= $pre.'�މʉ摜01���s���ł��B�w��ł���摜�̎�ނ́wJPG�x�wGIF�x�wPNG�x�݂̂ł��B'.$end;
			}else if( !my_image_size_check( $_FILES['image1']['name'], $_FILES['image1']['size'] ) ) {
				$message .= $pre.'�މʉ摜01���傫�����܂��B4MB�܂ł̉摜���w�肵�Ă��������B'.$end;
			}
			if( '' == $data['image2'] ){
				$message .= $pre.'�މʉ摜02��I�����Ă��������B'.$end;
			}else if( !my_image_type_check( $_FILES['image2']['name'] ) ) {
				$message .= $pre.'�މʉ摜02���s���ł��B�w��ł���摜�̎�ނ́wJPG�x�wGIF�x�wPNG�x�݂̂ł��B'.$end;
			}else if( !my_image_size_check( $_FILES['image2']['name'], $_FILES['image2']['size'] ) ) {
				$message .= $pre.'�މʉ摜02���傫�����܂��B4MB�܂ł̉摜���w�肵�Ă��������B'.$end;
			}
			if( '' != $data['image1'] && '' == $data['image2'] && $data['image1'] == $data['image2'] ){
				$message .= $pre.'�މʉ摜01�ƒމʉ摜02�͕ʂ̉摜��I�����Ă��������B'.$end;
			}
		}
	}
	return $message;
}

function my_change_br( $str ) {

	$text = htmlspecialchars( $str );
	if( get_magic_quotes_gpc() ) {
		$text = stripslashes( $text );
	}
	$text = nl2br($text);
	return $text;
}

function my_image_type_check( $name ) {
	$res = true;
	$allowedExtensions = array("jpg","jpeg","gif","png");
	if( $name > '') {
		$res = in_array(end(explode(".", strtolower($name))), $allowedExtensions);
	}
	return $res;
}

function my_image_size_check( $name, $size ) {
	$res = true;
	if( $name > '') {
		if( $size > (4*1024*1024) )
			$res = false;
    }
	return $res;
}

function my_file_uploads( &$data ) {
	global $usces;

	$member = $usces->get_member();
	$uploads_dir = WP_CONTENT_DIR.'/uploads/kanpari/'.$member['ID'];
	$uploads_url = WP_CONTENT_URL.'/uploads/kanpari/'.$member['ID'];
	if( !is_dir($uploads_dir) ) {
		mkdir($uploads_dir);
	}

	if( isset($_FILES["image1"]["tmp_name"]) ) {
		$tmp_name1 = $_FILES["image1"]["tmp_name"];
		$name1 = $uploads_dir.'/'.$_FILES["image1"]["name"];
		$name1_url = $uploads_url.'/'.$_FILES["image1"]["name"];
		@move_uploaded_file($tmp_name1, $name1);
		@chmod( $name1, 0400 );
		$data['display_image1'] = '<img src="'.$name1_url.'" alt="'.esc_html($data['image1']).'" height="100" />';
	}

	if( isset($_FILES["image2"]["tmp_name"]) ) {
		$tmp_name2 = $_FILES["image2"]["tmp_name"];
		$name2 = $uploads_dir.'/'.$_FILES["image2"]["name"];
		$name2_url = $uploads_url.'/'.$_FILES["image2"]["name"];
		@move_uploaded_file($tmp_name2, $name2);
		@chmod( $name2, 0400 );
		$data['display_image2'] = '<img src="'.$name2_url.'" alt="'.esc_html($data['image2']).'" height="100" />';
	}
}

function my_sendmail( $data ){
	global $usces;

	$name = $data['name1'].$data['name2'];
	$mail_data = $usces->options['mail_data'];
	$member = $usces->get_member();

	$res = false;
	$mes_head  = "���̃��[���͎������M����Ă��܂��B" ."\r\n";
	$mes_head .= "-------------------------------------------------------" ."\r\n\r\n";
	$mes_head .= $name . " �l" ."\r\n\r\n";
	$mes_head .= "���̓x�́A�މʓ��e���肪�Ƃ��������܂��B" ."\r\n\r\n\r\n";
	$mes_head .= "���L�̓��e�Ŏ󂯕t���܂����B" ."\r\n";
	$mes_body  = my_get_mes_body($data);
	$mes_foot  = '���߂ă��[���ł��A���������܂��̂ł��΂炭���҂����������B' ."\r\n\r\n";
	$mes_foot .= '����Ƃ�'.$usces->options['company_name'].'����낵�����肢�\���グ�܂��B' ."\r\n\r\n\r\n";
	$mes_foot .= '�����̃��[���ɂ��S������̂Ȃ����q�l�́A���萔�ł���' ."\r\n";
	$mes_foot .= '���L�̂��⍇�����育�A���������܂��悤���肢�v���܂��B' ."\r\n\r\n\r\n";
	$mes_foot .= $mail_data['footer']['othermail'];//���[���ݒ聨���̑��̃t�b�^

	$entry_name = $name;
	$entry_mailaddress = $data['email'];
	$subject2applicant = "(".$member['ID'].")".$name."�l����̒މʓ��e�����̊m�F";//�����������

	$admin_name = $usces->options['company_name'];
	$admin_mailaddress = $usces->options['sender_mail'];
	$subject2admin = "(".$member['ID'].")".$name."�l����̒މʓ��e�ʒm";//�Ǘ��Ҍ�������

	$para2applicant = array(
			'to_name' => $entry_name,
			'to_address' => $entry_mailaddress, 
			'from_name' => $admin_name,
			'from_address' => $admin_mailaddress,
			'return_path' => $admin_mailaddress,
			'subject' => $subject2applicant,
			'message' => $mes_head . $mes_body . $mes_foot
			);
	$entry_res = my_send_mail( $para2applicant );

	if ( $entry_res ) {
		$mes_head = $name . " �l���މʓ��e������܂����B" ."\r\n\r\n";
		$para2admin = array(
				'to_name' => $admin_name,
				'to_address' => $admin_mailaddress, 
				'from_name' => $entry_name . ' �l',
				'from_address' => $entry_mailaddress,
				'return_path' => $admin_mailaddress,
				'subject' => $subject2admin,
				'message' => $mes_head . $mes_body . $mes_foot
				);
		$member = $usces->get_member();
		if( my_is_iphone() ) {
			$attachments = array();
		} else {
			$uploads_dir = WP_CONTENT_DIR.'/uploads/kanpari/'.$member['ID'];
			$attachments = array( $uploads_dir.'/'.$data['image1'], $uploads_dir.'/'.$data['image2'] );
		}
		sleep(1);
		$res = my_send_mail( $para2admin, $attachments );
		if( $res ) {
			my_remove_dir( $uploads_dir, true );
		}
	}

	return $res;
}

function my_remove_dir($dir, $deleteMe) {
	if( !$dh = @opendir($dir) ) return;
	while( false !== ($obj = readdir($dh)) ) {
		if( $obj == '.' || $obj == '..' ) continue;
		if( !@unlink($dir.'/'.$obj) ) my_remove_dir( $dir.'/'.$obj, true );
	}

	closedir($dh);
	if($deleteMe) {
		@rmdir($dir);
	}
}

function my_reg_postform( $data ){
	global $wpdb, $usces;
	global $kanpari_area;

	$member = $usces->get_member();
	$table_name = $wpdb->prefix."usces_postform";
	$query = $wpdb->prepare(
		"INSERT INTO $table_name (
			`mem_id`, `kpf_date`, `kpf_handle`, `kpf_area`, `kpf_location`, `kpf_fishingdate`, `kpf_weather`, 
			`kpf_temperature`, `kpf_tide`, `kpf_timezone`, `kpf_style`, `kpf_fishing`, 
			`kpf_usetackle`, `kpf_comment`, `kpf_image1`, `kpf_image2`, `kpf_status` ) 
		VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)", 
			$member['ID'], 
			get_date_from_gmt(gmdate('Y-m-d H:i:s', time())), 
			$data['handle'], 
			$kanpari_area[$data['area']], 
			$data['location'], 
			$data['fishingdate'], 
			$data['weather'], 
			$data['temperature'], 
			$data['tide'], 
			$data['timezone'], 
			$data['style'], 
			$data['fishing'], 
			$data['usetackle'], 
			$data['comment'], 
			$data['image1'], 
			$data['image2'], 
			"���Ή�"
		);
	$res = $wpdb->query( $query );
	return $res;
}

function my_send_mail( $para, $attachments = array() ) {
	//$from_name = mb_encode_mimeheader(mb_convert_encoding($para['from_name'], "JIS", "UTF8"));
	//$from = $from_name . " <{$para['from_address']}>";
	$from = htmlspecialchars(html_entity_decode($para['from_name'], ENT_QUOTES)) . " <{$para['from_address']}>";
	$header = "From: " . $from . "\r\n";
	$header .= "Return-Path: {$para['return_path']}\r\n";

	$subject = html_entity_decode($para['subject'], ENT_QUOTES);
	$message = $para['message'];

	$mails = explode( ',', $para['to_address'] );
	$to_mailes = array();
	foreach( $mails as $mail ){
		if( is_email( trim($mail) ) ){
			$to_mailes[] = $mail;
		}
	}
	if( !empty( $to_mailes ) ){
		$res = @wp_mail( $to_mailes, $subject, $message, $header, $attachments );
	}else{
		$res = false;
	}
	return $res;
}

function my_get_mes_body($data){
	global $kanpari_area;

	$mes = "=========================================" ."\r\n";
	$mes .= "[       �����O       ]  " . $data['name1'].$data['name2'] ."\r\n\r\n";
	$mes .= "[   �n���h���l�[��   ]  " . $data['handle'] ."\r\n\r\n";
	$mes .= "[   ���[���A�h���X   ]  " . $data['email'] ."\r\n\r\n";
	$mes .= "[      �X�֔ԍ�      ]  " . $data['zipcode'] ."\r\n\r\n";
	$mes .= "[      �s���{��      ]  " . $data['pref'] ."\r\n\r\n";
	$mes .= "[     �s��S����     ]  " . $data['address1'] ."\r\n\r\n";
	$mes .= "[        �Ԓn        ]  " . $data['address2'] ."\r\n\r\n";
	$mes .= "[ �}���V�����E�r���� ]  " . $data['address3'] . "\r\n\r\n";
	$mes .= "[     �ލs�G���A     ]  " . $kanpari_area[$data['area']] . "\r\n\r\n";
	$mes .= "[      �ލs�ꏊ      ]  " . $data['location'] . "\r\n\r\n";
	$mes .= "[       �ލs��       ]  " . $data['fishingdate'] . "\r\n\r\n";
	$mes .= "[        �V�C        ]  " . $data['weather'] ."\r\n\r\n";
	$mes .= "[        �C��        ]  " . $data['temperature'] ."\r\n\r\n";
	$mes .= "[         ��         ]  " . $data['tide'] ."\r\n\r\n";
	$mes .= "[       ���ԑ�       ]  " . $data['timezone'] ."\r\n\r\n";
	$mes .= "[       �ނ��       ]  " . $data['style'] ."\r\n\r\n";
	$mes .= "[        �މ�        ]  " . $data['fishing'] ."\r\n\r\n";
	$mes .= "[    �g�p�^�b�N��    ]  " . $data['usetackle'] ."\r\n\r\n";
	$mes .= "[    �ލs���|�[�g    ]  " . $data['comment'] ."\r\n\r\n";
	if( my_is_iphone() ) {
		$mes .= "[      �މʉ摜      ]  ��iPhone�̂��ߕʑ��M\r\n\r\n";
	} else {
		$mes .= "[     �މʉ摜01     ]  " . $data['image1'] ."\r\n\r\n";
		$mes .= "[     �މʉ摜02     ]  " . $data['image2'] ."\r\n\r\n";
	}
	$mes .= "=========================================" ."\r\n\r\n\r\n";

	return $mes;
}

function usces_update_postformdata() {
	global $wpdb;

	$table_name = $wpdb->prefix."usces_postform";
	$ID = (int)$_REQUEST['kpf_id'];
	$query = $wpdb->prepare(
		"UPDATE $table_name SET kpf_point = %d, kpf_status = %s, kpf_note = %s WHERE ID = %d", 
			(int)$_POST['kpf_point'], 
			$_POST['kpf_status'], 
			$_POST['kpf_note'], 
			$ID
		);
	$res = $wpdb->query( $query );

	if( $res ) {
		if( (int)$_POST['kpf_point'] > 0 && (int)$_POST['kpf_point'] != (int)$_POST['kpf_point_before'] ) {
			$member_table_name = $wpdb->prefix."usces_member";
			$mquery = $wpdb->prepare(
				"UPDATE $member_table_name SET mem_point = (mem_point + %d) WHERE ID = %d", 
				(int)$_POST['kpf_point'], 
				$_POST['mem_id']
			);
			$res = $wpdb->query( $mquery );
		}
	}

	return $res;
}

function usces_delete_postformdata( $ID = 0 ) {
	global $wpdb, $usces;

	if( 0 === $ID ) {
		if( !isset($_REQUEST['kpf_id']) || $_REQUEST['kpf_id'] == '' )
			return 0;
		$ID = $_REQUEST['kpf_id'];
	}

	$table_name = $wpdb->prefix."usces_postform";
	$query = $wpdb->prepare( "DELETE FROM $table_name WHERE ID = %d", $ID );
	$res = $wpdb->query( $query );

	return $res;
}

function my_is_iphone() {
	return preg_match( '/iphone/i', $_SERVER['HTTP_USER_AGENT'] ) ||
		preg_match( '/ipad/i', $_SERVER['HTTP_USER_AGENT'] );
}
//kanpari end
?>
