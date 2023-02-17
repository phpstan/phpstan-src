<?php

namespace Bug8879;

trait GetThisTrait {
	/** @return $this */
	function getThis() {
		return $this;
	}
}

/** @template T */
final class A {
	use GetThisTrait;
}
