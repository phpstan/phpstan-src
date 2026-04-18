<?php declare(strict_types = 1);

namespace BugSelfReferencedTrait;

use BugSelfReferencedTrait\RecursiveTrait;

class Model
{
}

/** @template TModel of Model */
class Builder
{
}

class BelongsTo
{
}

/**
 * @method static Builder<static>|BaseModel query()
 */
class BaseModel extends Model
{
	use RecursiveTrait;

	public function parent(): BelongsTo
	{
		return new BelongsTo();
	}
}
