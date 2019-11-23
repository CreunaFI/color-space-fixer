---
nav_order: 3
description: Color Space Fixer requires you to have ImageMagick built with LCMS support
---

# LCMS

Color Space Fixer requires you to have ImageMagick built with LCMS support. LCMS or [Little CMS](http://www.littlecms.com/) is a color management system library which is required to in order to convert files between different color spaces. LCMS is included by default in most ImageMagick builds.

If LCMS delegate has not been installed, you will get an error message: **Color Space Fixer is activated but it's not doing anything because LCMS delegate for ImageMagick has not been installed.**. In this case you might need to [build ImageMagick from source](https://imagemagick.org/script/install-source.php) using `--with-lcms2=yes` flag.

If you do not have root access on your server, please contact your hosting provider for support.
