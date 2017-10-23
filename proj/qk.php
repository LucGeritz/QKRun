<?php
include "../vendor/autoload.php";

$parms = Tigrez\QKRun\CommandLine::parseArgs($argv);
$config = include('config/qkrun.config.php');		

if(!isset($parms['site']) && file_exists('site')){
	$parms['site'] = file_get_contents('site');
}

if(isset($config['sites'][$parms[ 'site']])){
	$siteConfig = include($config['sites'][$parms[ 'site']]);
	$config = array_merge($config,$siteConfig);
}
else{
	echo "*** No definition in config found for site ".$parms['site'];
	die(); 
}

$qkrun = new Tigrez\QKRun\QKRun($config, $parms);