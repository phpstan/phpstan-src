<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Analyser\StatementContext;
use PHPStan\DependencyInjection\Container;
use PHPStan\Parser\Parser;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\Meter;
use Tally\ScratchDirectory;
use function implode;
use function sprintf;
use function str_replace;

/**
 * Walks a few statements the way the analysis of a function body does, with a node callback
 * that collects nothing.
 */
final class NodeScopeResolverWalkBench
{

	private ?Container $container = null;

	private ?MutatingScope $scope = null;

	public function __construct(private Meter $meter, private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function statements(): iterable
	{
		// $r = 'a0' === $t || 'a1' === $t || ...;
		foreach ([16, 32] as $arms) {
			yield 'or-chain-' . $arms => [sprintf('$r = %s;', self::orChain('a', $arms))];
		}

		// writes into nested arrays that are not constant arrays
		$assigns = [];
		for ($i = 0; $i < 16; $i++) {
			$assigns[] = sprintf('$nested[\'k%d\'][\'x\'][%d] = $i;', $i, $i);
		}
		yield 'nested-dim-assign' => [implode("\n", $assigns)];

		// loops converge after a few passes over the body
		yield 'foreach' => ['foreach ($list as $k => $v) { if ($v > $max) { $max = $v; $maxKey = $k; } elseif ($v < 0) { $negative[] = $k; } }'];
		yield 'while' => ['while ($i < 100) { if ($i % 3 === 0) { $s .= \'a\'; } elseif ($i % 3 === 1) { $max = $i; } $i++; }'];
	}

	#[Before]
	public function boot(string $code): void
	{
		$this->container = BenchContainer::create($this->scratch);
		$int = new IntegerType();
		$string = new StringType();
		$scope = $this->container->getByType(ScopeFactory::class)->create(ScopeContext::create(__FILE__));
		$variables = [
			't' => $string,
			'i' => $int,
			's' => $string,
			'max' => $int,
			'list' => new ArrayType($int, $int),
			'negative' => new ArrayType($int, $int),
			'nested' => new ArrayType($string, new ArrayType($string, new ArrayType($int, $int))),
		];
		foreach ($variables as $name => $type) {
			$scope = $scope->assignVariable($name, $type, $type, TrinaryLogic::createYes());
		}
		$this->scope = $scope;

		// the warm-up walks every kind of statement once, with other constants
		foreach (self::statements() as [$warmupCode]) {
			$this->process($this->parse(str_replace(['\'a', '\'k'], ['\'w', '\'w'], $warmupCode)));
		}
	}

	#[DataProvider(method: 'statements')]
	#[ExpectedFor(dataSet: 'foreach', instructions: '16.95M', cycles: '25.08M', memory: '315.2KiB')]
	#[ExpectedFor(dataSet: 'nested-dim-assign', instructions: '277.4M', cycles: '330.1M', memory: '801.3KiB')]
	#[ExpectedFor(dataSet: 'or-chain-16', instructions: '22.42M', cycles: '31.58M', memory: '614.1KiB')]
	#[ExpectedFor(dataSet: 'or-chain-32', instructions: '50.17M', cycles: '67.50M', memory: '1.219MiB')]
	#[ExpectedFor(dataSet: 'while', instructions: '10.43M', cycles: '16.14M', memory: '236.3KiB')]
	public function benchProcessStmtNodes(string $code): Type
	{
		$stmts = $this->meter->exclude(fn (): array => $this->parse($code));

		return $this->process($stmts);
	}

	/**
	 * @param Stmt[] $stmts
	 */
	private function process(array $stmts): Type
	{
		if ($this->container === null || $this->scope === null) {
			throw new LogicException('boot() did not run.');
		}

		$scope = $this->scope;
		$result = $this->container->getByType(NodeScopeResolver::class)->processStmtNodes(
			new Stmt\Namespace_(null, $stmts),
			$stmts,
			$scope,
			static function (Node $node, Scope $scope): void {
			},
			StatementContext::createTopLevel(),
		);

		return $result->getScope()->getType(new Node\Expr\Variable('max'));
	}

	/**
	 * @return Stmt[]
	 */
	private function parse(string $code): array
	{
		if ($this->container === null) {
			throw new LogicException('boot() did not run.');
		}

		/** @var Parser $parser */
		$parser = $this->container->getService('currentPhpVersionRichParser');

		return $parser->parseString('<?php ' . $code);
	}

	private static function orChain(string $prefix, int $arms): string
	{
		$arm = [];
		for ($i = 0; $i < $arms; $i++) {
			$arm[] = sprintf('\'%s%d\' === $t', $prefix, $i);
		}

		return implode(' || ', $arm);
	}

}
