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
		return new ApiInstanceofTypeRule(self::createReflectionProvider());
	}

	public function testRule(): void
	{
		$tipText = 'Learn more: <fg=cyan>https://phpstan.org/blog/why-is-instanceof-type-wrong-and-getting-deprecated</>';
		$this->analyse([__DIR__ . '/data/instanceof-type.php'], [
			[
				'Doing instanceof PHPStan\Type\TypeWithClassName is error-prone and deprecated. Use Type::getObjectClassNames() or Type::getObjectClassReflections() instead.',
				21,
				$tipText,
			],
			[
				'Doing instanceof phpstan\type\typewithclassname is error-prone and deprecated. Use Type::getObjectClassNames() or Type::getObjectClassReflections() instead.',
				25,
				$tipText,
			],
			[
				'Doing instanceof PHPStan\Type\TypeWithClassName is error-prone and deprecated. Use Type::getObjectClassNames() or Type::getObjectClassReflections() instead.',
				37,
				$tipText,
			],
			[
				'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
				41,
				$tipText,
			],
		]);
	}

}
