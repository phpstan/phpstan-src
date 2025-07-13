<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<SealedRule>
 */
class SealedRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new SealedRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/sealed.php'], [
			[
				'Class Sealed\BaseClass is sealed and only permits Sealed\BarClass|Sealed\FooClass as subtypes, Sealed\BazClass given.',
				11,
			],
			[
				'Interface Sealed\BaseInterface is sealed and only permits Sealed\BarClass2|Sealed\FooClass2 as subtypes, Sealed\BazClass2 given.',
				19,
			],
			[
				'Interface Sealed\BaseInterface2 is sealed and only permits Sealed\BarInterface|Sealed\FooInterface as subtypes, Sealed\BazInterface given.',
				27,
			],
		]);
	}

}
