<?php // lint >= 8.0

namespace MbstringDeprecatedEncoding;

function doFoo(string $s, bool $b, string $encoding): void
{
	mb_convert_encoding($s, 'HTML-ENTITIES', 'UTF-8');
	mb_convert_encoding($s, 'html');
	mb_strlen($s, 'base64');
	mb_substr($s, 0, 1, 'UUENCODE');
	mb_strtoupper($s, 'Quoted-Printable');
	mb_check_encoding($s, 'qprint');
	mb_str_pad($s, 10, encoding: 'HTML-ENTITIES');
	\mb_strwidth($s, $b ? 'HTML' : 'HTML-ENTITIES');

	mb_convert_encoding($s, 'UTF-8', 'HTML-ENTITIES');
	mb_convert_encoding($s, 'UTF-8', ['UTF-8', 'BASE64']);
	mb_strlen($s, $b ? 'UTF-8' : 'BASE64');
	mb_strlen($s, $b ? 'HTML' : 'BASE64');
	mb_strlen($s, 'UTF-8');
	mb_strlen($s, $encoding);
	mb_strlen($s);
	mb_internal_encoding('HTML-ENTITIES');
	mb_http_output('BASE64');
	htmlspecialchars($s, ENT_QUOTES, 'HTML-ENTITIES');
}
