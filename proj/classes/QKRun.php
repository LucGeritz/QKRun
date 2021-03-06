<?php
namespace Tigrez\QKRun;

use Lurker\Event\FilesystemEvent;
use Lurker\ResourceWatcher;

class QKRun{

	const FATAL = true;
	const VERSION = '1.2.0';
	const STATUS_OK = true;
	const STATUS_NOK = false;
	
	protected $outputter;
	protected $status = self::STATUS_OK;

	protected function tell($msg, $fatal = false){
		$prefix = $fatal ? '*** ' : '';
		$this->outputter->output($prefix.$msg.PHP_EOL);
		if($fatal) $this->status = self::STATUS_NOK;	
	}
	
	protected function default_args(array $receivedArgs, array $defaults){
		foreach($defaults as $dftKey=>$dftVal){
			if(!key_exists($dftKey, $receivedArgs)){
				$receivedArgs[$dftKey]=$dftVal;
			}
		}	
		return $receivedArgs;
	}

	
	protected function getFlow($flowType,array $args){
		
		$flow = isset($args[$flowType]) ? strtoupper($args[$flowType]) : 'MC';
		if(!in_array($flow,['MC','CM'])){
			$this->tell("$flowType should be either MC or CM");
			return false;
		}
		return $flow;	
	}
	
	protected function normalizePaths($config){

		foreach(['csscrush_in','csscrush_out','jsmin_in','jsmin_out','jsconc_in','jsconc_out','cssconc_in','cssconc_out' ] as $configKey){
			if(isset($config[$configKey])){
				$realpath = realpath($config[$configKey]).DIRECTORY_SEPARATOR;
				// illegal paths are ignored, they will pop up later in the flow with a decent 'dir error' message
				if($realpath) $config[$configKey] = $realpath;  			
			}
		}
		return $config;	

	}
	
	protected function empty_dir($dir){
		// if(is_dir($dir)) array_map('unlink', glob("$dir*"));	
	}	
	
	protected function check_dir(array $dirs){
		foreach($dirs as $dirKey=>$dirVal){
			if(!is_dir($dirVal)){
				$this->tell("Setting '$dirKey' is not a valid directory", self::FATAL);
				return false;
			}
		}
		return true;
	}
	
	protected function check_config(array $config, array $demanded){
		foreach($demanded as $demand){
			if(!key_exists($demand, $config)){
				$this->tell("Missing config setting '$demand'", self::FATAL);
				return false;
			}
		}	
		return true;
	}
	
	protected function check_args(array $receivedArgs, array $demandedArgs){
		foreach($demandedArgs as $arg){
			if(!key_exists($arg,$receivedArgs)){
				$this->tell("missing argument '$arg'", self::FATAL);
				return false;
			}
		}	
		return true;
	}
	
	protected function has_sitecontext($args){
		if(!isset($args['site'])){
			$this->tell("select a site first with the select command", self::FATAL);
			return false;
		}
		return true;
	}
	
	protected function show_help(array $txt){
		foreach($txt as $key=>$val){
			if($key===0){
				$this->tell($val);
			}
			else{
				$this->tell('  '.$key."\t".$val);
			}
		}	
	}

	protected function concatFiles($dirIn, $dirOut, $fileOut, $ext, $sort = false){

		$allFiles = scandir($dirIn, $sort ? SCANDIR_SORT_ASCENDING : SCANDIR_SORT_NONE);
		$files=[];
		foreach($allFiles as $file){
			if(pathinfo($file, PATHINFO_EXTENSION)==$ext){
				$files[] = $file; 
			}
		}
		
		if($files){
			
			file_put_contents($dirOut.$fileOut,file_get_contents($dirIn.$files[0]));
			$this->tell('concatting '.$dirIn.$files[0]);			
			for($i=1; $i<sizeof($files);$i++){
				$this->tell('concatting '.$dirIn.$files[$i]);
				file_put_contents($dirOut.$fileOut,file_get_contents($dirIn.$files[$i]),FILE_APPEND);					
				
			}
			
		}		
		
	}
	
	
	/* help for commands */
	protected function help_help(){
		return ['show the available commands'];
	}
	
	protected function help_conjs(){
		return [
			'concatenate javascript files',
			'config: jsconc_in' => 'the dir with the source js files',
			'config: jsconc_out' => 'the dir where concatenated js file is written to',
			'config: jsconc_name' => 'the name of the concatenated js file',
			'--sort' => "(optional) specify this if you want the files being processed in alphabetic order, default false",
		];
		
	}
	protected function help_concss(){
		return [
			'concatenate css files',
			'config: cssconc_in' => 'the dir with the source css files',
			'config: cssconc_out' => 'the dir where concatenated css file is written to',
			'config: cssconc_name' => 'the name of the concatenated css file',
			'--sort' => "(optional) specify this if you want the files being processed in alphabetic order, default false",
		];
	}	
	
	
	protected function help_crush(){
		return [
			'process css files with css-crush (preprocess with optional minify)',
			'config: csscrush_in' => 'the dir with the source css files',
			'config: csscrush_out' => 'the dir where crushed files are written to',
			'--nominify' => "(optional) specify this if you don't want the output files minified, default false",
		];
	}
	
