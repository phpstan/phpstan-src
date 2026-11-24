<?php declare(strict_types = 1);

namespace PHPStan\Broker;

use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileHelper;
use PHPStan\File\ParentDirectoryRelativePathHelper;
use PHPStan\Parser\AnonymousClassVisitor;
use PHPStan\ShouldNotHappenException;
use function md5;
use function sprintf;

#[AutowiredService]
final class AnonymousClassNameHelper
{

	private ParentDirectoryRelativePathHelper $relativePathHelper;

	/**
	 * Every scope that enters an anonymous class asks for its name again, and the relative path
	 * costs splitting and joining both paths.
	 *
	 * @var array<string, string>
	 */
	private array $relativePaths = [];

	/**
	 * The file path in the name is relative to the PHPStan installation, the anchor of the
	 * result cache paths too, so the name does not change with the working directory.
	 */
	public function __construct(
		private FileHelper $fileHelper,
		#[AutowiredParameter(ref: '%rootDir%')]
		private string $rootDir,
	)
	{
		$this->relativePathHelper = new ParentDirectoryRelativePathHelper($rootDir);
	}

	/**
	 * Caches storing the names of anonymous classes must vary with this key.
	 */
	public function getCacheKey(): string
	{
		return $this->fileHelper->normalizePath($this->rootDir, '/');
	}

	/**
	 * @return non-empty-string
	 */
	public function getAnonymousClassName(
		Node\Stmt\Class_ $classNode,
		string $filename,
	): string
	{
		if (isset($classNode->namespacedName)) {
			throw new ShouldNotHappenException();
		}

		$filename = $this->relativePaths[$filename] ??= $this->relativePathHelper->getRelativePath(
			$this->fileHelper->normalizePath($filename, '/'),
		);

		/** @var int|null $lineIndex */
		$lineIndex = $classNode->getAttribute(AnonymousClassVisitor::ATTRIBUTE_LINE_INDEX);
		if ($lineIndex === null) {
			$hash = md5(sprintf('%s:%s', $filename, $classNode->getStartLine()));
		} else {
			$hash = md5(sprintf('%s:%s:%d', $filename, $classNode->getStartLine(), $lineIndex));
		}

		return sprintf(
			'AnonymousClass%s',
			$hash,
		);
	}

}
