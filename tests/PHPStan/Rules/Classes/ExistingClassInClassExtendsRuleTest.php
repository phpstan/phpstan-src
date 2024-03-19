<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ExistingClassInClassExtendsRule>
 */
class ExistingClassInClassExtendsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new ExistingClassInClassExtendsRule(
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true),
				new ClassForbiddenNameCheck(self::getContainer()),
			),
			$reflectionProvider,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/extends-implements.php'], [
			[
				'Class ExtendsImplements\Foo referenced with incorrect case: ExtendsImplements\FOO.',
				15,
			],
			[
				'Class ExtendsImplements\ExtendsFinalWithAnnotation extends @final class ExtendsImplements\FinalWithAnnotation.',
				43,
			],
		]);
	}

	public function testRuleExtendsError(): void
	{
		$this->analyse([__DIR__ . '/data/extends-error.php'], [
			[
				'Class ExtendsError\Foo extends unknown class ExtendsError\Bar.',
				5,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Class ExtendsError\Lorem extends interface ExtendsError\BazInterface.',
				15,
			],
			[
				'Class ExtendsError\Ipsum extends trait ExtendsError\DolorTrait.',
				25,
			],
			[
				'Anonymous class extends trait ExtendsError\DolorTrait.',
				30,
			],
			[
				'Class ExtendsError\Sit extends final class ExtendsError\FinalFoo.',
				39,
			],
		]);
	}

	public function testFinalByTag(): void
	{
		$this->analyse([__DIR__ . '/data/extends-final-by-tag.php'], [
			[
				'Class ExtendsFinalByTag\Bar2 extends @final class ExtendsFinalByTag\Bar.',
				21,
			],
		]);
	}

	public function testEnums(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('This test needs PHP 8.1');
		}

		$this->analyse([__DIR__ . '/data/class-extends-enum.php'], [
			[
				'Class ClassExtendsEnum\Foo extends enum ClassExtendsEnum\FooEnum.',
				10,
			],
			[
				'Anonymous class extends enum ClassExtendsEnum\FooEnum.',
				16,
			],
		]);
	}

	public function testPhpstanInternalClass(): void
	{
		$tip = 'This is most likely unintentional. Did you mean to type \AClass?';

		$this->analyse([__DIR__ . '/data/phpstan-internal-class.php'], [
			[
				'Referencing prefixed PHPStan class: _PHPStan_156ee64ba\AClass.',
				34,
				$tip,
			],
			[
				'Referencing prefixed Rector class: RectorPrefix202302\AClass.',
				52,
				$tip,
			],
			[
				'Referencing prefixed PHP-Scoper class: _PhpScoper19ae93be897e\AClass.',
				55,
				$tip,
			],
		]);
	}

}
