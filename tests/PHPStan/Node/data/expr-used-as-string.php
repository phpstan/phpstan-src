<?php // lint >= 8.3

declare(strict_types = 1);

namespace ExprUsedAsString;

function doFoo(string $s)
{
	echo 'plain';
	echo '<script src="' . $s . '" nonce=123></script>';
	echo "<script src=\"$s\" nonce=123></script>";
	print 'printed';
	print 'a' . $s . 'b';
	$x = (string) $s;
	$s .= "appended";
	$s .= ' src="' . $s . '"';
	$t = $s . 'plain';
	$u = "interp $s end";
	?>
<script src="my.js" nonce=123></script>
<?php
}

function doMore(): void
{
	$nonce = '123';
	$html = '';
	$html .= <<<EOS
<script nonce="{$nonce}" type="module">
EOS;
}

class Holder
{

	public string $prop = '';

	public function method(): void
	{
	}

	public const CONST_NAME = 1;

}

function dynamicNames(Holder $h, string $name): void
{
	echo $h->{$name};
	$h->{$name}();
	$$name = 1;
	$x = Holder::${$name};
	Holder::{$name}();
	$y = $h::{$name};
}
