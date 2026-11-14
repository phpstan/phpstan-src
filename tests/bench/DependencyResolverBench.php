<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use FilesystemIterator;
use LogicException;
use PHPStan\Dependency\Dependencies;
use PHPStan\Dependency\DependencyResolver;
use PHPStan\Dependency\ResolvedDependencies;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\File\FileHelper;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantStringType;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;
use function dirname;

final class DependencyResolverBench
{

	/** a class with a deep hierarchy and many interfaces */
	private const REFERENCED_CLASS = ConstantStringType::class;

	private ?DependencyResolver $resolver = null;

	private ?Dependencies $dependencies = null;

	/** @var array<string, true> */
	private array $analysedFiles = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function references(): iterable
	{
		yield 'analysed' => ['analysed'];
		yield 'not-analysed' => ['not-analysed'];
		// the class file itself is among the dependencies
		yield 'own-file' => ['own-file'];
	}

	#[Before]
	public function boot(string $case): void
	{
		$container = BenchContainer::create($this->scratch);
		$this->resolver = $container->getByType(DependencyResolver::class);
		$fileHelper = $container->getByType(FileHelper::class);

		$file = $fileHelper->normalizePath(__FILE__);
		if ($case === 'own-file') {
			$file = $fileHelper->normalizePath((string) $container->getByType(ReflectionProvider::class)->getClass(self::REFERENCED_CLASS)->getFileName());
		}

		$this->analysedFiles = [];
		if ($case !== 'not-analysed') {
			$src = dirname((string) (new ReflectionClass(ContainerFactory::class))->getFileName(), 2);
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS)) as $srcFile) {
				/** @var SplFileInfo $srcFile */
				$this->analysedFiles[$fileHelper->normalizePath($srcFile->getPathname())] = true;
			}
		}

		$this->dependencies = Dependencies::create($file, [], [self::REFERENCED_CLASS]);

		// what the analysis of the files before memoized
		$this->benchResolveClassReference($case);
	}

	/**
	 * The files and packages a reference to a class from an analysed file depends on.
	 */
	#[DataProvider(method: 'references')]
	public function benchResolveClassReference(string $case): ResolvedDependencies
	{
		if ($this->resolver === null || $this->dependencies === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->resolver->resolveFileDependencies($this->dependencies, $this->analysedFiles);
	}

}
