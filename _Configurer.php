<?php
class Configurer {
	private $data;
	public function __construct($values){
		$this->data = $values;
	}

	public function __get($varName){
		if (!array_key_exists($varName,$this->data)){
			//this attribute is not defined!
			throw new Exception('.....');
		} else {
			return $this->data[$varName];
		}
	}

	public function __set($varName,$value){
	  	$this->data[$varName] = $value;
	}
}
?>