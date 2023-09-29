<?php declare(strict_types=1);

namespace Bug7273b;

class HelloWorld
{
	/** @var array<non-empty-string, array{url?: non-empty-string, title: non-empty-string}> */
	public const FIRST_EXAMPLE = [
		'image.png'          => [
			'url'   => '/first-link',
			'title' => 'First Link',
		],
		'directory/image.jpg' => [
			'url'   => '/second-link',
			'title' => 'Second Link',
		],
		'another-image.jpg' => [
			'title' => 'An Image',
		],
	];

	/** @var array<string, array{url?: string, title: string}> */
	public const SECOND_EXAMPLE = [
		'image.png'          => [
			'url'   => '/first-link',
			'title' => 'First Link',
		],
		'directory/image.jpg' => [
			'url'   => '/second-link',
			'title' => 'Second Link',
		],
		'another-image.jpg' => [
			'title' => 'An Image',
		],
	];

	/** @var array{url?: string, title: string}[] */
	public const THIRD_EXAMPLE = [
		'image.png'          => [
			'url'   => '/first-link',
			'title' => 'First Link',
		],
		'directory/image.jpg' => [
			'url'   => '/second-link',
			'title' => 'Second Link',
		],
		'another-image.jpg' => [
			'title' => 'An Image',
		],
	];
}
