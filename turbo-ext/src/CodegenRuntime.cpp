/*
 * The out-of-line part of the PHP-to-C++ compiler's runtime
 * (CodegenRuntime.h).
 */

#include "CodegenRuntime.h"
#include "TypeTraits.h"


#ifdef PGRT_COUNT
#include <map>
#include <string>
#include <cstdio>
#include <cstdlib>
#include <vector>
#include <algorithm>
namespace {
std::map<std::string, unsigned long> *pgrtCounts;
void pgrtDump()
{
	std::vector<std::pair<unsigned long, std::string>> rows;
	for (auto &e : *pgrtCounts) rows.emplace_back(e.second, e.first);
	std::sort(rows.rbegin(), rows.rend());
	FILE *out = fopen(getenv("PGRT_COUNT_OUT") ? getenv("PGRT_COUNT_OUT") : "/dev/stderr", "a");
	for (size_t i = 0; i < rows.size() && i < 200; i++) fprintf(out, "%10lu %s\n", rows[i].first, rows[i].second.c_str());
	fclose(out);
}
}
#define PGRT_COUNT_HIT(kind, cls, name) do { if (pgrtCounts == nullptr) { pgrtCounts = new std::map<std::string, unsigned long>(); atexit(pgrtDump); } (*pgrtCounts)[std::string(kind) + " " + (cls) + "::" + (name)]++; } while (0)
#else
#define PGRT_COUNT_HIT(kind, cls, name) do { } while (0)
#endif

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
	site = { ce, pt_engine_generation, 0 };
	return ce;
}

zend_class_entry *lookupClassQuietSlow(ClassSite &site, const char *name, size_t len)
{
	zend_string *str = zend_string_init(name, len, 0);
	zend_class_entry *ce = zend_lookup_class_ex(str, NULL, ZEND_FETCH_CLASS_NO_AUTOLOAD);
	zend_string_release(str);
	site = { ce, pt_engine_generation, ce == NULL ? zend_hash_num_elements(EG(class_table)) : 0 };
	return ce;
}

uint32_t propIndexSlow(PropIndexSite &site, zend_object *object, const char *name, size_t len)
{
	zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&PT_CE(object->ce, properties_info), name, len);
	ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) == 0);
	site = { object->ce, (uint32_t) OBJ_PROP_TO_NUM(info->offset), pt_engine_generation };
	return site.index;
}

zval *propCachedSlow(PropSite &site, zend_object *object, const char *name, size_t len)
{
	/* public declared instance properties only: the compiler may speculate
	 * on a PHPDoc class, and another class's private property of the same
	 * name must go through the handlers (and their visibility check) */
	zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&PT_CE(object->ce, properties_info), name, len);
	if (info == NULL || (info->flags & (ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)) != ZEND_ACC_PUBLIC) return NULL;
	int32_t offset = (int32_t) info->offset;
	if (site.generation != pt_engine_generation) {
		site = {};
		site.generation = pt_engine_generation;
	}
	uint32_t way = site.next++ % PROP_SITE_WAYS;
	site.ce[way] = object->ce;
	site.offset[way] = (uint32_t) offset;
	return OBJ_PROP(object, offset);
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

bool callFunction(FunctionSite &site, const char *lcname, size_t len, zend_class_entry *scope, uint32_t argc, zval *argv, zval *ret, HashTable *named)
{
	PGRT_COUNT_HIT("F", "", lcname);
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
	zend_call_known_function(site.fn, NULL, NULL, ret, argc, argv, named);
	EG(fake_scope) = previousScope;
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(ret);
		ZVAL_UNDEF(ret);
		return false;
	}
	return true;
}

bool callStatic(pt_method_site &site, zend_class_entry *ce, const char *lcname, size_t len, uint32_t argc, zval *argv, zval *ret, HashTable *named)
{
	PGRT_COUNT_HIT("S", ZSTR_VAL(ce->name), lcname);
	if (UNEXPECTED(site.ce != ce || site.generation != pt_engine_generation || site.fn == NULL)) {
		zend_function *fn = pt_find_method(ce, lcname, len);
		if (UNEXPECTED(fn == NULL)) {
			ZVAL_UNDEF(ret);
			return false;
		}
		site = { ce, fn, pt_engine_generation };
	}
	zend_call_known_function(site.fn, NULL, ce, ret, argc, argv, named);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(ret);
		ZVAL_UNDEF(ret);
		return false;
	}
	return true;
}

