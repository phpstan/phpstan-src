<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Classes\ForbiddenClassNameExtension;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @extends RuleTestCase<CaughtExceptionExistenceRule>
 */
class CaughtExceptionExistenceRuleTest extends RuleTestCase
{

	private bool $checkImportedClassNameCase = false;

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new CaughtExceptionExistenceRule(
			$reflectionProvider,
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true, $this->checkImportedClassNameCase),
				new ClassForbiddenNameCheck($container->getExtensionsCollection(ForbiddenClassNameExtension::class)),
				$reflectionProvider,
				$container->getExtensionsCollection(RestrictedClassNameUsageExtension::class),
			),
			true,
			true,
		);
	}

	public function testCheckCaughtException(): void
	{
		$this->analyse([__DIR__ . '/data/catch.php'], [
			[
				'Caught class TestCatch\FooCatch is not an exception.',
				17,
			],
			[
				'Caught class FooCatchException not found.',
				29,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',

			],
			[
				'Class TestCatch\MyCatchException referenced with incorrect case: TestCatch\MyCatchEXCEPTION.',
				41,
			],
		]);
	}

	public function testClassExists(): void
	{
		$this->analyse([__DIR__ . '/data/class-exists.php'], []);
	}

	public function testBug3690(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3690.php'], []);
	}

	public function testPhpstanInternalClass(): void
	{
		$tip = 'This is most likely unintentional. Did you mean to type \PrefixedRuntimeException?';

		$this->analyse([__DIR__ . '/../Classes/data/phpstan-internal-class.php'], [
			[
				'Referencing prefixed PHPStan class: _PHPStan_156ee64ba\PrefixedRuntimeException.',
				19,
				$tip,
			],
		]);
	}

	public static function dataBug12827(): array
	{
		return [
			[true],
			[false],
		];
	}

	#[DataProvider('dataBug12827')]
	public function testBug12827(bool $checkImportedClassNameCase): void
	{
		$this->checkImportedClassNameCase = $checkImportedClassNameCase;

		$expectedErrors = [];
		if ($checkImportedClassNameCase) {
			$expectedErrors[] = [
				'Class Bug12827Exceptions\MissingRoutingReferenceException referenced with incorrect case: Bug12827Exceptions\MissingRoutingreferenceException.',
				20,
			];
		}

		$this->analyse([__DIR__ . '/data/bug-12827.php'], $expectedErrors);
	}

}
