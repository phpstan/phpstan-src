<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DumpTypeRule>
 */
class DumpTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DumpTypeRule($this->createReflectionProvider());
	}

	public function testRuleInPhpStanNamespace(): void
	{
		$this->analyse([__DIR__ . '/data/dump-type.php'], [
			[
				'Dumped type: non-empty-array',
				10,
			],
		]);
	}

	public function testRuleInDifferentNamespace(): void
	{
		$this->analyse([__DIR__ . '/data/dump-type-ns.php'], [
			[
				'Dumped type: non-empty-array',
				10,
			],
		]);
	}

	public function testRuleInUse(): void
	{
		$this->analyse([__DIR__ . '/data/dump-type-use.php'], [
			[
				'Dumped type: non-empty-array',
				12,
			],
			[
				'Dumped type: non-empty-array',
				13,
			],
		]);
	}

	public function testRuleSymbols(): void
	{
		$this->analyse([__DIR__ . '/data/dump-type-symbols.php'], [
			[
				'Dumped type: array{"\000": \'NUL\', NUL: "\000"}',
				6,
			],
			[
				'Dumped type: array{"\001": \'SOH\', SOH: "\001"}',
				7,
			],
			[
				'Dumped type: array{"\002": \'STX\', STX: "\002"}',
				8,
			],
			[
				'Dumped type: array{"\003": \'ETX\', ETX: "\003"}',
				9,
			],
			[
				'Dumped type: array{"\004": \'EOT\', EOT: "\004"}',
				10,
			],
			[
				'Dumped type: array{"\005": \'ENQ\', ENQ: "\005"}',
				11,
			],
			[
				'Dumped type: array{"\006": \'ACK\', ACK: "\006"}',
				12,
			],
			[
				'Dumped type: array{"\a": \'BEL\', BEL: "\a"}',
				13,
			],
			[
				'Dumped type: array{"\b": \'BS\', BS: "\b"}',
				14,
			],
			[
				'Dumped type: array{"\t": \'HT\', HT: "\t"}',
				15,
			],
			[
				'Dumped type: array{"\n": \'LF\', LF: "\n"}',
				16,
			],
			[
				'Dumped type: array{"\v": \'VT\', VT: "\v"}',
				17,
			],
			[
				'Dumped type: array{"\f": \'FF\', FF: "\f"}',
				18,
			],
			[
				'Dumped type: array{"\r": \'CR\', CR: "\r"}',
				19,
			],
			[
				'Dumped type: array{"\016": \'SO\', SO: "\016"}',
				20,
			],
			[
				'Dumped type: array{"\017": \'SI\', SI: "\017"}',
				21,
			],
			[
				'Dumped type: array{"\020": \'DLE\', DLE: "\020"}',
				22,
			],
			[
				'Dumped type: array{"\021": \'DC1\', DC1: "\021"}',
				23,
			],
			[
				'Dumped type: array{"\022": \'DC2\', DC2: "\022"}',
				24,
			],
			[
				'Dumped type: array{"\023": \'DC3\', DC3: "\023"}',
				25,
			],
			[
				'Dumped type: array{"\024": \'DC4\', DC4: "\024"}',
				26,
			],
			[
				'Dumped type: array{"\025": \'NAK\', NAK: "\025"}',
				27,
			],
			[
				'Dumped type: array{"\026": \'SYN\', SYN: "\026"}',
				28,
			],
			[
				'Dumped type: array{"\027": \'ETB\', ETB: "\027"}',
				29,
			],
			[
				'Dumped type: array{"\030": \'CAN\', CAN: "\030"}',
				30,
			],
			[
				'Dumped type: array{"\031": \'EM\', EM: "\031"}',
				31,
			],
			[
				'Dumped type: array{"\032": \'SUB\', SUB: "\032"}',
				32,
			],
			[
				'Dumped type: array{"\033": \'ESC\', ESC: "\033"}',
				33,
			],
			[
				'Dumped type: array{"\034": \'FS\', FS: "\034"}',
				34,
			],
			[
				'Dumped type: array{"\035": \'GS\', GS: "\035"}',
				35,
			],
			[
				'Dumped type: array{"\036": \'RS\', RS: "\036"}',
				36,
			],
			[
				'Dumped type: array{"\037": \'US\', US: "\037"}',
				37,
			],
			[
				'Dumped type: array{"\177": \'DEL\', DEL: "\177"}',
				38,
			],
			[
				'Dumped type: array{\' \': \'SP\', SP: \' \'}',
				41,
			],
			[
				"Dumped type: array{'foo ': 'ends with SP', ' foo': 'starts with SP', ' foo ': 'surrounded by SP', foo: 'no SP'}",
				42,
			],
			[
				"Dumped type: array{'foo?': 'foo?'}",
				45,
			],
			[
				"Dumped type: array{shallwedance: 'yes'}",
				46,
			],
			[
				"Dumped type: array{'shallwedance?': 'yes'}",
				47,
			],
			[
				"Dumped type: array{'Shall we dance': 'yes'}",
				48,
			],
			[
				"Dumped type: array{'Shall we dance?': 'yes'}",
				49,
			],
			[
				"Dumped type: array{shall_we_dance: 'yes'}",
				50,
			],
			[
				"Dumped type: array{'shall_we_dance?': 'yes'}",
				51,
			],
			[
				"Dumped type: array{shall-we-dance: 'yes'}",
				52,
			],
			[
				"Dumped type: array{'shall-we-dance?': 'yes'}",
				53,
			],
			[
				'Dumped type: array{"Let\'go": "Let\'go"}',
				54,
			],
			[
				'Dumped type: array{\'"HELLO!!"\': \'"HELLO!!"\'}',
				55,
			],
			[
				'Dumped type: array{"Don\'t say \"lazy\"": "Don\'t say \"lazy\""}',
				56,
			],
			[
				"Dumped type: array{'Foo\\\\Bar': 'Foo\\\\Bar'}",
				57,
			],
		]);
	}

	public function testBug7803(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7803.php'], [
			[
				'Dumped type: int<4, max>',
				11,
			],
			[
				'Dumped type: non-empty-array<int, string>',
				12,
			],
			[
				'Dumped type: int<4, max>',
				13,
			],
		]);
	}

	public function testBug10377(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10377.php'], [
			[
				'Dumped type: array<string, mixed>',
				22,
			],
			[
				'Dumped type: array<string, mixed>',
				34,
			],
		]);
	}

	public function testBug11179(): void
	{
		$this->analyse([__DIR__ . '/../DeadCode/data/bug-11179.php'], [
			[
				'Dumped type: string',
				9,
			],
		]);
	}

	public function testBug11179NoNamespace(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11179-no-namespace.php'], [
			[
				'Dumped type: string',
				11,
			],
		]);
	}

}
