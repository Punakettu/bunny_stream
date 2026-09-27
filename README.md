CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Notices
* Troubleshooting
* Maintainers

# INTRODUCTION

This module integrates the [Bunny.net Stream service](https://bunny.net/stream/)
with the Media module, so that videos hosted in Bunny can be used in Drupal.

The module provides new Media source plugin and some field formatters to embed
the videos or just list the links.

Also support embed private videos of Bunny Stream service.

**WARNING:** This module is not production ready, use it just for test and bug
report please.

# REQUIREMENTS

This module requires Media and BigPipe from Drupal core.

Also, the symfony components Serializer and Property Access on version 6.4
or higher.

Serializer component is required and used by Drupal, but not Property access,
you must require it.

```
$ composer require symfony/property-access
```

# INSTALLATION


* Install as you would normally install a contributed Drupal module. Visit
  https://www.drupal.org/node/1897420 for further information.

The uploader needs the [tus-js-client](https://github.com/tus/tus-js-client)
library. With Composer, we recommend
[Asset Packagist](https://www.drupal.org/docs/develop/using-composer/manage-dependencies#third-party-libraries)
repository.

```
composer require npm-asset/tus-js-client:^4.3
```

Without the local library, the JavaScript is loaded from jsDelivr CDN.

# CONFIGURATION


1. Create one stream library in your [dashboard of bunny.net](https://dash.bunny.net/stream/)

2. Configure the new library Administration > Structure > Bunny Stream Library
   (/admin/structure/bunny_stream_library/add), fill the fields. If the library
   is private, add the "Token Authentication Key" and chose the time to expire
   the security token.

3. Go to Administration > Structure > Media Types and create one new Media type
   selecting "Bunny stream" from the dropdown of "Media source". Select the
   library that was created in step 2.


# NOTICES

@todo add some information here.


# TROUBLESHOOTING

@todo add some information here.


# MAINTAINERS

Current maintainers:

* Borja Vicente - https://www.drupal.org/u/nireneko
