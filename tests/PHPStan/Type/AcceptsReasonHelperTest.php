<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AcceptsReasonHelperTest extends TestCase
{

	public static function dataGroupReasons(): iterable
	{
		yield 'no offset reasons' => [
			['Type #1 from the union: foo.', 'Array does not have offset \'a\'.'],
			['Type #1 from the union: foo.', 'Array does not have offset \'a\'.'],
		];

		yield 'single offset reason is unchanged' => [
			["Offset 'a' › 0 (int): reason."],
			["Offset 'a' › 0 (int): reason."],
		];

		yield 'two offsets' => [
			["Offset 'a' › 0 (int): reason.", "Offset 'b' › 1 (int): reason."],
			["Offsets 'a' › 0 and 'b' › 1 (int): reason."],
		];

		yield 'more offsets than listed' => [
			['Offset 0 (int): reason.', 'Offset 1 (int): reason.', 'Offset 2 (int): reason.', 'Offset 3 (int): reason.', 'Offset 4 (int): reason.'],
			['Offsets 0, 1, 2 and 2 more (int): reason.'],
		];

		yield 'groups keep the order of first appearance' => [
			['Offset 0 (int): a.', 'Unrelated.', 'Offset 1 (int): b.', 'Offset 2 (int): a.'],
			['Offsets 0 and 2 (int): a.', 'Unrelated.', 'Offset 1 (int): b.'],
		];

		yield 'quoted keys with escapes, separators and parentheses' => [
			["Offset 'it\\'s › here' › 'x (y)' (int): reason.", 'Offset "a\\nb" (int): reason.', 'Offset -1 (int): reason.'],
			["Offsets 'it\\'s › here' › 'x (y)', \"a\\nb\" and -1 (int): reason."],
		];

		yield 'leaf form without a nested reason' => [
			["Offset 'a' (int) does not accept type string.", "Offset 'b' (int) does not accept type string."],
			["Offsets 'a' and 'b' (int) does not accept type string."],
		];

		yield 'isSuperTypeOf() form' => [
			["Offset 'a': reason.", "Offset 'b': reason."],
			["Offsets 'a' and 'b': reason."],
		];

		yield 'not an offset path' => [
			['Offset foo is weird.', 'Offset foo is weird.'],
			['Offset foo is weird.', 'Offset foo is weird.'],
		];
	}

	/**
	 * @param list<string> $reasons
	 * @param list<string> $expected
	 */
	#[DataProvider('dataGroupReasons')]
	public function testGroupReasons(array $reasons, array $expected): void
	{
		$this->assertSame($expected, AcceptsReasonHelper::groupReasons($reasons));
	}

	public function testForTip(): void
	{
		$this->assertSame(
			['Offsets 0 and 1 (int): a.', 'b0.', 'b1.', 'b2.', 'b3.', '2 more reasons not shown.'],
			AcceptsReasonHelper::forTip(['Offset 0 (int): a.', 'b0.', 'Offset 1 (int): a.', 'b1.', 'b2.', 'b3.', 'b4.', 'b5.']),
		);
		$this->assertSame(['a.', 'b.'], AcceptsReasonHelper::forTip(['a.', 'b.']));
	}

	public function testMissingProperties(): void
	{
		$this->assertSame('Foo does not have property $a.', AcceptsReasonHelper::missingProperties('Foo', ['a'], true));
		$this->assertSame('Foo might not have properties $a and $b.', AcceptsReasonHelper::missingProperties('Foo', ['a', 'b'], false));
		$this->assertSame('Foo does not have properties $a, $b and $c.', AcceptsReasonHelper::missingProperties('Foo', ['a', 'b', 'c'], true));
		$this->assertSame('Foo does not have properties $a, $b, $c and 2 more.', AcceptsReasonHelper::missingProperties('Foo', ['a', 'b', 'c', 'd', 'e'], true));
	}

}
