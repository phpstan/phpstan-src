<?php declare(strict_types = 1);

namespace PHPStan\Command\Symfony;

use PHPStan\Command\OutputStyle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\NullOutput;

final class SymfonyOutputTest extends TestCase
{

	/**
	 * @return iterable<string, array{mixed, bool}>
	 */
	public static function dataIsDecorated(): iterable
	{
		yield 'preg_match hit' => [1, true];
		yield 'preg_match miss' => [0, false];
		yield 'already true' => [true, true];
		yield 'already false' => [false, false];
	}

	#[DataProvider('dataIsDecorated')]
	public function testIsDecoratedReturnsBool(mixed $symfonyValue, bool $expected): void
	{
		$symfonyOutput = new class($symfonyValue) extends NullOutput {

			public function __construct(private mixed $value)
			{
			}

			public function isDecorated(): mixed
			{
				return $this->value;
			}

		};

		$output = new SymfonyOutput($symfonyOutput, $this->createStub(OutputStyle::class));

		self::assertSame($expected, $output->isDecorated());
	}

}
