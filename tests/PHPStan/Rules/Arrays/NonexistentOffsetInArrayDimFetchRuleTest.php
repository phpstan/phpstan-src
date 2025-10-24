<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<NonexistentOffsetInArrayDimFetchRule>
 */
class NonexistentOffsetInArrayDimFetchRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	private bool $checkImplicitMixed = false;

	private bool $reportPossiblyNonexistentGeneralArrayOffset = false;

	private bool $reportPossiblyNonexistentConstantArrayOffset = false;

	protected function getRule(): Rule
	{
		$ruleLevelHelper = new RuleLevelHelper(self::createReflectionProvider(), true, false, true, $this->checkExplicitMixed, $this->checkImplicitMixed, false, true);

		return new NonexistentOffsetInArrayDimFetchRule(
			$ruleLevelHelper,
			new NonexistentOffsetInArrayDimFetchCheck($ruleLevelHelper, true, $this->reportPossiblyNonexistentGeneralArrayOffset, $this->reportPossiblyNonexistentConstantArrayOffset),
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
				'Offset int|null might not exist on array<int, string>.',
				309,
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
				'Offset int|null might not exist on array<string, string>.',
				314,
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
				'Offset \'feature_pretty_version\' might not exist on array{version: non-falsy-string, commit: string|null, pretty_version: string|null, feature_version: non-falsy-string, feature_pretty_version?: string|null}.',
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
				'Offset int|object might not exist on \'foo\'.',
				16,
			],
			[
				'Offset \'foo\' might not exist on array|string.',
				24,
			],
			[
				'Offset 12.34 might not exist on array|string.',
				28,
			],
			[
				'Offset int|object might not exist on array|string.',
				32,
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

	#[RequiresPhp('>= 8.0')]
	public function testRuleWithNullsafeVariant(): void
	{
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

	#[RequiresPhp('>= 8.0')]
	public function testBug4885(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4885.php'], []);
	}

	public function testBug7000(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7000.php'], [
			[
				"Offset 'require'|'require-dev' might not exist on array{require?: array<string, string>, require-dev?: array<string, string>}.",
				16,
			],
		]);
	}

	public function testBug7000b(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7000b.php'], [
			[
				"Offset 'require'|'require-dev' might not exist on array{require?: array<string, string>, require-dev?: array<string, string>}.",
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

	#[RequiresPhp('>= 8.1')]
	public function testBug7763(): void
	{
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
		$this->analyse([__DIR__ . '/data/bug-6243.php'], []);
	}

	public function testBug8356(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8356.php'], [
			[
				"Offset 'x' might not exist on array<mixed>|string.",
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

	#[RequiresPhp('>= 8.0')]
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

	public static function dataReportPossiblyNonexistentArrayOffset(): iterable
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
	 * @param list<array{0: string, 1: int, 2?: string|null}> $errors
	 */
	#[DataProvider('dataReportPossiblyNonexistentArrayOffset')]
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

	public function testBug11572(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11572.php'], [
			[
				'Cannot access an offset on int.',
				45,
			],
			[
				'Cannot access an offset on int<3, 4>.',
				46,
			],
		]);
	}

	public function testBug2313(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2313.php'], []);
	}

	public function testBug11655(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11655.php'], [
			[
				"Offset 3 does not exist on array{non-falsy-string, 'x', array{non-falsy-string, 'x'}}.",
				15,
			],
		]);
	}

	public function testBug2634(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2634.php'], []);
	}

	public function testBug11390(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11390.php'], []);
	}

	public function testInternalClassesWithOverloadedOffsetAccess(): void
	{
		$this->analyse([__DIR__ . '/data/internal-classes-overload-offset-access.php'], []);
	}

	#[RequiresPhp('>= 8.4')]
	public function testInternalClassesWithOverloadedOffsetAccess84(): void
	{
		$this->analyse([__DIR__ . '/data/internal-classes-overload-offset-access-php84.php'], []);
	}

	public function testInternalClassesWithOverloadedOffsetAccessInvalid(): void
	{
		$this->analyse([__DIR__ . '/data/internal-classes-overload-offset-access-invalid.php'], []);
	}

	#[RequiresPhp('>= 8.4')]
	public function testInternalClassesWithOverloadedOffsetAccessInvalid84(): void
	{
		$this->analyse([__DIR__ . '/data/internal-classes-overload-offset-access-invalid-php84.php'], []);
	}

	public function testBug12122(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12122.php'], []);
	}

	public function testArrayDimFetchAfterArrayKeyFirstOrLast(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/array-dim-after-array-key-first-or-last.php'], [
			[
				'Offset null does not exist on array{}.',
				19,
			],
		]);
	}

	public function testArrayDimFetchAfterCount(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/array-dim-after-count.php'], [
			[
				'Offset int<0, max> might not exist on list<string>.',
				26,
			],
			[
				'Offset int<-1, max> might not exist on array<string>.',
				35,
			],
			[
				'Offset int<0, max> might not exist on non-empty-array<string>.',
				42,
			],
		]);
	}

	public function testArrayDimFetchAfterArraySearch(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/array-dim-after-array-search.php'], [
			[
				'Offset int|string might not exist on array.',
				20,
			],
		]);
	}

	public function testArrayDimFetchOnArrayKeyFirsOrLastOrCount(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/array-dim-fetch-on-array-key-first-last.php'], [
			[
				'Offset 0|null might not exist on list<string>.',
				12,
			],
			[
				'Offset (int|string) might not exist on non-empty-list<string>.',
				16,
			],
			[
				'Offset int<-1, max> might not exist on non-empty-list<string>.',
				45,
			],
		]);
	}

	public function testBug12406(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-12406.php'], []);
	}

	public function testBug12406b(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-12406b.php'], []);
	}

	public function testBug11679(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-11679.php'], []);
	}

	public function testBug8649(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-8649.php'], []);
	}

	public function testBug11447(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-11447.php'], []);
	}

	public function testNarrowSuperglobals(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/narrow-superglobal.php'], []);
	}

	public function testBug12605(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-12605.php'], [
			[
				'Offset 1 might not exist on list<int>.',
				19,
			],
			[
				'Offset 10 might not exist on non-empty-list<int>.',
				26,
			],
		]);
	}

	public function testBug4809(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4809.php'], []);
	}

	public function testBug11602(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-11602.php'], []);
	}

	public function testBug12593(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-12593.php'], []);
	}

	public function testBug12981(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-12981.php'], [
			[
				'Offset array<int, int|string>|int|string might not exist on non-empty-array<bool|float|int|string>.',
				39,
			],
			[
				'Offset array<int, int|string>|int|string might not exist on non-empty-array<bool|float|int|string>.',
				41,
			],
		]);
	}

	public function testBugObject(): void
	{
		$this->analyse([__DIR__ . '/data/bug-object.php'], [
			[
				'Offset int|object does not exist on array{baz: 21}|array{foo: 17, bar: 19}.',
				12,
			],
		]);
	}

	public function testBug3747(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3747.php'], []);
	}

	public function testBug12447(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;

		$this->analyse([__DIR__ . '/data/bug-12447.php'], [
			[
				'Cannot access an offset on mixed.',
				5,
			],
		]);
	}

	public function testBug1061(): void
	{
		$this->analyse([__DIR__ . '/data/bug-1061.php'], [
			[
				"Offset 'one'|'two' might not exist on array{two: 1, three: 2}.",
				14,
			],
		]);
	}

	public function testBug4532(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4532.php'], [
			[
				'Offset int|null might not exist on array<int, string>.',
				25,
			],
		]);
	}

	public function testBug12115(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-12115.php'], []);
	}

	public function testBug10492(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10492.php'], [
			[
				'Offset 2|4 might not exist on array{array{0}, array{1}, array{2}}.',
				19,
			],
		]);
	}

	public function testBug12574a(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-12574a.php'], []);
	}

	public function testBug12574b(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-12574b.php'], []);
	}

	public function testBug12926(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-12926.php'], []);
	}

	public function testBug13538(): void
	{
		$this->reportPossiblyNonexistentConstantArrayOffset = true;
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-13538.php'], [
			[
				"Offset int might not exist on non-empty-array<int, ''>.",
				13,
			],
			[
				"Offset int might not exist on non-empty-array<int, ''>.",
				17,
			],
		]);
	}

	public function testPR4385(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;
		$this->reportPossiblyNonexistentConstantArrayOffset = true;

		$this->analyse([__DIR__ . '/data/pr-4385.php'], [
			[
				'Offset int might not exist on array<int>.',
				24,
			],
			[
				'Offset string might not exist on array<int>.',
				25,
			],
			[
				'Offset array<int>|int might not exist on array<int>.',
				28,
			],
			[
				'Offset array<int>|string might not exist on array<int>.',
				29,
			],
			[
				'Offset 0|array<int> might not exist on array<int>.',
				30,
			],
			[
				'Offset int might not exist on array{string}.',
				33,
			],
			[
				'Offset string might not exist on array{string}.',
				34,
			],
			[
				'Offset array<int>|int might not exist on array{string}.',
				37,
			],
			[
				'Offset array<int>|string might not exist on array{string}.',
				38,
			],
			[
				'Offset array<int>|int might not exist on array<int>|string.',
				41,
			],
		]);
	}

	public function testPR4385Bis(): void
	{
		$this->analyse([__DIR__ . '/data/pr-4385-bis.php'], []);
	}

	public function testBug12805(): void
	{
		$this->reportPossiblyNonexistentGeneralArrayOffset = true;

		$this->analyse([__DIR__ . '/data/bug-12805.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug6209(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = false;

		$this->analyse([__DIR__ . '/data/bug-6209.php'], []);
	}

	public function testBug11276(): void
	{
		$this->reportPossiblyNonexistentConstantArrayOffset = true;
		$this->analyse([__DIR__ . '/data/bug-11276.php'], []);
	}

}
