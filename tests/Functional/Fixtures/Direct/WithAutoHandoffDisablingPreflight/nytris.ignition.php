<?php

declare(strict_types=1);

use Nytris\Ignition\Ignition;
use Nytris\Ignition\IgnitionConfig;
use Nytris\Ignition\Preflight\PreflightInterface;

$ignitionConfig = new IgnitionConfig();

$ignitionConfig->installPreflight(new class implements PreflightInterface {
    public function getName(): string
    {
        return 'test';
    }

    public function getRunCallback(): Closure
    {
        return function () {
            Ignition::disableAutoHandoff();
        };
    }

    public function getVendor(): string
    {
        return 'test';
    }
});

return $ignitionConfig;
