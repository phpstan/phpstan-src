<?php

namespace Bug11534;

function hello(int $param1, int $param2): void
{
}
/** @param mixed[] $params */
function world(array $params): void
{
	if (!is_int($params['param1'])) {
		throw new \Exception();
	}
	hello($params['param1'], $params['param2']);
}
