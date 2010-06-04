<?php

class usc_e_shop
{

	var $page;   //page action
	var $cart;          //cart object
	var $use_ssl;       //ssl flag
	var $action, $action_status, $error_status;
	var $action_message, $error_message;
	var $itemskus, $itemsku, $itemopts, $itemopt;
	var $zaiko_status, $payment_structure, $display_mode, $shipping_rule, $shipping_charge_structure;
	var $member_status;
	var $options;
	var $login_mail, $current_member, $member_form;
	var $payment_results, $log_flg;

	function usc_e_shop()
	{
		global $post;
	
		$this->usces_session_start();

		if ( !isset($_SESSION['usces_member']) )
			$_SESSION['usces_member'] = array();

		$this->previous_url = isset($_SESSION['usces_previous_url']) ? $_SESSION['usces_previous_url'] : '';
		if(!isset($_SESSION['usces_checked_business_days'])) $this->update_business_days();
		$this->check_display_mode();
		
		$this->options = get_option('usces');
		if(!isset($this->options['smtp_hostname']) || empty($this->options['smtp_hostname'])){ $this->options['smtp_hostname'] = 'localhost';}
		if(!isset($this->options['divide_item'])) $this->options['divide_item'] = 0;
		if(!isset($this->options['fukugo_category_orderby'])) $this->options['fukugo_category_orderby'] = 'ID';
		if(!isset($this->options['fukugo_category_order'])) $this->options['fukugo_category_order'] = 'ASC';
		if(!isset($this->options['province'])) $this->options['province'] = get_option('usces_pref');
		if(!isset($this->options['membersystem_state'])) $this->options['membersystem_state'] = 'activate';
		if(!isset($this->options['membersystem_point'])) $this->options['membersystem_point'] = 'activate';
		if(!isset($this->options['settlement_path'])) $this->options['settlement_path'] = USCES_PLUGIN_DIR . '/settlement/';
		if(!isset($this->options['use_ssl'])) $this->options['use_ssl'] = 0;
		if(!isset($this->options['indi_item_name'])){
			$this->options['indi_item_name']['item_name'] = 1;
			$this->options['indi_item_name']['item_code'] = 1;
			$this->options['indi_item_name']['sku_name'] = 1;
			$this->options['indi_item_name']['sku_code'] = 1;
			$this->options['pos_item_name']['item_name'] = 1;
			$this->options['pos_item_name']['item_code'] = 2;
			$this->options['pos_item_name']['sku_name'] = 3;
			$this->options['pos_item_name']['sku_code'] = 4;
		}
		update_option('usces', $this->options);

		$this->error_message = '';
		$this->login_mail = '';
		$this->get_current_member();
		$this->page = '';
		$this->payment_results = array();

		//admin_ssl options
//		$this->use_ssl = get_option("admin_ssl_use_ssl") === "1" ? true : false;
//		$use_shared = get_option("admin_ssl_use_shared") === "1" && $this->use_ssl ? true : false;
//		$shared_url = get_option("admin_ssl_shared_url");
		
		$this->use_ssl = $this->options['use_ssl'];
//		if ( $use_shared ) {
//			$ssl_url = str_replace('/wp-admin/', '', $shared_url);
//		} else {
//			$ssl_url = str_replace('http://', 'https://', get_option('home'));
//		}
		if ( $this->use_ssl ) {
			$ssl_url = $this->options['ssl_url'];
			$ssl_url_admin = $this->options['ssl_url_admin'];
			if( $this->is_cart_or_member_page($_SERVER['REQUEST_URI']) ){
				define('USCES_FRONT_PLUGIN_URL', $ssl_url_admin . '/wp-content/plugins/' . USCES_PLUGIN_FOLDER);
			}else{
				define('USCES_FRONT_PLUGIN_URL', USCES_WP_CONTENT_URL . '/plugins/' . USCES_PLUGIN_FOLDER);
			}
			add_filter('page_link', array(&$this, 'usces_ssl_page_link'));
			add_filter('wp_get_attachment_url', array($this, 'usces_ssl_attachment_link'));
			add_filter('stylesheet_directory_uri', array($this, 'usces_ssl_contents_link'));
			add_filter('template_directory_uri', array($this, 'usces_ssl_contents_link'));
			add_filter('script_loader_src', array($this, 'usces_ssl_script_link'));
			add_filter('style_loader_src', array($this, 'usces_ssl_script_link'));
			define('USCES_SSL_URL', $ssl_url);
			define('USCES_SSL_URL_ADMIN', $ssl_url_admin);
			define('USCES_COOKIEPATH', preg_replace('|https?://[^/]+|i', '', $ssl_url . '/' ) );
		}else{
			define('USCES_FRONT_PLUGIN_URL', USCES_WP_CONTENT_URL . '/plugins/' . USCES_PLUGIN_FOLDER);
			define('USCES_SSL_URL', get_option('home'));
			define('USCES_SSL_URL_ADMIN', get_option('siteurl'));
			define('USCES_COOKIEPATH', COOKIEPATH);
		}

		define('USCES_CART_NUMBER', get_option('usces_cart_number'));
		define('USCES_MEMBER_NUMBER', get_option('usces_member_number'));
		if($this->use_ssl) {
			define('USCES_CART_URL', $ssl_url . '/?page_id=' . USCES_CART_NUMBER . '&usces=' . $this->get_uscesid());
			define('USCES_MEMBER_URL', $ssl_url . '/?page_id=' . USCES_MEMBER_NUMBER . '&usces=' . $this->get_uscesid());
		} else {
			define('USCES_CART_URL', get_option('home') . '/?page_id=' . USCES_CART_NUMBER);
			define('USCES_MEMBER_URL', get_option('home') . '/?page_id=' . USCES_MEMBER_NUMBER);
		}
		define('USCES_ITEM_CAT_PARENT_ID', get_option('usces_item_cat_parent_id'));
		
		$this->zaiko_status = get_option('usces_zaiko_status');
		$this->member_status = get_option('usces_customer_status');
		$this->payment_structure = get_option('usces_payment_structure');
		$this->display_mode = get_option('usces_display_mode');
		$this->shipping_rule = get_option('usces_shipping_rule');
		//$this->shipping_charge_structure = get_option('shipping_charge_structure');
		define('USCES_MYSQL_VERSION', (int)substr(mysql_get_server_info(), 0, 1));
		define('USCES_JP', ('ja' == get_locale() ? true : false));
		
	}
	
	function get_default_post_to_edit30( $post_type = 'post', $create_in_db = false ) {
		global $wpdb;
	
		$post_title = '';
		if ( !empty( $_REQUEST['post_title'] ) )
			$post_title = esc_html( stripslashes( $_REQUEST['post_title'] ));
	
		$post_content = '';
		if ( !empty( $_REQUEST['content'] ) )
			$post_content = esc_html( stripslashes( $_REQUEST['content'] ));
	
		$post_excerpt = '';
		if ( !empty( $_REQUEST['excerpt'] ) )
			$post_excerpt = esc_html( stripslashes( $_REQUEST['excerpt'] ));
	
		if ( $create_in_db ) {
			// Cleanup old auto-drafts more than 7 days old
			$old_posts = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_status = 'auto-draft' AND DATE_SUB( NOW(), INTERVAL 7 DAY ) > post_date" );
			foreach ( (array) $old_posts as $delete )
				wp_delete_post( $delete, true ); // Force delete
			$post = get_post( wp_insert_post( array( 'post_title' => __( 'Auto Draft' ), 'post_type' => $post_type, 'post_status' => 'auto-draft' ) ) );
		} else {
			$post->ID = 0;
			$post->post_author = '';
			$post->post_date = '';
			$post->post_date_gmt = '';
			$post->post_password = '';
			$post->post_type = $post_type;
			$post->post_status = 'draft';
			$post->to_ping = '';
			$post->pinged = '';
			$post->comment_status = get_option( 'default_comment_status' );
			$post->ping_status = get_option( 'default_ping_status' );
			$post->post_pingback = get_option( 'default_pingback_flag' );
			$post->post_category = get_option( 'default_category' );
			$post->page_template = 'default';
			$post->post_parent = 0;
			$post->menu_order = 0;
		}
	
		$post->post_content = apply_filters( 'default_content', $post_content, $post );
		$post->post_title   = apply_filters( 'default_title',   $post_title, $post   );
		$post->post_excerpt = apply_filters( 'default_excerpt', $post_excerpt, $post );
		$post->post_name = '';
	
		return $post;
	}

	function get_default_post_to_edit() {
		global $post;
		
		$post_title = '';
		if ( !empty( $_REQUEST['post_title'] ) )
			$post_title = esc_html( stripslashes( $_REQUEST['post_title'] ));
	
		$post_content = '';
		if ( !empty( $_REQUEST['content'] ) )
			$post_content = esc_html( stripslashes( $_REQUEST['content'] ));
	
		$post_excerpt = '';
		if ( !empty( $_REQUEST['excerpt'] ) )
			$post_excerpt = esc_html( stripslashes( $_REQUEST['excerpt'] ));
	
		$post->ID = 0;
		$post->post_name = '';
		$post->post_author = '';
		$post->post_date = '';
		$post->post_date_gmt = '';
		$post->post_password = '';
		$post->post_status = 'draft';
		$post->post_type = 'post';
		$post->to_ping = '';
		$post->pinged = '';
		$post->comment_status = get_option( 'default_comment_status' );
		$post->ping_status = get_option( 'default_ping_status' );
		$post->post_pingback = get_option( 'default_pingback_flag' );
		$post->post_category = get_option( 'default_category' );
		$post->post_content = apply_filters( 'default_content', $post_content);
		$post->post_title = apply_filters( 'default_title', $post_title );
		$post->post_excerpt = apply_filters( 'default_excerpt', $post_excerpt);
		$post->page_template = 'default';
		$post->post_parent = 0;
		$post->menu_order = 0;
	
		return $post;
	}
	function is_cart_or_member_page($link)
	{
		$search = array(('page_id='.USCES_CART_NUMBER), '/usces-cart', ('page_id='.USCES_MEMBER_NUMBER), '/usces-member');
		$flag = false;
		foreach($search as $value){
			if( strpos($link, $value) )
				$flag = true;
		}
		return $flag;
	}
	
	function usces_ssl_page_link($link)
	{
//		if( $this->is_cart_or_member_page($_SERVER['REQUEST_URI']) ){
//			$fronts = parse_url(get_option('home'));
//			$homes = parse_url(USCES_SSL_URL);
//			if(empty($fronts['scheme'])){
//				$frontsscheme = 'http://';
//			}else{
//				$frontsscheme = $fronts['scheme'].'://';
//			}
//			if(empty($homes['scheme'])){
//				$homesscheme = 'https://';
//			}else{
//				$homesscheme = $homes['scheme'].'://';
//			}
//			$site = str_replace($frontsscheme, '', get_option('home'));
//			$sslsite = str_replace($homesscheme, '', USCES_SSL_URL);
//			$link = str_replace($site, $sslsite, $link);
//			$link = str_replace('http://', 'https://', $link);
			if( strpos($link, '/usces-cart') || strpos($link, 'page_id='.USCES_CART_NUMBER) )
				$link = USCES_CART_URL;
			if( strpos($link, '/usces-member') || strpos($link, 'page_id='.USCES_MEMBER_NUMBER) )
				$link = USCES_MEMBER_URL;
				
//			$link = str_replace('/usces-cart', ('/?page_id='.USCES_CART_NUMBER), $link);
//			$link = str_replace('/usces-member', ('/?page_id='.USCES_MEMBER_NUMBER), $link);
				
//		}
		return $link;
	}

	function usces_ssl_contents_link($link)
	{
		if( $this->is_cart_or_member_page($_SERVER['REQUEST_URI']) ){
			$req = explode('/wp-content/',$link);
			$link = USCES_SSL_URL_ADMIN . '/wp-content/' . $req[1];
		}
		return $link;
	}

	function usces_ssl_attachment_link($link)
	{
		if( $this->is_cart_or_member_page($_SERVER['REQUEST_URI']) ){
			$link = str_replace(get_option('siteurl'), USCES_SSL_URL_ADMIN, $link);
		}
		return $link;
	}

	function usces_ssl_script_link($link)
	{
		if( $this->is_cart_or_member_page($_SERVER['REQUEST_URI']) ){
			if(strpos($link, '/wp-content/') !== false){
				$req = explode('/wp-content/',$link, 2);
				$link = USCES_SSL_URL_ADMIN . '/wp-content/' . $req[1];
			}else if(strpos($link, '/wp-includes/') !== false){
				$req = explode('/wp-includes/',$link, 2);
				$link = USCES_SSL_URL_ADMIN . '/wp-includes/' . $req[1];
			}else if(strpos($link, '/wp-admin/') !== false){
				$req = explode('/wp-admin/',$link, 2);
				$link = USCES_SSL_URL_ADMIN . '/wp-admin/' . $req[1];
			}
		}
		return $link;
	}

	function set_action_status($status, $message)
	{
		$this->action_status = $status;
		$this->action_message = $message;
	}


	/******************************************************************************/
	function add_pages() {

	
		add_object_page('Welcart Shop', 'Welcart Shop', 10, USCES_PLUGIN_BASENAME, array($this, 'admin_top_page'));
		add_submenu_page(USCES_PLUGIN_BASENAME, __('Home','usces'), __('Home','usces'), 10, USCES_PLUGIN_BASENAME, array($this, 'admin_top_page'));
		add_submenu_page(USCES_PLUGIN_BASENAME, __('Master Items','usces'), __('Master Items','usces'), 10, 'usces_itemedit', array($this, 'item_master_page'));
		add_submenu_page(USCES_PLUGIN_BASENAME, __('Add New Item','usces'), __('Add New Item','usces'), 10, 'usces_itemnew', array($this, 'item_master_page'));
		add_submenu_page(USCES_PLUGIN_BASENAME, __('General Setting','usces'), __('General Setting','usces'), 10, 'usces_initial', array($this, 'admin_setup_page'));
		add_submenu_page(USCES_PLUGIN_BASENAME, __('Business Days Setting','usces'), __('Business Days Setting','usces'), 10, 'usces_schedule', array($this, 'admin_schedule_page'));
		add_submenu_page(USCES_PLUGIN_BASENAME, __('Shipping Setting','usces'), __('Shipping Setting','usces'), 10, 'usces_delivery', array($this, 'admin_delivery_page'));
		add_submenu_page(USCES_PLUGIN_BASENAME, __('E-mail Setting','usces'), __('E-mail Setting','usces'), 10, 'usces_mail', array($this, 'admin_mail_page'));
		add_submenu_page(USCES_PLUGIN_BASENAME, __('Cart Page Setting','usces'), __('Cart Page Setting','usces'), 10, 'usces_cart', array($this, 'admin_cart_page'));
		add_submenu_page(USCES_PLUGIN_BASENAME, __('Member Page Setting','usces'), __('Member Page Setting','usces'), 10, 'usces_member', array($this, 'admin_member_page'));
		add_submenu_page(USCES_PLUGIN_BASENAME, __('System Setting','usces'), __('System Setting','usces'), 10, 'usces_system', array($this, 'admin_system_page'));
		//add_submenu_page(USCES_PLUGIN_BASENAME, __('Backup','usces'), __('Backup','usces'), 6, 'usces_backup', array($this, 'admin_backup_page'));
		
		add_object_page('Welcart Management', 'Welcart Management', 10, 'usces_orderlist', array($this, 'order_list_page'));
		add_submenu_page('usces_orderlist', __('Order List','usces'), __('Order List','usces'), 10, 'usces_orderlist', array($this, 'order_list_page'));
		add_submenu_page('usces_orderlist', __('New Order or Estimate','usces'), __('New Order or Estimate','usces'), 10, 'usces_ordernew', array($this, 'order_list_page'));
		add_submenu_page('usces_orderlist', __('List of Members','usces'), __('List of Members','usces'), 10, 'usces_memberlist', array($this, 'member_list_page'));
	}


	/* Item Master Page */
	function item_master_page() {
		global $wpdb, $wp_locale;
		global $wp_query;
		
		if(empty($this->action_message) || $this->action_message == '') {
			$this->action_status = 'none';
			$this->action_message = '';
		}
		
		if($_REQUEST['page'] == 'usces_itemnew'){
			$action = 'new';
		}else{
			$action = $_REQUEST['action'];
		}

		switch ( $action ) {
			case 'delete':
			case 'new':
			case 'editpost':
			case 'edit':
				global $current_user;
				require_once(USCES_PLUGIN_DIR . '/includes/usces_item_master_edit.php');
				break;
			default:
				require_once(USCES_PLUGIN_DIR . '/includes/usces_item_master_list.php');
				break;
		}
	}
	
	/* order list page */
	function order_list_page() {

		if(empty($this->action_message) || $this->action_message == '') {
			$this->action_status = 'none';
			$this->action_message = '';
		}
		if($_REQUEST['page'] == 'usces_ordernew'){
			$order_action = 'new';
		}else{
			$order_action = $_REQUEST['order_action'];
		}
		switch ($order_action) {
			case 'printpdf':
				require_once(USCES_PLUGIN_DIR . '/includes/order_print.php');	
				break;
			case 'editpost':
				do_action('usces_pre_update_orderdata', $_REQUEST['order_id']);
				$res = usces_update_orderdata();
				if ( 1 === $res ) {
					do_action('usces_after_update_orderdata', $_REQUEST['order_id']);
					$this->set_action_status('success', __('order date is updated','usces').' <a href="'.stripslashes( $_POST['usces_referer'] ).'">'.__('back to the summary','usces').'</a>');
				} elseif ( 0 === $res ) {
					$this->set_action_status('none', '');
				} else {
					$this->set_action_status('error', 'ERROR：'.__('failure in update','usces'));
				}
				require_once(USCES_PLUGIN_DIR . '/includes/order_edit_form.php');	
				break;
			case 'newpost':
				do_action('usces_pre_new_orderdata');
				$res = usces_new_orderdata();
				if ( 1 === $res ) {
					do_action('usces_after_new_orderdata');
					$this->set_action_status('success', __('New date is add','usces'));
				} elseif ( 0 === $res ) {
					$this->set_action_status('none', '');
				} else {
					$this->set_action_status('error', 'ERROR：'.__('failure in addition','usces'));
				}
				$_REQUEST['order_action'] = 'edit';
				$order_action = $_REQUEST['order_action'];
				require_once(USCES_PLUGIN_DIR . '/includes/order_edit_form.php');	
				break;
			case 'new':
			case 'edit':
				require_once(USCES_PLUGIN_DIR . '/includes/order_edit_form.php');	
				break;
			case 'delete':
				do_action('usces_pre_delete_orderdata', $_REQUEST['order_id']);
				$res = usces_delete_orderdata();
				if ( 1 === $res ) {
					do_action('usces_after_delete_orderdata', $_REQUEST['order_id']);
					$this->set_action_status('success', __('the order date is deleted','usces'));
				} elseif ( 0 === $res ) {
					$this->set_action_status('none', '');
				} else {
					$this->set_action_status('error', 'ERROR：'.__('failure in delete','usces'));
				}
			default:
				require_once(USCES_PLUGIN_DIR . '/includes/order_list.php');	
		}
	}
	
	/* member list page */
	function member_list_page() {

		if(empty($this->action_message) || $this->action_message == '') {
			$this->action_status = 'none';
			$this->action_message = '';
		}
		$member_action = $_REQUEST['member_action'];
		switch ($member_action) {
			case 'editpost':
				$this->error_message = $this->admin_member_check();
				if($this->error_message == ''){
					$res = usces_update_memberdata();
					if ( 1 === $res ) {
						$this->set_action_status('success', __('Membership information is updated','usces'));
					} elseif ( 0 === $res ) {
						$this->set_action_status('none', '');
					} else {
						$this->set_action_status('error', 'ERROR：'.__('failure in update','usces'));
					}
				}
				require_once(USCES_PLUGIN_DIR . '/includes/member_edit_form.php');	
				break;
			case 'edit':
				require_once(USCES_PLUGIN_DIR . '/includes/member_edit_form.php');	
				break;
			case 'delete':
				$res = usces_delete_memberdata();
				if ( 1 === $res ) {
					$this->set_action_status('success', __('the order date is deleted','usces'));
				} elseif ( 0 === $res ) {
					$this->set_action_status('none', '');
				} else {
					$this->set_action_status('error', 'ERROR：'.__('failure in delete','usces'));
				}
			default:
				require_once(USCES_PLUGIN_DIR . '/includes/member_list.php');	
		}

	}
	
	/* admin backup page */
	function admin_backup_page() {

		if(empty($this->action_message) || $this->action_message == '') {
			$this->action_status = 'none';
			$this->action_message = '';
		}
		require_once(USCES_PLUGIN_DIR . '/includes/admin_backup.php');	

	}
	
	/* Shop Setup Page */
	function admin_top_page() {

		require_once(USCES_PLUGIN_DIR . '/includes/admin_top.php');	

	}
	
	/* Shop Setup Page */
	function admin_setup_page() {
	
		$this->options = get_option('usces');
		//$this->options = array();

		if(isset($_POST['usces_option_update'])) {
			$this->options['display_mode'] = isset($_POST['display_mode']) ? wp_specialchars($_POST['display_mode']) : '';
			$this->options['campaign_category'] = isset($_POST['cat']) ? $_POST['cat'] : '0';
			$this->options['campaign_privilege'] = isset($_POST['cat_privilege']) ? wp_specialchars($_POST['cat_privilege']) : '';
			$this->options['privilege_point'] = isset($_POST['point_num']) ? (int)$_POST['point_num'] : '';
			$this->options['privilege_discount'] = isset($_POST['discount_num']) ? (int)$_POST['discount_num'] : '';
			$this->options['company_name'] = isset($_POST['company_name']) ? wp_specialchars($_POST['company_name']) : '';
			$this->options['zip_code'] = isset($_POST['zip_code']) ? wp_specialchars($_POST['zip_code']) : '';
			$this->options['address1'] = isset($_POST['address1']) ? wp_specialchars($_POST['address1']) : '';
			$this->options['address2'] = isset($_POST['address2']) ? wp_specialchars($_POST['address2']) : '';
			$this->options['tel_number'] = isset($_POST['tel_number']) ? wp_specialchars($_POST['tel_number']) : '';
			$this->options['fax_number'] = isset($_POST['fax_number']) ? wp_specialchars($_POST['fax_number']) : '';
			$this->options['order_mail'] = isset($_POST['order_mail']) ? wp_specialchars($_POST['order_mail']) : '';
			$this->options['inquiry_mail'] = isset($_POST['inquiry_mail']) ? wp_specialchars($_POST['inquiry_mail']) : '';
			$this->options['sender_mail'] = isset($_POST['sender_mail']) ? wp_specialchars($_POST['sender_mail']) : '';
			$this->options['error_mail'] = isset($_POST['error_mail']) ? wp_specialchars($_POST['error_mail']) : '';
			$this->options['postage_privilege'] = isset($_POST['postage_privilege']) ? wp_specialchars($_POST['postage_privilege']) : '';
			$this->options['purchase_limit'] = isset($_POST['purchase_limit']) ? wp_specialchars($_POST['purchase_limit']) : '';
			$this->options['point_rate'] = isset($_POST['point_rate']) ? (int)$_POST['point_rate'] : '';
			$this->options['start_point'] = isset($_POST['start_point']) ? (int)$_POST['start_point'] : '';
			$this->options['shipping_rule'] = isset($_POST['shipping_rule']) ? wp_specialchars($_POST['shipping_rule']) : '';
			$this->options['tax_rate'] = isset($_POST['tax_rate']) ? (int)$_POST['tax_rate'] : '';
			$this->options['tax_method'] = isset($_POST['tax_method']) ? wp_specialchars($_POST['tax_method']) : '';
			$this->options['cod_fee'] = isset($_POST['cod_fee']) ? wp_specialchars($_POST['cod_fee']) : '';
			$this->options['transferee'] = isset($_POST['transferee']) ? wp_specialchars($_POST['transferee']) : '';
			$this->options['copyright'] = isset($_POST['copyright']) ? wp_specialchars($_POST['copyright']) : '';
			$this->options['membersystem_state'] = isset($_POST['membersystem_state']) ? wp_specialchars($_POST['membersystem_state']) : '';
			$this->options['membersystem_point'] = isset($_POST['membersystem_point']) ? wp_specialchars($_POST['membersystem_point']) : '';

			update_option('usces', $this->options);
			
			$this->action_status = 'success';
			$this->action_message = __('options are updated','usces');
		} else {
			$this->action_status = 'none';
			$this->action_message = '';
		}
		
		require_once(USCES_PLUGIN_DIR . '/includes/admin_setup.php');	

	}
	
