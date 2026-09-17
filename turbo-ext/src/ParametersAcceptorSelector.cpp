/*
 * PHPStanTurbo\ParametersAcceptorSelector — native implementation of
 * PHPStan\Reflection\ParametersAcceptorSelector.
 *
 * A final class of static methods selecting the variant of a call from its
 * argument types, overriding the parameters of the intrinsically typed
 * callbacks (array_map/filter/walk/find, curl_setopt(_array), implode,
 * Closure::bind) and combining variants. Every public method is exported as a
 * pt_parameters_acceptor_selector_*() entry for the native engine and Type
 * classes. applyIntrinsicArgOverrides() takes its four type getters as a
 * Getters value: the \Closures of the public method, native closures of a
 * native caller, or — for selectFromArgs() — the scope itself, whose reads
 * the twin's arrow functions only forward.
 *
 * The parameter and acceptor getters go through the DummyParameter slots
 * (ParameterValues.h) or a cached site per getter; the value classes that
 * stay PHP (FunctionVariant and its subclasses, GenericParametersAcceptorResolver,
 * ParameterAllowedConstants) through the class map and cached sites.
 */

#include "support.h"
#include "generated/ParametersAcceptorSelector.h"

namespace sigs = ptdecl::ParametersAcceptorSelector::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "ParameterValues.h"
#include "AcceptorValues.h"
#include "zend_closures.h" /* zend_ce_closure */

zend_class_entry *pt_ce_parameters_acceptor_selector = nullptr;

namespace {

/* persistent interned literals, created at registration */
zend_string *pt_pas_original_arg = nullptr;
zend_string *pt_pas_array_map_args = nullptr;
zend_string *pt_pas_curl_set_opt_arg = nullptr;
zend_string *pt_pas_curl_set_opt_array_arg = nullptr;
zend_string *pt_pas_array_filter_arg = nullptr;
zend_string *pt_pas_implode_arg = nullptr;
zend_string *pt_pas_array_walk_arg = nullptr;
zend_string *pt_pas_array_find_arg = nullptr;
zend_string *pt_pas_closure_bind_to_var = nullptr;
zend_string *pt_pas_closure_bind_arg = nullptr;
zend_string *pt_pas_item = nullptr;
zend_string *pt_pas_key = nullptr;
zend_string *pt_pas_value = nullptr;
zend_string *pt_pas_arg = nullptr;
zend_string *pt_pas_array = nullptr;
zend_string *pt_pas_handle = nullptr;
zend_string *pt_pas_closure_class = nullptr;
zend_string *pt_pas_curl_handle = nullptr;
zend_string *pt_pas_curl_share_handle = nullptr;
zend_string *pt_pas_curl_share_persistent_handle = nullptr;

/* {{{ generic reads and calls */

zv::Val callOn(pt_method_site &site, zval *object, const char *lcname, size_t len, const char *name, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_call_method_cached(site, Z_OBJ_P(object), lcname, len, argc, argv);
}

/* $object->method(...$argv) on a receiver of any class (a Type, a scope, a
 * reflection), by name */
zv::Val callByName(zval *object, const char *lcname, size_t len, const char *name, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(object), lcname, len, argc, argv);
}

/* the engine's read of $object->name when the slot fast path does not apply;
 * NULL = pending exception */
zend_never_inline zval *readPropertySlow(zval *object, const char *name, size_t len, zv::Val &hold)
{
	if (Z_TYPE_P(object) != IS_OBJECT) {
		zend_error(E_WARNING, "Attempt to read property \"%s\" on %s", name, zend_zval_value_name(object));
		if (UNEXPECTED(EG(exception))) return NULL;
		return &EG(uninitialized_zval);
	}
	zval rv;
	ZVAL_UNDEF(&rv);
	zval *value = zend_read_property(Z_OBJCE_P(object), Z_OBJ_P(object), name, len, 0, &rv);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&rv);
		return NULL;
	}
	if (value == &rv) {
		hold = zv::Val::adopt(rv);
		return hold.raw();
	}
	ZVAL_DEREF(value);
	return value;
}

inline zval *readProperty(pt_property_site &site, zval *object, const char *name, size_t len, zv::Val &hold)
{
	if (EXPECTED(Z_TYPE_P(object) == IS_OBJECT)) {
		zval *slot = pt_property_cached(site, Z_OBJ_P(object), name, len);
		if (EXPECTED(slot != NULL)) {
			ZVAL_DEREF(slot);
			if (EXPECTED(Z_TYPE_P(slot) != IS_UNDEF)) return slot;
		}
	}
	return readPropertySlow(object, name, len, hold);
}

/* $value instanceof <class-map class or interface>; false = pending exception */
inline bool isA(zval *value, int classIdx, bool &out)
{
	if (Z_TYPE_P(value) != IS_OBJECT) {
		out = false;
		return true;
	}
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return false;
	out = instanceof_function(Z_OBJCE_P(value), ce);
	return true;
}

inline bool requireArray(zval *value, const char *what)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_ARRAY)) return true;
	zend_type_error("%s must be of type array, %s given", what, zend_zval_value_name(value));
	return false;
}

/* $array[$key] of a read the twin spells without a guard: the value, or null
 * after the engine's warning; NULL = pending exception */
zval *readIndex(HashTable *table, zend_ulong key)
{
	zval *value = zend_hash_index_find(table, key);
	if (EXPECTED(value != NULL)) {
		ZVAL_DEREF(value);
		return value;
	}
	zend_error(E_WARNING, "Undefined array key " ZEND_ULONG_FMT, key);
	if (UNEXPECTED(EG(exception))) return NULL;
	return &EG(uninitialized_zval);
}

/* isset($array[$key]) — the value or NULL */
inline zval *issetIndex(HashTable *table, zend_ulong key)
{
	zval *value = zend_hash_index_find(table, key);
	if (value == NULL) return NULL;
	ZVAL_DEREF(value);
	return Z_TYPE_P(value) == IS_NULL ? NULL : value;
}

/* array_last($array) — the last element (borrowed) or null */
inline zval *arrayLast(HashTable *table)
{
	uint32_t pos = table->nNumUsed;
	while (pos > 0) {
		pos--;
		zval *slot = HT_IS_PACKED(table) ? &table->arPacked[pos] : &table->arData[pos].val;
		if (Z_TYPE_P(slot) != IS_UNDEF) {
			ZVAL_DEREF(slot);
			return slot;
		}
	}
	return &EG(uninitialized_zval);
}

/* $array[$key] = $value (borrowed, addref'ed) for an entry key */
void setEntryKey(zv::Arr &array, zend_string *stringKey, zend_ulong intKey, zval *value)
{
	array.separate();
	Z_TRY_ADDREF_P(value);
	if (stringKey != NULL) {
		zend_hash_update(array.table(), stringKey, value);
	} else {
		zend_hash_index_update(array.table(), intKey, value);
	}
}

/* a TrinaryLogic's PT_TRI_* value; -1 = pending exception */
inline zend_long trinaryOf(zv::Val &value)
{
	if (UNEXPECTED(value.isUndef())) return -1;
	return pt_type_trinary_value(value.raw());
}

/* TrinaryLogic::create*() for a value, as an owned copy */
inline zv::Val trinary(zend_long value)
{
	return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value)));
}

/* a native Type constructor's out-zval as an owned value */
template <typename F>
inline zv::Val newType(F construct)
{
	zval out;
	if (UNEXPECTED(!construct(&out))) return zv::Val();
	return zv::Val::adopt(out);
}

/* $node->getAttribute($key) of a php-parser node (borrowed, NULL when absent) */
inline zval *nodeAttribute(zval *node, zend_string *key)
{
	if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) return NULL;
	zval *value = pt_node_attribute(Z_OBJ_P(node), key);
	if (value != NULL) {
		ZVAL_DEREF(value);
	}
	return value;
}

/* $node->getAttribute($key) on a value the twin calls it on: the Error of a
 * member call on a non-object; false = pending exception, value NULL for
 * null */
inline bool getAttribute(zval *node, zend_string *key, zval *&value)
{
	if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getAttribute() on %s", zend_zval_value_name(node));
		return false;
	}
	value = nodeAttribute(node, key);
	if (value != NULL && Z_TYPE_P(value) == IS_NULL) value = NULL;
	return true;
}

/* }}} */

/* {{{ the parameter and acceptor getters: a site per getter, the
 * DummyParameter / ExtendedDummyParameter slot where it has one */

enum ParameterGetter
{
	PG_NAME,
	PG_TYPE,
	PG_OPTIONAL,
	PG_VARIADIC,
	PG_PASSED_BY_REFERENCE,
	PG_DEFAULT_VALUE,
	PG_NATIVE_TYPE,
	PG_PHPDOC_TYPE,
	PG_OUT_TYPE,
	PG_IMMEDIATELY_INVOKED_CALLABLE,
	PG_CLOSURE_THIS_TYPE,
	PG_ATTRIBUTES,
	PG_ALLOWED_CONSTANTS,
	PG_PURE_UNLESS_CALLABLE_IS_IMPURE,
	PG_COUNT,
};

/* the PT_PR_* member of each parameter getter */
const pt_parameter_reflection_member pt_pas_parameter_members[PG_COUNT] = {
	PT_PR_GET_NAME,
	PT_PR_GET_TYPE,
	PT_PR_IS_OPTIONAL,
	PT_PR_IS_VARIADIC,
	PT_PR_PASSED_BY_REFERENCE,
	PT_PR_GET_DEFAULT_VALUE,
	PT_PR_GET_NATIVE_TYPE,
	PT_PR_GET_PHPDOC_TYPE,
	PT_PR_GET_OUT_TYPE,
	PT_PR_IS_IMMEDIATELY_INVOKED_CALLABLE,
	PT_PR_GET_CLOSURE_THIS_TYPE,
	PT_PR_GET_ATTRIBUTES,
	PT_PR_GET_ALLOWED_CONSTANTS,
	PT_PR_IS_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETER,
};

/* $parameter->getX() (ParameterValues.h) */
zv::Val parameterGet(zval *parameter, ParameterGetter getter)
{
	return pt_parameter_reflection_call(parameter, pt_pas_parameter_members[getter]);
}

/* a bool getter's truthiness; false = pending exception */
bool parameterBool(zval *parameter, ParameterGetter getter, bool &out)
{
	return pt_parameter_reflection_bool(parameter, pt_pas_parameter_members[getter], out);
}

enum AcceptorGetter
{
	AG_PARAMETERS = PT_PA_GET_PARAMETERS,
	AG_VARIADIC = PT_PA_IS_VARIADIC,
	AG_RETURN_TYPE = PT_PA_GET_RETURN_TYPE,
	AG_TEMPLATE_TYPE_MAP = PT_PA_GET_TEMPLATE_TYPE_MAP,
	AG_RESOLVED_TEMPLATE_TYPE_MAP = PT_PA_GET_RESOLVED_TEMPLATE_TYPE_MAP,
	AG_CALL_SITE_VARIANCE_MAP = PT_PA_GET_CALL_SITE_VARIANCE_MAP,
	AG_PHPDOC_RETURN_TYPE = PT_PA_GET_PHPDOC_RETURN_TYPE,
	AG_NATIVE_RETURN_TYPE = PT_PA_GET_NATIVE_RETURN_TYPE,
	AG_THROW_POINTS = PT_PA_GET_THROW_POINTS,
	AG_IS_PURE = PT_PA_IS_PURE,
	AG_IMPURE_POINTS = PT_PA_GET_IMPURE_POINTS,
	AG_INVALIDATE_EXPRESSIONS = PT_PA_GET_INVALIDATE_EXPRESSIONS,
	AG_USED_VARIABLES = PT_PA_GET_USED_VARIABLES,
	AG_ACCEPTS_NAMED_ARGUMENTS = PT_PA_ACCEPTS_NAMED_ARGUMENTS,
	AG_MUST_USE_RETURN_VALUE = PT_PA_MUST_USE_RETURN_VALUE,
	AG_ASSERTS = PT_PA_GET_ASSERTS,
	AG_IS_STATIC_CLOSURE = PT_PA_IS_STATIC_CLOSURE,
};

/* $acceptor->getX() (AcceptorValues.h) */
zv::Val acceptorGet(zval *acceptor, AcceptorGetter getter)
{
	return pt_parameters_acceptor_call(acceptor, (pt_parameters_acceptor_member) getter);
}

/* $acceptor->getParameters() as an array; UNDEF = pending exception */
zv::Val acceptorParameters(zval *acceptor)
{
	zv::Val parameters = acceptorGet(acceptor, AG_PARAMETERS);
	if (UNEXPECTED(parameters.isUndef() || !requireArray(parameters.raw(), "getParameters()"))) return zv::Val();
	return parameters;
}

/* $acceptor->isVariadic(); false = pending exception */
bool acceptorIsVariadic(zval *acceptor, bool &out)
{
	zv::Val value = acceptorGet(acceptor, AG_VARIADIC);
	if (UNEXPECTED(value.isUndef())) return false;
	out = zend_is_true(value.raw());
	return true;
}

/* GenericParametersAcceptorResolver::resolve($types, $acceptor) */
pt_method_site pt_pas_generic_resolve_site;

zv::Val genericResolve(zval *types, zval *acceptor)
{
	zv::Args argv{types, acceptor};
	return pt_call_static_cached(pt_pas_generic_resolve_site, PT_CLASS_GENERIC_PARAMETERS_ACCEPTOR_RESOLVER, PT_LC("resolve"), 2, argv);
}

/* }}} */

} // namespace

