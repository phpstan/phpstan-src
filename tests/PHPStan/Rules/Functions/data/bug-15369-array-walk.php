<?php

// php-src tests of https://github.com/php/php-src/commit/7e0d5c75e7e03f3a9abe488a3df0ab893e36c740
// String callbacks are namespaced, because PHP resolves them in the global namespace.

namespace Bug15369ArrayWalk\Gh16188 {

	// Zend/tests/exceptions/gh16188.phpt
	$re = new \TypeError();
	array_walk($re, function (&$item, $key) use (&$re) {
		if ($key === "\x00Error\x00previous") {
			$item = new \Exception();
		}
	});
	printf("getTraceAsString:\n%s\n\n", $re->getTraceAsString());

}

namespace Bug15369ArrayWalk\Gh9697 {

	// ext/ffi/tests/gh9697.phpt
	$x = \FFI::cdef()->new('int');
	array_walk($x, function($x) { echo "test\n"; });

}

namespace Bug15369ArrayWalk\ArrayWalkObject1 {

	// ext/standard/tests/array/array_walk/array_walk_object1.phpt
	function callback($value, $key, $user_data)
	{
		var_dump($key);
		var_dump($value);
		var_dump($user_data);
		echo "\n";
	}

	class MyClass
	{
		private $pri_value;
		public $pub_value;
		protected $pro_value;
		public function __construct($setVal)
		{
			$this->pri_value = $setVal;
			$this->pub_value = $setVal;
			$this->pro_value = $setVal;
		}
	};

	// object for 'input' argument
	$input = new MyClass(10);

	var_dump( array_walk($input, 'Bug15369ArrayWalk\ArrayWalkObject1\callback', 1));

}

namespace Bug15369ArrayWalk\ArrayWalkObjects {

	// ext/standard/tests/array/array_walk/array_walk_objects.phpt
	function walk($key, $value) {
		var_dump($value, $key);
	}

	class test {
		private $var_pri = "test_private";
		protected $var_pro = "test_protected";
		public $var_pub = "test_public";
	}

	$stdclass = new \stdclass;
	$stdclass->foo = "foo";
	$stdclass->bar = "bar";
	array_walk($stdclass, 'Bug15369ArrayWalk\ArrayWalkObjects\walk');

	$t = new test;
	array_walk($t, 'Bug15369ArrayWalk\ArrayWalkObjects\walk');

	$var = array();
	array_walk($var, 'Bug15369ArrayWalk\ArrayWalkObjects\walk');
	$var = "";
	try {
		array_walk($var, 'Bug15369ArrayWalk\ArrayWalkObjects\walk');
	} catch (\TypeError $e) {
		echo $e->getMessage(), "\n";
	}

}

namespace Bug15369ArrayWalk\ArrayWalkRecObjects {

	// ext/standard/tests/array/array_walk/array_walk_rec_objects.phpt
	function walk($key, $value) {
		var_dump($value, $key);
	}

	class test {
		private $var_pri = "test_private";
		protected $var_pro = "test_protected";
		public $var_pub = "test_public";
	}

	$stdclass = new \stdclass;
	$stdclass->foo = "foo";
	$stdclass->bar = "bar";
	array_walk_recursive($stdclass, 'Bug15369ArrayWalk\ArrayWalkRecObjects\walk');

	$t = new test;
	array_walk_recursive($t, 'Bug15369ArrayWalk\ArrayWalkRecObjects\walk');

	$var = array();
	array_walk_recursive($var, 'Bug15369ArrayWalk\ArrayWalkRecObjects\walk');
	$var = "";
	try {
		array_walk_recursive($var, 'Bug15369ArrayWalk\ArrayWalkRecObjects\walk');
	} catch (\TypeError $e) {
		echo $e->getMessage(), "\n";
	}

}

namespace Bug15369ArrayWalk\ArrayWalkRecursiveObject1 {

	// ext/standard/tests/array/array_walk/array_walk_recursive_object1.phpt
	function callback($value, $key, $user_data)
	{
		var_dump($key);
		var_dump($value);
		var_dump($user_data);
		echo "\n";
	}

	class MyClass
	{
		private $pri_value;
		public $pub_value;
		protected $pro_value;
		public function __construct($setVal)
		{
			$this->pri_value = $setVal;
			$this->pub_value = $setVal;
			$this->pro_value = $setVal;
		}
	};

	// object for 'input' argument
	$input = new MyClass(10);

	var_dump( array_walk_recursive($input, 'Bug15369ArrayWalk\ArrayWalkRecursiveObject1\callback', 1));

}

namespace Bug15369ArrayWalk\Bug79839 {

	// ext/standard/tests/array/array_walk/bug79839.phpt
	class Test {
		public int $prop = 42;
	}

	$test = new Test;
	try {
		array_walk($test, function(&$ref) {
			$ref = []; // Should throw
		});
	} catch (\TypeError $e) {
		echo $e->getMessage(), "\n";
	}
	var_dump($test);

}
