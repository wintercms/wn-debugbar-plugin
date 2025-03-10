# Winter.DebugBar Plugin

![debugbar](https://github.com/wintercms/wn-debugbar-plugin/assets/7253840/eb170da3-133e-4608-b963-fa692e00b127)
[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/wintercms/wn-pages-plugin/blob/main/LICENSE)

Easily see what's going on under the hood of your Winter CMS applications. Integrates [Laravel DebugBar](https://github.com/barryvdh/laravel-debugbar) / [PHP DebugBar](http://phpdebugbar.com/) into Winter CMS.

## Installation

This plugin is available for installation via [Composer](http://getcomposer.org/).

```bash
composer require --dev winter/wn-debugbar-plugin
```

After installing the plugin you will need to run the migrations and (if you are using a [public folder](https://wintercms.com/docs/develop/docs/setup/configuration#using-a-public-folder)) [republish your public directory](https://wintercms.com/docs/develop/docs/console/setup-maintenance#mirror-public-files).

```bash
php artisan migrate
```

## Usage

Set `debug` to `true` in `config/app.php` and the debugbar should appear on your site to all authenticated backend users with the `winter.debugbar.access_debugbar` permission.

If you would like to make the debug bar accessible to all users, regardless of authentication and permissions, set `allow_public_access` to `true` in the configuration file.

See [barryvdh/laravel-debugbar](https://github.com/barryvdh/laravel-debugbar) for more usage instructions and documentation.

## Configuration

All configuration for the plugin is found in the `plugins/winter/debugbar` directory. To override any of these settings, create an override file called `config/winter/debugbar/config.php` in your local system.

To include exceptions in the response header of AJAX calls, set `debugAjax` to `true` in `config/app.php`.

Events are not captured by default since it can slow down the front-end when many events are fired, you may enable it with the `collectors.events` setting.
