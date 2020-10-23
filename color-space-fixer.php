<?php

/*
Plugin name: Color Space Fixer
Description: Convert Adobe RGB / CMYK images to sRGB on upload. Requires ImageMagick built with lcms delegate.
Author: Johannes Siipola
Author URI: https://www.creuna.com/fi/
Version: 1.3.0
License: GPL v3 or later
Text Domain: csf
*/

//autoload dependencies
$vendor_dir = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendor_dir)) {
    require($vendor_dir);
}

class ColorSpaceFixer {
    /**
     * @var array
     */
    private $options;

    public function __construct()
    {
        $this->set_up_updater();

        $default_options = [
            'process_on_upload' => true,
            'show_media_column' => true,
        ];

        $db_options = get_option('csf_options') ?: [];

        $this->options = array_merge($default_options, $db_options);

        add_filter('wp_generate_attachment_metadata', [$this, 'wp_handle_upload'], 5, 2);
        add_action('admin_notices', [$this, 'admin_notices']);
        add_action('wp_ajax_csf_scan_images', [$this, 'ajax_scan_images']);
        add_action('wp_ajax_csf_get_image', [$this, 'ajax_get_image']);
        add_action('wp_ajax_csf_save_options', [$this, 'ajax_save_options']);
        add_action('wp_ajax_csf_get_options', [$this, 'ajax_get_options']);
        add_filter('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        add_action('admin_menu', [$this, 'csf_admin_menu']);
        add_filter('manage_media_columns', [$this, 'add_media_columns']);
        add_action(
            'manage_media_custom_column',
            [$this, 'fill_media_columns'],
            10,
            2
        );
        add_filter('attachment_fields_to_edit', [$this, 'media_fields'], 10, 2);

    }

    public function set_up_updater() {
        $update_checker = Puc_v4_Factory::buildUpdateChecker(
            'https://github.com/CreunaFI/color-space-fixer',
            __FILE__,
            'color-space-fixer'
        );

        $update_checker->getVcsApi()->enableReleaseAssets();
    }


    /**
     * Convert color space
     * @param Imagick $image
     * @param $path
     */
    public function fix_color_space(Imagick $image, $path)
    {
        // Color space conversion code based on cimage
        // https://github.com/mosbth/cimage/blob/cd142c58806c8edb6164a12a20e120eb7f436dfb/CImage.php#L2552
        // The MIT License (MIT)
        // Copyright (c) 2012 - 2016 Mikael Roos, https://mikaelroos.se, mos@dbwebb.se

        error_log("Color Space Fixer: Converting to sRGB");

        $sRGB_icc = file_get_contents(__DIR__ . '/icc/sRGB2014.icc');
        $image->profileImage('icc', $sRGB_icc);
        $image->transformImageColorspace(Imagick::COLORSPACE_RGB);

        error_log("Color Space Fixer: Writing image");

        $image->writeImage($path);
    }

    public function wp_handle_upload($metadata, $attachment_id)
    {
        if ($this->options['process_on_upload'] === false) {
            self::debug('Color Space Fixer: Step 2. Process on upload turned off, skipping color space fixing');
            return $metadata;
        }

        if (get_post_mime_type($attachment_id) !== 'image/jpeg' && get_post_mime_type($attachment_id) !== 'image/png') {
            error_log('Color Space Fixer: Step 2. Not a JPEG or PNG file, skipping color space fixing');
            return $metadata;
        }

        if (!extension_loaded('imagick')) {
            error_log('Color Space Fixer: Step 2. Whoops, imagick is not loaded');
            return $metadata;
        }

        if (extension_loaded('imagick') && !$this->lcms_enabled()) {
            error_log('Color Space Fixer: Step 2. Whoops, imagick was not built with lcms support');
            return $metadata;
        }

        try {
            $path = get_attached_file($attachment_id);

            if (function_exists('wp_get_original_image_path')) {
                $original = wp_get_original_image_path($attachment_id);
                if ($original) {
                    $path = $original;
                }
            }

            $image = new Imagick($path);

            $result = $this->check_color_space($image);

            $meta = [
                'converted' => false,
                'original_colorspace' => $result['colorspace'],
                'original_icc' => $result['icc'],
                'converted_colorspace' => null,
                'converted_icc' => null,
            ];

            update_post_meta($attachment_id, 'csf_data', $meta);

            if (!$result['convert']) {
                return $metadata;
            }

            $this->fix_color_space($image, $path);

            //avoid loop
            remove_filter(
                'wp_generate_attachment_metadata',
                [$this, 'wp_handle_upload'],
                5
            );

            $meta['converted'] = true;
            $meta['converted_colorspace'] = 'COLORSPACE_SRGB';
            $meta['converted_icc'] = 'sRGB2014';

            update_post_meta($attachment_id, 'csf_data', $meta);

            // Regenerate the thumbnails
            wp_generate_attachment_metadata($attachment_id, $path);

        } catch (Exception $e) {
            error_log('Color Space Fixer: Step 2. Whoops, failed to convert image color space');
        }

        return $metadata;
    }

    /**
     * @return array|null
     * @throws ReflectionException
     */
    public function get_constants() {
        $class = new ReflectionClass('Imagick');
        $constants = $class->getConstants();

        $constants = array_filter($constants, function ($constant) {
            return stripos($constant, 'colorspace') !== false;
        }, ARRAY_FILTER_USE_KEY);

        $constants = array_flip($constants);
        return $constants;
    }

    public static function debug($message)
    {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log(print_r($message, true));
        }
    }

