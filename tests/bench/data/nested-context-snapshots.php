<?php declare(strict_types = 1);

/*
 * instructions: 6.111B
 * cycles: 7.731B
 * memory: 22.92MiB
 */

namespace BenchNestedContextSnapshots;

use Countable;

/**
 * Shaped like a compiled Twig template with nested for-loops: every loop
 * stores the whole context in $context['_parent'] and in loop.parent, so
 * the same members are shared by every nesting level, and walking them
 * once per path leading to them is exponential in the depth.
 *
 * @param array{known: int, dynamic: mixed, metadata: array{null, null, null, null, null}} $context
 * @return iterable<mixed>
 */
function doDisplay(array $context): iterable
{
	yield $context['known'];
	$context['_parent'] = $context;
	$context['_seq'] = range(1, 3);
	$context['loop'] = ['parent' => $context['_parent'], 'index0' => 0, 'index' => 1, 'first' => true];
	if (is_array($context['_seq']) || is_object($context['_seq']) && $context['_seq'] instanceof Countable) {
		$length = count($context['_seq']);
		$context['loop']['revindex0'] = $length - 1;
		$context['loop']['revindex'] = $length;
		$context['loop']['length'] = $length;
		$context['loop']['last'] = 1 === $length;
	}
	foreach ($context['_seq'] as $context['_key'] => $context['l1']) {
		yield $context['loop']['index'];
		yield $context['known'];
		$context['_parent'] = $context;
		$context['_seq'] = range(1, 3);
		$context['loop'] = ['parent' => $context['_parent'], 'index0' => 0, 'index' => 1, 'first' => true];
		if (is_array($context['_seq']) || is_object($context['_seq']) && $context['_seq'] instanceof Countable) {
			$length = count($context['_seq']);
			$context['loop']['revindex0'] = $length - 1;
			$context['loop']['revindex'] = $length;
			$context['loop']['length'] = $length;
			$context['loop']['last'] = 1 === $length;
		}
		foreach ($context['_seq'] as $context['_key'] => $context['l2']) {
			yield $context['loop']['index'];
			yield $context['known'];
			$context['_parent'] = $context;
			$context['_seq'] = range(1, 3);
			$context['loop'] = ['parent' => $context['_parent'], 'index0' => 0, 'index' => 1, 'first' => true];
			if (is_array($context['_seq']) || is_object($context['_seq']) && $context['_seq'] instanceof Countable) {
				$length = count($context['_seq']);
				$context['loop']['revindex0'] = $length - 1;
				$context['loop']['revindex'] = $length;
				$context['loop']['length'] = $length;
				$context['loop']['last'] = 1 === $length;
			}
			foreach ($context['_seq'] as $context['_key'] => $context['l3']) {
				yield $context['loop']['index'];
				yield $context['known'];
				$context['_parent'] = $context;
				$context['_seq'] = range(1, 3);
				$context['loop'] = ['parent' => $context['_parent'], 'index0' => 0, 'index' => 1, 'first' => true];
				if (is_array($context['_seq']) || is_object($context['_seq']) && $context['_seq'] instanceof Countable) {
					$length = count($context['_seq']);
					$context['loop']['revindex0'] = $length - 1;
					$context['loop']['revindex'] = $length;
					$context['loop']['length'] = $length;
					$context['loop']['last'] = 1 === $length;
				}
				foreach ($context['_seq'] as $context['_key'] => $context['l4']) {
					yield $context['loop']['index'];
					yield $context['known'];
					$context['_parent'] = $context;
					$context['_seq'] = range(1, 3);
					$context['loop'] = ['parent' => $context['_parent'], 'index0' => 0, 'index' => 1, 'first' => true];
					if (is_array($context['_seq']) || is_object($context['_seq']) && $context['_seq'] instanceof Countable) {
						$length = count($context['_seq']);
						$context['loop']['revindex0'] = $length - 1;
						$context['loop']['revindex'] = $length;
						$context['loop']['length'] = $length;
						$context['loop']['last'] = 1 === $length;
					}
					foreach ($context['_seq'] as $context['_key'] => $context['l5']) {
						yield $context['loop']['index'];
						yield $context['known'];
						$context['_parent'] = $context;
						$context['_seq'] = range(1, 3);
						$context['loop'] = ['parent' => $context['_parent'], 'index0' => 0, 'index' => 1, 'first' => true];
						if (is_array($context['_seq']) || is_object($context['_seq']) && $context['_seq'] instanceof Countable) {
							$length = count($context['_seq']);
							$context['loop']['revindex0'] = $length - 1;
							$context['loop']['revindex'] = $length;
							$context['loop']['length'] = $length;
							$context['loop']['last'] = 1 === $length;
						}
						foreach ($context['_seq'] as $context['_key'] => $context['l6']) {
							yield $context['loop']['index'];
							++$context['loop']['index0'];
							++$context['loop']['index'];
							$context['loop']['first'] = false;
							if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
								--$context['loop']['revindex0'];
								--$context['loop']['revindex'];
								$context['loop']['last'] = 0 === $context['loop']['revindex0'];
							}
						}
						$_parent = $context['_parent'];
						unset($context['_seq'], $context['_key'], $context['l6'], $context['_parent'], $context['loop']);
						$context = array_intersect_key($context, $_parent) + $_parent;
						++$context['loop']['index0'];
						++$context['loop']['index'];
						$context['loop']['first'] = false;
						if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
							--$context['loop']['revindex0'];
							--$context['loop']['revindex'];
							$context['loop']['last'] = 0 === $context['loop']['revindex0'];
						}
					}
					$_parent = $context['_parent'];
					unset($context['_seq'], $context['_key'], $context['l5'], $context['_parent'], $context['loop']);
					$context = array_intersect_key($context, $_parent) + $_parent;
					++$context['loop']['index0'];
					++$context['loop']['index'];
					$context['loop']['first'] = false;
					if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
						--$context['loop']['revindex0'];
						--$context['loop']['revindex'];
						$context['loop']['last'] = 0 === $context['loop']['revindex0'];
					}
				}
				$_parent = $context['_parent'];
				unset($context['_seq'], $context['_key'], $context['l4'], $context['_parent'], $context['loop']);
				$context = array_intersect_key($context, $_parent) + $_parent;
				++$context['loop']['index0'];
				++$context['loop']['index'];
				$context['loop']['first'] = false;
				if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
					--$context['loop']['revindex0'];
					--$context['loop']['revindex'];
					$context['loop']['last'] = 0 === $context['loop']['revindex0'];
				}
			}
			$_parent = $context['_parent'];
			unset($context['_seq'], $context['_key'], $context['l3'], $context['_parent'], $context['loop']);
			$context = array_intersect_key($context, $_parent) + $_parent;
			++$context['loop']['index0'];
			++$context['loop']['index'];
			$context['loop']['first'] = false;
			if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
				--$context['loop']['revindex0'];
				--$context['loop']['revindex'];
				$context['loop']['last'] = 0 === $context['loop']['revindex0'];
			}
		}
		$_parent = $context['_parent'];
		unset($context['_seq'], $context['_key'], $context['l2'], $context['_parent'], $context['loop']);
		$context = array_intersect_key($context, $_parent) + $_parent;
		++$context['loop']['index0'];
		++$context['loop']['index'];
		$context['loop']['first'] = false;
		if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
			--$context['loop']['revindex0'];
			--$context['loop']['revindex'];
			$context['loop']['last'] = 0 === $context['loop']['revindex0'];
		}
	}
	$_parent = $context['_parent'];
	unset($context['_seq'], $context['_key'], $context['l1'], $context['_parent'], $context['loop']);
	$context = array_intersect_key($context, $_parent) + $_parent;
	yield $context['known'];
}