namespace {

pt_property_site pt_pas_arg_name_site;
pt_property_site pt_pas_arg_value_site;
pt_property_site pt_pas_arg_unpack_site;
pt_property_site pt_pas_identifier_name_site;
pt_property_site pt_pas_variable_name_site;

/* getCurlOptValueType()'s constant-name tables, in the twin's order */
const char *const pt_pas_curl_boolConstants[] = {
	"CURLOPT_AUTOREFERER",
	"CURLOPT_COOKIESESSION",
	"CURLOPT_CERTINFO",
	"CURLOPT_CONNECT_ONLY",
	"CURLOPT_CRLF",
	"CURLOPT_DISALLOW_USERNAME_IN_URL",
	"CURLOPT_DNS_SHUFFLE_ADDRESSES",
	"CURLOPT_HAPROXYPROTOCOL",
	"CURLOPT_SSH_COMPRESSION",
	"CURLOPT_DNS_USE_GLOBAL_CACHE",
	"CURLOPT_FAILONERROR",
	"CURLOPT_SSL_FALSESTART",
	"CURLOPT_FILETIME",
	"CURLOPT_FOLLOWLOCATION",
	"CURLOPT_FORBID_REUSE",
	"CURLOPT_FRESH_CONNECT",
	"CURLOPT_FTP_USE_EPRT",
	"CURLOPT_FTP_USE_EPSV",
	"CURLOPT_FTP_CREATE_MISSING_DIRS",
	"CURLOPT_FTPAPPEND",
	"CURLOPT_TCP_NODELAY",
	"CURLOPT_FTPASCII",
	"CURLOPT_FTPLISTONLY",
	"CURLOPT_HEADER",
	"CURLOPT_HTTP09_ALLOWED",
	"CURLOPT_HTTPGET",
	"CURLOPT_HTTPPROXYTUNNEL",
	"CURLOPT_HTTP_CONTENT_DECODING",
	"CURLOPT_KEEP_SENDING_ON_ERROR",
	"CURLOPT_MUTE",
	"CURLOPT_NETRC",
	"CURLOPT_NOBODY",
	"CURLOPT_NOPROGRESS",
	"CURLOPT_NOSIGNAL",
	"CURLOPT_PATH_AS_IS",
	"CURLOPT_PIPEWAIT",
	"CURLOPT_POST",
	"CURLOPT_PUT",
	"CURLOPT_RETURNTRANSFER",
	"CURLOPT_SASL_IR",
	"CURLOPT_SSL_ENABLE_ALPN",
	"CURLOPT_SSL_ENABLE_NPN",
	"CURLOPT_SSL_VERIFYPEER",
	"CURLOPT_SSL_VERIFYSTATUS",
	"CURLOPT_PROXY_SSL_VERIFYPEER",
	"CURLOPT_SUPPRESS_CONNECT_HEADERS",
	"CURLOPT_TCP_FASTOPEN",
	"CURLOPT_TFTP_NO_OPTIONS",
	"CURLOPT_TRANSFERTEXT",
	"CURLOPT_UNRESTRICTED_AUTH",
	"CURLOPT_UPLOAD",
	"CURLOPT_VERBOSE"
};

const char *const pt_pas_curl_intConstants[] = {
	"CURLOPT_BUFFERSIZE",
	"CURLOPT_CONNECTTIMEOUT",
	"CURLOPT_CONNECTTIMEOUT_MS",
	"CURLOPT_DNS_CACHE_TIMEOUT",
	"CURLOPT_EXPECT_100_TIMEOUT_MS",
	"CURLOPT_HAPPY_EYEBALLS_TIMEOUT_MS",
	"CURLOPT_FTPSSLAUTH",
	"CURLOPT_HEADEROPT",
	"CURLOPT_HTTP_VERSION",
	"CURLOPT_HTTPAUTH",
	"CURLOPT_INFILESIZE",
	"CURLOPT_LOW_SPEED_LIMIT",
	"CURLOPT_LOW_SPEED_TIME",
	"CURLOPT_MAXCONNECTS",
	"CURLOPT_MAXREDIRS",
	"CURLOPT_PORT",
	"CURLOPT_POSTREDIR",
	"CURLOPT_PROTOCOLS",
	"CURLOPT_PROXYAUTH",
	"CURLOPT_PROXYPORT",
	"CURLOPT_PROXYTYPE",
	"CURLOPT_REDIR_PROTOCOLS",
	"CURLOPT_RESUME_FROM",
	"CURLOPT_SOCKS5_AUTH",
	"CURLOPT_SSL_OPTIONS",
	"CURLOPT_SSL_VERIFYHOST",
	"CURLOPT_SSLVERSION",
	"CURLOPT_PROXY_SSL_OPTIONS",
	"CURLOPT_PROXY_SSL_VERIFYHOST",
	"CURLOPT_PROXY_SSLVERSION",
	"CURLOPT_STREAM_WEIGHT",
	"CURLOPT_TCP_KEEPALIVE",
	"CURLOPT_TCP_KEEPIDLE",
	"CURLOPT_TCP_KEEPINTVL",
	"CURLOPT_TIMECONDITION",
	"CURLOPT_TIMEOUT",
	"CURLOPT_TIMEOUT_MS",
	"CURLOPT_TIMEVALUE",
	"CURLOPT_TIMEVALUE_LARGE",
	"CURLOPT_MAX_RECV_SPEED_LARGE",
	"CURLOPT_SSH_AUTH_TYPES",
	"CURLOPT_IPRESOLVE",
	"CURLOPT_FTP_FILEMETHOD"
};

const char *const pt_pas_curl_nullableStringConstants[] = {
	"CURLOPT_CUSTOMREQUEST",
	"CURLOPT_DNS_INTERFACE",
	"CURLOPT_DNS_LOCAL_IP4",
	"CURLOPT_DNS_LOCAL_IP6",
	"CURLOPT_DOH_URL",
	"CURLOPT_FTP_ACCOUNT",
	"CURLOPT_FTPPORT",
	"CURLOPT_HSTS",
	"CURLOPT_KRBLEVEL",
	"CURLOPT_RANGE",
	"CURLOPT_RTSP_SESSION_ID",
	"CURLOPT_UNIX_SOCKET_PATH",
	"CURLOPT_XOAUTH2_BEARER"
};

const char *const pt_pas_curl_nonEmptyStringConstants[] = {
	"CURLOPT_ABSTRACT_UNIX_SOCKET",
	"CURLOPT_ALTSVC",
	"CURLOPT_AWS_SIGV4",
	"CURLOPT_CAINFO",
	"CURLOPT_CAPATH",
	"CURLOPT_COOKIE",
	"CURLOPT_COOKIEJAR",
	"CURLOPT_COOKIELIST",
	"CURLOPT_DEFAULT_PROTOCOL",
	"CURLOPT_DNS_SERVERS",
	"CURLOPT_EGDSOCKET",
	"CURLOPT_FTP_ALTERNATIVE_TO_USER",
	"CURLOPT_INTERFACE",
	"CURLOPT_KEYPASSWD",
	"CURLOPT_KRB4LEVEL",
	"CURLOPT_LOGIN_OPTIONS",
	"CURLOPT_MAIL_AUTH",
	"CURLOPT_MAIL_FROM",
	"CURLOPT_NOPROXY",
	"CURLOPT_PASSWORD",
	"CURLOPT_PINNEDPUBLICKEY",
	"CURLOPT_PROTOCOLS_STR",
	"CURLOPT_PROXY_CAINFO",
	"CURLOPT_PROXY_CAPATH",
	"CURLOPT_PROXY_CRLFILE",
	"CURLOPT_PROXY_ISSUERCERT",
	"CURLOPT_PROXY_KEYPASSWD",
	"CURLOPT_PROXY_PINNEDPUBLICKEY",
	"CURLOPT_PROXY_SERVICE_NAME",
	"CURLOPT_PROXY_SSL_CIPHER_LIST",
	"CURLOPT_PROXY_SSLCERT",
	"CURLOPT_PROXY_SSLCERTTYPE",
	"CURLOPT_PROXY_SSLKEY",
	"CURLOPT_PROXY_SSLKEYTYPE",
	"CURLOPT_PROXY_TLS13_CIPHERS",
	"CURLOPT_PROXY_TLSAUTH_PASSWORD",
	"CURLOPT_PROXY_TLSAUTH_TYPE",
	"CURLOPT_PROXY_TLSAUTH_USERNAME",
	"CURLOPT_PROXYPASSWORD",
	"CURLOPT_PROXYUSERNAME",
	"CURLOPT_PROXYUSERPWD",
	"CURLOPT_RANDOM_FILE",
	"CURLOPT_REDIR_PROTOCOLS_STR",
	"CURLOPT_REFERER",
	"CURLOPT_REQUEST_TARGET",
	"CURLOPT_RTSP_STREAM_URI",
	"CURLOPT_RTSP_TRANSPORT",
	"CURLOPT_SASL_AUTHZID",
	"CURLOPT_SERVICE_NAME",
	"CURLOPT_SOCKS5_GSSAPI_SERVICE",
	"CURLOPT_SSH_HOST_PUBLIC_KEY_MD5",
	"CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256",
	"CURLOPT_SSH_PRIVATE_KEYFILE",
	"CURLOPT_SSH_PUBLIC_KEYFILE",
	"CURLOPT_SSL_CIPHER_LIST",
	"CURLOPT_SSL_EC_CURVES",
	"CURLOPT_SSLCERT",
	"CURLOPT_SSLCERTPASSWD",
	"CURLOPT_SSLCERTTYPE",
	"CURLOPT_SSLENGINE",
	"CURLOPT_SSLENGINE_DEFAULT",
	"CURLOPT_SSLKEY",
	"CURLOPT_SSLKEYPASSWD",
	"CURLOPT_SSLKEYTYPE",
	"CURLOPT_TLS13_CIPHERS",
	"CURLOPT_TLSAUTH_PASSWORD",
	"CURLOPT_TLSAUTH_TYPE",
	"CURLOPT_TLSAUTH_USERNAME",
	"CURLOPT_TRANSFER_ENCODING",
	"CURLOPT_URL",
	"CURLOPT_USERAGENT",
	"CURLOPT_USERNAME",
	"CURLOPT_USERPWD"
};

const char *const pt_pas_curl_stringConstants[] = {
	"CURLOPT_COOKIEFILE",
	"CURLOPT_ENCODING",
	"CURLOPT_PRE_PROXY",
	"CURLOPT_PRIVATE",
	"CURLOPT_PROXY"
};

const char *const pt_pas_curl_intArrayStringKeysConstants[] = {
	"CURLOPT_HTTPHEADER"
};

const char *const pt_pas_curl_arrayConstants[] = {
	"CURLOPT_CONNECT_TO",
	"CURLOPT_HTTP200ALIASES",
	"CURLOPT_POSTQUOTE",
	"CURLOPT_PROXYHEADER",
	"CURLOPT_QUOTE",
	"CURLOPT_RESOLVE"
};

const char *const pt_pas_curl_arrayOrStringConstants[] = {
	"CURLOPT_POSTFIELDS"
};

const char *const pt_pas_curl_resourceConstants[] = {
	"CURLOPT_FILE",
	"CURLOPT_INFILE",
	"CURLOPT_STDERR",
	"CURLOPT_WRITEHEADER"
};

/* `defined($name) && constant($name) === $curlOpt` over a table */
inline bool curlConstantIn(const char *const *names, size_t count, zend_long curlOpt)
{
	for (size_t k = 0; k < count; k++) {
		zval *value = zend_get_constant_str(names[k], strlen(names[k]));
		if (value != NULL && Z_TYPE_P(value) == IS_LONG && Z_LVAL_P(value) == curlOpt) return true;
	}
	return false;
}

#define PT_PAS_CURL_IN(table, curlOpt) curlConstantIn(table, sizeof(table) / sizeof(table[0]), curlOpt)

/* $target = array_merge($target, $source) */
bool arrayMerge(zv::Arr &target, zval *source)
{
	if (UNEXPECTED(Z_TYPE_P(source) != IS_ARRAY)) {
		zend_type_error("array_merge(): Argument #2 must be of type array, %s given", zend_zval_value_name(source));
		return false;
	}
	HashTable *src = Z_ARRVAL_P(source);
	if (zend_hash_num_elements(src) == 0) return true;
	if (zend_hash_num_elements(target.table()) == 0 && zend_array_is_list(src)) {
		target = zv::Arr::copyOfTable(src);
		return true;
	}
	target.separate();
	HashTable *dest = target.table();
	zend_string *key;
	zval *entry;
	ZEND_HASH_FOREACH_STR_KEY_VAL(src, key, entry) {
		zval *value = entry;
		if (Z_ISREF_P(value) && Z_REFCOUNT_P(value) == 1) {
			value = Z_REFVAL_P(value);
		}
		Z_TRY_ADDREF_P(value);
		if (key != NULL) {
			zend_hash_update(dest, key, value);
		} else {
			zend_hash_next_index_insert_new(dest, value);
		}
	} ZEND_HASH_FOREACH_END();
	return true;
}

/* TypeCombinator::union($a, $b) */
zv::Val union2(zval *a, zval *b)
{
	zv::Args argv{a, b};
	return pt_type_combinator_union(2, argv);
}

/* TypeCombinator::union(...$types) over a list */
zv::Val unionOf(zv::Arr &types)
{
	HashTable *table = types.table();
	uint32_t count = zend_hash_num_elements(table);
	if (count == 0) return pt_type_combinator_union(0, NULL);
	return pt_type_combinator_union(count, table->arPacked);
}

/* throw new ShouldNotHappenException($message) */
void throwShouldNotHappen(const char *message)
{
	zval messageZv;
	ZVAL_STRING(&messageZv, message);
	zv::Val exception = pt_type_new(PT_CLASS_SHOULD_NOT_HAPPEN, 1, &messageZv);
	zval_ptr_dtor(&messageZv);
	if (UNEXPECTED(exception.isUndef())) return;
	zval raw = exception.take();
	zend_throw_exception_object(&raw);
}

/* a Type-typed value the twin calls a method on */
inline bool requireObject(zval *value, const char *method)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT)) return true;
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
	return false;
}

