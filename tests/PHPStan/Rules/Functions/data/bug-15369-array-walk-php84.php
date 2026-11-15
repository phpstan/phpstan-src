<?php // lint >= 8.4

// php-src tests of https://github.com/php/php-src/commit/7e0d5c75e7e03f3a9abe488a3df0ab893e36c740 that need PHP 8.4

namespace Bug15369ArrayWalkPhp84\LazyObjects {

	// Zend/tests/lazy_objects/array_walk.phpt
	class C {
		public int $a = 1;
	}

	$reflector = new \ReflectionClass(C::class);
	$obj = $reflector->newLazyProxy(function () {
		return new C();
	});

	array_walk($obj, function (&$value, $key) {
		try {
			$value = 'string';
		} catch (\Error $e) {
			printf("%s: %s\n", $e::class, $e->getMessage());
		}
		$value = 2;
	});

	var_dump($obj);

}

namespace Bug15369ArrayWalkPhp84\Gh18268 {

	// Zend/tests/property_hooks/gh18268.phpt
	class A {
		public $prop = 42;
	}

	class B extends A {
		public $prop = 42 {
			set {}
		}
	}

	$b = new B;
	array_walk($b, function (&$item) {
		var_dump($item);
	});

}
