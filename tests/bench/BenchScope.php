<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\DependencyInjection\Container;
use PHPStan\Parser\Parser;
use Tally\ScratchDirectory;
use function array_keys;
use function array_slice;
use function file_put_contents;
use function method_exists;

/**
 * Scopes for the microbenchmarks of MutatingScope and NodeScopeResolver, taken from the
 * analysis of a small file: the scope at each `marker*();` statement in a function or method.
 */
final class BenchScope
{

	/**
	 * @param array<string, MutatingScope> $scopes
	 */
	private function __construct(private array $scopes)
	{
	}

	/**
	 * @param string $name the file name, each analysis needs its own because the parser caches by file contents
	 */
	public static function analyse(Container $container, ScratchDirectory $scratch, string $name, string $code): self
	{
		[$file, $nodes] = self::parse($container, $scratch, $name, $code);
		$markers = [];
		foreach (self::markers($nodes) as $marker => [$stmt]) {
			$markers[$marker] = $stmt;
		}

		$scopes = [];
		$resolver = $container->getByType(NodeScopeResolver::class);
		$resolver->setAnalysedFiles([$file]);
		$scope = $container->getByType(ScopeFactory::class)->create(ScopeContext::create($file));
		$resolver->processNodes($nodes, $scope, static function (Node $node, Scope $scope) use ($markers, &$scopes): void {
			foreach ($markers as $marker => $stmt) {
				if ($node !== $stmt) {
					continue;
				}
				// the rules get a FiberScope from 2.1.34 and a NodeCallbackScope from 2.3,
				// the walk itself uses the MutatingScope behind them
				if (method_exists($scope, 'toWalkScope')) { // @phpstan-ignore function.alreadyNarrowedType (not before 2.3)
					$scope = $scope->toWalkScope();
				} elseif (method_exists($scope, 'toMutatingScope')) {
					$scope = $scope->toMutatingScope(); // @phpstan-ignore method.deprecated (what 2.1.34 to 2.2 have)
				}
				if (!$scope instanceof MutatingScope) {
					throw new LogicException('Not a MutatingScope.');
				}
				$scopes[$marker] = $scope;
			}
		});

		foreach (array_keys($markers) as $marker) {
			if (!isset($scopes[$marker])) {
				throw new LogicException($marker . '() was not reached.');
			}
		}

		return new self($scopes);
	}

	/**
	 * Parses without analysing, for statements no scope has seen yet.
	 *
	 * @return array{string, Node[]}
	 */
	public static function parse(Container $container, ScratchDirectory $scratch, string $name, string $code): array
	{
		$file = $scratch->path() . '/' . $name . '.php';
		// different contents for each name, the parser caches by them
		file_put_contents($file, $code . "\n// " . $name . "\n");

		/** @var Parser $parser */
		$parser = $container->getService('currentPhpVersionRichParser');

		return [$file, $parser->parseFile($file)];
	}

	/**
	 * The `marker*();` statements in functions and methods, with the function and the statements after them.
	 *
	 * @param Node[] $nodes
	 * @return array<string, array{Node\Stmt\Expression, Node\Stmt\Function_|Node\Stmt\ClassMethod, list<Node\Stmt>}>
	 */
	public static function markers(array $nodes): array
	{
		$markers = [];
		foreach ((new NodeFinder())->find($nodes, static fn (Node $node): bool => $node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) as $function) {
			if (!$function instanceof Node\Stmt\Function_ && !$function instanceof Node\Stmt\ClassMethod) {
				continue;
			}
			$stmts = $function->stmts ?? [];
			foreach ($stmts as $i => $stmt) {
				if (
					!$stmt instanceof Node\Stmt\Expression
					|| !$stmt->expr instanceof Node\Expr\FuncCall
					|| !$stmt->expr->name instanceof Node\Name
				) {
					continue;
				}
				$markers[$stmt->expr->name->getLast()] = [$stmt, $function, array_slice($stmts, $i + 1)];
			}
		}

		return $markers;
	}

	public function scope(string $marker): MutatingScope
	{
		return $this->scopes[$marker] ?? throw new LogicException('No ' . $marker . '().');
	}

}
