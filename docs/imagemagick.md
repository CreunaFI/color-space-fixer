---
nav_order: 2
---

# ImageMagick

WordPress has support for two image processing libraries, first of which is [GD](https://en.wikipedia.org/wiki/GD_Graphics_Library), short for Graphics Draw. It is pretty basic but is included in most PHP distributions. The other one is [ImageMagick](https://en.wikipedia.org/wiki/ImageMagick), sometimes referred to as Imagick. It is more advanced and supports more WordPress features, such as [PDF previews](https://make.wordpress.org/core/2016/11/15/enhanced-pdf-support-4-7/). However, it's not always installed in every Linux system.

Because GD does not support color space conversions, you will need ImageMagick installed on your system. If ImageMagick is missing, you will get the following error: **Color Space Fixer is activated but it's not doing anything because ImageMagick PHP extension has not been loaded.**

This means you will need to install ImageMagick and the [Imagick PHP extension](https://www.php.net/manual/en/imagick.installation.php).

## Installing ImageMagick on Ubuntu 18.04

You can install ImageMagick on Ubuntu 18.04 with the command `sudo apt install imagemagick`. After the installation is complete, install the PHP extension with command `sudo apt install php-imagick`. After the installation is complete, you will need to restart your web server with the command sudo `service apache2 restart` or `sudo service php7.2-fpm restart`

## Installing ImageMagick on shared hosting

If you are using shared hosting, please contact your hosting provider for support.
