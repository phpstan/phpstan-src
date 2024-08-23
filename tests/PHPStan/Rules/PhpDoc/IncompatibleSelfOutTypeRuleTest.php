<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<IncompatibleSelfOutTypeRule>
 */
class IncompatibleSelfOutTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IncompatibleSelfOutTypeRule(new UnresolvableTypeHelper());
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
		]);
	}

}
