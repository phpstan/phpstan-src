<?php

namespace ArgumentsCountAcceptedByNoVariant;

/**
 * @param resource $context
 */
function doFoo($context): void
{
	$a = mt_rand();
	$a = mt_rand(1, 2);
	$a = mt_rand(1);
	$a = rand();
	$a = rand(1, 2);
	$a = rand(1);
	$a = mt_rand(1, 2, 3);

	stream_context_set_option($context, 'http', 'method', 'POST');
	stream_context_set_option($context, ['http' => ['method' => 'POST']]);
	stream_context_set_option($context, 'http', 'method');

	$cb = static fn ($a, $b): int => $a <=> $b;
	$b = array_intersect_uassoc([1], [1], $cb);
	$b = array_intersect_uassoc([1], [1], [1], $cb);
	$b = array_intersect_ukey([1], [1], [1], $cb);

	$handler = static function (): void {
	};
	uopz_add_function('Foo', 'bar', $handler, 1);
	uopz_add_function('bar', $handler);
	uopz_del_function('Foo', 'bar');
}
