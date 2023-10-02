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

class TestClass2 {
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
	public function testNew() {
		(new TestClass)();
	}
	/**
	 * @param TestClass|TestClass2 $class
	 *
	 * @throws \Exception
	 **/
	public function testUnion(object $class) {
		$class();
	}
}
