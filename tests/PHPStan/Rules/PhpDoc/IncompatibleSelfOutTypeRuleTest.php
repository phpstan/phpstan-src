<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<IncompatibleSelfOutTypeRule>
 */
class IncompatibleSelfOutTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IncompatibleSelfOutTypeRule(new UnresolvableTypeHelper(), new GenericObjectTypeCheck());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-self-out-type.php'], [
			[
				'Self-out type int of method IncompatibleSelfOutType\A::three is not subtype of IncompatibleSelfOutType\A.',
				23,
			],
			[
				'Self-out type IncompatibleSelfOutType\A|null of method IncompatibleSelfOutType\A::four is not subtype of IncompatibleSelfOutType\A.',
				28,
			],
			[
				'PHPDoc tag @phpstan-self-out is not supported above static method IncompatibleSelfOutType\Foo::selfOutStatic().',
				38,
			],
			[
				'PHPDoc tag @phpstan-self-out for method IncompatibleSelfOutType\Foo::doFoo() contains unresolvable type.',
				46,
			],
			[
				'PHPDoc tag @phpstan-self-out for method IncompatibleSelfOutType\Foo::doBar() contains unresolvable type.',
				54,
			],
			[
				'PHPDoc tag @phpstan-self-out contains generic type IncompatibleSelfOutType\GenericCheck<int> but class IncompatibleSelfOutType\GenericCheck is not generic.',
				67,
			],
			[
				'Generic type IncompatibleSelfOutType\GenericCheck2<InvalidArgumentException> in PHPDoc tag @phpstan-self-out does not specify all template types of class IncompatibleSelfOutType\GenericCheck2: T, U',
				84,
			],
			[
				'Generic type IncompatibleSelfOutType\GenericCheck2<InvalidArgumentException, int<1, max>, string> in PHPDoc tag @phpstan-self-out specifies 3 template types, but class IncompatibleSelfOutType\GenericCheck2 supports only 2: T, U',
				92,
			],
			[
				'Type string in generic type IncompatibleSelfOutType\GenericCheck2<InvalidArgumentException, string> in PHPDoc tag @phpstan-self-out is not subtype of template type U of int of class IncompatibleSelfOutType\GenericCheck2.',
				100,
			],
		]);
	}

}
