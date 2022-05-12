<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<NonexistentOffsetInArrayDimFetchRule>
 */
class NonexistentOffsetInArrayDimFetchRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	protected function getRule(): Rule
	{
		$ruleLevelHelper = new RuleLevelHelper($this->createReflectionProvider(), true, false, true, $this->checkExplicitMixed);

		return new NonexistentOffsetInArrayDimFetchRule(
			$ruleLevelHelper,
			new NonexistentOffsetInArrayDimFetchCheck($ruleLevelHelper, true),
			true,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/nonexistent-offset.php'], [
			[
				'Offset \'b\' does not exist on array{a: stdClass, 0: 2}.',
				17,
			],
			[
				'Offset 1 does not exist on array{a: stdClass, 0: 2}.',
				18,
			],
			[
				'Offset \'a\' does not exist on array{b: 1}.',
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
				'Offset \'c\' does not exist on array{c: bool}|array{e: true}.',
				171,
			],
			[
				'Offset int does not exist on array{}|array{1: 1, 2: 2}|array{3: 3, 4: 4}.',
				190,
			],
			[
				'Offset int does not exist on array{}|array{1: 1, 2: 2}|array{3: 3, 4: 4}.',
				193,
			],
			[
				'Offset \'b\' does not exist on array{a: \'blabla\'}.',
				225,
			],
			[
				'Offset \'b\' does not exist on array{a: \'blabla\'}.',
				228,
			],
			[
				'Offset string does not exist on array<int, mixed>.',
				240,
			],
			[
				'Cannot access offset \'a\' on Closure(): void.',
				253,
			],
			[
				'Cannot access offset \'a\' on array{a: 1, b: 1}|(Closure(): void).',
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
				'Offset \'baz\' does not exist on array{bar: 1, baz?: 2}.',
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
			[
				'Offset \'feature_pretty…\' does not exist on array{version: non-empty-string, commit: string|null, pretty_version: string|null, feature_version: non-empty-string, feature_pretty_version?: string|null}.',
				504,
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
				'Offset \'foo\' does not exist on array{}.',
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

	public function testBug3782(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3782.php'], [
			[
				'Cannot access offset (int|string) on Bug3782\HelloWorld.',
				11,
			],
		]);
	}

	public function testBug4432(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4432.php'], []);
	}

	public function testBug1664(): void
	{
		$this->analyse([__DIR__ . '/data/bug-1664.php'], []);
	}

	public function testBug2689(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2689.php'], [
			[
				'Cannot access an offset on callable.',
				14,
			],
		]);
	}

	public function testBug5169(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5169.php'], [
			[
				'Cannot access offset mixed on (float|int).',
				29,
			],
		]);
	}

	public function testBug3297(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3297.php'], []);
	}

	public function testBug4829(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4829.php'], []);
	}

	public function testBug3784(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3784.php'], []);
	}

	public function testBug3700(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3700.php'], []);
	}

	public function testBug4842(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4842.php'], []);
	}

	public function testBug5669(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5669.php'], [
			[
				'Access to offset \'%customer…\' on an unknown class Bug5669\arr.',
				26,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testBug5744(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5744.php'], [
			[
				'Cannot access offset \'permission\' on mixed.',
				16,
			],
			[
				'Cannot access offset \'permission\' on mixed.',
				29,
			],
			[
				'Cannot access offset \'permission\' on mixed.',
				39,
			],
		]);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/nonexistent-offset-nullsafe.php'], [
			[
				'Offset 1 does not exist on array{a: int}.',
				18,
			],
		]);
	}

	public function testBug4926(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4926.php'], []);
	}

	public function testBug3171(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3171.php'], []);
	}

	public function testBug4747(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4747.php'], []);
	}

	public function testBug6379(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6379.php'], []);
	}

	public function testBug4885(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-4885.php'], []);
	}

	public function testBug7000(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7000.php'], [
			[
				"Offset 'require'|'require-dev' does not exist on array{require?: array<string, string>, require-dev?: array<string, string>}.",
				16,
			],
		]);
	}

	public function testBug6508(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6508.php'], []);
	}

}
