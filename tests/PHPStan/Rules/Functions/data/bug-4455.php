<?php

namespace Bug4455Function;

/**
 * @psalm-pure
 * @return never
 */
function nope() {
	throw new \Exception();
}

function (): void {
	nope();
};
