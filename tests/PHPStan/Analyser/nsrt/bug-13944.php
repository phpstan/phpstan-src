<?php

namespace Bug13944;

use function PHPStan\Testing\assertType;

/**
 * @param array{
 *     "when@dev"?: array<string, array{resource: string}>,
 *     "when@stage"?: array<string, array{resource: string}>,
 * } $config
 */
function config(array $config): void
{
}

config([
	'when@dev' => $does_not_work = [
		'controllers' => [
			'resource' => 'routing.controllers',
		],
	],
	'when@stage' => $does_not_work,
]);

assertType("array{'when@dev': array{controllers: array{resource: 'routing.controllers'}}, 'when@stage': array{controllers: array{resource: 'routing.controllers'}}}", [
	'when@dev' => $does_not_work,
	'when@stage' => $does_not_work,
]);

assertType("array{'when@dev': array{controllers: array{resource: 'routing.controllers'}}, 'when@stage': array{controllers: array{resource: 'routing.controllers'}}}", [
	'when@dev' => $defined_inside = [
		'controllers' => [
			'resource' => 'routing.controllers',
		],
	],
	'when@stage' => $defined_inside,
]);

$does_work = [
	'controllers' => [
		'resource' => 'routing.controllers',
	],
];
config([
	'when@dev' => $does_work,
	'when@stage' => $does_work,
]);
