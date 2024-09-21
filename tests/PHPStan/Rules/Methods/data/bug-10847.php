<?php

namespace Bug10847;

class FunctionRegistry
{
	/**
	 * @var array<string, array{overloads: array<string, callable(list<Value>): Value>, url: string}>
	 */
	private const BUILTIN_FUNCTIONS = [
		// sass:color
		'red' => ['overloads' => ['$color' => [ColorFunctions::class, 'red']], 'url' => 'sass:color'],
		'green' => ['overloads' => ['$color' => [ColorFunctions::class, 'green']], 'url' => 'sass:color'],
		'blue' => ['overloads' => ['$color' => [ColorFunctions::class, 'blue']], 'url' => 'sass:color'],
		'mix' => ['overloads' => ['$color1, $color2, $weight: 50%' => [ColorFunctions::class, 'mix']], 'url' => 'sass:color'],
		'rgb' => ['overloads' => [
			'$red, $green, $blue, $alpha' => [ColorFunctions::class, 'rgb'],
			'$red, $green, $blue' => [ColorFunctions::class, 'rgb'],
			'$color, $alpha' => [ColorFunctions::class, 'rgbTwoArgs'],
			'$channels' => [ColorFunctions::class, 'rgbOneArgs'],
		], 'url' => 'sass:color'],
		'rgba' => ['overloads' => [
			'$red, $green, $blue, $alpha' => [ColorFunctions::class, 'rgba'],
			'$red, $green, $blue' => [ColorFunctions::class, 'rgba'],
			'$color, $alpha' => [ColorFunctions::class, 'rgbaTwoArgs'],
			'$channels' => [ColorFunctions::class, 'rgbaOneArgs'],
		], 'url' => 'sass:color'],
		'invert' => ['overloads' => ['$color, $weight: 100%' => [ColorFunctions::class, 'invert']], 'url' => 'sass:color'],
		'hue' => ['overloads' => ['$color' => [ColorFunctions::class, 'hue']], 'url' => 'sass:color'],
		'saturation' => ['overloads' => ['$color' => [ColorFunctions::class, 'saturation']], 'url' => 'sass:color'],
		'lightness' => ['overloads' => ['$color' => [ColorFunctions::class, 'lightness']], 'url' => 'sass:color'],
		'complement' => ['overloads' => ['$color' => [ColorFunctions::class, 'complement']], 'url' => 'sass:color'],
		'hsl' => ['overloads' => [
			'$hue, $saturation, $lightness, $alpha' => [ColorFunctions::class, 'hsl'],
			'$hue, $saturation, $lightness' => [ColorFunctions::class, 'hsl'],
			'$hue, $saturation' => [ColorFunctions::class, 'hslTwoArgs'],
			'$channels' => [ColorFunctions::class, 'hslOneArgs'],
		], 'url' => 'sass:color'],
		'hsla' => ['overloads' => [
			'$hue, $saturation, $lightness, $alpha' => [ColorFunctions::class, 'hsla'],
			'$hue, $saturation, $lightness' => [ColorFunctions::class, 'hsla'],
			'$hue, $saturation' => [ColorFunctions::class, 'hslaTwoArgs'],
			'$channels' => [ColorFunctions::class, 'hslaOneArgs'],
		], 'url' => 'sass:color'],
		'grayscale' => ['overloads' => ['$color' => [ColorFunctions::class, 'grayscale']], 'url' => 'sass:color'],
		'adjust-hue' => ['overloads' => ['$color, $degrees' => [ColorFunctions::class, 'adjustHue']], 'url' => 'sass:color'],
		'lighten' => ['overloads' => ['$color, $amount' => [ColorFunctions::class, 'lighten']], 'url' => 'sass:color'],
		'darken' => ['overloads' => ['$color, $amount' => [ColorFunctions::class, 'darken']], 'url' => 'sass:color'],
		'saturate' => ['overloads' => [
			'$amount' => [ColorFunctions::class, 'saturateCss'],
			'$color, $amount' => [ColorFunctions::class, 'saturate'],
		], 'url' => 'sass:color'],
		'desaturate' => ['overloads' => ['$color, $amount' => [ColorFunctions::class, 'desaturate']], 'url' => 'sass:color'],
		'opacify' => ['overloads' => ['$color, $amount' => [ColorFunctions::class, 'opacify']], 'url' => 'sass:color'],
		'fade-in' => ['overloads' => ['$color, $amount' => [ColorFunctions::class, 'opacify']], 'url' => 'sass:color'],
		'transparentize' => ['overloads' => ['$color, $amount' => [ColorFunctions::class, 'transparentize']], 'url' => 'sass:color'],
		'fade-out' => ['overloads' => ['$color, $amount' => [ColorFunctions::class, 'transparentize']], 'url' => 'sass:color'],
		'alpha' => ['overloads' => [
			'$color' => [ColorFunctions::class, 'alpha'],
			'$args...' => [ColorFunctions::class, 'alphaMicrosoft'],
		], 'url' => 'sass:color'],
		'opacity' => ['overloads' => ['$color' => [ColorFunctions::class, 'opacity']], 'url' => 'sass:color'],
		'ie-hex-str' => ['overloads' => ['$color' => [ColorFunctions::class, 'ieHexStr']], 'url' => 'sass:color'],
		'adjust-color' => ['overloads' => ['$color, $kwargs...' => [ColorFunctions::class, 'adjust']], 'url' => 'sass:color'],
		'scale-color' => ['overloads' => ['$color, $kwargs...' => [ColorFunctions::class, 'scale']], 'url' => 'sass:color'],
		'change-color' => ['overloads' => ['$color, $kwargs...' => [ColorFunctions::class, 'change']], 'url' => 'sass:color'],
		// sass:list
		'length' => ['overloads' => ['$list' => [ListFunctions::class, 'length']], 'url' => 'sass:list'],
		'nth' => ['overloads' => ['$list, $n' => [ListFunctions::class, 'nth']], 'url' => 'sass:list'],
		'set-nth' => ['overloads' => ['$list, $n, $value' => [ListFunctions::class, 'setNth']], 'url' => 'sass:list'],
		'join' => ['overloads' => ['$list1, $list2, $separator: auto, $bracketed: auto' => [ListFunctions::class, 'join']], 'url' => 'sass:list'],
		'append' => ['overloads' => ['$list, $val, $separator: auto' => [ListFunctions::class, 'append']], 'url' => 'sass:list'],
		'zip' => ['overloads' => ['$lists...' => [ListFunctions::class, 'zip']], 'url' => 'sass:list'],
		'index' => ['overloads' => ['$list, $value' => [ListFunctions::class, 'index']], 'url' => 'sass:list'],
		'is-bracketed' => ['overloads' => ['$list' => [ListFunctions::class, 'isBracketed']], 'url' => 'sass:list'],
		'list-separator' => ['overloads' => ['$list' => [ListFunctions::class, 'separator']], 'url' => 'sass:list'],
		// sass:map
		'map-get' => ['overloads' => ['$map, $key, $keys...' => [MapFunctions::class, 'get']], 'url' => 'sass:map'],
		'map-merge' => ['overloads' => [
			'$map1, $map2' => [MapFunctions::class, 'mergeTwoArgs'],
			'$map1, $args...' => [MapFunctions::class, 'mergeVariadic'],
		], 'url' => 'sass:map'],
		'map-remove' => ['overloads' => [
			// Because the signature below has an explicit `$key` argument, it doesn't
			// allow zero keys to be passed. We want to allow that case, so we add an
			// explicit overload for it.
			'$map' => [MapFunctions::class, 'removeNoKeys'],
			// The first argument has special handling so that the $key parameter can be
			// passed by name.
			'$map, $key, $keys...' => [MapFunctions::class, 'remove'],
		], 'url' => 'sass:map'],
		'map-keys' => ['overloads' => ['$map' => [MapFunctions::class, 'keys']], 'url' => 'sass:map'],
		'map-values' => ['overloads' => ['$map' => [MapFunctions::class, 'values']], 'url' => 'sass:map'],
		'map-has-key' => ['overloads' => ['map, $key, $keys...' => [MapFunctions::class, 'hasKey']], 'url' => 'sass:map'],
		// sass:math
		'abs' => ['overloads' => ['$number' => [MathFunctions::class, 'abs']], 'url' => 'sass:math'],
		'ceil' => ['overloads' => ['$number' => [MathFunctions::class, 'ceil']], 'url' => 'sass:math'],
		'floor' => ['overloads' => ['$number' => [MathFunctions::class, 'floor']], 'url' => 'sass:math'],
		'max' => ['overloads' => ['$numbers...' => [MathFunctions::class, 'max']], 'url' => 'sass:math'],
		'min' => ['overloads' => ['$numbers...' => [MathFunctions::class, 'min']], 'url' => 'sass:math'],
		'random' => ['overloads' => ['$limit: null' => [MathFunctions::class, 'random']], 'url' => 'sass:math'],
		'percentage' => ['overloads' => ['$number' => [MathFunctions::class, 'percentage']], 'url' => 'sass:math'],
		'round' => ['overloads' => ['$number' => [MathFunctions::class, 'round']], 'url' => 'sass:math'],
		'unit' => ['overloads' => ['$number' => [MathFunctions::class, 'unit']], 'url' => 'sass:math'],
		'comparable' => ['overloads' => ['$number1, $number2' => [MathFunctions::class, 'compatible']], 'url' => 'sass:math'],
		'unitless' => ['overloads' => ['$number' => [MathFunctions::class, 'isUnitless']], 'url' => 'sass:math'],
		// sass:meta
		'feature-exists' => ['overloads' => ['$feature' => [MetaFunctions::class, 'featureExists']], 'url' => 'sass:meta'],
		'inspect' => ['overloads' => ['$value' => [MetaFunctions::class, 'inspect']], 'url' => 'sass:meta'],
		'type-of' => ['overloads' => ['$value' => [MetaFunctions::class, 'typeof']], 'url' => 'sass:meta'],
		// sass:selector
		'is-superselector' => ['overloads' => ['$super, $sub' => [SelectorFunctions::class, 'isSuperselector']], 'url' => 'sass:selector'],
		'simple-selectors' => ['overloads' => ['$selector' => [SelectorFunctions::class, 'simpleSelectors']], 'url' => 'sass:selector'],
		'selector-parse' => ['overloads' => ['$selector' => [SelectorFunctions::class, 'parse']], 'url' => 'sass:selector'],
		'selector-nest' => ['overloads' => ['$selectors...' => [SelectorFunctions::class, 'nest']], 'url' => 'sass:selector'],
		'selector-append' => ['overloads' => ['$selectors...' => [SelectorFunctions::class, 'append']], 'url' => 'sass:selector'],
		'selector-extend' => ['overloads' => ['$selector, $extendee, $extender' => [SelectorFunctions::class, 'extend']], 'url' => 'sass:selector'],
		'selector-replace' => ['overloads' => ['$selector, $original, $replacement' => [SelectorFunctions::class, 'replace']], 'url' => 'sass:selector'],
		'selector-unify' => ['overloads' => ['$selector1, $selector2' => [SelectorFunctions::class, 'unify']], 'url' => 'sass:selector'],
		// sass:string
		'unquote' => ['overloads' => ['$string' => [StringFunctions::class, 'unquote']], 'url' => 'sass:string'],
		'quote' => ['overloads' => ['$string' => [StringFunctions::class, 'quote']], 'url' => 'sass:string'],
		'to-upper-case' => ['overloads' => ['$string' => [StringFunctions::class, 'toUpperCase']], 'url' => 'sass:string'],
		'to-lower-case' => ['overloads' => ['$string' => [StringFunctions::class, 'toLowerCase']], 'url' => 'sass:string'],
		'uniqueId' => ['overloads' => ['' => [StringFunctions::class, 'uniqueId']], 'url' => 'sass:string'],
		'str-length' => ['overloads' => ['$string' => [StringFunctions::class, 'length']], 'url' => 'sass:string'],
		'str-insert' => ['overloads' => ['$string, $insert, $index' => [StringFunctions::class, 'insert']], 'url' => 'sass:string'],
		'str-index' => ['overloads' => ['$string, $substring' => [StringFunctions::class, 'index']], 'url' => 'sass:string'],
		'str-slice' => ['overloads' => ['$string, $start-at, $end-at: -1' => [StringFunctions::class, 'slice']], 'url' => 'sass:string'],
	];

