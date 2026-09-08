<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

/**
 * The namespace and the use statements in effect at a point in a file.
 *
 * This is everything an ExportedPhpDocNode records about the scope a PHPDoc was written in, and
 * all of it is read straight off the AST by ExportedNodeVisitor. Asking FileTypeMapper for a
 * NameScope instead would build the name scope map of the whole file, which resolves every PHPDoc
 * in it - far more than the exported nodes need, and it happens in the main process during a
 * result cache restore, in front of the analysis.
 */
final class ExportedNameScope
{

	/**
	 * @param non-empty-string|null $namespace
	 * @param array<string, string> $uses alias(string) => fullName(string)
	 * @param array<string, string> $constUses alias(string) => fullName(string)
	 */
	public function __construct(
		private ?string $namespace,
		private array $uses,
		private array $constUses,
	)
	{
	}

	/**
	 * @return non-empty-string|null
	 */
	public function getNamespace(): ?string
	{
		return $this->namespace;
	}

	/**
	 * @return array<string, string>
	 */
	public function getUses(): array
	{
		return $this->uses;
	}

	/**
	 * @return array<string, string>
	 */
	public function getConstUses(): array
	{
		return $this->constUses;
	}

}
