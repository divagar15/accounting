<?php 
/**
* Bootstrapper
*
* Bootstapper to startup the application execution
*/
require_once('Zend/Controller/Plugin/Abstract.php');
class Bootstrapper extends Zend_Controller_Plugin_Abstract 
{
	/**
    * @var Zend_Registry
    */
	protected $registry;
	
	/**
    * @var Zend_Config
    */
	protected $config;
	
	/**
    * @var string Setting enironment development / production
    */
	protected $environment;
	
	/**
     * @var string Applicatin Path
    */
	protected $root;
	
	/**
    * @var Zend_Front_Controller
    */
	protected $frontCrtls;
	
	/**
    * Constructor
    *
    * Initialize environment, front controller
    * 
    * @param  string $environment 
    * @return void
    */
	public function __construct($environment) {
		$this->environment = $environment;
		$this->frontCtrls = Zend_Controller_Front::getInstance();
	}
	
	/**
    * Route startup
    * 
    * @return void
    */	
	public function routeStartup(Zend_Controller_Request_Abstract $request) {
		Zend_Loader::registerAutoLoad();
		/*  Call Basic function */
		$this->setupRegistry();
		$this->setupRootDir();
		$this->setupPhpConfig();
		/* Call Default Setting */
		$this->setupIniConfig();
		$this->setDatabaseConfig();
		$this->setupFrontController();
		/*$this->setupRoutage();*/
		$this->setupView();
		/*$this->setupLocale();*/
		//$this->setFireBug();
		//$this->setupCache();
	}	
		
	/**
    * Creates the instance for Zend_Registry
    * 
    * @return void
    */	
	public function setupRegistry() {
		$this->registry = new Zend_Registry(array(), ArrayObject::ARRAY_AS_PROPS);
		Zend_Registry::setInstance($this->registry);
	}
		
	/**
    * Set Applicatin current Path
    * 
	* Intialize the Path to Registry Instance 
    * @return void
    */	
	public function setupRootDir() {
		$this->root = dirname(dirname(__FILE__));
		$this->registry->path = $this->root;
	}
	
	/**
    * Set Basic Php Configuration and set php.ini
    * 
	* Intialize the Path to Registry Instance 
    * @return void
    */		
	public function setupPhpConfig() {
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		ini_set('default_charset', 'ISO-8859-1');
		date_default_timezone_set('Asia/Calcutta');
	}
		
	/* config Ini file */
	public function setupIniConfig() {
		$this->configIni = new Zend_Config_Ini($this->root.'/config/configDB.ini', $this->environment);
		//$this->registry->set('config', $this->config);
		$this->registry->config = $this->configIni;
	}
	
		/* Database Configuration  */
		public function setDatabaseConfig() {
		// db parameters
			$dbType   = 'Pdo_Mysql'; 
			$dbParams = array('host'     =>  $this->configIni->db->host,
					      	  'username' =>  $this->configIni->db->username,
					          'password' =>  $this->configIni->db->password,
					          'dbname'   =>  $this->configIni->db->dbname,
						      'profiler' => true
						     );
			$db = Zend_Db::factory($dbType, $dbParams);
			Zend_Db_Table::setDefaultAdapter($db);
			$this->registry->database = $db;

			$profiler = new Zend_Db_Profiler_Firebug('accounting_new');
			$profiler->setEnabled(true);
			$db->setProfiler($profiler);
		
			$queryProfiler = $db->getProfiler();
			Zend_Registry::set('queryProfiler', $queryProfiler);
		}
		/* Set  default Controller Directory */
		public function setupFrontController() {
			Zend_Session::start();
		//	Zend_Session::rememberMe(3600); // Set session expiry if user idle for more than 5 mins
			$idleTimeout = 7200; // timeout after 1 hour</span>
 			
			if(isset($_SESSION['timeout_idle']) && $_SESSION['timeout_idle'] < time() && Zend_Session::namespaceIsset('sess_login')) {
				Zend_Session::destroy();
				Zend_Session::regenerateId();
				header('Location: index');

			} 
			$_SESSION['timeout_idle'] = time() + $idleTimeout;

			$this->frontCtrls->returnResponse(true);
			$this->frontCtrls->setControllerDirectory(array('default'      =>  $this->root.'/application/default/controllers/',
															'business'     =>  $this->root.'/application/business/controllers/',
															'transaction'  =>  $this->root.'/application/transaction/controllers/',
															'reports'      =>  $this->root.'/application/reports/controllers/'));
			$this->frontCtrls->throwExceptions(true);
			//$this->frontCtrls->setParam('registry', $this->registry);
		}
		//,	'admin'   =>  $this->root.'/application/admin/index/controllers/'))
		/* Set Routage for controller/action */
		public function setupRoutage() {
			//$route = new Zend_Controller_Router_Route
			$router = $this->frontCtrls->getRouter();
			$setRoute = new Zend_Controller_Router_Route(':action', array('module'=>'default', 'controller'=>'index', 'action'=>'index'));
			$router->addRoute('index', $setRoute);
		}
		