    function admin_notices() {
        $message = null;
        if (!extension_loaded('imagick')) {
            $message = __("Color Space Fixer is activated but it's not doing anything because ImageMagick PHP extension has not been loaded. <a href='%s' target='_blank'>How to fix</a>.", 'color-space-fixer');
            $message = sprintf($message, 'https://creunafi.github.io/color-space-fixer/imagemagick.html');
        }
        if (extension_loaded('imagick') && !$this->lcms_enabled()) {
            $message = __("Color Space Fixer is activated but it's not doing anything because LCMS delegate for ImageMagick has not been installed. <a href='%s' target='_blank'>How to fix</a>.", 'color-space-fixer');
            $message = sprintf($message, 'https://creunafi.github.io/color-space-fixer/lcms.html');
        }
        if ($message) {
            echo "<div class='notice notice-warning'><p>$message</p></div>";
        }
    }

    function lcms_enabled() {
        $imagick = new Imagick();
        $options = $imagick->getConfigureOptions();
        if (!empty($options['DELEGATES']) && stripos($options['DELEGATES'], 'lcms') !== false) {
            return true;
        }
        return false;
    }


    function get_colorspace_name($colorspace) {
        $constants = $this->get_constants();
        return $constants[$colorspace];
    }

    function ajax_scan_images()
    {
        $query = new WP_Query([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'fields' => 'ids',
            'post_mime_type' => ['image/jpeg', 'image/png'],
            'posts_per_page' => -1,
        ]);

        $json = [
            'posts' => $query->posts,
            'total' => $query->post_count,
        ];

        wp_send_json($json);
    }

    function ajax_get_options() {
        wp_send_json($this->options);
    }

    function ajax_save_options() {
        $options = $_POST['csf_options'];

        // 10 years and counting. Don't hold your breath. https://core.trac.wordpress.org/ticket/18322
        $options = stripslashes_deep($options);

        $options = json_decode($options, true);

        // TODO: maybe validate data somehow?
        update_option('csf_options', $options);
    }

