# LCMS support

Color Space Fixer requires you to have ImageMagick built with lcms support. LCMS or Little CMS is a color management system which is required to in order to convert files between different color spaces. LCMS is included by default in most ImageMagick builds but if it's not, you might need to [build ImageMagick from source](https://imagemagick.org/script/install-source.php) and use `-with-lcms2=yes` flag.
