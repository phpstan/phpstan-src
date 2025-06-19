<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<GetTemplateTypeRule>
 */
class GetTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new GetTemplateTypeRule(self::createReflectionProvider());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/get-template-type.php'], [
			[
				'Call to PHPStan\Type\Type::getTemplateType() references unknown template type TSendd on class Generator.',
				15,
			],
			[
				'Call to PHPStan\Type\ObjectType::getTemplateType() references unknown template type TSendd on class Generator.',
				21,
			],
		]);
	}

}
