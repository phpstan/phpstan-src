<?php
$array = [];
$string = '';
$int = 1;
$float = .1;
$resource = fopen('./.env', 'r');
\assert(false !== $resource);
$bool = true;
$object = new stdClass();
$stringable = new class() {
	public function __toString(): string {
		return '';
	}
};
$null = null;
/** @var mixed $mixed */
$mixed = null;

echo (string) $array . PHP_EOL;
echo strval($array) . PHP_EOL;

echo (int) $array . PHP_EOL;
echo intval($array) . PHP_EOL;

echo (float) $array . PHP_EOL;
echo floatval($array) . PHP_EOL;
echo doubleval($array) . PHP_EOL;

echo (string) $string . PHP_EOL;
echo strval($string) . PHP_EOL;

echo (int) $string . PHP_EOL;
echo intval($string) . PHP_EOL;

echo (float) $string . PHP_EOL;
echo floatval($string) . PHP_EOL;
echo doubleval($string) . PHP_EOL;

echo (string) $int . PHP_EOL;
echo strval($int) . PHP_EOL;

echo (int) $int . PHP_EOL;
echo intval($int) . PHP_EOL;

echo (float) $int . PHP_EOL;
echo floatval($int) . PHP_EOL;
echo doubleval($int) . PHP_EOL;

echo (string) $float . PHP_EOL;
echo strval($float) . PHP_EOL;

echo (int) $float . PHP_EOL;
echo intval($float) . PHP_EOL;

echo (float) $float . PHP_EOL;
echo floatval($float) . PHP_EOL;
echo doubleval($float) . PHP_EOL;

echo (string) $resource . PHP_EOL;
echo strval($resource) . PHP_EOL;

echo (int) $resource . PHP_EOL;
echo intval($resource) . PHP_EOL;

echo (float) $resource . PHP_EOL;
echo floatval($resource) . PHP_EOL;
echo doubleval($resource) . PHP_EOL;

echo (string) $bool . PHP_EOL;
echo strval($bool) . PHP_EOL;

echo (int) $bool . PHP_EOL;
echo intval($bool) . PHP_EOL;

echo (float) $bool . PHP_EOL;
echo floatval($bool) . PHP_EOL;
echo doubleval($bool) . PHP_EOL;

echo (string) $object . PHP_EOL;
echo strval($object) . PHP_EOL;

echo (int) $object . PHP_EOL;
echo intval($object) . PHP_EOL;

echo (float) $object . PHP_EOL;
echo floatval($object) . PHP_EOL;
echo doubleval($object) . PHP_EOL;

echo (string) $stringable . PHP_EOL;
echo strval($stringable) . PHP_EOL;

echo (int) $stringable . PHP_EOL;
echo intval($stringable) . PHP_EOL;

echo (float) $stringable . PHP_EOL;
echo floatval($stringable) . PHP_EOL;
echo doubleval($stringable) . PHP_EOL;

echo (string) $mixed . PHP_EOL;
echo strval($mixed) . PHP_EOL;

echo (int) $mixed . PHP_EOL;
echo intval($mixed) . PHP_EOL;

echo (float) $mixed . PHP_EOL;
echo floatval($mixed) . PHP_EOL;
echo doubleval($mixed) . PHP_EOL;
