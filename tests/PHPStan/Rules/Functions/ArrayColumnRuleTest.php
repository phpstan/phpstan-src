<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\Php\ArrayColumnHelper;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ArrayColumnRule>
 */
class ArrayColumnRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain = true;

	protected function getRule(): Rule
	{
		return new ArrayColumnRule(
			$this->createReflectionProvider(),
			$this->treatPhpDocTypesAsCertain,
			true,
			self::getContainer()->getByType(ArrayColumnHelper::class),
		);
	}

	public function testFile(): void
	{
		$expectedErrors = [];

		$this->analyse([__DIR__ . '/../../Analyser/nsrt/array-column-php7.php'], $expectedErrors);
	}

	public function testFilePhp82(): void
	{
		if (PHP_VERSION_ID < 80200) {
			$this->markTestSkipped('Test requires PHP 8.2');
		}

		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$expectedErrors = [
			[
				"Cannot access column 'column' on *NEVER*.",
				30,
				$tipText,
			],
			[
				"Cannot access column 'column' on *NEVER*.",
				31,
				$tipText,
			],
			[
				"Cannot access column 'key' on *NEVER*.",
				31,
				$tipText,
			],
			[
				"Cannot access column 'key' on *NEVER*.",
				32,
				$tipText,
			],
			[
				"Cannot access column 'foo' on array{column: string, key: string}.",
				76,
				$tipText,
			],
			[
				"Cannot access column 'foo' on array{column: string, key: string}.",
				77,
				$tipText,
			],
			[
				"Cannot access column 'nodeName' on ArrayColumn82\Foo.",
				216,
				$tipText,
			],
			[
				"Cannot access column 'nodeName' on ArrayColumn82\Foo.",
				217,
				$tipText,
			],
			[
				"Cannot access column 'tagName' on ArrayColumn82\Foo.",
				217,
				$tipText,
			],
		];

		$this->analyse([__DIR__ . '/../../Analyser/nsrt/array-column-php82.php'], $expectedErrors);
	}

	public function testBug5101(): void
	{
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';

		// in PHP < 8.2 dynamic properties can exist any time
		$expectedErrors = [];
		if (PHP_VERSION_ID >= 80200) {
			$expectedErrors = [
				[
					"Cannot access column 'y' on Bug5101\FinalFooBar.",
					22,
					$tipText,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/bug-5101.php'], $expectedErrors);
	}

	public function testBug12188(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1');
		}

		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$expectedErrors = [
			[
				"Cannot access column 'value' on Bug12188\Foo::A|Bug12188\Foo::B.",
				14,
				$tipText,
			],
		];

		$this->analyse([__DIR__ . '/data/bug-12188.php'], $expectedErrors);
	}

}
