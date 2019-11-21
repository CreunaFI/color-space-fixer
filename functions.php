<?php

/*
Plugin name: Color Space Fixer
Description: Convert Adobe RGB / CMYK images to sRGB on upload. Requires ImageMagick built with lcms2 delegate.
Author: Johannes Siipola
Author URI: https://www.creuna.com/fi/
Version: 1.0.0
Requires at least: 5.0
Requires PHP: 7.0
License: GPL v2 or later
*/

function csf_wp_handle_upload($array, $var)
{
    if ($array['type'] !== 'image/jpeg' && $array['type'] !== 'image/png') {
        error_log('Not a JPEG or PNG file, skipping color space fixing');
        return $array;
    }

    if (!extension_loaded('imagick')) {
        error_log('Whoops, imagick is not loaded');
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
        //$this->log(" Current colorspace: " . $colorspace);
        $profiles = $image->getImageProfiles('*', false);
        $has_ICC_profile = (array_search('icc', $profiles) !== false);
        //$this->log(" Has ICC color profile: " . ($has_ICC_profile ? "YES" : "NO"));
        if ($colorspace === Imagick::COLORSPACE_SRGB || !$has_ICC_profile) {
            error_log('No profile or image is already sRGB');
        } else {
            //$this->log(" Converting to sRGB.");
            $sRGB_icc = file_get_contents(__DIR__ . '/icc/sRGB_v4_ICC_preference.icc');
            $image->profileImage('icc', $sRGB_icc);
            $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
            $image->writeImage($path);
        }
    } catch (Exception $e) {
        error_log('Whoops, failed to convert image color space');
    }

    return $array;
};

// add the filter
add_filter('wp_handle_upload', 'csf_wp_handle_upload', 10, 2);