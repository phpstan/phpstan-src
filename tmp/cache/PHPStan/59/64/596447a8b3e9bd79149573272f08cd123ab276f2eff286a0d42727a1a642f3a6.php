<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../guzzlehttp/guzzle/src/RequestOptions.php-PHPStan\BetterReflection\Reflection\ReflectionClass-GuzzleHttp\RequestOptions
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-655077c65fb58e0d636e6784a090b8a6c7cdc751543b77b519bd470a2db6ede2-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'GuzzleHttp\\RequestOptions',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../guzzlehttp/guzzle/src/RequestOptions.php',
      ),
    ),
    'namespace' => 'GuzzleHttp',
    'name' => 'GuzzleHttp\\RequestOptions',
    'shortName' => 'RequestOptions',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * This class contains a list of built-in Guzzle request options.
 *
 * @see https://docs.guzzlephp.org/en/latest/request-options.html
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 274,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'ALLOW_REDIRECTS' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'ALLOW_REDIRECTS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'allow_redirects\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 27,
            'startFilePos' => 1413,
            'endTokenPos' => 27,
            'endFilePos' => 1429,
          ),
        ),
        'docComment' => '/**
 * allow_redirects: (bool|array) Controls redirect behavior. Pass false
 * to disable redirects, pass true to enable redirects, pass an
 * associative to provide custom redirect settings. Defaults to "false".
 * This option only works if your handler has the RedirectMiddleware. When
 * passing an associative array, you can provide the following key value
 * pairs:
 *
 * - max: (int, default=5) maximum number of allowed redirects.
 * - strict: (bool, default=false) Set to true to use strict redirects
 *   meaning redirect POST requests with POST requests vs. doing what most
 *   browsers do which is redirect POST requests with GET requests
 * - referer: (bool, default=false) Set to true to enable the Referer
 *   header.
 * - protocols: (array, default=[\'http\', \'https\']) Allowed redirect
 *   protocols.
 * - on_redirect: (callable) PHP callable that is invoked when a redirect
 *   is encountered. The callable is invoked with the request, the redirect
 *   response that was received, and the effective URI. Any return value
 *   from the on_redirect function is ignored.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 53,
      ),
      'AUTH' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'AUTH',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'auth\'',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 42,
            'startTokenPos' => 40,
            'startFilePos' => 1799,
            'endTokenPos' => 40,
            'endFilePos' => 1804,
          ),
        ),
        'docComment' => '/**
 * auth: (array) Pass an array of HTTP authentication parameters to use
 * with the request. The array must contain the username in index [0],
 * the password in index [1], and you can optionally provide a built-in
 * authentication type in index [2]. Pass null to disable authentication
 * for a request.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'BODY' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'BODY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'body\'',
          'attributes' => 
          array (
            'startLine' => 48,
            'endLine' => 48,
            'startTokenPos' => 53,
            'startFilePos' => 1965,
            'endTokenPos' => 53,
            'endFilePos' => 1970,
          ),
        ),
        'docComment' => '/**
 * body: (resource|string|null|int|float|StreamInterface|callable|\\Iterator)
 * Body to send in the request.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 48,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'CERT' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'CERT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'cert\'',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 66,
            'startFilePos' => 2354,
            'endTokenPos' => 66,
            'endFilePos' => 2359,
          ),
        ),
        'docComment' => '/**
 * cert: (string|array) Set to a string to specify the path to a file
 * containing a PEM formatted SSL client side certificate. If a password
 * is required, then set cert to an array containing the path to the PEM
 * file in the first array element followed by the certificate password
 * in the second array element.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'COOKIES' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'COOKIES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'cookies\'',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 66,
            'startTokenPos' => 79,
            'startFilePos' => 2761,
            'endTokenPos' => 79,
            'endFilePos' => 2769,
          ),
        ),
        'docComment' => '/**
 * cookies: (bool|GuzzleHttp\\Cookie\\CookieJarInterface, default=false)
 * Specifies whether or not cookies are used in a request or what cookie
 * jar to use or what cookies to send. This option only works if your
 * handler has the `cookie` middleware. Valid values are `false` and
 * an instance of {@see Cookie\\CookieJarInterface}.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 37,
      ),
      'CONNECT_TIMEOUT' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'CONNECT_TIMEOUT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'connect_timeout\'',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 92,
            'startFilePos' => 3015,
            'endTokenPos' => 92,
            'endFilePos' => 3031,
          ),
        ),
        'docComment' => '/**
 * connect_timeout: (float, default=0) Float describing the number of
 * seconds to wait while trying to connect to a server. Use 0 to wait
 * 300 seconds (the default behavior).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 53,
      ),
      'CRYPTO_METHOD' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'CRYPTO_METHOD',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'crypto_method\'',
          'attributes' => 
          array (
            'startLine' => 85,
            'endLine' => 85,
            'startTokenPos' => 105,
            'startFilePos' => 3496,
            'endTokenPos' => 105,
            'endFilePos' => 3510,
          ),
        ),
        'docComment' => '/**
 * crypto_method: (int) A value describing the minimum TLS protocol
 * version to use.
 *
 * This setting must be set to one of the
 * ``STREAM_CRYPTO_METHOD_TLS*_CLIENT`` constants. PHP 7.4 or higher is
 * required in order to use TLS 1.3, and cURL 7.34.0 or higher is required
 * in order to specify a crypto method, with cURL 7.52.0 or higher being
 * required to use TLS 1.3.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 49,
      ),
      'DEBUG' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'DEBUG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'debug\'',
          'attributes' => 
          array (
            'startLine' => 92,
            'endLine' => 92,
            'startTokenPos' => 118,
            'startFilePos' => 3721,
            'endTokenPos' => 118,
            'endFilePos' => 3727,
          ),
        ),
        'docComment' => '/**
 * debug: (bool|resource) Set to true or set to a PHP stream returned by
 * fopen()  enable debug output with the HTTP handler used to send a
 * request.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 92,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
      'DECODE_CONTENT' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'DECODE_CONTENT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'decode_content\'',
          'attributes' => 
          array (
            'startLine' => 99,
            'endLine' => 99,
            'startTokenPos' => 131,
            'startFilePos' => 3938,
            'endTokenPos' => 131,
            'endFilePos' => 3953,
          ),
        ),
        'docComment' => '/**
 * decode_content: (bool, default=true) Specify whether or not
 * Content-Encoding responses (gzip, deflate, etc.) are automatically
 * decoded.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 99,
        'endLine' => 99,
        'startColumn' => 5,
        'endColumn' => 51,
      ),
      'DELAY' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'DELAY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'delay\'',
          'attributes' => 
          array (
            'startLine' => 104,
            'endLine' => 104,
            'startTokenPos' => 144,
            'startFilePos' => 4078,
            'endTokenPos' => 144,
            'endFilePos' => 4084,
          ),
        ),
        'docComment' => '/**
 * delay: (int) The amount of time to delay before sending in milliseconds.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 104,
        'endLine' => 104,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
      'EXPECT' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'EXPECT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'expect\'',
          'attributes' => 
          array (
            'startLine' => 122,
            'endLine' => 122,
            'startTokenPos' => 157,
            'startFilePos' => 4910,
            'endTokenPos' => 157,
            'endFilePos' => 4917,
          ),
        ),
        'docComment' => '/**
 * expect: (bool|integer) Controls the behavior of the
 * "Expect: 100-Continue" header.
 *
 * Set to `true` to enable the "Expect: 100-Continue" header for all
 * requests that sends a body. Set to `false` to disable the
 * "Expect: 100-Continue" header for all requests. Set to a number so that
 * the size of the payload must be greater than the number in order to send
 * the Expect header. Setting to a number will send the Expect header for
 * all requests in which the size of the payload cannot be determined or
 * where the body is not rewindable.
 *
 * By default, Guzzle will add the "Expect: 100-Continue" header when the
 * size of the body of a request is greater than 1 MB and a request is
 * using HTTP/1.1.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 122,
        'endLine' => 122,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'FORM_PARAMS' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'FORM_PARAMS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'form_params\'',
          'attributes' => 
          array (
            'startLine' => 130,
            'endLine' => 130,
            'startTokenPos' => 170,
            'startFilePos' => 5229,
            'endTokenPos' => 170,
            'endFilePos' => 5241,
          ),
        ),
        'docComment' => '/**
 * form_params: (array) Associative array of form field names to values
 * where each value is a string or array of strings. Sets the Content-Type
 * header to application/x-www-form-urlencoded when no Content-Type header
 * is already present.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 130,
        'endLine' => 130,
        'startColumn' => 5,
        'endColumn' => 45,
      ),
      'HEADERS' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'HEADERS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'headers\'',
          'attributes' => 
          array (
            'startLine' => 136,
            'endLine' => 136,
            'startTokenPos' => 183,
            'startFilePos' => 5403,
            'endTokenPos' => 183,
            'endFilePos' => 5411,
          ),
        ),
        'docComment' => '/**
 * headers: (array) Associative array of HTTP headers. Each value MUST be
 * a string or array of strings.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 136,
        'endLine' => 136,
        'startColumn' => 5,
        'endColumn' => 37,
      ),
      'HTTP_ERRORS' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'HTTP_ERRORS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'http_errors\'',
          'attributes' => 
          array (
            'startLine' => 144,
            'endLine' => 144,
            'startTokenPos' => 196,
            'startFilePos' => 5746,
            'endTokenPos' => 196,
            'endFilePos' => 5758,
          ),
        ),
        'docComment' => '/**
 * http_errors: (bool, default=true) Set to false to disable exceptions
 * when a non- successful HTTP response is received. By default,
 * exceptions will be thrown for 4xx and 5xx responses. This option only
 * works if your handler has the `httpErrors` middleware.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 144,
        'endLine' => 144,
        'startColumn' => 5,
        'endColumn' => 45,
      ),
      'IDN_CONVERSION' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'IDN_CONVERSION',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'idn_conversion\'',
          'attributes' => 
          array (
            'startLine' => 152,
            'endLine' => 152,
            'startTokenPos' => 209,
            'startFilePos' => 6082,
            'endTokenPos' => 209,
            'endFilePos' => 6097,
          ),
        ),
        'docComment' => '/**
 * idn: (bool|int, default=true) A combination of IDNA_* constants for
 * idn_to_ascii() PHP\'s function (see "options" parameter). Set to false to
 * disable IDN support completely, or to true to use the default
 * configuration (IDNA_DEFAULT constant).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 152,
        'endLine' => 152,
        'startColumn' => 5,
        'endColumn' => 51,
      ),
      'JSON' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'JSON',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'json\'',
          'attributes' => 
          array (
            'startLine' => 159,
            'endLine' => 159,
            'startTokenPos' => 222,
            'startFilePos' => 6361,
            'endTokenPos' => 222,
            'endFilePos' => 6366,
          ),
        ),
        'docComment' => '/**
 * json: (mixed) Adds JSON data to a request. The provided value is JSON
 * encoded and a Content-Type header of application/json will be added to
 * the request if no Content-Type header is already present.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 159,
        'endLine' => 159,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'MULTIPART' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'MULTIPART',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'multipart\'',
          'attributes' => 
          array (
            'startLine' => 170,
            'endLine' => 170,
            'startTokenPos' => 235,
            'startFilePos' => 6891,
            'endTokenPos' => 235,
            'endFilePos' => 6901,
          ),
        ),
        'docComment' => '/**
 * multipart: (array) Array of associative arrays, each containing a
 * required "name" key mapping to the form field, name, a required
 * "contents" key mapping to a StreamInterface|resource|string, an
 * optional "headers" associative array of custom headers, and an
 * optional "filename" key mapping to a string to send as the filename in
 * the part. If no "filename" key is present, then no "filename" attribute
 * will be added to the part.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 170,
        'endLine' => 170,
        'startColumn' => 5,
        'endColumn' => 41,
      ),
      'ON_HEADERS' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'ON_HEADERS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'on_headers\'',
          'attributes' => 
          array (
            'startLine' => 177,
            'endLine' => 177,
            'startTokenPos' => 248,
            'startFilePos' => 7123,
            'endTokenPos' => 248,
            'endFilePos' => 7134,
          ),
        ),
        'docComment' => '/**
 * on_headers: (callable) A callable that is invoked when the HTTP headers
 * of the response have been received but the body has not yet begun to
 * download.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 177,
        'endLine' => 177,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
      'ON_STATS' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'ON_STATS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'on_stats\'',
          'attributes' => 
          array (
            'startLine' => 188,
            'endLine' => 188,
            'startTokenPos' => 261,
            'startFilePos' => 7684,
            'endTokenPos' => 261,
            'endFilePos' => 7693,
          ),
        ),
        'docComment' => '/**
 * on_stats: (callable) allows you to get access to transfer statistics of
 * a request and access the lower level transfer details of the handler
 * associated with your client. ``on_stats`` is a callable that is invoked
 * when a handler has finished sending a request. The callback is invoked
 * with transfer statistics about the request, the response received, or
 * the error encountered. Included in the data is the total amount of time
 * taken to send the request.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 188,
        'endLine' => 188,
        'startColumn' => 5,
        'endColumn' => 39,
      ),
      'PROGRESS' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'PROGRESS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'progress\'',
          'attributes' => 
          array (
            'startLine' => 197,
            'endLine' => 197,
            'startTokenPos' => 274,
            'startFilePos' => 8089,
            'endTokenPos' => 274,
            'endFilePos' => 8098,
          ),
        ),
        'docComment' => '/**
 * progress: (callable) Defines a function to invoke when transfer
 * progress is made. The function accepts the following positional
 * arguments: the total number of bytes expected to be downloaded, the
 * number of bytes downloaded so far, the number of bytes expected to be
 * uploaded, the number of bytes uploaded so far.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 197,
        'endLine' => 197,
        'startColumn' => 5,
        'endColumn' => 39,
      ),
      'PROXY' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'PROXY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'proxy\'',
          'attributes' => 
          array (
            'startLine' => 204,
            'endLine' => 204,
            'startTokenPos' => 287,
            'startFilePos' => 8356,
            'endTokenPos' => 287,
            'endFilePos' => 8362,
          ),
        ),
        'docComment' => '/**
 * proxy: (string|array) Pass a string to specify an HTTP proxy, or an
 * array to specify different proxies for different protocols (where the
 * key is the protocol and the value is a proxy string).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 204,
        'endLine' => 204,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
      'QUERY' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'QUERY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'query\'',
          'attributes' => 
          array (
            'startLine' => 212,
            'endLine' => 212,
            'startTokenPos' => 300,
            'startFilePos' => 8676,
            'endTokenPos' => 300,
            'endFilePos' => 8682,
          ),
        ),
        'docComment' => '/**
 * query: (array|string) Associative array of query string values to add
 * to the request. This option uses PHP\'s http_build_query() to create
 * the string representation. Pass a string value if you need more
 * control than what this method provides
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 212,
        'endLine' => 212,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
      'SINK' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'SINK',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'sink\'',
          'attributes' => 
          array (
            'startLine' => 219,
            'endLine' => 219,
            'startTokenPos' => 313,
            'startFilePos' => 8928,
            'endTokenPos' => 313,
            'endFilePos' => 8933,
          ),
        ),
        'docComment' => '/**
 * sink: (resource|string|StreamInterface) Where the data of the
 * response is written to. Defaults to a PHP temp stream. Providing a
 * string will write data to a file by the given name.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 219,
        'endLine' => 219,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'SYNCHRONOUS' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'SYNCHRONOUS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'synchronous\'',
          'attributes' => 
          array (
            'startLine' => 227,
            'endLine' => 227,
            'startTokenPos' => 326,
            'startFilePos' => 9238,
            'endTokenPos' => 326,
            'endFilePos' => 9250,
          ),
        ),
        'docComment' => '/**
 * synchronous: (bool) Set to true to inform HTTP handlers that you intend
 * on waiting on the response. This can be useful for optimizations. Note
 * that a promise is still returned if you are using one of the async
 * client methods.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 227,
        'endLine' => 227,
        'startColumn' => 5,
        'endColumn' => 45,
      ),
      'SSL_KEY' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'SSL_KEY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'ssl_key\'',
          'attributes' => 
          array (
            'startLine' => 235,
            'endLine' => 235,
            'startTokenPos' => 339,
            'startFilePos' => 9607,
            'endTokenPos' => 339,
            'endFilePos' => 9615,
          ),
        ),
        'docComment' => '/**
 * ssl_key: (array|string) Specify the path to a file containing a private
 * SSL key in PEM format. If a password is required, then set to an array
 * containing the path to the SSL key in the first array element followed
 * by the password required for the certificate in the second element.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 235,
        'endLine' => 235,
        'startColumn' => 5,
        'endColumn' => 37,
      ),
      'STREAM' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'STREAM',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'stream\'',
          'attributes' => 
          array (
            'startLine' => 241,
            'endLine' => 241,
            'startTokenPos' => 352,
            'startFilePos' => 9765,
            'endTokenPos' => 352,
            'endFilePos' => 9772,
          ),
        ),
        'docComment' => '/**
 * stream: Set to true to attempt to stream a response rather than
 * download it all up-front.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 241,
        'endLine' => 241,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'VERIFY' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'VERIFY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'verify\'',
          'attributes' => 
          array (
            'startLine' => 251,
            'endLine' => 251,
            'startTokenPos' => 365,
            'startFilePos' => 10250,
            'endTokenPos' => 365,
            'endFilePos' => 10257,
          ),
        ),
        'docComment' => '/**
 * verify: (bool|string, default=true) Describes the SSL certificate
 * verification behavior of a request. Set to true to enable SSL
 * certificate verification using the system CA bundle when available
 * (the default). Set to false to disable certificate verification (this
 * is insecure!). Set to a string to provide the path to a CA bundle on
 * disk to enable verification using a custom certificate.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 251,
        'endLine' => 251,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'TIMEOUT' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'TIMEOUT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'timeout\'',
          'attributes' => 
          array (
            'startLine' => 257,
            'endLine' => 257,
            'startTokenPos' => 378,
            'startFilePos' => 10453,
            'endTokenPos' => 378,
            'endFilePos' => 10461,
          ),
        ),
        'docComment' => '/**
 * timeout: (float, default=0) Float describing the timeout of the
 * request in seconds. Use 0 to wait indefinitely (the default behavior).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 257,
        'endLine' => 257,
        'startColumn' => 5,
        'endColumn' => 37,
      ),
      'READ_TIMEOUT' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'READ_TIMEOUT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'read_timeout\'',
          'attributes' => 
          array (
            'startLine' => 263,
            'endLine' => 263,
            'startTokenPos' => 391,
            'startFilePos' => 10654,
            'endTokenPos' => 391,
            'endFilePos' => 10667,
          ),
        ),
        'docComment' => '/**
 * read_timeout: (float, default=default_socket_timeout ini setting) Float describing
 * the body read timeout, for stream requests.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 263,
        'endLine' => 263,
        'startColumn' => 5,
        'endColumn' => 47,
      ),
      'VERSION' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'VERSION',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'version\'',
          'attributes' => 
          array (
            'startLine' => 268,
            'endLine' => 268,
            'startTokenPos' => 404,
            'startFilePos' => 10793,
            'endTokenPos' => 404,
            'endFilePos' => 10801,
          ),
        ),
        'docComment' => '/**
 * version: (float) Specifies the HTTP protocol version to attempt to use.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 268,
        'endLine' => 268,
        'startColumn' => 5,
        'endColumn' => 37,
      ),
      'FORCE_IP_RESOLVE' => 
      array (
        'declaringClassName' => 'GuzzleHttp\\RequestOptions',
        'implementingClassName' => 'GuzzleHttp\\RequestOptions',
        'name' => 'FORCE_IP_RESOLVE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'force_ip_resolve\'',
          'attributes' => 
          array (
            'startLine' => 273,
            'endLine' => 273,
            'startTokenPos' => 417,
            'startFilePos' => 10936,
            'endTokenPos' => 417,
            'endFilePos' => 10953,
          ),
        ),
        'docComment' => '/**
 * force_ip_resolve: (bool) Force client to use only ipv4 or ipv6 protocol
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 273,
        'endLine' => 273,
        'startColumn' => 5,
        'endColumn' => 55,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));