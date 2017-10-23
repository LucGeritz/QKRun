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
	
	}
	
	protected function testCrushCSS(Tigrez\IExpect\Assertion $I){
		
		$output = new StrOutputter();
		
		/* I expect that....................
		     If I execute the crush command without any config data
		*/
		$args = [0 => 'crush'];
		$args['site'] = 'dummy';
		$config = [] ;
				
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$output->get(); // empty the output

		/* ... the command will fail (in an unspecified way) */
		$I->Expect($qkrun->getStatus())->equals(false);
	
		/* I expect that..........................................................................................
		     If I execute the crush command with all config data specified
		     but no site argument
		*/
		$config = ['cssdir_in'=>self::CSSDIR_IN, 'cssdir_out'=>self::CSSDIR_OUT];
		$args   = [0 => 'crush'];
		
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		
		/* ... the command will fail */
		$I->Expect($qkrun->getStatus())->equals(false);
		$lines = $output->get();
		
		/* ... with a message about missing the site argument */
		$I->Expect($lines[0])->caseInsensitive()->contains('missing');
		$I->Expect($lines[0])->caseInsensitive()->contains('site');

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
		
		//var_dump($lines);
				
	}
	
	protected function testJSMin(Tigrez\IExpect\Assertion $I){
		
		$output = new StrOutputter();
		
		/* I expect that..........................................................................................
		     If I execute the minify command without any config data
		*/
		$args = [0 => 'minify'];
		$args['site'] = 'dummy';
		$config = [] ;
				
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$output->get(); // empty the output


		
		/* ... the command will fail (in an unspecified way) */
		$I->Expect($qkrun->getStatus())->equals(false);
	
		/* I expect that..........................................................................................
		     If I execute the crush command with all config data specified
		     but no site argument
		*/
		$config = ['jsdir_in'=>self::JSDIR_IN, 'jsdir_out'=>self::JSDIR_OUT];
		$args   = [0 => 'minify'];
		
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		
		/* ... the command will fail */
		$I->Expect($qkrun->getStatus())->equals(false);
		$lines = $output->get();
		
		/* ... with a message about missing the site argument */
		$I->Expect($lines[0])->caseInsensitive()->contains('missing');
		$I->Expect($lines[0])->caseInsensitive()->contains('site');
die(); //@@@
		/* I expect that..........................................................................................
		     If I execute the crush command with all config and args data specified
		     except the jsdir_in config parameter
		*/
		$config = ['jsdir_out'=>self::JSDIR_OUT];
		$args = [0 => 'minify', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();

		$I->expect($qkrun->getStatus())->equals(false);
		
		/* ... with a message about missing the jsdir_in config setting */
		$I->Expect($lines[0])->caseInsensitive()->contains('missing');
		$I->Expect($lines[0])->caseInsensitive()->contains('jsdir_in');
		
		/* I expect that..........................................................................................
		     If I execute the crush command with all config and args data specified
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
		     If I execute the crush command with all config and args data specified
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
		     If I execute the crush command with all config and args data specified
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
		     If I execute the crush command with all config and args data specified
		*/
		$config = ['jsdir_out'=>self::JSDIR_OUT, 'jsdir_in'=>self::JSDIR_IN];
		$args = [0 => 'minify', 'site'=>'dummy'];
		$qkrun = new Tigrez\QKRun\QKRun($config, $args, $output);
		$lines = $output->get();
		
		/* ... the command will succeed */
		$I->expect($qkrun->getStatus())->equals(true);
		
		/* ... and the crushed files are there */
		$I->expect(self::JSDIR_OUT.'001-min.js')->isFile();
		$I->expect(self::JSDIR_OUT.'002-min.js')->isFile();
		$I->expect(self::JSDIR_OUT.'003-min.js')->isFile();
		
		//var_dump($lines);
				
	}
	// The only method ITest forces you to implement is run()	
	public function run(Tigrez\IExpect\Assertion $I){
		
		$this->initializeTests();

		$this->testCrushCSS($I);
		$this->testJSMin($I);	
		
	}	
}