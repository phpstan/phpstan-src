<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use function array_values;
use function file_get_contents;
use function sort;

/**
 * DependencyResolver answers per node class which parts of resolveDependencies() a node can reach,
 * from three lists of node classes. Each list mirrors a chain of instanceof checks somewhere else, and
 * a branch added without an entry in its list would silently stop producing dependencies - which
 * leaves a stale result cache rather than a failing test. So the lists are read back out of the source
 * they mirror.
 */
final class NodeProfileMirrorsBranchesTest extends PHPStanTestCase
{

	/**
	 * @return iterable<string, array{string, string, string}>
	 */
	public static function dataLists(): iterable
	{
		yield 'the branch chain' => [
			'CHAIN_NODE_TYPES',
			__DIR__ . '/../../../src/Dependency/DependencyResolver.php',
			'collectNodeDependencies',
		];
		yield 'the exported nodes' => [
			'EXPORT_NODE_TYPES',
			__DIR__ . '/../../../src/Dependency/ExportedNodeResolver.php',
			'resolve',
		];
		yield 'the PHPDoc name scope' => [
			'NAME_SCOPE_NODE_TYPES',
			__DIR__ . '/../../../src/Dependency/ExportedNameScopeTracker.php',
			'enterNode',
		];
	}

	#[DataProvider('dataLists')]
	public function testListMirrorsTheBranches(string $constantName, string $file, string $methodName): void
	{
		$reflection = new ReflectionClass(DependencyResolver::class);
		/** @var list<string> $listed */
		$listed = $reflection->getConstant($constantName);
		sort($listed);

		$matched = $this->nodeClassesMatchedIn($file, $methodName);

		$this->assertSame(
			$listed,
			$matched,
			$constantName . ' does not match the node classes ' . $methodName . '() reacts to.',
		);
	}

	/**
	 * Every class the method's `$node instanceof X` checks name, in source order, deduplicated.
	 *
	 * @return list<string>
	 */
	private function nodeClassesMatchedIn(string $file, string $methodName): array
	{
		$contents = file_get_contents($file);
		$this->assertNotFalse($contents);

		$parser = (new ParserFactory())->createForHostVersion();
		$stmts = $parser->parse($contents);
		$this->assertNotNull($stmts);

		$traverser = new NodeTraverser();
		$traverser->addVisitor(new NameResolver());
		$stmts = $traverser->traverse($stmts);

		$method = null;
		foreach ((new NodeFinder())->findInstanceOf($stmts, Node\Stmt\ClassMethod::class) as $classMethod) {
			if ($classMethod->name->toString() !== $methodName) {
				continue;
			}

			$method = $classMethod;
			break;
		}

		$this->assertNotNull($method, $methodName . '() not found in ' . $file);

		$classNames = [];
		foreach ((new NodeFinder())->findInstanceOf([$method], Node\Expr\Instanceof_::class) as $instanceof) {
			if (!$instanceof->expr instanceof Node\Expr\Variable || $instanceof->expr->name !== 'node') {
				continue;
			}
			if (!$instanceof->class instanceof Node\Name) {
				continue;
			}

			$className = $instanceof->class->toString();
			$classNames[$className] = $className;
		}

		$classNames = array_values($classNames);
		sort($classNames);

		return $classNames;
	}

}
