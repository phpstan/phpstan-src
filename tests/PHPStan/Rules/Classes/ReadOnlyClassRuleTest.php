<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ReadOnlyClassRule>
 */
class ReadOnlyClassRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new ReadOnlyClassRule(self::getContainer()->getByType(PhpVersion::class));
	}

	public function testRule(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80200) {
			$errors = [
				[
					'Readonly classes are supported only on PHP 8.2 and later.',
					5,
				],
			];
		} elseif (PHP_VERSION_ID < 80300) {
			$errors = [
				[
					'Anonymous readonly classes are supported only on PHP 8.3 and later.',
					15,
				],
			];
		}
		$this->analyse([__DIR__ . '/data/readonly-class.php'], $errors);
	}

}
