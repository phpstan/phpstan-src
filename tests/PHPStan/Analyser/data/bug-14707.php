<?php declare(strict_types = 1);

namespace Bug14707;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class AttributeUsingItself
{
	#[AttributeUsingItself('hi')]
	public function __construct(string $param) {}
}

#[Attribute(Attribute::TARGET_ALL)]
class AttrA
{
	#[AttrB('world')]
	public function __construct(string $param) {}
}

#[Attribute(Attribute::TARGET_ALL)]
class AttrB
{
	#[AttrA('hello')]
	public function __construct(string $param) {}
}

#[Attribute(Attribute::TARGET_ALL)]
class SelfRefOnParam
{
	public function __construct(#[SelfRefOnParam('hello')] string $param) {}
}
