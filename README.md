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
* [ImageMagick](https://creunafi.github.io/color-space-fixer/imagemagick.html), needs to be built with [lcms delegate](https://creunafi.github.io/color-space-fixer/lcms.html)

## Installation

1. Download latest version from the [GitHub releases tab](https://github.com/CreunaFI/color-space-fixer/releases)
2. Unzip the plugin into your `wp-content/plugins` directory or upload it through the WordPress admin panel
3. Activate Color Space Fixer from your Plugins page

## Documentation

[Click here to read the documentation](https://creunafi.github.io/color-space-fixer)

## Frequently Asked Questions

### I have existing photos on my site, can I convert them to sRGB?

No. The color space conversion on done on upload. Possibility to batch convert existing images will be added in future.

### I use Photoshop's save for web when exporting images, do I need this plugin?

That's great! You probably don't need this plugin. However, in many cases there are more than one people who administer a WordPress site. Instead of teaching all of them about color spaces and exporting, it might be easier to automate the process using a plugin
