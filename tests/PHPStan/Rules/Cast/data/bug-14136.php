<?php declare(strict_types = 1);

namespace Bug14136Cast;

$xml = <<<'EOT'
<?xml version="1.0"?><item>3</item>
EOT;

$xml = simplexml_load_string($xml);
var_dump(intval($xml));
var_dump(intval(gmp_init(42)));
var_dump((int) ($xml));
var_dump((int)(gmp_init(42)));

var_dump(floatval ($xml));
var_dump(floatval(gmp_init(42)));
var_dump((float) ($xml));
var_dump((float)(gmp_init(42)));

var_dump(strval($xml));
var_dump(strval(gmp_init(42)));
var_dump((string) ($xml));
var_dump((string)(gmp_init(42)));

var_dump(boolval($xml));
var_dump(boolval(gmp_init(42)));
var_dump((bool) ($xml));
var_dump((bool) (gmp_init(42)));
