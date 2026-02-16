<?php declare(strict_types = 1);

namespace Bug12574c;

class Galaxy
{
	/** @var list<World> $worlds */
	public array $worlds = [];
}

class World
{
	public int $x = 1;
}


function hello(Galaxy $a): void
{
	$notEmpty = isset($a->worlds[0]);
	if ($notEmpty && $a->worlds[0]->x === 1) {
		echo 'hello';
	}
}

function hello2(Galaxy $a): void
{
	$worlds = $a->worlds;
	$notEmpty = isset($worlds[0]);
	if ($notEmpty && $worlds[0]->x === 1) {
		echo 'hello';
	}
}
