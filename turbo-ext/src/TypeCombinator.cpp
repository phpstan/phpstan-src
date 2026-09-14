/*
 * PHPStanTurbo\TypeCombinator — native implementation of
 * PHPStan\Type\TypeCombinator.
 *
 * Declared as PHPStan\Type\TypeCombinator itself at activation: final, a
 * class of static methods with the twin's one `private static ?bool
 * $cacheEnabled` slot. The public entry points union(), intersect() and
 * remove() consult that slot the way the twin does (lazily asking
 * TurboExtensionEnabler::isTypeCombinatorCacheEnabled()) and route through
 * the memo in TypeCombinatorCache.cpp when it says so; the memo computes a
 * miss with the doUnion()/doIntersect()/doRemove() bodies below through a
 * direct C++ call. The other native Type classes reach the class through
 * the exported pt_type_combinator_*() entry points (support.h) — no engine
 * frame, no class-map lookup.
 *
 * The logic lives in the TypeCombinator handle class below, structured to
 * mirror src/Type/TypeCombinator.php method for method (the private helpers
 * included, as plain C++ members: nothing can override a private static of
 * a final class). Lists of types are owned zval vectors; the twin's keyed
 * arrays — the description-keyed scalar/enum/accessory maps, the index-keyed
 * arrays-to-process of reduceArrays() — stay PHP arrays so key coercion,
 * insertion order and replace-in-place semantics are the engine's own. The
 * twin's usort() calls run the engine's sort (zend_sort with usort()'s
 * stable fallback) over the same comparison results. Everything a Type
 * answers is asked through its class entry; the shadowed classes are
 * instantiated through their exported constructors, the others
 * (ConstantArrayTypeBuilder, TypeTraverser, TemplateTypeFactory, the
 * template types) through the class map.
 */

#include "TypeTraits.h"
#include "generated/TypeCombinator.h"

namespace sigs = ptdecl::TypeCombinator::sig;

#include <vector>

zend_class_entry *pt_ce_type_combinator = nullptr;

/* an UNDEF zv::Val / a negative int = pending exception */
#define PT_FAIL_IF_UNDEF(v) \
	do { \
		if (UNEXPECTED((v).isUndef())) { \
			return zv::Val(); \
		} \
	} while (0)
#define PT_FAIL_IF_NEG(x) \
	do { \
		if (UNEXPECTED((x) < 0)) { \
			return zv::Val(); \
		} \
	} while (0)

namespace phpstanturbo {

/* a list of owned types; its data() is a contiguous zval vector (zv::Val
 * wraps exactly one zval), so it spreads into an argument vector as is */
typedef std::vector<zv::Val> TypeList;
static_assert(sizeof(zv::Val) == sizeof(zval), "zv::Val must wrap exactly one zval");

static zval *argvOf(TypeList &list)
{
	return list.empty() ? NULL : reinterpret_cast<zval *>(list.data());
}

/* {{{ helpers */

static zv::Val copy(zval *value)
{
	return zv::Val::copyOf(zv::Ref(value));
}

static bool isNull(const zv::Val &value)
{
	return Z_TYPE_P(const_cast<zv::Val &>(value).raw()) == IS_NULL;
}

static bool isInstance(zval *value, zend_class_entry *ce)
{
	return Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce);
}

/* $value instanceof <class-map class>; -1 = pending exception */
static int isInstanceMap(zval *value, int classIdx)
{
	bool out;
	if (UNEXPECTED(!pt_type_instanceof(value, classIdx, out))) return -1;
	return out ? 1 : 0;
}

static bool sameObject(zval *a, zval *b)
{
	return Z_TYPE_P(a) == IS_OBJECT && Z_TYPE_P(b) == IS_OBJECT && Z_OBJ_P(a) == Z_OBJ_P(b);
}

/* $object->method(...$args); UNDEF = pending exception */
static zv::Val call(zval *object, const char *lcname, size_t len, uint32_t argc = 0, zval *argv = NULL)
{
	return pt_type_call(Z_OBJ_P(object), lcname, len, argc, argv);
}

/* $object->method(...)'s TrinaryLogic value; -1 = pending exception */
[[nodiscard]] static zend_long callTrinary(zval *object, const char *lcname, size_t len, uint32_t argc = 0, zval *argv = NULL)
{
	return pt_type_call_trinary(Z_OBJ_P(object), lcname, len, argc, argv);
}

/* the PT_TRI_* value of $object->method(...)'s result object; -1 = pending
 * exception */
[[nodiscard]] static zend_long callResultTrinary(zval *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zv::Val result = call(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return -1;
	return pt_type_result_trinary(result.raw());
}

/* $object->isSuperTypeOf($other)->yes(); -1 = pending exception */
static int isSuperTypeYes(zval *object, zval *other)
{
	zend_long value = callResultTrinary(object, PT_LC("issupertypeof"), 1, other);
	if (UNEXPECTED(value < 0)) return -1;
	return value == PT_TRI_YES ? 1 : 0;
}

/* $object->method(...) returning bool; -1 = pending exception */
static int callBool(zval *object, const char *lcname, size_t len, uint32_t argc = 0, zval *argv = NULL)
{
	zv::Val result = call(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return -1;
	return zend_is_true(result.raw()) ? 1 : 0;
}

/* $a->equals($b); -1 = pending exception */
static int typeEquals(zval *a, zval *b)
{
	return callBool(a, PT_LC("equals"), 1, b);
}

/* count($array) of an array-valued zval */
static uint32_t countOf(zval *array)
{
	return Z_TYPE_P(array) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(array)) : 0;
}

/* $array[$index] (borrowed); NULL when absent */
static zval *indexOf(zval *array, zend_ulong index)
{
	return Z_TYPE_P(array) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(array), index) : NULL;
}

/* $compound->getTypes(); UNDEF = pending exception */
static zv::Val getTypes(zval *compound)
{
	return call(compound, PT_LC("gettypes"));
}

/* VerbosityLevel::<factory>(); UNDEF = pending exception */
static zv::Val verbosityLevel(const char *lcname, size_t len)
{
	return pt_type_call_static_ce(pt_ce_verbosity_level, lcname, len, 0, NULL);
}

/* $type->describe($level), the level created on first use (a lazily filled
 * VerbosityLevel::<factory>() holder); an owned string, UNDEF = pending
 * exception */
struct Level
{
	const char *lcname;
	size_t len;
	zv::Val value;

	Level(const char *lcname, size_t len) : lcname(lcname), len(len) {}

	zval *get()
	{
		if (value.isUndef()) {
			value = verbosityLevel(lcname, len);
		}
		return value.isUndef() ? NULL : value.raw();
	}
};

static zv::Val describe(zval *type, Level &level)
{
	zval *levelValue = level.get();
	if (UNEXPECTED(levelValue == NULL)) return zv::Val();
	return call(type, PT_LC("describe"), 1, levelValue);
}

/* a description as the symtable key it makes; NULL = pending exception */
[[nodiscard]] static zend_string *describedKey(zv::Val &description)
{
	if (UNEXPECTED(description.isUndef())) return NULL;
	if (UNEXPECTED(Z_TYPE_P(description.raw()) != IS_STRING)) {
		zend_type_error("phpstan_turbo: describe() must return a string, %s returned", zend_zval_value_name(description.raw()));
		return NULL;
	}
	return Z_STR_P(description.raw());
}

/* the copies of a PHP array's entries as a list */
static void listFrom(zval *array, TypeList &out)
{
	out.reserve(out.size() + countOf(array));
	for (zv::ArrayEntry entry : zv::ArrRef(array)) {
		out.push_back(copy(entry.value().raw()));
	}
}

/* array_values($list) as a PHP array (copies) */
static zv::Arr listOf(const TypeList &list)
{
	zv::Arr result = zv::Arr::create((uint32_t) list.size());
	for (const zv::Val &type : list) {
		result.push(zv::Ref(const_cast<zv::Val &>(type).raw()));
	}
	return result;
}

/* $outer[$key][$index] = $value over an owned map of maps */
static void nestedIndexSet(zv::Arr &outer, zend_string *key, zend_ulong index, zv::Val value)
{
	zval *inner = zend_symtable_find(outer.table(), key);
	if (inner == NULL) {
		zval fresh;
		array_init(&fresh);
		inner = zend_symtable_update(outer.table(), key, &fresh);
	}
	zval v = value.take();
	zend_hash_index_update(Z_ARRVAL_P(inner), index, &v);
}

/* $outer[$key][] = $value over an owned map of lists */
static void nestedPush(zv::Arr &outer, zend_string *key, zv::Val value)
{
	zval *inner = zend_symtable_find(outer.table(), key);
	if (inner == NULL) {
		zval fresh;
		array_init(&fresh);
		inner = zend_symtable_update(outer.table(), key, &fresh);
	}
	zval v = value.take();
	zend_hash_next_index_insert(Z_ARRVAL_P(inner), &v);
}

/* $map[$keyValue] for an int|string key value (the twin's `$keyType->getValue()`
 * keys), with PHP's key coercion; NULL for an unusable key with a TypeError
 * pending */
static zval *symtableFind(HashTable *map, zval *keyValue)
{
	if (Z_TYPE_P(keyValue) == IS_LONG) return zend_hash_index_find(map, (zend_ulong) Z_LVAL_P(keyValue));
	if (EXPECTED(Z_TYPE_P(keyValue) == IS_STRING)) return zend_symtable_find(map, Z_STR_P(keyValue));
	zend_type_error("phpstan_turbo: an int|string array key expected, %s given", zend_zval_value_name(keyValue));
	return NULL;
}

/* $map[$keyValue] = $value the same way; false = pending exception */
[[nodiscard]] static bool symtableSet(HashTable *map, zval *keyValue, zval *value)
{
	if (Z_TYPE_P(keyValue) == IS_LONG) {
		zend_hash_index_update(map, (zend_ulong) Z_LVAL_P(keyValue), value);
		return true;
	}
	if (EXPECTED(Z_TYPE_P(keyValue) == IS_STRING)) {
		zend_symtable_update(map, Z_STR_P(keyValue), value);
		return true;
	}
	zend_type_error("phpstan_turbo: an int|string array key expected, %s given", zend_zval_value_name(keyValue));
	return false;
}

