/*
 * PHPStanTurbo\ExprHandlerRegistry — native implementation of
 * PHPStan\Analyser\ExprHandlerRegistry.
 *
 * The memo lives where the twin keeps it: the class's private static
 * $exprHandlersByClass array, keyed by spl_object_id($container), then by
 * the twin's cache-key string (the Expr class name; `|1` or `|` for a
 * CallLike's first-class-callable flag; `|<class of $expr->class>` for a
 * New_). The key is assembled in a stack buffer and looked up by its bytes,
 * so a hit allocates nothing; the string is created only for the insert.
 * A miss sweeps $container->getExtensionsCollection(ExprHandler::class)
 * ->getAll() calling supports() on each handler, exactly as the twin does.
 *
 * pt_expr_handler_registry_resolve() is the direct entry for native
 * callers (MutatingScope, the handler ports).
 */

#include "support.h"
#include "generated/ExprHandlerRegistry.h"

namespace sigs = ptdecl::ExprHandlerRegistry::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_expr_handler_registry = nullptr;

/* the key parts a stack buffer takes; a longer key is built on the heap */
#define PT_EHR_KEY_BUFFER_LIMIT 512

namespace {

/* the twin's `private static array $exprHandlersByClass` slot (borrowed;
 * resolved once per activated class) */
zend_class_entry *pt_ehr_statics_ce = nullptr;
zval *pt_ehr_statics_slot = nullptr;

zval *handlersByClassSlot()
{
	zend_class_entry *ce = pt_ce_expr_handler_registry;
	if (UNEXPECTED(pt_ehr_statics_ce != ce)) {
		if (CE_STATIC_MEMBERS(ce) == NULL) {
			zend_class_init_statics(ce);
		}
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, PT_LC("exprHandlersByClass"));
		ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) != 0);
		pt_ehr_statics_slot = CE_STATIC_MEMBERS(ce) + info->offset;
		pt_ehr_statics_ce = ce;
	}
	zval *slot = pt_ehr_statics_slot;
	ZVAL_DEINDIRECT(slot);
	ZVAL_DEREF(slot);
	return slot;
}

/* the cache key's bytes: a view of the class name for a plain Expr, the
 * assembled key in `buffer` (or `heap`, when it does not fit) otherwise */
struct CacheKey
{
	const char *bytes;
	size_t len;
	zend_string *interned; /* the class name when the key is exactly it (its hash is cached) */
	zend_string *heap;
	char buffer[PT_EHR_KEY_BUFFER_LIMIT];

	CacheKey() : bytes(NULL), len(0), interned(NULL), heap(NULL) {}
	CacheKey(const CacheKey &) = delete;
	CacheKey &operator=(const CacheKey &) = delete;
	~CacheKey()
	{
		if (heap != NULL) zend_string_release(heap);
	}

	void append(size_t &at, const char *part, size_t partLen)
	{
		memcpy(buffer + at, part, partLen);
		at += partLen;
	}
};

/* $cacheKey = get_class($expr); CallLike: .= '|' . $expr->isFirstClassCallable();
 * New_: .= '|' . get_class($expr->class). false = pending exception */
