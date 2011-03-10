<?php
/**
 * combined core files to speed up your application (with comments stripped)
 *
 * includes App.php, Request.php, Router.php, Controller.php, View.php, Layout.php, Exception.php
 *
 * @category Sonic
 * @package Core
 * @author Craig Campbell
 * @link http://www.sonicframework.com
 * @license http://www.apache.org/licenses/LICENSE-2.0.html
 * @version 1.1 beta
 *
 * last commit: cad34e37ec82ea124f4d5e130efcd167d992092c
 * generated: 2011-03-10 14:44:54 EST
 */
namespace Sonic;
final class App{const WEB='www';const COMMAND_LINE='cli';const VERSION='1.1';protected static $_instance;protected $_request;protected $_delegate;protected $_paths=array();protected $_controllers=array();protected $_queued=array();protected $_layout_processed=false;protected $_configs=array();protected $_included=array();protected $_base_path;const MODE=0;const ENVIRONMENT=1;const AUTOLOAD=2;const CONFIG_FILE=3;const DEVS=4;const DB_DRIVER=5;const PDO=6;const MYSQL=7;const MYSQLI=8;const DISABLE_APC=9;const TURBO=10;const TURBO_PLACEHOLDER=11;const DEFAULT_SCHEMA=12;const EXTENSION_DATA=13;const EXTENSIONS_LOADED=14;protected $_settings=array( self::MODE=>self::WEB, self::AUTOLOAD=>false, self::CONFIG_FILE=>'ini', self::DEVS=>array('dev','development'), self::DB_DRIVER=>self::PDO, self::DISABLE_APC=>false, self::TURBO=>false, self::EXTENSIONS_LOADED=>array() );private function __construct() {} public function __call($name,$args){return $this->callIfExists($name,$args,__CLASS__,get_class($this));}public static function __callStatic($name,$args){return self::getInstance()->callIfExists($name,$args,__CLASS__,get_called_class(),true);}public function callIfExists($name,$args,$class,$class_name,$static=false){if(count($this->getSetting(self::EXTENSIONS_LOADED))==0){return trigger_error('Call to undefined method '.$class_name.'::'.$name.'()',E_USER_ERROR);}$this->includeFile('Sonic/Extension/Transformation.php');$method=$static ? 'callStatic' : 'call';return Extension\Transformation::$method($name,$args,$class,$class_name);}public static function getInstance(){if(self::$_instance===null){self::$_instance=new App();}return self::$_instance;}public function autoloader($class_name){$path=str_replace('\\','/',$class_name).'.php';return $this->includeFile($path);}public function includeFile($path){if(isset($this->_included[$path])){return false;}include $path;$this->_included[$path]=true;return true;}public function autoload(){spl_autoload_register(array($this,'autoloader'));}public function addSetting($key,$value){$this->_settings[$key]=$value;}public function getSetting($name){if(!isset($this->_settings[$name])){return null;}return $this->_settings[$name];}public static function getConfig($path=null){$app=self::getInstance();$environment=$app->getEnvironment();$type=$app->getSetting(self::CONFIG_FILE);if($path===null){$path=$app->getPath('configs').'/app.'.$type;}$cache_key= 'config_'.$path.'_'.$environment;if(isset($app->_configs[$cache_key])){return $app->_configs[$cache_key];}$app->includeFile('Sonic/Config.php');if(!self::isDev()&&!$app->getSetting(self::DISABLE_APC)&&($config=apc_fetch($cache_key))){$app->_configs[$cache_key]=$config;return $config;}$app->includeFile('Sonic/Util.php');$config=new Config($path,$environment,$type);$app->_configs[$cache_key]=$config;if(!self::isDev()&&!$app->getSetting(self::DISABLE_APC)){apc_store($cache_key,$config,Util::toSeconds('24 hours'));}return $config;}public static function isDev(){$app=self::getInstance();return in_array($app->getEnvironment(),$app->getSetting(self::DEVS));}public function getEnvironment(){if($env=$this->getSetting(self::ENVIRONMENT)){return $env;}if($env=getenv('ENVIRONMENT')){$this->addSetting(self::ENVIRONMENT,$env);return $env;}throw new Exception('ENVIRONMENT variable is not set! check your apache config');}public function getRequest(){if(!$this->_request){$this->_request=new Request();}return $this->_request;}public function getBasePath(){if($this->_base_path){return $this->_base_path;}if($this->getSetting(self::MODE)==self::COMMAND_LINE){$this->_base_path=str_replace(array(DIRECTORY_SEPARATOR.'libs',DIRECTORY_SEPARATOR.'lib'),'',get_include_path());return $this->_base_path;}$this->_base_path=str_replace('/public_html','',$this->getRequest()->getServer('DOCUMENT_ROOT'));return $this->_base_path;}public function getPath($dir=null){$cache_key= 'path_'.$dir;if(isset($this->_paths[$cache_key])){return $this->_paths[$cache_key];}$base_path=$this->getBasePath();if($dir!==null){$base_path .='/'.$dir;}$this->_paths[$cache_key]=$base_path;return $this->_paths[$cache_key];}public function setBasePath($path){$this->_base_path=$path;}public function setPath($dir,$path){$this->_paths['path_'.$dir]=$path;}public function disableLayout(){$this->_layout_processed=true;}public function getController($name){$name=strtolower($name);if(!isset($this->_controllers[$name])){include $this->getPath('controllers').'/'.$name.'.php';$class_name='\Controllers\\'.$name;$this->_controllers[$name]=new $class_name();$this->_controllers[$name]->name($name);$this->_controllers[$name]->request($this->getRequest());}return $this->_controllers[$name];}protected function _runController($controller_name,$action,$args=array(),$json=false,$id=null){$this->getRequest()->addParams($args);$controller=$this->getController($controller_name);$controller->setView($action,false);$view=$controller->getView();$view->setAction($action);$view->addVars($args);$can_run=$json||!$this->getSetting(self::TURBO);if($this->_delegate){$this->_delegate->actionWasCalled($controller,$action);}if($can_run&&!$controller->hasCompleted($action)){$this->_runAction($controller,$action);}if($this->_processLayout($controller,$view,$args)){return;}if($this->_delegate){$this->_delegate->viewStartedRendering($view,$json);}$view->output($json,$id);if($this->_delegate){$this->_delegate->viewFinishedRendering($view,$json);}} protected function _processLayout(Controller $controller,View $view,$args){if($this->_layout_processed){return false;}if(!$controller->hasLayout()){return false;}if(count($this->_controllers)!=1&&!isset($args['exception'])){return false;}$this->_layout_processed=true;$layout=$controller->getLayout();$layout->topView($view);if($this->_delegate){$this->_delegate->layoutStartedRendering($layout);}$layout->output();if($this->_delegate){$this->_delegate->layoutFinishedRendering($layout);}return true;}protected function _runAction(Controller $controller,$action){if($this->_delegate){$this->_delegate->actionStartedRunning($controller,$action);}$controller->$action();$controller->actionComplete($action);if($this->_delegate){$this->_delegate->actionFinishedRunning($controller,$action);}} public function runController($controller_name,$action,$args=array(),$json=false){try{$this->_runController($controller_name,$action,$args,$json);}catch (\Exception $e){$this->handleException($e,$controller_name,$action);return;}} public function queueView($controller,$name){$this->_queued[]=array($controller,$name);}public function processViewQueue(){if(!$this->getSetting(self::TURBO)){return;}while (count($this->_queued)){foreach ($this->_queued as $key=>$queue){$this->runController($queue[0],$queue[1],array(),true);unset($this->_queued[$key]);}}}public function handleException(\Exception $e,$controller=null,$action=null){if($this->_delegate){$this->_delegate->appCaughtException($e,$controller,$action);}if(!$e instanceof \Sonic\Exception){$e=new \Sonic\Exception($e->getMessage(),\Sonic\Exception::INTERNAL_SERVER_ERROR,$e);}if(!headers_sent()){header($e->getHttpCode());}$json=false;$id=null;if($this->getSetting(self::TURBO)&&$this->_layout_processed){$json=true;$id=View::generateId($controller,$action);}$completed=false;if($controller!==null&&$action!==null){$req=$this->getRequest();$first_controller=$req->getControllerName();$first_action=$req->getAction();$completed=$this->getController($first_controller)->hasCompleted($first_action);}$args=array( 'exception'=>$e, 'top_level_exception'=>!$completed, 'from_controller'=>$controller, 'from_action'=>$action );return $this->_runController('main','error',$args,$json,$id);}protected function _robotnikWins(){if($this->getRequest()->isAjax()||isset($_COOKIE['noturbo'])||isset($_COOKIE['bot'])){return true;}if(isset($_GET['noturbo'])){setcookie('noturbo',true,time() + 86400);return true;}if(isset($_SERVER['HTTP_USER_AGENT'])&&strpos($_SERVER['HTTP_USER_AGENT'],'Googlebot')!==false){setcookie('bot',true,time() + 86400);return true;}return false;}public function setDelegate($delegate){$this->includeFile('Sonic/App/Delegate.php');$this->autoloader($delegate);$delegate=new $delegate;if(!$delegate instanceof \Sonic\App\Delegate){throw new \Exception('app delegate ofclass '.get_class($delegate).' must be instance of \Sonic\App\Delegate');}$this->_delegate=$delegate;$this->_delegate->setApp($this);return $this;}public function loadExtension($name){if($this->extensionLoaded($name)){return;}$name=strtolower($name);$extensions=$this->getSetting(self::EXTENSION_DATA);if(!$extensions){$path=$this->getPath('extensions/installed.json');if(file_exists($path)){$extensions=json_decode(file_get_contents($path),true);$this->addSetting(self::EXTENSION_DATA,$extensions);}} if(!isset($extensions[$name])){throw new Exception('trying to load extension "'.$name.'" which is not installed!');}$data=$extensions[$name];$delegate=null;if(isset($data['delegate_path'])&&isset($data['delegate'])){$this->includeFile('Sonic/Extension/Delegate.php');$this->includeFile($this->getPath($data['delegate_path']));$delegate=new $data['delegate'];}if($delegate){$delegate->extensionStartedLoading();}$base_path=$this->getPath();$core='extensions/'.$name.'/Core.php';$has_core=isset($data['has_core'])&&$data['has_core'];$dev=isset($data['dev'])&&$data['dev'];foreach ($data['files'] as $file){$lib_file=strpos($file,'libs')===0;if(strpos($file,'extensions')!==0&&!$lib_file){continue;}if(substr($file,-4)!='.php'){continue;}if($dev&&$file==$core){continue;}if(!$dev&&!$lib_file&&$has_core&&$file!=$core){continue;}$this->includeFile($base_path.'/'.$file);if($delegate){$delegate->extensionLoadedFile($file);}} $loaded=$this->getSetting(self::EXTENSIONS_LOADED);$loaded[]=$name;$this->addSetting(self::EXTENSIONS_LOADED,$loaded);if($delegate){$delegate->extensionFinishedLoading();}return $this;}public function extensionLoaded($name){$loaded=$this->getSetting(self::EXTENSIONS_LOADED);return in_array(strtolower($name),$loaded);}public function extension($name){$this->includeFile('Sonic/Extension/Helper.php');return Extension\Helper::forExtension($name);}public function start($mode=self::WEB,$load=true){if($this->_delegate){$this->_delegate->appStartedLoading($mode);}$this->addSetting(self::MODE,$mode);if($load){$this->_included['Sonic/Exception.php']=true;$this->_included['Sonic/Request.php']=true;$this->_included['Sonic/Router.php']=true;$this->_included['Sonic/Controller.php']=true;$this->_included['Sonic/View.php']=true;$this->_included['Sonic/Layout.php']=true;}if($this->getSetting(self::AUTOLOAD)){$this->autoload();}if($this->_delegate){$this->_delegate->appFinishedLoading();}if($mode!=self::WEB){return;}if($this->getSetting(self::TURBO)&&$this->_robotnikWins()){$this->addSetting(self::TURBO,false);}$controller=$this->getRequest()->getControllerName();$action=$this->getRequest()->getAction();if($this->_delegate){$this->_delegate->appStartedRunning();}$this->runController($controller,$action);if($this->_delegate){$this->_delegate->appFinishedRunning();}}}use \Sonic\Exception;class Request{const POST='POST';const GET='GET';const PARAM='PARAM';protected $_base_url;protected $_params=array();protected $_router;protected $_controller;protected $_controller_name;protected $_action;protected $_router_merged=false;protected $_subdomain;public function getBaseUri(){if($this->_base_url){return $this->_base_url;}$uri=$this->getServer('REDIRECT_URL');if($uri===null||$uri=='/index.php'){$bits=explode('?',$this->getServer('REQUEST_URI'));$uri=$bits[0];}$this->_base_url=$uri;return $this->_base_url;}public function getServer($name){if(!isset($_SERVER[$name])){return null;}return $_SERVER[$name];}public function setSubdomain($subdomain){$this->_subdomain=$subdomain;}public function getRouter(){if($this->_router===null){$this->_router=new Router($this->getBaseUri(),null,$this->_subdomain);}return $this->_router;}public function getControllerName(){if($this->_controller_name!==null){return $this->_controller_name;}$this->_controller_name=$this->getRouter()->getController();if(!$this->_controller_name){throw new Exception('page not found at '.$this->getBaseUri(),Exception::NOT_FOUND);}return $this->_controller_name;}public function getAction(){if($this->_action!==null){return $this->_action;}$this->_action=$this->getRouter()->getAction();if(!$this->_action){throw new Exception('page not found at '.$this->getBaseUri(),Exception::NOT_FOUND);}return $this->_action;}protected function _mergeRouterParams(){if($this->_router_merged){return;}$this->addParams($this->getRouter()->getParams());$this->_router_merged=true;}public function addParams(array $params){foreach ($params as $key=>$value){$this->addParam($key,$value);}} public function addParam($key,$value){$this->_params[$key]=$value;return $this;}public function getParam($name,$type=self::PARAM){switch ($type){case self::POST: if(isset($_POST[$name])){return $_POST[$name];}break;case self::GET: if(isset($_GET[$name])){return $_GET[$name];}break;default: $this->_mergeRouterParams();if(isset($this->_params[$name])){return $this->_params[$name];}break;}return null;}public function getParams($type=self::PARAM){if($type===self::POST){return $_POST;}if($type===self::GET){return $_GET;}$this->_mergeRouterParams();return $this->_params;}public function getPost($name=null){if($name===null){return $this->getParams(self::POST);}return $this->getParam($name,self::POST);}public function isPost(){return $this->getServer('REQUEST_METHOD')=='POST';}public function isAjax(){return $this->getServer('HTTP_X_REQUESTED_WITH')=='XMLHttpRequest';}public function setHeader($key,$value,$overwrite=true){return header($key.': '.$value,$overwrite);}public function reset(){$this->_action=null;$this->_controller_name=null;$this->_base_url=null;$this->_router=null;}} class Router{protected $_base_uri;protected $_path;protected $_subdomain;protected $_routes;protected $_match;protected $_params=array();public function __construct($base_uri,$path=null,$subdomain=null){$this->_base_uri=$base_uri;$this->_path=$path;$this->_subdomain=$subdomain;}public function getRoutes(){if($this->_routes){return $this->_routes;}if(!$this->_path){$filename='routes.'.(!$this->_subdomain ? 'php' : $this->_subdomain.'.php');$this->_path=App::getInstance()->getPath('configs').'/'.$filename;}include $this->_path;$this->_routes=$routes;return $this->_routes;}public function setRoutes(array $routes){$this->_routes=$routes;}protected function _setMatch($match,$result=null){if($match===null){$this->_match=array(null,null);return $this;}if(!isset($match[2])){$this->_match=$this->_alterMatch($match);return $this;}$params=$match[2];foreach ($params as $key=>$param){if(!is_int($key)){$this->_params[$key]=$param;continue;}if(isset($result[$key])){$this->_params[$param]=$result[$key];continue;}} $this->_match=$this->_alterMatch($match);return $this;}protected function _alterMatch(array $match){if(isset($this->_params['CONTROLLER'])){$match[0]=$this->_params['CONTROLLER'];unset($this->_params['CONTROLLER']);}if(isset($this->_params['ACTION'])){$match[1]=$this->_params['ACTION'];unset($this->_params['ACTION']);}return $match;}protected function _getMatch(){if($this->_match!==null){return $this->_match;}$base_uri=$this->_base_uri=='/' ? $this->_base_uri : rtrim($this->_base_uri,'/');if($base_uri==='/'&&!$this->_subdomain){$this->_match=array('main','index');return $this->_match;}$routes=$this->getRoutes();if(isset($routes[$base_uri])){$this->_setMatch($routes[$base_uri]);return $this->_match;}$route_keys=array_keys($routes);$len=count($route_keys);$match=false;for ($i=0;$i < $len;++$i){$result=$this->_matches($route_keys[$i],$base_uri);if($result){$match=true;break;}} if($match){$this->_setMatch($routes[$route_keys[$i]],$result);return $this->_match;}$this->_setMatch(null);return $this->_match;}protected function _matches($route_uri,$base_uri){if(isset($route_uri[1])&&$route_uri[0].$route_uri[1]=='r:'){return $this->_matchesRegex($route_uri,$base_uri);}$route_bits=explode('/',$route_uri);$url_bits=explode('/',$base_uri);$route_bit_count=count($route_bits);if($route_bit_count!==count($url_bits)){return false;}$params=array();for ($i=1;$i < $route_bit_count;++$i){$first_char=isset($route_bits[$i][0]) ? $route_bits[$i][0] : null;if($first_char==':'||$first_char=='*'){$param=substr($route_bits[$i],1);$params[$param]=$url_bits[$i];continue;}if($first_char=='#'&&is_numeric($url_bits[$i])){$param=substr($route_bits[$i],1);$params[$param]=$url_bits[$i];continue;}if($first_char=='@'&&preg_match('/^[a-zA-Z]+$/',$url_bits[$i]) > 0){$param=substr($route_bits[$i],1);$params[$param]=$url_bits[$i];continue;}if($route_bits[$i]!=$url_bits[$i]){return false;}} $this->_params=$params;return true;}protected function _matchesRegex($route_uri,$base_uri){$route_uri=substr($route_uri,2);$match_count=preg_match('/'.$route_uri.'/i',$base_uri,$matches);return $match_count > 0 ? $matches : false;}public function getController(){$match=$this->_getMatch();return $match[0];}public function getAction(){$match=$this->_getMatch();return $match[1];}public function getParams(){return $this->_params;}} class Controller{protected $_name;protected $_view_name;protected $_view;protected $_layout;protected $_layout_name=Layout::MAIN;protected $_request;protected $_actions_completed=array();protected $_input_filter;public function __get($var){if($var==='view'){return $this->getView();}if($var==='layout'){return $this->getLayout();}throw new Exception('only views and layouts are magic');}public function __call($name,$args){return App::getInstance()->callIfExists($name,$args,__CLASS__,get_class($this));}public static function __callStatic($name,$args){return App::getInstance()->callIfExists($name,$args,__CLASS__,get_called_class(),true);}final public function name($name=null){if($name!==null){$this->_name=$name;}return $this->_name;}final public function setView($name,$from_controller=true){if($this->_view_name==$name){return $this;}$this->_view_name=$name;if($from_controller){$this->getView()->path($this->getViewPath());}if(!$from_controller){$this->_view=null;}if($this->_layout_name===null){return $this;}return $this;}public function request(Request $request=null){if($request!==null){$this->_request=$request;}return $this->_request;}public function actionComplete($action){$this->_actions_completed[$action]=true;return $this;}public function getActionsCompleted(){return array_keys($this->_actions_completed);}public function hasCompleted($action){return isset($this->_actions_completed[$action]);}public function disableLayout(){$this->_layout_name=null;return $this;}public function disableView(){$this->getView()->disable();}public function hasLayout(){return $this->_layout_name!==null;}public function setLayout($name){$this->_layout_name=$name;}public function getLayout(){if($this->_layout===null){$layout_dir=App::getInstance()->getPath('views/layouts');$layout=new Layout($layout_dir.'/'.$this->_layout_name.'.phtml');$this->_layout=$layout;}return $this->_layout;}final public function getViewPath(){return App::getInstance()->getPath('views').'/'.$this->_name.'/'.$this->_view_name.'.phtml';}public function getView(){if($this->_view===null){$this->_view=new View($this->getViewPath());$this->_view->setAction($this->_view_name);$this->_view->setActiveController($this->_name);}return $this->_view;}protected function _redirect($location){if(App::getInstance()->getSetting(App::TURBO)){$this->getView()->addTurboData('redirect',$location);return;}header('location: '.$location);exit;}protected function _json(array $data){header('Content-Type: application/json');echo json_encode($data);exit;}final public function filter($name){if($this->_input_filter!==null){return $this->_input_filter->filter($name);}App::getInstance()->includeFile('Sonic/InputFilter.php');$this->_input_filter=new InputFilter($this->request());return $this->_input_filter->filter($name);}public function __toString(){return get_class($this);}} class View{const TITLE=1;const CSS=2;const JS=3;const PATH=4;const TURBO_PLACEHOLDER=5;const KEYWORDS=6;const DESCRIPTION=7;protected $_active_controller;protected $_action;protected $_data=array( self::JS=>array(), self::CSS=>array() );protected $_html;protected $_disabled=false;protected $_turbo_data=array();protected static $_static_path='/assets';public function __construct($path){$this->path($path);}public function __get($var){if(!isset($this->$var)){return null;}return $this->$var;}public function escape($string){return htmlentities($string,ENT_QUOTES,'UTF-8',false);}public static function staticPath($path=null){if(!$path){return self::$_static_path;}self::$_static_path=$path;return self::$_static_path;}public function data($key,$value=null){$data=isset($this->_data[$key]) ? $this->_data[$key] : null;if($value===null){return $data;}if(is_array($data)){$this->_data[$key][]=$value;return;}$this->_data[$key]=$value;}public function path($path=null){return $this->data(self::PATH,$path);}public function title($title=null){if($title!==null){$title=Layout::getTitle($title);}return $this->data(self::TITLE,$title);}public function keywords($keywords=null){return $this->data(self::KEYWORDS,$keywords);}public function description($description=null){return $this->data(self::DESCRIPTION,$description);}public function addVars(array $args){foreach ($args as $key=>$value){$this->$key=$value;}} public function isTurbo(){return App::getInstance()->getSetting(App::TURBO);}public function setActiveController($name){$this->_active_controller=$name;}public function setAction($name){$this->_action=$name;}public function addJs($path){if($this->_isAbsolute($path)){return $this->data(self::JS,$path);}$this->data(self::JS,$this->staticPath().'/js/'.$path);}public function addCss($path){if($this->_isAbsolute($path)){return $this->data(self::CSS,$path);}$this->data(self::CSS,$this->staticPath().'/css/'.$path);}protected function _isAbsolute($path){if(!isset($path[7])){return false;}return $path[0].$path[1].$path[2].$path[3].$path[4]=='http:';}public function getJs(){return $this->data(self::JS);}public function getCss(){return $this->data(self::CSS);}public function disable(){$this->_disabled=true;}public function render($controller,$action=null,$args=array()){if($action===null||is_array($action)){$args=(array) $action;$action=$controller;$controller=$this instanceof Layout ? Layout::MAIN : $this->_active_controller;}App::getInstance()->runController($controller,$action,$args);}public function buffer(){if($this->_disabled){return;}if($this->isTurbo()){return;}ob_start();$this->output();$this->_html=ob_get_contents();ob_end_clean();}public function turboPlaceholder($html=null){return $this->data(self::TURBO_PLACEHOLDER,$html);}public static function generateId($controller,$action){return 'v'.substr(md5($controller.'::'.$action),0,7);}public function getId(){return $this->generateId($this->_active_controller,$this->_action);}public function getHtml(){if($this->isTurbo()&&!$this instanceof Layout&&!$this->_html){App::getInstance()->queueView($this->_active_controller,$this->_action);$placeholder=$this->data(self::TURBO_PLACEHOLDER) ?: App::getInstance()->getSetting(App::TURBO_PLACEHOLDER);$this->_html='<div class="sonic_fragment" id="'.$this->getId().'">'.$placeholder.'</div>';}return $this->_html;}public function addTurboData($key,$value){$this->_turbo_data[$key]=$value;}public function outputAsJson($id=null){if(!$id){$id=$this->getId();}ob_start();include $this->data(self::PATH);$html=ob_get_contents();ob_end_clean();$data=array( 'id'=>$id, 'content'=>$html, 'title'=>$this->title(), 'css'=>$this->getCss(), 'js'=>$this->getJs()) + $this->_turbo_data;$output='<script>SonicTurbo.render('.json_encode($data).');</script>';return $output;}public function output($json=false,$id=null){if($this->_disabled){return;}if(!$json&&!$this instanceof Layout&&$this->getHtml()!==null){echo $this->getHtml();return;}if($json){echo $this->outputAsJson($id);return;}include $this->data(self::PATH);}public function __toString(){return (string) $this->_action;}} class Layout extends View{const MAIN='main';const TOP_VIEW='top_view';protected static $_title_pattern;public function output($json=false,$id=null){if($this->topView()!==null){$this->topView()->buffer();}parent::output();}public function topView(View $view=null){return $this->data(self::TOP_VIEW,$view);}public function setTitlePattern($pattern){self::$_title_pattern=$pattern;return self::getTitle($this->topView() ? $this->topView()->title() : '');}public static function getTitle($string){if(!self::$_title_pattern){return $string;}return str_replace('${title}',$string,self::$_title_pattern);}public function noTurboUrl(){$uri=$_SERVER['REQUEST_URI'];if(strpos($uri,'?')!==false){return $uri.'&noturbo=1';}return $uri.'?noturbo=1';}public function turbo(){while (ob_get_level()){ob_end_flush();}flush();return App::getInstance()->processViewQueue();}} class Exception extends \Exception{const INTERNAL_SERVER_ERROR=0;const NOT_FOUND=1;const FORBIDDEN=2;const UNAUTHORIZED=3;public function getDisplayMessage(){switch ($this->code){case self::NOT_FOUND: return 'The page you were looking for could not be found.';break;case self::FORBIDDEN: return 'You do not have permission to view this page.';break;case self::UNAUTHORIZED: return 'This page requires login.';break;default: return 'Some kind of error occured.';break;}} public function getHttpCode(){switch ($this->code){case self::NOT_FOUND: return 'HTTP/1.1 404 Not Found';break;case self::FORBIDDEN: return 'HTTP/1.1 403 Forbidden';break;case self::UNAUTHORIZED: return 'HTTP/1.1 401 Unauthorized';break;default: return 'HTTP/1.1 500 Internal Server Error';break;}}}
