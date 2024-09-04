<?php

namespace Bug8474;

class World {}
class HelloWorld extends World {
	public string $hello = 'world';
}

function hello(World $world): bool {
	return property_exists($world, 'hello');
}

class Alpha
{
	public function __construct()
	{
		if (property_exists($this, 'data')) {
			$this->data = 'Hello';
		}
	}
}

class Beta extends Alpha
{
	/** @var string|null */
	public $data = null;
}

class Delta extends Alpha
{
}
