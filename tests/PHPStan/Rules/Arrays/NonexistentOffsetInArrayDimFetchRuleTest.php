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

	private bool $checkImplicitMixed = false;

	private bool $bleedingEdge = false;

	private bool $reportPossiblyNonexistentGeneralArrayOffset = false;

	private bool $reportPossiblyNonexistentConstantArrayOffset = false;

	protected function getRule(): Rule
	{
		$ruleLevelHelper = new RuleLevelHelper($this->createReflectionProvider(), true, false, true, $this->checkExplicitMixed, $this->checkImplicitMixed, true, false);

		return new NonexistentOffsetInArrayDimFetchRule(
			$ruleLevelHelper,
			new NonexistentOffsetInArrayDimFetchCheck($ruleLevelHelper, true, $this->bleedingEdge, $this->reportPossiblyNonexistentGeneralArrayOffset, $this->reportPossiblyNonexistentConstantArrayOffset),
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
				'Offset \'c\' does not exist on array{c: false}|array{c: true}|array{e: true}.',
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
				'Cannot access offset \'a\' on Closure(): void.',
				253,
			],
			[
				'Cannot access offset \'a\' on array{a: 1, b: 1}|(Closure(): void).',
				258,
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
				'Offset \'feature_pretty…\' does not exist on array{version: non-falsy-string, commit: string|null, pretty_version: string|null, feature_version: non-falsy-string, feature_pretty_version?: string|null}.',
				504,
			],
			[
				"Cannot access offset 'foo' on bool.",
				517,
			],
		]);
	}

	public function testRuleBleedingEdge(): void
	{
		$this->bleedingEdge = true;
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
				'Offset \'c\' might not exist on array{c: false}|array{c: true}|array{e: true}.',
				171,
			],
			[
				'Offset int might not exist on array{}|array{1: 1, 2: 2}|array{3: 3, 4: 4}.',
				190,
			],
			[
				'Offset int might not exist on array{}|array{1: 1, 2: 2}|array{3: 3, 4: 4}.',
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
				'Cannot access offset \'a\' on Closure(): void.',
				253,
			],
			[
				'Cannot access offset \'a\' on array{a: 1, b: 1}|(Closure(): void).',
				258,
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
				'Offset \'baz\' might not exist on array{bar: 1, baz?: 2}.',
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
				'Offset \'feature_pretty…\' might not exist on array{version: non-falsy-string, commit: string|null, pretty_version: string|null, feature_version: non-falsy-string, feature_pretty_version?: string|null}.',
				504,
			],
			[
				"Cannot access offset 'foo' on bool.",
				517,
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
				'Cannot access offset (int|string) on $this(Bug3782\HelloWorld)|(ArrayAccess&Bug3782\HelloWorld).',
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

	public function testBug7229(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7229.php'], [
			[
				'Cannot access offset string on mixed.',
				24,
			],
		]);
	}

	public function testBug7142(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7142.php'], []);
	}

	public function testBug6000(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6000.php'], []);
	}

	public function testBug5743(): void
	{
		$this->analyse([__DIR__ . '/../Comparison/data/bug-5743.php'], [
			[
				'Offset 1|int<3, max> does not exist on array{}.',
				10,
			],
		]);
	}

	public function testBug6364(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6364.php'], []);
	}

	public function testBug5758(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5758.php'], []);
	}

	public function testBug5223(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-5223.php'], [
			[
				'Offset \'something\' does not exist on array{categoryKeys: array<string>, tagNames: array<string>}.',
				26,
			],
			[
				'Offset \'something\' does not exist on array{categoryKeys: array<string>, tagNames: array<string>}.',
				27,
			],
			[
				'Offset \'something\' does not exist on array{categoryKeys: array<string>, tagNames: array<string>}.',
				41,
			],
			[
				'Offset \'something\' does not exist on array{categoryKeys: array<string>, tagNames: array<string>}.',
				42,
			],
		]);
	}

	public function testBug7469(): void
	{
		$expected = [];

		if (PHP_VERSION_ID < 80000) {
			$expected = [
				[
					"Cannot access offset 'languages' on array<'address'|'bankAccount'|'birthDate'|'email'|'firstName'|'ic'|'invoicing'|'invoicingAddress'|'languages'|'lastName'|'note'|'phone'|'radio'|'videoOnline'|'videoTvc'|'voiceExample', mixed>|false.",
					31,
				],
				[
					"Cannot access offset 'languages' on array<'address'|'bankAccount'|'birthDate'|'email'|'firstName'|'ic'|'invoicing'|'invoicingAddress'|'languages'|'lastName'|'note'|'phone'|'radio'|'videoOnline'|'videoTvc'|'voiceExample', mixed>|false.",
					31,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/bug-7469.php'], $expected);
	}

	public function testBug7763(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-7763.php'], []);
	}

	public function testSpecifyExistentOffsetWhenEnteringForeach(): void
	{
		$this->analyse([__DIR__ . '/data/specify-existent-offset-when-entering-foreach.php'], []);
	}

	public function testBug3872(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3872.php'], []);
	}

	public function testBug6783(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6783.php'], []);
	}

	public function testSlevomatForeachUnsetBug(): void
	{
		$this->analyse([__DIR__ . '/data/slevomat-foreach-unset-bug.php'], []);
	}

	public function testSlevomatForeachArrayKeyExistsBug(): void
	{
		$this->analyse([__DIR__ . '/data/slevomat-foreach-array-key-exists-bug.php'], []);
	}

	public function testBug7954(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7954.php'], []);
	}

	public function testBug8097(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8097.php'], []);
	}

	public function testBug8068(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8068.php'], [
			[
				"Cannot access offset 'path' on Closure.",
				18,
			],
			[
				"Cannot access offset 'path' on iterable<int|string, object>.",
				26,
			],
		]);
	}

	public function testBug6243(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->analyse([__DIR__ . '/data/bug-6243.php'], []);
	}

	public function testBug8356(): void
	{
		$this->bleedingEdge = true;
		$this->analyse([__DIR__ . '/data/bug-8356.php'], [
			[
				"Offset 'x' might not exist on array|string.",
				7,
			],
		]);
	}

	public function testBug6605(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6605.php'], [
			[
				"Cannot access offset 'invalidoffset' on Bug6605\\X.",
				11,
			],
			[
				"Offset 'invalid' does not exist on array{a: array{b: array{5}}}.",
				16,
			],
			[
				"Offset 'invalid' does not exist on array{b: array{5}}.",
				17,
			],
		]);
	}

	public function testBug9991(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-9991.php'], [
			[
				'Cannot access offset \'title\' on mixed.',
				9,
			],
		]);
	}

	public function testBug8166(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-8166.php'], [
			[
				'Offset \'b\' does not exist on array{a: 1}.',
				22,
			],
			[
				'Offset \'b\' does not exist on array<\'a\', string>.',
				23,
			],
		]);
	}

	public function testBug10926(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-10926.php'], [
			[
				'Cannot access offset \'a\' on stdClass.',
				10,
			],
		]);
	}

	public function testMixed(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/data/offset-access-mixed.php'], [
			[
				'Cannot access offset 5 on T of mixed.',
				11,
			],
			[
				'Cannot access offset 5 on mixed.',
				16,
			],
			[
				'Cannot access offset 5 on mixed.',
				21,
			],
		]);
	}

	public function testOffsetAccessLegal(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/offset-access-legal.php'], [
			[
				'Cannot access offset 0 on Closure(): void.',
				7,
			],
			[
				'Cannot access offset 0 on stdClass.',
				12,
			],
			[
				'Cannot access offset 0 on array{\'test\'}|stdClass.',
				96,
			],
			[
				'Cannot access offset 0 on array{\'test\'}|(Closure(): void).',
				98,
			],
		]);
	}

	public function testNonExistentParentOffsetAccessLegal(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/offset-access-legal-non-existent-parent.php'], [
			[
				'Cannot access offset 0 on parent.',
				9,
			],
		]);
	}

	public function dataReportPossiblyNonexistentArrayOffset(): iterable
	{
		yield [false, false, []];
		yield [false, true, [
			[
				'Offset string might not exist on array{foo: 1}.',
				20,
			],
		]];
		yield [true, false, [
			[
				"Offset 'foo' might not exist on array.",
				9,
			],
		]];
		yield [true, true, [
			[
				"Offset 'foo' might not exist on array.",
				9,
			],
			[
				'Offset string might not exist on array{foo: 1}.',
				20,
			],
		]];
	}

	/**
	 * @dataProvider dataReportPossiblyNonexistentArrayOffset
	 * @param list<array{0: string, 1: int, 2?: string|null}> $errors
	 */
	public function testReportPossiblyNonexistentArrayOffset(bool $reportPossiblyNonexistentGeneralArrayOffset, bool $reportPossiblyNonexistentConstantArrayOffset, array $errors): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = $reportPossiblyNonexistentGeneralArrayOffset;
		$this->reportPossiblyNonexistentConstantArrayOffset = $reportPossiblyNonexistentConstantArrayOffset;

		$this->analyse([__DIR__ . '/data/report-possibly-nonexistent-array-offset.php'], $errors);
	}

	public function testBug10997(): void
	{
		$this->reportPossiblyNonexistentConstantArrayOffset = true;
		$this->analyse([__DIR__ . '/data/bug-10997.php'], [
			[
				'Offset int<0, 4> might not exist on array{1, 2, 3, 4}.',
				15,
			],
		]);
	}

}