	/* Shop Schedule Page */
	function admin_schedule_page() {

		$this->options = get_option('usces');

		if(isset($_POST['usces_option_update'])) {

			$this->options['campaign_schedule'] = isset($_POST['campaign_schedule']) ? $_POST['campaign_schedule'] : '0';
			if(isset($_POST['business_days'])) $this->options['business_days'] = $_POST['business_days'];



			update_option('usces', $this->options);
			
			$this->action_status = 'success';
			$this->action_message = __('options are updated','usces');
		} else {
			$this->action_status = 'none';
			$this->action_message = '';
		}
		
		require_once(USCES_PLUGIN_DIR . '/includes/admin_schedule.php');	

	}
	
	/* Shop Delivery Page */
	function admin_delivery_page() {
	
		$this->options = get_option('usces');

		if(isset($_POST['usces_option_update'])) {

			//$this->options['delivery_time'] = isset($_POST['delivery_time']) ? $_POST['delivery_time'] : '';
			//$this->options['shipping_charges'] = isset($_POST['shipping_charge']) ? $_POST['shipping_charge'] : '';



			update_option('usces', $this->options);
			
			$this->action_status = 'success';
			$this->action_message = __('options are updated','usces');
		} else {
			$this->action_status = 'none';
			$this->action_message = '';
		}
		
		require_once(USCES_PLUGIN_DIR . '/includes/admin_delivery.php');	

	}
	
	/* Shop Mail Page */
	function admin_mail_page() {
	
		$this->options = get_option('usces');

		if(isset($_POST['usces_option_update'])) {
		
			$this->options['smtp_hostname'] = wp_specialchars(trim($_POST['smtp_hostname']));
		
			foreach ( $_POST['title'] as $key => $value ) {
				if( trim($value) == '' ) {
					$this->options['mail_data']['title'][$key] = $this->options['mail_default']['title'][$key];
				}else{
					$this->options['mail_data']['title'][$key] = wp_specialchars($value);
				}
			}
			foreach ( $_POST['header'] as $key => $value ) {
				if( trim($value) == '' ) {
					$this->options['mail_data']['header'][$key] = $this->options['mail_default']['header'][$key];
				}else{
					$this->options['mail_data']['header'][$key] = wp_specialchars($value);
				}
			}
			foreach ( $_POST['footer'] as $key => $value ) {
				if( trim($value) == '' ) {
					$this->options['mail_data']['footer'][$key] = $this->options['mail_default']['footer'][$key];
				}else{
					$this->options['mail_data']['footer'][$key] = wp_specialchars($value);
				}
			}

			
			$this->action_status = 'success';
			$this->action_message = __('options are updated','usces');
			
		} else {
		
			foreach ( (array)$this->options['mail_data']['title'] as $key => $value ) {
				if( trim($value) == '' ) {
					$this->options['mail_data']['title'][$key] = $this->options['mail_default']['title'][$key];
				}
			}
			foreach ( (array)$this->options['mail_data']['header'] as $key => $value ) {
				if( trim($value) == '' ) {
					$this->options['mail_data']['header'][$key] = $this->options['mail_default']['header'][$key];
				}
			}
			foreach ( (array)$this->options['mail_data']['footer'] as $key => $value ) {
				if( trim($value) == '' ) {
					$this->options['mail_data']['footer'][$key] = $this->options['mail_default']['footer'][$key];
				}
			}

			$this->action_status = 'none';
			$this->action_message = '';
			
		}
	
		update_option('usces', $this->options);
		
		require_once(USCES_PLUGIN_DIR . '/includes/admin_mail.php');	

	}
	
	/* Admin Cart Page */
	function admin_cart_page() {

		$this->options = get_option('usces');

		if(isset($_POST['usces_option_update'])) {

			foreach ( $this->options['indi_item_name'] as $key => $value ) {
				$this->options['indi_item_name'][$key] = isset($_POST['indication'][$key]) ? 1 : 0;
			}
			foreach ( $_POST['position'] as $key => $value ) {
				$this->options['pos_item_name'][$key] = $value;
			}
			foreach ( $_POST['header'] as $key => $value ) {
				$this->options['cart_page_data']['header'][$key] = $value;
			}
			foreach ( $_POST['footer'] as $key => $value ) {
				$this->options['cart_page_data']['footer'][$key] = $value;
			}

			update_option('usces', $this->options);
			
			$this->action_status = 'success';
			$this->action_message = __('options are updated','usces');
		} else {

			$this->action_status = 'none';
			$this->action_message = '';
		}


		
		require_once(USCES_PLUGIN_DIR . '/includes/admin_cart.php');	

	}
	
	/* Admin Member Page */
	function admin_member_page() {

		$this->options = get_option('usces');

		if(isset($_POST['usces_option_update'])) {

			foreach ( $_POST['header'] as $key => $value ) {
				$this->options['member_page_data']['header'][$key] = $value;
			}
			foreach ( $_POST['footer'] as $key => $value ) {
				$this->options['member_page_data']['footer'][$key] = $value;
			}

			update_option('usces', $this->options);
			
			$this->action_status = 'success';
			$this->action_message = __('options are updated','usces');
		} else {

			$this->action_status = 'none';
			$this->action_message = '';
		}


		
		require_once(USCES_PLUGIN_DIR . '/includes/admin_member.php');	

	}
	
	
	/* Admin System Page */
	function admin_system_page() {

		$this->options = get_option('usces');

		if(isset($_POST['usces_option_update'])) {
		
			if($_POST['province'] != ''){
				$temp_pref = explode("\n", $_POST['province']);
				for($i=-1; $i<count($temp_pref); $i++){
					if($i == -1){
						$usces_pref[] = '-選択-';
					}else{
						$usces_pref[] = wp_specialchars(trim($temp_pref[$i]));
					}
				}
			}else{
				$usces_pref = get_option('usces_pref');
			}

			$this->options['province'] = $usces_pref;
			$this->options['divide_item'] = isset($_POST['divide_item']) ? 1 : 0;
			$this->options['itemimg_anchor_rel'] = isset($_POST['itemimg_anchor_rel']) ? wp_specialchars(trim($_POST['itemimg_anchor_rel'])) : '';
			$this->options['fukugo_category_orderby'] = isset($_POST['fukugo_category_orderby']) ? $_POST['fukugo_category_orderby'] : '';
			$this->options['fukugo_category_order'] = isset($_POST['fukugo_category_order']) ? $_POST['fukugo_category_order'] : '';
			$this->options['settlement_path'] = isset($_POST['settlement_path']) ? stripslashes($_POST['settlement_path']) : '';
			if($this->options['settlement_path'] == '') $this->options['settlement_path'] = USCES_PLUGIN_DIR . '/settlement/';
			$sl = substr($this->options['settlement_path'], -1);
			if($sl != '/' && $sl != '\\') $this->options['settlement_path'] .= '/';
			$this->options['use_ssl'] = isset($_POST['use_ssl']) ? 1 : 0;
			$this->options['ssl_url'] = isset($_POST['ssl_url']) ? stripslashes(rtrim($_POST['ssl_url'], '/')) : '';
			$this->options['ssl_url_admin'] = isset($_POST['ssl_url_admin']) ? stripslashes(rtrim($_POST['ssl_url_admin'], '/')) : '';
			if( $this->options['ssl_url'] == '' || $this->options['ssl_url_admin'] == '' ) $this->options['use_ssl'] = 0;

			
			$this->action_status = 'success';
			$this->action_message = __('options are updated','usces');
		} else {

			if( !isset($this->options['province']) || $this->options['province'] == '' ){
				$this->options['province'] = get_option('usces_pref');
			}
			$this->action_status = 'none';
			$this->action_message = '';
		}

		update_option('usces', $this->options);

		
		require_once(USCES_PLUGIN_DIR . '/includes/admin_system.php');	

	}
	
	/********************************************************************************/
	function selected( $selected, $current) {
		if ( $selected == $current)
			echo ' selected="selected"';
	}
	/********************************************************************************/

	function get_request() {
		$host = $_SERVER['HTTP_HOST'];
		$uri = $_SERVER['REQUEST_URI'];
		$port = $_SERVER['REMOTE_PORT'];
		$scheme = ( $port == 443 ) ? 'https://' : 'http://';
		return $scheme . $host . $uri;
	}
	
	function redirect() {
	
		$redirect = '';

		$req = $_SERVER['QUERY_STRING'];
		$port = $_SERVER['SERVER_PORT'];
		
		$request = $this->get_request();
		
		$conjunction = ( empty($req) && (!strpos($request, USCES_CART_FOLDER, 1) && !strpos($request, USCES_MEMBER_FOLDER, 1)) ) ? '?' : '&';
		
		$sessid = $conjunction . 'usces=' . $this->get_uscesid();
	
		
		if( false === strpos($request, 'usces=') )
			$uri = $request . $sessid;
		else
			$uri = $request;
		

		if( $this->use_ssl ) {
		
			if ( '80' == $port && strpos($uri, USCES_CART_FOLDER, 1))
				$redirect = USCES_SSL_URL . '/?page_id=' . USCES_CART_NUMBER . $sessid;
		
			if ( '80' == $port && strpos($uri, USCES_MEMBER_FOLDER, 1))
				$redirect = USCES_SSL_URL . '/?page_id=' . USCES_MEMBER_NUMBER . $sessid;

			if ( '443' == $port && false === strpos($uri, 'wp-admin') && false === strpos($uri, 'wp-login.php') && false === strpos($uri, '?page_id=' . USCES_CART_NUMBER) && false === strpos($uri, '?page_id=' . USCES_MEMBER_NUMBER) && !strpos($uri, USCES_CART_FOLDER, 1) && !strpos($uri, USCES_MEMBER_FOLDER, 1) )
				$redirect = get_option('home');
		}

	
		if($redirect != '') {
			//wp_redirect($redirect);
			exit;
		}
	}

	function usces_session_start() {

		if(isset($_GET['usces']) && ($_GET['usces'] != '')) {
			$sessid = $_GET['usces'];
			//$this->uscesdc($sessid);
			session_id($sessid);
		}
		@session_start();
		
	}
	
	function usces_cookie() {
		if( !isset($_SESSION['usces_cookieid']) ) {
			$cookie = $this->get_cookie();
			if( !isset($cookie['id']) || $cookie['id'] == '' ) {
				$values = array(
							'id' => md5(uniqid(rand(), true)),
							'name' => '',
							'pass' => ''
							);
				$this->set_cookie($values);
				$_SESSION['usces_cookieid'] = $values['id'];
				//$this->cnt_access('first');
			} else {
				$_SESSION['usces_cookieid'] = $cookie['id'];
				//$this->cnt_access();
			}
		}
	}
	function set_cookie($values){
		$value = serialize($values);
		$timeout = time()+365*86400;
		$domain = $_SERVER['HTTP_HOST'];
		$res = setcookie('usces_cookie', $value, $timeout, USCES_COOKIEPATH, $domain);
	}
	
	function get_cookie() {
		$values = unserialize(stripslashes($_COOKIE['usces_cookie']));
		return $values;
	}
	
	function cnt_access( $flag = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "usces_access";

		$query = $wpdb->prepare("SELECT ID FROM $table_name WHERE acc_date = %s", substr(get_date_from_gmt(gmdate('Y-m-d H:i:s', time())), 0, 10));
		$res = $wpdb->get_var( $query );
		$wpdb->show_errors();
		if(empty($res)){
			if( $flag == '' ){
				$query = $wpdb->prepare("INSERT INTO $table_name (acc_type, acc_num1, acc_num2, acc_str1, acc_str2, acc_date) VALUES(%s, %d, %d, %s, %s, %s)", 'visiter', 1, 0, NULL, NULL, substr(get_date_from_gmt(gmdate('Y-m-d H:i:s', time())), 0, 10));
				$wpdb->query( $query );
			}elseif( $flag == 'first' ){
				$query = $wpdb->prepare("INSERT INTO $table_name (acc_type, acc_num1, acc_num2, acc_str1, acc_str2, acc_date) VALUES(%s, %d, %d, %s, %s, %s)", 'visiter', 0, 1, NULL, NULL, substr(get_date_from_gmt(gmdate('Y-m-d H:i:s', time())), 0, 10));
				$wpdb->query( $query );
			}
		}else{
			if( $flag == '' ){
				$query = $wpdb->prepare("UPDATE $table_name SET acc_num1 = acc_num1 + 1 WHERE acc_date = %s", substr(get_date_from_gmt(gmdate('Y-m-d H:i:s', time())), 0, 10));
				$wpdb->query( $query );
			}elseif( $flag == 'first' ){
				$query = $wpdb->prepare("UPDATE $table_name SET acc_num2 = acc_num2 + 1 WHERE acc_date = %s", substr(get_date_from_gmt(gmdate('Y-m-d H:i:s', time())), 0, 10));
				$wpdb->query( $query );
			}
		}
	}
	
	function get_uscesid() {

		$sessname = session_name();
		$sessid = isset($_REQUEST[$sessname]) ? $_REQUEST[$sessname] : session_id();
		//$this->uscescv($sessid);
		return $sessid;
	}
	
	function shop_head() {
		global $post, $current_user;
		get_currentuserinfo();
		
		if( $this->is_cart_or_member_page($_SERVER['REQUEST_URI']) ){
			$css_url = USCES_FRONT_PLUGIN_URL . '/css/usces_cart.css';
			$javascript_url = USCES_FRONT_PLUGIN_URL . '/js/usces_cart.js';
		}else{
			$css_url = USCES_WP_CONTENT_URL . '/plugins/' . USCES_PLUGIN_FOLDER . '/css/usces_cart.css';
			$javascript_url = USCES_WP_CONTENT_URL . '/plugins/' . USCES_PLUGIN_FOLDERL . '/js/usces_cart.js';
		}
		$this->member_name = ( is_user_logged_in() ) ? get_usermeta($current_user->ID,'first_name').get_usermeta($current_user->ID,'last_name') : '';
		?>

		<link href="<?php echo $css_url; ?>" rel="stylesheet" type="text/css" />
	<?php if( file_exists(get_stylesheet_directory() . '/usces_cart.css') ){ ?>
		<link href="<?php echo get_stylesheet_directory_uri(); ?>/usces_cart.css" rel="stylesheet" type="text/css" />
	<?php } ?>
		<?php 
		if(isset($post)) : 
		
			$ioptkeys = $this->get_itemOptionKey( $post->ID );
			$mes_opts_str = "";
			$key_opts_str = "";
			$opt_means = "";
			$opt_esse = "";
			if($ioptkeys){
				foreach($ioptkeys as $key => $value){
					$optValues = $this->get_itemOptions( $value, $post->ID );
					if($optValues['means'] < 2){
						$mes_opts_str .= "'" . sprintf(__("Chose the %s", 'usces'), $value) . "',";
					}else{
						$mes_opts_str .= "'" . sprintf(__("Input the %s", 'usces'), $value) . "',";
					}
					$key_opts_str .= "'{$value}',";
					$opt_means .= "'{$optValues['means']}',";
					$opt_esse .= "'{$optValues['essential']}',";
				}
				$mes_opts_str = rtrim($mes_opts_str, ',');
				$key_opts_str = rtrim($key_opts_str, ',');
				$opt_means = rtrim($opt_means, ',');
				$opt_esse = rtrim($opt_esse, ',');
			}
			$itemRestriction = get_post_custom_values('_itemRestriction', $post->ID);
		
		?>
		<script type='text/javascript'>
		/* <![CDATA[ */
			uscesL10n = {
				'ajaxurl': "<?php echo USCES_SSL_URL_ADMIN; ?>/wp-admin/admin-ajax.php",
				'post_id': "<?php echo $post->ID; ?>",
				'cart_number': "<?php echo get_option('usces_cart_number'); ?>",
				'opt_esse': new Array( <?php echo $opt_esse; ?> ),
				'opt_means': new Array( <?php echo $opt_means; ?> ),
				'mes_opts': new Array( <?php echo $mes_opts_str; ?> ),
				'key_opts': new Array( <?php echo $key_opts_str; ?> ), 
				'previous_url': "<?php if(isset($_SESSION['usces_previous_url'])) echo $_SESSION['usces_previous_url']; ?>", 
				'itemRestriction': "<?php echo $itemRestriction[0]; ?>"
			}
		/* ]]> */
		</script>
		<?php endif; ?>
		<script type='text/javascript' src='<?php echo $javascript_url; ?>'></script>
		<?php if( isset($post) && ((USCES_CART_NUMBER == $post->ID) || ('item' == $post->post_mime_type && is_single())) ) : ?>
		<script type='text/javascript'>
		(function($) {
		uscesCart = {
			intoCart : function (post_id, sku) {
				
				var zaikonum = document.getElementById("zaikonum["+post_id+"]["+sku+"]").value;
				var zaiko = document.getElementById("zaiko["+post_id+"]["+sku+"]").value;
				if( (zaiko != '0' && zaiko != '1') ||  parseInt(zaikonum) == 0 ){
					alert('<?php _e('temporaly out of stock now', 'usces'); ?>');
					return false;
				}
				
				var mes = '';
				if(document.getElementById("quant["+post_id+"]["+sku+"]")){
					var quant = document.getElementById("quant["+post_id+"]["+sku+"]").value;
					if( quant == '0' || quant == '' || !(uscesCart.isNum(quant))){
						mes += "<?php _e('enter the correct amount', 'usces'); ?>\n";
					}
					var checknum = '';
					var checkmode = '';
					if( parseInt(uscesL10n.itemRestriction) <= parseInt(zaikonum) && uscesL10n.itemRestriction != '' && uscesL10n.itemRestriction != '0' && zaikonum != '' ) {
						checknum = uscesL10n.itemRestriction;
						checkmode ='rest';
					} else if( parseInt(uscesL10n.itemRestriction) > parseInt(zaikonum) && uscesL10n.itemRestriction != '' && uscesL10n.itemRestriction != '0' && zaikonum != '' ) {
						checknum = zaikonum;
						checkmode ='zaiko';
					} else if( (uscesL10n.itemRestriction == '' || uscesL10n.itemRestriction == '0') && zaikonum != '' ) {
						checknum = zaikonum;
						checkmode ='zaiko';
					} else if( uscesL10n.itemRestriction != '' && uscesL10n.itemRestriction != '0' && zaikonum == '' ) {
						checknum = uscesL10n.itemRestriction;
						checkmode ='rest';
					}
									
	
					if( parseInt(quant) > parseInt(checknum) && checknum != '' ){
							if(checkmode == 'rest'){
								mes += <?php _e("'This article is limited by '+checknum+' at a time.'", 'usces'); ?>+"\n";
							}else{
								mes += <?php _e("'Stock is remainder '+checknum+'.'", 'usces'); ?>+"\n";
							}
					}
				}
				for(i=0; i<uscesL10n.key_opts.length; i++){
					var skuob = document.getElementById("itemOption["+post_id+"]["+sku+"]["+uscesL10n.key_opts[i]+"]");
					if( uscesL10n.opt_esse[i] == '1' ){
						
						if( uscesL10n.opt_means[i] < 2 && skuob.value == '#NONE#' ){
							mes += uscesL10n.mes_opts[i]+"\n";
						}else if( uscesL10n.opt_means[i] >= 2 && skuob.value == '' ){
							mes += uscesL10n.mes_opts[i]+"\n";
						}
					}
				}
				if( mes != '' ){
					alert( mes );
					return false;
				}else{
					return true;
				}
			},
			
			upCart : function () {
				
				var zaikoob = $("input[name*='zaikonum']");
				var quantob = $("input[name*='quant']");
				var postidob = $("input[name*='itempostid']");
				var skuob = $("input[name*='itemsku']");
				
				var zaikonum = '';
				var zaiko = '';
				var quant = '';
				var mes = '';
				var checknum = '';
				var post_id = '';
				var sku = '';
				var itemRestriction = '';
				
				var ct = zaikoob.length;
				for(var i=0; i< ct; i++){
					post_id = postidob[i].value;
					sku = skuob[i].value;
					itemRestriction = $("input[name='itemRestriction\[" + i + "\]']").val();
					zaikonum = $("input[name='zaikonum\[" + i + "\]\[" + post_id + "\]\[" + sku + "\]']").val();
			
					quant = $("input[name='quant\[" + i + "\]\[" + post_id + "\]\[" + sku + "\]']").val();
					if( $("input[name='quant\[" + i + "\]\[" + post_id + "\]\[" + sku + "\]']") ){
						if( quant == '0' || quant == '' || !(uscesCart.isNum(quant))){
							mes += <?php _e("'enter the correct amount for the No.' + (i+1) + ' item'", 'usces'); ?>+"\n";
						}
						var checknum = '';
						var checkmode = '';
						if( parseInt(itemRestriction) <= parseInt(zaikonum) && itemRestriction != '' && itemRestriction != '0' && zaikonum != '' ) {
							checknum = uscesL10n.itemRestriction;
							checkmode ='rest';
						} else if( parseInt(itemRestriction) > parseInt(zaikonum) && itemRestriction != '' && itemRestriction != '0' && zaikonum != '' ) {
							checknum = zaikonum;
							checkmode ='zaiko';
						} else if( (itemRestriction == '' || itemRestriction == '0') && zaikonum != '' ) {
							checknum = zaikonum;
							checkmode ='zaiko';
						} else if( itemRestriction != '' && itemRestriction != '0' && zaikonum == '' ) {
							checknum = itemRestriction;
							checkmode ='rest';
						}
						if( parseInt(quant) > parseInt(checknum) && checknum != '' ){
							if(checkmode == 'rest'){
								mes += <?php _e("'This article is limited by '+checknum+' at a time for the No.' + (i+1) + ' item.'", 'usces'); ?>+"\n";
							}else{
								mes += <?php _e("'Stock of No.' + (i+1) + ' item is remainder '+checknum+'.'", 'usces'); ?>+"\n";
							}
						}
					}
				}
	
				if( mes != '' ){
					alert( mes );
					return false;
				}else{
					return true;
				}
			},
			
			cartNext : function () {
			
				var zaikoob = $("input[name*='zaikonum']");
				var quantob = $("input[name*='quant']");
				var postidob = $("input[name*='itempostid']");
				var skuob = $("input[name*='itemsku']");
				
				var zaikonum = '';
				var zaiko = '';
				var quant = '';
				var mes = '';
				var checknum = '';
				var post_id = '';
				var sku = '';
				var itemRestriction = '';
				
				var ct = zaikoob.length;
				for(var i=0; i< ct; i++){
					post_id = postidob[i].value;
					sku = skuob[i].value;
					itemRestriction = $("input[name='itemRestriction\[" + i + "\]']").val();
					zaikonum = $("input[name='zaikonum\[" + i + "\]\[" + post_id + "\]\[" + sku + "\]']").val();
			
					quant = $("input[name='quant\[" + i + "\]\[" + post_id + "\]\[" + sku + "\]']").val();
					if( $("input[name='quant\[" + i + "\]\[" + post_id + "\]\[" + sku + "\]']") ){
						if( quant == '0' || quant == '' || !(uscesCart.isNum(quant))){
							mes += <?php _e("'enter the correct amount for the No.' + (i+1) + ' item'", 'usces'); ?>+"\n";
						}
						var checknum = '';
						var checkmode = '';
						if( parseInt(itemRestriction) <= parseInt(zaikonum) && itemRestriction != '' && itemRestriction != '0' && zaikonum != '' ) {
							checknum = uscesL10n.itemRestriction;
							checkmode ='rest';
						} else if( parseInt(itemRestriction) > parseInt(zaikonum) && itemRestriction != '' && itemRestriction != '0' && zaikonum != '' ) {
							checknum = zaikonum;
							checkmode ='zaiko';
						} else if( (itemRestriction == '' || itemRestriction == '0') && zaikonum != '' ) {
							checknum = zaikonum;
							checkmode ='zaiko';
						} else if( itemRestriction != '' && itemRestriction != '0' && zaikonum == '' ) {
							checknum = itemRestriction;
							checkmode ='rest';
						}
						if( parseInt(quant) > parseInt(checknum) && checknum != '' ){
							if(checkmode == 'rest'){
								mes += <?php _e("'This article is limited by '+checknum+' at a time for the No.' + (i+1) + ' item.'", 'usces'); ?>+"\n";
							}else{
								mes += <?php _e("'Stock of No.' + (i+1) + ' item is remainder '+checknum+'.'", 'usces'); ?>+"\n";
							}
						}
					}
				}
				if( mes != '' ){
					alert( mes );
					return false;
				}else{
					return true;
				}
			},
			
			previousCart : function () {
				location.href = uscesL10n.previous_url; 
			},
			
			isNum : function (num) {
				if (num.match(/[^0-9]/g)) {
					return false;
				}
				return true;
			}
		};
		})(jQuery);
		</script>
		<?php endif; ?>
<?php
	}
	
