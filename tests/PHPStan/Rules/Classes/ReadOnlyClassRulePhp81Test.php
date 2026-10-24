<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ReadOnlyClassRule>
 */
class ReadOnlyClassRulePhp81Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReadOnlyClassRule();
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testBug15425(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15425-readonly.php'], [
			[
				'Readonly classes are supported only on PHP 8.2 and later.',
				8,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/readOnlyClassPhp81.neon',
		];
	}

}