/* $type->hasTemplateOrLateResolvableType(); false = pending exception */
bool hasTemplateOrLateResolvableType(zval *type, bool &out)
{
	if (UNEXPECTED(!requireObject(type, "hasTemplateOrLateResolvableType"))) return false;
	zv::Val has = pt_type_op(Z_OBJ_P(type), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
	if (UNEXPECTED(has.isUndef())) return false;
	out = zend_is_true(has.raw());
	return true;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\ParametersAcceptorSelector; UNDEF = pending
 * exception. */
class ParametersAcceptorSelector
{
public:
	/* the four getters applyIntrinsicArgOverrides() reads types through:
	 * callables, or (callables NULL) the scope's own reads */
	struct Getters
	{
		zval *scope;
		zval *typeGetter;
		zval *nativeTypeGetter;
		zval *iterableValueTypeGetter;
		zval *iterableKeyTypeGetter;

		zv::Val type(zval *expr) const
		{
			if (typeGetter != NULL) return pt_type_call_callable(typeGetter, 1, expr);
			return pt_mutating_scope_get_type(Z_OBJ_P(scope), expr);
		}

		zv::Val nativeType(zval *expr) const
		{
			if (nativeTypeGetter != NULL) return pt_type_call_callable(nativeTypeGetter, 1, expr);
			return pt_mutating_scope_get_native_type(Z_OBJ_P(scope), expr);
		}

		zv::Val iterableValueType(zval *type) const
		{
			if (iterableValueTypeGetter != NULL) return pt_type_call_callable(iterableValueTypeGetter, 1, type);
			return pt_mutating_scope_get_iterable_value_type(Z_OBJ_P(scope), type);
		}

		zv::Val iterableKeyType(zval *type) const
		{
			if (iterableKeyTypeGetter != NULL) return pt_type_call_callable(iterableKeyTypeGetter, 1, type);
			return pt_mutating_scope_get_iterable_key_type(Z_OBJ_P(scope), type);
		}
	};

	/* Mirrors hasAcceptorTemplateOrLateResolvableType(); false = pending exception */
	static bool hasAcceptorTemplateOrLateResolvableType(zval *acceptor, bool &out)
	{
		zv::Val returnType = acceptorGet(acceptor, AG_RETURN_TYPE);
		if (UNEXPECTED(returnType.isUndef())) return false;
		bool has = false;
		if (UNEXPECTED(!hasTemplateOrLateResolvableType(returnType.raw(), has))) return false;
		if (has) {
			out = true;
			return true;
		}
		return hasAcceptorTemplateOrLateResolvableParameterType(acceptor, out);
	}

	/* Mirrors hasAcceptorTemplateOrLateResolvableParameterType(); false = pending exception */
	static bool hasAcceptorTemplateOrLateResolvableParameterType(zval *acceptor, bool &out)
	{
		out = false;
		zv::Val parameters = acceptorParameters(acceptor);
		if (UNEXPECTED(parameters.isUndef())) return false;
		zend_class_entry *extendedCe = pt_class(PT_CLASS_EXTENDED_PARAMETER_REFLECTION);
		if (UNEXPECTED(extendedCe == NULL)) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(parameters.raw())) {
			zval *parameter = entry.value().deref().raw();
			bool isExtended = Z_TYPE_P(parameter) == IS_OBJECT && instanceof_function(Z_OBJCE_P(parameter), extendedCe);
			if (isExtended) {
				bool has = false;
				if (UNEXPECTED(!optionalTypeHasTemplate(parameter, PG_OUT_TYPE, has))) return false;
				if (has) {
					out = true;
					return true;
				}
				if (UNEXPECTED(!optionalTypeHasTemplate(parameter, PG_CLOSURE_THIS_TYPE, has))) return false;
				if (has) {
					out = true;
					return true;
				}
			}

			zv::Val type = parameterGet(parameter, PG_TYPE);
			if (UNEXPECTED(type.isUndef())) return false;
			bool has = false;
			if (UNEXPECTED(!hasTemplateOrLateResolvableType(type.raw(), has))) return false;
			if (!has) continue;

			out = true;
			return true;
		}
		return true;
	}

	/* Mirrors selectFromTypes() */
	static zv::Val selectFromTypes(zval *types, zval *parametersAcceptors, bool unpack)
	{
		HashTable *acceptors = Z_ARRVAL_P(parametersAcceptors);
		uint32_t acceptorCount = zend_hash_num_elements(acceptors);
		if (acceptorCount == 1) {
			zval *acceptor = readIndex(acceptors, 0);
			if (UNEXPECTED(acceptor == NULL)) return zv::Val();
			return genericResolve(types, acceptor);
		}

		if (acceptorCount == 0) {
			throwShouldNotHappen("getVariants() must return at least one variant.");
			return zv::Val();
		}

		HashTable *typesTable = Z_ARRVAL_P(types);
		zend_long typesCount = zend_hash_num_elements(typesTable);
		zv::Arr acceptableAcceptors = zv::Arr::empty();

		for (zv::ArrayEntry entry : zv::TableRef(acceptors)) {
			zval *parametersAcceptor = entry.value().deref().raw();
			if (unpack) {
				acceptableAcceptors.push(zv::Ref(parametersAcceptor));
				continue;
			}

			zend_long functionParametersMinCount = 0;
			zend_long functionParametersMaxCount = 0;
			zv::Val parameters = acceptorParameters(parametersAcceptor);
			if (UNEXPECTED(parameters.isUndef())) return zv::Val();
			for (zv::ArrayEntry parameterEntry : zv::ArrRef(parameters.raw())) {
				bool optional = false;
				if (UNEXPECTED(!parameterBool(parameterEntry.value().deref().raw(), PG_OPTIONAL, optional))) return zv::Val();
				if (!optional) {
					functionParametersMinCount++;
				}

				functionParametersMaxCount++;
			}

			if (typesCount < functionParametersMinCount) continue;

			bool isVariadic = false;
			if (UNEXPECTED(!acceptorIsVariadic(parametersAcceptor, isVariadic))) return zv::Val();
			if (!isVariadic && typesCount > functionParametersMaxCount) continue;

			acceptableAcceptors.push(zv::Ref(parametersAcceptor));
		}

		uint32_t acceptableCount = zend_hash_num_elements(acceptableAcceptors.table());
		if (acceptableCount == 0) {
			zv::Val combined = combineAcceptors(parametersAcceptors);
			if (UNEXPECTED(combined.isUndef())) return zv::Val();
			return genericResolve(types, combined.raw());
		}

		if (acceptableCount == 1) return genericResolve(types, zend_hash_index_find(acceptableAcceptors.table(), 0));

		zv::Arr winningAcceptors = zv::Arr::empty();
		zend_long winningCertainty = -1; /* null */
		zend_class_entry *mixedCe = pt_ce_mixed_type;
		for (zv::ArrayEntry entry : zv::TableRef(acceptableAcceptors.table())) {
			zend_long isSuperType = PT_TRI_YES;
			zv::Val acceptableAcceptor = genericResolve(types, entry.value().raw());
			if (UNEXPECTED(acceptableAcceptor.isUndef())) return zv::Val();
			zv::Val parameters = acceptorParameters(acceptableAcceptor.raw());
			if (UNEXPECTED(parameters.isUndef())) return zv::Val();
			for (zv::ArrayEntry parameterEntry : zv::ArrRef(parameters.raw())) {
				zval *parameter = parameterEntry.value().deref().raw();
				zval *type = parameterEntry.stringKeyOrNull() != NULL ? NULL : issetIndex(typesTable, parameterEntry.indexKey());
				if (parameterEntry.stringKeyOrNull() != NULL) {
					zval *found = zend_symtable_find(typesTable, parameterEntry.stringKeyOrNull());
					if (found != NULL) {
						ZVAL_DEREF(found);
						type = Z_TYPE_P(found) == IS_NULL ? NULL : found;
					}
				}
				if (type == NULL) {
					if (!unpack || typesCount <= 0) break;

					type = arrayLast(typesTable);
				}

				zv::Val parameterType = parameterGet(parameter, PG_TYPE);
				if (UNEXPECTED(parameterType.isUndef())) return zv::Val();
				if (Z_TYPE_P(parameterType.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(parameterType.raw()), mixedCe)) {
					isSuperType = pt_trinary_and(isSuperType, PT_TRI_MAYBE);
				} else {
					zv::Val superType = parameterGet(parameter, PG_TYPE);
					if (UNEXPECTED(superType.isUndef() || !requireObject(superType.raw(), "isSuperTypeOf"))) return zv::Val();
					zv::Val result = pt_type_op(Z_OBJ_P(superType.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, type);
					if (UNEXPECTED(result.isUndef())) return zv::Val();
					zend_long value = pt_type_result_trinary(result.raw());
					if (UNEXPECTED(value < 0)) return zv::Val();
					isSuperType = pt_trinary_and(isSuperType, value);
				}
			}

			if (isSuperType == PT_TRI_NO) continue;

			if (winningCertainty < 0) {
				winningAcceptors.push(std::move(acceptableAcceptor));
				winningCertainty = isSuperType;
			} else if (winningCertainty < isSuperType) {
				/* $winningCertainty->compareTo($isSuperType) === $isSuperType */
				winningAcceptors = zv::Arr::create(1);
				winningAcceptors.push(std::move(acceptableAcceptor));
				winningCertainty = isSuperType;
			} else if (winningCertainty == isSuperType) {
				/* compareTo() === null */
				winningAcceptors.push(std::move(acceptableAcceptor));
			}
		}

		zv::Val combined = combineAcceptors(zend_hash_num_elements(winningAcceptors.table()) == 0 ? acceptableAcceptors.raw() : winningAcceptors.raw());
		if (UNEXPECTED(combined.isUndef())) return zv::Val();
		return genericResolve(types, combined.raw());
	}

	/* Mirrors combineVariantsForNormalization() */
	static zv::Val combineVariantsForNormalization(zval *args, zval *variants, zval *namedArgumentsVariants)
	{
		bool hasName = false;
		for (zv::ArrayEntry entry : zv::ArrRef(args)) {
			zv::Val nameHold;
			zval *name = readProperty(pt_pas_arg_name_site, entry.value().deref().raw(), PT_LC("name"), nameHold);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (Z_TYPE_P(name) != IS_NULL) {
				hasName = true;
				break;
			}
		}

		zval *selectedVariants = hasName && namedArgumentsVariants != NULL && Z_TYPE_P(namedArgumentsVariants) != IS_NULL ? namedArgumentsVariants : variants;
		if (zend_hash_num_elements(Z_ARRVAL_P(selectedVariants)) == 1) {
			zval *variant = readIndex(Z_ARRVAL_P(selectedVariants), 0);
			if (UNEXPECTED(variant == NULL)) return zv::Val();
			return zv::Val::copyOf(zv::Ref(variant));
		}
		return combineAcceptors(selectedVariants);
	}

private:
	/* `$parameter->getX() !== null && $parameter->getX()->hasTemplateOrLateResolvableType()`
	 * — the getter asked twice, as the twin asks it; false = pending exception */
	static bool optionalTypeHasTemplate(zval *parameter, ParameterGetter getter, bool &out)
	{
		out = false;
		zv::Val first = parameterGet(parameter, getter);
		if (UNEXPECTED(first.isUndef())) return false;
		if (first.isNull()) return true;
		zv::Val second = parameterGet(parameter, getter);
		if (UNEXPECTED(second.isUndef())) return false;
		return hasTemplateOrLateResolvableType(second.raw(), out);
	}

public:
	/* Mirrors combineAcceptors() */
	static zv::Val combineAcceptors(zval *acceptorsZv)
	{
		HashTable *acceptors = Z_ARRVAL_P(acceptorsZv);
		uint32_t acceptorCount = zend_hash_num_elements(acceptors);
		if (acceptorCount == 0) {
			throwShouldNotHappen("getVariants() must return at least one variant.");
			return zv::Val();
		}
		if (acceptorCount == 1) {
			zval *acceptor = readIndex(acceptors, 0);
			if (UNEXPECTED(acceptor == NULL)) return zv::Val();
			return wrapAcceptor(acceptor);
		}

		zend_class_entry *extendedParameterCe = pt_class(PT_CLASS_EXTENDED_PARAMETER_REFLECTION);
		zend_class_entry *extendedAcceptorCe = pt_class(PT_CLASS_EXTENDED_PARAMETERS_ACCEPTOR);
		zend_class_entry *callableAcceptorCe = pt_class(PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR);
		if (UNEXPECTED(extendedParameterCe == NULL || extendedAcceptorCe == NULL || callableAcceptorCe == NULL)) return zv::Val();

		zend_long minimumNumberOfParameters = -1; /* null */
		for (zv::ArrayEntry entry : zv::TableRef(acceptors)) {
			zend_long acceptorParametersMinCount = 0;
			zv::Val parameters = acceptorParameters(entry.value().deref().raw());
			if (UNEXPECTED(parameters.isUndef())) return zv::Val();
			for (zv::ArrayEntry parameterEntry : zv::ArrRef(parameters.raw())) {
				bool optional = false;
				if (UNEXPECTED(!parameterBool(parameterEntry.value().deref().raw(), PG_OPTIONAL, optional))) return zv::Val();
				if (optional) continue;

				acceptorParametersMinCount++;
			}

			if (minimumNumberOfParameters >= 0 && minimumNumberOfParameters <= acceptorParametersMinCount) continue;

			minimumNumberOfParameters = acceptorParametersMinCount;
		}

		zv::Arr parameters = zv::Arr::empty();
		bool isVariadic = false;
		zv::Arr returnTypes = zv::Arr::empty();
		zv::Arr phpDocReturnTypes = zv::Arr::empty();
		zv::Arr nativeReturnTypes = zv::Arr::empty();
		bool callableOccurred = false;
		zv::Arr throwPoints = zv::Arr::empty();
		zend_long isPure = PT_TRI_NO;
		zv::Arr impurePoints = zv::Arr::empty();
		zv::Arr invalidateExpressions = zv::Arr::empty();
		zv::Arr usedVariables = zv::Arr::empty();
		zend_long acceptsNamedArguments = PT_TRI_NO;
		zend_long mustUseReturnValue = PT_TRI_MAYBE;
		zend_long isStaticClosure = PT_TRI_MAYBE;

		for (zv::ArrayEntry entry : zv::TableRef(acceptors)) {
			zval *acceptor = entry.value().deref().raw();
			zv::Val returnType = acceptorGet(acceptor, AG_RETURN_TYPE);
			if (UNEXPECTED(returnType.isUndef())) return zv::Val();
			returnTypes.push(std::move(returnType));

			if (Z_TYPE_P(acceptor) == IS_OBJECT && instanceof_function(Z_OBJCE_P(acceptor), extendedAcceptorCe)) {
				zv::Val phpDocReturnType = acceptorGet(acceptor, AG_PHPDOC_RETURN_TYPE);
				if (UNEXPECTED(phpDocReturnType.isUndef())) return zv::Val();
				phpDocReturnTypes.push(std::move(phpDocReturnType));
				zv::Val nativeReturnType = acceptorGet(acceptor, AG_NATIVE_RETURN_TYPE);
				if (UNEXPECTED(nativeReturnType.isUndef())) return zv::Val();
				nativeReturnTypes.push(std::move(nativeReturnType));
			}
			if (Z_TYPE_P(acceptor) == IS_OBJECT && instanceof_function(Z_OBJCE_P(acceptor), callableAcceptorCe)) {
				callableOccurred = true;
				zv::Val acceptorThrowPoints = acceptorGet(acceptor, AG_THROW_POINTS);
				if (UNEXPECTED(acceptorThrowPoints.isUndef() || !arrayMerge(throwPoints, acceptorThrowPoints.raw()))) return zv::Val();
				zv::Val acceptorIsPure = acceptorGet(acceptor, AG_IS_PURE);
				zend_long isPureValue = trinaryOf(acceptorIsPure);
				if (UNEXPECTED(isPureValue < 0)) return zv::Val();
				isPure = pt_trinary_or(isPure, isPureValue);
				zv::Val acceptorImpurePoints = acceptorGet(acceptor, AG_IMPURE_POINTS);
				if (UNEXPECTED(acceptorImpurePoints.isUndef() || !arrayMerge(impurePoints, acceptorImpurePoints.raw()))) return zv::Val();
				zv::Val acceptorInvalidateExpressions = acceptorGet(acceptor, AG_INVALIDATE_EXPRESSIONS);
				if (UNEXPECTED(acceptorInvalidateExpressions.isUndef() || !arrayMerge(invalidateExpressions, acceptorInvalidateExpressions.raw()))) return zv::Val();
				zv::Val acceptorUsedVariables = acceptorGet(acceptor, AG_USED_VARIABLES);
				if (UNEXPECTED(acceptorUsedVariables.isUndef() || !arrayMerge(usedVariables, acceptorUsedVariables.raw()))) return zv::Val();
				zv::Val accepts = acceptorGet(acceptor, AG_ACCEPTS_NAMED_ARGUMENTS);
				zend_long acceptsValue = trinaryOf(accepts);
				if (UNEXPECTED(acceptsValue < 0)) return zv::Val();
				acceptsNamedArguments = pt_trinary_or(acceptsNamedArguments, acceptsValue);
				zv::Val mustUse = acceptorGet(acceptor, AG_MUST_USE_RETURN_VALUE);
				zend_long mustUseValue = trinaryOf(mustUse);
				if (UNEXPECTED(mustUseValue < 0)) return zv::Val();
				mustUseReturnValue = pt_trinary_or(mustUseReturnValue, mustUseValue);
				zv::Val isStatic = acceptorGet(acceptor, AG_IS_STATIC_CLOSURE);
				zend_long isStaticValue = trinaryOf(isStatic);
				if (UNEXPECTED(isStaticValue < 0)) return zv::Val();
				isStaticClosure = pt_trinary_or(isStaticClosure, isStaticValue);
			}
			if (!isVariadic && UNEXPECTED(!acceptorIsVariadic(acceptor, isVariadic))) return zv::Val();

			zv::Val acceptorParameterList = acceptorParameters(acceptor);
			if (UNEXPECTED(acceptorParameterList.isUndef())) return zv::Val();
			for (zv::ArrayEntry parameterEntry : zv::ArrRef(acceptorParameterList.raw())) {
				zval *parameter = parameterEntry.value().deref().raw();
				zend_string *stringKey = parameterEntry.stringKeyOrNull();
				zend_ulong i = parameterEntry.indexKey();
				zval *existing = stringKey != NULL ? zend_symtable_find(parameters.table(), stringKey) : zend_hash_index_find(parameters.table(), i);
				if (existing != NULL) {
					ZVAL_DEREF(existing);
					if (Z_TYPE_P(existing) == IS_NULL) existing = NULL;
				}
				bool optional = stringKey == NULL && (zend_long) i + 1 > minimumNumberOfParameters;
				bool parameterIsExtended = Z_TYPE_P(parameter) == IS_OBJECT && instanceof_function(Z_OBJCE_P(parameter), extendedParameterCe);
				if (existing == NULL) {
					zv::Val created = firstCombinedParameter(parameter, parameterIsExtended, optional);
					if (UNEXPECTED(created.isUndef())) return zv::Val();
					setEntryKey(parameters, stringKey, i, created.raw());
					continue;
				}

				bool isParameterVariadic = false;
				zv::Val combined = combinedParameter(existing, parameter, parameterIsExtended, optional, isParameterVariadic, parameters.table(), acceptorParameterList.raw(), (zend_long) i, extendedParameterCe);
				if (UNEXPECTED(combined.isUndef())) return zv::Val();
				setEntryKey(parameters, stringKey, i, combined.raw());

				if (isParameterVariadic) {
					/* array_slice($parameters, 0, $i + 1) */
					zv::Arr sliced = zv::Arr::create((uint32_t) i + 1);
					zend_long taken = 0;
					for (zv::ArrayEntry slicedEntry : zv::TableRef(parameters.table())) {
						if (taken >= (zend_long) i + 1) break;
						if (slicedEntry.stringKeyOrNull() != NULL) {
							sliced.separate();
							zval *value = slicedEntry.value().raw();
							Z_TRY_ADDREF_P(value);
							zend_hash_update(sliced.table(), slicedEntry.stringKeyOrNull(), value);
						} else {
							sliced.push(zv::Ref(slicedEntry.value().raw()));
						}
						taken++;
					}
					parameters = std::move(sliced);
					break;
				}
			}
		}

		zv::Val returnType = unionOf(returnTypes);
		if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		zv::Val phpDocReturnType;
		if (zend_hash_num_elements(phpDocReturnTypes.table()) != 0) {
			phpDocReturnType = unionOf(phpDocReturnTypes);
			if (UNEXPECTED(phpDocReturnType.isUndef())) return zv::Val();
		}
		zv::Val nativeReturnType;
		if (zend_hash_num_elements(nativeReturnTypes.table()) != 0) {
			nativeReturnType = unionOf(nativeReturnTypes);
			if (UNEXPECTED(nativeReturnType.isUndef())) return zv::Val();
		}

		zval emptyMap;
		if (UNEXPECTED(!pt_template_type_map_empty(&emptyMap))) return zv::Val();
		zv::Val emptyMapHold = zv::Val::adopt(emptyMap);
		zv::Val parameterList = arrayValues(parameters.raw());
		if (nativeReturnType.isUndef()) {
			nativeReturnType = pt_type_new_mixed_type();
			if (UNEXPECTED(nativeReturnType.isUndef())) return zv::Val();
		}
		zval null = {};
		ZVAL_NULL(&null);
		zval variadicZv = {};
		ZVAL_BOOL(&variadicZv, isVariadic);
		zval *phpDoc = phpDocReturnType.isUndef() ? returnType.raw() : phpDocReturnType.raw();

		if (callableOccurred) {
			zval argv[17];
			ZVAL_COPY_VALUE(&argv[0], emptyMapHold.raw());
			ZVAL_NULL(&argv[1]);
			ZVAL_COPY_VALUE(&argv[2], parameterList.raw());
			ZVAL_COPY_VALUE(&argv[3], &variadicZv);
			ZVAL_COPY_VALUE(&argv[4], returnType.raw());
			ZVAL_COPY_VALUE(&argv[5], phpDoc);
			ZVAL_COPY_VALUE(&argv[6], nativeReturnType.raw());
			ZVAL_NULL(&argv[7]);
			ZVAL_COPY_VALUE(&argv[8], throwPoints.raw());
			ZVAL_COPY_VALUE(&argv[9], pt_trinary_singleton(isPure));
			ZVAL_COPY_VALUE(&argv[10], impurePoints.raw());
			ZVAL_COPY_VALUE(&argv[11], invalidateExpressions.raw());
			ZVAL_COPY_VALUE(&argv[12], usedVariables.raw());
			ZVAL_COPY_VALUE(&argv[13], pt_trinary_singleton(acceptsNamedArguments));
			ZVAL_COPY_VALUE(&argv[14], pt_trinary_singleton(mustUseReturnValue));
			ZVAL_NULL(&argv[15]);
			ZVAL_COPY_VALUE(&argv[16], pt_trinary_singleton(isStaticClosure));
			return pt_extended_callable_function_variant_new(17, argv);
		}

		zv::Args argv{emptyMapHold.raw(), &null, parameterList.raw(), &variadicZv, returnType.raw(), phpDoc, nativeReturnType.raw()};
		return pt_extended_function_variant_new(7, argv);
	}

private:
	/* array_values($array) */
	static zv::Val arrayValues(zval *array)
	{
		HashTable *table = Z_ARRVAL_P(array);
		if (zend_array_is_list(table)) return zv::Val::copyOf(zv::Ref(array));
		zv::Arr values = zv::Arr::create(zend_hash_num_elements(table));
		for (zv::ArrayEntry entry : zv::TableRef(table)) {
			values.push(zv::Ref(entry.value().deref().raw()));
		}
		return zv::Val(std::move(values));
	}

	/* new ExtendedDummyParameter(...$argv) over fourteen owned values */
	static zv::Val newExtendedDummyParameter(zv::Val *values)
	{
		zval argv[14];
		for (int k = 0; k < 14; k++) {
			ZVAL_COPY_VALUE(&argv[k], values[k].raw());
		}
		return pt_extended_dummy_parameter_new(14, argv);
	}

	/* `$parameter instanceof ExtendedParameterReflection ? $parameter->getX()
	 * : <default>` */
	static zv::Val extendedOr(zval *parameter, bool isExtended, ParameterGetter getter, zv::Val (*fallback)())
	{
		if (isExtended) return parameterGet(parameter, getter);
		return fallback();
	}

	static zv::Val mixedDefault() { return pt_type_new_mixed_type(); }
	static zv::Val nullDefault() { return zv::Val::null(); }
	static zv::Val maybeDefault() { return trinary(PT_TRI_MAYBE); }
	static zv::Val noDefault() { return trinary(PT_TRI_NO); }
	static zv::Val emptyArrayDefault() { return zv::Val(zv::Arr::empty()); }

	/* the first acceptor's parameter at an index: new ExtendedDummyParameter
	 * over its getters ($i + 1 > $minimumNumberOfParameters as optional) */
	static zv::Val firstCombinedParameter(zval *parameter, bool isExtended, bool optional)
	{
		zv::Val values[14];
		values[0] = parameterGet(parameter, PG_NAME);
		if (UNEXPECTED(values[0].isUndef())) return zv::Val();
		values[1] = parameterGet(parameter, PG_TYPE);
		if (UNEXPECTED(values[1].isUndef())) return zv::Val();
		values[2] = zv::Val::boolean(optional);
		values[3] = parameterGet(parameter, PG_PASSED_BY_REFERENCE);
		if (UNEXPECTED(values[3].isUndef())) return zv::Val();
		values[4] = parameterGet(parameter, PG_VARIADIC);
		if (UNEXPECTED(values[4].isUndef())) return zv::Val();
		values[5] = parameterGet(parameter, PG_DEFAULT_VALUE);
		if (UNEXPECTED(values[5].isUndef())) return zv::Val();
		static const struct
		{
			ParameterGetter getter;
			zv::Val (*fallback)();
		} extended[8] = {
			{ PG_NATIVE_TYPE, mixedDefault },
			{ PG_PHPDOC_TYPE, mixedDefault },
			{ PG_OUT_TYPE, nullDefault },
			{ PG_IMMEDIATELY_INVOKED_CALLABLE, maybeDefault },
			{ PG_CLOSURE_THIS_TYPE, nullDefault },
			{ PG_ATTRIBUTES, emptyArrayDefault },
			{ PG_ALLOWED_CONSTANTS, nullDefault },
			{ PG_PURE_UNLESS_CALLABLE_IS_IMPURE, noDefault },
		};
		for (int k = 0; k < 8; k++) {
			values[6 + k] = extendedOr(parameter, isExtended, extended[k].getter, extended[k].fallback);
			if (UNEXPECTED(values[6 + k].isUndef())) return zv::Val();
		}
		return newExtendedDummyParameter(values);
	}

	/* a later acceptor's parameter at an index merged into the combined one
	 * ($isParameterVariadic set as the twin assigns it; $combinedParameters
	 * and $acceptorParameters are the parameters a variadic one drops) */
	static zv::Val combinedParameter(zval *existing, zval *parameter, bool isExtended, bool optional, bool &isParameterVariadic, HashTable *combinedParameters, zval *acceptorParameters, zend_long index, zend_class_entry *extendedParameterCe)
	{
		bool existingVariadic = false;
		if (UNEXPECTED(!parameterBool(existing, PG_VARIADIC, existingVariadic))) return zv::Val();
		isParameterVariadic = existingVariadic;
		if (!isParameterVariadic && UNEXPECTED(!parameterBool(parameter, PG_VARIADIC, isParameterVariadic))) return zv::Val();
		bool isVariadic = isParameterVariadic;

		zv::Val defaultValueLeft = parameterGet(existing, PG_DEFAULT_VALUE);
		if (UNEXPECTED(defaultValueLeft.isUndef())) return zv::Val();
		zv::Val defaultValueRight = parameterGet(parameter, PG_DEFAULT_VALUE);
		if (UNEXPECTED(defaultValueRight.isUndef())) return zv::Val();
		zv::Val defaultValue = zv::Val::null();
		if (!defaultValueLeft.isNull() && !defaultValueRight.isNull()) {
			defaultValue = union2(defaultValueLeft.raw(), defaultValueRight.raw());
			if (UNEXPECTED(defaultValue.isUndef())) return zv::Val();
		}

		zv::Val leftType = parameterGet(existing, PG_TYPE);
		if (UNEXPECTED(leftType.isUndef())) return zv::Val();
		zv::Val rightType = parameterGet(parameter, PG_TYPE);
		if (UNEXPECTED(rightType.isUndef())) return zv::Val();
		zv::Val type = union2(leftType.raw(), rightType.raw());
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zv::Val nativeType = parameterGet(existing, PG_NATIVE_TYPE);
		if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
		zv::Val phpDocType = parameterGet(existing, PG_PHPDOC_TYPE);
		if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
		zv::Val outType = parameterGet(existing, PG_OUT_TYPE);
		if (UNEXPECTED(outType.isUndef())) return zv::Val();
		zv::Val immediatelyInvokedCallable = parameterGet(existing, PG_IMMEDIATELY_INVOKED_CALLABLE);
		if (UNEXPECTED(immediatelyInvokedCallable.isUndef())) return zv::Val();
		zv::Val closureThisType = parameterGet(existing, PG_CLOSURE_THIS_TYPE);
		if (UNEXPECTED(closureThisType.isUndef())) return zv::Val();
		zv::Val attributes = parameterGet(existing, PG_ATTRIBUTES);
		if (UNEXPECTED(attributes.isUndef())) return zv::Val();
		if (isExtended) {
			zv::Val parameterNativeType = parameterGet(parameter, PG_NATIVE_TYPE);
			if (UNEXPECTED(parameterNativeType.isUndef())) return zv::Val();
			nativeType = union2(nativeType.raw(), parameterNativeType.raw());
			if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
			zv::Val parameterPhpDocType = parameterGet(parameter, PG_PHPDOC_TYPE);
			if (UNEXPECTED(parameterPhpDocType.isUndef())) return zv::Val();
			phpDocType = union2(phpDocType.raw(), parameterPhpDocType.raw());
			if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();

			zv::Val parameterOutType = parameterGet(parameter, PG_OUT_TYPE);
			if (UNEXPECTED(parameterOutType.isUndef())) return zv::Val();
			if (!parameterOutType.isNull()) {
				if (!outType.isNull()) {
					zv::Val again = parameterGet(parameter, PG_OUT_TYPE);
					if (UNEXPECTED(again.isUndef())) return zv::Val();
					outType = union2(outType.raw(), again.raw());
					if (UNEXPECTED(outType.isUndef())) return zv::Val();
				}
			} else {
				outType = zv::Val::null();
			}

			zv::Val parameterClosureThisType = parameterGet(parameter, PG_CLOSURE_THIS_TYPE);
			if (UNEXPECTED(parameterClosureThisType.isUndef())) return zv::Val();
			if (!parameterClosureThisType.isNull() && !closureThisType.isNull()) {
				zv::Val again = parameterGet(parameter, PG_CLOSURE_THIS_TYPE);
				if (UNEXPECTED(again.isUndef())) return zv::Val();
				closureThisType = union2(closureThisType.raw(), again.raw());
				if (UNEXPECTED(closureThisType.isUndef())) return zv::Val();
			} else {
				closureThisType = zv::Val::null();
			}

			zv::Val parameterImmediately = parameterGet(parameter, PG_IMMEDIATELY_INVOKED_CALLABLE);
			zend_long left = trinaryOf(parameterImmediately);
			if (UNEXPECTED(left < 0)) return zv::Val();
			zend_long right = trinaryOf(immediatelyInvokedCallable);
			if (UNEXPECTED(right < 0)) return zv::Val();
			immediatelyInvokedCallable = trinary(pt_trinary_or(left, right));
			zv::Val parameterAttributes = parameterGet(parameter, PG_ATTRIBUTES);
			if (UNEXPECTED(parameterAttributes.isUndef() || !requireArray(attributes.raw(), "array_merge(): Argument #1 ($array)"))) return zv::Val();
			zv::Arr mergedAttributes = zv::Arr::copyOfTable(Z_ARRVAL_P(attributes.raw()));
			if (UNEXPECTED(!arrayMerge(mergedAttributes, parameterAttributes.raw()))) return zv::Val();
			attributes = zv::Val(std::move(mergedAttributes));
		} else {
			nativeType = pt_type_new_mixed_type();
			if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
			phpDocType = zv::Val::copyOf(zv::Ref(type.raw()));
			outType = zv::Val::null();
			immediatelyInvokedCallable = trinary(PT_TRI_MAYBE);
			closureThisType = zv::Val::null();
		}

		if (isParameterVariadic) {
			// the variadic parameter swallows every parameter behind it,
			// so their types have to be merged into it before they're dropped:
			// array_merge(array_slice($parameters, $i + 1), array_slice($acceptor->getParameters(), $i + 1))
			std::vector<zval *> droppedParameters;
			zend_long position = 0;
			for (zv::ArrayEntry droppedEntry : zv::TableRef(combinedParameters)) {
				if (position++ > index) droppedParameters.push_back(droppedEntry.value().deref().raw());
			}
			if (Z_TYPE_P(acceptorParameters) == IS_ARRAY) {
				position = 0;
				for (zv::ArrayEntry droppedEntry : zv::ArrRef(acceptorParameters)) {
					if (position++ > index) droppedParameters.push_back(droppedEntry.value().deref().raw());
				}
			}
			for (zval *droppedParameter : droppedParameters) {
				zv::Val droppedType = parameterGet(droppedParameter, PG_TYPE);
				if (UNEXPECTED(droppedType.isUndef())) return zv::Val();
				type = union2(type.raw(), droppedType.raw());
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				if (Z_TYPE_P(droppedParameter) == IS_OBJECT && instanceof_function(Z_OBJCE_P(droppedParameter), extendedParameterCe)) {
					zv::Val droppedNativeType = parameterGet(droppedParameter, PG_NATIVE_TYPE);
					if (UNEXPECTED(droppedNativeType.isUndef())) return zv::Val();
					nativeType = union2(nativeType.raw(), droppedNativeType.raw());
					if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
					zv::Val droppedPhpDocType = parameterGet(droppedParameter, PG_PHPDOC_TYPE);
					if (UNEXPECTED(droppedPhpDocType.isUndef())) return zv::Val();
					phpDocType = union2(phpDocType.raw(), droppedPhpDocType.raw());
					if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
					continue;
				}

				nativeType = pt_type_new_mixed_type();
				if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
				zv::Val droppedPlainType = parameterGet(droppedParameter, PG_TYPE);
				if (UNEXPECTED(droppedPlainType.isUndef())) return zv::Val();
				phpDocType = union2(phpDocType.raw(), droppedPlainType.raw());
				if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
			}
		}

		zv::Val allowedConstants = parameterGet(existing, PG_ALLOWED_CONSTANTS);
		if (UNEXPECTED(allowedConstants.isUndef())) return zv::Val();
		if (!allowedConstants.isNull()) {
			zv::Val otherAllowedConstants = isExtended ? parameterGet(parameter, PG_ALLOWED_CONSTANTS) : zv::Val::null();
			if (UNEXPECTED(otherAllowedConstants.isUndef())) return zv::Val();
			bool equal = false;
			if (!otherAllowedConstants.isNull()) {
				static pt_method_site equalsSite;
				zv::Val equals = callOn(equalsSite, allowedConstants.raw(), PT_LC("equals"), "equals", 1, otherAllowedConstants.raw());
				if (UNEXPECTED(equals.isUndef())) return zv::Val();
				equal = zend_is_true(equals.raw());
			}
			if (!equal) {
				allowedConstants = zv::Val::null();
			}
		}

		zv::Val leftPureUnless = parameterGet(existing, PG_PURE_UNLESS_CALLABLE_IS_IMPURE);
		if (UNEXPECTED(leftPureUnless.isUndef())) return zv::Val();
		zv::Val rightPureUnless = isExtended ? parameterGet(parameter, PG_PURE_UNLESS_CALLABLE_IS_IMPURE) : trinary(PT_TRI_NO);
		if (UNEXPECTED(rightPureUnless.isUndef())) return zv::Val();
		zend_long leftPure = trinaryOf(leftPureUnless);
		if (UNEXPECTED(leftPure < 0)) return zv::Val();
		zend_long rightPure = trinaryOf(rightPureUnless);
		if (UNEXPECTED(rightPure < 0)) return zv::Val();
		zv::Val pureUnlessCallableIsImpureParameter = leftPure == rightPure ? std::move(leftPureUnless) : trinary(PT_TRI_MAYBE);

		zv::Val values[14];
		{
			zv::Val existingName = parameterGet(existing, PG_NAME);
			if (UNEXPECTED(existingName.isUndef())) return zv::Val();
			zv::Val parameterName = parameterGet(parameter, PG_NAME);
			if (UNEXPECTED(parameterName.isUndef())) return zv::Val();
			if (!zend_is_identical(existingName.raw(), parameterName.raw())) {
				zv::Val left = parameterGet(existing, PG_NAME);
				if (UNEXPECTED(left.isUndef())) return zv::Val();
				zv::Val right = parameterGet(parameter, PG_NAME);
				if (UNEXPECTED(right.isUndef())) return zv::Val();
				zend_string *leftString = zval_get_string(left.raw());
				zend_string *rightString = zval_get_string(right.raw());
				values[0] = zv::Val::adoptString(zend_strpprintf(0, "%s|%s", ZSTR_VAL(leftString), ZSTR_VAL(rightString)));
				zend_string_release(leftString);
				zend_string_release(rightString);
			} else {
				values[0] = parameterGet(parameter, PG_NAME);
				if (UNEXPECTED(values[0].isUndef())) return zv::Val();
			}
		}
		values[1] = std::move(type);
		values[2] = zv::Val::boolean(optional);
		{
			zv::Val existingByRef = parameterGet(existing, PG_PASSED_BY_REFERENCE);
			if (UNEXPECTED(existingByRef.isUndef())) return zv::Val();
			zv::Val parameterByRef = parameterGet(parameter, PG_PASSED_BY_REFERENCE);
			if (UNEXPECTED(parameterByRef.isUndef())) return zv::Val();
			values[3] = pt_passed_by_reference_combine(existingByRef.raw(), parameterByRef.raw());
			if (UNEXPECTED(values[3].isUndef())) return zv::Val();
		}
		values[4] = zv::Val::boolean(isVariadic);
		values[5] = std::move(defaultValue);
		values[6] = std::move(nativeType);
		values[7] = std::move(phpDocType);
		values[8] = std::move(outType);
		values[9] = std::move(immediatelyInvokedCallable);
		values[10] = std::move(closureThisType);
		values[11] = std::move(attributes);
		values[12] = std::move(allowedConstants);
		values[13] = std::move(pureUnlessCallableIsImpureParameter);
		return newExtendedDummyParameter(values);
	}

public:
	/* Mirrors wrapAcceptor() */
	static zv::Val wrapAcceptor(zval *acceptor)
	{
		bool isExtended = false, isCallable = false;
		if (UNEXPECTED(!isA(acceptor, PT_CLASS_EXTENDED_PARAMETERS_ACCEPTOR, isExtended))) return zv::Val();
		if (isExtended) return zv::Val::copyOf(zv::Ref(acceptor));
		if (UNEXPECTED(!isA(acceptor, PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR, isCallable))) return zv::Val();

		int count = isCallable ? 17 : 8;
		zv::Val values[17];
		values[0] = acceptorGet(acceptor, AG_TEMPLATE_TYPE_MAP);
		if (UNEXPECTED(values[0].isUndef())) return zv::Val();
		values[1] = acceptorGet(acceptor, AG_RESOLVED_TEMPLATE_TYPE_MAP);
		if (UNEXPECTED(values[1].isUndef())) return zv::Val();
		zv::Val parameters = acceptorParameters(acceptor);
		if (UNEXPECTED(parameters.isUndef())) return zv::Val();
		values[2] = wrapParameters(parameters.raw());
		if (UNEXPECTED(values[2].isUndef())) return zv::Val();
		values[3] = acceptorGet(acceptor, AG_VARIADIC);
		if (UNEXPECTED(values[3].isUndef())) return zv::Val();
		values[4] = acceptorGet(acceptor, AG_RETURN_TYPE);
		if (UNEXPECTED(values[4].isUndef())) return zv::Val();
		values[5] = acceptorGet(acceptor, AG_RETURN_TYPE);
		if (UNEXPECTED(values[5].isUndef())) return zv::Val();
		values[6] = pt_type_new_mixed_type();
		if (UNEXPECTED(values[6].isUndef())) return zv::Val();
		{
			zval emptyVariances;
			if (UNEXPECTED(!pt_template_type_variance_map_empty(&emptyVariances))) return zv::Val();
			values[7] = zv::Val::adopt(emptyVariances);
		}
		if (isCallable) {
			static const AcceptorGetter callableGetters[9] = { AG_THROW_POINTS, AG_IS_PURE, AG_IMPURE_POINTS, AG_INVALIDATE_EXPRESSIONS, AG_USED_VARIABLES, AG_ACCEPTS_NAMED_ARGUMENTS, AG_MUST_USE_RETURN_VALUE, AG_ASSERTS, AG_IS_STATIC_CLOSURE };
			for (int k = 0; k < 9; k++) {
				values[8 + k] = acceptorGet(acceptor, callableGetters[k]);
				if (UNEXPECTED(values[8 + k].isUndef())) return zv::Val();
			}
		}
		zval argv[17];
		for (int k = 0; k < count; k++) {
			ZVAL_COPY_VALUE(&argv[k], values[k].raw());
		}
		return isCallable ? pt_extended_callable_function_variant_new((uint32_t) count, argv) : pt_extended_function_variant_new((uint32_t) count, argv);
	}

	/* array_map(static fn (ParameterReflection $parameter): ExtendedParameterReflection
	 * => self::wrapParameter($parameter), $parameters) — keys kept */
	static zv::Val wrapParameters(zval *parameters)
	{
		zv::Arr wrapped = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(parameters)));
		for (zv::ArrayEntry entry : zv::ArrRef(parameters)) {
			zv::Val parameter = wrapParameter(entry.value().deref().raw());
			if (UNEXPECTED(parameter.isUndef())) return zv::Val();
			setEntryKey(wrapped, entry.stringKeyOrNull(), entry.indexKey(), parameter.raw());
		}
		return zv::Val(std::move(wrapped));
	}

	/* Mirrors wrapParameter() */
	static zv::Val wrapParameter(zval *parameter)
	{
		bool isExtended = false;
		if (UNEXPECTED(!isA(parameter, PT_CLASS_EXTENDED_PARAMETER_REFLECTION, isExtended))) return zv::Val();
		if (isExtended) return zv::Val::copyOf(zv::Ref(parameter));

		zv::Val values[14];
		static const ParameterGetter getters[6] = { PG_NAME, PG_TYPE, PG_OPTIONAL, PG_PASSED_BY_REFERENCE, PG_VARIADIC, PG_DEFAULT_VALUE };
		for (int k = 0; k < 6; k++) {
			values[k] = parameterGet(parameter, getters[k]);
			if (UNEXPECTED(values[k].isUndef())) return zv::Val();
		}
		values[6] = pt_type_new_mixed_type();
		if (UNEXPECTED(values[6].isUndef())) return zv::Val();
		values[7] = parameterGet(parameter, PG_TYPE);
		if (UNEXPECTED(values[7].isUndef())) return zv::Val();
		values[8] = zv::Val::null();
		values[9] = trinary(PT_TRI_MAYBE);
		values[10] = zv::Val::null();
		values[11] = zv::Val(zv::Arr::empty());
		values[12] = zv::Val::null();
		values[13] = trinary(PT_TRI_NO);
		return newExtendedDummyParameter(values);
	}

	/* Mirrors overrideParameterType() */
	static zv::Val overrideParameterType(zval *original, zval *type, zval *nativeType)
	{
		zv::Val wrapped = wrapParameter(original);
		if (UNEXPECTED(wrapped.isUndef())) return zv::Val();
		zv::Val values[14];
		values[0] = parameterGet(wrapped.raw(), PG_NAME);
		if (UNEXPECTED(values[0].isUndef())) return zv::Val();
		values[1] = zv::Val::copyOf(zv::Ref(type));
		static const struct
		{
			int index;
			ParameterGetter getter;
		} getters[10] = {
			{ 2, PG_OPTIONAL }, { 3, PG_PASSED_BY_REFERENCE }, { 4, PG_VARIADIC }, { 5, PG_DEFAULT_VALUE },
			{ 8, PG_OUT_TYPE }, { 9, PG_IMMEDIATELY_INVOKED_CALLABLE }, { 10, PG_CLOSURE_THIS_TYPE }, { 11, PG_ATTRIBUTES },
			{ 12, PG_ALLOWED_CONSTANTS }, { 13, PG_PURE_UNLESS_CALLABLE_IS_IMPURE },
		};
		values[6] = zv::Val::copyOf(zv::Ref(nativeType));
		values[7] = zv::Val::copyOf(zv::Ref(type));
		for (const auto &getter : getters) {
			values[getter.index] = parameterGet(wrapped.raw(), getter.getter);
			if (UNEXPECTED(values[getter.index].isUndef())) return zv::Val();
		}
		return newExtendedDummyParameter(values);
	}

	/* Mirrors overrideAcceptorParameters() */
	static zv::Val overrideAcceptorParameters(zval *acceptor, zval *parameters)
	{
		bool isExtended = false;
		if (UNEXPECTED(!isA(acceptor, PT_CLASS_EXTENDED_PARAMETERS_ACCEPTOR, isExtended))) return zv::Val();
		zv::Val templateTypeMap = acceptorGet(acceptor, AG_TEMPLATE_TYPE_MAP);
		if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
		zv::Val resolvedTemplateTypeMap = acceptorGet(acceptor, AG_RESOLVED_TEMPLATE_TYPE_MAP);
		if (UNEXPECTED(resolvedTemplateTypeMap.isUndef())) return zv::Val();
		if (isExtended) {
			zv::Val wrapped = wrapParameters(parameters);
			if (UNEXPECTED(wrapped.isUndef())) return zv::Val();
			zv::Val isVariadic = acceptorGet(acceptor, AG_VARIADIC);
			if (UNEXPECTED(isVariadic.isUndef())) return zv::Val();
			zv::Val returnType = acceptorGet(acceptor, AG_RETURN_TYPE);
			if (UNEXPECTED(returnType.isUndef())) return zv::Val();
			zv::Val phpDocReturnType = acceptorGet(acceptor, AG_PHPDOC_RETURN_TYPE);
			if (UNEXPECTED(phpDocReturnType.isUndef())) return zv::Val();
			zv::Val nativeReturnType = acceptorGet(acceptor, AG_NATIVE_RETURN_TYPE);
			if (UNEXPECTED(nativeReturnType.isUndef())) return zv::Val();
			zv::Val callSiteVarianceMap = acceptorGet(acceptor, AG_CALL_SITE_VARIANCE_MAP);
			if (UNEXPECTED(callSiteVarianceMap.isUndef())) return zv::Val();
			zv::Args argv{templateTypeMap.raw(), resolvedTemplateTypeMap.raw(), wrapped.raw(), isVariadic.raw(), returnType.raw(), phpDocReturnType.raw(), nativeReturnType.raw(), callSiteVarianceMap.raw()};
			return pt_extended_function_variant_new(8, argv);
		}

		zv::Val isVariadic = acceptorGet(acceptor, AG_VARIADIC);
		if (UNEXPECTED(isVariadic.isUndef())) return zv::Val();
		zv::Val returnType = acceptorGet(acceptor, AG_RETURN_TYPE);
		if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		zval emptyVariances;
		if (UNEXPECTED(!pt_template_type_variance_map_empty(&emptyVariances))) return zv::Val();
		zv::Val emptyVariancesHold = zv::Val::adopt(emptyVariances);
		zv::Args argv{templateTypeMap.raw(), resolvedTemplateTypeMap.raw(), parameters, isVariadic.raw(), returnType.raw(), emptyVariancesHold.raw()};
		return pt_function_variant_new(6, argv);
	}

private:
	/* new UnionType([$a, $b]) over two owned types */
	static zv::Val unionTypeOf(zv::Val a, zv::Val b)
	{
		if (UNEXPECTED(a.isUndef() || b.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(a));
		types.push(std::move(b));
		return pt_type_new_union(std::move(types));
	}

	/* new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]) */
	static zv::Val nonEmptyString()
	{
		return pt_type_new_string_with_accessory(pt_accessory_non_empty_string_type_new);
	}

	/* new ArrayType(new MixedType(), new MixedType()) */
	static zv::Val mixedArray()
	{
		zv::Val key = pt_type_new_mixed_type();
		if (UNEXPECTED(key.isUndef())) return zv::Val();
		zv::Val item = pt_type_new_mixed_type();
		if (UNEXPECTED(item.isUndef())) return zv::Val();
		return newType([&](zval *out) { return pt_array_type_new(out, key.raw(), item.raw()); });
	}

	/* new ObjectType($className) */
	static zv::Val objectType(zend_string *className)
	{
		return newType([&](zval *out) { return pt_object_type_new(out, className); });
	}

	/* PhpVersionStaticAccessor::getInstance()->$method() (truthiness); false =
	 * pending exception */
	static bool phpVersionSupports(const char *lcname, size_t len, const char *name, bool &out)
	{
		static pt_method_site getInstanceSite;
		zv::Val phpVersion = pt_call_static_cached(getInstanceSite, PT_CLASS_PHP_VERSION_STATIC_ACCESSOR, PT_LC("getinstance"), 0, NULL);
		if (UNEXPECTED(phpVersion.isUndef())) return false;
		zv::Val supports = callByName(phpVersion.raw(), lcname, len, name, 0, NULL);
		if (UNEXPECTED(supports.isUndef())) return false;
		out = zend_is_true(supports.raw());
		return true;
	}

	/* the callback option's callable: (handle, ...params): return, or null */
	static zv::Val curlCallbackType(zval *curlHandleType, zv::Val *paramTypes, int paramCount, zv::Val returnType)
	{
		if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		zend_object *no = pt_passed_by_reference_create_no();
		if (UNEXPECTED(no == NULL)) return zv::Val();
		zval noZv;
		ZVAL_OBJ(&noZv, no);
		zv::Arr parameters = zv::Arr::create((uint32_t) paramCount + 1);
		zv::Val handle = pt_dummy_parameter_new(pt_pas_handle, curlHandleType, false, &noZv, false, NULL);
		if (UNEXPECTED(handle.isUndef())) return zv::Val();
		parameters.push(std::move(handle));
		for (int k = 0; k < paramCount; k++) {
			if (UNEXPECTED(paramTypes[k].isUndef())) return zv::Val();
			zend_string *name = zend_strpprintf(0, "param%d", k);
			zv::Val parameter = pt_dummy_parameter_new(name, paramTypes[k].raw(), false, &noZv, false, NULL);
			zend_string_release(name);
			if (UNEXPECTED(parameter.isUndef())) return zv::Val();
			parameters.push(std::move(parameter));
		}
		zv::Val callable = newType([&](zval *out) { return pt_callable_type_new(out, parameters.raw(), returnType.raw(), false); });
		zval nullType;
		if (UNEXPECTED(!pt_null_type_new(&nullType))) return zv::Val();
		return unionTypeOf(std::move(callable), zv::Val::adopt(nullType));
	}

	/* Mirrors getCurlOptValueType() (the type or null) */
	static zv::Val getCurlOptValueType(zend_long curlOpt)
	{
		zval *verifyHost = zend_get_constant_str(PT_LC("CURLOPT_SSL_VERIFYHOST"));
		if (verifyHost != NULL && Z_TYPE_P(verifyHost) == IS_LONG && Z_LVAL_P(verifyHost) == curlOpt) {
			return unionTypeOf(pt_type_new_constant_integer(0), pt_type_new_constant_integer(2));
		}

		if (PT_PAS_CURL_IN(pt_pas_curl_boolConstants, curlOpt)) return newType([](zval *out) { return pt_boolean_type_new(out); });
		if (PT_PAS_CURL_IN(pt_pas_curl_intConstants, curlOpt)) return newType([](zval *out) { return pt_integer_type_new(out); });
		if (PT_PAS_CURL_IN(pt_pas_curl_nullableStringConstants, curlOpt)) {
			zval nullType;
			if (UNEXPECTED(!pt_null_type_new(&nullType))) return zv::Val();
			return unionTypeOf(zv::Val::adopt(nullType), nonEmptyString());
		}
		if (PT_PAS_CURL_IN(pt_pas_curl_nonEmptyStringConstants, curlOpt)) return nonEmptyString();
		if (PT_PAS_CURL_IN(pt_pas_curl_stringConstants, curlOpt)) return pt_type_new_string_type();
		if (PT_PAS_CURL_IN(pt_pas_curl_intArrayStringKeysConstants, curlOpt)) {
			zv::Val key = newType([](zval *out) { return pt_integer_type_new(out); });
			if (UNEXPECTED(key.isUndef())) return zv::Val();
			zv::Val item = pt_type_new_string_type();
			if (UNEXPECTED(item.isUndef())) return zv::Val();
			return newType([&](zval *out) { return pt_array_type_new(out, key.raw(), item.raw()); });
		}
		if (PT_PAS_CURL_IN(pt_pas_curl_arrayConstants, curlOpt)) return mixedArray();
		if (PT_PAS_CURL_IN(pt_pas_curl_arrayOrStringConstants, curlOpt)) return unionTypeOf(pt_type_new_string_type(), mixedArray());
		if (PT_PAS_CURL_IN(pt_pas_curl_resourceConstants, curlOpt)) return newType([](zval *out) { return pt_resource_type_new(out); });

		zval *share = zend_get_constant_str(PT_LC("CURLOPT_SHARE"));
		if (share != NULL && Z_TYPE_P(share) == IS_LONG && Z_LVAL_P(share) == curlOpt) {
			bool supportsShareHandle = false;
			if (UNEXPECTED(!phpVersionSupports(PT_LC("supportscurlsharehandle"), "supportsCurlShareHandle", supportsShareHandle))) return zv::Val();
			zv::Val shareType = supportsShareHandle ? objectType(pt_pas_curl_share_handle) : newType([](zval *out) { return pt_resource_type_new(out); });
			if (UNEXPECTED(shareType.isUndef())) return zv::Val();
			bool supportsPersistent = false;
			if (UNEXPECTED(!phpVersionSupports(PT_LC("supportscurlsharepersistenthandle"), "supportsCurlSharePersistentHandle", supportsPersistent))) return zv::Val();
			if (supportsPersistent) {
				zv::Val persistent = objectType(pt_pas_curl_share_persistent_handle);
				if (UNEXPECTED(persistent.isUndef())) return zv::Val();
				shareType = union2(shareType.raw(), persistent.raw());
			}
			return shareType;
		}

		bool supportsShareHandle = false;
		if (UNEXPECTED(!phpVersionSupports(PT_LC("supportscurlsharehandle"), "supportsCurlShareHandle", supportsShareHandle))) return zv::Val();
		zv::Val curlHandleType = supportsShareHandle ? objectType(pt_pas_curl_handle) : newType([](zval *out) { return pt_resource_type_new(out); });
		if (UNEXPECTED(curlHandleType.isUndef())) return zv::Val();

		// callback options: [parameter types passed to the callback (after the handle), expected return type]
		auto integer = []() { return newType([](zval *out) { return pt_integer_type_new(out); }); };
		auto resource = []() { return newType([](zval *out) { return pt_resource_type_new(out); }); };
		static const char *const callbackNames[] = { "CURLOPT_WRITEFUNCTION", "CURLOPT_HEADERFUNCTION", "CURLOPT_READFUNCTION", "CURLOPT_PROGRESSFUNCTION", "CURLOPT_XFERINFOFUNCTION", "CURLOPT_PREREQFUNCTION" };
		for (int kind = 0; kind < 6; kind++) {
			zval *constant = zend_get_constant_str(callbackNames[kind], strlen(callbackNames[kind]));
			if (constant == NULL || Z_TYPE_P(constant) != IS_LONG || Z_LVAL_P(constant) != curlOpt) continue;
			zv::Val params[4];
			switch (kind) {
				case 0:
				case 1:
					params[0] = pt_type_new_string_type();
					return curlCallbackType(curlHandleType.raw(), params, 1, integer());
				case 2:
					params[0] = resource();
					params[1] = integer();
					return curlCallbackType(curlHandleType.raw(), params, 2, pt_type_new_string_type());
				case 3:
				case 4:
					for (int k = 0; k < 4; k++) {
						params[k] = integer();
					}
					return curlCallbackType(curlHandleType.raw(), params, 4, integer());
				default:
					params[0] = pt_type_new_string_type();
					params[1] = pt_type_new_string_type();
					params[2] = integer();
					params[3] = integer();
					return curlCallbackType(curlHandleType.raw(), params, 4, integer());
			}
		}

		// unknown constant
		return zv::Val::null();
	}

	/* new DummyParameter($name, $type, optional: false, passedByReference:
	 * $mode, variadic: false, defaultValue: null) over an owned type */
	static zv::Val dummyParameter(zend_string *name, zv::Val type, zend_long mode = PT_PASSED_BY_REFERENCE_NO)
	{
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zend_object *passedByReference = mode == PT_PASSED_BY_REFERENCE_READS_ARGUMENT ? pt_passed_by_reference_create_reads_argument() : pt_passed_by_reference_create_no();
		if (UNEXPECTED(passedByReference == NULL)) return zv::Val();
		zval passedByReferenceZv;
		ZVAL_OBJ(&passedByReferenceZv, passedByReference);
		return pt_dummy_parameter_new(name, type.raw(), false, &passedByReferenceZv, false, NULL);
	}

	/* new CallableType($parameters, $returnType, false) over owned values */
	static zv::Val callableType(zv::Val parameters, zv::Val returnType)
	{
		if (UNEXPECTED(parameters.isUndef() || returnType.isUndef())) return zv::Val();
		return newType([&](zval *out) { return pt_callable_type_new(out, parameters.raw(), returnType.raw(), false); });
	}

	/* new UnionType([$type, new NullType()]) */
	static zv::Val orNull(zv::Val type)
	{
		zval nullType;
		if (UNEXPECTED(type.isUndef() || !pt_null_type_new(&nullType))) return zv::Val();
		return unionTypeOf(std::move(type), zv::Val::adopt(nullType));
	}

	/* a list of owned values */
	static zv::Val listOf(zv::Val *values, int count)
	{
		zv::Arr list = zv::Arr::create((uint32_t) count);
		for (int k = 0; k < count; k++) {
			if (UNEXPECTED(values[k].isUndef())) return zv::Val();
			list.push(std::move(values[k]));
		}
		return zv::Val(std::move(list));
	}

	/* $args[$i] (an Arg) — the warning and null of a missing key */
	static zval *argAt(HashTable *args, zend_ulong i)
	{
		return readIndex(args, i);
	}

	/* $arg->value; NULL = pending exception */
	static zval *argValueOf(zval *arg, zv::Val &hold)
	{
		return readProperty(pt_pas_arg_value_site, arg, PT_LC("value"), hold);
	}

	/* the acceptor at $parametersAcceptors[0] and its parameters with the
	 * one at $index replaced by $parameter, re-wrapped the way $wrap says:
	 * self::overrideAcceptorParameters() (true) or a FunctionVariant keeping
	 * the call-site variances (false) */
	static zv::Val replacedParameterAcceptors(zval *acceptor, zv::Val &parameters, zend_ulong index, zv::Val parameter, bool overrideAcceptor)
	{
		if (UNEXPECTED(parameter.isUndef())) return zv::Val();
		SEPARATE_ARRAY(parameters.raw());
		zval raw = parameter.take();
		zend_hash_index_update(Z_ARRVAL_P(parameters.raw()), index, &raw);
		zv::Val replaced;
		if (overrideAcceptor) {
			replaced = overrideAcceptorParameters(acceptor, parameters.raw());
		} else {
			replaced = functionVariantOver(acceptor, parameters.raw());
		}
		if (UNEXPECTED(replaced.isUndef())) return zv::Val();
		zv::Arr acceptors = zv::Arr::create(1);
		acceptors.push(std::move(replaced));
		return zv::Val(std::move(acceptors));
	}

	/* new FunctionVariant($acceptor->getTemplateTypeMap(),
	 * $acceptor->getResolvedTemplateTypeMap(), $parameters,
	 * $acceptor->isVariadic(), $acceptor->getReturnType(), $acceptor
	 * instanceof ExtendedParametersAcceptor ? $acceptor->getCallSiteVarianceMap()
	 * : TemplateTypeVarianceMap::createEmpty()) */
	static zv::Val functionVariantOver(zval *acceptor, zval *parameters)
	{
		zv::Val templateTypeMap = acceptorGet(acceptor, AG_TEMPLATE_TYPE_MAP);
		if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
		zv::Val resolvedTemplateTypeMap = acceptorGet(acceptor, AG_RESOLVED_TEMPLATE_TYPE_MAP);
		if (UNEXPECTED(resolvedTemplateTypeMap.isUndef())) return zv::Val();
		zv::Val isVariadic = acceptorGet(acceptor, AG_VARIADIC);
		if (UNEXPECTED(isVariadic.isUndef())) return zv::Val();
		zv::Val returnType = acceptorGet(acceptor, AG_RETURN_TYPE);
		if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		bool isExtended = false;
		if (UNEXPECTED(!isA(acceptor, PT_CLASS_EXTENDED_PARAMETERS_ACCEPTOR, isExtended))) return zv::Val();
		zv::Val variances;
		if (isExtended) {
			variances = acceptorGet(acceptor, AG_CALL_SITE_VARIANCE_MAP);
		} else {
			zval empty;
			if (pt_template_type_variance_map_empty(&empty)) {
				variances = zv::Val::adopt(empty);
			}
		}
		if (UNEXPECTED(variances.isUndef())) return zv::Val();
		zv::Args argv{templateTypeMap.raw(), resolvedTemplateTypeMap.raw(), parameters, isVariadic.raw(), returnType.raw(), variances.raw()};
		return pt_function_variant_new(6, argv);
	}

	/* new NativeParameterReflection($parameters[$index]->getName(),
	 * ->isOptional(), $type, ->passedByReference(), ->isVariadic(),
	 * ->getDefaultValue()) */
	static zv::Val nativeParameterWithType(zval *parameter, zv::Val type)
	{
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zv::Val values[6];
		values[0] = parameterGet(parameter, PG_NAME);
		if (UNEXPECTED(values[0].isUndef())) return zv::Val();
		values[1] = parameterGet(parameter, PG_OPTIONAL);
		if (UNEXPECTED(values[1].isUndef())) return zv::Val();
		values[2] = std::move(type);
		values[3] = parameterGet(parameter, PG_PASSED_BY_REFERENCE);
		if (UNEXPECTED(values[3].isUndef())) return zv::Val();
		values[4] = parameterGet(parameter, PG_VARIADIC);
		if (UNEXPECTED(values[4].isUndef())) return zv::Val();
		values[5] = parameterGet(parameter, PG_DEFAULT_VALUE);
		if (UNEXPECTED(values[5].isUndef())) return zv::Val();
		zval argv[6];
		for (int k = 0; k < 6; k++) {
			ZVAL_COPY_VALUE(&argv[k], values[k].raw());
		}
		return pt_native_parameter_reflection_new(6, argv);
	}

	/* the closure-$this override of Closure::bind()/bindTo() over a
	 * parameter of the scope's function: the closure this types by name,
	 * `$closureThisParameters[$name]` when the parameter variable is still
	 * the original one; UNDEF with no exception when nothing applies,
	 * `failed` on a pending exception */
	static zv::Val closureThisParameterType(zval *scope, zend_string *variableName, bool &failed)
	{
		failed = true;
		if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getFunction() on %s", zend_zval_value_name(scope));
			return zv::Val();
		}
		zv::Val inFunction = pt_mutating_scope_get_function(Z_OBJ_P(scope));
		if (UNEXPECTED(inFunction.isUndef())) return zv::Val();
		failed = false;
		if (inFunction.isNull()) return zv::Val();
		failed = true;
		static pt_method_site getParametersSite;
		zv::Val functionParameters = callOn(getParametersSite, inFunction.raw(), PT_LC("getparameters"), "getParameters", 0, NULL);
		if (UNEXPECTED(functionParameters.isUndef() || !requireArray(functionParameters.raw(), "foreach() argument"))) return zv::Val();
		zv::Arr closureThisParameters = zv::Arr::empty();
		for (zv::ArrayEntry entry : zv::ArrRef(functionParameters.raw())) {
			zval *parameter = entry.value().deref().raw();
			zv::Val closureThisType = parameterGet(parameter, PG_CLOSURE_THIS_TYPE);
			if (UNEXPECTED(closureThisType.isUndef())) return zv::Val();
			if (closureThisType.isNull()) continue;
			zv::Val name = parameterGet(parameter, PG_NAME);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			zv::Val again = parameterGet(parameter, PG_CLOSURE_THIS_TYPE);
			if (UNEXPECTED(again.isUndef())) return zv::Val();
			if (UNEXPECTED(!name.ref().isString())) {
				zend_type_error("Illegal offset type");
				return zv::Val();
			}
			closureThisParameters.set(Z_STR_P(name.raw()), std::move(again));
		}
		zval *found = zend_symtable_find(closureThisParameters.table(), variableName);
		failed = false;
		if (found == NULL) return zv::Val();
		failed = true;
		zval nameZv;
		ZVAL_STR(&nameZv, variableName);
		zv::Val originalValueExpr = pt_type_new(PT_CLASS_PARAMETER_VARIABLE_ORIGINAL_VALUE_EXPR, 1, &nameZv);
		if (UNEXPECTED(originalValueExpr.isUndef())) return zv::Val();
		zend_long has = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), originalValueExpr.raw());
		if (UNEXPECTED(has < 0)) return zv::Val();
		failed = false;
		if (has != PT_TRI_YES) return zv::Val();
		return zv::Val::copyOf(zv::Ref(found));
	}