bool callMethod(pt_method_site &site, zval *object, const char *name, const char *lcname, size_t len, uint32_t argc, zval *argv, zval *ret, HashTable *named)
{
	PGRT_COUNT_HIT("M", Z_TYPE_P(object) == IS_OBJECT ? ZSTR_VAL(Z_OBJCE_P(object)->name) : "?", lcname);
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(object));
		ZVAL_UNDEF(ret);
		return false;
	}
	if (UNEXPECTED(named != NULL)) {
		zend_function *fn = pt_find_method(Z_OBJCE_P(object), lcname, len);
		if (UNEXPECTED(fn == NULL)) {
			ZVAL_UNDEF(ret);
			return false;
		}
		zend_call_known_function(fn, Z_OBJ_P(object), Z_OBJCE_P(object), ret, argc, argv, named);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(ret);
			ZVAL_UNDEF(ret);
			return false;
		}
		return true;
	}
	zv::Val result = pt_call_method_cached(site, Z_OBJ_P(object), lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) {
		ZVAL_UNDEF(ret);
		return false;
	}
	*ret = result.take();
	return true;
}

bool callBound(pt_method_site &site, zend_class_entry *ce, zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv, zval *ret, HashTable *named)
{
	PGRT_COUNT_HIT("B", ZSTR_VAL(ce->name), lcname);
	if (UNEXPECTED(site.ce != ce || site.generation != pt_engine_generation || site.fn == NULL)) {
		zend_function *fn = pt_find_method(ce, lcname, len);
		if (UNEXPECTED(fn == NULL)) {
			ZVAL_UNDEF(ret);
			return false;
		}
		site = { ce, fn, pt_engine_generation };
	}
	zend_call_known_function(site.fn, object, object->ce, ret, argc, argv, named);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(ret);
		ZVAL_UNDEF(ret);
		return false;
	}
	return true;
}

bool callInheritedConstructor(zend_object *object)
{
	zend_function *constructor = PT_CE(object->ce, constructor);
	if (constructor == NULL) return true;
	zend_call_known_function(constructor, object, object->ce, NULL, 0, NULL, NULL);
	return !EG(exception);
}

bool newObject(zend_class_entry *ce, uint32_t argc, zval *argv, zval *out, HashTable *named)
{
	if (UNEXPECTED(object_init_ex(out, ce) != SUCCESS)) {
		ZVAL_UNDEF(out);
		return false;
	}
	zend_function *constructor = PT_CE(ce, constructor);
	if (constructor != NULL) {
		zend_call_known_function(constructor, Z_OBJ_P(out), Z_OBJCE_P(out), NULL, argc, argv, named);
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
	/* the engine names the declaring class, not the object's */
	zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&PT_CE(object->ce, properties_info), name, strlen(name));
	zend_class_entry *declaring = info != NULL ? info->ce : object->ce;
	zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(declaring->name), name);
}

