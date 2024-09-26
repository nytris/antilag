# Nytris Antilag

[![Build Status](https://github.com/nytris/antilag/workflows/CI/badge.svg)](https://github.com/nytris/antilag/actions?query=workflow%3ACI)

Caches filesystem hits during early Nytris boot when `open_basedir` is enabled,
prior to Nytris Boost starting. 

## Usage
Install this package with Composer:

```shell
$ composer require nytris/antilag
```

### When using Nytris platform (recommended)

Configure Nytris platform:

`nytris.config.php`

```php
<?php

declare(strict_types=1);

use Nytris\Antilag\AntilagPackage;
use Nytris\Boot\BootConfig;
use Nytris\Boot\PlatformConfig;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$bootConfig = new BootConfig(new PlatformConfig(__DIR__ . '/var/cache/nytris/'));

$bootConfig->installPackage(new AntilagPackage());

return $bootConfig;
```
