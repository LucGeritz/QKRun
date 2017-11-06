<?php
namespace Tigrez\QKRun;
class DftOutputter implements IOutputter{
	public function output($msg){
		echo $msg;
	}
}