	protected function help_minify(){
		return [
			'minify js files',
			'config: jsmin_in' => 'the dir with the source js files',
			'config: jsmin_out' => 'the dir where minified files are written to',
		];
	}

	protected function help_select(){
		return[
			'select the site QK:Run will be working on. It serves as the default for the --site parameter of other commands',
			'config: sites' => 'A list of available sites',
			'--site=name' => 'the site to select as default'  
		];
	}	
	protected function help_run(){
		return[
			'run is a combination of crush, minify, concat css and concat js. See individual commands for parameters.',
			'--jsflow' => 'Default flow is MC, Minify then Concat, use CM for Concat then Minify',
			'--cssflow' => 'Default flow is MC, Minify/Crush then Concat, use CM for Concat then Minify/Crush' 
		];
	}
	protected function help_watch(){
		return[
			'watches your jsmin_in and csscrush_in dirs for changes and starts a crush + concat (in case of css) or minify + concat (in case of js).',
			'--jsflow' => 'Default flow is MC, Minify then Concat, use CM for Concat then Minify',
			'--cssflow' => 'Default flow is MC, Minify/Crush then Concat, use CM for Concat then Minify/Crush' 
		];
	}
	public function getStatus(){
		return $this->status;
	}
	
	/* commands */
	
	/**
	* select command
	*/
	public function do_select($config, $args){
	
		if (!$this->check_args($args, ['site'])) return false;
		
		if (!$this->check_config($config, ['sites'])) return false;
		
		if(!is_array($config['sites']) || !key_exists($args['site'],$config['sites'])){
			$this->tell('site '.$args['site'].' is not defined in sites setting',self::FATAL);
			return false;
		}
		
		file_put_contents('site',$args['site']);	
		
		$this->tell($args['site']. " selected");
		
		return true;
	}

	/**
	* minify command
	* minifies javascripts in jsmin_in, output in jsmin_out
	*/
	public function do_minify($config, $args){
	
		if (!$this->has_sitecontext($args)) return false;
		
		if(!$this->check_config($config, ['jsmin_in','jsmin_out'])) return false;
		
		if(!$this->check_dir(['jsmin_in'=>$config['jsmin_in'], 'jsmin_out'=>$config['jsmin_out']])) return false;	
		
		$this->empty_dir($config['jsmin_out']);
		
		if ($handle = opendir($config['jsmin_in'])) {
			
        	while (($file = readdir($handle)) !== false) {
        		
        		if(is_file($config['jsmin_in'].$file) && pathinfo($file, PATHINFO_EXTENSION)=='js'){
					$this->tell('minifying '.$config['jsmin_in'].$file);
					$output_file = pathinfo($file ,PATHINFO_FILENAME).'-min';
			
					$minifiedCode = \JShrink\Minifier::minify(file_get_contents($config['jsmin_in'].$file));		
					
					file_put_contents($config['jsmin_out'].$output_file.'.js', $minifiedCode);
					
				}
        		
			}
			closedir($handle);	
     	}
     	return true;
			
	}		
	/**
	* crush command
	*/
	public function do_crush($config, $args){
	
		if (!$this->has_sitecontext($args)) return false;
		
		if (!$this->check_config($config,['csscrush_in','csscrush_out'])) return false;
		
		if(!$this->check_dir(['csscrush_in'=>$config['csscrush_in'], 'csscrush_out'=>$config['csscrush_out']])) return false;	
		
		$minify = isset($args['nominify']) ? !$args['nominify'] : true; 

		$this->empty_dir($config['csscrush_out']);
		
		if ($handle = opendir($config['csscrush_in'])) {
        	while (($file = readdir($handle)) !== false) {
        		
        		if(is_file($config['csscrush_in'].$file) && pathinfo($file, PATHINFO_EXTENSION)=='css'){
					$this->tell('crushing '.$config['csscrush_in'].$file);
					$output_file = pathinfo($file ,PATHINFO_FILENAME).($minify ? '-min' : '');
     				csscrush_file( $config['csscrush_in'].$file, ['minify'=>$minify, 
																'output_dir'=>$config['csscrush_out'], 
																'output_file'=> $output_file ] );	
				}
        		
			}
			closedir($handle);	
     	}
     	
     	return true;        
    }
	
	/**
	* help command
	*/		
	public function do_help($config, $args){
		
		$methods = get_class_methods($this);
		
		$this->tell('Available commands:');
		
		foreach($methods as $method){
			if(substr($method,0,3)=='do_'){
				$this->tell(substr($method,3));
				$this->tell(str_repeat('-', strlen(substr($method,3))));	
				$help = str_replace('do_','help_',$method);
				if(method_exists($this,$help)){
					$this->show_help($this->$help());
					$this->tell('');
				}
			}
						
		}
		
		return true;
	}
	
