<?php // onlyif PHP_VERSION_ID >= 70300

namespace StrCasingReturnType;

use function PHPStan\Testing\assertType;

class Foo {
	/**
	 * @param numeric-string $numericS
	 * @param non-empty-string $nonE
	 * @param literal-string $literal
	 * @param 'foo'|'Foo' $edgeUnion
	 * @param MB_CASE_UPPER|MB_CASE_LOWER|MB_CASE_TITLE|MB_CASE_FOLD|MB_CASE_UPPER_SIMPLE|MB_CASE_LOWER_SIMPLE|MB_CASE_TITLE_SIMPLE|MB_CASE_FOLD_SIMPLE $caseMode
	 * @param 'aKV'|'hA'|'AH'|'K'|'KV'|'RNKV' $kanaMode
	 * @param mixed $mixed
	 */
	public function bar($numericS, $nonE, $literal, $edgeUnion, $caseMode, $kanaMode, $mixed) {
		assertType("'abc'", strtolower('ABC'));
		assertType("'ABC'", strtoupper('abc'));
		assertType("'abc'", mb_strtolower('ABC'));
		assertType("'ABC'", mb_strtoupper('abc'));
		assertType("'abc'", mb_strtolower('ABC', 'UTF-8'));
		assertType("'ABC'", mb_strtoupper('abc', 'UTF-8'));
		assertType("'ａｂｃ'", mb_strtolower('Ａｂｃ'));
		assertType("'ＡＢＣ'", mb_strtoupper('Ａｂｃ'));
		assertType("'aBC'", lcfirst('ABC'));
		assertType("'Abc'", ucfirst('abc'));
		assertType("'Hello World'", ucwords('hello world'));
		assertType("'Hello|World'", ucwords('hello|world', "|"));
		assertType("'ČESKÁ REPUBLIKA'", mb_convert_case('Česká republika', MB_CASE_UPPER));
		assertType("'česká republika'", mb_convert_case('Česká republika', MB_CASE_LOWER));
		assertType("non-falsy-string", mb_convert_case('Česká republika', $mixed));
		assertType("'ČESKÁ REPUBLIKA'|'Česká Republika'|'česká republika'", mb_convert_case('Česká republika', $caseMode));
		assertType("'Ａｂｃ１２３アイウガギグばびぶ漢字'", mb_convert_kana('Ａｂｃ１２３ｱｲｳｶﾞｷﾞｸﾞばびぶ漢字'));
		assertType("'Abc123アイウガギグばびぶ漢字'", mb_convert_kana('Ａｂｃ１２３ｱｲｳｶﾞｷﾞｸﾞばびぶ漢字', 'aKV'));
		assertType("'Ａｂｃ１２３ｱｲｳｶﾞｷﾞｸﾞﾊﾞﾋﾞﾌﾞ漢字'", mb_convert_kana('Ａｂｃ１２３ｱｲｳｶﾞｷﾞｸﾞばびぶ漢字', 'hA'));
		assertType("'Abc123アガば漢'|'Ａｂｃ１２３あか゛ば漢'|'Ａｂｃ１２３アカ゛ば漢'|'Ａｂｃ１２３アガば漢'|'Ａｂｃ１２３ｱｶﾞﾊﾞ漢'", mb_convert_kana('Ａｂｃ１２３ｱｶﾞば漢', $kanaMode));
		assertType("non-falsy-string", mb_convert_kana('Ａｂｃ１２３ｱｶﾞば漢', $mixed));

		assertType("numeric-string", strtolower($numericS));
		assertType("numeric-string", strtoupper($numericS));
		assertType("numeric-string", mb_strtolower($numericS));
		assertType("numeric-string", mb_strtoupper($numericS));
		assertType("numeric-string", lcfirst($numericS));
		assertType("numeric-string", ucfirst($numericS));
		assertType("numeric-string", ucwords($numericS));
		assertType("numeric-string", mb_convert_case($numericS, MB_CASE_UPPER));
		assertType("numeric-string", mb_convert_case($numericS, MB_CASE_LOWER));
		assertType("numeric-string", mb_convert_case($numericS, $mixed));
		assertType("numeric-string", mb_convert_kana($numericS));
		assertType("numeric-string", mb_convert_kana($numericS, $mixed));

		assertType("non-empty-string", strtolower($nonE));
		assertType("non-empty-string", strtoupper($nonE));
		assertType("non-empty-string", mb_strtolower($nonE));
		assertType("non-empty-string", mb_strtoupper($nonE));
		assertType("non-empty-string", lcfirst($nonE));
		assertType("non-empty-string", ucfirst($nonE));
		assertType("non-empty-string", ucwords($nonE));
		assertType("non-empty-string", mb_convert_case($nonE, MB_CASE_UPPER));
		assertType("non-empty-string", mb_convert_case($nonE, MB_CASE_LOWER));
		assertType("non-empty-string", mb_convert_case($nonE, $mixed));
		assertType("non-empty-string", mb_convert_kana($nonE));
		assertType("non-empty-string", mb_convert_kana($nonE, $mixed));

		assertType("string", strtolower($literal));
		assertType("string", strtoupper($literal));
		assertType("string", mb_strtolower($literal));
		assertType("string", mb_strtoupper($literal));
		assertType("string", lcfirst($literal));
		assertType("string", ucfirst($literal));
		assertType("string", ucwords($literal));
		assertType("string", mb_convert_case($literal, MB_CASE_UPPER));
		assertType("string", mb_convert_case($literal, MB_CASE_LOWER));
		assertType("string", mb_convert_case($literal, $mixed));
		assertType("string", mb_convert_kana($literal));
		assertType("string", mb_convert_kana($literal, $mixed));

		assertType("'foo'", lcfirst($edgeUnion));
	}

	public function foo() {
		// invalid char conversions still lead to non-falsy-string
		assertType("non-falsy-string", mb_strtolower("\xfe\xff\x65\xe5\x67\x2c\x8a\x9e", 'CP1252'));
		// valid char sequence, but not support non ASCII / UTF-8 encodings
		assertType("non-falsy-string", mb_convert_kana("\x95\x5c\x8c\xbb", 'SJIS-win'));
		// invalid UTF-8 sequence
		assertType("non-falsy-string", mb_convert_kana("\x95\x5c\x8c\xbb", 'UTF-8'));
	}
}
