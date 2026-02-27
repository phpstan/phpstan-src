<?php declare(strict_types = 1);

namespace Bug14207;

use function assert;
use function PHPStan\Testing\assertType;

class WP_HTML_Token {
	public string $namespace;
	public string $node_name;
}

class HelloWorld
{
	/**
	 * Returns whether an element of a given name is in the HTML special category.
	 *
	 * @since 6.4.0
	 *
	 * @see https://html.spec.whatwg.org/#special
	 *
	 * @param WP_HTML_Token|string $tag_name Node to check, or only its name if in the HTML namespace.
	 * @return bool Whether the element of the given name is in the special category.
	 */
	public static function is_special( $tag_name ): bool {
		if ( is_string( $tag_name ) ) {
			$tag_name = strtoupper( $tag_name );
		} else {
			$tag_name = 'html' === $tag_name->namespace
				? strtoupper( $tag_name->node_name )
				: "{$tag_name->namespace} {$tag_name->node_name}";
		}

		assertType('non-falsy-string|uppercase-string', $tag_name);

		$x = (
			'ADDRESS' === $tag_name ||
			'APPLET' === $tag_name ||
			'AREA' === $tag_name ||
			'ARTICLE' === $tag_name ||
			'ASIDE' === $tag_name ||
			'BASE' === $tag_name ||
			'BASEFONT' === $tag_name ||
			'BGSOUND' === $tag_name ||
			'BLOCKQUOTE' === $tag_name ||
			'BODY' === $tag_name ||
			'BR' === $tag_name ||
			'BUTTON' === $tag_name ||
			'CAPTION' === $tag_name ||
			'CENTER' === $tag_name ||
			'COL' === $tag_name ||
			'COLGROUP' === $tag_name ||
			'DD' === $tag_name ||
			'DETAILS' === $tag_name ||
			'DIR' === $tag_name ||
			'DIV' === $tag_name ||
			'DL' === $tag_name ||
			'DT' === $tag_name ||
			'EMBED' === $tag_name ||
			'FIELDSET' === $tag_name ||
			'FIGCAPTION' === $tag_name ||
			'FIGURE' === $tag_name ||
			'FOOTER' === $tag_name ||
			'FORM' === $tag_name ||
			'FRAME' === $tag_name ||
			'FRAMESET' === $tag_name ||
			'H1' === $tag_name ||
			'H2' === $tag_name ||
			'H3' === $tag_name ||
			'H4' === $tag_name ||
			'H5' === $tag_name ||
			'H6' === $tag_name ||
			'HEAD' === $tag_name ||
			'HEADER' === $tag_name ||
			'HGROUP' === $tag_name ||
			'HR' === $tag_name ||
			'HTML' === $tag_name ||
			'IFRAME' === $tag_name ||
			'IMG' === $tag_name ||
			'INPUT' === $tag_name ||
			'KEYGEN' === $tag_name ||
			'LI' === $tag_name ||
			'LINK' === $tag_name ||
			'LISTING' === $tag_name ||
			'MAIN' === $tag_name ||
			'MARQUEE' === $tag_name ||
			'MENU' === $tag_name ||
			'META' === $tag_name ||
			'NAV' === $tag_name ||
			'NOEMBED' === $tag_name ||
			'NOFRAMES' === $tag_name ||
			'NOSCRIPT' === $tag_name ||
			'OBJECT' === $tag_name ||
			'OL' === $tag_name ||
			'P' === $tag_name ||
			'PARAM' === $tag_name ||
			'PLAINTEXT' === $tag_name ||
			'PRE' === $tag_name ||
			'SCRIPT' === $tag_name ||
			'SEARCH' === $tag_name ||
			'SECTION' === $tag_name ||
			'SELECT' === $tag_name ||
			'SOURCE' === $tag_name ||
			'STYLE' === $tag_name ||
			'SUMMARY' === $tag_name ||
			'TABLE' === $tag_name ||
			'TBODY' === $tag_name ||
			'TD' === $tag_name ||
			'TEMPLATE' === $tag_name ||
			'TEXTAREA' === $tag_name ||
			'TFOOT' === $tag_name ||
			'TH' === $tag_name ||
			'THEAD' === $tag_name ||
			'TITLE' === $tag_name ||
			'TR' === $tag_name ||
			'TRACK' === $tag_name ||
			'UL' === $tag_name ||
			'WBR' === $tag_name ||
			'XMP' === $tag_name ||

			// MathML.
			'math MI' === $tag_name ||
			'math MO' === $tag_name ||
			'math MN' === $tag_name ||
			'math MS' === $tag_name ||
			'math MTEXT' === $tag_name ||
			'math ANNOTATION-XML' === $tag_name ||

			// SVG.
			'svg DESC' === $tag_name ||
			'svg FOREIGNOBJECT' === $tag_name ||
			'svg TITLE' === $tag_name ||

			// some random stuff
			'a1' === $tag_name ||
			'a2' === $tag_name ||
			'a3' === $tag_name ||
			'a4' === $tag_name ||
			'a5' === $tag_name ||
			'a6' === $tag_name ||
			'a7' === $tag_name ||
			'a8' === $tag_name ||
			'a9' === $tag_name
		);

		assertType('bool', $x);
		if ($x) {
			assertType("'a1'|'a2'|'a3'|'a4'|'a5'|'a6'|'a7'|'a8'|'a9'|'ADDRESS'|'APPLET'|'AREA'|'ARTICLE'|'ASIDE'|'BASE'|'BASEFONT'|'BGSOUND'|'BLOCKQUOTE'|'BODY'|'BR'|'BUTTON'|'CAPTION'|'CENTER'|'COL'|'COLGROUP'|'DD'|'DETAILS'|'DIR'|'DIV'|'DL'|'DT'|'EMBED'|'FIELDSET'|'FIGCAPTION'|'FIGURE'|'FOOTER'|'FORM'|'FRAME'|'FRAMESET'|'H1'|'H2'|'H3'|'H4'|'H5'|'H6'|'HEAD'|'HEADER'|'HGROUP'|'HR'|'HTML'|'IFRAME'|'IMG'|'INPUT'|'KEYGEN'|'LI'|'LINK'|'LISTING'|'MAIN'|'MARQUEE'|'math ANNOTATION-XML'|'math MI'|'math MN'|'math MO'|'math MS'|'math MTEXT'|'MENU'|'META'|'NAV'|'NOEMBED'|'NOFRAMES'|'NOSCRIPT'|'OBJECT'|'OL'|'P'|'PARAM'|'PLAINTEXT'|'PRE'|'SCRIPT'|'SEARCH'|'SECTION'|'SELECT'|'SOURCE'|'STYLE'|'SUMMARY'|'svg DESC'|'svg FOREIGNOBJECT'|'svg TITLE'|'TABLE'|'TBODY'|'TD'|'TEMPLATE'|'TEXTAREA'|'TFOOT'|'TH'|'THEAD'|'TITLE'|'TR'|'TRACK'|'UL'|'WBR'|'XMP'", $tag_name);
		}

		return $x;
	}
}
