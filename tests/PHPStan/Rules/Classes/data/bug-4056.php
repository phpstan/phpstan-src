<?php

namespace Bug4056;

$a = new \ArrayObject([
	'Dummy' => new \ArrayObject([
		'properties' => [
			'id' => [
				'readOnly' => true,
				'type' => 'integer',
			],
			'description' => [
				'type' => 'string',
			],
		],
	]),
	'Dummy-list' => new \ArrayObject([
		'properties' => [
			'id' => [
				'readOnly' => true,
				'type' => 'integer',
			],
			'description' => [
				'type' => 'string',
			],
		],
	]),
	'Dummy-list_details' => new \ArrayObject([
		'properties' => [
			'id' => [
				'readOnly' => true,
				'type' => 'integer',
			],
			'description' => [
				'type' => 'string',
			],
			'relatedDummy' => new \ArrayObject([
				'$ref' => '#/definitions/RelatedDummy-list_details',
			]),
		],
	]),
	'Dummy:OutputDto' => new \ArrayObject([
		'type' => 'object',
		'properties' => [
			'baz' => new \ArrayObject([
				'readOnly' => true,
				'type' => 'string',
			]),
			'bat' => new \ArrayObject([
				'type' => 'integer',
			]),
		],
	]),
	'Dummy:InputDto' => new \ArrayObject([
		'type' => 'object',
		'properties' => [
			'foo' => new \ArrayObject([
				'type' => 'string',
			]),
			'bar' => new \ArrayObject([
				'type' => 'integer',
			]),
		],
	]),
	'RelatedDummy-list_details' => new \ArrayObject([
		'type' => 'object',
		'properties' => [
			'name' => new \ArrayObject([
				'type' => 'string',
			]),
		],
	]),
]);
