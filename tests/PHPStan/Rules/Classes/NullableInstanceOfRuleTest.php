<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NullableInstanceOfRule>
 */
class NullableInstanceOfRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	protected function getRule(): Rule
	{
		return new NullableInstanceOfRule($this->treatPhpDocTypesAsCertain);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testNullableClassname(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-10036.php'], [
			[
				'Classname in instanceof cannot be null, string|null given.',
				9,
			],
			[
				'Classname in instanceof cannot be null, mixed given.',
				17,
			],
			[
				'Classname in instanceof cannot be null, mixed given.',
				27,
			],
		]);
	}

	public function testNullablePhpdocClassname(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-10036.php'], [
			[
				'Classname in instanceof cannot be null, string|null given.',
				9,
			],
			[
				'Classname in instanceof cannot be null, string|null given.',
				17,
			],
			[
				'Classname in instanceof cannot be null, mixed given.',
				27,
			],
		]);
	}

}
