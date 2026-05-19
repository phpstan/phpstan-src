<?php

$microtimeStringWithoutArg = microtime();
$microtimeString = microtime(false);
$microtimeFloat = microtime(true);
$microtimeDefault = microtime(null);
$microtimeBenevolent = microtime($undefined);

$versionCompare1 = version_compare('7.0.0', '7.0.1');
$versionCompare2 = version_compare('7.0.0', doFoo() ? '7.0.1' : '6.0.0');
$versionCompare3 = version_compare(doFoo() ? '7.0.0' : '6.0.5', doBar() ? '7.0.1' : '6.0.0');
$versionCompare4 = version_compare('7.0.0', doFoo());
$versionCompare5 = version_compare('7.0.0', '7.0.1', '<');
$versionCompare6 = version_compare('7.0.0', doFoo() ? '7.0.1' : '6.0.0', '<');
$versionCompare7 = version_compare(doFoo() ? '7.0.0' : '6.0.5', doBar() ? '7.0.1' : '6.0.0', '<');
$versionCompare8 = version_compare('7.0.0', doFoo(), '<');

$mbHttpOutputWithoutEncoding = mb_http_output();
$mbHttpOutputWithValidEncoding = mb_http_output('utf-8');
$mbHttpOutputWithInvalidEncoding = mb_http_output('foo');
$mbHttpOutputWithValidAndInvalidEncoding = mb_http_output(doFoo() ? 'utf-8' : 'foo');
$mbHttpOutputWithUnknownEncoding = mb_http_output(doFoo());

$mbRegexEncodingWithoutEncoding = mb_regex_encoding();
$mbRegexEncodingWithValidEncoding = mb_regex_encoding('utf-8');
$mbRegexEncodingWithInvalidEncoding = mb_regex_encoding('foo');
$mbRegexEncodingWithValidAndInvalidEncoding = mb_regex_encoding(doFoo() ? 'utf8' : 'foo');
$mbRegexEncodingWithUnknownEncoding = mb_regex_encoding(doFoo());

$mbInternalEncodingWithoutEncoding = mb_internal_encoding();
$mbInternalEncodingWithValidEncoding = mb_internal_encoding('utf-8');
$mbInternalEncodingWithInvalidEncoding = mb_internal_encoding('foo');
$mbInternalEncodingWithValidAndInvalidEncoding = mb_internal_encoding(doFoo() ? 'utf-8' : 'foo');
$mbInternalEncodingWithUnknownEncoding = mb_internal_encoding(doFoo());

$mbEncodingAliasesWithValidEncoding = mb_encoding_aliases('utf-8');
$mbEncodingAliasesWithInvalidEncoding = mb_encoding_aliases('foo');
$mbEncodingAliasesWithValidAndInvalidEncoding = mb_encoding_aliases(doFoo() ? 'utf-8' : 'foo');
$mbEncodingAliasesWithUnknownEncoding = mb_encoding_aliases(doFoo());

$mbChrWithoutEncoding = mb_chr(68);
$mbChrWithValidEncoding = mb_chr(68, 'utf-8');
$mbChrWithInvalidEncoding = mb_chr(68, 'foo');
$mbChrWithValidAndInvalidEncoding = mb_chr(68, doFoo() ? 'utf-8' : 'foo');
$mbChrWithUnknownEncoding = mb_chr(68, doFoo());

$mbOrdWithoutEncoding = mb_ord('');
$mbOrdWithValidEncoding = mb_ord('', 'utf-8');
$mbOrdWithInvalidEncoding = mb_ord('', 'foo');
$mbOrdWithValidAndInvalidEncoding = mb_ord('', doFoo() ? 'utf-8' : 'foo');
$mbOrdWithUnknownEncoding = mb_ord('', doFoo());

$gettimeofdayArrayWithoutArg = gettimeofday();
$gettimeofdayArray = gettimeofday(false);
$gettimeofdayFloat = gettimeofday(true);
$gettimeofdayDefault = gettimeofday(null);
$gettimeofdayBenevolent = gettimeofday($undefined);

// parse_url
/** @var int $integer */
$integer = doFoo();
$parseUrlWithoutParameters = parse_url();
$parseUrlConstantUrlWithoutComponent1 = parse_url('http://abc.def');
$parseUrlConstantUrlWithoutComponent2 = parse_url('http://def.abc', -1);
$parseUrlConstantUrlUnknownComponent = parse_url('http://def.abc', $integer);
$parseUrlConstantUrlWithComponentNull = parse_url('http://def.abc', PHP_URL_FRAGMENT);
$parseUrlConstantUrlWithComponentSet = parse_url('http://def.abc#this-is-fragment', PHP_URL_FRAGMENT);
$parseUrlConstantUrlWithComponentInvalid = parse_url('http://def.abc#this-is-fragment', 9999);
$parseUrlStringUrlWithComponentInvalid = parse_url($string, 9999);
$parseUrlStringUrlWithComponentPort = parse_url($string, PHP_URL_PORT);
$parseUrlStringUrlWithoutComponent = parse_url($string);

/** @var resource $resource */
$resource = doFoo();
$stat = stat(__FILE__);
$lstat = lstat(__FILE__);
$fstat = fstat($resource);
$fileObject = new \SplFileObject(__FILE__);
$fileObjectStat = $fileObject->fstat();

$base64DecodeWithoutStrict = base64_decode('');
$base64DecodeWithStrictDisabled = base64_decode('', false);
$base64DecodeWithStrictEnabled = base64_decode('', true);
$base64DecodeDefault = base64_decode('', null);
$base64DecodeBenevolent = base64_decode('', $undefined);


//str_word_count
$strWordCountWithoutParameters = str_word_count();
$strWordCountWithTooManyParams = str_word_count('string', 0, 'string', 'extra');
$strWordCountStr = str_word_count('string');
$strWordCountStrType0 = str_word_count('string', 0);
$strWordCountStrType1 = str_word_count('string', 1);
$strWordCountStrType1Extra = str_word_count('string', 1, 'string');
$strWordCountStrType2 = str_word_count('string', 2);
$strWordCountStrType2Extra = str_word_count('string', 2, 'string');

/** @var int $integer */
$integer = doFoo();
$strWordCountStrTypeIndeterminant = str_word_count('string', $integer);

die;
