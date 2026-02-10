<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\Tag\AssertTag;
use PHPStan\Type\Type;
use function array_filter;
use function array_map;
use function array_merge;
use function count;

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

	/**
	 * Returns all assert tags regardless of condition.
	 *
	 * @return AssertTag[]
	 */
	public function getAll(): array
	{
		return $this->asserts;
	}

	/**
	 * Returns unconditional assertions (@phpstan-assert).
	 *
	 * These narrow parameter types regardless of the method's return value.
	 *
	 * @return AssertTag[]
	 */
	public function getAsserts(): array
	{
		return array_filter($this->asserts, static fn (AssertTag $assert) => $assert->getIf() === AssertTag::NULL);
	}

	/**
	 * Returns assertions that apply when the method returns true.
	 *
	 * Includes `@phpstan-assert-if-true` tags and negated `@phpstan-assert-if-false` tags.
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
	 * Returns assertions that apply when the method returns false.
	 *
	 * Includes `@phpstan-assert-if-false` tags and negated `@phpstan-assert-if-true` tags.
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

	/**
	 * Transforms all assertion types using the given callback.
	 *
	 * Used when resolving template types — the assertion types need to be
	 * substituted with concrete type arguments.
	 *
	 * @param callable(Type): Type $callable
	 */
	public function mapTypes(callable $callable): self
	{
		$assertTagsCallback = static fn (AssertTag $tag): AssertTag => $tag->withType($callable($tag->getType()));

		return new self(array_map($assertTagsCallback, $this->asserts));
	}

	public function intersectWith(Assertions $other): self
	{
		return new self(array_merge($this->getAll(), $other->getAll()));
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
		if (count($tags) === 0) {
			return self::createEmpty();
		}

		return new self($tags);
	}

}
