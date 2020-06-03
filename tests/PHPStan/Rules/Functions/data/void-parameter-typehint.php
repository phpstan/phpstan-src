<?php

namespace VoidParameterTypehint;

function (void $param): int {
	return 1;
};

function doFoo(void $param): int
{
	return 1;
}
