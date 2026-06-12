<?php // lint >= 8.0

namespace Bug13881;

class A {

	public function test(string $functionArg): void {
		$localVariable = "test";

		$map = [
			"var" => "1",
		];

		$values = get_defined_vars();

		unset($values["map"]);
		unset($values["functionArg"]);
		unset($values["localVariable"]);
		//unset($values["this"]);

		foreach ($map as $field => $val) {
			$values[$field] = $val;
		}

		$this->varDump(...$values);
	}

	public function varDump(mixed $var): void {
		var_dump($var);
	}
}

$a = new A();
$a->test("a");
