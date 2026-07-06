<?php declare(strict_types = 1);

namespace Bug13856PhpDoc;

class Foo
{

	/**
	 * @readonly
	 * @var \SplObjectStorage<object, bool>
	 */
	private \SplObjectStorage $store;

	public function __construct()
	{
		$this->store = new \SplObjectStorage();
		$this->store[(object) ['foo' => 'bar']] = true;
	}

}
