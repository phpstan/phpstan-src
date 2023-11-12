<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

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

	public function testNativeType(): void
	{
		if (PHP_VERSION_ID < 80300) {
			$this->markTestSkipped('Test requires PHP 8.3.');
		}

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

}