	function admin_head() {
		$payments_str = '';
		foreach ( (array)$this->options['payment_method'] as $id => $array ) {
			$payments_str .= "'" . $this->options['payment_method'][$id]['name'] . "': '" . $this->options['payment_method'][$id]['settlement'] . "', ";
		}
		$payments_str = rtrim($payments_str, ', ');
?>
		
		<link href="<?php echo USCES_PLUGIN_URL; ?>/css/admin_style.css" rel="stylesheet" type="text/css" media="all" />
		<script type='text/javascript'>
		/* <![CDATA[ */
			uscesL10n = {
				'requestFile': "<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php",
				'cart_number': "<?php echo get_option('usces_cart_number'); ?>", 
				'purchase_limit': "<?php echo $this->options['purchase_limit']; ?>", 
				'point_rate': "<?php echo $this->options['point_rate']; ?>",
				'shipping_rule': "<?php echo $this->options['shipping_rule']; ?>", 
				'now_loading': "<?php _e('now loading', 'usces'); ?>" 
			};
			uscesPayments = {<?php echo $payments_str; ?>};
		/* ]]> */
		</script>
		<script type='text/javascript' src='<?php echo USCES_PLUGIN_URL; ?>/js/usces_admin.js'></script>
		
	<?php if($this->action_status == 'edit' || $this->action_status == 'editpost'){ ?>
			<link rel='stylesheet' href='<?php echo get_option('siteurl'); ?>/wp-includes/js/thickbox/thickbox.css' type='text/css' media='all' />
<?php
		}
	}
	
	function main() {
		global $wpdb, $wp_locale, $wp_version;
		global $wp_query, $usces_action, $post, $action;

		do_action('usces_main');
		$this->usces_cookie();
		$this->update_table();
		
		//var_dump($_REQUEST);
		require_once(USCES_PLUGIN_DIR . '/classes/cart.class.php');
		$this->cart = new usces_cart();
		
		
		if( isset($_REQUEST['page']) && $_REQUEST['page'] == 'usces_itemedit' && isset($_REQUEST['action']) && $_REQUEST['action'] == 'duplicate' ){
			$post_id = (int)$_GET['post'];
			$new_id = usces_item_dupricate($post_id);
			$ref = isset($_REQUEST['usces_referer']) ? urlencode($_REQUEST['usces_referer']) : '';
			$url = USCES_ADMIN_URL . '?page=usces_itemedit&action=edit&post=' . $new_id . '&usces_referer=' . $ref;
			wp_redirect($url);
			exit;
		}else if( isset($_REQUEST['page']) && $_REQUEST['page'] == 'usces_itemedit' && isset($_REQUEST['action']) && $_REQUEST['action'] == 'itemcsv' ){
			$res = usces_item_uploadcsv();
			$url = USCES_ADMIN_URL . '?page=usces_itemedit&usces_status=' . $res['status'] . '&usces_message=' . urlencode($res['message']);
			wp_redirect($url);
			exit;
		}
		
		
		$this->ad_controller();
		//$this->controller();
		

		
		if($_GET['page'] == 'usces_itemnew')
			$itemnew = 'new';
		
		wp_enqueue_script('jquery');
		
		if( isset($_REQUEST['page']) && ($_REQUEST['action'] == 'edit' || $itemnew == 'new' || $_REQUEST['action'] == 'editpost')) {
		
			global $editing, $post;
			if($_REQUEST['action'] != 'editpost' && $itemnew == 'new'){
				if ( version_compare($wp_version, '3.0-beta', '>') ){
					if ( !isset($_GET['post_type']) )
						$post_type = 'post';
					elseif ( in_array( $_GET['post_type'], get_post_types( array('public' => true ) ) ) )
						$post_type = $_GET['post_type'];
					else
						wp_die( __('Invalid post type') );
					$post_type_object = get_post_type_object($post_type);
					$editing = true;
//					if ( current_user_can($post_type_object->edit_type_cap) ) {
						$post = $this->get_default_post_to_edit30( $post_type, true );
						$post_ID = $post->ID;
//					}
					
				}else{
					$post = $this->get_default_post_to_edit();
				}
			}else{
				if ( version_compare($wp_version, '3.0-beta', '>') ){
					if ( isset($_GET['post']) )
						$post_id = (int) $_GET['post'];
					elseif ( isset($_POST['post_ID']) )
						$post_id = (int) $_POST['post_ID'];
					else
						$post_id = 0;
					$post_ID = $post_id;
					$post = null;
					$post_type_object = null;
					$post_type = null;
					if ( $post_id ) {
						$post = get_post($post_id);
						if ( $post ) {
							$post_type_object = get_post_type_object($post->post_type);
							if ( $post_type_object ) {
								$post_type = $post->post_type;
								$current_screen->post_type = $post->post_type;
								$current_screen->id = $current_screen->post_type;
							}
						}
					} elseif ( isset($_POST['post_type']) ) {
						$post_type_object = get_post_type_object($_POST['post_type']);
						if ( $post_type_object ) {
							$post_type = $post_type_object->name;
							$current_screen->post_type = $post_type;
							$current_screen->id = $current_screen->post_type;
						}
					}
					

					$post = get_post( $post_id, OBJECT, 'edit' );
					if ( $post->post_type == 'page' )
						$post->page_template = get_post_meta( $id, '_wp_page_template', true );
						
				}else{
					if(isset($_GET['post'])){
						$post_ID =  (int) $_GET['post'];
						$post = get_post($post_ID);
					}else{
						$post_ID =  isset($_REQUEST['post_ID']) ? (int) $_REQUEST['post_ID'] : 0;
						if(!empty($post_ID))
							$post = get_post($post_ID);
					}
				}
//		global $wp_query, $usces_action, $post;

			}
			$editing = true;
			wp_enqueue_script('autosave');
			wp_enqueue_script('post');
			//if ( user_can_richedit() )
			wp_enqueue_script('editor');
			add_thickbox();
			wp_enqueue_script('media-upload');
			wp_enqueue_script('word-count');
			wp_enqueue_script( 'admin-comments' );
		
			//add_action( 'admin_head', 'wp_tiny_mce' );
			add_action( 'admin_print_footer_scripts', 'wp_tiny_mce', 25 );
			wp_enqueue_script('quicktags');

		}


		if( isset($_REQUEST['order_action']) && $_REQUEST['order_action'] == 'pdfout' ){
			require_once(USCES_PLUGIN_DIR . '/includes/order_print.php');
		}
		
	}
	
	function regist_action(){
		usces_register_action('inCart', 'post', 'inCart', NULL, 'inCart');
		usces_register_action('upButton', 'post', 'upButton', NULL, 'upButton');
		usces_register_action('delButton', 'post', 'delButton', NULL, 'delButton');
		usces_register_action('backCart', 'post', 'backCart', NULL, 'backCart');
		usces_register_action('customerinfo', 'request', 'customerinfo', NULL, 'customerinfo');
		usces_register_action('backCustomer', 'post', 'backCustomer', NULL, 'backCustomer');
		usces_register_action('customerlogin', 'post', 'customerlogin', NULL, 'customerlogin');
		usces_register_action('reganddeliveryinfo', 'post', 'reganddeliveryinfo', NULL, 'reganddeliveryinfo');
		usces_register_action('deliveryinfo', 'post', 'deliveryinfo', NULL, 'deliveryinfo');
		usces_register_action('backDelivery', 'post', 'backDelivery', NULL, 'backDelivery');
		usces_register_action('confirm', 'request', 'confirm', NULL, 'confirm');
		usces_register_action('use_point', 'post', 'use_point', NULL, 'use_point');
		usces_register_action('backConfirm', 'post', 'backConfirm', NULL, 'backConfirm');
		usces_register_action('purchase', 'request', 'purchase', NULL, 'purchase');
		usces_register_action('acting_return', 'request', 'acting_return', NULL, 'acting_return');
		usces_register_action('settlement_epsilon', 'request', 'settlement', 'epsilon', 'settlement_epsilon');
		usces_register_action('inquiry_button', 'post', 'inquiry_button', NULL, 'inquiry_button');
		usces_register_action('member_login', 'request', 'member_login', NULL, 'member_login_page');
		usces_register_action('regmember', 'request', 'regmember', NULL, 'regmember');
		usces_register_action('editmember', 'request', 'editmember', NULL, 'editmember');
		usces_register_action('page_login', 'get', 'page', 'login', 'member_login_page');
		usces_register_action('page_logout', 'get', 'page', 'logout', 'page_logout');
		usces_register_action('page_lostmemberpassword', 'get', 'page', 'lostmemberpassword', 'page_lostmemberpassword');
		usces_register_action('lostpassword', 'request', 'lostpassword', NULL, 'lostpassword');
		usces_register_action('uscesmode_changepassword', 'request', 'uscesmode', 'changepassword', 'uscesmode_changepassword');
		usces_register_action('changepassword', 'request', 'changepassword', NULL, 'changepassword_page');
		usces_register_action('page_newmember', 'get', 'page', 'newmember', 'page_newmember');
		usces_register_action('usces_export', 'post', 'usces_export', NULL, 'usces_export');
		usces_register_action('usces_import', 'post', 'usces_import', NULL, 'usces_import');
		usces_register_action('page_search_item', 'get', 'page', 'search_item', 'page_search_item');
	}

	function ad_controller(){
		global $usces_action;
		if($this->is_maintenance()){
			$this->maintenance();
		}else{
			$action_array = array('inCart', 'upButton', 'delButton', 'backCart', 'customerinfo', 'backCustomer', 
			'customerlogin', 'reganddeliveryinfo', 'deliveryinfo', 'backDelivery', 'confirm', 'use_point', 
			'backConfirm', 'purchase', 'acting_return', 'settlement_epsilon', 'inquiry_button', 'member_login', 
			'regmember', 'editmember', 'page_login', 'page_logout', 'page_lostmemberpassword', 'lostpassword', 
			'uscesmode_changepassword', 'changepassword', 'page_newmember', 'usces_export', 'usces_import', 
			'page_search_item');
			$flg = 0;
			foreach( $usces_action as $handle => $action ){
				extract($action);
				switch($type){
					case 'post':
						if( empty($value) ){
							if( isset($_POST[$key]) ){
								if(in_array($handle, $action_array)){
									$res = call_user_func(array($this, $function));
								}else{
									$res = call_user_func($function);
								}
								$flg = 1;
							}
						}else{
							if( isset($_POST[$key]) && $_POST[$key] == $value ){
								if(in_array($handle, $action_array)){
									$res = call_user_func(array($this, $function));
								}else{
									$res = call_user_func($function);
								}
								$flg = 1;
							}
						}
						break;
					case 'get':
						if( empty($value) ){
							if( isset($_GET[$key]) ){
								if(in_array($handle, $action_array)){
									$res = call_user_func(array($this, $function));
								}else{
									$res = call_user_func($function);
								}
								$flg = 1;
							}
						}else{
							if( isset($_GET[$key]) && $_GET[$key] == $value ){
								if(in_array($handle, $action_array)){
									$res = call_user_func(array($this, $function));
								}else{
									$res = call_user_func($function);
								}
								$flg = 1;
							}
						}
						break;
					case 'request':
						if( empty($value) ){
							if( isset($_REQUEST[$key]) ){
								if(in_array($handle, $action_array)){
									$res = call_user_func(array($this, $function));
								}else{
									$res = call_user_func($function);
								}
								$flg = 1;
							}
						}else{
							if( isset($_REQUEST[$key]) && $_REQUEST[$key] == $value ){
								if(in_array($handle, $action_array)){
									$res = call_user_func(array($this, $function));
								}else{
									$res = call_user_func($function);
								}
								$flg = 1;
							}
						}
						break;
				}
			}
			if( !$flg ) $this->default_page();
		}
	}

	//action function------------------------------------------------------------
	function maintenance(){
		$this->page = 'maintenance';
		add_action('the_post', array($this, 'action_cartFilter'));
	}

	function inCart(){
		global $wp_query;
		$this->page = 'cart';
		$this->cart->inCart();
		add_action('the_post', array($this, 'action_cartFilter'));
	}
	
	function upButton(){
		global $wp_query;
		$this->page = 'cart';
		$this->cart->upCart();
		add_action('the_post', array($this, 'action_cartFilter'));
	}
	
	function delButton(){
		global $wp_query;
		$this->page = 'cart';
		$this->cart->del_row();
		add_action('the_post', array($this, 'action_cartFilter'));
	}
	
	function backCart(){
		global $wp_query;
		$this->page = 'cart';
		add_action('the_post', array($this, 'action_cartFilter'));
	}
	
	function customerinfo(){
		global $wp_query;
		if( false === $this->cart->num_row() ){
			header('location: ' . get_option('home'));
			exit;
		}
		$this->cart->entry();
		$this->error_message = $this->zaiko_check();
		if($this->error_message == ''){
			if($this->is_member_logged_in()){
				$this->page = 'delivery';
			}else{
				$this->page = 'customer';
			}
		}else{
			$this->page = 'cart';
		}
		if ( !$this->cart->is_order_condition() ) {
			$order_conditions = $this->get_condition();
			$this->cart->set_order_condition($order_conditions);
		}
		add_action('the_post', array($this, 'action_cartFilter'));

	}
	
	function backCustomer(){
		global $wp_query;
		if( false === $this->cart->num_row() ){
			header('location: ' . get_option('home'));
			exit;
		}
		$this->page = 'customer';
		add_action('the_post', array($this, 'action_cartFilter'));
//		$this->cart->entry();
//		$this->error_message = $this->delivery_check();
//		$this->page = ($this->error_message == '') ? 'customer' : 'delivery';
	}
	
	function customerlogin(){
		global $wp_query;
		if( false === $this->cart->num_row() ){
			header('location: ' . get_option('home'));
			exit;
		}
		$this->cart->entry();
		$this->page = ($this->member_login() == 'member') ? 'delivery' : 'customer';
		add_action('the_post', array($this, 'action_cartFilter'));
	}
	
	function reganddeliveryinfo(){
		global $wp_query;
		if( false === $this->cart->num_row() ){
			header('location: ' . get_option('home'));
			exit;
		}
		$this->cart->entry();
		$_POST['member_regmode'] = 'newmemberfromcart';
		$this->page = ( $this->regist_member() == 'newcompletion' ) ? 'delivery' : 'customer';
		add_action('the_post', array($this, 'action_cartFilter'));
	}
	
	function deliveryinfo(){
		global $wp_query;
		if( false === $this->cart->num_row() ){
			header('location: ' . get_option('home'));
			exit;
		}
		$this->cart->entry();
		$this->error_message = $this->customer_check();
		$this->page = ($this->error_message == '') ? 'delivery' : 'customer';
		add_action('the_post', array($this, 'action_cartFilter'));
	}
	
	function backDelivery(){
		global $wp_query;
		if( false === $this->cart->num_row() ){
			header('location: ' . get_option('home'));
			exit;
		}
		$this->page = 'delivery';
		add_action('the_post', array($this, 'action_cartFilter'));
	}
	
	function confirm(){
		global $wp_query;
		if( false === $this->cart->num_row() ){
			header('location: ' . get_option('home'));
			exit;
		}
		
		$this->cart->entry();
		$this->set_reserve_pre_order_id();
		if(isset($_POST['confirm'])){
			$this->error_message = $this->delivery_check();
		}
		$this->page = ($this->error_message == '') ? 'confirm' : 'delivery';
		add_action('the_post', array($this, 'action_cartFilter'));
	}
	
	function use_point(){
		global $wp_query;
		$this->error_message = $this->point_check();
		$this->cart->entry();
		$this->page = 'confirm';
		add_action('the_post', array($this, 'action_cartFilter'));
	}
	
	function backConfirm(){
		global $wp_query;
		if( false === $this->cart->num_row() ){
			header('location: ' . get_option('home'));
			exit;
		}
		$this->page = 'confirm';
		add_action('the_post', array($this, 'action_cartFilter'));
	}
	
	function purchase(){
		global $wp_query;
		if( false === $this->cart->num_row() ){
			header('location: ' . get_option('home'));
			exit;
		}

		if( !apply_filters('usces_purchase_check', true) ) return;
		
		do_action('usces_purchase_validate');
		$entry = $this->cart->get_entry();
		$this->error_message = $this->zaiko_check();
		if($this->error_message == '' && 0 < $this->cart->num_row()){
			$payments = $this->getPayments( $entry['order']['payment_name'] );
			if( $payments['settlement'] == 'acting' && $entry['order']['total_full_price'] > 0 ){
				$query = '';
				foreach($_POST as $key => $value){
					if($key != 'purchase')
						$query .= '&' . $key . '=' . urlencode($value);
				}
				$actinc_status = $this->acting_processing($payments['module'], $query);
			}
			
			if($actinc_status == 'error'){
				$this->page = 'error';
			}else{
				$res = $this->order_processing();
				$this->page = $res;
			}
		}else{
			$this->page = 'cart';
		}
		add_action('the_post', array($this, 'action_cartFilter'));
	}
	
	function acting_return(){
		global $wp_query;
		if( 'paypal_ipn' == $_REQUEST['acting_return'] ){
			require_once($this->options['settlement_path'] . 'paypal.php');
			$ipn_res = paypal_ipn_check($usces_paypal_url);
			if( $ipn_res[0] === true ){
				$res = $this->order_processing( $ipn_res );
			}
			exit;
		}
		if( false === $this->cart->num_row() ){
			header('location: ' . get_option('home'));
			exit;
		}
		$this->payment_results = usces_check_acting_return();

		if(  isset($this->payment_results[0]) && $this->payment_results[0] === 'duplicate' ){
			header('location: ' . get_option('home'));
			exit;
		}else if( isset($this->payment_results[0]) && $this->payment_results[0] ){
			if( isset($this->payment_results['payment_status']) ){
				$this->page = 'ordercompletion';
			}else{
				$res = $this->order_processing( $this->payment_results );
				$this->page = $res;
			}
		}else{
			$this->page = 'error';
		}
		add_action('the_post', array($this, 'action_cartFilter'));
	}
	
	function settlement_epsilon(){
		global $wp_query;
		require_once($this->options['settlement_path'] . 'epsilon.php');	
	}
	
	function inquiry_button(){
		if( isset($_POST['inq_name']) && '' != trim($_POST['inq_name']) && isset($_POST['inq_mailaddress']) && '' != trim($_POST['inq_mailaddress']) && isset($_POST['inq_contents']) && '' != trim($_POST['inq_contents']) ){
			$res = $this->inquiry_processing();
		}else{
			$res = 'deficiency';
		}
		
		$this->page = $res;
	}
	
	function member_login_page(){
		global $wp_query;
		$res = $this->member_login();
		$this->page = $res;
		add_action('the_post', array($this, 'action_memberFilter'));
	}
	
	function regmember(){
		global $wp_query;
		$res = $this->regist_member();
		$this->page = $res;
		add_action('the_post', array($this, 'action_memberFilter'));
	}
	
	function editmember(){
		global $wp_query;
		$res = $this->regist_member();
		$this->page = $res;
		add_action('the_post', array($this, 'action_memberFilter'));
	}
	
	function page_logout(){
		global $wp_query;
		$this->member_logout();
		add_action('the_post', array($this, 'action_memberFilter'));
	}
	
