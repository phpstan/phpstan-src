<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends RuleTestCase<ConflictingPurityTagsRule>
 */
class ConflictingPurityTagsRuleTest extends RuleTestCase
{

	public function getRule(): Rule
	{
		return new ConflictingPurityTagsRule(self::getContainer()->getByType(FileTypeMapper::class));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/conflicting-purity-tags.php'], [
			[
				'Function ConflictingPurityTags\pureWithParameterPassed() is marked as pure, which conflicts with @pure-unless-parameter-passed for parameter $count.',
				10,
			],
			[
				'Function ConflictingPurityTags\impureWithCallable() is marked as impure, which conflicts with @pure-unless-callable-is-impure for parameter $cb.',
				20,
			],
			[
				'Method ConflictingPurityTags\Replacer::both() is marked as impure, which conflicts with @pure-unless-callable-is-impure for parameter $cb.',
				46,
			],
			[
				'Method ConflictingPurityTags\Replacer::both() is marked as impure, which conflicts with @pure-unless-parameter-passed for parameter $count.',
				46,
			],
			[
				'Function ConflictingPurityTags\pureWithCallable() is marked as pure, which conflicts with @pure-unless-callable-is-impure for parameter $cb.',
				86,
			],
			[
				'Function ConflictingPurityTags\prefixedTags() is marked as pure, which conflicts with @pure-unless-callable-is-impure for parameter $cb.',
				96,
			],
			[
				'Function ConflictingPurityTags\pureAndImpure() is marked as pure, which conflicts with @pure-unless-callable-is-impure for parameter $cb.',
				107,
			],
		]);
	}

}
