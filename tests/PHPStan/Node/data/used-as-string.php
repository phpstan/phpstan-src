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

	public int $num = 1;

	public static string $staticProp = '';

	public function method(): void
	{
	}

	public const CONST_NAME = 1;

	public function assignProperties(): void
	{
		$this->prop = 'assigned to string property';
		$this->num = 5;
		self::$staticProp = 'assigned to static string property';
	}

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

function takesString(string $s): void
{
}

function takesInt(int $i): void
{
}

function passArguments(Holder $h, string $s): void
{
	takesString($s);
	takesString('passed as string argument');
	takesInt(5);
	takesInt($h->num);
}
