<?php
// A test class should implement ITest
// Typically it has a 1:1 relation with a class you want to unit test

include "StrOutputter.php";

class testQKRun implements Tigrez\IExpect\ITest{

	const CSSDIR_IN = 'cssdirin/';
	const CSSDIR_OUT = 'cssdirout/';
	const CSSDIR_CON = 'cssdirconc/';
	const CSSDIR_CONNAME = 'test-con.css';
	const JSDIR_IN       = 'jsdirin/';
	const JSDIR_OUT      = 'jsdirout/';
	const JSDIR_CON      = 'jsdirconc/';
	const JSDIR_CONNAME  = 'test-con.js';

	private function countFileSizes($dirIn,$ext){
		$allFiles = scandir($dirIn);
		$size = 0;
		foreach($allFiles as $file){
			if(pathinfo($file, PATHINFO_EXTENSION)==$ext){
				$size+=filesize($dirIn.$file); 
			}
		}
		return $size;
	}
	
	private function emptyDir($dir){
		$files = glob($dir.'*'); // get all file names

		foreach($files as $file){ // iterate files
  			if(is_file($file)) unlink($file); // delete file
		}
		
	}
	
	private function initializeTests(){
		
		$this->emptyDir(self::CSSDIR_OUT);
		$this->emptyDir(self::CSSDIR_CON);
		$this->emptyDir(self::JSDIR_OUT);
		$this->emptyDir(self::JSDIR_CON);
		
		if(file_exists('site')){
			unlink('site');
		}	
	
	
	}
	
