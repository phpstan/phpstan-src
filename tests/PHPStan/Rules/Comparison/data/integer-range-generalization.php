<?php

namespace IntegerRangeGenerealization;

class Foo
{

	public function doFoo(string $array): string
	{
		$result = str_repeat("\x00", 4096);
		if($array !== $result){
			$i = 0;
			for($x = 0; $x < 16; ++$x){
				$zM = $x + 256;
				for($z = $x; $z < $zM; $z += 16){
					$yM = $z + 4096;
					for($y = $z; $y < $yM; $y += 256){
						$result[$i] = $array[$y];
						++$i;
					}
				}
			}
		}

		return $result;
	}

}
