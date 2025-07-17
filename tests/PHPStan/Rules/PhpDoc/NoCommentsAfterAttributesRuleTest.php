<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RequiresPhp;

/** @extends RuleTestCase<NoCommentsAfterAttributesRule> */
#[CoversNothing]
class NoCommentsAfterAttributesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NoCommentsAfterAttributesRule();
	}

	public function testRule(): void
	{
		$message = 'No comments after attributes.';

		$this->analyse([__DIR__ . '/data/no-comments-after-attributes.php'], [
			[$message, 37],
			[$message, 41],
			[$message, 45],
			[$message, 50],
			[$message, 53],
			[$message, 58],
			[$message, 62],
			[$message, 71],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testEnum(): void
	{
		$message = 'No comments after attributes.';

		$this->analyse([__DIR__ . '/data/no-comments-after-attributes-enum.php'], [
			[$message, 12],
		]);
	}

}
