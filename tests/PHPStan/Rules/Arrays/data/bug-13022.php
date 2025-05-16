<?php declare(strict_types = 1);

namespace Bug13022;

class RootClass
{
	function getId(): int
	{
		return 1;
	}
}

class SomeClass extends RootClass{

}

function test(RootClass $object): void
{

	$searchId = $object instanceof SomeClass ? 'uid' : 'aid';
	$targetId = $object instanceof SomeClass ? 'aid' : 'uid';

	$array =        [
		$searchId => $object->getId(),
		$targetId => 'info',
	];

	// sql()->insert('tablename', $array); - example how this will be used
}
