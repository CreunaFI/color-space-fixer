<?php

/*
Plugin name: Color Space Fixer
Description: Convert Adobe RGB / CMYK images to sRGB on upload. Requires ImageMagick built with lcms delegate.
Author: Johannes Siipola
Author URI: https://www.creuna.com/fi/
Version: 1.1.0
License: GPL v3 or later
Text Domain: color-space-fixer
*/

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

    // Color space conversion code based on cimage
    // https://github.com/mosbth/cimage/blob/cd142c58806c8edb6164a12a20e120eb7f436dfb/CImage.php#L2552
    // The MIT License (MIT)
    // Copyright (c) 2012 - 2016 Mikael Roos, https://mikaelroos.se, mos@dbwebb.se

    try {
        $path = $array['file'];
        $image = new Imagick($path);
        $colorspace = $image->getImageColorspace();

        $constants = csf_get_constants();

        csf_debug('Colorspace: ' . $constants[$colorspace]);

        $profiles = $image->getImageProfiles('*', false);
        $has_ICC_profile = (array_search('icc', $profiles) !== false);
        $icc_description = $image->getImageProperty('icc:description');

        csf_debug('icc profile: ' . $icc_description);

        if (!$has_ICC_profile) {
            error_log("Color Space Fixer: No icc profile found, can't convert");
            return $array;
        }

        if (stripos($icc_description, 'srgb') !== false) {
            error_log("Color Space Fixer: Already a sRGB image, no need to convert");
            return $array;
        }

        if ($colorspace !== Imagick::COLORSPACE_SRGB) {
            csf_debug("Color space is not SRGB (eg. it's a CMYK image), converting...");
        }

        if ($colorspace === Imagick::COLORSPACE_SRGB && stripos($icc_description, 'srgb') === false) {
            csf_debug("Color space is sRGB but ICC profile is not (eg. it's a Adobe RGB image), converting");
        }

        $sRGB_icc = file_get_contents(__DIR__ . '/icc/sRGB_v4_ICC_preference.icc');
        $image->profileImage('icc', $sRGB_icc);
        $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
        $image->writeImage($path);

    } catch (Exception $e) {
        error_log('Color Space Fixer: Whoops, failed to convert image color space');
    }

    return $array;
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
    if (!extension_loaded('imagick')) {
        echo '<div class="notice notice-warning"><p>';
        echo __("Color Space Fixer is activated but it's not doing anything because ImageMagick PHP extension has not been loaded.", 'color-space-fixer');
        echo '</p></div>';
    }
    if (extension_loaded('imagick') && !csf_lcms_enabled()) {
        echo '<div class="notice notice-warning"><p>';
        echo __("Color Space Fixer is activated but it's not doing anything because LCMS delegate for ImageMagick has not been installed.", 'color-space-fixer');
        echo '</p></div>';
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

// add the filter
add_filter('wp_handle_upload', 'csf_wp_handle_upload', 10, 2);

add_action('admin_notices', 'csf_admin_notices');
