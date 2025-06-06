<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/** @extends RuleTestCase<FinalPrivateMethodRule> */
class FinalPrivateMethodRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FinalPrivateMethodRule();
	}

	public static function dataRule(): array
	{
		return [
			[
				70400,
				[],
			],
			[
				80000,
				[
					[
						'Private method FinalPrivateMethod\Foo::foo() cannot be final as it is never overridden by other classes.',
						8,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataRule
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testRule(int $phpVersion, array $errors): void
	{
		$testVersion = new PhpVersion($phpVersion);
		$runtimeVersion = new PhpVersion(PHP_VERSION_ID);

		if (
			$testVersion->getMajorVersionId() !== $runtimeVersion->getMajorVersionId()
			|| $testVersion->getMinorVersionId() !== $runtimeVersion->getMinorVersionId()
		) {
			$this->markTestSkipped('Test requires PHP version ' . $testVersion->getMajorVersionId() . '.' . $testVersion->getMinorVersionId() . '.*');
		}

		$this->analyse([__DIR__ . '/data/final-private-method.php'], $errors);
	}

	public function testRulePhpVersions(): void
	{
		$this->analyse([__DIR__ . '/data/final-private-method-phpversions.php'], [
			[
				'Private method FinalPrivateMethodPhpVersions\FooBarPhp8orHigher::foo() cannot be final as it is never overridden by other classes.',
				9,
			],
			[
				'Private method FinalPrivateMethodPhpVersions\FooBarPhp74OrHigher::foo() cannot be final as it is never overridden by other classes.',
				29,
			],
			[
				'Private method FinalPrivateMethodPhpVersions\FooBarBaz::foo() cannot be final as it is never overridden by other classes.',
				39,
			],
		]);
	}

}
