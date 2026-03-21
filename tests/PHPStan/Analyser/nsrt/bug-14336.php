<?php declare(strict_types = 1);

namespace Bug14336;

use function PHPStan\Testing\assertType;

/**
 * @param list<string> $list
 */
function test(array $list, int $int): void {
	$list[$int] = 'foo';
	assertType('non-empty-array<int, string>', $list);
}

/**
 * @param array<string, list<array{xmlNamespace: string, namespace: string, name: string}>> $xsdFiles
 * @param array<string, list<array{xmlNamespace: string, namespace: string, name: string}>> $groupedByNamespace
 * @param array<string, list<string>> $extraNamespaces
 */
function test2(array $xsdFiles, array $groupedByNamespace, array $extraNamespaces, int $int): void {
	foreach ($extraNamespaces as $mergedNamespace) {
		if (count($mergedNamespace) < 2) {
			continue;
		}

		$targetNamespace = end($mergedNamespace);
		if (!isset($groupedByNamespace[$targetNamespace])) {
			continue;
		}
		$xmlNamespace = $groupedByNamespace[$targetNamespace][0]['xmlNamespace'];

		$xsdFiles[$xmlNamespace] = [];
		assertType('array{}', $xsdFiles[$xmlNamespace]);
		foreach ($mergedNamespace as $namespace) {
			foreach ($groupedByNamespace[$namespace] ?? [] as $viewHelper) {
				assertType('array{xmlNamespace: string, namespace: string, name: string}', $viewHelper);
				$xsdFiles[$xmlNamespace][$int] = $viewHelper;
				assertType('non-empty-array<int, array{xmlNamespace: string, namespace: string, name: string}>', $xsdFiles[$xmlNamespace]);
			}
			assertType('array<int, array{xmlNamespace: string, namespace: string, name: string}>', $xsdFiles[$xmlNamespace]);
		}
		assertType('array<int, array{xmlNamespace: string, namespace: string, name: string}>', $xsdFiles[$xmlNamespace]);
	}
}

/**
 * @param list<string> $list
 */
function testInLoop(array $list, int $int): void {
	foreach ([1, 2, 3] as $item) {
		$list[$int] = 'foo';
	}
	assertType('non-empty-array<int, string>', $list);
}

/**
 * @param array<string, list<string>> $map
 */
function testNestedDimFetchInLoop(array $map, string $key, int $int): void {
	$map[$key] = [];
	foreach ([1, 2, 3] as $item) {
		$map[$key][$int] = 'foo';
	}
	assertType('non-empty-array<int, string>', $map[$key]);
}

/**
 * @param array<string, list<string>> $map
 * @param list<string> $items
 * @param list<string> $items2
 */
function testDoubleNestedForeachDimFetch(array $map, string $key, int $int, array $items, array $items2): void {
	$map[$key] = [];
	foreach ($items as $item) {
		foreach ($items2 as $item2) {
			$map[$key][$int] = $item2;
		}
	}
	assertType('array<int, string>', $map[$key]);
}

/**
 * @param array<string, list<string>> $map
 * @param list<string> $items
 */
function testSingleVariableForeach(array $map, string $key, int $int, array $items): void {
	$map[$key] = [];
	foreach ($items as $item) {
		$map[$key][$int] = $item;
	}
	assertType('array<int, string>', $map[$key]);
}

/**
 * @param array<string, list<string>> $map
 * @param list<string> $items
 * @param list<string> $outerItems
 */
function testOuterForeach(array $map, string $key, int $int, array $items, array $outerItems): void {
	foreach ($outerItems as $outerItem) {
		$map[$key] = [];
		foreach ($items as $item) {
			$map[$key][$int] = $item;
		}
		assertType('array<int, string>', $map[$key]);
	}
}

/**
 * @param array<string, list<string>> $map
 * @param list<string> $items
 * @param list<string> $outerItems
 */
function testOuterForeachWithContinue(array $map, string $key, int $int, array $items, array $outerItems): void {
	foreach ($outerItems as $outerItem) {
		if (strlen($outerItem) < 2) {
			continue;
		}
		$map[$key] = [];
		foreach ($items as $item) {
			$map[$key][$int] = $item;
		}
		assertType('array<int, string>', $map[$key]);
	}
}

/**
 * @param array<string, list<string>> $map
 * @param list<list<string>> $nestedItems
 * @param list<string> $outerItems
 */
function testNestedInnerForeach(array $map, string $key, int $int, array $nestedItems, array $outerItems): void {
	foreach ($outerItems as $outerItem) {
		if (strlen($outerItem) < 2) {
			continue;
		}
		$map[$key] = [];
		foreach ($nestedItems as $items) {
			foreach ($items as $item) {
				$map[$key][$int] = $item;
			}
		}
		assertType('array<int, string>', $map[$key]);
	}
}

/**
 * @param array<string, list<string>> $map
 * @param array<string, list<string>> $nestedItems
 * @param list<string> $outerItems
 */
function testNestedInnerForeachNullCoalesce(array $map, string $key, int $int, array $nestedItems, array $outerItems): void {
	foreach ($outerItems as $outerItem) {
		if (strlen($outerItem) < 2) {
			continue;
		}
		$map[$key] = [];
		foreach ($outerItems as $ns) {
			foreach ($nestedItems[$ns] ?? [] as $item) {
				$map[$key][$int] = $item;
			}
		}
		assertType('array<int, string>', $map[$key]);
	}
}

/**
 * @param array<string, list<array{ns: string, name: string}>> $map
 * @param array<string, list<array{ns: string, name: string}>> $grouped
 * @param array<string, list<string>> $extra
 */
function testCloseToOriginal(array $map, array $grouped, array $extra, int $int): void {
	foreach ($extra as $merged) {
		if (count($merged) < 2) {
			continue;
		}
		$target = end($merged);
		if (!isset($grouped[$target])) {
			continue;
		}
		$key = $grouped[$target][0]['ns'];

		$map[$key] = [];
		foreach ($merged as $ns) {
			foreach ($grouped[$ns] ?? [] as $item) {
				$map[$key][$int] = $item;
			}
		}
		assertType('array<int, array{ns: string, name: string}>', $map[$key]);
	}
}

/**
 * @param list<string> $list
 */
function testAppend(array $list): void {
	$list[] = 'foo';
	assertType('non-empty-list<string>', $list);
}

/**
 * @param list<string> $list
 */
function testLiteralZero(array $list): void {
	$list[0] = 'foo';
	assertType("non-empty-list<string>&hasOffsetValue(0, 'foo')", $list);
}
