<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Analyser\MutatingScope;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;
use function implode;
use function sprintf;

/**
 * MutatingScope::mergeWith() of the scopes after an if without else: the scope that assigned
 * 64 variables or 64 properties, and the scope before them.
 */
final class MutatingScopeMergeBench
{

	private const ASSIGNS = 64;

	private ?MutatingScope $ours = null;

	private ?MutatingScope $theirs = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function merges(): iterable
	{
		// a scope merged with itself (#4640)
		yield 'same-scope' => ['same-scope'];
		// variables only one side defines become maybe-defined, unless they are super-globals (#4662)
		yield 'variables-on-one-side' => ['variables-on-one-side'];
		// other expressions only one side tracks are forgotten (#4719, #4720)
		yield 'property-fetches-on-one-side' => ['property-fetches-on-one-side'];
	}

	#[Before]
	public function boot(string $merge): void
	{
		$container = BenchContainer::create($this->scratch);
		$scopes = BenchScope::analyse($container, $this->scratch, 'merge', self::code());
		$markers = [
			'same-scope' => ['markerVariables', 'markerVariables'],
			'variables-on-one-side' => ['markerVariables', 'markerVariablesBefore'],
			'property-fetches-on-one-side' => ['markerProperties', 'markerPropertiesBefore'],
		];
		if (!isset($markers[$merge])) {
			throw new LogicException('Unknown merge ' . $merge . '.');
		}
		$this->ours = $scopes->scope($markers[$merge][0]);
		$this->theirs = $scopes->scope($markers[$merge][1]);

		// the analysis merges the same expressions again and again, the first merge may remember what it learned about them
		$this->ours->mergeWith($this->theirs);
	}

	#[DataProvider(method: 'merges')]
	#[ExpectedFor(dataSet: 'property-fetches-on-one-side', instructions: '1.749M', cycles: '2.291M', memory: '82.25KiB')]
	#[ExpectedFor('same-scope', instructions: '1.083K', cycles: '12.96K', memory: '0B')]
	#[ExpectedFor(dataSet: 'variables-on-one-side', instructions: '890.9K', cycles: '1.271M', memory: '53.26KiB')]
	public function benchMergeWith(string $merge): MutatingScope
	{
		if ($this->ours === null || $this->theirs === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->ours->mergeWith($this->theirs);
	}

	private static function code(): string
	{
		$properties = [];
		$propertyAssigns = [];
		$variableAssigns = [];
		for ($i = 0; $i < self::ASSIGNS; $i++) {
			$properties[] = sprintf('public int $p%d;', $i);
			$propertyAssigns[] = sprintf('$this->p%d = $i;', $i);
			$variableAssigns[] = sprintf('$v%d = $i;', $i);
		}

		return sprintf(<<<'PHP'
			<?php declare(strict_types = 1);

			namespace MutatingScopeMergeBench;

			class Foo
			{

				%s

				public function variables(int $i): void
				{
					markerVariablesBefore();
					%s
					markerVariables();
				}

				public function properties(int $i): void
				{
					markerPropertiesBefore();
					%s
					markerProperties();
				}

			}
			PHP, implode("\n", $properties), implode("\n", $variableAssigns), implode("\n", $propertyAssigns));
	}

}
