<?php declare(strict_types = 1);

namespace Bug14102;

abstract class HelloWorld
{
	public int $c;

	public function __construct(int $a, int $b){
		$this->c = $a + $b;
	}
}

class ChildWorld extends HelloWorld
{}

class HelloWorldFactory
{
	/**
	* @param class-string<HelloWorld> $className
	*/
	public function create(string $className): HelloWorld
	{
		return new $className();
	}
}

$a = (new HelloWorldFactory())->create(ChildWorld::class);