bool readPropertyGeneric(zval *value, zend_class_entry *scope, const char *name, size_t len, zval *rv)
{
	PGRT_COUNT_HIT("P", Z_TYPE_P(value) == IS_OBJECT ? ZSTR_VAL(Z_OBJCE_P(value)->name) : "?", name);
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

bool callableOf(zval *value, Callable &out)
{
	char *error = NULL;
	if (UNEXPECTED(zend_fcall_info_init(value, 0, &out.fci, &out.fcc, NULL, &error) != SUCCESS)) {
		zend_type_error("Value of type %s is not callable", zend_zval_value_name(value));
		if (error != NULL) efree(error);
		return false;
	}
	if (error != NULL) efree(error);
	return true;
}

bool callValue(zval *value, uint32_t argc, zval *argv, zval *ret, HashTable *named)
{
	if (EXPECTED(named == NULL)) {
		/* native closures, holders and bound internal closures are entered
		 * directly; anything else goes through the engine */
		zv::Val result = pt_type_call_callable(value, argc, argv);
		if (UNEXPECTED(result.isUndef())) {
			ZVAL_UNDEF(ret);
			return false;
		}
		*ret = result.take();
		return true;
	}
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
	Callable callable{fci, fcc};
	return callCallable(callable, argc, argv, ret, named);
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

zval *classConstant(zend_class_entry *ce, zval *name, zend_class_entry *scope)
{
	return zend_get_class_constant_ex(ce->name, Z_STR_P(name), scope, 0);
}

zval *dimWrite(zval *array, zval *key)
{
	HashTable *ht = Z_ARRVAL_P(array);
	zval none;
	ZVAL_NULL(&none);
	switch (Z_TYPE_P(key)) {
		case IS_STRING: {
			zval *found = zend_symtable_find(ht, Z_STR_P(key));
			return found != NULL ? found : zend_symtable_add_new(ht, Z_STR_P(key), &none);
		}
		case IS_LONG:
			return dimWriteLong(array, Z_LVAL_P(key));
		case IS_NULL: {
			zval *found = zend_hash_find(ht, ZSTR_EMPTY_ALLOC());
			return found != NULL ? found : zend_hash_add_new(ht, ZSTR_EMPTY_ALLOC(), &none);
		}
		case IS_FALSE:
			return dimWriteLong(array, 0);
		case IS_TRUE:
			return dimWriteLong(array, 1);
		case IS_REFERENCE:
			return dimWrite(array, Z_REFVAL_P(key));
		default:
			zend_type_error("Cannot access offset of type %s on array", zend_zval_value_name(key));
			return NULL;
	}
}

bool writePropertyGeneric(zval *object, zend_class_entry *scope, zval *name, zval *value)
{
	ZVAL_DEREF(object);
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Attempt to assign property \"%s\" on %s", Z_STRVAL_P(name), zend_zval_value_name(object));
		return false;
	}
	const zend_class_entry *previousScope = EG(fake_scope);
	EG(fake_scope) = scope;
	Z_OBJ_HT_P(object)->write_property(Z_OBJ_P(object), Z_STR_P(name), value, NULL);
	EG(fake_scope) = previousScope;
	return !EG(exception);
}

zval *dimRead(zval *base, zval *key, zval *tmp)
{
	ZVAL_DEREF(base);
	ZVAL_DEREF(key);
	if (EXPECTED(Z_TYPE_P(base) == IS_ARRAY)) {
		HashTable *ht = Z_ARRVAL_P(base);
		zval *found;
		switch (Z_TYPE_P(key)) {
			case IS_LONG:
				found = zend_hash_index_find(ht, Z_LVAL_P(key));
				if (found == NULL) {
					zend_error(E_WARNING, "Undefined array key " ZEND_LONG_FMT, Z_LVAL_P(key));
				}
				break;
			case IS_STRING:
				found = zend_symtable_find(ht, Z_STR_P(key));
				if (found == NULL) {
					zend_error(E_WARNING, "Undefined array key \"%s\"", Z_STRVAL_P(key));
				}
				break;
			case IS_NULL:
				found = zend_hash_find(ht, ZSTR_EMPTY_ALLOC());
				if (found == NULL) {
					zend_error(E_WARNING, "Undefined array key \"\"");
				}
				break;
			case IS_FALSE:
			case IS_TRUE: {
				zend_long index = Z_TYPE_P(key) == IS_TRUE ? 1 : 0;
				found = zend_hash_index_find(ht, index);
				if (found == NULL) {
					zend_error(E_WARNING, "Undefined array key " ZEND_LONG_FMT, index);
				}
				break;
			}
			default:
				zend_type_error("Cannot access offset of type %s on array", zend_zval_value_name(key));
				return NULL;
		}
		if (UNEXPECTED(EG(exception))) return NULL;
		return found != NULL ? found : &EG(uninitialized_zval);
	}
	if (Z_TYPE_P(base) == IS_OBJECT) {
		zend_object *object = Z_OBJ_P(base);
		zval *read = object->handlers->read_dimension(object, key, BP_VAR_R, tmp);
		if (UNEXPECTED(EG(exception))) return NULL;
		if (read == NULL) return &EG(uninitialized_zval);
		if (read != tmp) {
			ZVAL_COPY_DEREF(tmp, read);
		}
		return tmp;
	}
	if (Z_TYPE_P(base) == IS_STRING && Z_TYPE_P(key) == IS_LONG) {
		zend_long offset = Z_LVAL_P(key);
		if (offset < 0) offset += (zend_long) Z_STRLEN_P(base);
		if (offset < 0 || (size_t) offset >= Z_STRLEN_P(base)) {
			zend_error(E_WARNING, "Uninitialized string offset " ZEND_LONG_FMT, Z_LVAL_P(key));
			ZVAL_EMPTY_STRING(tmp);
			return EG(exception) ? NULL : tmp;
		}
		ZVAL_CHAR(tmp, Z_STRVAL_P(base)[offset]);
		return tmp;
	}
	if (Z_TYPE_P(base) == IS_STRING) {
		zend_throw_error(NULL, "phpstan_turbo codegen: this string offset is not compiled");
		return NULL;
	}
	zend_error(E_WARNING, "Trying to access array offset on %s", zend_zval_value_name(base));
	return EG(exception) ? NULL : &EG(uninitialized_zval);
}

