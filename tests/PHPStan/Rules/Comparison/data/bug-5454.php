<?php // lint >= 8.0

namespace Bug5454;

class TextFormat{
	public const ESCAPE = "\xc2\xa7"; //§

	public const BLACK = TextFormat::ESCAPE . "0";
	public const DARK_BLUE = TextFormat::ESCAPE . "1";
	public const DARK_GREEN = TextFormat::ESCAPE . "2";
	public const DARK_AQUA = TextFormat::ESCAPE . "3";
	public const DARK_RED = TextFormat::ESCAPE . "4";
	public const DARK_PURPLE = TextFormat::ESCAPE . "5";

	public const OBFUSCATED = TextFormat::ESCAPE . "k";
	public const BOLD = TextFormat::ESCAPE . "l";
	public const STRIKETHROUGH = TextFormat::ESCAPE . "m";
	public const UNDERLINE = TextFormat::ESCAPE . "n";
	public const ITALIC = TextFormat::ESCAPE . "o";
	public const RESET = TextFormat::ESCAPE . "r";
}

class Terminal
{

	/**
	 * @param string[] $string
	 */
	public static function toANSI(array $string) : string{
		$newString = "";
		foreach($string as $token){
			$newString .= match ($token){
				TextFormat::BOLD => "bold",
				TextFormat::OBFUSCATED => "obf",
				TextFormat::ITALIC => "italic",
				TextFormat::UNDERLINE => "underline",
				TextFormat::STRIKETHROUGH => "strike",
				TextFormat::RESET => "reset",
				TextFormat::BLACK => "black",
				TextFormat::DARK_BLUE => "blue",
				TextFormat::DARK_GREEN => "green",
				TextFormat::DARK_AQUA => "aqua",
				TextFormat::DARK_RED => "red",
				default => $token,
			};
		}

		return $newString;
	}
}