    function ajax_get_image() {

        $post_id = intval($_POST['csf_post_id']);

        $post = get_post($post_id);

        if (!$post) {
            wp_send_json([
                'success' => false,
                'post' => null,
            ]);
        }

        $path = get_attached_file($post_id);

        if (function_exists('wp_get_original_image_path')) {
            $original = wp_get_original_image_path($post_id);
            if ($original) {
                $path = $original;
            }
        }

        $image = new Imagick($path);

        $color_space_data = $this->check_color_space($image);

        $new_post = [];
        $new_post['id'] = $post->ID;
        $new_post['title'] = get_the_title($post);
        $new_post['thumbnail'] = null;
        $new_post['link'] = get_edit_post_link($post->ID, false);
        $new_post['icc'] = $color_space_data['icc'];
        $new_post['colorspace'] = $color_space_data['colorspace'];
        $thumbnail = wp_get_attachment_image_src($post->ID, 'medium');
        if ($thumbnail) {
            $new_post['thumbnail'] = $thumbnail[0];
        }

        wp_send_json([
            'success' => true,
            'fix' => $color_space_data['convert'],
            'post' => $new_post
        ]);
    }

    function admin_enqueue_scripts()
    {
        $plugin_data = get_plugin_data(__FILE__);
        $version = $plugin_data['Version'];
        $url = plugin_dir_url(__FILE__);
        $path = plugin_dir_path(__FILE__);

        wp_enqueue_script(
            'csf-script',
            "{$url}dist/script.js",
            [],
            WP_DEBUG ? md5_file($path . 'dist/script.js') : $version
        );

        wp_enqueue_style(
            'csf-style',
            "{$url}dist/style.css",
            [],
            WP_DEBUG ? md5_file($path . 'dist/style.css') : $version
        );

        $strings = [
            'plugin_name' => __('Color Space Fixer', 'csf'),
            'options' => __('Options', 'csf'),
            'batch_process_images' => __('Batch process images', 'csf'),
            'scan_for_images' => __('Scan for images', 'csf'),
            'batch_process_description' => __('This tool will scan your WordPress media library for images not in sRGB color space and let you convert them to sRGB.', 'csf'),
            'save' => __('Save', 'csf'),
            'options_saved' => __('Options have been saved', 'csf'),
            'scanning_in_progress' => __('Scanning media library...', 'csf'),
            'scan_progress_short' => __('%d%% complete.', 'csf'),
            'scan_progress' => __('%d%% complete. Scanned %d image of %d', 'csf'),
            'scan_progress_plural' => __('%d%% complete. Scanned %d images of %d', 'csf'),
            'scan_complete' => __('Scan complete', 'csf'),
            'scan_results' => __('Scanned %d images and found %d image that need fixing.', 'csf'),
            'scan_results_plural' => __('Scanned %d images and found %d images that need fixing.', 'csf'),
            'fix_images' => __('Fix images', 'csf'),
            'generic_error' => __('Error performing action', 'csf'),
            'ok' => __('OK', 'csf'),
            'image_list' => __('List of images', 'csf'),
        ];
        wp_localize_script('csf-script', 'csf_translations', $strings);
    }
    public function render_menu() {
        echo '<div id="color-space-fixer"><color-space-fixer></color-space-fixer></div>';
    }


