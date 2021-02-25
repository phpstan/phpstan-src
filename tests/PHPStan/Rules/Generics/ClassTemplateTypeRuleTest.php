<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ClassTemplateTypeRule>
 */
class ClassTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();

		return new ClassTemplateTypeRule(
			new TemplateTypeCheck($broker, new ClassCaseSensitivityCheck($broker), ['TypeAlias' => 'int'], true)
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/class-template.php'], [
			[
				'PHPDoc tag @template for class ClassTemplateType\Foo cannot have existing class stdClass as its name.',
				8,
			],
			[
				'PHPDoc tag @template T for class ClassTemplateType\Bar has invalid bound type ClassTemplateType\Zazzzu.',
				16,
			],
			[
				'PHPDoc tag @template T for class ClassTemplateType\Baz with bound type float is not supported.',
				24,
			],
			[
				'Class ClassTemplateType\Baz referenced with incorrect case: ClassTemplateType\baz.',
				32,
			],
			[
				'PHPDoc tag @template for class ClassTemplateType\Ipsum cannot have existing type alias TypeAlias as its name.',
				40,
			],
			[
				'PHPDoc tag @template for anonymous class cannot have existing class stdClass as its name.',
				45,
			],
			[
				'PHPDoc tag @template T for anonymous class has invalid bound type ClassTemplateType\Zazzzu.',
				50,
			],
			[
				'PHPDoc tag @template T for anonymous class with bound type float is not supported.',
				55,
			],
			[
				'Class ClassTemplateType\Baz referenced with incorrect case: ClassTemplateType\baz.',
				60,
			],
			[
				'PHPDoc tag @template for anonymous class cannot have existing type alias TypeAlias as its name.',
				65,
			],
		]);
	}

}