	function page_lostmemberpassword(){
		global $wp_query;
		$this->page = 'lostmemberpassword';
		add_action('the_post', array($this, 'action_memberFilter'));
	}
	
	function lostpassword(){
		global $wp_query;
		$this->error_message = $this->lostpass_mailaddcheck();
		if ( $this->error_message != '' ) {
			$this->page = 'lostmemberpassword';
		} else {
			$res = $this->lostmail();
			$this->page = $res;//'lostcompletion';
		}
		add_action('the_post', array($this, 'action_memberFilter'));
	}
	
	function uscesmode_changepassword(){
		global $wp_query;
		$this->page = 'changepassword';
		add_action('the_post', array($this, 'action_memberFilter'));
	}
	
	function changepassword_page(){
		global $wp_query;
		$this->error_message = $this->changepass_check();
		if ( $this->error_message != '' ) {
			$this->page = 'changepassword';
		} else {
			$res = $this->changepassword();
			$this->page = $res;//'changepasscompletion';
		}
		add_action('the_post', array($this, 'action_memberFilter'));
	}
	
	function page_newmember(){
		global $wp_query;
		$this->page = 'newmemberform';
		add_action('the_post', array($this, 'action_memberFilter'));
	}
	
	function usces_export(){
		$this->export();
	}
	
	function usces_import(){
		$this->import();
	}
	
	function page_search_item(){
		global $wp_query;
		$this->page = 'search_item';
		add_action('template_redirect', array($this, 'action_search_item'));
		add_action('the_post', array($this, 'action_cartFilter'));
	}
	
	function default_page(){
		global $wp_query;
		add_action('the_post', array($this, 'goDefaultPage'));
	}
	//--------------------------------------------------------------------------------------
	
	
	function goDefaultPage(){
		global $post;
		
		if( $post->ID == USCES_CART_NUMBER ) {
		
			$this->page = 'cart';
			add_filter('the_content', array($this, 'filter_cartContent'),20);

		}else if( $post->ID == USCES_MEMBER_NUMBER ) {
		
			$this->page = 'member';
			add_filter('the_content', array($this, 'filter_memberContent'),20);
		
		}/*else if( is_category() ) {
		
//			$this->page = 'category_item';
//			add_filter('the_content', array($this, 'filter_cartContent'),20);
		
		}*/else if( !is_singular() ) {
			$this->page = 'wp_search';
			add_filter('the_excerpt', array($this, 'filter_cartContent'),20);
			add_filter('the_content', array($this, 'filter_cartContent'),20);
		}
	}
	
	function import() {
		$res = usces_import_xml();
		if ( $res === false ) :
			$this->action_status = 'error';
			//$this->action_message = __('Import was not completed.', 'usces');
		else :
			$this->action_status = 'success';
			$this->action_message = __('Import is cmpleted', 'usces');
		endif;
		
//		require_once(USCES_PLUGIN_DIR . '/includes/admin_backup.php');	
	}

	function export() {
		$filename = 'usces.' . substr(get_date_from_gmt(gmdate('Y-m-d H:i:s', time())), 0, 10) . '.xml';
	
		header('Content-Description: File Transfer');
		header("Content-Disposition: attachment; filename=$filename");
		header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);