/* Class::NAME of a class-map class (borrowed); NULL = pending exception */
[[nodiscard]] static zval *classConstant(int classIdx, const char *name, size_t len)
{
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return NULL;
	zend_class_constant *constant = (zend_class_constant *) zend_hash_str_find_ptr(&ce->constants_table, name, len);
	if (UNEXPECTED(constant == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: %s::%s not found", ZSTR_VAL(ce->name), name);
		return NULL;
	}
	if (UNEXPECTED(Z_TYPE(constant->value) == IS_CONSTANT_AST && zval_update_constant_ex(&constant->value, ce) != SUCCESS)) return NULL;
	return &constant->value;
}

/* ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT; -1 = pending exception */
[[nodiscard]] static zend_long arrayCountLimit()
{
	zval *limit = classConstant(PT_CLASS_CONSTANT_ARRAY_TYPE_BUILDER, PT_LC("ARRAY_COUNT_LIMIT"));
	if (UNEXPECTED(limit == NULL)) return -1;
	return zval_get_long(limit);
}

/* the shadowed classes' constructors as Vals; UNDEF = pending exception */
static zv::Val adopted(bool created, zval &raw)
{
	if (UNEXPECTED(!created)) return zv::Val();
	return zv::Val::adopt(raw);
}

static zv::Val neverType(bool isExplicit = false)
{
	zval raw;
	return adopted(pt_never_type_new(&raw, isExplicit), raw);
}

/* new NeverType(reason: $reason) ($reason a string or null zval) */
static zv::Val neverTypeWithReason(zval *reason)
{
	zv::Args args{false, reason};
	return pt_type_new_ce(pt_ce_never_type, 2, args);
}

static zv::Val nullType()
{
	zval raw;
	return adopted(pt_null_type_new(&raw), raw);
}

static zv::Val booleanType()
{
	zval raw;
	return adopted(pt_boolean_type_new(&raw), raw);
}

static zv::Val stringType()
{
	zval raw;
	return adopted(pt_string_type_new(&raw), raw);
}

static zv::Val integerType()
{
	zval raw;
	return adopted(pt_integer_type_new(&raw), raw);
}

static zv::Val unionType(zv::Arr types, bool normalized)
{
	zval raw;
	return adopted(pt_union_type_new(&raw, types.raw(), normalized), raw);
}

static zv::Val benevolentUnionType(zv::Arr types, bool normalized)
{
	zval raw;
	return adopted(pt_benevolent_union_type_new(&raw, types.raw(), normalized), raw);
}

static zv::Val intersectionType(zv::Arr types)
{
	zval raw;
	return adopted(pt_intersection_type_new(&raw, types.raw()), raw);
}

static zv::Val hasOffsetValueType(zval *offsetType, zval *valueType)
{
	zval raw;
	return adopted(pt_has_offset_value_type_new(&raw, offsetType, valueType), raw);
}

static zv::Val iterableType(zval *keyType, zval *itemType)
{
	zval raw;
	return adopted(pt_iterable_type_new(&raw, keyType, itemType), raw);
}

static zv::Val arrayType(zval *keyType, zval *itemType)
{
	zval raw;
	return adopted(pt_array_type_new(&raw, keyType, itemType), raw);
}

static zv::Val mixedTypeMinus(zval *subtractedType)
{
	zval raw;
	return adopted(pt_mixed_type_new(&raw, false, subtractedType), raw);
}

static zv::Val accessoryArrayListType()
{
	zval raw;
	return adopted(pt_accessory_array_list_type_new(&raw), raw);
}

static zv::Val nonEmptyArrayType()
{
	zval raw;
	return adopted(pt_non_empty_array_type_new(&raw), raw);
}

static zv::Val oversizedArrayType()
{
	zval raw;
	return adopted(pt_oversized_array_type_new(&raw), raw);
}

static zv::Val accessoryLowercaseStringType()
{
	zval raw;
	return adopted(pt_accessory_lowercase_string_type_new(&raw), raw);
}

static zv::Val accessoryUppercaseStringType()
{
	zval raw;
	return adopted(pt_accessory_uppercase_string_type_new(&raw), raw);
}

static zv::Val accessoryNonEmptyStringType()
{
	zval raw;
	return adopted(pt_accessory_non_empty_string_type_new(&raw), raw);
}

static zv::Val accessoryDecimalIntegerStringType()
{
	zval raw;
	return adopted(pt_accessory_decimal_integer_string_type_new(&raw), raw);
}

/* the operations over argument vectors and lists; UNDEF = pending exception */
static zv::Val unionOf(TypeList &types)
{
	return pt_type_combinator_union((uint32_t) types.size(), argvOf(types));
}

static zv::Val union2(zval *a, zval *b)
{
	zv::Args args{a, b};
	return pt_type_combinator_union(2, args);
}

static zv::Val intersectOf(TypeList &types)
{
	return pt_type_combinator_intersect((uint32_t) types.size(), argvOf(types));
}

static zv::Val intersect2(zval *a, zval *b)
{
	zv::Args args{a, b};
	return pt_type_combinator_intersect(2, args);
}

/* TypeCombinator::union(...$array) / intersect(...$array) over a PHP array
 * (any keys, as the spread accepts) */
static zv::Val unionSpread(zval *array)
{
	return pt_type_combinator_call_spread(PT_LC("union"), Z_ARRVAL_P(array));
}

/* self::intersect($first, ...$rest) */
static zv::Val intersectWith(zval *first, TypeList &rest)
{
	std::vector<zval> args(rest.size() + 1);
	ZVAL_COPY_VALUE(&args[0], first);
	for (size_t i = 0; i < rest.size(); i++) {
		ZVAL_COPY_VALUE(&args[i + 1], rest[i].raw());
	}
	return pt_type_combinator_intersect((uint32_t) args.size(), args.data());
}

/* $type instanceof NeverType && !$type->isExplicit(); -1 = pending
 * exception */
static int isImplicitNever(zval *type)
{
	if (!isInstance(type, pt_ce_never_type)) return 0;
	bool isExplicit;
	if (UNEXPECTED(!pt_never_type_is_explicit(Z_OBJ_P(type), isExplicit))) return -1;
	return isExplicit ? 0 : 1;
}

/* $type instanceof MixedType && !$type->isExplicitMixed() && !$type
 * instanceof TemplateMixedType && $type->getSubtractedType() === null; -1 =
 * pending exception */
static int isPlainMixed(zval *type)
{
	if (!isInstance(type, pt_ce_mixed_type)) return 0;
	int isExplicitMixed = callBool(type, PT_LC("isexplicitmixed"));
	if (UNEXPECTED(isExplicitMixed < 0)) return -1;
	if (isExplicitMixed) return 0;
	if (isInstance(type, pt_ce_template_mixed_type)) return 0;
	zv::Val subtracted = call(type, PT_LC("getsubtractedtype"));
	if (UNEXPECTED(subtracted.isUndef())) return -1;
	return isNull(subtracted) ? 1 : 0;
}

/* }}} */

/* {{{ usort() over a list: the engine's sort (zend_sort) with usort()'s
 * stable fallback on the original order, over the comparator's own -1/0/1
 * answers; the comparator sets sortFailed on a pending exception */

typedef int (*ListComparator)(zval *a, zval *b);

static ListComparator sortComparator = NULL;
static bool sortFailed = false;

static int compareBuckets(Bucket *a, Bucket *b)
{
	int result = sortFailed ? 0 : sortComparator(&a->val, &b->val);
	if (result == 0) {
		/* usort()'s stable fallback: the original order */
		return Z_EXTRA(a->val) > Z_EXTRA(b->val) ? 1 : (Z_EXTRA(a->val) < Z_EXTRA(b->val) ? -1 : 0);
	}
	return result > 0 ? 1 : -1;
}

/* usort($list, $comparator); false = pending exception */
[[nodiscard]] static bool usortList(TypeList &list, ListComparator comparator)
{
	if (list.size() < 2) return true;
	zv::Arr array = listOf(list);
	ListComparator previousComparator = sortComparator;
	bool previousFailed = sortFailed;
	sortComparator = comparator;
	sortFailed = false;
	zend_hash_sort_ex(array.table(), zend_sort, compareBuckets, 1);
	bool failed = sortFailed || EG(exception) != NULL;
	sortComparator = previousComparator;
	sortFailed = previousFailed;
	if (UNEXPECTED(failed)) return false;
	list.clear();
	listFrom(array.raw(), list);
	return true;
}

/* }}} */

/* Mirrors PHPStan\Type\TypeCombinator. */
class TypeCombinator
{
public:
	/* {{{ self::$cacheEnabled ??= TurboExtensionEnabler::isTypeCombinatorCacheEnabled();
	 * false = pending exception */

	static bool cacheEnabled(bool &out)
	{
		zend_class_entry *ce = pt_ce_type_combinator;
		if (UNEXPECTED(ce == NULL)) {
			out = false;
			return true;
		}
		if (UNEXPECTED(ce != cacheEnabledCe)) {
			zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, PT_LC("cacheEnabled"));
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

	static zend_class_entry *cacheEnabledCe;
	static uint32_t cacheEnabledOffset;

	/* }}} */

	/* clearCache(); false = pending exception */
	[[nodiscard]] static bool clearCache()
	{
		bool enabled;
		if (UNEXPECTED(!cacheEnabled(enabled))) return false;
		if (!enabled) return true;
		pt_type_combinator_cache_clear();
		return true;
	}

	static zv::Val addNull(zval *type)
	{
		/* asking the type itself is both cheaper than NullType::isSuperTypeOf()
		 * (UnionType memoizes isNull(), isSubTypeOf() recomputes) and right for
		 * `never`, of which null is a supertype without never containing it */
		zend_long isNull = callTrinary(type, PT_LC("isnull"));
		PT_FAIL_IF_NEG(isNull);
		if (isNull == PT_TRI_NO) {
			zv::Val nullValue = nullType();
			PT_FAIL_IF_UNDEF(nullValue);
			return union2(type, nullValue.raw());
		}
		return copy(type);
	}

	static zv::Val remove(zval *fromType, zval *typeToRemove)
	{
		bool enabled;
		if (UNEXPECTED(!cacheEnabled(enabled))) return zv::Val();
		if (enabled) return pt_type_combinator_cache_remove(fromType, typeToRemove);
		return doRemove(fromType, typeToRemove);
	}

	/* private static canRemoveUnionAtOnce(Type $fromType): bool; -1 =
	 * pending exception */
	static int canRemoveUnionAtOnce(zval *fromType)
	{
		zv::Val classReflections = call(fromType, PT_LC("getobjectclassreflections"));
		if (UNEXPECTED(classReflections.isUndef())) return -1;
		if (countOf(classReflections.raw()) == 1) {
			zval *first = indexOf(classReflections.raw(), 0);
			if (UNEXPECTED(first == NULL || Z_TYPE_P(first) != IS_OBJECT)) {
				zend_throw_error(NULL, "phpstan_turbo: getObjectClassReflections() must return a list of class reflections");
				return -1;
			}
			zv::Val allowedSubTypes = call(first, PT_LC("getallowedsubtypes"));
			if (UNEXPECTED(allowedSubTypes.isUndef())) return -1;
			if (!isNull(allowedSubTypes)) return 1;
		}
		return isInstance(fromType, pt_ce_union_type) ? 1 : 0;
	}

	static zv::Val doRemove(zval *fromType, zval *typeToRemove)
	{
		if (isInstance(typeToRemove, pt_ce_union_type)) {
			int atOnce = canRemoveUnionAtOnce(fromType);
			PT_FAIL_IF_NEG(atOnce);
			if (atOnce) {
				zv::Val removed = call(fromType, PT_LC("tryremove"), 1, typeToRemove);
				PT_FAIL_IF_UNDEF(removed);
				if (!isNull(removed)) return removed;
			}

			zv::Val types = getTypes(typeToRemove);
			PT_FAIL_IF_UNDEF(types);
			zv::Val current = copy(fromType);
			for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
				zv::Val next = remove(current.raw(), entry.value().raw());
				PT_FAIL_IF_UNDEF(next);
				current = std::move(next);
				if (isInstance(current.raw(), pt_ce_never_type)) {
					/* there is nothing left to remove from */
					break;
				}
			}
			return current;
		}

		zend_long isSuperType = callResultTrinary(typeToRemove, PT_LC("issupertypeof"), 1, fromType);
		PT_FAIL_IF_NEG(isSuperType);
		if (isSuperType == PT_TRI_YES) return neverType();
		if (isSuperType == PT_TRI_NO) return copy(fromType);

		if (isInstance(typeToRemove, pt_ce_mixed_type)) {
			zv::Val subtractedType = call(typeToRemove, PT_LC("getsubtractedtype"));
			PT_FAIL_IF_UNDEF(subtractedType);
			if (!isNull(subtractedType)) return intersect2(fromType, subtractedType.raw());
		}

		zv::Val removed = call(fromType, PT_LC("tryremove"), 1, typeToRemove);
		PT_FAIL_IF_UNDEF(removed);
		if (!isNull(removed)) return removed;

		zv::Val fromFiniteTypes = call(fromType, PT_LC("getfinitetypes"));
		PT_FAIL_IF_UNDEF(fromFiniteTypes);
		uint32_t fromCount = countOf(fromFiniteTypes.raw());
		if (fromCount > 0) {
			zv::Val finiteTypesToRemove = call(typeToRemove, PT_LC("getfinitetypes"));
			PT_FAIL_IF_UNDEF(finiteTypesToRemove);
			if (countOf(finiteTypesToRemove.raw()) > 0) {
				zv::Arr result = zv::Arr::create(fromCount);
				for (zv::ArrayEntry finite : zv::ArrRef(fromFiniteTypes.raw())) {
					bool skip = false;
					for (zv::ArrayEntry toRemove : zv::ArrRef(finiteTypesToRemove.raw())) {
						int equal = typeEquals(finite.value().raw(), toRemove.value().raw());
						PT_FAIL_IF_NEG(equal);
						if (equal) {
							skip = true;
							break;
						}
					}
					if (!skip) {
						result.push(finite.value());
					}
				}

				uint32_t resultCount = zend_hash_num_elements(result.table());
				if (resultCount == fromCount) return copy(fromType);
				if (resultCount == 0) return neverType();
				if (resultCount == 1) return copy(zend_hash_index_find(result.table(), 0));
				return unionType(std::move(result), false);
			}
		}

		return copy(fromType);
	}

	static zv::Val removeNull(zval *type)
	{
		int contains = containsNull(type);
		PT_FAIL_IF_NEG(contains);
		if (contains) {
			zv::Val nullValue = nullType();
			PT_FAIL_IF_UNDEF(nullValue);
			return remove(type, nullValue.raw());
		}
		return copy(type);
	}

	/* -1 = pending exception */
	static int containsNull(zval *type)
	{
		if (isInstance(type, pt_ce_union_type)) {
			zv::Val types = getTypes(type);
			if (UNEXPECTED(types.isUndef())) return -1;
			for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
				zend_long isNullType = callTrinary(entry.value().raw(), PT_LC("isnull"));
				if (UNEXPECTED(isNullType < 0)) return -1;
				if (isNullType == PT_TRI_YES) return 1;
			}
			return 0;
		}
		return isInstance(type, pt_ce_null_type) ? 1 : 0;
	}

	static zv::Val union_(uint32_t argc, zval *argv)
	{
		bool enabled;
		if (UNEXPECTED(!cacheEnabled(enabled))) return zv::Val();
		if (enabled) return pt_type_combinator_cache_union(argc, argv);
		return doUnion(argc, argv);
	}

	/* {{{ doUnion() and its helpers */

	/* the scalar groups of doUnion(): `$scalarTypes[get_class($type)][$type->describe(cache)] = $type`
	 * — one entry per exact class in first-insertion order, the items a
	 * description-keyed map until the group is normalized into a list */
	struct ScalarGroup
	{
		zend_class_entry *ce;
		zv::Arr items;
		TypeList list;
		bool removed;
	};

	static zv::Val doUnion(uint32_t argc, zval *argv)
	{
		size_t typesCount = argc;
		if (typesCount == 0) return neverType();

		/* Fast path for single non-union type */
		if (typesCount == 1) {
			zval *singleType = &argv[0];
			if (!isInstance(singleType, pt_ce_union_type)) {
				zend_long isArray = callTrinary(singleType, PT_LC("isarray"));
				PT_FAIL_IF_NEG(isArray);
				if (isArray != PT_TRI_YES) return copy(singleType);
			}
		}

		/* Fast path for common 2-type cases */
		if (typesCount == 2) {
			zval *a = &argv[0];
			zval *b = &argv[1];

			/* union(never, X) = X and union(X, never) = X */
			int aNever = isImplicitNever(a);
			PT_FAIL_IF_NEG(aNever);
			if (aNever) return copy(b);
			int bNever = isImplicitNever(b);
			PT_FAIL_IF_NEG(bNever);
			if (bNever) return copy(a);

			/* union(mixed, X) = mixed (non-explicit, non-template, no subtracted) */
			int aMixed = isPlainMixed(a);
			PT_FAIL_IF_NEG(aMixed);
			if (aMixed) return copy(a);
			int bMixed = isPlainMixed(b);
			PT_FAIL_IF_NEG(bMixed);
			if (bMixed) return copy(b);

			/* union(X, X) = X — for equal array operands, not just identical
			 * ones (see the twin for why non-array operands keep taking the
			 * general path) */
			if (sameObject(a, b)) return copy(a);
			int equal = typeEquals(a, b);
			PT_FAIL_IF_NEG(equal);
			if (equal) {
				zend_long isArray = callTrinary(a, PT_LC("isarray"));
				PT_FAIL_IF_NEG(isArray);
				if (isArray == PT_TRI_YES) return copy(a);
			}
		}

		std::vector<zv::Val> alreadyNormalized;
		zv::Arr benevolentTypes = zv::Arr::create(0);
		size_t neverCount = 0;
		Level valueLevel(PT_LC("value"));
		TypeList types;
		types.reserve(argc);
		for (uint32_t i = 0; i < argc; i++) {
			types.push_back(copy(&argv[i]));
		}

		/* A member passed more than once contributes nothing; dropping the
		 * repeats up front keeps the pairwise comparison below from paying
		 * for them (array_values() of a shape unions the same value type per
		 * slot). */
		if (typesCount > 2) {
			zv::Arr seenTypes = zv::Arr::create((uint32_t) typesCount);
			TypeList uniqueTypes;
			uniqueTypes.reserve(typesCount);
			for (size_t i = 0; i < types.size(); i++) {
				zval *type = types[i].raw();
				zend_ulong typeId = Z_TYPE_P(type) == IS_OBJECT ? (zend_ulong) Z_OBJ_HANDLE_P(type) : (zend_ulong) i;
				if (zend_hash_index_add_empty_element(seenTypes.table(), typeId) == NULL) continue;
				uniqueTypes.push_back(std::move(types[i]));
			}
			if (uniqueTypes.size() == 1) return std::move(uniqueTypes[0]);
			if (uniqueTypes.size() == 2) return union2(uniqueTypes[0].raw(), uniqueTypes[1].raw());
			types = std::move(uniqueTypes);
		}

		/* transform A | (B | C) to A | B | C - in one pass, a union's members
		 * are never unions, implicit never or implicit mixed themselves */
		TypeList flattenedTypes;
		flattenedTypes.reserve(types.size());
		for (size_t i = 0; i < types.size(); i++) {
			zval *type = types[i].raw();
			int plainMixed = isPlainMixed(type);
			PT_FAIL_IF_NEG(plainMixed);
			if (plainMixed) return copy(type);
			int implicitNever = isImplicitNever(type);
			PT_FAIL_IF_NEG(implicitNever);
			if (implicitNever) {
				neverCount++;
				flattenedTypes.push_back(std::move(types[i]));
				continue;
			}
			if (isInstance(type, pt_ce_benevolent_union_type)) {
				int isTemplate = isInstanceMap(type, PT_CLASS_TEMPLATE_TYPE);
				PT_FAIL_IF_NEG(isTemplate);
				if (isTemplate) {
					flattenedTypes.push_back(std::move(types[i]));
					continue;
				}
				zv::Val typesInner = getTypes(type);
				PT_FAIL_IF_UNDEF(typesInner);
				for (zv::ArrayEntry entry : zv::ArrRef(typesInner.raw())) {
					zv::Val description = describe(entry.value().raw(), valueLevel);
					zend_string *key = describedKey(description);
					if (UNEXPECTED(key == NULL)) return zv::Val();
					benevolentTypes.set(key, copy(entry.value().raw()));
					flattenedTypes.push_back(copy(entry.value().raw()));
				}
				continue;
			}
			if (!isInstance(type, pt_ce_union_type)) {
				flattenedTypes.push_back(std::move(types[i]));
				continue;
			}
			int isTemplate = isInstanceMap(type, PT_CLASS_TEMPLATE_TYPE);
			PT_FAIL_IF_NEG(isTemplate);
			if (isTemplate) {
				flattenedTypes.push_back(std::move(types[i]));
				continue;
			}

			zv::Val typesInner = getTypes(type);
			PT_FAIL_IF_UNDEF(typesInner);
			for (zv::ArrayEntry entry : zv::ArrRef(typesInner.raw())) {
				flattenedTypes.push_back(copy(entry.value().raw()));
			}
			alreadyNormalized.push_back(std::move(typesInner));
		}
		types = std::move(flattenedTypes);
		typesCount = types.size();

		/* Bulk-remove implicit NeverTypes (skipped during the loop above) */
		if (neverCount > 0) {
			if (neverCount == typesCount) return neverType();

			TypeList filtered;
			filtered.reserve(typesCount - neverCount);
			for (size_t i = 0; i < typesCount; i++) {
				int implicitNever = isImplicitNever(types[i].raw());
				PT_FAIL_IF_NEG(implicitNever);
				if (implicitNever) continue;
				filtered.push_back(std::move(types[i]));
			}
			types = std::move(filtered);
			typesCount = types.size();

			if (typesCount == 0) return neverType();
			if (typesCount == 1) {
				zend_long isArray = callTrinary(types[0].raw(), PT_LC("isarray"));
				PT_FAIL_IF_NEG(isArray);
				if (isArray != PT_TRI_YES) return std::move(types[0]);
			}
			if (typesCount == 2) return union2(types[0].raw(), types[1].raw());
		}

		if (typesCount == 0) return neverType();

		if (typesCount == 1) {
			zend_long isArray = callTrinary(types[0].raw(), PT_LC("isarray"));
			PT_FAIL_IF_NEG(isArray);
			if (isArray != PT_TRI_YES) return std::move(types[0]);
		}

		TypeList arrayTypes;
		std::vector<ScalarGroup> scalarTypes;
		bool hasGenericBoolean = false;
		bool hasGenericFloat = false;
		bool hasGenericInteger = false;
		bool hasGenericString = false;
		zv::Arr enumCaseTypesMap = zv::Arr::create(0);
		TypeList integerRangeTypes;
		TypeList remaining;
		Level cacheLevel(PT_LC("cache"));
		for (size_t i = 0; i < typesCount; i++) {
			zval *type = types[i].raw();
			zend_long isConstantScalarValue = callTrinary(type, PT_LC("isconstantscalarvalue"));
			PT_FAIL_IF_NEG(isConstantScalarValue);
			if (isConstantScalarValue == PT_TRI_YES) {
				zv::Val description = describe(type, cacheLevel);
				zend_string *key = describedKey(description);
				if (UNEXPECTED(key == NULL)) return zv::Val();
				scalarGroupSet(scalarTypes, Z_OBJCE_P(type), key, copy(type));
				continue;
			}

			zend_long isBoolean = callTrinary(type, PT_LC("isboolean"));
			PT_FAIL_IF_NEG(isBoolean);
			if (isBoolean == PT_TRI_YES) {
				hasGenericBoolean = true;
			} else {
				zend_long isFloat = callTrinary(type, PT_LC("isfloat"));
				PT_FAIL_IF_NEG(isFloat);
				if (isFloat == PT_TRI_YES) {
					hasGenericFloat = true;
				} else {
					zend_long isInteger = callTrinary(type, PT_LC("isinteger"));
					PT_FAIL_IF_NEG(isInteger);
					if (isInteger == PT_TRI_YES && !isInstance(type, pt_ce_integer_range_type)) {
						hasGenericInteger = true;
					} else {
						int plainString = isPlainString(type);
						PT_FAIL_IF_NEG(plainString);
						if (plainString) {
							hasGenericString = true;
						} else {
							zv::Val enumCase = call(type, PT_LC("getenumcaseobject"));
							PT_FAIL_IF_UNDEF(enumCase);
							if (!isNull(enumCase)) {
								zv::Val description = describe(type, cacheLevel);
								zend_string *key = describedKey(description);
								if (UNEXPECTED(key == NULL)) return zv::Val();
								enumCaseTypesMap.set(key, copy(type));
								continue;
							}
						}
					}
				}
			}

			if (isInstance(type, pt_ce_integer_range_type)) {
				integerRangeTypes.push_back(copy(type));
				continue;
			}

			zend_long isArray = callTrinary(type, PT_LC("isarray"));
			PT_FAIL_IF_NEG(isArray);
			if (isArray != PT_TRI_YES) {
				remaining.push_back(copy(type));
				continue;
			}

			arrayTypes.push_back(copy(type));
		}

		TypeList enumCaseTypes;
		listFrom(enumCaseTypesMap.raw(), enumCaseTypes);
		if (UNEXPECTED(!usortList(integerRangeTypes, compareIntegerRanges))) return zv::Val();
		types = std::move(remaining);
		for (zv::Val &rangeType : integerRangeTypes) {
			types.push_back(std::move(rangeType));
		}
		typesCount = types.size();

		for (ScalarGroup &group : scalarTypes) {
			zend_class_entry *classType = group.ce;
			if ((classType == pt_ce_constant_boolean_type && hasGenericBoolean)
				|| (classType == pt_ce_constant_float_type && hasGenericFloat)
				|| (classType == pt_ce_constant_integer_type && hasGenericInteger)
				|| (classType == pt_ce_constant_string_type && hasGenericString)) {
				group.removed = true;
				continue;
			}
			if (classType == pt_ce_constant_boolean_type && zend_hash_num_elements(group.items.table()) == 2) {
				zv::Val boolean = booleanType();
				PT_FAIL_IF_UNDEF(boolean);
				types.push_back(std::move(boolean));
				typesCount++;
				group.removed = true;
				continue;
			}

			TypeList scalarTypeItems;
			listFrom(group.items.raw(), scalarTypeItems);
			size_t scalarTypeItemsCount = scalarTypeItems.size();
			for (size_t i = 0; i < typesCount; i++) {
				for (size_t j = 0; j < scalarTypeItemsCount; j++) {
					zv::Val merged;
					int compareResult = compareTypesInUnion(types[i].raw(), scalarTypeItems[j].raw(), merged);
					PT_FAIL_IF_NEG(compareResult);
					if (compareResult == 0) continue;

					if (compareResult == 1) {
						types[i] = std::move(merged);
						scalarTypeItems.erase(scalarTypeItems.begin() + (ptrdiff_t) j);
						scalarTypeItemsCount--;
						j = (size_t) -1;
						continue;
					}
					scalarTypeItems[j] = std::move(merged);
					types.erase(types.begin() + (ptrdiff_t) i);
					i--;
					typesCount--;
					break;
				}
			}

			group.list = std::move(scalarTypeItems);
		}

		if (types.size() > 16) {
			zv::Arr newTypes = zv::Arr::create((uint32_t) types.size());
			for (zv::Val &type : types) {
				zv::Val description = describe(type.raw(), cacheLevel);
				zend_string *key = describedKey(description);
				if (UNEXPECTED(key == NULL)) return zv::Val();
				newTypes.set(key, std::move(type));
			}
			types.clear();
			listFrom(newTypes.raw(), types);
		}

		zv::Val processedArrayTypes = processArrayTypes(arrayTypes);
		PT_FAIL_IF_UNDEF(processedArrayTypes);
		listFrom(processedArrayTypes.raw(), types);
		typesCount = types.size();

		/* transform A | A to A
		 * transform A | never to A */
		for (size_t i = 0; i < typesCount; i++) {
			for (size_t j = i + 1; j < typesCount; j++) {
				if (isAlreadyNormalized(alreadyNormalized, types[i].raw(), types[j].raw())) continue;
				zv::Val merged;
				int compareResult = compareTypesInUnion(types[i].raw(), types[j].raw(), merged);
				PT_FAIL_IF_NEG(compareResult);
				if (compareResult == 0) continue;

				if (compareResult == 1) {
					types[i] = std::move(merged);
					types.erase(types.begin() + (ptrdiff_t) j);
					j--;
					typesCount--;
					continue;
				}
				types[j] = std::move(merged);
				types.erase(types.begin() + (ptrdiff_t) i);
				i--;
				typesCount--;
				break;
			}
		}

		size_t enumCasesCount = enumCaseTypes.size();
		for (size_t i = 0; i < typesCount; i++) {
			for (size_t j = 0; j < enumCasesCount; j++) {
				zv::Val merged;
				int compareResult = compareTypesInUnion(types[i].raw(), enumCaseTypes[j].raw(), merged);
				PT_FAIL_IF_NEG(compareResult);
				if (compareResult == 0) continue;

				if (compareResult == 1) {
					types[i] = std::move(merged);
					enumCaseTypes.erase(enumCaseTypes.begin() + (ptrdiff_t) j);
					j--;
					enumCasesCount--;
					continue;
				}
				enumCaseTypes[j] = std::move(merged);
				types.erase(types.begin() + (ptrdiff_t) i);
				i--;
				typesCount--;
				break;
			}
		}

		for (zv::Val &enumCaseType : enumCaseTypes) {
			types.push_back(std::move(enumCaseType));
			typesCount++;
		}

		for (ScalarGroup &group : scalarTypes) {
			if (group.removed) continue;
			for (zv::Val &scalarType : group.list) {
				types.push_back(std::move(scalarType));
				typesCount++;
			}
		}

		if (typesCount == 0) return neverType();
		if (typesCount == 1) return std::move(types[0]);

		if (zend_hash_num_elements(benevolentTypes.table()) > 0) {
			bool allBenevolent = true;
			for (zv::Val &type : types) {
				zv::Val description = describe(type.raw(), valueLevel);
				zend_string *key = describedKey(description);
				if (UNEXPECTED(key == NULL)) return zv::Val();
				if (zend_symtable_find(benevolentTypes.table(), key) == NULL) {
					allBenevolent = false;
					break;
				}
			}

			if (allBenevolent) return benevolentUnionType(listOf(types), true);
		}

		return unionType(listOf(types), true);
	}

	/* array_splice($types, $i, 1, $replacement): the element at $i replaced
	 * by the entries of a PHP array (copies) */
	static void spliceReplace(TypeList &types, size_t i, zval *replacement)
	{
		TypeList inner;
		listFrom(replacement, inner);
		types.erase(types.begin() + (ptrdiff_t) i);
		types.insert(types.begin() + (ptrdiff_t) i, std::make_move_iterator(inner.begin()), std::make_move_iterator(inner.end()));
	}

	/* $scalarTypes[$ce][$key] = $type */
	static void scalarGroupSet(std::vector<ScalarGroup> &groups, zend_class_entry *ce, zend_string *key, zv::Val type)
	{
		for (ScalarGroup &group : groups) {
			if (group.ce == ce) {
				group.items.set(key, std::move(type));
				return;
			}
		}
		groups.push_back({ ce, zv::Arr::create(1), TypeList(), false });
		groups.back().items.set(key, std::move(type));
	}

	/* $type->isString()->yes() && $type->isClassString()->no() &&
	 * TypeUtils::getAccessoryTypes($type) === []; -1 = pending exception */
	static int isPlainString(zval *type)
	{
		zend_long isString = callTrinary(type, PT_LC("isstring"));
		if (UNEXPECTED(isString < 0)) return -1;
		if (isString != PT_TRI_YES) return 0;
		zend_long isClassString = callTrinary(type, PT_LC("isclassstring"));
		if (UNEXPECTED(isClassString < 0)) return -1;
		if (isClassString != PT_TRI_NO) return 0;
		zv::Val accessoryTypes = pt_type_call_static_ce(pt_ce_type_utils, PT_LC("getaccessorytypes"), 1, type);
		if (UNEXPECTED(accessoryTypes.isUndef())) return -1;
		return countOf(accessoryTypes.raw()) == 0 ? 1 : 0;
	}

	/* the integer range comparator of doUnion(): ($a->getMin() ?? PHP_INT_MIN)
	 * <=> ($b->getMin() ?? PHP_INT_MIN) ?: ($a->getMax() ?? PHP_INT_MAX) <=>
	 * ($b->getMax() ?? PHP_INT_MAX) */
	static int compareIntegerRanges(zval *a, zval *b)
	{
		NullableLong aMin, aMax, bMin, bMax;
		if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(a), aMin, aMax) || !pt_integer_range_bounds(Z_OBJ_P(b), bMin, bMax))) {
			sortFailed = true;
			return 0;
		}
		zend_long aMinValue = aMin.isNull ? ZEND_LONG_MIN : aMin.value;
		zend_long bMinValue = bMin.isNull ? ZEND_LONG_MIN : bMin.value;
		if (aMinValue != bMinValue) return aMinValue < bMinValue ? -1 : 1;
		zend_long aMaxValue = aMax.isNull ? ZEND_LONG_MAX : aMax.value;
		zend_long bMaxValue = bMax.isNull ? ZEND_LONG_MAX : bMax.value;
		if (aMaxValue != bMaxValue) return aMaxValue < bMaxValue ? -1 : 1;
		return 0;
	}

	/* private static isAlreadyNormalized(array $alreadyNormalized, Type $a, Type $b): bool */
	static bool isAlreadyNormalized(std::vector<zv::Val> &alreadyNormalized, zval *a, zval *b)
	{
		for (zv::Val &normalizedTypes : alreadyNormalized) {
			for (zv::ArrayEntry entry : zv::ArrRef(normalizedTypes.raw())) {
				if (!sameObject(entry.value().raw(), a)) continue;

				for (zv::ArrayEntry another : zv::ArrRef(normalizedTypes.raw())) {
					if (another.stringKeyOrNull() == entry.stringKeyOrNull() && another.indexKey() == entry.indexKey()) continue;
					if (sameObject(another.value().raw(), b)) return true;
				}
			}
		}

		return false;
	}

	/* private static compareTypesInUnion(Type $a, Type $b): array{Type, null}|array{null, Type}|null
	 * — 1 for [$merged, null], 2 for [null, $merged], 0 for null; -1 =
	 * pending exception */
	static int compareTypesInUnion(zval *a, zval *b, zv::Val &merged)
	{
		if (isInstance(a, pt_ce_integer_range_type)) {
			zv::Val type = call(a, PT_LC("tryunion"), 1, b);
			if (UNEXPECTED(type.isUndef())) return -1;
			if (!isNull(type)) {
				merged = std::move(type);
				return 1;
			}
		}
		if (isInstance(b, pt_ce_integer_range_type)) {
			zv::Val type = call(b, PT_LC("tryunion"), 1, a);
			if (UNEXPECTED(type.isUndef())) return -1;
			if (!isNull(type)) {
				merged = std::move(type);
				return 2;
			}
		}
		if (isInstance(a, pt_ce_integer_range_type) && isInstance(b, pt_ce_integer_range_type)) return 0;
		if (isInstance(a, pt_ce_has_offset_value_type) && isInstance(b, pt_ce_has_offset_value_type)) {
			zv::Val aOffset = pt_has_offset_value_type_get_offset_type(Z_OBJ_P(a));
			zv::Val bOffset = pt_has_offset_value_type_get_offset_type(Z_OBJ_P(b));
			if (UNEXPECTED(aOffset.isUndef() || bOffset.isUndef())) return -1;
			int equal = typeEquals(aOffset.raw(), bOffset.raw());
			if (UNEXPECTED(equal < 0)) return -1;
			if (equal) {
				zv::Val aValue = pt_has_offset_value_type_get_value_type(Z_OBJ_P(a));
				zv::Val bValue = pt_has_offset_value_type_get_value_type(Z_OBJ_P(b));
				if (UNEXPECTED(aValue.isUndef() || bValue.isUndef())) return -1;
				zv::Val valueType = union2(aValue.raw(), bValue.raw());
				if (UNEXPECTED(valueType.isUndef())) return -1;
				merged = hasOffsetValueType(aOffset.raw(), valueType.raw());
				return merged.isUndef() ? -1 : 1;
			}
		}
		if (isInstance(a, pt_ce_intersection_type) && isInstance(b, pt_ce_intersection_type)) {
			zv::Val mergedIntersection = mergeIntersectionsForUnion(a, b);
			if (UNEXPECTED(mergedIntersection.isUndef())) return -1;
			if (!isNull(mergedIntersection)) {
				merged = std::move(mergedIntersection);
				return 1;
			}
		}
		zend_long aConstantArray = callTrinary(a, PT_LC("isconstantarray"));
		if (UNEXPECTED(aConstantArray < 0)) return -1;
		if (aConstantArray == PT_TRI_YES) {
			zend_long bConstantArray = callTrinary(b, PT_LC("isconstantarray"));
			if (UNEXPECTED(bConstantArray < 0)) return -1;
			if (bConstantArray == PT_TRI_YES) return 0;
		}

		/* simplify string[] | int[] to (string|int)[] */
		if (isInstance(a, pt_ce_iterable_type) && isInstance(b, pt_ce_iterable_type)) {
			zv::Val aKey = call(a, PT_LC("getiterablekeytype"));
			zv::Val bKey = call(b, PT_LC("getiterablekeytype"));
			if (UNEXPECTED(aKey.isUndef() || bKey.isUndef())) return -1;
			zv::Val keyType = union2(aKey.raw(), bKey.raw());
			if (UNEXPECTED(keyType.isUndef())) return -1;
			zv::Val aValue = call(a, PT_LC("getiterablevaluetype"));
			zv::Val bValue = call(b, PT_LC("getiterablevaluetype"));
			if (UNEXPECTED(aValue.isUndef() || bValue.isUndef())) return -1;
			zv::Val valueType = union2(aValue.raw(), bValue.raw());
			if (UNEXPECTED(valueType.isUndef())) return -1;
			merged = iterableType(keyType.raw(), valueType.raw());
			return merged.isUndef() ? -1 : 1;
		}

		int aSubtractable = isInstanceMap(a, PT_CLASS_SUBTRACTABLE_TYPE);
		if (UNEXPECTED(aSubtractable < 0)) return -1;
		if (aSubtractable) {
			int isSuperType = subtractableCovers(a, b);
			if (UNEXPECTED(isSuperType < 0)) return -1;
			if (isSuperType) {
				merged = intersectWithSubtractedType(a, b);
				return merged.isUndef() ? -1 : 1;
			}
		}

		int bSubtractable = isInstanceMap(b, PT_CLASS_SUBTRACTABLE_TYPE);
		if (UNEXPECTED(bSubtractable < 0)) return -1;
		if (bSubtractable) {
			int isSuperType = subtractableCovers(b, a);
			if (UNEXPECTED(isSuperType < 0)) return -1;
			if (isSuperType) {
				merged = intersectWithSubtractedType(b, a);
				return merged.isUndef() ? -1 : 2;
			}
		}

		int bCoversA = isSuperTypeYes(b, a);
		if (UNEXPECTED(bCoversA < 0)) return -1;
		if (bCoversA) {
			merged = copy(b);
			return 2;
		}

		int aCoversB = isSuperTypeYes(a, b);
		if (UNEXPECTED(aCoversB < 0)) return -1;
		if (aCoversB) {
			merged = copy(a);
			return 1;
		}

		if (isInstance(a, pt_ce_constant_string_type)) {
			int result = compareConstantStringInUnion(a, b, merged);
			if (result != 0) return result < 0 ? -1 : 2;
		}

		if (isInstance(b, pt_ce_constant_string_type)) {
			int result = compareConstantStringInUnion(b, a, merged);
			if (result != 0) return result < 0 ? -1 : 1;
		}

		/* numeric-string | non-decimal-int-string → string (preserving common accessories)
		 * Works because decimal-int-string ⊂ numeric-string, so together they cover all strings */
		zend_long aString = callTrinary(a, PT_LC("isstring"));
		if (UNEXPECTED(aString < 0)) return -1;
		if (aString == PT_TRI_YES) {
			zend_long bString = callTrinary(b, PT_LC("isstring"));
			if (UNEXPECTED(bString < 0)) return -1;
			if (bString == PT_TRI_YES) {
				zv::Val decimalIntString = decimalIntStringType();
				if (UNEXPECTED(decimalIntString.isUndef())) return -1;
				int result = compareStringsInUnion(a, b, decimalIntString.raw(), merged);
				if (result != 0) return result < 0 ? -1 : 2;
				result = compareStringsInUnion(b, a, decimalIntString.raw(), merged);
				if (result != 0) return result < 0 ? -1 : 1;
			}
		}

		return 0;
	}

	/* the SubtractableType arm of compareTypesInUnion(): whether
	 * $a->getTypeWithoutSubtractedType() is a super type of $b
	 * (isSuperTypeOfMixed() between two MixedTypes); -1 = pending
	 * exception */
	static int subtractableCovers(zval *a, zval *b)
	{
		zv::Val withoutSubtracted = call(a, PT_LC("gettypewithoutsubtractedtype"));
		if (UNEXPECTED(withoutSubtracted.isUndef())) return -1;
		zend_long isSuperType;
		if (isInstance(withoutSubtracted.raw(), pt_ce_mixed_type) && isInstance(b, pt_ce_mixed_type)) {
			isSuperType = callResultTrinary(withoutSubtracted.raw(), PT_LC("issupertypeofmixed"), 1, b);
		} else {
			isSuperType = callResultTrinary(withoutSubtracted.raw(), PT_LC("issupertypeof"), 1, b);
		}
		if (UNEXPECTED(isSuperType < 0)) return -1;
		return isSuperType == PT_TRI_YES ? 1 : 0;
	}

	/* the ConstantStringType arm of compareTypesInUnion() for the constant
	 * $constant against $other: '' absorbed by a non-empty/non-falsy string
	 * (keeping its case accessories), '0' downgrading a non-falsy string to
	 * non-empty; 1 with $merged the replacement for $other, 0 for no
	 * answer; -1 = pending exception */
	static int compareConstantStringInUnion(zval *constant, zval *other, zv::Val &merged)
	{
		zv::Val value = pt_constant_string_get_value(Z_OBJ_P(constant));
		if (UNEXPECTED(value.isUndef())) return -1;
		zend_string *valueString = Z_STR_P(value.raw());
		if (ZSTR_LEN(valueString) == 0) {
			Level valueLevel(PT_LC("value"));
			zv::Val description = describe(other, valueLevel);
			zend_string *described = describedKey(description);
			if (UNEXPECTED(described == NULL)) return -1;
			if (zend_string_equals_literal(described, "non-empty-string") || zend_string_equals_literal(described, "non-falsy-string")) {
				zv::Val accessories = getAccessoryCaseStringTypes(other);
				if (UNEXPECTED(accessories.isUndef())) return -1;
				zv::Val string = stringType();
				if (UNEXPECTED(string.isUndef())) return -1;
				TypeList rest;
				listFrom(accessories.raw(), rest);
				merged = intersectWith(string.raw(), rest);
				return merged.isUndef() ? -1 : 1;
			}
		}

		if (zend_string_equals_literal(valueString, "0")) {
			zv::Val nonEmpty = downgradeNonFalsyStringToNonEmpty(other);
			if (UNEXPECTED(nonEmpty.isUndef())) return -1;
			if (!isNull(nonEmpty)) {
				merged = std::move(nonEmpty);
				return 1;
			}
		}

		return 0;
	}

	/* new IntersectionType([new StringType(), new AccessoryDecimalIntegerStringType()]) */
	static zv::Val decimalIntStringType()
	{
		zv::Val string = stringType();
		PT_FAIL_IF_UNDEF(string);
		zv::Val accessory = accessoryDecimalIntegerStringType();
		PT_FAIL_IF_UNDEF(accessory);
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(string));
		types.push(std::move(accessory));
		return intersectionType(std::move(types));
	}

	/* the string arm of compareTypesInUnion() with $other the candidate
	 * absorbing $type: $other without its decimal-int-string accessory
	 * covers $type, and $type covers decimal-int-string; 1 with $merged
	 * the base of $other, 0 for no answer; -1 = pending exception */
	static int compareStringsInUnion(zval *type, zval *other, zval *decimalIntString, zv::Val &merged)
	{
		zend_long isDecimal = callTrinary(other, PT_LC("isdecimalintegerstring"));
		if (UNEXPECTED(isDecimal < 0)) return -1;
		if (isDecimal != PT_TRI_NO) return 0;
		zv::Val base = removeDecimalIntStringAccessory(other);
		if (UNEXPECTED(base.isUndef())) return -1;
		int baseCovers = isSuperTypeYes(base.raw(), type);
		if (UNEXPECTED(baseCovers < 0)) return -1;
		if (!baseCovers) return 0;
		int coversDecimal = isSuperTypeYes(type, decimalIntString);
		if (UNEXPECTED(coversDecimal < 0)) return -1;
		if (!coversDecimal) return 0;
		merged = std::move(base);
		return 1;
	}

	/* private static getAccessoryCaseStringTypes(Type $type): list<Type> */
	static zv::Val getAccessoryCaseStringTypes(zval *type)
	{
		zv::Arr accessory = zv::Arr::create(0);
		zend_long isLowercase = callTrinary(type, PT_LC("islowercasestring"));
		PT_FAIL_IF_NEG(isLowercase);
		if (isLowercase == PT_TRI_YES) {
			zv::Val lowercase = accessoryLowercaseStringType();
			PT_FAIL_IF_UNDEF(lowercase);
			accessory.push(std::move(lowercase));
		}
		zend_long isUppercase = callTrinary(type, PT_LC("isuppercasestring"));
		PT_FAIL_IF_NEG(isUppercase);
		if (isUppercase == PT_TRI_YES) {
			zv::Val uppercase = accessoryUppercaseStringType();
			PT_FAIL_IF_UNDEF(uppercase);
			accessory.push(std::move(uppercase));
		}

		return zv::Val(std::move(accessory));
	}

	/* private static downgradeNonFalsyStringToNonEmpty(Type $type): ?Type
	 * (IS_NULL for null) */
	static zv::Val downgradeNonFalsyStringToNonEmpty(zval *type)
	{
		if (!isInstance(type, pt_ce_intersection_type)) return zv::Val::null();
		zend_long isNonFalsy = callTrinary(type, PT_LC("isnonfalsystring"));
		PT_FAIL_IF_NEG(isNonFalsy);
		if (isNonFalsy == PT_TRI_NO) return zv::Val::null();

		zv::Val types = getTypes(type);
		PT_FAIL_IF_UNDEF(types);
		TypeList newTypes;
		bool found = false;
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			if (isInstance(entry.value().raw(), pt_ce_accessory_non_falsy_string_type)) {
				found = true;
				continue;
			}

			newTypes.push_back(copy(entry.value().raw()));
		}

		if (!found) return zv::Val::null();

		zv::Val withoutNonFalsy = intersectOf(newTypes);
		PT_FAIL_IF_UNDEF(withoutNonFalsy);
		zend_long isNonEmpty = callTrinary(withoutNonFalsy.raw(), PT_LC("isnonemptystring"));
		PT_FAIL_IF_NEG(isNonEmpty);
		if (isNonEmpty == PT_TRI_YES) return withoutNonFalsy;

		zv::Val nonEmpty = accessoryNonEmptyStringType();
		PT_FAIL_IF_UNDEF(nonEmpty);
		return intersect2(withoutNonFalsy.raw(), nonEmpty.raw());
	}

	/* private static removeDecimalIntStringAccessory(Type $type): Type */
	static zv::Val removeDecimalIntStringAccessory(zval *type)
	{
		if (!isInstance(type, pt_ce_intersection_type)) return copy(type);

		zv::Val types = getTypes(type);
		PT_FAIL_IF_UNDEF(types);
		TypeList kept;
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			if (isInstance(entry.value().raw(), pt_ce_accessory_decimal_integer_string_type)) continue;
			kept.push_back(copy(entry.value().raw()));
		}
		return intersectOf(kept);
	}

	/* private static unionWithSubtractedType(Type $type, ?Type $subtractedType): Type
	 * ($subtractedType an IS_NULL zval for null) */
	static zv::Val unionWithSubtractedType(zval *type, zval *subtractedType)
	{
		if (Z_TYPE_P(subtractedType) == IS_NULL) return copy(type);

		int subtractedSubtractable = isInstanceMap(subtractedType, PT_CLASS_SUBTRACTABLE_TYPE);
		PT_FAIL_IF_NEG(subtractedSubtractable);
		if (subtractedSubtractable) {
			zv::Val withoutSubtracted = call(subtractedType, PT_LC("gettypewithoutsubtractedtype"));
			PT_FAIL_IF_UNDEF(withoutSubtracted);
			int covers = isSuperTypeYes(withoutSubtracted.raw(), type);
			PT_FAIL_IF_NEG(covers);
			if (covers) {
				zv::Val subtractedSubtractedType = call(subtractedType, PT_LC("getsubtractedtype"));
				PT_FAIL_IF_UNDEF(subtractedSubtractedType);
				if (isNull(subtractedSubtractedType)) return neverType();

				return intersect2(type, subtractedSubtractedType.raw());
			}
		}

		int typeSubtractable = isInstanceMap(type, PT_CLASS_SUBTRACTABLE_TYPE);
		PT_FAIL_IF_NEG(typeSubtractable);
		if (typeSubtractable) {
			zv::Val typeSubtracted = call(type, PT_LC("getsubtractedtype"));
			PT_FAIL_IF_UNDEF(typeSubtracted);
			zv::Val subtracted;
			if (isNull(typeSubtracted)) {
				subtracted = copy(subtractedType);
			} else {
				subtracted = union2(typeSubtracted.raw(), subtractedType);
				PT_FAIL_IF_UNDEF(subtracted);
			}

			zv::Val withoutSubtracted = call(type, PT_LC("gettypewithoutsubtractedtype"));
			PT_FAIL_IF_UNDEF(withoutSubtracted);
			subtracted = intersect2(withoutSubtracted.raw(), subtracted.raw());
			PT_FAIL_IF_UNDEF(subtracted);
			if (isInstance(subtracted.raw(), pt_ce_never_type)) {
				subtracted = zv::Val::null();
			}

			return call(type, PT_LC("changesubtractedtype"), 1, subtracted.raw());
		}

		int covers = isSuperTypeYes(subtractedType, type);
		PT_FAIL_IF_NEG(covers);
		if (covers) return neverType();

		return remove(type, subtractedType);
	}

	/* private static intersectWithSubtractedType(SubtractableType $a, Type $b): Type */
	static zv::Val intersectWithSubtractedType(zval *a, zval *b)
	{
		zv::Val aSubtracted = call(a, PT_LC("getsubtractedtype"));
		PT_FAIL_IF_UNDEF(aSubtracted);
		if (isNull(aSubtracted) || isInstance(b, pt_ce_never_type)) return copy(a);

		zv::Val subtractedType;
		if (isInstance(b, pt_ce_intersection_type)) {
			zv::Val bTypes = getTypes(b);
			PT_FAIL_IF_UNDEF(bTypes);
			TypeList subtractableTypes;
			for (zv::ArrayEntry entry : zv::ArrRef(bTypes.raw())) {
				int subtractable = isInstanceMap(entry.value().raw(), PT_CLASS_SUBTRACTABLE_TYPE);
				PT_FAIL_IF_NEG(subtractable);
				if (!subtractable) continue;

				subtractableTypes.push_back(copy(entry.value().raw()));
			}

			if (subtractableTypes.empty()) return call(a, PT_LC("gettypewithoutsubtractedtype"));

			TypeList subtractedTypes;
			for (zv::Val &subtractableType : subtractableTypes) {
				zv::Val subtracted = call(subtractableType.raw(), PT_LC("getsubtractedtype"));
				PT_FAIL_IF_UNDEF(subtracted);
				if (isNull(subtracted)) continue;

				subtractedTypes.push_back(std::move(subtracted));
			}

			if (subtractedTypes.empty()) return call(a, PT_LC("gettypewithoutsubtractedtype"));

			subtractedType = unionOf(subtractedTypes);
			PT_FAIL_IF_UNDEF(subtractedType);
		} else {
			zend_long isBAlreadySubtracted = callResultTrinary(aSubtracted.raw(), PT_LC("issupertypeof"), 1, b);
			PT_FAIL_IF_NEG(isBAlreadySubtracted);

			if (isBAlreadySubtracted == PT_TRI_NO) {
				return copy(a);
			} else if (isBAlreadySubtracted == PT_TRI_YES) {
				zv::Val subtracted = remove(aSubtracted.raw(), b);
				PT_FAIL_IF_UNDEF(subtracted);

				if (isInstance(subtracted.raw(), pt_ce_never_type)) {
					subtracted = zv::Val::null();
				} else {
					zend_long stillCovers = callResultTrinary(subtracted.raw(), PT_LC("issupertypeof"), 1, b);
					PT_FAIL_IF_NEG(stillCovers);
					if (stillCovers != PT_TRI_NO) {
						subtracted = zv::Val::null();
					}
				}

				return call(a, PT_LC("changesubtractedtype"), 1, subtracted.raw());
			}
			int bSubtractable = isInstanceMap(b, PT_CLASS_SUBTRACTABLE_TYPE);
			PT_FAIL_IF_NEG(bSubtractable);
			if (bSubtractable) {
				subtractedType = call(b, PT_LC("getsubtractedtype"));
				PT_FAIL_IF_UNDEF(subtractedType);
				if (isNull(subtractedType)) return call(a, PT_LC("gettypewithoutsubtractedtype"));
			} else {
				zv::Val withoutSubtracted = call(a, PT_LC("gettypewithoutsubtractedtype"));
				PT_FAIL_IF_UNDEF(withoutSubtracted);
				zv::Val subtractedTypeTmp = intersect2(withoutSubtracted.raw(), aSubtracted.raw());
				PT_FAIL_IF_UNDEF(subtractedTypeTmp);
				int covers = isSuperTypeYes(b, subtractedTypeTmp.raw());
				PT_FAIL_IF_NEG(covers);
				if (covers) return call(a, PT_LC("gettypewithoutsubtractedtype"));
				subtractedType = mixedTypeMinus(b);
				PT_FAIL_IF_UNDEF(subtractedType);
			}
		}

		subtractedType = intersect2(aSubtracted.raw(), subtractedType.raw());
		PT_FAIL_IF_UNDEF(subtractedType);
		if (isInstance(subtractedType.raw(), pt_ce_never_type)) {
			subtractedType = zv::Val::null();
		}

		return call(a, PT_LC("changesubtractedtype"), 1, subtractedType.raw());
	}

	/* }}} */

	/* {{{ the array arm of doUnion() */

	/* private static processArrayAccessoryTypes(array $arrayTypes): list<Type> */
	static zv::Val processArrayAccessoryTypes(TypeList &arrayTypes)
	{
		bool allIterableAtLeastOnce = true;
		zv::Arr accessoryTypes = zv::Arr::create(0);
		Level cacheLevel(PT_LC("cache"));
		for (size_t i = 0; i < arrayTypes.size(); i++) {
			zval *arrayType = arrayTypes[i].raw();
			zend_long atLeastOnce = callTrinary(arrayType, PT_LC("isiterableatleastonce"));
			PT_FAIL_IF_NEG(atLeastOnce);
			if (atLeastOnce != PT_TRI_YES) {
				allIterableAtLeastOnce = false;
			}

			if (isInstance(arrayType, pt_ce_intersection_type)) {
				zv::Val innerTypes = getTypes(arrayType);
				PT_FAIL_IF_UNDEF(innerTypes);
				for (zv::ArrayEntry entry : zv::ArrRef(innerTypes.raw())) {
					zv::Val innerType = copy(entry.value().raw());
					int isTemplate = isInstanceMap(innerType.raw(), PT_CLASS_TEMPLATE_TYPE);
					PT_FAIL_IF_NEG(isTemplate);
					if (isTemplate) break;
					int isAccessory = isInstanceMap(innerType.raw(), PT_CLASS_ACCESSORY_TYPE);
					PT_FAIL_IF_NEG(isAccessory);
					if (!isAccessory && !isInstance(innerType.raw(), pt_ce_callable_type)) continue;
					if (isInstance(innerType.raw(), pt_ce_has_offset_type)) {
						zv::Val offsetType = pt_has_offset_type_get_offset_type(Z_OBJ_P(innerType.raw()));
						PT_FAIL_IF_UNDEF(offsetType);
						zv::Val valueType = call(arrayType, PT_LC("getiterablevaluetype"));
						PT_FAIL_IF_UNDEF(valueType);
						innerType = hasOffsetValueType(offsetType.raw(), valueType.raw());
						PT_FAIL_IF_UNDEF(innerType);
					}
					if (isInstance(innerType.raw(), pt_ce_has_offset_value_type)) {
						zv::Val offsetType = pt_has_offset_value_type_get_offset_type(Z_OBJ_P(innerType.raw()));
						PT_FAIL_IF_UNDEF(offsetType);
						zv::Val description = describe(offsetType.raw(), cacheLevel);
						zend_string *described = describedKey(description);
						if (UNEXPECTED(described == NULL)) return zv::Val();
						zend_string *key = zend_strpprintf(0, "hasOffsetValue(%s)", ZSTR_VAL(described));
						nestedIndexSet(accessoryTypes, key, (zend_ulong) i, std::move(innerType));
						zend_string_release(key);
						continue;
					}

					zv::Val description = describe(innerType.raw(), cacheLevel);
					zend_string *key = describedKey(description);
					if (UNEXPECTED(key == NULL)) return zv::Val();
					nestedIndexSet(accessoryTypes, key, (zend_ulong) i, std::move(innerType));
				}
			}

			zend_long isConstantArray = callTrinary(arrayType, PT_LC("isconstantarray"));
			PT_FAIL_IF_NEG(isConstantArray);
			if (isConstantArray != PT_TRI_YES) continue;
			zv::Val constantArrays = call(arrayType, PT_LC("getconstantarrays"));
			PT_FAIL_IF_UNDEF(constantArrays);

			for (zv::ArrayEntry entry : zv::ArrRef(constantArrays.raw())) {
				zval *constantArray = entry.value().raw();
				zend_long isList = callTrinary(constantArray, PT_LC("islist"));
				PT_FAIL_IF_NEG(isList);
				if (isList == PT_TRI_YES) {
					zv::Val list = accessoryArrayListType();
					PT_FAIL_IF_UNDEF(list);
					zv::Val description = describe(list.raw(), cacheLevel);
					zend_string *key = describedKey(description);
					if (UNEXPECTED(key == NULL)) return zv::Val();
					nestedIndexSet(accessoryTypes, key, (zend_ulong) i, std::move(list));
				}

				zend_long constantAtLeastOnce = callTrinary(constantArray, PT_LC("isiterableatleastonce"));
				PT_FAIL_IF_NEG(constantAtLeastOnce);
				if (constantAtLeastOnce != PT_TRI_YES) continue;

				zv::Val nonEmpty = nonEmptyArrayType();
				PT_FAIL_IF_UNDEF(nonEmpty);
				zv::Val description = describe(nonEmpty.raw(), cacheLevel);
				zend_string *key = describedKey(description);
				if (UNEXPECTED(key == NULL)) return zv::Val();
				nestedIndexSet(accessoryTypes, key, (zend_ulong) i, std::move(nonEmpty));
			}
		}

		zv::Arr commonAccessoryTypes = zv::Arr::create(0);
		uint32_t arrayTypeCount = (uint32_t) arrayTypes.size();
		for (zv::ArrayEntry entry : zv::ArrRef(accessoryTypes.raw())) {
			zval *accessoryType = entry.value().raw();
			if (countOf(accessoryType) != arrayTypeCount) {
				zval *first = NULL;
				for (zv::ArrayEntry inner : zv::ArrRef(accessoryType)) {
					first = inner.value().raw();
					break;
				}
				if (first != NULL && isInstance(first, pt_ce_oversized_array_type)) {
					commonAccessoryTypes.push(zv::Ref(first));
				}
				continue;
			}

			zval *zeroth = indexOf(accessoryType, 0);
			if (UNEXPECTED(zeroth == NULL)) {
				zend_throw_error(NULL, "Undefined array key 0");
				return zv::Val();
			}
			if (isInstance(zeroth, pt_ce_has_offset_value_type)) {
				zv::Val unioned = unionSpread(accessoryType);
				PT_FAIL_IF_UNDEF(unioned);
				commonAccessoryTypes.push(std::move(unioned));
				continue;
			}

			commonAccessoryTypes.push(zv::Ref(zeroth));
		}

		if (allIterableAtLeastOnce) {
			zv::Val nonEmpty = nonEmptyArrayType();
			PT_FAIL_IF_UNDEF(nonEmpty);
			commonAccessoryTypes.push(std::move(nonEmpty));
		}

		return zv::Val(std::move(commonAccessoryTypes));
	}

	/* private static processArrayTypes(list<Type> $arrayTypes): Type[] */
	static zv::Val processArrayTypes(TypeList &arrayTypes)
	{
		if (arrayTypes.empty()) return zv::Val(zv::Arr::create(0));

		zv::Val accessoryTypesArray = processArrayAccessoryTypes(arrayTypes);
		PT_FAIL_IF_UNDEF(accessoryTypesArray);
		TypeList accessoryTypes;
		listFrom(accessoryTypesArray.raw(), accessoryTypes);

		if (arrayTypes.size() == 1) {
			zv::Val intersected = intersectWith(arrayTypes[0].raw(), accessoryTypes);
			PT_FAIL_IF_UNDEF(intersected);
			zv::Arr result = zv::Arr::create(1);
			result.push(std::move(intersected));
			return zv::Val(std::move(result));
		}

		TypeList keyTypesForGeneralArray;
		TypeList valueTypesForGeneralArray;
		bool generalArrayOccurred = false;
		zv::Arr seenConstantKeyTypes = zv::Arr::create(0);

		for (zv::Val &arrayType : arrayTypes) {
			zv::Val constantArrays = call(arrayType.raw(), PT_LC("getconstantarrays"));
			PT_FAIL_IF_UNDEF(constantArrays);
			bool isConstantArray = countOf(constantArrays.raw()) > 0;

			if (!isConstantArray) {
				zv::Val arrays = call(arrayType.raw(), PT_LC("getarrays"));
				PT_FAIL_IF_UNDEF(arrays);
				for (zv::ArrayEntry entry : zv::ArrRef(arrays.raw())) {
					zv::Val keyType = call(entry.value().raw(), PT_LC("getiterablekeytype"));
					PT_FAIL_IF_UNDEF(keyType);
					keyTypesForGeneralArray.push_back(std::move(keyType));
					zv::Val itemType = call(entry.value().raw(), PT_LC("getitemtype"));
					PT_FAIL_IF_UNDEF(itemType);
					valueTypesForGeneralArray.push_back(std::move(itemType));
					generalArrayOccurred = true;
				}
				continue;
			}

			for (zv::ArrayEntry entry : zv::ArrRef(constantArrays.raw())) {
				zval *constantArray = entry.value().raw();
				zv::Val valueTypes = call(constantArray, PT_LC("getvaluetypes"));
				PT_FAIL_IF_UNDEF(valueTypes);
				zv::Val keyTypes = call(constantArray, PT_LC("getkeytypes"));
				PT_FAIL_IF_UNDEF(keyTypes);
				for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
					zval *valueType = indexOf(valueTypes.raw(), keyEntry.indexKey());
					if (UNEXPECTED(valueType == NULL)) {
						zend_throw_error(NULL, "Undefined array key " ZEND_LONG_FMT, (zend_long) keyEntry.indexKey());
						return zv::Val();
					}
					valueTypesForGeneralArray.push_back(copy(valueType));

					zval *keyType = keyEntry.value().raw();
					zv::Val keyTypeValue = call(keyType, PT_LC("getvalue"));
					PT_FAIL_IF_UNDEF(keyTypeValue);
					zval *seen = symtableFind(seenConstantKeyTypes.table(), keyTypeValue.raw());
					if (seen != NULL) continue;
					if (UNEXPECTED(EG(exception))) return zv::Val();
					zval trueValue;
					ZVAL_TRUE(&trueValue);
					if (UNEXPECTED(!symtableSet(seenConstantKeyTypes.table(), keyTypeValue.raw(), &trueValue))) return zv::Val();
					keyTypesForGeneralArray.push_back(copy(keyType));
				}
			}
		}

		if (generalArrayOccurred) {
			zv::Arr arrayTypesArray = listOf(arrayTypes);
			zv::Val reducedArrayTypes = reduceArrays(arrayTypesArray.raw(), false);
			PT_FAIL_IF_UNDEF(reducedArrayTypes);
			if (countOf(reducedArrayTypes.raw()) == 1) {
				zval *reduced = indexOf(reducedArrayTypes.raw(), 0);
				if (UNEXPECTED(reduced == NULL)) {
					zend_throw_error(NULL, "Undefined array key 0");
					return zv::Val();
				}
				zv::Val intersected = intersectWith(reduced, accessoryTypes);
				PT_FAIL_IF_UNDEF(intersected);
				zv::Arr result = zv::Arr::create(1);
				result.push(std::move(intersected));
				return zv::Val(std::move(result));
			}

			zval *templateArrayType = NULL;
			for (zv::Val &arrayType : arrayTypes) {
				if (!isInstance(arrayType.raw(), pt_ce_template_array_type)) {
					templateArrayType = NULL;
					break;
				}

				if (templateArrayType != NULL) continue;

				templateArrayType = arrayType.raw();
			}

			zv::Val keyType = unionOf(keyTypesForGeneralArray);
			PT_FAIL_IF_UNDEF(keyType);
			zv::Arr valueTypesArray = listOf(valueTypesForGeneralArray);
			zv::Val optimizedValueTypes = optimizeConstantArrays(valueTypesArray.raw());
			PT_FAIL_IF_UNDEF(optimizedValueTypes);
			zv::Val valueType = unionSpread(optimizedValueTypes.raw());
			PT_FAIL_IF_UNDEF(valueType);
			zv::Val newArrayType = arrayType(keyType.raw(), valueType.raw());
			PT_FAIL_IF_UNDEF(newArrayType);

			if (templateArrayType != NULL) {
				zv::Val scope = call(templateArrayType, PT_LC("getscope"));
				PT_FAIL_IF_UNDEF(scope);
				zv::Val strategy = call(templateArrayType, PT_LC("getstrategy"));
				PT_FAIL_IF_UNDEF(strategy);
				zv::Val variance = call(templateArrayType, PT_LC("getvariance"));
				PT_FAIL_IF_UNDEF(variance);
				zv::Val name = call(templateArrayType, PT_LC("getname"));
				PT_FAIL_IF_UNDEF(name);
				zv::Val defaultType = call(templateArrayType, PT_LC("getdefault"));
				PT_FAIL_IF_UNDEF(defaultType);
				if (UNEXPECTED(!zv::Ref(name.raw()).isString())) {
					zend_type_error("phpstan_turbo: %s::getName() must return string", ZSTR_VAL(Z_OBJCE_P(templateArrayType)->name));
					return zv::Val();
				}
				zval created;
				if (UNEXPECTED(!pt_template_array_type_new(&created, scope.raw(), strategy.raw(), variance.raw(), Z_STR_P(name.raw()), newArrayType.raw(), defaultType.raw()))) {
					return zv::Val();
				}
				newArrayType = zv::Val::adopt(created);
			}

			zv::Val intersected = intersectWith(newArrayType.raw(), accessoryTypes);
			PT_FAIL_IF_UNDEF(intersected);
			zv::Arr result = zv::Arr::create(1);
			result.push(std::move(intersected));
			return zv::Val(std::move(result));
		}

		zv::Arr arrayTypesArray = listOf(arrayTypes);
		zv::Val reduced = reduceArrays(arrayTypesArray.raw(), true);
		PT_FAIL_IF_UNDEF(reduced);
		zv::Val reducedArrayTypes = optimizeConstantArrays(reduced.raw());
		PT_FAIL_IF_UNDEF(reducedArrayTypes);
		zv::Arr result = zv::Arr::adoptVal(std::move(reducedArrayTypes));
		result.separate();
		for (zv::ArrayEntry entry : zv::ArrRef(result.raw())) {
			zval *reducedArray = entry.value().raw();
			TypeList applied;
			zend_long atLeastOnce = callTrinary(reducedArray, PT_LC("isiterableatleastonce"));
			PT_FAIL_IF_NEG(atLeastOnce);
			for (zv::Val &accessory : accessoryTypes) {
				/* Empty arrays cannot satisfy non-empty / oversized
				 * constraints — applying those accessories would produce a
				 * contradictory intersection */
				if (atLeastOnce == PT_TRI_NO && (isInstance(accessory.raw(), pt_ce_oversized_array_type) || isInstance(accessory.raw(), pt_ce_non_empty_array_type))) {
					continue;
				}
				applied.push_back(copy(accessory.raw()));
			}
			zv::Val intersected = intersectWith(reducedArray, applied);
			PT_FAIL_IF_UNDEF(intersected);
			entry.value().assign(std::move(intersected));
		}
		return zv::Val(std::move(result));
	}

	/* the body of optimizeConstantArrays()'s TypeTraverser::map() callback:
	 * a non-empty ConstantArrayType generalized to an oversized array over
	 * the union of its (generalized) keys and values, `use (&$isOversized)`
	 * in the holder's first state slot */
	static void generalizeOversizedCallback(zval *isOversized, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		if (UNEXPECTED(argc < 2 || Z_TYPE_P(&argv[0]) != IS_OBJECT)) {
			zend_wrong_parameters_count_error(2, 2);
			return;
		}
		zval *type = &argv[0];
		if (!isInstance(type, pt_ce_constant_array_type)) {
			zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
			if (UNEXPECTED(traversed.isUndef())) return;
			traversed.intoReturnValue(return_value);
			return;
		}

		zend_long atLeastOnce = callTrinary(type, PT_LC("isiterableatleastonce"));
		if (UNEXPECTED(atLeastOnce < 0)) return;
		if (atLeastOnce == PT_TRI_NO) {
			ZVAL_COPY(return_value, type);
			return;
		}

		ZVAL_TRUE(isOversized);

		zv::Val generalized = generalizeOversized(type);
		if (UNEXPECTED(generalized.isUndef())) return;
		generalized.intoReturnValue(return_value);
	}

	static zv::Val generalizeOversized(zval *type)
	{
		bool isList = true;
		zv::Arr valueTypes = zv::Arr::create(0);
		zv::Arr keyTypes = zv::Arr::create(0);
		zend_long nextAutoIndex = 0;
		bool nextAutoIndexOverflowed = false;
		zv::Val innerValueTypes = call(type, PT_LC("getvaluetypes"));
		PT_FAIL_IF_UNDEF(innerValueTypes);
		zv::Val innerKeyTypes = call(type, PT_LC("getkeytypes"));
		PT_FAIL_IF_UNDEF(innerKeyTypes);
		zv::Val moreSpecific = pt_type_call_static(PT_CLASS_GENERALIZE_PRECISION, PT_LC("morespecific"), 0, NULL);
		PT_FAIL_IF_UNDEF(moreSpecific);
		Level preciseLevel(PT_LC("precise"));
		for (zv::ArrayEntry entry : zv::ArrRef(innerKeyTypes.raw())) {
			zval *innerKeyType = entry.value().raw();
			if (!isInstance(innerKeyType, pt_ce_constant_integer_type)) {
				isList = false;
			} else {
				zend_long value;
				if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(innerKeyType), value))) return zv::Val();
				if (nextAutoIndexOverflowed || value != nextAutoIndex) {
					isList = false;
					/* $nextAutoIndex = $innerKeyType->getValue() + 1 — a
					 * float past PHP_INT_MAX, which no later key equals */
					nextAutoIndexOverflowed = value == ZEND_LONG_MAX;
					nextAutoIndex = nextAutoIndexOverflowed ? 0 : value + 1;
				} else if (nextAutoIndex == ZEND_LONG_MAX) {
					nextAutoIndexOverflowed = true;
				} else {
					nextAutoIndex++;
				}
			}

			zv::Val generalizedKeyType = call(innerKeyType, PT_LC("generalize"), 1, moreSpecific.raw());
			PT_FAIL_IF_UNDEF(generalizedKeyType);
			zv::Val keyDescription = describe(generalizedKeyType.raw(), preciseLevel);
			zend_string *keyKey = describedKey(keyDescription);
			if (UNEXPECTED(keyKey == NULL)) return zv::Val();
			keyTypes.set(keyKey, std::move(generalizedKeyType));

			zval *innerValueType = indexOf(innerValueTypes.raw(), entry.indexKey());
			if (UNEXPECTED(innerValueType == NULL)) {
				zend_throw_error(NULL, "Undefined array key " ZEND_LONG_FMT, (zend_long) entry.indexKey());
				return zv::Val();
			}
			zv::Val callback = pt_type_native_callback(generalizeValueCallback, NULL, NULL);
			PT_FAIL_IF_UNDEF(callback);
			zv::Val generalizedValueType;
			if (UNEXPECTED(!pt_type_traverser_map(generalizedValueType.raw(), innerValueType, callback.raw()))) return zv::Val();
			zv::Val valueDescription = describe(generalizedValueType.raw(), preciseLevel);
			zend_string *valueKey = describedKey(valueDescription);
			if (UNEXPECTED(valueKey == NULL)) return zv::Val();
			valueTypes.set(valueKey, std::move(generalizedValueType));
		}

		zv::Val keyType = unionSpread(keyTypes.raw());
		PT_FAIL_IF_UNDEF(keyType);
		zv::Val valueType = unionSpread(valueTypes.raw());
		PT_FAIL_IF_UNDEF(valueType);

		return oversizedArrayOf(keyType.raw(), valueType.raw(), isList);
	}

	/* self::intersect(new ArrayType($keyType, $valueType), [new
	 * AccessoryArrayListType()], new NonEmptyArrayType(), new
	 * OversizedArrayType()) */
	static zv::Val oversizedArrayOf(zval *keyType, zval *valueType, bool isList)
	{
		zv::Val array = arrayType(keyType, valueType);
		PT_FAIL_IF_UNDEF(array);
		TypeList accessories;
		if (isList) {
			zv::Val list = accessoryArrayListType();
			PT_FAIL_IF_UNDEF(list);
			accessories.push_back(std::move(list));
		}
		zv::Val nonEmpty = nonEmptyArrayType();
		PT_FAIL_IF_UNDEF(nonEmpty);
		accessories.push_back(std::move(nonEmpty));
		zv::Val oversized = oversizedArrayType();
		PT_FAIL_IF_UNDEF(oversized);
		accessories.push_back(std::move(oversized));

		return intersectWith(array.raw(), accessories);
	}

	/* the inner TypeTraverser::map() callback of the value position: an
	 * empty constant array stays, any array becomes oversized, a constant
	 * scalar is generalized, everything else traversed */
	static void generalizeValueCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state0;
		(void) state1;
		if (UNEXPECTED(argc < 2 || Z_TYPE_P(&argv[0]) != IS_OBJECT)) {
			zend_wrong_parameters_count_error(2, 2);
			return;
		}
		zval *type = &argv[0];
		if (isInstance(type, pt_ce_constant_array_type)) {
			zend_long atLeastOnce = callTrinary(type, PT_LC("isiterableatleastonce"));
			if (UNEXPECTED(atLeastOnce < 0)) return;
			if (atLeastOnce == PT_TRI_NO) {
				ZVAL_COPY(return_value, type);
				return;
			}
		}

		if (isInstance(type, pt_ce_array_type) || isInstance(type, pt_ce_constant_array_type)) {
			zv::Val oversized = oversizedArrayType();
			if (UNEXPECTED(oversized.isUndef())) return;
			zv::Arr types = zv::Arr::create(2);
			types.push(zv::Ref(type));
			types.push(std::move(oversized));
			zv::Val intersection = intersectionType(std::move(types));
			if (UNEXPECTED(intersection.isUndef())) return;
			intersection.intoReturnValue(return_value);
			return;
		}

		int isConstantScalar = isInstanceMap(type, PT_CLASS_CONSTANT_SCALAR_TYPE);
		if (UNEXPECTED(isConstantScalar < 0)) return;
		if (isConstantScalar) {
			zv::Val moreSpecific = pt_type_call_static(PT_CLASS_GENERALIZE_PRECISION, PT_LC("morespecific"), 0, NULL);
			if (UNEXPECTED(moreSpecific.isUndef())) return;
			zv::Val generalized = call(type, PT_LC("generalize"), 1, moreSpecific.raw());
			if (UNEXPECTED(generalized.isUndef())) return;
			generalized.intoReturnValue(return_value);
			return;
		}

		zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
		if (UNEXPECTED(traversed.isUndef())) return;
		traversed.intoReturnValue(return_value);
	}

	/* private static optimizeConstantArrays(Type[] $types): Type[] */
	static zv::Val optimizeConstantArrays(zval *typesArray)
	{
		zv::Val types = copy(typesArray);
		zend_long limit = arrayCountLimit();
		PT_FAIL_IF_NEG(limit);
		zend_long constantArrayValuesCount = countConstantArrayValueTypes(types.raw());
		PT_FAIL_IF_NEG(constantArrayValuesCount);

		if (constantArrayValuesCount <= limit) return types;

		/* Stage 1: collapse same-key-set ConstantArrayType variants
		 * per-position before the (lossy) generalization below kicks in */
		zv::Arr signatureGroups = zv::Arr::create(0);
		zv::Arr nonConstantTypes = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zval *type = entry.value().raw();
			if (!isInstance(type, pt_ce_constant_array_type)) {
				zval copied;
				ZVAL_COPY(&copied, type);
				if (entry.stringKeyOrNull() != NULL) {
					zend_hash_update(nonConstantTypes.table(), entry.stringKeyOrNull(), &copied);
				} else {
					zend_hash_index_update(nonConstantTypes.table(), entry.indexKey(), &copied);
				}
				continue;
			}
			zv::Val signature = keySignature(type, true);
			PT_FAIL_IF_UNDEF(signature);
			nestedPush(signatureGroups, Z_STR_P(signature.raw()), copy(type));
		}
		if (zend_hash_num_elements(signatureGroups.table()) > 0) {
			zv::Arr collapsed = zv::Arr::adoptVal(zv::Val::copyOf(nonConstantTypes.ref()));
			bool anyMerged = false;
			for (zv::ArrayEntry groupEntry : zv::ArrRef(signatureGroups.raw())) {
				zval *group = groupEntry.value().raw();
				uint32_t count = countOf(group);
				if (count == 1) {
					collapsed.push(zv::Ref(indexOf(group, 0)));
					continue;
				}
				zv::Val merged = copy(indexOf(group, 0));
				for (uint32_t i = 1; i < count; i++) {
					zv::Val next = call(merged.raw(), PT_LC("mergewith"), 1, indexOf(group, i));
					PT_FAIL_IF_UNDEF(next);
					merged = std::move(next);
				}
				collapsed.push(std::move(merged));
				anyMerged = true;
			}
			if (anyMerged) {
				TypeList values;
				listFrom(collapsed.raw(), values);
				types = zv::Val(listOf(values));
				constantArrayValuesCount = countConstantArrayValueTypes(types.raw());
				PT_FAIL_IF_NEG(constantArrayValuesCount);
				if (constantArrayValuesCount <= limit) return types;
			}
		}

		zv::Arr results = zv::Arr::create(countOf(types.raw()));
		bool eachIsOversized = true;
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zval isOversized;
			ZVAL_FALSE(&isOversized);
			zv::Val callback = pt_type_native_callback(generalizeOversizedCallback, &isOversized, NULL);
			PT_FAIL_IF_UNDEF(callback);
			zv::Val result;
			if (UNEXPECTED(!pt_type_traverser_map(result.raw(), entry.value().raw(), callback.raw()))) return zv::Val();

			if (Z_TYPE_P(pt_type_native_callback_state(callback.raw(), 0)) != IS_TRUE) {
				eachIsOversized = false;
			}

			results.push(std::move(result));
		}

		if (eachIsOversized) {
			bool eachIsList = true;
			TypeList keyTypes;
			TypeList valueTypes;
			for (zv::ArrayEntry entry : zv::ArrRef(results.raw())) {
				zval *result = entry.value().raw();
				zv::Val keyType = call(result, PT_LC("getiterablekeytype"));
				PT_FAIL_IF_UNDEF(keyType);
				keyTypes.push_back(std::move(keyType));
				zv::Val valueType = call(result, PT_LC("getiterablevaluetype"));
				PT_FAIL_IF_UNDEF(valueType);
				valueTypes.push_back(std::move(valueType));
				zend_long isList = callTrinary(result, PT_LC("islist"));
				PT_FAIL_IF_NEG(isList);
				if (isList == PT_TRI_YES) continue;
				eachIsList = false;
			}

			zv::Val keyType = unionOf(keyTypes);
			PT_FAIL_IF_UNDEF(keyType);
			zv::Val valueType = unionOf(valueTypes);
			PT_FAIL_IF_UNDEF(valueType);

			if (isInstance(valueType.raw(), pt_ce_union_type)) {
				zv::Val members = getTypes(valueType.raw());
				PT_FAIL_IF_UNDEF(members);
				if ((zend_long) countOf(members.raw()) > limit) {
					zv::Val lessSpecific = pt_type_call_static(PT_CLASS_GENERALIZE_PRECISION, PT_LC("lessspecific"), 0, NULL);
					PT_FAIL_IF_UNDEF(lessSpecific);
					valueType = call(valueType.raw(), PT_LC("generalize"), 1, lessSpecific.raw());
					PT_FAIL_IF_UNDEF(valueType);
				}
			}

			zv::Val oversized = oversizedArrayOf(keyType.raw(), valueType.raw(), eachIsList);
			PT_FAIL_IF_UNDEF(oversized);
			zv::Arr single = zv::Arr::create(1);
			single.push(std::move(oversized));
			return zv::Val(std::move(single));
		}

		return zv::Val(std::move(results));
	}

	/* the key signature of a ConstantArrayType the stage-1 grouping and the
	 * list-variant fold use: ['L'|'A' when $withListFlag,] then per key
	 * '?'|'!' . 'i'|'s' . value, joined by ','; an owned string */
	static zv::Val keySignature(zval *type, bool withListFlag)
	{
		smart_str signature = { 0, 0 };
		bool first = true;
		if (withListFlag) {
			zend_long isList = callTrinary(type, PT_LC("islist"));
			if (UNEXPECTED(isList < 0)) {
				smart_str_free(&signature);
				return zv::Val();
			}
			smart_str_appendc(&signature, isList == PT_TRI_YES ? 'L' : 'A');
			first = false;
		}
		zv::Val keyTypes = call(type, PT_LC("getkeytypes"));
		if (UNEXPECTED(keyTypes.isUndef())) {
			smart_str_free(&signature);
			return zv::Val();
		}
		for (zv::ArrayEntry entry : zv::ArrRef(keyTypes.raw())) {
			zval index;
			ZVAL_LONG(&index, (zend_long) entry.indexKey());
			int optional = callBool(type, PT_LC("isoptionalkey"), 1, &index);
			if (UNEXPECTED(optional < 0)) {
				smart_str_free(&signature);
				return zv::Val();
			}
			zval *keyType = entry.value().raw();
			zv::Val value = call(keyType, PT_LC("getvalue"));
			if (UNEXPECTED(value.isUndef())) {
				smart_str_free(&signature);
				return zv::Val();
			}
			if (!first) {
				smart_str_appendc(&signature, ',');
			}
			first = false;
			smart_str_appendc(&signature, optional ? '?' : '!');
			smart_str_appendc(&signature, isInstance(keyType, pt_ce_constant_integer_type) ? 'i' : 's');
			if (Z_TYPE_P(value.raw()) == IS_LONG) {
				smart_str_append_long(&signature, Z_LVAL_P(value.raw()));
			} else {
				zend_string *str = zval_get_string(value.raw());
				smart_str_append(&signature, str);
				zend_string_release(str);
			}
		}
		smart_str_0(&signature);
		if (signature.s == NULL) return zv::Val::string("", 0);
		return zv::Val::adoptString(smart_str_extract(&signature));
	}

	/* countConstantArrayValueTypes()'s callback: `use (&$constantArrayValuesCount)`
	 * in the holder's first state slot, every ConstantArrayType's value
	 * count added, the type traversed */
	static void countValueTypesCallback(zval *count, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		if (UNEXPECTED(argc < 2 || Z_TYPE_P(&argv[0]) != IS_OBJECT)) {
			zend_wrong_parameters_count_error(2, 2);
			return;
		}
		zval *type = &argv[0];
		if (isInstance(type, pt_ce_constant_array_type)) {
			zv::Val valueTypes = call(type, PT_LC("getvaluetypes"));
			if (UNEXPECTED(valueTypes.isUndef())) return;
			ZVAL_LONG(count, Z_LVAL_P(count) + (zend_long) countOf(valueTypes.raw()));
		}

		zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
		if (UNEXPECTED(traversed.isUndef())) return;
		traversed.intoReturnValue(return_value);
	}

	/* countConstantArrayValueTypes(Type[] $types): int; -1 = pending
	 * exception */
	[[nodiscard]] static zend_long countConstantArrayValueTypes(zval *types)
	{
		zend_long constantArrayValuesCount = 0;
		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			zval count;
			ZVAL_LONG(&count, constantArrayValuesCount);
			zv::Val callback = pt_type_native_callback(countValueTypesCallback, &count, NULL);
			if (UNEXPECTED(callback.isUndef())) return -1;
			zv::Val mapped;
			if (UNEXPECTED(!pt_type_traverser_map(mapped.raw(), entry.value().raw(), callback.raw()))) return -1;
			constantArrayValuesCount = Z_LVAL_P(pt_type_native_callback_state(callback.raw(), 0));
		}
		return constantArrayValuesCount;
	}

	/* {{{ reduceArrays() */

	/* $arraysToProcess[$i] (borrowed); NULL when unset */
	static zval *arrayAt(zv::Arr &arraysToProcess, zend_ulong i)
	{
		return zend_hash_index_find(arraysToProcess.table(), i);
	}

	/* count($array->getKeyTypes()); -1 = pending exception */
	[[nodiscard]] static zend_long keyTypesCount(zval *array)
	{
		zv::Val keyTypes = call(array, PT_LC("getkeytypes"));
		if (UNEXPECTED(keyTypes.isUndef())) return -1;
		return (zend_long) countOf(keyTypes.raw());
	}

	/* $arraysToProcess[$into] = $arraysToProcess[$into]->mergeWith($arraysToProcess[$from]);
	 * unset($arraysToProcess[$from]); false = pending exception */
	[[nodiscard]] static bool mergeInto(zv::Arr &arraysToProcess, zend_ulong into, zend_ulong from)
	{
		zv::Val merged = call(arrayAt(arraysToProcess, into), PT_LC("mergewith"), 1, arrayAt(arraysToProcess, from));
		if (UNEXPECTED(merged.isUndef())) return false;
		zval v = merged.take();
		zend_hash_index_update(arraysToProcess.table(), into, &v);
		zend_hash_index_del(arraysToProcess.table(), from);
		return true;
	}

	/* $a->isKeysSupersetOf($b); -1 = pending exception */
	static int isKeysSupersetOf(zval *a, zval *b)
	{
		return callBool(a, PT_LC("iskeyssupersetof"), 1, b);
	}

	/* private static reduceArrays(list<Type> $constantArrays, bool $preserveTaggedUnions): list<Type> */
	static zv::Val reduceArrays(zval *constantArrays, bool preserveTaggedUnions)
	{
		zv::Arr newArrays = zv::Arr::create(0);
		zv::Arr arraysToProcess = zv::Arr::create(0);
		zv::Val emptyArray = zv::Val::null();
		for (zv::ArrayEntry entry : zv::ArrRef(constantArrays)) {
			zval *constantArray = entry.value().raw();
			zend_long isConstantArray = callTrinary(constantArray, PT_LC("isconstantarray"));
			PT_FAIL_IF_NEG(isConstantArray);
			if (isConstantArray != PT_TRI_YES) {
				/* the $preserveTaggedUnions=false use-case needs one constant
				 * array as a result, or generalizes the $constantArrays */
				if (!preserveTaggedUnions) return copy(constantArrays);
				newArrays.push(zv::Ref(constantArray));
				continue;
			}

			zend_long atLeastOnce = callTrinary(constantArray, PT_LC("isiterableatleastonce"));
			PT_FAIL_IF_NEG(atLeastOnce);
			if (atLeastOnce == PT_TRI_NO) {
				emptyArray = copy(constantArray);
				continue;
			}

			zv::Val inner = call(constantArray, PT_LC("getconstantarrays"));
			PT_FAIL_IF_UNDEF(inner);
			for (zv::ArrayEntry innerEntry : zv::ArrRef(inner.raw())) {
				arraysToProcess.push(innerEntry.value());
			}
		}

		if (!isNull(emptyArray)) {
			if (preserveTaggedUnions && isInstance(emptyArray.raw(), pt_ce_constant_array_type)) {
				/* the empty array takes part in merging — absorbed by any
				 * array that already accepts [] */
				arraysToProcess.push(std::move(emptyArray));
			} else {
				newArrays.push(std::move(emptyArray));
			}
		}

		zv::Arr arraysToProcessPerKey = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(arraysToProcess.raw())) {
			zv::Val keyTypes = call(entry.value().raw(), PT_LC("getkeytypes"));
			PT_FAIL_IF_UNDEF(keyTypes);
			for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
				zv::Val keyValue = call(keyEntry.value().raw(), PT_LC("getvalue"));
				PT_FAIL_IF_UNDEF(keyValue);
				zval *perKey = symtableFind(arraysToProcessPerKey.table(), keyValue.raw());
				if (perKey == NULL) {
					if (UNEXPECTED(EG(exception))) return zv::Val();
					zval fresh;
					array_init(&fresh);
					if (UNEXPECTED(!symtableSet(arraysToProcessPerKey.table(), keyValue.raw(), &fresh))) return zv::Val();
					perKey = symtableFind(arraysToProcessPerKey.table(), keyValue.raw());
				}
				zval index;
				ZVAL_LONG(&index, (zend_long) entry.indexKey());
				zend_hash_next_index_insert(Z_ARRVAL_P(perKey), &index);
			}
		}

		zv::Arr eligibleCombinations = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(arraysToProcessPerKey.raw())) {
			HashTable *arrays = Z_ARRVAL_P(entry.value().raw());
			zend_long arraysCount = (zend_long) zend_hash_num_elements(arrays);
			for (zend_long i = 0; i < arraysCount - 1; i++) {
				zend_ulong first = (zend_ulong) Z_LVAL_P(zend_hash_index_find(arrays, (zend_ulong) i));
				for (zend_long j = i + 1; j < arraysCount; j++) {
					zend_ulong second = (zend_ulong) Z_LVAL_P(zend_hash_index_find(arrays, (zend_ulong) j));
					zval *other = zend_hash_index_find(eligibleCombinations.table(), first);
					if (other == NULL) {
						zval fresh;
						array_init(&fresh);
						other = zend_hash_index_update(eligibleCombinations.table(), first, &fresh);
					}
					zval *count = zend_hash_index_find(Z_ARRVAL_P(other), second);
					if (count == NULL) {
						zval zero;
						ZVAL_LONG(&zero, 0);
						count = zend_hash_index_update(Z_ARRVAL_P(other), second, &zero);
					}
					Z_LVAL_P(count)++;
				}
			}
		}

		for (zv::ArrayEntry entry : zv::ArrRef(eligibleCombinations.raw())) {
			zend_ulong i = entry.indexKey();
			if (arrayAt(arraysToProcess, i) == NULL) continue;

			for (zv::ArrayEntry otherEntry : zv::ArrRef(entry.value().raw())) {
				zend_ulong j = otherEntry.indexKey();
				zend_long overlappingKeysCount = Z_LVAL_P(otherEntry.value().raw());
				if (arrayAt(arraysToProcess, j) == NULL) continue;

				zend_long iKeysCount = keyTypesCount(arrayAt(arraysToProcess, i));
				PT_FAIL_IF_NEG(iKeysCount);

				/* Merge two single-key arrays sharing the same key when their
				 * value types union into a single type (not a UnionType) —
				 * lossless, and it prevents exponential union growth
				 * (phpstan/phpstan#14462) */
				if (preserveTaggedUnions && overlappingKeysCount == 1 && iKeysCount == 1) {
					zend_long jKeysCount = keyTypesCount(arrayAt(arraysToProcess, j));
					PT_FAIL_IF_NEG(jKeysCount);
					if (jKeysCount == 1) {
						zv::Val iValueTypes = call(arrayAt(arraysToProcess, i), PT_LC("getvaluetypes"));
						PT_FAIL_IF_UNDEF(iValueTypes);
						zv::Val jValueTypes = call(arrayAt(arraysToProcess, j), PT_LC("getvaluetypes"));
						PT_FAIL_IF_UNDEF(jValueTypes);
						zval *iValueType = indexOf(iValueTypes.raw(), 0);
						zval *jValueType = indexOf(jValueTypes.raw(), 0);
						if (UNEXPECTED(iValueType == NULL || jValueType == NULL)) {
							zend_throw_error(NULL, "Undefined array key 0");
							return zv::Val();
						}
						zv::Val unionValueType = union2(iValueType, jValueType);
						PT_FAIL_IF_UNDEF(unionValueType);
						if (!isInstance(unionValueType.raw(), pt_ce_union_type)) {
							if (UNEXPECTED(!mergeInto(arraysToProcess, j, i))) return zv::Val();
							break;
						}
					}
				}

				if (preserveTaggedUnions && overlappingKeysCount == iKeysCount) {
					int superset = isKeysSupersetOf(arrayAt(arraysToProcess, j), arrayAt(arraysToProcess, i));
					PT_FAIL_IF_NEG(superset);
					if (superset) {
						if (UNEXPECTED(!mergeInto(arraysToProcess, j, i))) return zv::Val();
						break;
					}
				}

				zend_long jKeysCount = keyTypesCount(arrayAt(arraysToProcess, j));
				PT_FAIL_IF_NEG(jKeysCount);
				if (preserveTaggedUnions && overlappingKeysCount == jKeysCount) {
					int superset = isKeysSupersetOf(arrayAt(arraysToProcess, i), arrayAt(arraysToProcess, j));
					PT_FAIL_IF_NEG(superset);
					if (superset) {
						if (UNEXPECTED(!mergeInto(arraysToProcess, i, j))) return zv::Val();
						continue;
					}
				}

				if (!preserveTaggedUnions
					/* both arrays have same keys */
					&& overlappingKeysCount == iKeysCount
					&& overlappingKeysCount == jKeysCount) {
					if (UNEXPECTED(!mergeInto(arraysToProcess, j, i))) return zv::Val();
					break;
				}
			}
		}

		/* Second pass: merge pairs that the eligibleCombinations loop above
		 * couldn't touch — pairs sharing no known key where one side's
		 * extras or optional-key shape can absorb the other side's content;
		 * two sealed, non-empty, no-extras arrays are skipped via a
		 * candidate flag (see the twin) */
		std::vector<zend_ulong> indices;
		for (zv::ArrayEntry entry : zv::ArrRef(arraysToProcess.raw())) {
			indices.push_back(entry.indexKey());
		}
		size_t indicesCount = indices.size();
		if (indicesCount > 1) {
			std::vector<bool> candidateFlags(indicesCount, false);
			for (size_t position = 0; position < indicesCount; position++) {
				zval *arr = arrayAt(arraysToProcess, indices[position]);
				zv::Val unsealed = call(arr, PT_LC("getunsealedtypes"));
				PT_FAIL_IF_UNDEF(unsealed);
				if (isNull(unsealed)) {
					candidateFlags[position] = false;
					continue;
				}
				zval *unsealedKey = indexOf(unsealed.raw(), 0);
				if (UNEXPECTED(unsealedKey == NULL)) {
					zend_throw_error(NULL, "Undefined array key 0");
					return zv::Val();
				}
				int explicitNever = isExplicitNever(unsealedKey);
				PT_FAIL_IF_NEG(explicitNever);
				bool hasRealExtras = !explicitNever;
				if (hasRealExtras) {
					candidateFlags[position] = true;
					continue;
				}
				zend_long keysCount = keyTypesCount(arr);
				PT_FAIL_IF_NEG(keysCount);
				if (keysCount == 0) {
					candidateFlags[position] = true;
					continue;
				}
				zv::Val optionalKeys = call(arr, PT_LC("getoptionalkeys"));
				PT_FAIL_IF_UNDEF(optionalKeys);
				candidateFlags[position] = countOf(optionalKeys.raw()) > 0;
			}

			for (size_t ii = 0; ii < indicesCount - 1; ii++) {
				zend_ulong i = indices[ii];
				if (arrayAt(arraysToProcess, i) == NULL) continue;
				zv::Val iUnsealed = call(arrayAt(arraysToProcess, i), PT_LC("getunsealedtypes"));
				PT_FAIL_IF_UNDEF(iUnsealed);
				if (isNull(iUnsealed)) continue;
				for (size_t jj = ii + 1; jj < indicesCount; jj++) {
					zend_ulong j = indices[jj];
					if (arrayAt(arraysToProcess, j) == NULL) continue;
					if (!candidateFlags[ii] && !candidateFlags[jj]) continue;
					zv::Val jUnsealed = call(arrayAt(arraysToProcess, j), PT_LC("getunsealedtypes"));
					PT_FAIL_IF_UNDEF(jUnsealed);
					if (isNull(jUnsealed)) continue;
					int jSuperset = isKeysSupersetOf(arrayAt(arraysToProcess, j), arrayAt(arraysToProcess, i));
					PT_FAIL_IF_NEG(jSuperset);
					if (jSuperset) {
						if (UNEXPECTED(!mergeInto(arraysToProcess, j, i))) return zv::Val();
						break;
					}
					int iSuperset = isKeysSupersetOf(arrayAt(arraysToProcess, i), arrayAt(arraysToProcess, j));
					PT_FAIL_IF_NEG(iSuperset);
					if (!iSuperset) continue;

					if (UNEXPECTED(!mergeInto(arraysToProcess, i, j))) return zv::Val();
				}
			}
		}

		/* Final pass: a ConstantArrayType with no known keys but real
		 * unsealed extras collapses to a plain ArrayType */
		std::vector<zend_ulong> collapsedIndices;
		for (zv::ArrayEntry entry : zv::ArrRef(arraysToProcess.raw())) {
			zval *arr = entry.value().raw();
			zend_long keysCount = keyTypesCount(arr);
			PT_FAIL_IF_NEG(keysCount);
			if (keysCount != 0) continue;
			zv::Val unsealed = call(arr, PT_LC("getunsealedtypes"));
			PT_FAIL_IF_UNDEF(unsealed);
			if (isNull(unsealed)) continue;
			zval *unsealedKey = indexOf(unsealed.raw(), 0);
			zval *unsealedValue = indexOf(unsealed.raw(), 1);
			if (UNEXPECTED(unsealedKey == NULL || unsealedValue == NULL)) {
				zend_throw_error(NULL, "Undefined array key");
				return zv::Val();
			}
			int explicitNever = isExplicitNever(unsealedKey);
			PT_FAIL_IF_NEG(explicitNever);
			if (explicitNever) continue;
			zv::Val array = arrayType(unsealedKey, unsealedValue);
			PT_FAIL_IF_UNDEF(array);
			newArrays.push(std::move(array));
			collapsedIndices.push_back(entry.indexKey());
		}
		for (zend_ulong idx : collapsedIndices) {
			zend_hash_index_del(arraysToProcess.table(), idx);
		}

		/* Final pass: collapse the loop-accumulator pattern where each
		 * iteration produced a longer non-empty list variant into a single
		 * non-empty-list<unionValueType>, unless every list variant shares
		 * one key signature (those collapse losslessly in
		 * optimizeConstantArrays()) */
		if (preserveTaggedUnions && zend_hash_num_elements(arraysToProcess.table()) > 1) {
			std::vector<zend_ulong> listVariantIndices;
			TypeList listValueTypes;
			zv::Arr listVariants = zv::Arr::create(0);
			zv::Arr listVariantSignatures = zv::Arr::create(0);
			for (zv::ArrayEntry entry : zv::ArrRef(arraysToProcess.raw())) {
				zval *arr = entry.value().raw();
				zend_long isList = callTrinary(arr, PT_LC("islist"));
				PT_FAIL_IF_NEG(isList);
				if (isList != PT_TRI_YES) continue;
				zend_long atLeastOnce = callTrinary(arr, PT_LC("isiterableatleastonce"));
				PT_FAIL_IF_NEG(atLeastOnce);
				if (atLeastOnce != PT_TRI_YES) continue;
				listVariantIndices.push_back(entry.indexKey());
				zv::Val valueType = call(arr, PT_LC("getiterablevaluetype"));
				PT_FAIL_IF_UNDEF(valueType);
				listValueTypes.push_back(std::move(valueType));
				listVariants.push(zv::Ref(arr));
				zv::Val signature = keySignature(arr, false);
				PT_FAIL_IF_UNDEF(signature);
				listVariantSignatures.set(Z_STR_P(signature.raw()), zv::Val::boolean(true));
			}
			if (listVariantIndices.size() >= 2 && zend_hash_num_elements(listVariantSignatures.table()) >= 2) {
				zend_long limit = arrayCountLimit();
				PT_FAIL_IF_NEG(limit);
				zend_long valuesCount = countConstantArrayValueTypes(listVariants.raw());
				PT_FAIL_IF_NEG(valuesCount);
				if (valuesCount > limit) {
					zv::Val mergedValueType = unionOf(listValueTypes);
					PT_FAIL_IF_UNDEF(mergedValueType);
					zv::Val integer = integerType();
					PT_FAIL_IF_UNDEF(integer);
					zv::Val array = arrayType(integer.raw(), mergedValueType.raw());
					PT_FAIL_IF_UNDEF(array);
					TypeList accessories;
					zv::Val nonEmpty = nonEmptyArrayType();
					PT_FAIL_IF_UNDEF(nonEmpty);
					accessories.push_back(std::move(nonEmpty));
					zv::Val list = accessoryArrayListType();
					PT_FAIL_IF_UNDEF(list);
					accessories.push_back(std::move(list));
					zv::Val merged = intersectWith(array.raw(), accessories);
					PT_FAIL_IF_UNDEF(merged);
					newArrays.push(std::move(merged));
					for (zend_ulong idx : listVariantIndices) {
						zend_hash_index_del(arraysToProcess.table(), idx);
					}
				}
			}
		}

		/* array_merge($newArrays, $arraysToProcess) */
		for (zv::ArrayEntry entry : zv::ArrRef(arraysToProcess.raw())) {
			newArrays.push(entry.value());
		}
		return zv::Val(std::move(newArrays));
	}

	/* $type instanceof NeverType && $type->isExplicit(); -1 = pending
	 * exception */
	static int isExplicitNever(zval *type)
	{
		if (!isInstance(type, pt_ce_never_type)) return 0;
		bool isExplicit;
		if (UNEXPECTED(!pt_never_type_is_explicit(Z_OBJ_P(type), isExplicit))) return -1;
		return isExplicit ? 1 : 0;
	}

	/* }}} */

	/* {{{ intersect() and its helpers */

	/* private static intersectFiniteUnions(UnionType $a, UnionType $b): ?Type
	 * (IS_NULL for null) */
	static zv::Val intersectFiniteUnions(zval *a, zval *b)
	{
		zv::Val membersA = finiteUnionMembers(a);
		PT_FAIL_IF_UNDEF(membersA);
		if (isNull(membersA)) return zv::Val::null();

		zv::Val membersB = finiteUnionMembers(b);
		PT_FAIL_IF_UNDEF(membersB);
		if (isNull(membersB)) return zv::Val::null();

		TypeList common;
		for (zv::ArrayEntry entry : zv::ArrRef(membersA.raw())) {
			if (!pt_ht_exists(Z_ARRVAL_P(membersB.raw()), entry.stringKeyOrNull(), entry.indexKey())) continue;

			common.push_back(copy(entry.value().raw()));
		}

		if (common.empty()) return neverType();

		return unionOf(common);
	}

	/* private static finiteUnionMembers(UnionType $union): ?array (IS_NULL
	 * for null) */
	static zv::Val finiteUnionMembers(zval *unionType)
	{
		zv::Val finiteTypeSet = call(unionType, PT_LC("getfinitetypeset"));
		PT_FAIL_IF_UNDEF(finiteTypeSet);
		if (isNull(finiteTypeSet)) return zv::Val::null();
		int complete = callBool(finiteTypeSet.raw(), PT_LC("iscomplete"));
		PT_FAIL_IF_NEG(complete);
		if (!complete) return zv::Val::null();

		return call(finiteTypeSet.raw(), PT_LC("getmembers"));
	}

	static zv::Val intersect(uint32_t argc, zval *argv)
	{
		bool enabled;
		if (UNEXPECTED(!cacheEnabled(enabled))) return zv::Val();
		if (enabled) return pt_type_combinator_cache_intersect(argc, argv);
		return doIntersect(argc, argv);
	}

	/* the $sortTypes comparator of doIntersect(): only UnionTypes are
	 * ordered relative to each other — template unions first, then
	 * benevolent ones */
	static int compareUnionsForIntersect(zval *a, zval *b)
	{
		if (!isInstance(a, pt_ce_union_type) || !isInstance(b, pt_ce_union_type)) return 0;

		int aTemplate = isInstanceMap(a, PT_CLASS_TEMPLATE_TYPE);
		if (UNEXPECTED(aTemplate < 0)) {
			sortFailed = true;
			return 0;
		}
		if (aTemplate) return -1;
		int bTemplate = isInstanceMap(b, PT_CLASS_TEMPLATE_TYPE);
		if (UNEXPECTED(bTemplate < 0)) {
			sortFailed = true;
			return 0;
		}
		if (bTemplate) return 1;

		if (isInstance(a, pt_ce_benevolent_union_type)) return -1;
		if (isInstance(b, pt_ce_benevolent_union_type)) return 1;

		return 0;
	}

	/* $type instanceof SubtractableType && $type->getSubtractedType() !== null;
	 * -1 = pending exception */
	static int hasSubtractedType(zval *type)
	{
		int subtractable = isInstanceMap(type, PT_CLASS_SUBTRACTABLE_TYPE);
		if (UNEXPECTED(subtractable < 0)) return -1;
		if (!subtractable) return 0;
		zv::Val subtracted = call(type, PT_LC("getsubtractedtype"));
		if (UNEXPECTED(subtracted.isUndef())) return -1;
		return isNull(subtracted) ? 0 : 1;
	}

	/* the subtractable/constant-array comparator of doIntersect(): the
	 * subtractables with subtracts first, then the constant arrays */
	static int compareSubtractablesForIntersect(zval *a, zval *b)
	{
		int aSubtracts = hasSubtractedType(a);
		if (UNEXPECTED(aSubtracts < 0)) {
			sortFailed = true;
			return 0;
		}
		if (aSubtracts) return -1;
		int bSubtracts = hasSubtractedType(b);
		if (UNEXPECTED(bSubtracts < 0)) {
			sortFailed = true;
			return 0;
		}
		if (bSubtracts) return 1;

		bool aConstantArray = isInstance(a, pt_ce_constant_array_type);
		bool bConstantArray = isInstance(b, pt_ce_constant_array_type);
		if (aConstantArray && !bConstantArray) return -1;
		if (bConstantArray && !aConstantArray) return 1;

		return 0;
	}

	/* $types[$j]->isSuperTypeOfMixed($types[$i]) for an IterableType,
	 * $types[$j]->isSuperTypeOf($types[$i]) otherwise; UNDEF = pending
	 * exception */
	static zv::Val superTypeResult(zval *type, zval *other)
	{
		if (isInstance(type, pt_ce_iterable_type)) return call(type, PT_LC("issupertypeofmixed"), 1, other);
		return call(type, PT_LC("issupertypeof"), 1, other);
	}

	/* the SubtractableType arm of doIntersect()'s reduction: whether the
	 * subtractable's type without its subtracted type is a super type of
	 * $other; -1 = pending exception */
	static int subtractableCoversInIntersect(zval *subtractable, zval *other)
	{
		return subtractableCovers(subtractable, other);
	}

	/* $constantArray->makeOffsetRequired($constantArray->getKeyTypes()[0])
	 * applies: a single key or a list, key 0 optional, not unsealed; -1 =
	 * pending exception */
	static int singleOptionalKeyToRequire(zval *constantArray)
	{
		zend_long keysCount = keyTypesCount(constantArray);
		if (UNEXPECTED(keysCount < 0)) return -1;
		if (keysCount != 1) {
			zend_long isList = callTrinary(constantArray, PT_LC("islist"));
			if (UNEXPECTED(isList < 0)) return -1;
			if (isList != PT_TRI_YES) return 0;
		}
		zval zero;
		ZVAL_LONG(&zero, 0);
		int optional = callBool(constantArray, PT_LC("isoptionalkey"), 1, &zero);
		if (UNEXPECTED(optional < 0)) return -1;
		if (!optional) return 0;
		zend_long unsealed = callTrinary(constantArray, PT_LC("isunsealed"));
		if (UNEXPECTED(unsealed < 0)) return -1;
		return unsealed == PT_TRI_YES ? 0 : 1;
	}

	/* $constantArray->makeOffsetRequired($constantArray->getKeyTypes()[0]) */
	static zv::Val makeFirstOffsetRequired(zval *constantArray)
	{
		zv::Val keyTypes = call(constantArray, PT_LC("getkeytypes"));
		PT_FAIL_IF_UNDEF(keyTypes);
		zval *first = indexOf(keyTypes.raw(), 0);
		if (UNEXPECTED(first == NULL)) {
			zend_throw_error(NULL, "Undefined array key 0");
			return zv::Val();
		}
		return call(constantArray, PT_LC("makeoffsetrequired"), 1, first);
	}

	/* $constantArray->setOffsetValueType($offset->getOffsetType(),
	 * self::intersect($constantArray->getOffsetValueType(...), $offset->getValueType()))
	 * for a HasOffsetValueType $offset; the intersected value type in
	 * $newValueType (NeverType = the caller returns it); UNDEF = pending
	 * exception */
	static zv::Val setOffsetValueFromAccessory(zval *constantArray, zval *offset, zv::Val &newValueType)
	{
		zv::Val offsetType = pt_has_offset_value_type_get_offset_type(Z_OBJ_P(offset));
		PT_FAIL_IF_UNDEF(offsetType);
		zv::Val valueType = pt_has_offset_value_type_get_value_type(Z_OBJ_P(offset));
		PT_FAIL_IF_UNDEF(valueType);
		zv::Val existing = call(constantArray, PT_LC("getoffsetvaluetype"), 1, offsetType.raw());
		PT_FAIL_IF_UNDEF(existing);
		newValueType = intersect2(existing.raw(), valueType.raw());
		PT_FAIL_IF_UNDEF(newValueType);
		if (isInstance(newValueType.raw(), pt_ce_never_type)) return zv::Val::null();
		zv::Args args{offsetType.raw(), newValueType.raw()};
		return call(constantArray, PT_LC("setoffsetvaluetype"), 2, args);
	}

	/* the constant array & array arm of doIntersect(): $constArray merged
	 * with $otherArray (a definite constant array pair through
	 * intersectDefiniteConstantArrays(), otherwise rebuilt through the
	 * builder); the never reason falls back on $isSuperTypeA/$isSuperTypeB;
	 * UNDEF = pending exception */
	static zv::Val intersectConstantArrayWith(zval *constArray, zval *otherArray, zval *isSuperTypeA, zval *isSuperTypeB)
	{
		if (isInstance(otherArray, pt_ce_constant_array_type)) {
			zend_long constUnsealed = callTrinary(constArray, PT_LC("isunsealed"));
			PT_FAIL_IF_NEG(constUnsealed);
			if (constUnsealed != PT_TRI_MAYBE) {
				zend_long otherUnsealed = callTrinary(otherArray, PT_LC("isunsealed"));
				PT_FAIL_IF_NEG(otherUnsealed);
				if (otherUnsealed != PT_TRI_MAYBE) {
					zv::Val merged = intersectDefiniteConstantArrays(constArray, otherArray);
					PT_FAIL_IF_UNDEF(merged);
					if (isInstance(merged.raw(), pt_ce_never_type)) {
						zv::Val reason = call(merged.raw(), PT_LC("getreason"));
						PT_FAIL_IF_UNDEF(reason);
						if (isNull(reason)) {
							zv::Val reasonsA = call(isSuperTypeA, PT_LC("getreasons"));
							PT_FAIL_IF_UNDEF(reasonsA);
							zv::Val reasonsB = call(isSuperTypeB, PT_LC("getreasons"));
							PT_FAIL_IF_UNDEF(reasonsB);
							zval *firstReason = NULL;
							for (zval *reasons : { reasonsA.raw(), reasonsB.raw() }) {
								for (zv::ArrayEntry entry : zv::ArrRef(reasons)) {
									firstReason = entry.value().raw();
									break;
								}
								if (firstReason != NULL) break;
							}
							if (firstReason != NULL) return neverTypeWithReason(firstReason);
						}
					}
					return merged;
				}
			}
		}

		zv::Val newArray = pt_type_call_static(PT_CLASS_CONSTANT_ARRAY_TYPE_BUILDER, PT_LC("createempty"), 0, NULL);
		PT_FAIL_IF_UNDEF(newArray);
		/* Preserve unsealed extras from the source shape, intersected with
		 * the other side's iterable key/value */
		zv::Val constUnsealedTypes = call(constArray, PT_LC("getunsealedtypes"));
		PT_FAIL_IF_UNDEF(constUnsealedTypes);
		if (!isNull(constUnsealedTypes)) {
			zend_long constUnsealed = callTrinary(constArray, PT_LC("isunsealed"));
			PT_FAIL_IF_NEG(constUnsealed);
			if (constUnsealed == PT_TRI_YES) {
				zval *unsealedKey = indexOf(constUnsealedTypes.raw(), 0);
				zval *unsealedValue = indexOf(constUnsealedTypes.raw(), 1);
				if (UNEXPECTED(unsealedKey == NULL || unsealedValue == NULL)) {
					zend_throw_error(NULL, "Undefined array key");
					return zv::Val();
				}
				zv::Val otherKeyType = call(otherArray, PT_LC("getiterablekeytype"));
				PT_FAIL_IF_UNDEF(otherKeyType);
				zv::Val newUnsealedKey = intersect2(unsealedKey, otherKeyType.raw());
				PT_FAIL_IF_UNDEF(newUnsealedKey);
				zv::Val otherValueType = call(otherArray, PT_LC("getiterablevaluetype"));
				PT_FAIL_IF_UNDEF(otherValueType);
				zv::Val newUnsealedValue = intersect2(unsealedValue, otherValueType.raw());
				PT_FAIL_IF_UNDEF(newUnsealedValue);
				if (!isInstance(newUnsealedKey.raw(), pt_ce_never_type) && !isInstance(newUnsealedValue.raw(), pt_ce_never_type)) {
					zv::Args args{newUnsealedKey.raw(), newUnsealedValue.raw()};
					zv::Val made = call(newArray.raw(), PT_LC("makeunsealed"), 2, args);
					PT_FAIL_IF_UNDEF(made);
				}
			}
		}
		zv::Val valueTypes = call(constArray, PT_LC("getvaluetypes"));
		PT_FAIL_IF_UNDEF(valueTypes);
		zv::Val keyTypes = call(constArray, PT_LC("getkeytypes"));
		PT_FAIL_IF_UNDEF(keyTypes);
		for (zv::ArrayEntry entry : zv::ArrRef(keyTypes.raw())) {
			zval *keyType = entry.value().raw();
			zv::Val hasOffset = call(otherArray, PT_LC("hasoffsetvaluetype"), 1, keyType);
			PT_FAIL_IF_UNDEF(hasOffset);
			zend_long hasOffsetValue = pt_type_trinary_value(hasOffset.raw());
			PT_FAIL_IF_NEG(hasOffsetValue);
			if (hasOffsetValue == PT_TRI_NO) continue;
			zv::Val otherKeyType = call(otherArray, PT_LC("getiterablekeytype"));
			PT_FAIL_IF_UNDEF(otherKeyType);
			zv::Val newKeyType = intersect2(keyType, otherKeyType.raw());
			PT_FAIL_IF_UNDEF(newKeyType);
			zval *valueType = indexOf(valueTypes.raw(), entry.indexKey());
			if (UNEXPECTED(valueType == NULL)) {
				zend_throw_error(NULL, "Undefined array key " ZEND_LONG_FMT, (zend_long) entry.indexKey());
				return zv::Val();
			}
			zv::Val otherValueType = call(otherArray, PT_LC("getoffsetvaluetype"), 1, keyType);
			PT_FAIL_IF_UNDEF(otherValueType);
			zv::Val newValueType = intersect2(valueType, otherValueType.raw());
			PT_FAIL_IF_UNDEF(newValueType);
			zval index;
			ZVAL_LONG(&index, (zend_long) entry.indexKey());
			int optional = callBool(constArray, PT_LC("isoptionalkey"), 1, &index);
			PT_FAIL_IF_NEG(optional);
			zv::Args args{newKeyType.raw(), newValueType.raw(), bool(optional && hasOffsetValue != PT_TRI_YES)};
			zv::Val set = call(newArray.raw(), PT_LC("setoffsetvaluetype"), 3, args);
			PT_FAIL_IF_UNDEF(set);
		}
		return call(newArray.raw(), PT_LC("getarray"));
	}

	static zv::Val doIntersect(uint32_t argc, zval *argv)
	{
		size_t typesCount = argc;
		if (typesCount == 0) return neverType();

		TypeList types;
		types.reserve(argc);
		for (uint32_t i = 0; i < argc; i++) {
			types.push_back(copy(&argv[i]));
		}
		if (typesCount == 1) return std::move(types[0]);

		/* The comparator only orders UnionTypes relative to each other, so
		 * sorting is a no-op unless there are at least two of them */
		size_t unionTypesCount = 0;
		for (zv::Val &type : types) {
			int implicitNever = isImplicitNever(type.raw());
			PT_FAIL_IF_NEG(implicitNever);
			if (implicitNever) return copy(type.raw());
			if (!isInstance(type.raw(), pt_ce_union_type)) continue;
			unionTypesCount++;
		}

		/* Fast path: the intersection of two plain unions whose members are
		 * all finite, mutually-disjoint values is their identity-keyed set
		 * intersection; restricted to the exact UnionType class */
		if (typesCount == 2 && Z_OBJCE_P(types[0].raw()) == pt_ce_union_type && Z_OBJCE_P(types[1].raw()) == pt_ce_union_type) {
			zv::Val finiteIntersection = intersectFiniteUnions(types[0].raw(), types[1].raw());
			PT_FAIL_IF_UNDEF(finiteIntersection);
			if (!isNull(finiteIntersection)) return finiteIntersection;
		}

		if (unionTypesCount >= 2) {
			if (UNEXPECTED(!usortList(types, compareUnionsForIntersect))) return zv::Val();
		}
		/* transform A & (B | C) to (A & B) | (A & C) */
		for (size_t i = 0; i < types.size(); i++) {
			zval *type = types[i].raw();
			if (!isInstance(type, pt_ce_union_type)) continue;

			zv::Val innerTypesArray = getTypes(type);
			PT_FAIL_IF_UNDEF(innerTypesArray);
			TypeList innerTypes;
			listFrom(innerTypesArray.raw(), innerTypes);
			size_t innerUnionTypesCount = 0;
			for (zv::Val &innerType : innerTypes) {
				if (!isInstance(innerType.raw(), pt_ce_union_type)) continue;
				innerUnionTypesCount++;
				if (innerUnionTypesCount >= 2) break;
			}
			if (innerUnionTypesCount >= 2) {
				if (UNEXPECTED(!usortList(innerTypes, compareUnionsForIntersect))) return zv::Val();
			}
			TypeList topLevelUnionSubTypes;
			for (zv::Val &innerUnionSubType : innerTypes) {
				/* self::intersect($innerUnionSubType, ...$slice1, ...$slice2) */
				std::vector<zval> args;
				args.reserve(types.size());
				zval first;
				ZVAL_COPY_VALUE(&first, innerUnionSubType.raw());
				args.push_back(first);
				for (size_t k = 0; k < types.size(); k++) {
					if (k == i) continue;
					zval other;
					ZVAL_COPY_VALUE(&other, types[k].raw());
					args.push_back(other);
				}
				zv::Val intersected = pt_type_combinator_intersect((uint32_t) args.size(), args.data());
				PT_FAIL_IF_UNDEF(intersected);
				topLevelUnionSubTypes.push_back(std::move(intersected));
			}

			zv::Val unioned = unionOf(topLevelUnionSubTypes);
			PT_FAIL_IF_UNDEF(unioned);
			if (isInstance(unioned.raw(), pt_ce_never_type)) return unioned;

			if (isInstance(type, pt_ce_benevolent_union_type)) {
				unioned = pt_union_to_benevolent(unioned.raw());
				PT_FAIL_IF_UNDEF(unioned);
			}

			if (isInstance(type, pt_ce_template_union_type) || isInstance(type, pt_ce_template_benevolent_union_type)) {
				zv::Val scope = call(type, PT_LC("getscope"));
				PT_FAIL_IF_UNDEF(scope);
				zv::Val name = call(type, PT_LC("getname"));
				PT_FAIL_IF_UNDEF(name);
				zv::Val variance = call(type, PT_LC("getvariance"));
				PT_FAIL_IF_UNDEF(variance);
				zv::Val strategy = call(type, PT_LC("getstrategy"));
				PT_FAIL_IF_UNDEF(strategy);
				zv::Val defaultType = call(type, PT_LC("getdefault"));
				PT_FAIL_IF_UNDEF(defaultType);
				unioned = pt_template_type_factory_create(scope.raw(), name.raw(), unioned.raw(), variance.raw(), strategy.raw(), defaultType.raw());
				PT_FAIL_IF_UNDEF(unioned);
			}

			return unioned;
		}

		TypeList newTypes;
		size_t hasOffsetValueTypeCount = 0;
		typesCount = types.size();
		bool typesNeedSorting = false;
		bool hasPropertyType = false;
		for (size_t i = 0; i < typesCount; i++) {
			zv::Val type = copy(types[i].raw());

			int subtractable = isInstanceMap(type.raw(), PT_CLASS_SUBTRACTABLE_TYPE);
			PT_FAIL_IF_NEG(subtractable);
			if (subtractable || isInstance(type.raw(), pt_ce_constant_array_type)) {
				typesNeedSorting = true;
			}

			if (isInstance(type.raw(), pt_ce_has_property_type)) {
				hasPropertyType = true;
			}

			if (isInstance(type.raw(), pt_ce_intersection_type)) {
				int isTemplate = isInstanceMap(type.raw(), PT_CLASS_TEMPLATE_TYPE);
				PT_FAIL_IF_NEG(isTemplate);
				if (!isTemplate) {
					/* transform A & (B & C) to A & B & C */
					zv::Val inner = getTypes(type.raw());
					PT_FAIL_IF_UNDEF(inner);
					spliceReplace(types, i, inner.raw());
					i--;
					typesCount = types.size();
					continue;
				}
			}
			if (isInstance(type.raw(), pt_ce_has_offset_value_type)) {
				hasOffsetValueTypeCount++;
			} else {
				newTypes.push_back(std::move(type));
			}
		}

		if (hasOffsetValueTypeCount > 32) {
			zv::Val oversized = oversizedArrayType();
			PT_FAIL_IF_UNDEF(oversized);
			newTypes.push_back(std::move(oversized));
			types = std::move(newTypes);
			typesCount = types.size();
		}

		if (typesNeedSorting) {
			/* subtractables with subtracts before those without, to avoid
			 * losing them in the union logic */
			if (UNEXPECTED(!usortList(types, compareSubtractablesForIntersect))) return zv::Val();
		}

		/* Resolve object-shape optional keys that a HasPropertyType asserts
		 * are present before the reduction loop below (see the twin) */
		if (hasPropertyType) {
			for (size_t i = 0; i < typesCount; i++) {
				for (size_t j = i + 1; j < typesCount; j++) {
					int required = requireShapeProperty(types, i, j);
					PT_FAIL_IF_NEG(required);
					if (required) {
						j--;
						typesCount--;
						continue;
					}

					required = requireShapeProperty(types, j, i);
					PT_FAIL_IF_NEG(required);
					if (required) {
						i--;
						typesCount--;
						break;
					}
				}
			}
		}

		/* transform IntegerType & ConstantIntegerType to ConstantIntegerType
		 * transform Child & Parent to Child
		 * transform Object & ~null to Object
		 * transform A & A to A
		 * transform int[] & string to never
		 * transform callable & int to never
		 * transform A & ~A to never
		 * transform int & string to never */
		for (size_t i = 0; i < typesCount; i++) {
			for (size_t j = i + 1; j < typesCount; j++) {
				int jSubtractable = isInstanceMap(types[j].raw(), PT_CLASS_SUBTRACTABLE_TYPE);
				PT_FAIL_IF_NEG(jSubtractable);
				if (jSubtractable) {
					int covers = subtractableCoversInIntersect(types[j].raw(), types[i].raw());
					PT_FAIL_IF_NEG(covers);
					if (covers) {
						zv::Val subtracted = call(types[j].raw(), PT_LC("getsubtractedtype"));
						PT_FAIL_IF_UNDEF(subtracted);
						zv::Val unioned = unionWithSubtractedType(types[i].raw(), subtracted.raw());
						PT_FAIL_IF_UNDEF(unioned);
						types[i] = std::move(unioned);
						types.erase(types.begin() + (ptrdiff_t) j);
						j--;
						typesCount--;
						continue;
					}
				}

				int iSubtractable = isInstanceMap(types[i].raw(), PT_CLASS_SUBTRACTABLE_TYPE);
				PT_FAIL_IF_NEG(iSubtractable);
				if (iSubtractable) {
					int covers = subtractableCoversInIntersect(types[i].raw(), types[j].raw());
					PT_FAIL_IF_NEG(covers);
					if (covers) {
						zv::Val subtracted = call(types[i].raw(), PT_LC("getsubtractedtype"));
						PT_FAIL_IF_UNDEF(subtracted);
						zv::Val unioned = unionWithSubtractedType(types[j].raw(), subtracted.raw());
						PT_FAIL_IF_UNDEF(unioned);
						types[j] = std::move(unioned);
						types.erase(types.begin() + (ptrdiff_t) i);
						i--;
						typesCount--;
						break;
					}
				}

				if (isInstance(types[i].raw(), pt_ce_integer_range_type)) {
					zv::Val intersectionType = call(types[i].raw(), PT_LC("tryintersect"), 1, types[j].raw());
					PT_FAIL_IF_UNDEF(intersectionType);
					if (!isNull(intersectionType)) {
						types[j] = std::move(intersectionType);
						types.erase(types.begin() + (ptrdiff_t) i);
						i--;
						typesCount--;
						break;
					}
				}

				zv::Val isSuperTypeA = superTypeResult(types[j].raw(), types[i].raw());
				PT_FAIL_IF_UNDEF(isSuperTypeA);
				zend_long isSuperTypeAValue = pt_type_result_trinary(isSuperTypeA.raw());
				PT_FAIL_IF_NEG(isSuperTypeAValue);

				if (isSuperTypeAValue == PT_TRI_YES) {
					types.erase(types.begin() + (ptrdiff_t) j);
					j--;
					typesCount--;
					continue;
				}

				zv::Val isSuperTypeB = superTypeResult(types[i].raw(), types[j].raw());
				PT_FAIL_IF_UNDEF(isSuperTypeB);
				zend_long isSuperTypeBValue = pt_type_result_trinary(isSuperTypeB.raw());
				PT_FAIL_IF_NEG(isSuperTypeBValue);

				if (isSuperTypeBValue == PT_TRI_MAYBE) {
					int step = reduceMaybePair(types, i, j, typesCount, isSuperTypeA.raw(), isSuperTypeB.raw());
					PT_FAIL_IF_NEG(step);
					if (step == REDUCE_RETURN) return std::move(reduceReturnValue);
					if (step == REDUCE_CONTINUE_INNER) continue;
					if (step == REDUCE_CONTINUE_OUTER) break;
					continue;
				}

				if (isSuperTypeBValue == PT_TRI_YES) {
					types.erase(types.begin() + (ptrdiff_t) i);
					i--;
					typesCount--;
					break;
				}

				if (isSuperTypeAValue == PT_TRI_NO) {
					zv::Val reasons = call(isSuperTypeA.raw(), PT_LC("getreasons"));
					PT_FAIL_IF_UNDEF(reasons);
					zval *first = indexOf(reasons.raw(), 0);
					zval nullReason;
					ZVAL_NULL(&nullReason);
					return neverTypeWithReason(first != NULL ? first : &nullReason);
				}
			}
		}

		if (typesCount == 1) return std::move(types[0]);

		TypeList accessoryBaseTypes;
		bool allAccessories = true;
		for (zv::Val &type : types) {
			int isAccessory = isInstanceMap(type.raw(), PT_CLASS_ACCESSORY_TYPE);
			PT_FAIL_IF_NEG(isAccessory);
			if (!isAccessory) {
				allAccessories = false;
				break;
			}
			/* Accessory types share their default base type; adding the same
			 * base type again narrows nothing, but the intersect() below
			 * distributes `A & (B | C)` one union at a time — n copies of
			 * `array|ArrayAccess` would cost 2^n recursive calls */
			zv::Val baseType = call(type.raw(), PT_LC("getdefaultbasetype"));
			PT_FAIL_IF_UNDEF(baseType);
			bool added = false;
			for (zv::Val &addedBaseType : accessoryBaseTypes) {
				int equal = typeEquals(addedBaseType.raw(), baseType.raw());
				PT_FAIL_IF_NEG(equal);
				if (equal) {
					added = true;
					break;
				}
			}
			if (added) continue;
			accessoryBaseTypes.push_back(std::move(baseType));
		}
		if (allAccessories) {
			/* Accessory types never stand alone — supply the base type they refine */
			zv::Val base = intersectOf(accessoryBaseTypes);
			PT_FAIL_IF_UNDEF(base);
			return intersectWith(base.raw(), types);
		}

		return intersectionType(listOf(types));
	}

	/* the ObjectShapeType & HasPropertyType collapse of doIntersect() for
	 * the shape at $shapeIndex and the accessory at $propertyIndex: the
	 * shape's property made required, the accessory removed; 1 when it
	 * applied, 0 otherwise; -1 = pending exception */
	static int requireShapeProperty(TypeList &types, size_t shapeIndex, size_t propertyIndex)
	{
		if (!isInstance(types[shapeIndex].raw(), pt_ce_object_shape_type) || !isInstance(types[propertyIndex].raw(), pt_ce_has_property_type)) return 0;
		zv::Val propertyName = call(types[propertyIndex].raw(), PT_LC("getpropertyname"));
		if (UNEXPECTED(propertyName.isUndef())) return -1;
		zend_long has = callTrinary(types[shapeIndex].raw(), PT_LC("hasinstanceproperty"), 1, propertyName.raw());
		if (UNEXPECTED(has < 0)) return -1;
		if (has == PT_TRI_NO) return 0;
		zv::Val required = call(types[shapeIndex].raw(), PT_LC("makepropertyrequired"), 1, propertyName.raw());
		if (UNEXPECTED(required.isUndef())) return -1;
		types[shapeIndex] = std::move(required);
		types.erase(types.begin() + (ptrdiff_t) propertyIndex);
		return 1;
	}

	/* what the maybe-arm of doIntersect()'s reduction did with the pair */
	enum ReduceStep
	{
		REDUCE_NEXT = 0,          /* nothing applied: the twin's trailing `continue` */
		REDUCE_CONTINUE_INNER = 1, /* `continue` after $types[$j] was spliced away */
		REDUCE_CONTINUE_OUTER = 2, /* `continue 2` after $types[$i] was spliced away */
		REDUCE_RETURN = 3,         /* an early return, the value in reduceReturnValue */
	};

	static zv::Val reduceReturnValue;

	/* the maybe-arm of doIntersect()'s reduction over $types[$i] and
	 * $types[$j] (isSuperTypeB maybe): the constant array / accessory /
	 * array / generic-class-string combinations; the pair's indexes and
	 * count updated as the twin's array_splice() calls do; -1 = pending
	 * exception */
	static int reduceMaybePair(TypeList &types, size_t &i, size_t &j, size_t &typesCount, zval *isSuperTypeA, zval *isSuperTypeB)
	{
		zval *ti = types[i].raw();
		zval *tj = types[j].raw();
		bool iConstantArray = isInstance(ti, pt_ce_constant_array_type);
		bool jConstantArray = isInstance(tj, pt_ce_constant_array_type);

		if (iConstantArray && isInstance(tj, pt_ce_has_offset_type)) {
			zv::Val offsetType = pt_has_offset_type_get_offset_type(Z_OBJ_P(tj));
			if (UNEXPECTED(offsetType.isUndef())) return -1;
			zv::Val required = call(ti, PT_LC("makeoffsetrequired"), 1, offsetType.raw());
			if (UNEXPECTED(required.isUndef())) return -1;
			types[i] = std::move(required);
			types.erase(types.begin() + (ptrdiff_t) j);
			j--;
			typesCount--;
			return REDUCE_CONTINUE_INNER;
		}

		if (jConstantArray && isInstance(ti, pt_ce_has_offset_type)) {
			zv::Val offsetType = pt_has_offset_type_get_offset_type(Z_OBJ_P(ti));
			if (UNEXPECTED(offsetType.isUndef())) return -1;
			zv::Val required = call(tj, PT_LC("makeoffsetrequired"), 1, offsetType.raw());
			if (UNEXPECTED(required.isUndef())) return -1;
			types[j] = std::move(required);
			types.erase(types.begin() + (ptrdiff_t) i);
			i--;
			typesCount--;
			return REDUCE_CONTINUE_OUTER;
		}

		if (iConstantArray && isInstance(tj, pt_ce_accessory_array_list_type)) {
			zv::Val list = call(ti, PT_LC("makelist"));
			if (UNEXPECTED(list.isUndef())) return -1;
			types[i] = std::move(list);
			types.erase(types.begin() + (ptrdiff_t) j);
			j--;
			typesCount--;
			return REDUCE_CONTINUE_INNER;
		}

		if (jConstantArray && isInstance(ti, pt_ce_accessory_array_list_type)) {
			zv::Val list = call(tj, PT_LC("makelist"));
			if (UNEXPECTED(list.isUndef())) return -1;
			types[j] = std::move(list);
			types.erase(types.begin() + (ptrdiff_t) i);
			i--;
			typesCount--;
			return REDUCE_CONTINUE_OUTER;
		}

		if (iConstantArray && isInstance(tj, pt_ce_non_empty_array_type)) {
			int applies = singleOptionalKeyToRequire(ti);
			if (UNEXPECTED(applies < 0)) return -1;
			if (applies) {
				zv::Val required = makeFirstOffsetRequired(ti);
				if (UNEXPECTED(required.isUndef())) return -1;
				types[i] = std::move(required);
				types.erase(types.begin() + (ptrdiff_t) j);
				j--;
				typesCount--;
				return REDUCE_CONTINUE_INNER;
			}
		}

		if (jConstantArray && isInstance(ti, pt_ce_non_empty_array_type)) {
			int applies = singleOptionalKeyToRequire(tj);
			if (UNEXPECTED(applies < 0)) return -1;
			if (applies) {
				zv::Val required = makeFirstOffsetRequired(tj);
				if (UNEXPECTED(required.isUndef())) return -1;
				types[j] = std::move(required);
				types.erase(types.begin() + (ptrdiff_t) i);
				i--;
				typesCount--;
				return REDUCE_CONTINUE_OUTER;
			}
		}

		if (iConstantArray && isInstance(tj, pt_ce_has_offset_value_type)) {
			zv::Val newValueType;
			zv::Val updated = setOffsetValueFromAccessory(ti, tj, newValueType);
			if (UNEXPECTED(updated.isUndef())) return -1;
			if (isNull(updated)) {
				reduceReturnValue = std::move(newValueType);
				return REDUCE_RETURN;
			}
			types[i] = std::move(updated);
			types.erase(types.begin() + (ptrdiff_t) j);
			j--;
			typesCount--;
			return REDUCE_CONTINUE_INNER;
		}

		if (jConstantArray && isInstance(ti, pt_ce_has_offset_value_type)) {
			zv::Val newValueType;
			zv::Val updated = setOffsetValueFromAccessory(tj, ti, newValueType);
			if (UNEXPECTED(updated.isUndef())) return -1;
			if (isNull(updated)) {
				reduceReturnValue = std::move(newValueType);
				return REDUCE_RETURN;
			}
			types[j] = std::move(updated);
			types.erase(types.begin() + (ptrdiff_t) i);
			i--;
			typesCount--;
			return REDUCE_CONTINUE_OUTER;
		}

		if (isInstance(ti, pt_ce_oversized_array_type) && isInstance(tj, pt_ce_has_offset_value_type)) {
			types.erase(types.begin() + (ptrdiff_t) j);
			j--;
			typesCount--;
			return REDUCE_CONTINUE_INNER;
		}

		if (isInstance(tj, pt_ce_oversized_array_type) && isInstance(ti, pt_ce_has_offset_value_type)) {
			types.erase(types.begin() + (ptrdiff_t) i);
			i--;
			typesCount--;
			return REDUCE_CONTINUE_OUTER;
		}

		bool iArray = isInstance(ti, pt_ce_array_type);
		bool jArray = isInstance(tj, pt_ce_array_type);
		bool constArrayIsI = iConstantArray && (jArray || jConstantArray);
		bool constArrayIsJ = jConstantArray && (iArray || iConstantArray);
		if (constArrayIsI || constArrayIsJ) {
			zval *constArray = constArrayIsI ? ti : tj;
			zval *otherArray = constArrayIsI ? tj : ti;

			zv::Val newArrayType = intersectConstantArrayWith(constArray, otherArray, isSuperTypeA, isSuperTypeB);
			if (UNEXPECTED(newArrayType.isUndef())) return -1;
			if (isInstance(newArrayType.raw(), pt_ce_never_type)) {
				reduceReturnValue = std::move(newArrayType);
				return REDUCE_RETURN;
			}

			if (constArrayIsI) {
				types[i] = std::move(newArrayType);
				types.erase(types.begin() + (ptrdiff_t) j);
			} else {
				types[j] = std::move(newArrayType);
				types.erase(types.begin() + (ptrdiff_t) i);
				i--;
			}
			typesCount--;
			return REDUCE_CONTINUE_OUTER;
		}

		bool iIterable = isInstance(ti, pt_ce_iterable_type);
		bool jIterable = isInstance(tj, pt_ce_iterable_type);
		if ((iArray || iConstantArray || iIterable) && (jArray || jConstantArray || jIterable)) {
			zv::Val iKeyType = call(ti, PT_LC("getiterablekeytype"));
			if (UNEXPECTED(iKeyType.isUndef())) return -1;
			zv::Val jKeyType = call(tj, PT_LC("getkeytype"));
			if (UNEXPECTED(jKeyType.isUndef())) return -1;
			zv::Val keyType = intersect2(iKeyType.raw(), jKeyType.raw());
			if (UNEXPECTED(keyType.isUndef())) return -1;
			zv::Val iItemType = call(ti, PT_LC("getitemtype"));
			if (UNEXPECTED(iItemType.isUndef())) return -1;
			zv::Val jItemType = call(tj, PT_LC("getitemtype"));
			if (UNEXPECTED(jItemType.isUndef())) return -1;
			zv::Val itemType = intersect2(iItemType.raw(), jItemType.raw());
			if (UNEXPECTED(itemType.isUndef())) return -1;
			zv::Val merged = iIterable && jIterable ? iterableType(keyType.raw(), itemType.raw()) : arrayType(keyType.raw(), itemType.raw());
			if (UNEXPECTED(merged.isUndef())) return -1;
			types[j] = std::move(merged);
			types.erase(types.begin() + (ptrdiff_t) i);
			i--;
			typesCount--;
			return REDUCE_CONTINUE_OUTER;
		}

		if (isInstance(ti, pt_ce_generic_class_string_type) && isInstance(tj, pt_ce_generic_class_string_type)) {
			zv::Val iGeneric = call(ti, PT_LC("getgenerictype"));
			if (UNEXPECTED(iGeneric.isUndef())) return -1;
			zv::Val jGeneric = call(tj, PT_LC("getgenerictype"));
			if (UNEXPECTED(jGeneric.isUndef())) return -1;
			zv::Val genericType = intersect2(iGeneric.raw(), jGeneric.raw());
			if (UNEXPECTED(genericType.isUndef())) return -1;
			zv::Val classString = pt_type_new_ce(pt_ce_generic_class_string_type, 1, genericType.raw());
			if (UNEXPECTED(classString.isUndef())) return -1;
			types[i] = std::move(classString);
			types.erase(types.begin() + (ptrdiff_t) j);
			j--;
			typesCount--;
			return REDUCE_CONTINUE_INNER;
		}

		if (iArray && Z_OBJCE_P(ti) == pt_ce_array_type && isInstance(tj, pt_ce_accessory_array_list_type)) {
			zv::Val jKeyType = call(tj, PT_LC("getiterablekeytype"));
			if (UNEXPECTED(jKeyType.isUndef())) return -1;
			zv::Val iKeyType = call(ti, PT_LC("getiterablekeytype"));
			if (UNEXPECTED(iKeyType.isUndef())) return -1;
			int covers = isSuperTypeYes(jKeyType.raw(), iKeyType.raw());
			if (UNEXPECTED(covers < 0)) return -1;
			if (!covers) {
				zv::Val keyType = intersect2(iKeyType.raw(), jKeyType.raw());
				if (UNEXPECTED(keyType.isUndef())) return -1;
				if (isInstance(keyType.raw(), pt_ce_never_type)) {
					reduceReturnValue = std::move(keyType);
					return REDUCE_RETURN;
				}
				zv::Val itemType = call(ti, PT_LC("getitemtype"));
				if (UNEXPECTED(itemType.isUndef())) return -1;
				zv::Val narrowed = arrayType(keyType.raw(), itemType.raw());
				if (UNEXPECTED(narrowed.isUndef())) return -1;
				types[i] = std::move(narrowed);
				return REDUCE_NEXT;
			}
		}

		return REDUCE_NEXT;
	}

	/* private static intersectDefiniteConstantArrays(ConstantArrayType $a, ConstantArrayType $b): Type */
	static zv::Val intersectDefiniteConstantArrays(zval *a, zval *b)
	{
		zend_long aUnsealed = callTrinary(a, PT_LC("isunsealed"));
		PT_FAIL_IF_NEG(aUnsealed);
		zend_long bUnsealed = callTrinary(b, PT_LC("isunsealed"));
		PT_FAIL_IF_NEG(bUnsealed);
		bool aSealed = aUnsealed == PT_TRI_NO;
		bool bSealed = bUnsealed == PT_TRI_NO;
		zv::Val aUnsealedTypes = call(a, PT_LC("getunsealedtypes"));
		PT_FAIL_IF_UNDEF(aUnsealedTypes);
		zv::Val bUnsealedTypes = call(b, PT_LC("getunsealedtypes"));
		PT_FAIL_IF_UNDEF(bUnsealedTypes);
		bool bothUnsealed = !aSealed && !bSealed && !isNull(aUnsealedTypes) && !isNull(bUnsealedTypes);

		zv::Val aKeyTypes = call(a, PT_LC("getkeytypes"));
		PT_FAIL_IF_UNDEF(aKeyTypes);
		zv::Val bKeyTypes = call(b, PT_LC("getkeytypes"));
		PT_FAIL_IF_UNDEF(bKeyTypes);
		zv::Val aValueTypes = call(a, PT_LC("getvaluetypes"));
		PT_FAIL_IF_UNDEF(aValueTypes);
		zv::Val bValueTypes = call(b, PT_LC("getvaluetypes"));
		PT_FAIL_IF_UNDEF(bValueTypes);

		/* $aKeyByValue[$keyType->getValue()] = $k, $bKeyByValue likewise */
		zv::Arr aKeyByValue = zv::Arr::create(0);
		zv::Arr bKeyByValue = zv::Arr::create(0);
		for (int side = 0; side < 2; side++) {
			zval *keyTypes = side == 0 ? aKeyTypes.raw() : bKeyTypes.raw();
			zv::Arr &keyByValue = side == 0 ? aKeyByValue : bKeyByValue;
			for (zv::ArrayEntry entry : zv::ArrRef(keyTypes)) {
				zv::Val keyValue = call(entry.value().raw(), PT_LC("getvalue"));
				PT_FAIL_IF_UNDEF(keyValue);
				zval index;
				ZVAL_LONG(&index, (zend_long) entry.indexKey());
				if (UNEXPECTED(!symtableSet(keyByValue.table(), keyValue.raw(), &index))) return zv::Val();
			}
		}

		if (aSealed && bSealed) {
			for (int side = 0; side < 2; side++) {
				zval *array = side == 0 ? a : b;
				zv::Arr &own = side == 0 ? aKeyByValue : bKeyByValue;
				zv::Arr &other = side == 0 ? bKeyByValue : aKeyByValue;
				for (zv::ArrayEntry entry : zv::ArrRef(own.raw())) {
					int optional = callBool(array, PT_LC("isoptionalkey"), 1, entry.value().raw());
					PT_FAIL_IF_NEG(optional);
					if (!optional && !pt_ht_exists(other.table(), entry.stringKeyOrNull(), entry.indexKey())) return neverType();
				}
			}
		}

		zv::Val newArray = pt_type_call_static(PT_CLASS_CONSTANT_ARRAY_TYPE_BUILDER, PT_LC("createempty"), 0, NULL);
		PT_FAIL_IF_UNDEF(newArray);

		if (bothUnsealed) {
			zval *aKey = indexOf(aUnsealedTypes.raw(), 0);
			zval *aValue = indexOf(aUnsealedTypes.raw(), 1);
			zval *bKey = indexOf(bUnsealedTypes.raw(), 0);
			zval *bValue = indexOf(bUnsealedTypes.raw(), 1);
			if (UNEXPECTED(aKey == NULL || aValue == NULL || bKey == NULL || bValue == NULL)) {
				zend_throw_error(NULL, "Undefined array key");
				return zv::Val();
			}
			zv::Val unsealedKey = intersect2(aKey, bKey);
			PT_FAIL_IF_UNDEF(unsealedKey);
			zv::Val unsealedValue = intersect2(aValue, bValue);
			PT_FAIL_IF_UNDEF(unsealedValue);
			if (isInstance(unsealedKey.raw(), pt_ce_never_type) || isInstance(unsealedValue.raw(), pt_ce_never_type)) return neverType();
			zv::Args args{unsealedKey.raw(), unsealedValue.raw()};
			zv::Val made = call(newArray.raw(), PT_LC("makeunsealed"), 2, args);
			PT_FAIL_IF_UNDEF(made);
		} else {
			zv::Val never = neverType(true);
			PT_FAIL_IF_UNDEF(never);
			zv::Args args{never.raw(), never.raw()};
			zv::Val made = call(newArray.raw(), PT_LC("makeunsealed"), 2, args);
			PT_FAIL_IF_UNDEF(made);
		}

		/* $keysToProcess: a's keys in order, then b's keys a lacks, each
		 * with its index on either side (-1 for none) */
		struct KeyPair
		{
			zend_long aIdx;
			zend_long bIdx;
		};
		std::vector<KeyPair> keysToProcess;
		for (zv::ArrayEntry entry : zv::ArrRef(aKeyByValue.raw())) {
			zval *bIdx = pt_ht_find(bKeyByValue.table(), entry.stringKeyOrNull(), entry.indexKey());
			keysToProcess.push_back({ Z_LVAL_P(entry.value().raw()), bIdx != NULL ? Z_LVAL_P(bIdx) : -1 });
		}
		for (zv::ArrayEntry entry : zv::ArrRef(bKeyByValue.raw())) {
			if (pt_ht_exists(aKeyByValue.table(), entry.stringKeyOrNull(), entry.indexKey())) continue;

			keysToProcess.push_back({ -1, Z_LVAL_P(entry.value().raw()) });
		}

		for (const KeyPair &pair : keysToProcess) {
			zval *keyType;
			zv::Val value;
			bool optional;
			if (pair.aIdx >= 0 && pair.bIdx >= 0) {
				keyType = indexOf(aKeyTypes.raw(), (zend_ulong) pair.aIdx);
				zval *aValue = indexOf(aValueTypes.raw(), (zend_ulong) pair.aIdx);
				zval *bValue = indexOf(bValueTypes.raw(), (zend_ulong) pair.bIdx);
				if (UNEXPECTED(keyType == NULL || aValue == NULL || bValue == NULL)) {
					zend_throw_error(NULL, "Undefined array key");
					return zv::Val();
				}
				value = intersect2(aValue, bValue);
				PT_FAIL_IF_UNDEF(value);
				int aOptional = isOptionalKey(a, pair.aIdx);
				PT_FAIL_IF_NEG(aOptional);
				optional = aOptional != 0;
				if (optional) {
					int bOptional = isOptionalKey(b, pair.bIdx);
					PT_FAIL_IF_NEG(bOptional);
					optional = bOptional != 0;
				}
			} else if (pair.aIdx >= 0) {
				keyType = indexOf(aKeyTypes.raw(), (zend_ulong) pair.aIdx);
				zval *aValue = indexOf(aValueTypes.raw(), (zend_ulong) pair.aIdx);
				if (UNEXPECTED(keyType == NULL || aValue == NULL)) {
					zend_throw_error(NULL, "Undefined array key");
					return zv::Val();
				}
				zv::Val bValue = resolveOtherValue(b, keyType);
				PT_FAIL_IF_UNDEF(bValue);
				int aOptional = isOptionalKey(a, pair.aIdx);
				PT_FAIL_IF_NEG(aOptional);
				if (isNull(bValue)) {
					if (aOptional) continue;
					return neverType();
				}
				value = intersect2(aValue, bValue.raw());
				PT_FAIL_IF_UNDEF(value);
				optional = aOptional != 0;
			} else {
				keyType = indexOf(bKeyTypes.raw(), (zend_ulong) pair.bIdx);
				zval *bValue = indexOf(bValueTypes.raw(), (zend_ulong) pair.bIdx);
				if (UNEXPECTED(keyType == NULL || bValue == NULL)) {
					zend_throw_error(NULL, "Undefined array key");
					return zv::Val();
				}
				zv::Val aValue = resolveOtherValue(a, keyType);
				PT_FAIL_IF_UNDEF(aValue);
				int bOptional = isOptionalKey(b, pair.bIdx);
				PT_FAIL_IF_NEG(bOptional);
				if (isNull(aValue)) {
					if (bOptional) continue;
					return neverType();
				}
				value = intersect2(aValue.raw(), bValue);
				PT_FAIL_IF_UNDEF(value);
				optional = bOptional != 0;
			}

			if (isInstance(value.raw(), pt_ce_never_type)) {
				if (optional) continue;
				return neverType();
			}
			zv::Args args{keyType, value.raw(), optional};
			zv::Val set = call(newArray.raw(), PT_LC("setoffsetvaluetype"), 3, args);
			PT_FAIL_IF_UNDEF(set);
		}

		return call(newArray.raw(), PT_LC("getarray"));
	}

	/* $array->isOptionalKey($index); -1 = pending exception */
	static int isOptionalKey(zval *array, zend_long index)
	{
		zval indexValue;
		ZVAL_LONG(&indexValue, index);
		return callBool(array, PT_LC("isoptionalkey"), 1, &indexValue);
	}

	/* the $resolveOtherValue closure of intersectDefiniteConstantArrays():
	 * the other array's value at the key, its unsealed value when the key
	 * falls in real extras, null otherwise (IS_NULL) */
	static zv::Val resolveOtherValue(zval *other, zval *keyType)
	{
		zend_long has = callTrinary(other, PT_LC("hasoffsetvaluetype"), 1, keyType);
		PT_FAIL_IF_NEG(has);
		if (has == PT_TRI_YES) return call(other, PT_LC("getoffsetvaluetype"), 1, keyType);
		zv::Val otherUnsealed = call(other, PT_LC("getunsealedtypes"));
		PT_FAIL_IF_UNDEF(otherUnsealed);
		if (isNull(otherUnsealed)) return zv::Val::null();
		zval *unsealedKey = indexOf(otherUnsealed.raw(), 0);
		zval *unsealedValue = indexOf(otherUnsealed.raw(), 1);
		if (UNEXPECTED(unsealedKey == NULL || unsealedValue == NULL)) {
			zend_throw_error(NULL, "Undefined array key");
			return zv::Val();
		}
		int explicitNever = isExplicitNever(unsealedKey);
		PT_FAIL_IF_NEG(explicitNever);
		if (explicitNever) return zv::Val::null();
		zend_long covers = callResultTrinary(unsealedKey, PT_LC("issupertypeof"), 1, keyType);
		PT_FAIL_IF_NEG(covers);
		if (covers == PT_TRI_NO) return zv::Val::null();
		return copy(unsealedValue);
	}

	/* private static mergeIntersectionsForUnion(IntersectionType $a, IntersectionType $b): ?Type
	 * (IS_NULL for null) — two intersections of the same structure differing
	 * in HasOffsetValueType value types (matched by offset key) merged */
	static zv::Val mergeIntersectionsForUnion(zval *a, zval *b)
	{
		zv::Val aTypesArray = getTypes(a);
		PT_FAIL_IF_UNDEF(aTypesArray);
		zv::Val bTypesArray = getTypes(b);
		PT_FAIL_IF_UNDEF(bTypesArray);
		TypeList aTypes;
		listFrom(aTypesArray.raw(), aTypes);
		TypeList bTypes;
		listFrom(bTypesArray.raw(), bTypes);

		if (aTypes.size() != bTypes.size()) return zv::Val::null();

		TypeList mergedTypes;
		bool hasDifference = false;
		std::vector<bool> bUsed(bTypes.size(), false);

		for (zv::Val &aType : aTypes) {
			bool matched = false;
			for (size_t bIdx = 0; bIdx < bTypes.size(); bIdx++) {
				if (bUsed[bIdx]) continue;
				zval *bType = bTypes[bIdx].raw();

				int equal = typeEquals(aType.raw(), bType);
				PT_FAIL_IF_NEG(equal);
				if (equal) {
					mergedTypes.push_back(copy(aType.raw()));
					bUsed[bIdx] = true;
					matched = true;
					break;
				}

				/* HasOffsetValueType: merge value types when offset keys match */
				if (isInstance(aType.raw(), pt_ce_has_offset_value_type) && isInstance(bType, pt_ce_has_offset_value_type)) {
					zv::Val aOffset = pt_has_offset_value_type_get_offset_type(Z_OBJ_P(aType.raw()));
					PT_FAIL_IF_UNDEF(aOffset);
					zv::Val bOffset = pt_has_offset_value_type_get_offset_type(Z_OBJ_P(bType));
					PT_FAIL_IF_UNDEF(bOffset);
					int offsetsEqual = typeEquals(aOffset.raw(), bOffset.raw());
					PT_FAIL_IF_NEG(offsetsEqual);
					if (offsetsEqual) {
						zv::Val aValue = pt_has_offset_value_type_get_value_type(Z_OBJ_P(aType.raw()));
						PT_FAIL_IF_UNDEF(aValue);
						zv::Val bValue = pt_has_offset_value_type_get_value_type(Z_OBJ_P(bType));
						PT_FAIL_IF_UNDEF(bValue);
						zv::Val valueType = union2(aValue.raw(), bValue.raw());
						PT_FAIL_IF_UNDEF(valueType);
						zv::Val merged = hasOffsetValueType(aOffset.raw(), valueType.raw());
						PT_FAIL_IF_UNDEF(merged);
						mergedTypes.push_back(std::move(merged));
						hasDifference = true;
						bUsed[bIdx] = true;
						matched = true;
						break;
					}
				}

				/* HasOffsetType, HasMethodType, HasPropertyType: only equal values match */
			}
			if (!matched) return zv::Val::null();
		}

		if (!hasDifference) return zv::Val::null();

		zv::Val result = std::move(mergedTypes[0]);
		for (size_t i = 1; i < mergedTypes.size(); i++) {
			result = intersect2(result.raw(), mergedTypes[i].raw());
			PT_FAIL_IF_UNDEF(result);
		}
		return result;
	}

	static zv::Val removeFalsey(zval *type)
	{
		zv::Val falsey = pt_type_call_static(PT_CLASS_STATIC_TYPE_FACTORY, PT_LC("falsey"), 0, NULL);
		PT_FAIL_IF_UNDEF(falsey);
		return remove(type, falsey.raw());
	}

	static zv::Val removeTruthy(zval *type)
	{
		zv::Val truthy = pt_type_call_static(PT_CLASS_STATIC_TYPE_FACTORY, PT_LC("truthy"), 0, NULL);
		PT_FAIL_IF_UNDEF(truthy);
		return remove(type, truthy.raw());
	}

	/* }}} */
};

