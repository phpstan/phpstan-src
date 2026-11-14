<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Dependency\PackageDependencyResolver;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\File\FileHelper;
use ReflectionClass;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;
use function dirname;

final class PackageDependencyResolverBench
{

	private ?PackageDependencyResolver $resolver = null;

	private string $file = '';

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function files(): iterable
	{
		yield 'package-file' => ['vendor/nikic/php-parser/lib/PhpParser/Node/Expr/Variable.php'];
		// matches none of the installed packages
		yield 'project-file' => ['src/Analyser/MutatingScope.php'];
	}

	#[Before]
	public function boot(string $file): void
	{
		BenchContainer::create($this->scratch);
		$root = dirname((string) (new ReflectionClass(ContainerFactory::class))->getFileName(), 3);
		$fileHelper = new FileHelper($root);
		// the installed packages of PHPStan's own vendor directory
		$this->resolver = new PackageDependencyResolver([$root], $fileHelper);
		$this->file = $fileHelper->normalizePath($root . '/' . $file);
		$this->resolver->resolvePackage($this->file);
	}

	/**
	 * Every analysed file that depends on a file resolves it again.
	 */
	#[DataProvider(method: 'files')]
	#[ExpectedFor('package-file', instructions: '1.082K', cycles: '13.07K', memory: '0B')]
	#[ExpectedFor('project-file', instructions: '1.082K', cycles: '13.07K', memory: '0B')]
	public function benchResolvePackageAgain(string $file): ?string
	{
		if ($this->resolver === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->resolver->resolvePackage($this->file);
	}

}
