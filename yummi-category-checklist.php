<?php
/*
Plugin Name: Parent Category AutoCheck + Category Tree Checklist
Version: 1.1.8
Description: Preserves the category hierarchy on the post editing screen + Check Parent Automatically + Auto scroll to first checked
Author: Alex Egorov
Author URI: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SLHFMF373Z9GG&source=url
Plugin URI: https://wordpress.org/plugins/yummi-auto-check-parent-category-category-tree-checklist/
License: GPLv2 or later (license.txt)
Text Domain: cchl
Domain Path: /languages
*/

define('cchl_URL', plugins_url( '/', __FILE__ ) );
define('cchl_PATH', plugin_dir_path(__FILE__) );

function ycc_array_depth($array) {
	$iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array), \RecursiveIteratorIterator::CHILD_FIRST);
	$iterator->rewind();
	$maxDepth = 0;
	foreach ($iterator as $k => $v) {
			$depth = $iterator->getDepth();
			if ($depth > $maxDepth) {
					$maxDepth = $depth;
			}
	}
	return ($maxDepth-1)/2;
}
function ycc_get_taxonomy_hierarchy( $taxonomy, $hide_empty = 0, $parent = 0 ) {
	// only 1 taxonomy
	$taxonomy = is_array( $taxonomy ) ? array_shift( $taxonomy ) : $taxonomy;
	// get all direct decendants of the $parent
	$terms = get_terms( $taxonomy, array( 'parent' => $parent, 'hide_empty' => $hide_empty ) );
	// prepare a new array.  these are the children of $parent
	// we'll ultimately copy all the $terms into this new array, but only after they
	// find their own children
	$children = array();
	// go through all the direct decendants of $parent, and gather their children
	foreach ( $terms as $term ){
		// recurse to get the direct decendants of "this" term
		$term->children = ycc_get_taxonomy_hierarchy( $taxonomy, $hide_empty, $term->term_id );
		// add the term to our new array
		$children[ $term->term_id ] = $term;
	}
	// send the results back to the caller
	return $children;
}
/**
 * Recursively get all taxonomies as complete hierarchies
 *
 * @param $taxonomies array of taxonomy slugs
 * @param $parent int - Starting parent term id
 *
 * @return array
 */
function ycc_get_taxonomy_hierarchy_multiple( $taxonomies, $hide_empty = 0, $parent = 0 ) {
	if ( ! is_array( $taxonomies )  ) {
		$taxonomies = array( $taxonomies );
	}
	$results = array();
	foreach( $taxonomies as $taxonomy ){
		$terms = ycc_get_taxonomy_hierarchy( $taxonomy, $hide_empty, $parent );
		if ( $terms ) {
			$results[ $taxonomy ] = $terms;
		}
	}
	return $results;
}

function ycc_recursive($array, $type = 'term_id', $parent = 0, $nbsp = ''){
	foreach($array as $key => $value){
		if(is_array($value->children)){
			if ($value->parent == 0){
				echo '<b>'.$value->$type.'</b><br/>';
				$parent = $value->term_id;
				$nbsp = '';
			}else{
				if( $parent == $value->parent ){
					$parent = $value->term_id;
					$nbsp .= '&emsp;';
				}
				echo $nbsp.'- '.$value->$type.'<br/>';
			}
			ycc_recursive($value->children, $type, $parent, $nbsp);
		}
	}
}

class Yummi_Category_Checklist {

	static function init() {
		add_filter( 'wp_terms_checklist_args', array( __CLASS__, 'yummi_checklist_args' ) );
		add_filter( 'allowed_block_types', array( __CLASS__, 'yummi_gutenberg_checklist_args' ) );
		//if( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() )

    // global $current_screen;
    // if (!isset($current_screen)) {$current_screen = get_current_screen();}
    // if ( ( method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor() ) || ( function_exists('is_gutenberg_page')) && is_gutenberg_page() ) ) {
    //     // DO SOMETHING HERE
    // }
	}

	static function yummi_checklist_args( $args ) {
		add_action( 'admin_footer', array( __CLASS__, 'script' ) );
		$args['checked_ontop'] = false;
		return $args;
	}

	static function yummi_gutenberg_checklist_args( $args ) {
		add_action( 'admin_footer', array( __CLASS__, 'script' ) );
		return $args;
	}