bool cloneObject(zval *value, zval *out)
{
	ZVAL_DEREF(value);
	if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
		zend_throw_error(NULL, "__clone method called on non-object");
		return false;
	}
	zend_object *object = Z_OBJ_P(value);
	if (UNEXPECTED(object->handlers->clone_obj == NULL)) {
		zend_throw_error(NULL, "Trying to clone an uncloneable object of class %s", ZSTR_VAL(object->ce->name));
		return false;
	}
	zend_object *clone = object->handlers->clone_obj(object);
	if (UNEXPECTED(EG(exception))) {
		if (clone != NULL) OBJ_RELEASE(clone);
		return false;
	}
	ZVAL_OBJ(out, clone);
	return true;
}

bool spreadInto(zval *array, zval *value)
{
	ZVAL_DEREF(value);
	if (UNEXPECTED(Z_TYPE_P(value) != IS_ARRAY)) {
		zend_throw_error(NULL, "Only arrays and Traversables can be unpacked");
		return false;
	}
	zend_ulong index;
	zend_string *key;
	zval *entry;
	ZEND_HASH_FOREACH_KEY_VAL(Z_ARRVAL_P(value), index, key, entry) {
		(void) index;
		if (Z_ISREF_P(entry) && Z_REFCOUNT_P(entry) == 1) {
			entry = Z_REFVAL_P(entry);
		}
		Z_TRY_ADDREF_P(entry);
		if (key != NULL) {
			zend_hash_update(Z_ARRVAL_P(array), key, entry);
		} else if (UNEXPECTED(zend_hash_next_index_insert(Z_ARRVAL_P(array), entry) == NULL)) {
			zval_ptr_dtor(entry);
			zend_throw_error(NULL, "Cannot add element to the array as the next element is already occupied");
			return false;
		}
	}
	ZEND_HASH_FOREACH_END();
	return true;
}

zval *dimIs(zval *base, zval *key, zval *tmp)
{
	ZVAL_DEREF(base);
	if (Z_TYPE_P(base) == IS_ARRAY) {
		HashTable *ht = Z_ARRVAL_P(base);
		zval *found = NULL;
		switch (Z_TYPE_P(key)) {
			case IS_STRING:
				found = zend_symtable_find(ht, Z_STR_P(key));
				break;
			case IS_LONG:
				found = zend_hash_index_find(ht, Z_LVAL_P(key));
				break;
			case IS_NULL:
				found = zend_hash_find(ht, ZSTR_EMPTY_ALLOC());
				break;
			case IS_FALSE:
				found = zend_hash_index_find(ht, 0);
				break;
			case IS_TRUE:
				found = zend_hash_index_find(ht, 1);
				break;
			case IS_DOUBLE:
				found = zend_hash_index_find(ht, zend_dval_to_lval(Z_DVAL_P(key)));
				break;
			default:
				return NULL;
		}
		if (found == NULL) return NULL;
		ZVAL_DEREF(found);
		return found;
	}
	if (Z_TYPE_P(base) == IS_OBJECT) {
		zend_object *object = Z_OBJ_P(base);
		if (!object->handlers->has_dimension(object, key, 0)) return NULL;
		if (UNEXPECTED(EG(exception))) return NULL;
		zval *read = object->handlers->read_dimension(object, key, BP_VAR_IS, tmp);
		if (read == NULL || UNEXPECTED(EG(exception))) return NULL;
		if (read != tmp) {
			ZVAL_COPY_DEREF(tmp, read);
		}
		return tmp;
	}
	if (Z_TYPE_P(base) == IS_STRING && Z_TYPE_P(key) == IS_LONG) {
		zend_long offset = Z_LVAL_P(key);
		if (offset < 0) offset += (zend_long) Z_STRLEN_P(base);
		if (offset < 0 || (size_t) offset >= Z_STRLEN_P(base)) return NULL;
		ZVAL_CHAR(tmp, Z_STRVAL_P(base)[offset]);
		return tmp;
	}
	return NULL;
}

