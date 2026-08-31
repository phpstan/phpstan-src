<?php declare(strict_types = 1);

namespace PHPStan\Process;

use PHPStan\Testing\PHPStanTestCase;
use function defined;
use function sprintf;
use const SIGBUS;
use const SIGKILL;

final class TerminationSignalTest extends PHPStanTestCase
{

	public function testKnownSignal(): void
	{
		if (!defined('SIGKILL')) {
			self::markTestSkipped('Requires ext-pcntl for the SIG* constants.');
		}

		$this->assertSame('9 (SIGKILL)', TerminationSignal::describe(SIGKILL));
	}

	public function testPlatformSpecificNumber(): void
	{
		if (!defined('SIGBUS')) {
			self::markTestSkipped('Requires ext-pcntl for the SIG* constants.');
		}

		// 7 on Linux, 10 on macOS - the name has to come from the platform's
		// own constant rather than a table of numbers.
		$this->assertSame(sprintf('%d (SIGBUS)', SIGBUS), TerminationSignal::describe(SIGBUS));
	}

	public function testUnknownSignal(): void
	{
		$this->assertSame('4242', TerminationSignal::describe(4242));
	}

}
