<?php

namespace PHPStan;

// Control characters
dumpType(["\0" => 'NUL', 'NUL' => "\0"]);
dumpType(["\x01" => 'SOH', 'SOH' => "\x01"]);
dumpType(["\x02" => 'STX', 'STX' => "\x02"]);
dumpType(["\x03" => 'ETX', 'ETX' => "\x03"]);
dumpType(["\x04" => 'EOT', 'EOT' => "\x04"]);
dumpType(["\x05" => 'ENQ', 'ENQ' => "\x05"]);
dumpType(["\x06" => 'ACK', 'ACK' => "\x06"]);
dumpType(["\x07" => 'BEL', 'BEL' => "\x07"]);
dumpType(["\x08" => 'BS', 'BS' => "\x08"]);
dumpType(["\t" => 'HT', 'HT' => "\t"]);
dumpType(["\n" => 'LF', 'LF' => "\n"]);
dumpType(["\v" => 'VT', 'VT' => "\v"]);
dumpType(["\f" => 'FF', 'FF' => "\f"]);
dumpType(["\r" => 'CR', 'CR' => "\r"]);
dumpType(["\x0e" => 'SO', 'SO' => "\x0e"]);
dumpType(["\x0f" => 'SI', 'SI' => "\x0f"]);
dumpType(["\x10" => 'DLE', 'DLE' => "\x10"]);
dumpType(["\x11" => 'DC1', 'DC1' => "\x11"]);
dumpType(["\x12" => 'DC2', 'DC2' => "\x12"]);
dumpType(["\x13" => 'DC3', 'DC3' => "\x13"]);
dumpType(["\x14" => 'DC4', 'DC4' => "\x14"]);
dumpType(["\x15" => 'NAK', 'NAK' => "\x15"]);
dumpType(["\x16" => 'SYN', 'SYN' => "\x16"]);
dumpType(["\x17" => 'ETB', 'ETB' => "\x17"]);
dumpType(["\x18" => 'CAN', 'CAN' => "\x18"]);
dumpType(["\x19" => 'EM', 'EM' => "\x19"]);
dumpType(["\x1a" => 'SUB', 'SUB' => "\x1a"]);
dumpType(["\e" => 'ESC', 'ESC' => "\e"]);
dumpType(["\x1c" => 'FS', 'FS' => "\x1c"]);
dumpType(["\x1d" => 'GS', 'GS' => "\x1d"]);
dumpType(["\x1e" => 'RS', 'RS' => "\x1e"]);
dumpType(["\x1f" => 'US', 'US' => "\x1f"]);
dumpType(["\x7f" => 'DEL', 'DEL' => "\x7f"]);

// Space
dumpType([" " => 'SP', 'SP' => ' ']);
dumpType(["foo " => 'ends with SP', " foo" => 'starts with SP', " foo " => 'surrounded by SP', 'foo' => 'no SP']);

// Punctuation marks
dumpType(["foo?" => 'foo?']);
dumpType(["shallwedance" => 'yes']);
dumpType(["shallwedance?" => 'yes']);
dumpType(["Shall we dance" => 'yes']);
dumpType(["Shall we dance?" => 'yes']);
dumpType(["shall_we_dance" => 'yes']);
dumpType(["shall_we_dance?" => 'yes']);
dumpType(["shall-we-dance" => 'yes']);
dumpType(["shall-we-dance?" => 'yes']);
dumpType(["Let'go" => "Let'go"]);
dumpType(['"HELLO!!"' => '"HELLO!!"']);
dumpType(['Don\'t say "lazy"' => 'Don\'t say "lazy"']);
dumpType(['Foo\\Bar' => 'Foo\\Bar']);
