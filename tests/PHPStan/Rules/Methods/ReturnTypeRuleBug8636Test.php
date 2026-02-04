<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;

/**
 * @extends RuleTestCase<ReturnTypeRule>
 */
class ReturnTypeRuleBug8636Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReturnTypeRule(new FunctionReturnTypeCheck(new RuleLevelHelper(self::createReflectionProvider(), true, false, true, false, false, false, true)));
	}

	public function testBug8636(): void
	{
		ConstantArrayTypeBuilder::setArrayCountLimit(ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT);

		$this->analyse([__DIR__ . '/data/bug-8636.php'], []);
	}

}
