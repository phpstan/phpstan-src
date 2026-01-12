<?php declare(strict_types = 1);

namespace PHPStan\Broker;

use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileHelper;
use PHPStan\File\RelativePathHelper;
use PHPStan\Parser\AnonymousClassVisitor;
use PHPStan\ShouldNotHappenException;
use function md5;
use function sprintf;

#[AutowiredService]
final class AnonymousClassNameHelper
{

	public function __construct(
		private FileHelper $fileHelper,
		#[AutowiredParameter(ref: '@simpleRelativePathHelper')]
		private RelativePathHelper $relativePathHelper,
	)
	{
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

		$filename = $this->relativePathHelper->getRelativePath(
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
