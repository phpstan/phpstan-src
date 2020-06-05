<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<AbstractMethodInNonAbstractClassRule>
 */
class AbstractMethodInNonAbstractClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new AbstractMethodInNonAbstractClassRule();
	}

	public function testRule(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}
		$this->analyse([__DIR__ . '/data/abstract-method.php'], [
			[
				'Non-abstract class AbstractMethod\Bar contains abstract method doBar().',
				15,
			],
			[
				'Non-abstract class AbstractMethod\Baz contains abstract method doBar().',
				22,
			],
		]);
	}

}
