<?php

use Symfony\Component\Yaml\Yaml;

$modulePath = drupal_get_path('module', 'cypress');

$configFile = $modulePath . '/tests/features/config/system.site.yml';

$config = Yaml::parseFile($configFile);
$config['name'] = "Drupal";
file_put_contents($configFile, Yaml::dump($config));
