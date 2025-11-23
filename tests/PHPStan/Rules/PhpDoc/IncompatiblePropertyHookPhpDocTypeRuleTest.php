<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Generics\TemplateTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<IncompatiblePropertyHookPhpDocTypeRule>
 */
class IncompatiblePropertyHookPhpDocTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver([], $reflectionProvider);

		$container = self::getContainer();
		return new IncompatiblePropertyHookPhpDocTypeRule(
			$container->getByType(FileTypeMapper::class),
			new IncompatiblePhpDocTypeCheck(
				new GenericObjectTypeCheck(),
				new UnresolvableTypeHelper(),
				new GenericCallableRuleHelper(
					new TemplateTypeCheck(
						$reflectionProvider,
						new ClassNameCheck(
							new ClassCaseSensitivityCheck($reflectionProvider, true),
							new ClassForbiddenNameCheck($container),
							$reflectionProvider,
							$container,
						),
						new GenericObjectTypeCheck(),
						$typeAliasResolver,
						true,
					),
				),
			),
		);
	}

	#[RequiresPhp('>= 8.4')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-property-hook-phpdoc-types.php'], [
			[
				'PHPDoc tag @return with type string is incompatible with native type int.',
				10,
			],
			[
				'PHPDoc tag @return with type string is incompatible with native type void.',
				17,
			],
			[
				'PHPDoc tag @param for parameter $value with type string is incompatible with native type int.',
				27,
			],
			[
				'Parameter $value for PHPDoc tag @param-out is not passed by reference.',
				27,
			],
			[
				'PHPDoc tag @param for parameter $value contains unresolvable type.',
				34,
			],
			[
				'PHPDoc tag @param for parameter $value contains generic type Exception<int> but class Exception is not generic.',
				41,
			],
			[
				'PHPDoc tag @param for parameter $value template T of callable<T of mixed>(T): T shadows @template T for class IncompatiblePropertyHookPhpDocTypes\GenericFoo.',
				54,
			],
			[
				'PHPDoc tag @param for parameter $value template of callable<\stdClass of mixed>(T): T cannot have existing class \stdClass as its name.',
				61,
			],
		]);
	}

}
