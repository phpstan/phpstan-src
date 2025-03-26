<?php

namespace Bug12499;

class HelloWorld {
	public static string $testString = 'test';
}

$property = uopz_get_property(HelloWorld::class, 'testString');

uopz_set_property(HelloWorld::class, 'testString', 'test2');

class HelloWorld2 {
	public function __construct(
		public string $testString,
	) {}
}

$classInstance = new HelloWorld2('test');

$property = uopz_get_property($classInstance, 'testString');

uopz_set_property($classInstance, 'testString', 'test2');