zval *propIs(zval *base, zend_class_entry *scope, zval *name, zval *tmp)
{
	ZVAL_DEREF(base);
	if (Z_TYPE_P(base) != IS_OBJECT) return NULL;
	zend_object *object = Z_OBJ_P(base);
	const zend_class_entry *previousScope = EG(fake_scope);
	EG(fake_scope) = scope;
	zval *result = NULL;
	if (object->handlers->has_property(object, Z_STR_P(name), ZEND_PROPERTY_EXISTS, NULL) && !EG(exception)) {
		zval *read = object->handlers->read_property(object, Z_STR_P(name), BP_VAR_IS, NULL, tmp);
		if (read != NULL && !EG(exception)) {
			if (read != tmp) {
				ZVAL_COPY_DEREF(tmp, read);
			}
			result = tmp;
		}
	}
	EG(fake_scope) = previousScope;
	return result;
}

bool concat(zval *a, zval *b, zval *out)
{
	if (EXPECTED(Z_TYPE_P(a) == IS_STRING && Z_TYPE_P(b) == IS_STRING)) {
		ZVAL_STR(out, zend_string_concat2(Z_STRVAL_P(a), Z_STRLEN_P(a), Z_STRVAL_P(b), Z_STRLEN_P(b)));
		return true;
	}
	if (UNEXPECTED(concat_function(out, a, b) != SUCCESS) || UNEXPECTED(EG(exception))) {
		ZVAL_UNDEF(out);
		return false;
	}
	return true;
}

bool castString(zval *value)
{
	if (Z_TYPE_P(value) == IS_STRING) return true;
	zend_string *str = zval_try_get_string(value);
	if (UNEXPECTED(str == NULL)) return false;
	zval_ptr_dtor(value);
	ZVAL_STR(value, str);
	return true;
}

bool throwIntegerOverflow()
{
	zend_throw_error(NULL, "phpstan_turbo codegen: integer overflow of an int-typed value");
	return false;
}

bool getClass(zval *object, zval *ret)
{
	zval *value = object;
	ZVAL_DEREF(value);
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT)) {
		ZVAL_STR_COPY(ret, Z_OBJCE_P(value)->name);
		return true;
	}
	zend_type_error("get_class(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(value));
	ZVAL_UNDEF(ret);
	return false;
}

bool inArrayStrict(zval *needle, zval *array)
{
	for (auto entry : zv::ArrRef(array)) {
		if (zend_is_identical(needle, entry.value().deref().raw())) return true;
	}
	return false;
}

bool arrayEdge(FunctionSite &site, const char *name, size_t len, bool last, zval *array, zval *ret)
{
	zval *value = array;
	ZVAL_DEREF(value);
	if (UNEXPECTED(Z_TYPE_P(value) != IS_ARRAY)) {
		return callFunction(site, name, len, NULL, 1, array, ret);
	}
	HashTable *table = Z_ARRVAL_P(value);
	if (zend_hash_num_elements(table) == 0) {
		ZVAL_NULL(ret);
		return true;
	}
	HashPosition position;
	if (last) {
		zend_hash_internal_pointer_end_ex(table, &position);
	} else {
		zend_hash_internal_pointer_reset_ex(table, &position);
	}
	ZVAL_COPY_DEREF(ret, zend_hash_get_current_data_ex(table, &position));
	return true;
}

