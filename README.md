# Nytris Antilag

[![Build Status](https://github.com/nytris/antilag/workflows/CI/badge.svg)](https://github.com/nytris/antilag/actions?query=workflow%3ACI)

Caches filesystem hits during early Nytris boot when `open_basedir` is enabled,
prior to Nytris Boost starting. 

## Usage
Install this package with Composer:

```shell
$ composer require nytris/antilag
```

### Configure Nytris platform:

`nytris.config.php`

```php
<?php

declare(strict_types=1);

use Nytris\Antilag\AntilagPackage;
use Nytris\Antilag\Stage;
use Nytris\Boot\BootConfig;
use Nytris\Boot\PlatformConfig;

$bootConfig = new BootConfig(new PlatformConfig(__DIR__ . '/var/cache/nytris/'));

$bootConfig->installPackage(new AntilagPackage(stage: Stage::STAGE_2));

// (Other Nytris packages, Nytris Boost is recommended...)

$bootConfig->installPackage(new AntilagPackage(stage: Stage::STAGE_3));

return $bootConfig;
```

### Invoke Stage 1 as early as possible

e.g. from a front controller:

`app.php`
```php
<?php

if (getenv('ENABLE_NYTRIS_ANTILAG') !== 'no') {
    require dirname(__DIR__) . '/vendor/nytris/antilag/antilag.php';
    Antilag::stage1();
}

// Using Symfony as an example:
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
// ...
```

## See also

- [Nytris Boost][Nytris Boost]

[Nytris Boost]: https://github.com/nytris/boost
