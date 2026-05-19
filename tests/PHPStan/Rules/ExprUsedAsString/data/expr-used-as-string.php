<?php // lint >= 8.0

namespace ExprUsedAsString;

class Foo {

	public function doEcho(): void {
		echo 'hello';
		echo 'hello', ' world';
	}

	public function doPrint(): void {
		print 'hello';
	}

	public function doCast(int $i): void {
		(string) $i;
	}

	public function doConcat(string $a, string $b): void {
		$a . $b;
		'a' . $b . 'c';
	}

	public function doInterpolatedString(string $name): void {
		"Hello $name!";
	}

	public function doConcatAssign(string $a, string $b): void {
		$a .= $b;
	}

}

function doEchoConcat(string $s): void {
	echo '<script src="' . $s . '" nonce=123></script>';
}

function doHeredoc(): void {
	$nonce = '123';
	$html = <<<EOS
	<script nonce="{$nonce}" type="module">
	EOS;
}
