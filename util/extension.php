#!/usr/bin/env php
<?php
/**
 * Runs extension manager
 *
 * @author Craig Campbell
 */
use Sonic\Extension\Manager;
use Sonic\App;

$lib_path = str_replace(DIRECTORY_SEPARATOR . 'util' . DIRECTORY_SEPARATOR . 'extension.php', '', realpath(__FILE__)) . DIRECTORY_SEPARATOR . 'libs';
set_include_path($lib_path);
include 'Sonic/App.php';
include 'Sonic/Extension/Manager.php';

try {
    App::getInstance()->start(App::COMMAND_LINE);
    Manager::start($_SERVER['argv']);
} catch (\Exception $e) {
    echo $e->getMessage(),"\n";
}
