# www.pillot.fr

## Pre-requisities

- download and install php 7.3
- activate the openssl php extension (for composer) 

## Install locally for development use

```bash
git clone git@github.com:baptistepillot/homepage
cd homepage
php composer.phar update
```

## PhpStorm configuration

Find and apply these settings :

- Code Style : for css, html, json, php, scss files : indent with tabs
- File Watchers : activate the default SCSS file watcher provided with PhpStorm,
  but add this to arguments : `--sourcemap=none` 
