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
				62,
			],
			[
				"Used as string: 'assigned to static string property' ('assigned to static string property')",
				64,
			],
			[
				"Used as string: 'assigned to union string property' ('assigned to union string property')",
				65,
			],
			[
				'Used as string: $h->{$name} (mixed)',
				73,
			],
			[
				'Used as string: $name (string)',
				73,
			],
			[
				'Used as string: $name (string)',
				74,
			],
			[
				'Used as string: $name (string)',
				75,
			],
			[
				'Used as string: $name (string)',
				76,
			],
			[
				'Used as string: $name (string)',
				77,
			],
			[
				'Used as string: $name (string)',
				78,
			],
			[
				'Used as string: $s (string)',
				91,
			],
			[
				"Used as string: 'passed as string argument' ('passed as string argument')",
				92,
			],
			[
				"Used as string: 'method string argument' ('method string argument')",
				99,
			],
			[
				"Used as string: 'static string argument' ('static string argument')",
				100,
			],
			[
				"Used as string: 'string default' ('string default')",
				103,
			],
			[
				"Used as string: 'closure string default' ('closure string default')",
				109,
			],
			[
				'Used as string: $s (string)',
				111,
			],
			[
				"Used as string: 'closure string argument' ('closure string argument')",
				112,
			],
			[
				"Used as string: 'arrow string default' ('arrow string default')",
				114,
			],
			[
				'Used as string: $s (string)',
				115,
			],
			[
				"Used as string: 'arrow string argument' ('arrow string argument')",
				116,
			],
			[
				"Used as string: '' ('')",
				121,
			],
			[
				"Used as string: \$html .= <<<'EOS'\n<script nonce=\"123\" type=\"module\">\nEOS ('<script nonce=\"123\" type=\"module\">')",
				122,
			],
			[
				"Used as string: 'union with string argument' ('union with string argument')",
				137,
			],
			[
				'Used as string: $obj (ExprUsedAsString\StringableObject)',
				179,
			],
		]);
	}

}
