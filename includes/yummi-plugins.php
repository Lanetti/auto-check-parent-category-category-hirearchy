<?php

if ( is_multisite() && ! is_network_admin() ) {
	wp_redirect( network_admin_url( 'plugin-install.php' ) );
	exit();
}

$wp_list_table = _get_list_table('WP_Plugin_Install_List_Table');
$pagenum = $wp_list_table->get_pagenum();

if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
	$location = remove_query_arg( '_wp_http_referer', wp_unslash( $_SERVER['REQUEST_URI'] ) );

	if ( ! empty( $_REQUEST['paged'] ) ) {
		$location = add_query_arg( 'paged', (int) $_REQUEST['paged'], $location );
	}

	wp_redirect( $location );//?s=yummi-multicategory-breadcrumbs&tab=search&type=term
	exit;
}
$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );

if ( $pagenum > $total_pages && $total_pages > 0 ) {
	wp_redirect( add_query_arg( 'paged', $total_pages ) );
	exit;
}

wp_enqueue_script( 'plugin-install' );
//if ( 'plugin-information' != $tab )
	add_thickbox();

//$body_id = $tab;

wp_enqueue_script( 'updates' );

/**
 * Fires before each tab on the Install Plugins screen is loaded.
 *
 * The dynamic portion of the action hook, `$tab`, allows for targeting
 * individual tabs, for instance 'install_plugins_pre_plugin-information'.
 *
 * @since 2.7.0
 */
//do_action( "install_plugins_pre_$tab" );
/*
 * Call the pre upload action on every non-upload plugin install screen
 * because the form is always displayed on these screens.
 */
//if ( 'upload' !== $tab )
	do_action( 'install_plugins_pre_upload' ); /** This action is documented in wp-admin/plugin-install.php */

$_REQUEST = array(
	 's' => 'evilex'
	,'tab' => 'search'
	,'type' => 'author'
);
$_GET = $_REQUEST;
$wp_list_table->prepare_items(); ?>
<div class="wrap <?php echo esc_attr( "plugin-install-tab-$tab" ); ?>">

	<section id="plugin-filter">
		<div id="poststuff">

			<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
				<div id="postbox-container-1" class="postbox-container" style="text-align:center">
					<div class="postbox">
						<h3 class="hndle"><span><?php _e('Welcome to Yummi plugins collection') ?></span></h3>
						<div class="inside"><?php _e('We collected some links for your convenience')?></div>
					</div>
				</div>

				<div id="postbox-container-2" class="postbox-container">
					<div class="postbox">
						<h3 class="hndle"><span><?php _e('Donate') ?></span></h3>
						<div class="inside">
							<div style="display: inline-block; width:25%; text-align:center; vertical-align:top;">
								<strong><?php _e('WebMoney') ?></strong>
								<hr/>
								<div class="ewm-widget-donate" data-guid="4fbd3229-7deb-403b-930f-f2301b532f65" data-type="compact"></div>

								<script type="text/javascript">//<!--
								(function(w, d, id) {
										w.ewmAsyncWidgets = function() { EWM.Widgets.init(); };
										if (!d.getElementById(id)) {
												var s = d.createElement('script'); s.id = id; s.async = true; s.src = '//events.webmoney.ru/js/ewm-api.js?11';
												(d.getElementsByTagName('head')[0] || d.documentElement).appendChild(s);
										}
								})(window, document, 'ewm-js-api');
								//-->
								</script>
							</div>
							<div style="display: inline-block; width:20%; text-align:center; vertical-align:top;">
								<strong><?php _e('PayPal') ?></strong>
								<hr/>
								<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
									<input type="hidden" name="cmd" value="_s-xclick" />
									<input type="hidden" name="hosted_button_id" value="SLHFMF373Z9GG" />
									<input type="image" src="https://www.paypalobjects.com/en_US/IT/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
									<img alt="" border="0" src="https://www.paypal.com/en_IT/i/scr/pixel.gif" width="1" height="1" />
								</form>
								<!--<a href="https://yummi.club/paypal" target="_blank">
									<img src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" alt="<?php _e('PayPal - The safer, easier way to pay online!') ?>" width="147">
								</a>-->
							</div>
							<div style="display: inline-block; width:50%; text-align:center; vertical-align:top;">
								<strong><?php _e('Yandex.Money') ?></strong>
								<hr/>
								<iframe frameborder="0" allowtransparency="true" scrolling="no" src="https://money.yandex.ru/embed/donate.xml?account=41001556291842&quickpay=donate&payment-type-choice=on&default-sum=&targets=%D0%9F%D0%BE%D0%B4%D0%B4%D0%B5%D1%80%D0%B6%D0%BA%D0%B0+%D0%BF%D1%80%D0%BE%D0%B5%D0%BA%D1%82%D0%B0&project-name=Yummi&project-site=https%3A%2F%2Fyummi.club%2F&button-text=05&successURL=" width="422" height="91"></iframe>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
			<br class="clear">

			<h1><?php _e('Add Plugins')?> <?php _e('from Yummi') ?></h1>

			<?php $wp_list_table->display(); ?>

	</section>

</div>
