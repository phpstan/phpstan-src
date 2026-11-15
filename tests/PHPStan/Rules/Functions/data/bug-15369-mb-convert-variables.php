<?php // lint >= 8.0

// php-src tests of https://github.com/php/php-src/commit/7cc143235d2be7b989348d37f465860835833a25

namespace Bug15369MbConvertVariables\Gh16261 {

	// ext/mbstring/tests/gh16261.phpt
	class Test {
		public string $x;
		public string $y;
		public array $z;
	}
	$test = new Test;
	$ref = "hello";
	$ref2 = "world";
	$ref3 = [&$ref2];
	$test->x =& $ref;
	$test->z =& $ref3;
	mb_convert_variables("EUC-JP", "Shift_JIS", $test);

	class Test2 {
		public function __construct(public string $x) {}
	}
	$test2 = new Test2("foo");

	mb_convert_variables("EUC-JP", "Shift_JIS", $test->x);

	var_dump($test, $test2);

}

namespace Bug15369MbConvertVariables\MbConvertVariables {

	// ext/mbstring/tests/mb_convert_variables.phpt
	$euc_jp = mb_convert_encoding("日本語テキストです。01234５６７８９。", 'EUC-JP', 'UTF-8');

	// Test for single scalar
	$s = $euc_jp;
	$encoding = mb_convert_variables('SJIS', 'EUC-JP', $s);

	// Test for multiple scalar
	$s1 = $euc_jp;
	$s2 = $euc_jp;
	$s3 = $euc_jp;
	$encoding = mb_convert_variables('EUC-JP', 'auto', $s1, $s2, $s3);

	// Note: Mixing encoding in array/object is not supported
	$a = array($s3, $s2, $s1);
	$aa = $a;
	$encoding = mb_convert_variables('EUC-JP', 'auto', $aa);

	// Test for object
	class foo
	{
		public $s1;
		public $s2;
		public $s3;

		function __construct()
		{
			global $sjis, $jis, $euc_jp;

			$this->s1 = $euc_jp;
			$this->s2 = $euc_jp;
			$this->s3 = $euc_jp;
		}
	}

	$o = new foo;
	$oo = $o;
	$encoding = mb_convert_variables('EUC-JP', 'auto', $oo);

	// Test for scalar, array and object
	$s1 = $euc_jp;
	$s2 = $euc_jp;
	$s3 = $euc_jp;
	$a = array($s1, $s2, $s3);
	$aa = $a;
	$oo = $o;

	$encoding = mb_convert_variables('EUC-JP', 'auto', $s1, $s2, $s3, $aa, $oo);

	class Nested
	{
		public $inner;

		function __construct($value)
		{
			$this->inner = $value;
		}
	}

	$deeplyNested = array(new Nested(array(new Nested(array(new Nested("BLAH"))))));

	$encoding = mb_convert_variables('UTF-16LE', 'UTF-8', $deeplyNested);

	$bad_utf7 = "abc + abc";
	var_dump(mb_convert_variables('UTF-8', 'UTF-7,UTF-8', $bad_utf7));

}

namespace Bug15369MbConvertVariables\InvalidTypes {

	// ext/mbstring/tests/mb_convert_variables_invalid_types.phpt
	$i = 5;
	$a = [$i];

	try {
		var_dump( mb_convert_variables('UTF-8', 'EUC-JP', $i, $a));
	} catch (\ValueError $e) {
		echo $e::class, ': ', $e->getMessage(), PHP_EOL;
	}
	try {
		var_dump( mb_convert_variables('UTF-8', 'EUC-JP', $a));
	} catch (\ValueError $e) {
		echo $e::class, ': ', $e->getMessage(), PHP_EOL;
	}

}
