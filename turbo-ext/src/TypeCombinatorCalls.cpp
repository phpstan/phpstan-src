/*
 * The native code's calls of TypeCombinator (support.h), which is not
 * shadowed: its PHP body is fast enough that a native twin does not pay for
 * keeping two implementations of the type algebra in step. union(),
 * intersect() and remove() answer as the PHP methods do without their
 * frame: through the memoizing TypeCombinatorCache when $cacheEnabled says
 * so, which computes a miss with the PHP do*() bodies, else with those
 * bodies directly; every other method is called through the class entry.
 */

#include "TypeTraits.h"

#include <vector>

/* the twin's `Type` parameters: every argument an object (the native
 * callers pass Types); false with a TypeError pending otherwise */
static bool requireObjects(uint32_t argc, zval *argv)
{
	for (uint32_t i = 0; i < argc; i++) {
		if (UNEXPECTED(Z_TYPE_P(&argv[i]) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: TypeCombinator expects Type objects, %s given", zend_zval_value_name(&argv[i]));
			return false;
		}
	}
	return true;
}

static zv::Val callPhp(const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(!requireObjects(argc, argv))) return zv::Val();
	return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, lcname, len, argc, argv);
}

static zend_class_entry *cacheEnabledCe = nullptr;
static uint32_t cacheEnabledOffset = 0;

/* self::$cacheEnabled ??= TurboExtensionEnabler::isTypeCombinatorCacheEnabled();
 * false = pending exception */
static bool cacheEnabled(bool &out)
{
	zend_class_entry *ce = pt_class(PT_CLASS_TYPE_COMBINATOR);
	if (UNEXPECTED(ce == NULL)) return false;
	if (UNEXPECTED(ce != cacheEnabledCe)) {
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&PT_CE(ce, properties_info), PT_LC("cacheEnabled"));
		if (UNEXPECTED(info == NULL || (info->flags & ZEND_ACC_STATIC) == 0)) {
			zend_throw_error(NULL, "phpstan_turbo: %s::$cacheEnabled not found", ZSTR_VAL(ce->name));
			return false;
		}
		cacheEnabledCe = ce;
		cacheEnabledOffset = info->offset;
	}
	if (UNEXPECTED(CE_STATIC_MEMBERS(ce) == NULL)) {
		zend_class_init_statics(ce);
	}
	zval *slot = &CE_STATIC_MEMBERS(ce)[cacheEnabledOffset];
	ZVAL_DEREF(slot);
	if (EXPECTED(Z_TYPE_P(slot) == IS_TRUE || Z_TYPE_P(slot) == IS_FALSE)) {
		out = Z_TYPE_P(slot) == IS_TRUE;
		return true;
	}
	zv::Val enabled = pt_type_call_static(PT_CLASS_TURBO_EXTENSION_ENABLER, PT_LC("istypecombinatorcacheenabled"), 0, NULL);
	if (UNEXPECTED(enabled.isUndef())) return false;
	out = zend_is_true(enabled.raw());
	zval_ptr_dtor(slot);
	ZVAL_BOOL(slot, out);
	return true;
}

zv::Val pt_type_combinator_union(uint32_t argc, zval *argv)
{
	if (UNEXPECTED(!requireObjects(argc, argv))) return zv::Val();
	bool enabled;
	if (UNEXPECTED(!cacheEnabled(enabled))) return zv::Val();
	if (!enabled) return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("dounion"), argc, argv);
	return pt_type_combinator_cache_union(argc, argv);
}

zv::Val pt_type_combinator_intersect(uint32_t argc, zval *argv)
{
	if (UNEXPECTED(!requireObjects(argc, argv))) return zv::Val();
	bool enabled;
	if (UNEXPECTED(!cacheEnabled(enabled))) return zv::Val();
	if (!enabled) return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("dointersect"), argc, argv);
	return pt_type_combinator_cache_intersect(argc, argv);
}

zv::Val pt_type_combinator_remove(zval *fromType, zval *typeToRemove)
{
	zval argv[2];
	ZVAL_COPY_VALUE(&argv[0], fromType);
	ZVAL_COPY_VALUE(&argv[1], typeToRemove);
	if (UNEXPECTED(!requireObjects(2, argv))) return zv::Val();
	bool enabled;
	if (UNEXPECTED(!cacheEnabled(enabled))) return zv::Val();
	if (!enabled) return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("doremove"), 2, argv);
	return pt_type_combinator_cache_remove(fromType, typeToRemove);
}

zv::Val pt_type_combinator_remove_null(zval *type)
{
	return callPhp(PT_LC("removenull"), 1, type);
}

zv::Val pt_type_combinator_add_null(zval *type)
{
	return callPhp(PT_LC("addnull"), 1, type);
}

bool pt_type_combinator_contains_null(zval *type, bool &out)
{
	zv::Val contains = callPhp(PT_LC("containsnull"), 1, type);
	if (UNEXPECTED(contains.isUndef())) return false;
	out = Z_TYPE_P(contains.raw()) == IS_TRUE;
	return true;
}

zv::Val pt_type_combinator_do_union(uint32_t argc, zval *argv)
{
	return callPhp(PT_LC("dounion"), argc, argv);
}

zv::Val pt_type_combinator_do_intersect(uint32_t argc, zval *argv)
{
	return callPhp(PT_LC("dointersect"), argc, argv);
}

zv::Val pt_type_combinator_do_remove(zval *fromType, zval *typeToRemove)
{
	zval argv[2];
	ZVAL_COPY_VALUE(&argv[0], fromType);
	ZVAL_COPY_VALUE(&argv[1], typeToRemove);
	return callPhp(PT_LC("doremove"), 2, argv);
}

static bool lcnameIs(const char *lcname, size_t len, const char *literal, size_t literalLen)
{
	return len == literalLen && memcmp(lcname, literal, len) == 0;
}

zv::Val pt_type_combinator_call(const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	if (lcnameIs(lcname, len, PT_LC("union"))) return pt_type_combinator_union(argc, argv);
	if (lcnameIs(lcname, len, PT_LC("intersect"))) return pt_type_combinator_intersect(argc, argv);
	if (argc == 2 && lcnameIs(lcname, len, PT_LC("remove"))) return pt_type_combinator_remove(&argv[0], &argv[1]);
	return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, lcname, len, argc, argv);
}

zv::Val pt_type_combinator_call_spread(const char *lcname, size_t len, HashTable *args)
{
	/* a packed table without holes is a contiguous zval array already
	 * (borrowed); any other layout is copied into a vector */
	uint32_t count = zend_hash_num_elements(args);
	if (EXPECTED(HT_IS_PACKED(args) && HT_IS_WITHOUT_HOLES(args))) return pt_type_combinator_call(lcname, len, count, args->arPacked);
	std::vector<zval> argv;
	argv.reserve(count);
	for (zv::ArrayEntry entry : zv::TableRef(args)) {
		zval value;
		ZVAL_COPY_VALUE(&value, entry.value().raw());
		argv.push_back(value);
	}
	return pt_type_combinator_call(lcname, len, (uint32_t) argv.size(), argv.data());
}
