/*
 * PHPStanTurbo\Dependencies — native implementation of
 * PHPStan\Dependency\Dependencies.
 *
 * What a piece of analysed code depends on, carried on the results the
 * handlers return: a final value class over the twin's seven slots, in its
 * order. Every handler merges the dependencies of the results of its parts
 * (pt_dependencies_merge(), no call into PHP and no allocation unless two
 * or more of them are non-null), the handlers resolving something add their
 * own with pt_dependencies_create(). The tree is walked once per file, by
 * the PHP DependencyResolver.
 */

#include "support.h"
#include "generated/Dependencies.h"

namespace slots = ptdecl::Dependencies::slot;
namespace sigs = ptdecl::Dependencies::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_dependencies = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Dependency\Dependencies; UNDEF = pending exception. */
class Dependencies
{
public:
	explicit Dependencies(zend_object *self) : self(self) {}

	/* the private constructor: the slots in the twin's order, borrowed */
	void construct(zval *file, zval *types, zval *classNames, zval *reflections, zval *filePaths, zval *usedTraits, zval *merged) const
	{
		pt_write_slot(self, slots::file, file);
		pt_write_slot(self, slots::types, types);
		pt_write_slot(self, slots::classNames, classNames);
		pt_write_slot(self, slots::reflections, reflections);
		pt_write_slot(self, slots::filePaths, filePaths);
		pt_write_slot(self, slots::usedTraits, usedTraits);
		pt_write_slot(self, slots::merged, merged);
	}

	/* Mirrors create(); the arrays NULL for [] */
	static zv::Val create(zval *file, zval *types, zval *classNames, zval *reflections, zval *filePaths, zval *usedTraits)
	{
		zv::Val nonNullTypes = withoutNulls(types);
		if (
			zend_hash_num_elements(Z_ARRVAL_P(nonNullTypes.raw())) == 0
			&& isEmpty(classNames)
			&& isEmpty(reflections)
			&& isEmpty(filePaths)
			&& isEmpty(usedTraits)
		) {
			return zv::Val::null();
		}

		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		return newSelf(file, nonNullTypes.raw(), orEmpty(classNames, &empty), orEmpty(reflections, &empty), orEmpty(filePaths, &empty), orEmpty(usedTraits, &empty), &empty);
	}

	/* Mirrors merge(?self ...$dependencies): the arguments borrowed, NULL
	 * or IS_NULL for null */
	static zv::Val merge(uint32_t argc, zval *const *argv)
	{
		uint32_t count = 0;
		zval *single = NULL;
		for (uint32_t i = 0; i < argc; i++) {
			if (argv[i] == NULL || Z_TYPE_P(argv[i]) == IS_NULL) continue;
			count++;
			single = argv[i];
		}
		if (count == 0) return zv::Val::null();
		if (count == 1) return zv::Val::copyOf(zv::Ref(single));

		zv::Arr merged = zv::Arr::create(count);
		for (uint32_t i = 0; i < argc; i++) {
			if (argv[i] == NULL || Z_TYPE_P(argv[i]) == IS_NULL) continue;
			merged.push(zv::Ref(argv[i]));
		}
		zval file, empty;
		ZVAL_EMPTY_STRING(&file);
		ZVAL_EMPTY_ARRAY(&empty);
		return newSelf(&file, &empty, &empty, &empty, &empty, &empty, merged.raw());
	}

