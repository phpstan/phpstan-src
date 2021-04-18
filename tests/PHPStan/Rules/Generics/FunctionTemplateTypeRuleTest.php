<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<FunctionTemplateTypeRule>
 */
class FunctionTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver(['TypeAlias' => 'int'], $broker);

		return new FunctionTemplateTypeRule(
			self::getContainer()->getByType(FileTypeMapper::class),
			new TemplateTypeCheck($broker, new ClassCaseSensitivityCheck($broker), new GenericObjectTypeCheck(), $typeAliasResolver, true)
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/function-template.php'], [
			[
				'PHPDoc tag @template for function FunctionTemplateType\foo() cannot have existing class stdClass as its name.',
				8,
			],
			[
				'PHPDoc tag @template T for function FunctionTemplateType\bar() has invalid bound type FunctionTemplateType\Zazzzu.',
				16,
			],
			[
				'PHPDoc tag @template T for function FunctionTemplateType\baz() with bound type float is not supported.',
				24,
			],
			[
				'PHPDoc tag @template for function FunctionTemplateType\lorem() cannot have existing type alias TypeAlias as its name.',
				32,
			],
		]);
	}

	public function testBug3769(): void
	{
		require_once __DIR__ . '/data/bug-3769.php';
		$this->analyse([__DIR__ . '/data/bug-3769.php'], []);
	}

}
