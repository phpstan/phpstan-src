<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ExprUsedAsStringRule>
 */
class ExprUsedAsStringRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ExprUsedAsStringRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/expr-used-as-string.php'], [
			[
				"Used as string: 'plain' ('plain')",
				7,
			],
			[
				'Used as string: \'<script src="\' . $s . \'" nonce=123></script>\' (non-falsy-string)',
				8,
			],
			[
				'Used as string: "<script src=\"{$s}\" nonce=123></script>" (non-falsy-string)',
				9,
			],
			[
				"Used as string: 'printed' ('printed')",
				10,
			],
			[
				"Used as string: 'a' . \$s . 'b' (non-falsy-string)",
				11,
			],
			[
				'Used as string: $s (string)',
				12,
			],
			[
				'Used as string: $s .= "appended" (non-falsy-string)',
				13,
			],
			[
				'Used as string: $s .= \' src="\' . $s . \'"\' (non-falsy-string)',
				14,
			],
			[
				"Used as string: \$s . 'plain' (non-falsy-string)",
				15,
			],
			[
				'Used as string: "interp {$s} end" (non-falsy-string)',
				16,
			],
			[
				"Used as string: '<script src=\"my.js\" nonce=123></script>\n' (\"<script src=\\\"my.js\\\" nonce=123></script>\\n\")",
				18,
			],
			[
				"Used as string: \$html .= <<<EOS\n<script nonce=\"{\$nonce}\" type=\"module\">\nEOS ('<script nonce=\"123\" type=\"module\">')",
				26,
			],
			[
				'Used as string: $h->{$name} (mixed)',
				46,
			],
			[
				'Used as string: $name (string)',
				46,
			],
			[
				'Used as string: $name (string)',
				47,
			],
			[
				'Used as string: $name (string)',
				48,
			],
			[
				'Used as string: $name (string)',
				49,
			],
			[
				'Used as string: $name (string)',
				50,
			],
			[
				'Used as string: $name (string)',
				51,
			],
		]);
	}

}
