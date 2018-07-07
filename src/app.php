<?php
require_once __DIR__ . '/../vendor/autoload.php';
$Application = new \Symfony\Component\Console\Application();
$Application->add(new \App\Command\Rename());
$Application->add(new \App\Command\Write());
$Application->run();