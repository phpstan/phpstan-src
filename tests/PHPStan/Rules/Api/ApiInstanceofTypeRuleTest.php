<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ApiInstanceofTypeRule>
 */
class ApiInstanceofTypeRuleTest extends RuleTestCase
{

	public function getRule(): Rule
	{
		return new ApiInstanceofTypeRule($this->createReflectionProvider());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/instanceof-type.php'], [
			[
				'Doing instanceof PHPStan\Type\TypeWithClassName is error-prone. Use Type::getObjectClassNames() instead.',
				19,
			],
			[
				'Doing instanceof phpstan\type\typewithclassname is error-prone. Use Type::getObjectClassNames() instead.',
				23,
			],
			[
				'Doing instanceof PHPStan\Type\TypeWithClassName is error-prone. Use Type::getObjectClassNames() instead.',
				35,
			],
		]);
	}

}
