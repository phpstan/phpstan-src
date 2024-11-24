<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<NoncapturingCatchRule>
 */
class NoncapturingCatchRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NoncapturingCatchRule();
	}

	public function dataRule(): array
	{
		return [
			[
				70400,
				[
					[
						'Non-capturing catch is supported only on PHP 8.0 and later.',
						12,
					],
					[
						'Non-capturing catch is supported only on PHP 8.0 and later.',
						21,
					],
				],
			],
			[
				80000,
				[],
			],
		];
	}

	/**
	 * @dataProvider dataRule
	 *
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function testRule(int $phpVersion, array $expectedErrors): void
	{
		$testVersion = new PhpVersion($phpVersion);
		$runtimeVersion = new PhpVersion(PHP_VERSION_ID);
		if (
			$testVersion->getMajorVersionId() !== $runtimeVersion->getMajorVersionId()
			|| $testVersion->getMinorVersionId() !== $runtimeVersion->getMinorVersionId()
		) {
			$this->markTestSkipped('Test requires PHP version ' . $testVersion->getMajorVersionId() . '.' . $testVersion->getMinorVersionId() . '.*');
		}

		$this->analyse([
			__DIR__ . '/data/noncapturing-catch.php',
			__DIR__ . '/data/bug-8663.php',
		], $expectedErrors);
	}

}
