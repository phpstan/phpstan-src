<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<IncompatibleClassConstantPhpDocTypeRule>
 */
class IncompatibleClassConstantPhpDocTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IncompatibleClassConstantPhpDocTypeRule(new GenericObjectTypeCheck(), new UnresolvableTypeHelper(), self::getContainer()->getByType(InitializerExprTypeResolver::class));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-class-constant-phpdoc.php'], [
			[
				'PHPDoc tag @var for constant IncompatibleClassConstantPhpDoc\Foo::FOO contains unresolvable type.',
				9,
			],
			[
				'PHPDoc tag @var for constant IncompatibleClassConstantPhpDoc\Foo::BAZ with type string is incompatible with value 1.',
				17,
			],
			[
				'PHPDoc tag @var for constant IncompatibleClassConstantPhpDoc\Foo::DOLOR with type IncompatibleClassConstantPhpDoc\Foo<int> is incompatible with value 1.',
				26,
			],
			[
				'PHPDoc tag @var for constant IncompatibleClassConstantPhpDoc\Foo::DOLOR contains generic type IncompatibleClassConstantPhpDoc\Foo<int> but class IncompatibleClassConstantPhpDoc\Foo is not generic.',
				26,
			],
			[
				'PHPDoc tag @var for constant IncompatibleClassConstantPhpDoc\Bar::BAZ with type string is incompatible with value 2.',
				35,
			],
		]);
	}

	public function testBug7352(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7352.php'], []);
	}

	public function testBug7352WithSubNamespace(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7352-with-sub-namespace.php'], []);
	}

	public function testBug7273(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7273.php'], []);
	}

	public function testBug7273b(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7273b.php'], []);
	}

	public function testBug5655(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5655.php'], []);
	}

}
