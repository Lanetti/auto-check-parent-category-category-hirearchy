<?php
/*
Plugin Name: Parent Category AutoCheck + Category Tree Checklist
Version: 1.2.1
Description: Preserves the category hierarchy on the post editing screen + Check Parent Automatically + Auto scroll to first checked
Author: Elsama
Author URI: https://lafu.fi
Plugin URI: https://lafu.fi/plugins/parent-category-autocheck
License: GPLv2 or later (license.txt)
Text Domain: lca
Domain Path: /languages
*/

define('LCA_URL', plugin_dir_url(__FILE__));
define('LCA_PATH', plugin_dir_path(__FILE__));

class Lafu_Category_Checklist {
    public static function init() {
        add_filter('wp_terms_checklist_args', [__CLASS__, 'modify_checklist_args']);
        add_action('admin_footer', [__CLASS__, 'enqueue_scripts']);
    }

    public static function modify_checklist_args($args) {
        $args['checked_ontop'] = false;
        return $args;
    }

    public static function enqueue_scripts() {
        if (!is_admin()) {
            return;
        }
        ?>
        <script>
            (function($) {
                function updateCategoryChecklist() {
                    var categories = $('.categorychecklist, .cat-checklist');
                    if (categories.length) {
                        categories.each(function() {
                            var firstChecked = $(this).find(':checkbox:checked').first();
                            if (!firstChecked.length) { return; }
                            var offset = firstChecked.position().top;
                            $(this).closest('.tabs-panel').scrollTop(offset - 10);
                        });
                    }
                }
                $(document).ready(updateCategoryChecklist);
            })(jQuery);
        </script>
        <?php
    }
}

Lafu_Category_Checklist::init();

add_action('admin_menu', 'lca_admin_menu');
function lca_admin_menu() {
    add_options_page('Category AutoCheck', 'Category AutoCheck', 'manage_options', 'category_autocheck', 'category_autocheck_options_page');
}

function category_autocheck_options_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized access.', 'lca'));
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Parent Category AutoCheck Settings', 'lca'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('lca_settings_group');
            do_settings_sections('category_autocheck');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function() {
    register_setting('lca_settings_group', 'lca_settings');
    add_settings_section('lca_main_section', esc_html__('Settings', 'lca'), null, 'category_autocheck');
    add_settings_field('enable_auto_check', esc_html__('Enable Auto Check', 'lca'), function() {
        $options = get_option('lca_settings', ['enable_auto_check' => 1]);
        echo '<input type="checkbox" name="lca_settings[enable_auto_check]" value="1" ' . checked(1, $options['enable_auto_check'], false) . ' />';
    }, 'category_autocheck', 'lca_main_section');
});