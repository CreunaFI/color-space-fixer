---
nav_order: 1
title: Color Space Fixer
description: Color Space Fixer will automatically convert images you upload in WordPress media gallery into standard sRGB color space.
---

# Color Space Fixer

[![Packagist Version](https://img.shields.io/packagist/v/creuna-fi/color-space-fixer)](https://packagist.org/packages/creuna-fi/color-space-fixer)

Are your photos strangely faded after uploading? Do your image colors look different in every browser?

This WordPress plugin will automatically convert images you upload in WordPress media gallery into standard sRGB color space. This makes sure your images will look consistant across all browsers and operating systems.

It can handle:

* Images in different color space (for example CMYK)
* Images with different ICC profile (for example Adobe RGB, Display P3 or ProPhoto)

## Requirements

* WordPress 5.0 or later
* PHP 7.0 or later
* [ImageMagick](imagemagick.html), needs to be built with [lcms delegate](lcms.html)
