<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\Traits\TraitConstantsCollector;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ConstantsInTraitsRule>
 */
class ConstantsInTraitsRuleTest extends RuleTestCase
{

	private int $phpVersionId;

	protected function getRule(): Rule
	{
		return new ConstantsInTraitsRule(new PhpVersion($this->phpVersionId));
	}

	public function dataRule(): array
	{
		return [
			[
				80100,
				[
					[
						'Constant is declared inside a trait but is only supported on PHP 8.2 and later.',
						7,
					],
					[
						'Constant is declared inside a trait but is only supported on PHP 8.2 and later.',
						8,
					],
				],
			],
			[
				80200,
				[],
			],
		];
	}

	/**
	 * @dataProvider dataRule
	 *
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testRule(int $phpVersionId, array $errors): void
	{
		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/constants-in-traits.php'], $errors);
	}

}
