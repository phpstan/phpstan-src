<?php declare(strict_types = 1);

namespace Bug14336;

use function PHPStan\Testing\assertType;

/**
 * @param array<string, list<array{xmlNamespace: string, namespace: string, name: string}>> $xsdFiles
 * @param array<string, list<array{xmlNamespace: string, namespace: string, name: string}>> $groupedByNamespace
 * @param array<string, list<string>> $extraNamespaces
 */
function test(array $xsdFiles, array $groupedByNamespace, array $extraNamespaces, int $int): void {
	foreach ($extraNamespaces as $mergedNamespace) {
		if (count($mergedNamespace) < 2) {
			continue;
		}

		$targetNamespace = end($mergedNamespace);
		if (!isset($groupedByNamespace[$targetNamespace])) {
			continue;
		}
		$xmlNamespace = $groupedByNamespace[$targetNamespace][0]['xmlNamespace'];

		assertType('string', $xmlNamespace);
		assertType('non-empty-list<string>&hasOffsetValue(1, string)', $mergedNamespace);

		$xsdFiles[$xmlNamespace] = [];
		foreach ($mergedNamespace as $namespace) {
			foreach ($groupedByNamespace[$namespace] ?? [] as $viewHelper) {
				$xsdFiles[$xmlNamespace][$int] = $viewHelper;
			}
		}
		// After assigning any int, $xsdFiles[$xmlNamespace] should NOT be a list
		assertType('array<int, array{xmlNamespace: string, namespace: string, name: string}>', $xsdFiles[$xmlNamespace]);
		$xsdFiles[$xmlNamespace] = array_values($xsdFiles[$xmlNamespace]);
	}
}