	public static function has(string $name): bool
	{
		return isset(self::BUILTIN_FUNCTIONS[$name]);
	}

	public static function get(string $name): BuiltInCallable
	{
		if (!isset(self::BUILTIN_FUNCTIONS[$name])) {
			throw new \InvalidArgumentException("There is no builtin function named $name.");
		}

		return BuiltInCallable::overloadedFunction($name, self::BUILTIN_FUNCTIONS[$name]['overloads'], self::BUILTIN_FUNCTIONS[$name]['url']);
	}
}

abstract class Value {}

class BuiltInCallable
{
	/**
	 * @param array<string, callable(list<Value>): Value> $overloads
	 */
	public static function overloadedFunction(string $name, array $overloads, ?string $url = null): BuiltInCallable
	{
		$processedOverloads = [];

		foreach ($overloads as $args => $callback) {
			$overloads[] = [
				$args,
				$callback,
			];
		}

		return new BuiltInCallable($name, $processedOverloads, $url);
	}

	/**
	 * @param list<array{string, callable(list<Value>): Value}> $overloads
	 */
	private function __construct(public readonly string $name, public readonly array $overloads, public readonly ?string $url)
	{
	}
}

/**
 * @internal
 */
class ColorFunctions
{
	/**
	 * @param list<Value> $arguments
	 */
	public static function rgb(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function rgbTwoArgs(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function rgbOneArgs(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function rgba(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function rgbaTwoArgs(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function rgbaOneArgs(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function invert(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function hsl(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function hslTwoArgs(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function hslOneArgs(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function hsla(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function hslaTwoArgs(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function hslaOneArgs(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function grayscale(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function adjustHue(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function lighten(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function darken(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function saturateCss(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function saturate(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function desaturate(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function alpha(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function alphaMicrosoft(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function opacity(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function red(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function green(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function blue(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function mix(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function hue(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function saturation(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function lightness(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function complement(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function adjust(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function scale(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function change(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function ieHexStr(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function opacify(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function transparentize(array $arguments): Value
	{
		return $arguments[0];
	}
}
class ListFunctions
{
	/**
	 * @param list<Value> $arguments
	 */
	public static function length(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function nth(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function setNth(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function join(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function append(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function zip(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function index(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function separator(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function isBracketed(array $arguments): Value
	{
		return $arguments[0];
	}
}
class MapFunctions
{
	/**
	 * @param list<Value> $arguments
	 */
	public static function get(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function mergeTwoArgs(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function mergeVariadic(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function removeNoKeys(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function remove(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function keys(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function values(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function hasKey(array $arguments): Value
	{
		return $arguments[0];
	}
}
final class MathFunctions
{
	/**
	 * @param list<Value> $arguments
	 */
	public static function abs(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function ceil(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function floor(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function max(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function min(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function round(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function compatible(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function isUnitless(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function unit(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function percentage(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function random(array $arguments): Value
	{
		return $arguments[0];
	}
}
final class MetaFunctions
{
	/**
	 * @param list<Value> $arguments
	 */
	public static function featureExists(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function inspect(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function typeof(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function keywords(array $arguments): Value
	{
		return $arguments[0];
	}
}
final class SelectorFunctions
{
	/**
	 * @param list<Value> $arguments
	 */
	public static function nest(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function append(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function extend(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function replace(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function unify(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function isSuperselector(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function simpleSelectors(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function parse(array $arguments): Value
	{
		return $arguments[0];
	}
}
final class StringFunctions
{
	/**
	 * @param list<Value> $arguments
	 */
	public static function unquote(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function quote(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function length(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function insert(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function index(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function slice(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function toUpperCase(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function toLowerCase(array $arguments): Value
	{
		return $arguments[0];
	}

	/**
	 * @param list<Value> $arguments
	 */
	public static function uniqueId(array $arguments): Value
	{
		return $arguments[0];
	}
}
