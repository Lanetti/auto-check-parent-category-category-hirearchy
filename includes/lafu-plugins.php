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
    wp_redirect( $location );
    exit();
}

$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );
if ( $pagenum > $total_pages && $total_pages > 0 ) {
    wp_redirect( add_query_arg( 'paged', $total_pages ) );
    exit();
}

wp_enqueue_script( 'plugin-install' );
add_thickbox();
wp_enqueue_script( 'updates' );
do_action( 'install_plugins_pre_upload' );

$_REQUEST = array(
    's' => 'evilex',
    'tab' => 'search',
    'type' => 'author',
);
$_GET = $_REQUEST;
$wp_list_table->prepare_items();
?>

<div class="wrap plugin-install-tab-search">
    <section id="plugin-filter">
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
                <div id="postbox-container-1" class="postbox-container" style="text-align:center">
                    <div class="postbox">
                        <h3 class="hndle"><span><?php _e('Welcome to Lafu Plugins Collection', 'lca'); ?></span></h3>
                        <div class="inside"><?php _e('We collected some links for your convenience', 'lca'); ?></div>
                    </div>
                </div>
                <div id="postbox-container-2" class="postbox-container">
                    <div class="postbox">
                        <h3 class="hndle"><span><?php _e('Donate', 'lca'); ?></span></h3>
                        <div class="inside">
                            <div style="display: inline-block; width:20%; text-align:center; vertical-align:top;">
                                <strong><?php _e('PayPal', 'lca'); ?></strong>
                                <hr/>
                                <a href="#" target="_blank">Donate via PayPal</a>
                            </div>
                            <div style="display: inline-block; width:20%; text-align:center; vertical-align:top;">
                                <strong><?php _e('Buy Me a Coffee', 'lca'); ?></strong>
                                <hr/>
                                <a href="#" target="_blank">Support Us on Buy Me a Coffee</a>
                            </div>
                            <div style="display: inline-block; width:20%; text-align:center; vertical-align:top;">
                                <strong><?php _e('Ko-fi', 'lca'); ?></strong>
                                <hr/>
                                <a href="#" target="_blank">Support Us on Ko-fi</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br class="clear">
            <h1><?php _e('Add Plugins from Lafu', 'lca'); ?></h1>
            <?php $wp_list_table->display(); ?>
        </div>
    </section>
</div>
