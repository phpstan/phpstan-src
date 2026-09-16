/*
 * PHPStanTurbo\StmtHandlerRegistry — native implementation of
 * PHPStan\Analyser\StmtHandlerRegistry.
 *
 * The memo lives where the twin keeps it: the class's private static
 * $stmtHandlersByClass array, keyed by spl_object_id($container), then by
 * the Stmt class name (the class entry's name string, whose hash is cached —
 * a hit allocates nothing). A miss sweeps
 * $container->getExtensionsCollection(StmtHandler::class)->getAll() calling
 * supports() on each handler, exactly as the twin does.
 *
 * pt_stmt_handler_registry_resolve() is the direct entry for native callers.
 */

#include "support.h"
#include "generated/StmtHandlerRegistry.h"

namespace sigs = ptdecl::StmtHandlerRegistry::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_stmt_handler_registry = nullptr;

namespace {

/* the twin's `private static array $stmtHandlersByClass` slot (borrowed;
 * resolved once per activated class) */
zend_class_entry *pt_shr_statics_ce = nullptr;
zval *pt_shr_statics_slot = nullptr;

zval *handlersByClassSlot()
{
	zend_class_entry *ce = pt_ce_stmt_handler_registry;
	if (UNEXPECTED(pt_shr_statics_ce != ce)) {
		if (CE_STATIC_MEMBERS(ce) == NULL) {
			zend_class_init_statics(ce);
		}
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, PT_LC("stmtHandlersByClass"));
		ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) != 0);
		pt_shr_statics_slot = CE_STATIC_MEMBERS(ce) + info->offset;
		pt_shr_statics_ce = ce;
	}
	zval *slot = pt_shr_statics_slot;
	ZVAL_DEINDIRECT(slot);
	ZVAL_DEREF(slot);
	return slot;
}

/* self::$stmtHandlersByClass (an array, repaired only if something replaced it) */
zval *statics()
{
	zval *slot = handlersByClassSlot();
	if (UNEXPECTED(Z_TYPE_P(slot) != IS_ARRAY)) {
		zval_ptr_dtor(slot);
		array_init(slot);
	}
	return slot;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandlerRegistry. */
class StmtHandlerRegistry
{
public:
	/* Mirrors resolve(): the handler or null; UNDEF = pending exception */
	static zv::Val resolve(zend_object *stmt, zval *container)
	{
		zend_string *cacheKey = stmt->ce->name;

		zend_ulong containerId = (zend_ulong) Z_OBJ_HANDLE_P(container);
		/* self::$stmtHandlersByClass[$containerId] ??= [] */
		zval *all = statics();
		zval *byKey = zend_hash_index_find(Z_ARRVAL_P(all), containerId);
		if (byKey == NULL || Z_TYPE_P(byKey) == IS_NULL) {
			SEPARATE_ARRAY(all);
			zval empty;
			ZVAL_EMPTY_ARRAY(&empty);
			byKey = zend_hash_index_update(Z_ARRVAL_P(all), containerId, &empty);
		}
		ZVAL_DEREF(byKey);
		if (EXPECTED(Z_TYPE_P(byKey) == IS_ARRAY)) {
			zval *cached = zend_hash_find(Z_ARRVAL_P(byKey), cacheKey);
			if (cached != NULL) {
				ZVAL_DEREF(cached);
				if (EXPECTED(Z_TYPE_P(cached) != IS_NULL)) {
					if (Z_TYPE_P(cached) == IS_FALSE) return zv::Val::null();
					return zv::Val::copyOf(zv::Ref(cached));
				}
			}
		}

		zend_class_entry *stmtHandlerCe = pt_class(PT_CLASS_STMT_HANDLER);
		if (UNEXPECTED(stmtHandlerCe == NULL)) return zv::Val();
		zval interfaceName;
		ZVAL_STR(&interfaceName, stmtHandlerCe->name);
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
			zval stmtValue;
			ZVAL_OBJ(&stmtValue, stmt);
			for (auto entry : zv::TableRef(Z_ARRVAL_P(handlers.raw()))) {
				zv::Ref handler = entry.value().deref();
				if (UNEXPECTED(!handler.isObject())) {
					zend_throw_error(NULL, "Call to a member function supports() on %s", zend_zval_value_name(handler.raw()));
					return zv::Val();
				}
				zv::Val supports = pt_type_call(handler.asObject(), PT_LC("supports"), 1, &stmtValue);
				if (UNEXPECTED(supports.isUndef())) return zv::Val();
				if (!zend_is_true(supports.raw())) continue;

				matchedHandler = zv::Val::copyOf(handler);
				break;
			}
		}

		/* self::$stmtHandlersByClass[$containerId][$cacheKey] = $matchedHandler ?? false;
		 * re-read: a supports() call may have resolved another statement */
		all = statics();
		SEPARATE_ARRAY(all);
		byKey = zend_hash_index_find(Z_ARRVAL_P(all), containerId);
		if (byKey == NULL) {
			zval empty;
			ZVAL_EMPTY_ARRAY(&empty);
			byKey = zend_hash_index_add_new(Z_ARRVAL_P(all), containerId, &empty);
		}
		ZVAL_DEREF(byKey);
		if (UNEXPECTED(Z_TYPE_P(byKey) != IS_ARRAY)) {
			zval_ptr_dtor(byKey);
			ZVAL_EMPTY_ARRAY(byKey);
		}
		SEPARATE_ARRAY(byKey);
		zval stored;
		if (matchedHandler.isNull()) {
			ZVAL_FALSE(&stored);
		} else {
			ZVAL_COPY(&stored, matchedHandler.raw());
		}
		zend_hash_update(Z_ARRVAL_P(byKey), cacheKey, &stored);

		return matchedHandler;
	}
};

} // namespace phpstanturbo

using phpstanturbo::StmtHandlerRegistry;

zv::Val pt_stmt_handler_registry_resolve(zend_object *stmt, zval *container)
{
	return StmtHandlerRegistry::resolve(stmt, container);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_stmt_handler_registry()
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandlerRegistry");
	ptdecl::StmtHandlerRegistry::declareClass(cls);
	ptdecl::StmtHandlerRegistry::declareProperties(cls);

	cls.method(sigs::resolve, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *stmt, *container;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, stmt, container)) RETURN_THROWS();
		PT_RETURN_VAL(StmtHandlerRegistry::resolve(Z_OBJ_P(stmt), container));
	});

	cls.shadow(&pt_ce_stmt_handler_registry);
}

/* }}} */
