=== Color Space Fixer ===
Contributors: joppuyo
Tags: image, color space, color profile, icc, adobe rgb, srgb, prophoto, display p3
Requires at least: 5.0
Tested up to: 5.3
Requires PHP: 7.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Convert Adobe RGB / CMYK images to sRGB on upload. Requires ImageMagick built with lcms delegate.

== Changelog ==

= 2.0.0 =
* Renamed plugin main file name, please re-activate plugin after updating

= 1.3.0 =
* Feature: Support for Facebook's tiny sRGB profile

= 1.2.3 =
* Fix: Bump version

= 1.2.2 =
* Fix: Archive structure

= 1.2.1 =
* Fix: Improve error messages

= 1.2.0 =
* Feature: Add plugin update checker
* Fix: Use sRGBv2 profile instead of sRGBv4 because Firefox doesn't support sRGBv4

= 1.1.0 =
* Fix: Skip processing if uploaded file is not an image
* Feature: Process image that are sRGB but have ICC profile. For example Adobe RGB, ProPhoto or Display P3 images.

= 1.0.0 =
* Initial release