<?php
$object = new stdClass();
$stringable = new class() {
	public function __toString(): string {
		return '';
	}
};

echo intval($object);
echo intval($stringable);

echo floatval($object);
echo floatval($stringable);

echo doubleval($object);
echo doubleval($stringable);
