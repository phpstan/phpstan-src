<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NativeTypedClassConstantRule>
 */
class NativeTypedClassConstantRuleBug13133Test extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new NativeTypedClassConstantRule(new PhpVersion(80200));
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/bug-13133.neon',
		];
	}

	public function testBug13133(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13133.php'], []);
	}

}
