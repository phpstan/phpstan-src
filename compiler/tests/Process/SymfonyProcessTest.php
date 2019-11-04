<?php declare(strict_types = 1);

namespace PHPStan\Compiler\Process;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

final class SymfonyProcessTest extends TestCase
{

	public function testGetProcess(): void
	{
		$output = $this->createMock(OutputInterface::class);
		$output->expects(self::once())->method('write');

		$process = (new SymfonyProcess('ls', __DIR__, $output))->getProcess();
		self::assertSame('ls', $process->getCommandLine());
		self::assertSame(__DIR__, $process->getWorkingDirectory());
	}

}
