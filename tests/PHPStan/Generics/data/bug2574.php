<?php

namespace Generics\Bug2574;

abstract class Model {
	/** @return static */
	public function newInstance() {
		return new static();
	}
}

/**
 * @template T of Model
 * @param T $m
 * @return T
 */
function foo(Model $m) : Model {
	return $m->newInstance();
}
