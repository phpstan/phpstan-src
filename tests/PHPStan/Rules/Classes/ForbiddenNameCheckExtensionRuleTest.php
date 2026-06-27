<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

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
use function array_merge;

/**
 * @extends RuleTestCase<InstantiationRule>
 */
class ForbiddenNameCheckExtensionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		$ruleLevelHelper = new RuleLevelHelper(
			$reflectionProvider,
			checkNullables: true,
			checkThisOnly: false,
			checkUnionTypes: true,
			checkExplicitMixed: false,
			checkImplicitMixed: false,
			checkBenevolentUnionTypes: false,
			discoveringSymbolsTip: true,
		);
		return new InstantiationRule(
			$container,
			$reflectionProvider,
			new FunctionCallParametersCheck(
				$ruleLevelHelper,
				new NullsafeCheck(),
				new UnresolvableTypeHelper(),
				new PropertyReflectionFinder(),
				$reflectionProvider,
				checkArgumentTypes: true,
				checkArgumentsPassedByReference: true,
				checkExtraArguments: true,
				checkMissingTypehints: true,
				reportMixedTernaryAndCoalesce: true,
			),
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, checkInternalClassCaseSensitivity: true),
				new ClassForbiddenNameCheck($container),
				$reflectionProvider,
				$container,
			),
			$ruleLevelHelper,
			new ConsistentConstructorHelper(),
			newOnNonObject: true,
			discoveringSymbolsTip: true,
		);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(parent::getAdditionalConfigFiles(), [
			__DIR__ . '/data/forbidden-name-class-extension.neon',
		]);
	}

	public function testInternalClassFromExtensions(): void
	{
		$this->analyse([__DIR__ . '/data/forbidden-name-class-extension.php'], [
			[
				'Referencing prefixed Doctrine class: App\GeneratedProxy\__CG__\App\TestDoctrineEntity.',
				31,
				'This is most likely unintentional. Did you mean to type \App\TestDoctrineEntity?',
			],
			[
				'Referencing prefixed PHPStan class: _PHPStan_15755dag8c\TestPhpStanEntity.',
				32,
				'This is most likely unintentional. Did you mean to type \TestPhpStanEntity?',
			],
		]);
	}

}
