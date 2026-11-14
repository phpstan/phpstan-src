<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Tally\Attribute\Before;
use Tally\ScratchDirectory;
use function file_put_contents;

final class BetterReflectionProviderBench
{

	private ?ReflectionProvider $reflectionProvider = null;

	private ?Node\Stmt\Class_ $class = null;

	private ?Scope $scope = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		$container = BenchContainer::create($this->scratch);
		$this->reflectionProvider = $container->getByType(ReflectionProvider::class);

		$file = $this->scratch->path() . '/anonymous-class.php';
		$code = "<?php\n\n\$a = new class {};\n\$b = new class {};\n";
		file_put_contents($file, $code);
		$classes = (new NodeFinder())->findInstanceOf((new ParserFactory())->createForHostVersion()->parse($code) ?? [], Node\Stmt\Class_::class);
		$this->scope = $container->getByType(ScopeFactory::class)->create(ScopeContext::create($file));

		$this->reflectionProvider->getAnonymousClassReflection($classes[0], $this->scope);
		$this->reflectionProvider->getAnonymousClassReflection($classes[0], $this->scope);
		$this->class = $classes[1];
		$this->reflectionProvider->getAnonymousClassReflection($this->class, $this->scope);
	}

	/**
	 * Every scope that enters an anonymous class asks for its reflection again.
	 */
	public function benchGetAnonymousClassReflectionAgain(): ClassReflection
	{
		if ($this->reflectionProvider === null || $this->class === null || $this->scope === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->reflectionProvider->getAnonymousClassReflection($this->class, $this->scope);
	}

}
