<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\Tag\AssertTag;
use PHPStan\Type\Type;
use function array_map;
use function array_merge;
use function count;

class Assertions
{

	private static ?self $empty = null;

	/**
	 * @param AssertTag[] $asserts
	 * @param AssertTag[] $assertsIfTrue
	 * @param AssertTag[] $assertsIfFalse
	 */
	private function __construct(
		private array $asserts,
		private array $assertsIfTrue,
		private array $assertsIfFalse,
	)
	{
	}

	/**
	 * @return AssertTag[]
	 */
	public function getAsserts(): array
	{
		return $this->asserts;
	}

	/**
	 * @return AssertTag[]
	 */
	public function getAssertsIfTrue(): array
	{
		return $this->assertsIfTrue;
	}

	/**
	 * @return AssertTag[]
	 */
	public function getAssertsIfFalse(): array
	{
		return $this->assertsIfFalse;
	}

	public function isEmpty(): bool
	{
		return count($this->asserts) === 0
			&& count($this->assertsIfTrue) === 0
			&& count($this->assertsIfFalse) === 0;
	}

	/**
	 * @param callable(Type): Type $callable
	 */
	public function mapTypes(callable $callable): self
	{
		$assertTagsCallback = static fn (AssertTag $tag): AssertTag => $tag->withType($callable($tag->getType()));

		return new self(
			array_map($assertTagsCallback, $this->asserts),
			array_map($assertTagsCallback, $this->assertsIfTrue),
			array_map($assertTagsCallback, $this->assertsIfFalse),
		);
	}

	public function mergeWith(self $other): self
	{
		return new self(
			array_merge($this->asserts, $other->asserts),
			array_merge($this->assertsIfTrue, $other->assertsIfTrue),
			array_merge($this->assertsIfFalse, $other->assertsIfFalse),
		);
	}

	public static function createEmpty(): self
	{
		$empty = self::$empty;

		if ($empty !== null) {
			return $empty;
		}

		$empty = new self([], [], []);
		self::$empty = $empty;

		return $empty;
	}

	public static function createFromResolvedPhpDocBlock(ResolvedPhpDocBlock $phpDocBlock): self
	{
		return self::createFromTags(
			$phpDocBlock->getAssertTags(),
			$phpDocBlock->getAssertIfTrueTags(),
			$phpDocBlock->getAssertIfFalseTags(),
		);
	}

	/**
	 * @param AssertTag[] $assertTags
	 * @param AssertTag[] $assertIfTrueTags
	 * @param AssertTag[] $assertIfFalseTags
	 */
	public static function createFromTags(
		array $assertTags,
		array $assertIfTrueTags,
		array $assertIfFalseTags,
	): self
	{
		return new self(
			$assertTags,
			$assertIfTrueTags,
			$assertIfFalseTags,
		);
	}

	public static function fromParametersAcceptor(ParametersAcceptor $parametersAcceptor): self
	{
		if ($parametersAcceptor instanceof ParametersAcceptorWithAsserts) {
			return $parametersAcceptor->getAsserts();
		}

		return self::createEmpty();
	}

}
