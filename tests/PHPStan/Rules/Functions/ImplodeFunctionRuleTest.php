<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use const PHP_VERSION_ID;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ImplodeFunctionRule>
 */
class ImplodeFunctionRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new ImplodeFunctionRule($reflectionProvider);
	}

	public function testFile(): void
	{
		$this->analyse([__DIR__ . '/data/implode.php'], [
			[
				'Call to implode with invalid nested array argument in union type.',
				9,
			],
			[
				'Call to implode with invalid nested array argument.',
				10,
			],
			[
				'Call to join with invalid nested array argument.',
				11,
			],
		]);
	}

}
