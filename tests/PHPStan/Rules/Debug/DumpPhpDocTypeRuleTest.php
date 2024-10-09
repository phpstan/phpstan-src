<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DumpPhpDocTypeRule>
 */
class DumpPhpDocTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DumpPhpDocTypeRule($this->createReflectionProvider());
	}

	public function testRuleSymbols(): void
	{
		$this->analyse([__DIR__ . '/data/dump-phpdoc-type.php'], [
			[
				"Dumped type: array{'': ''}",
				5,
			],
			[
				"Dumped type: array{'\0': 'NUL', NUL: '\0'}",
				6,
			],
			[
				"Dumped type: array{' ': 'SP', SP: ' '}",
				9,
			],
			[
				"Dumped type: array{'foo ': 'ends with SP', ' foo': 'starts with SP', ' foo ': 'surrounded by SP', foo: 'no SP'}",
				10,
			],
			[
				"Dumped type: array{'foo?': 'foo?'}",
				13,
			],
			[
				"Dumped type: array{shallwedance: 'yes'}",
				14,
			],
			[
				"Dumped type: array{'shallwedance?': 'yes'}",
				15,
			],
			[
				"Dumped type: array{'Shall we dance': 'yes'}",
				16,
			],
			[
				"Dumped type: array{'Shall we dance?': 'yes'}",
				17,
			],
			[
				"Dumped type: array{shall_we_dance: 'yes'}",
				18,
			],
			[
				"Dumped type: array{'shall_we_dance?': 'yes'}",
				19,
			],
			[
				"Dumped type: array{shall-we-dance: 'yes'}",
				20,
			],
			[
				"Dumped type: array{'shall-we-dance?': 'yes'}",
				21,
			],
			[
				"Dumped type: array{'Let\'s go': 'Let\'s go'}",
				22,
			],
			[
				"Dumped type: array{Foo\\Bar: 'Foo\\\\Bar'}",
				23,
			],
			[
				"Dumped type: T",
				32,
			],
		]);
	}

}
