<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DumpPhpDocTypeRule>
 */
class DumpPhpDocTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DumpPhpDocTypeRule(self::createReflectionProvider(), new Printer());
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
				"Dumped type: array{'\001': 'SOH', SOH: '\001'}",
				7,
			],
			[
				"Dumped type: array{'\t': 'HT', HT: '\t'}",
				8,
			],
			[
				"Dumped type: array{' ': 'SP', SP: ' '}",
				11,
			],
			[
				"Dumped type: array{'foo ': 'ends with SP', ' foo': 'starts with SP', ' foo ': 'surrounded by SP', foo: 'no SP'}",
				12,
			],
			[
				"Dumped type: array{'foo?': 'foo?'}",
				15,
			],
			[
				"Dumped type: array{shallwedance: 'yes'}",
				16,
			],
			[
				"Dumped type: array{'shallwedance?': 'yes'}",
				17,
			],
			[
				"Dumped type: array{'Shall we dance': 'yes'}",
				18,
			],
			[
				"Dumped type: array{'Shall we dance?': 'yes'}",
				19,
			],
			[
				"Dumped type: array{shall_we_dance: 'yes'}",
				20,
			],
			[
				"Dumped type: array{'shall_we_dance?': 'yes'}",
				21,
			],
			[
				"Dumped type: array{shall-we-dance: 'yes'}",
				22,
			],
			[
				"Dumped type: array{'shall-we-dance?': 'yes'}",
				23,
			],
			[
				"Dumped type: array{'Let\'s go': 'Let\'s go'}",
				24,
			],
			[
				"Dumped type: array{Foo\\Bar: 'Foo\\\\Bar'}",
				25,
			],
			[
				"Dumped type: array{'3.14': 3.14}",
				26,
			],
			[
				'Dumped type: array{1: true, 0: false}',
				27,
			],
			[
				'Dumped type: T',
				36,
			],
		]);
	}

}
