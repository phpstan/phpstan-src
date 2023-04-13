<?php

namespace RuleErrorBuilderGenerics;

use PHPStan\Rules\RuleErrorBuilder;
use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		$builder = RuleErrorBuilder::message('test');
		assertType('PHPStan\Rules\RuleErrorBuilder<PHPStan\Rules\RuleError>', $builder);
		assertType('PHPStan\Rules\RuleError', $builder->build());

		$builder->identifier('test');
		assertType('PHPStan\Rules\RuleErrorBuilder<PHPStan\Rules\IdentifierRuleError>', $builder);
		assertType('PHPStan\Rules\IdentifierRuleError', $builder->build());

		assertType('PHPStan\Rules\IdentifierRuleError', RuleErrorBuilder::message('test')->identifier('test')->build());

		$builder->tip('test');
		assertType('PHPStan\Rules\RuleErrorBuilder<PHPStan\Rules\IdentifierRuleError&PHPStan\Rules\TipRuleError>', $builder);
		assertType('PHPStan\Rules\IdentifierRuleError&PHPStan\Rules\TipRuleError', $builder->build());
	}

}
