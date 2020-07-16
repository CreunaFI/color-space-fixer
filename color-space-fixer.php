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

$update_checker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/CreunaFI/color-space-fixer',
    __FILE__,
    'color-space-fixer'
);

$update_checker->getVcsApi()->enableReleaseAssets();

function csf_wp_handle_upload($array, $var)
{
    if ($array['type'] !== 'image/jpeg' && $array['type'] !== 'image/png') {
        error_log('Color Space Fixer: Not a JPEG or PNG file, skipping color space fixing');
        return $array;
    }

    if (!extension_loaded('imagick')) {
        error_log('Color Space Fixer: Whoops, imagick is not loaded');
        return $array;
    }

    if (extension_loaded('imagick') && !csf_lcms_enabled()) {
        error_log('Color Space Fixer: Whoops, imagick was not built with lcms support');
        return $array;
    }

    try {
        $path = $array['file'];
        $image = new Imagick($path);

        $result = csf_check_color_space($image);

        if (!$result) {
            return $array;
        }

        csf_fix_color_space($image, $path);

    } catch (Exception $e) {
        error_log('Color Space Fixer: Whoops, failed to convert image color space');
    }

    return $array;
}

/**
 * Convert color space
 * @param Imagick $image
 * @param $path
 */
function csf_fix_color_space(Imagick $image, $path)
{
    // Color space conversion code based on cimage
    // https://github.com/mosbth/cimage/blob/cd142c58806c8edb6164a12a20e120eb7f436dfb/CImage.php#L2552
    // The MIT License (MIT)
    // Copyright (c) 2012 - 2016 Mikael Roos, https://mikaelroos.se, mos@dbwebb.se

    error_log("Color Space Fixer: Converting to sRGB");

    $sRGB_icc = file_get_contents(__DIR__ . '/icc/sRGB2014.icc');
    $image->profileImage('icc', $sRGB_icc);
    $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);

    error_log("Color Space Fixer: Writing image");

    $image->writeImage($path);
}

/**
 * @return array|null
 * @throws ReflectionException
 */
function csf_get_constants() {
    $class = new ReflectionClass('Imagick');
    $constants = $class->getConstants();

    $constants = array_filter($constants, function ($constant) {
        return stripos($constant, 'colorspace') !== false;
    }, ARRAY_FILTER_USE_KEY);

    $constants = array_flip($constants);
    return $constants;
};

function csf_debug($message)
{
    if (defined('WP_DEBUG') && WP_DEBUG === true) {
        error_log(print_r($message, true));
    }
}

function csf_admin_notices() {
    $message = null;
    if (!extension_loaded('imagick')) {
        $message = __("Color Space Fixer is activated but it's not doing anything because ImageMagick PHP extension has not been loaded. <a href='%s' target='_blank'>How to fix</a>.", 'color-space-fixer');
        $message = sprintf($message, 'https://creunafi.github.io/color-space-fixer/imagemagick.html');
    }
    if (extension_loaded('imagick') && !csf_lcms_enabled()) {
        $message = __("Color Space Fixer is activated but it's not doing anything because LCMS delegate for ImageMagick has not been installed. <a href='%s' target='_blank'>How to fix</a>.", 'color-space-fixer');
        $message = sprintf($message, 'https://creunafi.github.io/color-space-fixer/lcms.html');
    }
    if ($message) {
        echo "<div class='notice notice-warning'><p>$message</p></div>";
    }
}

function csf_lcms_enabled() {
    $imagick = new Imagick();
    $options = $imagick->getConfigureOptions();
    if (!empty($options['DELEGATES']) && stripos($options['DELEGATES'], 'lcms') !== false) {
        return true;
    }
    return false;
}

function csf_get_colorspace_name($colorspace) {
    $constants = csf_get_constants();
    return $constants[$colorspace];
}

function csf_ajax_scan_images()
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

function csf_ajax_get_image() {

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

    $fix = csf_check_color_space($image);

    $new_post = [];
    $new_post['id'] = $post->ID;
    $new_post['title'] = get_the_title($post);
    $new_post['thumbnail'] = null;
    $new_post['link'] = get_edit_post_link($post->ID, false);
    $thumbnail = wp_get_attachment_image_src($post->ID, 'medium');
    if ($thumbnail) {
        $new_post['thumbnail'] = $thumbnail[0];
    }

    wp_send_json([
        'success' => true,
        'fix' => $fix,
        'post' => $new_post
    ]);
}

add_filter('wp_handle_upload', 'csf_wp_handle_upload', 10, 2);

add_action('admin_notices', 'csf_admin_notices');

add_action('wp_ajax_csf_scan_images', 'csf_ajax_scan_images');
add_action('wp_ajax_csf_get_image', 'csf_ajax_get_image');

/**
 * Check if color space should be converted, return true if should be converted, false if not
 * @param $image
 * @return bool
 */
function csf_check_color_space($image) {
    $colorspace = $image->getImageColorspace();
    csf_debug('Colorspace: ' . csf_get_colorspace_name($colorspace));

    $profiles = $image->getImageProfiles('*', false);
    $has_ICC_profile = (array_search('icc', $profiles) !== false);
    $icc_description = $image->getImageProperty('icc:description');

    csf_debug('icc profile: ' . $icc_description);

    if (!$has_ICC_profile) {
        error_log("Color Space Fixer: No icc profile found, can't convert");
        return false;
    }

    // c2 is the Facebook's tiny sRGB profile https://pippin.gimp.org/sRGBz/ , used by Facebook and some CDNs too
    // eg. Fastly
    if (stripos($icc_description, 'srgb') !== false || $icc_description === 'c2') {
        error_log("Color Space Fixer: Already a sRGB image, no need to convert.");
        return false;
    }

    if ($colorspace !== Imagick::COLORSPACE_SRGB) {
        csf_debug("Color space is not SRGB (eg. it's a CMYK image). Should be converted.");
        return true;
    }

    if ($colorspace === Imagick::COLORSPACE_SRGB && stripos($icc_description, 'srgb') === false) {
        csf_debug("Color space is sRGB but ICC profile is not (eg. it's a Adobe RGB image). Should be converted.");
        return true;
    }
    return false;
}

function csf_admin_enqueue_scripts()
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
        'batch_process_description' => __('This tool will scan your WordPress media library for images not in sRGB color space.', 'csf'),
        'save' => __('Save', 'csf'),
        'options_saved' => __('Options have been saved', 'csf'),
        'scanning_in_progress' => __('Scanning media library...', 'csf'),
        'scan_progress' => __('%d%% complete. Scanned %d image of %d', 'csf'),
        'scan_progress_plural' => __('%d%% complete. Scanned %d images of %d', 'csf'),
        'scan_complete' => __('Scan complete', 'csf'),
        'scan_results' => __('Found %d image that need fixing.', 'csf'),
        'scan_results_plural' => __('Found %d images that need fixing.', 'csf'),
        'fix_images' => __('Fix images', 'csf'),
    ];
    wp_localize_script('csf-script', 'csf_translations', $strings);
}

add_filter('admin_enqueue_scripts', 'csf_admin_enqueue_scripts');

function csf_admin_menu() {
    add_options_page(
        __( 'Color Space Fixer', 'csf' ),
        __( 'Color Space Fixer', 'csf' ),
        'manage_options',
        'color-space-fixer',
        'csf_render_menu'
    );
}

add_action( 'admin_menu', 'csf_admin_menu' );

function csf_render_menu() {
    echo '<div id="color-space-fixer"><color-space-fixer></color-space-fixer></div>';
}