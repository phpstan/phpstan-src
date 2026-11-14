<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\Regex\RegexGroupParser;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;

final class RegexGroupParserBench
{

	private ?RegexGroupParser $parser = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function patterns(): iterable
	{
		yield 'date' => ['/^(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})(?:T(\d{2}):(\d{2})(?::(\d{2}))?)?$/'];
		// a pattern with a modifier the parser does not support is not checked again either
		yield 'unsupported-modifier' => ['/^(?<word>\w+)\s+(\d+)$/J'];
	}

	#[Before]
	public function boot(string $pattern): void
	{
		$this->parser = BenchContainer::create($this->scratch)->getByType(RegexGroupParser::class);
		// preg_match() and the other functions of the analysed code ask for the same pattern again
		$this->parser->parseGroups($pattern);
	}

	#[DataProvider(method: 'patterns')]
	#[ExpectedFor(dataSet: 'date', instructions: '1.426M', cycles: '2.041M', memory: '9.656KiB')]
	#[ExpectedFor(dataSet: 'unsupported-modifier', instructions: '1.286K', cycles: '13.41K', memory: '0B')]
	public function benchParseGroupsAgain(string $pattern): bool
	{
		if ($this->parser === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->parser->parseGroups($pattern) !== null;
	}

}
