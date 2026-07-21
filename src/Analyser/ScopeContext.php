<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Reflection\ClassReflection;
use PHPStan\ShouldNotHappenException;

final class ScopeContext
{

	private function __construct(
		private string $file,
		private string $analysisFile,
		private ?ClassReflection $classReflection,
		private ?ClassReflection $traitReflection,
	)
	{
	}

	/**
	 * @api
	 * @param string|null $analysisFile the file whose content is actually analysed; differs from $file in editor mode (--tmp-file / --instead-of)
	 */
	public static function create(string $file, ?string $analysisFile = null): self
	{
		return new self($file, $analysisFile ?? $file, classReflection: null, traitReflection: null);
	}

	public function beginFile(): self
	{
		return new self($this->file, $this->analysisFile, classReflection: null, traitReflection: null);
	}

	public function enterClass(ClassReflection $classReflection): self
	{
		if ($this->classReflection !== null && !$classReflection->isAnonymous()) {
			throw new ShouldNotHappenException();
		}
		if ($classReflection->isTrait()) {
			throw new ShouldNotHappenException();
		}
		return new self($this->file, $this->analysisFile, $classReflection, traitReflection: null);
	}

	public function enterTrait(ClassReflection $traitReflection): self
	{
		if ($this->classReflection === null) {
			throw new ShouldNotHappenException();
		}
		if (!$traitReflection->isTrait()) {
			throw new ShouldNotHappenException();
		}

		return new self($this->file, $this->analysisFile, $this->classReflection, $traitReflection);
	}

	public function equals(self $otherContext): bool
	{
		if ($this->file !== $otherContext->file) {
			return false;
		}

		if ($this->getClassReflection() === null) {
			return $otherContext->getClassReflection() === null;
		} elseif ($otherContext->getClassReflection() === null) {
			return false;
		}

		$isSameClass = $this->getClassReflection()->getName() === $otherContext->getClassReflection()->getName();

		if ($this->getTraitReflection() === null) {
			return $otherContext->getTraitReflection() === null && $isSameClass;
		} elseif ($otherContext->getTraitReflection() === null) {
			return false;
		}

		$isSameTrait = $this->getTraitReflection()->getName() === $otherContext->getTraitReflection()->getName();

		return $isSameClass && $isSameTrait;
	}

	public function getFile(): string
	{
		return $this->file;
	}

	/**
	 * The file whose content is actually analysed. Equal to getFile() except in
	 * editor mode (--tmp-file / --instead-of), where getFile() is the real path
	 * while this is the temp file being read.
	 */
	public function getAnalysisFile(): string
	{
		return $this->analysisFile;
	}

	public function getClassReflection(): ?ClassReflection
	{
		return $this->classReflection;
	}

	public function getTraitReflection(): ?ClassReflection
	{
		return $this->traitReflection;
	}

}
