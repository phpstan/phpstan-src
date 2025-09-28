<?php

namespace Levels\ArrayAccess;

class Foo
{

	/**
	 * @param object $object
	 */
	public function doFoo(
		$object
	)
	{
		$splObjectStorage = new \SplObjectStorage();
		$splObjectStorage[$object] = 1;
	}

	/**
	 * @param object|int $objectOrInt
	 */
	public function doBar(
		$objectOrInt
	)
	{
		$splObjectStorage = new \SplObjectStorage();
		$splObjectStorage[$objectOrInt] = 1;
	}

	public function doBaz(
		int $int
	)
	{
		$splObjectStorage = new \SplObjectStorage();
		$splObjectStorage[$int] = 1;
	}

	public function doLorem(
		$mixed
	)
	{
		$splObjectStorage = new \SplObjectStorage();
		$splObjectStorage[$mixed] = 1;
	}

}

/**
 * @return mixed[]
 */
function bug12931():array {
	/** @var array<string, array<string, int>> $data */
	$data = [];
	$data['attr'] = [];
	$data['attr']['first'] = 1;
	$data['attr']['second'] = 2;
	$data['attr']['third'] = 3;

	return $data;
}
