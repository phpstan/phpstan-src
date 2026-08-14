<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Classes\ForbiddenClassNameExtension;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ClassConstantRule>
 */
class ClassConstantPhp74RuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new ClassConstantRule(
			$reflectionProvider,
			new RuleLevelHelper(
				$reflectionProvider,
				checkNullables: true,
				checkThisOnly: false,
				checkUnionTypes: true,
				checkExplicitMixed: true,
				checkImplicitMixed: true,
				checkBenevolentUnionTypes: false,
				discoveringSymbolsTip: true,
			),
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, checkInternalClassCaseSensitivity: true, checkImportedClassNameCase: true),
				new ClassForbiddenNameCheck($container->getExtensionsCollection(ForbiddenClassNameExtension::class)),
				$reflectionProvider,
				$container->getExtensionsCollection(RestrictedClassNameUsageExtension::class),
			),
			$container->getByType(PhpVersion::class),
			checkNonStringableDynamicAccess: true,
		);
	}

	public function testClassConstantOnExpressionInDeadBranch(): void
	{
		// the `mixed` typehint parses as an unknown class before PHP 8.0, so
		// both branches are dead - the ::class version error must still be
		// reported from the narrowed (object) expression there
		$this->analyse([__DIR__ . '/data/class-constant-on-expr-never.php'], [
			[
				'Accessing ::class constant on an expression is supported only on PHP 8.0 and later.',
				11,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/classConstantPhp74.neon',
		];
	}

}
