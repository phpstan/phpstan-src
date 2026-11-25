<?php declare(strict_types = 1);

namespace Bug10521;

class HelloWorld
{
	public function count(bool $value) : void{
		$slot = 0;
		while(++$slot < 5){
			if($value){
				break;
			}
		}
		if($slot === 5){
			echo "Still have remaining inventory";
		}
	}
}
