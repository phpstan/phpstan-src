<?php

namespace Bug11820;

use function PHPStan\Testing\assertType;

/**
 * @param array{subscriptionId?: non-empty-string, resourceGroupName?: non-empty-string, servicename?: non-empty-string} $parsedPath
 */
function foo($parsedPath): array {

	$capturing_groups = [
		'subscriptionId' => 'subscription ID',
		'resourceGroupName' => 'resource group name',
		'servicename' => 'service name',
	];

	assertType('array{subscriptionId?: non-empty-string, resourceGroupName?: non-empty-string, servicename?: non-empty-string}', $parsedPath);
	foreach ($capturing_groups as $capturing_group => $capturing_group_label) {
		if (!isset($parsedPath[$capturing_group])) {
			throw new \InvalidArgumentException(sprintf('The DSN must contain a %s name.', $capturing_group_label));
		}
	}

	assertType('array{subscriptionId: non-empty-string, resourceGroupName: non-empty-string, servicename: non-empty-string}', $parsedPath);

	return [];
}
