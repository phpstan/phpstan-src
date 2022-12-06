<?php

namespace ConstantStringUnions;

use function PHPStan\Testing\assertType;

// See https://github.com/phpstan/phpstan/issues/6439
class HelloWorld
{
	public function unionOnLeft(string $name, ?int $gesperrt = null, ?int $adaid = null):void
	{
		$string = 'general';
		if (null !== $gesperrt) {
			$string = $string . ' branch-a';
		}
		assertType("'general'|'general branch-a'", $string);
		if (null !== $adaid) {
			$string = $string . ' branch-b';
		}
		assertType("'general'|'general branch-a'|'general branch-a branch-b'|'general branch-b'", $string);
	}

	public function unionOnRight(string $name, ?int $gesperrt = null, ?int $adaid = null):void
	{
		$string = 'general';
		if (null !== $gesperrt) {
			$string = 'branch-a ' . $string;
		}
		assertType("'branch-a general'|'general'", $string);
		if (null !== $adaid) {
			$string = 'branch-b ' . $string;
		}
		assertType("'branch-a general'|'branch-b branch-a general'|'branch-b general'|'general'", $string);
	}

	public function unionOnBoth():void
	{
		$left = rand() ? 'a' : 'b';
		$right = rand() ? 'x' : 'y';
		assertType("'ax'|'ay'|'bx'|'by'", $left . $right);
	}

	public function encapsedString():void
	{
		$str = rand() ? 'a' : 'b';
		$int = rand() ? 1 : 0;
		$float = rand() ? 1.0 : 2.0;
		$bool = (bool) rand();
		$nullable = rand() ? 'a' : null;
		assertType("'.a.'|'.b.'", ".$str.");
		assertType("'.0.'|'.1.'", ".$int.");
		assertType("'.1.'|'.2.'", ".$float.");
		assertType("'..'|'.1.'", ".$bool.");
		assertType("'..'|'.a.'", ".$nullable.");
		assertType("'.a.0.'|'.a.1.'|'.b.0.'|'.b.1.'", ".$str.$int.");
	}

	/**
	 * @param '1'|'2'|'3'|'4'|'5'|'6'|'7'|'8'|'9'|'10'|'11'|'12'|'13'|'14'|'15' $s15
	 * @param '1'|'2'|'3'|'4'|'5'|'6'|'7'|'8'|'9'|'10'|'11'|'12'|'13'|'14'|'15'|'16' $s16
	 * @param '1'|'2'|'3'|'4'|'5'|'6'|'7'|'8'|'9'|'10'|'11'|'12'|'13'|'14'|'15'|'16'|'17' $s17
	 */
	public function testLimit(string $s15, string $s16, string $s17) {
		if (rand(0,1)) {
			// doubles the number of elements
			$s15 .= 'a';
			$s16 .= 'a';
			$s17 .= 'a';
		}
		// union should contain 30 elements
		assertType("'1'|'10'|'10a'|'11'|'11a'|'12'|'12a'|'13'|'13a'|'14'|'14a'|'15'|'15a'|'1a'|'2'|'2a'|'3'|'3a'|'4'|'4a'|'5'|'5a'|'6'|'6a'|'7'|'7a'|'8'|'8a'|'9'|'9a'", $s15);
		// union should contain 32 elements
		assertType("'1'|'10'|'10a'|'11'|'11a'|'12'|'12a'|'13'|'13a'|'14'|'14a'|'15'|'15a'|'16'|'16a'|'1a'|'2'|'2a'|'3'|'3a'|'4'|'4a'|'5'|'5a'|'6'|'6a'|'7'|'7a'|'8'|'8a'|'9'|'9a'", $s16);
		// fallback to the more general form
		assertType("literal-string&non-falsy-string", $s17);
		$left = rand() ? 'a' : 'b';
		$right = rand() ? 'x' : 'y';
		$left .= $right;
		$left .= $right;
		$left .= $right;
		assertType("'axxx'|'axxy'|'axyx'|'axyy'|'ayxx'|'ayxy'|'ayyx'|'ayyy'|'bxxx'|'bxxy'|'bxyx'|'bxyy'|'byxx'|'byxy'|'byyx'|'byyy'", $left);
		$left .= $right;
		assertType("literal-string&non-falsy-string", $left);

		$left = rand() ? 'a' : 'b';
		$right = rand() ? 'x' : 'y';
		$left = "{$left}{$right}";
		$left = "{$left}{$right}";
		$left = "{$left}{$right}";
		assertType("'axxx'|'axxy'|'axyx'|'axyy'|'ayxx'|'ayxy'|'ayyx'|'ayyy'|'bxxx'|'bxxy'|'bxyx'|'bxyy'|'byxx'|'byxy'|'byyx'|'byyy'", $left);
		$left = "{$left}{$right}";
		assertType("literal-string&non-falsy-string", $left);
	}

	/**
	 * @param '1'|'2' $s2
	 */
	public function appendEmpty($s2) {
		if (rand(0,1)) {
			$s2 .= '';
		}
		assertType("'1'|'2'", $s2);

		if (rand(0,1)) {
			$s2 = '';
		}
		assertType("''|'1'|'2'", $s2);
	}

    public function concatCase() {
		$extra = '';
		if (rand(0,1)) {
			$extra = '[0-9]';
		}

		assertType("''|'[0-9]'", $extra);

		$regex = '~[A-Z]' . $extra . '~';
		assertType("'~[A-Z][0-9]~'|'~[A-Z]~'", $regex);
	}
}
