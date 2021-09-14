<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\FunctionDefinitionCheck;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ExistingClassesInArrowFunctionTypehintsRule>
 */
class ExistingClassesInArrowFunctionTypehintsRuleTest extends \PHPStan\Testing\RuleTestCase
{

	/** @var int */
	private $phpVersionId = PHP_VERSION_ID;

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$broker = $this->createReflectionProvider();
		return new ExistingClassesInArrowFunctionTypehintsRule(new FunctionDefinitionCheck($broker, new ClassCaseSensitivityCheck($broker), new PhpVersion($this->phpVersionId), true, false, true));
	}

	public function testRule(): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$this->analyse([__DIR__ . '/data/arrow-function-typehints.php'], [
			[
				'Parameter $bar of anonymous function has invalid type ArrowFunctionExistingClassesInTypehints\Bar.',
				10,
			],
			[
				'Anonymous function has invalid return type ArrowFunctionExistingClassesInTypehints\Baz.',
				10,
			],
		]);
	}

	public function dataNativeUnionTypes(): array
	{
		return [
			[
				70400,
				[
					[
						'Anonymous function uses native union types but they\'re supported only on PHP 8.0 and later.',
						23,
					],
					[
						'Anonymous function uses native union types but they\'re supported only on PHP 8.0 and later.',
						24,
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
	 * @dataProvider dataNativeUnionTypes
	 * @param int $phpVersionId
	 * @param mixed[] $errors
	 */
	public function testNativeUnionTypes(int $phpVersionId, array $errors): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/native-union-types.php'], $errors);
	}

	public function dataRequiredParameterAfterOptional(): array
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
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						5,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						9,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						11,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataRequiredParameterAfterOptional
	 * @param int $phpVersionId
	 * @param mixed[] $errors
	 */
	public function testRequiredParameterAfterOptional(int $phpVersionId, array $errors): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/required-parameter-after-optional-arrow.php'], $errors);
	}

}
