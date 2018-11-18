<?php

$start = microtime(true);

use Symfony\Component\Yaml\Yaml;

ini_set('display_errors', true);
error_reporting(E_ALL);
include __DIR__ . '/lib/Autoloader.php';
(new Autoloader)->register();

$yaml = Yaml::parseFile(__DIR__ . '/pages/nav.tree');
$nav_tree = new Nav_Tree($yaml);
(new Template(__DIR__ . '/pages/home.html', $nav_tree))->parse();

echo '<span class="speed">' . round((microtime(true) - $start) * 1000, 1) . 'ms' . '</span>';
