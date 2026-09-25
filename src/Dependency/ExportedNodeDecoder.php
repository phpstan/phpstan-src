<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PHPStan\Analyser\NamespaceUses;

/**
 * Decodes the exported nodes of one file sent by a parallel worker.
 *
 * The JSON a worker sends repeats the namespace and the uses for every PHPDoc. Handing out one
 * NamespaceUses for equal values restores the sharing the worker had, so the main process holds
 * the imports of a file once, and the result cache stores them once.
 */
final class ExportedNodeDecoder
{

	/** @var list<NamespaceUses> */
	private array $namespaceUses = [];

	/**
	 * @param non-empty-string|null $namespace
	 * @param array<string, string> $uses
	 * @param array<string, string> $constUses
	 */
	public function getNamespaceUses(?string $namespace, array $uses, array $constUses): NamespaceUses
	{
		$namespaceUses = new NamespaceUses($namespace, $uses, $constUses);
		foreach ($this->namespaceUses as $existing) {
			if ($existing->equals($namespaceUses)) {
				return $existing;
			}
		}

		return $this->namespaceUses[] = $namespaceUses;
	}

}
