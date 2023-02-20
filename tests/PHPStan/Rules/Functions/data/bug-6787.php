<?php

namespace Bug6787;

/**
 * @template T of \DateTimeInterface
 * @param T $p
 * @return T
 */
function f($p) {
	return new \DateTime();
}
