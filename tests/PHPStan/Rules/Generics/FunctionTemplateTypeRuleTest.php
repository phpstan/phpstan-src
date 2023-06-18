<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends RuleTestCase<FunctionTemplateTypeRule>
 */
class FunctionTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver(['TypeAlias' => 'int'], $broker);

		return new FunctionTemplateTypeRule(
			self::getContainer()->getByType(FileTypeMapper::class),
			new TemplateTypeCheck($broker, new ClassCaseSensitivityCheck($broker, true), new GenericObjectTypeCheck(), $typeAliasResolver, true),
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
				'PHPDoc tag @template for function FunctionTemplateType\lorem() cannot have existing type alias TypeAlias as its name.',
				32,
			],
			[
				'PHPDoc tag @template T for function FunctionTemplateType\resourceBound() with bound type resource is not supported.',
				50,
			],
			[
				'PHPDoc tag @template T for function FunctionTemplateType\nullNotSupported() with bound type null is not supported.',
				68,
			],
			[
				'Type projection covariant int in generic type FunctionTemplateType\GenericCovariant<covariant int> in PHPDoc tag @template U is redundant, template type T of class FunctionTemplateType\GenericCovariant has the same variance.',
				94,
				'You can safely remove the type projection.',
			],
			[
				'Type projection contravariant int in generic type FunctionTemplateType\GenericCovariant<contravariant int> in PHPDoc tag @template W is conflicting with variance of template type T of class FunctionTemplateType\GenericCovariant.',
				94,
			],
		]);
	}

	public function testBug3769(): void
	{
		require_once __DIR__ . '/data/bug-3769.php';
		$this->analyse([__DIR__ . '/data/bug-3769.php'], []);
	}

}