bool strMatch(FunctionSite &site, const char *name, size_t len, StrMatch kind, zval *haystack, zval *needle, zval *ret)
{
	if (UNEXPECTED(Z_TYPE_P(haystack) != IS_STRING || Z_TYPE_P(needle) != IS_STRING)) {
		zval argv[2];
		ZVAL_COPY_VALUE(&argv[0], haystack);
		ZVAL_COPY_VALUE(&argv[1], needle);
		return callFunction(site, name, len, NULL, 2, argv, ret);
	}
	zend_string *h = Z_STR_P(haystack);
	zend_string *n = Z_STR_P(needle);
	bool result;
	switch (kind) {
		case StrMatch::STARTS:
			result = ZSTR_LEN(n) <= ZSTR_LEN(h) && memcmp(ZSTR_VAL(h), ZSTR_VAL(n), ZSTR_LEN(n)) == 0;
			break;
		case StrMatch::ENDS:
			result = ZSTR_LEN(n) <= ZSTR_LEN(h) && memcmp(ZSTR_VAL(h) + ZSTR_LEN(h) - ZSTR_LEN(n), ZSTR_VAL(n), ZSTR_LEN(n)) == 0;
			break;
		default:
			result = ZSTR_LEN(n) == 0 || zend_memnstr(ZSTR_VAL(h), ZSTR_VAL(n), ZSTR_LEN(n), ZSTR_VAL(h) + ZSTR_LEN(h)) != NULL;
			break;
	}
	ZVAL_BOOL(ret, result);
	return true;
}

void arrayPop(zval *array, zval *ret)
{
	HashTable *table = Z_ARRVAL_P(array);
	if (zend_hash_num_elements(table) == 0) {
		ZVAL_NULL(ret);
		return;
	}
	HashPosition position;
	zend_hash_internal_pointer_end_ex(table, &position);
	zval *value = zend_hash_get_current_data_ex(table, &position);
	zend_string *key;
	zend_ulong index;
	int type = zend_hash_get_current_key_ex(table, &key, &index, &position);
	ZVAL_COPY_DEREF(ret, value);
	if (type == HASH_KEY_IS_STRING) {
		zend_hash_del(table, key);
	} else {
		if ((zend_long) index == table->nNextFreeElement - 1) {
			table->nNextFreeElement = table->nNextFreeElement - 1;
		}
		zend_hash_index_del(table, index);
	}
	zend_hash_internal_pointer_reset(table);
}

/* {{{ php-parser node methods */

namespace {

enum class NodeShape : uint8_t { NAME, CALL, ATTRIBUTES };

/* per (class, shape): the slot the intrinsic reads, -1 when the class does
 * not keep php-parser's implementation */
struct NodeShapeEntry
{
	zend_class_entry *ce;
	uint32_t generation;
	NodeShape shape;
	int32_t offset;
};

constexpr unsigned NODE_SHAPE_CACHE_BITS = 9;
NodeShapeEntry nodeShapeCache[1u << NODE_SHAPE_CACHE_BITS];

zend_class_entry *methodScope(zend_class_entry *ce, const char *lcname, size_t len)
{
	zend_function *fn = (zend_function *) zend_hash_str_find_ptr(&PT_CE(ce, function_table), lcname, len);
	return fn != NULL ? fn->common.scope : NULL;
}

int32_t publicOffset(zend_class_entry *ce, const char *name, size_t len)
{
	zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&PT_CE(ce, properties_info), name, len);
	if (info == NULL || (info->flags & (ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)) != ZEND_ACC_PUBLIC) return -1;
	return (int32_t) info->offset;
}