		usces_export_xml();
		die();
	
	}

	function changepassword() {
		global $wpdb;

		if ( !isset($_SESSION['usces_lostmail']) ) :
			$this->error_message = __('Failed in update due to time-out', 'usces');
			return 'login';
		else :
		
			$member_table = $wpdb->prefix . "usces_member";
			
			$query = $wpdb->prepare("UPDATE $member_table SET mem_pass = %s WHERE mem_email = %s", 
							md5(trim($_POST['loginpass1'])), $_SESSION['usces_lostmail']);
			$res = $wpdb->query( $query );
			//$res = $wpdb->last_results;

			if ( $res === false ) :
				$this->error_message = __('Error: failure in updating password', 'usces');
				return 'login';
			else :
				return 'changepasscompletion';
			endif;

		endif;
	}
	
	function lostmail() {
	
		$_SESSION['usces_lostmail'] = wp_specialchars(trim($_POST['loginmail']));
		$id = session_id();
		$uri = USCES_MEMBER_URL . '&uscesmode=changepassword';
		$res = usces_lostmail($uri);
		return $res;
	
	}
	
	function regist_member() {
		global $wpdb;
		
		$member = $this->get_member();
		$mode = $_POST['member_regmode'];
		$member_table = $wpdb->prefix . "usces_member";
			
		$error_mes = ( $_POST['member_regmode'] == 'newmemberfromcart' ) ? $this->member_check_fromcart() : $this->member_check();
		
		if ( $error_mes != '' ) {
		
			$this->error_message = $error_mes;
			return $mode;
			
		} elseif ( $_POST['member_regmode'] == 'editmemberform' ) {
	
		$query = $wpdb->prepare("SELECT mem_pass FROM $member_table WHERE ID = %d", $_POST['member_id']);
		$pass = $wpdb->get_var( $query );

		$password = ( !empty($_POST['member']['password1']) && trim($_POST['member']['password1']) == trim($_POST['member']['password2']) ) ? md5(trim($_POST['member']['password1'])) : $pass;
		$query = $wpdb->prepare("UPDATE $member_table SET 
				mem_pass = %s, mem_name1 = %s, mem_name2 = %s, mem_name3 = %s, mem_name4 = %s, 
				mem_zip = %s, mem_pref = %s, mem_address1 = %s, mem_address2 = %s, 
				mem_address3 = %s, mem_tel = %s, mem_fax = %s, mem_email = %s WHERE ID = %d", 
				$password, 
				trim($_POST['member']['name1']), 
				trim($_POST['member']['name2']), 
				trim($_POST['member']['name3']), 
				trim($_POST['member']['name4']), 
				trim($_POST['member']['zipcode']), 
				trim($_POST['member']['pref']), 
				trim($_POST['member']['address1']), 
				trim($_POST['member']['address2']), 
				trim($_POST['member']['address3']), 
				trim($_POST['member']['tel']), 
				trim($_POST['member']['fax']), 
				trim($_POST['member']['mailaddress1']), 
				$_POST['member_id'] 
				);
			$res = $wpdb->query( $query );
			
			$this->get_current_member();
			return 'editmemberform';
			
		} elseif ( $_POST['member_regmode'] == 'newmemberform' ) {

			$query = $wpdb->prepare("SELECT ID FROM $member_table WHERE mem_email = %s", trim($_POST['member']['mailaddress1']));
			$id = $wpdb->get_var( $query );
			if ( !empty($id) ) {
				$this->error_message = __('This e-mail address has been already registered.', 'usces');
				return $mode;
			} else {
			
				$point = $this->options['start_point'];
				$pass = md5(trim($_POST['member']['password1']));
		    	$query = $wpdb->prepare("INSERT INTO $member_table 
						(mem_email, mem_pass, mem_status, mem_cookie, mem_point, 
						mem_name1, mem_name2, mem_name3, mem_name4, mem_zip, mem_pref, 
						mem_address1, mem_address2, mem_address3, mem_tel, mem_fax, 
						mem_delivery_flag, mem_delivery, mem_registered, mem_nicename) 
						VALUES (%s, %s, %d, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s)", 
						trim($_POST['member']['mailaddress1']), 
						$pass, 
						0,
						"",
						$point,
						trim($_POST['member']['name1']), 
						trim($_POST['member']['name2']), 
						trim($_POST['member']['name3']), 
						trim($_POST['member']['name4']), 
						trim($_POST['member']['zipcode']), 
						trim($_POST['member']['pref']), 
						trim($_POST['member']['address1']), 
						trim($_POST['member']['address2']), 
						trim($_POST['member']['address3']), 
						trim($_POST['member']['tel']), 
						trim($_POST['member']['fax']), 
						'',
						'',
						get_date_from_gmt(gmdate('Y-m-d H:i:s', time())),
						'');
				$res = $wpdb->query( $query );
			
				//$_SESSION['usces_member']['ID'] = $wpdb->insert_id;
				//$this->get_current_member();
				if($res !== false) 
					$mser = usces_send_regmembermail();
				
				return 'newcompletion';
			}
			
		} elseif ( $_POST['member_regmode'] == 'newmemberfromcart' ) {

			$query = $wpdb->prepare("SELECT ID FROM $member_table WHERE mem_email = %s", trim($_POST['customer']['mailaddress1']));
			$id = $wpdb->get_var( $query );
			if ( !empty($id) ) {
				$this->error_message = __('This e-mail address has been already registered.', 'usces');
				return $mode;
			} else {
			
				$point = $this->options['start_point'];
				$pass = md5(trim($_POST['customer']['password1']));
		    	$query = $wpdb->prepare("INSERT INTO $member_table 
						(mem_email, mem_pass, mem_status, mem_cookie, mem_point, 
						mem_name1, mem_name2, mem_name3, mem_name4, mem_zip, mem_pref, 
						mem_address1, mem_address2, mem_address3, mem_tel, mem_fax, 
						mem_delivery_flag, mem_delivery, mem_registered, mem_nicename) 
						VALUES (%s, %s, %d, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s)", 
						trim($_POST['customer']['mailaddress1']), 
						$pass, 
						0,
						"",
						$point,
						trim($_POST['customer']['name1']), 
						trim($_POST['customer']['name2']), 
						trim($_POST['customer']['name3']), 
						trim($_POST['customer']['name4']), 
						trim($_POST['customer']['zipcode']), 
						trim($_POST['customer']['pref']), 
						trim($_POST['customer']['address1']), 
						trim($_POST['customer']['address2']), 
						trim($_POST['customer']['address3']), 
						trim($_POST['customer']['tel']), 
						trim($_POST['customer']['fax']), 
						'',
						'',
						get_date_from_gmt(gmdate('Y-m-d H:i:s', time())),
						'');
				$res = $wpdb->query( $query );
				
				//$_SESSION['usces_member']['ID'] = $wpdb->insert_id;
				//$this->get_current_member();
				if( $res ) {
					//usces_send_regmembermail();
					$_POST['loginmail'] = trim($_POST['customer']['mailaddress1']);
					$_POST['loginpass'] = trim($_POST['customer']['password1']);
					if( $this->member_login() == 'member' ){
						$_SESSION['usces_entry']['member_regmode'] = 'editmemberfromcart';
						return 'newcompletion';
					}
				}
				
				return false;
			}
		}
	}

	function is_member_logged_in( $id = false ) {
		if( $id === false ){
			if( isset($_SESSION['usces_member']['ID']) )
				return true;
			else
				return false;
		}else{
			if( isset($_SESSION['usces_member']['ID']) && $_SESSION['usces_member']['ID'] == $id )
				return true;
			else
				return false;
		}
	}

	function is_member($email) {
		global $wpdb;
		
		$member_table = $wpdb->prefix . "usces_member";
		$query = $wpdb->prepare("SELECT mem_email FROM $member_table WHERE mem_email = %s", $email);
		$member = $wpdb->get_row( $query, ARRAY_A );
		if ( empty($member) ) {
			return false;
		}else{
			return true;
		}
	}

	function member_login() {
		global $wpdb;
		
		if ( $_POST['loginmail'] == '' && $_POST['loginpass'] == '' ) {
			return 'login';
		} else if ( $_POST['loginpass'] == '' ) {
			$this->current_member['email'] = wp_specialchars(trim($_POST['loginmail']));
			$this->error_message = __('<b>Error:</b> Enter the password.', 'usces');
			return 'login';
		} else {
			$email = trim($_POST['loginmail']);
			$pass = md5(trim($_POST['loginpass']));
			$member_table = $wpdb->prefix . "usces_member";
	
			$query = $wpdb->prepare("SELECT ID FROM $member_table WHERE mem_email = %s", $email);
			$id = $wpdb->get_var( $query );
			
			if ( !$id ) {
				$this->current_member['email'] = htmlspecialchars($email);
				$this->error_message = __('<b>Error:</b> E-mail address is not correct.', 'usces');
				return 'login';
			} else {
				$query = $wpdb->prepare("SELECT * FROM $member_table WHERE mem_email = %s AND mem_pass = %s", $email, $pass);
				$member = $wpdb->get_row( $query, ARRAY_A );
				if ( empty($member) ) {
					$this->current_member['email'] = htmlspecialchars($email);
					$this->error_message = __('<b>Error:</b> Password is not correct.', 'usces');
					return 'login';
				} else {
					$_SESSION['usces_member']['ID'] = $member['ID'];
					$_SESSION['usces_member']['mailaddress1'] = $member['mem_email'];
					$_SESSION['usces_member']['mailaddress2'] = $member['mem_email'];
					$_SESSION['usces_member']['point'] = $member['mem_point'];
					$_SESSION['usces_member']['name1'] = $member['mem_name1'];
					$_SESSION['usces_member']['name2'] = $member['mem_name2'];
					$_SESSION['usces_member']['name3'] = $member['mem_name3'];
					$_SESSION['usces_member']['name4'] = $member['mem_name4'];
					$_SESSION['usces_member']['zipcode'] = $member['mem_zip'];
					$_SESSION['usces_member']['pref'] = $member['mem_pref'];
					$_SESSION['usces_member']['address1'] = $member['mem_address1'];
					$_SESSION['usces_member']['address2'] = $member['mem_address2'];
					$_SESSION['usces_member']['address3'] = $member['mem_address3'];
					$_SESSION['usces_member']['tel'] = $member['mem_tel'];
					$_SESSION['usces_member']['fax'] = $member['mem_fax'];
					$_SESSION['usces_member']['delivery_flag'] = $member['mem_delivery_flag'];
					$_SESSION['usces_member']['delivery'] = !empty($member['mem_delivery']) ? unserialize($member['mem_delivery']) : '';
					$_SESSION['usces_member']['registered'] = $member['mem_registered'];
					$_SESSION['usces_member']['nicename'] = $member['mem_nicename'];
					$this->get_current_member();
					
					$cookie = $this->get_cookie();
					if(isset($_POST['rememberme']) && $cookie){
						$cookie['name'] = $email;
						$cookie['pass'] = trim($_POST['loginpass']);
						$this->set_cookie($cookie);
					}else{
						$cookie['name'] = '';
						$cookie['pass'] = '';
						$this->set_cookie($cookie);
					}
					return 'member';
				}
			}
		}
	}

	function member_just_login($email, $pass) {
		global $wpdb;
		$pass = md5($pass);
		$member_table = $wpdb->prefix . "usces_member";

		$query = $wpdb->prepare("SELECT * FROM $member_table WHERE mem_email = %s AND mem_pass = %s", $email, $pass);
		$member = $wpdb->get_row( $query, ARRAY_A );
		if ( empty($member) ) {
			$this->current_member['email'] = htmlspecialchars($email);
			$this->error_message = __('<b>Error:</b> Password is not correct.', 'usces');
			return 'login';
		} else {
			$_SESSION['usces_member']['ID'] = $member['ID'];
			$_SESSION['usces_member']['mailaddress1'] = $member['mem_email'];
			$_SESSION['usces_member']['mailaddress2'] = $member['mem_email'];
			$_SESSION['usces_member']['point'] = $member['mem_point'];
			$_SESSION['usces_member']['name1'] = $member['mem_name1'];
			$_SESSION['usces_member']['name2'] = $member['mem_name2'];
			$_SESSION['usces_member']['name3'] = $member['mem_name3'];
			$_SESSION['usces_member']['name4'] = $member['mem_name4'];
			$_SESSION['usces_member']['zipcode'] = $member['mem_zip'];
			$_SESSION['usces_member']['pref'] = $member['mem_pref'];
			$_SESSION['usces_member']['address1'] = $member['mem_address1'];
			$_SESSION['usces_member']['address2'] = $member['mem_address2'];
			$_SESSION['usces_member']['address3'] = $member['mem_address3'];
			$_SESSION['usces_member']['tel'] = $member['mem_tel'];
			$_SESSION['usces_member']['fax'] = $member['mem_fax'];
			$_SESSION['usces_member']['delivery_flag'] = $member['mem_delivery_flag'];
			$_SESSION['usces_member']['delivery'] = !empty($member['mem_delivery']) ? unserialize($member['mem_delivery']) : '';
			$_SESSION['usces_member']['registered'] = $member['mem_registered'];
			$_SESSION['usces_member']['nicename'] = $member['mem_nicename'];
			$this->get_current_member();
			
			$cookie = $this->get_cookie();
			if(isset($_POST['rememberme']) && $cookie){
				$cookie['name'] = $email;
				$cookie['pass'] = trim($_POST['loginpass']);
				$this->set_cookie($cookie);
			}else{
				$cookie['name'] = '';
				$cookie['pass'] = '';
				$this->set_cookie($cookie);
			}
			return 'member';
		}
	}

	function member_logout() {
		unset($_SESSION['usces_member'], $_SESSION['usces_entry']);
		wp_redirect(get_option('home'));
		exit;
	}
	
	function get_current_member() {
		
		if ( isset($_SESSION['usces_member']['ID']) ) {
			$this->current_member['id'] = $_SESSION['usces_member']['ID'];
			$this->current_member['name'] = $_SESSION['usces_member']['name1'] . ' ' . $_SESSION['usces_member']['name2'];
		} else {
			$this->current_member['id'] = 0;
			$this->current_member['name'] = __('guest', 'usces');
		}
	}

	function get_member() {
		foreach ( $_SESSION['usces_member'] as $key => $vlue ) {
			$res[$key] = htmlspecialchars($vlue);
		}
		return $res;
	}

	function is_order($mid, $oid) {
		global $wpdb;
		
		$table = $wpdb->prefix . "usces_order";
		$query = $wpdb->prepare("SELECT ID FROM $table WHERE ID = %d AND mem_id = %d", $oid, $mid);
		$mem_id = $wpdb->get_var( $query );
		if ( empty($mem_id) ) {
			return false;
		}else{
			return true;
		}
	}

	function is_purchased_item($mid, $post_id) {
		global $wpdb;
		$res = false;
		
		$history = $this->get_member_history($mid);
		foreach ( $history as $umhs ) {
			$cart = $umhs['cart'];
			for($i=0; $i<count($cart); $i++) { 
				$cart_row = $cart[$i];
				if($cart_row['post_id'] == $post_id){
					$res = true;
					break 2;
				}
			}
		
		}
			return $res;
	}
	
	function get_order_data($order_id) {
		global $wpdb;
		$order_table = $wpdb->prefix . "usces_order";
	
		$query = $wpdb->prepare("SELECT ID, order_cart, order_condition, order_date, order_usedpoint, order_getpoint, 
								order_discount, order_shipping_charge, order_cod_fee, order_tax, order_status 
							FROM $order_table WHERE ID = %d", $order_id);
		$value = $wpdb->get_row( $query );
	
		if( $value == NULL ) {
			return false;
		}else{
			$res =array();
		}
		if(strpos($value->order_status, 'cancel') !== false || strpos($value->order_status, 'estimate') !== false){
			return false;
		}
		
		$res = array(
					'ID' => $value->ID,
					'cart' => unserialize($value->order_cart),
					'condition' => unserialize($value->order_condition),
					'getpoint' => $value->order_getpoint,
					'usedpoint' => $value->order_usedpoint,
					'discount' => $value->order_discount,
					'shipping_charge' => $value->order_shipping_charge,
					'cod_fee' => $value->order_cod_fee,
					'tax' => $value->order_tax,
					'date' => mysql2date(__('Y/m/d'), $value->order_date)
					);

		return $res;
	}

	function get_orderIDs_by_postID($post_id) {
		global $wpdb;
		$order_table = $wpdb->prefix . "usces_order";
	
		$query = "SELECT ID, order_cart, order_status FROM $order_table";
		$rows = $wpdb->get_query( $query, ARRAY_A );
	
		if( $value == NULL ) {
			return false;
		}else{
			foreach($rows as $row){
				if(strpos($row['order_status'], 'cancel') !== false || strpos($row['order_status'], 'estimate') !== false){
					continue;
				}else{
					$carts = unserialize($row['order_cart']);
					foreach($carts as $cart){
						if( $post_id == $cart['post_id'] ){
							$res[] = $row['ID'];
							break;
						}
					}
				}
			}
		}
		return $res;
	}

	function zaiko_check() {
		$red = '';
		$cart = $this->cart->get_cart();
		for($i=0; $i<count($cart); $i++) { 
			$cart_row = $cart[$i];
			$post_id = $cart_row['post_id'];
			$sku = $cart_row['sku'];
			$quant = ( isset($_POST['quant']) ) ? $_POST['quant'][$i][$post_id][$sku] : $cart_row['quantity'];
			$stock = $this->getItemZaiko($post_id, $sku);
			$zaikonum = $this->getItemZaikoNum($post_id, $sku);
			$red = (in_array($stock, array(__('Sold Out', 'usces'), __('Out Of Stock', 'usces'), __('Out of print', 'usces')))) ? 'red' : '';
		}
		if( $red != '' ){
			$mes = __('Sorry, this item is sold out.', 'usces');
		}else if( $zaikonum != '' && $zaikonum < $quant ){
			$mes = __('Sorry, stock is insufficient.', 'usces');
		}else{
			$mes = '';
		}
		return $mes;	
	}
	
	function member_check() {
		$mes = '';
		foreach ( $_POST['member'] as $key => $vlue ) {
			$_SESSION['usces_member'][$key] = trim($vlue);
		}
		if ( $_POST['member_regmode'] == 'editmemberform' ) {
			if ( (trim($_POST['member']['password1']) != '' || trim($_POST['member']['password2']) != '') && trim($_POST['member']['password1']) != trim($_POST['member']['password2']) )
				$mes .= __('Password is not correct.', 'usces') . "<br />";
			if ( !strstr($_POST['member']['mailaddress1'], '@') || trim($_POST['member']['mailaddress1']) == '' )
				$mes .= __('e-mail address is not correct', 'usces') . "<br />";
				
		} else {
			if ( trim($_POST['member']['password1']) == '' || trim($_POST['member']['password2']) == '' || trim($_POST['member']['password1']) != trim($_POST['member']['password2']) )
				$mes .= __('Password is not correct.', 'usces') . "<br />";
			if ( !strstr($_POST['member']['mailaddress1'], '@') || trim($_POST['member']['mailaddress1']) == '' || trim($_POST['member']['mailaddress2']) == '' || trim($_POST['member']['mailaddress1']) != trim($_POST['member']['mailaddress2']) )
				$mes .= __('e-mail address is not correct', 'usces') . "<br />";
			
		}
		if ( trim($_POST["member"]["name1"]) == "" )
			$mes .= __('Name is not correct', 'usces');
//		if ( trim($_POST["member"]["name3"]) == "" && USCES_JP )
//			$mes .= __('Invalid CANNAT pretend.', 'usces') . "<br />";
		if ( trim($_POST["member"]["zipcode"]) == "" )
			$mes .= __('postal code is not correct', 'usces') . "<br />";
		if ( $_POST["member"]["pref"] == "-選択-" )
			$mes .= __('enter the prefecture', 'usces') . "<br />";
		if ( trim($_POST["member"]["address1"]) == "" )
			$mes .= __('enter the city name', 'usces') . "<br />";
		if ( trim($_POST["member"]["address2"]) == "" )
			$mes .= __('enter house numbers', 'usces') . "<br />";
		if ( trim($_POST["member"]["tel"]) == "" )
			$mes .= __('enter phone numbers', 'usces') . "<br />";
	
		return $mes;
	}

	function member_check_fromcart() {
		$mes = '';
		if ( trim($_POST['customer']['password1']) == '' || trim($_POST['customer']['password2']) == '' || trim($_POST['customer']['password1']) != trim($_POST['customer']['password2']) )
			$mes .= __('Password is not correct.', 'usces') . "<br />";
		if ( !strstr($_POST['customer']['mailaddress1'], '@') || trim($_POST['customer']['mailaddress1']) == '' || trim($_POST['customer']['mailaddress2']) == '' || trim($_POST['customer']['mailaddress1']) != trim($_POST['customer']['mailaddress2']) )
			$mes .= __('e-mail address is not correct', 'usces') . "<br />";
		if ( trim($_POST["customer"]["name1"]) == "" )
			$mes .= __('Name is not correct', 'usces');
//		if ( trim($_POST["customer"]["name3"]) == "" && USCES_JP )
//			$mes .= __('Invalid CANNAT pretend.', 'usces') . "<br />";
		if ( trim($_POST["customer"]["zipcode"]) == "" )
			$mes .= __('postal code is not correct', 'usces') . "<br />";
		if ( $_POST["customer"]["pref"] == "-選択-" )
			$mes .= __('enter the prefecture', 'usces') . "<br />";
		if ( trim($_POST["customer"]["address1"]) == "" )
			$mes .= __('enter the city name', 'usces') . "<br />";
		if ( trim($_POST["customer"]["address2"]) == "" )
			$mes .= __('enter house numbers', 'usces') . "<br />";
		if ( trim($_POST["customer"]["tel"]) == "" )
			$mes .= __('enter phone numbers', 'usces') . "<br />";
	
		return $mes;
	}

	function admin_member_check() {
		$mes = '';
		if ( !is_email( trim($_POST["mem_email"]) ) )
			$mes .= __('e-mail address is not correct', 'usces') . "<br />";
		if ( trim($_POST["mem_name1"]) == "" )
			$mes .= __('Name is not correct', 'usces') . "<br />";
//		if ( trim($_POST["mem_name3"]) == "" && USCES_JP )
//			$mes .= __('Invalid CANNAT pretend.', 'usces') . "<br />";
		if ( trim($_POST["mem_zip"]) == "" )
			$mes .= __('postal code is not correct', 'usces') . "<br />";
		if ( $_POST["mem_pref"] == "-選択-" )
			$mes .= __('enter the prefecture', 'usces') . "<br />";
		if ( trim($_POST["mem_address1"]) == "" )
			$mes .= __('enter the city name', 'usces') . "<br />";
		if ( trim($_POST["mem_address2"]) == "" )
			$mes .= __('enter house numbers', 'usces') . "<br />";
		if ( trim($_POST["mem_tel"]) == "" )
			$mes .= __('enter phone numbers', 'usces') . "<br />";
	
		return $mes;
	}

	function customer_check() {
		$mes = '';
		if ( !strstr($_POST['customer']['mailaddress1'], '@') || trim($_POST['customer']['mailaddress1']) == '' || trim($_POST['customer']['mailaddress2']) == '' || trim($_POST['customer']['mailaddress1']) != trim($_POST['customer']['mailaddress2']) )
			$mes .= __('e-mail address is not correct', 'usces') . "<br />";
		if ( trim($_POST["customer"]["name1"]) == "" )
			$mes .= __('Name is not correct', 'usces');
//		if ( trim($_POST["customer"]["name3"]) == "" && USCES_JP )
//			$mes .= __('Invalid CANNAT pretend.', 'usces') . "<br />";
		if ( trim($_POST["customer"]["zipcode"]) == "" )
			$mes .= __('postal code is not correct', 'usces') . "<br />";
		if ( $_POST["customer"]["pref"] == __('-- Select --', 'usces') )
			$mes .= __('enter the prefecture', 'usces') . "<br />";
		if ( trim($_POST["customer"]["address1"]) == "" )
			$mes .= __('enter the city name', 'usces') . "<br />";
		if ( trim($_POST["customer"]["address2"]) == "" )
			$mes .= __('enter house numbers', 'usces') . "<br />";
		if ( trim($_POST["customer"]["tel"]) == "" )
			$mes .= __('enter phone numbers', 'usces') . "<br />";
	
		return $mes;
	}

	function delivery_check() {
		$mes = '';
		if ( $_POST['customer']['delivery_flag'] == '1' ) {
			if ( trim($_POST["delivery"]["name1"]) == "" )
				$mes .= __('Name is not correct', 'usces');
//			if ( trim($_POST["delivery"]["name3"]) == "" && USCES_JP )
//				$mes .= __('Invalid CANNAT pretend.', 'usces') . "<br />";
			if ( trim($_POST["delivery"]["zipcode"]) == "" )
				$mes .= __('postal code is not correct', 'usces') . "<br />";
			if ( $_POST["delivery"]["pref"] == __('-- Select --', 'usces') )
				$mes .= __('enter the prefecture', 'usces') . "<br />";
			if ( trim($_POST["delivery"]["address1"]) == "" )
				$mes .= __('enter the city name', 'usces') . "<br />";
			if ( trim($_POST["delivery"]["address2"]) == "" )
				$mes .= __('enter house numbers', 'usces') . "<br />";
			if ( trim($_POST["delivery"]["tel"]) == "" )
				$mes .= __('enter phone numbers', 'usces') . "<br />";
		}
		if ( !isset($_POST['order']['delivery_method']) || (empty($_POST['order']['delivery_method']) && $_POST['order']['delivery_method'] != 0) )
			$mes .= __('chose one from delivery method.', 'usces') . "<br />";
		if ( !isset($_POST['order']['payment_name']) )
			$mes .= __('chose one from payment options.', 'usces') . "<br />";
	
		return $mes;
	}

	function point_check() {
		$member = $this->get_member();
		$this->set_cart_fees( $member, &$entries );
		$mes = '';
		if ( trim($_POST['order']["usedpoint"]) == "" || !(int)$_POST['order']["usedpoint"] || (int)$_POST['order']["usedpoint"] < 0 ) {
			$mes .= __('Invalid value. Please enter in the numbers.', 'usces') . "<br />";
		} elseif ( trim($_POST['order']["usedpoint"]) > $member['point'] || trim($_POST['order']["usedpoint"]) > $entries['order']['total_price']) {
			$mes .= __('You have exceeded the maximum available.', 'usces') . "<br />";
			$_POST['order']["usedpoint"] = 0;
		}

		return $mes;
	}

	function lostpass_mailaddcheck() {
		$mes = '';
		if ( !strstr($_POST['loginmail'], '@') || trim($_POST['loginmail']) == '' ) {
			$mes .= __('e-mail address is not correct', 'usces') . "<br />";
		}elseif( !$this->is_member($_POST['loginmail']) ){
			$mes .= __('It is the e-mail address that there is not.', 'usces') . "<br />";
		}

		return $mes;
	}

	function changepass_check() {
		$mes = '';
		if ( trim($_POST['loginpass1']) == '' || trim($_POST['loginpass2']) == '' || (trim($_POST['loginpass1']) != trim($_POST['loginpass2'])))
			$mes .= __('Password is not correct.', 'usces') . "<br />";

		return $mes;
	}

	function get_page() {
		return $this->page;
	}
	
	function check_display_mode() {
		$options = get_option('usces');
		if($options['display_mode'] == 'Maintenancemode') return;
		
		$start = $options['campaign_schedule']['start'];
		$end = $options['campaign_schedule']['end'];
		$starttime = mktime($start['hour'], $start['min'], 0, $start['month'], $start['day'], $start['year']); 
		$endtime = mktime($end['hour'], $end['min'], 0, $end['month'], $end['day'], $end['year']); 

		if( (time() >= $starttime) && (time() <= $endtime) )
			$options['display_mode'] = 'Promotionsale';
		else
			$options['display_mode'] = 'Usualsale';
		
		update_option('usces', $options);
	
	}
	
	function update_business_days() {
		$options = get_option('usces');
		$datenow = getdate();
		list($year, $mon, $mday) = getBeforeMonth($datenow['year'], $datenow['mon'], $datenow['mday'], 1);
		
		if(isset($options['business_days'][$year][$mon][1]))
			unset($options['business_days'][$year][$mon]);
		
		for($i=0; $i<3; $i++){
			list($year, $mon, $mday) = getAfterMonth($datenow['year'], $datenow['mon'], $datenow['mday'], $i);
			$last = getLastDay($year, $mon);
			for($j=1; $j<=$last; $j++){
				if(!isset($options['business_days'][$year][$mon][$j]))
					$options['business_days'][$year][$mon][$j] = 1;
			}
		}

		update_option('usces', $options);

		$_SESSION['usces_checked_business_days'] = '';
	}
	 
	function display_cart() { 
		if($this->cart->num_row() > 0) {
			include (USCES_PLUGIN_DIR . '/includes/cart_table.php');
		} else {
			echo "<div class='no_cart'>" . __('There is no items in your cart.', 'usces') . "</div>\n";
		}
	}

	function display_cart_confirm() { 
		if($this->cart->num_row() > 0) {
			include (USCES_PLUGIN_DIR . '/includes/cart_confirm.php');
		} else {
			echo "<div class='no_cart'>" . __('There is no items in your cart.', 'usces') . "</div>\n";
		}
	}

	//
	function set_initial()
	{
		usces_metakey_change();
		
		$this->set_default_theme();
		$this->set_default_page();
		$this->set_default_categories();
		$this->create_table();
		$this->update_table();
	}
	
	function create_table()
	{
		global $wpdb;
		
		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty($wpdb->charset) )
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if ( ! empty($wpdb->collate) )
				$charset_collate .= " COLLATE $wpdb->collate";
		}
		
		$access_table = $wpdb->prefix . "usces_access";
		$member_table = $wpdb->prefix . "usces_member";
		$order_table = $wpdb->prefix . "usces_order";
		$order_meta_table = $wpdb->prefix . "usces_order_meta";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		if($wpdb->get_var("show tables like '$member_table'") != $member_table) {
		
			$sql = "CREATE TABLE " . $access_table . " (
				ID BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				acc_type VARCHAR( 20 ) NOT NULL ,
				acc_num1 INT( 11 ) NOT NULL DEFAULT '0',
				acc_num2 INT( 11 ) NOT NULL DEFAULT '0',
				acc_str1 VARCHAR( 100 ) NULL ,
				acc_str2 VARCHAR( 100 ) NULL ,
				acc_date DATE NOT NULL DEFAULT '0000-00-00',
				KEY acc_date ( acc_date )  
				) ENGINE = MYISAM AUTO_INCREMENT=0 $charset_collate;";
		
			dbDelta($sql);
			add_option("usces_db_access", USCES_DB_ACCESS);
		}
		if($wpdb->get_var("show tables like '$member_table'") != $member_table) {
		
			$sql = "CREATE TABLE " . $member_table . " (
				ID BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				mem_email VARCHAR( 100 ) NOT NULL ,
				mem_pass VARCHAR( 64 ) NOT NULL ,
				mem_status INT( 11 ) NOT NULL DEFAULT '0',
				mem_cookie VARCHAR( 13 ) NULL ,
				mem_point INT( 11 ) NOT NULL DEFAULT '0',
				mem_name1 VARCHAR( 100 ) NOT NULL ,
				mem_name2 VARCHAR( 100 ) NULL ,
				mem_name3 VARCHAR( 100 ) NULL ,
				mem_name4 VARCHAR( 100 ) NULL ,
				mem_zip VARCHAR( 50 ) NULL ,
				mem_pref VARCHAR( 100 ) NOT NULL ,
				mem_address1 VARCHAR( 100 ) NOT NULL ,
				mem_address2 VARCHAR( 100 ) NULL ,
				mem_address3 VARCHAR( 100 ) NULL ,
				mem_tel VARCHAR( 100 ) NOT NULL ,
				mem_fax VARCHAR( 100 ) NULL ,
				mem_delivery_flag TINYINT ( 1 ) NULL ,
				mem_delivery LONGTEXT,
				mem_registered DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
				mem_nicename VARCHAR( 50 ) NULL ,
				KEY mem_email ( mem_email ) ,  
				KEY mem_pass ( mem_pass )  
				) ENGINE = MYISAM AUTO_INCREMENT=1000 $charset_collate;";
		
			dbDelta($sql);
			add_option("usces_db_member", USCES_DB_MEMBER);
		}
		if($wpdb->get_var("show tables like '$order_table'") != $order_table) {
		
			$sql = "CREATE TABLE " . $order_table . " (
				ID BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				mem_id BIGINT( 20 ) UNSIGNED NULL ,
				order_email VARCHAR( 100 ) NOT NULL ,
				order_name1 VARCHAR( 100 ) NOT NULL ,
				order_name2 VARCHAR( 100 ) NULL ,
				order_name3 VARCHAR( 100 ) NULL ,
				order_name4 VARCHAR( 100 ) NULL ,
				order_zip VARCHAR( 50 ) NULL ,
				order_pref VARCHAR( 100 ) NOT NULL ,
				order_address1 VARCHAR( 100 ) NOT NULL ,
				order_address2 VARCHAR( 100 ) NULL ,
				order_address3 VARCHAR( 100 ) NULL ,
				order_tel VARCHAR( 100 ) NOT NULL ,
				order_fax VARCHAR( 100 ) NULL ,
				order_delivery LONGTEXT,
				order_cart LONGTEXT,
				order_note TEXT,
				order_delivery_time VARCHAR( 100 ) NOT NULL ,
				order_payment_name VARCHAR( 100 ) NOT NULL ,
				order_condition TEXT,
				order_item_total_price INT( 10 ) NOT NULL DEFAULT '0',
				order_getpoint INT( 10 ) NOT NULL DEFAULT '0',
				order_usedpoint INT( 10 ) NOT NULL DEFAULT '0',
				order_discount INT( 10 ) NOT NULL DEFAULT '0',
				order_shipping_charge INT( 10 ) NOT NULL DEFAULT '0',
				order_cod_fee INT( 10 ) NOT NULL DEFAULT '0',
				order_tax INT( 10 ) NOT NULL DEFAULT '0',
				order_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
				order_modified VARCHAR( 20 ) NULL ,
				order_status VARCHAR( 255 ) NULL ,
				order_check VARCHAR( 255 ) NULL ,
				order_delidue_date VARCHAR( 30 ) NULL ,
				order_delivery_method INT( 10 ) NOT NULL DEFAULT -1,
				KEY order_email ( order_email ) ,  
				KEY order_name1 ( order_name1 ) ,  
				KEY order_name2 ( order_name2 ) ,  
				KEY order_pref ( order_pref ) ,  
				KEY order_address1 ( order_address1 ) ,  
				KEY order_tel ( order_tel ) ,  
				KEY order_date ( order_date )  
				) ENGINE = MYISAM AUTO_INCREMENT=1000 $charset_collate;";
		
			dbDelta($sql);
			add_option("usces_db_order", USCES_DB_ORDER);
		}
		if($wpdb->get_var("show tables like '$order_meta_table'") != $order_meta_table) {
		
			$sql = "CREATE TABLE " . $order_meta_table . " (
				ometa_id bigint(20) NOT NULL auto_increment,
				order_id bigint(20) NOT NULL default '0',

				meta_key varchar(255) default NULL,
				meta_value longtext,
				PRIMARY KEY  (ometa_id),
				KEY order_id (order_id),
				KEY meta_key (meta_key)
				) ENGINE = MYISAM $charset_collate;";
		
			dbDelta($sql);
			add_option("usces_db_order_meta", USCES_DB_ORDER_META);
		}

	}
	
	function update_table()
	{
		global $wpdb;
		$access_table = $wpdb->prefix . "usces_access";
		$member_table = $wpdb->prefix . "usces_member";
		$order_table = $wpdb->prefix . "usces_order";
		$order_meta_table = $wpdb->prefix . "usces_order_meta";
		
		$access_ver = get_option( "usces_db_access" );
		$member_ver = get_option( "usces_db_member" );
		$order_ver = get_option( "usces_db_order" );
		$order_meta_ver = get_option( "usces_db_order_meta" );
		
		if( $access_ver != USCES_DB_ACCESS ) {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			$sql = "CREATE TABLE " . $access_table . " (
				ID BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				acc_type VARCHAR( 20 ) NOT NULL ,
				acc_num1 INT( 11 ) NOT NULL DEFAULT '0',
				acc_num2 INT( 11 ) NOT NULL DEFAULT '0',
				acc_str1 VARCHAR( 100 ) NULL ,
				acc_str2 VARCHAR( 100 ) NULL ,
				acc_date DATE NOT NULL DEFAULT '0000-00-00',
				KEY acc_date ( acc_date )  
				) ENGINE = MYISAM;";
			
			dbDelta($sql);
			update_option( "usces_db_access", USCES_DB_ACCESS );
		}
		if( $member_ver != USCES_DB_MEMBER ) {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			$sql = "CREATE TABLE " . $member_table . " (
				ID BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				mem_email VARCHAR( 100 ) NOT NULL ,
				mem_pass VARCHAR( 64 ) NOT NULL ,
				mem_status INT( 11 ) NOT NULL DEFAULT '0',
				mem_cookie VARCHAR( 13 ) NULL ,
				mem_point INT( 11 ) NOT NULL DEFAULT '0',
				mem_name1 VARCHAR( 100 ) NOT NULL ,
				mem_name2 VARCHAR( 100 ) NULL ,
				mem_name3 VARCHAR( 100 ) NULL ,
				mem_name4 VARCHAR( 100 ) NULL ,
				mem_zip VARCHAR( 50 ) NULL ,
				mem_pref VARCHAR( 100 ) NOT NULL ,
				mem_address1 VARCHAR( 100 ) NOT NULL ,
				mem_address2 VARCHAR( 100 ) NULL ,
				mem_address3 VARCHAR( 100 ) NULL ,
				mem_tel VARCHAR( 100 ) NOT NULL ,
				mem_fax VARCHAR( 100 ) NULL ,
				mem_delivery_flag TINYINT ( 1 ) NULL ,
				mem_delivery LONGTEXT,
				mem_registered DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
				mem_nicename VARCHAR( 50 ) NULL ,
				KEY mem_email ( mem_email ) ,  
				KEY mem_pass ( mem_pass )  
				) ENGINE = MYISAM ;";
			
			dbDelta($sql);
			update_option( "usces_db_member", USCES_DB_MEMBER );
		}
		if( $order_ver != USCES_DB_ORDER ) {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			$sql = "CREATE TABLE " . $order_table . " (
				ID BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				mem_id BIGINT( 20 ) UNSIGNED NULL ,
				order_email VARCHAR( 100 ) NOT NULL ,
				order_name1 VARCHAR( 100 ) NOT NULL ,
				order_name2 VARCHAR( 100 ) NULL ,
				order_name3 VARCHAR( 100 ) NULL ,
				order_name4 VARCHAR( 100 ) NULL ,
				order_zip VARCHAR( 50 ) NULL ,
				order_pref VARCHAR( 100 ) NOT NULL ,
				order_address1 VARCHAR( 100 ) NOT NULL ,
				order_address2 VARCHAR( 100 ) NULL ,
				order_address3 VARCHAR( 100 ) NULL ,
				order_tel VARCHAR( 100 ) NOT NULL ,
				order_fax VARCHAR( 100 ) NULL ,
				order_delivery LONGTEXT,
				order_cart LONGTEXT,
				order_note TEXT,
				order_delivery_time VARCHAR( 100 ) NOT NULL ,
				order_payment_name VARCHAR( 100 ) NOT NULL ,
				order_condition TEXT,
				order_item_total_price INT( 10 ) NOT NULL DEFAULT '0',
				order_getpoint INT( 10 ) NOT NULL DEFAULT '0',
				order_usedpoint INT( 10 ) NOT NULL DEFAULT '0',
				order_discount INT( 10 ) NOT NULL DEFAULT '0',
				order_shipping_charge INT( 10 ) NOT NULL DEFAULT '0',
				order_cod_fee INT( 10 ) NOT NULL DEFAULT '0',
				order_tax INT( 10 ) NOT NULL DEFAULT '0',
				order_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
				order_modified VARCHAR( 20 ) NULL ,
				order_status VARCHAR( 255 ) NULL ,
				order_check VARCHAR( 255 ) NULL ,
				order_delidue_date VARCHAR( 30 ) NULL ,
				order_delivery_method INT( 10 ) NOT NULL DEFAULT -1,
				KEY order_email ( order_email ) ,  
				KEY order_name1 ( order_name1 ) ,  
				KEY order_name2 ( order_name2 ) ,  
				KEY order_pref ( order_pref ) ,  
				KEY order_address1 ( order_address1 ) ,  
				KEY order_tel ( order_tel ) ,  
				KEY order_date ( order_date )  
				) ENGINE = MYISAM;";
		
			dbDelta($sql);
			update_option("usces_db_order", USCES_DB_ORDER);
		}
		if( $order_meta_ver != USCES_DB_ORDER_META ) {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			$sql = "CREATE TABLE " . $order_meta_table . " (
				ometa_id bigint(20) NOT NULL auto_increment,
				order_id bigint(20) NOT NULL default '0',
				meta_key varchar(255) default NULL,
				meta_value longtext,
				PRIMARY KEY  (ometa_id),
				KEY order_id (order_id),
				KEY meta_key (meta_key)
				) ENGINE = MYISAM';";
		
			dbDelta($sql);
			update_option("usces_db_order_meta", USCES_DB_ORDER_META);
		}
	}
	
	function set_default_theme()
	{
		$themepath = USCES_WP_CONTENT_DIR.'/themes/welcart_default';
		$resourcepath = USCES_WP_CONTENT_DIR.'/plugins/usc-e-shop/theme/welcart_default';
		if( file_exists($themepath) ) return false;
		if(!file_exists($resourcepath) ) return false;
		
		mkdir($themepath);
		$this->dir_copy($resourcepath, $themepath);
	
	}
	
	function dir_copy($source, $dest){
		if ($res = opendir($source)) {
			while (($file = readdir($res)) !== false) {
				$sorce_path = $source . '/' . $file;
				$dest_path = $dest . '/' . $file;
				$filetype = @filetype($sorce_path);
				if( $filetype == 'file' ) {
					copy($sorce_path, $dest_path);
				}elseif( $filetype == 'dir' && $file != '..' && $file != '.' ){
					mkdir($dest_path);
					$this->dir_copy($sorce_path, $dest_path);
				}
			}
			closedir($res);
		}
	}

	function set_default_page()
	{
		global $wpdb;
		$datetime = get_date_from_gmt(gmdate('Y-m-d H:i:s', time()));
		$datetime_gmt = gmdate('Y-m-d H:i:s', time());

		//cart_page
		$query = $wpdb->prepare("SELECT ID from $wpdb->posts where post_name = %s", USCES_CART_FOLDER);
		$cart_number = $wpdb->get_var( $query );
		if( $cart_number === NULL ) {
			$query = $wpdb->prepare("INSERT INTO $wpdb->posts 
				(post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, 
				comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, 
				post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
				VALUES (%d, %s, %s, %s, %s, %s, %s, 
				%s, %s, %s, %s, %s, %s, %s, %s, 
				%s, %d, %s, %d, %s, %s, %d)", 
				1, $datetime, $datetime_gmt, '', __('Cart', 'usces'), '', 'publish', 
				'closed', 'closed', '', USCES_CART_FOLDER, '', '', $datetime, $datetime_gmt, 
				'', 0, '', 0, 'page', '', 0);
			$wpdb->query($query);
			$cart_number = $wpdb->insert_id;
			if( $cart_number !== NULL ) {
				$query = $wpdb->prepare("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES (%d, %s, %s)", 
					$cart_number, '_wp_page_template', 'uscescart.php');
				$wpdb->query($query);
			}
		}
		update_option('usces_cart_number', $cart_number);
		
		//member_page
		$query = $wpdb->prepare("SELECT ID from $wpdb->posts where post_name = %s", USCES_MEMBER_FOLDER);
		$member_number = $wpdb->get_var( $query );
		if( $member_number === NULL ) {
			$query = $wpdb->prepare("INSERT INTO $wpdb->posts 
				(post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, 
				comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, 
				post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
				VALUES (%d, %s, %s, %s, %s, %s, %s, 
				%s, %s, %s, %s, %s, %s, %s, %s, 
				%s, %d, %s, %d, %s, %s, %d)", 
				1, $datetime, $datetime_gmt, '', __('Membership', 'usces'), '', 'publish', 
				'closed', 'closed', '', USCES_MEMBER_FOLDER, '', '', $datetime, $datetime_gmt, 
				'', 0, '', 0, 'page', '', 0);
			$wpdb->query($query);
			$member_number = $wpdb->insert_id;
			if( $member_number !== NULL ) {
				$query = $wpdb->prepare("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES (%d, %s, %s)", 
					$member_number, '_wp_page_template', 'uscesmember.php');
				$wpdb->query($query);
			}
		}
		update_option('usces_member_number', $member_number);
		
		//footernavi page
/*		$footernaviid = usces_get_page_ID_by_pname( 'usces-footernavi', 'return' );
		if( $footernaviid === NULL ) {
			$query = $wpdb->prepare("INSERT INTO $wpdb->posts 
				(post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, 
				comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, 
				post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
				VALUES (%d, %s, %s, %s, %s, %s, %s, 
				%s, %s, %s, %s, %s, %s, %s, %s, 
				%s, %d, %s, %d, %s, %s, %d)", 
				1, $datetime, $datetime_gmt, '', 'フッタナビ用ダミーページ', '', 'publish', 
				'closed', 'closed', '', 'usces-footernavi', '', '', $datetime, $datetime_gmt, 
				'', 0, '', 0, 'page', '', 0);
			$wpdb->query($query);
			$ser_id = $wpdb->insert_id;
			if( $ser_id !== NULL ) {
				$xml = USCES_PLUGIN_DIR . '/includes/initial_data.xml';
				$match = $this->get_initial_data($xml);
				foreach($match as $data){
					$title = $data[1];
					$status = $data[2];
					$name = $data[3];
					$content = $data[4];
					if( $name == 'usces-privacy' || $name == 'usces-company' || $name == 'usces-law' ) {
						$query2 = $wpdb->prepare("INSERT INTO $wpdb->posts 
							(post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, 
							comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, 
							post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
							VALUES (%d, %s, %s, %s, %s, %s, %s, 
							%s, %s, %s, %s, %s, %s, %s, %s, 
							%s, %d, %s, %d, %s, %s, %d)", 
							1, $datetime, $datetime_gmt, $content, $title, '', $status, 
							'closed', 'closed', '', $name, '', '', $datetime, $datetime_gmt, 
							'', $ser_id, '', 0, 'page', '', 0);
						$wpdb->query($query2);
					}
				}
			}
		}
		
		//mainnavi page
		$mainnaviid = usces_get_page_ID_by_pname( 'usces-mainnavi', 'return' );
		if( $mainnaviid === NULL ) {
			$query = $wpdb->prepare("INSERT INTO $wpdb->posts 
				(post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, 
				comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, 
				post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
				VALUES (%d, %s, %s, %s, %s, %s, %s, 
				%s, %s, %s, %s, %s, %s, %s, %s, 
				%s, %d, %s, %d, %s, %s, %d)", 
				1, $datetime, $datetime_gmt, '', 'メインナビ用ダミーページ', '', 'publish', 
				'closed', 'closed', '', 'usces-mainnavi', '', '', $datetime, $datetime_gmt, 
				'', 0, '', 0, 'page', '', 0);
			$wpdb->query($query);
			$ser_id = $wpdb->insert_id;
			if( $ser_id !== NULL ) {
				$xml = USCES_PLUGIN_DIR . '/includes/initial_data.xml';
				$match = $this->get_initial_data($xml);
				foreach($match as $data){
					$title = $data[1];
					$status = $data[2];
					$name = $data[3];
					$content = $data[4];
					if( $name == 'usces-inquiry' || $name == 'usces-guid' ) {
						$query2 = $wpdb->prepare("INSERT INTO $wpdb->posts 
							(post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, 
							comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, 
							post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
							VALUES (%d, %s, %s, %s, %s, %s, %s, 
							%s, %s, %s, %s, %s, %s, %s, %s, 
							%s, %d, %s, %d, %s, %s, %d)", 
							1, $datetime, $datetime_gmt, $content, $title, '', $status, 
							'closed', 'closed', '', $name, '', '', $datetime, $datetime_gmt, 
							'', $ser_id, '', 0, 'page', '', 0);
						$wpdb->query($query2);
						$meta_id = $wpdb->insert_id;
						if( $meta_id !== NULL && $name == 'usces-inquiry' ) {
							$query3 = $wpdb->prepare("INSERT INTO $wpdb->postmeta 
								(post_id, meta_key, meta_value) VALUES (%d, %s, %s)", 
								$meta_id, '_wp_page_template', 'inquiry.php');
							$wpdb->query($query3);
						}
					}
				}
			}
		}
		
		//search in detail page
		$searchid = usces_get_page_ID_by_pname( 'usces-search-in-detail', 'return' );
		if( $searchid === NULL ) {
			$query = $wpdb->prepare("INSERT INTO $wpdb->posts 
				(post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, 
				comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, 
				post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
				VALUES (%d, %s, %s, %s, %s, %s, %s, 
				%s, %s, %s, %s, %s, %s, %s, %s, 
				%s, %d, %s, %d, %s, %s, %d)", 
				1, $datetime, $datetime_gmt, '', 'Search in detail', '', 'publish', 
				'closed', 'closed', '', 'usces-search-in-detail', '', '', $datetime, $datetime_gmt, 
				'', 0, '', 0, 'page', '', 0);
			$wpdb->query($query);
			$ser_id = $wpdb->insert_id;
			if( $ser_id !== NULL ) {
				$query2 = $wpdb->prepare("INSERT INTO $wpdb->postmeta 
					(post_id, meta_key, meta_value) VALUES (%d, %s, %s)", 
					$ser_id, '_wp_page_template', 'uscesearch.php');
				$wpdb->query($query2);
			}
		}
*/		
		
	}
	
	function set_default_categories()
	{
		global $wpdb;
		
		
		//$wpdb->show_errors();

		//item_parent
		$query = "SELECT term_id FROM $wpdb->terms WHERE slug = 'item'";
		$item_parent = $wpdb->get_var( $query );
		if($item_parent === NULL) {
			$query = $wpdb->prepare("INSERT INTO $wpdb->terms (name, slug, term_group) VALUES (%s, %s, %d)", 
				__('Items', 'usces'), 'item', 0);
			$wpdb->query($query);
			$item_parent = $wpdb->insert_id;
			if( $item_parent !== NULL ) {
				$query = $wpdb->prepare("INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description, parent, count) 
					VALUES (%d, %s, %s, %d, %d)", $item_parent, 'category', '', 0, 0);
				$wpdb->query($query);
			}
		}
		update_option('usces_item_cat_parent_id', $item_parent);

		//item_reco
		$query = "SELECT term_id FROM $wpdb->terms WHERE slug = 'itemreco'";
		$item_id = $wpdb->get_var( $query );
		if($item_id === NULL) {
			$query = $wpdb->prepare("INSERT INTO $wpdb->terms (name, slug, term_group) VALUES (%s, %s, %d)", 
				__('Items recommended', 'usces'), 'itemreco', 0);
			$wpdb->query($query);
			$item_id = $wpdb->insert_id;
			if( $item_id !== NULL ) {
				$query = $wpdb->prepare("INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description, parent, count) 
					VALUES (%d, %s, %s, %d, %d)", $item_id, 'category', '', $item_parent, 0);
				$wpdb->query($query);
			}
		}

		//item_new
		$query = "SELECT term_id FROM $wpdb->terms WHERE slug = 'itemnew'";
		$item_id = $wpdb->get_var( $query );
		if($item_id === NULL) {
			$query = $wpdb->prepare("INSERT INTO $wpdb->terms (name, slug, term_group) VALUES (%s, %s, %d)", 
				__('New items', 'usces'), 'itemnew', 0);
			$wpdb->query($query);
			$item_id = $wpdb->insert_id;
			if( $item_id !== NULL ) {
				$query = $wpdb->prepare("INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description, parent, count) 
					VALUES (%d, %s, %s, %d, %d)", $item_id, 'category', '', $item_parent, 0);
				$wpdb->query($query);
			}
		}

		//item_category
		$query = "SELECT term_id FROM $wpdb->terms WHERE slug = 'itemgenre'";
		$item_id = $wpdb->get_var( $query );
		if($item_id === NULL) {
			$query = $wpdb->prepare("INSERT INTO $wpdb->terms (name, slug, term_group) VALUES (%s, %s, %d)", 
				__('Item genre', 'usces'), 'itemgenre', 0);
			$wpdb->query($query);
			$item_id = $wpdb->insert_id;
			if( $item_id !== NULL ) {
				$query = $wpdb->prepare("INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description, parent, count) 
					VALUES (%d, %s, %s, %d, %d)", $item_id, 'category', '', $item_parent, 0);
				$wpdb->query($query);
			}
		}

		//item_discount
/*		$query = "SELECT term_id FROM $wpdb->terms WHERE slug = 'itemdiscount'";
		$item_id = $wpdb->get_var( $query );
		if($item_id === NULL) {
			$query = $wpdb->prepare("INSERT INTO $wpdb->terms (name, slug, term_group) VALUES (%s, %s, %d)", 
				__('Special price', 'usces'), 'itemdiscount', 0);
			$wpdb->query($query);
			$item_id = $wpdb->insert_id;
			if( $item_id !== NULL ) {
				$query = $wpdb->prepare("INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description, parent, count) 
					VALUES (%d, %s, %s, %d, %d)", $item_id, 'category', '', $item_parent, 0);
				$wpdb->query($query);
			}
		}

		//news
		$query = "SELECT term_id FROM $wpdb->terms WHERE slug = 'news'";
		$item_id = $wpdb->get_var( $query );
		if($item_id === NULL) {
			$query = $wpdb->prepare("INSERT INTO $wpdb->terms (name, slug, term_group) VALUES (%s, %s, %d)", 
				'お知らせ', 'news', 0);
			$wpdb->query($query);
			$item_id = $wpdb->insert_id;
			if( $item_id !== NULL ) {
				$query = $wpdb->prepare("INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description, parent, count) 
					VALUES (%d, %s, %s, %d, %d)", $item_id, 'category', '', 0, 0);
				$wpdb->query($query);
			}
		}

		//blog
		$query = "SELECT term_id FROM $wpdb->terms WHERE slug = 'blog'";
		$item_id = $wpdb->get_var( $query );
		if($item_id === NULL) {
			$query = $wpdb->prepare("INSERT INTO $wpdb->terms (name, slug, term_group) VALUES (%s, %s, %d)", 
				'ブログ', 'blog', 0);
			$wpdb->query($query);
			$item_id = $wpdb->insert_id;
			if( $item_id !== NULL ) {
				$query = $wpdb->prepare("INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description, parent, count) 
					VALUES (%d, %s, %s, %d, %d)", $item_id, 'category', '', 0, 0);
				$wpdb->query($query);
			}
		}
*/
	}

	function set_item_mime($post_id, $str)
	{
		global $wpdb;
		if($str == '') return;
		
		$query = $wpdb->prepare("UPDATE $wpdb->posts SET post_mime_type = %s WHERE ID = %s", $str, $post_id);
		$results = $wpdb->query( $query );
		return $results;
	}
	
	function isAdnminSSL()
	{
		$plugins = get_option('active_plugins');
		foreach($plugins as $plugin) {
			if( strpos($plugin, USCES_ADMIN_SSL_BASE_NAME) )
				return true;
		}
		return false;
	}
	
	function getGuidTax() {
		if ( (int)$this->options['tax_rate'] > 0 )
			return '<em class="tax">'.__('(Excl. Tax)', 'usces').'</em>';
		else
			return '<em class="tax">'.__('(Incl. Tax)', 'usces').'</em>';
	}

	function getItemCode($post_id) {
		$str = get_post_custom_values('_itemCode', $post_id);
		return $str[0];
	}
	
	function getItemName($post_id) {
		$str = get_post_custom_values('_itemName', $post_id);
		return $str[0];
	}
	
	function getItemRestriction($post_id) {
		$str = get_post_custom_values('_itemRestriction', $post_id);
		return $str[0];
	}
	
	function getItemPointrate($post_id) {
		$str = get_post_custom_values('_itemPointrate', $post_id);
		return $str[0];
	}
	
	function getItemShipping($post_id) {
		$str = get_post_custom_values('_itemShipping', $post_id);
		return $str[0];
	}
	
	function getItemShippingCharge($post_id) {
		$str = get_post_custom_values('_itemShippingCharge', $post_id);
		return (int)$str[0];
	}
	
	function getItemDeliveryMethod($post_id) {
		$str = get_post_custom_values('_itemDeliveryMethod', $post_id);
		return unserialize($str[0]);
	}
	
	function getItemIndividualSCharge($post_id) {
		$str = get_post_custom_values('_itemIndividualSCharge', $post_id);
		return $str[0];
	}
	
	function getItemGpNum1($post_id) {
		$str = get_post_custom_values('_itemGpNum1', $post_id);
		return $str[0];
	}
	
	function getItemGpNum2($post_id) {
		$str = get_post_custom_values('_itemGpNum2', $post_id);
		return $str[0];
	}
	
	function getItemGpNum3($post_id) {
		$str = get_post_custom_values('_itemGpNum3', $post_id);
		return $str[0];
	}
	
	function getItemGpDis1($post_id) {
		$str = get_post_custom_values('_itemGpDis1', $post_id);
		return $str[0];
	}
	
	function getItemGpDis2($post_id) {
		$str = get_post_custom_values('_itemGpDis2', $post_id);
		return $str[0];
	}
	
	function getItemGpDis3($post_id) {
		$str = get_post_custom_values('_itemGpDis3', $post_id);
		return $str[0];
	}
	
	function getItemSku($post_id, $index = '') {
		$fields = get_post_custom($post_id);
		foreach((array)$fields as $key => $value){
			if( preg_match('/^_isku_/', $key, $match) ){
				$key = substr($key, 6);
				$skus[] = $key;
			}
		}
		if(!$skus) return false;
		if($index == ''){
			return $skus;
		}else if(isset($skus[$index])){
			return $skus[$index];
		}else{
			return false;
		}
	}
	
	function getItemPrice($post_id, $skukey = '') {
		$fields = get_post_custom($post_id);
		foreach((array)$fields as $key => $value){
			if( preg_match('/^_isku_/', $key, $match) ){
				$key = substr($key, 6);
				$values = maybe_unserialize($value[0]);
				$skus[$key] = (float)str_replace(',', '', $values['price']);
			}
		}
		if(!$skus) return false;
		if($skukey == ''){
			return $skus;
		}else if(isset($skus[$skukey])){
			return $skus[$skukey];
		}else{
			return false;
		}
	}
	
	function getItemZaiko($post_id, $skukey = '') {
		$fields = get_post_custom($post_id);
		foreach((array)$fields as $key => $value){
			if( preg_match('/^_isku_/', $key, $match) ){
				$key = substr($key, 6);
				$values = maybe_unserialize($value[0]);
				$num = $values['zaiko'];
				$skus[$key] = $this->zaiko_status[$num];
			}
		}
		if(!$skus) return false;
		if($skukey == ''){
			return $skus;
		}else if(isset($skus[$skukey])){
			return $skus[$skukey];
		}else{
			return false;
		}
	}
	
	function getItemZaikoStatusId($post_id, $skukey = '') {
		$fields = get_post_custom($post_id);
		foreach((array)$fields as $key => $value){
			if( preg_match('/^_isku_/', $key, $match) ){
				$key = substr($key, 6);
				$values = maybe_unserialize($value[0]);
				$num = $values['zaiko'];
				$skus[$key] = $num;
			}
		}
		if(!$skus) return false;
		if($skukey == ''){
			return $skus;
		}else if(isset($skus[$skukey])){
			return $skus[$skukey];
		}else{
			return false;
		}
	}
	
	function updateItemZaiko($post_id, $skukey, $status) {
		$fields = get_post_custom($post_id);
		foreach((array)$fields as $key => $value){
			$turekey = '_isku_'.$skukey;
			if( $key == $turekey ){
				$values = maybe_unserialize($value[0]);
				$values['zaiko'] = $status;
				update_post_meta($post_id, $turekey, $values);
				return;
			}
		}
	}
	
	function getItemZaikoNum($post_id, $skukey = '') {
		$fields = get_post_custom($post_id);
		foreach((array)$fields as $key => $value){
			if( preg_match('/^_isku_/', $key, $match) ){
				$key = substr($key, 6);
				$values = maybe_unserialize($value[0]);
				$skus[$key] = $values['zaikonum'];
			}
		}
		if(!$skus) return false;
		if($skukey == ''){
			return $skus;
		}else if(isset($skus[$skukey])){
			return $skus[$skukey];
		}else{
			return false;
		}
	}
	
	function updateItemZaikoNum($post_id, $skukey, $num) {
		$fields = get_post_custom($post_id);
		foreach((array)$fields as $key => $value){
			$turekey = '_isku_'.$skukey;
			if( $key == $turekey ){
				$values = maybe_unserialize($value[0]);
				$values['zaikonum'] = $num;
				update_post_meta($post_id, $turekey, $values);
				return;
			}
		}
	}
	
	function getItemSkuDisp($post_id, $skukey = '') {
		$fields = get_post_custom($post_id);
		foreach((array)$fields as $key => $value){
			if( preg_match('/^_isku_/', $key, $match) ){
				$key = substr($key, 6);
				$values = maybe_unserialize($value[0]);
				$skus[$key] = $values['disp'];
			}
		}
		if(!$skus) return false;
		if($skukey == ''){
			return $skus;
		}else if(isset($skus[$skukey])){
			return $skus[$skukey];
		}else{
			return false;
		}
	}
	
	function getItemSkuUnit($post_id, $skukey = '') {
		$fields = get_post_custom($post_id);
		foreach((array)$fields as $key => $value){
			if( preg_match('/^_isku_/', $key, $match) ){
				$key = substr($key, 6);
				$values = maybe_unserialize($value[0]);
				$skus[$key] = $values['unit'];
			}
		}
		if(!$skus) return false;
		if($skukey == ''){
			return $skus;
		}else if(isset($skus[$skukey])){
			return $skus[$skukey];
		}else{
			return false;
		}
	}
	
	function get_item( $post_id ) {
		$usces_item['post_id'] = $post_id;
		$usces_item['itemCode'] = $this->getItemCode($post_id);
		$usces_item['itemName'] = $this->getItemName($post_id);
		
		$fields = get_post_custom($post_id);
		foreach((array)$fields as $key => $value){
			if( preg_match('/^_isku_/', $key, $match) ){
				$key = substr($key, 6);
				$values = maybe_unserialize($value[0]);
				$usces_item['skuCodes'][] = $key;
				$usces_item['skuValues'][] = $values;
			}
		}
		
		$usces_item = apply_filters('usces_filter_get_item', $usces_item, $post_id);
		
		return $usces_item;
	}

	function get_itemOptionKey( $post_id ) {
		$custom_field_keys = get_post_custom_keys( $post_id );
		if(empty($custom_field_keys)) return;
		
		foreach ( (array)$custom_field_keys as $key => $value ) {
			if ( '_iopt_' == substr($value,0 , 6) )
				$res[] = substr($value, 6);
		}
		if($res)
			natcasesort($res);
		return $res;
	}
	
	function get_itemOptions( $key, $post_id ) {
		$metakey = '_iopt_' . $key;
		$values = get_post_custom_values( $metakey, $post_id );
		if(empty($values)) return;

		return unserialize($values[0]);
	}
	
	function get_postIDbyCode( $itemcode ) {
		global $wpdb;
		
		$codestr = $itemcode;
		$query = $wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s", $codestr);
		$res = $wpdb->get_var( $query );
		return $res;
	}

	function get_pictids($item_code) {
		global $wpdb;
		
		$codestr = $item_code.'%';
		$query = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title LIKE %s AND post_type = 'attachment' ORDER BY post_title", $codestr);
		$results = $wpdb->get_col( $query );
		return $results;
	}
	
	function get_skus( $post_id, $output='' ) {
		$fields = get_post_custom($post_id);
		ksort($fields);
		foreach((array)$fields as $k => $v){
			if( preg_match('/^_isku_/', $k, $match) ){
				$values = maybe_unserialize($v[0]);
				$key[] = substr($k, 6);
				$cprice[] = $values['cprice'];
				$price[] = $values['price'];
				$zaiko[] = $values['zaiko'];
				$zaikonum[] = $values['zaikonum'];
				$disp[] = $values['disp'];
				$unit[] = $values['unit'];
				$gptekiyo[] = $values['gptekiyo'];
				
				$res[substr($k, 6)]['cprice'] = $values['cprice'];
				$res[substr($k, 6)]['price'] = $values['price'];
				$res[substr($k, 6)]['zaiko'] = $values['zaiko'];
				$res[substr($k, 6)]['zaikonum'] = $values['zaikonum'];
				$res[substr($k, 6)]['disp'] = $values['disp'];
				$res[substr($k, 6)]['unit'] = $values['unit'];
				$res[substr($k, 6)]['gptekiyo'] = $values['gptekiyo'];
			}
		}
		if($output == 'ARRAY_A'){
			return $res;
		}else{
			return compact('key', 'cprice', 'price', 'zaiko', 'zaikonum', 'disp', 'unit', 'gptekiyo' );
		}
	}
	
	function is_item( $post ) {
	
//		$catids = wp_get_post_categories($post_id);
//		
//		$res = '';
//		foreach($catids as $id){
//			$cat = get_category($id);
//			if( $cat->slug == 'item' || $cat->parent == USCES_ITEM_CAT_PARENT_ID )
//				$res = 'ok';
//		}
		
		if( $post->post_mime_type == 'item' )
			return true;
		else
			return false;
	}
	
	function getItemIds() {
		global $wpdb;
		$query = $wpdb->prepare("SELECT ID  FROM $wpdb->posts WHERE post_mime_type = %s", 'item');
		$ids = $wpdb->get_col( $query );
		if( empty($ids) ) $ids = array();
		return $ids;
	}
	
	function getPaymentMethod( $name ) {
		$res = array();
		$payments = $this->options['payment_method'];
		foreach ( (array)$payments as $payment ) {
			if($name = $payment['name']) {
				$res = $payment;
				break;
			}
		}
		return 	$res;
	}
	
	function order_processing( $results = array() ) {
		do_action('usces_pre_reg_orderdata');
		//データベース登録(function.php)
		$order_id = usces_reg_orderdata( $results );
		//var_dump($order_id);exit;
		if ( $order_id ) {
			//メール送信処理(function.php)
			$mail_res = usces_send_ordermail( $order_id );
			return 'ordercompletion';
		
		} else {
			return 'error';
		}
	
	}

	function acting_processing($module, $query) {

		$module = trim($module);
		//$usces_entries = $this->cart->get_entry();

		if( empty($module) || !file_exists($this->options['settlement_path'] . $module) ) return 'error';
		
		
		//include(USCES_PLUGIN_DIR . '/settlement/' . $module);
		if($module == 'paypal.php'){
			require_once($this->options['settlement_path'] . "paypal.php");
			paypal_submit();
		}else if($module == 'epsilon.php'){
			if ( $this->use_ssl ) {
				$redirect = str_replace('http://', 'https://', USCES_CART_URL);
			}else{
				$redirect = USCES_CART_URL;
			}
			$query .= '&settlement=epsilon&redirect_url=' . urlencode($redirect);
			header("location: " . $redirect . $query);
			exit;
		}
	}

	function inquiry_processing() {
	
		$mail_res = usces_send_inquirymail();
		
		if ( $mail_res )
			return 'inquiry_comp';
		else
			return 'inquiry_error';
	}
	
//	function widget_usces_register() {
//		if ( function_exists('register_sidebar_widget') )
//			register_sidebar_widget('usces カレンダー', array($this, 'usces_calendar'));	
//	
//	}
//	
//	function usces_calendar() {
//	
//	}
	
	function lastprocessing() {
		
		if ( $this->page == 'ordercompletion' )
			$this->cart->crear_cart();

	}

	// function for the cart ***********************************************************
	function get_total_price( $cart = array() ) {
		if( empty($cart) )
			$cart = $this->cart->get_cart();
	
		$total_price = 0;

		if( !empty($cart) ) {
			for($i=0; $i<count($cart); $i++) { 
				$quantity = $cart[$i]['quantity'];
				$skuPrice = $cart[$i]['price'];
				
				$total_price += ($skuPrice * $quantity);
			}
		}
		return $total_price;
	}
	
	function get_total_quantity( $cart = array() ) {
		if( empty($cart) )
			$cart = $this->cart->get_cart();
	
		$total_quantity = 0;

		if( !empty($cart) ) {
			for($i=0; $i<count($cart); $i++) { 
				$total_quantity += $cart[$i]['quantity'];
			}
		}
		return $total_quantity;
	}
	
	function get_order_point( $mem_id = '', $display_mode = '', $cart = array() ) {
		if( $mem_id == '' || $this->options['membersystem_state'] == 'deactivate' || $this->options['membersystem_point'] == 'deactivate') return 0;
		
		if ( empty($cart) )
			$cart = $this->cart->get_cart();
		
		if ( empty($display_mode) )
			$display_mode = $this->options['display_mode'];
		
		$point = 0;
		$total = $this->get_total_price( $cart );
		if ( $display_mode == 'Promotionsale' ) {
			if ( $this->options['campaign_privilege'] == 'discount' ) {
				$point = 0;
			} elseif ( $this->options['campaign_privilege'] == 'point' ) {
				foreach ( $cart as $rows ) {
					$rate = get_post_custom_values('_itemPointrate', $rows['post_id']);
					//$price = $this->getItemPrice($rows['post_id'], $rows['sku']) * $rows['quantity'];
					$price = $rows['price'] * $rows['quantity'];
					$cats = $this->get_post_term_ids($rows['post_id'], 'category');
					if ( in_array($this->options['campaign_category'], $cats) )
						$point += $price * $rate[0] / 100 * $this->options['privilege_point'];
					else
						$point += $price * $rate[0] / 100;
				}
			}
		} else {
			foreach ( $cart as $rows ) {
				$rate = get_post_custom_values('_itemPointrate', $rows['post_id']);
				//$price = $this->getItemPrice($rows['post_id'], $rows['sku']) * $rows['quantity'];
				$price = $rows['price'] * $rows['quantity'];
				$point += $price * $rate[0] / 100;
			}
		}
	
		return ceil($point);
	}
	
	function get_order_discount( $display_mode = '', $cart = array() ) {
		if ( empty($cart) )
			$cart = $this->cart->get_cart();
		
		if ( empty($display_mode) )
			$display_mode = $this->options['display_mode'];
		
		$discount = 0;
		$total = $this->get_total_price( $cart );
		if ( $display_mode == 'Promotionsale' ) {
			if ( $this->options['campaign_privilege'] == 'discount' )
				$discount = $total * $this->options['privilege_discount'] / 100;
			elseif ( $this->options['campaign_privilege'] == 'point' )
				$discount = 0;
		}

		$discount = ceil($discount * -1);
		$discount = apply_filters('usces_order_discount', $discount, $cart);
		return $discount;
	} 

	function getShippingCharge( $pref, $cart = array(), $entry = array() ) {
		if( empty($cart) )
			$cart = $this->cart->get_cart();
		if( empty($entry) )
			$entry = $this->cart->get_entry();
			
		$d_method_id = $entry['order']['delivery_method'];
		$d_method_index = $this->get_delivery_method_index($d_method_id);
		
		$fixed_charge_id = $this->options['delivery_method'][$d_method_index]['charge'];
		
		$individual_quant = 0;
		$total_quant = 0;
		$charges = array();
		
		foreach ( $cart as $rows ) {
			$s_charge_id = $this->getItemShippingCharge($rows['post_id']);
			$s_charge_index = $this->get_shipping_charge_index($s_charge_id);
			$charge = $this->options['shipping_charge'][$s_charge_index]['value'][$pref];
			if($this->getItemIndividualSCharge($rows['post_id'])){
				$individual_quant += $rows['quantity'];
				$individual_charge += $rows['quantity'] * $charge;
			}else{
				$charges[] = $charge;
			}
			$total_quant += $rows['quantity'];
		}

		if( $fixed_charge_id >= 0 ){
			$fix_charge_index = $this->get_shipping_charge_index($fixed_charge_id);
			$fix_charge = $this->options['shipping_charge'][$fix_charge_index]['value'][$pref];
			if( $total_quant > $individual_quant ){
				$charge = $fix_charge + $fix_charge * $individual_quant;
			}else{
				$charge = $fix_charge * $individual_quant;
			}
		
		}else{
			if( count($charges) > 0 ){
				rsort($charges);
				$max_charge = $charges[0];
				$charge = $max_charge + $individual_charge;
			}else{
				$charge = $individual_charge;
			}
		
		}
		
		return $charge;

	}
	
	function getCODFee($payment_name) {
		$payments = $this->getPayments($payment_name);
		$fee = $payments['settlement'] == 'COD' ? $this->options['cod_fee'] : 0;

		return $fee;
	}
	
	function getTax( $total ) {
		if( empty($this->options['tax_rate']) )
			return 0;

		if( $this->options['tax_method'] == 'cutting' )
			$tax = floor($total * $this->options['tax_rate'] / 100);
		elseif($this->options['tax_method'] == 'bring')
			$tax = ceil($total * $this->options['tax_rate'] / 100);
		elseif($this->options['tax_method'] == 'rounding')
			$tax = round($total * $this->options['tax_rate'] / 100);

		return $tax;
	}
	
	function set_cart_fees( $member, &$entries ) {
		$total_items_price = $this->get_total_price();
		if ( empty($this->options['postage_privilege']) || $total_items_price < $this->options['postage_privilege'] ) {
			$shipping_charge = $this->getShippingCharge( $entries['delivery']['pref'] );
		} else {
			$shipping_charge = 0;
		}
		$payments = $this->getPayments( $entries['order']['payment_name'] );
		$cod_fee = $this->getCODFee($entries['order']['payment_name']);
		$get_point = $this->get_order_point( $member['ID'] );
		$use_point = $entries['order']['usedpoint'];
		$discount = $this->get_order_discount();
		$total_price = $total_items_price - $use_point + $discount + $shipping_charge + $cod_fee;
		$tax = $this->getTax( $total_price );
		$total_full_price = $total_price + $tax;

		$array = array(
				'total_items_price' => $total_items_price,
				'total_price' => $total_price,
				'total_full_price' => $total_full_price,
				'getpoint' => $get_point,
				'usedpoint' => $use_point,
				'discount' => $discount,
				'shipping_charge' => $shipping_charge,
				'cod_fee' => $cod_fee,
				'tax' => $tax
				);
		$this->cart->set_order_entry( $array );
		$entries = $this->cart->get_entry();
	}
	
	function getPayments( $payment_name ) {
		foreach ( (array)$this->options['payment_method'] as $id => $array ) {
			if ( $this->options['payment_method'][$id]['name'] == $payment_name )
				break;
		}

		return $this->options['payment_method'][$id];
	}

	function is_maintenance() {
		if ( $this->options['display_mode'] == 'Maintenancemode' )
			return true;
		else
			return false;
	}

//	function maintenance_mode() {
//		if ( $this->is_maintenance() && !is_user_logged_in() && (!strstr($_SERVER['REQUEST_URI'], 'wp-admin') || !strstr($_SERVER['REQUEST_URI'], 'wp-login') ) ) {
//			include ( TEMPLATEPATH . '/maintenance.php ');
//			exit;
//		} elseif ( isset($_GET['uscesmode']) && $_GET['uscesmode'] == 'lostpassword' ) {
//			$error = isset($_GET['error']) ? '?error='.$_GET['error'] : '';
//			include ( TEMPLATEPATH . '/member/changepassword.php ');
//			exit;
//		}
//	}
	
	function get_member_history($mem_id) {
		global $wpdb;
		$order_table = $wpdb->prefix . "usces_order";
	
		$query = $wpdb->prepare("SELECT ID, order_cart, order_condition, order_date, order_usedpoint, order_getpoint, 
								order_discount, order_shipping_charge, order_cod_fee, order_tax, order_status 
							FROM $order_table WHERE mem_id = %d ORDER BY order_date DESC", $mem_id);
		$results = $wpdb->get_results( $query );
	
		$i=0;
		$res = array();
		foreach ( $results as $value ) {
			if(strpos($value->order_status, 'cancel') === false && strpos($value->order_status, 'estimate') === false){
		
				$res[] = array(
							'ID' => $value->ID,
							'cart' => unserialize($value->order_cart),
							'condition' => unserialize($value->order_condition),
							'getpoint' => $value->order_getpoint,
							'usedpoint' => $value->order_usedpoint,
							'discount' => $value->order_discount,
							'shipping_charge' => $value->order_shipping_charge,
							'cod_fee' => $value->order_cod_fee,
							'tax' => $value->order_tax,
							'date' => mysql2date(__('Y/m/d'), $value->order_date),
							'order_date' => $value->order_date
							);
				$i++;
			
			}
		}

		return $res;
	
	}
	
	function get_post_term_ids( $post_id, $taxonomy ){
		global $wpdb;
		$query = $wpdb->prepare("SELECT tt.term_id  FROM $wpdb->term_relationships AS tr 
									INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id 
									WHERE tt.taxonomy = %s AND tr.object_id = %d", $taxonomy, $post_id);
		$ids = $wpdb->get_col( $query );

		return $ids;
	
	}

	function get_tag_names($post_id) {
		global $wpdb;
		$tag = 'post_tag';
		$query = $wpdb->prepare("SELECT t.name  FROM $wpdb->term_relationships AS tr 
									INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id 
									INNER JOIN $wpdb->terms AS t ON t.term_id = tt.term_id 
									WHERE tt.taxonomy = %s AND tr.object_id = %d", $tag, $post_id);
		$names = $wpdb->get_col( $query );

		return $names;
	
	}
	
	function get_ID_byItemName($itemname, $status = 'publish') {
		global $wpdb;
		$meta_key = '_itemCode';
		$query = $wpdb->prepare("SELECT p.ID  FROM $wpdb->posts AS p 
									INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id 
									WHERE p.post_status = %s AND pm.meta_key = %s AND meta_value = %s ", $status, $meta_key, $itemname);
		$id = $wpdb->get_var( $query );

//		$wpdb->show_errors(); 
//		$wpdb->print_error();

		return $id;
	
	}
	
	function uscescv( &$sessid ) {
		
		$chars = '';
		$i=0;
		$h=0;
		while($h<strlen($sessid)){
			if(0 == $i % 3){
				$chars .= base_convert($i, 10, 36);
			}else{
				$chars .= substr($sessid, $h, 1);
				$h++;
			}
			$i++;
		}
		$sessid = $chars;
		//var_dump($sessid);
	}
	
	function uscesdc( &$sessid ) {
		$chars = '';
		$h=0;
		while($h<strlen($sessid)){
			if(0 != $i % 3){
				$chars .= substr($sessid, $h, 1);
			}
			$h++;
		}
		$sessid = $chars;
		
		//var_dump($sessid);
	}

	function get_visiter( $period ) {
		global $wpdb;
		$datestr = substr(get_date_from_gmt(gmdate('Y-m-d H:i:s', time())), 0, 10);
		$yearstr = substr($datestr, 0, 4);
		$monthstr = substr($datestr, 5, 2);
		$daystr = substr($datestr, 8, 2);
		if($period == 'today') {
			$date = $datestr;
			$today = $datestr;
		}else if($period == 'thismonth') {
			$date = date('Y-m-01');
			$today = $datestr;
		}else if($period == 'lastyear') {
			$date = date('Y-m-01', mktime(0, 0, 0, (int)$monthstr, 1, (int)$yearstr-1));
			$today = date('Y-m-01', mktime(0, 0, 0, (int)$monthstr, (int)$daystr, (int)$yearstr-1));
		}
		$table_name = $wpdb->prefix . 'usces_access';
		
		$query = $wpdb->prepare("SELECT SUM(acc_num1) AS ct1, SUM(acc_num2) AS ct2 FROM $table_name WHERE acc_date >= %s AND acc_date <= %s", $date, $today);
		$res = $wpdb->get_row($query, ARRAY_A);
		
		if( $res == NULL )
			return 0;
		else
			return $res['ct1']+$res['ct2'];
	}

	function get_fvisiter( $period ) {
		global $wpdb;
		$datestr = substr(get_date_from_gmt(gmdate('Y-m-d H:i:s', time())), 0, 10);
		$yearstr = substr($datestr, 0, 4);
		$monthstr = substr($datestr, 5, 2);
		$daystr = substr($datestr, 8, 2);
		if($period == 'today') {
			$date = $datestr;
			$today = $datestr;
		}else if($period == 'thismonth') {
			$date = date('Y-m-01');
			$today = $datestr;
		}else if($period == 'lastyear') {
			$date = date('Y-m-01', mktime(0, 0, 0, (int)$monthstr, 1, (int)$yearstr-1));
			$today = date('Y-m-01', mktime(0, 0, 0, (int)$monthstr, (int)$daystr, (int)$yearstr-1));
		}
		$table_name = $wpdb->prefix . 'usces_access';
		
		$query = $wpdb->prepare("SELECT SUM(acc_num2) AS ct FROM $table_name WHERE acc_date >= %s AND acc_date <= %s", $date, $today);
		$res = $wpdb->get_var($query);
		
		if( $res == NULL )
			return 0;
		else
			return $res;
	}
	
	function get_order_num( $period ) {
		global $wpdb;
		$datestr = substr(get_date_from_gmt(gmdate('Y-m-d H:i:s', time())), 0, 10);
		$yearstr = substr($datestr, 0, 4);
		$monthstr = substr($datestr, 5, 2);
		$daystr = substr($datestr, 8, 2);
		if($period == 'today') {
			$date = date('Y-m-d 00:00:00', current_time('timestamp'));
			$today = date('Y-m-d 23:59:59', current_time('timestamp'));
		}else if($period == 'thismonth') {
			$date = date('Y-m-01 00:00:00', current_time('timestamp'));
			$today = date('Y-m-d 23:59:59', current_time('timestamp'));
		}else if($period == 'lastyear') {
			$date = date('Y-m-01 00:00:00', mktime(0, 0, 0, (int)$monthstr, 1, (int)$yearstr-1));
			$today = date('Y-m-01 23:59:59', mktime(0, 0, 0, (int)$monthstr, (int)$daystr, (int)$yearstr-1));
		}
		$table_name = $wpdb->prefix . 'usces_order';
		
		$query = $wpdb->prepare("SELECT COUNT(ID) AS ct FROM $table_name WHERE order_date >= %s AND order_date <= %s", $date, $today);
		$res = $wpdb->get_var($query);
		
		if( $res == NULL )
			return 0;
		else
			return $res;
	}

	function get_order_amount( $period ) {
		global $wpdb;
		$datestr = substr(get_date_from_gmt(gmdate('Y-m-d H:i:s', time())), 0, 10);
		$yearstr = substr($datestr, 0, 4);
		$monthstr = substr($datestr, 5, 2);
		$daystr = substr($datestr, 8, 2);
		if($period == 'today') {
			$date = date('Y-m-d 00:00:00', current_time('timestamp'));
			$today = date('Y-m-d 23:59:59', current_time('timestamp'));
		}else if($period == 'thismonth') {
			$date = date('Y-m-01 00:00:00', current_time('timestamp'));
			$today = date('Y-m-d 23:59:59', current_time('timestamp'));
		}else if($period == 'lastyear') {
			$date = date('Y-m-01 00:00:00', mktime(0, 0, 0, (int)$monthstr, 1, (int)$yearstr-1));
			$today = date('Y-m-01 23:59:59', mktime(0, 0, 0, (int)$monthstr, (int)$daystr, (int)$yearstr-1));
		}
		$table_name = $wpdb->prefix . 'usces_order';
		
		$query = $wpdb->prepare("SELECT 
									SUM(order_item_total_price) AS price, 
									SUM(order_usedpoint) AS point, 
									SUM(order_discount) AS discount, 
									SUM(order_shipping_charge) AS shipping, 
									SUM(order_cod_fee) AS cod, 
									SUM(order_tax) AS tax 
								 FROM $table_name WHERE order_date >= %s AND order_date <= %s", $date, $today);
		$res = $wpdb->get_row($query, ARRAY_A);
		
		if( $res == NULL )
			return 0;
		else
			return $res['price'] - $res['point'] + $res['discount'] + $res['shipping'] + $res['cod'] + $res['tax'];
	}

	function get_items_skus() {
		global $wpdb;
		
		$query = $wpdb->prepare("SELECT ID, meta_key, meta_value FROM {$wpdb->posts} 
									INNER JOIN {$wpdb->postmeta} ON ID = post_id AND SUBSTRING(meta_key, 1, 6) = %s 
									WHERE post_mime_type = %s AND post_status = %s 
									ORDER BY ID, meta_key", 
									'_isku_', 'item', 'publish');
		$res = $wpdb->get_results($query, ARRAY_A);
		
		$sku = array();
		$status = array();
		foreach((array)$res as $key => $value){
			$sku['data'][$key]['ID'] = $value['ID'];
			$sku['data'][$key]['code'] = $this->getItemCode($value['ID']);
			$sku['data'][$key]['name'] = $this->getItemName($value['ID']);
			$sku['data'][$key]['sku'] = substr($value['meta_key'], 6);
			$sku['data'][$key]['num'] = $this->getItemZaikoNum($value['ID'], $sku['data'][$key]['sku']);
			$status[] = $this->getItemZaiko($value['ID'], $sku['data'][$key]['sku']);
		}
		$sku['count'] = array_count_values($status);
		return $sku;
	}

	function is_status($need, $str){
		$array = explode(',', $str);
		return in_array($need, $array);
	}
	
	function make_status( $taio='', $receipt='', $admin='' ){
		$str = '';
		if($taio != '' && $taio != '#none#')
		 	$str .= $taio . ',';
		if($receipt != '' && $receipt != '#none#')
		 	$str .= $receipt . ',';
		if($admin != '' && $admin != '#none#')
		 	$str .= $admin . ',';
		return $str;
	}
	
	function get_memberid_by_email($email){
		return;
	}
	
	function get_condition(){
		$order_conditions = array(
		'display_mode' => $this->options['display_mode'],
		'campaign_privilege' => $this->options['campaign_privilege'],
		'privilege_point' => $this->options['privilege_point'],
		'privilege_discount' => $this->options['privilege_discount']);
		return $order_conditions;
	}
	
	function get_bestseller_ids( $days = "" ){
		global $wpdb;
		$datestr = substr(get_date_from_gmt(gmdate('Y-m-d H:i:s', time())), 0, 10);
		$yearstr = substr($datestr, 0, 4);
		$monthstr = substr($datestr, 5, 2);
		$daystr = substr($datestr, 8, 2);
		$res = array();
		$order_table_name = $wpdb->prefix . "usces_order";
		$where = "";
		if($days != ''){
			$order_date = date('Y-m-d H:i:s', mktime(0, 0, 0, (int)$monthstr, ((int)$daystr-$days), (int)$yearstr));
			$where = " WHERE order_date >= '{$order_date}'";
		}
		$query = "SELECT order_cart FROM {$order_table_name}" . $where;
		$dbres = $wpdb->get_col($query);
		if(!$dbres) return false;
		
		foreach((array)$dbres as $carts){
			$rows = unserialize($carts);
			foreach((array)$rows as $carts){
				$id = $carts['post_id'];
				$qu = $carts['quantity'];
				if(array_key_exists($id, $res)){
					$res[$id] = $res[$id] + $qu;
				}else{
					$res[$id] = $qu;
				}
			}
		}
		arsort($res);
		$results = array_keys($res);
		return $results;
	}
	
	function get_items_num(){
		global $wpdb;
		$query = $wpdb->prepare("SELECT COUNT(ID) AS ct FROM {$wpdb->posts} WHERE post_mime_type = %s AND post_status = %s", 
								'item', 'publish');
		$res = $wpdb->get_var($query);

		return $res;
	}
	
	function is_gptekiyo( $post_id, $sku, $quant ) {
		$skus = $this->get_skus( $post_id, 'ARRAY_A' );
		if( !$skus[$sku]['gptekiyo'] ) return false;

		$GpN1 = $this->getItemGpNum1($post_id);
		$GpN2 = $this->getItemGpNum2($post_id);
		$GpN3 = $this->getItemGpNum3($post_id);
	
		if( empty($GpN1) ) {
		
				return false;
				
		}else if( !empty($GpN1) && empty($GpN2) ) {
		
			if( $quant >= $GpN1 ) {
				return true;
			}else{
				return false;
			}
			
		}else if( !empty($GpN1) && !empty($GpN2) && empty($GpN3) ) {
		
			if( $quant >= $GpN2 ) {
				return true;
			}else if( $quant >= $GpN1 && $quant < $GpN2 ) {
				return true;
			}else{
				return false;
			}
			
		}else if( !empty($GpN1) && !empty($GpN2) && !empty($GpN3) ) {
		
			if( $quant >= $GpN3 ) {
				return true;
			}else if( $quant >= $GpN2 && $quant < $GpN3 ) {
				return true;
			}else if( $quant >= $GpN1 && $quant < $GpN2 ) {
				return true;
			}else{
				return false;
			}
		}
	}
	
	function get_available_delivery_method() { 
		if($this->cart->num_row() > 0) {
			$cart = $this->cart->get_cart();
			$before_deli = array();
			$intersect = array();
			foreach($cart as $key => $row){
				$deli = $this->getItemDeliveryMethod($row['post_id']);
				if(!is_array($deli)) {
					return array();
				}
				if( $key === 0 ){
					$intersect = $deli;
				}
				$intersect = array_intersect($deli, $intersect);
				$before_deli = $deli;
				foreach($deli as $value){
					$integration[] = $value;
				}
			}
			$integration = array_unique($integration);
			foreach($integration as $id){
				$index = $this->get_delivery_method_index($id);
				$temp[$index] = $id;
			}
			ksort($temp);
			if(!$intersect){
				$deli = array();
				$deli[0] = (int)$temp[0];
				return $deli;
			}else{
				return $intersect;
			}
		}
		return array();
	}

	function get_delivery_method_index($id) {
		$index = false; 
		for($i=0; $i<count($this->options['delivery_method']); $i++){
			if( $this->options['delivery_method'][$i]['id'] === (int)$id ){
				$index = $i;
			}
		}
		if($index === false)
			return -1;
		else
			return $index;
	}

	function get_shipping_charge_index($id) {
		$index = false; 
		for($i=0; $i<count($this->options['shipping_charge']); $i++){
			if( $this->options['shipping_charge'][$i]['id'] === $id ){
				$index = $i;
			}
		}
		if($index === false)
			return -1;
		else
			return $index;
	}
	
	function get_initial_data($xml){
		$buf = file_get_contents($xml);
		preg_match_all('@<page>.*?<post_title>(.*?)</post_title>.*?<post_status>(.*?)</post_status>.*?<post_name>(.*?)</post_name>.*?<post_content>(.*?)</post_content>.*?</page>@s', $buf, $match, PREG_SET_ORDER);
		return $match;
	}

	function getCurrencySymbol(){
		return get_option('usces_currency_symbol');
	}

	function getCartItemName($post_id, $sku){
		$name_arr = array();
		$name_str = '';
		
		foreach($this->options['indi_item_name'] as $key => $value){
			if($value){
				$pos = (int)$this->options['pos_item_name'][$key];
				$ind = ($pos === 0) ? 'A' : $pos;
				switch($key){
					case 'item_name':
						$name_arr[$ind][$key] = $this->getItemName($post_id);
						break;
					case 'item_code':
						$name_arr[$ind][$key] = $this->getItemCode($post_id);
						break;
					case 'sku_name':
						$name_arr[$ind][$key] = $this->getItemSkuDisp($post_id, $sku);
						break;
					case 'sku_code':
						$name_arr[$ind][$key] = $sku;
						break;
				}
			}
			
		}
		ksort($name_arr);
		foreach($name_arr as $vals){
			foreach($vals as $key => $value){
			
				$name_str .= $value . ' ';
			}
		}
		
		$name_str = apply_filters('usces_admin_order_item_name_filter', $name_str);
		
		return trim($name_str);
	}
	
	function set_reserve_pre_order_id(){
		$entry = $this->cart->get_entry();
		$id = ( isset($entry['reserve']['pre_order_id']) && !empty($entry['reserve']['pre_order_id']) ) ? $entry['reserve']['pre_order_id'] : uniqid('');
		$this->cart->set_pre_order_id($id);
	}

	function get_current_pre_order_id(){
		$entry = $this->cart->get_entry();
		$id = ( isset($entry['reserve']['pre_order_id']) && !empty($entry['reserve']['pre_order_id']) ) ? $entry['reserve']['pre_order_id'] : NULL;
		return $id;
	}

	function get_reserve($order_id, $key){
		global $wpdb;
		$order_meta_table_name = $wpdb->prefix . "usces_order_meta";
		$query = $wpdb->prepare("SELECT meta_value FROM $order_meta_table_name WHERE order_id = %d AND meta_key = %s", 
								$order_id, $key);
		$res = $wpdb->get_var($query);
		return $res;
	}

	//shortcode-----------------------------------------------------------------------------
	function sc_company_name() {
		return htmlspecialchars($this->options['company_name']);
	}
	function sc_zip_code() {
		return htmlspecialchars($this->options['zip_code']);
	}
	function sc_address1() {
		return htmlspecialchars($this->options['address1']);
	}
	function sc_address2() {
		return htmlspecialchars($this->options['address2']);
	}
	function sc_tel_number() {
		return htmlspecialchars($this->options['tel_number']);
	}
	function sc_fax_number() {
		return htmlspecialchars($this->options['fax_number']);
	}
	function sc_inquiry_mail() {
		return htmlspecialchars($this->options['inquiry_mail']);
	}
	function sc_payment() {
		$payments = $this->options['payment_method'];
		$htm = "<ul>\n";
		foreach ( (array)$payments as $payment ) {
			$htm .= "<li>" . htmlspecialchars($payment['name']) . "<br />\n";
			$htm .= nl2br(htmlspecialchars($payment['explanation'])) . "</li>\n";
		}
		$htm .= "</ul>\n";
		return $htm;
	}
	function sc_payment_title() {
		$payments = $this->options['payment_method'];
		$htm = "<ul>\n";
		foreach ( (array)$payments as $payment ) {
			$htm .= "<li>" . htmlspecialchars($payment['name']) . "</li>\n";
		}
		$htm .= "</ul>\n";
		return $htm;
	}
	function sc_cod_fee() {
		return number_format($this->options['cod_fee']);
	}
	function sc_start_point() {
		return number_format($this->options['start_point']);
	}
	function sc_postage_privilege() {
		if(empty($this->options['postage_privilege'])) 
			return;
		return number_format($this->options['postage_privilege']);
	}
	function sc_shipping_charge() {
		$arr = array();
		foreach ( (array)$this->options['shipping_charge'] as $charges ) {
			foreach ( (array)$charges['value'] as $value ) {
				$arr[] = $value;
			}
		}
		sort($arr);
		$min = $arr[0];
		rsort($arr);
		$max = $arr[0];
		if($min == $max){
			$res = number_format($min);
		}else{
			$res = number_format($min) . '～' . number_format($max);
		}
		return $res;
	}
	function sc_site_url() {
		return get_option('home');
	}
	function sc_button_to_cart($atts) {
		extract(shortcode_atts(array(
			'item' => '',
			'sku' => '',
			'value' => __('to the cart', 'usces'),
		), $atts));
	
		$post_id = $this->get_ID_byItemName($item);
		$datas = $this->get_skus( $post_id, 'ARRAY_A' );
	
		$zaikonum = $datas[$sku]['zaikonum'];
		$zaiko = $datas[$sku]['zaiko'];
		$gptekiyo = $datas[$sku]['gptekiyo'];
		$skuPrice = $datas[$sku]['price'];
		
		$html = "<form action=\"" . USCES_CART_URL . "\" method=\"post\">\n";
		$html .= "<input name=\"zaikonum[{$post_id}][{$sku}]\" type=\"hidden\" id=\"zaikonum[{$post_id}][{$sku}]\" value=\"{$zaikonum}\" />\n";
		$html .= "<input name=\"zaiko[{$post_id}][{$sku}]\" type=\"hidden\" id=\"zaiko[{$post_id}][{$sku}]\" value=\"{$zaiko}\" />\n";
		$html .= "<input name=\"gptekiyo[{$post_id}][{$sku}]\" type=\"hidden\" id=\"gptekiyo[{$post_id}][{$sku}]\" value=\"{$gptekiyo}\" />\n";
		$html .= "<input name=\"skuPrice[{$post_id}][{$sku}]\" type=\"hidden\" id=\"skuPrice[{$post_id}][{$sku}]\" value=\"{$skuPrice}\" />\n";
		$html .= "<input name=\"inCart[{$post_id}][{$sku}]\" type=\"submit\" id=\"inCart[{$post_id}][{$sku}]\" class=\"skubutton\" value=\"{$value}\" onclick=\"return uscesCart.intoCart('{$post_id}','{$sku}')\" />";
		$html .= "</form>";
	
		return $html;
	}

	function filter_itemPage($content){
		global $post;
		if($post->post_mime_type != 'item' || !is_single()) return $content;
		
		$temp_path = apply_filters('usces_template_path_single_item', USCES_PLUGIN_DIR . '/templates/single_item.php');
		include( $temp_path );
		
		$content = apply_filters('usces_filter_itemPage', $html, $post->ID);

		return $content;
	}

	function filter_cartContent($content) {
		global $post;
		
		switch($this->page){
			case 'cart':
				$temp_path = apply_filters('usces_template_path_cart', USCES_PLUGIN_DIR . '/templates/cart/cart.php');
				include( $temp_path );
				break;
			case 'customer':
				$temp_path = apply_filters('usces_template_path_customer', USCES_PLUGIN_DIR . '/templates/cart/customer_info.php');
				include( $temp_path );
				break;
			case 'delivery':
				$temp_path = apply_filters('usces_template_path_delivery', USCES_PLUGIN_DIR . '/templates/cart/delivery_info.php');
				include( $temp_path );
				break;
			case 'confirm':
				$temp_path = apply_filters('usces_template_path_confirm', USCES_PLUGIN_DIR . '/templates/cart/confirm.php');
				include( $temp_path );
				break;
			case 'ordercompletion':
				$temp_path = apply_filters('usces_template_path_ordercompletion', USCES_PLUGIN_DIR . '/templates/cart/completion.php');
				include( $temp_path );
				break;
			case 'error':
				$temp_path = apply_filters('usces_template_path_carterror', USCES_PLUGIN_DIR . '/templates/cart/error.php');
				include( $temp_path );
				break;
			case 'maintenance':
				$temp_path = apply_filters('usces_template_path_maintenance', USCES_PLUGIN_DIR . '/templates/cart/maintenance.php');
				include( $temp_path );
				break;
			case 'search_item':
				$temp_path = apply_filters('usces_template_path_search_item', USCES_PLUGIN_DIR . '/templates/search_item.php');
				include( $temp_path );
				break;
			case 'wp_search':
				if($post->post_mime_type == 'item'){
					$temp_path = apply_filters('usces_template_path_wp_search', USCES_PLUGIN_DIR . '/templates/wp_search_item.php');
					include( $temp_path );
				}else{
					$html = $content;
				}
				break;
			default:
				$html = $content;
		}

		$html = apply_filters('usces_filter_cartContent', $html);

		$content = $html;
		
		remove_filter('the_title', array($this, 'filter_cartTitle'));

		return $content;
	}

	function filter_cartTitle($title) {

		if( $title == 'Cart' || $title == __('Cart', 'usces') ){
			switch($this->page){
				case 'cart':
					$newtitle = apply_filters('usces_filter_title_cart', __('In the cart', 'usces'));
					break;
				case 'customer':
					$newtitle = apply_filters('usces_filter_title_customer', __('Customer Information', 'usces'));
					break;
				case 'delivery':
					$newtitle = apply_filters('usces_filter_title_delivery', __('Shipping / Payment options', 'usces'));
					break;
				case 'confirm':
					$newtitle = apply_filters('usces_filter_title_confirm', __('Confirmation', 'usces'));
					break;
				case 'ordercompletion':
					$newtitle = apply_filters('usces_filter_title_ordercompletion', __('Completion', 'usces'));
					break;
				case 'error':
					$newtitle = apply_filters('usces_filter_title_carterror', __('Error', 'usces'));
					break;
				case 'search_item':
					$newtitle = apply_filters('usces_filter_title_search_item', __("'AND' search by categories", 'usces'));
					break;
				case 'maintenance':
					$newtitle = apply_filters('usces_filter_title_maintenance', __('Under Maintenance', 'usces'));
					break;
				default:
					$newtitle = $title;
			}
		}else{
			$newtitle = $title;
		}
	
		$newtitle = apply_filters('usces_filter_cartTitle', $newtitle);
		return $newtitle;
	}
	
	function action_cartFilter(){
		add_filter('the_title', array($this, 'filter_cartTitle'));
		add_filter('the_content', array($this, 'filter_cartContent'),21);
	}
		
	function action_search_item(){
		include(TEMPLATEPATH . '/page.php');
		exit;
	}
		
	function filter_memberContent($content) {
		global $post;
		
		if( $this->is_member_logged_in() ) {
		
			$member_regmode = 'editmemberform';
			$temp_path = apply_filters('usces_template_path_member', USCES_PLUGIN_DIR . '/templates/member/member.php');
			include( $temp_path );
		
		} else {
		
			switch($this->page){
				case 'login':
					$temp_path = apply_filters('usces_template_path_login', USCES_PLUGIN_DIR . '/templates/member/login.php');
					include( $temp_path );
					break;
				case 'lostmemberpassword':
					$temp_path = apply_filters('usces_template_path_lostpassword', USCES_PLUGIN_DIR . '/templates/member/lostpassword.php');
					include( $temp_path );
					break;
				case 'changepassword':
					$temp_path = apply_filters('usces_template_path_changepassword', USCES_PLUGIN_DIR . '/templates/member/changepassword.php');
					include( $temp_path );
					break;
				case 'newcompletion':
				case 'editcompletion':
				case 'lostcompletion':
				case 'changepasscompletion':
					$temp_path = apply_filters('usces_template_path_membercompletion', USCES_PLUGIN_DIR . '/templates/member/completion.php');
					include( $temp_path );
					break;
				case 'newmemberform':
					$member_form_title = apply_filters('usces_filter_title_newmemberform', __('New enrollment form', 'usces'));
					$member_regmode = 'newmemberform';
					$temp_path = apply_filters('usces_template_path_member_form', USCES_PLUGIN_DIR . '/templates/member/member_form.php');
					include( $temp_path );
					break;
				default:
					$temp_path = apply_filters('usces_template_path_login', USCES_PLUGIN_DIR . '/templates/member/login.php');
					include( $temp_path );
			}
		
		}
		
		$content = $html;
		
		remove_filter('the_title', array($this, 'filter_memberTitle'));

		return $content;
	}

	function filter_memberTitle($title) {

		if( $title == 'Member' || $title == __('Membership', 'usces') ){
			switch($this->page){
				case 'login':
					$newtitle = apply_filters('usces_filter_title_login', __('Log-in for members', 'usces'));
					break;
				case 'newmemberform':
					$newtitle = apply_filters('usces_filter_title_newmemberform', __('New enrollment form', 'usces'));
					break;
				case 'lostmemberpassword':
					$newtitle = apply_filters('usces_filter_title_lostmemberpassword', __('The new password acquisition', 'usces'));
					break;
				case 'changepassword':
					$newtitle = apply_filters('usces_filter_title_changepassword', __('Change password', 'usces'));
					break;
				case 'newcompletion':
				case 'editcompletion':
				case 'lostcompletion':
				case 'changepasscompletion':
					$newtitle = apply_filters('usces_filter_title_changepasscompletion', __('Completion', 'usces'));
					break;
				case 'error':
					$newtitle = apply_filters('usces_filter_title_membererror', __('Error', 'usces'));
					break;
				default:
					$newtitle = $title;
			}
		}else{
			$newtitle = $title;
		}
	
		return $newtitle;
	}
	
	function action_memberFilter(){
		add_filter('the_title', array($this, 'filter_memberTitle'));
		add_filter('the_content', array($this, 'filter_memberContent'),20);
	}

	function filter_usces_cart_css(){
		$path = get_stylesheet_directory_uri() . '/usces_cart.css';
		return $path;
	}
	
	function filter_divide_item(){
		global $wp_query;

		$ids = $this->getItemIds();

		if( $usces->options['divide_item'] && !is_category() && !is_search() && !is_singular() && !is_admin() ){
			$wp_query->query_vars['post__not_in'] = $ids; 
		}
		if( is_admin() ){
			//$wp_query->query_vars['category__not_in'] = array(USCES_ITEM_CAT_PARENT_ID); 
			$wp_query->query_vars['post__not_in'] = $ids;
		}
	}

	function load_upload_template(){
		$post_id = $_POST['post_id'];
		$file = 'upload_template01.php';
		include(TEMPLATEPATH . '/' . $file);
		exit;
	}
		
	function filter_itemimg_anchor_rel($html){
	
		if( is_single() ){
			$str = ' rel="' . $this->options['itemimg_anchor_rel'] . '"';
		}else{
			$str = '';
		}
		return $html . $str;
	}
	
	function filter_permalink( $link ) {
		
		if(strpos('?page_id=4', $link) || strpos('?page_id=3', $link) || strpos('usces-cart', $link) || strpos('usces-member', $link) )
			$link = str_replace('http://', 'https://', $link);
	
		return $link;
	}

	function filter_cart_page_header($html){
		if( !empty($this->options['cart_page_data']['header']['cart']) ){
			$html = $this->options['cart_page_data']['header']['cart'];
		}
		return stripslashes($html);
	}
	
	function filter_cart_page_footer($html){
		if( !empty($this->options['cart_page_data']['footer']['cart']) ){
			$html = $this->options['cart_page_data']['footer']['cart'];
		}
		return stripslashes($html);
	}
	
	function filter_customer_page_header($html){
		if( !empty($this->options['cart_page_data']['header']['customer']) ){
			$html = $this->options['cart_page_data']['header']['customer'];
		}
		return stripslashes($html);
	}
	
	function filter_customer_page_footer($html){
		if( !empty($this->options['cart_page_data']['footer']['customer']) ){
			$html = $this->options['cart_page_data']['footer']['customer'];
		}
		return stripslashes($html);
	}
	
	function filter_delivery_page_header($html){
		if( !empty($this->options['cart_page_data']['header']['delivery']) ){
			$html = $this->options['cart_page_data']['header']['delivery'];
		}
		return stripslashes($html);
	}
	
	function filter_delivery_page_footer($html){
		if( !empty($this->options['cart_page_data']['footer']['delivery']) ){
			$html = $this->options['cart_page_data']['footer']['delivery'];
		}
		return stripslashes($html);
	}
	
	function filter_confirm_page_header($html){
		if( !empty($this->options['cart_page_data']['header']['confirm']) ){
			$html = $this->options['cart_page_data']['header']['confirm'];
		}
		return stripslashes($html);
	}
	
	function filter_confirm_page_footer($html){
		if( !empty($this->options['cart_page_data']['footer']['confirm']) ){
			$html = $this->options['cart_page_data']['footer']['confirm'];
		}
		return stripslashes($html);
	}
	
	function filter_cartcompletion_page_header($html){
		if( !empty($this->options['cart_page_data']['header']['completion']) ){
			$html = $this->options['cart_page_data']['header']['completion'];
		}
		return stripslashes($html);
	}
	
	function filter_cartcompletion_page_footer($html){
		if( !empty($this->options['cart_page_data']['footer']['completion']) ){
			$html = $this->options['cart_page_data']['footer']['completion'];
		}
		return stripslashes($html);
	}
	
	function filter_login_page_header($html){
		if( !empty($this->options['member_page_data']['header']['login']) ){
			$html = $this->options['member_page_data']['header']['login'];
		}
		return stripslashes($html);
	}
	
	function filter_login_page_footer($html){
		if( !empty($this->options['member_page_data']['footer']['login']) ){
			$html = $this->options['member_page_data']['footer']['login'];
		}
		return stripslashes($html);
	}
	
	function filter_newmember_page_header($html){
		if( !empty($this->options['member_page_data']['header']['newmember']) ){
			$html = $this->options['member_page_data']['header']['newmember'];
		}
		return stripslashes($html);
	}
	
	function filter_newmember_page_footer($html){
		if( !empty($this->options['member_page_data']['footer']['newmember']) ){
			$html = $this->options['member_page_data']['footer']['newmember'];
		}
		return stripslashes($html);
	}
	
	function filter_newpass_page_header($html){
		if( !empty($this->options['member_page_data']['header']['newpass']) ){
			$html = $this->options['member_page_data']['header']['newpass'];
		}
		return stripslashes($html);
	}
	
	function filter_newpass_page_footer($html){
		if( !empty($this->options['member_page_data']['footer']['newpass']) ){
			$html = $this->options['member_page_data']['footer']['newpass'];
		}
		return stripslashes($html);
	}
	
	function filter_changepass_page_header($html){
		if( !empty($this->options['member_page_data']['header']['changepass']) ){
			$html = $this->options['member_page_data']['header']['changepass'];
		}
		return stripslashes($html);
	}
	
	function filter_changepass_page_footer($html){
		if( !empty($this->options['member_page_data']['footer']['changepass']) ){
			$html = $this->options['member_page_data']['footer']['changepass'];
		}
		return stripslashes($html);
	}
	
	function filter_memberinfo_page_header($html){
		if( !empty($this->options['member_page_data']['header']['memberinfo']) ){
			$html = $this->options['member_page_data']['header']['memberinfo'];
		}
		return stripslashes($html);
	}
	
	function filter_memberinfo_page_footer($html){
		if( !empty($this->options['member_page_data']['footer']['memberinfo']) ){
			$html = $this->options['member_page_data']['footer']['memberinfo'];
		}
		return stripslashes($html);
	}
	
	function filter_membercompletion_page_header($html){
		if( !empty($this->options['member_page_data']['header']['completion']) ){
			$html = $this->options['member_page_data']['header']['completion'];
		}
		return stripslashes($html);
	}
	
	function filter_membercompletion_page_footer($html){
		if( !empty($this->options['member_page_data']['footer']['completion']) ){
			$html = $this->options['member_page_data']['footer']['completion'];
		}
		return stripslashes($html);
	}
	
}
?>
