<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<UnusedVariableRule>
 */
class UnusedVariableRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedVariableRule(self::getContainer()->getByType(PhpVersion::class));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable.php'], [
			[
				'Value assigned to variable $a is never read.',
				27,
			],
			[
				'Value assigned to variable $a is never read.',
				32,
			],
			[
				'Value assigned to variable $a is never read.',
				40,
			],
			[
				'Value assigned to variable $x is never read.',
				46,
			],
			[
				'Value assigned to variable $a is never read.',
				70,
			],
			[
				'Value assigned to variable $a is never read.',
				76,
			],
			[
				'Value assigned to variable $a is never read.',
				93,
			],
			[
				'Value assigned to variable $a is never read.',
				95,
			],
			[
				'Value assigned to variable $a is never read.',
				101,
			],
			[
				'Value assigned to variable $a is never read.',
				113,
			],
			[
				'Value assigned to variable $k is never read.',
				119,
			],
			[
				'Value assigned to variable $v is never read.',
				126,
			],
			[
				'Value assigned to variable $v is never read.',
				133,
			],
			[
				'Value assigned to variable $a is never read.',
				148,
			],
			[
				'Value assigned to variable $i is never read.',
				157,
			],
			[
				'Value assigned to variable $x is never read.',
				223,
			],
			[
				'Value assigned to variable $x is never read.',
				251,
			],
			[
				'Value assigned to variable $s is never read.',
				264,
			],
			[
				'Value assigned to variable $a is never read.',
				276,
			],
			[
				'Value assigned to variable $f is never read.',
				283,
			],
			[
				'Value assigned to variable $a is never read.',
				303,
			],
			[
				'Value assigned to variable $x is never read.',
				337,
			],
			[
				'Value assigned to variable $title is never read.',
				422,
			],
			[
				'Value assigned to variable $b is never read.',
				614,
			],
			[
				'Value assigned to variable $a is never read.',
				632,
			],
			[
				'Value assigned to variable $a is never read.',
				637,
			],
			[
				'Value assigned to variable $a is never read.',
				703,
			],
			[
				'Value assigned to variable $a is never read.',
				739,
			],
			[
				'Value assigned to variable $a is never read.',
				744,
			],
			[
				'Value assigned to variable $tags is never read.',
				840,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testPhp8(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-php8.php'], [
			[
				'Value assigned to variable $e is never read.',
				23,
			],
			[
				'Value assigned to variable $nightsFrom is never read.',
				98,
			],
		]);
	}

}
