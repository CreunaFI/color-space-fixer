<?php

/*
Plugin name: Color Space Fixer
Description: Convert Adobe RGB / CMYK images to sRGB on upload. Requires ImageMagick built with lcms delegate.
Author: Johannes Siipola
Author URI: https://www.creuna.com/fi/
Version: 1.3.0
License: GPL v3 or later
Text Domain: color-space-fixer
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

add_filter('wp_handle_upload', 'csf_wp_handle_upload', 10, 2);

add_action('admin_notices', 'csf_admin_notices');

add_action('wp_ajax_csf_get_images', 'csf_ajax_get_images');

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