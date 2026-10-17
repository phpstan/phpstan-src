/*
 * The out-of-line part of the PHP-to-C++ compiler's runtime
 * (CodegenRuntime.h).
 */

#include "CodegenRuntime.h"

namespace pgrt {

zend_class_entry *lookupClassSlow(ClassSite &site, const char *name, size_t len)
{
	zend_string *str = zend_string_init(name, len, 0);
	zend_class_entry *ce = zend_lookup_class(str);
	zend_string_release(str);
	if (UNEXPECTED(ce == NULL)) {
		if (!EG(exception)) zend_throw_error(NULL, "Class \"%s\" not found", name);
		return NULL;
	}
	site = { ce, pt_engine_generation };
	return ce;
}

zval *staticSlotSlow(StaticSite &site, zend_class_entry *ce, const char *name, size_t len)
{
	if (CE_STATIC_MEMBERS(ce) == NULL) {
		zend_class_init_statics(ce);
	}
	zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&PT_CE(ce, properties_info), name, len);
	ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) != 0);
	site = { ce, CE_STATIC_MEMBERS(ce) + info->offset, pt_engine_generation };
	return site.slot;
}

bool callFunction(FunctionSite &site, const char *lcname, size_t len, zend_class_entry *scope, uint32_t argc, zval *argv, zval *ret)
{
	if (UNEXPECTED(site.fn == NULL || site.generation != pt_engine_generation)) {
		zend_function *fn = (zend_function *) zend_hash_str_find_ptr(EG(function_table), lcname, len);
		if (UNEXPECTED(fn == NULL)) {
			zend_throw_error(NULL, "Call to undefined function %s()", lcname);
			ZVAL_UNDEF(ret);
			return false;
		}
		site = { fn, pt_engine_generation };
	}
	const zend_class_entry *previousScope = EG(fake_scope);
	EG(fake_scope) = scope;
	zend_call_known_function(site.fn, NULL, NULL, ret, argc, argv, NULL);
	EG(fake_scope) = previousScope;
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(ret);
		ZVAL_UNDEF(ret);
		return false;
	}
	return true;
}

bool callStatic(pt_method_site &site, zend_class_entry *ce, const char *lcname, size_t len, uint32_t argc, zval *argv, zval *ret)
{
	if (UNEXPECTED(site.ce != ce || site.generation != pt_engine_generation || site.fn == NULL)) {
		zend_function *fn = pt_find_method(ce, lcname, len);
		if (UNEXPECTED(fn == NULL)) {
			ZVAL_UNDEF(ret);
			return false;
		}
		site = { ce, fn, pt_engine_generation };
	}
	zend_call_known_function(site.fn, NULL, ce, ret, argc, argv, NULL);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(ret);
		ZVAL_UNDEF(ret);
		return false;
	}
	return true;
}

bool callMethod(pt_method_site &site, zval *object, const char *name, const char *lcname, size_t len, uint32_t argc, zval *argv, zval *ret)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(object));
		ZVAL_UNDEF(ret);
		return false;
	}
	zv::Val result = pt_call_method_cached(site, Z_OBJ_P(object), lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) {
		ZVAL_UNDEF(ret);
		return false;
	}
	*ret = result.take();
	return true;
}

bool newObject(zend_class_entry *ce, uint32_t argc, zval *argv, zval *out)
{
	if (UNEXPECTED(object_init_ex(out, ce) != SUCCESS)) {
		ZVAL_UNDEF(out);
		return false;
	}
	zend_function *constructor = PT_CE(ce, constructor);
	if (constructor != NULL) {
		zend_call_known_instance_method(constructor, Z_OBJ_P(out), NULL, argc, argv);
		if (UNEXPECTED(EG(exception))) {
			zend_object_store_ctor_failed(Z_OBJ_P(out));
			zval_ptr_dtor(out);
			ZVAL_UNDEF(out);
			return false;
		}
	}
	return true;
}

void throwUninitializedProperty(zend_object *object, const char *name)
{
	zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(object->ce->name), name);
}

bool readPropertyGeneric(zval *value, zend_class_entry *scope, const char *name, size_t len, zval *rv)
{
	ZVAL_DEREF(value);
	if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
		zend_error(E_WARNING, "Attempt to read property \"%s\" on %s", name, zend_zval_value_name(value));
		ZVAL_NULL(rv);
		return !EG(exception);
	}
	zend_string *str = zend_string_init(name, len, 0);
	const zend_class_entry *previousScope = EG(fake_scope);
	EG(fake_scope) = scope;
	zval tmp;
	zval *read = Z_OBJ_HT_P(value)->read_property(Z_OBJ_P(value), str, BP_VAR_R, NULL, &tmp);
	EG(fake_scope) = previousScope;
	zend_string_release(str);
	if (UNEXPECTED(EG(exception))) {
		if (read == &tmp) zval_ptr_dtor(&tmp);
		ZVAL_UNDEF(rv);
		return false;
	}
	if (read == &tmp) {
		ZVAL_COPY_VALUE(rv, &tmp);
	} else {
		ZVAL_COPY_DEREF(rv, read);
	}
	return true;
}