	protected function testCrushCSS(Tigrez\IExpect\Assertion $I){
		
		$output = new StrOutputter();
	
		/* I expect that....................
			If I execute the crush command with no site context
		*/
		$args = [0 => 'crush'];
		$config = ['cssdir_in'=>'somedir', 'cssdir_out'=>'somedir'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);

		/* ... the command will fail */
		$I->Expect($qkrun->getStatus())->equals(false);
		$lines = $output->get();

		/* ... with a message about having no site */
		$I->Expect($lines[0])->caseInsensitive()->contains('select a site first');
	
		/* I expect that....................
		     If I execute the crush command without any config data
		*/
		$args = [0 => 'crush', 'site'=>'dummy'];
		$config = [] ;
				
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$output->get(); // empty the output

		/* ... the command will fail (in an unspecified way) */
		$I->Expect($qkrun->getStatus())->equals(false);
		/* I expect that....................
		     If I execute the crush command with all config and args data specified
		     except the cssdir_in config parameter
		*/
		$config = ['cssdir_out'=>self::CSSDIR_OUT];
		$args = [0 => 'crush', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the cssdir_in config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('missing');
		$I->Expect($lines[0])->caseInsensitive()->contains('cssdir_in');
		
		/* I expect that..........................................................................................
		     If I execute the crush command with all config and args data specified
		     except the cssdir_out config setting
		*/
		$config = ['cssdir_in'=>self::CSSDIR_IN];
		$args = [0 => 'crush', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		/* ... the command will fail */
		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the cssdir_out config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('missing');
		$I->Expect($lines[0])->caseInsensitive()->contains('cssdir_out');

		/* I expect that..........................................................................................
		     If I execute the crush command with all config and args data specified
		     but cssdir_in specifying a wrong dir
		*/
		$config = ['cssdir_out'=>self::CSSDIR_OUT, 'cssdir_in'=>'I/Donot/Exist'];
		$args = [0 => 'crush', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();
		
		/* ... the command will fail */
		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about the cssdir_in value not being a directory */
		$I->Expect($lines[0])->caseInsensitive()->contains('cssdir_in');
		$I->Expect($lines[0])->caseInsensitive()->contains('not a valid dir');
		

		/* I expect that..........................................................................................
		     If I execute the crush command with all config and args data specified
		     but cssdir_out specifying a wrong dir
		*/
		$config = ['cssdir_out'=>'Wish/You/Were/Here', 'cssdir_in'=>self::CSSDIR_IN];
		$args = [0 => 'crush', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();
		
		/* ... the command will fail */
		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about the cssdir_in value not being a directory */
		$I->Expect($lines[0])->caseInsensitive()->contains('cssdir_out');
		$I->Expect($lines[0])->caseInsensitive()->contains('not a valid dir');
		
		/* I expect that..........................................................................................
		     If I execute the crush command with all config and args data specified
		*/
		$config = ['cssdir_out'=>self::CSSDIR_OUT, 'cssdir_in'=>self::CSSDIR_IN];
		$args = [0 => 'crush', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();
		
		/* ... the command will succeed */
		$I->expect($qkrun->getStatus())->equals(true);
		
		/* ... and the crushed files are there */
		$I->expect(self::CSSDIR_OUT.'001-min.css')->isFile();
		$I->expect(self::CSSDIR_OUT.'002-min.css')->isFile();
		$I->expect(self::CSSDIR_OUT.'003-min.css')->isFile();
		$I->expect(self::CSSDIR_OUT.'.csscrush')->isFile();

		$minifiedSize = filesize(self::CSSDIR_OUT.'001-min.css');
		
		/* I expect that..........................................................................................
		     If I execute the crush command with all config and args data specified
		     and the optional arg --nominify specified
		*/
		$config = ['cssdir_out'=>self::CSSDIR_OUT, 'cssdir_in'=>self::CSSDIR_IN];
		$args = [0 => 'crush', 'nominify'=>true,  'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();
				
		/* ... the command will succeed */
		$I->expect($qkrun->getStatus())->equals(true);
				
		//var_dump($lines);
		/* ... and the crushed file 001-min.css to be larger then its minified version */
		$I->expect($minifiedSize<filesize(self::CSSDIR_OUT.'001.css'))->equals(true);
				
	}
	
	protected function testJSMin(Tigrez\IExpect\Assertion $I){
		
		$output = new StrOutputter();
	
		/* I expect that....................
			If I execute the jsmin command with no site context
		*/
		$args = [0 => 'minify'];
		$config = ['jsdir_in'=>'somedir', 'jsdir_out'=>'somedir'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);

		/* ... the command will fail */
		$I->Expect($qkrun->getStatus())->equals(false);
		$lines = $output->get();

		/* ... with a message about having no site */
		$I->Expect($lines[0])->caseInsensitive()->contains('select a site first');
	
		/* I expect that..........................................................................................
		     If I execute the minify command without any config data
		*/
		$args = [0 => 'minify', 'site'=>'dummy'];
		$config = [] ;
				
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$output->get(); // empty the output

		/* ... the command will fail (in an unspecified way) */
		$I->Expect($qkrun->getStatus())->equals(false);
	
		/* I expect that..........................................................................................
		     If I execute the minify command with all config and args data specified
		     except the jsdir_in config parameter
		*/
		$config = ['jsdir_out'=>self::JSDIR_OUT];
		$args = [0 => 'minify',  'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the jsdir_in config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('missing');
		$I->Expect($lines[0])->caseInsensitive()->contains('jsdir_in');
		
		/* I expect that..........................................................................................
		     If I execute the minify command with all config and args data specified
		     except the jsdir_out config setting
		*/
		$config = ['jsdir_in'=>self::JSDIR_IN];
		$args = [0 => 'minify', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		/* ... the command will fail */
		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the jsdir_out config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('missing');
		$I->Expect($lines[0])->caseInsensitive()->contains('jsdir_out');

		/* I expect that..........................................................................................
		     If I execute the minify  command with all config and args data specified
		     but jsdir_in specifying a wrong dir
		*/
		$config = ['jsdir_out'=>self::JSDIR_OUT, 'jsdir_in'=>'I/Donot/Exist'];
		$args = [0 => 'minify', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();
		
		/* ... the command will fail */
		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about the jsdir_in value not being a directory */
		$I->Expect($lines[0])->caseInsensitive()->contains('jsdir_in');
		$I->Expect($lines[0])->caseInsensitive()->contains('not a valid dir');
		

		/* I expect that..........................................................................................
		     If I execute the minify command with all config and args data specified
		     but jsdir_out specifying a wrong dir
		*/
		$config = ['jsdir_out'=>'Wish/You/Were/Here', 'jsdir_in'=>self::JSDIR_IN];
		$args = [0 => 'minify', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();
		
		/* ... the command will fail */
		$I->expect($qkrun->getStatus())->equals(false);

		/* ... with a message about the jsdir_in value not being a directory */
		$I->Expect($lines[0])->caseInsensitive()->contains('jsdir_out');
		$I->Expect($lines[0])->caseInsensitive()->contains('not a valid dir');
		
		/* I expect that..........................................................................................
		     If I execute the minify command with all config and args data specified
		*/
		$config = ['jsdir_out'=>self::JSDIR_OUT, 'jsdir_in'=>self::JSDIR_IN];
		$args = [0 => 'minify', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();
		
		/* ... the command will succeed */
		$I->expect($qkrun->getStatus())->equals(true);
		
		/* ... and the minified files are there */
		$I->expect(self::JSDIR_OUT.'001-min.js')->isFile();
		$I->expect(self::JSDIR_OUT.'002-min.js')->isFile();
		$I->expect(self::JSDIR_OUT.'003-min.js')->isFile();
				
	}
	
	protected function testConCSS(Tigrez\IExpect\Assertion $I){
		
		$output = new StrOutputter();
		
		/* I expect that....................
			If I execute the concss command with no site context
		*/
		$args = [0 => 'crush'];
		$config = ['cssconc_in'=>'somedir', 'cssconc_out'=>'somedir', 'cssconc_name'=>'somename'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);

		/* ... the command will fail */
		$I->Expect($qkrun->getStatus())->equals(false);
		$lines = $output->get();

		/* ... with a message about having no site */
		$I->Expect($lines[0])->caseInsensitive()->contains('select a site first');
	
		/* I expect that..........................................................................................
		     If I execute the concss command without any config data
		*/
		$args = [0 => 'concss', 'site'=>'dummy'];
		$config = [] ;
				
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$output->get(); // empty the output

		/* ... the command will fail (in an unspecified way) */
		$I->Expect($qkrun->getStatus())->equals(false);
	
		/* I expect that..........................................................................................
		     If I execute the concss command with all config and args data specified
		     except the cssconc_in config parameter
		*/
		$config = ['cssconc_out'=>self::CSSDIR_CON,'cssconc_name'=>self::CSSDIR_CONNAME,];
		$args = [0 => 'concss', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the cssdir_in config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('missing');
		$I->Expect($lines[0])->caseInsensitive()->contains('cssconc_in');
		
		/* I expect that..........................................................................................
		     If I execute the concss command with all config and args data specified
		     except the cssconc_out config parameter
		*/
		$config = ['cssconc_in'=>self::CSSDIR_OUT,'cssconc_name'=>self::CSSDIR_CONNAME ,];
		$args = [0 => 'concss', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the cssdir_in config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('missing');
		$I->Expect($lines[0])->caseInsensitive()->contains('cssconc_out');
		
		/* I expect that..........................................................................................
		     If I execute the concss command with all config and args data specified
		     except the cssconc_name config parameter
		*/
		$config = ['cssconc_in'=>self::CSSDIR_OUT,
				   'cssconc_out'=>self::CSSDIR_CON ];
		$args = [0 => 'concss', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the cssdir_in config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('missing');
		$I->Expect($lines[0])->caseInsensitive()->contains('cssconc_name');
		
		/* I expect that..........................................................................................
		     If I execute the concss command with all config and args data specified
		     but with a illegal value for the cssconc_in config parameter
		*/
		$config = [ 'cssconc_in'  =>'Nonexisting/Folder',
					'cssconc_name'=>self::CSSDIR_CONNAME ,
					'cssconc_out' =>self::CSSDIR_CON];
		$args = [0 => 'concss', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the cssdir_in config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('not a valid dir');
		$I->Expect($lines[0])->caseInsensitive()->contains('cssconc_in');
		
		/* I expect that..........................................................................................
		     If I execute the concss command with all config and args data specified
		     but with a illegal value for the cssconc_out config parameter
		*/
		$config = [ 'cssconc_in'  =>'Nonexisting/Folder',
					'cssconc_name'=>self::CSSDIR_CONNAME ,
					'cssconc_out' =>self::CSSDIR_CON];
		$args = [0 => 'concss', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the cssdir_in config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('not a valid dir');
		$I->Expect($lines[0])->caseInsensitive()->contains('cssconc_in');
		
		/* I expect that..........................................................................................
		     If I execute the concss command with all config and args data specified
		*/
		$config = [ 'cssconc_in'  =>self::CSSDIR_OUT,
					'cssconc_name'=>self::CSSDIR_CONNAME ,
					'cssconc_out' =>self::CSSDIR_CON];
		$args = [0 => 'concss', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(true);
		
		/* ... with the concatted file in the target folder */
		$I->Expect(self::CSSDIR_CON.self::CSSDIR_CONNAME)->isFile();
		
		$filesize = $this->countFileSizes(self::CSSDIR_OUT,'css');
		/* ... and the file has the size of all source files combined */
		$I->expect(filesize(self::CSSDIR_CON.self::CSSDIR_CONNAME))->equals($filesize);

}		
	
	protected function testConJS(Tigrez\IExpect\Assertion $I){
		
		$output = new StrOutputter();

		/* I expect that....................
			If I execute the conjs command with no site context
		*/
		$args = [0 => 'conjs'];
		$config = ['jsconc_in'=>'somedir', 'jsconc_out'=>'somedir', 'jsconc_name'=>'somename'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);

		/* ... the command will fail */
		$I->Expect($qkrun->getStatus())->equals(false);
		$lines = $output->get();

		/* ... with a message about having no site */
		$I->Expect($lines[0])->caseInsensitive()->contains('select a site first');
		
		/* I expect that..........................................................................................
		     If I execute the conjs command without any config data
		*/
		$args = [0 => 'conjs', 'site'=>'dummy'];
		$config = [] ;
				
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$output->get(); // empty the output

		/* ... the command will fail (in an unspecified way) */
		$I->Expect($qkrun->getStatus())->equals(false);
	
		/* I expect that..........................................................................................
		     If I execute the conjs command with all config and args data specified
		     except the jsconc_in config parameter
		*/
		$config = ['jsconc_out'=>self::JSDIR_CON,'jsconc_name'=>self::JSDIR_CONNAME,];
		$args = [0 => 'conjs', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the jsdir_in config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('missing');
		$I->Expect($lines[0])->caseInsensitive()->contains('jsconc_in');
		
		/* I expect that..........................................................................................
		     If I execute the conjs command with all config and args data specified
		     except the jsconc_out config parameter
		*/
		$config = ['jsconc_in'=>self::JSDIR_OUT,'jsconc_name'=>self::JSDIR_CONNAME ,];
		$args = [0 => 'conjs', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the jsdir_in config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('missing');
		$I->Expect($lines[0])->caseInsensitive()->contains('jsconc_out');
		
		/* I expect that..........................................................................................
		     If I execute the conjs command with all config and args data specified
		     except the jsconc_name config parameter
		*/
		$config = ['jsconc_in'=>self::JSDIR_OUT,
				   'jsconc_out'=>self::JSDIR_CON ,];
		$args = [0 => 'conjs', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the jsdir_in config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('missing');
		$I->Expect($lines[0])->caseInsensitive()->contains('jsconc_name');
		
		/* I expect that..........................................................................................
		     If I execute the conjs command with all config and args data specified
		     but with a illegal value for the jsconc_in config parameter
		*/
		$config = [ 'jsconc_in'  =>'Nonexisting/Folder',
					'jsconc_name'=>self::JSDIR_CONNAME ,
					'jsconc_out' =>self::JSDIR_CON];
		$args = [0 => 'conjs', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the jsdir_in config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('not a valid dir');
		$I->Expect($lines[0])->caseInsensitive()->contains('jsconc_in');
		
		/* I expect that..........................................................................................
		     If I execute the conjs command with all config and args data specified
		     but with a illegal value for the jsconc_out config parameter
		*/
		$config = [ 'jsconc_in'  =>'Nonexisting/Folder',
					'jsconc_name'=>self::JSDIR_CONNAME ,
					'jsconc_out' =>self::JSDIR_CON];
		$args = [0 => 'conjs', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the jsdir_in config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('not a valid dir');
		$I->Expect($lines[0])->caseInsensitive()->contains('jsconc_in');
		
		/* I expect that..........................................................................................
		     If I execute the conjs command with all config and args data specified
		*/
		$config = [ 'jsconc_in'  =>self::JSDIR_OUT,
					'jsconc_name'=>self::JSDIR_CONNAME ,
					'jsconc_out' =>self::JSDIR_CON];
		$args = [0 => 'conjs', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(true);
		
		/* ... with the concatted file in the target folder */
		$I->Expect(self::JSDIR_CON.self::JSDIR_CONNAME)->isFile();
		
		$filesize = $this->countFileSizes(self::JSDIR_OUT,'js');
		/* ... and the file has the size of all source files combined */
		$I->expect(filesize(self::JSDIR_CON.self::JSDIR_CONNAME))->equals($filesize);

}

	protected function testSelect(Tigrez\IExpect\Assertion $I){
		
		$output = new StrOutputter();
		
		/* I expect that..........................................................................................
		     If I execute the select command without any config data
		*/
		$args = [0 => 'select', 'site' => 'dummy'];
		
		$config = [] ;
				
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$output->get(); // empty the output

		/* ... the command will fail (in an unspecified way) */
		$I->Expect($qkrun->getStatus())->equals(false);
		/* ... and no site file is generated  */
		$I->Expect('site')->not()->isFile('site');

		/* I expect that..........................................................................................
		     If I execute the select command with all config data but no site argument
		*/
		$args = [0 => 'select'];
		$config = ['sites'=>['site1'=>'']] ;

		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();
		
		/* ... the command will fail  */
		$I->Expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about a missing site parameter */
		$I->Expect($lines[0])->caseInsensitive()->contains('site');
		$I->Expect($lines[0])->caseInsensitive()->contains('missing');
		/* ... and no site file is generated  */
		$I->Expect('site')->not()->isFile('site');

		/* I expect that..........................................................................................
		     If I execute the select command with all config data and a site argument
		     but the value of the site argument is not one in sites list
		*/
		$args = [0 => 'select', 'site'=>'site9'];
		$config = [ 'sites' => ['site1'=>'1', 'site2'=>'2']] ;

		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();
		
		/* ... the command will fail  */
		$I->Expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about the site not in the sites parameters */
		$I->Expect($lines[0])->caseInsensitive()->contains('not defined');
		$I->Expect($lines[0])->caseInsensitive()->contains('in sites');
		/* ... and no site file is generated  */
		$I->Expect('site')->not()->isFile('site');

		/* I expect that..........................................................................................
		     If I execute the select command with all config data and a site argument
		     and the value of the site argument is one in sites list
		*/
		$args = [0 => 'select', 'site'=>'site2'];
		$config = [ 'sites' => ['site1'=>'1', 'site2'=>'2']] ;

		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();
		
		/* ... the command will succeed  */
		$I->Expect($qkrun->getStatus())->equals(true);
		
		/* ... and a site file is generated  */
		$I->Expect('site')->isFile();
		/* ... containing the name of the selected site */
		$I->Expect(file_get_contents('site'))->equals('site2');
		//var_dump($lines);
		
			
	}
	
	
	// The only method ITest forces you to implement is run()	
	public function run(Tigrez\IExpect\Assertion $I){
		
		$this->initializeTests();

		$this->testCrushCSS($I);
		$this->testJSMin($I);	
		$this->testConCSS($I);
		$this->testConJS($I);
		$this->testSelect($I);
	}	
}