<?php declare(strict_types = 1);

namespace Bug6642;

$i = 0;
foreach([1,2,3] as $n){
	$bool = $i++ < 3;
	if($bool){
	}
}