zend_class_entry *TypeCombinator::cacheEnabledCe = nullptr;
uint32_t TypeCombinator::cacheEnabledOffset = 0;
zv::Val TypeCombinator::reduceReturnValue;

} // namespace phpstanturbo

using phpstanturbo::TypeCombinator;

/* {{{ the exported entry points (support.h) */

/* the twins' `Type` parameters: every argument an object (the class check
 * is the engine's on the PHP-visible methods; the native callers pass
 * Types); false with a TypeError pending otherwise */
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

zv::Val pt_type_combinator_union(uint32_t argc, zval *argv)
{
	if (UNEXPECTED(!requireObjects(argc, argv))) return zv::Val();
	return TypeCombinator::union_(argc, argv);
}

zv::Val pt_type_combinator_intersect(uint32_t argc, zval *argv)
{
	if (UNEXPECTED(!requireObjects(argc, argv))) return zv::Val();
	return TypeCombinator::intersect(argc, argv);
}

zv::Val pt_type_combinator_remove(zval *fromType, zval *typeToRemove)
{
	if (UNEXPECTED(Z_TYPE_P(fromType) != IS_OBJECT || Z_TYPE_P(typeToRemove) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: TypeCombinator::remove() expects Type objects");
		return zv::Val();
	}
	return TypeCombinator::remove(fromType, typeToRemove);
}

zv::Val pt_type_combinator_remove_null(zval *type)
{
	if (UNEXPECTED(!requireObjects(1, type))) return zv::Val();
	return TypeCombinator::removeNull(type);
}

zv::Val pt_type_combinator_add_null(zval *type)
{
	if (UNEXPECTED(!requireObjects(1, type))) return zv::Val();
	return TypeCombinator::addNull(type);
}

bool pt_type_combinator_contains_null(zval *type, bool &out)
{
	if (UNEXPECTED(!requireObjects(1, type))) return false;
	int contains = TypeCombinator::containsNull(type);
	if (UNEXPECTED(contains < 0)) return false;
	out = contains != 0;
	return true;
}

zv::Val pt_type_combinator_do_union(uint32_t argc, zval *argv)
{
	if (UNEXPECTED(!requireObjects(argc, argv))) return zv::Val();
	return TypeCombinator::doUnion(argc, argv);
}

zv::Val pt_type_combinator_do_intersect(uint32_t argc, zval *argv)
{
	if (UNEXPECTED(!requireObjects(argc, argv))) return zv::Val();
	return TypeCombinator::doIntersect(argc, argv);
}

zv::Val pt_type_combinator_do_remove(zval *fromType, zval *typeToRemove)
{
	if (UNEXPECTED(Z_TYPE_P(fromType) != IS_OBJECT || Z_TYPE_P(typeToRemove) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: TypeCombinator::doRemove() expects Type objects");
		return zv::Val();
	}
	return TypeCombinator::doRemove(fromType, typeToRemove);
}

static bool lcnameIs(const char *lcname, size_t len, const char *literal, size_t literalLen)
{
	return len == literalLen && memcmp(lcname, literal, len) == 0;
}

/* a `bool` result as a Val; UNDEF = pending exception */
static zv::Val boolResult(bool ok, bool value)
{
	if (UNEXPECTED(!ok)) return zv::Val();
	return zv::Val::boolean(value);
}

zv::Val pt_type_combinator_call(const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	if (lcnameIs(lcname, len, PT_LC("union"))) return pt_type_combinator_union(argc, argv);
	if (lcnameIs(lcname, len, PT_LC("intersect"))) return pt_type_combinator_intersect(argc, argv);
	if (argc == 2 && lcnameIs(lcname, len, PT_LC("remove"))) return pt_type_combinator_remove(&argv[0], &argv[1]);
	if (argc == 1) {
		if (lcnameIs(lcname, len, PT_LC("removenull"))) return pt_type_combinator_remove_null(&argv[0]);
		if (lcnameIs(lcname, len, PT_LC("addnull"))) return pt_type_combinator_add_null(&argv[0]);
		if (lcnameIs(lcname, len, PT_LC("containsnull"))) {
			bool contains = false;
			return boolResult(pt_type_combinator_contains_null(&argv[0], contains), contains);
		}
		if (lcnameIs(lcname, len, PT_LC("removefalsey"))) return requireObjects(1, argv) ? TypeCombinator::removeFalsey(&argv[0]) : zv::Val();
		if (lcnameIs(lcname, len, PT_LC("removetruthy"))) return requireObjects(1, argv) ? TypeCombinator::removeTruthy(&argv[0]) : zv::Val();
	}
	if (UNEXPECTED(pt_ce_type_combinator == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: TypeCombinator::%s() called before the shadowing classes were activated", lcname);
		return zv::Val();
	}
	return pt_type_call_static_ce(pt_ce_type_combinator, lcname, len, argc, argv);
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

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

void pt_register_type_combinator()
{

	reg::Class cls("PHPStan\\Type\\TypeCombinator");
	ptdecl::TypeCombinator::declareClass(cls);
	cls.privateStaticTypedPropertyDefaultNull("cacheEnabled", MAY_BE_BOOL);

	cls.method(sigs::clearCache, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		if (UNEXPECTED(!TypeCombinator::clearCache())) RETURN_THROWS();
	});
	cls.method(sigs::addNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(TypeCombinator::addNull(type));
	});
	cls.method(sigs::remove, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *fromType, *typeToRemove;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, fromType, typeToRemove)) RETURN_THROWS();
		PT_RETURN_VAL(TypeCombinator::remove(fromType, typeToRemove));
	});
	cls.method(sigs::doRemove, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *fromType, *typeToRemove;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, fromType, typeToRemove)) RETURN_THROWS();
		PT_RETURN_VAL(TypeCombinator::doRemove(fromType, typeToRemove));
	});
	cls.method(sigs::removeNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(TypeCombinator::removeNull(type));
	});
	cls.method(sigs::containsNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		int contains = TypeCombinator::containsNull(type);
		if (UNEXPECTED(contains < 0)) RETURN_THROWS();
		RETURN_BOOL(contains);
	});
	cls.method(sigs::union_, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		uint32_t count;
		ZEND_PARSE_PARAMETERS_START(0, -1)
			Z_PARAM_VARIADIC('*', types, count)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TypeCombinator::union_(count, types));
	});
	cls.method(sigs::doUnion, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		uint32_t count;
		ZEND_PARSE_PARAMETERS_START(0, -1)
			Z_PARAM_VARIADIC('*', types, count)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TypeCombinator::doUnion(count, types));
	});
	cls.method(sigs::countConstantArrayValueTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		if (!zp::parse<zp::Arr>(execute_data, types)) RETURN_THROWS();
		zend_long count = TypeCombinator::countConstantArrayValueTypes(types);
		if (UNEXPECTED(count < 0)) RETURN_THROWS();
		RETURN_LONG(count);
	});
	cls.method(sigs::intersect, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		uint32_t count;
		ZEND_PARSE_PARAMETERS_START(0, -1)
			Z_PARAM_VARIADIC('*', types, count)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TypeCombinator::intersect(count, types));
	});
	cls.method(sigs::doIntersect, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		uint32_t count;
		ZEND_PARSE_PARAMETERS_START(0, -1)
			Z_PARAM_VARIADIC('*', types, count)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TypeCombinator::doIntersect(count, types));
	});
	cls.method(sigs::removeFalsey, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(TypeCombinator::removeFalsey(type));
	});
	cls.method(sigs::removeTruthy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(TypeCombinator::removeTruthy(type));
	});

	cls.shadow(&pt_ce_type_combinator);
}

/* }}} */
