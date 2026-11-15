<?php // lint >= 8.0

// php-src tests of https://github.com/php/php-src/commit/a49acfa9a7aeaa44a0c49bea15058d97d9238960

namespace Bug15369HttpBuildQuery\Bug26817 {

	// ext/standard/tests/http/http_build_query/bug26817.phpt
	class test {
		protected $foo;
		private $bar;
		public $test;

		function foo()
		{
			$this->bar = 'meuh';
			$this->foo = 'lala';
			$this->test = 'test';

			var_dump(http_build_query($this));
		}
	}

	$obj = new test();
	$obj->foo();
	var_dump(http_build_query($obj));

}

namespace Bug15369HttpBuildQuery\ObjectBasic {

	// ext/standard/tests/http/http_build_query/http_build_query_object_basic.phpt
	class KeyVal {
		public $public = "input";
		protected $protected = "hello";
		private $private = "world";
	}

	$o = new KeyVal();

	// Percent encoded "public=input"
	var_dump(http_build_query($o));

}

namespace Bug15369HttpBuildQuery\ObjectEmpty {

	// ext/standard/tests/http/http_build_query/http_build_query_object_empty.phpt
	class EmptyObj {}
	$o = new EmptyObj();

	var_dump(http_build_query($o));

}

namespace Bug15369HttpBuildQuery\ObjectJustStringable {

	// ext/standard/tests/http/http_build_query/http_build_query_object_just_stringable.phpt
	class StringableObject {
		public function __toString() : string {
			return "Stringable";
		}
	}

	$o = new StringableObject();

	var_dump(http_build_query(['hello', $o]));
	var_dump(http_build_query($o));
	var_dump(http_build_query(['hello', $o], numeric_prefix: 'prefix_'));
	var_dump(http_build_query($o, numeric_prefix: 'prefix_'));

}

namespace Bug15369HttpBuildQuery\ObjectKeyValStringable {

	// ext/standard/tests/http/http_build_query/http_build_query_object_key_val_stringable.phpt
	class KeyValStringable {
		public $public = "input";
		protected $protected = "hello";
		private $private = "world";

		public function __toString(): string {
			return "Stringable";
		}
	}

	$o = new KeyValStringable();

	var_dump(http_build_query($o));

}

namespace Bug15369HttpBuildQuery\ObjectNested {

	// ext/standard/tests/http/http_build_query/http_build_query_object_nested.phpt
	class KeyVal {
		public $public = "input";
		protected $protected = "hello";
		private $private = "world";
	}

	$o = new KeyVal();
	$nested = new KeyVal();

	$o->public = $nested;

	// Percent encoded "public[public]=input"
	var_dump(http_build_query($o));

}

namespace Bug15369HttpBuildQuery\ObjectRecursif {

	// ext/standard/tests/http/http_build_query/http_build_query_object_recursif.phpt
	class KeyVal {
		public $public = "input";
		protected $protected = "hello";
		private $private = "world";
	}

	$o = new KeyVal();
	$o->public = $o;

	var_dump(http_build_query($o));

}

namespace Bug15369HttpBuildQuery\Variation1 {

	// ext/standard/tests/http/http_build_query/http_build_query_variation1.phpt
	class UrlBuilder
	{
		public $name = 'homepage';
		public $page = 1;
		protected $sort = 'desc,name';
		private $access = 'admin';
	}

	$obj = new \stdClass;
	$obj->name = 'homepage';
	$obj->page = 1;
	$obj->sort = 'desc,name';

	echo http_build_query($obj) . PHP_EOL;
	echo http_build_query(new UrlBuilder());

}