[[nodiscard]] bool buildCacheKey(zend_object *expr, CacheKey &key)
{
	zend_string *className = expr->ce->name;
	zend_class_entry *callLikeCe = pt_class(PT_CLASS_CALL_LIKE);
	if (UNEXPECTED(callLikeCe == NULL)) return false;
	if (!instanceof_function(expr->ce, callLikeCe)) {
		key.interned = className;
		key.bytes = ZSTR_VAL(className);
		key.len = ZSTR_LEN(className);
		return true;
	}

	bool firstClassCallable;
	if (UNEXPECTED(!pt_call_like_is_first_class_callable(expr, firstClassCallable))) return false;
	zend_string *newClassName = NULL;
	zend_class_entry *newCe = pt_class(PT_CLASS_NEW);
	if (UNEXPECTED(newCe == NULL)) return false;
	if (instanceof_function(expr->ce, newCe)) {
		zv::Ref classProperty = zv::ObjRef(expr).prop(PT_LC("class"));
		zval undefined;
		ZVAL_UNDEF(&undefined);
		zv::Ref value = classProperty.raw() != NULL ? classProperty.deref() : zv::Ref(&undefined);
		if (UNEXPECTED(!value.isObject())) {
			if (value.isUndef()) {
				zend_throw_error(NULL, "Typed property %s::$class must not be accessed before initialization", ZSTR_VAL(expr->ce->name));
			} else {
				zend_type_error("get_class(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(value.raw()));
			}
			return false;
		}
		newClassName = Z_OBJCE_P(value.raw())->name;
	}

	size_t len = ZSTR_LEN(className) + 1 + (firstClassCallable ? 1 : 0) + (newClassName != NULL ? 1 + ZSTR_LEN(newClassName) : 0);
	char *target;
	if (len <= PT_EHR_KEY_BUFFER_LIMIT) {
		target = key.buffer;
	} else {
		key.heap = zend_string_alloc(len, 0);
		target = ZSTR_VAL(key.heap);
		ZSTR_VAL(key.heap)[len] = '\0';
	}
	size_t at = 0;
	memcpy(target + at, ZSTR_VAL(className), ZSTR_LEN(className));
	at += ZSTR_LEN(className);
	target[at++] = '|';
	if (firstClassCallable) {
		target[at++] = '1';
	}
	if (newClassName != NULL) {
		target[at++] = '|';
		memcpy(target + at, ZSTR_VAL(newClassName), ZSTR_LEN(newClassName));
		at += ZSTR_LEN(newClassName);
	}
	key.bytes = target;
	key.len = at;
	return true;
}

zval *findKey(HashTable *table, const CacheKey &key)
{
	if (key.interned != NULL) return zend_hash_find(table, key.interned);
	return zend_hash_str_find(table, key.bytes, key.len);
}

/* self::$exprHandlersByClass[$containerId] ??= [], separated for a write;
 * the per-container table */
HashTable *containerTable(zend_long containerId)
{
	zval *statics = handlersByClassSlot();
	if (UNEXPECTED(Z_TYPE_P(statics) != IS_ARRAY)) {
		zval_ptr_dtor(statics);
		array_init(statics);
	}
	zval *entry = zend_hash_index_find(Z_ARRVAL_P(statics), (zend_ulong) containerId);
	if (entry != NULL && Z_TYPE_P(entry) != IS_NULL) {
		return Z_TYPE_P(entry) == IS_ARRAY ? Z_ARRVAL_P(entry) : NULL;
	}
	SEPARATE_ARRAY(statics);
	zval empty;
	ZVAL_EMPTY_ARRAY(&empty);
	entry = zend_hash_index_update(Z_ARRVAL_P(statics), (zend_ulong) containerId, &empty);
	return Z_ARRVAL_P(entry);
}

/* self::$exprHandlersByClass[$containerId][$cacheKey] = $value */
void storeKey(zend_long containerId, const CacheKey &key, zval *value)
{
	zval *statics = handlersByClassSlot();
	if (UNEXPECTED(Z_TYPE_P(statics) != IS_ARRAY)) {
		zval_ptr_dtor(statics);
		array_init(statics);
	}
	SEPARATE_ARRAY(statics);
	zval *entry = zend_hash_index_find(Z_ARRVAL_P(statics), (zend_ulong) containerId);
	if (entry == NULL) {
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		entry = zend_hash_index_add_new(Z_ARRVAL_P(statics), (zend_ulong) containerId, &empty);
	}
	ZVAL_DEREF(entry);
	if (UNEXPECTED(Z_TYPE_P(entry) != IS_ARRAY)) {
		zval_ptr_dtor(entry);
		ZVAL_EMPTY_ARRAY(entry);
	}
	SEPARATE_ARRAY(entry);
	Z_TRY_ADDREF_P(value);
	if (key.interned != NULL) {
		zend_hash_update(Z_ARRVAL_P(entry), key.interned, value);
	} else {
		zend_hash_str_update(Z_ARRVAL_P(entry), key.bytes, key.len, value);
	}
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandlerRegistry. */
class ExprHandlerRegistry
{
public:
	/* Mirrors resolve(): the handler or null; UNDEF = pending exception */
	static zv::Val resolve(zend_object *expr, zval *container)
	{
		CacheKey key;
		if (UNEXPECTED(!buildCacheKey(expr, key))) return zv::Val();

		zend_long containerId = (zend_long) Z_OBJ_HANDLE_P(container);
		HashTable *byKey = containerTable(containerId);
		if (EXPECTED(byKey != NULL)) {
			zval *cached = findKey(byKey, key);
			if (cached != NULL) {
				ZVAL_DEREF(cached);
				if (EXPECTED(Z_TYPE_P(cached) != IS_NULL)) {
					if (Z_TYPE_P(cached) == IS_FALSE) return zv::Val::null();
					return zv::Val::copyOf(zv::Ref(cached));
				}
			}
		}

		zend_class_entry *exprHandlerCe = pt_class(PT_CLASS_EXPR_HANDLER);
		if (UNEXPECTED(exprHandlerCe == NULL)) return zv::Val();
		zval interfaceName;
		ZVAL_STR(&interfaceName, exprHandlerCe->name);
		zv::Val collection = pt_type_call(Z_OBJ_P(container), PT_LC("getextensionscollection"), 1, &interfaceName);
		if (UNEXPECTED(collection.isUndef())) return zv::Val();
		if (UNEXPECTED(!collection.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getAll() on %s", zend_zval_value_name(collection.raw()));
			return zv::Val();
		}
		zv::Val handlers = pt_type_call(Z_OBJ_P(collection.raw()), PT_LC("getall"), 0, NULL);
		if (UNEXPECTED(handlers.isUndef())) return zv::Val();

		zv::Val matchedHandler = zv::Val::null();
		if (EXPECTED(handlers.ref().isArray())) {
			zval exprValue;
			ZVAL_OBJ(&exprValue, expr);
			for (auto entry : zv::TableRef(Z_ARRVAL_P(handlers.raw()))) {
				zv::Ref handler = entry.value().deref();
				if (UNEXPECTED(!handler.isObject())) {
					zend_throw_error(NULL, "Call to a member function supports() on %s", zend_zval_value_name(handler.raw()));
					return zv::Val();
				}
				zv::Val supports = pt_type_call(handler.asObject(), PT_LC("supports"), 1, &exprValue);
				if (UNEXPECTED(supports.isUndef())) return zv::Val();
				if (!zend_is_true(supports.raw())) continue;

				matchedHandler = zv::Val::copyOf(handler);
				break;
			}
		}

		if (matchedHandler.isNull()) {
			zval falseValue;
			ZVAL_FALSE(&falseValue);
			storeKey(containerId, key, &falseValue);
		} else {
			storeKey(containerId, key, matchedHandler.raw());
		}

		return matchedHandler;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ExprHandlerRegistry;

zv::Val pt_expr_handler_registry_resolve(zend_object *expr, zval *container)
{
	return ExprHandlerRegistry::resolve(expr, container);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_expr_handler_registry()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandlerRegistry");
	ptdecl::ExprHandlerRegistry::declareClass(cls);
	ptdecl::ExprHandlerRegistry::declareProperties(cls);

	cls.method(sigs::resolve, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *container;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expr, container)) RETURN_THROWS();
		PT_RETURN_VAL(ExprHandlerRegistry::resolve(Z_OBJ_P(expr), container));
	});

	cls.shadow(&pt_ce_expr_handler_registry);
}

/* }}} */
