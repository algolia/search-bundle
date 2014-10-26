<?php

require __DIR__ . '/app.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

global $kernel;
$application = new Application($kernel);

$application->run();