		public function setupView() {
			$this->viewLayout        = Zend_Layout::startMvc();
			$this->viewLayout->setLayoutPath($this->root.'/application/resources/layouts/');
			$this->viewLayout->setLayout('commonlayout'); /* commonLayout.phtml  */
			$this->configXml   		  	      =  	new Zend_Config_Xml($this->root.'/config/applicationPath.xml', $this->environment);
			$view 		   = new Zend_View();
       		$viewRenderer  = new Zend_Controller_Action_Helper_ViewRenderer($view); // Add the view to the ViewRenderer
			// View Helper Path
			$view->setHelperPath($this->root.'/application/resources/helpers/','Zend_View_Helper'); // common helper
			$view->addHelperPath('library/ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper'); 
			 
			// Jquery helper
			Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
			$view->adminEmail				  =		'admin@ummtech.com';
			$urlConfig						  =     $this->configXml->weburl->toArray();
			$view->title					  = 	$this->configXml->webtitle;
			$view->sitePath        			  =  	'http://' . $_SERVER['HTTP_HOST'].$urlConfig['sitepath']."index.php/";
			$this->sitePath        			  =  	'http://' . $_SERVER['HTTP_HOST'].$urlConfig['sitepath']."index.php/";
			$view->absPath         			  =  	$urlConfig['abspath'];
			$view->styleSheetPath  			  = 	'http://' . $_SERVER['HTTP_HOST'].$urlConfig['stylesheetpath'];
			$view->scriptpath		   		  =     'http://' . $_SERVER['HTTP_HOST'].$urlConfig['scriptpath'];
			$view->imagesPath      		      = 	'http://' . $_SERVER['HTTP_HOST'] .$urlConfig['imagespath'];
			$view->uploadFileUrl    		  = 	'http://' . $_SERVER['HTTP_HOST'] .$urlConfig['uploadfilepath'];
			$view->pluginpath		   		  =     'http://' . $_SERVER['HTTP_HOST'].$urlConfig['pluginpath'];
			$view->uploadpath		   		  =     'http://' . $_SERVER['HTTP_HOST'].$urlConfig['uploadpath'];
			$view->receiptuploadpath		  =     'http://' . $_SERVER['HTTP_HOST'].$urlConfig['receiptuploadpath'];
			$view->footText                   = 	$this->configXml->footertext;
			$view->cacheurl      		  	  =     $this->configXml->cacheurl;
			// register site value 
			$this->registry->pagingVal        =     $this->configXml->perpagevalue;		  
			$this->registry->sitePath         =  	$urlConfig['sitepath'];
			$this->registry->absPath          =  	$urlConfig['abspath'];
			$this->registry->styleSheetPath   = 	$urlConfig['stylesheetpath'];
			$this->registry->scriptpath		  =     $urlConfig['scriptpath'];
			$this->registry->uploadpath 	  = 	$urlConfig['uploadpath'];
			$this->registry->receiptuploadpath=     $urlConfig['receiptuploadpath'];
			$this->registry->cacheurl 		  =     $view->cacheurl;
			//$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
    		//$viewRenderer->setView($this->view);
			//echo '<pre>';  print_r($this->configXml->webtitle); echo '</pre>';
			//echo '<pre>';  print_r($this->view); echo '</pre>';
		}
		
		/* Config Language if multiple lang present */
		public function setupLocale() {
			$trans = new Zend_Translate('array', $this->root.'/languages/fr/fr.php', 'fr'); // (adapter, content(path or array name), locale))
			//$trans->setLocale('en');
			$this->registry->set('translate', $trans);
		}
		
		
		/* Create Zend Log Instances and Zend Firebug*/
		public function setFireBug() {
			$writer = new Zend_Log_Writer_Firebug();
			$logger = new Zend_Log($writer);
			$this->registry->set('logger', $logger);
		}
		
		
		/* Create Cache file */
		public function setupCache() {
			$frontOpt = array('lifetime' => '7200',
							 'automatic_serialization' => 'true');
			$backOpt = array('cache_dir' => './cache/') ; /* Directory to specific file */
		$cache = Zend_Cache::factory('Core', 'File', $frontOpt, $backOpt); /* Config cache  */
		$this->registry->cache = $cache;	 
		}
		

}
?>