public:
	/* Mirrors applyIntrinsicArgOverrides() */
	static zv::Val applyIntrinsicArgOverrides(zval *argsZv, zval *parametersAcceptorsZv, zval *namedArgumentsVariants, zval *scope, const Getters &getters)
	{
		HashTable *args = Z_ARRVAL_P(argsZv);
		zv::Val parametersAcceptors = zv::Val::copyOf(zv::Ref(parametersAcceptorsZv));
		if (zend_hash_num_elements(args) == 0 || zend_hash_num_elements(Z_ARRVAL_P(parametersAcceptorsZv)) == 0) return parametersAcceptors;

		zval *firstArg = argAt(args, 0);
		if (UNEXPECTED(firstArg == NULL)) return zv::Val();

		{
			zv::Val valueHold;
			zval *firstValue = argValueOf(firstArg, valueHold);
			if (UNEXPECTED(firstValue == NULL)) return zv::Val();
			zval *arrayMapArgs = NULL;
			if (UNEXPECTED(!getAttribute(firstValue, pt_pas_array_map_args, arrayMapArgs))) return zv::Val();
			if (arrayMapArgs != NULL && UNEXPECTED(!applyArrayMap(parametersAcceptors, arrayMapArgs, getters))) return zv::Val();
		}

		if (zend_hash_num_elements(args) >= 3) {
			zval *isCurlSetOptArg = NULL;
			if (UNEXPECTED(!getAttribute(firstArg, pt_pas_curl_set_opt_arg, isCurlSetOptArg))) return zv::Val();
			if (isCurlSetOptArg != NULL && zend_is_true(isCurlSetOptArg) && UNEXPECTED(!applyCurlSetOpt(parametersAcceptors, args, getters))) return zv::Val();
		}

		if (zend_hash_num_elements(args) >= 2) {
			zval *secondArg = argAt(args, 1);
			if (UNEXPECTED(secondArg == NULL)) return zv::Val();
			zval *isCurlSetOptArrayArg = NULL;
			if (UNEXPECTED(!getAttribute(secondArg, pt_pas_curl_set_opt_array_arg, isCurlSetOptArrayArg))) return zv::Val();
			if (isCurlSetOptArrayArg != NULL && zend_is_true(isCurlSetOptArrayArg) && UNEXPECTED(!applyCurlSetOptArray(parametersAcceptors, secondArg, getters))) return zv::Val();
		}

		{
			zval *isArrayFilterArg = NULL;
			if (UNEXPECTED(!getAttribute(firstArg, pt_pas_array_filter_arg, isArrayFilterArg))) return zv::Val();
			if (isArrayFilterArg != NULL && zend_is_true(isArrayFilterArg) && UNEXPECTED(!applyArrayFilter(parametersAcceptors, args, firstArg, getters))) return zv::Val();
		}

		if (zend_hash_num_elements(args) <= 2) {
			zval *isImplodeArg = NULL;
			if (UNEXPECTED(!getAttribute(firstArg, pt_pas_implode_arg, isImplodeArg))) return zv::Val();
			if (isImplodeArg != NULL && zend_is_true(isImplodeArg) && UNEXPECTED(!applyImplode(parametersAcceptors, args, firstArg, namedArgumentsVariants))) return zv::Val();
		}

		{
			zval *isArrayWalkArg = NULL;
			if (UNEXPECTED(!getAttribute(firstArg, pt_pas_array_walk_arg, isArrayWalkArg))) return zv::Val();
			if (isArrayWalkArg != NULL && zend_is_true(isArrayWalkArg) && UNEXPECTED(!applyArrayWalk(parametersAcceptors, args, firstArg, getters))) return zv::Val();
		}

		{
			zval *isArrayFindArg = NULL;
			if (UNEXPECTED(!getAttribute(firstArg, pt_pas_array_find_arg, isArrayFindArg))) return zv::Val();
			if (isArrayFindArg != NULL && zend_is_true(isArrayFindArg) && UNEXPECTED(!applyArrayFind(parametersAcceptors, firstArg, getters))) return zv::Val();
		}

		{
			zval *closureBindToVar = NULL;
			if (UNEXPECTED(!getAttribute(firstArg, pt_pas_closure_bind_to_var, closureBindToVar))) return zv::Val();
			if (closureBindToVar != NULL && UNEXPECTED(!applyClosureBindToVar(parametersAcceptors, closureBindToVar, scope, getters))) return zv::Val();
		}

		{
			zval *closureBindArg = NULL;
			if (UNEXPECTED(!getAttribute(firstArg, pt_pas_closure_bind_arg, closureBindArg))) return zv::Val();
			if (closureBindArg != NULL && UNEXPECTED(!applyClosureBindArg(parametersAcceptors, firstArg, scope))) return zv::Val();
		}

		return parametersAcceptors;
	}

