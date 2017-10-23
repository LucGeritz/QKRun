<?php
class StrOutputter implements Tigrez\QKRun\IOutputter{
	private $lines = [];
	
	public function output($msg){
		$this->lines[]=$msg;	
	}
	public function get($remove_logo = true){
		$lines = $this->lines;
		
		if($remove_logo) array_shift($lines);
		
		$this->lines = [];
		
		return $lines;
	}
}