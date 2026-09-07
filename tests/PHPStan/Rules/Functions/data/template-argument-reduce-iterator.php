<?php

namespace TemplateArgumentReduceIterator;

/** @param list<\Iterator<int, \stdClass>> $iterators */
function iteratorClosure(array $iterators): \Closure
{
	return function () use ($iterators) {
		return array_reduce(
			$iterators,
			static function (\AppendIterator $global, \Iterator $iterator) {
				$global->append($iterator);
				return $global;
			},
			new \AppendIterator()
		);
	};
}
