<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<EmptyRule>
 */
class EmptyRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	protected function getRule(): Rule
	{
		return new EmptyRule(new IssetCheck(
			new PropertyDescriptor(),
			new PropertyReflectionFinder(),
			true,
			$this->treatPhpDocTypesAsCertain,
		));
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testRule(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/empty-rule.php'], [
			[
				'Offset \'nonexistent\' on array{0?: bool, 1?: false, 2: bool, 3: false, 4: true} in empty() does not exist.',
				22,
			],
			[
				'Offset 3 on array{0?: bool, 1?: false, 2: bool, 3: false, 4: true} in empty() always exists and is always falsy.',
				24,
			],
			[
				'Offset 4 on array{0?: bool, 1?: false, 2: bool, 3: false, 4: true} in empty() always exists and is not falsy.',
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

}
