<?php

namespace Bug3669;

function foo(): \Generator
{
	while ($bar = yield 'foo') {
	}
}

function bar($m): \Generator
{
	while ($bar = yield $m) {
	}
}
