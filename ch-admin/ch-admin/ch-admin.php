<?php
/**
 * Plugin Name: CH Admin
 * Description: Community Health admin tools. Lets you upload a custom login logo, toggle the admin toolbar, and add Google Analytics.
 * Version: 3.2
 * Author: Tyler Couty
 */

// Add settings page
function ch_admin_settings_page() {
    add_options_page(
        'CH Admin',
        'CH Admin',
        'manage_options',
        'ch-admin',
        'ch_admin_render_settings'
    );
}
add_action('admin_menu', 'ch_admin_settings_page');

// Register settings
function ch_admin_register_settings() {
    register_setting('ch_admin_settings_group', 'ch_admin_login_logo');
    register_setting('ch_admin_settings_group', 'ch_admin_toolbar_enabled');
    register_setting('ch_admin_settings_group', 'ch_admin_ga_id'); // NEW
}
add_action('admin_init', 'ch_admin_register_settings');

// Enqueue media uploader
function ch_admin_enqueue($hook) {
    if ($hook !== 'settings_page_ch-admin') {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script('ch-admin-js', plugin_dir_url(__FILE__) . 'ch-admin.js', ['jquery'], '1.0', true);
}
add_action('admin_enqueue_scripts', 'ch_admin_enqueue');

// Render settings page
function ch_admin_render_settings() {
    $logo = get_option('ch_admin_login_logo');
    $toolbar_enabled = get_option('ch_admin_toolbar_enabled', '1');
    $ga_id = get_option('ch_admin_ga_id', '');
    ?>
    <div class="wrap">
        <h1>CH Admin</h1>
        <form method="post" action="options.php">
            <?php settings_fields('ch_admin_settings_group'); ?>
            <?php do_settings_sections('ch_admin_settings_group'); ?>

            <table class="form-table">
                <!-- Login Logo -->
                <tr valign="top">
                    <th scope="row">Login Logo</th>
                    <td>
                        <input type="hidden" id="ch_admin_login_logo" name="ch_admin_login_logo" value="<?php echo esc_url($logo); ?>" />
                        <button type="button" class="button" id="ch_admin_upload_logo_button">Upload / Select Logo</button>
                        <div style="margin-top:30px; border: 1px dashed #ccc; border-radius: 10px; width: fit-content; padding: 20px;" id="ch_admin_logo_preview">
                            <?php if ($logo): ?>
                                <img src="<?php echo esc_url($logo); ?>" style="max-height:50px;" />
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>

                <!-- Admin Toolbar Toggle -->
                <tr valign="top">
                    <th scope="row">Admin Toolbar</th>
                    <td>
                        <label class="ch-toggle">
                            <input type="checkbox" name="ch_admin_toolbar_enabled" value="1" <?php checked($toolbar_enabled, '1'); ?> />
                            <span class="ch-toggle-slider"></span>
                        </label>
                        <p class="description">Toggle the WordPress admin toolbar on or off for all users.</p>
                        <style>
                            .ch-toggle {
                                position: relative;
                                display: inline-block;
                                width: 50px;
                                height: 26px;
                            }
                            .ch-toggle input { display:none; }
                            .ch-toggle-slider {
                                position: absolute;
                                cursor: pointer;
                                top: 0; left: 0;
                                right: 0; bottom: 0;
                                background-color: #ccc;
                                transition: .4s;
                                border-radius: 34px;
                            }
                            .ch-toggle-slider:before {
                                position: absolute;
                                content: "";
                                height: 20px;
                                width: 20px;
                                left: 3px;
                                bottom: 3px;
                                background-color: white;
                                transition: .4s;
                                border-radius: 50%;
                            }
                            input:checked + .ch-toggle-slider {
                                background-color: #007cba;
                            }
                            input:checked + .ch-toggle-slider:before {
                                transform: translateX(24px);
                            }
                        </style>
                    </td>
                </tr>

                <!-- Google Analytics -->
                <tr valign="top">
                    <th scope="row">Google Analytics</th>
                    <td>
                        <input type="text" name="ch_admin_ga_id" value="<?php echo esc_attr($ga_id); ?>" placeholder="G-XXXXXXXXXX or UA-XXXXXX-X" style="width:300px;" />
                        <p class="description">Enter your Google Analytics Measurement ID (GA4 or Universal). Leave blank to disable tracking.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Apply logo to login page
function ch_admin_custom_login_logo() {
    $logo = get_option('ch_admin_login_logo');
    if ($logo) {
        echo '<style>
            #login h1 a {
                background-image:url(' . esc_url($logo) . ') !important;
                background-size: contain !important;
                width:200px !important;
                height:50px !important;
            }
        </style>';
    }
}
add_action('login_enqueue_scripts', 'ch_admin_custom_login_logo');

// Change login logo link
function ch_admin_login_logo_url() {
    return 'https://communityhealthmediagroup.com';
}
add_filter('login_headerurl', 'ch_admin_login_logo_url');

// Change title on hover
function ch_admin_login_logo_title() {
    return 'Community Health Media Group';
}
add_filter('login_headertext', 'ch_admin_login_logo_title');

// Hide admin bar if disabled
function ch_admin_toggle_toolbar() {
    $toolbar_enabled = get_option('ch_admin_toolbar_enabled', '1');
    if ($toolbar_enabled !== '1') {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'ch_admin_toggle_toolbar');

// Add Google Analytics (front end only)
function ch_admin_add_google_analytics() {
    if (is_admin() || is_user_logged_in()) {
        return; // Don't load GA in admin or for logged-in users
    }

    $ga_id = get_option('ch_admin_ga_id');
    if (empty($ga_id)) {
        return;
    }

    echo "
    <!-- Google Analytics -->
    <script async src='https://www.googletagmanager.com/gtag/js?id=" . esc_js($ga_id) . "'></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '" . esc_js($ga_id) . "');
    </script>
    <!-- /Google Analytics -->
    ";
}
add_action('wp_head', 'ch_admin_add_google_analytics');


// ----------------------
// CH Admin Self-Updater
// ----------------------
add_action('init', function() {
    if (is_admin()) {
        new CH_Admin_Update_Checker(__FILE__, 'https://tcouty.github.io/wp-updates/ch-admin/ch-admin.json');
    }
});

class CH_Admin_Update_Checker {
    private $plugin_file;
    private $metadata_url;

    public function __construct($plugin_file, $metadata_url) {
        $this->plugin_file = plugin_basename($plugin_file);
        $this->metadata_url = $metadata_url;

        add_filter('site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
    }

    public function check_for_update($transient) {
        if (empty($transient->checked)) return $transient;

        $remote = wp_remote_get($this->metadata_url, ['timeout' => 10]);
        if (is_wp_error($remote) || wp_remote_retrieve_response_code($remote) !== 200) return $transient;

        $data = json_decode(wp_remote_retrieve_body($remote));
        if (!$data) return $transient;

        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $this->plugin_file);
        $local_version = $plugin_data['Version'];

        if (version_compare($data->version, $local_version, '>')) {
            $transient->response[$this->plugin_file] = (object)[
                'slug'        => $data->slug,
                'plugin'      => $this->plugin_file,
                'new_version' => $data->version,
                'package'     => $data->download_url,
                'url'         => 'https://tcouty.github.io/wp-updates',
                'tested'      => $data->tested,
                'requires'    => $data->requires,
                'icons'       => []
            ];
        }
        return $transient;
    }

    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== 'ch-admin') return $result;

        $remote = wp_remote_get($this->metadata_url, ['timeout' => 10]);
        if (is_wp_error($remote) || wp_remote_retrieve_response_code($remote) !== 200) return $result;

        $data = json_decode(wp_remote_retrieve_body($remote));
        if (!$data) return $result;

        return (object)[
            'name'         => $data->name,
            'slug'         => $data->slug,
            'version'      => $data->version,
            'author'       => $data->author,
            'download_link'=> $data->download_url,
            'sections'     => (array)$data->sections
        ];
    }
}
