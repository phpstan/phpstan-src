<?php declare(strict_types = 1);

namespace Bug14207And;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public static function is_not_special(string $tag_name): bool {
		$x = (
			'ADDRESS' !== $tag_name &&
			'APPLET' !== $tag_name &&
			'AREA' !== $tag_name &&
			'ARTICLE' !== $tag_name &&
			'ASIDE' !== $tag_name &&
			'BASE' !== $tag_name &&
			'BASEFONT' !== $tag_name &&
			'BGSOUND' !== $tag_name &&
			'BLOCKQUOTE' !== $tag_name &&
			'BODY' !== $tag_name &&
			'BR' !== $tag_name &&
			'BUTTON' !== $tag_name &&
			'CAPTION' !== $tag_name &&
			'CENTER' !== $tag_name &&
			'COL' !== $tag_name &&
			'COLGROUP' !== $tag_name &&
			'DD' !== $tag_name &&
			'DETAILS' !== $tag_name &&
			'DIR' !== $tag_name &&
			'DIV' !== $tag_name &&
			'DL' !== $tag_name &&
			'DT' !== $tag_name &&
			'EMBED' !== $tag_name &&
			'FIELDSET' !== $tag_name &&
			'FIGCAPTION' !== $tag_name &&
			'FIGURE' !== $tag_name &&
			'FOOTER' !== $tag_name &&
			'FORM' !== $tag_name &&
			'FRAME' !== $tag_name &&
			'FRAMESET' !== $tag_name &&
			'H1' !== $tag_name &&
			'H2' !== $tag_name &&
			'H3' !== $tag_name &&
			'H4' !== $tag_name &&
			'H5' !== $tag_name &&
			'H6' !== $tag_name &&
			'HEAD' !== $tag_name &&
			'HEADER' !== $tag_name &&
			'HGROUP' !== $tag_name &&
			'HR' !== $tag_name &&
			'HTML' !== $tag_name &&
			'IFRAME' !== $tag_name &&
			'IMG' !== $tag_name &&
			'INPUT' !== $tag_name &&
			'KEYGEN' !== $tag_name &&
			'LI' !== $tag_name &&
			'LINK' !== $tag_name &&
			'LISTING' !== $tag_name &&
			'MAIN' !== $tag_name &&
			'MARQUEE' !== $tag_name &&
			'MENU' !== $tag_name &&
			'META' !== $tag_name &&
			'NAV' !== $tag_name &&
			'NOEMBED' !== $tag_name &&
			'NOFRAMES' !== $tag_name &&
			'NOSCRIPT' !== $tag_name &&
			'OBJECT' !== $tag_name &&
			'OL' !== $tag_name &&
			'P' !== $tag_name &&
			'PARAM' !== $tag_name &&
			'PLAINTEXT' !== $tag_name &&
			'PRE' !== $tag_name &&
			'SCRIPT' !== $tag_name &&
			'SEARCH' !== $tag_name &&
			'SECTION' !== $tag_name &&
			'SELECT' !== $tag_name &&
			'SOURCE' !== $tag_name &&
			'STYLE' !== $tag_name &&
			'SUMMARY' !== $tag_name &&
			'TABLE' !== $tag_name &&
			'TBODY' !== $tag_name &&
			'TD' !== $tag_name &&
			'TEMPLATE' !== $tag_name &&
			'TEXTAREA' !== $tag_name &&
			'TFOOT' !== $tag_name &&
			'TH' !== $tag_name &&
			'THEAD' !== $tag_name &&
			'TITLE' !== $tag_name &&
			'TR' !== $tag_name &&
			'TRACK' !== $tag_name &&
			'UL' !== $tag_name &&
			'WBR' !== $tag_name &&
			'XMP' !== $tag_name &&
			'a1' !== $tag_name &&
			'a2' !== $tag_name &&
			'a3' !== $tag_name &&
			'a4' !== $tag_name &&
			'a5' !== $tag_name &&
			'a6' !== $tag_name &&
			'a7' !== $tag_name &&
			'a8' !== $tag_name &&
			'a9' !== $tag_name
		);

		assertType('bool', $x);
		if ($x) {
			assertType('string', $tag_name);
		}

		return $x;
	}
}
