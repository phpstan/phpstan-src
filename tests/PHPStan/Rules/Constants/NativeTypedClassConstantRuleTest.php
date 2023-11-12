<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<NativeTypedClassConstantRule>
 */
class NativeTypedClassConstantRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new NativeTypedClassConstantRule(new PhpVersion(PHP_VERSION_ID));
	}

	public function testRule(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80300) {
			$errors = [
				[
					'Class constants with native types are supported only on PHP 8.3 and later.',
					10,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/native-typed-class-constant.php'], $errors);
	}

}
