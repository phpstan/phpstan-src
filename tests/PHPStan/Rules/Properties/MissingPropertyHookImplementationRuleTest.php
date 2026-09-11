<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MissingPropertyHookImplementationRule>
 */
class MissingPropertyHookImplementationRuleTest extends RuleTestCase
{

	private int $phpVersionId = PHP_VERSION_ID;

	protected function getRule(): Rule
	{
		return new MissingPropertyHookImplementationRule(new PhpVersion($this->phpVersionId));
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/missing-property-hook-implementation.php'], [
			[
				'Non-abstract class MissingPropertyHookImplementation\\MissingGet contains abstract property $name from interface MissingPropertyHookImplementation\\RequiresGet.',
				10,
			],
			[
				'Non-abstract class MissingPropertyHookImplementation\\MissingSet contains abstract property $name from interface MissingPropertyHookImplementation\\RequiresSet.',
				19,
			],
			[
				'Non-abstract class MissingPropertyHookImplementation\\MissingBoth contains abstract property $id from class MissingPropertyHookImplementation\\AbstractBase.',
				28,
			],
			[
				'Non-abstract class MissingPropertyHookImplementation\\MissingTraitHook contains abstract property $active from trait MissingPropertyHookImplementation\\RequiresFromTrait.',
				41,
			],
			[
				'Non-abstract class MissingPropertyHookImplementation\\RequiresGet@anonymous/tests/PHPStan/Rules/Properties/data/missing-property-hook-implementation.php:59 contains abstract property $name from interface MissingPropertyHookImplementation\\RequiresGet.',
				59,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testPhpLessThan84(): void
	{
		$this->phpVersionId = 80300;
		$this->analyse([__DIR__ . '/data/missing-property-hook-implementation.php'], []);
	}

}
