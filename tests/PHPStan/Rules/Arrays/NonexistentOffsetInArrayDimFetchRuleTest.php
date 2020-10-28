<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<NonexistentOffsetInArrayDimFetchRule>
 */
class NonexistentOffsetInArrayDimFetchRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new NonexistentOffsetInArrayDimFetchRule(
			new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false),
			true
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/nonexistent-offset.php'], [
			[
				'Offset \'b\' does not exist on array(\'a\' => stdClass, 0 => 2).',
				17,
			],
			[
				'Offset 1 does not exist on array(\'a\' => stdClass, 0 => 2).',
				18,
			],
			[
				'Offset \'a\' does not exist on array(\'b\' => 1).',
				55,
			],
			[
				'Access to offset \'bar\' on an unknown class NonexistentOffset\Bar.',
				101,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Access to an offset on an unknown class NonexistentOffset\Bar.',
				102,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Offset 0 does not exist on array<string, string>.',
				111,
			],
			[
				'Offset \'0\' does not exist on array<string, string>.',
				112,
			],
			[
				'Offset int does not exist on array<string, string>.',
				114,
			],
			[
				'Offset \'test\' does not exist on null.',
				126,
			],
			[
				'Cannot access offset 42 on int.',
				142,
			],
			[
				'Cannot access offset 42 on float.',
				143,
			],
			[
				'Cannot access offset 42 on bool.',
				144,
			],
			[
				'Cannot access offset 42 on resource.',
				145,
			],
			[
				'Offset \'c\' does not exist on array(\'c\' => bool)|array(\'e\' => true).',
				171,
			],
			[
				'Offset int does not exist on array()|array(1 => 1, 2 => 2)|array(3 => 3, 4 => 4).',
				190,
			],
			[
				'Offset int does not exist on array()|array(1 => 1, 2 => 2)|array(3 => 3, 4 => 4).',
				193,
			],
			[
				'Offset \'b\' does not exist on array(\'a\' => \'blabla\').',
				225,
			],
			[
				'Offset \'b\' does not exist on array(\'a\' => \'blabla\').',
				228,
			],
			[
				'Offset string does not exist on array<int, mixed>.',
				240,
			],
			[
				'Cannot access offset \'a\' on Closure(): mixed.',
				253,
			],
			[
				'Cannot access offset \'a\' on array(\'a\' => 1, \'b\' => 1)|(Closure(): mixed).',
				258,
			],
			[
				'Offset string does not exist on array<int, string>.',
				308,
			],
			[
				'Offset null does not exist on array<int, string>.',
				310,
			],
			[
				'Offset int does not exist on array<string, string>.',
				312,
			],
			[
				'Offset \'baz\' does not exist on array(\'bar\' => 1, ?\'baz\' => 2).',
				344,
			],
			[
				'Offset \'foo\' does not exist on ArrayAccess<int, stdClass>.',
				411,
			],
			[
				'Cannot access offset \'foo\' on stdClass.',
				423,
			],
			[
				'Cannot access offset \'foo\' on true.',
				426,
			],
			[
				'Cannot access offset \'foo\' on false.',
				429,
			],
			[
				'Cannot access offset \'foo\' on resource.',
				433,
			],
			[
				'Cannot access offset \'foo\' on 42.',
				436,
			],
			[
				'Cannot access offset \'foo\' on 4.141.',
				439,
			],
			[
				'Cannot access offset \'foo\' on array|int.',
				443,
			],
		]);
	}

	public function testStrings(): void
	{
		$this->analyse([__DIR__ . '/data/strings-offset-access.php'], [
			[
				'Offset \'foo\' does not exist on \'foo\'.',
				10,
			],
			[
				'Offset 12.34 does not exist on \'foo\'.',
				13,
			],
			[
				'Offset \'foo\' does not exist on array|string.',
				24,
			],
			[
				'Offset 12.34 does not exist on array|string.',
				28,
			],
		]);
	}

	public function testAssignOp(): void
	{
		$this->analyse([__DIR__ . '/data/offset-access-assignop.php'], [
			[
				'Offset \'foo\' does not exist on array().',
				4,
			],
			[
				'Offset \'foo\' does not exist on \'Foo\'.',
				10,
			],
			[
				'Cannot access offset \'foo\' on stdClass.',
				13,
			],
			[
				'Cannot access offset \'foo\' on true.',
				16,
			],
			[
				'Cannot access offset \'foo\' on false.',
				19,
			],
			[
				'Cannot access offset \'foo\' on resource.',
				23,
			],
			[
				'Cannot access offset \'foo\' on 4.141.',
				26,
			],
			[
				'Cannot access offset \'foo\' on array|int.',
				30,
			],
			[
				'Cannot access offset \'foo\' on 42.',
				33,
			],
		]);
	}

	public function testCoalesceAssign(): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$this->analyse([__DIR__ . '/data/nonexistent-offset-coalesce-assign.php'], []);
	}

	public function testIntersection(): void
	{
		$this->analyse([__DIR__ . '/data/nonexistent-offset-intersection.php'], []);
	}

}
