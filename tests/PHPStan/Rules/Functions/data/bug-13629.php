<?php declare(strict_types = 1);

namespace Bug13629;

$allViewHelpers = [
	['xmlNamespace' => 'http://typo3.org/ns/ns1', 'namespace' => 'TYPO3\Fluid', 'name' => 'Fluid'],
	['xmlNamespace' => 'http://typo3.org/ns/ns2', 'namespace' => 'TYPO3\Form', 'name' => 'Form'],
	['xmlNamespace' => 'http://typo3.org/ns/ns3', 'namespace' => 'TYPO3\Core', 'name' => 'Core'],
	['xmlNamespace' => 'http://typo3.org/ns/ns4', 'namespace' => 'Fluid\Fluid', 'name' => 'FluidCore'],
];

$extraNamespaces = [
	'core' => ['TYPO3\Core'],
	'f' => ['TYPO3\Fluid', 'Fluid\Fluid'],
	'form' => ['TYPO3\Form'],
];

$xsdFiles = $groupedByNamespace = [];
foreach ($allViewHelpers as $viewHelper) {
	$xsdFiles[$viewHelper['xmlNamespace']] ??= [];
	$xsdFiles[$viewHelper['xmlNamespace']][] = $viewHelper;

	$groupedByNamespace[$viewHelper['namespace']] ??= [];
	$groupedByNamespace[$viewHelper['namespace']][] = $viewHelper;
}

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
	foreach ($mergedNamespace as $namespace) {
		foreach ($groupedByNamespace[$namespace] ?? [] as $viewHelper) {
			$xsdFiles[$xmlNamespace][$viewHelper['name']] = $viewHelper;
		}
	}
	$xsdFiles[$xmlNamespace] = array_values($xsdFiles[$xmlNamespace]);
}
