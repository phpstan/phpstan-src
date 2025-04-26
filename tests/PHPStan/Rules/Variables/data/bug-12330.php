<?php

namespace Bug12330;

/**
 * @param array{items: list<array<string, mixed>>} $options
 * @param-out array{items: list<array<string, mixed>>} $options
 */
function alterItems(array &$options): void
{
	foreach ($options['items'] as $i => $item) {
		$options['items'][$i]['options']['title'] = $item['name'];
	}
}

/**
 * @param array{items: array<int, array<string, mixed>>} $options
 * @param-out array{items: array<int, array<string, mixed>>} $options
 */
function alterItems2(array &$options): void
{
	foreach ($options['items'] as $i => $item) {
		$options['items'][$i]['options']['title'] = $item['name'];
	}
}
