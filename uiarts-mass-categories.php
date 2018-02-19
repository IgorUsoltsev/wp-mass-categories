<?php
/*
Plugin Name: Mass Categories Actions
Plugin URI: https://www.ui-arts.com
Description: Enables mass add/remove category to post
Version: 1.0.0
Author: Igor Usoltsev
Author URI: https://www.ui-arts.com
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: uiarts-mass-categories
Domain Path: /langs

Copyright (c) 2017 Igor Usoltsev. All rights reserved.
*/

if (!class_exists('UiartsMassCategories')) {

    class UiartsMassCategories
    {
        public function __construct()
        {
            add_action('init', array(&$this, 'uiarts_mass_categories_init'));
            add_action('init', array(&$this, 'set_session'));
            add_action('admin_footer-edit.php', array(&$this, 'custom_bulk_footer'));
            add_action('load-edit.php', array(&$this, 'custom_bulk_action'));
            add_action('wp_ajax_select_categories', array(&$this, 'select_categories_callback'));

        }

        function uiarts_mass_categories_init()
        {
            load_plugin_textdomain('uiarts-mass-categories', false, 'uiarts-mass-categories/langs');
        }

        function custom_bulk_footer()
        {
            global $post_type;
            if ($post_type == 'post') {
                ?>
                <form action="" method="post">
                    <select multiple id="category-select" style="display: none">
                        <option
                            value="<?php wp_dropdown_categories('hide_empty=0&orderby=ID'); ?>"><?php wp_dropdown_categories('hide_empty=0&orderby=ID'
                            ); ?>
                        </option>
                    </select>
                </form>

                <script>
                    jQuery(document).ready(function () {
                        jQuery('<option>').val('add').text('<?php _e('Add to Categories', 'uiarts-mass-categories')?>').appendTo("select[name='action']");
                        jQuery('<option>').val('move').text('<?php _e('Move to Categories', 'uiarts-mass-categories')?>').appendTo("select[name='action']");
                        jQuery('<option>').val('remove').text('<?php _e('Remove from Categories', 'uiarts-mass-categories')?>').appendTo("select[name='action']");
                        jQuery("#category-select").insertAfter("#bulk-action-selector-top");
                        jQuery('#bulk-action-selector-top').change(function () {
                            bulk_val = jQuery('#bulk-action-selector-top option:selected').val();
                            if (bulk_val == "add" || bulk_val == "move" || bulk_val == "remove") {
                                jQuery("#category-select").show();
                            } else {
                                jQuery("#category-select").hide();
                            }
                        });
                        jQuery("#doaction").click(function (e) {
                            bulk_val = jQuery('#bulk-action-selector-top option:selected').val();
                            if (bulk_val == "add" || bulk_val == "move" || bulk_val == "remove") {
                                var categories = [];
                                jQuery.each(jQuery("#category-select option:selected"), function () {
                                    categories.push(jQuery(this).text());
                                });
                                var data = {
                                    action: 'select_categories',
                                    categories: categories
                                };
                                jQuery.post(ajaxurl, data, function (response) {
                                });
                            }
                        });
                    });
                </script>
                <?php
            }
        }

        function custom_bulk_action()
        {
            global $typenow;
            $post_type = $typenow;

            $wp_list_table = _get_list_table('WP_Posts_List_Table');
            $action = $wp_list_table->current_action();

            $pagenum = $wp_list_table->get_pagenum();
            $sendback = add_query_arg('paged', $pagenum, $sendback);

            if ($post_type == 'post') {

                if (isset($_REQUEST['post'])) {
                    $post_ids = array_map('intval', $_REQUEST['post']);
                }
                if (empty($post_ids)) return;

                $sendback = add_query_arg(array('ids' => join(',', $post_ids)), $sendback);

                wp_redirect($sendback);
                $cat_ids = $_SESSION['cat_ids'];

                if ($action == 'move') {
                    foreach ($post_ids as $post_id) {
                        wp_set_post_categories($post_id, $cat_ids);
                    }
                } elseif ($action == 'add') {
                    foreach ($post_ids as $post_id) {
                        $cat_ids_new = array_merge($cat_ids, wp_get_post_categories($post_id));
                        wp_set_post_categories($post_id, $cat_ids_new);
                    }
                } elseif ($action == 'remove') {
                    foreach ($post_ids as $post_id) {
                        $cat_ids_new = array_diff(wp_get_post_categories($post_id), $cat_ids);
                        wp_set_post_categories($post_id, $cat_ids_new);
                    }
                }
                exit();
            }
        }

        function select_categories_callback()
        {
            $categories = $_POST['categories'];
            foreach ($categories as $category) {
                $cat_id = get_cat_ID($category);
                $cat_ids[] = $cat_id;
            }
            if (!session_id()) {
                session_start();
            }
            $_SESSION['cat_ids'] = $cat_ids;
            wp_die();
        }

        function set_session()
        {
            if (!session_id()) {
                session_start();
            }
        }
    }
}

new UiartsMassCategories();