private:
	/* $parametersAcceptors[0] and its parameters; false = pending exception */
	static bool firstAcceptor(zv::Val &parametersAcceptors, zv::Val &acceptor, zv::Val &parameters)
	{
		zval *first = readIndex(Z_ARRVAL_P(parametersAcceptors.raw()), 0);
		if (UNEXPECTED(first == NULL)) return false;
		acceptor = zv::Val::copyOf(zv::Ref(first));
		parameters = acceptorParameters(acceptor.raw());
		return !parameters.isUndef();
	}

	/* the array_map() callback parameters of one arg type flavour; false =
	 * pending exception */
	static bool arrayMapParameters(zv::Arr &callbackParameters, zval *argType, bool unpack, const Getters &getters)
	{
		if (!unpack) {
			zv::Val parameter = dummyParameter(pt_pas_item, getters.iterableValueType(argType));
			if (UNEXPECTED(parameter.isUndef())) return false;
			callbackParameters.push(std::move(parameter));
			return true;
		}
		if (UNEXPECTED(!requireObject(argType, "getConstantArrays"))) return false;
		zv::Val constantArrays = pt_type_op(Z_OBJ_P(argType), PT_OP_GET_CONSTANT_ARRAYS, 0, NULL);
		if (UNEXPECTED(constantArrays.isUndef() || !requireArray(constantArrays.raw(), "count(): Argument #1 ($value)"))) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(constantArrays.raw())) {
			zval *constantArray = entry.value().deref().raw();
			if (UNEXPECTED(!requireObject(constantArray, "getValueTypes"))) return false;
			zv::Val valueTypes = pt_type_op(Z_OBJ_P(constantArray), PT_OP_GET_VALUE_TYPES, 0, NULL);
			if (UNEXPECTED(valueTypes.isUndef() || !requireArray(valueTypes.raw(), "foreach() argument"))) return false;
			for (zv::ArrayEntry valueEntry : zv::ArrRef(valueTypes.raw())) {
				zv::Val parameter = dummyParameter(pt_pas_item, getters.iterableValueType(valueEntry.value().deref().raw()));
				if (UNEXPECTED(parameter.isUndef())) return false;
				callbackParameters.push(std::move(parameter));
			}
		}
		return true;
	}

	/* the ArrayMapArgVisitor override; false = pending exception */
	static bool applyArrayMap(zv::Val &parametersAcceptors, zval *arrayMapArgs, const Getters &getters)
	{
		zv::Arr callbackParameters = zv::Arr::empty();
		zv::Arr nativeCallbackParameters = zv::Arr::empty();
		if (UNEXPECTED(!requireArray(arrayMapArgs, "foreach() argument"))) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(arrayMapArgs)) {
			zval *arg = entry.value().deref().raw();
			zv::Val valueHold;
			zval *value = argValueOf(arg, valueHold);
			if (UNEXPECTED(value == NULL)) return false;
			zv::Val argType = getters.type(value);
			if (UNEXPECTED(argType.isUndef())) return false;
			zv::Val nativeArgType = getters.nativeType(value);
			if (UNEXPECTED(nativeArgType.isUndef())) return false;
			zv::Val unpackHold;
			zval *unpack = readProperty(pt_pas_arg_unpack_site, arg, PT_LC("unpack"), unpackHold);
			if (UNEXPECTED(unpack == NULL)) return false;
			if (zend_is_true(unpack)) {
				if (UNEXPECTED(!arrayMapParameters(callbackParameters, argType.raw(), true, getters))) return false;
				if (UNEXPECTED(!arrayMapParameters(nativeCallbackParameters, nativeArgType.raw(), true, getters))) return false;
			} else {
				if (UNEXPECTED(!arrayMapParameters(callbackParameters, argType.raw(), false, getters))) return false;
				if (UNEXPECTED(!arrayMapParameters(nativeCallbackParameters, nativeArgType.raw(), false, getters))) return false;
			}
		}

		zv::Val acceptor, parameters;
		if (UNEXPECTED(!firstAcceptor(parametersAcceptors, acceptor, parameters))) return false;
		zval *firstParameter = issetIndex(Z_ARRVAL_P(parameters.raw()), 0);
		if (firstParameter == NULL) return true;
		zv::Val callable = orNull(callableType(zv::Val(std::move(callbackParameters)), pt_type_new_mixed_type()));
		if (UNEXPECTED(callable.isUndef())) return false;
		zv::Val nativeCallable = orNull(callableType(zv::Val(std::move(nativeCallbackParameters)), pt_type_new_mixed_type()));
		if (UNEXPECTED(nativeCallable.isUndef())) return false;
		zv::Val replaced = replacedParameterAcceptors(acceptor.raw(), parameters, 0, overrideParameterType(firstParameter, callable.raw(), nativeCallable.raw()), true);
		if (UNEXPECTED(replaced.isUndef())) return false;
		parametersAcceptors = std::move(replaced);
		return true;
	}

	/* the CurlSetOptArgVisitor override; false = pending exception */
	static bool applyCurlSetOpt(zv::Val &parametersAcceptors, HashTable *args, const Getters &getters)
	{
		zval *optArg = argAt(args, 1);
		if (UNEXPECTED(optArg == NULL)) return false;
		zv::Val optValueHold;
		zval *optValue = argValueOf(optArg, optValueHold);
		if (UNEXPECTED(optValue == NULL)) return false;
		zv::Val optType = getters.type(optValue);
		if (UNEXPECTED(optType.isUndef() || !requireObject(optType.raw(), "getConstantScalarValues"))) return false;

		zv::Arr valueTypes = zv::Arr::empty();
		zv::Val scalarValues = pt_type_op(Z_OBJ_P(optType.raw()), PT_OP_GET_CONSTANT_SCALAR_VALUES, 0, NULL);
		if (UNEXPECTED(scalarValues.isUndef() || !requireArray(scalarValues.raw(), "foreach() argument"))) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(scalarValues.raw())) {
			zval *scalarValue = entry.value().deref().raw();
			if (Z_TYPE_P(scalarValue) != IS_LONG) {
				valueTypes = zv::Arr::empty();
				break;
			}

			zv::Val valueType = getCurlOptValueType(Z_LVAL_P(scalarValue));
			if (UNEXPECTED(valueType.isUndef())) return false;
			if (valueType.isNull()) {
				valueTypes = zv::Arr::empty();
				break;
			}

			valueTypes.push(std::move(valueType));
		}

		zv::Val acceptor, parameters;
		if (UNEXPECTED(!firstAcceptor(parametersAcceptors, acceptor, parameters))) return false;
		if (zend_hash_num_elements(valueTypes.table()) == 0) return true;
		zval *thirdParameter = issetIndex(Z_ARRVAL_P(parameters.raw()), 2);
		if (thirdParameter == NULL) return true;
		zv::Val replaced = replacedParameterAcceptors(acceptor.raw(), parameters, 2, nativeParameterWithType(thirdParameter, unionOf(valueTypes)), false);
		if (UNEXPECTED(replaced.isUndef())) return false;
		parametersAcceptors = std::move(replaced);
		return true;
	}

	/* the CurlSetOptArrayArgVisitor override; false = pending exception */
	static bool applyCurlSetOptArray(zv::Val &parametersAcceptors, zval *secondArg, const Getters &getters)
	{
		zv::Val valueHold;
		zval *value = argValueOf(secondArg, valueHold);
		if (UNEXPECTED(value == NULL)) return false;
		zv::Val optArrayType = getters.type(value);
		if (UNEXPECTED(optArrayType.isUndef() || !requireObject(optArrayType.raw(), "getIterableKeyType"))) return false;

		bool hasTypes = false;
		zv::Val builder = pt_constant_array_type_builder_create_empty();
		if (UNEXPECTED(builder.isUndef())) return false;
		zv::Val keyType = pt_type_op(Z_OBJ_P(optArrayType.raw()), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
		if (UNEXPECTED(keyType.isUndef())) return false;
		zv::Val scalarTypes = callByName(keyType.raw(), PT_LC("getconstantscalartypes"), "getConstantScalarTypes", 0, NULL);
		if (UNEXPECTED(scalarTypes.isUndef() || !requireArray(scalarTypes.raw(), "foreach() argument"))) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(scalarTypes.raw())) {
			zval *optType = entry.value().deref().raw();
			if (UNEXPECTED(!requireObject(optType, "getValue"))) return false;
			zv::Val optValue = pt_type_op(Z_OBJ_P(optType), PT_OP_GET_VALUE, 0, NULL);
			if (UNEXPECTED(optValue.isUndef())) return false;

			if (Z_TYPE_P(optValue.raw()) != IS_LONG) {
				hasTypes = false;
				break;
			}

			zv::Val optValueType = getCurlOptValueType(Z_LVAL_P(optValue.raw()));
			if (UNEXPECTED(optValueType.isUndef())) return false;
			if (optValueType.isNull()) {
				hasTypes = false;
				break;
			}

			hasTypes = true;
			zv::Val offsetType = pt_type_new_constant_integer(Z_LVAL_P(optValue.raw()));
			if (UNEXPECTED(offsetType.isUndef())) return false;
			zv::Val hasOffset = pt_type_op(Z_OBJ_P(optArrayType.raw()), PT_OP_HAS_OFFSET_VALUE_TYPE, 1, optType);
			zend_long hasOffsetValue = trinaryOf(hasOffset);
			if (UNEXPECTED(hasOffsetValue < 0)) return false;
			if (UNEXPECTED(!pt_constant_array_type_builder_set_offset_value_type(builder.raw(), offsetType.raw(), optValueType.raw(), hasOffsetValue != PT_TRI_YES))) return false;
		}

		zv::Val acceptor, parameters;
		if (UNEXPECTED(!firstAcceptor(parametersAcceptors, acceptor, parameters))) return false;
		if (!hasTypes) return true;
		zval *secondParameter = issetIndex(Z_ARRVAL_P(parameters.raw()), 1);
		if (secondParameter == NULL) return true;
		zv::Val replaced = replacedParameterAcceptors(acceptor.raw(), parameters, 1, nativeParameterWithType(secondParameter, pt_constant_array_type_builder_get_array(builder.raw())), false);
		if (UNEXPECTED(replaced.isUndef())) return false;
		parametersAcceptors = std::move(replaced);
		return true;
	}

	/* ($getter)($args[0]->value) */
	static zv::Val typeOfFirstArg(zval *firstArg, bool native, const Getters &getters)
	{
		zv::Val valueHold;
		zval *value = argValueOf(firstArg, valueHold);
		if (UNEXPECTED(value == NULL)) return zv::Val();
		return native ? getters.nativeType(value) : getters.type(value);
	}

	/* the ArrayFilterArgVisitor override; false = pending exception */
	static bool applyArrayFilter(zv::Val &parametersAcceptors, HashTable *args, zval *firstArg, const Getters &getters)
	{
		zv::Val arrayFilterParameters; /* UNDEF: null */
		zv::Val nativeArrayFilterParameters;
		zval *modeArg = issetIndex(args, 2);
		if (modeArg != NULL) {
			zv::Val modeValueHold;
			zval *modeValue = argValueOf(modeArg, modeValueHold);
			if (UNEXPECTED(modeValue == NULL)) return false;
			zv::Val mode = getters.type(modeValue);
			if (UNEXPECTED(mode.isUndef())) return false;
			if (Z_TYPE_P(mode.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(mode.raw()), pt_ce_constant_integer_type)) {
				zv::Val modeInt = pt_type_op(Z_OBJ_P(mode.raw()), PT_OP_GET_VALUE, 0, NULL);
				if (UNEXPECTED(modeInt.isUndef())) return false;
				bool useKey = Z_TYPE_P(modeInt.raw()) == IS_LONG && Z_LVAL_P(modeInt.raw()) == 2; /* ARRAY_FILTER_USE_KEY */
				if (useKey) {
					zv::Val params[1];
					zv::Val type = typeOfFirstArg(firstArg, false, getters);
					if (UNEXPECTED(type.isUndef())) return false;
					params[0] = dummyParameter(pt_pas_key, getters.iterableKeyType(type.raw()));
					arrayFilterParameters = listOf(params, 1);
					if (UNEXPECTED(arrayFilterParameters.isUndef())) return false;
					zv::Val nativeType = typeOfFirstArg(firstArg, true, getters);
					if (UNEXPECTED(nativeType.isUndef())) return false;
					params[0] = dummyParameter(pt_pas_key, getters.iterableKeyType(nativeType.raw()));
					nativeArrayFilterParameters = listOf(params, 1);
					if (UNEXPECTED(nativeArrayFilterParameters.isUndef())) return false;
				} else {
					zv::Val modeAgain = pt_type_op(Z_OBJ_P(mode.raw()), PT_OP_GET_VALUE, 0, NULL);
					if (UNEXPECTED(modeAgain.isUndef())) return false;
					if (Z_TYPE_P(modeAgain.raw()) == IS_LONG && Z_LVAL_P(modeAgain.raw()) == 1) { /* ARRAY_FILTER_USE_BOTH */
						for (int flavour = 0; flavour < 2; flavour++) {
							zv::Val params[2];
							zv::Val itemType = typeOfFirstArg(firstArg, flavour == 1, getters);
							if (UNEXPECTED(itemType.isUndef())) return false;
							params[0] = dummyParameter(pt_pas_item, getters.iterableValueType(itemType.raw()));
							if (UNEXPECTED(params[0].isUndef())) return false;
							zv::Val keyType = typeOfFirstArg(firstArg, flavour == 1, getters);
							if (UNEXPECTED(keyType.isUndef())) return false;
							params[1] = dummyParameter(pt_pas_key, getters.iterableKeyType(keyType.raw()));
							zv::Val list = listOf(params, 2);
							if (UNEXPECTED(list.isUndef())) return false;
							(flavour == 0 ? arrayFilterParameters : nativeArrayFilterParameters) = std::move(list);
						}
					}
				}
			}
		}

		zv::Val acceptor, parameters;
		if (UNEXPECTED(!firstAcceptor(parametersAcceptors, acceptor, parameters))) return false;
		zval *secondParameter = issetIndex(Z_ARRVAL_P(parameters.raw()), 1);
		if (secondParameter == NULL) return true;

		zv::Val callables[2];
		for (int flavour = 0; flavour < 2; flavour++) {
			zv::Val arrayArgType = typeOfFirstArg(firstArg, flavour == 1, getters);
			if (UNEXPECTED(arrayArgType.isUndef())) return false;
			zv::Val &filterParameters = flavour == 0 ? arrayFilterParameters : nativeArrayFilterParameters;
			zv::Val callbackParameters;
			if (!filterParameters.isUndef()) {
				callbackParameters = std::move(filterParameters);
			} else {
				zv::Val params[1];
				params[0] = dummyParameter(pt_pas_item, getters.iterableValueType(arrayArgType.raw()));
				callbackParameters = listOf(params, 1);
				if (UNEXPECTED(callbackParameters.isUndef())) return false;
			}
			callables[flavour] = orNull(callableType(std::move(callbackParameters), newType([](zval *out) { return pt_boolean_type_new(out); })));
			if (UNEXPECTED(callables[flavour].isUndef())) return false;
		}
		zv::Val replaced = replacedParameterAcceptors(acceptor.raw(), parameters, 1, overrideParameterType(secondParameter, callables[0].raw(), callables[1].raw()), true);
		if (UNEXPECTED(replaced.isUndef())) return false;
		parametersAcceptors = std::move(replaced);
		return true;
	}

	/* new NativeParameterReflection($name, optional: false, type: $type,
	 * passedByReference: PassedByReference::createNo(), variadic: false,
	 * defaultValue: null) over a parameter's name */
	static zv::Val plainNativeParameter(zval *parameter, zv::Val (*type)())
	{
		zv::Val name = parameterGet(parameter, PG_NAME);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val typeValue = type();
		if (UNEXPECTED(typeValue.isUndef())) return zv::Val();
		zend_object *no = pt_passed_by_reference_create_no();
		if (UNEXPECTED(no == NULL)) return zv::Val();
		zval argv[6];
		ZVAL_COPY_VALUE(&argv[0], name.raw());
		ZVAL_FALSE(&argv[1]);
		ZVAL_COPY_VALUE(&argv[2], typeValue.raw());
		ZVAL_OBJ(&argv[3], no);
		ZVAL_FALSE(&argv[4]);
		ZVAL_NULL(&argv[5]);
		return pt_native_parameter_reflection_new(6, argv);
	}

	static zv::Val stringTypeValue() { return pt_type_new_string_type(); }
	static zv::Val mixedArrayValue() { return mixedArray(); }

	/* the ImplodeArgVisitor override; false = pending exception */
	static bool applyImplode(zv::Val &parametersAcceptors, HashTable *args, zval *firstArg, zval *namedArgumentsVariants)
	{
		zval *acceptorZv = NULL;
		if (namedArgumentsVariants != NULL && Z_TYPE_P(namedArgumentsVariants) == IS_ARRAY) {
			acceptorZv = zend_hash_index_find(Z_ARRVAL_P(namedArgumentsVariants), 0);
			if (acceptorZv != NULL) {
				ZVAL_DEREF(acceptorZv);
				if (Z_TYPE_P(acceptorZv) == IS_NULL) acceptorZv = NULL;
			}
		}
		if (acceptorZv == NULL) {
			acceptorZv = readIndex(Z_ARRVAL_P(parametersAcceptors.raw()), 0);
			if (UNEXPECTED(acceptorZv == NULL)) return false;
		}
		zv::Val acceptor = zv::Val::copyOf(zv::Ref(acceptorZv));
		zv::Val parameters = acceptorParameters(acceptor.raw());
		if (UNEXPECTED(parameters.isUndef())) return false;
		HashTable *parameterTable = Z_ARRVAL_P(parameters.raw());

		bool arrayForm = issetIndex(args, 1) != NULL;
		if (!arrayForm) {
			zv::Val nameHold;
			zval *name = readProperty(pt_pas_arg_name_site, firstArg, PT_LC("name"), nameHold);
			if (UNEXPECTED(name == NULL)) return false;
			if (Z_TYPE_P(name) != IS_NULL) {
				zv::Val identifierNameHold;
				zval *identifierName = readProperty(pt_pas_identifier_name_site, name, PT_LC("name"), identifierNameHold);
				if (UNEXPECTED(identifierName == NULL)) return false;
				arrayForm = Z_TYPE_P(identifierName) == IS_STRING && zend_string_equals(Z_STR_P(identifierName), pt_pas_array);
			}
		}

		zv::Val newParameters;
		zval *firstParameter = issetIndex(parameterTable, 0);
		zval *secondParameter = issetIndex(parameterTable, 1);
		if (arrayForm && firstParameter != NULL && secondParameter != NULL) {
			zv::Val values[2];
			values[0] = plainNativeParameter(firstParameter, stringTypeValue);
			if (UNEXPECTED(values[0].isUndef())) return false;
			values[1] = plainNativeParameter(secondParameter, mixedArrayValue);
			newParameters = listOf(values, 2);
		} else if (firstParameter != NULL) {
			zv::Val values[1];
			values[0] = plainNativeParameter(firstParameter, mixedArrayValue);
			newParameters = listOf(values, 1);
		} else {
			newParameters = std::move(parameters);
		}
		if (UNEXPECTED(newParameters.isUndef())) return false;

		zv::Val variant = functionVariantOver(acceptor.raw(), newParameters.raw());
		if (UNEXPECTED(variant.isUndef())) return false;
		zv::Arr acceptors = zv::Arr::create(1);
		acceptors.push(std::move(variant));
		parametersAcceptors = zv::Val(std::move(acceptors));
		return true;
	}

	/* the ArrayWalkArgVisitor override; false = pending exception */
	static bool applyArrayWalk(zv::Val &parametersAcceptors, HashTable *args, zval *firstArg, const Getters &getters)
	{
		zv::Val arrayArgType = typeOfFirstArg(firstArg, false, getters);
		if (UNEXPECTED(arrayArgType.isUndef())) return false;
		zv::Val nativeArrayArgType = typeOfFirstArg(firstArg, true, getters);
		if (UNEXPECTED(nativeArrayArgType.isUndef())) return false;
		zv::Arr walkParameters[2] = { zv::Arr::create(3), zv::Arr::create(3) };
		for (int flavour = 0; flavour < 2; flavour++) {
			zval *type = flavour == 0 ? arrayArgType.raw() : nativeArrayArgType.raw();
			zv::Val item = dummyParameter(pt_pas_item, getters.iterableValueType(type), PT_PASSED_BY_REFERENCE_READS_ARGUMENT);
			if (UNEXPECTED(item.isUndef())) return false;
			walkParameters[flavour].push(std::move(item));
			zv::Val key = dummyParameter(pt_pas_key, getters.iterableKeyType(type));
			if (UNEXPECTED(key.isUndef())) return false;
			walkParameters[flavour].push(std::move(key));
		}
		zval *extraArg = issetIndex(args, 2);
		if (extraArg != NULL) {
			for (int flavour = 0; flavour < 2; flavour++) {
				zv::Val valueHold;
				zval *value = argValueOf(extraArg, valueHold);
				if (UNEXPECTED(value == NULL)) return false;
				zv::Val arg = dummyParameter(pt_pas_arg, flavour == 0 ? getters.type(value) : getters.nativeType(value));
				if (UNEXPECTED(arg.isUndef())) return false;
				walkParameters[flavour].push(std::move(arg));
			}
		}

		zv::Val acceptor, parameters;
		if (UNEXPECTED(!firstAcceptor(parametersAcceptors, acceptor, parameters))) return false;
		zval *secondParameter = issetIndex(Z_ARRVAL_P(parameters.raw()), 1);
		if (secondParameter == NULL) return true;
		zv::Val callable = callableType(zv::Val(std::move(walkParameters[0])), pt_type_new_mixed_type());
		if (UNEXPECTED(callable.isUndef())) return false;
		zv::Val nativeCallable = callableType(zv::Val(std::move(walkParameters[1])), pt_type_new_mixed_type());
		if (UNEXPECTED(nativeCallable.isUndef())) return false;
		zv::Val replaced = replacedParameterAcceptors(acceptor.raw(), parameters, 1, overrideParameterType(secondParameter, callable.raw(), nativeCallable.raw()), true);
		if (UNEXPECTED(replaced.isUndef())) return false;
		parametersAcceptors = std::move(replaced);
		return true;
	}

	/* the ArrayFindArgVisitor override; false = pending exception */
	static bool applyArrayFind(zv::Val &parametersAcceptors, zval *firstArg, const Getters &getters)
	{
		zv::Val acceptor, parameters;
		if (UNEXPECTED(!firstAcceptor(parametersAcceptors, acceptor, parameters))) return false;
		zval *secondParameter = issetIndex(Z_ARRVAL_P(parameters.raw()), 1);
		if (secondParameter == NULL) return true;

		zv::Val callables[2];
		for (int flavour = 0; flavour < 2; flavour++) {
			zv::Val argType = typeOfFirstArg(firstArg, flavour == 1, getters);
			if (UNEXPECTED(argType.isUndef())) return false;
			zv::Val params[2];
			params[0] = dummyParameter(pt_pas_value, getters.iterableValueType(argType.raw()));
			if (UNEXPECTED(params[0].isUndef())) return false;
			params[1] = dummyParameter(pt_pas_key, getters.iterableKeyType(argType.raw()));
			callables[flavour] = callableType(listOf(params, 2), newType([](zval *out) { return pt_boolean_type_new(out); }));
			if (UNEXPECTED(callables[flavour].isUndef())) return false;
		}
		zv::Val replaced = replacedParameterAcceptors(acceptor.raw(), parameters, 1, overrideParameterType(secondParameter, callables[0].raw(), callables[1].raw()), true);
		if (UNEXPECTED(replaced.isUndef())) return false;
		parametersAcceptors = std::move(replaced);
		return true;
	}

	/* the ClosureBindToVarVisitor override; false = pending exception */
	static bool applyClosureBindToVar(zv::Val &parametersAcceptors, zval *closureBindToVar, zval *scope, const Getters &getters)
	{
		bool isVariable = false;
		if (UNEXPECTED(!isA(closureBindToVar, PT_CLASS_VARIABLE, isVariable))) return false;
		if (!isVariable) return true;
		zv::Val nameHold;
		zval *name = readProperty(pt_pas_variable_name_site, closureBindToVar, PT_LC("name"), nameHold);
		if (UNEXPECTED(name == NULL)) return false;
		if (Z_TYPE_P(name) != IS_STRING) return true;

		zv::Val varType = getters.type(closureBindToVar);
		if (UNEXPECTED(varType.isUndef())) return false;
		zv::Val closureObject = objectType(pt_pas_closure_class);
		if (UNEXPECTED(closureObject.isUndef())) return false;
		zv::Val isSuperType = pt_type_op(Z_OBJ_P(closureObject.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, varType.raw());
		if (UNEXPECTED(isSuperType.isUndef())) return false;
		zend_long isSuperTypeValue = pt_type_result_trinary(isSuperType.raw());
		if (UNEXPECTED(isSuperTypeValue < 0)) return false;
		if (isSuperTypeValue != PT_TRI_YES) return true;

		bool failed = false;
		zv::Val closureThisType = closureThisParameterType(scope, Z_STR_P(name), failed);
		if (UNEXPECTED(failed)) return false;
		if (closureThisType.isUndef()) return true;

		zv::Val acceptor, parameters;
		if (UNEXPECTED(!firstAcceptor(parametersAcceptors, acceptor, parameters))) return false;
		zval *firstParameter = issetIndex(Z_ARRVAL_P(parameters.raw()), 0);
		if (firstParameter == NULL) return true;
		zv::Val replaced = replacedParameterAcceptors(acceptor.raw(), parameters, 0, nativeParameterWithType(firstParameter, std::move(closureThisType)), false);
		if (UNEXPECTED(replaced.isUndef())) return false;
		parametersAcceptors = std::move(replaced);
		return true;
	}

	/* the ClosureBindArgVisitor override; false = pending exception */
	static bool applyClosureBindArg(zv::Val &parametersAcceptors, zval *firstArg, zval *scope)
	{
		zv::Val valueHold;
		zval *value = argValueOf(firstArg, valueHold);
		if (UNEXPECTED(value == NULL)) return false;
		bool isVariable = false;
		if (UNEXPECTED(!isA(value, PT_CLASS_VARIABLE, isVariable))) return false;
		if (!isVariable) return true;
		zv::Val nameHold;
		zval *name = readProperty(pt_pas_variable_name_site, value, PT_LC("name"), nameHold);
		if (UNEXPECTED(name == NULL)) return false;
		if (Z_TYPE_P(name) != IS_STRING) return true;

		bool failed = false;
		zv::Val closureThisType = closureThisParameterType(scope, Z_STR_P(name), failed);
		if (UNEXPECTED(failed)) return false;
		if (closureThisType.isUndef()) return true;

		zv::Val acceptor, parameters;
		if (UNEXPECTED(!firstAcceptor(parametersAcceptors, acceptor, parameters))) return false;
		zval *secondParameter = issetIndex(Z_ARRVAL_P(parameters.raw()), 1);
		if (secondParameter == NULL) return true;
		zv::Val replaced = replacedParameterAcceptors(acceptor.raw(), parameters, 1, nativeParameterWithType(secondParameter, std::move(closureThisType)), false);
		if (UNEXPECTED(replaced.isUndef())) return false;
		parametersAcceptors = std::move(replaced);
		return true;
	}

public:
	/* Mirrors selectFromArgs() ($namedArgumentsVariants IS_NULL / NULL for null) */
	static zv::Val selectFromArgs(zval *scopeZv, zval *argsZv, zval *parametersAcceptorsZv, zval *namedArgumentsVariants)
	{
		zval null = {};
		ZVAL_NULL(&null);
		if (namedArgumentsVariants == NULL) {
			namedArgumentsVariants = &null;
		}
		zv::Arr types = zv::Arr::empty();
		bool unpack = false;
		const Getters getters = { scopeZv, NULL, NULL, NULL, NULL };
		zv::Val parametersAcceptors = applyIntrinsicArgOverrides(argsZv, parametersAcceptorsZv, namedArgumentsVariants, scopeZv, getters);
		if (UNEXPECTED(parametersAcceptors.isUndef())) return zv::Val();
		if (UNEXPECTED(!requireArray(parametersAcceptors.raw(), "count(): Argument #1 ($value)"))) return zv::Val();
		HashTable *acceptors = Z_ARRVAL_P(parametersAcceptors.raw());

		if (zend_hash_num_elements(acceptors) == 1) {
			zval *acceptor = readIndex(acceptors, 0);
			if (UNEXPECTED(acceptor == NULL)) return zv::Val();
			bool has = false;
			if (UNEXPECTED(!hasAcceptorTemplateOrLateResolvableType(acceptor, has))) return zv::Val();
			if (!has) return zv::Val::copyOf(zv::Ref(acceptor));
		}

		zv::Val args = zv::Val::copyOf(zv::Ref(argsZv));
		zv::Val reorderedArgs = zv::Val::copyOf(zv::Ref(argsZv));
		zval *singleParametersAcceptor = NULL;
		if (zend_hash_num_elements(acceptors) == 1) {
			if (!zend_array_is_list(Z_ARRVAL_P(args.raw()))) {
				zv::Arr values = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(args.raw())));
				for (zv::ArrayEntry entry : zv::ArrRef(args.raw())) {
					values.push(zv::Ref(entry.value().deref().raw()));
				}
				args = zv::Val(std::move(values));
			}
			singleParametersAcceptor = readIndex(acceptors, 0);
			if (UNEXPECTED(singleParametersAcceptor == NULL)) return zv::Val();
			reorderedArgs = pt_arguments_normalizer_reorder_args(singleParametersAcceptor, args.raw());
			if (UNEXPECTED(reorderedArgs.isUndef())) return zv::Val();
		}

		bool hasName = false;
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeZv));
		zval *iterated = reorderedArgs.isNull() ? args.raw() : reorderedArgs.raw();
		for (zv::ArrayEntry entry : zv::ArrRef(iterated)) {
			zval *arg = entry.value().deref().raw();
			zval *originalArg = arg;
			if (UNEXPECTED(Z_TYPE_P(arg) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getAttribute() on %s", zend_zval_value_name(arg));
				return zv::Val();
			}
			zval *original = nodeAttribute(arg, pt_pas_original_arg);
			if (original != NULL && Z_TYPE_P(original) != IS_NULL) {
				originalArg = original;
			}

			zend_string *stringKey = entry.stringKeyOrNull();
			zend_ulong i = entry.indexKey();
			zval *parameter = NULL;
			zv::Val parametersHold;
			if (singleParametersAcceptor != NULL) {
				parametersHold = acceptorParameters(singleParametersAcceptor);
				if (UNEXPECTED(parametersHold.isUndef())) return zv::Val();
				HashTable *parameters = Z_ARRVAL_P(parametersHold.raw());
				zval *found = stringKey != NULL ? zend_symtable_find(parameters, stringKey) : zend_hash_index_find(parameters, i);
				if (found != NULL) {
					ZVAL_DEREF(found);
					if (Z_TYPE_P(found) == IS_NULL) found = NULL;
				}
				if (found != NULL) {
					parameter = found;
				} else if (zend_hash_num_elements(parameters) > 0) {
					bool isVariadic = false;
					if (UNEXPECTED(!acceptorIsVariadic(singleParametersAcceptor, isVariadic))) return zv::Val();
					if (isVariadic) {
						parameter = arrayLast(parameters);
					}
				}
			}

			zv::Val originalValueHold;
			zval *originalValue = argValueOf(originalArg, originalValueHold);
			if (UNEXPECTED(originalValue == NULL)) return zv::Val();
			bool pushed = parameter != NULL && Z_TYPE_P(parameter) != IS_NULL && Z_TYPE_P(scope.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(scope.raw()), pt_ce_mutating_scope);
			if (pushed) {
				zend_class_entry *closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
				zend_class_entry *arrowCe = pt_class(PT_CLASS_ARROW_FUNCTION);
				if (UNEXPECTED(closureCe == NULL || arrowCe == NULL)) return zv::Val();
				bool rememberTypes = !(Z_TYPE_P(originalValue) == IS_OBJECT && (instanceof_function(Z_OBJCE_P(originalValue), closureCe) || instanceof_function(Z_OBJCE_P(originalValue), arrowCe)));
				zv::Val pushedScope = pt_mutating_scope_push_in_function_call(Z_OBJ_P(scope.raw()), &null, parameter, rememberTypes);
				if (UNEXPECTED(pushedScope.isUndef())) return zv::Val();
				scope = std::move(pushedScope);
			}

			if (UNEXPECTED(!requireObject(scope.raw(), "getType"))) return zv::Val();
			zv::Val type = pt_mutating_scope_get_type(Z_OBJ_P(scope.raw()), originalValue);
			if (UNEXPECTED(type.isUndef())) return zv::Val();

			if (pushed && instanceof_function(Z_OBJCE_P(scope.raw()), pt_ce_mutating_scope)) {
				zv::Val popped = pt_mutating_scope_pop_in_function_call(Z_OBJ_P(scope.raw()));
				if (UNEXPECTED(popped.isUndef())) return zv::Val();
				scope = std::move(popped);
			}

			if (UNEXPECTED(!gatherType(types, unpack, hasName, originalArg, stringKey, i, type.raw()))) return zv::Val();
		}

		if (hasName && Z_TYPE_P(namedArgumentsVariants) != IS_NULL) return selectFromTypes(types.raw(), namedArgumentsVariants, unpack);

		return selectFromTypes(types.raw(), parametersAcceptors.raw(), unpack);
	}