	/* Mirrors walk(): the same order as the twin's array_pop() stack; false =
	 * pending exception */
	bool walk(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		HashTable seen;
		zend_hash_init(&seen, 8, NULL, NULL, 0);
		/* the stack holds borrowed objects: the tree is immutable and this
		 * object keeps it alive */
		uint32_t capacity = 16, size = 0;
		zend_object **stack = (zend_object **) safe_emalloc(capacity, sizeof(zend_object *), 0);
		stack[size++] = self;
		bool ok = true;
		while (size > 0) {
			zend_object *dependencies = stack[--size];
			if (zend_hash_index_add_empty_element(&seen, dependencies->handle) == NULL) continue;

			zval *merged = OBJ_PROP_NUM(dependencies, slots::merged);
			if (Z_TYPE_P(merged) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(merged)) > 0) {
				for (auto entry : zv::ArrRef(merged)) {
					if (size == capacity) {
						capacity *= 2;
						stack = (zend_object **) safe_erealloc(stack, capacity, sizeof(zend_object *), 0);
					}
					stack[size++] = Z_OBJ_P(entry.value().deref().raw());
				}
				continue;
			}

			zval argv[6], retval;
			ZVAL_COPY_VALUE(&argv[0], OBJ_PROP_NUM(dependencies, slots::file));
			ZVAL_COPY_VALUE(&argv[1], OBJ_PROP_NUM(dependencies, slots::types));
			ZVAL_COPY_VALUE(&argv[2], OBJ_PROP_NUM(dependencies, slots::classNames));
			ZVAL_COPY_VALUE(&argv[3], OBJ_PROP_NUM(dependencies, slots::reflections));
			ZVAL_COPY_VALUE(&argv[4], OBJ_PROP_NUM(dependencies, slots::filePaths));
			ZVAL_COPY_VALUE(&argv[5], OBJ_PROP_NUM(dependencies, slots::usedTraits));
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 6, argv, &retval))) {
				ok = false;
				break;
			}
			zval_ptr_dtor(&retval);
		}
		efree(stack);
		zend_hash_destroy(&seen);
		return ok;
	}

private:
	zend_object *self;

	static bool isEmpty(zval *array)
	{
		return array == NULL || zend_hash_num_elements(Z_ARRVAL_P(array)) == 0;
	}

	static zval *orEmpty(zval *array, zval *empty)
	{
		return array != NULL ? array : empty;
	}

	/* the list without its nulls - the array itself when it is a list
	 * without any */
	static zv::Val withoutNulls(zval *types)
	{
		if (types == NULL) return zv::Val(zv::Arr::empty());
		HashTable *table = Z_ARRVAL_P(types);
		bool hasNull = false;
		for (auto entry : zv::ArrRef(types)) {
			if (Z_TYPE_P(entry.value().deref().raw()) == IS_NULL) {
				hasNull = true;
				break;
			}
		}
		if (!hasNull && HT_IS_PACKED(table) && HT_IS_WITHOUT_HOLES(table)) return zv::Val::copyOf(zv::Ref(types));

		zv::Arr nonNull = zv::Arr::create(zend_hash_num_elements(table));
		for (auto entry : zv::ArrRef(types)) {
			zval *type = entry.value().deref().raw();
			if (Z_TYPE_P(type) == IS_NULL) continue;
			nonNull.push(zv::Ref(type));
		}
		return zv::Val(std::move(nonNull));
	}

	static zv::Val newSelf(zval *file, zval *types, zval *classNames, zval *reflections, zval *filePaths, zval *usedTraits, zval *merged)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_dependencies) != SUCCESS)) return zv::Val();
		Dependencies(Z_OBJ(object)).construct(file, types, classNames, reflections, filePaths, usedTraits, merged);
		return zv::Val::adopt(object);
	}
};

} // namespace phpstanturbo

using phpstanturbo::Dependencies;

/* {{{ direct entries (support.h) */

zv::Val pt_dependencies_create(zval *file, zval *types, zval *classNames, zval *reflections, zval *filePaths, zval *usedTraits)
{
	return Dependencies::create(file, types, classNames, reflections, filePaths, usedTraits);
}

zv::Val pt_dependencies_create_in(zval *scope, zval *types, zval *classNames, zval *reflections, zval *filePaths, zval *usedTraits)
{
	zv::Val file = pt_mutating_scope_get_file(Z_OBJ_P(scope));
	if (UNEXPECTED(file.isUndef())) return zv::Val();
	return Dependencies::create(file.raw(), types, classNames, reflections, filePaths, usedTraits);
}

zv::Val pt_dependencies_merge(uint32_t argc, zval *const *argv)
{
	return Dependencies::merge(argc, argv);
}

