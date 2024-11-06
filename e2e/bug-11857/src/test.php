<?php

namespace Bug11857;

use function PHPStan\Testing\assertType;

abstract class Model
{
	/** @return BelongsTo<*, *> */
	public function belongsTo(string $related): BelongsTo
	{
		return new BelongsTo();
	}
}

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 */
class BelongsTo {}

class User extends Model {}

class Post extends Model
{
	/** @return BelongsTo<User, $this> */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	/** @return BelongsTo<User, self> */
	public function userSelf(): BelongsTo
	{
		/** @phpstan-ignore return.type */
		return $this->belongsTo(User::class);
	}
}

class ChildPost extends Post {}

final class Comment extends Model
{
	// This model is final, so either of these
	// two methods would work. It seems that
	// PHPStan is automatically converting the
	// `$this` to a `self` type in the user docblock,
	// but it is not doing so likewise for the `$this`
	// that is returned by the dynamic return extension.

	/** @return BelongsTo<User, $this> */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	/** @return BelongsTo<User, self> */
	public function user2(): BelongsTo
	{
		/** @phpstan-ignore return.type */
		return $this->belongsTo(User::class);
	}
}

function test(ChildPost $child): void
{
	assertType('Bug11857\BelongsTo<Bug11857\User, Bug11857\ChildPost>', $child->user());
	// This demonstrates why `$this` is needed in non-final models
	assertType('Bug11857\BelongsTo<Bug11857\User, Bug11857\Post>', $child->userSelf()); // should be: Bug11857\BelongsTo<Bug11857\User, Bug11857\ChildPost>
}
