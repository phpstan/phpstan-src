<?php declare(strict_types=1);

namespace Bug2675;

class MyClass{
	public $t = 0;

	public function test() : array{
		$result = [];
		for($xx = 0; $xx <= 2; ++$xx){
			for($zz = 0; $zz <= 2; ++$zz){
				$i = $zz * 3 + $xx;
				if($i === 4){
					continue; //center chunk
				}
				$result[$i] = true;
			}
		}
		return $result;
	}
}
