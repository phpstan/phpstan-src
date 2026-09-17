/*
 * PHPStanTurbo\VolatileExpressionHelper — native implementation of
 * PHPStan\Analyser\VolatileExpressionHelper.
 *
 * The three static methods forget tracked entries of the two by-reference
 * expression tables a MutatingScope hands in as copies of its own; like the
 * twin's `unset($table[$key])` the tables are separated only when an entry
 * is actually removed — the common call finds nothing tracked and must not
 * duplicate the (large) tables.
 */

#include "support.h"
#include "generated/VolatileExpressionHelper.h"

namespace sigs = ptdecl::VolatileExpressionHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"

#include <cstring>

zend_class_entry *pt_ce_volatile_expression_helper;

namespace {

/* the twin's private VOLATILE_FUNCTION_NAMES */
const pt_superglobal_name pt_veh_volatile_function_names[] = {
	{ PT_LC("ob_get_level") },
	{ PT_LC("openssl_error_string") },
};

/* the twin's private EXISTENCE_CHECK_FUNCTION_NAMES */
const pt_superglobal_name pt_veh_existence_check_function_names[] = {
	{ PT_LC("class_exists") },
	{ PT_LC("interface_exists") },
	{ PT_LC("trait_exists") },
	{ PT_LC("enum_exists") },
	{ PT_LC("function_exists") },
};

/* the same lists as the twin's constants: persistent immutable arrays the
 * engine references for the process lifetime */
HashTable *pt_veh_persistent_list(const pt_superglobal_name *names, size_t count)
{
	HashTable *list = (HashTable *) pemalloc(sizeof(HashTable), 1);
	zend_hash_init(list, (uint32_t) count, NULL, NULL, 1);
	for (size_t i = 0; i < count; i++) {
		zval value;
		ZVAL_INTERNED_STR(&value, zend_string_init_interned(names[i].name, names[i].len, 1));
		zend_hash_next_index_insert(list, &value);
	}
	GC_ADD_FLAGS(list, IS_ARRAY_IMMUTABLE);
	GC_SET_REFCOUNT(list, 2);
	return list;
}

void pt_veh_volatile_function_names_constant(zval *out)
{
	ZVAL_ARR(out, pt_veh_persistent_list(pt_veh_volatile_function_names, sizeof(pt_veh_volatile_function_names) / sizeof(pt_veh_volatile_function_names[0])));
	Z_TYPE_INFO_P(out) = IS_ARRAY;
}

void pt_veh_existence_check_function_names_constant(zval *out)
{
	ZVAL_ARR(out, pt_veh_persistent_list(pt_veh_existence_check_function_names, sizeof(pt_veh_existence_check_function_names) / sizeof(pt_veh_existence_check_function_names[0])));
	Z_TYPE_INFO_P(out) = IS_ARRAY;
}

/* unset($table[$key]) on the caller's array: separates a shared table
 * first, exactly when the twin's unset would */
void pt_veh_unset(zval *table, const char *key, size_t len)
{
	SEPARATE_ARRAY(table);
	zend_hash_str_del(Z_ARRVAL_P(table), key, len);
}

/* unset($expressionTypes[$key]); unset($nativeExpressionTypes[$key]) for the
 * key of a bucket of one of the two tables: the first removal may release
 * the last reference to that bucket's key string, so the key is held for the
 * second */
void pt_veh_unset_both(zval *expressionTypes, zval *nativeExpressionTypes, zend_string *key, zend_ulong idx)
{
	zv::Str held = key != NULL ? zv::Str::copyOf(key) : zv::Str();
	SEPARATE_ARRAY(expressionTypes);
	pt_ht_del(Z_ARRVAL_P(expressionTypes), key, idx);
	SEPARATE_ARRAY(nativeExpressionTypes);
	pt_ht_del(Z_ARRVAL_P(nativeExpressionTypes), key, idx);
}

/* a by-reference `array &$x` argument: the reference's inner array (the
 * caller's variable), or NULL with the twin's TypeError pending */
zval *pt_veh_array_ref(zval *arg, uint32_t argNum)
{
	ZVAL_DEREF(arg);
	if (UNEXPECTED(Z_TYPE_P(arg) != IS_ARRAY)) {
		zend_argument_type_error(argNum, "must be of type array, %s given", zend_zval_value_name(arg));
		return NULL;
	}
	return arg;
}

/* ltrim(strtolower($name), '\\') as a borrowed byte range of an owned
 * lowercase copy */
zend_string *pt_veh_lower_symbol(zend_string *name, const char **start, size_t *len)
{
	zend_string *lower = zend_string_tolower(name);
	const char *p = ZSTR_VAL(lower);
	size_t n = ZSTR_LEN(lower);
	while (n > 0 && *p == '\\') {
		p++;
		n--;
	}
	*start = p;
	*len = n;
	return lower;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\VolatileExpressionHelper. The table arguments
 * are the by-reference arrays' inner zvals (never references). */
class VolatileExpressionHelper
{
public:
	/* Mirrors invalidateVolatileFunctionCalls(). */
	static bool invalidateVolatileFunctionCalls(zval *expressionTypes, zval *nativeExpressionTypes)
	{
		bool changed = false;
		char key[64];
		for (size_t i = 0; i < sizeof(pt_veh_volatile_function_names) / sizeof(pt_veh_volatile_function_names[0]); i++) {
			const pt_superglobal_name &functionName = pt_veh_volatile_function_names[i];
			for (int leading = 0; leading < 2; leading++) {
				size_t len = 0;
				if (leading == 1) {
					key[len++] = '\\';
				}
				memcpy(key + len, functionName.name, functionName.len);
				len += functionName.len;
				key[len++] = '(';
				key[len++] = ')';
				if (!zend_hash_str_exists(Z_ARRVAL_P(expressionTypes), key, len)
					&& !zend_hash_str_exists(Z_ARRVAL_P(nativeExpressionTypes), key, len)) {
					continue;
				}

				pt_veh_unset(expressionTypes, key, len);
				pt_veh_unset(nativeExpressionTypes, key, len);
				changed = true;
			}
		}

		return changed;
	}

	/* Mirrors invalidateSuperglobals(). */
	static bool invalidateSuperglobals(zval *expressionTypes, zval *nativeExpressionTypes)
	{
		size_t superglobalCount;
		const pt_superglobal_name *superglobals = pt_superglobal_names(&superglobalCount);
		bool hasTrackedSuperglobal = false;
		char variableString[16];
		for (size_t i = 0; i < superglobalCount; i++) {
			variableString[0] = '$';
			memcpy(variableString + 1, superglobals[i].name, superglobals[i].len);
			size_t len = superglobals[i].len + 1;
			if (!zend_hash_str_exists(Z_ARRVAL_P(expressionTypes), variableString, len)
				&& !zend_hash_str_exists(Z_ARRVAL_P(nativeExpressionTypes), variableString, len)) {
				continue;
			}

			hasTrackedSuperglobal = true;
			break;
		}

		if (!hasTrackedSuperglobal) return false;

		/* the keys of $expressionTypes + $nativeExpressionTypes: both tables'
		 * keys, each table iterated as it was before any removal (a removal
		 * separates a shared table, and marks a bucket of an unshared one
		 * — the walk sees every original key either way) */
		bool changed = false;
		zv::TableRef tables[2] = { zv::TableRef(Z_ARRVAL_P(expressionTypes)), zv::TableRef(Z_ARRVAL_P(nativeExpressionTypes)) };
		for (int t = 0; t < 2; t++) {
			for (auto entry : tables[t]) {
				zend_string *exprString = entry.stringKeyOrNull();
				if (exprString == NULL || !isSuperglobalExprString(exprString, superglobals, superglobalCount)) continue;

				pt_veh_unset_both(expressionTypes, nativeExpressionTypes, exprString, 0);
				changed = true;
			}
		}

		return changed;
	}

	/*
	 * Mirrors invalidateNegativeExistenceChecks(). $functionNames NULL stands
	 * for the twin's EXISTENCE_CHECK_FUNCTION_NAMES default, $declaredSymbolName
	 * NULL for null; UNDEF = pending exception.
	 */
	static zv::Val invalidateNegativeExistenceChecks(zval *scope, zval *expressionTypes, zval *nativeExpressionTypes, HashTable *functionNames, zend_string *declaredSymbolName)
	{
		zend_class_entry *funcCallCe = pt_class(PT_CLASS_FUNC_CALL);
		zend_class_entry *nameCe = pt_class(PT_CLASS_NAME);
		if (UNEXPECTED(funcCallCe == NULL || nameCe == NULL)) return zv::Val();

		bool changed = false;
		/* foreach by value walks the table as it was before any removal */
		for (auto entry : zv::TableRef(Z_ARRVAL_P(expressionTypes))) {
			zv::Ref exprTypeHolder = entry.value().deref();
			if (UNEXPECTED(!pt_check_holder(exprTypeHolder.raw()))) return zv::Val();
			zend_object *expr = zv::ObjRef(exprTypeHolder.asObject()).propAt(PT_ETH_PROP_EXPR).asObject();

			if (!instanceof_function(expr->ce, funcCallCe)) continue;
			bool isFirstClassCallable;
			if (UNEXPECTED(!pt_call_like_is_first_class_callable(expr, isFirstClassCallable))) return zv::Val();
			if (isFirstClassCallable) continue;
			zv::Ref name = zv::ObjRef(expr).prop(PT_LC("name"));
			if (UNEXPECTED(name.raw() == NULL)) continue;
			name = name.deref();
			if (!name.instanceOf(nameCe)) continue;
			/* $expr->name->toLowerString() */
			zv::Ref nameString = zv::ObjRef(name.asObject()).prop(PT_LC("name"));
			if (UNEXPECTED(nameString.raw() == NULL)) continue;
			nameString = nameString.deref();
			if (!nameString.isString()) continue;
			zv::Str lowerName = zv::Str::adopt(zend_string_tolower(nameString.asString()));
			if (!inFunctionNames(lowerName.get(), functionNames)) continue;
			/* $exprTypeHolder->getType()->isTrue()->yes() */
			zend_long isTrue = pt_type_call_trinary(zv::ObjRef(exprTypeHolder.asObject()).propAt(PT_ETH_PROP_TYPE).asObject(), PT_LC("istrue"), 0, NULL);
			if (UNEXPECTED(isTrue < 0)) return zv::Val();
			if (isTrue == PT_TRI_YES) continue;

			/* keep a check whose single constant-string argument names a symbol
			 * other than the declared one */
			zv::Ref args = declaredSymbolName != NULL ? zv::ObjRef(expr).prop(PT_LC("args")) : zv::Ref(NULL);
			if (args.raw() != NULL && args.deref().isArray()) {
				zval *firstArg = zend_hash_index_find(args.deref().asArrayTable(), 0);
				if (firstArg != NULL && Z_TYPE_P(firstArg) != IS_NULL) {
					bool keep = false;
					if (UNEXPECTED(!namesAnotherSymbol(scope, zv::Ref(firstArg).deref(), declaredSymbolName, keep))) return zv::Val();
					if (keep) continue;
				}
			}

			pt_veh_unset_both(expressionTypes, nativeExpressionTypes, entry.stringKeyOrNull(), entry.indexKey());
			changed = true;
		}

		return zv::Val::boolean(changed);
	}

private:
	/* $exprString === '$' . $name || str_starts_with($exprString, '$' . $name . '[') */
	static bool isSuperglobalExprString(zend_string *exprString, const pt_superglobal_name *superglobals, size_t superglobalCount)
	{
		const char *s = ZSTR_VAL(exprString);
		size_t len = ZSTR_LEN(exprString);
		if (len < 2 || s[0] != '$') return false;
		for (size_t i = 0; i < superglobalCount; i++) {
			size_t nameLen = superglobals[i].len;
			if (len < nameLen + 1 || memcmp(s + 1, superglobals[i].name, nameLen) != 0) continue;
			if (len == nameLen + 1 || s[nameLen + 1] == '[') return true;
		}
		return false;
	}

	/* in_array($lowerName, $functionNames, true) — the default list when
	 * $functionNames is NULL */
	static bool inFunctionNames(zend_string *lowerName, HashTable *functionNames)
	{
		if (functionNames == NULL) {
			return pt_veh_in_list(lowerName, pt_veh_existence_check_function_names, sizeof(pt_veh_existence_check_function_names) / sizeof(pt_veh_existence_check_function_names[0]));
		}
		for (auto entry : zv::TableRef(functionNames)) {
			zv::Ref value = entry.value().deref();
			if (value.isString() && zend_string_equals(value.asString(), lowerName)) return true;
		}
		return false;
	}

	static bool pt_veh_in_list(zend_string *name, const pt_superglobal_name *names, size_t count)
	{
		for (size_t i = 0; i < count; i++) {
			if (ZSTR_LEN(name) == names[i].len && memcmp(ZSTR_VAL(name), names[i].name, names[i].len) == 0) return true;
		}
		return false;
	}

	/*
	 * The twin's keep condition: $scope->getType($arg->value) has exactly one
	 * constant string, and it names (case-insensitively, leading backslashes
	 * aside) a symbol other than $declaredSymbolName; false = pending
	 * exception.
	 */
	[[nodiscard]] static bool namesAnotherSymbol(zval *scope, zv::Ref arg, zend_string *declaredSymbolName, bool &keep)
	{
		keep = false;
		if (!arg.isObject()) return true;
		zv::Ref argValue = zv::ObjRef(arg.asObject()).prop(PT_LC("value"));
		if (argValue.raw() == NULL) return true;
		argValue = argValue.deref();
		if (!argValue.isObject()) return true;
		zv::Val type = pt_type_call(Z_OBJ_P(scope), PT_LC("gettype"), 1, argValue.raw());
		if (UNEXPECTED(type.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(type.raw()) != IS_OBJECT)) return true;
		zv::Val constantStrings = pt_type_call(Z_OBJ_P(type.raw()), PT_LC("getconstantstrings"), 0, NULL);
		if (UNEXPECTED(constantStrings.isUndef())) return false;
		if (Z_TYPE_P(constantStrings.raw()) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(constantStrings.raw())) != 1) return true;
		zval *constantString = zend_hash_index_find(Z_ARRVAL_P(constantStrings.raw()), 0);
		if (constantString == NULL || Z_TYPE_P(constantString) != IS_OBJECT) return true;
		zv::Val value = pt_type_op(Z_OBJ_P(constantString), PT_OP_GET_VALUE, 0, NULL);
		if (UNEXPECTED(value.isUndef())) return false;
		if (Z_TYPE_P(value.raw()) != IS_STRING) return true;
		const char *a;
		size_t aLen;
		zv::Str lowerValue = zv::Str::adopt(pt_veh_lower_symbol(Z_STR_P(value.raw()), &a, &aLen));
		const char *b;
		size_t bLen;
		zv::Str lowerDeclared = zv::Str::adopt(pt_veh_lower_symbol(declaredSymbolName, &b, &bLen));
		keep = aLen != bLen || memcmp(a, b, aLen) != 0;
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::VolatileExpressionHelper;

/* {{{ direct entries (support.h) */

bool pt_volatile_expression_helper_invalidate_volatile_function_calls(zval *expressionTypes, zval *nativeExpressionTypes)
{
	return VolatileExpressionHelper::invalidateVolatileFunctionCalls(expressionTypes, nativeExpressionTypes);
}

bool pt_volatile_expression_helper_invalidate_superglobals(zval *expressionTypes, zval *nativeExpressionTypes)
{
	return VolatileExpressionHelper::invalidateSuperglobals(expressionTypes, nativeExpressionTypes);
}

zv::Val pt_volatile_expression_helper_invalidate_negative_existence_checks(zval *scope, zval *expressionTypes, zval *nativeExpressionTypes, HashTable *functionNames, zend_string *declaredSymbolName)
{
	return VolatileExpressionHelper::invalidateNegativeExistenceChecks(scope, expressionTypes, nativeExpressionTypes, functionNames, declaredSymbolName);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_volatile_expression_helper()
{
	reg::Class cls("PHPStan\\Analyser\\VolatileExpressionHelper");
	ptdecl::VolatileExpressionHelper::declareClass(cls);
	ptdecl::VolatileExpressionHelper::declareProperties(cls);
	cls.privateClassConstantValue("VOLATILE_FUNCTION_NAMES", pt_veh_volatile_function_names_constant);
	cls.privateClassConstantValue("EXISTENCE_CHECK_FUNCTION_NAMES", pt_veh_existence_check_function_names_constant);

	cls.method(sigs::invalidateVolatileFunctionCalls, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionTypes, *nativeExpressionTypes;
		if (!zp::parse<zp::Zval, zp::Zval>(execute_data, expressionTypes, nativeExpressionTypes)) RETURN_THROWS();
		expressionTypes = pt_veh_array_ref(expressionTypes, 1);
		if (UNEXPECTED(expressionTypes == NULL)) RETURN_THROWS();
		nativeExpressionTypes = pt_veh_array_ref(nativeExpressionTypes, 2);
		if (UNEXPECTED(nativeExpressionTypes == NULL)) RETURN_THROWS();
		RETURN_BOOL(VolatileExpressionHelper::invalidateVolatileFunctionCalls(expressionTypes, nativeExpressionTypes));
	});

	cls.method(sigs::invalidateSuperglobals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionTypes, *nativeExpressionTypes;
		if (!zp::parse<zp::Zval, zp::Zval>(execute_data, expressionTypes, nativeExpressionTypes)) RETURN_THROWS();
		expressionTypes = pt_veh_array_ref(expressionTypes, 1);
		if (UNEXPECTED(expressionTypes == NULL)) RETURN_THROWS();
		nativeExpressionTypes = pt_veh_array_ref(nativeExpressionTypes, 2);
		if (UNEXPECTED(nativeExpressionTypes == NULL)) RETURN_THROWS();
		RETURN_BOOL(VolatileExpressionHelper::invalidateSuperglobals(expressionTypes, nativeExpressionTypes));
	});

	cls.method(sigs::invalidateNegativeExistenceChecks, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expressionTypes, *nativeExpressionTypes;
		HashTable *functionNames = NULL;
		zend_string *declaredSymbolName = NULL;
		if (!zp::parse<zp::Obj, zp::Zval, zp::Zval, zp::Opt<zp::Ht>, zp::Opt<zp::StrOrNull>>(execute_data, scope, expressionTypes, nativeExpressionTypes, functionNames, declaredSymbolName)) RETURN_THROWS();
		expressionTypes = pt_veh_array_ref(expressionTypes, 2);
		if (UNEXPECTED(expressionTypes == NULL)) RETURN_THROWS();
		nativeExpressionTypes = pt_veh_array_ref(nativeExpressionTypes, 3);
		if (UNEXPECTED(nativeExpressionTypes == NULL)) RETURN_THROWS();
		zv::Val result = VolatileExpressionHelper::invalidateNegativeExistenceChecks(scope, expressionTypes, nativeExpressionTypes, functionNames, declaredSymbolName);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.shadow(&pt_ce_volatile_expression_helper);
}

/* }}} */
