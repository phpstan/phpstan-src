<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node;
use PHPStan\Parser\Parser;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;
use function dirname;

final class PathRoutingParserBench
{

	private ?Parser $parser = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function stubs(): iterable
	{
		$stubs = dirname(__DIR__, 2) . '/vendor/phpstan/php-8-stubs/stubs';
		yield 'function' => [$stubs . '/Zend/strlen.php'];
		yield 'class' => [$stubs . '/ext/pdo/PDO.php'];
	}

	#[Before]
	public function boot(string $file): void
	{
		$parser = BenchContainer::create($this->scratch)->getService('pathRoutingParser');
		if (!$parser instanceof Parser) {
			throw new LogicException('pathRoutingParser is not a parser.');
		}

		// another stub loads the parser's classes, then the benchmark's stub is parsed the first time
		$parser->parseFile(dirname(__DIR__, 2) . '/vendor/phpstan/php-8-stubs/stubs/Zend/ArrayAccess.php');
		$parser->parseFile($file);
		$this->parser = $parser;
	}

	/**
	 * Reflection asks for the nodes of a PHP 8 stub again for each symbol it declares.
	 *
	 * @return Node[]
	 */
	#[DataProvider(method: 'stubs')]
	#[ExpectedFor(dataSet: 'class', instructions: '6.901K', cycles: '57.94K', memory: '0B')]
	#[ExpectedFor(dataSet: 'function', instructions: '6.893K', cycles: '57.97K', memory: '0B')]
	public function benchParsePhp8StubAgain(string $file): array
	{
		if ($this->parser === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->parser->parseFile($file);
	}

}
