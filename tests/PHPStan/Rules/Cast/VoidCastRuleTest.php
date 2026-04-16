<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<VoidCastRule>
 */
class VoidCastRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new VoidCastRule(new PhpVersion(PHP_VERSION_ID));
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testPrintRule(): void
	{
		$this->analyse([__DIR__ . '/data/void-cast.php'], [
			[
				'The (void) cast cannot be used within an expression.',
				5,
			],
			[
				'The (void) cast cannot be used within an expression.',
				6,
			],
			[
				'The (void) cast cannot be used within an expression.',
				7,
			],
		]);
	}

	public function testSupport(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80500) {
			$errors = [
				[
					'The (void) cast is supported only on PHP 8.5 and later.',
					10,
				],
			];
		}
		$this->analyse([__DIR__ . '/data/void-cast-support.php'], $errors);
	}

}
