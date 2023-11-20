<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<DynamicClassConstantFetchRule>
 */
class DynamicClassConstantFetchRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new DynamicClassConstantFetchRule(
			self::getContainer()->getByType(PhpVersion::class),
			new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, true, false),
		);
	}

	public function testRule(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80300) {
			$errors = [
				[
					'Fetching class constants with a dynamic name is supported only on PHP 8.3 and later.',
					15,
				],
				[
					'Fetching class constants with a dynamic name is supported only on PHP 8.3 and later.',
					16,
				],
				[
					'Fetching class constants with a dynamic name is supported only on PHP 8.3 and later.',
					18,
				],
				[
					'Fetching class constants with a dynamic name is supported only on PHP 8.3 and later.',
					19,
				],
				[
					'Fetching class constants with a dynamic name is supported only on PHP 8.3 and later.',
					20,
				],
			];
		} else {
			$errors = [
				[
					'Class constant name in dynamic fetch can only be a string, int given.',
					18,
				],
				[
					'Class constant name in dynamic fetch can only be a string, int|string given.',
					19,
				],
				[
					'Class constant name in dynamic fetch can only be a string, string|null given.',
					20,
				],
			];
		}
		$this->analyse([__DIR__ . '/data/dynamic-class-constant-fetch.php'], $errors);
	}

}
