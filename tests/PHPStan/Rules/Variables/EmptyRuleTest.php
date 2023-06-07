<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<EmptyRule>
 */
class EmptyRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	private bool $strictUnnecessaryNullsafePropertyFetch;

	protected function getRule(): Rule
	{
		return new EmptyRule(new IssetCheck(
			new PropertyDescriptor(),
			new PropertyReflectionFinder(),
			true,
			$this->treatPhpDocTypesAsCertain,
			$this->strictUnnecessaryNullsafePropertyFetch,
		));
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testRule(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
		$this->analyse([__DIR__ . '/data/empty-rule.php'], [
			[
				'Offset \'nonexistent\' on array{2: bool, 3: false, 4: true}|array{bool, false, bool, false, true} in empty() does not exist.',
				22,
			],
			[
				'Offset 3 on array{2: bool, 3: false, 4: true}|array{bool, false, bool, false, true} in empty() always exists and is always falsy.',
				24,
			],
			[
				'Offset 4 on array{2: bool, 3: false, 4: true}|array{bool, false, bool, false, true} in empty() always exists and is not falsy.',
				25,
			],
			[
				'Offset 0 on array{\'\', \'0\', \'foo\', \'\'|\'foo\'} in empty() always exists and is always falsy.',
				36,
			],
			[
				'Offset 1 on array{\'\', \'0\', \'foo\', \'\'|\'foo\'} in empty() always exists and is always falsy.',
				37,
			],
			[
				'Offset 2 on array{\'\', \'0\', \'foo\', \'\'|\'foo\'} in empty() always exists and is not falsy.',
				38,
			],
			[
				'Variable $a in empty() is never defined.',
				44,
			],
			[
				'Variable $b in empty() always exists and is not falsy.',
				47,
			],
		]);
	}

	public function testBug970(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
		$this->analyse([__DIR__ . '/data/bug-970.php'], [
			[
				'Variable $ar in empty() is never defined.',
				9,
			],
		]);
	}

	public function testBug6974(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
		$this->analyse([__DIR__ . '/data/bug-6974.php'], [
			[
				'Variable $a in empty() always exists and is always falsy.',
				12,
			],
		]);
	}

	public function testBug6974TreatPhpDocTypesAsCertain(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
		$this->analyse([__DIR__ . '/data/bug-6974.php'], [
			[
				'Variable $a in empty() always exists and is always falsy.',
				12,
			],
			[
				'Variable $a in empty() always exists and is not falsy.',
				30,
			],
		]);
	}

	public function testBug7109(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;

		$this->analyse([__DIR__ . '/../Properties/data/bug-7109.php'], [
			[
				'Expression in empty() is not falsy.',
				59,
			],
		]);
	}

	public function testBug7109Strict(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = true;

		$this->analyse([__DIR__ . '/../Properties/data/bug-7109.php'], [
			[
				'Using nullsafe property access "?->aaa" in empty() is unnecessary. Use -> instead.',
				19,
			],
			[
				'Using nullsafe property access "?->aaa" in empty() is unnecessary. Use -> instead.',
				30,
			],
			[
				'Using nullsafe property access "?->aaa" in empty() is unnecessary. Use -> instead.',
				42,
			],
			[
				'Using nullsafe property access "?->notFalsy" in empty() is unnecessary. Use -> instead.',
				54,
			],
			[
				'Expression in empty() is not falsy.',
				59,
			],
			[
				'Using nullsafe property access "?->aaa" in empty() is unnecessary. Use -> instead.',
				68,
			],
			[
				'Using nullsafe property access "?->(Expression)" in empty() is unnecessary. Use -> instead.',
				75,
			],
		]);
	}

	public function testBug7318(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = true;

		$this->analyse([__DIR__ . '/../Properties/data/bug-7318.php'], []);
	}

	public function testBug7424(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;

		$this->analyse([__DIR__ . '/data/bug-7424.php'], []);
	}

	public function testBug7724(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;

		$this->analyse([__DIR__ . '/data/bug-7724.php'], []);
	}

	public function testBug7199(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;

		$this->analyse([__DIR__ . '/data/bug-7199.php'], []);
	}

	public function testBug9126(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->strictUnnecessaryNullsafePropertyFetch = false;

		$this->analyse([__DIR__ . '/data/bug-9126.php'], []);
	}

	public function dataBug9403(): iterable
	{
		yield [true];
		yield [false];
	}

	/**
	 * @dataProvider dataBug9403
	 */
	public function testBug9403(bool $treatPhpDocTypesAsCertain): void
	{
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
		$this->strictUnnecessaryNullsafePropertyFetch = false;

		$this->analyse([__DIR__ . '/data/bug-9403.php'], []);
	}

}
