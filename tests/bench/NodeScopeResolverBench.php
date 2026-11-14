<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StatementResult;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;
use function sprintf;

/**
 * NodeScopeResolver::processStmtNodes() of a few statements of one kind, in a function whose
 * scope comes from analysing it. The counted statements are parsed apart from the analysed ones,
 * so no scope or node attribute remembers them.
 */
final class NodeScopeResolverBench
{

	private ?NodeScopeResolver $resolver = null;

	private ?MutatingScope $scope = null;

	private ?Node $function = null;

	/** @var list<Node\Stmt> */
	private array $stmts = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function statements(): iterable
	{
		// the assigned types have no falsey part, so there is nothing to remember for if ($v) (#4705, #4716)
		yield 'assign-non-falsy' => [<<<'PHP'
			$v0 = 'a'; $v1 = 'b'; $v2 = 'c'; $v3 = 'd'; $v4 = 'e'; $v5 = 'f'; $v6 = 'g'; $v7 = 'h';
			$v8 = [1]; $v9 = [2]; $v10 = [3]; $v11 = [4]; $v12 = [5]; $v13 = [6]; $v14 = [7]; $v15 = [8];
			PHP];
		// arguments of a user function, none by reference or callable (#4708, #4709, #4710, #4711)
		yield 'func-call-args' => [<<<'PHP'
			take($i, $j, $s, $a, $i, $j, $s, $a);
			take($j, $i, $s, $a, $j, $i, $s, $a);
			take($i, $i, $s, $a, $j, $j, $s, $a);
			take($j, $j, $s, $a, $i, $i, $s, $a);
			PHP];
		// a loop in a loop is not at the top level until the outer one converged (#4706)
		yield 'nested-while' => [<<<'PHP'
			while ($i > 0) {
				while ($j > 0) {
					$j--;
				}
				do {
					$j++;
				} while ($j < 10);
				$i--;
			}
			PHP];
		// the storage is duplicated only at the top level (#4728)
		yield 'nested-foreach' => [<<<'PHP'
			foreach ($a as $k => $v) {
				foreach ($a as $k2 => $v2) {
					$i = $k2;
				}
			}
			PHP];
		// without conditions there is nothing to process in a duplicated storage (#4729)
		yield 'for-without-condition' => [<<<'PHP'
			for (;;) {
				for (;;) {
					$j = 1;
					break;
				}
				$i = 1;
				break;
			}
			PHP];
		// with a default arm the match cannot throw UnhandledMatchError (#4739)
		yield 'match-with-default' => [<<<'PHP'
			$r0 = match ($i) { 1 => 'a', 2 => 'b', 3 => 'c', default => 'd' };
			$r1 = match ($j) { 1 => 'a', 2 => 'b', 3 => 'c', default => 'd' };
			$r2 = match ($s) { 'a' => 1, 'b' => 2, 'c' => 3, default => 4 };
			$r3 = match (true) { $i > 1 => 1, $j > 1 => 2, default => 3 };
			PHP];
	}

	#[Before]
	public function boot(string $statements): void
	{
		$container = BenchContainer::create($this->scratch);
		$this->resolver = $container->getByType(NodeScopeResolver::class);
		$code = self::code($statements);
		$this->scope = BenchScope::analyse($container, $this->scratch, 'analysed', $code)->scope('marker');

		[, $warmupNodes] = BenchScope::parse($container, $this->scratch, 'warmup', $code);
		[, $warmupFunction, $warmupStmts] = BenchScope::markers($warmupNodes)['marker'];
		$this->resolver->processStmtNodes($warmupFunction, $warmupStmts, $this->scope, static function (): void {
		}, StatementContext::createTopLevel());

		[, $nodes] = BenchScope::parse($container, $this->scratch, 'counted', $code);
		[, $this->function, $this->stmts] = BenchScope::markers($nodes)['marker'];
	}

	#[DataProvider(method: 'statements')]
	public function benchProcessStmtNodes(string $statements): StatementResult
	{
		if ($this->resolver === null || $this->scope === null || $this->function === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->resolver->processStmtNodes($this->function, $this->stmts, $this->scope, static function (Node $node, Scope $scope): void {
		}, StatementContext::createTopLevel());
	}

	private static function code(string $statements): string
	{
		return sprintf(<<<'PHP'
			<?php declare(strict_types = 1);

			namespace NodeScopeResolverBench;

			function take(int $a, int $b, string $c, array $d, int $e, int $f, string $g, array $h): void
			{
			}

			/**
			 * @param list<int> $a
			 */
			function doFoo(int $i, int $j, string $s, array $a): void
			{
				marker();
				%s
			}
			PHP, $statements);
	}

}