int32_t nodeShapeSlow(zend_class_entry *ce, NodeShape shape)
{
	switch (shape) {
		case NodeShape::NAME: {
			zend_class_entry *name = pt_class(PT_CLASS_NAME);
			zend_class_entry *identifier = pt_class(PT_CLASS_IDENTIFIER);
			zend_class_entry *own = name != NULL && instanceof_function(ce, name) ? name
				: (identifier != NULL && instanceof_function(ce, identifier) ? identifier : NULL);
			if (own == NULL || methodScope(ce, PT_LC("tostring")) != own || methodScope(ce, PT_LC("tolowerstring")) != own) return -1;
			return publicOffset(ce, PT_LC("name"));
		}
		case NodeShape::CALL: {
			zend_class_entry *callLike = pt_class(PT_CLASS_CALL_LIKE);
			if (callLike == NULL || !instanceof_function(ce, callLike)) return -1;
			if (methodScope(ce, PT_LC("isfirstclasscallable")) != callLike || methodScope(ce, PT_LC("getargs")) != callLike) return -1;
			zend_class_entry *raw = methodScope(ce, PT_LC("getrawargs"));
			if (raw == NULL || (raw != pt_class(PT_CLASS_FUNC_CALL) && raw != pt_class(PT_CLASS_METHOD_CALL) && raw != pt_class(PT_CLASS_STATIC_CALL)
				&& raw != pt_class(PT_CLASS_NULLSAFE_METHOD_CALL) && raw != pt_class(PT_CLASS_NEW))) return -1;
			return publicOffset(ce, PT_LC("args"));
		}
		case NodeShape::ATTRIBUTES: {
			zend_class_entry *nodeAbstract = pt_class(PT_CLASS_NODE_ABSTRACT);
			if (nodeAbstract == NULL || !instanceof_function(ce, nodeAbstract)) return -1;
			if (methodScope(ce, PT_LC("setattribute")) != nodeAbstract || methodScope(ce, PT_LC("getattribute")) != nodeAbstract || methodScope(ce, PT_LC("hasattribute")) != nodeAbstract) return -1;
			zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&PT_CE(ce, properties_info), PT_LC("attributes"));
			if (info == NULL || (info->flags & ZEND_ACC_STATIC) != 0) return -1;
			return (int32_t) info->offset;
		}
	}
	return -1;
}

zend_always_inline int32_t nodeShape(zend_class_entry *ce, NodeShape shape)
{
	uintptr_t h = (((uintptr_t) ce >> 4) ^ (uintptr_t) shape) * (uintptr_t) 0x9E3779B97F4A7C15ull;
	NodeShapeEntry &entry = nodeShapeCache[(size_t) (h >> (sizeof(uintptr_t) * 8 - NODE_SHAPE_CACHE_BITS))];
	if (EXPECTED(entry.ce == ce && entry.shape == shape && entry.generation == pt_engine_generation)) return entry.offset;
	int32_t offset = nodeShapeSlow(ce, shape);
	entry = { ce, pt_engine_generation, shape, offset };
	return offset;
}

/* the slot of `node` for the shape, dereferenced; NULL for the engine path */
zend_always_inline zval *shapeSlot(zval *node, NodeShape shape, uint8_t wantedType)
{
	if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) return NULL;
	int32_t offset = nodeShape(Z_OBJCE_P(node), shape);
	if (UNEXPECTED(offset < 0)) return NULL;
	zval *slot = OBJ_PROP(Z_OBJ_P(node), offset);
	ZVAL_DEREF(slot);
	return EXPECTED(Z_TYPE_P(slot) == wantedType) ? slot : NULL;
}

bool viaEngine(pt_method_site &site, zval *node, const char *name, const char *lcname, size_t len, uint32_t argc, zval *argv, zval *ret)
{
	return callMethod(site, node, name, lcname, len, argc, argv, ret, NULL);
}

} // namespace

bool nodeSetAttribute(pt_method_site &site, zval *node, zval *key, zval *value, zval *ret)
{
	zval *attributes = Z_TYPE_P(key) == IS_STRING ? shapeSlot(node, NodeShape::ATTRIBUTES, IS_ARRAY) : NULL;
	if (EXPECTED(attributes != NULL)) {
		SEPARATE_ARRAY(attributes);
		Z_TRY_ADDREF_P(value);
		zend_symtable_update(Z_ARRVAL_P(attributes), Z_STR_P(key), value);
		ZVAL_NULL(ret);
		return true;
	}
	zval argv[2];
	ZVAL_COPY_VALUE(&argv[0], key);
	ZVAL_COPY_VALUE(&argv[1], value);
	return viaEngine(site, node, "setAttribute", "setattribute", 12, 2, argv, ret);
}