private:
	/* the $types / $unpack / $hasName bookkeeping of selectFromArgs() for one
	 * argument; false = pending exception */
	static bool gatherType(zv::Arr &types, bool &unpack, bool &hasName, zval *originalArg, zend_string *argStringKey, zend_ulong i, zval *type)
	{
		zv::Val nameHold;
		zval *name = readProperty(pt_pas_arg_name_site, originalArg, PT_LC("name"), nameHold);
		if (UNEXPECTED(name == NULL)) return false;
		zv::Val index; /* UNDEF: the integer $i */
		if (Z_TYPE_P(name) != IS_NULL) {
			static pt_method_site toStringSite;
			index = callOn(toStringSite, name, PT_LC("tostring"), "toString", 0, NULL);
			if (UNEXPECTED(index.isUndef())) return false;
			hasName = true;
		} else if (argStringKey != NULL) {
			index = zv::Val::string(argStringKey);
		}

		zv::Val unpackHold;
		zval *isUnpack = readProperty(pt_pas_arg_unpack_site, originalArg, PT_LC("unpack"), unpackHold);
		if (UNEXPECTED(isUnpack == NULL)) return false;
		if (!zend_is_true(isUnpack)) {
			setGathered(types, index, i, type);
			return true;
		}

		unpack = true;
		if (UNEXPECTED(!requireObject(type, "getConstantArrays"))) return false;
		zv::Val constantArrays = pt_type_op(Z_OBJ_P(type), PT_OP_GET_CONSTANT_ARRAYS, 0, NULL);
		if (UNEXPECTED(constantArrays.isUndef() || !requireArray(constantArrays.raw(), "count(): Argument #1 ($value)"))) return false;
		if (zend_hash_num_elements(Z_ARRVAL_P(constantArrays.raw())) == 0) {
			zv::Val iterableValueType = pt_type_op(Z_OBJ_P(type), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
			if (UNEXPECTED(iterableValueType.isUndef())) return false;
			setGathered(types, index, i, iterableValueType.raw());
			return true;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(constantArrays.raw())) {
			zval *constantArray = entry.value().deref().raw();
			if (UNEXPECTED(!requireObject(constantArray, "getValueTypes"))) return false;
			zv::Val values = pt_type_op(Z_OBJ_P(constantArray), PT_OP_GET_VALUE_TYPES, 0, NULL);
			if (UNEXPECTED(values.isUndef())) return false;
			zv::Val keyTypes = pt_type_op(Z_OBJ_P(constantArray), PT_OP_GET_KEY_TYPES, 0, NULL);
			if (UNEXPECTED(keyTypes.isUndef())) return false;
			if (UNEXPECTED(!requireArray(values.raw(), "getValueTypes()") || !requireArray(keyTypes.raw(), "foreach() argument"))) return false;
			for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
				zend_ulong j = keyEntry.indexKey();
				zval *valueType = readIndex(Z_ARRVAL_P(values.raw()), j);
				if (UNEXPECTED(valueType == NULL)) return false;
				zval *keyType = keyEntry.value().deref().raw();
				if (UNEXPECTED(!requireObject(keyType, "getValue"))) return false;
				zv::Val valueIndex = pt_type_op(Z_OBJ_P(keyType), PT_OP_GET_VALUE, 0, NULL);
				if (UNEXPECTED(valueIndex.isUndef())) return false;
				zv::Val stringIndex;
				zend_ulong intIndex = 0;
				if (valueIndex.ref().isString()) {
					hasName = true;
					stringIndex = std::move(valueIndex);
				} else {
					intIndex = i + j;
				}
				zval *existing = stringIndex.isUndef() ? zend_hash_index_find(types.table(), intIndex) : zend_symtable_find(types.table(), Z_STR_P(stringIndex.raw()));
				if (existing != NULL) {
					ZVAL_DEREF(existing);
					if (Z_TYPE_P(existing) == IS_NULL) existing = NULL;
				}
				if (existing != NULL) {
					zv::Val unionType = union2(existing, valueType);
					if (UNEXPECTED(unionType.isUndef())) return false;
					setGathered(types, stringIndex, intIndex, unionType.raw());
				} else {
					setGathered(types, stringIndex, intIndex, valueType);
				}
			}
		}
		return true;
	}

	/* $types[$index] = $type ($index a string value, or UNDEF for the integer) */
	static void setGathered(zv::Arr &types, zv::Val &index, zend_ulong intIndex, zval *type)
	{
		types.separate();
		Z_TRY_ADDREF_P(type);
		if (!index.isUndef() && index.ref().isString()) {
			zend_symtable_update(types.table(), Z_STR_P(index.raw()), type);
		} else if (!index.isUndef()) {
			zval_ptr_dtor(type);
		} else {
			zend_hash_index_update(types.table(), intIndex, type);
		}
	}
};

} // namespace phpstanturbo

