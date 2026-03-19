<?php declare(strict_types = 1);

/**
 * Mapping of PHP constants to the functions/methods and parameters where they belong.
 *
 * Top-level key = function name or 'Class::method', second-level key = parameter name.
 * Constants can be global ('JSON_THROW_ON_ERROR') or class-level ('PDO::FETCH_ASSOC').
 *
 * Each entry has:
 *   'type'       => 'single' | 'bitmask'
 *   'constants'  => list of constant names valid for this parameter
 *   'exclusiveGroups' => (optional, bitmask only) groups of constants that are mutually exclusive
 */
return [

	// ————————————————————————————————————————————
	// JSON
	// ————————————————————————————————————————————

	'json_encode' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'JSON_HEX_QUOT',
				'JSON_HEX_TAG',
				'JSON_HEX_AMP',
				'JSON_HEX_APOS',
				'JSON_NUMERIC_CHECK',
				'JSON_PRETTY_PRINT',
				'JSON_UNESCAPED_SLASHES',
				'JSON_FORCE_OBJECT',
				'JSON_PRESERVE_ZERO_FRACTION',
				'JSON_UNESCAPED_UNICODE',
				'JSON_PARTIAL_OUTPUT_ON_ERROR',
				'JSON_UNESCAPED_LINE_TERMINATORS',
				'JSON_THROW_ON_ERROR',
				'JSON_INVALID_UTF8_IGNORE',
				'JSON_INVALID_UTF8_SUBSTITUTE',
			],
		],
	],

	'json_decode' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'JSON_BIGINT_AS_STRING',
				'JSON_OBJECT_AS_ARRAY',
				'JSON_THROW_ON_ERROR',
				'JSON_INVALID_UTF8_IGNORE',
				'JSON_INVALID_UTF8_SUBSTITUTE',
			],
		],
	],

	'json_validate' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'JSON_INVALID_UTF8_IGNORE',
				'JSON_THROW_ON_ERROR',
			],
		],
	],

	// ————————————————————————————————————————————
	// PCRE
	// ————————————————————————————————————————————

	'preg_match' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'PREG_OFFSET_CAPTURE',
				'PREG_UNMATCHED_AS_NULL',
			],
		],
	],

	'preg_match_all' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'PREG_PATTERN_ORDER',
				'PREG_SET_ORDER',
				'PREG_OFFSET_CAPTURE',
				'PREG_UNMATCHED_AS_NULL',
			],
			'exclusiveGroups' => [
				['PREG_PATTERN_ORDER', 'PREG_SET_ORDER'],
			],
		],
	],

	'preg_split' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'PREG_SPLIT_NO_EMPTY',
				'PREG_SPLIT_DELIM_CAPTURE',
				'PREG_SPLIT_OFFSET_CAPTURE',
			],
		],
	],

	'preg_grep' => [
		'flags' => [
			'type' => 'single',
			'constants' => [
				'PREG_GREP_INVERT',
			],
		],
	],

	// ————————————————————————————————————————————
	// Sorting
	// ————————————————————————————————————————————

	'sort' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'SORT_REGULAR',
				'SORT_NUMERIC',
				'SORT_STRING',
				'SORT_LOCALE_STRING',
				'SORT_NATURAL',
				'SORT_FLAG_CASE',
			],
			'exclusiveGroups' => [
				['SORT_REGULAR', 'SORT_NUMERIC', 'SORT_STRING', 'SORT_LOCALE_STRING', 'SORT_NATURAL'],
			],
		],
	],

	'rsort' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'SORT_REGULAR',
				'SORT_NUMERIC',
				'SORT_STRING',
				'SORT_LOCALE_STRING',
				'SORT_NATURAL',
				'SORT_FLAG_CASE',
			],
			'exclusiveGroups' => [
				['SORT_REGULAR', 'SORT_NUMERIC', 'SORT_STRING', 'SORT_LOCALE_STRING', 'SORT_NATURAL'],
			],
		],
	],

	'asort' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'SORT_REGULAR',
				'SORT_NUMERIC',
				'SORT_STRING',
				'SORT_LOCALE_STRING',
				'SORT_NATURAL',
				'SORT_FLAG_CASE',
			],
			'exclusiveGroups' => [
				['SORT_REGULAR', 'SORT_NUMERIC', 'SORT_STRING', 'SORT_LOCALE_STRING', 'SORT_NATURAL'],
			],
		],
	],

	'arsort' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'SORT_REGULAR',
				'SORT_NUMERIC',
				'SORT_STRING',
				'SORT_LOCALE_STRING',
				'SORT_NATURAL',
				'SORT_FLAG_CASE',
			],
			'exclusiveGroups' => [
				['SORT_REGULAR', 'SORT_NUMERIC', 'SORT_STRING', 'SORT_LOCALE_STRING', 'SORT_NATURAL'],
			],
		],
	],

	'ksort' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'SORT_REGULAR',
				'SORT_NUMERIC',
				'SORT_STRING',
				'SORT_LOCALE_STRING',
				'SORT_NATURAL',
				'SORT_FLAG_CASE',
			],
			'exclusiveGroups' => [
				['SORT_REGULAR', 'SORT_NUMERIC', 'SORT_STRING', 'SORT_LOCALE_STRING', 'SORT_NATURAL'],
			],
		],
	],

	'krsort' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'SORT_REGULAR',
				'SORT_NUMERIC',
				'SORT_STRING',
				'SORT_LOCALE_STRING',
				'SORT_NATURAL',
				'SORT_FLAG_CASE',
			],
			'exclusiveGroups' => [
				['SORT_REGULAR', 'SORT_NUMERIC', 'SORT_STRING', 'SORT_LOCALE_STRING', 'SORT_NATURAL'],
			],
		],
	],

	'array_unique' => [
		'flags' => [
			'type' => 'single',
			'constants' => [
				'SORT_REGULAR',
				'SORT_NUMERIC',
				'SORT_STRING',
				'SORT_LOCALE_STRING',
			],
		],
	],

	// ————————————————————————————————————————————
	// Array functions
	// ————————————————————————————————————————————

	'array_change_key_case' => [
		'case' => [
			'type' => 'single',
			'constants' => [
				'CASE_LOWER',
				'CASE_UPPER',
			],
		],
	],

	'array_filter' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'ARRAY_FILTER_USE_KEY',
				'ARRAY_FILTER_USE_BOTH',
			],
		],
	],

	'count' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'COUNT_NORMAL',
				'COUNT_RECURSIVE',
			],
		],
	],

	'extract' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'EXTR_OVERWRITE',
				'EXTR_SKIP',
				'EXTR_PREFIX_SAME',
				'EXTR_PREFIX_ALL',
				'EXTR_PREFIX_INVALID',
				'EXTR_IF_EXISTS',
				'EXTR_PREFIX_IF_EXISTS',
				'EXTR_REFS',
			],
			'exclusiveGroups' => [
				['EXTR_OVERWRITE', 'EXTR_SKIP', 'EXTR_PREFIX_SAME', 'EXTR_PREFIX_ALL', 'EXTR_PREFIX_INVALID', 'EXTR_IF_EXISTS', 'EXTR_PREFIX_IF_EXISTS'],
			],
		],
	],

	// ————————————————————————————————————————————
	// HTML entities
	// ————————————————————————————————————————————

	'htmlspecialchars' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'ENT_COMPAT',
				'ENT_QUOTES',
				'ENT_NOQUOTES',
				'ENT_IGNORE',
				'ENT_SUBSTITUTE',
				'ENT_DISALLOWED',
				'ENT_HTML401',
				'ENT_XML1',
				'ENT_XHTML',
				'ENT_HTML5',
			],
			'exclusiveGroups' => [
				['ENT_COMPAT', 'ENT_QUOTES', 'ENT_NOQUOTES'],
				['ENT_HTML401', 'ENT_XML1', 'ENT_XHTML', 'ENT_HTML5'],
			],
		],
	],

	'htmlentities' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'ENT_COMPAT',
				'ENT_QUOTES',
				'ENT_NOQUOTES',
				'ENT_IGNORE',
				'ENT_SUBSTITUTE',
				'ENT_DISALLOWED',
				'ENT_HTML401',
				'ENT_XML1',
				'ENT_XHTML',
				'ENT_HTML5',
			],
			'exclusiveGroups' => [
				['ENT_COMPAT', 'ENT_QUOTES', 'ENT_NOQUOTES'],
				['ENT_HTML401', 'ENT_XML1', 'ENT_XHTML', 'ENT_HTML5'],
			],
		],
	],

	'html_entity_decode' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'ENT_COMPAT',
				'ENT_QUOTES',
				'ENT_NOQUOTES',
				'ENT_IGNORE',
				'ENT_SUBSTITUTE',
				'ENT_DISALLOWED',
				'ENT_HTML401',
				'ENT_XML1',
				'ENT_XHTML',
				'ENT_HTML5',
			],
			'exclusiveGroups' => [
				['ENT_COMPAT', 'ENT_QUOTES', 'ENT_NOQUOTES'],
				['ENT_HTML401', 'ENT_XML1', 'ENT_XHTML', 'ENT_HTML5'],
			],
		],
	],

	'htmlspecialchars_decode' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'ENT_COMPAT',
				'ENT_QUOTES',
				'ENT_NOQUOTES',
				'ENT_IGNORE',
				'ENT_SUBSTITUTE',
				'ENT_DISALLOWED',
				'ENT_HTML401',
				'ENT_XML1',
				'ENT_XHTML',
				'ENT_HTML5',
			],
			'exclusiveGroups' => [
				['ENT_COMPAT', 'ENT_QUOTES', 'ENT_NOQUOTES'],
				['ENT_HTML401', 'ENT_XML1', 'ENT_XHTML', 'ENT_HTML5'],
			],
		],
	],

	// ————————————————————————————————————————————
	// URL / Path
	// ————————————————————————————————————————————

	'parse_url' => [
		'component' => [
			'type' => 'single',
			'constants' => [
				'PHP_URL_SCHEME',
				'PHP_URL_HOST',
				'PHP_URL_PORT',
				'PHP_URL_USER',
				'PHP_URL_PASS',
				'PHP_URL_PATH',
				'PHP_URL_QUERY',
				'PHP_URL_FRAGMENT',
			],
		],
	],

	'pathinfo' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'PATHINFO_DIRNAME',
				'PATHINFO_BASENAME',
				'PATHINFO_EXTENSION',
				'PATHINFO_FILENAME',
				'PATHINFO_ALL',
			],
		],
	],

	'http_build_query' => [
		'encoding_type' => [
			'type' => 'single',
			'constants' => [
				'PHP_QUERY_RFC1738',
				'PHP_QUERY_RFC3986',
			],
		],
	],

	// ————————————————————————————————————————————
	// File operations
	// ————————————————————————————————————————————

	'file_put_contents' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'FILE_USE_INCLUDE_PATH',
				'FILE_APPEND',
				'LOCK_EX',
			],
		],
	],

	'file' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'FILE_USE_INCLUDE_PATH',
				'FILE_IGNORE_NEW_LINES',
				'FILE_SKIP_EMPTY_LINES',
				'FILE_NO_DEFAULT_CONTEXT',
			],
		],
	],

	'flock' => [
		'operation' => [
			'type' => 'bitmask',
			'constants' => [
				'LOCK_SH',
				'LOCK_EX',
				'LOCK_UN',
				'LOCK_NB',
			],
			'exclusiveGroups' => [
				['LOCK_SH', 'LOCK_EX', 'LOCK_UN'],
			],
		],
	],

	'glob' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'GLOB_MARK',
				'GLOB_NOSORT',
				'GLOB_NOCHECK',
				'GLOB_NOESCAPE',
				'GLOB_BRACE',
				'GLOB_ONLYDIR',
				'GLOB_ERR',
			],
		],
	],

	'fnmatch' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'FNM_NOESCAPE',
				'FNM_PATHNAME',
				'FNM_PERIOD',
				'FNM_CASEFOLD',
			],
		],
	],

	'scandir' => [
		'sorting_order' => [
			'type' => 'single',
			'constants' => [
				'SCANDIR_SORT_ASCENDING',
				'SCANDIR_SORT_DESCENDING',
				'SCANDIR_SORT_NONE',
			],
		],
	],

	// ————————————————————————————————————————————
	// Math
	// ————————————————————————————————————————————

	'round' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'PHP_ROUND_HALF_UP',
				'PHP_ROUND_HALF_DOWN',
				'PHP_ROUND_HALF_EVEN',
				'PHP_ROUND_HALF_ODD',
			],
		],
	],

	// ————————————————————————————————————————————
	// Random
	// ————————————————————————————————————————————

	'srand' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'MT_RAND_MT19937',
				'MT_RAND_PHP',
			],
		],
	],

	'mt_srand' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'MT_RAND_MT19937',
				'MT_RAND_PHP',
			],
		],
	],

	// ————————————————————————————————————————————
	// Filter
	// ————————————————————————————————————————————

	'filter_var' => [
		'filter' => [
			'type' => 'single',
			'constants' => [
				'FILTER_VALIDATE_INT',
				'FILTER_VALIDATE_BOOLEAN',
				'FILTER_VALIDATE_FLOAT',
				'FILTER_VALIDATE_REGEXP',
				'FILTER_VALIDATE_DOMAIN',
				'FILTER_VALIDATE_URL',
				'FILTER_VALIDATE_EMAIL',
				'FILTER_VALIDATE_IP',
				'FILTER_VALIDATE_MAC',
				'FILTER_SANITIZE_STRING',
				'FILTER_SANITIZE_STRIPPED',
				'FILTER_SANITIZE_ENCODED',
				'FILTER_SANITIZE_SPECIAL_CHARS',
				'FILTER_SANITIZE_FULL_SPECIAL_CHARS',
				'FILTER_SANITIZE_EMAIL',
				'FILTER_SANITIZE_URL',
				'FILTER_SANITIZE_NUMBER_INT',
				'FILTER_SANITIZE_NUMBER_FLOAT',
				'FILTER_SANITIZE_ADD_SLASHES',
				'FILTER_UNSAFE_RAW',
				'FILTER_DEFAULT',
				'FILTER_CALLBACK',
			],
		],
	],

	'filter_input' => [
		'type' => [
			'type' => 'single',
			'constants' => [
				'INPUT_POST',
				'INPUT_GET',
				'INPUT_COOKIE',
				'INPUT_ENV',
				'INPUT_SERVER',
			],
		],
		'filter' => [
			'type' => 'single',
			'constants' => [
				'FILTER_VALIDATE_INT',
				'FILTER_VALIDATE_BOOLEAN',
				'FILTER_VALIDATE_FLOAT',
				'FILTER_VALIDATE_REGEXP',
				'FILTER_VALIDATE_DOMAIN',
				'FILTER_VALIDATE_URL',
				'FILTER_VALIDATE_EMAIL',
				'FILTER_VALIDATE_IP',
				'FILTER_VALIDATE_MAC',
				'FILTER_SANITIZE_STRING',
				'FILTER_SANITIZE_STRIPPED',
				'FILTER_SANITIZE_ENCODED',
				'FILTER_SANITIZE_SPECIAL_CHARS',
				'FILTER_SANITIZE_FULL_SPECIAL_CHARS',
				'FILTER_SANITIZE_EMAIL',
				'FILTER_SANITIZE_URL',
				'FILTER_SANITIZE_NUMBER_INT',
				'FILTER_SANITIZE_NUMBER_FLOAT',
				'FILTER_SANITIZE_ADD_SLASHES',
				'FILTER_UNSAFE_RAW',
				'FILTER_DEFAULT',
				'FILTER_CALLBACK',
			],
		],
	],

	'filter_input_array' => [
		'type' => [
			'type' => 'single',
			'constants' => [
				'INPUT_POST',
				'INPUT_GET',
				'INPUT_COOKIE',
				'INPUT_ENV',
				'INPUT_SERVER',
			],
		],
	],

	// ————————————————————————————————————————————
	// Password hashing
	// ————————————————————————————————————————————

	'password_hash' => [
		'algo' => [
			'type' => 'single',
			'constants' => [
				'PASSWORD_DEFAULT',
				'PASSWORD_BCRYPT',
				'PASSWORD_ARGON2I',
				'PASSWORD_ARGON2ID',
			],
		],
	],

	'password_needs_rehash' => [
		'algo' => [
			'type' => 'single',
			'constants' => [
				'PASSWORD_DEFAULT',
				'PASSWORD_BCRYPT',
				'PASSWORD_ARGON2I',
				'PASSWORD_ARGON2ID',
			],
		],
	],

	// ————————————————————————————————————————————
	// Error handling
	// ————————————————————————————————————————————

	'error_reporting' => [
		'error_level' => [
			'type' => 'bitmask',
			'constants' => [
				'E_ALL',
				'E_ERROR',
				'E_WARNING',
				'E_PARSE',
				'E_NOTICE',
				'E_STRICT',
				'E_RECOVERABLE_ERROR',
				'E_DEPRECATED',
				'E_CORE_ERROR',
				'E_CORE_WARNING',
				'E_COMPILE_ERROR',
				'E_COMPILE_WARNING',
				'E_USER_ERROR',
				'E_USER_WARNING',
				'E_USER_NOTICE',
				'E_USER_DEPRECATED',
			],
		],
	],

	'trigger_error' => [
		'error_level' => [
			'type' => 'single',
			'constants' => [
				'E_USER_NOTICE',
				'E_USER_WARNING',
				'E_USER_ERROR',
				'E_USER_DEPRECATED',
			],
		],
	],

	'user_error' => [
		'error_level' => [
			'type' => 'single',
			'constants' => [
				'E_USER_NOTICE',
				'E_USER_WARNING',
				'E_USER_ERROR',
				'E_USER_DEPRECATED',
			],
		],
	],

	// ————————————————————————————————————————————
	// Multibyte string
	// ————————————————————————————————————————————

	'mb_convert_case' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'MB_CASE_UPPER',
				'MB_CASE_LOWER',
				'MB_CASE_TITLE',
				'MB_CASE_FOLD',
				'MB_CASE_UPPER_SIMPLE',
				'MB_CASE_LOWER_SIMPLE',
				'MB_CASE_TITLE_SIMPLE',
				'MB_CASE_FOLD_SIMPLE',
			],
		],
	],

	// ————————————————————————————————————————————
	// Fileinfo
	// ————————————————————————————————————————————

	'finfo_file' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'FILEINFO_NONE',
				'FILEINFO_SYMLINK',
				'FILEINFO_MIME',
				'FILEINFO_MIME_TYPE',
				'FILEINFO_MIME_ENCODING',
				'FILEINFO_DEVICES',
				'FILEINFO_CONTINUE',
				'FILEINFO_PRESERVE_ATIME',
				'FILEINFO_RAW',
				'FILEINFO_EXTENSION',
				'FILEINFO_APPLE',
			],
		],
	],

	// ————————————————————————————————————————————
	// Debug
	// ————————————————————————————————————————————

	'debug_backtrace' => [
		'options' => [
			'type' => 'bitmask',
			'constants' => [
				'DEBUG_BACKTRACE_PROVIDE_OBJECT',
				'DEBUG_BACKTRACE_IGNORE_ARGS',
			],
		],
	],

	'debug_print_backtrace' => [
		'options' => [
			'type' => 'single',
			'constants' => [
				'DEBUG_BACKTRACE_IGNORE_ARGS',
			],
		],
	],

	// ————————————————————————————————————————————
	// Tokenizer
	// ————————————————————————————————————————————

	'token_get_all' => [
		'flags' => [
			'type' => 'single',
			'constants' => [
				'TOKEN_PARSE',
			],
		],
	],

	// ————————————————————————————————————————————
	// cURL
	// ————————————————————————————————————————————

	'curl_setopt' => [
		'option' => [
			'type' => 'single',
			'constants' => [
				'CURLOPT_AUTOREFERER',
				'CURLOPT_BINARYTRANSFER',
				'CURLOPT_BUFFERSIZE',
				'CURLOPT_CAINFO',
				'CURLOPT_CAPATH',
				'CURLOPT_CERTINFO',
				'CURLOPT_CONNECTTIMEOUT',
				'CURLOPT_CONNECTTIMEOUT_MS',
				'CURLOPT_COOKIE',
				'CURLOPT_COOKIEFILE',
				'CURLOPT_COOKIEJAR',
				'CURLOPT_COOKIESESSION',
				'CURLOPT_CRLF',
				'CURLOPT_CUSTOMREQUEST',
				'CURLOPT_DEFAULT_PROTOCOL',
				'CURLOPT_DNS_CACHE_TIMEOUT',
				'CURLOPT_DNS_INTERFACE',
				'CURLOPT_DNS_LOCAL_IP4',
				'CURLOPT_DNS_LOCAL_IP6',
				'CURLOPT_DNS_USE_GLOBAL_CACHE',
				'CURLOPT_EGDSOCKET',
				'CURLOPT_ENCODING',
				'CURLOPT_EXPECT_100_TIMEOUT_MS',
				'CURLOPT_FAILONERROR',
				'CURLOPT_FILE',
				'CURLOPT_FILETIME',
				'CURLOPT_FOLLOWLOCATION',
				'CURLOPT_FORBID_REUSE',
				'CURLOPT_FRESH_CONNECT',
				'CURLOPT_FTPAPPEND',
				'CURLOPT_FTPLISTONLY',
				'CURLOPT_FTPPORT',
				'CURLOPT_FTPSSLAUTH',
				'CURLOPT_FTP_ACCOUNT',
				'CURLOPT_FTP_ALTERNATIVE_TO_USER',
				'CURLOPT_FTP_CREATE_MISSING_DIRS',
				'CURLOPT_FTP_FILEMETHOD',
				'CURLOPT_FTP_RESPONSE_TIMEOUT',
				'CURLOPT_FTP_SKIP_PASV_IP',
				'CURLOPT_FTP_SSL',
				'CURLOPT_FTP_SSL_CCC',
				'CURLOPT_FTP_USE_EPRT',
				'CURLOPT_FTP_USE_EPSV',
				'CURLOPT_FTP_USE_PRET',
				'CURLOPT_HEADER',
				'CURLOPT_HEADERFUNCTION',
				'CURLOPT_HEADEROPT',
				'CURLOPT_HTTP200ALIASES',
				'CURLOPT_HTTPAUTH',
				'CURLOPT_HTTPGET',
				'CURLOPT_HTTPHEADER',
				'CURLOPT_HTTPPROXYTUNNEL',
				'CURLOPT_HTTP_CONTENT_DECODING',
				'CURLOPT_HTTP_TRANSFER_DECODING',
				'CURLOPT_HTTP_VERSION',
				'CURLOPT_INFILE',
				'CURLOPT_INFILESIZE',
				'CURLOPT_INTERFACE',
				'CURLOPT_IPRESOLVE',
				'CURLOPT_KEYPASSWD',
				'CURLOPT_KRB4LEVEL',
				'CURLOPT_LOGIN_OPTIONS',
				'CURLOPT_LOW_SPEED_LIMIT',
				'CURLOPT_LOW_SPEED_TIME',
				'CURLOPT_MAIL_AUTH',
				'CURLOPT_MAIL_FROM',
				'CURLOPT_MAIL_RCPT',
				'CURLOPT_MAXCONNECTS',
				'CURLOPT_MAXFILESIZE',
				'CURLOPT_MAXREDIRS',
				'CURLOPT_MAX_RECV_SPEED_LARGE',
				'CURLOPT_MAX_SEND_SPEED_LARGE',
				'CURLOPT_NETRC',
				'CURLOPT_NETRC_FILE',
				'CURLOPT_NOBODY',
				'CURLOPT_NOPROGRESS',
				'CURLOPT_NOSIGNAL',
				'CURLOPT_PASSWORD',
				'CURLOPT_PATH_AS_IS',
				'CURLOPT_PINNEDPUBLICKEY',
				'CURLOPT_PIPEWAIT',
				'CURLOPT_PORT',
				'CURLOPT_POST',
				'CURLOPT_POSTFIELDS',
				'CURLOPT_POSTQUOTE',
				'CURLOPT_POSTREDIR',
				'CURLOPT_PREQUOTE',
				'CURLOPT_PRIVATE',
				'CURLOPT_PROGRESSFUNCTION',
				'CURLOPT_PROTOCOLS',
				'CURLOPT_PROXY',
				'CURLOPT_PROXYAUTH',
				'CURLOPT_PROXYHEADER',
				'CURLOPT_PROXYPASSWORD',
				'CURLOPT_PROXYPORT',
				'CURLOPT_PROXYTYPE',
				'CURLOPT_PROXYUSERNAME',
				'CURLOPT_PROXYUSERPWD',
				'CURLOPT_PROXY_SERVICE_NAME',
				'CURLOPT_PROXY_TRANSFER_MODE',
				'CURLOPT_PUT',
				'CURLOPT_QUOTE',
				'CURLOPT_RANDOM_FILE',
				'CURLOPT_RANGE',
				'CURLOPT_READDATA',
				'CURLOPT_READFUNCTION',
				'CURLOPT_REDIR_PROTOCOLS',
				'CURLOPT_REFERER',
				'CURLOPT_RESOLVE',
				'CURLOPT_RESUME_FROM',
				'CURLOPT_RETURNTRANSFER',
				'CURLOPT_SASL_IR',
				'CURLOPT_SERVICE_NAME',
				'CURLOPT_SHARE',
				'CURLOPT_SOCKS5_GSSAPI_NEC',
				'CURLOPT_SOCKS5_GSSAPI_SERVICE',
				'CURLOPT_SSH_AUTH_TYPES',
				'CURLOPT_SSH_HOST_PUBLIC_KEY_MD5',
				'CURLOPT_SSH_KNOWNHOSTS',
				'CURLOPT_SSH_PRIVATE_KEYFILE',
				'CURLOPT_SSH_PUBLIC_KEYFILE',
				'CURLOPT_SSLCERT',
				'CURLOPT_SSLCERTPASSWD',
				'CURLOPT_SSLCERTTYPE',
				'CURLOPT_SSLENGINE',
				'CURLOPT_SSLENGINE_DEFAULT',
				'CURLOPT_SSLKEY',
				'CURLOPT_SSLKEYPASSWD',
				'CURLOPT_SSLKEYTYPE',
				'CURLOPT_SSLVERSION',
				'CURLOPT_SSL_CIPHER_LIST',
				'CURLOPT_SSL_ENABLE_ALPN',
				'CURLOPT_SSL_ENABLE_NPN',
				'CURLOPT_SSL_FALSESTART',
				'CURLOPT_SSL_OPTIONS',
				'CURLOPT_SSL_VERIFYHOST',
				'CURLOPT_SSL_VERIFYPEER',
				'CURLOPT_SSL_VERIFYSTATUS',
				'CURLOPT_STDERR',
				'CURLOPT_STREAM_WEIGHT',
				'CURLOPT_TCP_FASTOPEN',
				'CURLOPT_TCP_KEEPALIVE',
				'CURLOPT_TCP_KEEPIDLE',
				'CURLOPT_TCP_KEEPINTVL',
				'CURLOPT_TCP_NODELAY',
				'CURLOPT_TFTP_NO_OPTIONS',
				'CURLOPT_TIMECONDITION',
				'CURLOPT_TIMEOUT',
				'CURLOPT_TIMEOUT_MS',
				'CURLOPT_TIMEVALUE',
				'CURLOPT_TRANSFERTEXT',
				'CURLOPT_UNIX_SOCKET_PATH',
				'CURLOPT_UNRESTRICTED_AUTH',
				'CURLOPT_UPLOAD',
				'CURLOPT_URL',
				'CURLOPT_USERAGENT',
				'CURLOPT_USERNAME',
				'CURLOPT_USERPWD',
				'CURLOPT_VERBOSE',
				'CURLOPT_WRITEFUNCTION',
				'CURLOPT_WRITEHEADER',
				'CURLOPT_XOAUTH2_BEARER',
			],
		],
	],

	'curl_getinfo' => [
		'option' => [
			'type' => 'single',
			'constants' => [
				'CURLINFO_APPCONNECT_TIME',
				'CURLINFO_APPCONNECT_TIME_T',
				'CURLINFO_CERTINFO',
				'CURLINFO_CONDITION_UNMET',
				'CURLINFO_CONNECT_TIME',
				'CURLINFO_CONNECT_TIME_T',
				'CURLINFO_CONTENT_LENGTH_DOWNLOAD',
				'CURLINFO_CONTENT_LENGTH_DOWNLOAD_T',
				'CURLINFO_CONTENT_LENGTH_UPLOAD',
				'CURLINFO_CONTENT_LENGTH_UPLOAD_T',
				'CURLINFO_CONTENT_TYPE',
				'CURLINFO_EFFECTIVE_URL',
				'CURLINFO_FILETIME',
				'CURLINFO_FILETIME_T',
				'CURLINFO_HEADER_OUT',
				'CURLINFO_HEADER_SIZE',
				'CURLINFO_HTTPAUTH_AVAIL',
				'CURLINFO_HTTP_CODE',
				'CURLINFO_HTTP_CONNECTCODE',
				'CURLINFO_HTTP_VERSION',
				'CURLINFO_LOCAL_IP',
				'CURLINFO_LOCAL_PORT',
				'CURLINFO_NAMELOOKUP_TIME',
				'CURLINFO_NAMELOOKUP_TIME_T',
				'CURLINFO_NUM_CONNECTS',
				'CURLINFO_OS_ERRNO',
				'CURLINFO_PRETRANSFER_TIME',
				'CURLINFO_PRETRANSFER_TIME_T',
				'CURLINFO_PRIMARY_IP',
				'CURLINFO_PRIMARY_PORT',
				'CURLINFO_PRIVATE',
				'CURLINFO_PROTOCOL',
				'CURLINFO_PROXYAUTH_AVAIL',
				'CURLINFO_REDIRECT_COUNT',
				'CURLINFO_REDIRECT_TIME',
				'CURLINFO_REDIRECT_TIME_T',
				'CURLINFO_REDIRECT_URL',
				'CURLINFO_REQUEST_SIZE',
				'CURLINFO_RESPONSE_CODE',
				'CURLINFO_SCHEME',
				'CURLINFO_SIZE_DOWNLOAD',
				'CURLINFO_SIZE_DOWNLOAD_T',
				'CURLINFO_SIZE_UPLOAD',
				'CURLINFO_SIZE_UPLOAD_T',
				'CURLINFO_SPEED_DOWNLOAD',
				'CURLINFO_SPEED_DOWNLOAD_T',
				'CURLINFO_SPEED_UPLOAD',
				'CURLINFO_SPEED_UPLOAD_T',
				'CURLINFO_SSL_ENGINES',
				'CURLINFO_SSL_VERIFYRESULT',
				'CURLINFO_STARTTRANSFER_TIME',
				'CURLINFO_STARTTRANSFER_TIME_T',
				'CURLINFO_TOTAL_TIME',
				'CURLINFO_TOTAL_TIME_T',
			],
		],
	],

	'curl_pause' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'CURLPAUSE_ALL',
				'CURLPAUSE_CONT',
				'CURLPAUSE_RECV',
				'CURLPAUSE_RECV_CONT',
				'CURLPAUSE_SEND',
				'CURLPAUSE_SEND_CONT',
			],
		],
	],

	'curl_multi_setopt' => [
		'option' => [
			'type' => 'single',
			'constants' => [
				'CURLMOPT_PIPELINING',
				'CURLMOPT_MAXCONNECTS',
				'CURLMOPT_CHUNK_LENGTH_PENALTY_SIZE',
				'CURLMOPT_CONTENT_LENGTH_PENALTY_SIZE',
				'CURLMOPT_MAX_HOST_CONNECTIONS',
				'CURLMOPT_MAX_PIPELINE_LENGTH',
				'CURLMOPT_MAX_TOTAL_CONNECTIONS',
				'CURLMOPT_PUSHFUNCTION',
			],
		],
	],

	'curl_share_setopt' => [
		'option' => [
			'type' => 'single',
			'constants' => [
				'CURLSHOPT_NONE',
				'CURLSHOPT_SHARE',
				'CURLSHOPT_UNSHARE',
			],
		],
	],

	// ————————————————————————————————————————————
	// Image type
	// ————————————————————————————————————————————

	'image_type_to_extension' => [
		'image_type' => [
			'type' => 'single',
			'constants' => [
				'IMAGETYPE_GIF',
				'IMAGETYPE_JPEG',
				'IMAGETYPE_PNG',
				'IMAGETYPE_SWF',
				'IMAGETYPE_PSD',
				'IMAGETYPE_BMP',
				'IMAGETYPE_WBMP',
				'IMAGETYPE_XBM',
				'IMAGETYPE_TIFF_II',
				'IMAGETYPE_TIFF_MM',
				'IMAGETYPE_ICO',
				'IMAGETYPE_WEBP',
				'IMAGETYPE_AVIF',
				'IMAGETYPE_JPC',
				'IMAGETYPE_JP2',
				'IMAGETYPE_JPX',
				'IMAGETYPE_JB2',
				'IMAGETYPE_SWC',
				'IMAGETYPE_IFF',
			],
		],
	],

	'image_type_to_mime_type' => [
		'image_type' => [
			'type' => 'single',
			'constants' => [
				'IMAGETYPE_GIF',
				'IMAGETYPE_JPEG',
				'IMAGETYPE_PNG',
				'IMAGETYPE_SWF',
				'IMAGETYPE_PSD',
				'IMAGETYPE_BMP',
				'IMAGETYPE_WBMP',
				'IMAGETYPE_XBM',
				'IMAGETYPE_TIFF_II',
				'IMAGETYPE_TIFF_MM',
				'IMAGETYPE_ICO',
				'IMAGETYPE_WEBP',
				'IMAGETYPE_AVIF',
				'IMAGETYPE_JPC',
				'IMAGETYPE_JP2',
				'IMAGETYPE_JPX',
				'IMAGETYPE_JB2',
				'IMAGETYPE_SWC',
				'IMAGETYPE_IFF',
			],
		],
	],

	// ————————————————————————————————————————————
	// GD image functions
	// ————————————————————————————————————————————

	'imagecropauto' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'IMG_CROP_DEFAULT',
				'IMG_CROP_TRANSPARENT',
				'IMG_CROP_BLACK',
				'IMG_CROP_WHITE',
				'IMG_CROP_SIDES',
				'IMG_CROP_THRESHOLD',
			],
		],
	],

	'imagelayereffect' => [
		'effect' => [
			'type' => 'single',
			'constants' => [
				'IMG_EFFECT_REPLACE',
				'IMG_EFFECT_ALPHABLEND',
				'IMG_EFFECT_NORMAL',
				'IMG_EFFECT_OVERLAY',
				'IMG_EFFECT_MULTIPLY',
			],
		],
	],

	'imageflip' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'IMG_FLIP_HORIZONTAL',
				'IMG_FLIP_VERTICAL',
				'IMG_FLIP_BOTH',
			],
		],
	],

	'imagefilter' => [
		'filter' => [
			'type' => 'single',
			'constants' => [
				'IMG_FILTER_NEGATE',
				'IMG_FILTER_GRAYSCALE',
				'IMG_FILTER_BRIGHTNESS',
				'IMG_FILTER_CONTRAST',
				'IMG_FILTER_COLORIZE',
				'IMG_FILTER_EDGEDETECT',
				'IMG_FILTER_GAUSSIAN_BLUR',
				'IMG_FILTER_SELECTIVE_BLUR',
				'IMG_FILTER_EMBOSS',
				'IMG_FILTER_MEAN_REMOVAL',
				'IMG_FILTER_SMOOTH',
				'IMG_FILTER_PIXELATE',
				'IMG_FILTER_SCATTER',
			],
		],
	],

	// ————————————————————————————————————————————
	// Iconv
	// ————————————————————————————————————————————

	'iconv_mime_decode' => [
		'mode' => [
			'type' => 'bitmask',
			'constants' => [
				'ICONV_MIME_DECODE_STRICT',
				'ICONV_MIME_DECODE_CONTINUE_ON_ERROR',
			],
		],
	],

	'iconv_mime_decode_headers' => [
		'mode' => [
			'type' => 'bitmask',
			'constants' => [
				'ICONV_MIME_DECODE_STRICT',
				'ICONV_MIME_DECODE_CONTINUE_ON_ERROR',
			],
		],
	],

	// ————————————————————————————————————————————
	// Output buffering
	// ————————————————————————————————————————————

	'ob_start' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'PHP_OUTPUT_HANDLER_CLEANABLE',
				'PHP_OUTPUT_HANDLER_FLUSHABLE',
				'PHP_OUTPUT_HANDLER_REMOVABLE',
				'PHP_OUTPUT_HANDLER_STDFLAGS',
			],
		],
	],

	// ————————————————————————————————————————————
	// Streams
	// ————————————————————————————————————————————

	'stream_socket_client' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'STREAM_CLIENT_CONNECT',
				'STREAM_CLIENT_ASYNC_CONNECT',
				'STREAM_CLIENT_PERSISTENT',
			],
		],
	],

	'stream_socket_server' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'STREAM_SERVER_BIND',
				'STREAM_SERVER_LISTEN',
			],
		],
	],

	'stream_socket_recvfrom' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'STREAM_OOB',
				'STREAM_PEEK',
			],
		],
	],

	'stream_socket_sendto' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'STREAM_OOB',
			],
		],
	],

	'stream_wrapper_register' => [
		'flags' => [
			'type' => 'single',
			'constants' => [
				'STREAM_IS_URL',
			],
		],
	],

	'stream_socket_shutdown' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'STREAM_SHUT_RD',
				'STREAM_SHUT_WR',
				'STREAM_SHUT_RDWR',
			],
		],
	],

	// ————————————————————————————————————————————
	// Syslog
	// ————————————————————————————————————————————

	'openlog' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'LOG_CONS',
				'LOG_NDELAY',
				'LOG_ODELAY',
				'LOG_PERROR',
				'LOG_PID',
			],
		],
		'facility' => [
			'type' => 'single',
			'constants' => [
				'LOG_AUTH',
				'LOG_AUTHPRIV',
				'LOG_CRON',
				'LOG_DAEMON',
				'LOG_KERN',
				'LOG_LOCAL0',
				'LOG_LOCAL1',
				'LOG_LOCAL2',
				'LOG_LOCAL3',
				'LOG_LOCAL4',
				'LOG_LOCAL5',
				'LOG_LOCAL6',
				'LOG_LOCAL7',
				'LOG_LPR',
				'LOG_MAIL',
				'LOG_NEWS',
				'LOG_SYSLOG',
				'LOG_USER',
				'LOG_UUCP',
			],
		],
	],

	'syslog' => [
		'priority' => [
			'type' => 'single',
			'constants' => [
				'LOG_EMERG',
				'LOG_ALERT',
				'LOG_CRIT',
				'LOG_ERR',
				'LOG_WARNING',
				'LOG_NOTICE',
				'LOG_INFO',
				'LOG_DEBUG',
			],
		],
	],

	// ————————————————————————————————————————————
	// Sockets
	// ————————————————————————————————————————————

	'socket_recv' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'MSG_OOB',
				'MSG_PEEK',
				'MSG_WAITALL',
				'MSG_DONTWAIT',
			],
		],
	],

	'socket_recvfrom' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'MSG_OOB',
				'MSG_PEEK',
				'MSG_WAITALL',
				'MSG_DONTWAIT',
			],
		],
	],

	'socket_send' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'MSG_OOB',
				'MSG_EOR',
				'MSG_EOF',
				'MSG_DONTROUTE',
			],
		],
	],

	'socket_sendto' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'MSG_OOB',
				'MSG_EOR',
				'MSG_EOF',
				'MSG_DONTROUTE',
			],
		],
	],

	// ————————————————————————————————————————————
	// DNS
	// ————————————————————————————————————————————

	'dns_get_record' => [
		'type' => [
			'type' => 'bitmask',
			'constants' => [
				'DNS_ANY',
				'DNS_ALL',
				'DNS_A',
				'DNS_AAAA',
				'DNS_CNAME',
				'DNS_HINFO',
				'DNS_MX',
				'DNS_NS',
				'DNS_PTR',
				'DNS_SOA',
				'DNS_SRV',
				'DNS_TXT',
				'DNS_NAPTR',
				'DNS_A6',
				'DNS_CAA',
			],
		],
	],

	// ————————————————————————————————————————————
	// FTP
	// ————————————————————————————————————————————

	'ftp_get' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'FTP_ASCII',
				'FTP_BINARY',
			],
		],
	],

	'ftp_fget' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'FTP_ASCII',
				'FTP_BINARY',
			],
		],
	],

	'ftp_put' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'FTP_ASCII',
				'FTP_BINARY',
			],
		],
	],

	'ftp_fput' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'FTP_ASCII',
				'FTP_BINARY',
			],
		],
	],

	// ————————————————————————————————————————————
	// IMAP
	// ————————————————————————————————————————————

	'imap_close' => [
		'flags' => [
			'type' => 'single',
			'constants' => [
				'CL_EXPUNGE',
			],
		],
	],

	// ————————————————————————————————————————————
	// OpenSSL
	// ————————————————————————————————————————————

	'openssl_pkcs7_verify' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'PKCS7_TEXT',
				'PKCS7_BINARY',
				'PKCS7_NOINTERN',
				'PKCS7_NOVERIFY',
				'PKCS7_NOCHAIN',
				'PKCS7_NOCERTS',
				'PKCS7_NOATTR',
				'PKCS7_DETACHED',
				'PKCS7_NOSIGS',
			],
		],
	],

	'openssl_pkcs7_sign' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'PKCS7_TEXT',
				'PKCS7_BINARY',
				'PKCS7_NOINTERN',
				'PKCS7_NOVERIFY',
				'PKCS7_NOCHAIN',
				'PKCS7_NOCERTS',
				'PKCS7_NOATTR',
				'PKCS7_DETACHED',
				'PKCS7_NOSIGS',
			],
		],
	],

	'openssl_pkcs7_encrypt' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'PKCS7_TEXT',
				'PKCS7_BINARY',
				'PKCS7_NOINTERN',
				'PKCS7_NOVERIFY',
				'PKCS7_NOCHAIN',
				'PKCS7_NOCERTS',
				'PKCS7_NOATTR',
				'PKCS7_DETACHED',
				'PKCS7_NOSIGS',
			],
		],
		'cipher_algo' => [
			'type' => 'single',
			'constants' => [
				'OPENSSL_CIPHER_RC2_40',
				'OPENSSL_CIPHER_RC2_128',
				'OPENSSL_CIPHER_RC2_64',
				'OPENSSL_CIPHER_DES',
				'OPENSSL_CIPHER_3DES',
				'OPENSSL_CIPHER_AES_128_CBC',
				'OPENSSL_CIPHER_AES_192_CBC',
				'OPENSSL_CIPHER_AES_256_CBC',
			],
		],
	],

	// ————————————————————————————————————————————
	// IDN
	// ————————————————————————————————————————————

	'idn_to_ascii' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'IDNA_DEFAULT',
				'IDNA_ALLOW_UNASSIGNED',
				'IDNA_CHECK_BIDI',
				'IDNA_CHECK_CONTEXTJ',
				'IDNA_NONTRANSITIONAL_TO_ASCII',
				'IDNA_NONTRANSITIONAL_TO_UNICODE',
				'IDNA_USE_STD3_RULES',
			],
		],
		'variant' => [
			'type' => 'single',
			'constants' => [
				'INTL_IDNA_VARIANT_UTS46',
				'INTL_IDNA_VARIANT_2003',
			],
		],
	],

	'idn_to_utf8' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'IDNA_DEFAULT',
				'IDNA_ALLOW_UNASSIGNED',
				'IDNA_CHECK_BIDI',
				'IDNA_CHECK_CONTEXTJ',
				'IDNA_NONTRANSITIONAL_TO_ASCII',
				'IDNA_NONTRANSITIONAL_TO_UNICODE',
				'IDNA_USE_STD3_RULES',
			],
		],
		'variant' => [
			'type' => 'single',
			'constants' => [
				'INTL_IDNA_VARIANT_UTS46',
				'INTL_IDNA_VARIANT_2003',
			],
		],
	],

	// ————————————————————————————————————————————
	// String functions
	// ————————————————————————————————————————————

	'str_pad' => [
		'pad_type' => [
			'type' => 'single',
			'constants' => [
				'STR_PAD_RIGHT',
				'STR_PAD_LEFT',
				'STR_PAD_BOTH',
			],
		],
	],

	// ————————————————————————————————————————————
	// File seeking
	// ————————————————————————————————————————————

	'fseek' => [
		'whence' => [
			'type' => 'single',
			'constants' => [
				'SEEK_SET',
				'SEEK_CUR',
				'SEEK_END',
			],
		],
	],

	// ————————————————————————————————————————————
	// INI parsing
	// ————————————————————————————————————————————

	'parse_ini_file' => [
		'scanner_mode' => [
			'type' => 'single',
			'constants' => [
				'INI_SCANNER_NORMAL',
				'INI_SCANNER_RAW',
				'INI_SCANNER_TYPED',
			],
		],
	],

	'parse_ini_string' => [
		'scanner_mode' => [
			'type' => 'single',
			'constants' => [
				'INI_SCANNER_NORMAL',
				'INI_SCANNER_RAW',
				'INI_SCANNER_TYPED',
			],
		],
	],

	// ————————————————————————————————————————————
	// Message queues
	// ————————————————————————————————————————————

	'msg_receive' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'MSG_IPC_NOWAIT',
				'MSG_EXCEPT',
				'MSG_NOERROR',
			],
		],
	],

	// ————————————————————————————————————————————
	// Locale
	// ————————————————————————————————————————————

	'setlocale' => [
		'category' => [
			'type' => 'single',
			'constants' => [
				'LC_CTYPE',
				'LC_NUMERIC',
				'LC_TIME',
				'LC_COLLATE',
				'LC_MONETARY',
				'LC_MESSAGES',
				'LC_ALL',
			],
		],
	],

	// ————————————————————————————————————————————
	// libxml (functions)
	// ————————————————————————————————————————————

	'simplexml_load_file' => [
		'options' => [
			'type' => 'bitmask',
			'constants' => [
				'LIBXML_NOENT',
				'LIBXML_DTDLOAD',
				'LIBXML_DTDATTR',
				'LIBXML_DTDVALID',
				'LIBXML_NOERROR',
				'LIBXML_NOWARNING',
				'LIBXML_NOBLANKS',
				'LIBXML_XINCLUDE',
				'LIBXML_NSCLEAN',
				'LIBXML_NOCDATA',
				'LIBXML_NONET',
				'LIBXML_PEDANTIC',
				'LIBXML_COMPACT',
				'LIBXML_PARSEHUGE',
				'LIBXML_BIGLINES',
			],
		],
	],

	'simplexml_load_string' => [
		'options' => [
			'type' => 'bitmask',
			'constants' => [
				'LIBXML_NOENT',
				'LIBXML_DTDLOAD',
				'LIBXML_DTDATTR',
				'LIBXML_DTDVALID',
				'LIBXML_NOERROR',
				'LIBXML_NOWARNING',
				'LIBXML_NOBLANKS',
				'LIBXML_XINCLUDE',
				'LIBXML_NSCLEAN',
				'LIBXML_NOCDATA',
				'LIBXML_NONET',
				'LIBXML_PEDANTIC',
				'LIBXML_COMPACT',
				'LIBXML_PARSEHUGE',
				'LIBXML_BIGLINES',
			],
		],
	],

	// ————————————————————————————————————————————
	// mysqli (functions)
	// ————————————————————————————————————————————

	'mysqli_begin_transaction' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'MYSQLI_TRANS_START_READ_ONLY',
				'MYSQLI_TRANS_START_READ_WRITE',
				'MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT',
			],
			'exclusiveGroups' => [
				['MYSQLI_TRANS_START_READ_ONLY', 'MYSQLI_TRANS_START_READ_WRITE'],
			],
		],
	],

	'mysqli_commit' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'MYSQLI_TRANS_COR_AND_CHAIN',
				'MYSQLI_TRANS_COR_AND_NO_CHAIN',
				'MYSQLI_TRANS_COR_RELEASE',
				'MYSQLI_TRANS_COR_NO_RELEASE',
			],
			'exclusiveGroups' => [
				['MYSQLI_TRANS_COR_AND_CHAIN', 'MYSQLI_TRANS_COR_AND_NO_CHAIN'],
				['MYSQLI_TRANS_COR_RELEASE', 'MYSQLI_TRANS_COR_NO_RELEASE'],
			],
		],
	],

	'mysqli_rollback' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'MYSQLI_TRANS_COR_AND_CHAIN',
				'MYSQLI_TRANS_COR_AND_NO_CHAIN',
				'MYSQLI_TRANS_COR_RELEASE',
				'MYSQLI_TRANS_COR_NO_RELEASE',
			],
			'exclusiveGroups' => [
				['MYSQLI_TRANS_COR_AND_CHAIN', 'MYSQLI_TRANS_COR_AND_NO_CHAIN'],
				['MYSQLI_TRANS_COR_RELEASE', 'MYSQLI_TRANS_COR_NO_RELEASE'],
			],
		],
	],

	// ————————————————————————————————————————————
	// Methods with global constants
	// ————————————————————————————————————————————

	// finfo methods (FILEINFO_* global constants)

	'finfo::__construct' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'FILEINFO_NONE',
				'FILEINFO_SYMLINK',
				'FILEINFO_MIME',
				'FILEINFO_MIME_TYPE',
				'FILEINFO_MIME_ENCODING',
				'FILEINFO_DEVICES',
				'FILEINFO_CONTINUE',
				'FILEINFO_PRESERVE_ATIME',
				'FILEINFO_RAW',
				'FILEINFO_EXTENSION',
				'FILEINFO_APPLE',
			],
		],
	],

	'finfo::file' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'FILEINFO_NONE',
				'FILEINFO_SYMLINK',
				'FILEINFO_MIME',
				'FILEINFO_MIME_TYPE',
				'FILEINFO_MIME_ENCODING',
				'FILEINFO_DEVICES',
				'FILEINFO_CONTINUE',
				'FILEINFO_PRESERVE_ATIME',
				'FILEINFO_RAW',
				'FILEINFO_EXTENSION',
				'FILEINFO_APPLE',
			],
		],
	],

	'finfo::buffer' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'FILEINFO_NONE',
				'FILEINFO_SYMLINK',
				'FILEINFO_MIME',
				'FILEINFO_MIME_TYPE',
				'FILEINFO_MIME_ENCODING',
				'FILEINFO_DEVICES',
				'FILEINFO_CONTINUE',
				'FILEINFO_PRESERVE_ATIME',
				'FILEINFO_RAW',
				'FILEINFO_EXTENSION',
				'FILEINFO_APPLE',
			],
		],
	],

	'finfo::set_flags' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'FILEINFO_NONE',
				'FILEINFO_SYMLINK',
				'FILEINFO_MIME',
				'FILEINFO_MIME_TYPE',
				'FILEINFO_MIME_ENCODING',
				'FILEINFO_DEVICES',
				'FILEINFO_CONTINUE',
				'FILEINFO_PRESERVE_ATIME',
				'FILEINFO_RAW',
				'FILEINFO_EXTENSION',
				'FILEINFO_APPLE',
			],
		],
	],

	// SplFileObject methods (global constants)

	'SplFileObject::flock' => [
		'operation' => [
			'type' => 'bitmask',
			'constants' => [
				'LOCK_SH',
				'LOCK_EX',
				'LOCK_UN',
				'LOCK_NB',
			],
			'exclusiveGroups' => [
				['LOCK_SH', 'LOCK_EX', 'LOCK_UN'],
			],
		],
	],

	'SplFileObject::fseek' => [
		'whence' => [
			'type' => 'single',
			'constants' => [
				'SEEK_SET',
				'SEEK_CUR',
				'SEEK_END',
			],
		],
	],

	// DOMDocument methods (LIBXML_* global constants)

	'DOMDocument::load' => [
		'options' => [
			'type' => 'bitmask',
			'constants' => [
				'LIBXML_NOENT',
				'LIBXML_DTDLOAD',
				'LIBXML_DTDATTR',
				'LIBXML_DTDVALID',
				'LIBXML_NOERROR',
				'LIBXML_NOWARNING',
				'LIBXML_NOBLANKS',
				'LIBXML_XINCLUDE',
				'LIBXML_NSCLEAN',
				'LIBXML_NOCDATA',
				'LIBXML_NONET',
				'LIBXML_PEDANTIC',
				'LIBXML_COMPACT',
				'LIBXML_PARSEHUGE',
				'LIBXML_BIGLINES',
			],
		],
	],

	'DOMDocument::loadXML' => [
		'options' => [
			'type' => 'bitmask',
			'constants' => [
				'LIBXML_NOENT',
				'LIBXML_DTDLOAD',
				'LIBXML_DTDATTR',
				'LIBXML_DTDVALID',
				'LIBXML_NOERROR',
				'LIBXML_NOWARNING',
				'LIBXML_NOBLANKS',
				'LIBXML_XINCLUDE',
				'LIBXML_NSCLEAN',
				'LIBXML_NOCDATA',
				'LIBXML_NONET',
				'LIBXML_PEDANTIC',
				'LIBXML_COMPACT',
				'LIBXML_PARSEHUGE',
				'LIBXML_BIGLINES',
			],
		],
	],

	'DOMDocument::loadHTML' => [
		'options' => [
			'type' => 'bitmask',
			'constants' => [
				'LIBXML_NOENT',
				'LIBXML_DTDLOAD',
				'LIBXML_DTDATTR',
				'LIBXML_DTDVALID',
				'LIBXML_NOERROR',
				'LIBXML_NOWARNING',
				'LIBXML_NOBLANKS',
				'LIBXML_XINCLUDE',
				'LIBXML_NSCLEAN',
				'LIBXML_NOCDATA',
				'LIBXML_NONET',
				'LIBXML_PEDANTIC',
				'LIBXML_COMPACT',
				'LIBXML_PARSEHUGE',
				'LIBXML_BIGLINES',
				'LIBXML_HTML_NOIMPLIED',
				'LIBXML_HTML_NODEFDTD',
			],
		],
	],

	'DOMDocument::loadHTMLFile' => [
		'options' => [
			'type' => 'bitmask',
			'constants' => [
				'LIBXML_NOENT',
				'LIBXML_DTDLOAD',
				'LIBXML_DTDATTR',
				'LIBXML_DTDVALID',
				'LIBXML_NOERROR',
				'LIBXML_NOWARNING',
				'LIBXML_NOBLANKS',
				'LIBXML_XINCLUDE',
				'LIBXML_NSCLEAN',
				'LIBXML_NOCDATA',
				'LIBXML_NONET',
				'LIBXML_PEDANTIC',
				'LIBXML_COMPACT',
				'LIBXML_PARSEHUGE',
				'LIBXML_BIGLINES',
				'LIBXML_HTML_NOIMPLIED',
				'LIBXML_HTML_NODEFDTD',
			],
		],
	],

	'DOMDocument::save' => [
		'options' => [
			'type' => 'bitmask',
			'constants' => [
				'LIBXML_NOEMPTYTAG',
			],
		],
	],

	'DOMDocument::saveXML' => [
		'options' => [
			'type' => 'bitmask',
			'constants' => [
				'LIBXML_NOEMPTYTAG',
			],
		],
	],

	'DOMDocument::schemaValidate' => [
		'options' => [
			'type' => 'bitmask',
			'constants' => [
				'LIBXML_SCHEMA_CREATE',
			],
		],
	],

	'DOMDocument::schemaValidateSource' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'LIBXML_SCHEMA_CREATE',
			],
		],
	],

	// XMLReader methods (LIBXML_* global constants)

	'XMLReader::open' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'LIBXML_NOENT',
				'LIBXML_DTDLOAD',
				'LIBXML_DTDATTR',
				'LIBXML_DTDVALID',
				'LIBXML_NOERROR',
				'LIBXML_NOWARNING',
				'LIBXML_NOBLANKS',
				'LIBXML_XINCLUDE',
				'LIBXML_NSCLEAN',
				'LIBXML_NOCDATA',
				'LIBXML_NONET',
				'LIBXML_PEDANTIC',
				'LIBXML_COMPACT',
				'LIBXML_PARSEHUGE',
				'LIBXML_BIGLINES',
			],
		],
	],

	'XMLReader::XML' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'LIBXML_NOENT',
				'LIBXML_DTDLOAD',
				'LIBXML_DTDATTR',
				'LIBXML_DTDVALID',
				'LIBXML_NOERROR',
				'LIBXML_NOWARNING',
				'LIBXML_NOBLANKS',
				'LIBXML_XINCLUDE',
				'LIBXML_NSCLEAN',
				'LIBXML_NOCDATA',
				'LIBXML_NONET',
				'LIBXML_PEDANTIC',
				'LIBXML_COMPACT',
				'LIBXML_PARSEHUGE',
				'LIBXML_BIGLINES',
			],
		],
	],

	// mysqli methods (global constants)

	'mysqli::begin_transaction' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'MYSQLI_TRANS_START_READ_ONLY',
				'MYSQLI_TRANS_START_READ_WRITE',
				'MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT',
			],
			'exclusiveGroups' => [
				['MYSQLI_TRANS_START_READ_ONLY', 'MYSQLI_TRANS_START_READ_WRITE'],
			],
		],
	],

	'mysqli::commit' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'MYSQLI_TRANS_COR_AND_CHAIN',
				'MYSQLI_TRANS_COR_AND_NO_CHAIN',
				'MYSQLI_TRANS_COR_RELEASE',
				'MYSQLI_TRANS_COR_NO_RELEASE',
			],
			'exclusiveGroups' => [
				['MYSQLI_TRANS_COR_AND_CHAIN', 'MYSQLI_TRANS_COR_AND_NO_CHAIN'],
				['MYSQLI_TRANS_COR_RELEASE', 'MYSQLI_TRANS_COR_NO_RELEASE'],
			],
		],
	],

	'mysqli::rollback' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'MYSQLI_TRANS_COR_AND_CHAIN',
				'MYSQLI_TRANS_COR_AND_NO_CHAIN',
				'MYSQLI_TRANS_COR_RELEASE',
				'MYSQLI_TRANS_COR_NO_RELEASE',
			],
			'exclusiveGroups' => [
				['MYSQLI_TRANS_COR_AND_CHAIN', 'MYSQLI_TRANS_COR_AND_NO_CHAIN'],
				['MYSQLI_TRANS_COR_RELEASE', 'MYSQLI_TRANS_COR_NO_RELEASE'],
			],
		],
	],

	// Collator methods (class constants)

	'Collator::sort' => [
		'flags' => [
			'type' => 'single',
			'constants' => [
				'Collator::SORT_REGULAR',
				'Collator::SORT_STRING',
				'Collator::SORT_NUMERIC',
			],
		],
	],

	'Collator::asort' => [
		'flags' => [
			'type' => 'single',
			'constants' => [
				'Collator::SORT_REGULAR',
				'Collator::SORT_STRING',
				'Collator::SORT_NUMERIC',
			],
		],
	],

	'Collator::setAttribute' => [
		'attribute' => [
			'type' => 'single',
			'constants' => [
				'Collator::FRENCH_COLLATION',
				'Collator::ALTERNATE_HANDLING',
				'Collator::CASE_FIRST',
				'Collator::CASE_LEVEL',
				'Collator::NORMALIZATION_MODE',
				'Collator::STRENGTH',
				'Collator::HIRAGANA_QUATERNARY_MODE',
				'Collator::NUMERIC_COLLATION',
			],
		],
	],

	'Collator::getAttribute' => [
		'attribute' => [
			'type' => 'single',
			'constants' => [
				'Collator::FRENCH_COLLATION',
				'Collator::ALTERNATE_HANDLING',
				'Collator::CASE_FIRST',
				'Collator::CASE_LEVEL',
				'Collator::NORMALIZATION_MODE',
				'Collator::STRENGTH',
				'Collator::HIRAGANA_QUATERNARY_MODE',
				'Collator::NUMERIC_COLLATION',
			],
		],
	],

	// ————————————————————————————————————————————
	// Methods with class constants
	// ————————————————————————————————————————————

	// PDO

	'PDO::setAttribute' => [
		'attribute' => [
			'type' => 'single',
			'constants' => [
				'PDO::ATTR_AUTOCOMMIT',
				'PDO::ATTR_PREFETCH',
				'PDO::ATTR_TIMEOUT',
				'PDO::ATTR_ERRMODE',
				'PDO::ATTR_SERVER_VERSION',
				'PDO::ATTR_CLIENT_VERSION',
				'PDO::ATTR_SERVER_INFO',
				'PDO::ATTR_CONNECTION_STATUS',
				'PDO::ATTR_CASE',
				'PDO::ATTR_CURSOR_NAME',
				'PDO::ATTR_CURSOR',
				'PDO::ATTR_ORACLE_NULLS',
				'PDO::ATTR_PERSISTENT',
				'PDO::ATTR_STATEMENT_CLASS',
				'PDO::ATTR_FETCH_TABLE_NAMES',
				'PDO::ATTR_FETCH_CATALOG_NAMES',
				'PDO::ATTR_DRIVER_NAME',
				'PDO::ATTR_STRINGIFY_FETCHES',
				'PDO::ATTR_MAX_COLUMN_LEN',
				'PDO::ATTR_EMULATE_PREPARES',
				'PDO::ATTR_DEFAULT_FETCH_MODE',
				'PDO::ATTR_DEFAULT_STR_PARAM',
			],
		],
	],

	'PDO::getAttribute' => [
		'attribute' => [
			'type' => 'single',
			'constants' => [
				'PDO::ATTR_AUTOCOMMIT',
				'PDO::ATTR_PREFETCH',
				'PDO::ATTR_TIMEOUT',
				'PDO::ATTR_ERRMODE',
				'PDO::ATTR_SERVER_VERSION',
				'PDO::ATTR_CLIENT_VERSION',
				'PDO::ATTR_SERVER_INFO',
				'PDO::ATTR_CONNECTION_STATUS',
				'PDO::ATTR_CASE',
				'PDO::ATTR_CURSOR_NAME',
				'PDO::ATTR_CURSOR',
				'PDO::ATTR_ORACLE_NULLS',
				'PDO::ATTR_PERSISTENT',
				'PDO::ATTR_STATEMENT_CLASS',
				'PDO::ATTR_FETCH_TABLE_NAMES',
				'PDO::ATTR_FETCH_CATALOG_NAMES',
				'PDO::ATTR_DRIVER_NAME',
				'PDO::ATTR_STRINGIFY_FETCHES',
				'PDO::ATTR_MAX_COLUMN_LEN',
				'PDO::ATTR_EMULATE_PREPARES',
				'PDO::ATTR_DEFAULT_FETCH_MODE',
				'PDO::ATTR_DEFAULT_STR_PARAM',
			],
		],
	],

	// PDOStatement

	'PDOStatement::fetch' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'PDO::FETCH_DEFAULT',
				'PDO::FETCH_LAZY',
				'PDO::FETCH_ASSOC',
				'PDO::FETCH_NUM',
				'PDO::FETCH_BOTH',
				'PDO::FETCH_OBJ',
				'PDO::FETCH_BOUND',
				'PDO::FETCH_COLUMN',
				'PDO::FETCH_CLASS',
				'PDO::FETCH_INTO',
				'PDO::FETCH_FUNC',
				'PDO::FETCH_GROUP',
				'PDO::FETCH_UNIQUE',
				'PDO::FETCH_KEY_PAIR',
				'PDO::FETCH_CLASSTYPE',
				'PDO::FETCH_SERIALIZE',
				'PDO::FETCH_PROPS_LATE',
				'PDO::FETCH_NAMED',
			],
		],
		'cursorOrientation' => [
			'type' => 'single',
			'constants' => [
				'PDO::FETCH_ORI_NEXT',
				'PDO::FETCH_ORI_PRIOR',
				'PDO::FETCH_ORI_FIRST',
				'PDO::FETCH_ORI_LAST',
				'PDO::FETCH_ORI_ABS',
				'PDO::FETCH_ORI_REL',
			],
		],
	],

	'PDOStatement::fetchAll' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'PDO::FETCH_DEFAULT',
				'PDO::FETCH_LAZY',
				'PDO::FETCH_ASSOC',
				'PDO::FETCH_NUM',
				'PDO::FETCH_BOTH',
				'PDO::FETCH_OBJ',
				'PDO::FETCH_BOUND',
				'PDO::FETCH_COLUMN',
				'PDO::FETCH_CLASS',
				'PDO::FETCH_INTO',
				'PDO::FETCH_FUNC',
				'PDO::FETCH_GROUP',
				'PDO::FETCH_UNIQUE',
				'PDO::FETCH_KEY_PAIR',
				'PDO::FETCH_CLASSTYPE',
				'PDO::FETCH_SERIALIZE',
				'PDO::FETCH_PROPS_LATE',
				'PDO::FETCH_NAMED',
			],
		],
	],

	'PDOStatement::setFetchMode' => [
		'mode' => [
			'type' => 'single',
			'constants' => [
				'PDO::FETCH_DEFAULT',
				'PDO::FETCH_LAZY',
				'PDO::FETCH_ASSOC',
				'PDO::FETCH_NUM',
				'PDO::FETCH_BOTH',
				'PDO::FETCH_OBJ',
				'PDO::FETCH_BOUND',
				'PDO::FETCH_COLUMN',
				'PDO::FETCH_CLASS',
				'PDO::FETCH_INTO',
				'PDO::FETCH_FUNC',
				'PDO::FETCH_GROUP',
				'PDO::FETCH_UNIQUE',
				'PDO::FETCH_KEY_PAIR',
				'PDO::FETCH_CLASSTYPE',
				'PDO::FETCH_SERIALIZE',
				'PDO::FETCH_PROPS_LATE',
				'PDO::FETCH_NAMED',
			],
		],
	],

	'PDOStatement::bindColumn' => [
		'type' => [
			'type' => 'single',
			'constants' => [
				'PDO::PARAM_NULL',
				'PDO::PARAM_BOOL',
				'PDO::PARAM_INT',
				'PDO::PARAM_STR',
				'PDO::PARAM_LOB',
				'PDO::PARAM_STMT',
				'PDO::PARAM_INPUT_OUTPUT',
				'PDO::PARAM_STR_NATL',
				'PDO::PARAM_STR_CHAR',
			],
		],
	],

	'PDOStatement::bindParam' => [
		'type' => [
			'type' => 'single',
			'constants' => [
				'PDO::PARAM_NULL',
				'PDO::PARAM_BOOL',
				'PDO::PARAM_INT',
				'PDO::PARAM_STR',
				'PDO::PARAM_LOB',
				'PDO::PARAM_STMT',
				'PDO::PARAM_INPUT_OUTPUT',
				'PDO::PARAM_STR_NATL',
				'PDO::PARAM_STR_CHAR',
			],
		],
	],

	'PDOStatement::bindValue' => [
		'type' => [
			'type' => 'single',
			'constants' => [
				'PDO::PARAM_NULL',
				'PDO::PARAM_BOOL',
				'PDO::PARAM_INT',
				'PDO::PARAM_STR',
				'PDO::PARAM_LOB',
				'PDO::PARAM_STMT',
				'PDO::PARAM_INPUT_OUTPUT',
				'PDO::PARAM_STR_NATL',
				'PDO::PARAM_STR_CHAR',
			],
		],
	],

	// ZipArchive

	'ZipArchive::open' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'ZipArchive::CREATE',
				'ZipArchive::EXCL',
				'ZipArchive::CHECKCONS',
				'ZipArchive::OVERWRITE',
				'ZipArchive::RDONLY',
			],
		],
	],

	'ZipArchive::setCompressionName' => [
		'method' => [
			'type' => 'single',
			'constants' => [
				'ZipArchive::CM_DEFAULT',
				'ZipArchive::CM_STORE',
				'ZipArchive::CM_SHRINK',
				'ZipArchive::CM_REDUCE_1',
				'ZipArchive::CM_REDUCE_2',
				'ZipArchive::CM_REDUCE_3',
				'ZipArchive::CM_REDUCE_4',
				'ZipArchive::CM_IMPLODE',
				'ZipArchive::CM_DEFLATE',
				'ZipArchive::CM_DEFLATE64',
				'ZipArchive::CM_PKWARE_IMPLODE',
				'ZipArchive::CM_BZIP2',
				'ZipArchive::CM_LZMA',
				'ZipArchive::CM_LZMA2',
				'ZipArchive::CM_ZSTD',
				'ZipArchive::CM_XZ',
			],
		],
	],

	'ZipArchive::setCompressionIndex' => [
		'method' => [
			'type' => 'single',
			'constants' => [
				'ZipArchive::CM_DEFAULT',
				'ZipArchive::CM_STORE',
				'ZipArchive::CM_SHRINK',
				'ZipArchive::CM_REDUCE_1',
				'ZipArchive::CM_REDUCE_2',
				'ZipArchive::CM_REDUCE_3',
				'ZipArchive::CM_REDUCE_4',
				'ZipArchive::CM_IMPLODE',
				'ZipArchive::CM_DEFLATE',
				'ZipArchive::CM_DEFLATE64',
				'ZipArchive::CM_PKWARE_IMPLODE',
				'ZipArchive::CM_BZIP2',
				'ZipArchive::CM_LZMA',
				'ZipArchive::CM_LZMA2',
				'ZipArchive::CM_ZSTD',
				'ZipArchive::CM_XZ',
			],
		],
	],

	'ZipArchive::setEncryptionName' => [
		'method' => [
			'type' => 'single',
			'constants' => [
				'ZipArchive::EM_NONE',
				'ZipArchive::EM_TRAD_PKWARE',
				'ZipArchive::EM_AES_128',
				'ZipArchive::EM_AES_192',
				'ZipArchive::EM_AES_256',
			],
		],
	],

	'ZipArchive::setEncryptionIndex' => [
		'method' => [
			'type' => 'single',
			'constants' => [
				'ZipArchive::EM_NONE',
				'ZipArchive::EM_TRAD_PKWARE',
				'ZipArchive::EM_AES_128',
				'ZipArchive::EM_AES_192',
				'ZipArchive::EM_AES_256',
			],
		],
	],

	// IntlDateFormatter

	'IntlDateFormatter::__construct' => [
		'dateType' => [
			'type' => 'single',
			'constants' => [
				'IntlDateFormatter::FULL',
				'IntlDateFormatter::LONG',
				'IntlDateFormatter::MEDIUM',
				'IntlDateFormatter::SHORT',
				'IntlDateFormatter::NONE',
				'IntlDateFormatter::RELATIVE_FULL',
				'IntlDateFormatter::RELATIVE_LONG',
				'IntlDateFormatter::RELATIVE_MEDIUM',
				'IntlDateFormatter::RELATIVE_SHORT',
			],
		],
		'timeType' => [
			'type' => 'single',
			'constants' => [
				'IntlDateFormatter::FULL',
				'IntlDateFormatter::LONG',
				'IntlDateFormatter::MEDIUM',
				'IntlDateFormatter::SHORT',
				'IntlDateFormatter::NONE',
				'IntlDateFormatter::RELATIVE_FULL',
				'IntlDateFormatter::RELATIVE_LONG',
				'IntlDateFormatter::RELATIVE_MEDIUM',
				'IntlDateFormatter::RELATIVE_SHORT',
			],
		],
	],

	'IntlDateFormatter::create' => [
		'dateType' => [
			'type' => 'single',
			'constants' => [
				'IntlDateFormatter::FULL',
				'IntlDateFormatter::LONG',
				'IntlDateFormatter::MEDIUM',
				'IntlDateFormatter::SHORT',
				'IntlDateFormatter::NONE',
				'IntlDateFormatter::RELATIVE_FULL',
				'IntlDateFormatter::RELATIVE_LONG',
				'IntlDateFormatter::RELATIVE_MEDIUM',
				'IntlDateFormatter::RELATIVE_SHORT',
			],
		],
		'timeType' => [
			'type' => 'single',
			'constants' => [
				'IntlDateFormatter::FULL',
				'IntlDateFormatter::LONG',
				'IntlDateFormatter::MEDIUM',
				'IntlDateFormatter::SHORT',
				'IntlDateFormatter::NONE',
				'IntlDateFormatter::RELATIVE_FULL',
				'IntlDateFormatter::RELATIVE_LONG',
				'IntlDateFormatter::RELATIVE_MEDIUM',
				'IntlDateFormatter::RELATIVE_SHORT',
			],
		],
	],

	// NumberFormatter

	'NumberFormatter::__construct' => [
		'style' => [
			'type' => 'single',
			'constants' => [
				'NumberFormatter::PATTERN_DECIMAL',
				'NumberFormatter::DECIMAL',
				'NumberFormatter::CURRENCY',
				'NumberFormatter::PERCENT',
				'NumberFormatter::SCIENTIFIC',
				'NumberFormatter::SPELLOUT',
				'NumberFormatter::ORDINAL',
				'NumberFormatter::DURATION',
				'NumberFormatter::PATTERN_RULEBASED',
				'NumberFormatter::IGNORE',
				'NumberFormatter::CURRENCY_ACCOUNTING',
				'NumberFormatter::DEFAULT_STYLE',
			],
		],
	],

	'NumberFormatter::create' => [
		'style' => [
			'type' => 'single',
			'constants' => [
				'NumberFormatter::PATTERN_DECIMAL',
				'NumberFormatter::DECIMAL',
				'NumberFormatter::CURRENCY',
				'NumberFormatter::PERCENT',
				'NumberFormatter::SCIENTIFIC',
				'NumberFormatter::SPELLOUT',
				'NumberFormatter::ORDINAL',
				'NumberFormatter::DURATION',
				'NumberFormatter::PATTERN_RULEBASED',
				'NumberFormatter::IGNORE',
				'NumberFormatter::CURRENCY_ACCOUNTING',
				'NumberFormatter::DEFAULT_STYLE',
			],
		],
	],

	'NumberFormatter::format' => [
		'type' => [
			'type' => 'single',
			'constants' => [
				'NumberFormatter::TYPE_DEFAULT',
				'NumberFormatter::TYPE_INT32',
				'NumberFormatter::TYPE_INT64',
				'NumberFormatter::TYPE_DOUBLE',
				'NumberFormatter::TYPE_CURRENCY',
			],
		],
	],

	'NumberFormatter::setAttribute' => [
		'attribute' => [
			'type' => 'single',
			'constants' => [
				'NumberFormatter::PARSE_INT_ONLY',
				'NumberFormatter::GROUPING_USED',
				'NumberFormatter::DECIMAL_ALWAYS_SHOWN',
				'NumberFormatter::MAX_INTEGER_DIGITS',
				'NumberFormatter::MIN_INTEGER_DIGITS',
				'NumberFormatter::INTEGER_DIGITS',
				'NumberFormatter::MAX_FRACTION_DIGITS',
				'NumberFormatter::MIN_FRACTION_DIGITS',
				'NumberFormatter::FRACTION_DIGITS',
				'NumberFormatter::MULTIPLIER',
				'NumberFormatter::GROUPING_SIZE',
				'NumberFormatter::ROUNDING_MODE',
				'NumberFormatter::ROUNDING_INCREMENT',
				'NumberFormatter::FORMAT_WIDTH',
				'NumberFormatter::PADDING_POSITION',
				'NumberFormatter::SECONDARY_GROUPING_SIZE',
				'NumberFormatter::SIGNIFICANT_DIGITS_USED',
				'NumberFormatter::MIN_SIGNIFICANT_DIGITS',
				'NumberFormatter::MAX_SIGNIFICANT_DIGITS',
				'NumberFormatter::LENIENT_PARSE',
			],
		],
	],

	'NumberFormatter::getAttribute' => [
		'attribute' => [
			'type' => 'single',
			'constants' => [
				'NumberFormatter::PARSE_INT_ONLY',
				'NumberFormatter::GROUPING_USED',
				'NumberFormatter::DECIMAL_ALWAYS_SHOWN',
				'NumberFormatter::MAX_INTEGER_DIGITS',
				'NumberFormatter::MIN_INTEGER_DIGITS',
				'NumberFormatter::INTEGER_DIGITS',
				'NumberFormatter::MAX_FRACTION_DIGITS',
				'NumberFormatter::MIN_FRACTION_DIGITS',
				'NumberFormatter::FRACTION_DIGITS',
				'NumberFormatter::MULTIPLIER',
				'NumberFormatter::GROUPING_SIZE',
				'NumberFormatter::ROUNDING_MODE',
				'NumberFormatter::ROUNDING_INCREMENT',
				'NumberFormatter::FORMAT_WIDTH',
				'NumberFormatter::PADDING_POSITION',
				'NumberFormatter::SECONDARY_GROUPING_SIZE',
				'NumberFormatter::SIGNIFICANT_DIGITS_USED',
				'NumberFormatter::MIN_SIGNIFICANT_DIGITS',
				'NumberFormatter::MAX_SIGNIFICANT_DIGITS',
				'NumberFormatter::LENIENT_PARSE',
			],
		],
	],

	'NumberFormatter::setTextAttribute' => [
		'attribute' => [
			'type' => 'single',
			'constants' => [
				'NumberFormatter::POSITIVE_PREFIX',
				'NumberFormatter::POSITIVE_SUFFIX',
				'NumberFormatter::NEGATIVE_PREFIX',
				'NumberFormatter::NEGATIVE_SUFFIX',
				'NumberFormatter::PADDING_CHARACTER',
				'NumberFormatter::CURRENCY_CODE',
				'NumberFormatter::DEFAULT_RULESET',
				'NumberFormatter::PUBLIC_RULESETS',
			],
		],
	],

	'NumberFormatter::getTextAttribute' => [
		'attribute' => [
			'type' => 'single',
			'constants' => [
				'NumberFormatter::POSITIVE_PREFIX',
				'NumberFormatter::POSITIVE_SUFFIX',
				'NumberFormatter::NEGATIVE_PREFIX',
				'NumberFormatter::NEGATIVE_SUFFIX',
				'NumberFormatter::PADDING_CHARACTER',
				'NumberFormatter::CURRENCY_CODE',
				'NumberFormatter::DEFAULT_RULESET',
				'NumberFormatter::PUBLIC_RULESETS',
			],
		],
	],

	// SplPriorityQueue

	'SplPriorityQueue::setExtractFlags' => [
		'flags' => [
			'type' => 'single',
			'constants' => [
				'SplPriorityQueue::EXTR_BOTH',
				'SplPriorityQueue::EXTR_PRIORITY',
				'SplPriorityQueue::EXTR_DATA',
			],
		],
	],

	// FilesystemIterator / GlobIterator / RecursiveDirectoryIterator

	'FilesystemIterator::__construct' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'FilesystemIterator::CURRENT_AS_PATHNAME',
				'FilesystemIterator::CURRENT_AS_FILEINFO',
				'FilesystemIterator::CURRENT_AS_SELF',
				'FilesystemIterator::KEY_AS_PATHNAME',
				'FilesystemIterator::KEY_AS_FILENAME',
				'FilesystemIterator::FOLLOW_SYMLINKS',
				'FilesystemIterator::NEW_CURRENT_AND_KEY',
				'FilesystemIterator::SKIP_DOTS',
				'FilesystemIterator::UNIX_PATHS',
			],
			'exclusiveGroups' => [
				['FilesystemIterator::CURRENT_AS_PATHNAME', 'FilesystemIterator::CURRENT_AS_FILEINFO', 'FilesystemIterator::CURRENT_AS_SELF'],
				['FilesystemIterator::KEY_AS_PATHNAME', 'FilesystemIterator::KEY_AS_FILENAME'],
			],
		],
	],

	'FilesystemIterator::setFlags' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'FilesystemIterator::CURRENT_AS_PATHNAME',
				'FilesystemIterator::CURRENT_AS_FILEINFO',
				'FilesystemIterator::CURRENT_AS_SELF',
				'FilesystemIterator::KEY_AS_PATHNAME',
				'FilesystemIterator::KEY_AS_FILENAME',
				'FilesystemIterator::FOLLOW_SYMLINKS',
				'FilesystemIterator::NEW_CURRENT_AND_KEY',
				'FilesystemIterator::SKIP_DOTS',
				'FilesystemIterator::UNIX_PATHS',
			],
			'exclusiveGroups' => [
				['FilesystemIterator::CURRENT_AS_PATHNAME', 'FilesystemIterator::CURRENT_AS_FILEINFO', 'FilesystemIterator::CURRENT_AS_SELF'],
				['FilesystemIterator::KEY_AS_PATHNAME', 'FilesystemIterator::KEY_AS_FILENAME'],
			],
		],
	],

	'GlobIterator::__construct' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'FilesystemIterator::CURRENT_AS_PATHNAME',
				'FilesystemIterator::CURRENT_AS_FILEINFO',
				'FilesystemIterator::CURRENT_AS_SELF',
				'FilesystemIterator::KEY_AS_PATHNAME',
				'FilesystemIterator::KEY_AS_FILENAME',
				'FilesystemIterator::FOLLOW_SYMLINKS',
				'FilesystemIterator::NEW_CURRENT_AND_KEY',
				'FilesystemIterator::SKIP_DOTS',
				'FilesystemIterator::UNIX_PATHS',
			],
			'exclusiveGroups' => [
				['FilesystemIterator::CURRENT_AS_PATHNAME', 'FilesystemIterator::CURRENT_AS_FILEINFO', 'FilesystemIterator::CURRENT_AS_SELF'],
				['FilesystemIterator::KEY_AS_PATHNAME', 'FilesystemIterator::KEY_AS_FILENAME'],
			],
		],
	],

	'RecursiveDirectoryIterator::__construct' => [
		'flags' => [
			'type' => 'bitmask',
			'constants' => [
				'FilesystemIterator::CURRENT_AS_PATHNAME',
				'FilesystemIterator::CURRENT_AS_FILEINFO',
				'FilesystemIterator::CURRENT_AS_SELF',
				'FilesystemIterator::KEY_AS_PATHNAME',
				'FilesystemIterator::KEY_AS_FILENAME',
				'FilesystemIterator::FOLLOW_SYMLINKS',
				'FilesystemIterator::NEW_CURRENT_AND_KEY',
				'FilesystemIterator::SKIP_DOTS',
				'FilesystemIterator::UNIX_PATHS',
			],
			'exclusiveGroups' => [
				['FilesystemIterator::CURRENT_AS_PATHNAME', 'FilesystemIterator::CURRENT_AS_FILEINFO', 'FilesystemIterator::CURRENT_AS_SELF'],
				['FilesystemIterator::KEY_AS_PATHNAME', 'FilesystemIterator::KEY_AS_FILENAME'],
			],
		],
	],
];
