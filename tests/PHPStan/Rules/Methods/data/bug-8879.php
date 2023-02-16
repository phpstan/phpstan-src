<?php declare(strict_types = 1);

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
