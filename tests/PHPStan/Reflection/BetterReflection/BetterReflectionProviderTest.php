<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Testing\PHPStanTestCase;
use function array_shift;
use function file_put_contents;
use function gc_collect_cycles;
use function memory_get_usage;
use function sprintf;
use function str_repeat;
use function strlen;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

class BetterReflectionProviderTest extends PHPStanTestCase
{

	private const ANONYMOUS_CLASS_COUNT_LIMIT = 10;

	public function testAnonymousClassesOfOneFileShareItsContents(): void
	{
		$fileName = sys_get_temp_dir() . '/phpstan-anonymous-classes-' . uniqid() . '.php';
		$contents = "<?php\n" . str_repeat(' ', 1_048_576) . "\n";
		for ($i = 0; $i <= self::ANONYMOUS_CLASS_COUNT_LIMIT; $i++) {
			$contents .= sprintf("\$x%d = new class { public function foo(): int { return %d; } };\n", $i, $i);
		}
		file_put_contents($fileName, $contents);

		try {
			$classNodes = (new NodeFinder())->find(
				self::getParser()->parseFile($fileName),
				static fn (Node $node): bool => $node instanceof Class_ && $node->isAnonymous(),
			);
			$this->assertCount(self::ANONYMOUS_CLASS_COUNT_LIMIT + 1, $classNodes);

			$reflectionProvider = self::createReflectionProvider();
			$scope = self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create($fileName));

			$firstClassNode = array_shift($classNodes);
			$this->assertInstanceOf(Class_::class, $firstClassNode);
			$reflections = [$reflectionProvider->getAnonymousClassReflection($firstClassNode, $scope)];

			gc_collect_cycles();
			$memoryBefore = memory_get_usage();
			foreach ($classNodes as $classNode) {
				$this->assertInstanceOf(Class_::class, $classNode);
				$reflections[] = $reflectionProvider->getAnonymousClassReflection($classNode, $scope);
			}
			gc_collect_cycles();

			// every reflection keeps its located source alive, so a copy per anonymous class would add up to 10 MB
			$this->assertLessThan(strlen($contents), memory_get_usage() - $memoryBefore);
			foreach ($reflections as $reflection) {
				$this->assertTrue($reflection->hasNativeMethod('foo'));
			}
		} finally {
			@unlink($fileName);
		}
	}

}
