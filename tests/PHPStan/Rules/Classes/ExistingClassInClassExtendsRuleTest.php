<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ExistingClassInClassExtendsRule>
 */
class ExistingClassInClassExtendsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new ExistingClassInClassExtendsRule(
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true),
				new ClassForbiddenNameCheck($container),
				$reflectionProvider,
				$container,
			),
			$reflectionProvider,
			true,
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

	#[RequiresPhp('>= 8.1')]
	public function testEnums(): void
	{
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
				56,
				$tip,
			],
			[
				'Referencing prefixed PHP-Scoper class: _PhpScoper19ae93be897e\AClass.',
				59,
				$tip,
			],
			[
				'Referencing prefixed PHPUnit class: PHPUnitPHAR\SebastianBergmann\Diff\Exception.',
				62,
				'This is most likely unintentional. Did you mean to type \SebastianBergmann\Diff\Exception?',
			],
			[
				'Referencing prefixed Box class: _HumbugBox02f3b3909847\AClass.',
				73,
				$tip,
			],
		]);
	}

	#[RequiresPhp('>= 8.2')]
	public function testReadonly(): void
	{
		$this->analyse([__DIR__ . '/data/extends-readonly-class.php'], [
			[
				'Readonly class ExtendsReadOnlyClass\Foo extends non-readonly class ExtendsReadOnlyClass\Nonreadonly.',
				25,
			],
			[
				'Non-readonly class ExtendsReadOnlyClass\Bar extends readonly class ExtendsReadOnlyClass\ReadonlyClass.',
				30,
			],
			[
				'Anonymous non-readonly class extends readonly class ExtendsReadOnlyClass\ReadonlyClass.',
				35,
			],
			[
				'Anonymous readonly class extends non-readonly class ExtendsReadOnlyClass\Nonreadonly.',
				39,
			],
		]);
	}

}
