# Color Space Fixer

Are your photos strangely faded after uploading? Do your image colors look different in every browser?

This WordPress plugin will automatically convert images your upload in WordPress media gallery into standard sRGB color space. This makes sure your images will look consistant across all browsers and operating systems.

It can handle:

* Images in different color space (for example CMYK)
* Images with different ICC profile (for example Adobe RGB, Display P3 or ProPhoto)

## Requirements

* WordPress 5.0 or later
* PHP 7.0 or later
* ImageMagick, needs to be built with lcms delegate (check with `convert -list configure | grep 'lcms'`)
