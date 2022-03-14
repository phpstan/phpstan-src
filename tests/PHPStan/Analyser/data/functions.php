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

$mbStrlenWithoutEncoding = mb_strlen('');
$mbStrlenWithValidEncoding = mb_strlen('', 'utf-8');
$mbStrlenWithValidEncodingAlias = mb_strlen('', 'utf8');
$mbStrlenWithInvalidEncoding = mb_strlen('', 'foo');
$mbStrlenWithValidAndInvalidEncoding = mb_strlen('', doFoo() ? 'utf-8' : 'foo');
$mbStrlenWithUnknownEncoding = mb_strlen('', doFoo());

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

// str_split
/** @var string $string */
$string = doFoo();
$strSplitConstantStringWithoutDefinedParameters = str_split();
$strSplitConstantStringWithoutDefinedSplitLength = str_split('abcdef');
$strSplitStringWithoutDefinedSplitLength = str_split($string);
$strSplitConstantStringWithOneSplitLength = str_split('abcdef', 1);
$strSplitConstantStringWithGreaterSplitLengthThanStringLength = str_split('abcdef', 999);
$strSplitConstantStringWithFailureSplitLength = str_split('abcdef', 0);
$strSplitConstantStringWithInvalidSplitLengthType = str_split('abcdef', []);
$strSplitConstantStringWithVariableStringAndConstantSplitLength = str_split(doFoo() ? 'abcdef' : 'ghijkl', 1);
$strSplitConstantStringWithVariableStringAndVariableSplitLength = str_split(doFoo() ? 'abcdef' : 'ghijkl', doFoo() ? 1 : 2);

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

// curl_getinfo
$curlGetinfoWithoutParam = curl_getinfo();
$handle = new CurlHandle();
$curlGetinfoWithOnlyHandle = curl_getinfo($handle);
$curlGetinfoWithEffectiveUrlOption = curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);
$curlGetinfoFiletimeWithOption = curl_getinfo($handle, CURLINFO_FILETIME);
$curlGetinfoWithTotalTimeOption = curl_getinfo($handle, CURLINFO_TOTAL_TIME);
$curlGetinfoWithNamelookupTimeOption = curl_getinfo($handle, CURLINFO_NAMELOOKUP_TIME);
$curlGetinfoWithConnectTimeOption = curl_getinfo($handle, CURLINFO_CONNECT_TIME);
$curlGetinfoWithPretransferTimeOption = curl_getinfo($handle, CURLINFO_PRETRANSFER_TIME);
$curlGetinfoWithStarttransferTimeOption = curl_getinfo($handle, CURLINFO_STARTTRANSFER_TIME);
$curlGetinfoWithRedirectCountOption = curl_getinfo($handle, CURLINFO_REDIRECT_COUNT);
$curlGetinfoWithRedirectTimeOption = curl_getinfo($handle, CURLINFO_REDIRECT_TIME);
$curlGetinfoWithRedirectUrlOption = curl_getinfo($handle, CURLINFO_REDIRECT_URL);
$curlGetinfoWithPrimaryIpOption = curl_getinfo($handle, CURLINFO_PRIMARY_IP);
$curlGetinfoWithPrimaryPortOption = curl_getinfo($handle, CURLINFO_PRIMARY_PORT);
$curlGetinfoWithLocalIpOption = curl_getinfo($handle, CURLINFO_LOCAL_IP);
$curlGetinfoWithLocalPortOption = curl_getinfo($handle, CURLINFO_LOCAL_PORT);
$curlGetinfoWithSizeUploadOption = curl_getinfo($handle, CURLINFO_SIZE_UPLOAD);
$curlGetinfoWithSizeDownloadOption = curl_getinfo($handle, CURLINFO_SIZE_DOWNLOAD);
$curlGetinfoWithSpeedDownloadOption = curl_getinfo($handle, CURLINFO_SPEED_DOWNLOAD);
$curlGetinfoWithSpeedUploadOption = curl_getinfo($handle, CURLINFO_SPEED_UPLOAD);
$curlGetinfoWithHeaderSizeOption = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
$curlGetinfoWithHeaderOutOption = curl_getinfo($handle, CURLINFO_HEADER_OUT);
$curlGetinfoWithRequestSizeOption = curl_getinfo($handle, CURLINFO_REQUEST_SIZE);
$curlGetinfoWithSSLVerifyresultOption = curl_getinfo($handle, CURLINFO_SSL_VERIFYRESULT);
$curlGetinfoWithContentLengthDownloadOption = curl_getinfo($handle, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
$curlGetinfoWithContentLengthUploadOption = curl_getinfo($handle, CURLINFO_CONTENT_LENGTH_UPLOAD);
$curlGetinfoWithContentTypeOption = curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
$curlGetinfoWithPrivateOption = curl_getinfo($handle, CURLINFO_PRIVATE);
$curlGetinfoWithResponseCodeOption = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
$curlGetinfoWithHTTPConnectcodeOption = curl_getinfo($handle, CURLINFO_HTTP_CONNECTCODE);
$curlGetinfoWithHTTPAuthAvailOption = curl_getinfo($handle, CURLINFO_HTTPAUTH_AVAIL);
$curlGetinfoWithProxyauthAvailOption = curl_getinfo($handle, CURLINFO_PROXYAUTH_AVAIL);
$curlGetinfoWithOSErrornoOption = curl_getinfo($handle, CURLINFO_OS_ERRNO);
$curlGetinfoWithNumConnectsOption = curl_getinfo($handle, CURLINFO_NUM_CONNECTS);
$curlGetinfoWithSSLEnginesOption = curl_getinfo($handle, CURLINFO_SSL_ENGINES);
$curlGetinfoWithCookielistOption = curl_getinfo($handle, CURLINFO_COOKIELIST);
$curlGetinfoWithFTPEntryPathOption = curl_getinfo($handle, CURLINFO_FTP_ENTRY_PATH);
$curlGetinfoWithAppconnectTimeOption = curl_getinfo($handle, CURLINFO_APPCONNECT_TIME);
$curlGetinfoWithCertinfoOption = curl_getinfo($handle, CURLINFO_CERTINFO);
$curlGetinfoWithConditionUnmetOption = curl_getinfo($handle, CURLINFO_CONDITION_UNMET);
$curlGetinfoWithRTSPClientCSEQOption = curl_getinfo($handle, CURLINFO_RTSP_CLIENT_CSEQ);
$curlGetinfoWithRTSPCSEQRECVOption = curl_getinfo($handle, CURLINFO_RTSP_CSEQ_RECV);
$curlGetinfoWithRTSPServerCSEQOption = curl_getinfo($handle, CURLINFO_RTSP_SERVER_CSEQ);
$curlGetinfoWithRTSQSessionIdOption = curl_getinfo($handle, CURLINFO_RTSP_SESSION_ID);
$curlGetinfoWithHTTPVersionOption = curl_getinfo($handle, CURLINFO_HTTP_VERSION);
$curlGetinfoWithProtocolOption = curl_getinfo($handle, CURLINFO_PROTOCOL);
$curlGetinfoWithProxySSLVerifyresultOption = curl_getinfo($handle, CURLINFO_PROXY_SSL_VERIFYRESULT);
$curlGetinfoWithSchemeOption = curl_getinfo($handle, CURLINFO_SCHEME);

die;
