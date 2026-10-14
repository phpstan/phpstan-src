<?php declare(strict_types = 1);

namespace PHPStan\Command;

use LogicException;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\Command\Symfony\SymfonyStyle;
use PHPStan\Reflection\BetterReflection\SourceLocator\BootstrapAutoloaderExceptionReporter;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use function fopen;
use function rewind;
use function stream_get_contents;

class BootstrapFilesRunnerTest extends PHPStanTestCase
{

	/**
	 * @return iterable<string, array{bool, bool}>
	 */
	public static function dataReporterIsEnabledOnlyWithDebug(): iterable
	{
		yield 'debug' => [true, true];
		yield 'no debug' => [false, false];
	}

	/**
	 * @throws InceptionNotSuccessfulException
	 */
	#[DataProvider('dataReporterIsEnabledOnlyWithDebug')]
	public function testReporterIsEnabledOnlyWithDebug(bool $debugEnabled, bool $expectPrinted): void
	{
		$stream = fopen('php://memory', 'w+');
		$this->assertNotFalse($stream);
		$output = new StreamOutput($stream);
		$errorOutput = new SymfonyOutput($output, new SymfonyStyle(new \Symfony\Component\Console\Style\SymfonyStyle($this->createStub(InputInterface::class), $output)));

		$reporter = new BootstrapAutoloaderExceptionReporter();
		$runner = new BootstrapFilesRunner(self::getContainer(), $reporter);
		$runner->run($errorOutput, $debugEnabled);
		$reporter->report('SomeClass', new LogicException('Cannot read the class map.'));

		rewind($stream);
		$printed = stream_get_contents($stream);
		$this->assertIsString($printed);
		if ($expectPrinted) {
			$this->assertStringContainsString('by a bootstrap autoloader for class SomeClass: Cannot read the class map.', $printed);
		} else {
			$this->assertSame('', $printed);
		}
	}

	/**
	 * @throws InceptionNotSuccessfulException
	 */
	public function testReporterIsEnabledWhenTheFilesAlreadyRan(): void
	{
		$stream = fopen('php://memory', 'w+');
		$this->assertNotFalse($stream);
		$output = new StreamOutput($stream);
		$errorOutput = new SymfonyOutput($output, new SymfonyStyle(new \Symfony\Component\Console\Style\SymfonyStyle($this->createStub(InputInterface::class), $output)));

		$reporter = new BootstrapAutoloaderExceptionReporter();
		$runner = new BootstrapFilesRunner(self::getContainer(), $reporter);
		$runner->run($errorOutput, false);
		$runner->run($errorOutput, true);
		$reporter->report('SomeClass', new LogicException('Cannot read the class map.'));

		rewind($stream);
		$printed = stream_get_contents($stream);
		$this->assertIsString($printed);
		$this->assertStringContainsString('by a bootstrap autoloader for class SomeClass', $printed);
	}

}