bool initReadonly(zend_object *object, uint32_t slot, const char *name, zval *owned)
{
	zval *p = OBJ_PROP_NUM(object, slot);
	if (UNEXPECTED(Z_TYPE_P(p) != IS_UNDEF)) {
		zval_ptr_dtor(owned);
		zend_throw_error(NULL, "Cannot modify readonly property %s::$%s", ZSTR_VAL(object->ce->name), name);
		return false;
	}
	ZVAL_COPY_VALUE(p, owned);
	Z_PROP_FLAG_P(p) = 0;
	return true;
}

bool callValue(zval *value, uint32_t argc, zval *argv, zval *ret)
{
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	char *error = NULL;
	if (UNEXPECTED(zend_fcall_info_init(value, 0, &fci, &fcc, NULL, &error) != SUCCESS)) {
		if (Z_TYPE_P(value) == IS_OBJECT) {
			zend_throw_error(NULL, "Object of type %s is not callable", ZSTR_VAL(Z_OBJCE_P(value)->name));
		} else {
			zend_throw_error(NULL, "Value not callable");
		}
		if (error != NULL) efree(error);
		ZVAL_UNDEF(ret);
		return false;
	}
	if (error != NULL) efree(error);
	if (!pt_call_fci(&fci, &fcc, argc, argv, ret)) {
		ZVAL_UNDEF(ret);
		return false;
	}
	return true;
}

void throwReturnType(const char *function, const char *expected, zval *given)
{
	zend_type_error("%s(): Return value must be of type %s, %s returned", function, expected, zend_zval_value_name(given));
}

void throwArgumentType(const char *function, uint32_t position, const char *parameter, const char *expected, zval *given)
{
	zend_type_error("%s(): Argument #%u ($%s) must be of type %s, %s given", function, position, parameter, expected, zend_zval_value_name(given));
}

void throwTooFewArguments(const char *function, uint32_t passed, uint32_t required)
{
	zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function %s(), %u passed and %s %u expected", function, passed, "exactly", required);
}

void warnUndefinedIndex(zend_long key)
{
	zend_error(E_WARNING, "Undefined array key " ZEND_LONG_FMT, key);
}

static zend_always_inline zval *mergeSource(zval *entry)
{
	if (UNEXPECTED(Z_ISREF_P(entry)) && UNEXPECTED(Z_REFCOUNT_P(entry) == 1)) {
		entry = Z_REFVAL_P(entry);
	}
	Z_TRY_ADDREF_P(entry);
	return entry;
}

/* php_array_merge(): list values appended, string keys overwritten */
static void mergeInto(HashTable *dest, HashTable *src)
{
	zval *entry;
	zend_string *key;
	ZEND_HASH_FOREACH_STR_KEY_VAL(src, key, entry) {
		entry = mergeSource(entry);
		if (UNEXPECTED(key != NULL)) {
			zend_hash_update(dest, key, entry);
		} else {
			zend_hash_next_index_insert_new(dest, entry);
		}
	}
	ZEND_HASH_FOREACH_END();
}

bool arrayMerge(uint32_t argc, zval *argv, zval *ret)
{
	if (argc == 0) {
		ZVAL_EMPTY_ARRAY(ret);
		return true;
	}
	uint32_t count = 0;
	for (uint32_t i = 0; i < argc; i++) {
		zval *arg = &argv[i];
		ZVAL_DEREF(arg);
		if (UNEXPECTED(Z_TYPE_P(arg) != IS_ARRAY)) {
			zend_type_error("array_merge(): Argument #%u must be of type array, %s given", i + 1, zend_zval_value_name(arg));
			ZVAL_UNDEF(ret);
			return false;
		}
		count += zend_hash_num_elements(Z_ARRVAL_P(arg));
	}
	if (argc == 2) {
		/* the builtin's shortcut: merging into or from [] returns the other
		 * list as is */
		zval *a = &argv[0];
		zval *b = &argv[1];
		ZVAL_DEREF(a);
		ZVAL_DEREF(b);
		zval *only = zend_hash_num_elements(Z_ARRVAL_P(a)) == 0 ? b : (zend_hash_num_elements(Z_ARRVAL_P(b)) == 0 ? a : NULL);
		if (only != NULL && HT_IS_PACKED(Z_ARRVAL_P(only)) && HT_IS_WITHOUT_HOLES(Z_ARRVAL_P(only))) {
			ZVAL_COPY(ret, only);
			return true;
		}
	}
	array_init_size(ret, count);
	for (uint32_t i = 0; i < argc; i++) {
		zval *arg = &argv[i];
		ZVAL_DEREF(arg);
		mergeInto(Z_ARRVAL_P(ret), Z_ARRVAL_P(arg));
	}
	return true;
}

