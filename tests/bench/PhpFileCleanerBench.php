<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Reflection\BetterReflection\SourceLocator\PhpFileCleaner;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use function sprintf;
use function str_repeat;

final class PhpFileCleanerBench
{

	private ?PhpFileCleaner $cleaner = null;

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function files(): iterable
	{
		yield 'code' => [self::file(static fn (int $i): string => sprintf("\tpublic function method%d(int \$value): int\n\t{\n\t\treturn \$value + %d;\n\t}\n\n", $i, $i))];
		yield 'docblocks' => [self::file(static fn (int $i): string => sprintf("\t/**\n\t * Returns the value increased by %d.\n%s\t */\n\tpublic function method%d(int \$value): int\n\t{\n\t\treturn \$value + %d;\n\t}\n\n", $i, str_repeat("\t * Lorem ipsum dolor sit amet, consectetur adipiscing elit.\n", 4), $i, $i))];
		yield 'line-comments' => [self::file(static fn (int $i): string => sprintf("\tpublic function method%d(int \$value): int\n\t{\n%s\t\treturn \$value + %d;\n\t}\n\n", $i, str_repeat("\t\t// lorem ipsum dolor sit amet, consectetur adipiscing elit\n", 4), $i))];
		yield 'strings' => [self::file(static fn (int $i): string => sprintf("\tpublic function method%d(): string\n\t{\n\t\treturn 'lorem ipsum dolor sit amet' . \"consectetur adipiscing elit %d\";\n\t}\n\n", $i, $i))];
	}

	#[Before]
	public function boot(): void
	{
		$this->cleaner = new PhpFileCleaner();
		$this->cleaner->clean("<?php\n/** warmup */\nclass Warmup { const A = 'a'; // b\n}\n", 2);
	}

	/**
	 * The symbol scan of the optimized directory locators cleans every file it finds, with as many
	 * matches as the file may declare symbols.
	 */
	#[DataProvider(method: 'files')]
	#[ExpectedFor(dataSet: 'code', instructions: '3.054M', cycles: '3.365M', memory: '4.172KiB')]
	#[ExpectedFor(dataSet: 'docblocks', instructions: '3.788M', cycles: '4.135M', memory: '4.172KiB')]
	#[ExpectedFor(dataSet: 'line-comments', instructions: '3.947M', cycles: '4.288M', memory: '8.172KiB')]
	#[ExpectedFor(dataSet: 'strings', instructions: '2.909M', cycles: '3.235M', memory: '4.164KiB')]
	public function benchClean(string $contents): string
	{
		if ($this->cleaner === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->cleaner->clean($contents, 2);
	}

	/**
	 * @param callable(int): string $method
	 */
	private static function file(callable $method): string
	{
		$code = "<?php declare(strict_types = 1);\n\nnamespace PhpFileCleanerBench;\n\nfinal class Methods\n{\n\n";
		for ($i = 0; $i < 50; $i++) {
			$code .= $method($i);
		}

		return $code . "}\n\nfunction helper(): void\n{\n}\n";
	}

}
