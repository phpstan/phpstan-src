<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPUnit\Framework\TestCase;

class BenchmarkProfileParserTest extends TestCase
{

	private const PROFILE = <<<'CALLGRIND'
		# callgrind format
		version: 1
		creator: callgrind-3.26.0.codspeed12
		pid: 34
		cmd:  /usr/bin/php8.5 -d ffi.enable=1 tests/bench/measure.php tests/bench/phpstan.neon tests/bench/data/bug-10147.php
		part: 1
		desc: Spawned pid: 35

		desc: I1 cache: 32768 B, 64 B, 8-way associative
		desc: Timerange: Basic block 0 - 0
		desc: Trigger: Client Request: Metadata: phpstan-bench 1.0.0

		positions: line
		events: Ir Dr Dw I1mr D1mr D1mw ILmr DLmr DLmw sysCount sysTime sysCpuTime Ct Cl
		summary: 999

		totals: 999 1 1 1 1 1 1 1 1 1 1 1 1 1

		part: 2
		desc: Benchmark pid: 34

		desc: Timerange: Basic block 0 - 1134541461
		desc: Trigger: Client Request: tests/bench/data/bug-10147.php

		positions: line
		events: Ir Dr Dw I1mr D1mr D1mw ILmr DLmr DLmw sysCount sysTime sysCpuTime Ct Cl
		summary: 0

		fn=zend_execute
		0 10 6 1 3 2 1 3 2 1 0 0 0 591 2636

		totals: 5605163255 1529261174 892831611 32176288 34788426 12883708 89670 1339330 2320891 41472 1274091615 393413506 343745161500 1032710357354

		part: 3
		desc: Timerange: Basic block 1134541461 - 1134541462
		desc: Trigger: Program termination
		events: Ir Dr Dw I1mr D1mr D1mw ILmr DLmr DLmw sysCount sysTime sysCpuTime Ct Cl
		summary: 7

		totals: 7 1 1 1 1 1 1 1 1 1 1 1 1 1
		CALLGRIND;

	public function testParseCallgrindOutput(): void
	{
		$this->assertSame([
			'instructions' => 5_605_163_255,
			// 5605163255 + 10 * (32176288 + 34788426 + 12883708) + 100 * (89670 + 1339330 + 2320891)
			'estimatedCycles' => 6_778_636_575,
		], BenchmarkProfileParser::parseCallgrindOutput(self::PROFILE, 'tests/bench/data/bug-10147.php'));
	}

	public function testParseCallgrindOutputOfOtherBenchmark(): void
	{
		$this->assertNull(BenchmarkProfileParser::parseCallgrindOutput(self::PROFILE, 'tests/bench/data/bug-1014.php'));
	}

	public function testParseCallgrindOutputOfProcessWithoutMeasurement(): void
	{
		$contents = <<<'CALLGRIND'
			# callgrind format
			cmd:  /usr/bin/stty -a
			part: 1
			desc: Trigger: Program termination
			events: Ir Dr Dw I1mr D1mr D1mw ILmr DLmr DLmw
			totals: 21618 5618 2229 497 177 70 491 177 70
			CALLGRIND;

		$this->assertNull(BenchmarkProfileParser::parseCallgrindOutput($contents, 'tests/bench/data/bug-10147.php'));
	}

}
