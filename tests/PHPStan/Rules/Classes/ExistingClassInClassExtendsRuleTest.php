<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ExistingClassInClassExtendsRule>
 */
class ExistingClassInClassExtendsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new ExistingClassInClassExtendsRule(
			new ClassCaseSensitivityCheck($broker, true),
			$broker
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
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('This test needs static reflection');
		}

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

}