bool arrayUnique(zval *array, zval *ret)
{
	ZVAL_DEREF(array);
	if (UNEXPECTED(Z_TYPE_P(array) != IS_ARRAY)) {
		zend_type_error("array_unique(): Argument #1 ($array) must be of type array, %s given", zend_zval_value_name(array));
		ZVAL_UNDEF(ret);
		return false;
	}
	HashTable *input = Z_ARRVAL_P(array);
	if (zend_hash_num_elements(input) <= 1) {
		ZVAL_COPY(ret, array);
		return true;
	}
	HashTable seen;
	zend_hash_init(&seen, zend_hash_num_elements(input), NULL, NULL, 0);
	array_init(ret);
	zend_ulong index;
	zend_string *key;
	zval *value;
	ZEND_HASH_FOREACH_KEY_VAL(input, index, key, value) {
		zval *added;
		if (Z_TYPE_P(value) == IS_STRING) {
			added = zend_hash_add_empty_element(&seen, Z_STR_P(value));
		} else {
			zend_string *tmp;
			zend_string *str = zval_get_tmp_string(value, &tmp);
			added = zend_hash_add_empty_element(&seen, str);
			zend_tmp_string_release(tmp);
		}
		if (added != NULL) {
			if (UNEXPECTED(Z_ISREF_P(value) && Z_REFCOUNT_P(value) == 1)) {
				ZVAL_DEREF(value);
			}
			Z_TRY_ADDREF_P(value);
			if (key != NULL) {
				zend_hash_add_new(Z_ARRVAL_P(ret), key, value);
			} else {
				zend_hash_index_add_new(Z_ARRVAL_P(ret), index, value);
			}
		}
	}
	ZEND_HASH_FOREACH_END();
	zend_hash_destroy(&seen);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(ret);
		ZVAL_UNDEF(ret);
		return false;
	}
	return true;
}

bool arrayValues(zval *array, zval *ret)
{
	ZVAL_DEREF(array);
	if (UNEXPECTED(Z_TYPE_P(array) != IS_ARRAY)) {
		zend_type_error("array_values(): Argument #1 ($array) must be of type array, %s given", zend_zval_value_name(array));
		ZVAL_UNDEF(ret);
		return false;
	}
	HashTable *input = Z_ARRVAL_P(array);
	uint32_t count = zend_hash_num_elements(input);
	if (count == 0) {
		ZVAL_EMPTY_ARRAY(ret);
		return true;
	}
	if (HT_IS_PACKED(input) && HT_IS_WITHOUT_HOLES(input) && input->nNextFreeElement == (zend_long) count) {
		ZVAL_COPY(ret, array);
		return true;
	}
	array_init_size(ret, count);
	zend_hash_real_init_packed(Z_ARRVAL_P(ret));
	zval *entry;
	ZEND_HASH_FOREACH_VAL(input, entry) {
		zend_hash_next_index_insert_new(Z_ARRVAL_P(ret), mergeSource(entry));
	}
	ZEND_HASH_FOREACH_END();
	return true;
}

bool arrayMinMax(FunctionSite &site, bool max, zval *array, zend_class_entry *scope, zval *ret)
{
	zval *deref = array;
	ZVAL_DEREF(deref);
	if (EXPECTED(Z_TYPE_P(deref) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(deref)) > 0)) {
		zval *best = NULL;
		zval *entry;
		bool allLongs = true;
		ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(deref), entry) {
			if (UNEXPECTED(Z_TYPE_P(entry) != IS_LONG)) {
				allLongs = false;
				break;
			}
			/* zend_hash_minmax(): a later element replaces only a strictly better one */
			if (best == NULL || (max ? Z_LVAL_P(entry) > Z_LVAL_P(best) : Z_LVAL_P(entry) < Z_LVAL_P(best))) {
				best = entry;
			}
		}
		ZEND_HASH_FOREACH_END();
		if (allLongs) {
			ZVAL_LONG(ret, Z_LVAL_P(best));
			return true;
		}
	}
	return max ? callFunction(site, "max", 3, scope, 1, array, ret) : callFunction(site, "min", 3, scope, 1, array, ret);
}

zend_string *internLiteral(const char *s, size_t len)
{
	return zend_string_init_interned(s, len, 1);
}

} // namespace pgrt
