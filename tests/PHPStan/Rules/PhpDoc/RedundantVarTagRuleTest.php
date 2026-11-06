<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use function array_merge;

/**
 * @extends RuleTestCase<RedundantVarTagRule>
 */
class RedundantVarTagRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RedundantVarTagRule(self::getContainer()->getByType(FileTypeMapper::class));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/redundant-var-tag.php'], [
			[
				'PHPDoc tag @var for variable $a with type string is redundant, the variable already has this type.',
				39,
			],
			[
				'PHPDoc tag @var with type string is redundant, the expression already has this type.',
				42,
			],
			[
				'PHPDoc tag @var for variable $c with type string is redundant, the variable already has this type.',
				45,
			],
			[
				'PHPDoc tag @var for variable $g with type list<int> is redundant, the variable already has this type.',
				57,
			],
			[
				'PHPDoc tag @var for variable $h with type array<string, mixed> is redundant, the variable already has this type.',
				60,
			],
			[
				'PHPDoc tag @var for variable $i with type mixed is redundant, the variable already has this type.',
				63,
			],
			[
				'PHPDoc tag @var for variable $a with type string is redundant, the variable already has this type.',
				69,
			],
			[
				'PHPDoc tag @var for variable $k with type int is redundant, the variable already has this type.',
				72,
			],
			[
				'PHPDoc tag @var for variable $m with type string is redundant, the variable already has this type.',
				75,
			],
			[
				'PHPDoc tag @var for variable $value with type int is redundant, the variable already has this type.',
				83,
			],
			[
				'PHPDoc tag @var for variable $key with type int<0, max> is redundant, the variable already has this type.',
				87,
			],
			[
				'PHPDoc tag @var for variable $list with type list<int> is redundant, the variable already has this type.',
				91,
			],
			[
				'PHPDoc tag @var for variable $s with type string is redundant, the variable already has this type.',
				121,
			],
			[
				'PHPDoc tag @var with type string is redundant, the expression already has this type.',
				130,
			],
			[
				'PHPDoc tag @var with type Exception is redundant, the expression already has this type.',
				142,
			],
			[
				'PHPDoc tag @var with type string is redundant, the expression already has this type.',
				225,
			],
			[
				'PHPDoc tag @var with type non-empty-string is redundant, the expression already has this type.',
				233,
			],
			[
				'PHPDoc tag @var for variable $x with type list<int> is redundant, the variable already has this type.',
				391,
			],
			[
				'PHPDoc tag @var for variable $k with type int is redundant, the variable already has this type.',
				419,
			],
			[
				'PHPDoc tag @var for variable $v with type int is redundant, the variable already has this type.',
				419,
			],
			[
				'PHPDoc tag @var with type int is redundant, the expression already has this type.',
				491,
			],
			[
				'PHPDoc tag @var for variable $x with type int is redundant, the variable already has this type.',
				498,
			],
		]);
	}

	public function testBug5308(): void
	{
		// the tags widen 'ab' to string
		$this->analyse([__DIR__ . '/data/bug-5308.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(parent::getAdditionalConfigFiles(), [
			__DIR__ . '/redundant-var-tag.neon',
		]);
	}

}
