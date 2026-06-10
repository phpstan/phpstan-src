<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\Tag\AssertTag;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_filter;
use function array_map;
use function array_merge;
use function count;
use function sprintf;

/**
 * Collection of @phpstan-assert annotations on a function or method.
 *
 * PHPStan supports type assertions via PHPDoc annotations:
 * - `@phpstan-assert Type $param` — narrows the parameter type unconditionally
 * - `@phpstan-assert-if-true Type $param` — narrows when the method returns true
 * - `@phpstan-assert-if-false Type $param` — narrows when the method returns false
 *
 * This class collects all such assertions and provides methods to retrieve them
 * by condition type. It also handles negation: an `@phpstan-assert-if-true` assertion
 * is automatically negated and included in the `getAssertsIfFalse()` result.
 *
 * Returned by ExtendedMethodReflection::getAsserts() and FunctionReflection::getAsserts().
 *
 * @api
 */
final class Assertions
{

	private static ?self $empty = null;

	/**
	 * @param AssertTag[] $asserts
	 */
	private function __construct(private array $asserts)
	{
	}

	/** @return AssertTag[] */
	public function getAll(): array
	{
		return $this->asserts;
	}

	/**
	 * Unconditional assertions — narrow parameter types regardless of the method's return value.
	 *
	 * @return AssertTag[]
	 */
	public function getAsserts(): array
	{
		return array_filter($this->asserts, static fn (AssertTag $assert) => $assert->getIf() === AssertTag::NULL);
	}

	/**
	 * Includes @phpstan-assert-if-true tags and negated @phpstan-assert-if-false tags.
	 *
	 * @return AssertTag[]
	 */
	public function getAssertsIfTrue(): array
	{
		return array_merge(
			array_filter($this->asserts, static fn (AssertTag $assert) => $assert->getIf() === AssertTag::IF_TRUE),
			array_map(
				static fn (AssertTag $assert) => $assert->negate(),
				array_filter($this->asserts, static fn (AssertTag $assert) => $assert->getIf() === AssertTag::IF_FALSE && !$assert->isEquality()),
			),
		);
	}

	/**
	 * Includes @phpstan-assert-if-false tags and negated @phpstan-assert-if-true tags.
	 *
	 * @return AssertTag[]
	 */
	public function getAssertsIfFalse(): array
	{
		return array_merge(
			array_filter($this->asserts, static fn (AssertTag $assert) => $assert->getIf() === AssertTag::IF_FALSE),
			array_map(
				static fn (AssertTag $assert) => $assert->negate(),
				array_filter($this->asserts, static fn (AssertTag $assert) => $assert->getIf() === AssertTag::IF_TRUE && !$assert->isEquality()),
			),
		);
	}

	/** @param callable(Type): Type $callable */
	public function mapTypes(callable $callable): self
	{
		$assertTagsCallback = static fn (AssertTag $tag): AssertTag => $tag->withType($callable($tag->getType()));

		return self::create(array_map($assertTagsCallback, $this->asserts));
	}

	/**
	 * @deprecated use union() or intersect() instead
	 */
	public function intersectWith(Assertions $other): self
	{
		return $this->union($other);
	}

	public function union(Assertions $other): self
	{
		if ($this === self::$empty) {
			return $other;
		}
		if ($other === self::$empty) {
			return $this;
		}

		return self::create(array_merge($this->getAll(), $other->getAll()));
	}

	public function intersect(Assertions $other): self
	{
		if ($this === self::$empty) {
			return $other;
		}
		if ($other === self::$empty) {
			return $this;
		}

		$otherAsserts = $other->getAll();
		$thisAsserts = $this->getAll();

		$merged = [];
		foreach ($thisAsserts as $thisAssert) {
			$key = self::getAssertKey($thisAssert);

			foreach ($otherAsserts as $otherAssert) {
				if (self::getAssertKey($otherAssert) !== $key) {
					continue;
				}

				$merged[] = $thisAssert->withType(TypeCombinator::union($thisAssert->getType(), $otherAssert->getType()));
			}
		}

		return self::create($merged);
	}

	private static function getAssertKey(AssertTag $assert): string
	{
		return sprintf(
			'%s-%s-%s',
			$assert->getParameter()->describe(),
			$assert->getIf(),
			$assert->isNegated() ? '1' : '0',
		);
	}

	/**
	 * @param AssertTag[] $asserts
	 */
	private static function create(array $asserts): self
	{
		if (count($asserts) === 0) {
			return self::createEmpty();
		}
		return new self($asserts);
	}

	public static function createEmpty(): self
	{
		$empty = self::$empty;

		if ($empty !== null) {
			return $empty;
		}

		$empty = new self([]);
		self::$empty = $empty;

		return $empty;
	}

	public static function createFromResolvedPhpDocBlock(ResolvedPhpDocBlock $phpDocBlock): self
	{
		$tags = $phpDocBlock->getAssertTags();

		return self::create($tags);
	}

	/**
	 * @param AssertTag[] $assertTags
	 */
	public static function createFromAssertTags(array $assertTags): self
	{
		return self::create($assertTags);
	}

}