	/**
	* conjs command
	*/
	public function do_conjs($config, $args){
	
		if (!$this->has_sitecontext($args)) return false;
		
		if(!$this->check_config($config, ['jsconc_in', 'jsconc_out', 'jsconc_name'])) return false;
		if(!$this->check_dir(['jsconc_in' => $config['jsconc_in'], 'jsconc_out'=> $config['jsconc_out'] ])) return false;
		
		$sort = isset($args['sort']);	
	
	    $this->empty_dir($config['jsconc_out']);
		
		$this->concatFiles($config['jsconc_in'],$config['jsconc_out'],$config['jsconc_name'],'js',$sort);	
		
		return true;
	}

	/**
	* concss command
	*/
	public function do_concss($config, $args){
		
		if (!$this->has_sitecontext($args)) return false;
		
		if(!$this->check_config($config, ['cssconc_in', 'cssconc_out', 'cssconc_name'])) return false;
		if(!$this->check_dir(['cssconc_in' => $config['cssconc_in'], 'cssconc_out'=> $config['cssconc_out'] ])) return false;
		
		$sort = isset($args['sort']);	
	
		$this->empty_dir($config['cssconc_out']);
		
		$this->concatFiles($config['cssconc_in'],$config['cssconc_out'],$config['cssconc_name'],'css',$sort);	
		
		return true;
	}
	
	/**
	* run command
	* run is a combination of
	* - crush
	* - minify
	* - concat
	*/
	public function do_run($config, $args){
	
		$cssflow = $this->getFlow('cssflow',$args);
		if(!$cssflow) return false;
		
	    $jsflow = $this->getFlow('jsflow',$args);
		if(!$jsflow) return false;
		
		for($step = 0; $step <2 ; $step++ ){
			
			if($cssflow[$step]=='M'){
				// M for minify = 'crush'
				if(!$this->do_crush($config, $args)) return false;
			}
			else{
				if(!$this->do_concss($config, $args)) return false;	
				
			}	
			
			if($jsflow[$step]=='M'){
				if(!$this->do_minify($config, $args)) return false;
			}
			else{
				if(!$this->do_conjs($config, $args)) return false;		
			}
		}
		
		return true;	
	}
	

	public function do_watch($config, $args){

		$cssflow = $this->getFlow('cssflow',$args);
		if(!$cssflow) return false;
		
	    $jsflow = $this->getFlow('jsflow',$args);
		if(!$jsflow) return false;
		
		$watcher = new ResourceWatcher;
			
		if($cssflow=='MC'){
			$watcher->track('css', $config['csscrush_in']);
			$this->tell('Watching '.$config['csscrush_in']);
		}
		else{
			$watcher->track('css', $config['cssconc_in']);
			$this->tell('Watching '.$config['cssconc_in']);
		}	

		if($jsflow == 'MC'){
			$watcher->track('js', $config['jsmin_in']);
			$this->tell('Watching '.$config['jsmin_in']);
		}
		else{
			$watcher->track('js', $config['jsonc_in']);
			$this->tell('Watching '.$config['jsconc_in']);
		}
		
        $watcher->addListener('css', function (FilesystemEvent $event) use($config, $args){
	        $this->tell('Detected '.$event->getTypeString().' on '.$event->getResource());
        	for($step = 0; $step <2 ; $step++ ){
        		if($cssflow[$step]=='M'){
					// M for minify = 'crush'
					if(!$this->do_crush($config, $args)) return false;
				}
				else{
					if(!$this->do_concss($config, $args)) return false;	
				}
			}	
	     });

		$watcher->addListener('js', function (FilesystemEvent $event) use($config, $args){
	        $this->tell('Detected '.$event->getTypeString().' on '.$event->getResource());
        	for($step = 0; $step <2 ; $step++ ){
        		if($jsflow[$step]=='M'){
					// M for minify
					if(!$this->do_minify($config, $args)) return false;
				}
				else{
					if(!$this->do_conjs($config, $args)) return false;	
				}
			}
        });
		$watcher->start();
		
		return true;
	}
	
	public function setOutputter(IOutputter $outputter){
		
		$this->outputter = $outputter;  
		 		
	}	
	
	public function __construct($config, $args, $outputter=null){
		
		$config = $config === null ? [] : $config;
				
		$this->setOutputter($outputter === null ? new DftOutputter() : $outputter);
		
		$this->tell("QK:Run ".self::VERSION." ". (isset($args['site']) ? '['.$args['site'].']' : '(no site selected)'));
		
		$command = isset($args[0]) ? $args[0] : 'help';
		
		if(!method_exists($this,'do_'.$command)){
			$this->tell("'$command' is not a valid command", self::FATAL);
			$command = 'help';		
		}
		
		$method = 'do_'.$command;

		$this->$method($config, $args);
		$this->tell($this->status ? '..Ok' : '..Nok');			
	}
	
}