	static function yummi_plugins() {
		if(!is_admin() || !current_user_can("manage_options"))
			die( 'yummi-oops' );
		if(!function_exists('yummi_plugins'))
			include_once( $this->path . '/includes/yummi-plugins.php' );
	}

	static function script() {
		// load_plugin_textdomain( 'cchl', cchl_PATH . '/languages' );
		$cchla = array(
			 'UncheckMain' => 0
			,'sync' => 1
			,'collapse' => 1
			,'collapse_acf' => 1
		);
		$cchl = get_option('cchl',$cchla);
		//update_option('cchl', $cchla);
		//delete_option('cchl');

		$depth = ycc_array_depth(ycc_get_taxonomy_hierarchy( 'category' )); // ($taxonomy,$hide_empty,$parent) // ycc_get_taxonomy_hierarchy_multiple(array( 'category','post_tag' )); ?>
		<script>
			(function($){

				function cat_check(){
					// console.log(wp.data.select('core/editor')); //core/button //core/editor
					var reloader = setInterval(function(){
						var gutenberg;
						if( $('[class$="__hierarchical-terms-list"]').length ) {
							gutenberg = true;
							var classes = '[class$="__hierarchical-terms-list"]';
						}else{
							var classes = '.categorychecklist, .cat-checklist';
						}
						// gutenberg ? console.log('gutenberg editor') : console.log('old editor');

						if( $(classes).length ){
							$(classes).each(function(index, value){
								if( gutenberg ){
									var loader = setInterval(function(){
										if( value.innerHTML !== "" ){
											ycc_load(gutenberg);
											// console.log(index + ' Categories loaded!');
											clearInterval(loader);
										}
									},500);
								}else
									ycc_load(gutenberg);
							});
							clearInterval(reloader);
						}
					},500);
				}
				cat_check(); // check on page load
				var sys_buttons_loader = setInterval(function(){ // check on click toggles
					if( $('button.components-icon-button:has(svg.dashicons-admin-generic), .components-panel h2 button.components-panel__body-toggle, button.edit-post-sidebar__panel-tab, .components-panel h2 button').length ){
						$('button.components-icon-button:has(svg.dashicons-admin-generic), .components-panel h2 button.components-panel__body-toggle, button.edit-post-sidebar__panel-tab, .components-panel h2 button').on('click',function(){ // check on system buttons clicks
							cat_check();
							// console.log('system buttons clicks');
						});
						clearInterval(sys_buttons_loader);
					}
				}, 2000);

				// var reload_check = false; var publish_button_click = false;
		    // jQuery(document).ready(function($) {
		    //     add_publish_button_click = setInterval(function() {
		    //         $publish_button = jQuery('.edit-post-header__settings .editor-post-publish-button');
		    //         if ($publish_button && !publish_button_click) {
		    //             publish_button_click = true;
		    //             $publish_button.on('click', function() {
		    //                 var reloader = setInterval(function() {
		    //                     if (reload_check) {return;} else {reload_check = true;}
		    //                     postsaving = wp.data.select('core/editor').isSavingPost();
		    //                     autosaving = wp.data.select('core/editor').isAutosavingPost();
		    //                     success = wp.data.select('core/editor').didPostSaveRequestSucceed();
		    //                     console.log('Saving: '+postsaving+' - Autosaving: '+autosaving+' - Success: '+success);
		    //                     if (postsaving || autosaving || !success) {classic_reload_check = false; return;}
		    //                     clearInterval(reloader);
				//
		    //                     value = document.getElementById('metabox_input_id').value;
		    //                     if (value == 'trigger_value') {
		    //                         if (confirm('Page reload required. Refresh the page now?')) {
		    //                             window.location.href = window.location.href+'&refreshed=1';
		    //                         }
		    //                     }
		    //                 }, 1000);
		    //             });
		    //         }
		    //     }, 500);
		    // });

				function ycc_load(gutenberg){
					//console.log('<?//=add_filter('allowed_block_types',array(__CLASS__))?>');

					$('[class$="__hierarchical-terms-list"]').addClass('categorychecklist');
					$('[class$="__hierarchical-terms-subchoices"]').addClass('is-parent children');
					$('ul.cat-checklist').addClass('categorychecklist').attr("id","categorychecklist").wrap('<div id="category-all"></div>');
					$('[class$="__hierarchical-terms-choice"],.categorychecklist li,[data-taxonomy="category"] li').addClass('li');

					$('.categorychecklist').each(function() {// Scrolls to first checked category
						var $list = $(this);
						var $firstChecked = $list.find(':checkbox:checked').first();
						if(!$firstChecked.length)
							return;
						var pos_first = $list.find(':checkbox').position().top;
						var pos_checked = $firstChecked.position().top;
						$list.closest('.tabs-panel').scrollTop(pos_checked - pos_first + 10);
					});

					/* отключаем от чека заглавные которые имеют детей */

					<?php if( $cchl['UncheckMain'] == 0 ){ ?>
						$('.categorychecklist .children, [data-taxonomy="category"] .acf-checkbox-list .children').each(function() {
							if(gutenberg){
								// console.log($(this).first());
								if( $(this).children()['length'] == 1 && $(this).children()[0]['childElementCount'] != 2 )
									$(this).find("> .li > input").addClass("disabled");//.attr('onClick','return false;');
							}else{
								// console.log($(this).first().find('ul')['length']);
								// console.log('children length:'+$(this).children()['length']+' | childElementCount:'+$(this).children()[0]['childElementCount']);
								if( $(this).children()['length'] == 1 && $(this).first().find('ul')['length'] != 0 ) //$(this).children()[0]['childElementCount'] != 1
									$(this).find("> li > label > input").addClass("disabled").attr('onClick', 'return false;');
							}
						});
						$('.categorychecklist > .li:has(.children), [data-taxonomy="category"] .acf-checkbox-list > .li:has(.children)').each(function(){
							if(gutenberg)
								$(this).find("> input:first-child").addClass("disabled");//.attr('onClick','return false;');
							else
								$(this).find(">:first-child input").addClass("disabled").attr('onClick','return false;');
						});
					<?php } ?>

					// $('input.disabled').unbind("click");
					// $('input.disabled').on('click',function(event){
					// 	event.preventDefault();
					// 	event.stopPropagation();
					// 	$(this).prop("checked",$(this)[0]['checked']);
					// 	return false;
					// });

					<?php if( $cchl['collapse'] == 1 ){ ?>
						/* add +/- to colapce categories */
						$('.categorychecklist .li').each(function(){
							if($(this).find('.children').length){
								$(this).addClass('is-parent');
								if($(this).find('.toggler').length){
									$(this).find('.toggler').detach(); //$('.categorychecklist .li .toggler').remove();
									$(this).prepend('<span class="toggler"></span>');
								}else{
									$(this).prepend('<span class="toggler"></span>');
								}
							}
						});
						$('.categorychecklist .li .toggler').click(function(){
							$(this).parent().toggleClass('open');
						});
					<?php } else { ?>
						$('.categorychecklist .li').each(function(){
							if($(this).find('.children').length)
								$(this).addClass('is-parent');
						});
					<?php } ?>

					/* Чекаем главную категорию если чаилд выбран */
					$('.categorychecklist .li<?php echo $cchl['UncheckMain'] == 1 ? ':not(.is-parent)' : null?> :checkbox, [data-taxonomy="category"] .acf-checkbox-list .li .children .li :checkbox').on('change',function(e){
						/* on('change' - работает при сохранении, on('click' - не работает при сохранении но правильно работает выделение родителей */
				    var n = $(this).first().find("input:checked").length; // $(this).parent().parent().parent().find("input:checked").length
						<?php $data = '.parent().parent()';
						for ($i=0; $i < $depth; $i++) {
							$data .= '.parent().parent()';
							echo 'var input_'.$i.' = $(this)'.$data.'.children("label").children();';
						}	?>
						// console.log('input_0:');
						// console.log(input_0);
				    if(n > 0){
							<?php for ($i=0; $i < $depth; $i++) {
								echo 'input_'.$i.'.prop("checked", true);';
							} ?>
							<?php if( $cchl['sync'] == 1 ){ ?>
								setTimeout(function() {
									$('[data-taxonomy="category"] .acf-checkbox-list .li :checkbox').prop("checked", false);
									$('.categorychecklist .li :checkbox:checked').each(function(index, value) {
										var id = $(this).attr('id');
										id = id.replace(/[^0-9]/g,'');
								    //console.log('div' + index + ': ' + id);
										$('[data-taxonomy="category"] .acf-checkbox-list .li[data-id='+id+']').children().children().prop("checked", true);
									});
								}, 1000);
							<?php } ?>
						}else{
							if(gutenberg){
								<?php
								 	$data = '';
								 	for ($i=0; $i < $depth; $i++) {
										$data .= $i == 1 ? '.parent().parent()' : '.parent().parent().parent()';
								 		echo 'if( $(this)'.$data.'.is(".is-parent") ){
														if( $(this)'.$data.'.children("input").is(":checked") )
															$(this)'.$data.'.children("input").click();
														if( $(this)'.$data.'.find(":has(:checkbox:checked)").length ){
															$(this)'.$data.'.children("input").click();
														}else{
															if( $(this)'.$data.'.children("input").is(":checked") )
																$(this)'.$data.'.children("input").click();
														}
													}';
								 	}
								?>
							}else{
								<?php if( $cchl['sync'] == 1 ){ ?>
									setTimeout(function() {
										$('[data-taxonomy="category"] .acf-checkbox-list .li :checkbox').prop("checked", false);
										$('.categorychecklist .li :checkbox:checked').each(function(index, value) {
											var id = $(this).attr('id');
											id = id.replace(/[^0-9]/g,'');
											//console.log('div' + index + ': ' + id);
											$('[data-taxonomy="category"] .acf-checkbox-list .li[data-id='+id+']').children().children().prop("checked", true);
										});
									}, 1000);
								<?php }
								for( $i=0; $i < $depth; $i++ ){
									 echo 'input_'.$i.'.prop("checked", false);';
								} ?>
								$('.categorychecklist .li :checkbox:checked').each(function(index,value){
									<?php
										$data = '.parent().parent()';
										for ($i=0; $i < $depth; $i++) {
											$data .= '.parent().parent()';
											echo '$(this)'.$data.'.children("label").children().prop("checked", true);';
										} ?>
								});
								// if($(this).parent().parent().hasClass("is-parent")){
								// 	console.log('is-parent clicked!');
								// 	$(this).prop('checked', !($(this).is(':checked')));
								// }
							}
						}
				    //console.log(n + (n === 1 ? " is" : " are") + " checked!");
				  });

					<?php if( $cchl['collapse_acf'] == 1 ){ ?>
						/* add +/- to ACF colapce categories */
						$('[data-taxonomy="category"] .acf-checkbox-list .li').each(function(){
							if($(this).find('.children').length){
								$(this).addClass('is-parent');
								$(this).prepend('<span class="toggler"></span>')
							}
						});
						$('[data-taxonomy="category"] .acf-checkbox-list .li .toggler').click(function(){
							$(this).parent().toggleClass('open');
						});
					<?php } ?>
				}
				})(jQuery);
			</script>
			<?php if( $cchl['collapse'] == 1 ){ ?>
				<style>
				/*[id$="-all"]:hover{width: 100%;}*/
				/*.categorychecklist .wpseo-make-primary-term,*/.categorychecklist .li .children{display:none}
				.categorychecklist .li.is-parent>label{font-weight:bold}
				.categorychecklist .li.open>.children{display:block}
				.categorychecklist .li.open>.toggler::after{content:" \f460"}
				.categorychecklist .toggler::after{content:" \f132"}
				.categorychecklist .toggler{float:<?php echo is_rtl() ? 'left' : 'right' ?>;color:#666;padding-top:5px;font:400 14px/1 dashicons!important;cursor:pointer}
				</style>
			<?php }
			if( $cchl['collapse_acf'] == 1 ){ ?>
				<style>
				/*[data-taxonomy="category"] .acf-checkbox-list .wpseo-make-primary-term,*/[data-taxonomy="category"] .acf-checkbox-list .li .children { display: none	}
				[data-taxonomy="category"] .acf-checkbox-list .li.is-parent>label{font-weight:bold}
				[data-taxonomy="category"] .acf-checkbox-list .li.open>.children{display:block}
				[data-taxonomy="category"] .acf-checkbox-list .li.open>.toggler::after{content:" \f460"}
				[data-taxonomy="category"] .acf-checkbox-list .toggler::after{content:" \f132"}
				[data-taxonomy="category"] .acf-checkbox-list .toggler{float:<?php echo is_rtl() ? 'left' : 'right' ?>;color:#666;padding-top:5px;font:400 14px/1 dashicons!important;cursor:pointer}
				</style>
			<?php }
	}
	static function admin_menu() {
		/*add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );*/
		if( empty( $GLOBALS['admin_page_hooks']['yummi']) )
			$main_page = add_menu_page( 'yummi', 'Yummi '.__('Plugins'), 'manage_options', 'yummi', array($this, 'yummi_plugins'), $this->url.'/includes/img/dashicons-yummi.png' );
		/*add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );*/
		//$main_page = add_submenu_page( 'yummi','yummicategory', __('Yummi Category Checklist','cchl'), 'manage_options', 'yummicategory', array( $this, 'options_do_page')  );

		//add_action( 'admin_print_styles-' . $main_page, array(&$this, 'admin_page_styles') );
		//add_action( 'admin_print_scripts-'. $main_page, array(&$this, 'admin_page_scripts') );
		//register_setting('yummicategory', $this->option_name, array($this, 'validate'));
	}
}
Yummi_Category_Checklist::init();

/* Multiplugin functions */
if(!function_exists('wp_get_current_user'))
	include(ABSPATH . "wp-includes/pluggable.php");

/* Красивая функция вывода масивов */
if (!function_exists('prr')){ function prr($str) { echo "<pre>"; print_r($str); echo "</pre>\r\n"; } }

if( isset($_REQUEST['page']) && $_REQUEST['page'] == 'yummi' && !function_exists('yummi_register_settings') || isset($_REQUEST['page']) && $_REQUEST['page'] == 'cchl' && !function_exists('yummi_register_settings') ){ /* Filter pages */
	add_action( 'admin_init', 'yummi_register_settings' );
	function yummi_register_settings() {
		$url = plugin_dir_url( __FILE__ );
		//register_setting( 'cchl_admin_menu', 'cchl', 'cchl_validate_options' );
		wp_enqueue_style( 'yummi-style', $url . '/css/admin_style.min.css' );
		wp_enqueue_style( 'yummi-hint', $url . '/css/hint.min.css' );

		if ( !current_user_can('manage_options') )
			wp_die(__('Sorry, you are not allowed to install plugins on this site.'));
	}
}

add_action('admin_menu', 'cchl_admin_menu');
function cchl_admin_menu() {
	if( empty( $GLOBALS['admin_page_hooks']['yummi']) )
		add_menu_page( 'yummi', 'Yummi '.__('Plugins'), 'manage_options', 'yummi', 'yummi_plugins_cchl', cchl_URL.'/includes/img/dashicons-yummi.png' );

	/*add_submenu_page( parent_slug, page_title, menu_title, rights(user can manage_options), menu_slug, function ); */
	add_submenu_page('yummi', __('Category AutoCheck','cchl'), __('Category AutoCheck','cchl'), 'manage_options', 'category_autocheck', 'category_autocheck_options_page');
}

function yummi_plugins_cchl() { if(!function_exists('yummi_plugins')) include_once( cchl_PATH . '/includes/yummi-plugins.php' ); }

function category_autocheck_options_page() {

	global $cchl;

	$cchl = array(
		  'UncheckMain' => 0
		 ,'sync' => 1
		 ,'collapse' => 1
		 ,'collapse_acf' => 1
	);
	//update_option("cchl", $cchl);

	#Get option values
	$cchl = get_option( 'cchl', $cchl );

	// prr($cchl);

	#Get new updated option values, and save them
	if( @$_POST['action'] == 'update' ) {

		check_admin_referer('update-options-cchl');

		$cchl = array( //(int)$_POST[cchl] //sanitize_text_field($_POST[cchl])
			//Валидация данных https://codex.wordpress.org/%D0%92%D0%B0%D0%BB%D0%B8%D0%B4%D0%B0%D1%86%D0%B8%D1%8F_%D0%B4%D0%B0%D0%BD%D0%BD%D1%8B%D1%85
			  'UncheckMain' => $_POST['UncheckMain']?1:0
			 ,'sync' => $_POST['sync']?1:0
			 ,'collapse' => $_POST['collapse']?1:0
			 ,'collapse_acf' => $_POST['collapse_acf']?1:0
		);
		update_option("cchl", $cchl);
		echo '<div id="message" class="updated fade"><p><strong>'.__('Settings saved.').'</strong></p></div>'; //<script type="text/javascript">document.location.reload(true);</script>
	}
	global $wp_version;
	$isOldWP = floatval($wp_version) < 2.5;

	$beforeRow = $isOldWP ? "<p>" : '<tr valign="top"><th scope="row">';
	$betweenRow = $isOldWP ? "" : '</th><td>';
	$afterRow = $isOldWP ? "</p>" : '</td><tr>'; ?>

	<div class="wrap">

		<?php screen_icon(); echo "<h1>" . __('Parent Category AutoCheck Plugin', 'cchl') .' '. __( 'Settings' ) . "</h1>"; ?>
		<div style='float:right;margin-top: -27px;'><span style="font-size:1.3em">&starf;</span> <a href="https://wordpress.org/support/plugin/yummi-auto-check-parent-category-category-tree-checklist/reviews/#new-post" target="_blank"><?php _e('Rate','cchl')?></a> &ensp; ❤ <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SLHFMF373Z9GG&source=url" target="_blank"><?php _e('Donate', 'cchl')?></a></div>

		<form method="post" action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>">

			<?php
			if(function_exists('wp_nonce_field'))
				wp_nonce_field('update-options-cchl');

			$hierarchy = ycc_get_taxonomy_hierarchy( 'category' ); // $taxonomy, $hide_empty, $parent // ycc_get_taxonomy_hierarchy_multiple( array( 'category', 'post_tag' ) )
			$depth = ycc_array_depth($hierarchy);

			//ycc_recursive($hierarchy, 'name');
				// [term_id] => 1
        // [name] => Parent category
        // [slug] => bez-rubriki
        // [term_group] => 0
        // [term_taxonomy_id] => 1
        // [taxonomy] => category
        // [description] =>
        // [parent] => 0
        // [count] => 2
        // [filter] => raw
        // [term_order] => 0
			//prr($hierarchy);

			if(!$isOldWP)
				echo "<table class='form-table'>"; ?>

			<?php echo $beforeRow ?>

				<label for="UncheckMain"><?php _e('Allow UnCheck Parents','cchl')?>:<br/><small>- <i><?php _e('no auto check for parents','cchl')?></i> -</small><br/>&emsp;<small><?php _e('found max child levels','cchl');?>: <?php echo $depth;?></small></label>
			<?php echo $betweenRow ?>
				<input type="checkbox" id="UncheckMain" name="UncheckMain" <?php checked( $cchl['UncheckMain'], 1 ); ?>/>
			<?php echo $afterRow ?>

			<?php echo $beforeRow ?>
				<label for="sync"><?php _e('Synchronously allocate categories in ACF:','cchl')?></label>
			<?php echo $betweenRow ?>
				<input type="checkbox" id="sync" name="sync" <?php checked( $cchl['sync'], 1 ); ?>/>
			<?php echo $afterRow ?>

			<?php echo $beforeRow ?>
				<label for="collapse"><?php _e('Collapse parent:','cchl')?></label>
			<?php echo $betweenRow ?>
				<input type="checkbox" id="collapse" name="collapse" <?php checked( $cchl['collapse'], 1 ); ?>/>
			<?php echo $afterRow ?>

			<?php echo $beforeRow ?>
				<label for="collapse_acf"><?php _e('Collapse ACF parent:','cchl')?></label>
			<?php echo $betweenRow ?>
				<input type="checkbox" id="collapse_acf" name="collapse_acf" <?php checked( $cchl['collapse_acf'], 1 ); ?>/>
			<?php echo $afterRow ?>

			<?php if(!$isOldWP)
					echo "</table>"; ?>

			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="page_options" value="cchl" />

			<p class="submit">
				<input type="submit" name="Submit" class="button-primary collectsnow" value="<?php _e('Save Changes') ?>" />
			</p>
			<span id="log"></span>

		</form>

	</div>

<?php }

function yummi_cc_plugin_action_links($links, $file) {
    static $this_plugin;
    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) { // check to make sure we are on the correct plugin
			$settings_link = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SLHFMF373Z9GG&source=url" target="_blank">❤ ' . __('Donate', 'cchl') . '</a> | <a href="admin.php?page=category_autocheck">' . __('Settings') . '</a>';
        array_unshift($links, $settings_link); // add the link to the list
    }
    return $links;
}
add_filter('plugin_action_links', 'yummi_cc_plugin_action_links', 10, 2);
