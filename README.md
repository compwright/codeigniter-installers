CodeIgniter Installers for Composer
===================================

[![Build Status](https://secure.travis-ci.org/compwright/codeigniter-installers.png)](http://travis-ci.org/compwright/codeigniter-installers)

[Composer](http://getcomposer.org) installers for [CodeIgniter](http://codeigniter.com) components,
[Sparks](http://getsparks.org/), and
[modules](https://bitbucket.org/wiredesignz/codeigniter-modular-extensions-hmvc/wiki/Home)

Usage
-----

To use, simply specify the desired `type` from the list below and `require` the
`compwright/codeigniter-installers` package in your `composer.json` file, like so:

```json
{
	"name": "vendor/package",
	"type": "codeigniter-library",
	"require": {
		"compwright/codeigniter-installers": "*"
	}
}
```

By default this installer expects your project's `composer.json` file to be at the same level as your
`application` directory and `sparks` directory. If you are using a different directory structure for
your project, you will need to configure the paths accordingly in your project `composer.json` file:

```json
{
	"extra": {
		"codeigniter-application-dir": "Source/application",
		"codeigniter-sparks-dir": "Source/sparks",
	}
}
```

Supported Package Types
-----------------------

Package Type              | Installs To
--------------------------|-------------------------------------
`codeigniter-library`     | `application/libraries/{package}/`
`codeigniter-core`        | `application/core/`
`codeigniter-third-party` | `application/third_party/{package}/`
`codeigniter-module`      | `application/modules/{package}/`
`codeigniter-spark`       | `sparks/{package}/`


Notes
-----

* `codeigniter-library` packages should follow CodeIgniter library naming conventions, and the
  library PHP file should match the package name or you will need to set up a custom loader
  or manually `include` the file.
  
  If one or more PHP files have the `MY_` subclass prefix, they will be moved up one level into the
  `application/libraries/` directory. If all of the PHP files have the `MY_` prefix, then the
  `application/libraries/{package}` directory will be deleted after the PHP files are moved.
  Uninstallation of these files must be performed manually.

* `codeigniter-core` packages are specifically for packages that override a core CodeIgniter file
  in the `application/core/` directory. All PHP files will installed into that directory. Any
  non-PHP files included in the package will not be installed.
  
  Uninstallation of `codeigniter-core` packages must be performed manually.

* `codeigniter-module` packages are designed for the Modular Extensions add-on for CodeIgniter, but
  in theory it could work with any type of module provided that the installation directory is the same.

* `codeigniter-spark` packages should be built according to the instructions on [getsparks.org](http://getsparks.org/make-sparks).

* Individual support for CodeIgniter controllers, config files, language files, models, or helpers
  is not supported. Those components should be encapsulated in a module or spark.

