<?php declare(strict_types = 1);

namespace Bug11909;

function doFoo(): never {
	throw new LogicException("throws");
}

echo doFoo();
echo "hello";
