<?php

namespace CurlGetinfo;

use CurlHandle;
use function PHPStan\Testing\assertType;

class Foo
{
	public function bar()
	{
		$handle = new CurlHandle();
		assertType('mixed', curl_getinfo());
		assertType('mixed', CURL_GETINFO());
		assertType('mixed', CuRl_GeTiNfO());
		assertType('false', curl_getinfo($handle, 'Invalid Argument'));
		assertType('(array{url: string, content_type: string|null, http_code: int, header_size: int, request_size: int, filetime: int, ssl_verify_result: int, redirect_count: int, total_time: float, namelookup_time: float, connect_time: float, pretransfer_time: float, size_upload: float, size_download: float, speed_download: float, speed_upload: float, download_content_length: float, upload_content_length: float, starttransfer_time: float, redirect_time: float, redirect_url: string, primary_ip: string, certinfo: array<int, array<string, string>>, primary_port: int, local_ip: string, local_port: int, http_version: int, protocol: int, ssl_verifyresult: int, scheme: string}|false)', curl_getinfo($handle, PHP_INT_MAX));
		assertType('false', curl_getinfo($handle, PHP_EOL));
		assertType('(array{url: string, content_type: string|null, http_code: int, header_size: int, request_size: int, filetime: int, ssl_verify_result: int, redirect_count: int, total_time: float, namelookup_time: float, connect_time: float, pretransfer_time: float, size_upload: float, size_download: float, speed_download: float, speed_upload: float, download_content_length: float, upload_content_length: float, starttransfer_time: float, redirect_time: float, redirect_url: string, primary_ip: string, certinfo: array<int, array<string, string>>, primary_port: int, local_ip: string, local_port: int, http_version: int, protocol: int, ssl_verifyresult: int, scheme: string}|false)', curl_getinfo($handle));
		assertType('(array{url: string, content_type: string|null, http_code: int, header_size: int, request_size: int, filetime: int, ssl_verify_result: int, redirect_count: int, total_time: float, namelookup_time: float, connect_time: float, pretransfer_time: float, size_upload: float, size_download: float, speed_download: float, speed_upload: float, download_content_length: float, upload_content_length: float, starttransfer_time: float, redirect_time: float, redirect_url: string, primary_ip: string, certinfo: array<int, array<string, string>>, primary_port: int, local_ip: string, local_port: int, http_version: int, protocol: int, ssl_verifyresult: int, scheme: string}|false)', curl_getinfo($handle, null));
		assertType('string', curl_getinfo($handle, CURLINFO_EFFECTIVE_URL));
		assertType('string', curl_getinfo($handle, 1048577)); // CURLINFO_EFFECTIVE_URL int value without using constant
		assertType('false', curl_getinfo($handle, 12345678)); // Non constant non CURLINFO_* int value
		assertType('int', curl_getinfo($handle, CURLINFO_FILETIME));
		assertType('float', curl_getinfo($handle, CURLINFO_TOTAL_TIME));
		assertType('float', curl_getinfo($handle, CURLINFO_NAMELOOKUP_TIME));
		assertType('float', curl_getinfo($handle, CURLINFO_CONNECT_TIME));
		assertType('float', curl_getinfo($handle, CURLINFO_PRETRANSFER_TIME));
		assertType('float', curl_getinfo($handle, CURLINFO_STARTTRANSFER_TIME));
		assertType('int', curl_getinfo($handle, CURLINFO_REDIRECT_COUNT));
		assertType('float', curl_getinfo($handle, CURLINFO_REDIRECT_TIME));
		assertType('string', curl_getinfo($handle, CURLINFO_REDIRECT_URL));
		assertType('string', curl_getinfo($handle, CURLINFO_PRIMARY_IP));
		assertType('int', curl_getinfo($handle, CURLINFO_PRIMARY_PORT));
		assertType('string', curl_getinfo($handle, CURLINFO_LOCAL_IP));
		assertType('int', curl_getinfo($handle, CURLINFO_LOCAL_PORT));
		assertType('int', curl_getinfo($handle, CURLINFO_SIZE_UPLOAD));
		assertType('int', curl_getinfo($handle, CURLINFO_SIZE_DOWNLOAD));
		assertType('int', curl_getinfo($handle, CURLINFO_SPEED_DOWNLOAD));
		assertType('int', curl_getinfo($handle, CURLINFO_SPEED_UPLOAD));
		assertType('int', curl_getinfo($handle, CURLINFO_HEADER_SIZE));
		assertType('string|false', curl_getinfo($handle, CURLINFO_HEADER_OUT));
		assertType('int', curl_getinfo($handle, CURLINFO_REQUEST_SIZE));
		assertType('int', curl_getinfo($handle, CURLINFO_SSL_VERIFYRESULT));
		assertType('float', curl_getinfo($handle, CURLINFO_CONTENT_LENGTH_DOWNLOAD));
		assertType('float', curl_getinfo($handle, CURLINFO_CONTENT_LENGTH_UPLOAD));
		assertType('string|false', curl_getinfo($handle, CURLINFO_CONTENT_TYPE));
		assertType('string|false', curl_getinfo($handle, CURLINFO_PRIVATE));
		assertType('int', curl_getinfo($handle, CURLINFO_RESPONSE_CODE));
		assertType('int', curl_getinfo($handle, CURLINFO_HTTP_CONNECTCODE));
		assertType('int', curl_getinfo($handle, CURLINFO_HTTPAUTH_AVAIL));
		assertType('int', curl_getinfo($handle, CURLINFO_PROXYAUTH_AVAIL));
		assertType('int', curl_getinfo($handle, CURLINFO_OS_ERRNO));
		assertType('int', curl_getinfo($handle, CURLINFO_NUM_CONNECTS));
		assertType('array<int, string>', curl_getinfo($handle, CURLINFO_SSL_ENGINES));
		assertType('array<int, string>', curl_getinfo($handle, CURLINFO_COOKIELIST));
		assertType('string|false', curl_getinfo($handle, CURLINFO_FTP_ENTRY_PATH));
		assertType('float', curl_getinfo($handle, CURLINFO_APPCONNECT_TIME));
		assertType('array<int, array<string, string>>', curl_getinfo($handle, CURLINFO_CERTINFO));
		assertType('int', curl_getinfo($handle, CURLINFO_CONDITION_UNMET));
		assertType('int', curl_getinfo($handle, CURLINFO_RTSP_CLIENT_CSEQ));
		assertType('int', curl_getinfo($handle, CURLINFO_RTSP_CSEQ_RECV));
		assertType('int', curl_getinfo($handle, CURLINFO_RTSP_SERVER_CSEQ));
		assertType('int', curl_getinfo($handle, CURLINFO_RTSP_SESSION_ID));
	}
}
