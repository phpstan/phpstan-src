<?php declare(strict_types = 1);

return [
	'root' => [
		'name' => 'acme/project',
		'install_path' => __DIR__ . '/../../',
	],
	'versions' => [
		'acme/project' => [
			'pretty_version' => '1.0.0+no-version-set',
			'install_path' => __DIR__ . '/../../',
		],
		'acme/widget' => [
			'pretty_version' => '1.0.0',
			'reference' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
			'install_path' => __DIR__ . '/../acme/widget',
		],
		'acme/gadget' => [
			'pretty_version' => '2.0.0',
			'reference' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
			'install_path' => __DIR__ . '/../acme/gadget',
		],
	],
];
