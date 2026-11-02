<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\GenerateFactory;

interface ValueFactory
{

	public function create(string $name): Value;

}
