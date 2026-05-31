<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UsedAsStringRule>
 */
class UsedAsStringRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UsedAsStringRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/used-as-string.php'], [
			[
				"Used as string: 'plain' ('plain')",
				9,
			],
			[
				'Used as string: \'<script src="\' . $s . \'" nonce=123></script>\' (non-falsy-string)',
				10,
			],
			[
				'Used as string: "<script src=\"{$s}\" nonce=123></script>" (non-falsy-string)',
				11,
			],
			[
				"Used as string: 'printed' ('printed')",
				12,
			],
			[
				"Used as string: 'a' . \$s . 'b' (non-falsy-string)",
				13,
			],
			[
				'Used as string: $s (string)',
				14,
			],
			[
				'Used as string: $s .= "appended" (non-falsy-string)',
				15,
			],
			[
				'Used as string: $s .= \' src="\' . $s . \'"\' (non-falsy-string)',
				16,
			],
			[
				"Used as string: \$s . 'plain' (non-falsy-string)",
				17,
			],
			[
				'Used as string: "interp {$s} end" (non-falsy-string)',
				18,
			],
			[
				"Used as string: '<script src=\"my.js\" nonce=123></script>\n' (\"<script src=\\\"my.js\\\" nonce=123></script>\\n\")",
				20,
			],
			[
				"Used as string: '123' ('123')",
				26,
			],
			[
				"Used as string: '' ('')",
				27,
			],
			[
				"Used as string: \$html .= <<<EOS\n<script nonce=\"{\$nonce}\" type=\"module\">\nEOS ('<script nonce=\"123\" type=\"module\">')",
				28,
			],
			[
				"Used as string: '' ('')",
				36,
			],
			[
				"Used as string: '' ('')",
				40,
			],
			[
				"Used as string: 'assigned to string property' ('assigned to string property')",
				50,
			],
			[
				"Used as string: 'assigned to static string property' ('assigned to static string property')",
				52,
			],
			[
				'Used as string: $h->{$name} (mixed)',
				59,
			],
			[
				'Used as string: $name (string)',
				59,
			],
			[
				'Used as string: $name (string)',
				60,
			],
			[
				'Used as string: $name (string)',
				61,
			],
			[
				'Used as string: $name (string)',
				62,
			],
			[
				'Used as string: $name (string)',
				63,
			],
			[
				'Used as string: $name (string)',
				64,
			],
		]);
	}

}
