<?php

namespace Bug8880;

function putStrLn(string $s): void {
	echo $s, PHP_EOL;
}

/**
 * @template-covariant T
 */
interface IProcessor {
	/**
	 * @param iterable<T> $items
	 * @return void
	 */
	function processItems($items);
}

/** @implements IProcessor<string> */
final class StringPrinter implements IProcessor {
	function processItems($items) {
		foreach ($items as $s)
			putStrLn($s);
	}
}

/**
 * @param IProcessor<string | int> $p
 * @return void
 */
function callWithInt($p) {
	$p->processItems([1]);
}
