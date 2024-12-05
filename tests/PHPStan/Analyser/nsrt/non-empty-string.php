<?php

namespace NonEmptyString;

use function htmlspecialchars;
use function lcfirst;
use function PHPStan\Testing\assertType;
use function strtolower;
use function strtoupper;
use function ucfirst;
use const ENT_SUBSTITUTE;

class Foo
{

	public function doFoo(string $s): void
	{
		if ($s === '') {
			return;
		}

		assertType('non-empty-string', $s);
	}

	public function doBar(string $s): void
	{
		if ($s !== '') {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	public function doFoo2(string $s): void
	{
		if (strlen($s) === 0) {
			return;
		}

		assertType('non-empty-string', $s);
	}

	public function doBar2(string $s): void
	{
		if (strlen($s) > 0) {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	public function doBar3(string $s): void
	{
		if (strlen($s) >= 1) {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	public function doFoo5(string $s): void
	{
		if (0 === strlen($s)) {
			return;
		}

		assertType('non-empty-string', $s);
	}

	public function doBar4(string $s): void
	{
		if (0 < strlen($s)) {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	public function doBar5(string $s): void
	{
		if (1 <= strlen($s)) {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	/**
	 * @param literal-string $s
	 */
	public function doBar6($s): void
	{
		if (1 === strlen($s)) {
			assertType('literal-string&non-empty-string', $s);
			return;
		}
		assertType('literal-string', $s);
	}

	/**
	 * @param literal-string $s
	 */
	public function doBar7($s): void
	{
		if (0 < strlen($s)) {
			assertType('literal-string&non-empty-string', $s);
			return;
		}
		assertType("''", $s);
	}

	public function doFoo3(string $s): void
	{
		if ($s) {
			assertType('non-falsy-string', $s);
		} else {
			assertType('\'\'|\'0\'', $s);
		}
	}

	/**
	 * @param non-empty-string $s
	 */
	public function doFoo4(string $s): void
	{
		assertType('non-empty-list<lowercase-string>', explode($s, 'foo'));
	}

	/**
	 * @param non-empty-string $s
	 */
	public function doWithNumeric(string $s): void
	{
		if (!is_numeric($s)) {
			return;
		}

		assertType('non-empty-string&numeric-string', $s);
	}

	public function doEmpty(string $s): void
	{
		if (empty($s)) {
			return;
		}

		assertType('non-falsy-string', $s);
	}

	public function doEmpty2(string $s): void
	{
		if (!empty($s)) {
			assertType('non-falsy-string', $s);
		}
	}

}

class ImplodingStrings
{

	/**
	 * @param array<string> $commonStrings
	 */
	public function doFoo(string $s, array $commonStrings): void
	{
		assertType('string', implode($s, $commonStrings));
		assertType('string', implode(' ', $commonStrings));
		assertType('string', implode('', $commonStrings));
		assertType('string', implode($commonStrings));
	}

	/**
	 * @param non-empty-array<string> $nonEmptyArrayWithStrings
	 */
	public function doFoo2(string $s, array $nonEmptyArrayWithStrings): void
	{
		assertType('string', implode($s, $nonEmptyArrayWithStrings));
		assertType('string', implode('', $nonEmptyArrayWithStrings));
		assertType('non-falsy-string', implode(' ', $nonEmptyArrayWithStrings));
		assertType('string', implode($nonEmptyArrayWithStrings));
	}

	/**
	 * @param array<non-empty-string> $arrayWithNonEmptyStrings
	 */
	public function doFoo3(string $s, array $arrayWithNonEmptyStrings): void
	{
		assertType('string', implode($s, $arrayWithNonEmptyStrings));
		assertType('string', implode('', $arrayWithNonEmptyStrings));
		assertType('string', implode(' ', $arrayWithNonEmptyStrings));
		assertType('string', implode($arrayWithNonEmptyStrings));
	}

	/**
	 * @param non-empty-array<non-empty-string> $nonEmptyArrayWithNonEmptyStrings
	 */
	public function doFoo4(string $s, array $nonEmptyArrayWithNonEmptyStrings): void
	{
		assertType('non-empty-string', implode($s, $nonEmptyArrayWithNonEmptyStrings));
		assertType('non-empty-string', implode('', $nonEmptyArrayWithNonEmptyStrings));
		assertType('non-falsy-string', implode(' ', $nonEmptyArrayWithNonEmptyStrings));
		assertType('non-empty-string', implode($nonEmptyArrayWithNonEmptyStrings));
	}

	public function sayHello(int $i): void
	{
		// coming from issue #5291
		$s = array(1, $i);

		assertType('non-falsy-string', implode("a", $s));
	}

	/**
	 * @param non-empty-string $glue
	 */
	public function nonE($glue, array $a)
	{
		// coming from issue #5291
		if (empty($a)) {
			return "xyz";
		}

		assertType('non-empty-string', implode($glue, $a));
	}

	public function sayHello2(int $i): void
	{
		// coming from issue #5291
		$s = array(1, $i);

		assertType('non-falsy-string', join("a", $s));
	}

	/**
	 * @param non-empty-string $glue
	 */
	public function nonE2($glue, array $a)
	{
		// coming from issue #5291
		if (empty($a)) {
			return "xyz";
		}

		assertType('non-empty-string', join($glue, $a));
	}

}

class LiteralString
{

	function x(string $tableName, string $original): void
	{
		assertType('non-falsy-string', "from `$tableName`");
	}

	/**
	 * @param non-empty-string $nonEmpty
	 */
	function concat(string $s, string $nonEmpty): void
	{
		assertType('string', $s . '');
		assertType('non-empty-string', $nonEmpty . '');
		assertType('non-empty-string', $nonEmpty . $s);
	}

}

class GeneralizeConstantStringType
{

	/**
	 * @param array<non-empty-string, int> $a
	 * @param non-empty-string $s
	 */
	public function doFoo(array $a, string $s): void
	{
		$a[$s] = 2;

		// there might be non-empty-string that becomes a number instead
		assertType('non-empty-array<non-empty-string, int>', $a);
	}

	/**
	 * @param array<non-empty-string, int> $a
	 * @param non-empty-string $s
	 */
	public function doFoo2(array $a, string $s): void
	{
		$a[''] = 2;
		assertType('non-empty-array<string, int>&hasOffsetValue(\'\', 2)', $a);
	}

}

class MoreNonEmptyStringFunctions
{

	/**
	 * @param non-empty-string $nonEmpty
	 * @param non-falsy-string $nonFalsy
	 * @param '1'|'2'|'5'|'10' $constUnion
	 */
	public function doFoo(string $s, string $nonEmpty, string $nonFalsy, int $i, bool $bool, $constUnion)
	{
		assertType('string', addslashes($s));
		assertType('non-empty-string', addslashes($nonEmpty));
		assertType('string', addcslashes($s));
		assertType('non-empty-string', addcslashes($nonEmpty));

		assertType('string', escapeshellarg($s));
		assertType('non-empty-string', escapeshellarg($nonEmpty));
		assertType('string', escapeshellcmd($s));
		assertType('non-empty-string', escapeshellcmd($nonEmpty));

		assertType('uppercase-string', strtoupper($s));
		assertType('non-empty-string&uppercase-string', strtoupper($nonEmpty));
		assertType('lowercase-string', strtolower($s));
		assertType('lowercase-string&non-empty-string', strtolower($nonEmpty));
		assertType('uppercase-string', mb_strtoupper($s));
		assertType('non-empty-string&uppercase-string', mb_strtoupper($nonEmpty));
		assertType('lowercase-string', mb_strtolower($s));
		assertType('lowercase-string&non-empty-string', mb_strtolower($nonEmpty));
		assertType('string', lcfirst($s));
		assertType('non-empty-string', lcfirst($nonEmpty));
		assertType('string', ucfirst($s));
		assertType('non-empty-string', ucfirst($nonEmpty));
		assertType('string', ucwords($s));
		assertType('non-empty-string', ucwords($nonEmpty));
		assertType('string', htmlspecialchars($s));
		assertType('string', htmlspecialchars($s, ENT_SUBSTITUTE));
		assertType('string', htmlspecialchars($s, 0));
		assertType('non-empty-string', htmlspecialchars($nonEmpty));
		assertType('non-empty-string', htmlspecialchars($nonEmpty, ENT_SUBSTITUTE));
		assertType('string', htmlspecialchars($nonEmpty, 0));
		assertType('string', htmlentities($s));
		assertType('string', htmlentities($s, ENT_SUBSTITUTE));
		assertType('string', htmlentities($s, 0));
		assertType('non-empty-string', htmlentities($nonEmpty));
		assertType('non-empty-string', htmlentities($nonEmpty, ENT_SUBSTITUTE));
		assertType('string', htmlentities($nonEmpty, 0));

		assertType('string', urlencode($s));
		assertType('non-empty-string', urlencode($nonEmpty));
		assertType('string', urldecode($s));
		assertType('non-empty-string', urldecode($nonEmpty));
		assertType('string', rawurlencode($s));
		assertType('non-empty-string', rawurlencode($nonEmpty));
		assertType('string', rawurldecode($s));
		assertType('non-empty-string', rawurldecode($nonEmpty));

		assertType('string', preg_quote($s));
		assertType('non-empty-string', preg_quote($nonEmpty));

		assertType('string', sprintf($s));
		assertType('string', sprintf($nonEmpty));
		assertType('string', sprintf($s, $nonEmpty));
		assertType('string', sprintf($nonEmpty, $s));
		assertType('string', sprintf($s, $nonFalsy));
		assertType('string', sprintf($nonFalsy, $s));
		assertType('non-empty-string', sprintf($nonEmpty, $nonEmpty));
		assertType('non-empty-string', sprintf($nonEmpty, $nonEmpty, $nonEmpty));
		assertType('non-empty-string', sprintf($nonEmpty, $nonFalsy, $nonFalsy));
		assertType('non-empty-string', sprintf($nonFalsy, $nonEmpty));
		assertType('non-empty-string', sprintf($nonFalsy, $nonEmpty, $nonEmpty));
		assertType('non-empty-string', sprintf($nonFalsy, $nonFalsy, $nonEmpty));
		assertType('non-empty-string', sprintf($nonFalsy, $nonFalsy, $nonFalsy));
		assertType('string', vsprintf($s, []));
		assertType('string', vsprintf($nonEmpty, []));
		assertType('non-empty-string', vsprintf($nonEmpty, [$nonEmpty]));
		assertType('non-empty-string', vsprintf($nonEmpty, [$nonEmpty, $nonEmpty]));
		assertType('non-empty-string', vsprintf($nonEmpty, [$nonFalsy, $nonFalsy]));
		assertType('non-empty-string', vsprintf($nonFalsy, [$nonEmpty]));
		assertType('non-empty-string', vsprintf($nonFalsy, [$nonEmpty, $nonEmpty]));
		assertType('non-empty-string', vsprintf($nonFalsy, [$nonFalsy, $nonEmpty]));
		assertType('non-empty-string', vsprintf($nonFalsy, [$nonFalsy, $nonFalsy]));

		assertType('non-empty-string', sprintf("%s0%s", $s, $s));
		assertType('non-empty-string', sprintf("%s0%s%s%s%s", $s, $s, $s, $s, $s));
		assertType('non-empty-string', sprintf("%s0%s%s%s%s%s", $s, $s, $s, $s, $s, $s));

		assertType('0', strlen(''));
		assertType('5', strlen('hallo'));
		assertType('int<0, 1>', strlen($bool));
		assertType('int<1, max>', strlen($i));
		assertType('int<0, max>', strlen($s));
		assertType('int<1, max>', strlen($nonEmpty));
		assertType('int<1, 2>', strlen($constUnion));

		assertType('non-empty-string', str_pad($nonEmpty, 0));
		assertType('non-empty-string', str_pad($nonEmpty, 1));
		assertType('string', str_pad($s, 0));
		assertType('non-empty-string', str_pad($s, 1));

		assertType('non-empty-string', str_repeat($nonEmpty, 1));
		assertType('\'\'', str_repeat($nonEmpty, 0));
		assertType('string', str_repeat($nonEmpty, $i));
		assertType('\'\'', str_repeat($s, 0));
		assertType('string', str_repeat($s, 1));
		assertType('string', str_repeat($s, $i));
	}

	function multiplesPrintfFormats(string $s) {
		$maybeNonEmpty = '%s';
		$maybeNonFalsy = '%s';
		$nonEmpty = '%s0';
		$nonFalsy = '%sAA';

		if (rand(0,1)) {
			$maybeNonEmpty = '%s0';
			$maybeNonFalsy = '%sAA';
			$nonEmpty = '0%s';
			$nonFalsy = 'AA%s';
		}

		assertType('string', sprintf($maybeNonEmpty, $s));
		assertType('string', sprintf($maybeNonFalsy, $s));
		assertType('non-empty-string', sprintf($nonEmpty, $s));
		assertType('non-falsy-string', sprintf($nonFalsy, $s));
	}

	function subtract($m) {
		if ($m) {
			assertType("mixed~(0|0.0|''|'0'|array{}|false|null)", $m);
			assertType('non-falsy-string', (string) $m);
		}
		if ($m != '') {
			assertType("mixed~(''|false|null)", $m);
			assertType('non-empty-string', (string) $m);
		}
		if ($m !== '') {
			assertType("mixed~''", $m);
			assertType('string', (string) $m);
		}
		if (!is_string($m)) {
			assertType("mixed~string", $m);
			assertType('string', (string) $m);
		}

		if ($m !== true) {
			assertType("mixed~true", $m);
			assertType('string', (string) $m);
		}
		if ($m !== false) {
			assertType("mixed~false", $m);
			assertType('string', (string) $m);
		}
		if ($m !== false && $m !== '' && $m !== null) {
			assertType("mixed~(''|false|null)", $m);
			assertType('non-empty-string', (string) $m);
		}
		if (!is_bool($m) && $m !== '' && $m !== null) {
			assertType("mixed~(''|bool|null)", $m);
			assertType('non-empty-string', (string) $m);
		}
	}
}
