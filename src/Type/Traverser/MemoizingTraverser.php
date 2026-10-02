<?php declare(strict_types = 1);

namespace PHPStan\Type\Traverser;

use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverserCallable;
use function spl_object_id;

/**
 * Calls the wrapped callback only once per distinct Type object within a single
 * TypeTraverser::map() call and reuses its result on repeated visits.
 *
 * Big array shapes built from type aliases share the same inner Type objects many
 * times over, so a plain traversal visits orders of magnitude more nodes than there
 * are distinct types. Only suitable for callbacks whose result depends on the visited
 * type alone — callbacks collecting types into a list see each object once.
 */
final class MemoizingTraverser implements TypeTraverserCallable
{

	/** @var array<int, array{Type, Type}> */
	private array $results = [];

	/**
	 * @param callable(Type $type, callable(Type): Type $traverse): Type $cb
	 */
	public function __construct(private $cb)
	{
	}

	/**
	 * @param callable(Type): Type $traverse
	 */
	public function traverse(Type $type, callable $traverse): Type
	{
		$id = spl_object_id($type);
		if (isset($this->results[$id])) {
			return $this->results[$id][1];
		}

		$result = ($this->cb)($type, $traverse);

		// keeps the visited type alive so that its object id is not reused
		$this->results[$id] = [$type, $result];

		return $result;
	}

}
