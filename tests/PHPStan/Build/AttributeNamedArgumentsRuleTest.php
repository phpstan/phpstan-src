<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<AttributeNamedArgumentsRule>
 */
class AttributeNamedArgumentsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new AttributeNamedArgumentsRule(self::createReflectionProvider());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/attribute-arguments.php'], [
			[
				'Attribute PHPStan\DependencyInjection\AutowiredService is not using named arguments.',
				13,
			],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/attribute-arguments.php', __DIR__ . '/data/attribute-arguments.php.fixed');
	}

}
