<?php

namespace Bug6233;

class TestClass {
	/**
	 * @throws \Exception
	 **/
	public function __invoke() {
		throw new \Exception();
	}
}

class Container {
	/**
	 * @throws \Exception
	 **/
	public function test(TestClass $class) {
		$class();
	}
	/**
	 * @throws \Exception
	 **/
	public function test2() {
		(new TestClass)();
	}
}
