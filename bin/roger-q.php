#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Command\Dedupe;
use App\Command\Dump;
use App\Command\Publish;
use Symfony\Component\Console\Application;

$application = new Application();
$application->setName('roger-q rabbitmq tool');
$application->setVersion('latest-develop');

$application->add(new Dump());
$application->add(new Dedupe());
$application->add(new Publish());

$application->run();
