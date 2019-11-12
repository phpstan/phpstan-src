<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<MethodTemplateTypeRule>
 */
class MethodTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createBroker();
		return new MethodTemplateTypeRule(
			self::getContainer()->getByType(FileTypeMapper::class),
			new TemplateTypeCheck($broker, new ClassCaseSensitivityCheck($broker), true)
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/method-template.php'], [
			[
				'PHPDoc tag @template for method MethodTemplateType\Foo::doFoo() cannot have existing class stdClass as its name.',
				11,
			],
			[
				'PHPDoc tag @template T for method MethodTemplateType\Foo::doBar() has invalid bound type MethodTemplateType\Zazzzu.',
				19,
			],
			[
				'PHPDoc tag @template T for method MethodTemplateType\Bar::doFoo() shadows @template T of Exception for class MethodTemplateType\Bar.',
				37,
			],
			[
				'PHPDoc tag @template T for method MethodTemplateType\Baz::doFoo() with bound type int is not supported.',
				50,
			],
		]);
	}

}
