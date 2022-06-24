<?php declare(strict_types = 1);

namespace Bug7142;

/**
* @return array{id: int}|null
*/

function maybeNull(){
	if ((rand(3)%2) != 0) {
		return ['id' => 1];
	}
	return null;
}

/**
* @return void
*/
function foo(){
	if (!is_null($a = $b = $c = maybeNull())){
		echo $a['id'];
		echo $b['id']; // 20 "Offset 'id' does not exist on array{id: int}|null."
		echo $c['id']; // 21 "Offset 'id' does not exist on array{id: int}|null."
	}
}