    /**
     * Check if color space should be converted
     * @param $image
     * @return array
     */
    function check_color_space($image) {
        $colorspace = $image->getImageColorspace();
        $colorspace_name = $this->get_colorspace_name($colorspace);
        self::debug('Color Space Fixer: Colorspace: ' . $colorspace_name);

        $profiles = $image->getImageProfiles('*', false);
        $has_ICC_profile = (array_search('icc', $profiles) !== false);
        $icc_description = $image->getImageProperty('icc:description');

        self::debug('Color Space Fixer: ICC profile: ' . $icc_description);

        if (!$has_ICC_profile) {
            error_log("Color Space Fixer: No icc profile found, can't convert");
            return [
                'convert' => false,
                'colorspace' => $colorspace_name,
                'icc' => $icc_description ?: null,
            ];
        }

        // c2 is the Facebook's tiny sRGB profile https://pippin.gimp.org/sRGBz/ , used by Facebook
        // and some CDNs too eg. Fastly. uRGB and nRGB are these compact profiles:
        // https://github.com/saucecontrol/Compact-ICC-Profiles . Since all of these are pretty
        // much the same as sRGB, there's no need to convert them which would cause generation loss
        if (stripos($icc_description, 'srgb') !== false ||
            $icc_description === 'c2' ||
            $icc_description === 'uRGB' ||
            $icc_description === 'nRGB'
        ) {
            error_log("Color Space Fixer: Already a sRGB image, no need to convert.");
            return [
                'convert' => false,
                'colorspace' => $colorspace_name,
                'icc' => $icc_description,
            ];
        }

        if ($colorspace !== Imagick::COLORSPACE_SRGB) {
            $this->debug("Color Space Fixer: Color space is not SRGB (eg. it's a CMYK image). Should be converted.");
            return [
                'convert' => true,
                'colorspace' => $colorspace_name,
                'icc' => $icc_description,
            ];
        }

        if ($colorspace === Imagick::COLORSPACE_SRGB && stripos($icc_description, 'srgb') === false) {
            self::debug("Color Space Fixer: Color space is sRGB but ICC profile is not (eg. it's a Adobe RGB image). Should be converted.");
            return [
                'convert' => true,
                'colorspace' => $colorspace_name,
                'icc' => $icc_description,
            ];
        }
        return [
            'convert' => false,
            'colorspace' => $colorspace_name,
            'icc' => $icc_description,
        ];
    }

    function csf_admin_menu() {
        add_options_page(
            __( 'Color Space Fixer', 'csf' ),
            __( 'Color Space Fixer', 'csf' ),
            'manage_options',
            'color-space-fixer',
            [$this, 'render_menu']
        );
    }

    public function add_media_columns($columns)
    {
        if ($this->options['show_media_column']) {
            $columns['csf'] = 'Color Space';
        }
        return $columns;
    }

    function fill_media_columns($column_name, $id)
    {
        if (strcmp($column_name, 'csf') === 0) {
            echo $this->get_info_description($id);
        }
    }

    private function simplify_colorspace($colorspace)
    {
        if ($colorspace === 'COLORSPACE_CMYK') {
            return 'CMYK';
        }
        if ($colorspace === 'COLORSPACE_RGB' || $colorspace === 'COLORSPACE_SRGB') {
            return 'RGB';
        }
        return $colorspace;
    }

    private function format_icc($icc)
    {
        if (empty($icc)) {
            return __('Unknown ICC profile');
        }
        return $icc;
    }

    public function media_fields($form_fields, $post) {
        $post_id = $post->ID;
        $html = '<div>' . $this->get_info_description($post_id) . '</div>';

        $form_fields['csf'] = [
            'label' => __('Color Space', 'csf'),
            'input' => 'html',
            'html' => $html,
        ];
        return $form_fields;
    }

    /**
     * @param $id
     * @return string|void
     */
    public function get_info_description($id)
    {
        $csf_meta = get_post_meta($id, 'csf_data', true);
        if (!$csf_meta) {
            return __('No color space information', 'csf');
        }

        if ($csf_meta && $csf_meta['converted']) {
            return sprintf(
                __('Converted from <strong>%s (%s)</strong> to <strong>%s (%s)</strong>'),
                $this->format_icc($csf_meta['original_icc']),
                $this->simplify_colorspace($csf_meta['original_colorspace']),
                $this->format_icc($csf_meta['converted_icc']),
                $this->simplify_colorspace($csf_meta['converted_colorspace'])
            );
        }
        if ($csf_meta && !$csf_meta['converted']) {
            return sprintf(
                __('%s (%s)'),
                $this->format_icc($csf_meta['original_icc']),
                $this->simplify_colorspace($csf_meta['original_colorspace'])
            );
        }
    }


}

$color_space_fixer = new ColorSpaceFixer();