zv::Val pt_dependencies_merge(std::initializer_list<zval *> dependencies)
{
	return Dependencies::merge((uint32_t) dependencies.size(), dependencies.begin());
}

zv::Val pt_dependencies_merge_list(HashTable *list)
{
	uint32_t count = zend_hash_num_elements(list);
	if (count == 0) return zv::Val::null();
	zval **argv = (zval **) safe_emalloc(count, sizeof(zval *), 0);
	uint32_t i = 0;
	for (auto entry : zv::TableRef(list)) {
		argv[i++] = entry.value().deref().raw();
	}
	zv::Val result = Dependencies::merge(i, argv);
	efree(argv);
	return result;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_DEPS_RETURN(expr) \
	do { \
		zv::Val pt_deps_result = (expr); \
		if (UNEXPECTED(pt_deps_result.isUndef())) { \
			RETURN_THROWS(); \
		} \
		pt_deps_result.intoReturnValue(return_value); \
	} while (0)

PT_MINIT_REGISTRATION(pt_register_dependencies)
{
	reg::Class cls("PHPStan\\Dependency\\Dependencies");
	ptdecl::Dependencies::declareClass(cls);
	ptdecl::Dependencies::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *file;
		zval *types, *classNames, *reflections, *filePaths, *usedTraits, *merged;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_STR(file)
			Z_PARAM_ARRAY(types)
			Z_PARAM_ARRAY(classNames)
			Z_PARAM_ARRAY(reflections)
			Z_PARAM_ARRAY(filePaths)
			Z_PARAM_ARRAY(usedTraits)
			Z_PARAM_ARRAY(merged)
		ZEND_PARSE_PARAMETERS_END();
		zval fileValue;
		ZVAL_STR(&fileValue, file);
		Dependencies(Z_OBJ_P(ZEND_THIS)).construct(&fileValue, types, classNames, reflections, filePaths, usedTraits, merged);
	});

	cls.method(sigs::create, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *file;
		zval *types = NULL, *classNames = NULL, *reflections = NULL, *filePaths = NULL, *usedTraits = NULL;
		ZEND_PARSE_PARAMETERS_START(1, 6)
			Z_PARAM_STR(file)
			Z_PARAM_OPTIONAL
			Z_PARAM_ARRAY(types)
			Z_PARAM_ARRAY(classNames)
			Z_PARAM_ARRAY(reflections)
			Z_PARAM_ARRAY(filePaths)
			Z_PARAM_ARRAY(usedTraits)
		ZEND_PARSE_PARAMETERS_END();
		zval fileValue;
		ZVAL_STR(&fileValue, file);
		PT_DEPS_RETURN(Dependencies::create(&fileValue, types, classNames, reflections, filePaths, usedTraits));
	});

	cls.method(sigs::merge, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *dependencies;
		uint32_t count;
		ZEND_PARSE_PARAMETERS_START(0, -1)
			Z_PARAM_VARIADIC('*', dependencies, count)
		ZEND_PARSE_PARAMETERS_END();
		zval **argv = (zval **) safe_emalloc(count > 0 ? count : 1, sizeof(zval *), 0);
		for (uint32_t i = 0; i < count; i++) {
			zval *dependency = &dependencies[i];
			ZVAL_DEREF(dependency);
			if (UNEXPECTED(Z_TYPE_P(dependency) != IS_NULL && !(Z_TYPE_P(dependency) == IS_OBJECT && instanceof_function(Z_OBJCE_P(dependency), pt_ce_dependencies)))) {
				efree(argv);
				zend_argument_type_error(i + 1, "must be of type ?PHPStan\\Dependency\\Dependencies, %s given", zend_zval_value_name(dependency));
				RETURN_THROWS();
			}
			argv[i] = dependency;
		}
		zv::Val result = Dependencies::merge(count, argv);
		efree(argv);
		PT_DEPS_RETURN(std::move(result));
	});

	cls.method(sigs::walk, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!Dependencies(Z_OBJ_P(ZEND_THIS)).walk(&fci, &fcc))) RETURN_THROWS();
	});

	cls.shadow(&pt_ce_dependencies);
}

/* }}} */
