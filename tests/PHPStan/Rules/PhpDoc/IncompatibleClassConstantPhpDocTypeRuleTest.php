<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<IncompatibleClassConstantPhpDocTypeRule>
 */
class IncompatibleClassConstantPhpDocTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IncompatibleClassConstantPhpDocTypeRule(new GenericObjectTypeCheck(), new UnresolvableTypeHelper());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-class-constant-phpdoc.php'], [
			[
				'PHPDoc tag @var for constant IncompatibleClassConstantPhpDoc\Foo::FOO contains unresolvable type.',
				9,
			],
			[
				'PHPDoc tag @var for constant IncompatibleClassConstantPhpDoc\Foo::DOLOR contains generic type IncompatibleClassConstantPhpDoc\Foo<int> but class IncompatibleClassConstantPhpDoc\Foo is not generic.',
				12,
			],
		]);
	}

	#[RequiresPhp('>= 8.3')]
	public function testNativeType(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-class-constant-phpdoc-native-type.php'], [
			[
				'PHPDoc tag @var for constant IncompatibleClassConstantPhpDocNativeType\Foo::BAZ with type string is incompatible with native type int.',
				14,
			],
			[
				'PHPDoc tag @var for constant IncompatibleClassConstantPhpDocNativeType\Foo::LOREM with type int|string is not subtype of native type int.',
				17,
			],
		]);
	}

	#[RequiresPhp('>= 8.3')]
	public function testBug10911(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10911.php'], []);
	}

}