bool nodeGetAttribute(pt_method_site &site, zval *node, zval *key, zval *defaultValue, zval *ret)
{
	zval *attributes = Z_TYPE_P(key) == IS_STRING ? shapeSlot(node, NodeShape::ATTRIBUTES, IS_ARRAY) : NULL;
	if (EXPECTED(attributes != NULL)) {
		zval *found = zend_symtable_find(Z_ARRVAL_P(attributes), Z_STR_P(key));
		if (found != NULL) {
			ZVAL_COPY_DEREF(ret, found);
		} else if (defaultValue != NULL) {
			ZVAL_COPY(ret, defaultValue);
		} else {
			ZVAL_NULL(ret);
		}
		return true;
	}
	zval argv[2];
	ZVAL_COPY_VALUE(&argv[0], key);
	if (defaultValue != NULL) ZVAL_COPY_VALUE(&argv[1], defaultValue);
	return viaEngine(site, node, "getAttribute", "getattribute", 12, defaultValue != NULL ? 2 : 1, argv, ret);
}

bool nodeHasAttribute(pt_method_site &site, zval *node, zval *key, zval *ret)
{
	zval *attributes = Z_TYPE_P(key) == IS_STRING ? shapeSlot(node, NodeShape::ATTRIBUTES, IS_ARRAY) : NULL;
	if (EXPECTED(attributes != NULL)) {
		ZVAL_BOOL(ret, zend_symtable_exists(Z_ARRVAL_P(attributes), Z_STR_P(key)));
		return true;
	}
	return viaEngine(site, node, "hasAttribute", "hasattribute", 12, 1, key, ret);
}

bool nameToString(pt_method_site &site, zval *node, zval *ret)
{
	zval *name = shapeSlot(node, NodeShape::NAME, IS_STRING);
	if (EXPECTED(name != NULL)) {
		ZVAL_COPY(ret, name);
		return true;
	}
	return viaEngine(site, node, "toString", "tostring", 8, 0, NULL, ret);
}

bool nameToLowerString(pt_method_site &site, zval *node, zval *ret)
{
	zval *name = shapeSlot(node, NodeShape::NAME, IS_STRING);
	if (EXPECTED(name != NULL)) {
		/* strtolower() is the ASCII fold since PHP 8.2; an already
		 * lowercase string comes back with its refcount bumped */
		ZVAL_STR(ret, zend_string_tolower(Z_STR_P(name)));
		return true;
	}
	return viaEngine(site, node, "toLowerString", "tolowerstring", 13, 0, NULL, ret);
}

bool callIsFirstClassCallable(pt_method_site &site, zval *node, zval *ret)
{
	zval *args = shapeSlot(node, NodeShape::CALL, IS_ARRAY);
	if (EXPECTED(args != NULL)) {
		bool result = false;
		if (zend_hash_num_elements(Z_ARRVAL_P(args)) == 1) {
			zend_class_entry *placeholder = pt_class(PT_CLASS_VARIADIC_PLACEHOLDER);
			for (auto entry : zv::ArrRef(args)) {
				result = placeholder != NULL && entry.value().deref().instanceOf(placeholder);
				break;
			}
		}
		ZVAL_BOOL(ret, result);
		return true;
	}
	return viaEngine(site, node, "isFirstClassCallable", "isfirstclasscallable", 20, 0, NULL, ret);
}

bool callGetArgs(pt_method_site &site, zval *node, zval *ret)
{
	zval *args = shapeSlot(node, NodeShape::CALL, IS_ARRAY);
	if (EXPECTED(args != NULL)) {
		/* only plain Args: anything else is a partial application, whose
		 * assertion the method itself raises */
		zend_class_entry *arg = pt_class(PT_CLASS_ARG);
		bool plain = arg != NULL;
		for (auto entry : zv::ArrRef(args)) {
			zv::Ref value = entry.value().deref();
			if (!value.isObject() || Z_OBJCE_P(value.raw()) != arg) {
				plain = false;
				break;
			}
		}
		if (EXPECTED(plain)) {
			ZVAL_COPY(ret, args);
			return true;
		}
	}
	return viaEngine(site, node, "getArgs", "getargs", 7, 0, NULL, ret);
}

/* }}} */

zend_string *internLiteral(const char *s, size_t len)
{
	return zend_string_init_interned(s, len, 1);
}

} // namespace pgrt
