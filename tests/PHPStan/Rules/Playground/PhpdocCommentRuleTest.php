<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<PhpdocCommentRule>
 */
class PhpdocCommentRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PhpdocCommentRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/comments.php'], [
			[
				'Comment contains PHPDoc tag but does not start with /** prefix.',
				13,
			],
			[
				'Comment contains PHPDoc tag but does not start with /** prefix.',
				23,
			],
		]);
	}

}
