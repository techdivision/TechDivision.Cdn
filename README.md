
# Introduction

This package provides a CDN (e.g. cloudfront) functionality for TYPO3 Neos 1.2


# Installation

you need to add the package to your `composer.json`

``` bash
{
    "require": {
        "techdivision/cdn": "1.0.*"
    },
}
```

Install the package:

``` bash
composer update techdivision/cdn
```



# Configuration

add this to your settings.yaml

``` php
TechDivision:
  Cdn:
    host: cdn.example.local
    schema-less: true
```

* `host`: set the domain which should be used as cdn domain. Do not add "http://" or "https://" here
* `schema-less`: set to true to use "//" instead of "http://" or "https://"



If you are using custom fonts, you need to set a additional header.

snippet for Apache with .htaccess:

``` bash
<FilesMatch "\.(ttf|otf|eot|woff)$">
  <IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
  </IfModule>
</FilesMatch>
```

snippet for Nginx webserver:

``` bash
location ~* \.(eot|ttf|woff)$ {
    add_header Access-Control-Allow-Origin *;
}
```



# Caution!

This Packages overrides the *mirrorMode*. If you ever want to switch from `symlink` to `copy` you need to change these settings
by adding the following lines to your Project or Site `Settings.yaml`:

``` bash
TechDivision:
  Cdn:
    resource:
      publishing:
        detectPackageResourceChanges: FALSE
        fileSystem:
          mirrorMode: copy
```


# Hint

This Package adds to every resource url an query string parameter. So if you are using cloudfront as cdn you should enable
`Forward Query Strings` in the Behavior settings. If you change a Resource, for instance uploading a new image, the query
string will change and cloudfront fetches the new version of the image, independently of any cache header settings.


