<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ConstantAttributesRule>
 */
class ConstantAttributesRuleTest extends RuleTestCase
{

	private int $phpVersion = PHP_VERSION_ID;

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new ConstantAttributesRule(
			new AttributesCheck(
				$reflectionProvider,
				new FunctionCallParametersCheck(
					new RuleLevelHelper($reflectionProvider, true, false, true, true, true, false, true),
					new NullsafeCheck(),
					new UnresolvableTypeHelper(),
					new PropertyReflectionFinder(),
					true,
					true,
					true,
					true,
				),
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, false),
					new ClassForbiddenNameCheck($container),
					$reflectionProvider,
					$container,
				),
				true,
			),
			new PhpVersion($this->phpVersion),
		);
	}

	#[RequiresPhp('>= 8.5')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/constant-attributes.php'], [
			[
				'Attribute class ConstantAttributes\IncompatibleAttr does not have the constant target.',
				31,
			],
			[
				'Attribute class ConstantAttributes\MyAttr does not have a constructor and must be instantiated without any parameters.',
				34,
			],
		]);
	}

	public static function dataRuleBefore85Runtime(): iterable
	{
		yield [
			80400,
			[
				[
					'Attributes on global constants are supported only on PHP 8.5 and later.',
					25,
				],
				[
					'Attributes on global constants are supported only on PHP 8.5 and later.',
					28,
				],
				[
					'Attributes on global constants are supported only on PHP 8.5 and later.',
					31,
				],
				[
					'Attributes on global constants are supported only on PHP 8.5 and later.',
					34,
				],
			],
		];
		yield [
			80500,
			[
				[
					'ConstantAttributesRule requires PHP 8.5 runtime to check the code.',
					25,
				],
				[
					'ConstantAttributesRule requires PHP 8.5 runtime to check the code.',
					28,
				],
				[
					'ConstantAttributesRule requires PHP 8.5 runtime to check the code.',
					31,
				],
				[
					'ConstantAttributesRule requires PHP 8.5 runtime to check the code.',
					34,
				],
			],
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string|null}> $expectedErrors
	 */
	#[RequiresPhp('< 8.5')]
	#[DataProvider('dataRuleBefore85Runtime')]
	public function testRuleBefore85Runtime(int $phpVersionId, array $expectedErrors): void
	{
		$this->phpVersion = $phpVersionId;
		$this->analyse([__DIR__ . '/data/constant-attributes.php'], $expectedErrors);
	}

}
