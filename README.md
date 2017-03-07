BC Change Subtree Language
===================

This extension provides a basic but flexible solution for quick and simple changing of a subtree of node objects primary language. Great for developers!


Version
=======

* The current version of BC Change Subtree Language is 0.1.1

* Last Major update: March 06, 2017


Copyright
=========

* BC Change Subtree Language is copyright 1999 - 2017 Brookins Consulting and Andreas Adelsberger

* See: [COPYRIGHT.md](COPYRIGHT.md) for more information on the terms of the copyright and license


License
=======

BC Change Subtree Language is licensed under the GNU General Public License.

The complete license agreement is included in the [LICENSE](LICENSE.md) file.

BC Change Subtree Language is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License or at your
option a later version.

BC Change Subtree Language is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

The GNU GPL gives you the right to use, modify and redistribute
BC Change Subtree Language under certain conditions. The GNU GPL license
is distributed with the software, see the file LICENSE.

It is also available at [http://www.gnu.org/licenses/gpl.txt](http://www.gnu.org/licenses/gpl.txt)

You should have received a copy of the GNU General Public License
along with BC Change Subtree Language in LICENSE.

If not, see [http://www.gnu.org/licenses/](http://www.gnu.org/licenses/).

Using BC Change Subtree Language under the terms of the GNU GPL is free (as in freedom).

For more information or questions please contact: license@brookinsconsulting.com


Requirements
============

The following requirements exists for using BC Change Subtree Language extension:


### eZ Publish version

* Make sure you use eZ Publish version 5.x (required) or higher.

* Designed and tested with eZ Publish Platform 5.4


### PHP version

* Make sure you have PHP 5.x or higher.


Features
========

This solution provides the following features:

* Command line script


Dependencies
============

This solution depends on eZ Publish Legacy only


Installation
============

### Bundle Installation via Composer

Run the following command from your project root to install the bundle:

    bash$ composer require brookinsconsulting/bcchangesubtreelanguage dev-master;


### Extension Activation

Optional. Activate this extension by adding the following to your `settings/override/site.ini.append.php`:

    [ExtensionSettings]
    # <snip existing active extensions list />
    ActiveExtensions[]=bcchangesubtreelanguage


### Clear the caches

Optional. Clear eZ Publish Platform / eZ Publish Legacy caches (Required).

    php ./bin/php/ezcache.php --clear-all;


Usage
=====

The solution is configured by the command line script arguments passed to it at runtime.

WARNING! Remember to backup your installations database, source code and var directory content before using this solution!


Usage - Command line script
============

Note: This script must be run using **only** the admin siteaccess!

Change directory into eZ Publish website document root:

    cd path/to/ezpublish/ezpublish_legacy/;

Run the script in test only mode to generate a limited report of the changes which might be made.

    php ./extension/bcchangesubtreelanguage/bin/php/bcchangesubtreelanguage.php -s site_admin --script-verbose=true --parent-node-id=2 --content-class-identifier=folder --locale-identifier=eng-US --test-only;

Run the script normally to re-assign the content object tree default language and remove all other content object translations.

    php ./extension/bcchangesubtreelanguage/bin/php/bcchangesubtreelanguage.php -s site_admin --script-verbose=true --parent-node-id=2 --content-class-identifier=folder --locale-identifier=eng-US;


Troubleshooting
===============

### Read the FAQ

Some problems are more common than others. The most common ones are listed in the the [doc/FAQ.md](doc/FAQ.md)


### Support

If you have find any problems not handled by this document or the FAQ you can contact Brookins Consulting through the support system: [http://brookinsconsulting.com/contact](http://brookinsconsulting.com/contact)

