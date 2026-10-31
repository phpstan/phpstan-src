<?php // lint >= 8.3

namespace Bug12387;

abstract class Model {
	protected const int PAGER = 200;
}

class User extends Model {
	// public const int PAGER = 500;
}

/**
 * @template TModel of Model
 */
class Fetcher {
	protected int $pager;

	/**
	 * @param class-string<TModel> $modelClass
	 */
	public function __construct(protected string $modelClass) {
		$this->pager = $modelClass::PAGER; // Eehh? Should this not be an error?
	}
}
