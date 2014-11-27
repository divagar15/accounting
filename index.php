<?php
/* Global Path */
set_include_path('../'.PATH_SEPARATOR.'library/'
				. PATH_SEPARATOR .'models/'
				. PATH_SEPARATOR .'application/'
				. PATH_SEPARATOR .get_include_path());
/* include loader class */
require_once('Zend/Loader.php');
require_once('Zend/Cache.php');
require_once('docroot/Bootstrapper.php');
	/* Load all Class from loader */
	Zend_Loader::registerAutoLoad();
	$frontController = Zend_Controller_Front::getInstance();
	/* Check environment */
	if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '192.168.1.11') {
		//create instance to bootstapper(development)
		$bootstrap = new Bootstrapper('development');
	} else {
		//create instance to bootstappe(production)
		$bootstrap = new Bootstrapper('production');
	}

	/* Register with controller plugin */
	$frontController->registerPlugin($bootstrap);
	try {
		$response = $frontController->dispatch()->sendResponse();
	} catch(Exception $e) {
		echo $e->getMessage();
	}
?>