<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\Internal\ComposerHelper;
use ReflectionClass;
use Tally\Attribute\Before;
use Tally\ScratchDirectory;
use function dirname;

final class ComposerHelperBench
{

	private string $root = '';

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		BenchContainer::create($this->scratch);
		$this->root = dirname((string) (new ReflectionClass(ContainerFactory::class))->getFileName(), 3);
		ComposerHelper::getComposerConfig($this->root);
	}

	/**
	 * Several services read the same composer.json while the container is built.
	 *
	 * @return array<string, mixed>|null
	 */
	public function benchGetComposerConfigAgain(): ?array
	{
		return ComposerHelper::getComposerConfig($this->root);
	}

}
