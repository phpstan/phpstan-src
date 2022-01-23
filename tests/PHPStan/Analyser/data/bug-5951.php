<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug5951;

#[\Attribute]
class Route
{

	/** @param string[] $methods */
	public function __construct(public string $path, public string $name, public array $methods)
	{

	}

}

class Response
{

}

final class SomeController
{
	public const ROUTE_INDEX = 'some_index';

	#[Route('/some', name: self::ROUTE_INDEX, methods: ['GET'])]
	public function index(): Response
	{
		return new Response();
	}
}
