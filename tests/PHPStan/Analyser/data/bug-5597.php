<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug5597;

interface InterfaceA {}

class ClassA implements InterfaceA {}

class ClassB
{
	public function __construct(
        private InterfaceA $parameterA,
    ) {
    }

	public function test() : InterfaceA
	{
		return $this->parameterA;
	}
}

$classA = new class() extends ClassA {};
$thisWorks = new class($classA) extends ClassB {};

$thisFailsWithTwoErrors = new class(new class() extends ClassA {}) extends ClassB {};
