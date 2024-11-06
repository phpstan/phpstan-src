<?php

namespace Bug11857;

use function PHPStan\Testing\assertType;

trait Filterable
{
	/**
	 * @param array<string, mixed> $attributes
	 * @return $this
	 */
	public function filter(array $attributes): static
	{
		// filter stuff
		return $this;
	}

	/**
	 * @param array<string, mixed> $attributes
	 * @return $this
	 */
	public function filterUsingRequest(array $attributes): static
	{
		// request handling
		return $this->filter($attributes);
	}
}

/** @template TModel of Model */
class Builder
{
	public function __construct(
		/** @var TModel */
		protected Model $model
	) {
	}

}

/**
 * @template TModel of Model
 * @extends Builder<TModel>
 */
class BaseBuilder extends Builder
{
	use Filterable;
}

/** @extends BaseBuilder<User> */
class UserBuilder extends BaseBuilder {}

/**
 * @template TModel of Model
 * @extends Builder<TModel>
 */
class PackageBuilder extends Builder {}

// this extends a Builder coming from a package
// so can't extend the BaseBuilder in our app
/** @extends PackageBuilder<Comment> */
final class CommentBuilder extends PackageBuilder
{
	use Filterable;
}

function test(UserBuilder $user, CommentBuilder $comment): void
{
	assertType(UserBuilder::class, $user->filterUsingRequest(['foo' => 'bar']));
	assertType(CommentBuilder::class, $comment->filterUsingRequest(['foo' => 'bar']));
}
