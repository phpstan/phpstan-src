<?php declare(strict_types = 1);

namespace Bug9684;

use Bug9684\RecursiveTrait;

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