using phpstanturbo::ParametersAcceptorSelector;

/* {{{ direct entries (support.h): the native bodies for arguments of the
 * twin's parameter types, the method (and its TypeError) for anything else */

zv::Val pt_parameters_acceptor_selector_select_from_args(zval *scope, zval *args, zval *parametersAcceptors, zval *namedArgumentsVariants)
{
	if (EXPECTED(Z_TYPE_P(scope) == IS_OBJECT && Z_TYPE_P(args) == IS_ARRAY && Z_TYPE_P(parametersAcceptors) == IS_ARRAY && (namedArgumentsVariants == NULL || Z_TYPE_P(namedArgumentsVariants) == IS_ARRAY || Z_TYPE_P(namedArgumentsVariants) == IS_NULL))) {
		return ParametersAcceptorSelector::selectFromArgs(scope, args, parametersAcceptors, namedArgumentsVariants);
	}
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{scope, args, parametersAcceptors, namedArgumentsVariants != NULL ? namedArgumentsVariants : &null};
	return pt_type_call_static_ce(pt_ce_parameters_acceptor_selector, PT_LC("selectfromargs"), 4, argv);
}

zv::Val pt_parameters_acceptor_selector_apply_intrinsic_arg_overrides(zval *args, zval *parametersAcceptors, zval *namedArgumentsVariants, zval *scope, zval *typeGetter, zval *nativeTypeGetter, zval *iterableValueTypeGetter, zval *iterableKeyTypeGetter)
{
	zval null;
	ZVAL_NULL(&null);
	const ParametersAcceptorSelector::Getters getters = { scope, typeGetter, nativeTypeGetter, iterableValueTypeGetter, iterableKeyTypeGetter };
	return ParametersAcceptorSelector::applyIntrinsicArgOverrides(args, parametersAcceptors, namedArgumentsVariants != NULL ? namedArgumentsVariants : &null, scope, getters);
}

bool pt_parameters_acceptor_selector_has_acceptor_template_or_late_resolvable_type(zval *acceptor, bool &out)
{
	return ParametersAcceptorSelector::hasAcceptorTemplateOrLateResolvableType(acceptor, out);
}

bool pt_parameters_acceptor_selector_has_acceptor_template_or_late_resolvable_parameter_type(zval *acceptor, bool &out)
{
	return ParametersAcceptorSelector::hasAcceptorTemplateOrLateResolvableParameterType(acceptor, out);
}

zv::Val pt_parameters_acceptor_selector_select_from_types(zval *types, zval *parametersAcceptors, bool unpack)
{
	if (EXPECTED(Z_TYPE_P(types) == IS_ARRAY && Z_TYPE_P(parametersAcceptors) == IS_ARRAY)) return ParametersAcceptorSelector::selectFromTypes(types, parametersAcceptors, unpack);
	zv::Args argv{types, parametersAcceptors, unpack};
	return pt_type_call_static_ce(pt_ce_parameters_acceptor_selector, PT_LC("selectfromtypes"), 3, argv);
}

zv::Val pt_parameters_acceptor_selector_combine_variants_for_normalization(zval *args, zval *variants, zval *namedArgumentsVariants)
{
	if (EXPECTED(Z_TYPE_P(args) == IS_ARRAY && Z_TYPE_P(variants) == IS_ARRAY && (namedArgumentsVariants == NULL || Z_TYPE_P(namedArgumentsVariants) == IS_ARRAY || Z_TYPE_P(namedArgumentsVariants) == IS_NULL))) {
		return ParametersAcceptorSelector::combineVariantsForNormalization(args, variants, namedArgumentsVariants);
	}
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{args, variants, namedArgumentsVariants != NULL ? namedArgumentsVariants : &null};
	return pt_type_call_static_ce(pt_ce_parameters_acceptor_selector, PT_LC("combinevariantsfornormalization"), 3, argv);
}

zv::Val pt_parameters_acceptor_selector_combine_acceptors(zval *acceptors)
{
	if (EXPECTED(Z_TYPE_P(acceptors) == IS_ARRAY)) return ParametersAcceptorSelector::combineAcceptors(acceptors);
	return pt_type_call_static_ce(pt_ce_parameters_acceptor_selector, PT_LC("combineacceptors"), 1, acceptors);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_parameters_acceptor_selector()
{
	pt_pas_original_arg = zend_string_init_interned(PT_LC("originalArg"), 1);
	pt_pas_array_map_args = zend_string_init_interned(PT_LC("arrayMapArgs"), 1);
	pt_pas_curl_set_opt_arg = zend_string_init_interned(PT_LC("isCurlSetOptArg"), 1);
	pt_pas_curl_set_opt_array_arg = zend_string_init_interned(PT_LC("isCurlSetOptArrayArg"), 1);
	pt_pas_array_filter_arg = zend_string_init_interned(PT_LC("isArrayFilterArg"), 1);
	pt_pas_implode_arg = zend_string_init_interned(PT_LC("isImplodeArg"), 1);
	pt_pas_array_walk_arg = zend_string_init_interned(PT_LC("isArrayWalkArg"), 1);
	pt_pas_array_find_arg = zend_string_init_interned(PT_LC("isArrayFindArg"), 1);
	pt_pas_closure_bind_to_var = zend_string_init_interned(PT_LC("closureBindToVar"), 1);
	pt_pas_closure_bind_arg = zend_string_init_interned(PT_LC("closureBindArg"), 1);
	pt_pas_item = zend_string_init_interned(PT_LC("item"), 1);
	pt_pas_key = zend_string_init_interned(PT_LC("key"), 1);
	pt_pas_value = zend_string_init_interned(PT_LC("value"), 1);
	pt_pas_arg = zend_string_init_interned(PT_LC("arg"), 1);
	pt_pas_array = zend_string_init_interned(PT_LC("array"), 1);
	pt_pas_handle = zend_string_init_interned(PT_LC("handle"), 1);
	pt_pas_closure_class = zend_string_init_interned(PT_LC("Closure"), 1);
	pt_pas_curl_handle = zend_string_init_interned(PT_LC("CurlHandle"), 1);
	pt_pas_curl_share_handle = zend_string_init_interned(PT_LC("CurlShareHandle"), 1);
	pt_pas_curl_share_persistent_handle = zend_string_init_interned(PT_LC("CurlSharePersistentHandle"), 1);

	reg::Class cls("PHPStan\\Reflection\\ParametersAcceptorSelector");
	ptdecl::ParametersAcceptorSelector::declareClass(cls);

	cls.method(sigs::selectFromArgs, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *args, *parametersAcceptors, *namedArgumentsVariants = NULL;
		ZEND_PARSE_PARAMETERS_START(3, 4)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_ARRAY(args)
			Z_PARAM_ARRAY(parametersAcceptors)
			Z_PARAM_OPTIONAL
			Z_PARAM_ARRAY_OR_NULL(namedArgumentsVariants)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ParametersAcceptorSelector::selectFromArgs(scope, args, parametersAcceptors, namedArgumentsVariants));
	});

	cls.method(sigs::applyIntrinsicArgOverrides, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *args, *parametersAcceptors, *namedArgumentsVariants, *scope, *typeGetter, *nativeTypeGetter, *iterableValueTypeGetter, *iterableKeyTypeGetter;
		ZEND_PARSE_PARAMETERS_START(8, 8)
			Z_PARAM_ARRAY(args)
			Z_PARAM_ARRAY(parametersAcceptors)
			Z_PARAM_ARRAY_OR_NULL(namedArgumentsVariants)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT_OF_CLASS(typeGetter, zend_ce_closure)
			Z_PARAM_OBJECT_OF_CLASS(nativeTypeGetter, zend_ce_closure)
			Z_PARAM_OBJECT_OF_CLASS(iterableValueTypeGetter, zend_ce_closure)
			Z_PARAM_OBJECT_OF_CLASS(iterableKeyTypeGetter, zend_ce_closure)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(pt_parameters_acceptor_selector_apply_intrinsic_arg_overrides(args, parametersAcceptors, namedArgumentsVariants, scope, typeGetter, nativeTypeGetter, iterableValueTypeGetter, iterableKeyTypeGetter));
	});

	cls.method(sigs::hasAcceptorTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptor;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(acceptor)
		ZEND_PARSE_PARAMETERS_END();
		bool out = false;
		if (UNEXPECTED(!ParametersAcceptorSelector::hasAcceptorTemplateOrLateResolvableType(acceptor, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::hasAcceptorTemplateOrLateResolvableParameterType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptor;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(acceptor)
		ZEND_PARSE_PARAMETERS_END();
		bool out = false;
		if (UNEXPECTED(!ParametersAcceptorSelector::hasAcceptorTemplateOrLateResolvableParameterType(acceptor, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::selectFromTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types, *parametersAcceptors;
		bool unpack;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_ARRAY(types)
			Z_PARAM_ARRAY(parametersAcceptors)
			Z_PARAM_BOOL(unpack)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ParametersAcceptorSelector::selectFromTypes(types, parametersAcceptors, unpack));
	});

	cls.method(sigs::combineVariantsForNormalization, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *args, *variants, *namedArgumentsVariants;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_ARRAY(args)
			Z_PARAM_ARRAY(variants)
			Z_PARAM_ARRAY_OR_NULL(namedArgumentsVariants)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ParametersAcceptorSelector::combineVariantsForNormalization(args, variants, namedArgumentsVariants));
	});

	cls.method(sigs::combineAcceptors, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptors;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ARRAY(acceptors)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ParametersAcceptorSelector::combineAcceptors(acceptors));
	});

	cls.shadow(&pt_ce_parameters_acceptor_selector);
}

/* }}} */
