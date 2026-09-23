/*
 * PHPStanTurbo\ConstantArrayType — native implementation of
 * PHPStan\Type\Constant\ConstantArrayType, the array-shape type.
 *
 * Declared as PHPStan\Type\Constant\ConstantArrayType itself at activation:
 * not final (the PHP TemplateConstantArrayType extends it, overriding
 * recreate() and the TemplateTypeTrait methods), implementing
 * PHPStan\Type\Type. State is the twin's twelve private properties, declared
 * typed property slots in the twin's order — $isList, $unsealed, the six
 * memos ($allArrays, $iterableKeyType, $iterableValueType, $keyTypesUnion,
 * $keyIndexMap, $optionalKeySet) and the four promoted constructor properties ($keyTypes,
 * $valueTypes, $nextAutoIndexes, $optionalKeys) — so the std object
 * handlers do GC/clone. The three traits the twin is composed of come from
 * the shared registrars in TypeTraits.cpp, run after the class's own
 * methods so the class body wins over the traits exactly as in PHP; the
 * `chunkArray as traitChunkArray` alias is registered under its alias name
 * with the trait's handler.
 *
 * Every `$this->method()` the twin makes on a public or protected method
 * goes through the object's class entry — a subclass may have overridden it
 * — with a direct C++ call when the object is exactly a ConstantArrayType
 * (or still carries the native method); `new self(...)` and `self::`
 * statics are always this class. The private slots of another instance
 * (`$type->keyTypes`, `$type->unsealed`) are read directly, as the twin
 * does from inside the class. The twin's closures (RecursionGuard's
 * callback, the decorateReasons() formatters, the lazy reason) are native
 * bodies behind pt_type_native_callback() / pt_carr_native_closure()
 * (TypeTraits.cpp).
 */

#include "TypeTraits.h"
#include "generated/ConstantArrayType.h"

namespace slots = ptdecl::ConstantArrayType::slot;
namespace sigs = ptdecl::ConstantArrayType::sig;

zend_class_entry *pt_ce_constant_array_type = nullptr;

/* AcceptsResult.cpp: new <result class>($trinary, $reasons, $lazyReasons) */
bool pt_result_object_create(zval *out, zend_class_entry *ce, zval *trinary, zval *reasons, zval *lazyReasons);

/* the twin's private constants */
#define PT_CAT_DESCRIBE_LIMIT 8
#define PT_CAT_CHUNK_FINITE_TYPES_LIMIT 5
#define PT_CAT_UNSEALED_ARRAY_SHAPES_LINK "https://phpstan.org/blog/phpstan-2-2-unsealed-array-shapes-safer-array-keys"
/* the twin's inline thresholds: getAllArrays() enumerates the power set of
 * up to this many optional keys, generalize() keeps per-offset accessories
 * below this many required keys */
#define PT_CAT_POWER_SET_OPTIONAL_KEYS_LIMIT 10
#define PT_CAT_GENERALIZE_OFFSET_ACCESSORIES_LIMIT 32

/* PHP's CASE_LOWER / CASE_UPPER */
#define PT_CAT_CASE_LOWER 0
#define PT_CAT_CASE_UPPER 1

/* isValidIdentifier()'s pattern (a permanent interned string, made at
 * MINIT) and the internal preg_match() / preg_last_error() it is matched
 * with (internal functions, resolved once; the pcre extension caches the
 * compiled pattern per request) */
static zend_string *pt_carr_identifier_regex = nullptr;
static zend_function *pt_carr_preg_match = nullptr;
static zend_function *pt_carr_preg_last_error = nullptr;

namespace phpstanturbo {

/* the instanceof checks the twin makes, as helpers: a class-map class
 * (false = pending exception) or a shadowed class the native code holds */
[[nodiscard]] static bool isInstance(zval *value, int classIdx, bool &out)
{
	return pt_type_instanceof(value, classIdx, out);
}

static bool isInstance(zval *value, zend_class_entry *ce, bool &out)
{
	out = Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce);
	return true;
}

/* TypeCombinator::<method>($a, $b); UNDEF = pending exception */
static zv::Val combinator2(const char *lcname, size_t len, zval *a, zval *b)
{
	zv::Args args{a, b};
	return pt_type_combinator_call(lcname, len, 2, args);
}

/* TypeCombinator::<method>(...$types) over an owned array */
static zv::Val combinatorSpread(const char *lcname, size_t len, HashTable *types)
{
	return pt_type_combinator_call_spread(lcname, len, types);
}

/* new IntersectionType($types) ($types consumed) */
static zv::Val intersection(zv::Arr types)
{
	return pt_intersection_of(std::move(types));
}

/* the shadowed classes' constructors as owned values; UNDEF = pending
 * exception */
static zv::Val nonEmptyArray()
{
	return pt_val_of<pt_non_empty_array_type_new>();
}

static zv::Val accessoryList()
{
	return pt_val_of<pt_accessory_array_list_type_new>();
}

static zv::Val integerType()
{
	return pt_val_of<pt_integer_type_new>();
}

static zv::Val stringType()
{
	return pt_val_of<pt_string_type_new>();
}

static zv::Val classStringType()
{
	return pt_val_of<pt_class_string_type_new>();
}

static zv::Val objectWithoutClassType()
{
	return pt_type_new_object_without_class_type();
}

static zv::Val constantBoolean(bool value)
{
	zval result;
	if (UNEXPECTED(!pt_constant_boolean_type_new(&result, value))) return zv::Val();
	return zv::Val::adopt(result);
}

static zv::Val booleanType()
{
	return pt_val_of<pt_boolean_type_new>();
}

/* new NeverType($isExplicit) */
static zv::Val neverType(bool isExplicit = false)
{
	zval result;
	if (UNEXPECTED(!pt_never_type_new(&result, isExplicit))) return zv::Val();
	return zv::Val::adopt(result);
}

static zv::Val nullType()
{
	return pt_val_of<pt_null_type_new>();
}

/* new ConstantStringType($owned) — the owned string released */
static zv::Val constantStringOf(zend_string *owned)
{
	zval result;
	bool created = pt_constant_string_type_new(&result, owned);
	zend_string_release(owned);
	if (UNEXPECTED(!created)) return zv::Val();
	return zv::Val::adopt(result);
}

/* new ArrayType($keyType, $itemType) */
static zv::Val arrayType(zval *keyType, zval *itemType)
{
	zval result;
	if (UNEXPECTED(!pt_array_type_new(&result, keyType, itemType))) return zv::Val();
	return zv::Val::adopt(result);
}

/* new HasOffsetValueType($offsetType, $valueType) */
static zv::Val newHasOffsetValueType(zval *offsetType, zval *valueType)
{
	zval result;
	if (UNEXPECTED(!pt_has_offset_value_type_new(&result, offsetType, valueType))) return zv::Val();
	return zv::Val::adopt(result);
}

/* $object->method(...) that returns a Type: the result checked to be an
 * object (the engine's return check of the PHP twin); UNDEF = pending
 * exception */
static zv::Val callType(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	return pt_type_call_type(object, lcname, len, argc, argv);
}

/* $object->method(...) returning string; UNDEF = pending exception */
static zv::Val callString(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zv::Val result = pt_type_call(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(result.raw()).isString())) {
		zend_type_error("phpstan_turbo: %s::%s() must return string, %s returned", ZSTR_VAL(object->ce->name), lcname, zend_zval_value_name(result.raw()));
		return zv::Val();
	}
	return result;
}

/* $a->isSuperTypeOf($b)'s trinary value; -1 = pending exception */
[[nodiscard]] static zend_long isSuperTypeOfValue(zval *a, zval *b)
{
	return pt_type_call_result_trinary(Z_OBJ_P(a), PT_LC("issupertypeof"), 1, b);
}

/* $type->describe($level) as an owned string; UNDEF = pending exception */
static zv::Val describeOf(zval *type, zval *level)
{
	return callString(Z_OBJ_P(type), PT_LC("describe"), 1, level);
}

/* VerbosityLevel::value() (the shadowing class's singleton, VerbosityLevel.cpp) */
static zv::Val verbosityValue()
{
	return pt_type_verbosity_level(PT_VERBOSITY_LEVEL_VALUE);
}

/* $type->describe(VerbosityLevel::value()) / (VerbosityLevel::precise()) */
static zv::Val describeValue(zval *type)
{
	zv::Val level = verbosityValue();
	if (UNEXPECTED(level.isUndef())) return zv::Val();
	return describeOf(type, level.raw());
}

static zv::Val describePrecise(zval *type)
{
	zval description;
	if (UNEXPECTED(!pt_type_describe_precise(type, &description))) return zv::Val();
	if (UNEXPECTED(Z_TYPE(description) != IS_STRING)) {
		zval_ptr_dtor(&description);
		zend_type_error("phpstan_turbo: describe() must return string");
		return zv::Val();
	}
	return zv::Val::adopt(description);
}

/* $type instanceof MixedType && $type->describe(VerbosityLevel::precise()) === 'mixed' && !$type->isExplicitMixed();
 * false = pending exception */
[[nodiscard]] static bool isImplicitMixed(zval *type, bool &out)
{
	bool isMixed;
	if (UNEXPECTED(!isInstance(type, pt_ce_mixed_type, isMixed))) return false;
	if (!isMixed) {
		out = false;
		return true;
	}
	zv::Val description = describePrecise(type);
	if (UNEXPECTED(description.isUndef())) return false;
	if (!zv::Ref(description.raw()).stringEquals("mixed")) {
		out = false;
		return true;
	}
	zv::Val explicitMixed = pt_type_call(Z_OBJ_P(type), PT_LC("isexplicitmixed"), 0, NULL);
	if (UNEXPECTED(explicitMixed.isUndef())) return false;
	out = !zend_is_true(explicitMixed.raw());
	return true;
}

/* $type instanceof NeverType && $type->isExplicit(); false = pending
 * exception */
[[nodiscard]] static bool isExplicitNever(zval *type, bool &out)
{
	if (Z_TYPE_P(type) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(type), pt_ce_never_type)) {
		out = false;
		return true;
	}
	return pt_never_type_is_explicit(Z_OBJ_P(type), out);
}

/* (new BenevolentUnionType([new IntegerType(), new StringType()]))->toArrayKey() */
static zv::Val benevolentArrayKey()
{
	zv::Val integer = integerType();
	zv::Val string = stringType();
	if (UNEXPECTED(integer.isUndef() || string.isUndef())) return zv::Val();
	zv::Arr types = zv::Arr::create(2);
	types.push(std::move(integer));
	types.push(std::move(string));
	zv::Val benevolent = pt_union_benevolent_of(std::move(types));
	if (UNEXPECTED(benevolent.isUndef())) return zv::Val();
	return callType(Z_OBJ_P(benevolent.raw()), PT_LC("toarraykey"), 0, NULL);
}

/* the unsealed key type the iterable-key methods substitute: a MixedType
 * (not a TemplateMixedType) or a StrictMixedType (not a
 * TemplateStrictMixedType) becomes (int|string)->toArrayKey(); an owned
 * value either way; UNDEF = pending exception */
static zv::Val substituteMixedUnsealedKey(zval *unsealedKeyType)
{
	bool isMixed;
	if (UNEXPECTED(!isInstance(unsealedKeyType, pt_ce_mixed_type, isMixed))) return zv::Val();
	if (isMixed) {
		bool isTemplateMixed;
		if (UNEXPECTED(!isInstance(unsealedKeyType, pt_ce_template_mixed_type, isTemplateMixed))) return zv::Val();
		if (!isTemplateMixed) return benevolentArrayKey();
		return zv::Val::copyOf(zv::Ref(unsealedKeyType));
	}
	bool isStrictMixed;
	if (UNEXPECTED(!isInstance(unsealedKeyType, pt_ce_strict_mixed_type, isStrictMixed))) return zv::Val();
	if (isStrictMixed) {
		bool isTemplateStrictMixed;
		if (UNEXPECTED(!isInstance(unsealedKeyType, pt_ce_template_strict_mixed_type, isTemplateStrictMixed))) return zv::Val();
		if (!isTemplateStrictMixed) return benevolentArrayKey();
	}
	return zv::Val::copyOf(zv::Ref(unsealedKeyType));
}

/* ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT — the shadowed class's
 * constant, shared as a native constant */
static bool arrayCountLimit(zend_long &out)
{
	out = PT_CONSTANT_ARRAY_TYPE_BUILDER_ARRAY_COUNT_LIMIT;
	return true;
}

/* InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT — the shadowed
 * class's constant, shared as a native constant */
static bool calculateScalarsLimit(zend_long &out)
{
	out = PT_INITIALIZER_EXPR_TYPE_RESOLVER_CALCULATE_SCALARS_LIMIT;
	return true;
}

/* BleedingEdgeToggle::isBleedingEdge(): the private static the final class
 * returns, read through the engine's static-property path under the
 * class's own scope (the same value the method returns, without the
 * userland frame); false = pending exception */
[[nodiscard]] static bool isBleedingEdge(bool &out)
{
	zend_class_entry *ce = pt_class(PT_CLASS_BLEEDING_EDGE_TOGGLE);
	if (UNEXPECTED(ce == NULL)) return false;
	auto previousScope = EG(fake_scope); /* const from PHP 8.4 */
	EG(fake_scope) = ce;
	zval *value = zend_read_static_property(ce, PT_LC("bleedingEdge"), 1);
	EG(fake_scope) = previousScope;
	if (EXPECTED(value != NULL && (Z_TYPE_P(value) == IS_TRUE || Z_TYPE_P(value) == IS_FALSE))) {
		out = Z_TYPE_P(value) == IS_TRUE;
		return true;
	}
	if (UNEXPECTED(EG(exception))) return false;
	zv::Val result = pt_type_call_static(PT_CLASS_BLEEDING_EDGE_TOGGLE, PT_LC("isbleedingedge"), 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* $array[$index]; NULL with an Error pending when absent — the twin's
 * read of a missing index warns and then fails on the null */
[[nodiscard]] static zval *arrayIndex(zval *array, zend_long index, const char *what)
{
	zval *found = zend_hash_index_find(Z_ARRVAL_P(array), (zend_ulong) index);
	if (UNEXPECTED(found == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: undefined array key " ZEND_LONG_FMT " in %s", index, what);
		return NULL;
	}
	ZVAL_DEREF(found);
	return found;
}

/* a Type held by an array slot; NULL with an Error pending otherwise */
[[nodiscard]] static zval *arrayIndexObject(zval *array, zend_long index, const char *what)
{
	zval *found = arrayIndex(array, index, what);
	if (UNEXPECTED(found != NULL && Z_TYPE_P(found) != IS_OBJECT)) {
		zend_throw_error(NULL, "phpstan_turbo: %s[" ZEND_LONG_FMT "] must be a %s, %s given", what, index, ptcls::type, zend_zval_value_name(found));
		return NULL;
	}
	return found;
}

/* count($array) */
static zend_long arrayCount(zval *array)
{
	return (zend_long) zend_hash_num_elements(Z_ARRVAL_P(array));
}

/* in_array($needle, $haystack, true) for an int needle */
static bool inArrayStrictLong(zval *haystack, zend_long needle)
{
	for (zv::ArrayEntry entry : zv::ArrRef(haystack)) {
		zval *value = entry.value().deref().raw();
		if (Z_TYPE_P(value) == IS_LONG && Z_LVAL_P(value) == needle) return true;
	}
	return false;
}

/* a fresh list from the values of $array (array_values()) */
static zv::Arr arrayValues(zval *array)
{
	zv::Arr values = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(array)));
	for (zv::ArrayEntry entry : zv::ArrRef(array)) {
		values.push(entry.value());
	}
	return values;
}

/* array_values(array_unique($ints)) for a list of ints: the first
 * occurrences, in order */
static zv::Arr uniqueLongs(zval *array)
{
	zv::ScratchTable seen((uint32_t) zend_hash_num_elements(Z_ARRVAL_P(array)));
	zv::Arr values = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(array)));
	for (zv::ArrayEntry entry : zv::ArrRef(array)) {
		zval *value = entry.value().deref().raw();
		if (Z_TYPE_P(value) == IS_LONG) {
			if (zend_hash_index_add_empty_element(seen.table(), (zend_ulong) Z_LVAL_P(value)) == NULL) continue;
		}
		values.push(zv::Ref(value));
	}
	return values;
}

/* sort($ints) for a list of ints (sort() compares ints numerically; equal
 * ints are indistinguishable, so the algorithm does not matter) */
static int compareLongBuckets(Bucket *a, Bucket *b)
{
	return Z_LVAL(a->val) < Z_LVAL(b->val) ? -1 : (Z_LVAL(a->val) > Z_LVAL(b->val) ? 1 : 0);
}

static void sortLongs(zv::Arr &ints)
{
	ints.separate();
	zend_hash_sort_ex(ints.table(), zend_sort, compareLongBuckets, 1);
}

/* array_pop($array): the last element in order removed, the next free
 * index pulled back when it was the last appended one */
static void arrayPop(zval *array)
{
	SEPARATE_ARRAY(array);
	HashTable *ht = Z_ARRVAL_P(array);
	if (zend_hash_num_elements(ht) == 0) return;
	uint32_t idx = ht->nNumUsed;
	if (HT_IS_PACKED(ht)) {
		while (idx > 0) {
			idx--;
			zval *p = &ht->arPacked[idx];
			if (Z_TYPE_P(p) != IS_UNDEF) {
				if ((zend_long) idx == ht->nNextFreeElement - 1) {
					ht->nNextFreeElement--;
				}
				zend_hash_index_del(ht, idx);
				break;
			}
		}
	} else {
		while (idx > 0) {
			idx--;
			Bucket *p = &ht->arData[idx];
			if (Z_TYPE(p->val) != IS_UNDEF) {
				if (p->key == NULL) {
					if ((zend_long) p->h == ht->nNextFreeElement - 1) {
						ht->nNextFreeElement--;
					}
					zend_hash_index_del(ht, p->h);
				} else {
					zend_hash_del(ht, p->key);
				}
				break;
			}
		}
	}
	zend_hash_internal_pointer_reset(ht);
}

/* range($start, $end) over ints */
static zv::Arr rangeLongs(zend_long start, zend_long end)
{
	zend_long count = (start <= end ? end - start : start - end) + 1;
	zv::Arr values = zv::Arr::create((uint32_t) count);
	if (start <= end) {
		for (zend_long i = start; ; i++) {
			values.push(zv::Val::integer(i));
			if (i == end) break;
		}
	} else {
		for (zend_long i = start; ; i--) {
			values.push(zv::Val::integer(i));
			if (i == end) break;
		}
	}
	return values;
}

/* $map[$key] = $value / isset($map[$key]) / unset($map[$key]) with PHP's
 * array-key semantics for an int|string key (a numeric string is an int
 * key); other key kinds are the twin's getValue() contract violations */
static bool mapKeyOk(zval *key)
{
	if (EXPECTED(Z_TYPE_P(key) == IS_LONG || Z_TYPE_P(key) == IS_STRING)) return true;
	zend_type_error("phpstan_turbo: an array key type's getValue() must return int|string, %s returned", zend_zval_value_name(key));
	return false;
}

static bool mapSet(HashTable *map, zval *key, zval *value)
{
	if (UNEXPECTED(!mapKeyOk(key))) return false;
	Z_TRY_ADDREF_P(value);
	if (Z_TYPE_P(key) == IS_LONG) {
		zend_hash_index_update(map, (zend_ulong) Z_LVAL_P(key), value);
	} else {
		zend_symtable_update(map, Z_STR_P(key), value);
	}
	return true;
}

static zval *mapFind(HashTable *map, zval *key)
{
	if (Z_TYPE_P(key) == IS_LONG) return zend_hash_index_find(map, (zend_ulong) Z_LVAL_P(key));
	if (Z_TYPE_P(key) == IS_STRING) return zend_symtable_find(map, Z_STR_P(key));
	return NULL;
}

static void mapDel(HashTable *map, zval *key)
{
	if (Z_TYPE_P(key) == IS_LONG) {
		zend_hash_index_del(map, (zend_ulong) Z_LVAL_P(key));
	} else if (Z_TYPE_P(key) == IS_STRING) {
		zend_symtable_del(map, Z_STR_P(key));
	}
}

/* $keyType->getValue() of a ConstantIntegerType|ConstantStringType key —
 * the slots of the native classes, the method through the class entry
 * otherwise; an owned int or string; UNDEF = pending exception */
static zv::Val keyValue(zval *keyType)
{
	if (UNEXPECTED(Z_TYPE_P(keyType) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: a key type must be an object, %s given", zend_zval_value_name(keyType));
		return zv::Val();
	}
	zend_object *object = Z_OBJ_P(keyType);
	if (instanceof_function(object->ce, pt_ce_constant_integer_type)) {
		zend_long value;
		if (UNEXPECTED(!pt_constant_integer_get_value(object, value))) return zv::Val();
		return zv::Val::integer(value);
	}
	if (instanceof_function(object->ce, pt_ce_constant_string_type)) return pt_constant_string_get_value(object);
	return pt_type_call(object, PT_LC("getvalue"), 0, NULL);
}

/* the reasons array of a result object (an AcceptsResult /
 * IsSuperTypeOfResult, native or the PHP twin — its public $reasons);
 * UNDEF = pending exception */
static zv::Val resultReasons(zval *result)
{
	if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: expected a result object, %s given", zend_zval_value_name(result));
		return zv::Val();
	}
	/* the engine hands back the declared slot, or fills rv for a magic
	 * read — only then is rv ours to release */
	zval rv;
	ZVAL_UNDEF(&rv);
	zval *reasons = zend_read_property(Z_OBJCE_P(result), Z_OBJ_P(result), PT_LC("reasons"), 0, &rv);
	if (UNEXPECTED(reasons == NULL || EG(exception))) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(reasons) != IS_ARRAY)) {
		zend_type_error("phpstan_turbo: $reasons must be an array");
		if (reasons == &rv) {
			zval_ptr_dtor(&rv);
		}
		return zv::Val();
	}
	zv::Val copy = zv::Val::copyOf(zv::Ref(reasons));
	if (reasons == &rv) {
		zval_ptr_dtor(&rv);
	}
	return copy;
}

/* new AcceptsResult($result->result, [$reason]) — the reasons the twin
 * substitutes for a reasonless non-yes result; $reason owned */
static zv::Val acceptsResultWithReason(zval *result, zv::Val reason)
{
	if (UNEXPECTED(reason.isUndef())) return zv::Val();
	zend_long value = pt_type_result_trinary(result);
	if (UNEXPECTED(value < 0)) return zv::Val();
	zv::Arr reasons = zv::Arr::create(1);
	reasons.push(std::move(reason));
	zval reasonsRaw = reasons.take();
	zval created;
	if (UNEXPECTED(!pt_accepts_result_create(&created, pt_trinary_singleton(value), &reasonsRaw))) return zv::Val();
	return zv::Val::adopt(created);
}

/* AcceptsResult::createNo([$reason]); $reason owned */
static zv::Val acceptsNoWithReason(zv::Val reason)
{
	if (UNEXPECTED(reason.isUndef())) return zv::Val();
	zv::Arr reasons = zv::Arr::create(1);
	reasons.push(std::move(reason));
	zval reasonsRaw = reasons.take();
	zval created;
	if (UNEXPECTED(!pt_accepts_result_create(&created, pt_trinary_singleton(PT_TRI_NO), &reasonsRaw))) return zv::Val();
	return zv::Val::adopt(created);
}

/* $result->decorateReasons($cb) with a native $cb over two captured
 * values; UNDEF = pending exception */
static zv::Val decorateReasons(zval *result, pt_native_callback fn, zval *state0, zval *state1)
{
	zv::Val callback = pt_type_native_callback(fn, state0, state1);
	if (UNEXPECTED(callback.isUndef())) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: expected a result object, %s given", zend_zval_value_name(result));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(result), PT_LC("decoratereasons"), 1, callback.raw());
}

/* the string argument of a decorateReasons() callback */
static zend_string *reasonArgument(uint32_t argc, zval *argv)
{
	if (UNEXPECTED(argc < 1 || Z_TYPE_P(&argv[0]) != IS_STRING)) {
		zend_type_error("phpstan_turbo: the decorateReasons() callback takes a string");
		return NULL;
	}
	return Z_STR_P(&argv[0]);
}

/* a ConstantArrayTypeBuilder: createEmpty() / createFromConstantArray($array)
 * — the shadowed class, driven through its exported helpers (direct C++
 * calls) */
static zv::Val builderCreateEmpty()
{
	return pt_constant_array_type_builder_create_empty();
}

static zv::Val builderCreateFromConstantArray(zval *array)
{
	return pt_constant_array_type_builder_create_from_constant_array(array);
}

/* $builder->setOffsetValueType($offsetType, $valueType[, $optional]);
 * offsetType NULL = the twin's null, optional -1 = the parameter left at
 * its default (false); false = pending exception */
[[nodiscard]] static bool builderSet(zval *builder, zval *offsetType, zval *valueType, int optional = -1)
{
	return pt_constant_array_type_builder_set_offset_value_type(builder, offsetType, valueType, optional > 0);
}

/* $builder->makeUnsealed($keyType, $valueType); false = pending exception */
[[nodiscard]] static bool builderMakeUnsealed(zval *builder, zval *keyType, zval *valueType)
{
	return pt_constant_array_type_builder_make_unsealed(builder, keyType, valueType);
}

/* $builder->getArray() */
static zv::Val builderGetArray(zval *builder)
{
	zv::Val array = pt_constant_array_type_builder_get_array(builder);
	if (UNEXPECTED(array.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(array.raw()).isObject())) {
		zend_type_error("phpstan_turbo: ConstantArrayTypeBuilder::getArray() must return %s", ptcls::type);
		return zv::Val();
	}
	return array;
}

/* $builder->degradeToGeneralArray() / ->disableArrayDegradation(); false =
 * pending exception */
static bool builderCall0(zval *builder, const char *lcname, size_t len)
{
	zv::Val result = pt_type_call(Z_OBJ_P(builder), lcname, len, 0, NULL);
	return !result.isUndef();
}

/* $builder->isList(); false = pending exception */
[[nodiscard]] static bool builderIsList(zval *builder, bool &out)
{
	return pt_type_op_bool(Z_OBJ_P(builder), PT_OP_IS_LIST, 0, NULL, out);
}

/* [$key, $value] = $unsealed: the pair's two Types (borrowed); false with
 * an Error pending when the array is not a pair of objects */
static bool unsealedPair(zval *unsealed, zval *&keyType, zval *&valueType)
{
	if (UNEXPECTED(Z_TYPE_P(unsealed) != IS_ARRAY)) {
		zend_type_error("phpstan_turbo: $unsealed must be an array");
		return false;
	}
	zval *k = zend_hash_index_find(Z_ARRVAL_P(unsealed), 0);
	zval *v = zend_hash_index_find(Z_ARRVAL_P(unsealed), 1);
	if (UNEXPECTED(k == NULL || v == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: $unsealed must be a pair of %s", ptcls::type);
		return false;
	}
	ZVAL_DEREF(k);
	ZVAL_DEREF(v);
	if (UNEXPECTED(Z_TYPE_P(k) != IS_OBJECT || Z_TYPE_P(v) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: $unsealed must be a pair of %s", ptcls::type);
		return false;
	}
	keyType = k;
	valueType = v;
	return true;
}

/* [$keyType, $valueType] as an owned pair array */
static zv::Val pairOf(zval *keyType, zval *valueType)
{
	zv::Arr pair = zv::Arr::create(2);
	pair.push(zv::Ref(keyType));
	pair.push(zv::Ref(valueType));
	return zv::Val(std::move(pair));
}

/* the PT_TRI_* value of a TrinaryLogic zval (the twin's ->yes()/->no()/
 * ->maybe() reads); -1 = pending exception */
[[nodiscard]] static zend_long trinaryOf(zval *trinary)
{
	return pt_type_trinary_value(trinary);
}

/* TrinaryLogic::createFromBoolean() */
static zend_long trinaryFromBoolean(bool value)
{
	return value ? PT_TRI_YES : PT_TRI_NO;
}

/* TrinaryLogic::negate() — 3 >> $value */
static zend_long trinaryNegate(zend_long value)
{
	return 3 >> value;
}

/* TrinaryLogic::extremeIdentity(...$values) over collected values; the
 * twin throws for none */
static zend_long trinaryExtremeIdentity(const zend_long *values, size_t count)
{
	if (UNEXPECTED(count == 0)) {
		pt_throw_should_not_happen();
		return -1;
	}
	zend_long min = values[0], max = values[0];
	for (size_t i = 1; i < count; i++) {
		if (values[i] < min) {
			min = values[i];
		}
		if (values[i] > max) {
			max = values[i];
		}
	}
	return min == max ? min : PT_TRI_MAYBE;
}

/* $trinary->toBooleanType() */
static zv::Val trinaryToBooleanType(zend_long value)
{
	if (value == PT_TRI_MAYBE) return booleanType();
	return constantBoolean(value == PT_TRI_YES);
}

/* $a - $b through the engine (a float past the int range) */
static void phpSub(zend_long a, zend_long b, zval *out)
{
	zval x, y;
	ZVAL_LONG(&x, a);
	ZVAL_LONG(&y, b);
	sub_function(out, &x, &y);
}

/* the class's own handlers (defined with the registration below): the
 * fast-path identities for the $this-calls a subclass could override */
} // namespace phpstanturbo
static void ZEND_FASTCALL catIsUnsealed(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catGetUnsealedTypes(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catRecreate(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catGetIterableKeyType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catGetIterableValueType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catGetKeyType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catGetItemType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catGetKeyTypes(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catGetValueTypes(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catIsOptionalKey(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catFindTypeAndMethodNames(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catHasOffsetValueType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catGetOffsetValueType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catUnsetOffset(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catReverseArray(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catSliceArray(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catIsIterableAtLeastOnce(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catGetArraySize(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catIsList(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catToBoolean(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catGetValuesArray(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catDescribe(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL catTraverse(INTERNAL_FUNCTION_PARAMETERS);
namespace phpstanturbo {

/* Mirrors PHPStan\Type\Constant\ConstantArrayType. State lives in the PHP
 * object's slots. */
class ConstantArrayType
{
public:
	explicit ConstantArrayType(zend_object *self) : self(self) {}

	/* the twin's typed parameter checks for a `new self()` / `new
	 * ConstantArrayType()` from native code; false with a TypeError pending */
	static bool checkArray(zval *value, int argNumber)
	{
		if (UNEXPECTED(Z_TYPE_P(value) != IS_ARRAY)) {
			zend_argument_type_error((uint32_t) argNumber, "must be of type array, %s given", zend_zval_value_name(value));
			return false;
		}
		return true;
	}

	static bool checkNullableArray(zval *value, int argNumber)
	{
		if (UNEXPECTED(Z_TYPE_P(value) != IS_ARRAY && Z_TYPE_P(value) != IS_NULL)) {
			zend_argument_type_error((uint32_t) argNumber, "must be of type ?array, %s given", zend_zval_value_name(value));
			return false;
		}
		return true;
	}

	static bool checkNullableTrinary(zval *value, int argNumber)
	{
		if (UNEXPECTED(Z_TYPE_P(value) != IS_NULL && (Z_TYPE_P(value) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(value), pt_ce_trinary)))) {
			zend_argument_type_error((uint32_t) argNumber, "must be of type ?%s, %s given", ptcls::trinaryLogic, zend_zval_value_name(value));
			return false;
		}
		return true;
	}

	/* __construct(private array $keyTypes, private array $valueTypes,
	 * private array $nextAutoIndexes = [0], private array $optionalKeys = [],
	 * ?TrinaryLogic $isList = null, ?array $unsealed = null); the NULL
	 * arguments stand for the defaults, an IS_NULL $isList/$unsealed for
	 * the twin's null; false = pending exception */
	[[nodiscard]] bool construct(zval *keyTypesArg, zval *valueTypesArg, zval *nextAutoIndexesArg, zval *optionalKeysArg, zval *isListArg, zval *unsealedArg)
	{
		/* assert(count($keyTypes) === count($valueTypes)) — an
		 * AssertionError when the engine evaluates assertions */
		if (UNEXPECTED(EG(assertions) > 0 && zend_hash_num_elements(Z_ARRVAL_P(keyTypesArg)) != zend_hash_num_elements(Z_ARRVAL_P(valueTypesArg)))) {
			zend_string *assertionErrorName = zend_string_init(PT_LC("AssertionError"), 0);
			zend_class_entry *assertionError = zend_lookup_class_ex(assertionErrorName, NULL, ZEND_FETCH_CLASS_NO_AUTOLOAD);
			zend_string_release(assertionErrorName);
			zend_throw_exception(assertionError != NULL ? assertionError : zend_ce_error, "assert(count($keyTypes) === count($valueTypes))", 0);
			return false;
		}

		zv::Val unsealed = unsealedArg == NULL || Z_TYPE_P(unsealedArg) == IS_NULL ? zv::Val::null() : zv::Val::copyOf(zv::Ref(unsealedArg));

		/* Fill in `$isList` from the shape when the caller didn't pass one.
		 * For empty CATs the answer derives from the unsealed key type (no
		 * explicit keys to inspect); for non-empty ones the default is `No`
		 * and the caller is expected to assert list-ness via `makeList()` if
		 * appropriate. */
		zv::Val isList;
		if (isListArg == NULL || Z_TYPE_P(isListArg) == IS_NULL) {
			zend_long value;
			if (zend_hash_num_elements(Z_ARRVAL_P(keyTypesArg)) == 0) {
				if (unsealed.isNull()) {
					value = PT_TRI_YES;
				} else {
					zval *unsealedKeyType = unsealedKeyOf(unsealed.raw());
					if (UNEXPECTED(unsealedKeyType == NULL)) return false;
					bool explicitNever;
					if (UNEXPECTED(!isExplicitNever(unsealedKeyType, explicitNever))) return false;
					if (explicitNever) {
						value = PT_TRI_YES;
					} else {
						zend_long isInteger = pt_type_op_trinary(Z_OBJ_P(unsealedKeyType), PT_OP_IS_INTEGER, 0, NULL);
						if (UNEXPECTED(isInteger < 0)) return false;
						value = isInteger == PT_TRI_YES ? PT_TRI_MAYBE : PT_TRI_NO;
					}
				}
			} else {
				value = PT_TRI_NO;
			}
			isList = pt_type_trinary(value);
		} else {
			isList = zv::Val::copyOf(zv::Ref(isListArg));
		}

		if (!unsealed.isNull()) {
			zval *unsealedKeyType = unsealedKeyOf(unsealed.raw());
			if (UNEXPECTED(unsealedKeyType == NULL)) return false;
			/* Only a BenevolentUnionType describes with the surrounding
			 * parentheses of '(int|string)' / '(int|non-decimal-int-string)',
			 * so skip the describe() call for every other key type. */
			bool isBenevolent;
			if (UNEXPECTED(!pt_type_instanceof_ce(unsealedKeyType, pt_ce_benevolent_union_type, isBenevolent))) return false;
			if (isBenevolent) {
				zv::Val description = describeValue(unsealedKeyType);
				if (UNEXPECTED(description.isUndef())) return false;
				zv::Ref d(description.raw());
				if (d.stringEquals("(int|string)") || d.stringEquals("(int|non-decimal-int-string)")) {
					zv::Val mixed = pt_type_new_mixed_type();
					if (UNEXPECTED(mixed.isUndef())) return false;
					zv::ArrRef(unsealed.raw()).setIndex(0, zv::Ref(mixed.raw()));
					unsealedKeyType = unsealedKeyOf(unsealed.raw());
				}
			}
			bool isStrictMixed;
			if (UNEXPECTED(!isInstance(unsealedKeyType, pt_ce_strict_mixed_type, isStrictMixed))) return false;
			if (isStrictMixed) {
				bool isTemplateStrictMixed;
				if (UNEXPECTED(!isInstance(unsealedKeyType, pt_ce_template_strict_mixed_type, isTemplateStrictMixed))) return false;
				if (!isTemplateStrictMixed) {
					/* (new UnionType([new StringType(), new IntegerType()]))->toArrayKey() */
					zv::Val string = stringType();
					zv::Val integer = integerType();
					if (UNEXPECTED(string.isUndef() || integer.isUndef())) return false;
					zv::Arr types = zv::Arr::create(2);
					types.push(std::move(string));
					types.push(std::move(integer));
					zv::Val unionType = pt_type_new_union(std::move(types));
					if (UNEXPECTED(unionType.isUndef())) return false;
					zv::Val arrayKey = callType(Z_OBJ_P(unionType.raw()), PT_LC("toarraykey"), 0, NULL);
					if (UNEXPECTED(arrayKey.isUndef())) return false;
					zv::ArrRef(unsealed.raw()).setIndex(0, zv::Ref(arrayKey.raw()));
					unsealedKeyType = unsealedKeyOf(unsealed.raw());
				}
			}
			bool explicitNever;
			if (UNEXPECTED(!isExplicitNever(unsealedKeyType, explicitNever))) return false;
			if (explicitNever) {
				zv::Val never = neverType(true);
				if (UNEXPECTED(never.isUndef())) return false;
				zv::ArrRef(unsealed.raw()).setIndex(1, zv::Ref(never.raw()));
			}
		} else {
			bool bleedingEdge;
			if (UNEXPECTED(!isBleedingEdge(bleedingEdge))) return false;
			if (bleedingEdge) {
				zv::Val never = neverType(true);
				if (UNEXPECTED(never.isUndef())) return false;
				unsealed = pairOf(never.raw(), never.raw());
			}
		}

		zv::ObjRef ref(self);
		writeSlot(ref, slots::keyTypes, zv::Val::copyOf(zv::Ref(keyTypesArg)));
		writeSlot(ref, slots::valueTypes, zv::Val::copyOf(zv::Ref(valueTypesArg)));
		if (nextAutoIndexesArg != NULL) {
			writeSlot(ref, slots::nextAutoIndexes, zv::Val::copyOf(zv::Ref(nextAutoIndexesArg)));
		} else {
			zv::Arr zero = zv::Arr::create(1);
			zero.push(zv::Val::integer(0));
			writeSlot(ref, slots::nextAutoIndexes, zv::Val(std::move(zero)));
		}
		if (optionalKeysArg != NULL) {
			writeSlot(ref, slots::optionalKeys, zv::Val::copyOf(zv::Ref(optionalKeysArg)));
		} else {
			writeSlot(ref, slots::optionalKeys, zv::Val(zv::Arr::empty()));
		}
		writeSlot(ref, slots::isList, std::move(isList));
		writeSlot(ref, slots::unsealed, std::move(unsealed));
		return true;
	}

	/* new self(...) — exactly the class, with the twin's typed parameters'
	 * checks; the NULL/IS_NULL conventions of construct(); UNDEF = pending
	 * exception */
	static zv::Val create(zval *keyTypes, zval *valueTypes, zval *nextAutoIndexes = NULL, zval *optionalKeys = NULL, zval *isList = NULL, zval *unsealed = NULL)
	{
		if (UNEXPECTED(!checkArray(keyTypes, 1) || !checkArray(valueTypes, 2)
			|| (nextAutoIndexes != NULL && !checkArray(nextAutoIndexes, 3))
			|| (optionalKeys != NULL && !checkArray(optionalKeys, 4))
			|| (isList != NULL && !checkNullableTrinary(isList, 5))
			|| (unsealed != NULL && !checkNullableArray(unsealed, 6)))) {
			return zv::Val();
		}
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_constant_array_type) != SUCCESS)) return zv::Val();
		if (UNEXPECTED(!ConstantArrayType(Z_OBJ(object)).construct(keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList, unsealed))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

	/* new ConstantArrayType([], []) */
	static zv::Val createEmpty()
	{
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		return create(&empty, &empty);
	}

	/* $this->isUnsealed()->negate() — through the object's class */
	zv::Val isSealed() const
	{
		zend_long unsealed = thisIsUnsealed();
		if (UNEXPECTED(unsealed < 0)) return zv::Val();
		return pt_type_trinary(trinaryNegate(unsealed));
	}

	/* maybe for no unsealed pair, else whether its key type is not an
	 * explicit never; -1 = pending exception */
	[[nodiscard]] zend_long isUnsealed() const
	{
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return -1;
		if (Z_TYPE_P(unsealed) == IS_NULL) return PT_TRI_MAYBE;
		zval *keyType = unsealedKeyOf(unsealed);
		if (UNEXPECTED(keyType == NULL)) return -1;
		bool explicitNever;
		if (UNEXPECTED(!isExplicitNever(keyType, explicitNever))) return -1;
		return trinaryFromBoolean(!explicitNever);
	}

	zv::Val getUnsealedTypes() const
	{
		zval *unsealed = unsealedSlot();
		return unsealed == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(unsealed));
	}

	/* $this->recreate(..., null) */
	zv::Val dropUnsealedTypes() const
	{
		zval *k = keyTypes();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zval *v = valueTypes();
		zval *n = v != NULL ? nextAutoIndexes() : NULL;
		zval *o = n != NULL ? optionalKeys() : NULL;
		zval *l = o != NULL ? isListSlot() : NULL;
		if (UNEXPECTED(l == NULL)) return zv::Val();
		zval nullZv;
		ZVAL_NULL(&nullZv);
		return thisRecreate(k, v, n, o, l, &nullZv);
	}

	/* protected recreate(): new self(...) */
	static zv::Val recreate(zval *keyTypes, zval *valueTypes, zval *nextAutoIndexes, zval *optionalKeys, zval *isList, zval *unsealed)
	{
		return create(keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList, unsealed);
	}

	/* [$this] */
	zv::Val getConstantArrays() const
	{
		zv::Arr arrays = zv::Arr::create(1);
		arrays.push(zv::Ref(thisZv()));
		return zv::Val(std::move(arrays));
	}

	/* the referenced classes of every key type, every value type and the
	 * unsealed pair, collected in that order; UNDEF = pending exception */
	zv::Val getReferencedClasses() const
	{
		zv::Arr referencedClasses = zv::Arr::create(8);
		zv::Val keyTypes = thisGetKeyTypes();
		if (UNEXPECTED(keyTypes.isUndef())) return zv::Val();
		if (UNEXPECTED(!collectReferencedClasses(referencedClasses, keyTypes.raw()))) return zv::Val();
		zv::Val valueTypes = thisGetValueTypes();
		if (UNEXPECTED(valueTypes.isUndef())) return zv::Val();
		if (UNEXPECTED(!collectReferencedClasses(referencedClasses, valueTypes.raw()))) return zv::Val();
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		if (Z_TYPE_P(unsealed) != IS_NULL) {
			zv::Val pair = zv::Val::copyOf(zv::Ref(unsealed));
			if (UNEXPECTED(!collectReferencedClasses(referencedClasses, pair.raw()))) return zv::Val();
		}
		return zv::Val(std::move(referencedClasses));
	}

	/* memoized in $iterableKeyType: never (explicit) / the single key /
	 * the union of the keys, unioned with the unsealed key type (mixed and
	 * strict mixed replaced by (int|string) keys) of an unsealed shape,
	 * through UnsafeArrayStringKeyCastingTraverser::castKeyType(); UNDEF =
	 * pending exception */
	zv::Val getIterableKeyType() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::iterableKeyType);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));
		zval *k = keyTypes();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zend_long keyTypesCount = arrayCount(k);
		zv::Val keyType;
		if (keyTypesCount == 0) {
			keyType = neverType(true);
		} else if (keyTypesCount == 1) {
			zval *first = arrayIndexObject(k, 0, "keyTypes");
			if (UNEXPECTED(first == NULL)) return zv::Val();
			keyType = zv::Val::copyOf(zv::Ref(first));
		} else {
			keyType = pt_type_new_union(zv::Arr::copyOfTable(Z_ARRVAL_P(k)));
		}
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();

		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
			zval *unsealedKey = unsealedKeyOf(unsealed);
			if (UNEXPECTED(unsealedKey == NULL)) return zv::Val();
			zv::Val unsealedKeyType = substituteMixedUnsealedKey(unsealedKey);
			if (UNEXPECTED(unsealedKeyType.isUndef())) return zv::Val();
			keyType = combinator2(PT_LC("union"), keyType.raw(), unsealedKeyType.raw());
			if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		}

		zv::Val cast = pt_type_call_static(PT_CLASS_UNSAFE_ARRAY_STRING_KEY_CASTING_TRAVERSER, PT_LC("castkeytype"), 1, keyType.raw());
		if (UNEXPECTED(cast.isUndef())) return zv::Val();
		zv::ObjRef(self).propAtWrite(slots::iterableKeyType, zv::Val::copyOf(zv::Ref(cast.raw())));
		return cast;
	}

	/* memoized in $iterableValueType: the union of the value types (never
	 * (explicit) for none), unioned with the unsealed value type of an
	 * unsealed shape; UNDEF = pending exception */
	zv::Val getIterableValueType() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::iterableValueType);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));
		zval *v = valueTypes();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zv::Val valueType = arrayCount(v) > 0 ? combinatorSpread(PT_LC("union"), Z_ARRVAL_P(v)) : neverType(true);
		if (UNEXPECTED(valueType.isUndef())) return zv::Val();
		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
			zval *unsealedKey, *unsealedValue;
			if (UNEXPECTED(!unsealedPair(unsealed, unsealedKey, unsealedValue))) return zv::Val();
			valueType = combinator2(PT_LC("union"), valueType.raw(), unsealedValue);
			if (UNEXPECTED(valueType.isUndef())) return zv::Val();
		}
		if (UNEXPECTED(!zv::Ref(valueType.raw()).isObject())) {
			zend_type_error("phpstan_turbo: TypeCombinator::union() must return %s", ptcls::type);
			return zv::Val();
		}
		zv::ObjRef(self).propAtWrite(slots::iterableValueType, zv::Val::copyOf(zv::Ref(valueType.raw())));
		return valueType;
	}

	/* private, memoized in $keyTypesUnion: the union of the keys, never for
	 * none; UNDEF = pending exception */
	zv::Val getKeyTypesUnion() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::keyTypesUnion);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));
		zval *k = keyTypes();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zv::Val unionType = arrayCount(k) > 0 ? combinatorSpread(PT_LC("union"), Z_ARRVAL_P(k)) : neverType();
		if (UNEXPECTED(unionType.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(unionType.raw()).isObject())) {
			zend_type_error("phpstan_turbo: TypeCombinator::union() must return %s", ptcls::type);
			return zv::Val();
		}
		zv::ObjRef(self).propAtWrite(slots::keyTypesUnion, zv::Val::copyOf(zv::Ref(unionType.raw())));
		return unionType;
	}

	zv::Val getKeyType() const { return thisGetIterableKeyType(); }
	zv::Val getItemType() const { return thisGetIterableValueType(); }

	/* no for an unsealed shape, yes otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isConstantValue() const
	{
		zend_long unsealed = thisIsUnsealed();
		if (UNEXPECTED(unsealed < 0)) return -1;
		return unsealed == PT_TRI_YES ? PT_TRI_NO : PT_TRI_YES;
	}

	zv::Val getNextAutoIndexes() const
	{
		zval *n = nextAutoIndexes();
		return n == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(n));
	}

	zv::Val getOptionalKeys() const
	{
		zval *o = optionalKeys();
		return o == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(o));
	}

	/* memoized in $allArrays: one shape per combination of the optional keys
	 * (the power set of up to POWER_SET_OPTIONAL_KEYS_LIMIT keys, four
	 * representative combinations otherwise), the required keys always
	 * present, list shapes skipping non-list combinations; UNDEF = pending
	 * exception */
	zv::Val getAllArrays() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::allArrays);
		if (Z_TYPE_P(memo) == IS_ARRAY) return zv::Val::copyOf(zv::Ref(memo));
		/* the twin's read order: $optionalKeys, $keyTypes, $isList, $valueTypes */
		zval *o = optionalKeys();
		zval *k = o != NULL ? keyTypes() : NULL;
		zval *l = k != NULL ? isListSlot() : NULL;
		zval *v = l != NULL ? valueTypes() : NULL;
		if (UNEXPECTED(v == NULL)) return zv::Val();

		zv::Arr optionalKeysCombinations;
		if (arrayCount(o) <= PT_CAT_POWER_SET_OPTIONAL_KEYS_LIMIT) {
			optionalKeysCombinations = powerSet(o);
		} else {
			optionalKeysCombinations = zv::Arr::create(4);
			optionalKeysCombinations.push(zv::Val(zv::Arr::empty()));
			optionalKeysCombinations.push(zv::Val(arraySlice(o, 0, 1)));
			optionalKeysCombinations.push(zv::Val(arraySlice(o, -1, 1)));
			optionalKeysCombinations.push(zv::Ref(o));
		}

		zv::Arr requiredKeys = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(k)));
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			if (inArrayStrictLong(o, i)) continue;
			requiredKeys.push(zv::Val::integer(i));
		}

		zend_long isListValue = trinaryOf(l);
		if (UNEXPECTED(isListValue < 0)) return zv::Val();
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();

		zv::Arr arrays = zv::Arr::create(optionalKeysCombinations.arrRef().size());
		for (zv::ArrayEntry combinationEntry : optionalKeysCombinations.arrRef()) {
			zval *combination = combinationEntry.value().deref().raw();
			/* $keys = array_merge($requiredKeys, $combination); sort($keys); */
			zv::Arr keys = zv::Arr::create(requiredKeys.arrRef().size() + zend_hash_num_elements(Z_ARRVAL_P(combination)));
			for (zv::ArrayEntry entry : requiredKeys.arrRef()) {
				keys.push(entry.value());
			}
			for (zv::ArrayEntry entry : zv::ArrRef(combination)) {
				keys.push(entry.value());
			}
			sortLongs(keys);

			if (isListValue == PT_TRI_YES) {
				/* array_keys($keys) !== $keys */
				bool sequential = true;
				zend_long expected = 0;
				for (zv::ArrayEntry entry : keys.arrRef()) {
					zval *key = entry.value().deref().raw();
					if (Z_TYPE_P(key) != IS_LONG || Z_LVAL_P(key) != expected) {
						sequential = false;
						break;
					}
					expected++;
				}
				if (!sequential) continue;
			}

			zend_long unsealedness = thisIsUnsealed();
			if (UNEXPECTED(unsealedness < 0)) return zv::Val();
			bool hasExtras = unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL;
			if (keys.arrRef().size() == 0 && hasExtras) {
				/* Variant with no explicit keys but real unsealed extras:
				 * the builder's getArray() would degrade this to a general
				 * ArrayType. Construct the CAT directly so the variant keeps
				 * its extras for downstream consumers (e.g. flattenTypes). */
				zval empty;
				ZVAL_EMPTY_ARRAY(&empty);
				zv::Val array = create(&empty, &empty, NULL, NULL, NULL, unsealed);
				if (UNEXPECTED(array.isUndef())) return zv::Val();
				arrays.push(std::move(array));
				continue;
			}

			zv::Val builder = builderCreateEmpty();
			if (UNEXPECTED(builder.isUndef())) return zv::Val();
			if (UNEXPECTED(!builderCall0(builder.raw(), PT_LC("disablearraydegradation")))) return zv::Val();
			for (zv::ArrayEntry entry : keys.arrRef()) {
				zend_long i = zv::Ref(entry.value().deref().raw()).asLong();
				zval *keyType = arrayIndexObject(k, i, "keyTypes");
				zval *valueType = keyType != NULL ? arrayIndexObject(v, i, "valueTypes") : NULL;
				if (UNEXPECTED(valueType == NULL)) return zv::Val();
				if (UNEXPECTED(!builderSet(builder.raw(), keyType, valueType))) return zv::Val();
			}
			if (hasExtras) {
				zval *unsealedKey, *unsealedValue;
				if (UNEXPECTED(!unsealedPair(unsealed, unsealedKey, unsealedValue))) return zv::Val();
				if (UNEXPECTED(!builderMakeUnsealed(builder.raw(), unsealedKey, unsealedValue))) return zv::Val();
			}

			zv::Val array = builderGetArray(builder.raw());
			if (UNEXPECTED(array.isUndef())) return zv::Val();
			if (!instanceof_function(Z_OBJCE_P(array.raw()), pt_ce_constant_array_type)) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			arrays.push(std::move(array));
		}

		zv::ObjRef(self).propAtWrite(slots::allArrays, zv::Val::copyOf(arrays.arrRef()));
		return zv::Val(std::move(arrays));
	}

	/* private powerSet(): every subset of $in, in the binary-counting order
	 * the twin's sprintf('%0{count}b') enumerates (the subset of the ones
	 * bit j from the left ↔ $in[$j]) */
	static zv::Arr powerSet(zval *in)
	{
		zend_long count = arrayCount(in);
		zend_long members = (zend_long) 1 << count;
		zv::Arr result = zv::Arr::create((uint32_t) members);
		for (zend_long i = 0; i < members; i++) {
			zv::Arr out = zv::Arr::create((uint32_t) count);
			for (zend_long j = 0; j < count; j++) {
				if (((i >> (count - 1 - j)) & 1) != 1) continue;
				zval *value = zend_hash_index_find(Z_ARRVAL_P(in), (zend_ulong) j);
				if (value == NULL) {
					/* $in[$j] of a non-list: null — the twin's warning-and-null read */
					out.push(zv::Val::null());
					continue;
				}
				out.push(zv::Ref(value));
			}
			result.push(zv::Val(std::move(out)));
		}
		return result;
	}

	zv::Val getKeyTypes() const
	{
		zval *k = keyTypes();
		return k == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(k));
	}

	zv::Val getValueTypes() const
	{
		zval *v = valueTypes();
		return v == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(v));
	}

	/* isset($this->optionalKeySet[$i]), the set memoized as
	 * array_flip($this->optionalKeys); false = pending exception */
	[[nodiscard]] bool isOptionalKey(zend_long i, bool &out) const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::optionalKeySet);
		if (Z_TYPE_P(memo) != IS_ARRAY) {
			zval *o = optionalKeys();
			if (UNEXPECTED(o == NULL)) return false;
			zv::Arr set = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(o)));
			for (zv::ArrayEntry entry : zv::ArrRef(o)) {
				zval *key = entry.value().deref().raw();
				/* $optionalKeys is a list<int>: array_flip() skips (with a
				 * warning) anything but int and string values */
				if (Z_TYPE_P(key) != IS_LONG && Z_TYPE_P(key) != IS_STRING) continue;
				zval position;
				ZVAL_LONG(&position, (zend_long) entry.indexKey());
				if (UNEXPECTED(!mapSet(set.table(), key, &position))) return false;
			}
			zv::ObjRef(self).propAtWrite(slots::optionalKeySet, zv::Val(std::move(set)));
			memo = OBJ_PROP_NUM(self, slots::optionalKeySet);
		}
		out = zend_hash_index_find(Z_ARRVAL_P(memo), (zend_ulong) i) != NULL;
		return true;
	}

	/* the keys sorted by their values (usort's stable sort), the values and
	 * optional keys following them; UNDEF = pending exception */
	zv::Val sortKeys() const
	{
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *n = v != NULL ? nextAutoIndexes() : NULL;
		zval *o = n != NULL ? optionalKeys() : NULL;
		zval *l = o != NULL ? isListSlot() : NULL;
		zval *u = l != NULL ? unsealedSlot() : NULL;
		if (UNEXPECTED(u == NULL)) return zv::Val();

		/* $indices = array_keys($this->keyTypes); usort($indices, fn ($a, $b) => value($a) <=> value($b)) */
		uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(k));
		zv::Arr values = zv::Arr::create(count);
		zv::Arr indices = zv::Arr::create(count);
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zv::Val value = keyValue(entry.value().deref().raw());
			if (UNEXPECTED(value.isUndef())) return zv::Val();
			values.push(std::move(value));
			indices.push(zv::Val::integer((zend_long) entry.indexKey()));
		}
		if (UNEXPECTED(!usortIndicesByValue(indices, values))) return zv::Val();

		zv::Arr newKeyTypes = zv::Arr::create(count);
		zv::Arr newValueTypes = zv::Arr::create(count);
		zv::ScratchTable indexMap(count);
		zend_long newIdx = 0;
		for (zv::ArrayEntry entry : indices.arrRef()) {
			zend_long oldIdx = zv::Ref(entry.value().deref().raw()).asLong();
			zval *keyType = arrayIndex(k, oldIdx, "keyTypes");
			zval *valueType = keyType != NULL ? arrayIndex(v, oldIdx, "valueTypes") : NULL;
			if (UNEXPECTED(valueType == NULL)) return zv::Val();
			newKeyTypes.push(zv::Ref(keyType));
			newValueTypes.push(zv::Ref(valueType));
			zval mapped;
			ZVAL_LONG(&mapped, newIdx);
			zend_hash_index_update(indexMap.table(), (zend_ulong) oldIdx, &mapped);
			newIdx++;
		}

		zv::Arr newOptionalKeys = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(o)));
		for (zv::ArrayEntry entry : zv::ArrRef(o)) {
			zval *oldIdx = entry.value().deref().raw();
			zval *mapped = Z_TYPE_P(oldIdx) == IS_LONG ? zend_hash_index_find(indexMap.table(), (zend_ulong) Z_LVAL_P(oldIdx)) : NULL;
			if (UNEXPECTED(mapped == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: an optional key is not an index of the key types");
				return zv::Val();
			}
			newOptionalKeys.push(zv::Ref(mapped));
		}
		sortLongs(newOptionalKeys);

		return thisRecreate(newKeyTypes.raw(), newValueTypes.raw(), n, newOptionalKeys.raw(), l, u);
	}

	/* the CompoundType callback (an IntersectionType excepted); for a sealed
	 * empty shape whether another shape is empty; else the per-key
	 * acceptance and'ed with the type being an array, an oversized array
	 * accepted unless refused outright, and — for a definite unsealed pair —
	 * the extra keys of a constant array refused by a sealed shape or
	 * accepted by the unsealed key/value types, the unsealed part of a
	 * non-constant array likewise; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool compound, isIntersection = false;
		if (UNEXPECTED(!isInstance(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound && UNEXPECTED(!pt_type_instanceof_ce(type, pt_ce_intersection_type, isIntersection))) return zv::Val();
		if (compound && !isIntersection) {
			zv::Args args{thisZv(), strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}

		zend_long isUnsealed = thisIsUnsealed();
		if (UNEXPECTED(isUnsealed < 0)) return zv::Val();
		zval *k = keyTypes();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		if (isUnsealed != PT_TRI_YES) {
			if (instanceof_function(Z_OBJCE_P(type), pt_ce_constant_array_type) && arrayCount(k) == 0) {
				zval *otherKeyTypes = slotOf(Z_OBJ_P(type), slots::keyTypes, "keyTypes");
				if (UNEXPECTED(otherKeyTypes == NULL)) return zv::Val();
				return pt_type_accepts_result(trinaryFromBoolean(arrayCount(otherKeyTypes) == 0));
			}
		}

		zv::Val result = checkOurKeys(type, strictTypes);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zv::Val isArray = pt_type_op(Z_OBJ_P(type), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray.isUndef())) return zv::Val();
		if (UNEXPECTED(trinaryOf(isArray.raw()) < 0)) return zv::Val();
		zval emptyReasons, isArrayResult;
		ZVAL_EMPTY_ARRAY(&emptyReasons);
		if (UNEXPECTED(!pt_accepts_result_create(&isArrayResult, isArray.raw(), &emptyReasons))) return zv::Val();
		result = pt_type_result_and(std::move(result), &isArrayResult);
		zval_ptr_dtor(&isArrayResult);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		if (Z_TYPE_P(unsealed) == IS_NULL) {
			zend_long oversized = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isoversizedarray"), 0, NULL);
			if (UNEXPECTED(oversized < 0)) return zv::Val();
			if (oversized == PT_TRI_YES) {
				zend_long value = pt_type_result_trinary(result.raw());
				if (UNEXPECTED(value < 0)) return zv::Val();
				if (value != PT_TRI_NO) return pt_type_accepts_result(PT_TRI_YES);
			}
			return result;
		}

		zend_long resultValue = pt_type_result_trinary(result.raw());
		if (UNEXPECTED(resultValue < 0)) return zv::Val();
		if (resultValue == PT_TRI_NO) return result;

		zval *unsealedKeyType, *unsealedValueType;
		if (UNEXPECTED(!unsealedPair(unsealed, unsealedKeyType, unsealedValueType))) return zv::Val();

		zend_long typeIsConstantArray = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
		if (UNEXPECTED(typeIsConstantArray < 0)) return zv::Val();

		if (isUnsealed == PT_TRI_NO) {
			if (typeIsConstantArray != PT_TRI_YES) {
				return pt_type_result_and(std::move(result), acceptsNoWithReason(zv::Val::string(PT_LC("Sealed array shape can only accept a constant array. Extra keys are not allowed."))).raw());
			}

			zv::Val constantArrays = pt_type_call_array(Z_OBJ_P(type), PT_LC("getconstantarrays"), 0, NULL);
			if (UNEXPECTED(constantArrays.isUndef())) return zv::Val();
			if (arrayCount(constantArrays.raw()) != 1) {
				zend_throw_exception(pt_class(PT_CLASS_SHOULD_NOT_HAPPEN), "Type with more than one constant array occurred, should have been eliminated with `instanceof CompoundType` above.", 0);
				return zv::Val();
			}
			zval *constantArray = arrayIndexObject(constantArrays.raw(), 0, "constantArrays");
			if (UNEXPECTED(constantArray == NULL)) return zv::Val();

			/* $keys[$otherKeyType->getValue()] = $otherKeyType; unset($keys[$keyType->getValue()]) for our keys */
			zv::Val otherKeyTypes = pt_type_call_array(Z_OBJ_P(constantArray), PT_LC("getkeytypes"), 0, NULL);
			if (UNEXPECTED(otherKeyTypes.isUndef())) return zv::Val();
			zv::Arr keys = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(otherKeyTypes.raw())));
			for (zv::ArrayEntry entry : zv::ArrRef(otherKeyTypes.raw())) {
				zval *otherKeyType = entry.value().deref().raw();
				zv::Val value = keyValue(otherKeyType);
				if (UNEXPECTED(value.isUndef() || !mapSet(keys.table(), value.raw(), otherKeyType))) return zv::Val();
			}
			for (zv::ArrayEntry entry : zv::ArrRef(k)) {
				zv::Val value = keyValue(entry.value().deref().raw());
				if (UNEXPECTED(value.isUndef())) return zv::Val();
				mapDel(keys.table(), value.raw());
			}

			for (zv::ArrayEntry entry : keys.arrRef()) {
				zv::Val description = describePrecise(entry.value().deref().raw());
				if (UNEXPECTED(description.isUndef())) return zv::Val();
				result = pt_type_result_and(std::move(result), acceptsNoWithReason(zv::Val::adoptString(zend_strpprintf(0, "Sealed array shape does not accept array with extra key %s.", ZSTR_VAL(zv::Ref(description.raw()).asString())))).raw());
				if (UNEXPECTED(result.isUndef())) return zv::Val();
			}

			zend_long otherUnsealed = pt_type_call_trinary(Z_OBJ_P(constantArray), PT_LC("isunsealed"), 0, NULL);
			if (UNEXPECTED(otherUnsealed < 0)) return zv::Val();
			if (otherUnsealed != PT_TRI_NO) {
				result = pt_type_result_and(std::move(result), acceptsNoWithReason(zv::Val::string(PT_LC("Sealed array shape does not accept unsealed array shape."))).raw());
			}

			return result;
		}

		if (typeIsConstantArray != PT_TRI_YES) {
			/* $result->and($unsealedKeyType->accepts($type->getIterableKeyType(), $strictTypes))
			 *   ->and($unsealedValueType->accepts($type->getIterableValueType(), $strictTypes)) */
			zv::Val typeKey = callType(Z_OBJ_P(type), PT_LC("getiterablekeytype"), 0, NULL);
			if (UNEXPECTED(typeKey.isUndef())) return zv::Val();
			zv::Args args{typeKey.raw(), strictTypes};
			zv::Val acceptsKey = pt_type_op(Z_OBJ_P(unsealedKeyType), PT_OP_ACCEPTS, 2, args);
			if (UNEXPECTED(acceptsKey.isUndef())) return zv::Val();
			result = pt_type_result_and(std::move(result), acceptsKey.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zv::Val typeValue = callType(Z_OBJ_P(type), PT_LC("getiterablevaluetype"), 0, NULL);
			if (UNEXPECTED(typeValue.isUndef())) return zv::Val();
			ZVAL_COPY_VALUE(&args[0], typeValue.raw());
			zv::Val acceptsValue = pt_type_op(Z_OBJ_P(unsealedValueType), PT_OP_ACCEPTS, 2, args);
			if (UNEXPECTED(acceptsValue.isUndef())) return zv::Val();
			return pt_type_result_and(std::move(result), acceptsValue.raw());
		}

		zv::Val constantArrays = pt_type_call_array(Z_OBJ_P(type), PT_LC("getconstantarrays"), 0, NULL);
		if (UNEXPECTED(constantArrays.isUndef())) return zv::Val();
		if (arrayCount(constantArrays.raw()) != 1) {
			zend_throw_exception(pt_class(PT_CLASS_SHOULD_NOT_HAPPEN), "Type with more than one constant array occurred, should have been eliminated with `instanceof CompoundType` above.", 0);
			return zv::Val();
		}
		zval *constantArray = arrayIndexObject(constantArrays.raw(), 0, "constantArrays");
		if (UNEXPECTED(constantArray == NULL)) return zv::Val();

		/* $keys[$otherKeyType->getValue()] = [$i, $otherKeyType]; unset for our keys */
		zv::Val otherKeyTypes = pt_type_call_array(Z_OBJ_P(constantArray), PT_LC("getkeytypes"), 0, NULL);
		if (UNEXPECTED(otherKeyTypes.isUndef())) return zv::Val();
		zv::Arr keys = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(otherKeyTypes.raw())));
		for (zv::ArrayEntry entry : zv::ArrRef(otherKeyTypes.raw())) {
			zval *otherKeyType = entry.value().deref().raw();
			zv::Val value = keyValue(otherKeyType);
			if (UNEXPECTED(value.isUndef())) return zv::Val();
			zv::Arr pair = zv::Arr::create(2);
			pair.push(zv::Val::integer((zend_long) entry.indexKey()));
			pair.push(zv::Ref(otherKeyType));
			zval pairRaw = pair.take();
			bool set = mapSet(keys.table(), value.raw(), &pairRaw);
			zval_ptr_dtor(&pairRaw);
			if (UNEXPECTED(!set)) return zv::Val();
		}
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zv::Val value = keyValue(entry.value().deref().raw());
			if (UNEXPECTED(value.isUndef())) return zv::Val();
			mapDel(keys.table(), value.raw());
		}

		zv::Val otherValueTypes;
		for (zv::ArrayEntry entry : keys.arrRef()) {
			zval *pair = entry.value().deref().raw();
			zval *iZv = zend_hash_index_find(Z_ARRVAL_P(pair), 0);
			zval *extraKeyType = zend_hash_index_find(Z_ARRVAL_P(pair), 1);
			ZEND_ASSERT(iZv != NULL && extraKeyType != NULL);
			zv::Args args{extraKeyType, strictTypes};
			zv::Val acceptsKey = pt_type_op(Z_OBJ_P(unsealedKeyType), PT_OP_ACCEPTS, 2, args);
			if (UNEXPECTED(acceptsKey.isUndef())) return zv::Val();
			acceptsKey = decorateReasons(acceptsKey.raw(), unsealedKeyReasonCallback, unsealedKeyType, extraKeyType);
			if (UNEXPECTED(acceptsKey.isUndef())) return zv::Val();
			bool reasonless;
			if (UNEXPECTED(!isNonYesWithoutReasons(acceptsKey.raw(), reasonless))) return zv::Val();
			if (reasonless) {
				acceptsKey = acceptsResultWithReason(acceptsKey.raw(), unsealedKeyReason(unsealedKeyType, extraKeyType, NULL));
				if (UNEXPECTED(acceptsKey.isUndef())) return zv::Val();
			}
			result = pt_type_result_and(std::move(result), acceptsKey.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();

			/* $extraValueType = $constantArray->getValueTypes()[$i] */
			if (otherValueTypes.isUndef()) {
				otherValueTypes = pt_type_call_array(Z_OBJ_P(constantArray), PT_LC("getvaluetypes"), 0, NULL);
				if (UNEXPECTED(otherValueTypes.isUndef())) return zv::Val();
			}
			zval *extraValueType = arrayIndexObject(otherValueTypes.raw(), Z_LVAL_P(iZv), "valueTypes");
			if (UNEXPECTED(extraValueType == NULL)) return zv::Val();
			ZVAL_COPY_VALUE(&args[0], extraValueType);
			zv::Val acceptsValue = pt_type_op(Z_OBJ_P(unsealedValueType), PT_OP_ACCEPTS, 2, args);
			if (UNEXPECTED(acceptsValue.isUndef())) return zv::Val();
			zv::Val captured = tripleOf(unsealedValueType, extraKeyType, extraValueType);
			acceptsValue = decorateReasons(acceptsValue.raw(), unsealedValueReasonCallback, captured.raw(), NULL);
			if (UNEXPECTED(acceptsValue.isUndef())) return zv::Val();
			if (UNEXPECTED(!isNonYesWithoutReasons(acceptsValue.raw(), reasonless))) return zv::Val();
			if (reasonless) {
				acceptsValue = acceptsResultWithReason(acceptsValue.raw(), unsealedValueReason(unsealedValueType, extraKeyType, extraValueType, NULL));
				if (UNEXPECTED(acceptsValue.isUndef())) return zv::Val();
			}
			result = pt_type_result_and(std::move(result), acceptsValue.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}

		/* $otherUnsealed = $constantArray->unsealed — the private slot of the
		 * other shape */
		if (UNEXPECTED(!instanceof_function(Z_OBJCE_P(constantArray), pt_ce_constant_array_type))) {
			zend_throw_error(NULL, "Cannot access private property %s::$unsealed", ZSTR_VAL(Z_OBJCE_P(constantArray)->name));
			return zv::Val();
		}
		zval *otherUnsealed = slotOf(Z_OBJ_P(constantArray), slots::unsealed, "unsealed", true);
		if (UNEXPECTED(otherUnsealed == NULL)) return zv::Val();
		if (Z_TYPE_P(otherUnsealed) != IS_NULL) {
			zend_long otherUnsealedness = pt_type_call_trinary(Z_OBJ_P(constantArray), PT_LC("isunsealed"), 0, NULL);
			if (UNEXPECTED(otherUnsealedness < 0)) return zv::Val();
			if (otherUnsealedness != PT_TRI_NO) {
				zval *otherUnsealedKeyType, *otherUnsealedValueType;
				if (UNEXPECTED(!unsealedPair(otherUnsealed, otherUnsealedKeyType, otherUnsealedValueType))) return zv::Val();
				zv::Args args{otherUnsealedKeyType, strictTypes};
				zv::Val acceptsUnsealedKey = pt_type_op(Z_OBJ_P(unsealedKeyType), PT_OP_ACCEPTS, 2, args);
				if (UNEXPECTED(acceptsUnsealedKey.isUndef())) return zv::Val();
				acceptsUnsealedKey = decorateReasons(acceptsUnsealedKey.raw(), unsealedKeyPairReasonCallback, unsealedKeyType, otherUnsealedKeyType);
				if (UNEXPECTED(acceptsUnsealedKey.isUndef())) return zv::Val();
				bool reasonless;
				if (UNEXPECTED(!isNonYesWithoutReasons(acceptsUnsealedKey.raw(), reasonless))) return zv::Val();
				if (reasonless) {
					acceptsUnsealedKey = acceptsResultWithReason(acceptsUnsealedKey.raw(), unsealedPairReason("key", unsealedKeyType, otherUnsealedKeyType, NULL));
					if (UNEXPECTED(acceptsUnsealedKey.isUndef())) return zv::Val();
				}
				result = pt_type_result_and(std::move(result), acceptsUnsealedKey.raw());
				if (UNEXPECTED(result.isUndef())) return zv::Val();

				ZVAL_COPY_VALUE(&args[0], otherUnsealedValueType);
				zv::Val acceptsUnsealedValue = pt_type_op(Z_OBJ_P(unsealedValueType), PT_OP_ACCEPTS, 2, args);
				if (UNEXPECTED(acceptsUnsealedValue.isUndef())) return zv::Val();
				acceptsUnsealedValue = decorateReasons(acceptsUnsealedValue.raw(), unsealedValuePairReasonCallback, unsealedValueType, otherUnsealedValueType);
				if (UNEXPECTED(acceptsUnsealedValue.isUndef())) return zv::Val();
				if (UNEXPECTED(!isNonYesWithoutReasons(acceptsUnsealedValue.raw(), reasonless))) return zv::Val();
				if (reasonless) {
					acceptsUnsealedValue = acceptsResultWithReason(acceptsUnsealedValue.raw(), unsealedPairReason("value", unsealedValueType, otherUnsealedValueType, NULL));
					if (UNEXPECTED(acceptsUnsealedValue.isUndef())) return zv::Val();
				}
				result = pt_type_result_and(std::move(result), acceptsUnsealedValue.raw());
				if (UNEXPECTED(result.isUndef())) return zv::Val();
			}
		}

		return result;
	}

	/* private: yes and'ed, key by key, with the offset's presence in $type
	 * (an absent required key refuses, an absent optional key is skipped, a
	 * possibly-present optional key counts as present) and the value's
	 * acceptance (a refusal returned at once); UNDEF = pending exception */
	zv::Val checkOurKeys(zval *type, bool strictTypes) const
	{
		zv::Val result = pt_type_accepts_result(PT_TRI_YES);
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		if (UNEXPECTED(result.isUndef() || v == NULL)) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			zval *valueType = arrayIndexObject(v, i, "valueTypes");
			if (UNEXPECTED(valueType == NULL)) return zv::Val();
			zv::Val hasOffsetValueType = pt_type_call(Z_OBJ_P(type), PT_LC("hasoffsetvaluetype"), 1, keyType);
			if (UNEXPECTED(hasOffsetValueType.isUndef())) return zv::Val();
			zend_long has = trinaryOf(hasOffsetValueType.raw());
			if (UNEXPECTED(has < 0)) return zv::Val();
			/* $hasOffsetValueType->yes() || !$type->isConstantArray()->yes() ? [] : ['Array %s have offset %s.'] */
			zv::Arr reasons = zv::Arr::empty();
			if (has != PT_TRI_YES) {
				zend_long typeIsConstantArray = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
				if (UNEXPECTED(typeIsConstantArray < 0)) return zv::Val();
				if (typeIsConstantArray == PT_TRI_YES) {
					zv::Val keyDescription = describeValue(keyType);
					if (UNEXPECTED(keyDescription.isUndef())) return zv::Val();
					reasons = zv::Arr::create(1);
					reasons.push(zv::Val::adoptString(zend_strpprintf(0, "Array %s have offset %s.", has == PT_TRI_NO ? "does not" : "might not", ZSTR_VAL(zv::Ref(keyDescription.raw()).asString()))));
				}
			}
			zval reasonsRaw = reasons.take();
			zval hasOffsetRaw;
			if (UNEXPECTED(!pt_accepts_result_create(&hasOffsetRaw, hasOffsetValueType.raw(), &reasonsRaw))) return zv::Val();
			zv::Val hasOffset = zv::Val::adopt(hasOffsetRaw);
			if (has == PT_TRI_NO) {
				bool optional;
				if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return zv::Val();
				if (optional) continue;
				return hasOffset;
			}
			if (has == PT_TRI_MAYBE) {
				bool optional;
				if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return zv::Val();
				if (optional) {
					hasOffset = pt_type_accepts_result(PT_TRI_YES);
					if (UNEXPECTED(hasOffset.isUndef())) return zv::Val();
				}
			}

			result = pt_type_result_and(std::move(result), hasOffset.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zv::Val otherValueType = callType(Z_OBJ_P(type), PT_LC("getoffsetvaluetype"), 1, keyType);
			if (UNEXPECTED(otherValueType.isUndef())) return zv::Val();
			zv::Val verbosity = pt_type_verbosity_recommended(valueType, otherValueType.raw());
			if (UNEXPECTED(verbosity.isUndef())) return zv::Val();
			zv::Args args{otherValueType.raw(), strictTypes};
			zv::Val acceptsValue = pt_type_op(Z_OBJ_P(valueType), PT_OP_ACCEPTS, 2, args);
			if (UNEXPECTED(acceptsValue.isUndef())) return zv::Val();
			zv::Val captured = quadOf(keyType, valueType, verbosity.raw(), otherValueType.raw());
			acceptsValue = decorateReasons(acceptsValue.raw(), offsetReasonCallback, captured.raw(), NULL);
			if (UNEXPECTED(acceptsValue.isUndef())) return zv::Val();
			bool reasonless;
			if (UNEXPECTED(!isNonYesWithoutReasons(acceptsValue.raw(), reasonless))) return zv::Val();
			if (reasonless) {
				zend_long typeIsConstantArray = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
				if (UNEXPECTED(typeIsConstantArray < 0)) return zv::Val();
				if (typeIsConstantArray == PT_TRI_YES) {
					acceptsValue = acceptsResultWithReason(acceptsValue.raw(), offsetReason(keyType, valueType, verbosity.raw(), otherValueType.raw(), NULL));
					if (UNEXPECTED(acceptsValue.isUndef())) return zv::Val();
				}
			}
			zend_long acceptsValueValue = pt_type_result_trinary(acceptsValue.raw());
			if (UNEXPECTED(acceptsValueValue < 0)) return zv::Val();
			if (acceptsValueValue == PT_TRI_NO) return acceptsValue;
			result = pt_type_result_and(std::move(result), acceptsValue.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}

		return result;
	}

	/* for another shape: an empty shape covers exactly the possibly-empty
	 * (a sealed one with a definite pair likewise), else key by key —
	 * presence in the other shape (an absent key possibly in its unsealed
	 * range counts as maybe), absence refusing a required key (with the
	 * sealed-shapes reason when both are sealed), the value covered — and,
	 * with definite pairs on both sides, the other's extra keys refused by
	 * a sealed shape (maybe when optional) or covered by the unsealed pair,
	 * plus the other's unsealed pair covered; for an ArrayType maybe,
	 * narrowed by the key and item types of a non-empty shape; the
	 * CompoundType callback; no otherwise; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_constant_array_type)) {
			zend_long thisUnsealedness = thisIsUnsealed();
			if (UNEXPECTED(thisUnsealedness < 0)) return zv::Val();
			zend_long typeUnsealedness = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isunsealed"), 0, NULL);
			if (UNEXPECTED(typeUnsealedness < 0)) return zv::Val();
			zval *unsealed = unsealedSlot();
			zval *typeUnsealed = unsealed != NULL ? slotOf(Z_OBJ_P(type), slots::unsealed, "unsealed", true) : NULL;
			if (UNEXPECTED(typeUnsealed == NULL)) return zv::Val();
			bool bothDefinite = Z_TYPE_P(unsealed) != IS_NULL && Z_TYPE_P(typeUnsealed) != IS_NULL;
			zval *k = keyTypes();
			zval *v = k != NULL ? valueTypes() : NULL;
			if (UNEXPECTED(v == NULL)) return zv::Val();

			if (arrayCount(k) == 0) {
				if (!bothDefinite || thisUnsealedness == PT_TRI_NO) {
					zend_long atLeastOnce = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
					if (UNEXPECTED(atLeastOnce < 0)) return zv::Val();
					return pt_type_new_is_super_type_of_result(trinaryNegate(atLeastOnce));
				}
				/* $this is unsealed with no known keys — fall through to extras/unsealed-part checks below */
			}

			zv::Arr results = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(k)) + 4);
			for (zv::ArrayEntry entry : zv::ArrRef(k)) {
				zend_long i = (zend_long) entry.indexKey();
				zval *keyType = entry.value().deref().raw();
				zend_long hasOffset = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("hasoffsetvaluetype"), 1, keyType);
				if (UNEXPECTED(hasOffset < 0)) return zv::Val();
				if (bothDefinite && hasOffset == PT_TRI_NO && typeUnsealedness == PT_TRI_YES) {
					zval *typeUnsealedKey = unsealedKeyOf(typeUnsealed);
					if (UNEXPECTED(typeUnsealedKey == NULL)) return zv::Val();
					zend_long covers = isSuperTypeOfValue(typeUnsealedKey, keyType);
					if (UNEXPECTED(covers < 0)) return zv::Val();
					if (covers != PT_TRI_NO) {
						hasOffset = PT_TRI_MAYBE;
					}
				}
				if (hasOffset == PT_TRI_NO) {
					bool optional;
					if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return zv::Val();
					if (!optional) {
						if (thisUnsealedness == PT_TRI_NO && typeUnsealedness == PT_TRI_NO) return sealedShapesNo(type);
						return pt_type_is_super_type_of_result(PT_TRI_NO);
					}
					zv::Val yes = pt_type_is_super_type_of_result(PT_TRI_YES);
					if (UNEXPECTED(yes.isUndef())) return zv::Val();
					results.push(std::move(yes));
					continue;
				} else if (hasOffset == PT_TRI_MAYBE) {
					bool optional;
					if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return zv::Val();
					if (!optional) {
						zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
						if (UNEXPECTED(maybe.isUndef())) return zv::Val();
						results.push(std::move(maybe));
					}
				}

				zv::Val otherValueType = callType(Z_OBJ_P(type), PT_LC("getoffsetvaluetype"), 1, keyType);
				if (UNEXPECTED(otherValueType.isUndef())) return zv::Val();
				bool isError;
				if (UNEXPECTED(!isInstance(otherValueType.raw(), pt_ce_error_type, isError))) return zv::Val();
				if (isError && bothDefinite && typeUnsealedness == PT_TRI_YES) {
					zval *typeUnsealedKey, *typeUnsealedValue;
					if (UNEXPECTED(!unsealedPair(typeUnsealed, typeUnsealedKey, typeUnsealedValue))) return zv::Val();
					otherValueType = zv::Val::copyOf(zv::Ref(typeUnsealedValue));
				}
				zval *valueType = arrayIndexObject(v, i, "valueTypes");
				if (UNEXPECTED(valueType == NULL)) return zv::Val();
				zv::Val isValueSuperType = pt_type_op(Z_OBJ_P(valueType), PT_OP_IS_SUPER_TYPE_OF, 1, otherValueType.raw());
				if (UNEXPECTED(isValueSuperType.isUndef())) return zv::Val();
				zend_long valueSuper = pt_type_result_trinary(isValueSuperType.raw());
				if (UNEXPECTED(valueSuper < 0)) return zv::Val();
				if (valueSuper == PT_TRI_NO) return decorateReasons(isValueSuperType.raw(), offsetPrefixReasonCallback, keyType, NULL);
				results.push(std::move(isValueSuperType));
			}

			if (bothDefinite) {
				/* $thisKeyValues[$thisKeyType->getValue()] = true */
				zv::Arr thisKeyValues = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(k)));
				for (zv::ArrayEntry entry : zv::ArrRef(k)) {
					zv::Val value = keyValue(entry.value().deref().raw());
					zval trueZv;
					ZVAL_TRUE(&trueZv);
					if (UNEXPECTED(value.isUndef() || !mapSet(thisKeyValues.table(), value.raw(), &trueZv))) return zv::Val();
				}

				zv::Val typeKeyTypes = pt_type_call_array(Z_OBJ_P(type), PT_LC("getkeytypes"), 0, NULL);
				if (UNEXPECTED(typeKeyTypes.isUndef())) return zv::Val();
				zv::Val typeValueTypes;
				for (zv::ArrayEntry entry : zv::ArrRef(typeKeyTypes.raw())) {
					zend_long i = (zend_long) entry.indexKey();
					zval *typeKey = entry.value().deref().raw();
					zv::Val value = keyValue(typeKey);
					if (UNEXPECTED(value.isUndef())) return zv::Val();
					if (mapFind(thisKeyValues.table(), value.raw()) != NULL) continue;

					if (thisUnsealedness == PT_TRI_NO) {
						bool typeOptional;
						if (UNEXPECTED(!otherIsOptionalKey(type, i, typeOptional))) return zv::Val();
						if (!typeOptional) {
							if (typeUnsealedness == PT_TRI_NO) return sealedShapesNo(type);
							return pt_type_is_super_type_of_result(PT_TRI_NO);
						}
						zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
						if (UNEXPECTED(maybe.isUndef())) return zv::Val();
						results.push(std::move(maybe));
						continue;
					}

					zval *thisUnsealedKey, *thisUnsealedValue;
					if (UNEXPECTED(!unsealedPair(unsealed, thisUnsealedKey, thisUnsealedValue))) return zv::Val();
					zv::Val keyCheck = pt_type_op(Z_OBJ_P(thisUnsealedKey), PT_OP_IS_SUPER_TYPE_OF, 1, typeKey);
					if (UNEXPECTED(keyCheck.isUndef())) return zv::Val();
					zend_long keyCheckValue = pt_type_result_trinary(keyCheck.raw());
					if (UNEXPECTED(keyCheckValue < 0)) return zv::Val();
					if (keyCheckValue == PT_TRI_NO) {
						bool typeOptional;
						if (UNEXPECTED(!otherIsOptionalKey(type, i, typeOptional))) return zv::Val();
						if (typeOptional) {
							zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
							if (UNEXPECTED(maybe.isUndef())) return zv::Val();
							results.push(std::move(maybe));
							continue;
						}
						return pt_type_is_super_type_of_result(PT_TRI_NO);
					}
					if (typeValueTypes.isUndef()) {
						typeValueTypes = pt_type_call_array(Z_OBJ_P(type), PT_LC("getvaluetypes"), 0, NULL);
						if (UNEXPECTED(typeValueTypes.isUndef())) return zv::Val();
					}
					zval *typeValue = arrayIndexObject(typeValueTypes.raw(), i, "valueTypes");
					if (UNEXPECTED(typeValue == NULL)) return zv::Val();
					zv::Val valueCheck = pt_type_op(Z_OBJ_P(thisUnsealedValue), PT_OP_IS_SUPER_TYPE_OF, 1, typeValue);
					if (UNEXPECTED(valueCheck.isUndef())) return zv::Val();
					zend_long valueCheckValue = pt_type_result_trinary(valueCheck.raw());
					if (UNEXPECTED(valueCheckValue < 0)) return zv::Val();
					if (valueCheckValue == PT_TRI_NO) {
						bool typeOptional;
						if (UNEXPECTED(!otherIsOptionalKey(type, i, typeOptional))) return zv::Val();
						if (typeOptional) {
							zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
							if (UNEXPECTED(maybe.isUndef())) return zv::Val();
							results.push(std::move(maybe));
							continue;
						}
						return pt_type_is_super_type_of_result(PT_TRI_NO);
					}
					zv::Val combined = pt_type_result_and(std::move(keyCheck), valueCheck.raw());
					if (UNEXPECTED(combined.isUndef())) return zv::Val();
					results.push(std::move(combined));
				}

				if (typeUnsealedness == PT_TRI_YES) {
					if (thisUnsealedness == PT_TRI_NO) {
						zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
						if (UNEXPECTED(maybe.isUndef())) return zv::Val();
						results.push(std::move(maybe));
					} else {
						zval *thisUnsealedKey, *thisUnsealedValue, *typeUnsealedKey, *typeUnsealedValue;
						if (UNEXPECTED(!unsealedPair(unsealed, thisUnsealedKey, thisUnsealedValue) || !unsealedPair(typeUnsealed, typeUnsealedKey, typeUnsealedValue))) {
							return zv::Val();
						}
						zv::Val keyCheck = pt_type_op(Z_OBJ_P(thisUnsealedKey), PT_OP_IS_SUPER_TYPE_OF, 1, typeUnsealedKey);
						if (UNEXPECTED(keyCheck.isUndef())) return zv::Val();
						results.push(std::move(keyCheck));
						zv::Val valueCheck = pt_type_op(Z_OBJ_P(thisUnsealedValue), PT_OP_IS_SUPER_TYPE_OF, 1, typeUnsealedValue);
						if (UNEXPECTED(valueCheck.isUndef())) return zv::Val();
						results.push(std::move(valueCheck));
					}
				}
			}

			/* IsSuperTypeOfResult::createYes()->and(...$results) */
			zv::Val yes = pt_type_is_super_type_of_result(PT_TRI_YES);
			if (UNEXPECTED(yes.isUndef())) return zv::Val();
			return pt_is_super_type_of_result_spread(Z_OBJ_P(yes.raw()), true, results.table());
		}

		if (instanceof_function(Z_OBJCE_P(type), pt_ce_array_type)) {
			zv::Val result = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
			zval *k = keyTypes();
			if (UNEXPECTED(result.isUndef() || k == NULL)) return zv::Val();
			if (arrayCount(k) == 0) return result;

			zv::Val keyType = thisGetKeyType();
			if (UNEXPECTED(keyType.isUndef())) return zv::Val();
			zv::Val typeKeyType = pt_array_type_get_key_type(Z_OBJ_P(type));
			if (UNEXPECTED(typeKeyType.isUndef())) return zv::Val();
			zv::Val isKeySuperType = pt_type_op(Z_OBJ_P(keyType.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, typeKeyType.raw());
			if (UNEXPECTED(isKeySuperType.isUndef())) return zv::Val();
			zend_long keySuper = pt_type_result_trinary(isKeySuperType.raw());
			if (UNEXPECTED(keySuper < 0)) return zv::Val();
			if (keySuper == PT_TRI_NO) return isKeySuperType;

			zv::Val itemType = thisGetItemType();
			if (UNEXPECTED(itemType.isUndef())) return zv::Val();
			zv::Val typeItemType = pt_array_type_get_item_type(Z_OBJ_P(type));
			if (UNEXPECTED(typeItemType.isUndef())) return zv::Val();
			zv::Val isItemSuperType = pt_type_op(Z_OBJ_P(itemType.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, typeItemType.raw());
			if (UNEXPECTED(isItemSuperType.isUndef())) return zv::Val();
			/* $result->and($isKeySuperType, $this->getItemType()->isSuperTypeOf(...)) */
			zv::Args args{isKeySuperType.raw(), isItemSuperType.raw()};
			return pt_type_op(Z_OBJ_P(result.raw()), PT_OP_AND, 2, args);
		}

		bool compound;
		if (UNEXPECTED(!isInstance(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, thisZv());

		return pt_type_is_super_type_of_result(PT_TRI_NO);
	}

	/* private: the reason rendered lazily (only when the reason is
	 * actually rendered, never during the hot comparisons whose reasons are
	 * discarded); UNDEF = pending exception */
	zv::Val sealedArrayShapesCannotBeIntersectedReason(zval *type) const
	{
		zv::Val thisDescription = thisDescribeValue();
		if (UNEXPECTED(thisDescription.isUndef())) return zv::Val();
		zv::Val typeDescription = describeValue(type);
		if (UNEXPECTED(typeDescription.isUndef())) return zv::Val();
		return zv::Val::adoptString(zend_strpprintf(0, "Sealed array shapes %s and %s cannot be intersected. Unseal at least one of them with ... syntax. Learn more: %s", ZSTR_VAL(zv::Ref(thisDescription.raw()).asString()), ZSTR_VAL(zv::Ref(typeDescription.raw()).asString()), PT_CAT_UNSEALED_ARRAY_SHAPES_LINK));
	}

	/* false for an integer; for a possibly-empty shape false against a
	 * non-empty iterable, the loose comparison of the constant scalar
	 * values with []; new BooleanType() otherwise; UNDEF = pending exception */
	zv::Val looseCompare(zval *type) const
	{
		zend_long isInteger = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_INTEGER, 0, NULL);
		if (UNEXPECTED(isInteger < 0)) return zv::Val();
		if (isInteger == PT_TRI_YES) return constantBoolean(false);

		zend_long atLeastOnce = thisIsIterableAtLeastOnce();
		if (UNEXPECTED(atLeastOnce < 0)) return zv::Val();
		if (atLeastOnce == PT_TRI_NO) {
			zend_long typeAtLeastOnce = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
			if (UNEXPECTED(typeAtLeastOnce < 0)) return zv::Val();
			if (typeAtLeastOnce == PT_TRI_YES) return constantBoolean(false);

			zv::Val constantScalarValues = pt_type_call_array(Z_OBJ_P(type), PT_LC("getconstantscalarvalues"), 0, NULL);
			if (UNEXPECTED(constantScalarValues.isUndef())) return zv::Val();
			uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(constantScalarValues.raw()));
			if (count > 0) {
				zend_long *results = (zend_long *) safe_emalloc(count, sizeof(zend_long), 0);
				uint32_t n = 0;
				zval emptyArray;
				ZVAL_EMPTY_ARRAY(&emptyArray);
				for (zv::ArrayEntry entry : zv::ArrRef(constantScalarValues.raw())) {
					/* $constantScalarValue == [] */
					int comparison = zend_compare(entry.value().deref().raw(), &emptyArray);
					if (UNEXPECTED(EG(exception))) {
						efree(results);
						return zv::Val();
					}
					results[n++] = trinaryFromBoolean(comparison == 0);
				}
				zend_long identity = trinaryExtremeIdentity(results, n);
				efree(results);
				if (UNEXPECTED(identity < 0)) return zv::Val();
				return trinaryToBooleanType(identity);
			}
		}

		return booleanType();
	}

	/* another shape with pairwise equal keys and values, the same optional
	 * keys, the same extras-or-not and equal extras; false = pending
	 * exception */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_constant_array_type)) {
			out = false;
			return true;
		}
		zend_object *other = Z_OBJ_P(type);
		zval *k = keyTypes();
		zval *otherK = k != NULL ? slotOf(other, slots::keyTypes, "keyTypes") : NULL;
		if (UNEXPECTED(otherK == NULL)) return false;
		if (arrayCount(k) != arrayCount(otherK)) {
			out = false;
			return true;
		}

		zval *v = valueTypes();
		zval *otherV = v != NULL ? slotOf(other, slots::valueTypes, "valueTypes") : NULL;
		if (UNEXPECTED(otherV == NULL)) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			zval *valueType = arrayIndexObject(v, i, "valueTypes");
			zval *otherValueType = valueType != NULL ? arrayIndexObject(otherV, i, "valueTypes") : NULL;
			if (UNEXPECTED(otherValueType == NULL)) return false;
			if (!pt_call_type_equals(valueType, otherValueType)) {
				if (UNEXPECTED(EG(exception))) return false;
				out = false;
				return true;
			}
			zval *otherKeyType = arrayIndexObject(otherK, i, "keyTypes");
			if (UNEXPECTED(otherKeyType == NULL)) return false;
			if (!pt_call_type_equals(keyType, otherKeyType)) {
				if (UNEXPECTED(EG(exception))) return false;
				out = false;
				return true;
			}
		}

		zval *o = optionalKeys();
		zval *otherO = o != NULL ? slotOf(other, slots::optionalKeys, "optionalKeys") : NULL;
		if (UNEXPECTED(otherO == NULL)) return false;
		if (!zend_is_identical(o, otherO)) {
			out = false;
			return true;
		}

		/* Both `unsealed === null` (legacy / pre-bleeding-edge, where
		 * `isUnsealed()` answers `Maybe`) and `unsealed === [explicitNever,
		 * explicitNever]` (the fresh bleeding-edge sealed marker, where
		 * `isUnsealed()` answers `No`) mean "no real extras". Treat them as
		 * equivalent here — use `!isUnsealed()->yes()` rather than
		 * `isUnsealed()->no()`, otherwise a legacy-null shape and a
		 * marker-sealed shape compare unequal. Only compare the actual
		 * extras when both sides genuinely have them. */
		zend_long thisUnsealedness = thisIsUnsealed();
		if (UNEXPECTED(thisUnsealedness < 0)) return false;
		zend_long otherUnsealedness = pt_type_call_trinary(other, PT_LC("isunsealed"), 0, NULL);
		if (UNEXPECTED(otherUnsealedness < 0)) return false;
		bool thisHasExtras = thisUnsealedness == PT_TRI_YES;
		bool otherHasExtras = otherUnsealedness == PT_TRI_YES;
		if (thisHasExtras != otherHasExtras) {
			out = false;
			return true;
		}

		zval *unsealed = unsealedSlot();
		zval *otherUnsealed = unsealed != NULL ? slotOf(other, slots::unsealed, "unsealed", true) : NULL;
		if (UNEXPECTED(otherUnsealed == NULL)) return false;
		if (thisHasExtras && Z_TYPE_P(unsealed) != IS_NULL && Z_TYPE_P(otherUnsealed) != IS_NULL) {
			zval *thisKey, *thisValue, *otherKey, *otherValue;
			if (UNEXPECTED(!unsealedPair(unsealed, thisKey, thisValue) || !unsealedPair(otherUnsealed, otherKey, otherValue))) return false;
			if (!pt_call_type_equals(thisKey, otherKey)) {
				if (UNEXPECTED(EG(exception))) return false;
				out = false;
				return true;
			}
			if (!pt_call_type_equals(thisValue, otherValue)) {
				if (UNEXPECTED(EG(exception))) return false;
				out = false;
				return true;
			}
		}

		out = true;
		return true;
	}

	/* RecursionGuard::run($this, fn): the certainties of the callable
	 * [class-or-object, method] readings and'ed (maybe when a named method
	 * does not exist), no for none; no when the guard trips (an ErrorType);
	 * -1 = pending exception */
	[[nodiscard]] zend_long isCallable() const
	{
		zv::Val callback = pt_type_native_callback(isCallableCallback, thisZv(), NULL);
		if (UNEXPECTED(callback.isUndef())) return -1;
		zv::Val result = pt_type_recursion_guard_run(thisZv(), callback.raw());
		if (UNEXPECTED(result.isUndef())) return -1;
		bool isError;
		if (UNEXPECTED(!isInstance(result.raw(), pt_ce_error_type, isError))) return -1;
		if (isError) return PT_TRI_NO;
		return trinaryOf(result.raw());
	}

	/* the acceptors of every callable reading: trivial for an unknown or
	 * uncertain one, InaccessibleMethod for one the scope cannot call, the
	 * method's variants otherwise; throws for a non-callable shape; UNDEF =
	 * pending exception */
	zv::Val getCallableParametersAcceptors(zval *scope) const
	{
		zv::Val typeAndMethodNames = thisFindTypeAndMethodNames();
		if (UNEXPECTED(typeAndMethodNames.isUndef())) return zv::Val();
		if (arrayCount(typeAndMethodNames.raw()) == 0) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		zv::Arr acceptors = zv::Arr::create(arrayCount(typeAndMethodNames.raw()));
		for (zv::ArrayEntry entry : zv::ArrRef(typeAndMethodNames.raw())) {
			zval *typeAndMethodName = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(typeAndMethodName) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: findTypeAndMethodNames() must return ConstantArrayTypeAndMethod instances");
				return zv::Val();
			}
			zend_object *tam = Z_OBJ_P(typeAndMethodName);
			zv::Val unknown = pt_type_call(tam, PT_LC("isunknown"), 0, NULL);
			if (UNEXPECTED(unknown.isUndef())) return zv::Val();
			bool trivial = zend_is_true(unknown.raw());
			if (!trivial) {
				zend_long certainty = pt_type_call_trinary(tam, PT_LC("getcertainty"), 0, NULL);
				if (UNEXPECTED(certainty < 0)) return zv::Val();
				trivial = certainty != PT_TRI_YES;
			}
			if (trivial) {
				zv::Val acceptor = pt_trivial_parameters_acceptor_new();
				if (UNEXPECTED(acceptor.isUndef())) return zv::Val();
				acceptors.push(std::move(acceptor));
				continue;
			}

			zv::Val type = callType(tam, PT_LC("gettype"), 0, NULL);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			zv::Val methodName = pt_type_call(tam, PT_LC("getmethod"), 0, NULL);
			if (UNEXPECTED(methodName.isUndef())) return zv::Val();
			zv::Args args{methodName.raw(), scope};
			zv::Val method = pt_type_call(Z_OBJ_P(type.raw()), PT_LC("getmethod"), 2, args);
			if (UNEXPECTED(method.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(method.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getMethod() must return an object");
				return zv::Val();
			}

			zv::Val canCall = pt_type_call(Z_OBJ_P(scope), PT_LC("cancallmethod"), 1, method.raw());
			if (UNEXPECTED(canCall.isUndef())) return zv::Val();
			if (!zend_is_true(canCall.raw())) {
				zv::Val inaccessible = pt_type_new(PT_CLASS_INACCESSIBLE_METHOD, 1, method.raw());
				if (UNEXPECTED(inaccessible.isUndef())) return zv::Val();
				acceptors.push(std::move(inaccessible));
				continue;
			}

			zv::Val variants = pt_extended_method_reflection_call(method.raw(), PT_MR_GET_VARIANTS);
			if (UNEXPECTED(variants.isUndef())) return zv::Val();
			ZVAL_COPY_VALUE(&args[0], method.raw());
			ZVAL_COPY_VALUE(&args[1], variants.raw());
			zv::Val callableVariants = pt_type_call_static(PT_CLASS_FUNCTION_CALLABLE_VARIANT, PT_LC("createfromvariants"), 2, args);
			if (UNEXPECTED(callableVariants.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(callableVariants.raw()).isArray())) {
				zend_type_error("phpstan_turbo: FunctionCallableVariant::createFromVariants() must return array");
				return zv::Val();
			}
			for (zv::ArrayEntry variant : zv::ArrRef(callableVariants.raw())) {
				acceptors.push(variant.value());
			}
		}

		return zv::Val(std::move(acceptors));
	}

	zv::Val findTypeAndMethodNames() const
	{
		bool hasNonExistentMethod = false;
		return doFindTypeAndMethodNames(hasNonExistentMethod);
	}

	/* private: the [class-or-object, method] readings of the shape —
	 * exactly the keys 0 and 1 of a sealed shape (an unsealed one may draw a
	 * missing slot from its extras when they can hold it), unknown for
	 * non-constant method names or a non-object class-or-object type, else
	 * one concrete reading per existing method name (its certainty lowered
	 * for a possibly-static-only, optional-key or unsealed reading); UNDEF =
	 * pending exception */
	zv::Val doFindTypeAndMethodNames(bool &hasNonExistentMethod) const
	{
		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		bool isUnsealed = unsealedness == PT_TRI_YES;
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		if (UNEXPECTED(v == NULL)) return zv::Val();

		/* Sealed: must have exactly the two callable slots, no more, no less.
		 * Unsealed: explicit keys may cover 0, 1, both, or neither — but any
		 * explicit key outside {0, 1} immediately disqualifies, because the
		 * callable shape `[classOrObject, method]` has no room for other
		 * keys. */
		zend_long keyTypesCount = arrayCount(k);
		if (!isUnsealed && keyTypesCount != 2) return zv::Val(zv::Arr::empty());
		if (keyTypesCount > 2) return zv::Val(zv::Arr::empty());

		zv::Val zero = pt_type_new_constant_integer(0);
		zv::Val one = pt_type_new_constant_integer(1);
		if (UNEXPECTED(zero.isUndef() || one.isUndef())) return zv::Val();
		zv::Val classOrObject, method;
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			zend_long coversZero = isSuperTypeOfValue(keyType, zero.raw());
			if (UNEXPECTED(coversZero < 0)) return zv::Val();
			if (coversZero == PT_TRI_YES) {
				zval *valueType = arrayIndexObject(v, i, "valueTypes");
				if (UNEXPECTED(valueType == NULL)) return zv::Val();
				classOrObject = zv::Val::copyOf(zv::Ref(valueType));
				continue;
			}
			zend_long coversOne = isSuperTypeOfValue(keyType, one.raw());
			if (UNEXPECTED(coversOne < 0)) return zv::Val();
			if (coversOne == PT_TRI_YES) {
				zval *valueType = arrayIndexObject(v, i, "valueTypes");
				if (UNEXPECTED(valueType == NULL)) return zv::Val();
				method = zv::Val::copyOf(zv::Ref(valueType));
				continue;
			}
			/* Explicit key is something other than 0 or 1 — not callable. */
			return zv::Val(zv::Arr::empty());
		}

		/* Try to fill missing callable slots from the unsealed extras: an
		 * unsealed array `array{0: object, ...<int, string>}` *might* turn
		 * into a callable if the actual value carries a `1 => 'method'`
		 * extra. Require that the unsealed key range covers the missing
		 * slot and that the unsealed value type can overlap with the type
		 * required for that slot (object|class-string for key 0,
		 * non-falsy-string for key 1) — otherwise no concrete value of this
		 * CAT can ever be callable. */
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		if (isUnsealed && Z_TYPE_P(unsealed) != IS_NULL) {
			zval *unsealedKey, *unsealedValue;
			if (UNEXPECTED(!unsealedPair(unsealed, unsealedKey, unsealedValue))) return zv::Val();

			if (classOrObject.isUndef()) {
				zend_long coversZero = isSuperTypeOfValue(unsealedKey, zero.raw());
				if (UNEXPECTED(coversZero < 0)) return zv::Val();
				if (coversZero == PT_TRI_NO) return zv::Val(zv::Arr::empty());
				zv::Val objectWithoutClass = objectWithoutClassType();
				zv::Val classString = classStringType();
				if (UNEXPECTED(objectWithoutClass.isUndef() || classString.isUndef())) return zv::Val();
				zv::Val expected = combinator2(PT_LC("union"), objectWithoutClass.raw(), classString.raw());
				if (UNEXPECTED(expected.isUndef())) return zv::Val();
				zend_long covers = isSuperTypeOfValue(expected.raw(), unsealedValue);
				if (UNEXPECTED(covers < 0)) return zv::Val();
				if (covers == PT_TRI_NO) return zv::Val(zv::Arr::empty());
				classOrObject = zv::Val::copyOf(zv::Ref(unsealedValue));
			}

			if (method.isUndef()) {
				zend_long coversOne = isSuperTypeOfValue(unsealedKey, one.raw());
				if (UNEXPECTED(coversOne < 0)) return zv::Val();
				if (coversOne == PT_TRI_NO) return zv::Val(zv::Arr::empty());
				zv::Val string = stringType();
				zval nonFalsyRaw;
				zv::Val nonFalsy = pt_accessory_non_falsy_string_type_new(&nonFalsyRaw) ? zv::Val::adopt(nonFalsyRaw) : zv::Val();
				if (UNEXPECTED(string.isUndef() || nonFalsy.isUndef())) return zv::Val();
				zv::Val expected = combinator2(PT_LC("intersect"), string.raw(), nonFalsy.raw());
				if (UNEXPECTED(expected.isUndef())) return zv::Val();
				zend_long covers = isSuperTypeOfValue(expected.raw(), unsealedValue);
				if (UNEXPECTED(covers < 0)) return zv::Val();
				if (covers == PT_TRI_NO) return zv::Val(zv::Arr::empty());
				method = zv::Val::copyOf(zv::Ref(unsealedValue));
			}
		}

		if (classOrObject.isUndef() || method.isUndef()) return zv::Val(zv::Arr::empty());

		/* [$classOrObject, $methods] = [$classOrObject, $method] */
		zv::Val methodConstantStrings = pt_type_call_array(Z_OBJ_P(method.raw()), PT_LC("getconstantstrings"), 0, NULL);
		if (UNEXPECTED(methodConstantStrings.isUndef())) return zv::Val();
		if (arrayCount(methodConstantStrings.raw()) == 0) return unknownTypeAndMethod();

		zv::Val type = callType(Z_OBJ_P(classOrObject.raw()), PT_LC("getobjecttypeorclassstringobjecttype"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zend_long isObject = pt_type_call_trinary(Z_OBJ_P(type.raw()), PT_LC("isobject"), 0, NULL);
		if (UNEXPECTED(isObject < 0)) return zv::Val();
		if (isObject != PT_TRI_YES) return unknownTypeAndMethod();

		zv::Arr typeAndMethods = zv::Arr::create(arrayCount(methodConstantStrings.raw()));
		zv::Val phpVersion = pt_type_call_static(PT_CLASS_PHP_VERSION_STATIC_ACCESSOR, PT_LC("getinstance"), 0, NULL);
		if (UNEXPECTED(phpVersion.isUndef() || !zv::Ref(phpVersion.raw()).isObject())) {
			if (!EG(exception)) {
				zend_type_error("phpstan_turbo: PhpVersionStaticAccessor::getInstance() must return an object");
			}
			return zv::Val();
		}
		/* $methods->getConstantStrings() — the twin calls it again for the loop */
		zv::Val methodNames = pt_type_call_array(Z_OBJ_P(method.raw()), PT_LC("getconstantstrings"), 0, NULL);
		if (UNEXPECTED(methodNames.isUndef())) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(methodNames.raw())) {
			zval *methodName = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(methodName) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: getConstantStrings() must return %s instances", ZSTR_VAL(pt_ce_constant_string_type->name));
				return zv::Val();
			}
			zv::Val name = pt_type_call(Z_OBJ_P(methodName), PT_LC("getvalue"), 0, NULL);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			zend_long has = pt_type_call_trinary(Z_OBJ_P(type.raw()), PT_LC("hasmethod"), 1, name.raw());
			if (UNEXPECTED(has < 0)) return zv::Val();
			if (has == PT_TRI_NO) {
				hasNonExistentMethod = true;
				continue;
			}

			if (has == PT_TRI_YES) {
				bool supports;
				if (UNEXPECTED(!pt_php_version_answer(phpVersion.raw(), PT_PHP_VERSION_SUPPORTS_CALLABLE_INSTANCE_METHODS, supports))) return zv::Val();
				if (!supports) {
					zend_long isString = pt_type_op_trinary(Z_OBJ_P(classOrObject.raw()), PT_OP_IS_STRING, 0, NULL);
					if (UNEXPECTED(isString < 0)) return zv::Val();
					if (isString == PT_TRI_YES) {
						zv::Val outOfClassScope = pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL);
						if (UNEXPECTED(outOfClassScope.isUndef())) return zv::Val();
						zv::Args args{name.raw(), outOfClassScope.raw()};
						zv::Val methodReflection = pt_type_call(Z_OBJ_P(type.raw()), PT_LC("getmethod"), 2, args);
						if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();
						if (UNEXPECTED(!zv::Ref(methodReflection.raw()).isObject())) {
							zend_type_error("phpstan_turbo: getMethod() must return an object");
							return zv::Val();
						}
						zv::Val isStatic = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_IS_STATIC);
						if (UNEXPECTED(isStatic.isUndef())) return zv::Val();
						if (!zend_is_true(isStatic.raw())) continue;
					} else if (isString == PT_TRI_MAYBE) {
						has &= PT_TRI_MAYBE;
					}
				}
			}

			bool optionalZero, optionalOne = false;
			if (UNEXPECTED(!thisIsOptionalKey(0, optionalZero))) return zv::Val();
			if (!optionalZero && UNEXPECTED(!thisIsOptionalKey(1, optionalOne))) return zv::Val();
			if (optionalZero || optionalOne) {
				has &= PT_TRI_MAYBE;
			}

			/* Unsealed: the actual value may carry extras beyond keys 0/1,
			 * which would void the callable shape. The CAT itself describes
			 * "zero or more extras", so callable-ness is uncertain. */
			if (isUnsealed) {
				has &= PT_TRI_MAYBE;
			}

			zv::Args args{type.raw(), name.raw(), pt_trinary_singleton(has)};
			zv::Val concrete = pt_type_call_static(PT_CLASS_CONSTANT_ARRAY_TYPE_AND_METHOD, PT_LC("createconcrete"), 3, args);
			if (UNEXPECTED(concrete.isUndef())) return zv::Val();
			typeAndMethods.push(std::move(concrete));
		}

		return zv::Val(std::move(typeAndMethods));
	}

	/* the offset's array key (narrowed by the allowed array keys when it
	 * has none; no when nothing remains) looked up recursively; -1 =
	 * pending exception */
	zend_long hasOffsetValueType(zval *offsetType) const
	{
		zv::Val offsetArrayKeyType = callType(Z_OBJ_P(offsetType), PT_LC("toarraykey"), 0, NULL);
		if (UNEXPECTED(offsetArrayKeyType.isUndef())) return -1;
		bool isError;
		if (UNEXPECTED(!isInstance(offsetArrayKeyType.raw(), pt_ce_error_type, isError))) return -1;
		if (isError) {
			zv::Val allowedArrayKeys = pt_type_call_static(PT_CLASS_ALLOWED_ARRAY_KEYS_TYPES, PT_LC("gettype"), 0, NULL);
			if (UNEXPECTED(allowedArrayKeys.isUndef())) return -1;
			zv::Val intersected = combinator2(PT_LC("intersect"), allowedArrayKeys.raw(), offsetType);
			if (UNEXPECTED(intersected.isUndef() || !zv::Ref(intersected.raw()).isObject())) return -1;
			offsetArrayKeyType = callType(Z_OBJ_P(intersected.raw()), PT_LC("toarraykey"), 0, NULL);
			if (UNEXPECTED(offsetArrayKeyType.isUndef())) return -1;
			if (instanceof_function(Z_OBJCE_P(offsetArrayKeyType.raw()), pt_ce_never_type)) return PT_TRI_NO;
		}

		return recursiveHasOffsetValueType(offsetArrayKeyType.raw());
	}

	/* private: the extreme identity over a union's members and a finite
	 * range's values; else no unless a key covers the offset (maybe for an
	 * optional key, a non-constant decimal-integer string hitting an
	 * integer key, or a possibly-covering key), a no lifted to maybe by an
	 * unsealed key range overlapping the offset; -1 = pending exception */
	[[nodiscard]] zend_long recursiveHasOffsetValueType(zval *offsetType) const
	{
		bool isUnion;
		if (UNEXPECTED(!pt_type_instanceof_ce(offsetType, pt_ce_union_type, isUnion))) return -1;
		if (isUnion) {
			zv::Val innerTypes = pt_type_call_array(Z_OBJ_P(offsetType), PT_LC("gettypes"), 0, NULL);
			if (UNEXPECTED(innerTypes.isUndef())) return -1;
			return extremeIdentityOverTypes(innerTypes.raw());
		}
		if (instanceof_function(Z_OBJCE_P(offsetType), pt_ce_integer_range_type)) {
			zv::Val finiteTypes = pt_type_call_array(Z_OBJ_P(offsetType), PT_LC("getfinitetypes"), 0, NULL);
			if (UNEXPECTED(finiteTypes.isUndef())) return -1;
			if (arrayCount(finiteTypes.raw()) > 0) return extremeIdentityOverTypes(finiteTypes.raw());
		}

		/* Constant offsets against constant keys resolve by value: a hit at
		 * the indexed slot is verified before use, a miss falls through to
		 * the scan (which also covers the unsealed extras below). */
		NullableLong index;
		if (UNEXPECTED(!findVerifiedKeyIndex(offsetType, index))) return -1;
		if (!index.isNull) {
			bool optional;
			if (UNEXPECTED(!thisIsOptionalKey(index.value, optional))) return -1;
			return optional ? PT_TRI_MAYBE : PT_TRI_YES;
		}

		zend_long result = PT_TRI_NO;
		zval *k = keyTypes();
		if (UNEXPECTED(k == NULL)) return -1;
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			/* PHP coerces decimal-integer strings to int when used as array
			 * keys ("123" → 123), so a non-constant string offset *could* hit
			 * a constant-integer slot. Skip the upgrade when the offset is
			 * definitely a non-decimal-integer string — those stay as strings
			 * and can never collide with an int key. */
			if (Z_TYPE_P(keyType) == IS_OBJECT && instanceof_function(Z_OBJCE_P(keyType), pt_ce_constant_integer_type)) {
				zend_long isString = pt_type_op_trinary(Z_OBJ_P(offsetType), PT_OP_IS_STRING, 0, NULL);
				if (UNEXPECTED(isString < 0)) return -1;
				if (isString != PT_TRI_NO) {
					zend_long isConstantScalar = pt_type_op_trinary(Z_OBJ_P(offsetType), PT_OP_IS_CONSTANT_SCALAR_VALUE, 0, NULL);
					if (UNEXPECTED(isConstantScalar < 0)) return -1;
					if (isConstantScalar == PT_TRI_NO) {
						zend_long isDecimalIntegerString = pt_type_call_trinary(Z_OBJ_P(offsetType), PT_LC("isdecimalintegerstring"), 0, NULL);
						if (UNEXPECTED(isDecimalIntegerString < 0)) return -1;
						if (isDecimalIntegerString != PT_TRI_NO) return PT_TRI_MAYBE;
					}
				}
			}

			zend_long has = isSuperTypeOfValue(keyType, offsetType);
			if (UNEXPECTED(has < 0)) return -1;
			if (has == PT_TRI_YES) {
				bool optional;
				if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return -1;
				return optional ? PT_TRI_MAYBE : PT_TRI_YES;
			}
			if (has != PT_TRI_MAYBE) continue;
			result = PT_TRI_MAYBE;
		}

		/* Unsealed extras (zero-or-more additional entries) can never make a
		 * hit definite — they're uncertain by construction. They only matter
		 * when no explicit key matched ($result is No): if the unsealed key
		 * range overlaps the offset, upgrade No → Maybe. Explicit keys take
		 * precedence at any slot they cover (PHP keys are unique), so a
		 * non-No $result already reflects the strongest answer the unsealed
		 * extras could contribute. */
		if (result == PT_TRI_NO) {
			zend_long unsealedness = thisIsUnsealed();
			if (UNEXPECTED(unsealedness < 0)) return -1;
			zval *unsealed = unsealedSlot();
			if (UNEXPECTED(unsealed == NULL)) return -1;
			if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
				zval *unsealedKeyType = unsealedKeyOf(unsealed);
				if (UNEXPECTED(unsealedKeyType == NULL)) return -1;
				zend_long covers = isSuperTypeOfValue(unsealedKeyType, offsetType);
				if (UNEXPECTED(covers < 0)) return -1;
				if (covers != PT_TRI_NO) {
					result = PT_TRI_MAYBE;
				}
			}
		}

		return result;
	}

	/* new ErrorType() for a sealed empty shape; the offset's array key
	 * matched against the keys: the iterable value type when every key
	 * covers it (a sealed shape) or when it could hit every key, else the
	 * union of the matching values (mixed for an ErrorType union) and the
	 * unsealed value type when the offset reaches beyond the keys into the
	 * unsealed range, new ErrorType() for none; UNDEF = pending exception */
	zv::Val getOffsetValueType(zval *offsetTypeArg) const
	{
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		if (arrayCount(k) == 0 && unsealedness != PT_TRI_YES) return pt_type_new_error_type();

		zv::Val offsetType = callType(Z_OBJ_P(offsetTypeArg), PT_LC("toarraykey"), 0, NULL);
		if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
		if (arrayCount(k) > 1) {
			/* Same result as the scan below for a verified hit: exactly one
			 * explicit key matches a constant offset, so neither the
			 * all-keys nor the maybe-all fallbacks apply and the unsealed
			 * extras cannot contribute. */
			NullableLong index;
			if (UNEXPECTED(!findVerifiedKeyIndex(offsetType.raw(), index))) return zv::Val();
			if (!index.isNull) {
				zval *valueType = arrayIndexObject(v, index.value, "valueTypes");
				if (UNEXPECTED(valueType == NULL)) return zv::Val();
				zv::Arr single = zv::Arr::create(1);
				single.push(zv::Ref(valueType));
				zv::Val type = combinatorSpread(PT_LC("union"), single.table());
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				bool isError;
				if (UNEXPECTED(!isInstance(type.raw(), pt_ce_error_type, isError))) return zv::Val();
				if (isError) return pt_type_new_mixed_type();
				return type;
			}
		}
		zv::Arr matchingValueTypes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(k)) + 1);
		bool all = true;
		bool maybeAll = true;
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			zend_long covers = isSuperTypeOfValue(keyType, offsetType.raw());
			if (UNEXPECTED(covers < 0)) return zv::Val();
			if (covers == PT_TRI_NO) {
				all = false;

				if (Z_TYPE_P(keyType) == IS_OBJECT && instanceof_function(Z_OBJCE_P(keyType), pt_ce_constant_integer_type)) {
					zend_long isString = pt_type_op_trinary(Z_OBJ_P(offsetType.raw()), PT_OP_IS_STRING, 0, NULL);
					if (UNEXPECTED(isString < 0)) return zv::Val();
					if (isString != PT_TRI_NO) {
						zend_long isConstantScalar = pt_type_op_trinary(Z_OBJ_P(offsetType.raw()), PT_OP_IS_CONSTANT_SCALAR_VALUE, 0, NULL);
						if (UNEXPECTED(isConstantScalar < 0)) return zv::Val();
						if (isConstantScalar == PT_TRI_NO) continue;
					}
				}
				maybeAll = false;
				continue;
			}

			zval *valueType = arrayIndexObject(v, i, "valueTypes");
			if (UNEXPECTED(valueType == NULL)) return zv::Val();
			matchingValueTypes.push(zv::Ref(valueType));
		}

		/* Unsealed extras describe entries at keys NOT in the explicit set —
		 * PHP array keys are unique, so an explicit key fully owns its slot.
		 * Only include the unsealed value when the offset has parts not
		 * covered by any explicit key AND those parts overlap the unsealed
		 * key range. */
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
			zval *unsealedKeyType, *unsealedValueType;
			if (UNEXPECTED(!unsealedPair(unsealed, unsealedKeyType, unsealedValueType))) return zv::Val();
			zv::Val keyTypesUnion = getKeyTypesUnion();
			if (UNEXPECTED(keyTypesUnion.isUndef())) return zv::Val();
			zend_long unionCovers = isSuperTypeOfValue(keyTypesUnion.raw(), offsetType.raw());
			if (UNEXPECTED(unionCovers < 0)) return zv::Val();
			if (unionCovers != PT_TRI_YES) {
				zend_long unsealedCovers = isSuperTypeOfValue(unsealedKeyType, offsetType.raw());
				if (UNEXPECTED(unsealedCovers < 0)) return zv::Val();
				if (unsealedCovers != PT_TRI_NO) {
					matchingValueTypes.push(zv::Ref(unsealedValueType));
				}
			}
		}

		if (all && unsealedness != PT_TRI_YES) return thisGetIterableValueType();

		if (matchingValueTypes.arrRef().size() > 0) {
			zv::Val type = combinatorSpread(PT_LC("union"), matchingValueTypes.table());
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			bool isError;
			if (UNEXPECTED(!isInstance(type.raw(), pt_ce_error_type, isError))) return zv::Val();
			if (isError) return pt_type_new_mixed_type();
			return type;
		}

		if (maybeAll) return thisGetIterableValueType();

		return pt_type_new_error_type(); /* undefined offset */
	}

	/* new ErrorType() for an append with no next index; else the builder
	 * over this shape with the offset set; offsetType NULL = the twin's
	 * null; UNDEF = pending exception */
	zv::Val setOffsetValueType(zval *offsetType, zval *valueType, bool unionValues) const
	{
		(void) unionValues;
		if (offsetType == NULL) {
			zval *n = nextAutoIndexes();
			if (UNEXPECTED(n == NULL)) return zv::Val();
			if (arrayCount(n) == 0) return pt_type_new_error_type();
		}
		zv::Val builder = builderCreateFromConstantArray(thisZv());
		if (UNEXPECTED(builder.isUndef() || !builderSet(builder.raw(), offsetType, valueType))) return zv::Val();
		return builderGetArray(builder.raw());
	}

	/* the builder over this shape with the offset set */
	zv::Val setExistingOffsetValueType(zval *offsetType, zval *valueType) const
	{
		zv::Val builder = builderCreateFromConstantArray(thisZv());
		if (UNEXPECTED(builder.isUndef() || !builderSet(builder.raw(), offsetType, valueType))) return zv::Val();
		return builderGetArray(builder.raw());
	}

	/* Removes or marks as optional the key(s) matching the given offset type
	 * from this constant array: a constant offset's key removed (the list
	 * certainty after the unset, weakened to maybe unless preserved — an
	 * impossible unset of a definite list is never); the keys matching a
	 * finite offset, or covered by any other offset, made optional; $this
	 * when nothing matches; UNDEF = pending exception */
	zv::Val unsetOffset(zval *offsetTypeArg, bool preserveListCertainty) const
	{
		zv::Val offsetType = callType(Z_OBJ_P(offsetTypeArg), PT_LC("toarraykey"), 0, NULL);
		if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *n = v != NULL ? nextAutoIndexes() : NULL;
		zval *o = n != NULL ? optionalKeys() : NULL;
		zval *l = o != NULL ? isListSlot() : NULL;
		zval *u = l != NULL ? unsealedSlot() : NULL;
		if (UNEXPECTED(u == NULL)) return zv::Val();
		zend_long isListValue = trinaryOf(l);
		if (UNEXPECTED(isListValue < 0)) return zv::Val();

		zend_class_entry *offsetCe = Z_OBJCE_P(offsetType.raw());
		if (instanceof_function(offsetCe, pt_ce_constant_integer_type) || instanceof_function(offsetCe, pt_ce_constant_string_type)) {
			zv::Val offsetValue = keyValue(offsetType.raw());
			if (UNEXPECTED(offsetValue.isUndef())) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(k)) {
				zend_long i = (zend_long) entry.indexKey();
				zv::Val value = keyValue(entry.value().deref().raw());
				if (UNEXPECTED(value.isUndef())) return zv::Val();
				if (!zend_is_identical(value.raw(), offsetValue.raw())) continue;

				/* $keyTypes = $this->keyTypes; unset($keyTypes[$i]); the same for the values */
				zv::Arr keyTypesCopy = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(k)));
				keyTypesCopy.separate();
				zend_hash_index_del(keyTypesCopy.table(), (zend_ulong) i);
				zv::Arr valueTypesCopy = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(v)));
				valueTypesCopy.separate();
				zend_hash_index_del(valueTypesCopy.table(), (zend_ulong) i);

				zv::Arr newKeyTypes = zv::Arr::create(keyTypesCopy.arrRef().size());
				zv::Arr newValueTypes = zv::Arr::create(keyTypesCopy.arrRef().size());
				zv::Arr newOptionalKeys = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(o)));
				zend_long kk = 0;
				for (zv::ArrayEntry keyEntry : keyTypesCopy.arrRef()) {
					zend_long j = (zend_long) keyEntry.indexKey();
					newKeyTypes.push(keyEntry.value());
					zval *newValueType = zend_hash_index_find(valueTypesCopy.table(), (zend_ulong) j);
					if (newValueType == NULL) {
						newValueTypes.push(zv::Val::null());
					} else {
						newValueTypes.push(zv::Ref(newValueType));
					}
					if (inArrayStrictLong(o, j)) {
						newOptionalKeys.push(zv::Val::integer(kk));
					}
					kk++;
				}

				zend_long newIsList = isListAfterUnset(newKeyTypes.raw(), newOptionalKeys.raw(), isListValue, inArrayStrictLong(o, i));
				if (UNEXPECTED(newIsList < 0)) return zv::Val();
				if (!preserveListCertainty) {
					newIsList &= PT_TRI_MAYBE;
				} else if (isListValue == PT_TRI_YES && newIsList == PT_TRI_NO) {
					return neverType();
				}

				return thisRecreate(newKeyTypes.raw(), newValueTypes.raw(), n, newOptionalKeys.raw(), pt_trinary_singleton(newIsList), u);
			}

			return zv::Val::copyOf(zv::Ref(thisZv()));
		}

		zv::Val constantScalars = pt_type_call_array(Z_OBJ_P(offsetType.raw()), PT_LC("getconstantscalartypes"), 0, NULL);
		if (UNEXPECTED(constantScalars.isUndef())) return zv::Val();
		if (arrayCount(constantScalars.raw()) > 0) {
			zv::Arr optionalKeysCopy = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(o)));

			bool arrayHasChanged = false;
			for (zv::ArrayEntry scalarEntry : zv::ArrRef(constantScalars.raw())) {
				zval *scalar = scalarEntry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(scalar) != IS_OBJECT)) {
					zend_type_error("phpstan_turbo: getConstantScalarTypes() must return %s instances", ptcls::type);
					return zv::Val();
				}
				zv::Val constantScalar = callType(Z_OBJ_P(scalar), PT_LC("toarraykey"), 0, NULL);
				if (UNEXPECTED(constantScalar.isUndef())) return zv::Val();
				zend_class_entry *scalarCe = Z_OBJCE_P(constantScalar.raw());
				if (!instanceof_function(scalarCe, pt_ce_constant_integer_type) && !instanceof_function(scalarCe, pt_ce_constant_string_type)) continue;
				zv::Val scalarValue = keyValue(constantScalar.raw());
				if (UNEXPECTED(scalarValue.isUndef())) return zv::Val();

				for (zv::ArrayEntry entry : zv::ArrRef(k)) {
					zend_long i = (zend_long) entry.indexKey();
					zv::Val value = keyValue(entry.value().deref().raw());
					if (UNEXPECTED(value.isUndef())) return zv::Val();
					if (!zend_is_identical(value.raw(), scalarValue.raw())) continue;

					arrayHasChanged = true;
					if (inArrayStrictLong(optionalKeysCopy.raw(), i)) {
						goto nextScalar; /* continue 2 */
					}

					optionalKeysCopy.push(zv::Val::integer(i));
				}
				nextScalar:;
			}

			if (!arrayHasChanged) return zv::Val::copyOf(zv::Ref(thisZv()));

			zend_long newIsList = isListAfterUnset(k, optionalKeysCopy.raw(), isListValue, optionalKeysCopy.arrRef().size() == (uint32_t) arrayCount(o));
			if (UNEXPECTED(newIsList < 0)) return zv::Val();
			if (!preserveListCertainty) {
				newIsList &= PT_TRI_MAYBE;
			}

			return thisRecreate(k, v, n, optionalKeysCopy.raw(), pt_trinary_singleton(newIsList), u);
		}

		zv::Arr optionalKeysCopy = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(o)));
		bool arrayHasChanged = false;
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zend_long covers = isSuperTypeOfValue(offsetType.raw(), entry.value().deref().raw());
			if (UNEXPECTED(covers < 0)) return zv::Val();
			if (covers != PT_TRI_YES) continue;
			arrayHasChanged = true;
			optionalKeysCopy.push(zv::Val::integer(i));
		}
		zv::Arr uniqueOptionalKeys = uniqueLongs(optionalKeysCopy.raw());

		if (!arrayHasChanged) return zv::Val::copyOf(zv::Ref(thisZv()));

		zend_long newIsList = isListAfterUnset(k, uniqueOptionalKeys.raw(), isListValue, uniqueOptionalKeys.arrRef().size() == (uint32_t) arrayCount(o));
		if (UNEXPECTED(newIsList < 0)) return zv::Val();
		if (!preserveListCertainty) {
			newIsList &= PT_TRI_MAYBE;
		} else if (isListValue == PT_TRI_YES && newIsList == PT_TRI_NO) {
			return neverType();
		}

		return thisRecreate(k, v, n, uniqueOptionalKeys.raw(), pt_trinary_singleton(newIsList), u);
	}

	/* List-ness of a sealed shape from its keys and optionality: `yes` if
	 * every realization (choice of present optional keys) is a list, `no`
	 * if none is, `maybe` otherwise; -1 = pending exception */
	[[nodiscard]] static zend_long inferIsListFromShape(zval *keyTypes, zval *optionalKeys)
	{
		zv::ScratchTable optional((uint32_t) zend_hash_num_elements(Z_ARRVAL_P(optionalKeys)));
		for (zv::ArrayEntry entry : zv::ArrRef(optionalKeys)) {
			zval *optionalKey = entry.value().deref().raw();
			if (Z_TYPE_P(optionalKey) == IS_LONG) {
				zend_hash_index_add_empty_element(optional.table(), (zend_ulong) Z_LVAL_P(optionalKey));
			}
		}

		/* Prefix lengths reachable by realizations that are still a valid list. */
		zv::ScratchTable validLengths(8);
		zend_hash_index_add_empty_element(validLengths.table(), 0);
		bool existsInvalid = false;

		for (zv::ArrayEntry entry : zv::ArrRef(keyTypes)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			bool isOptional = zend_hash_index_exists(optional.table(), (zend_ulong) i);
			/* A numeric-string key like "1" is an integer key at runtime, so
			 * normalize before deciding whether it continues the list. */
			zv::Val arrayKey = callType(Z_OBJ_P(keyType), PT_LC("toarraykey"), 0, NULL);
			if (UNEXPECTED(arrayKey.isUndef())) return -1;
			bool hasValue = false;
			zend_long value = 0;
			if (instanceof_function(Z_OBJCE_P(arrayKey.raw()), pt_ce_constant_integer_type)) {
				if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(arrayKey.raw()), value))) return -1;
				hasValue = true;
			}

			zv::ScratchTable newValidLengths((uint32_t) validLengths.size() * 2);
			for (zv::ArrayEntry lengthEntry : zv::TableRef(validLengths.table())) {
				zend_long length = (zend_long) lengthEntry.indexKey();
				if (isOptional) {
					zend_hash_index_add_empty_element(newValidLengths.table(), (zend_ulong) length);
				}

				/* A key equal to the current length extends the prefix;
				 * anything else is a non-list realization. */
				if (hasValue && value == length) {
					zend_hash_index_add_empty_element(newValidLengths.table(), (zend_ulong) (length + 1));
				} else {
					existsInvalid = true;
				}
			}

			/* $validLengths = $newValidLengths */
			zend_hash_clean(validLengths.table());
			for (zv::ArrayEntry lengthEntry : zv::TableRef(newValidLengths.table())) {
				zend_hash_index_add_empty_element(validLengths.table(), lengthEntry.indexKey());
			}
			if (validLengths.size() == 0) return PT_TRI_NO;
		}

		return existsInvalid ? PT_TRI_MAYBE : PT_TRI_YES;
	}

	/* When we're unsetting something not on the array, it will be untouched,
	 * so the nextAutoIndexes won't change, and the array might still be a
	 * list even with PHPStan definition; -1 = pending exception */
	[[nodiscard]] static zend_long isListAfterUnset(zval *newKeyTypes, zval *newOptionalKeys, zend_long arrayIsList, bool unsetOptionalKey)
	{
		if (!unsetOptionalKey || arrayIsList == PT_TRI_NO) return PT_TRI_NO;

		bool isListOnlyIfKeysAreOptional = false;
		for (zv::ArrayEntry entry : zv::ArrRef(newKeyTypes)) {
			zend_long k2 = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(keyType) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: a key type must be an object, %s given", zend_zval_value_name(keyType));
				return -1;
			}
			/* A numeric-string key like "1" is an integer key at runtime, so
			 * normalize before deciding whether it continues the list. */
			zv::Val newKeyType2 = callType(Z_OBJ_P(keyType), PT_LC("toarraykey"), 0, NULL);
			if (UNEXPECTED(newKeyType2.isUndef())) return -1;
			bool continuesList = false;
			if (instanceof_function(Z_OBJCE_P(newKeyType2.raw()), pt_ce_constant_integer_type)) {
				zend_long value;
				if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(newKeyType2.raw()), value))) return -1;
				continuesList = value == k2;
			}
			if (!continuesList) {
				/* We found a non-optional key that implies that the array is never a list. */
				if (!inArrayStrictLong(newOptionalKeys, k2)) return PT_TRI_NO;

				/* The array can still be a list if all the following keys are also optional. */
				isListOnlyIfKeysAreOptional = true;
				continue;
			}

			if (isListOnlyIfKeysAreOptional && !inArrayStrictLong(newOptionalKeys, k2)) return PT_TRI_NO;
		}

		return arrayIsList;
	}

	/* the trait's list-of-chunks for an unsealed shape or a non-constant
	 * length; else, for a length of at least 1 with few finite values, the
	 * union over the lengths of the shapes of slices; UNDEF = pending
	 * exception */
	zv::Val chunkArray(zval *lengthType, zval *preserveKeys) const
	{
		/* With real unsealed extras, we can't precisely enumerate the chunks
		 * — the source has an unknown number of extras that could form
		 * additional partial or full chunks. Fall back to the general
		 * `list<chunk<sourceValues>>` shape produced by the trait, which is
		 * correct (just less precise). */
		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		if (unsealedness == PT_TRI_YES) return thisTraitChunkArray(lengthType, preserveKeys);

		zv::Val biggerOne = pt_integer_range_from_interval(NullableLong::of(1), NullableLong::null(), 0);
		if (UNEXPECTED(biggerOne.isUndef())) return zv::Val();
		zv::Val finiteTypes = pt_type_call_array(Z_OBJ_P(lengthType), PT_LC("getfinitetypes"), 0, NULL);
		if (UNEXPECTED(finiteTypes.isUndef())) return zv::Val();
		zend_long covers = isSuperTypeOfValue(biggerOne.raw(), lengthType);
		if (UNEXPECTED(covers < 0)) return zv::Val();
		if (covers == PT_TRI_YES && arrayCount(finiteTypes.raw()) < PT_CAT_CHUNK_FINITE_TYPES_LIMIT) {
			zval *k = keyTypes();
			if (UNEXPECTED(k == NULL)) return zv::Val();
			zend_long preserve = trinaryOf(preserveKeys);
			if (UNEXPECTED(preserve < 0)) return zv::Val();
			zv::Arr results = zv::Arr::create(arrayCount(finiteTypes.raw()));
			for (zv::ArrayEntry entry : zv::ArrRef(finiteTypes.raw())) {
				zval *finiteType = entry.value().deref().raw();
				zend_long length = 0;
				bool isConstantInteger = Z_TYPE_P(finiteType) == IS_OBJECT && instanceof_function(Z_OBJCE_P(finiteType), pt_ce_constant_integer_type);
				if (isConstantInteger && UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(finiteType), length))) return zv::Val();
				if (!isConstantInteger || length < 1) return thisTraitChunkArray(lengthType, preserveKeys);

				zv::Val builder = builderCreateEmpty();
				if (UNEXPECTED(builder.isUndef())) return zv::Val();

				zend_long keyTypesCount = arrayCount(k);
				for (zend_long i = 0; i < keyTypesCount; i += length) {
					zv::Val offset = pt_type_new_constant_integer(i);
					zv::Val lengthConstant = pt_type_new_constant_integer(length);
					if (UNEXPECTED(offset.isUndef() || lengthConstant.isUndef())) return zv::Val();
					zv::Val chunk = thisSliceArray(offset.raw(), lengthConstant.raw(), pt_trinary_singleton(PT_TRI_YES));
					if (UNEXPECTED(chunk.isUndef())) return zv::Val();
					zv::Val chunkValue;
					if (preserve == PT_TRI_YES) {
						chunkValue = std::move(chunk);
					} else {
						chunkValue = callType(Z_OBJ_P(chunk.raw()), PT_LC("getvaluesarray"), 0, NULL);
						if (UNEXPECTED(chunkValue.isUndef())) return zv::Val();
					}
					if (UNEXPECTED(!builderSet(builder.raw(), NULL, chunkValue.raw()))) return zv::Val();
				}

				zv::Val built = builderGetArray(builder.raw());
				if (UNEXPECTED(built.isUndef())) return zv::Val();
				results.push(std::move(built));
			}

			return combinatorSpread(PT_LC("union"), results.table());
		}

		return thisTraitChunkArray(lengthType, preserveKeys);
	}

	/* a shape keyed by the values (a non-integer value's string form, an
	 * ErrorType stopping), each optional for an optional key or a
	 * multi-valued one; the unsealed value type as a further key range;
	 * UNDEF = pending exception */
	zv::Val fillKeysArray(zval *valueType) const
	{
		zv::Val builder = builderCreateEmpty();
		zval *v = valueTypes();
		if (UNEXPECTED(builder.isUndef() || v == NULL)) return zv::Val();

		for (zv::ArrayEntry entry : zv::ArrRef(v)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(keyType) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: valueTypes[" ZEND_LONG_FMT "] must be a %s", i, ptcls::type);
				return zv::Val();
			}
			zend_long isInteger = pt_type_op_trinary(Z_OBJ_P(keyType), PT_OP_IS_INTEGER, 0, NULL);
			if (UNEXPECTED(isInteger < 0)) return zv::Val();
			zv::Val offset;
			if (isInteger == PT_TRI_NO) {
				zv::Val stringKeyType = callType(Z_OBJ_P(keyType), PT_LC("tostring"), 0, NULL);
				if (UNEXPECTED(stringKeyType.isUndef())) return zv::Val();
				bool isError;
				if (UNEXPECTED(!isInstance(stringKeyType.raw(), pt_ce_error_type, isError))) return zv::Val();
				if (isError) return stringKeyType;
				offset = std::move(stringKeyType);
			} else {
				offset = zv::Val::copyOf(zv::Ref(keyType));
			}
			bool optional;
			if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return zv::Val();
			if (!optional) {
				zv::Val scalars = pt_type_call_array(Z_OBJ_P(offset.raw()), PT_LC("getconstantscalartypes"), 0, NULL);
				if (UNEXPECTED(scalars.isUndef())) return zv::Val();
				optional = arrayCount(scalars.raw()) > 1;
			}
			if (UNEXPECTED(!builderSet(builder.raw(), offset.raw(), valueType, optional ? 1 : 0))) return zv::Val();
		}

		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
			zval *unsealedKey, *unsealedValue;
			if (UNEXPECTED(!unsealedPair(unsealed, unsealedKey, unsealedValue))) return zv::Val();
			zv::Val tailKey = callType(Z_OBJ_P(unsealedValue), PT_LC("toarraykey"), 0, NULL);
			if (UNEXPECTED(tailKey.isUndef())) return zv::Val();
			/* See flipArray() for the rationale: install the unsealed tail
			 * only when its key type is non-finite; otherwise let
			 * setOffsetValueType expand it into optional explicit slots
			 * (merged with any matching existing keys). */
			zv::Val finiteTypes = pt_type_call_array(Z_OBJ_P(tailKey.raw()), PT_LC("getfinitetypes"), 0, NULL);
			if (UNEXPECTED(finiteTypes.isUndef())) return zv::Val();
			if (arrayCount(finiteTypes.raw()) == 0 && UNEXPECTED(!builderMakeUnsealed(builder.raw(), tailKey.raw(), valueType))) return zv::Val();
			if (UNEXPECTED(!builderSet(builder.raw(), tailKey.raw(), valueType, 1))) return zv::Val();
		}

		return builderGetArray(builder.raw());
	}

	/* a shape keyed by the values' array keys holding the keys, each
	 * optional for an optional key or a multi-valued one; the unsealed pair
	 * flipped; UNDEF = pending exception */
	zv::Val flipArray() const
	{
		zv::Val builder = builderCreateEmpty();
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		if (UNEXPECTED(builder.isUndef() || v == NULL)) return zv::Val();

		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			zval *valueType = arrayIndexObject(v, i, "valueTypes");
			if (UNEXPECTED(valueType == NULL)) return zv::Val();
			zv::Val offsetType = callType(Z_OBJ_P(valueType), PT_LC("toarraykey"), 0, NULL);
			if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
			bool optional;
			if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return zv::Val();
			if (!optional) {
				zv::Val scalars = pt_type_call_array(Z_OBJ_P(offsetType.raw()), PT_LC("getconstantscalartypes"), 0, NULL);
				if (UNEXPECTED(scalars.isUndef())) return zv::Val();
				optional = arrayCount(scalars.raw()) > 1;
			}
			if (UNEXPECTED(!builderSet(builder.raw(), offsetType.raw(), keyType, optional ? 1 : 0))) return zv::Val();
		}

		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
			zval *unsealedKey, *unsealedValue;
			if (UNEXPECTED(!unsealedPair(unsealed, unsealedKey, unsealedValue))) return zv::Val();
			zv::Val flippedKey = callType(Z_OBJ_P(unsealedValue), PT_LC("toarraykey"), 0, NULL);
			if (UNEXPECTED(flippedKey.isUndef())) return zv::Val();
			/* For a non-finite tail key (e.g. `string`), install the unsealed
			 * extras first; setOffsetValueType then widens any overlapping
			 * explicit values with the tail's value type. For a finite tail
			 * key (e.g. `0|1`), setOffsetValueType expands the tail into
			 * optional explicit slots that fully cover the tail's domain, so
			 * no residual unsealed tail is needed. */
			zv::Val finiteTypes = pt_type_call_array(Z_OBJ_P(flippedKey.raw()), PT_LC("getfinitetypes"), 0, NULL);
			if (UNEXPECTED(finiteTypes.isUndef())) return zv::Val();
			if (arrayCount(finiteTypes.raw()) == 0 && UNEXPECTED(!builderMakeUnsealed(builder.raw(), flippedKey.raw(), unsealedKey))) return zv::Val();
			if (UNEXPECTED(!builderSet(builder.raw(), flippedKey.raw(), unsealedKey, 1))) return zv::Val();
		}

		return builderGetArray(builder.raw());
	}

	/* the keys the other array may have (optional unless it surely has
	 * them), the unsealed key range narrowed to the other's keys; UNDEF =
	 * pending exception */
	zv::Val intersectKeyArray(zval *otherArraysType) const
	{
		zv::Val builder = builderCreateEmpty();
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		if (UNEXPECTED(builder.isUndef() || v == NULL)) return zv::Val();

		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			zval *valueType = arrayIndexObject(v, i, "valueTypes");
			if (UNEXPECTED(valueType == NULL)) return zv::Val();
			zend_long has = pt_type_call_trinary(Z_OBJ_P(otherArraysType), PT_LC("hasoffsetvaluetype"), 1, keyType);
			if (UNEXPECTED(has < 0)) return zv::Val();
			if (has == PT_TRI_NO) continue;
			bool optional;
			if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return zv::Val();
			if (UNEXPECTED(!builderSet(builder.raw(), keyType, valueType, (optional || has != PT_TRI_YES) ? 1 : 0))) return zv::Val();
		}

		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
			zval *unsealedKey, *unsealedValue;
			if (UNEXPECTED(!unsealedPair(unsealed, unsealedKey, unsealedValue))) return zv::Val();
			/* An unsealed extra at key K survives only if `$other` can also
			 * have key K. Narrow the unsealed key to the intersection of our
			 * extras-range and `$other`'s key type. If they don't overlap,
			 * the unsealed slot is dropped. */
			zv::Val otherKeyType = callType(Z_OBJ_P(otherArraysType), PT_LC("getiterablekeytype"), 0, NULL);
			if (UNEXPECTED(otherKeyType.isUndef())) return zv::Val();
			zv::Val narrowedKey = combinator2(PT_LC("intersect"), unsealedKey, otherKeyType.raw());
			if (UNEXPECTED(narrowedKey.isUndef())) return zv::Val();
			if (!zv::Ref(narrowedKey.raw()).instanceOf(pt_ce_never_type) && UNEXPECTED(!builderMakeUnsealed(builder.raw(), narrowedKey.raw(), unsealedValue))) {
				return zv::Val();
			}
		}

		return builderGetArray(builder.raw());
	}

	zv::Val popArray() const { return removeLastElements(1); }

	/* the keys in reverse order (integer keys renumbered unless preserved),
	 * the unsealed pair kept; UNDEF = pending exception */
	zv::Val reverseArray(zval *preserveKeys) const
	{
		zv::Val builder = builderCreateEmpty();
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		if (UNEXPECTED(builder.isUndef() || v == NULL)) return zv::Val();
		zend_long preserve = trinaryOf(preserveKeys);
		if (UNEXPECTED(preserve < 0)) return zv::Val();

		for (zend_long i = arrayCount(k) - 1; i >= 0; i--) {
			zval *keyType = arrayIndexObject(k, i, "keyTypes");
			zval *valueType = keyType != NULL ? arrayIndexObject(v, i, "valueTypes") : NULL;
			if (UNEXPECTED(valueType == NULL)) return zv::Val();
			zval *offsetType = keyType;
			if (preserve != PT_TRI_YES) {
				zend_long isInteger = pt_type_op_trinary(Z_OBJ_P(keyType), PT_OP_IS_INTEGER, 0, NULL);
				if (UNEXPECTED(isInteger < 0)) return zv::Val();
				if (isInteger != PT_TRI_NO) {
					offsetType = NULL;
				}
			}
			bool optional;
			if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return zv::Val();
			if (UNEXPECTED(!builderSet(builder.raw(), offsetType, valueType, optional ? 1 : 0))) return zv::Val();
		}

		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
			/* `array_reverse` only permutes positions; the unsealed slot is
			 * "zero or more extras at unspecified positions" both before and
			 * after. */
			zval *unsealedKey, *unsealedValue;
			if (UNEXPECTED(!unsealedPair(unsealed, unsealedKey, unsealedValue) || !builderMakeUnsealed(builder.raw(), unsealedKey, unsealedValue))) {
				return zv::Val();
			}
		}

		return builderGetArray(builder.raw());
	}

	/* the keys whose values may hold the needle (constant scalars compared
	 * loosely; strictly for a strict search) unioned, with false unless a
	 * required key surely holds it; the unsealed key range too; false when
	 * nothing matches; strict NULL = the twin's null; UNDEF = pending
	 * exception */
	zv::Val searchArray(zval *needleType, zval *strict) const
	{
		zend_long strictValue = PT_TRI_MAYBE;
		if (strict != NULL) {
			strictValue = trinaryOf(strict);
			if (UNEXPECTED(strictValue < 0)) return zv::Val();
		}
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zv::Arr matches = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(v)) + 1);
		bool hasIdenticalValue = false;

		bool needleIsConstantScalar;
		if (UNEXPECTED(!isInstance(needleType, PT_CLASS_CONSTANT_SCALAR_TYPE, needleIsConstantScalar))) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(v)) {
			zend_long index = (zend_long) entry.indexKey();
			zval *valueType = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(valueType) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: valueTypes[" ZEND_LONG_FMT "] must be a %s", index, ptcls::type);
				return zv::Val();
			}
			if (strictValue == PT_TRI_YES) {
				zend_long isNeedleSuperType = isSuperTypeOfValue(valueType, needleType);
				if (UNEXPECTED(isNeedleSuperType < 0)) return zv::Val();
				if (isNeedleSuperType == PT_TRI_NO) continue;
			}

			if (needleIsConstantScalar) {
				bool valueIsConstantScalar;
				if (UNEXPECTED(!isInstance(valueType, PT_CLASS_CONSTANT_SCALAR_TYPE, valueIsConstantScalar))) return zv::Val();
				if (valueIsConstantScalar) {
					zv::Val needleValue = pt_type_call(Z_OBJ_P(needleType), PT_LC("getvalue"), 0, NULL);
					if (UNEXPECTED(needleValue.isUndef())) return zv::Val();
					zv::Val value = pt_type_call(Z_OBJ_P(valueType), PT_LC("getvalue"), 0, NULL);
					if (UNEXPECTED(value.isUndef())) return zv::Val();
					/* $needleType->getValue() == $valueType->getValue() */
					int comparison = zend_compare(needleValue.raw(), value.raw());
					if (UNEXPECTED(EG(exception))) return zv::Val();
					if (comparison != 0) continue;
					if (strictValue == PT_TRI_NO || zend_is_identical(needleValue.raw(), value.raw())) {
						bool optional;
						if (UNEXPECTED(!thisIsOptionalKey(index, optional))) return zv::Val();
						if (!optional) {
							hasIdenticalValue = true;
						}
					}
				}
			}

			zval *keyType = arrayIndexObject(k, index, "keyTypes");
			if (UNEXPECTED(keyType == NULL)) return zv::Val();
			matches.push(zv::Ref(keyType));
		}

		/* Unsealed extras can host additional entries beyond the explicit
		 * keys, so the search may also find the needle there. The unsealed
		 * extras' presence is uncertain by definition (zero or more
		 * entries), so they can never make the needle "definitely found"
		 * (`hasIdenticalValue` stays false) — `false` always remains a
		 * possible result. */
		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
			zval *unsealedKeyType, *unsealedValueType;
			if (UNEXPECTED(!unsealedPair(unsealed, unsealedKeyType, unsealedValueType))) return zv::Val();
			bool considerUnsealed = true;
			if (strictValue == PT_TRI_YES) {
				zend_long covers = isSuperTypeOfValue(unsealedValueType, needleType);
				if (UNEXPECTED(covers < 0)) return zv::Val();
				considerUnsealed = covers != PT_TRI_NO;
			}
			if (considerUnsealed) {
				matches.push(zv::Ref(unsealedKeyType));
			}
		}

		if (matches.arrRef().size() > 0) {
			if (hasIdenticalValue) return combinatorSpread(PT_LC("union"), matches.table());

			zv::Val falseType = constantBoolean(false);
			if (UNEXPECTED(falseType.isUndef())) return zv::Val();
			zv::Arr withFalse = zv::Arr::create(matches.arrRef().size() + 1);
			withFalse.push(std::move(falseType));
			for (zv::ArrayEntry entry : matches.arrRef()) {
				withFalse.push(entry.value());
			}
			return combinatorSpread(PT_LC("union"), withFalse.table());
		}

		return constantBoolean(false);
	}

	zv::Val shiftArray() const { return removeFirstElements(1, true); }

	/* $this->getValuesArray()->degradeToGeneralArray() — the private method
	 * on the values shape */
	zv::Val shuffleArray() const
	{
		zv::Val valuesArray = thisGetValuesArray();
		if (UNEXPECTED(valuesArray.isUndef())) return zv::Val();
		return ConstantArrayType(Z_OBJ_P(valuesArray.raw())).degradeToGeneralArray();
	}

	/* $this for an empty shape; the general array's slice for a
	 * non-constant offset or length; nothing for a zero length (or negative
	 * ones that meet), the last elements removed for a negative length, a
	 * negative offset reversed into a positive one, a positive offset
	 * shifted away; else the first $length elements (a not-yet-full slice
	 * with an optional key making its last element optional), the unsealed
	 * pair carried when the length runs past the keys; UNDEF = pending
	 * exception */
	zv::Val sliceArray(zval *offsetTypeArg, zval *lengthType, zval *preserveKeys) const
	{
		zval *k = keyTypes();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zend_long keyTypesCount = arrayCount(k);
		if (keyTypesCount == 0) return zv::Val::copyOf(zv::Ref(thisZv()));

		NullableLong offset = NullableLong::null();
		if (instanceof_function(Z_OBJCE_P(offsetTypeArg), pt_ce_constant_integer_type)) {
			zend_long value;
			if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(offsetTypeArg), value))) return zv::Val();
			offset = NullableLong::of(value);
		}

		NullableLong length = NullableLong::null();
		if (instanceof_function(Z_OBJCE_P(lengthType), pt_ce_constant_integer_type)) {
			zend_long value;
			if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(lengthType), value))) return zv::Val();
			length = NullableLong::of(value);
		} else {
			zend_long isNull = pt_type_op_trinary(Z_OBJ_P(lengthType), PT_OP_IS_NULL, 0, NULL);
			if (UNEXPECTED(isNull < 0)) return zv::Val();
			if (isNull == PT_TRI_YES) {
				length = NullableLong::of(keyTypesCount);
			}
		}

		if (offset.isNull || length.isNull) {
			zv::Val general = degradeToGeneralArray();
			if (UNEXPECTED(general.isUndef())) return zv::Val();
			zv::Args args{offsetTypeArg, lengthType, preserveKeys};
			return pt_type_call(Z_OBJ_P(general.raw()), PT_LC("slicearray"), 3, args);
		}

		zend_long offsetValue = offset.value;
		zend_long lengthValue = length.value;
		if (keyTypesCount + offsetValue <= 0) {
			/* A negative offset cannot reach left outside the array twice */
			offsetValue = 0;
		}

		if (keyTypesCount + lengthValue <= 0) {
			/* A negative length cannot reach left outside the array twice */
			lengthValue = 0;
		}

		if (lengthValue == 0 || (offsetValue < 0 && lengthValue < 0 && offsetValue - lengthValue >= 0)) {
			/* 0 / 0, 3 / 0 or e.g. -3 / -3 or -3 / -4 and so on never extract anything */
			zv::Val never = neverType(true);
			if (UNEXPECTED(never.isUndef())) return zv::Val();
			zval empty;
			ZVAL_EMPTY_ARRAY(&empty);
			zv::Arr zero = zv::Arr::create(1);
			zero.push(zv::Val::integer(0));
			zv::Val unsealedPairValue = pairOf(never.raw(), never.raw());
			zval nullZv;
			ZVAL_NULL(&nullZv);
			return thisRecreate(&empty, &empty, zero.raw(), &empty, &nullZv, unsealedPairValue.raw());
		}

		if (lengthValue < 0) {
			/* Negative lengths prevent access to the most right n elements */
			zv::Val shortened = removeLastElements(lengthValue * -1);
			if (UNEXPECTED(shortened.isUndef())) return zv::Val();
			zv::Val nullLength = nullType();
			if (UNEXPECTED(nullLength.isUndef())) return zv::Val();
			zv::Args args{offsetTypeArg, nullLength.raw(), preserveKeys};
			return callType(Z_OBJ_P(shortened.raw()), PT_LC("slicearray"), 3, args);
		}

		if (offsetValue < 0) {
			/* Transforms the problem with the negative offset in one with a
			 * positive offset using array reversion. The reason is below
			 * handling of optional keys which works only from left to right.
			 *
			 * e.g. array{a: 0, b: 1, c: 2, d: 3, e: 4} with offset -4 and
			 * length 2 (which would be sliced to array{b: 1, c: 2}) is
			 * transformed via reversion to array{e: 4, d: 3, c: 2, b: 1, a: 0}
			 * with offset 2 and length 2 (which will be sliced to
			 * array{c: 2, b: 1} and then reversed again) */
			offsetValue *= -1;
			zend_long reversedLength = lengthValue < offsetValue ? lengthValue : offsetValue;
			zend_long reversedOffset = offsetValue - reversedLength;
			zv::Val reversed = thisReverseArray(pt_trinary_singleton(PT_TRI_YES));
			if (UNEXPECTED(reversed.isUndef())) return zv::Val();
			zv::Val reversedOffsetType = pt_type_new_constant_integer(reversedOffset);
			zv::Val reversedLengthType = pt_type_new_constant_integer(reversedLength);
			if (UNEXPECTED(reversedOffsetType.isUndef() || reversedLengthType.isUndef())) return zv::Val();
			zv::Args args{reversedOffsetType.raw(), reversedLengthType.raw(), preserveKeys};
			zv::Val sliced = callType(Z_OBJ_P(reversed.raw()), PT_LC("slicearray"), 3, args);
			if (UNEXPECTED(sliced.isUndef())) return zv::Val();
			return callType(Z_OBJ_P(sliced.raw()), PT_LC("reversearray"), 1, pt_trinary_singleton(PT_TRI_YES));
		}

		if (offsetValue > 0) {
			zv::Val shifted = removeFirstElements(offsetValue, false);
			if (UNEXPECTED(shifted.isUndef())) return zv::Val();
			zv::Val zero = pt_type_new_constant_integer(0);
			if (UNEXPECTED(zero.isUndef())) return zv::Val();
			zv::Args args{zero.raw(), lengthType, preserveKeys};
			return callType(Z_OBJ_P(shifted.raw()), PT_LC("slicearray"), 3, args);
		}

		zv::Val builder = builderCreateEmpty();
		zval *v = valueTypes();
		if (UNEXPECTED(builder.isUndef() || v == NULL)) return zv::Val();
		zend_long preserve = trinaryOf(preserveKeys);
		if (UNEXPECTED(preserve < 0)) return zv::Val();

		zend_long nonOptionalElementsCount = 0;
		bool hasOptional = false;
		for (zend_long i = 0; nonOptionalElementsCount < lengthValue && i < keyTypesCount; i++) {
			bool isOptional;
			if (UNEXPECTED(!thisIsOptionalKey(i, isOptional))) return zv::Val();
			if (!isOptional) {
				nonOptionalElementsCount++;
			} else {
				hasOptional = true;
			}

			bool isLastElement = nonOptionalElementsCount >= lengthValue || i + 1 >= keyTypesCount;
			if (isLastElement && lengthValue < keyTypesCount && hasOptional) {
				/* If the slice is not full yet, but has at least one optional
				 * key the last non-optional element is going to be optional.
				 * Otherwise, it would not fit into the slice if previous
				 * non-optional keys are there. */
				isOptional = true;
			}

			zval *keyType = arrayIndexObject(k, i, "keyTypes");
			zval *valueType = keyType != NULL ? arrayIndexObject(v, i, "valueTypes") : NULL;
			if (UNEXPECTED(valueType == NULL)) return zv::Val();
			zval *offsetType = keyType;
			if (preserve != PT_TRI_YES) {
				zend_long isInteger = pt_type_op_trinary(Z_OBJ_P(keyType), PT_OP_IS_INTEGER, 0, NULL);
				if (UNEXPECTED(isInteger < 0)) return zv::Val();
				if (isInteger != PT_TRI_NO) {
					offsetType = NULL;
				}
			}

			if (UNEXPECTED(!builderSet(builder.raw(), offsetType, valueType, isOptional ? 1 : 0))) return zv::Val();
		}

		/* When the requested length runs past the explicit keys, the missing
		 * trailing slots could be filled by the source's unsealed extras (or
		 * be absent). Carry the unsealed slot through so the result still
		 * describes those potential extras. */
		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL && nonOptionalElementsCount < lengthValue) {
			zval *unsealedKey, *unsealedValue;
			if (UNEXPECTED(!unsealedPair(unsealed, unsealedKey, unsealedValue) || !builderMakeUnsealed(builder.raw(), unsealedKey, unsealedValue))) {
				return zv::Val();
			}
		}

		return builderGetArray(builder.raw());
	}

	/* $this for an empty shape; the general array's splice for a
	 * non-constant offset or length; else, per replacement array, the
	 * shape with the replacement's values inserted at the offset (counted
	 * past the optional keys before it) and the extracted slice's keys
	 * dropped (a possibly-extracted key made optional), integer keys
	 * renumbered, the unsealed pair carried, a list accessory re-attached
	 * for integer keys — unioned; UNDEF = pending exception */
	zv::Val spliceArray(zval *offsetTypeArg, zval *lengthType, zval *replacementType) const
	{
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zend_long keyTypesCount = arrayCount(k);
		if (keyTypesCount == 0) return zv::Val::copyOf(zv::Ref(thisZv()));

		NullableLong offset = NullableLong::null();
		if (instanceof_function(Z_OBJCE_P(offsetTypeArg), pt_ce_constant_integer_type)) {
			zend_long value;
			if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(offsetTypeArg), value))) return zv::Val();
			offset = NullableLong::of(value);
		}

		NullableLong length = NullableLong::null();
		if (instanceof_function(Z_OBJCE_P(lengthType), pt_ce_constant_integer_type)) {
			zend_long value;
			if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(lengthType), value))) return zv::Val();
			length = NullableLong::of(value);
		} else {
			zend_long isNull = pt_type_op_trinary(Z_OBJ_P(lengthType), PT_OP_IS_NULL, 0, NULL);
			if (UNEXPECTED(isNull < 0)) return zv::Val();
			if (isNull == PT_TRI_YES) {
				length = NullableLong::of(keyTypesCount);
			}
		}

		if (offset.isNull || length.isNull) {
			zv::Val general = degradeToGeneralArray();
			if (UNEXPECTED(general.isUndef())) return zv::Val();
			zv::Args args{offsetTypeArg, lengthType, replacementType};
			return pt_type_call(Z_OBJ_P(general.raw()), PT_LC("splicearray"), 3, args);
		}

		zv::Val iterableKeyType = thisGetIterableKeyType();
		if (UNEXPECTED(iterableKeyType.isUndef())) return zv::Val();
		zend_long keysInteger = pt_type_op_trinary(Z_OBJ_P(iterableKeyType.raw()), PT_OP_IS_INTEGER, 0, NULL);
		if (UNEXPECTED(keysInteger < 0)) return zv::Val();
		bool allKeysInteger = keysInteger == PT_TRI_YES;

		zend_long offsetValue = offset.value;
		zend_long lengthValue = length.value;
		if (keyTypesCount + offsetValue <= 0) {
			/* A negative offset cannot reach left outside the array twice */
			offsetValue = 0;
		}

		if (keyTypesCount + lengthValue <= 0) {
			/* A negative length cannot reach left outside the array twice */
			lengthValue = 0;
		}

		bool offsetWasNegative = false;
		if (offsetValue < 0) {
			offsetWasNegative = true;
			offsetValue = keyTypesCount + offsetValue;
		}

		if (lengthValue < 0) {
			lengthValue = keyTypesCount - offsetValue + lengthValue;
		}

		zv::Val extractType = thisSliceArray(offsetTypeArg, lengthType, pt_trinary_singleton(PT_TRI_YES));
		if (UNEXPECTED(extractType.isUndef())) return zv::Val();

		zv::Val replacementArray = callType(Z_OBJ_P(replacementType), PT_LC("toarray"), 0, NULL);
		if (UNEXPECTED(replacementArray.isUndef())) return zv::Val();
		zv::Val replacementArrays = pt_type_call_array(Z_OBJ_P(replacementArray.raw()), PT_LC("getarrays"), 0, NULL);
		if (UNEXPECTED(replacementArrays.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(arrayCount(replacementArrays.raw()));
		for (zv::ArrayEntry replacementEntry : zv::ArrRef(replacementArrays.raw())) {
			zval *replacementArrayType = replacementEntry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(replacementArrayType) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: getArrays() must return %s instances", ptcls::type);
				return zv::Val();
			}
			zend_long removeKeysCount = 0;
			zend_long optionalKeysBeforeReplacement = 0;

			zv::Val builder = builderCreateEmpty();
			if (UNEXPECTED(builder.isUndef())) return zv::Val();
			for (zend_long i = 0;; i++) {
				bool isOptional;
				if (UNEXPECTED(!thisIsOptionalKey(i, isOptional))) return zv::Val();

				if (!offsetWasNegative && i < offsetValue && isOptional) {
					optionalKeysBeforeReplacement++;
				}

				if (i == offsetValue + optionalKeysBeforeReplacement) {
					/* When the offset is reached we have to a) put the replacement array in and b) remove $length elements */
					removeKeysCount = lengthValue;

					if (instanceof_function(Z_OBJCE_P(replacementArrayType), pt_ce_constant_array_type)) {
						zv::Val valuesArray = callType(Z_OBJ_P(replacementArrayType), PT_LC("getvaluesarray"), 0, NULL);
						if (UNEXPECTED(valuesArray.isUndef())) return zv::Val();
						if (UNEXPECTED(!instanceof_function(Z_OBJCE_P(valuesArray.raw()), pt_ce_constant_array_type))) {
							zend_throw_error(NULL, "Cannot access private property %s::$keyTypes", ZSTR_VAL(Z_OBJCE_P(valuesArray.raw())->name));
							return zv::Val();
						}
						zend_object *values = Z_OBJ_P(valuesArray.raw());
						zval *valuesKeyTypes = slotOf(values, slots::keyTypes, "keyTypes");
						zval *valuesValueTypes = valuesKeyTypes != NULL ? slotOf(values, slots::valueTypes, "valueTypes") : NULL;
						if (UNEXPECTED(valuesValueTypes == NULL)) return zv::Val();
						for (zend_long j = 0, jMax = arrayCount(valuesKeyTypes); j < jMax; j++) {
							zval *valueType = arrayIndexObject(valuesValueTypes, j, "valueTypes");
							if (UNEXPECTED(valueType == NULL)) return zv::Val();
							bool valueOptional;
							if (UNEXPECTED(!ConstantArrayType(values).thisIsOptionalKey(j, valueOptional))) return zv::Val();
							if (UNEXPECTED(!builderSet(builder.raw(), NULL, valueType, valueOptional ? 1 : 0))) return zv::Val();
						}
					} else {
						if (UNEXPECTED(!builderCall0(builder.raw(), PT_LC("degradetogeneralarray")))) return zv::Val();
						zv::Val valuesArray = callType(Z_OBJ_P(replacementArrayType), PT_LC("getvaluesarray"), 0, NULL);
						if (UNEXPECTED(valuesArray.isUndef())) return zv::Val();
						zv::Val valuesKeyType = callType(Z_OBJ_P(valuesArray.raw()), PT_LC("getiterablekeytype"), 0, NULL);
						if (UNEXPECTED(valuesKeyType.isUndef())) return zv::Val();
						zv::Val replacementValueType = callType(Z_OBJ_P(replacementArrayType), PT_LC("getiterablevaluetype"), 0, NULL);
						if (UNEXPECTED(replacementValueType.isUndef())) return zv::Val();
						if (UNEXPECTED(!builderSet(builder.raw(), valuesKeyType.raw(), replacementValueType.raw(), 1))) return zv::Val();
					}
				}

				zval *keyType = zend_hash_index_find(Z_ARRVAL_P(k), (zend_ulong) i);
				if (keyType == NULL) break;
				ZVAL_DEREF(keyType);
				if (UNEXPECTED(Z_TYPE_P(keyType) != IS_OBJECT)) {
					zend_type_error("phpstan_turbo: keyTypes[" ZEND_LONG_FMT "] must be a %s", i, ptcls::type);
					return zv::Val();
				}

				if (removeKeysCount > 0) {
					zend_long extractTypeHasOffsetValueType = pt_type_call_trinary(Z_OBJ_P(extractType.raw()), PT_LC("hasoffsetvaluetype"), 1, keyType);
					if (UNEXPECTED(extractTypeHasOffsetValueType < 0)) return zv::Val();

					if ((!isOptional && extractTypeHasOffsetValueType == PT_TRI_YES) || (isOptional && extractTypeHasOffsetValueType == PT_TRI_MAYBE)) {
						removeKeysCount--;
						continue;
					}
				}

				if (!isOptional) {
					zend_long has = pt_type_call_trinary(Z_OBJ_P(extractType.raw()), PT_LC("hasoffsetvaluetype"), 1, keyType);
					if (UNEXPECTED(has < 0)) return zv::Val();
					if (has == PT_TRI_MAYBE) {
						isOptional = true;
					}
				}

				zend_long isInteger = pt_type_op_trinary(Z_OBJ_P(keyType), PT_OP_IS_INTEGER, 0, NULL);
				if (UNEXPECTED(isInteger < 0)) return zv::Val();
				zval *valueType = arrayIndexObject(v, i, "valueTypes");
				if (UNEXPECTED(valueType == NULL)) return zv::Val();
				if (UNEXPECTED(!builderSet(builder.raw(), isInteger == PT_TRI_NO ? keyType : NULL, valueType, isOptional ? 1 : 0))) return zv::Val();
			}

			/* `array_splice` removes a slice at an explicit offset and
			 * inserts a replacement there. Real unsealed extras live at
			 * positions past the explicit keys, so they're unaffected by the
			 * operation (re-indexing of int keys keeps the `<int, V>` range
			 * intact). Carry the slot through. */
			zend_long unsealedness = thisIsUnsealed();
			if (UNEXPECTED(unsealedness < 0)) return zv::Val();
			zval *unsealed = unsealedSlot();
			if (UNEXPECTED(unsealed == NULL)) return zv::Val();
			if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
				zval *unsealedKey, *unsealedValue;
				if (UNEXPECTED(!unsealedPair(unsealed, unsealedKey, unsealedValue) || !builderMakeUnsealed(builder.raw(), unsealedKey, unsealedValue))) {
					return zv::Val();
				}
			}

			zv::Val builtType = builderGetArray(builder.raw());
			if (UNEXPECTED(builtType.isUndef())) return zv::Val();
			if (allKeysInteger) {
				zend_long builtIsList = pt_type_op_trinary(Z_OBJ_P(builtType.raw()), PT_OP_IS_LIST, 0, NULL);
				if (UNEXPECTED(builtIsList < 0)) return zv::Val();
				if (builtIsList != PT_TRI_YES) {
					zv::Val list = accessoryList();
					if (UNEXPECTED(list.isUndef())) return zv::Val();
					builtType = combinator2(PT_LC("intersect"), builtType.raw(), list.raw());
					if (UNEXPECTED(builtType.isUndef())) return zv::Val();
				}
			}
			types.push(std::move(builtType));
		}

		return combinatorSpread(PT_LC("union"), types.table());
	}

	/* the shape narrowed to the size's bounds: intersected with non-empty
	 * when unanchored below, past the builder's limit or when the keys do
	 * not cover the required prefix; else the required prefix, the optional
	 * middle of a bounded size or the probed keys past the minimum of an
	 * unbounded one (the unsealed pair carried then), rebuilt as a list;
	 * UNDEF = pending exception */
	zv::Val truncateListToSize(zval *sizeType) const
	{
		NullableLong min, max;
		if (UNEXPECTED(!extractTruncateListBoundsValues(sizeType, min, max))) return zv::Val();
		zend_long limit;
		if (UNEXPECTED(!arrayCountLimit(limit))) return zv::Val();

		/* `getMin() === null` ↔ unbounded below; the narrowing has no anchor
		 * to start from. Also bail out when the required prefix would exceed
		 * the array-shape limit — we can't enumerate that many keys.
		 * `isList()` is intentionally NOT checked here: the call site
		 * (`TypeSpecifier`) only invokes this when the *outer* aggregate is
		 * already a list, but a CAT inside a `non-empty-list` intersection
		 * may have its own `isList()` weakened to `Maybe`. */
		bool fallBack = min.isNull || min.value >= limit;
		if (!fallBack) {
			zval upper;
			phpSub(max.isNull ? min.value : max.value, 1, &upper);
			if (UNEXPECTED(Z_TYPE(upper) != IS_LONG)) {
				zend_type_error("%s::fromInterval(): Argument #2 ($max) must be of type ?int, float given", ZSTR_VAL(pt_ce_integer_range_type->name));
				return zv::Val();
			}
			zv::Val range = pt_integer_range_from_interval(NullableLong::of(0), NullableLong::of(Z_LVAL(upper)), 0);
			if (UNEXPECTED(range.isUndef())) return zv::Val();
			zv::Val keyType = thisGetKeyType();
			if (UNEXPECTED(keyType.isUndef())) return zv::Val();
			zend_long covers = isSuperTypeOfValue(keyType.raw(), range.raw());
			if (UNEXPECTED(covers < 0)) return zv::Val();
			fallBack = covers != PT_TRI_YES;
		}
		if (fallBack) return intersectedWithNonEmpty();

		/* Required prefix `[0, $min)`: every value definitely present. */
		zv::Arr builderData = zv::Arr::create(8);
		for (zend_long i = 0; i < min.value; i++) {
			if (UNEXPECTED(!pushBuilderData(builderData, i, false))) return zv::Val();
		}

		if (!max.isNull) {
			/* Optional middle `[$min, $max)`. */
			if (max.value - min.value > limit) return intersectedWithNonEmpty();
			for (zend_long i = min.value; i < max.value; i++) {
				if (UNEXPECTED(!pushBuilderData(builderData, i, true))) return zv::Val();
			}
		} else {
			/* Unbounded max: probe explicit keys from `$min` onward until
			 * `hasOffsetValueType` answers `no`. Each probe contributes one
			 * optional (or required, when `hasOffsetValueType` is `yes`)
			 * slot. */
			zend_long unsealedness = thisIsUnsealed();
			if (UNEXPECTED(unsealedness < 0)) return zv::Val();
			bool isUnsealed = unsealedness == PT_TRI_YES;
			for (zend_long i = min.value;; i++) {
				zv::Val offsetType = pt_type_new_constant_integer(i);
				if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
				zend_long hasOffset = thisHasOffsetValueType(offsetType.raw());
				if (UNEXPECTED(hasOffset < 0)) return zv::Val();
				if (hasOffset == PT_TRI_NO) break;
				/* Real unsealed extras make `hasOffsetValueType` answer
				 * `Maybe` for *any* in-range key, so the probe would
				 * otherwise run until `ARRAY_COUNT_LIMIT` bails (slow +
				 * lossy). Stop once the explicit keys are exhausted; the
				 * unsealed slot attached below covers further entries. */
				if (isUnsealed && hasOffset != PT_TRI_YES) break;
				if (UNEXPECTED(!pushBuilderData(builderData, i, hasOffset != PT_TRI_YES))) return zv::Val();
			}
		}

		if ((zend_long) builderData.arrRef().size() > limit) return intersectedWithNonEmpty();

		zv::Val builder = builderCreateEmpty();
		if (UNEXPECTED(builder.isUndef())) return zv::Val();
		for (zv::ArrayEntry entry : builderData.arrRef()) {
			zval *triple = entry.value().deref().raw();
			zval *offsetType = zend_hash_index_find(Z_ARRVAL_P(triple), 0);
			zval *valueType = zend_hash_index_find(Z_ARRVAL_P(triple), 1);
			zval *optional = zend_hash_index_find(Z_ARRVAL_P(triple), 2);
			ZEND_ASSERT(offsetType != NULL && valueType != NULL && optional != NULL);
			if (UNEXPECTED(!builderSet(builder.raw(), offsetType, valueType, Z_TYPE_P(optional) == IS_TRUE ? 1 : 0))) return zv::Val();
		}

		/* Carry the unsealed slot through only for the unbounded-max branch
		 * — a bounded-max range caps the result size and the unsealed extras
		 * can't fit. */
		if (max.isNull) {
			zend_long unsealedness = thisIsUnsealed();
			if (UNEXPECTED(unsealedness < 0)) return zv::Val();
			zval *unsealed = unsealedSlot();
			if (UNEXPECTED(unsealed == NULL)) return zv::Val();
			if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
				zval *unsealedKey, *unsealedValue;
				if (UNEXPECTED(!unsealedPair(unsealed, unsealedKey, unsealedValue) || !builderMakeUnsealed(builder.raw(), unsealedKey, unsealedValue))) {
					return zv::Val();
				}
			}
		}

		zv::Val builtArray = builderGetArray(builder.raw());
		if (UNEXPECTED(builtArray.isUndef())) return zv::Val();
		/* `setOffsetValueType` on a brand-new builder produces a list when
		 * the resulting offsets are sequential ints — but it may not preserve
		 * list-ness in every shape. Reattach it for the single-CAT case. */
		bool builderList;
		if (UNEXPECTED(!builderIsList(builder.raw(), builderList))) return zv::Val();
		if (!builderList) {
			zv::Val constantArrays = pt_type_call_array(Z_OBJ_P(builtArray.raw()), PT_LC("getconstantarrays"), 0, NULL);
			if (UNEXPECTED(constantArrays.isUndef())) return zv::Val();
			if (arrayCount(constantArrays.raw()) == 1) {
				zval *only = arrayIndexObject(constantArrays.raw(), 0, "constantArrays");
				if (UNEXPECTED(only == NULL)) return zv::Val();
				builtArray = callType(Z_OBJ_P(only), PT_LC("makelist"), 0, NULL);
				if (UNEXPECTED(builtArray.isUndef())) return zv::Val();
			}
		}

		return builtArray;
	}

	/* Extracts (min, max) bounds from a size type for `truncateListToSize`.
	 * `ConstantIntegerType(N)` → `[N, N]`. `IntegerRangeType` →
	 * `[$min, $max]`. Anything else returns `[null, null]` and the caller
	 * falls back to the non-precise path; UNDEF = pending exception */
	static zv::Val extractTruncateListBounds(zval *sizeType)
	{
		NullableLong min, max;
		if (UNEXPECTED(!extractTruncateListBoundsValues(sizeType, min, max))) return zv::Val();
		zv::Arr bounds = zv::Arr::create(2);
		bounds.push(min.toVal());
		bounds.push(max.toVal());
		return zv::Val(std::move(bounds));
	}

	/* no for a sealed empty shape (maybe for an unsealed one), yes with a
	 * required key, maybe otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isIterableAtLeastOnce() const
	{
		zval *k = keyTypes();
		if (UNEXPECTED(k == NULL)) return -1;
		zend_long keysCount = arrayCount(k);
		if (keysCount == 0) {
			zend_long unsealedness = thisIsUnsealed();
			if (UNEXPECTED(unsealedness < 0)) return -1;
			return unsealedness == PT_TRI_YES ? PT_TRI_MAYBE : PT_TRI_NO;
		}

		zval *o = optionalKeys();
		if (UNEXPECTED(o == NULL)) return -1;
		if (arrayCount(o) < keysCount) return PT_TRI_YES;

		return PT_TRI_MAYBE;
	}

	/* the exact count for a sealed shape without optional keys, else the
	 * range from the required count to the total (unbounded for an unsealed
	 * shape); UNDEF = pending exception */
	zv::Val getArraySize() const
	{
		zval *o = optionalKeys();
		if (UNEXPECTED(o == NULL)) return zv::Val();
		zend_long optionalKeysCount = arrayCount(o);
		zv::Val keyTypes = thisGetKeyTypes();
		if (UNEXPECTED(keyTypes.isUndef())) return zv::Val();
		zend_long totalKeysCount = arrayCount(keyTypes.raw());
		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		NullableLong max;
		if (unsealedness != PT_TRI_YES) {
			if (optionalKeysCount == 0) return pt_type_new_constant_integer(totalKeysCount);
			max = NullableLong::of(totalKeysCount);
		} else {
			max = NullableLong::null();
		}

		return pt_integer_range_from_interval(NullableLong::of(totalKeysCount - optionalKeysCount), max, 0);
	}

	/* the union of the leading keys up to the first required one, and the
	 * unsealed key type (mixed substituted); UNDEF = pending exception */
	zv::Val getFirstIterableKeyType() const { return leadingUnion(true, true); }
	zv::Val getLastIterableKeyType() const { return leadingUnion(true, false); }
	zv::Val getFirstIterableValueType() const { return leadingUnion(false, true); }
	zv::Val getLastIterableValueType() const { return leadingUnion(false, false); }

	static zend_long isConstantArray() { return PT_TRI_YES; }

	zv::Val isList() const
	{
		zval *l = isListSlot();
		return l == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(l));
	}

	/* private: the last $length keys removed — for an unsealed shape only
	 * made optional (the removed elements may come from the extras); the
	 * next auto index reset to a removed integer key; a removed optional
	 * key makes a preceding required key optional; UNDEF = pending
	 * exception */
	zv::Val removeLastElements(zend_long length) const
	{
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *n = v != NULL ? nextAutoIndexes() : NULL;
		zval *o = n != NULL ? optionalKeys() : NULL;
		zval *l = o != NULL ? isListSlot() : NULL;
		zval *u = l != NULL ? unsealedSlot() : NULL;
		if (UNEXPECTED(u == NULL)) return zv::Val();
		zend_long keyTypesCount = arrayCount(k);
		if (keyTypesCount == 0) return zv::Val::copyOf(zv::Ref(thisZv()));

		/* With real unsealed extras on the source, the elements being
		 * "removed" might come from the unsealed range rather than from the
		 * trailing explicit keys — the array might have zero extras (so the
		 * trailing explicit keys are popped) or one+ extras (so they're
		 * popped instead, leaving the explicit keys intact). Encode this by
		 * marking the trailing keys as optional and keeping the unsealed
		 * slot in place. */
		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		if (unsealedness == PT_TRI_YES) {
			zv::Arr optionalKeysCopy = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(o)));
			zend_long newLength = keyTypesCount - length;
			for (zend_long i = keyTypesCount - 1; i >= (newLength > 0 ? newLength : 0); i--) {
				if (inArrayStrictLong(optionalKeysCopy.raw(), i)) continue;
				optionalKeysCopy.push(zv::Val::integer(i));
			}

			zv::Arr renumbered = arrayValues(optionalKeysCopy.raw());
			return thisRecreate(k, v, n, renumbered.raw(), l, u);
		}

		zv::Arr keyTypesCopy = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(k)));
		zv::Arr valueTypesCopy = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(v)));
		zv::Arr optionalKeysCopy = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(o)));
		zv::Val nextAutoindexes = zv::Val::copyOf(zv::Ref(n));

		zend_long optionalKeysRemoved = 0;
		zend_long newLength = keyTypesCount - length;
		for (zend_long i = keyTypesCount - 1; i >= 0; i--) {
			bool isOptional;
			if (UNEXPECTED(!thisIsOptionalKey(i, isOptional))) return zv::Val();

			if (i >= newLength) {
				if (isOptional) {
					optionalKeysRemoved++;
					optionalKeysCopy.separate();
					for (zv::ArrayEntry entry : optionalKeysCopy.arrRef()) {
						zval *value = entry.value().deref().raw();
						if (Z_TYPE_P(value) == IS_LONG && Z_LVAL_P(value) == i) {
							if (entry.hasStringKey()) {
								zend_hash_del(optionalKeysCopy.table(), entry.stringKey());
							} else {
								zend_hash_index_del(optionalKeysCopy.table(), entry.indexKey());
							}
							break;
						}
					}
				}

				/* $removedKeyType = array_pop($keyTypes); array_pop($valueTypes); */
				zv::Val removedKeyType = lastValue(keyTypesCopy.raw());
				arrayPop(keyTypesCopy.raw());
				arrayPop(valueTypesCopy.raw());
				if (!removedKeyType.isUndef() && zv::Ref(removedKeyType.raw()).instanceOf(pt_ce_constant_integer_type)) {
					zend_long removedValue;
					if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(removedKeyType.raw()), removedValue))) return zv::Val();
					zv::Arr reset = zv::Arr::create(1);
					reset.push(zv::Val::integer(removedValue));
					nextAutoindexes = zv::Val(std::move(reset));
				} else {
					nextAutoindexes = zv::Val::copyOf(zv::Ref(n));
				}
				continue;
			}

			if (isOptional || optionalKeysRemoved <= 0) continue;

			optionalKeysCopy.push(zv::Val::integer(i));
			optionalKeysRemoved--;
		}

		zv::Arr renumbered = arrayValues(optionalKeysCopy.raw());
		return thisRecreate(keyTypesCopy.raw(), valueTypesCopy.raw(), nextAutoindexes.raw(), renumbered.raw(), l, u);
	}

	/* private: the first $length keys dropped (a dropped optional key makes
	 * a following required key optional), integer keys renumbered when
	 * reindexing, the unsealed pair carried; UNDEF = pending exception */
	zv::Val removeFirstElements(zend_long length, bool reindex) const
	{
		zv::Val builder = builderCreateEmpty();
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		if (UNEXPECTED(builder.isUndef() || v == NULL)) return zv::Val();

		zend_long optionalKeysIgnored = 0;
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			bool isOptional;
			if (UNEXPECTED(!thisIsOptionalKey(i, isOptional))) return zv::Val();
			if (i <= length - 1) {
				if (isOptional) {
					optionalKeysIgnored++;
				}
				continue;
			}

			if (!isOptional && optionalKeysIgnored > 0) {
				isOptional = true;
				optionalKeysIgnored--;
			}

			zval *valueType = arrayIndexObject(v, i, "valueTypes");
			if (UNEXPECTED(valueType == NULL)) return zv::Val();
			zval *offsetType = keyType;
			if (reindex && Z_TYPE_P(keyType) == IS_OBJECT && instanceof_function(Z_OBJCE_P(keyType), pt_ce_constant_integer_type)) {
				offsetType = NULL;
			}

			if (UNEXPECTED(!builderSet(builder.raw(), offsetType, valueType, isOptional ? 1 : 0))) return zv::Val();
		}

		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
			/* `array_shift` removes the *first* element. The explicit keys
			 * precede the unsealed extras in insertion order, so the shift
			 * always lands on an explicit key (when there is one); the
			 * unsealed slot is unaffected. Re-indexing of int keys doesn't
			 * change the unsealed range — it stays `<int, V>`. */
			zval *unsealedKey, *unsealedValue;
			if (UNEXPECTED(!unsealedPair(unsealed, unsealedKey, unsealedValue) || !builderMakeUnsealed(builder.raw(), unsealedKey, unsealedValue))) {
				return zv::Val();
			}
		}

		return builderGetArray(builder.raw());
	}

	/* $this->getArraySize()->toBoolean() */
	zv::Val toBoolean() const
	{
		zv::Val size = thisGetArraySize();
		if (UNEXPECTED(size.isUndef())) return zv::Val();
		return callType(Z_OBJ_P(size.raw()), PT_LC("toboolean"), 0, NULL);
	}

	/* $this->toBoolean()->toInteger() / ->toFloat() */
	zv::Val toInteger() const { return booleanTo(PT_LC("tointeger")); }
	zv::Val toFloat() const { return booleanTo(PT_LC("tofloat")); }

	/* $this for a sealed empty shape; the values generalized for a
	 * template argument; else the general array of the generalized key and
	 * value types with, for more-specific precision and few required keys,
	 * a HasOffsetValueType per required key, a non-empty accessory for a
	 * non-empty shape otherwise, and a list accessory for a list; UNDEF =
	 * pending exception */
	zv::Val generalize(zval *precision) const
	{
		zval *k = keyTypes();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		/* No explicit keys and no real extras — actually empty, return as-is. */
		if (arrayCount(k) == 0 && unsealedness != PT_TRI_YES) return zv::Val::copyOf(zv::Ref(thisZv()));

		zv::Val isTemplateArgument = pt_type_call(Z_OBJ_P(precision), PT_LC("istemplateargument"), 0, NULL);
		if (UNEXPECTED(isTemplateArgument.isUndef())) return zv::Val();
		if (zend_is_true(isTemplateArgument.raw())) {
			zv::Val callback = pt_type_native_callback(generalizeCallback, precision, NULL);
			if (UNEXPECTED(callback.isUndef())) return zv::Val();
			return thisTraverse(callback.raw());
		}

		zv::Val iterableKeyType = thisGetIterableKeyType();
		if (UNEXPECTED(iterableKeyType.isUndef())) return zv::Val();
		zv::Val generalizedKeyType = callType(Z_OBJ_P(iterableKeyType.raw()), PT_LC("generalize"), 1, precision);
		if (UNEXPECTED(generalizedKeyType.isUndef())) return zv::Val();
		zv::Val iterableValueType = thisGetIterableValueType();
		if (UNEXPECTED(iterableValueType.isUndef())) return zv::Val();
		zv::Val generalizedValueType = callType(Z_OBJ_P(iterableValueType.raw()), PT_LC("generalize"), 1, precision);
		if (UNEXPECTED(generalizedValueType.isUndef())) return zv::Val();
		zv::Val array = arrayType(generalizedKeyType.raw(), generalizedValueType.raw());
		if (UNEXPECTED(array.isUndef())) return zv::Val();

		zval *v = valueTypes();
		zval *o = v != NULL ? optionalKeys() : NULL;
		if (UNEXPECTED(o == NULL)) return zv::Val();
		zend_long keyTypesCount = arrayCount(k);
		zend_long optionalKeysCount = arrayCount(o);

		zv::Arr accessoryTypes = zv::Arr::create(4);
		zv::Val isMoreSpecific = pt_type_call(Z_OBJ_P(precision), PT_LC("ismorespecific"), 0, NULL);
		if (UNEXPECTED(isMoreSpecific.isUndef())) return zv::Val();
		if (zend_is_true(isMoreSpecific.raw()) && (keyTypesCount - optionalKeysCount) < PT_CAT_GENERALIZE_OFFSET_ACCESSORIES_LIMIT) {
			for (zv::ArrayEntry entry : zv::ArrRef(k)) {
				zend_long i = (zend_long) entry.indexKey();
				zval *keyType = entry.value().deref().raw();
				bool optional;
				if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return zv::Val();
				if (optional) continue;
				zval *valueType = arrayIndexObject(v, i, "valueTypes");
				if (UNEXPECTED(valueType == NULL)) return zv::Val();
				zv::Val generalizedValue = callType(Z_OBJ_P(valueType), PT_LC("generalize"), 1, precision);
				if (UNEXPECTED(generalizedValue.isUndef())) return zv::Val();
				zv::Val accessory = newHasOffsetValueType(keyType, generalizedValue.raw());
				if (UNEXPECTED(accessory.isUndef())) return zv::Val();
				accessoryTypes.push(std::move(accessory));
			}
		} else {
			zend_long atLeastOnce = thisIsIterableAtLeastOnce();
			if (UNEXPECTED(atLeastOnce < 0)) return zv::Val();
			if (atLeastOnce == PT_TRI_YES) {
				/* Previously gated on `keyTypesCount > optionalKeysCount`,
				 * which mishandles "no explicit keys + real unsealed extras"
				 * (`isIterableAtLeastOnce()` answers `Maybe` — extras might
				 * be empty — and correctly skips `NonEmptyArrayType`). The
				 * new gate also covers the usual sealed-with-required-keys
				 * case, so behaviour for existing CAT shapes is unchanged. */
				zv::Val nonEmpty = nonEmptyArray();
				if (UNEXPECTED(nonEmpty.isUndef())) return zv::Val();
				accessoryTypes.push(std::move(nonEmpty));
			}
		}

		zend_long isListValue = thisIsList();
		if (UNEXPECTED(isListValue < 0)) return zv::Val();
		if (isListValue == PT_TRI_YES) {
			zv::Val list = accessoryList();
			if (UNEXPECTED(list.isUndef())) return zv::Val();
			array = combinator2(PT_LC("intersect"), array.raw(), list.raw());
			if (UNEXPECTED(array.isUndef())) return zv::Val();
		}

		if (accessoryTypes.arrRef().size() > 0) {
			zv::Arr args = zv::Arr::create(accessoryTypes.arrRef().size() + 1);
			args.push(std::move(array));
			for (zv::ArrayEntry entry : accessoryTypes.arrRef()) {
				args.push(entry.value());
			}
			return combinatorSpread(PT_LC("intersect"), args.table());
		}

		return array;
	}

	/* the values (and the unsealed value type) generalized to less
	 * specific; UNDEF = pending exception */
	zv::Val generalizeValues() const
	{
		/* the twin's read order: $valueTypes, $unsealed, then recreate()'s arguments */
		zval *v = valueTypes();
		zval *u = v != NULL ? unsealedSlot() : NULL;
		zval *k = u != NULL ? keyTypes() : NULL;
		zval *n = k != NULL ? nextAutoIndexes() : NULL;
		zval *o = n != NULL ? optionalKeys() : NULL;
		zval *l = o != NULL ? isListSlot() : NULL;
		if (UNEXPECTED(l == NULL)) return zv::Val();
		zv::Arr valueTypesGeneralized = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(v)));
		for (zv::ArrayEntry entry : zv::ArrRef(v)) {
			zval *valueType = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(valueType) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: valueTypes must hold %s instances", ptcls::type);
				return zv::Val();
			}
			zv::Val precision = pt_type_call_static(PT_CLASS_GENERALIZE_PRECISION, PT_LC("lessspecific"), 0, NULL);
			if (UNEXPECTED(precision.isUndef())) return zv::Val();
			zv::Val generalized = callType(Z_OBJ_P(valueType), PT_LC("generalize"), 1, precision.raw());
			if (UNEXPECTED(generalized.isUndef())) return zv::Val();
			valueTypesGeneralized.push(std::move(generalized));
		}

		zv::Val unsealed = zv::Val::copyOf(zv::Ref(u));
		if (Z_TYPE_P(u) != IS_NULL) {
			zval *unsealedKey, *unsealedValue;
			if (UNEXPECTED(!unsealedPair(u, unsealedKey, unsealedValue))) return zv::Val();
			zv::Val precision = pt_type_call_static(PT_CLASS_GENERALIZE_PRECISION, PT_LC("lessspecific"), 0, NULL);
			if (UNEXPECTED(precision.isUndef())) return zv::Val();
			zv::Val generalizedValue = callType(Z_OBJ_P(unsealedValue), PT_LC("generalize"), 1, precision.raw());
			if (UNEXPECTED(generalizedValue.isUndef())) return zv::Val();
			unsealed = pairOf(unsealedKey, generalizedValue.raw());
		}

		return thisRecreate(k, valueTypesGeneralized.raw(), n, o, l, unsealed.raw());
	}

	/* private: the builder over this shape degraded to a general array */
	zv::Val degradeToGeneralArray() const
	{
		zv::Val builder = builderCreateFromConstantArray(thisZv());
		if (UNEXPECTED(builder.isUndef() || !builderCall0(builder.raw(), PT_LC("degradetogeneralarray")))) return zv::Val();
		return builderGetArray(builder.raw());
	}

	/* a list of the keys' value type */
	zv::Val getKeysArrayFiltered() const
	{
		zval *k = keyTypes();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zv::Val keysArray = getKeysOrValuesArray(k, unsealedSourceType(0));
		if (UNEXPECTED(keysArray.isUndef())) return zv::Val();
		zval zero;
		ZVAL_LONG(&zero, 0);
		zv::Val keyType = pt_integer_range_create_all_greater_than_or_equal_to(&zero);
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		zv::Val valueType = callType(Z_OBJ_P(keysArray.raw()), PT_LC("getiterablevaluetype"), 0, NULL);
		if (UNEXPECTED(valueType.isUndef())) return zv::Val();
		zv::Val array = arrayType(keyType.raw(), valueType.raw());
		zv::Val list = accessoryList();
		if (UNEXPECTED(array.isUndef() || list.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(array));
		types.push(std::move(list));
		return intersection(std::move(types));
	}

	zv::Val getKeysArray() const
	{
		zval *k = keyTypes();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		return getKeysOrValuesArray(k, unsealedSourceType(0));
	}

	zv::Val getValuesArray() const
	{
		zval *v = valueTypes();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		return getKeysOrValuesArray(v, unsealedSourceType(1));
	}

	/* private: the types as a list — numbered straight for a list, else
	 * each value the union of the values up to the next required key
	 * (optional past the last one); the next indexes from the required
	 * count to the count; an unsealed source's extras at int<0, max>;
	 * unsealedSourceType NULL = the twin's null; UNDEF = pending exception */
	zv::Val getKeysOrValuesArray(zval *types, zval *unsealedSourceTypeArg) const
	{
		zval *o = optionalKeys();
		zval *n = o != NULL ? nextAutoIndexes() : NULL;
		zval *l = n != NULL ? isListSlot() : NULL;
		if (UNEXPECTED(l == NULL)) return zv::Val();
		zend_long count = arrayCount(types);
		zv::Arr autoIndexes = rangeLongs(count - arrayCount(o), count);

		/* The result is always a list — the source's keys/values are numbered
		 * sequentially. The new unsealed slot (if the source has real extras)
		 * describes "zero or more extras at int positions >= 0 whose values
		 * are the source's unsealed key/value type". `int<0, max>` is the
		 * conventional unsealed key for list-shaped extras; it also enables
		 * the short-form `<value>` describe. */
		zv::Val resultUnsealed = zv::Val::null();
		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		if (unsealedness == PT_TRI_YES && unsealedSourceTypeArg != NULL) {
			zval zero;
			ZVAL_LONG(&zero, 0);
			zv::Val keyType = pt_integer_range_create_all_greater_than_or_equal_to(&zero);
			if (UNEXPECTED(keyType.isUndef())) return zv::Val();
			resultUnsealed = pairOf(keyType.raw(), unsealedSourceTypeArg);
		}

		zend_long isListValue = trinaryOf(l);
		if (UNEXPECTED(isListValue < 0)) return zv::Val();
		if (isListValue == PT_TRI_YES) {
			/* Optimized version for lists: Assume that if a later key exists, then earlier keys also exist. */
			zv::Arr keyTypesList = zv::Arr::create((uint32_t) count);
			for (zv::ArrayEntry entry : zv::ArrRef(types)) {
				zv::Val keyType = pt_type_new_constant_integer((zend_long) entry.indexKey());
				if (UNEXPECTED(keyType.isUndef())) return zv::Val();
				keyTypesList.push(std::move(keyType));
			}
			return thisRecreate(keyTypesList.raw(), types, autoIndexes.raw(), o, pt_trinary_singleton(PT_TRI_YES), resultUnsealed.raw());
		}

		zv::Arr keyTypesList = zv::Arr::create((uint32_t) count);
		zv::Arr valueTypesList = zv::Arr::create((uint32_t) count);
		zv::Arr optionalKeysList = zv::Arr::create((uint32_t) count);
		zend_long maxIndex = 0;

		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			zend_long i = (zend_long) entry.indexKey();
			zv::Val keyType = pt_type_new_constant_integer(i);
			if (UNEXPECTED(keyType.isUndef())) return zv::Val();
			keyTypesList.push(std::move(keyType));

			bool optional;
			if (UNEXPECTED(!thisIsOptionalKey(maxIndex, optional))) return zv::Val();
			if (optional) {
				/* move $maxIndex to next non-optional key */
				do {
					maxIndex++;
					if (maxIndex >= count) break;
					if (UNEXPECTED(!thisIsOptionalKey(maxIndex, optional))) return zv::Val();
				} while (optional);
			}

			if (i == maxIndex) {
				valueTypesList.push(entry.value());
			} else {
				/* TypeCombinator::union(...array_slice($types, $i, $maxIndex - $i + 1)) */
				zv::Arr slice = arraySlice(types, i, maxIndex - i + 1);
				zv::Val unionType = combinatorSpread(PT_LC("union"), slice.table());
				if (UNEXPECTED(unionType.isUndef())) return zv::Val();
				valueTypesList.push(std::move(unionType));
				if (maxIndex >= count) {
					optionalKeysList.push(zv::Val::integer(i));
				}
			}
			maxIndex++;
		}

		return thisRecreate(keyTypesList.raw(), valueTypesList.raw(), autoIndexes.raw(), optionalKeysList.raw(), pt_trinary_singleton(PT_TRI_YES), resultUnsealed.raw());
	}

	/* $level->handle(): 'array'/'list' (a possibly-non-empty one with its
	 * key and value types) at the type-only level, the shape at the value
	 * level (truncated past DESCRIBE_LIMIT items) and precise level (in
	 * full), with the unsealed pair after '...'; UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		bool asList;
		if (UNEXPECTED(!shouldBeDescribedAsAList(asList))) return zv::Val();
		const char *arrayName = asList ? "list" : "array";

		pt_verbosity_case which;
		if (UNEXPECTED(!pt_type_verbosity_case(level, which))) return zv::Val();
		if (which == PT_VERBOSITY_TYPE_ONLY) {
			zend_long atLeastOnce = thisIsIterableAtLeastOnce();
			if (UNEXPECTED(atLeastOnce < 0)) return zv::Val();
			if (atLeastOnce == PT_TRI_NO) return zv::Val::string(arrayName, strlen(arrayName));
			zv::Val keyType = thisGetIterableKeyType();
			if (UNEXPECTED(keyType.isUndef())) return zv::Val();
			/* Only a BenevolentUnionType describes with the surrounding
			 * parentheses of '(int|string)' / '(int|non-decimal-int-string)',
			 * so skip the describe() call for every other key type. */
			bool isBenevolent;
			if (UNEXPECTED(!pt_type_instanceof_ce(keyType.raw(), pt_ce_benevolent_union_type, isBenevolent))) return zv::Val();
			if (isBenevolent) {
				zv::Val keyDescription = describeValue(keyType.raw());
				if (UNEXPECTED(keyDescription.isUndef())) return zv::Val();
				zv::Ref d(keyDescription.raw());
				if (d.stringEquals("(int|string)") || d.stringEquals("(int|non-decimal-int-string)")) {
					zv::Val valueType = thisGetIterableValueType();
					if (UNEXPECTED(valueType.isUndef())) return zv::Val();
					zv::Val valueDescription = describeOf(valueType.raw(), level);
					if (UNEXPECTED(valueDescription.isUndef())) return zv::Val();
					return zv::Val::adoptString(zend_strpprintf(0, "%s<%s>", arrayName, ZSTR_VAL(zv::Ref(valueDescription.raw()).asString())));
				}
			}
			zv::Val keyDescription = describeOf(keyType.raw(), level);
			if (UNEXPECTED(keyDescription.isUndef())) return zv::Val();
			zv::Val valueType = thisGetIterableValueType();
			if (UNEXPECTED(valueType.isUndef())) return zv::Val();
			zv::Val valueDescription = describeOf(valueType.raw(), level);
			if (UNEXPECTED(valueDescription.isUndef())) return zv::Val();
			return zv::Val::adoptString(zend_strpprintf(0, "%s<%s, %s>", arrayName, ZSTR_VAL(zv::Ref(keyDescription.raw()).asString()), ZSTR_VAL(zv::Ref(valueDescription.raw()).asString())));
		}
		/* the value level truncates; the precise level (and the cache level,
		 * which handle() routes to the precise callback) does not */
		return describeValueLevel(level, arrayName, which == PT_VERBOSITY_VALUE);
	}

	/* private: a definite list with more than one optional key, or one
	 * that is not the last key; false = pending exception */
	[[nodiscard]] bool shouldBeDescribedAsAList(bool &out) const
	{
		zval *l = isListSlot();
		zval *o = l != NULL ? optionalKeys() : NULL;
		if (UNEXPECTED(o == NULL)) return false;
		zend_long isListValue = trinaryOf(l);
		if (UNEXPECTED(isListValue < 0)) return false;
		if (isListValue != PT_TRI_YES) {
			out = false;
			return true;
		}
		zend_long optionalCount = arrayCount(o);
		if (optionalCount == 0) {
			out = false;
			return true;
		}
		if (optionalCount > 1) {
			out = true;
			return true;
		}
		zval *k = keyTypes();
		zval *first = k != NULL ? zend_hash_index_find(Z_ARRVAL_P(o), 0) : NULL;
		if (UNEXPECTED(k == NULL)) return false;
		/* $this->optionalKeys[0] !== count($this->keyTypes) - 1 */
		out = first == NULL || Z_TYPE_P(first) != IS_LONG || Z_LVAL_P(first) != arrayCount(k) - 1;
		return true;
	}

	/* the union/intersection callback; for another shape the per-key
	 * inference over the keys it has, the unsealed key/value inference over
	 * its extra keys that fit the unsealed key type, and its own unsealed
	 * pair (never for a key type outside ours); for an array the key and
	 * value maps unioned; the empty map otherwise; UNDEF = pending
	 * exception */
	zv::Val inferTemplateTypes(zval *receivedType) const
	{
		bool isUnion, isIntersection = false;
		if (UNEXPECTED(!pt_type_instanceof_ce(receivedType, pt_ce_union_type, isUnion))) return zv::Val();
		if (!isUnion && UNEXPECTED(!pt_type_instanceof_ce(receivedType, pt_ce_intersection_type, isIntersection))) return zv::Val();
		if (isUnion || isIntersection) return pt_type_call(Z_OBJ_P(receivedType), PT_LC("infertemplatetypeson"), 1, thisZv());

		if (instanceof_function(Z_OBJCE_P(receivedType), pt_ce_constant_array_type)) {
			zend_object *received = Z_OBJ_P(receivedType);
			zv::Val typeMap = pt_type_template_type_map_empty();
			zval *k = keyTypes();
			zval *v = k != NULL ? valueTypes() : NULL;
			if (UNEXPECTED(typeMap.isUndef() || v == NULL)) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(k)) {
				zend_long i = (zend_long) entry.indexKey();
				zval *keyType = entry.value().deref().raw();
				zval *valueType = arrayIndexObject(v, i, "valueTypes");
				if (UNEXPECTED(valueType == NULL)) return zv::Val();
				zend_long has = pt_type_call_trinary(received, PT_LC("hasoffsetvaluetype"), 1, keyType);
				if (UNEXPECTED(has < 0)) return zv::Val();
				if (has == PT_TRI_NO) continue;
				zv::Val receivedValueType = callType(received, PT_LC("getoffsetvaluetype"), 1, keyType);
				if (UNEXPECTED(receivedValueType.isUndef())) return zv::Val();
				if (UNEXPECTED(!unionInto(typeMap, valueType, receivedValueType.raw()))) return zv::Val();
			}

			zv::Val unsealed = thisGetUnsealedTypes();
			if (UNEXPECTED(unsealed.isUndef())) return zv::Val();
			if (!unsealed.isNull()) {
				zval *unsealedKeyType, *unsealedValueType;
				if (UNEXPECTED(!unsealedPair(unsealed.raw(), unsealedKeyType, unsealedValueType))) return zv::Val();

				/* Received's explicit keys not in $this's explicit keys are
				 * candidates for matching $this's unsealed extras pattern.
				 * Only contribute when the key type matches; mismatched
				 * explicit keys are extra entries the parameter wouldn't
				 * accept anyway, surfaced by the regular argument-type check. */
				zv::Val receivedKeyTypes = pt_type_call_array(received, PT_LC("getkeytypes"), 0, NULL);
				if (UNEXPECTED(receivedKeyTypes.isUndef())) return zv::Val();
				zv::Val receivedValueTypes = pt_type_call_array(received, PT_LC("getvaluetypes"), 0, NULL);
				if (UNEXPECTED(receivedValueTypes.isUndef())) return zv::Val();
				for (zv::ArrayEntry entry : zv::ArrRef(receivedKeyTypes.raw())) {
					zend_long j = (zend_long) entry.indexKey();
					zval *receivedKeyType = entry.value().deref().raw();
					if (UNEXPECTED(Z_TYPE_P(receivedKeyType) != IS_OBJECT)) {
						zend_type_error("phpstan_turbo: getKeyTypes() must return %s instances", ptcls::type);
						return zv::Val();
					}
					zend_long has = thisHasOffsetValueType(receivedKeyType);
					if (UNEXPECTED(has < 0)) return zv::Val();
					if (has == PT_TRI_YES) continue;
					zend_long covers = isSuperTypeOfValue(unsealedKeyType, receivedKeyType);
					if (UNEXPECTED(covers < 0)) return zv::Val();
					if (covers != PT_TRI_YES) continue;
					if (UNEXPECTED(!unionInto(typeMap, unsealedKeyType, receivedKeyType))) return zv::Val();
					zval *receivedValueType = arrayIndexObject(receivedValueTypes.raw(), j, "valueTypes");
					if (UNEXPECTED(receivedValueType == NULL || !unionInto(typeMap, unsealedValueType, receivedValueType))) return zv::Val();
				}

				/* Received's own unsealed extras describe "all the rest" —
				 * when the key type doesn't fit $this's unsealed key pattern
				 * there is no valid template assignment, so force NEVER. */
				zv::Val receivedUnsealed = pt_type_call(received, PT_LC("getunsealedtypes"), 0, NULL);
				if (UNEXPECTED(receivedUnsealed.isUndef())) return zv::Val();
				if (!receivedUnsealed.isNull()) {
					zval *receivedUnsealedKey, *receivedUnsealedValue;
					if (UNEXPECTED(!unsealedPair(receivedUnsealed.raw(), receivedUnsealedKey, receivedUnsealedValue))) return zv::Val();
					zend_long covers = isSuperTypeOfValue(unsealedKeyType, receivedUnsealedKey);
					if (UNEXPECTED(covers < 0)) return zv::Val();
					if (covers == PT_TRI_NO) {
						zv::Val never = neverType();
						if (UNEXPECTED(never.isUndef() || !unionInto(typeMap, unsealedValueType, never.raw()))) return zv::Val();
					} else {
						if (UNEXPECTED(!unionInto(typeMap, unsealedKeyType, receivedUnsealedKey) || !unionInto(typeMap, unsealedValueType, receivedUnsealedValue))) {
							return zv::Val();
						}
					}
				}
			}

			return typeMap;
		}

		zend_long isArray = pt_type_op_trinary(Z_OBJ_P(receivedType), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray < 0)) return zv::Val();
		if (isArray == PT_TRI_YES) {
			zv::Val keyType = thisGetIterableKeyType();
			if (UNEXPECTED(keyType.isUndef())) return zv::Val();
			zv::Val receivedKeyType = callType(Z_OBJ_P(receivedType), PT_LC("getiterablekeytype"), 0, NULL);
			if (UNEXPECTED(receivedKeyType.isUndef())) return zv::Val();
			zv::Val keyTypeMap = pt_type_call(Z_OBJ_P(keyType.raw()), PT_LC("infertemplatetypes"), 1, receivedKeyType.raw());
			if (UNEXPECTED(keyTypeMap.isUndef())) return zv::Val();
			zv::Val valueType = thisGetIterableValueType();
			if (UNEXPECTED(valueType.isUndef())) return zv::Val();
			zv::Val receivedValueType = callType(Z_OBJ_P(receivedType), PT_LC("getiterablevaluetype"), 0, NULL);
			if (UNEXPECTED(receivedValueType.isUndef())) return zv::Val();
			zv::Val itemTypeMap = pt_type_call(Z_OBJ_P(valueType.raw()), PT_LC("infertemplatetypes"), 1, receivedValueType.raw());
			if (UNEXPECTED(itemTypeMap.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(keyTypeMap.raw()).isObject())) {
				zend_type_error("phpstan_turbo: inferTemplateTypes() must return TemplateTypeMap");
				return zv::Val();
			}
			return pt_type_call(Z_OBJ_P(keyTypeMap.raw()), PT_LC("union"), 1, itemTypeMap.raw());
		}

		return pt_type_template_type_map_empty();
	}

	/* the referenced template types of the keys, the values and the
	 * unsealed pair under the covariant composition of the position's
	 * variance; UNDEF = pending exception */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const
	{
		zv::Val covariant = pt_type_template_type_variance(PT_TEMPLATE_TYPE_VARIANCE_COVARIANT);
		if (UNEXPECTED(covariant.isUndef())) return zv::Val();
		/* $positionVariance->compose(...) — the native body for the native class */
		zval composedVariance;
		if (UNEXPECTED(!pt_template_type_variance_compose(&composedVariance, positionVariance, covariant.raw()))) return zv::Val();
		zv::Val variance = zv::Val::adopt(composedVariance);
		zv::Arr references = zv::Arr::create(8);
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *u = v != NULL ? unsealedSlot() : NULL;
		if (UNEXPECTED(u == NULL)) return zv::Val();
		if (UNEXPECTED(!collectReferencedTemplateTypes(references, k, variance.raw()) || !collectReferencedTemplateTypes(references, v, variance.raw()))) {
			return zv::Val();
		}
		if (Z_TYPE_P(u) != IS_NULL) {
			zv::Val pair = zv::Val::copyOf(zv::Ref(u));
			if (UNEXPECTED(!collectReferencedTemplateTypes(references, pair.raw(), variance.raw()))) return zv::Val();
		}
		return zv::Val(std::move(references));
	}

	/* non-empty when an empty constant array is removed; the empty shape
	 * for a NonEmptyArrayType; for a HasOffsetValueType the matching key
	 * unset (never when that empties a definite list), its value narrowed,
	 * or null; for a HasOffsetType the offset unset likewise; null
	 * otherwise; UNDEF = pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		zend_long removeIsConstantArray = pt_type_op_trinary(Z_OBJ_P(typeToRemove), PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
		if (UNEXPECTED(removeIsConstantArray < 0)) return zv::Val();
		if (removeIsConstantArray == PT_TRI_YES) {
			zend_long atLeastOnce = pt_type_op_trinary(Z_OBJ_P(typeToRemove), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
			if (UNEXPECTED(atLeastOnce < 0)) return zv::Val();
			if (atLeastOnce == PT_TRI_NO) return intersectedWithNonEmpty();
		}

		zend_class_entry *removeCe = Z_OBJCE_P(typeToRemove);
		if (instanceof_function(removeCe, pt_ce_non_empty_array_type)) return createEmpty();

		if (instanceof_function(removeCe, pt_ce_has_offset_value_type)) {
			zv::Val offsetType = pt_has_offset_value_type_get_offset_type(Z_OBJ_P(typeToRemove));
			if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
			zv::Val valueTypeToRemove = pt_has_offset_value_type_get_value_type(Z_OBJ_P(typeToRemove));
			if (UNEXPECTED(valueTypeToRemove.isUndef())) return zv::Val();
			zv::Val offsetValue = keyValue(offsetType.raw());
			if (UNEXPECTED(offsetValue.isUndef())) return zv::Val();
			zval *k = keyTypes();
			zval *v = k != NULL ? valueTypes() : NULL;
			zval *n = v != NULL ? nextAutoIndexes() : NULL;
			zval *o = n != NULL ? optionalKeys() : NULL;
			zval *l = o != NULL ? isListSlot() : NULL;
			zval *u = l != NULL ? unsealedSlot() : NULL;
			if (UNEXPECTED(u == NULL)) return zv::Val();

			for (zv::ArrayEntry entry : zv::ArrRef(k)) {
				zend_long i = (zend_long) entry.indexKey();
				zv::Val value = keyValue(entry.value().deref().raw());
				if (UNEXPECTED(value.isUndef())) return zv::Val();
				if (!zend_is_identical(value.raw(), offsetValue.raw())) continue;

				zval *currentValueType = arrayIndexObject(v, i, "valueTypes");
				if (UNEXPECTED(currentValueType == NULL)) return zv::Val();
				zend_long valueIsSuperType = isSuperTypeOfValue(valueTypeToRemove.raw(), currentValueType);
				if (UNEXPECTED(valueIsSuperType < 0)) return zv::Val();

				if (valueIsSuperType == PT_TRI_NO) return zv::Val::null();

				if (valueIsSuperType == PT_TRI_YES) return unsetPreservingListCertainty(offsetType.raw(), l);

				zv::Val newValueType = combinator2(PT_LC("remove"), currentValueType, valueTypeToRemove.raw());
				if (UNEXPECTED(newValueType.isUndef())) return zv::Val();
				zv::Arr valueTypesCopy = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(v)));
				valueTypesCopy.separate();
				zv::ArrRef(valueTypesCopy.raw()).setIndex((zend_ulong) i, zv::Ref(newValueType.raw()));

				return thisRecreate(k, valueTypesCopy.raw(), n, o, l, u);
			}

			return zv::Val::null();
		}

		if (instanceof_function(removeCe, pt_ce_has_offset_type)) {
			zv::Val offsetType = pt_has_offset_type_get_offset_type(Z_OBJ_P(typeToRemove));
			zval *l = isListSlot();
			if (UNEXPECTED(offsetType.isUndef() || l == NULL)) return zv::Val();
			return unsetPreservingListCertainty(offsetType.raw(), l);
		}

		return zv::Val::null();
	}

	/* $this when the callback leaves every value and the unsealed pair,
	 * else the recreated shape over the transformed ones; UNDEF = pending
	 * exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *n = v != NULL ? nextAutoIndexes() : NULL;
		zval *o = n != NULL ? optionalKeys() : NULL;
		zval *l = o != NULL ? isListSlot() : NULL;
		zval *u = l != NULL ? unsealedSlot() : NULL;
		if (UNEXPECTED(u == NULL)) return zv::Val();
		zv::Arr valueTypesNew = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(v)));

		bool stillOriginal = true;
		for (zv::ArrayEntry entry : zv::ArrRef(v)) {
			zval *valueType = entry.value().deref().raw();
			zval transformed;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, valueType, &transformed))) return zv::Val();
			if (!sameObject(&transformed, valueType)) {
				stillOriginal = false;
			}
			valueTypesNew.push(zv::Val::adopt(transformed));
		}

		zv::Val unsealed = zv::Val::copyOf(zv::Ref(u));
		if (Z_TYPE_P(u) != IS_NULL) {
			zval *unsealedKeyType, *unsealedValueType;
			if (UNEXPECTED(!unsealedPair(u, unsealedKeyType, unsealedValueType))) return zv::Val();
			zval transformedKey, transformedValue;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, unsealedKeyType, &transformedKey))) return zv::Val();
			zv::Val transformedKeyType = zv::Val::adopt(transformedKey);
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, unsealedValueType, &transformedValue))) return zv::Val();
			zv::Val transformedValueType = zv::Val::adopt(transformedValue);
			if (!sameObject(transformedKeyType.raw(), unsealedKeyType) || !sameObject(transformedValueType.raw(), unsealedValueType)) {
				stillOriginal = false;
				unsealed = pairOf(transformedKeyType.raw(), transformedValueType.raw());
			}
		}

		if (stillOriginal) return zv::Val::copyOf(zv::Ref(thisZv()));

		return thisRecreate(k, valueTypesNew.raw(), n, o, l, unsealed.raw());
	}

	/* $this for a non-array; else traverse() with the right side's value
	 * at each key (its iterable key and value types for the unsealed
	 * pair) as the callback's second argument; UNDEF = pending exception */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zend_long rightIsArray = pt_type_op_trinary(Z_OBJ_P(right), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(rightIsArray < 0)) return zv::Val();
		if (rightIsArray != PT_TRI_YES) return zv::Val::copyOf(zv::Ref(thisZv()));

		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *n = v != NULL ? nextAutoIndexes() : NULL;
		zval *o = n != NULL ? optionalKeys() : NULL;
		zval *l = o != NULL ? isListSlot() : NULL;
		zval *u = l != NULL ? unsealedSlot() : NULL;
		if (UNEXPECTED(u == NULL)) return zv::Val();
		zv::Arr valueTypesNew = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(v)));

		bool stillOriginal = true;
		for (zv::ArrayEntry entry : zv::ArrRef(v)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *valueType = entry.value().deref().raw();
			zval *keyType = arrayIndexObject(k, i, "keyTypes");
			if (UNEXPECTED(keyType == NULL)) return zv::Val();
			zv::Val rightValue = callType(Z_OBJ_P(right), PT_LC("getoffsetvaluetype"), 1, keyType);
			if (UNEXPECTED(rightValue.isUndef())) return zv::Val();
			zv::Args args{valueType, rightValue.raw()};
			zval transformed;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, args, &transformed))) return zv::Val();
			if (!sameObject(&transformed, valueType)) {
				stillOriginal = false;
			}
			valueTypesNew.push(zv::Val::adopt(transformed));
		}

		zv::Val unsealed = zv::Val::copyOf(zv::Ref(u));
		if (Z_TYPE_P(u) != IS_NULL) {
			zval *unsealedKeyType, *unsealedValueType;
			if (UNEXPECTED(!unsealedPair(u, unsealedKeyType, unsealedValueType))) return zv::Val();
			zv::Val rightKey = callType(Z_OBJ_P(right), PT_LC("getiterablekeytype"), 0, NULL);
			if (UNEXPECTED(rightKey.isUndef())) return zv::Val();
			zv::Args args{unsealedKeyType, rightKey.raw()};
			zval transformedKey;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, args, &transformedKey))) return zv::Val();
			zv::Val transformedKeyType = zv::Val::adopt(transformedKey);
			zv::Val rightValue = callType(Z_OBJ_P(right), PT_LC("getiterablevaluetype"), 0, NULL);
			if (UNEXPECTED(rightValue.isUndef())) return zv::Val();
			ZVAL_COPY_VALUE(&args[0], unsealedValueType);
			ZVAL_COPY_VALUE(&args[1], rightValue.raw());
			zval transformedValue;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, args, &transformedValue))) return zv::Val();
			zv::Val transformedValueType = zv::Val::adopt(transformedValue);
			if (!sameObject(transformedKeyType.raw(), unsealedKeyType) || !sameObject(transformedValueType.raw(), unsealedValueType)) {
				stillOriginal = false;
				unsealed = pairOf(transformedKeyType.raw(), transformedValueType.raw());
			}
		}

		if (stillOriginal) return zv::Val::copyOf(zv::Ref(thisZv()));

		return thisRecreate(k, valueTypesNew.raw(), n, o, l, unsealed.raw());
	}

	/* the legacy shape check without definite pairs on both sides; a
	 * sealed empty other absorbed only into an all-optional shape; extras
	 * on both sides absorb anything; one side's extras absorb the other's
	 * required keys; false = pending exception */
	[[nodiscard]] bool isKeysSupersetOf(zval *otherArray, bool &out) const
	{
		zend_object *other = Z_OBJ_P(otherArray);
		zval *u = unsealedSlot();
		zval *otherU = u != NULL ? slotOf(other, slots::unsealed, "unsealed", true) : NULL;
		if (UNEXPECTED(otherU == NULL)) return false;
		if (Z_TYPE_P(u) == IS_NULL || Z_TYPE_P(otherU) == IS_NULL) return legacyIsKeysSupersetOf(otherArray, out);

		zval *thisUnsealedKey, *thisUnsealedValue, *otherUnsealedKey, *otherUnsealedValue;
		if (UNEXPECTED(!unsealedPair(u, thisUnsealedKey, thisUnsealedValue) || !unsealedPair(otherU, otherUnsealedKey, otherUnsealedValue))) return false;
		zend_long thisUnsealedness = thisIsUnsealed();
		if (UNEXPECTED(thisUnsealedness < 0)) return false;
		zend_long otherUnsealedness = pt_type_call_trinary(other, PT_LC("isunsealed"), 0, NULL);
		if (UNEXPECTED(otherUnsealedness < 0)) return false;
		bool thisHasExtras = thisUnsealedness == PT_TRI_YES;
		bool otherHasExtras = otherUnsealedness == PT_TRI_YES;

		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *otherK = v != NULL ? slotOf(other, slots::keyTypes, "keyTypes") : NULL;
		zval *otherV = otherK != NULL ? slotOf(other, slots::valueTypes, "valueTypes") : NULL;
		if (UNEXPECTED(otherV == NULL)) return false;

		bool otherHasRequiredKeys = false;
		for (zv::ArrayEntry entry : zv::ArrRef(otherK)) {
			bool optional;
			if (UNEXPECTED(!otherIsOptionalKey(otherArray, (zend_long) entry.indexKey(), optional))) return false;
			if (optional) continue;
			otherHasRequiredKeys = true;
			break;
		}

		/* Sealed empty $other (no keys, no extras): absorbing it is lossless
		 * iff $this already accepts []. i.e., all of $this's known keys are
		 * optional. Otherwise merge would add [] as a new instance. */
		if (!otherHasRequiredKeys && !otherHasExtras && arrayCount(otherK) == 0) {
			for (zv::ArrayEntry entry : zv::ArrRef(k)) {
				bool optional;
				if (UNEXPECTED(!thisIsOptionalKey((zend_long) entry.indexKey(), optional))) return false;
				if (!optional) {
					out = false;
					return true;
				}
			}
			out = true;
			return true;
		}

		/* With real unsealed extras on both sides that can absorb each
		 * other's required keys, merging is acceptable regardless of which
		 * keys overlap. */
		if (thisHasExtras && otherHasExtras) {
			out = true;
			return true;
		}

		/* Asymmetric extras: one side has real extras that can absorb the other's keys. */
		if (thisHasExtras) {
			bool legacy;
			if (UNEXPECTED(!legacyIsKeysSupersetOf(otherArray, legacy))) return false;
			if (legacy) {
				out = true;
				return true;
			}
			for (zv::ArrayEntry entry : zv::ArrRef(otherK)) {
				zend_long j = (zend_long) entry.indexKey();
				bool optional;
				if (UNEXPECTED(!otherIsOptionalKey(otherArray, j, optional))) return false;
				if (optional) continue;
				zend_long coversKey = isSuperTypeOfValue(thisUnsealedKey, entry.value().deref().raw());
				if (UNEXPECTED(coversKey < 0)) return false;
				if (coversKey == PT_TRI_NO) {
					out = false;
					return true;
				}
				zval *otherValueType = arrayIndexObject(otherV, j, "valueTypes");
				if (UNEXPECTED(otherValueType == NULL)) return false;
				zend_long coversValue = isSuperTypeOfValue(thisUnsealedValue, otherValueType);
				if (UNEXPECTED(coversValue < 0)) return false;
				if (coversValue == PT_TRI_NO) {
					out = false;
					return true;
				}
			}
			out = true;
			return true;
		}

		if (otherHasExtras) {
			bool legacy;
			if (UNEXPECTED(!legacyIsKeysSupersetOf(otherArray, legacy))) return false;
			if (legacy) {
				out = true;
				return true;
			}
			for (zv::ArrayEntry entry : zv::ArrRef(k)) {
				zend_long i = (zend_long) entry.indexKey();
				bool optional;
				if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return false;
				if (optional) continue;
				zend_long coversKey = isSuperTypeOfValue(otherUnsealedKey, entry.value().deref().raw());
				if (UNEXPECTED(coversKey < 0)) return false;
				if (coversKey == PT_TRI_NO) {
					out = false;
					return true;
				}
				zval *valueType = arrayIndexObject(v, i, "valueTypes");
				if (UNEXPECTED(valueType == NULL)) return false;
				zend_long coversValue = isSuperTypeOfValue(otherUnsealedValue, valueType);
				if (UNEXPECTED(coversValue < 0)) return false;
				if (coversValue == PT_TRI_NO) {
					out = false;
					return true;
				}
			}
			out = true;
			return true;
		}

		/* Both sealed: fall back to the legacy key/value shape check. */
		return legacyIsKeysSupersetOf(otherArray, out);
	}

	/* private: at least as many keys as the other; an empty other only for
	 * an empty $this; every other key present here, at most one value
	 * differing (none for differing counts or fewer than two keys), at most
	 * one required key of ours absent there; false = pending exception */
	[[nodiscard]] bool legacyIsKeysSupersetOf(zval *otherArray, bool &out) const
	{
		zend_object *other = Z_OBJ_P(otherArray);
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *otherK = v != NULL ? slotOf(other, slots::keyTypes, "keyTypes") : NULL;
		zval *otherV = otherK != NULL ? slotOf(other, slots::valueTypes, "valueTypes") : NULL;
		if (UNEXPECTED(otherV == NULL)) return false;
		zend_long keyTypesCount = arrayCount(k);
		zend_long otherKeyTypesCount = arrayCount(otherK);

		if (keyTypesCount < otherKeyTypesCount) {
			out = false;
			return true;
		}

		if (otherKeyTypesCount == 0) {
			out = keyTypesCount == 0;
			return true;
		}

		bool failOnDifferentValueType = keyTypesCount != otherKeyTypesCount || keyTypesCount < 2;

		zv::Val keyIndexMap = getKeyIndexMap();
		if (UNEXPECTED(keyIndexMap.isUndef())) return false;
		zv::Arr otherKeyValues = zv::Arr::create((uint32_t) otherKeyTypesCount);

		for (zv::ArrayEntry entry : zv::ArrRef(otherK)) {
			zend_long j = (zend_long) entry.indexKey();
			zv::Val keyValueZv = keyValue(entry.value().deref().raw());
			if (UNEXPECTED(keyValueZv.isUndef())) return false;
			zval *iZv = mapFind(Z_ARRVAL_P(keyIndexMap.raw()), keyValueZv.raw());
			if (iZv == NULL || Z_TYPE_P(iZv) != IS_LONG) {
				out = false;
				return true;
			}

			zval trueZv;
			ZVAL_TRUE(&trueZv);
			if (UNEXPECTED(!mapSet(otherKeyValues.table(), keyValueZv.raw(), &trueZv))) return false;

			zval *valueType = arrayIndexObject(v, Z_LVAL_P(iZv), "valueTypes");
			zval *otherValueType = valueType != NULL ? arrayIndexObject(otherV, j, "valueTypes") : NULL;
			if (UNEXPECTED(otherValueType == NULL)) return false;
			zend_long covers = isSuperTypeOfValue(otherValueType, valueType);
			if (UNEXPECTED(covers < 0)) return false;
			if (covers != PT_TRI_NO) continue;

			if (failOnDifferentValueType) {
				out = false;
				return true;
			}
			failOnDifferentValueType = true;
		}

		zend_long requiredKeyCount = 0;
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zv::Val keyValueZv = keyValue(entry.value().deref().raw());
			if (UNEXPECTED(keyValueZv.isUndef())) return false;
			if (mapFind(otherKeyValues.table(), keyValueZv.raw()) != NULL) continue;
			bool optional;
			if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return false;
			if (optional) continue;

			requiredKeyCount++;
			if (requiredKeyCount > 1) {
				out = false;
				return true;
			}
		}

		out = true;
		return true;
	}

	/* the legacy merge without definite pairs on both sides; else the
	 * shared keys with unioned values (optional when optional on either
	 * side), the single-side keys absorbed into the other side's extras
	 * when they fit and optional otherwise, the unsealed pairs unioned, the
	 * next indexes merged, the list-ness and'ed (or'ed with the shape's
	 * own for a sealed result); UNDEF = pending exception */
	zv::Val mergeWith(zval *otherArray) const
	{
		/* only call this after verifying isKeysSupersetOf, or if losing tagged unions is not an issue */
		zend_object *other = Z_OBJ_P(otherArray);
		zval *u = unsealedSlot();
		zval *otherU = u != NULL ? slotOf(other, slots::unsealed, "unsealed", true) : NULL;
		if (UNEXPECTED(otherU == NULL)) return zv::Val();
		if (Z_TYPE_P(u) == IS_NULL || Z_TYPE_P(otherU) == IS_NULL) return legacyMergeWith(otherArray);

		zval *thisUnsealedKey, *thisUnsealedValue, *otherUnsealedKey, *otherUnsealedValue;
		if (UNEXPECTED(!unsealedPair(u, thisUnsealedKey, thisUnsealedValue) || !unsealedPair(otherU, otherUnsealedKey, otherUnsealedValue))) return zv::Val();

		zv::Val mergedUnsealedKey = combinator2(PT_LC("union"), thisUnsealedKey, otherUnsealedKey);
		if (UNEXPECTED(mergedUnsealedKey.isUndef())) return zv::Val();
		zv::Val mergedUnsealedValue = combinator2(PT_LC("union"), thisUnsealedValue, otherUnsealedValue);
		if (UNEXPECTED(mergedUnsealedValue.isUndef())) return zv::Val();

		/* $absorbIntoExtras: the merged pair widened by a key/value */
		auto absorbIntoExtras = [&](zval *keyType, zval *valueType) -> bool {
			mergedUnsealedKey = combinator2(PT_LC("union"), mergedUnsealedKey.raw(), keyType);
			if (UNEXPECTED(mergedUnsealedKey.isUndef())) return false;
			mergedUnsealedValue = combinator2(PT_LC("union"), mergedUnsealedValue.raw(), valueType);
			return !mergedUnsealedValue.isUndef();
		};

		/* $canAbsorb: whether a side's real extras cover a key/value; the
		 * out parameter, false = pending exception */
		auto canAbsorb = [&](zval *side, zval *keyType, zval *valueType, bool &can) -> bool {
			zend_long sideUnsealedness = pt_type_call_trinary(Z_OBJ_P(side), PT_LC("isunsealed"), 0, NULL);
			if (UNEXPECTED(sideUnsealedness < 0)) return false;
			if (sideUnsealedness != PT_TRI_YES) {
				can = false;
				return true;
			}
			zval *sideUnsealed = slotOf(Z_OBJ_P(side), slots::unsealed, "unsealed", true);
			if (UNEXPECTED(sideUnsealed == NULL)) return false;
			if (Z_TYPE_P(sideUnsealed) == IS_NULL) {
				can = false;
				return true;
			}
			zval *sideUnsealedKey, *sideUnsealedValue;
			if (UNEXPECTED(!unsealedPair(sideUnsealed, sideUnsealedKey, sideUnsealedValue))) return false;
			zend_long coversKey = isSuperTypeOfValue(sideUnsealedKey, keyType);
			if (UNEXPECTED(coversKey < 0)) return false;
			if (coversKey == PT_TRI_NO) {
				can = false;
				return true;
			}
			zend_long coversValue = isSuperTypeOfValue(sideUnsealedValue, valueType);
			if (UNEXPECTED(coversValue < 0)) return false;
			can = coversValue != PT_TRI_NO;
			return true;
		};

		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *n = v != NULL ? nextAutoIndexes() : NULL;
		zval *l = n != NULL ? isListSlot() : NULL;
		zval *otherK = l != NULL ? slotOf(other, slots::keyTypes, "keyTypes") : NULL;
		zval *otherV = otherK != NULL ? slotOf(other, slots::valueTypes, "valueTypes") : NULL;
		zval *otherN = otherV != NULL ? slotOf(other, slots::nextAutoIndexes, "nextAutoIndexes") : NULL;
		zval *otherL = otherN != NULL ? slotOf(other, slots::isList, "isList") : NULL;
		if (UNEXPECTED(otherL == NULL)) return zv::Val();

		zv::Arr keyTypesNew = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(k)) + zend_hash_num_elements(Z_ARRVAL_P(otherK)));
		zv::Arr valueTypesNew = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(k)) + zend_hash_num_elements(Z_ARRVAL_P(otherK)));
		zv::Arr optionalKeysNew = zv::Arr::create(8);

		zv::Val otherKeyIndexMap = ConstantArrayType(other).getKeyIndexMap();
		if (UNEXPECTED(otherKeyIndexMap.isUndef())) return zv::Val();
		zv::Arr processed = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(k)));

		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			zv::Val keyValueZv = keyValue(keyType);
			if (UNEXPECTED(keyValueZv.isUndef())) return zv::Val();
			zval trueZv;
			ZVAL_TRUE(&trueZv);
			if (UNEXPECTED(!mapSet(processed.table(), keyValueZv.raw(), &trueZv))) return zv::Val();
			zval *valueType = arrayIndexObject(v, i, "valueTypes");
			if (UNEXPECTED(valueType == NULL)) return zv::Val();

			zval *jZv = mapFind(Z_ARRVAL_P(otherKeyIndexMap.raw()), keyValueZv.raw());
			if (jZv != NULL) {
				zend_long j = Z_TYPE_P(jZv) == IS_LONG ? Z_LVAL_P(jZv) : -1;
				zval *otherValueType = arrayIndexObject(otherV, j, "valueTypes");
				if (UNEXPECTED(otherValueType == NULL)) return zv::Val();
				zv::Val mergedValue = combinator2(PT_LC("union"), valueType, otherValueType);
				if (UNEXPECTED(mergedValue.isUndef())) return zv::Val();
				bool optional, otherOptional = false;
				if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return zv::Val();
				if (!optional && UNEXPECTED(!otherIsOptionalKey(otherArray, j, otherOptional))) return zv::Val();

				keyTypesNew.push(zv::Ref(keyType));
				valueTypesNew.push(std::move(mergedValue));
				if (optional || otherOptional) {
					optionalKeysNew.push(zv::Val::integer((zend_long) keyTypesNew.arrRef().size() - 1));
				}
				continue;
			}

			bool can;
			if (UNEXPECTED(!canAbsorb(otherArray, keyType, valueType, can))) return zv::Val();
			if (can) {
				if (UNEXPECTED(!absorbIntoExtras(keyType, valueType))) return zv::Val();
				continue;
			}

			keyTypesNew.push(zv::Ref(keyType));
			valueTypesNew.push(zv::Ref(valueType));
			optionalKeysNew.push(zv::Val::integer((zend_long) keyTypesNew.arrRef().size() - 1));
		}

		for (zv::ArrayEntry entry : zv::ArrRef(otherK)) {
			zend_long j = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			zv::Val keyValueZv = keyValue(keyType);
			if (UNEXPECTED(keyValueZv.isUndef())) return zv::Val();
			if (mapFind(processed.table(), keyValueZv.raw()) != NULL) continue;
			zval *valueType = arrayIndexObject(otherV, j, "valueTypes");
			if (UNEXPECTED(valueType == NULL)) return zv::Val();

			bool can;
			if (UNEXPECTED(!canAbsorb(thisZv(), keyType, valueType, can))) return zv::Val();
			if (can) {
				if (UNEXPECTED(!absorbIntoExtras(keyType, valueType))) return zv::Val();
				continue;
			}

			keyTypesNew.push(zv::Ref(keyType));
			valueTypesNew.push(zv::Ref(valueType));
			optionalKeysNew.push(zv::Val::integer((zend_long) keyTypesNew.arrRef().size() - 1));
		}

		zv::Val resultUnsealed = pairOf(mergedUnsealedKey.raw(), mergedUnsealedValue.raw());

		zv::Arr nextAutoIndexesNew = mergedNextAutoIndexes(n, otherN);
		zv::Arr optionalKeysUnique = uniqueLongs(optionalKeysNew.raw());

		/* Merging widens single-side keys to optional, so a sealed result
		 * may gain list realizations (e.g. `[]`) the naive `and` misses.
		 * `or`-ing in the shape's own list-ness lifts a `no`/`maybe`. The
		 * `or` also keeps a genuine `yes`: widening forgets which keys can
		 * appear together, so the shape read on its own counts realizations
		 * neither input admits and answers `maybe` where two list inputs
		 * guarantee `yes`. */
		zend_long thisIsListValue = trinaryOf(l);
		zend_long otherIsListValue = thisIsListValue < 0 ? -1 : trinaryOf(otherL);
		if (UNEXPECTED(otherIsListValue < 0)) return zv::Val();
		zend_long naiveIsList = thisIsListValue & otherIsListValue;
		bool mergedIsSealed;
		if (UNEXPECTED(!isExplicitNever(mergedUnsealedKey.raw(), mergedIsSealed))) return zv::Val();
		zend_long isListValue = naiveIsList;
		if (mergedIsSealed) {
			zend_long inferred = inferIsListFromShape(keyTypesNew.raw(), optionalKeysUnique.raw());
			if (UNEXPECTED(inferred < 0)) return zv::Val();
			isListValue = naiveIsList | inferred;
		}

		return thisRecreate(keyTypesNew.raw(), valueTypesNew.raw(), nextAutoIndexesNew.raw(), optionalKeysUnique.raw(), pt_trinary_singleton(isListValue), resultUnsealed.raw());
	}

	/* private: our keys with the other's values unioned in (optional where
	 * absent or optional there), the next indexes merged, the list-ness
	 * and'ed (or'ed with the shape's own for a sealed $this); UNDEF =
	 * pending exception */
	zv::Val legacyMergeWith(zval *otherArray) const
	{
		zend_object *other = Z_OBJ_P(otherArray);
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *n = v != NULL ? nextAutoIndexes() : NULL;
		zval *o = n != NULL ? optionalKeys() : NULL;
		zval *l = o != NULL ? isListSlot() : NULL;
		zval *u = l != NULL ? unsealedSlot() : NULL;
		zval *otherV = u != NULL ? slotOf(other, slots::valueTypes, "valueTypes") : NULL;
		zval *otherN = otherV != NULL ? slotOf(other, slots::nextAutoIndexes, "nextAutoIndexes") : NULL;
		zval *otherL = otherN != NULL ? slotOf(other, slots::isList, "isList") : NULL;
		if (UNEXPECTED(otherL == NULL)) return zv::Val();
		zv::Arr valueTypesNew = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(v)));
		zv::Arr optionalKeysNew = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(o)));
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			NullableLong otherIndex;
			if (UNEXPECTED(!ConstantArrayType(other).getKeyIndex(entry.value().deref().raw(), otherIndex))) return zv::Val();
			if (otherIndex.isNull) {
				optionalKeysNew.push(zv::Val::integer(i));
				continue;
			}
			bool otherOptional;
			if (UNEXPECTED(!otherIsOptionalKey(otherArray, otherIndex.value, otherOptional))) return zv::Val();
			if (otherOptional) {
				optionalKeysNew.push(zv::Val::integer(i));
			}
			zval *otherValueType = arrayIndexObject(otherV, otherIndex.value, "valueTypes");
			zval *valueType = otherValueType != NULL ? arrayIndex(valueTypesNew.raw(), i, "valueTypes") : NULL;
			if (UNEXPECTED(valueType == NULL)) return zv::Val();
			zv::Val merged = combinator2(PT_LC("union"), valueType, otherValueType);
			if (UNEXPECTED(merged.isUndef())) return zv::Val();
			valueTypesNew.separate();
			zv::ArrRef(valueTypesNew.raw()).setIndex((zend_ulong) i, zv::Ref(merged.raw()));
		}

		zv::Arr optionalKeysUnique = uniqueLongs(optionalKeysNew.raw());
		zv::Arr nextAutoIndexesNew = mergedNextAutoIndexes(n, otherN);

		/* Same recompute as mergeWith(), over `$this`'s keys only — this
		 * legacy path drops the other side's extra keys. */
		zend_long thisIsListValue = trinaryOf(l);
		zend_long otherIsListValue = thisIsListValue < 0 ? -1 : trinaryOf(otherL);
		if (UNEXPECTED(otherIsListValue < 0)) return zv::Val();
		zend_long naiveIsList = thisIsListValue & otherIsListValue;
		bool mergedIsSealed = Z_TYPE_P(u) == IS_NULL;
		if (!mergedIsSealed) {
			zval *unsealedKey = unsealedKeyOf(u);
			if (UNEXPECTED(unsealedKey == NULL || !isExplicitNever(unsealedKey, mergedIsSealed))) return zv::Val();
		}
		zend_long isListValue = naiveIsList;
		if (mergedIsSealed) {
			zend_long inferred = inferIsListFromShape(k, optionalKeysUnique.raw());
			if (UNEXPECTED(inferred < 0)) return zv::Val();
			isListValue = naiveIsList | inferred;
		}

		return thisRecreate(k, valueTypesNew.raw(), nextAutoIndexesNew.raw(), optionalKeysUnique.raw(), pt_trinary_singleton(isListValue), u);
	}

	/* private, memoized in $keyIndexMap: key value → index; UNDEF =
	 * pending exception */
	zv::Val getKeyIndexMap() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::keyIndexMap);
		if (Z_TYPE_P(memo) == IS_ARRAY) return zv::Val::copyOf(zv::Ref(memo));
		zval *k = keyTypes();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zv::Arr map = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(k)));
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zv::Val value = keyValue(entry.value().deref().raw());
			if (UNEXPECTED(value.isUndef())) return zv::Val();
			zval index;
			ZVAL_LONG(&index, (zend_long) entry.indexKey());
			if (UNEXPECTED(!mapSet(map.table(), value.raw(), &index))) return zv::Val();
		}
		zv::ObjRef(self).propAtWrite(slots::keyIndexMap, zv::Val::copyOf(map.arrRef()));
		return zv::Val(std::move(map));
	}

	/* private: the index of the explicit key a constant scalar offset
	 * (already passed through toArrayKey()) resolves to, null when the
	 * offset is not a constant scalar or no explicit key is a supertype of
	 * it; false = pending exception */
	[[nodiscard]] bool findVerifiedKeyIndex(zval *offsetType, NullableLong &out) const
	{
		out = NullableLong::null();
		zend_long isConstantScalar = pt_type_op_trinary(Z_OBJ_P(offsetType), PT_OP_IS_CONSTANT_SCALAR_VALUE, 0, NULL);
		if (UNEXPECTED(isConstantScalar < 0)) return false;
		if (isConstantScalar != PT_TRI_YES) return true;

		zv::Val values = pt_type_call_array(Z_OBJ_P(offsetType), PT_LC("getconstantscalarvalues"), 0, NULL);
		if (UNEXPECTED(values.isUndef())) return false;
		/* $offsetType->getConstantScalarValues()[0] ?? null */
		zval *value = zend_hash_index_find(Z_ARRVAL_P(values.raw()), 0);
		if (value == NULL) return true;
		ZVAL_DEREF(value);
		if (Z_TYPE_P(value) != IS_LONG && Z_TYPE_P(value) != IS_STRING) return true;

		zv::Val map = getKeyIndexMap();
		if (UNEXPECTED(map.isUndef())) return false;
		zval *found = mapFind(Z_ARRVAL_P(map.raw()), value);
		if (found == NULL || Z_TYPE_P(found) != IS_LONG) return true;
		zval *k = keyTypes();
		if (UNEXPECTED(k == NULL)) return false;
		zval *keyType = arrayIndexObject(k, Z_LVAL_P(found), "keyTypes");
		if (UNEXPECTED(keyType == NULL)) return false;
		zend_long covers = isSuperTypeOfValue(keyType, offsetType);
		if (UNEXPECTED(covers < 0)) return false;
		if (covers == PT_TRI_YES) {
			out = NullableLong::of(Z_LVAL_P(found));
		}
		return true;
	}

	/* private: the index of a key of that value, null for none; false =
	 * pending exception */
	bool getKeyIndex(zval *otherKeyType, NullableLong &out) const
	{
		zv::Val map = getKeyIndexMap();
		if (UNEXPECTED(map.isUndef())) return false;
		zv::Val value = keyValue(otherKeyType);
		if (UNEXPECTED(value.isUndef())) return false;
		zval *found = mapFind(Z_ARRVAL_P(map.raw()), value.raw());
		out = found != NULL && Z_TYPE_P(found) == IS_LONG ? NullableLong::of(Z_LVAL_P(found)) : NullableLong::null();
		return true;
	}

	/* the key equal to the offset made required (in a definite list, the
	 * integer keys below it too); an offset in the unsealed key range
	 * promoted to a required key of the unsealed value type; $this
	 * otherwise; UNDEF = pending exception */
	zv::Val makeOffsetRequired(zval *offsetTypeArg) const
	{
		zv::Val offsetType = callType(Z_OBJ_P(offsetTypeArg), PT_LC("toarraykey"), 0, NULL);
		if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *n = v != NULL ? nextAutoIndexes() : NULL;
		zval *o = n != NULL ? optionalKeys() : NULL;
		zval *l = o != NULL ? isListSlot() : NULL;
		zval *u = l != NULL ? unsealedSlot() : NULL;
		if (UNEXPECTED(u == NULL)) return zv::Val();
		zend_long isListValue = trinaryOf(l);
		if (UNEXPECTED(isListValue < 0)) return zv::Val();
		bool isList = isListValue == PT_TRI_YES;
		zv::Arr optionalKeysCopy = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(o)));
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(keyType) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: keyTypes must hold %s instances", ptcls::type);
				return zv::Val();
			}
			if (!pt_call_type_equals(keyType, offsetType.raw())) {
				if (UNEXPECTED(EG(exception))) return zv::Val();
				continue;
			}

			zv::Val keyValueZv = keyValue(keyType);
			if (UNEXPECTED(keyValueZv.isUndef())) return zv::Val();
			optionalKeysCopy.separate();
			/* iterated over a snapshot: the twin's foreach reads a copy */
			zv::Arr snapshot = zv::Arr::copyOfTable(optionalKeysCopy.table());
			for (zv::ArrayEntry optionalEntry : snapshot.arrRef()) {
				zval *key = optionalEntry.value().deref().raw();
				bool keep = Z_TYPE_P(key) != IS_LONG || i != Z_LVAL_P(key);
				if (keep) {
					if (!isList || Z_TYPE_P(keyValueZv.raw()) != IS_LONG) continue;
					zval *otherKeyType = arrayIndexObject(k, Z_TYPE_P(key) == IS_LONG ? Z_LVAL_P(key) : -1, "keyTypes");
					if (UNEXPECTED(otherKeyType == NULL)) return zv::Val();
					zv::Val otherKeyValue = keyValue(otherKeyType);
					if (UNEXPECTED(otherKeyValue.isUndef())) return zv::Val();
					if (Z_TYPE_P(otherKeyValue.raw()) != IS_LONG || Z_LVAL_P(otherKeyValue.raw()) >= Z_LVAL_P(keyValueZv.raw())) continue;
				}

				if (optionalEntry.hasStringKey()) {
					zend_hash_del(optionalKeysCopy.table(), optionalEntry.stringKey());
				} else {
					zend_hash_index_del(optionalKeysCopy.table(), optionalEntry.indexKey());
				}
			}

			if (arrayCount(o) != (zend_long) optionalKeysCopy.arrRef().size()) {
				zv::Arr renumbered = arrayValues(optionalKeysCopy.raw());
				return thisRecreate(k, v, n, renumbered.raw(), l, u);
			}

			return zv::Val::copyOf(zv::Ref(thisZv()));
		}

		/* Offset isn't in the explicit set. If the unsealed extras' key
		 * range covers it (e.g. `array{a: int, ...<string, float>}` narrowing
		 * on `array_key_exists('b', $arr)`), promote it into the explicit set
		 * as a required slot with the unsealed value type. The unsealed
		 * extras stay around — additional entries at other matching keys
		 * are still possible. */
		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		zend_class_entry *offsetCe = Z_OBJCE_P(offsetType.raw());
		if (unsealedness == PT_TRI_YES && Z_TYPE_P(u) != IS_NULL && (instanceof_function(offsetCe, pt_ce_constant_integer_type) || instanceof_function(offsetCe, pt_ce_constant_string_type))) {
			zval *unsealedKeyType, *unsealedValueType;
			if (UNEXPECTED(!unsealedPair(u, unsealedKeyType, unsealedValueType))) return zv::Val();
			zend_long covers = isSuperTypeOfValue(unsealedKeyType, offsetType.raw());
			if (UNEXPECTED(covers < 0)) return zv::Val();
			if (covers != PT_TRI_NO) {
				zv::Arr keyTypesCopy = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(k)));
				zv::Arr valueTypesCopy = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(v)));
				keyTypesCopy.push(zv::Ref(offsetType.raw()));
				valueTypesCopy.push(zv::Ref(unsealedValueType));

				return thisRecreate(keyTypesCopy.raw(), valueTypesCopy.raw(), n, o, pt_trinary_singleton(PT_TRI_NO), u);
			}
		}

		return zv::Val::copyOf(zv::Ref(thisZv()));
	}

	/* $this for a definite list, never for a definite non-list; else the
	 * shape with the list-ness asserted — a sealed one trimmed to its
	 * contiguous 0..m prefix first; UNDEF = pending exception */
	zv::Val makeList() const
	{
		/* the twin's read order: $isList first */
		zval *l = isListSlot();
		zval *k = l != NULL ? keyTypes() : NULL;
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *n = v != NULL ? nextAutoIndexes() : NULL;
		zval *o = n != NULL ? optionalKeys() : NULL;
		zval *u = o != NULL ? unsealedSlot() : NULL;
		if (UNEXPECTED(u == NULL)) return zv::Val();
		zend_long isListValue = trinaryOf(l);
		if (UNEXPECTED(isListValue < 0)) return zv::Val();
		if (isListValue == PT_TRI_YES) return zv::Val::copyOf(zv::Ref(thisZv()));

		if (isListValue == PT_TRI_NO) return neverType();

		/* isList is Maybe. In a sealed shape a key past a gap in the 0..n
		 * sequence (or any non-integer key) can never appear in a list, so
		 * keep only the contiguous 0..m prefix. Unsealed extras may fill the
		 * gaps, so keep every key there. */
		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		if (unsealedness == PT_TRI_NO) {
			zv::ScratchTable positionByIndex((uint32_t) zend_hash_num_elements(Z_ARRVAL_P(k)));
			for (zv::ArrayEntry entry : zv::ArrRef(k)) {
				zval *keyType = entry.value().deref().raw();
				if (Z_TYPE_P(keyType) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(keyType), pt_ce_constant_integer_type)) continue;
				zend_long value;
				if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(keyType), value))) return zv::Val();
				zval position;
				ZVAL_LONG(&position, (zend_long) entry.indexKey());
				zend_hash_index_update(positionByIndex.table(), (zend_ulong) value, &position);
			}

			zv::Arr keptPositions = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(k)));
			for (zend_long index = 0;; index++) {
				zval *position = zend_hash_index_find(positionByIndex.table(), (zend_ulong) index);
				if (position == NULL) break;
				keptPositions.push(zv::Ref(position));
			}

			if ((zend_long) keptPositions.arrRef().size() < arrayCount(k)) {
				zv::Val builder = builderCreateEmpty();
				if (UNEXPECTED(builder.isUndef())) return zv::Val();
				for (zv::ArrayEntry entry : keptPositions.arrRef()) {
					zend_long position = zv::Ref(entry.value().deref().raw()).asLong();
					zval *keyType = arrayIndexObject(k, position, "keyTypes");
					zval *valueType = keyType != NULL ? arrayIndexObject(v, position, "valueTypes") : NULL;
					if (UNEXPECTED(valueType == NULL)) return zv::Val();
					bool optional;
					if (UNEXPECTED(!thisIsOptionalKey(position, optional))) return zv::Val();
					if (UNEXPECTED(!builderSet(builder.raw(), keyType, valueType, optional ? 1 : 0))) return zv::Val();
				}

				return builderGetArray(builder.raw());
			}
		}

		return thisRecreate(k, v, n, o, pt_trinary_singleton(PT_TRI_YES), u);
	}

	/* $this unless a definite list, whose list-ness becomes maybe; UNDEF =
	 * pending exception */
	zv::Val makeListMaybe() const
	{
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *n = v != NULL ? nextAutoIndexes() : NULL;
		zval *o = n != NULL ? optionalKeys() : NULL;
		zval *l = o != NULL ? isListSlot() : NULL;
		zval *u = l != NULL ? unsealedSlot() : NULL;
		if (UNEXPECTED(u == NULL)) return zv::Val();
		zend_long isListValue = trinaryOf(l);
		if (UNEXPECTED(isListValue < 0)) return zv::Val();
		if (isListValue != PT_TRI_YES) return zv::Val::copyOf(zv::Ref(thisZv()));

		return thisRecreate(k, v, n, o, pt_trinary_singleton(PT_TRI_MAYBE), u);
	}

	/* the values (and the unsealed value type) mapped; UNDEF = pending
	 * exception */
	zv::Val mapValueType(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *n = v != NULL ? nextAutoIndexes() : NULL;
		zval *o = n != NULL ? optionalKeys() : NULL;
		zval *l = o != NULL ? isListSlot() : NULL;
		zval *u = l != NULL ? unsealedSlot() : NULL;
		if (UNEXPECTED(u == NULL)) return zv::Val();
		zv::Arr valueTypesNew = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(v)));
		for (zv::ArrayEntry entry : zv::ArrRef(v)) {
			zval mapped;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, entry.value().deref().raw(), &mapped))) return zv::Val();
			valueTypesNew.push(zv::Val::adopt(mapped));
		}

		zv::Val unsealedNew = zv::Val::null();
		if (Z_TYPE_P(u) != IS_NULL) {
			zval *unsealedKey, *unsealedValue;
			if (UNEXPECTED(!unsealedPair(u, unsealedKey, unsealedValue))) return zv::Val();
			zval mapped;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, unsealedValue, &mapped))) return zv::Val();
			zv::Val mappedValue = zv::Val::adopt(mapped);
			unsealedNew = pairOf(unsealedKey, mappedValue.raw());
		}

		return thisRecreate(k, valueTypesNew.raw(), n, o, l, unsealedNew.raw());
	}

	/* Constant array shapes already encode precise per-slot keys; a
	 * blanket key-type rewrite (the prior `TypeTraverser`-based pattern in
	 * `NodeScopeResolver`) would coerce constants into a broader type and
	 * lose precision. Pass through unchanged. */
	zv::Val mapKeyType() const { return zv::Val::copyOf(zv::Ref(thisZv())); }

	/* every key made optional; $this for an empty shape; UNDEF = pending
	 * exception */
	zv::Val makeAllArrayKeysOptional() const
	{
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *n = v != NULL ? nextAutoIndexes() : NULL;
		zval *l = n != NULL ? isListSlot() : NULL;
		zval *u = l != NULL ? unsealedSlot() : NULL;
		if (UNEXPECTED(u == NULL)) return zv::Val();
		zend_long keyCount = arrayCount(k);
		if (keyCount == 0) return zv::Val::copyOf(zv::Ref(thisZv()));

		zv::Arr all = rangeLongs(0, keyCount - 1);
		return thisRecreate(k, v, n, all.raw(), l, u);
	}

	/* the constant string keys case-folded (both cases for an unknown
	 * case), the unsealed key type folded too, a list accessory
	 * re-attached for a list; UNDEF = pending exception */
	zv::Val changeKeyCaseArray(NullableLong caseArg) const
	{
		zv::Val builder = builderCreateEmpty();
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *u = v != NULL ? unsealedSlot() : NULL;
		if (UNEXPECTED(builder.isUndef() || u == NULL)) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			zv::Val newKeyType;
			if (Z_TYPE_P(keyType) == IS_OBJECT && instanceof_function(Z_OBJCE_P(keyType), pt_ce_constant_string_type)) {
				newKeyType = foldConstantStringKeyCase(keyType, caseArg);
			} else {
				newKeyType = zv::Val::copyOf(zv::Ref(keyType));
			}
			if (UNEXPECTED(newKeyType.isUndef())) return zv::Val();
			zval *valueType = arrayIndexObject(v, i, "valueTypes");
			if (UNEXPECTED(valueType == NULL)) return zv::Val();
			bool optional;
			if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return zv::Val();
			if (UNEXPECTED(!builderSet(builder.raw(), newKeyType.raw(), valueType, optional ? 1 : 0))) return zv::Val();
		}

		if (Z_TYPE_P(u) != IS_NULL) {
			zval *unsealedKey, *unsealedValue;
			if (UNEXPECTED(!unsealedPair(u, unsealedKey, unsealedValue))) return zv::Val();
			zv::Val foldedKey = foldUnsealedKeyCase(unsealedKey, caseArg);
			if (UNEXPECTED(foldedKey.isUndef() || !builderMakeUnsealed(builder.raw(), foldedKey.raw(), unsealedValue))) return zv::Val();
		}

		zv::Val result = builderGetArray(builder.raw());
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zend_long isListValue = thisIsList();
		if (UNEXPECTED(isListValue < 0)) return zv::Val();
		if (isListValue == PT_TRI_YES) {
			zv::Val list = accessoryList();
			if (UNEXPECTED(list.isUndef())) return zv::Val();
			result = combinator2(PT_LC("intersect"), result.raw(), list.raw());
		}
		return result;
	}

	/* the surely-falsey values dropped, the possibly-falsey ones narrowed
	 * and made optional, the unsealed value type narrowed (the pair dropped
	 * when nothing remains); UNDEF = pending exception */
	zv::Val filterArrayRemovingFalsey() const
	{
		zv::Val falseyTypes = pt_static_type_factory_falsey();
		if (UNEXPECTED(falseyTypes.isUndef() || !zv::Ref(falseyTypes.raw()).isObject())) {
			if (!EG(exception)) {
				zend_type_error("phpstan_turbo: StaticTypeFactory::falsey() must return %s", ptcls::type);
			}
			return zv::Val();
		}
		zv::Val builder = builderCreateEmpty();
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		zval *u = v != NULL ? unsealedSlot() : NULL;
		if (UNEXPECTED(builder.isUndef() || u == NULL)) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			zval *value = arrayIndexObject(v, i, "valueTypes");
			if (UNEXPECTED(value == NULL)) return zv::Val();
			zend_long isFalsey = isSuperTypeOfValue(falseyTypes.raw(), value);
			if (UNEXPECTED(isFalsey < 0)) return zv::Val();
			if (isFalsey == PT_TRI_YES) continue;
			if (isFalsey == PT_TRI_MAYBE) {
				zv::Val narrowed = combinator2(PT_LC("remove"), value, falseyTypes.raw());
				if (UNEXPECTED(narrowed.isUndef() || !builderSet(builder.raw(), keyType, narrowed.raw(), 1))) return zv::Val();
				continue;
			}
			bool optional;
			if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return zv::Val();
			if (UNEXPECTED(!builderSet(builder.raw(), keyType, value, optional ? 1 : 0))) return zv::Val();
		}

		if (Z_TYPE_P(u) != IS_NULL) {
			zval *unsealedKey, *unsealedValueType;
			if (UNEXPECTED(!unsealedPair(u, unsealedKey, unsealedValueType))) return zv::Val();
			zv::Val unsealedValue = combinator2(PT_LC("remove"), unsealedValueType, falseyTypes.raw());
			if (UNEXPECTED(unsealedValue.isUndef())) return zv::Val();
			if (!zv::Ref(unsealedValue.raw()).instanceOf(pt_ce_never_type) && UNEXPECTED(!builderMakeUnsealed(builder.raw(), unsealedKey, unsealedValue.raw()))) {
				return zv::Val();
			}
		}

		return builderGetArray(builder.raw());
	}

	/* private static: the lower- or upper-cased constant, or the union of
	 * both for an unknown case; UNDEF = pending exception */
	static zv::Val foldConstantStringKeyCase(zval *type, NullableLong caseArg)
	{
		zv::Val value = pt_constant_string_get_value(Z_OBJ_P(type));
		if (UNEXPECTED(value.isUndef())) return zv::Val();
		zend_string *v = zv::Ref(value.raw()).asString();
		if (!caseArg.isNull && caseArg.value == PT_CAT_CASE_LOWER) return constantStringOf(zend_string_tolower(v));
		if (!caseArg.isNull && caseArg.value == PT_CAT_CASE_UPPER) return constantStringOf(zend_string_toupper(v));

		zv::Val lower = constantStringOf(zend_string_tolower(v));
		zv::Val upper = constantStringOf(zend_string_toupper(v));
		if (UNEXPECTED(lower.isUndef() || upper.isUndef())) return zv::Val();
		return combinator2(PT_LC("union"), lower.raw(), upper.raw());
	}

	/* private static: a constant string folded, a union folded member by
	 * member, a non-string kept, a string rebuilt from `string` plus the
	 * case-independent accessories it carries and the case's accessory
	 * (both cases unioned for an unknown case); UNDEF = pending exception */
	static zv::Val foldUnsealedKeyCase(zval *key, NullableLong caseArg)
	{
		if (Z_TYPE_P(key) == IS_OBJECT && instanceof_function(Z_OBJCE_P(key), pt_ce_constant_string_type)) return foldConstantStringKeyCase(key, caseArg);

		bool isUnion;
		if (UNEXPECTED(!pt_type_instanceof_ce(key, pt_ce_union_type, isUnion))) return zv::Val();
		if (isUnion) {
			zv::Val innerKeys = pt_type_call_array(Z_OBJ_P(key), PT_LC("gettypes"), 0, NULL);
			if (UNEXPECTED(innerKeys.isUndef())) return zv::Val();
			zv::Arr folded = zv::Arr::create(arrayCount(innerKeys.raw()));
			for (zv::ArrayEntry entry : zv::ArrRef(innerKeys.raw())) {
				zval *innerKey = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(innerKey) != IS_OBJECT)) {
					zend_type_error("phpstan_turbo: getTypes() must return %s instances", ptcls::type);
					return zv::Val();
				}
				zv::Val one = foldUnsealedKeyCase(innerKey, caseArg);
				if (UNEXPECTED(one.isUndef())) return zv::Val();
				folded.push(std::move(one));
			}
			return combinatorSpread(PT_LC("union"), folded.table());
		}

		/* `array_change_key_case` only folds string keys — int keys (e.g.
		 * `...<int, ...>`) pass through unchanged. */
		zend_long isString = pt_type_op_trinary(Z_OBJ_P(key), PT_OP_IS_STRING, 0, NULL);
		if (UNEXPECTED(isString < 0)) return zv::Val();
		if (isString != PT_TRI_YES) return zv::Val::copyOf(zv::Ref(key));

		/* Rebuild from a clean `string` plus the non-case accessories that
		 * case-folding preserves (length is unchanged, so numeric / non-
		 * falsy / non-empty all survive). Any prior lowercase/uppercase
		 * accessory is dropped — matches the `ArrayType::changeKeyCaseArray`
		 * behavior where `strtoupper(lowercase-string)` reads as
		 * `uppercase-string`, not the contradictory intersection. */
		zv::Val string = stringType();
		if (UNEXPECTED(string.isUndef())) return zv::Val();
		zv::Arr preserved = zv::Arr::create(3);
		preserved.push(std::move(string));
		zend_long numeric = pt_type_call_trinary(Z_OBJ_P(key), PT_LC("isnumericstring"), 0, NULL);
		if (UNEXPECTED(numeric < 0)) return zv::Val();
		if (numeric == PT_TRI_YES) {
			if (UNEXPECTED(!pushNew(preserved, pt_accessory_numeric_string_type_new))) return zv::Val();
		} else {
			zend_long nonFalsy = pt_type_call_trinary(Z_OBJ_P(key), PT_LC("isnonfalsystring"), 0, NULL);
			if (UNEXPECTED(nonFalsy < 0)) return zv::Val();
			if (nonFalsy == PT_TRI_YES) {
				if (UNEXPECTED(!pushNew(preserved, pt_accessory_non_falsy_string_type_new))) return zv::Val();
			} else {
				zend_long nonEmpty = pt_type_call_trinary(Z_OBJ_P(key), PT_LC("isnonemptystring"), 0, NULL);
				if (UNEXPECTED(nonEmpty < 0)) return zv::Val();
				if (nonEmpty == PT_TRI_YES && UNEXPECTED(!pushNew(preserved, pt_accessory_non_empty_string_type_new))) return zv::Val();
			}
		}

		if (!caseArg.isNull && caseArg.value == PT_CAT_CASE_LOWER) return intersectionWith(preserved, pt_accessory_lowercase_string_type_new);
		if (!caseArg.isNull && caseArg.value == PT_CAT_CASE_UPPER) return intersectionWith(preserved, pt_accessory_uppercase_string_type_new);

		/* `null` (PHP <8.4 / unspecified) yields lower- or upper-case keys;
		 * record both as a union. */
		zv::Val lower = intersectionWith(preserved, pt_accessory_lowercase_string_type_new);
		zv::Val upper = intersectionWith(preserved, pt_accessory_uppercase_string_type_new);
		if (UNEXPECTED(lower.isUndef() || upper.isUndef())) return zv::Val();
		return combinator2(PT_LC("union"), lower.raw(), upper.raw());
	}

	/* the shape node: an item per key with a constant key node (an
	 * identifier for a valid identifier string), value-only items when
	 * every key is its position; an unsealed shape with its unsealed type
	 * node (the key omitted for a mixed or list-int key, the whole node for
	 * mixed values); list kind when described as a list; UNDEF = pending
	 * exception */
	zv::Val toPhpDocNode() const
	{
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zv::Arr items = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(k)));
		zv::Arr values = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(k)));
		bool exportValuesOnly = true;
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			zv::Val keyValueZv = keyValue(keyType);
			if (UNEXPECTED(keyValueZv.isUndef())) return zv::Val();
			if (Z_TYPE_P(keyValueZv.raw()) != IS_LONG || Z_LVAL_P(keyValueZv.raw()) != i) {
				exportValuesOnly = false;
			}
			zv::Val keyPhpDocNode = pt_type_call(Z_OBJ_P(keyType), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(keyPhpDocNode.isUndef())) return zv::Val();
			bool isConstTypeNode;
			if (UNEXPECTED(!isInstance(keyPhpDocNode.raw(), PT_CLASS_CONST_TYPE_NODE, isConstTypeNode))) return zv::Val();
			if (!isConstTypeNode) continue;
			zval *valueType = arrayIndexObject(v, i, "valueTypes");
			if (UNEXPECTED(valueType == NULL)) return zv::Val();

			/* $keyNode = $keyPhpDocNode->constExpr */
			zv::Val keyNode = readProperty(keyPhpDocNode.raw(), PT_LC("constExpr"));
			if (UNEXPECTED(keyNode.isUndef())) return zv::Val();
			bool isConstExprString;
			if (UNEXPECTED(!isInstance(keyNode.raw(), PT_CLASS_CONST_EXPR_STRING_NODE, isConstExprString))) return zv::Val();
			if (isConstExprString) {
				zv::Val value = readProperty(keyNode.raw(), PT_LC("value"));
				if (UNEXPECTED(value.isUndef())) return zv::Val();
				if (UNEXPECTED(!zv::Ref(value.raw()).isString())) {
					zend_type_error("phpstan_turbo: ConstExprStringNode::$value must be a string");
					return zv::Val();
				}
				bool valid;
				if (UNEXPECTED(!isValidIdentifier(zv::Ref(value.raw()).asString(), valid))) return zv::Val();
				if (valid) {
					keyNode = pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, value.raw());
					if (UNEXPECTED(keyNode.isUndef())) return zv::Val();
				}
			}

			bool isOptional;
			if (UNEXPECTED(!thisIsOptionalKey(i, isOptional))) return zv::Val();
			if (isOptional) {
				exportValuesOnly = false;
			}
			zv::Val valueNode = pt_type_call(Z_OBJ_P(valueType), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(valueNode.isUndef())) return zv::Val();
			zv::Args args{keyNode.raw(), isOptional, valueNode.raw()};
			zv::Val item = pt_type_new(PT_CLASS_ARRAY_SHAPE_ITEM_NODE, 3, args);
			if (UNEXPECTED(item.isUndef())) return zv::Val();
			items.push(std::move(item));
			zv::Val valueNodeAgain = pt_type_call(Z_OBJ_P(valueType), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(valueNodeAgain.isUndef())) return zv::Val();
			ZVAL_NULL(&args[0]);
			ZVAL_COPY_VALUE(&args[2], valueNodeAgain.raw());
			zv::Val valueItem = pt_type_new(PT_CLASS_ARRAY_SHAPE_ITEM_NODE, 3, args);
			if (UNEXPECTED(valueItem.isUndef())) return zv::Val();
			values.push(std::move(valueItem));
		}

		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		zv::Arr &chosen = exportValuesOnly ? values : items;
		if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
			zval *unsealedKeyType, *unsealedValueType;
			if (UNEXPECTED(!unsealedPair(unsealed, unsealedKeyType, unsealedValueType))) return zv::Val();
			zv::Val unsealedKeyTypeDescription = describePrecise(unsealedKeyType);
			if (UNEXPECTED(unsealedKeyTypeDescription.isUndef())) return zv::Val();
			bool isMixedUnsealedKeyType, isMixedUnsealedItemType;
			if (UNEXPECTED(!isImplicitMixedDescribed(unsealedKeyType, unsealedKeyTypeDescription.raw(), isMixedUnsealedKeyType) || !isImplicitMixed(unsealedValueType, isMixedUnsealedItemType))) {
				return zv::Val();
			}
			bool listKey = false;
			if (!isMixedUnsealedKeyType) {
				zend_long isListValue = thisIsList();
				if (UNEXPECTED(isListValue < 0)) return zv::Val();
				listKey = isListValue == PT_TRI_YES && zv::Ref(unsealedKeyTypeDescription.raw()).stringEquals("int<0, max>");
			}
			if (isMixedUnsealedKeyType || listKey) {
				bool asList;
				if (UNEXPECTED(!shouldBeDescribedAsAList(asList))) return zv::Val();
				if (isMixedUnsealedItemType) {
					zval nullZv;
					ZVAL_NULL(&nullZv);
					return arrayShapeNode(false, chosen, &nullZv, asList);
				}

				zv::Val valueNode = pt_type_call(Z_OBJ_P(unsealedValueType), PT_LC("tophpdocnode"), 0, NULL);
				if (UNEXPECTED(valueNode.isUndef())) return zv::Val();
				zval nullZv = {};
				ZVAL_NULL(&nullZv);
				zv::Args args{valueNode.raw(), &nullZv};
				zv::Val unsealedNode = pt_type_new(PT_CLASS_ARRAY_SHAPE_UNSEALED_TYPE_NODE, 2, args);
				if (UNEXPECTED(unsealedNode.isUndef())) return zv::Val();
				return arrayShapeNode(false, chosen, unsealedNode.raw(), asList);
			}

			zv::Val valueNode = pt_type_call(Z_OBJ_P(unsealedValueType), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(valueNode.isUndef())) return zv::Val();
			zv::Val keyNode = pt_type_call(Z_OBJ_P(unsealedKeyType), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(keyNode.isUndef())) return zv::Val();
			zv::Args args{valueNode.raw(), keyNode.raw()};
			zv::Val unsealedNode = pt_type_new(PT_CLASS_ARRAY_SHAPE_UNSEALED_TYPE_NODE, 2, args);
			if (UNEXPECTED(unsealedNode.isUndef())) return zv::Val();
			return arrayShapeNode(false, chosen, unsealedNode.raw(), false);
		}

		bool asList;
		if (UNEXPECTED(!shouldBeDescribedAsAList(asList))) return zv::Val();
		return arrayShapeNode(true, chosen, NULL, asList);
	}

	/* Strings::match($value, '~^(?:[\\]?+[a-z_\x80-\xFF][0-9a-z_\x80-\xFF-]*+)++$~si') !== null —
	 * the same preg_match(), called as the internal function it is (the
	 * pcre extension caches the compiled pattern), without the userland
	 * frames; false = pending exception */
	[[nodiscard]] static bool isValidIdentifier(zend_string *value, bool &out)
	{
		if (UNEXPECTED(pt_carr_preg_match == nullptr || pt_carr_preg_last_error == nullptr)) {
			pt_carr_preg_match = (zend_function *) zend_hash_str_find_ptr(EG(function_table), PT_LC("preg_match"));
			pt_carr_preg_last_error = (zend_function *) zend_hash_str_find_ptr(EG(function_table), PT_LC("preg_last_error"));
			if (UNEXPECTED(pt_carr_preg_match == nullptr || pt_carr_preg_last_error == nullptr)) {
				zend_throw_error(NULL, "phpstan_turbo: preg_match() is not available");
				return false;
			}
		}
		zval args[2], matched;
		ZVAL_STR(&args[0], pt_carr_identifier_regex);
		ZVAL_STR(&args[1], value);
		zend_call_known_function(pt_carr_preg_match, NULL, NULL, &matched, 2, args, NULL);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&matched);
			return false;
		}
		if (UNEXPECTED(Z_TYPE(matched) != IS_LONG)) {
			zval_ptr_dtor(&matched);
			throwRegexpException();
			return false;
		}
		out = Z_LVAL(matched) > 0;
		return true;
	}

	/* preg_match() failed (a backtracking/recursion limit): Nette's
	 * Strings::pcre() throws new RegexpException((RegexpException::MESSAGES[$code]
	 * ?? 'Unknown error') . ' (pattern: ' . $pattern . ')', $code) with
	 * $code = preg_last_error() */
	static void throwRegexpException()
	{
		zend_class_entry *exceptionCe = pt_class(PT_CLASS_NETTE_REGEXP_EXCEPTION);
		if (UNEXPECTED(exceptionCe == NULL)) return;
		zval code;
		zend_call_known_function(pt_carr_preg_last_error, NULL, NULL, &code, 0, NULL, NULL);
		if (UNEXPECTED(EG(exception))) return;
		zend_long codeValue = Z_TYPE(code) == IS_LONG ? Z_LVAL(code) : 0;
		zval_ptr_dtor(&code);

		const char *reason = "Unknown error";
		size_t reasonLength = sizeof("Unknown error") - 1;
		zend_string *constantName = zend_string_init(PT_LC("MESSAGES"), 0);
		zval *messages = zend_get_class_constant_ex(exceptionCe->name, constantName, exceptionCe, ZEND_FETCH_CLASS_SILENT);
		zend_string_release(constantName);
		if (UNEXPECTED(EG(exception))) return;
		if (messages != NULL && Z_TYPE_P(messages) == IS_ARRAY) {
			zval *message = zend_hash_index_find(Z_ARRVAL_P(messages), (zend_ulong) codeValue);
			if (message != NULL && Z_TYPE_P(message) == IS_STRING) {
				reason = Z_STRVAL_P(message);
				reasonLength = Z_STRLEN_P(message);
			}
		}
		zend_string *text = zend_string_concat3(reason, reasonLength, PT_LC(" (pattern: "), ZSTR_VAL(pt_carr_identifier_regex), ZSTR_LEN(pt_carr_identifier_regex));
		zend_string *full = zend_string_concat2(ZSTR_VAL(text), ZSTR_LEN(text), PT_LC(")"));
		zend_string_release(text);
		zend_throw_exception(exceptionCe, ZSTR_VAL(full), codeValue);
		zend_string_release(full);
	}

	/* [] for an unsealed shape; else every combination of the values'
	 * finite types (an optional key forking a without-variant), built one
	 * key at a time and bounded by CALCULATE_SCALARS_LIMIT partial shapes;
	 * UNDEF = pending exception */
	zv::Val getFiniteTypes() const
	{
		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		if (unsealedness == PT_TRI_YES) return zv::Val(zv::Arr::empty());

		zend_long limit;
		if (UNEXPECTED(!calculateScalarsLimit(limit))) return zv::Val();
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		if (UNEXPECTED(v == NULL)) return zv::Val();

		/* Build finite array types incrementally, processing one key at a
		 * time. For optional keys, fork each partial result into
		 * with/without variants. This avoids generating 2^N
		 * ConstantArrayType objects via getAllArrays().
		 * Count first: a shape with many optional keys overflows the limit
		 * after a handful of keys, and building the partial arrays up to that
		 * point costs hundreds of builder clones per call for a result that is
		 * thrown away. */
		zv::Arr finiteValueTypesPerKey = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(v)));
		zend_long count = 1;
		for (zv::ArrayEntry entry : zv::ArrRef(v)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *valueType = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(valueType) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: valueTypes must hold %s instances", ptcls::type);
				return zv::Val();
			}
			zv::Val finiteValueTypes = pt_type_call_array(Z_OBJ_P(valueType), PT_LC("getfinitetypes"), 0, NULL);
			if (UNEXPECTED(finiteValueTypes.isUndef())) return zv::Val();
			zend_long finiteCount = arrayCount(finiteValueTypes.raw());
			if (finiteCount == 0) return zv::Val(zv::Arr::empty());
			bool isOptional;
			if (UNEXPECTED(!thisIsOptionalKey(i, isOptional))) return zv::Val();
			zend_hash_index_update(finiteValueTypesPerKey.table(), (zend_ulong) i, finiteValueTypes.raw());
			Z_TRY_ADDREF_P(finiteValueTypes.raw());
			count *= finiteCount + (isOptional ? 1 : 0);
			if (count > limit) return zv::Val(zv::Arr::empty());
		}

		zv::Val firstBuilder = builderCreateEmpty();
		if (UNEXPECTED(firstBuilder.isUndef())) return zv::Val();
		zv::Arr partials = zv::Arr::create(1);
		partials.push(std::move(firstBuilder));

		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			zval *finiteValueTypesSlot = zend_hash_index_find(finiteValueTypesPerKey.table(), (zend_ulong) i);
			if (UNEXPECTED(finiteValueTypesSlot == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: keyTypes and valueTypes are not parallel");
				return zv::Val();
			}
			zv::Val finiteValueTypes = zv::Val::copyOf(zv::Ref(finiteValueTypesSlot));

			bool isOptional;
			if (UNEXPECTED(!thisIsOptionalKey(i, isOptional))) return zv::Val();
			zv::Arr newPartials = zv::Arr::create(partials.arrRef().size() * (arrayCount(finiteValueTypes.raw()) + 1));

			for (zv::ArrayEntry partialEntry : partials.arrRef()) {
				zval *partial = partialEntry.value().deref().raw();
				if (isOptional) {
					zv::Val cloned = cloneObject(partial);
					if (UNEXPECTED(cloned.isUndef())) return zv::Val();
					newPartials.push(std::move(cloned));
				}
				for (zv::ArrayEntry finiteEntry : zv::ArrRef(finiteValueTypes.raw())) {
					zv::Val newPartial = cloneObject(partial);
					if (UNEXPECTED(newPartial.isUndef())) return zv::Val();
					if (UNEXPECTED(!builderSet(newPartial.raw(), keyType, finiteEntry.value().deref().raw()))) return zv::Val();
					newPartials.push(std::move(newPartial));
				}
			}

			partials = std::move(newPartials);
		}

		zv::Arr finiteTypes = zv::Arr::create(partials.arrRef().size());
		for (zv::ArrayEntry partialEntry : partials.arrRef()) {
			zv::Val array = builderGetArray(partialEntry.value().deref().raw());
			if (UNEXPECTED(array.isUndef())) return zv::Val();
			finiteTypes.push(std::move(array));
		}

		return zv::Val(std::move(finiteTypes));
	}

	/* a value with a template or late-resolvable type, a TemplateType key,
	 * or such an unsealed pair; false = pending exception */
	[[nodiscard]] bool hasTemplateOrLateResolvableType(bool &out) const
	{
		zval *v = valueTypes();
		if (UNEXPECTED(v == NULL)) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(v)) {
			zval *valueType = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(valueType) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: valueTypes must hold %s instances", ptcls::type);
				return false;
			}
			zv::Val has = pt_type_op(Z_OBJ_P(valueType), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
			if (UNEXPECTED(has.isUndef())) return false;
			if (!zend_is_true(has.raw())) continue;
			out = true;
			return true;
		}

		zval *k = keyTypes();
		if (UNEXPECTED(k == NULL)) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			bool isTemplate;
			if (UNEXPECTED(!isInstance(entry.value().deref().raw(), PT_CLASS_TEMPLATE_TYPE, isTemplate))) return false;
			if (!isTemplate) continue;
			out = true;
			return true;
		}

		zval *u = unsealedSlot();
		if (UNEXPECTED(u == NULL)) return false;
		if (Z_TYPE_P(u) != IS_NULL) {
			zval *unsealedKey, *unsealedValue;
			if (UNEXPECTED(!unsealedPair(u, unsealedKey, unsealedValue))) return false;
			zv::Val keyHas = pt_type_op(Z_OBJ_P(unsealedKey), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
			if (UNEXPECTED(keyHas.isUndef())) return false;
			if (zend_is_true(keyHas.raw())) {
				out = true;
				return true;
			}
			zv::Val valueHas = pt_type_op(Z_OBJ_P(unsealedValue), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
			if (UNEXPECTED(valueHas.isUndef())) return false;
			if (zend_is_true(valueHas.raw())) {
				out = true;
				return true;
			}
		}

		out = false;
		return true;
	}

private:
	zend_object *self;

	/* exactly a ConstantArrayType, none of its methods overridden:
	 * $this-calls can go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_constant_array_type; }

	/* whether the object's method is still this class's native one — the
	 * $this-call fast path for a subclass that did not override it */
	bool ownMethod(const char *lcname, size_t len, zif_handler handler) const
	{
		return isExact() || pt_type_method_is(self, lcname, len, handler);
	}

	/* $this as a zval (borrowed) */
	zval *thisZv() const
	{
		ZVAL_OBJ(&selfZv, self);
		return &selfZv;
	}
	mutable zval selfZv;

	/* $object->$name — a private slot declared here, read directly on a
	 * subclass too (as `$type->keyTypes` does); NULL with an Error pending
	 * when uninitialized (ReflectionClass::newInstanceWithoutConstructor())
	 * — the twin's typed-property read raises the same */
	[[nodiscard]] static zval *slotOf(zend_object *object, uint32_t slot, const char *name, bool nullable = false)
	{
		(void) nullable;
		zval *value = OBJ_PROP_NUM(object, slot);
		if (UNEXPECTED(Z_TYPE_P(value) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_constant_array_type->name), name);
			return NULL;
		}
		return value;
	}

	zval *keyTypes() const { return slotOf(self, slots::keyTypes, "keyTypes"); }
	zval *valueTypes() const { return slotOf(self, slots::valueTypes, "valueTypes"); }
	zval *nextAutoIndexes() const { return slotOf(self, slots::nextAutoIndexes, "nextAutoIndexes"); }
	zval *optionalKeys() const { return slotOf(self, slots::optionalKeys, "optionalKeys"); }
	zval *isListSlot() const { return slotOf(self, slots::isList, "isList"); }
	/* IS_NULL or IS_ARRAY */
	zval *unsealedSlot() const { return slotOf(self, slots::unsealed, "unsealed", true); }

	/* the constructor's slot initialization: the value in, IS_PROP_UNINIT
	 * cleared */
	static void writeSlot(zv::ObjRef &ref, uint32_t slot, zv::Val value)
	{
		ref.propAtWrite(slot, std::move(value));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(ref.raw(), slot)) = 0;
	}

	/* [$unsealedKeyType] = $unsealed — the pair's key type (borrowed); NULL
	 * with an Error pending when missing or not an object */
	static zval *unsealedKeyOf(zval *unsealed)
	{
		if (UNEXPECTED(Z_TYPE_P(unsealed) != IS_ARRAY)) {
			zend_type_error("phpstan_turbo: $unsealed must be an array");
			return NULL;
		}
		zval *k = zend_hash_index_find(Z_ARRVAL_P(unsealed), 0);
		if (UNEXPECTED(k == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: $unsealed must be a pair of %s", ptcls::type);
			return NULL;
		}
		ZVAL_DEREF(k);
		if (UNEXPECTED(Z_TYPE_P(k) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: $unsealed must be a pair of %s", ptcls::type);
			return NULL;
		}
		return k;
	}

	/* $this->unsealed[$index] ?? null (borrowed; NULL for null) */
	zval *unsealedSourceType(zend_ulong index) const
	{
		zval *u = OBJ_PROP_NUM(self, slots::unsealed);
		if (Z_TYPE_P(u) != IS_ARRAY) return NULL;
		zval *found = zend_hash_index_find(Z_ARRVAL_P(u), index);
		if (found == NULL) return NULL;
		ZVAL_DEREF(found);
		return Z_TYPE_P(found) == IS_NULL ? NULL : found;
	}

	/* {{{ the $this-calls the twin makes — through the object's class, direct
	 * when the object still carries this class's method */

	zend_long thisIsUnsealed() const
	{
		if (EXPECTED(ownMethod(PT_LC("isunsealed"), catIsUnsealed))) return isUnsealed();
		return pt_type_call_trinary(self, PT_LC("isunsealed"), 0, NULL);
	}

	bool thisIsOptionalKey(zend_long i, bool &out) const
	{
		if (EXPECTED(ownMethod(PT_LC("isoptionalkey"), catIsOptionalKey))) return isOptionalKey(i, out);
		zval index;
		ZVAL_LONG(&index, i);
		zv::Val result = pt_type_call(self, PT_LC("isoptionalkey"), 1, &index);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* $otherArray->isOptionalKey($j) — through the other shape's class */
	static bool otherIsOptionalKey(zval *other, zend_long j, bool &out)
	{
		return ConstantArrayType(Z_OBJ_P(other)).thisIsOptionalKey(j, out);
	}

	zv::Val thisGetKeyTypes() const
	{
		if (EXPECTED(ownMethod(PT_LC("getkeytypes"), catGetKeyTypes))) return getKeyTypes();
		return pt_type_call_array(self, PT_LC("getkeytypes"), 0, NULL);
	}

	zv::Val thisGetValueTypes() const
	{
		if (EXPECTED(ownMethod(PT_LC("getvaluetypes"), catGetValueTypes))) return getValueTypes();
		return pt_type_call_array(self, PT_LC("getvaluetypes"), 0, NULL);
	}

	zv::Val thisGetUnsealedTypes() const
	{
		if (EXPECTED(ownMethod(PT_LC("getunsealedtypes"), catGetUnsealedTypes))) return getUnsealedTypes();
		zv::Val result = pt_type_call(self, PT_LC("getunsealedtypes"), 0, NULL);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isArray() && !result.isNull())) {
			zend_type_error("phpstan_turbo: getUnsealedTypes() must return ?array");
			return zv::Val();
		}
		return result;
	}

	zv::Val thisGetIterableKeyType() const
	{
		if (EXPECTED(ownMethod(PT_LC("getiterablekeytype"), catGetIterableKeyType))) return getIterableKeyType();
		return callType(self, PT_LC("getiterablekeytype"), 0, NULL);
	}

	zv::Val thisGetIterableValueType() const
	{
		if (EXPECTED(ownMethod(PT_LC("getiterablevaluetype"), catGetIterableValueType))) return getIterableValueType();
		return callType(self, PT_LC("getiterablevaluetype"), 0, NULL);
	}

	zv::Val thisGetKeyType() const
	{
		if (EXPECTED(ownMethod(PT_LC("getkeytype"), catGetKeyType))) return getKeyType();
		return callType(self, PT_LC("getkeytype"), 0, NULL);
	}

	zv::Val thisGetItemType() const
	{
		if (EXPECTED(ownMethod(PT_LC("getitemtype"), catGetItemType))) return getItemType();
		return callType(self, PT_LC("getitemtype"), 0, NULL);
	}

	zend_long thisIsIterableAtLeastOnce() const
	{
		if (EXPECTED(ownMethod(PT_LC("isiterableatleastonce"), catIsIterableAtLeastOnce))) return isIterableAtLeastOnce();
		return pt_type_op_trinary(self, PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
	}

	zend_long thisIsList() const
	{
		if (EXPECTED(ownMethod(PT_LC("islist"), catIsList))) {
			zval *l = isListSlot();
			return l == NULL ? -1 : trinaryOf(l);
		}
		return pt_type_op_trinary(self, PT_OP_IS_LIST, 0, NULL);
	}

	zend_long thisHasOffsetValueType(zval *offsetType) const
	{
		if (EXPECTED(ownMethod(PT_LC("hasoffsetvaluetype"), catHasOffsetValueType))) return hasOffsetValueType(offsetType);
		return pt_type_call_trinary(self, PT_LC("hasoffsetvaluetype"), 1, offsetType);
	}

	zv::Val thisGetOffsetValueType(zval *offsetType) const
	{
		if (EXPECTED(ownMethod(PT_LC("getoffsetvaluetype"), catGetOffsetValueType))) return getOffsetValueType(offsetType);
		return callType(self, PT_LC("getoffsetvaluetype"), 1, offsetType);
	}

	zv::Val thisUnsetOffset(zval *offsetType, bool preserveListCertainty) const
	{
		if (EXPECTED(ownMethod(PT_LC("unsetoffset"), catUnsetOffset))) return unsetOffset(offsetType, preserveListCertainty);
		zv::Args args{offsetType, preserveListCertainty};
		return callType(self, PT_LC("unsetoffset"), 2, args);
	}

	zv::Val thisReverseArray(zval *preserveKeys) const
	{
		if (EXPECTED(ownMethod(PT_LC("reversearray"), catReverseArray))) return reverseArray(preserveKeys);
		return callType(self, PT_LC("reversearray"), 1, preserveKeys);
	}

	zv::Val thisSliceArray(zval *offsetType, zval *lengthType, zval *preserveKeys) const
	{
		if (EXPECTED(ownMethod(PT_LC("slicearray"), catSliceArray))) return sliceArray(offsetType, lengthType, preserveKeys);
		zv::Args args{offsetType, lengthType, preserveKeys};
		return callType(self, PT_LC("slicearray"), 3, args);
	}

	zv::Val thisGetArraySize() const
	{
		if (EXPECTED(ownMethod(PT_LC("getarraysize"), catGetArraySize))) return getArraySize();
		return callType(self, PT_LC("getarraysize"), 0, NULL);
	}

	zv::Val thisGetValuesArray() const
	{
		if (EXPECTED(ownMethod(PT_LC("getvaluesarray"), catGetValuesArray))) return getValuesArray();
		zv::Val result = callType(self, PT_LC("getvaluesarray"), 0, NULL);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!instanceof_function(Z_OBJCE_P(result.raw()), pt_ce_constant_array_type))) {
			zend_type_error("phpstan_turbo: getValuesArray() must return %s", ZSTR_VAL(pt_ce_constant_array_type->name));
			return zv::Val();
		}
		return result;
	}

	/* $this->describe(VerbosityLevel::value()) */
	zv::Val thisDescribeValue() const
	{
		zv::Val level = verbosityValue();
		if (UNEXPECTED(level.isUndef())) return zv::Val();
		if (EXPECTED(ownMethod(PT_LC("describe"), catDescribe))) return describe(level.raw());
		return callString(self, PT_LC("describe"), 1, level.raw());
	}

	zv::Val thisFindTypeAndMethodNames() const
	{
		if (EXPECTED(ownMethod(PT_LC("findtypeandmethodnames"), catFindTypeAndMethodNames))) return findTypeAndMethodNames();
		return pt_type_call_array(self, PT_LC("findtypeandmethodnames"), 0, NULL);
	}

	/* $this->traverse($cb) with a callable value */
	zv::Val thisTraverse(zval *callback) const
	{
		if (EXPECTED(ownMethod(PT_LC("traverse"), catTraverse))) {
			zend_fcall_info fci;
			zend_fcall_info_cache fcc;
			char *error = NULL;
			if (UNEXPECTED(zend_fcall_info_init(callback, 0, &fci, &fcc, NULL, &error) != SUCCESS)) {
				if (error != NULL) {
					efree(error);
				}
				zend_throw_error(NULL, "phpstan_turbo: the traverse callback is not callable");
				return zv::Val();
			}
			if (error != NULL) {
				efree(error);
			}
			return traverse(&fci, &fcc);
		}
		return callType(self, PT_LC("traverse"), 1, callback);
	}

	/* $this->traitChunkArray($lengthType, $preserveKeys) — the aliased trait
	 * method, through the object's class */
	zv::Val thisTraitChunkArray(zval *lengthType, zval *preserveKeys) const
	{
		zv::Args args{lengthType, preserveKeys};
		return callType(self, PT_LC("traitchunkarray"), 2, args);
	}

	/* $this->recreate(...) — this class's `new self()` when the object still
	 * carries the native recreate(), a subclass's override otherwise
	 * (TemplateConstantArrayType); isList / unsealed as zvals (IS_NULL for
	 * null); UNDEF = pending exception */
	zv::Val thisRecreate(zval *keyTypes, zval *valueTypes, zval *nextAutoIndexes, zval *optionalKeys, zval *isList, zval *unsealed) const
	{
		if (EXPECTED(ownMethod(PT_LC("recreate"), catRecreate))) return recreate(keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList, unsealed);
		zv::Args args{keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList, unsealed};
		zv::Val result = callType(self, PT_LC("recreate"), 6, args);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!instanceof_function(Z_OBJCE_P(result.raw()), pt_ce_constant_array_type))) {
			zend_type_error("phpstan_turbo: recreate() must return %s", ZSTR_VAL(pt_ce_constant_array_type->name));
			return zv::Val();
		}
		return result;
	}

	/* }}} */

	/* {{{ the callback bodies (pt_type_native_callback holders) */

	/* the reason of a rejected offset: 'Offset %s (%s) does not accept type
	 * %s' with the key at the precise level and the value types at the
	 * recommended one, a trailing reason after ': ' or a full stop */
	static zv::Val offsetReason(zval *keyType, zval *valueType, zval *verbosity, zval *otherValueType, zend_string *reason)
	{
		zv::Val keyDescription = describePrecise(keyType);
		if (UNEXPECTED(keyDescription.isUndef())) return zv::Val();
		zv::Val valueDescription = describeOf(valueType, verbosity);
		if (UNEXPECTED(valueDescription.isUndef())) return zv::Val();
		zv::Val otherDescription = describeOf(otherValueType, verbosity);
		if (UNEXPECTED(otherDescription.isUndef())) return zv::Val();
		if (reason == NULL) {
			return zv::Val::adoptString(zend_strpprintf(0, "Offset %s (%s) does not accept type %s.", ZSTR_VAL(zv::Ref(keyDescription.raw()).asString()), ZSTR_VAL(zv::Ref(valueDescription.raw()).asString()), ZSTR_VAL(zv::Ref(otherDescription.raw()).asString())));
		}
		return zv::Val::adoptString(zend_strpprintf(0, "Offset %s (%s) does not accept type %s: %s", ZSTR_VAL(zv::Ref(keyDescription.raw()).asString()), ZSTR_VAL(zv::Ref(valueDescription.raw()).asString()), ZSTR_VAL(zv::Ref(otherDescription.raw()).asString()), ZSTR_VAL(reason)));
	}

	/* checkOurKeys()'s decorator: state0 = [keyType, valueType, verbosity, otherValueType] */
	static void offsetReasonCallback(zval *captured, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		zend_string *reason = reasonArgument(argc, argv);
		if (UNEXPECTED(reason == NULL || Z_TYPE_P(captured) != IS_ARRAY)) return;
		zval *keyType = zend_hash_index_find(Z_ARRVAL_P(captured), 0);
		zval *valueType = zend_hash_index_find(Z_ARRVAL_P(captured), 1);
		zval *verbosity = zend_hash_index_find(Z_ARRVAL_P(captured), 2);
		zval *otherValueType = zend_hash_index_find(Z_ARRVAL_P(captured), 3);
		ZEND_ASSERT(keyType != NULL && valueType != NULL && verbosity != NULL && otherValueType != NULL);
		zv::Val result = offsetReason(keyType, valueType, verbosity, otherValueType, reason);
		if (UNEXPECTED(result.isUndef())) return;
		result.intoReturnValue(return_value);
	}

	/* 'Unsealed array key type %s does not accept extra key type %s' */
	static zv::Val unsealedKeyReason(zval *unsealedKeyType, zval *extraKeyType, zend_string *reason)
	{
		zv::Val a = describeValue(unsealedKeyType);
		if (UNEXPECTED(a.isUndef())) return zv::Val();
		zv::Val b = describeValue(extraKeyType);
		if (UNEXPECTED(b.isUndef())) return zv::Val();
		if (reason == NULL) {
			return zv::Val::adoptString(zend_strpprintf(0, "Unsealed array key type %s does not accept extra key type %s.", ZSTR_VAL(zv::Ref(a.raw()).asString()), ZSTR_VAL(zv::Ref(b.raw()).asString())));
		}
		return zv::Val::adoptString(zend_strpprintf(0, "Unsealed array key type %s does not accept extra key type %s: %s", ZSTR_VAL(zv::Ref(a.raw()).asString()), ZSTR_VAL(zv::Ref(b.raw()).asString()), ZSTR_VAL(reason)));
	}

	static void unsealedKeyReasonCallback(zval *unsealedKeyType, zval *extraKeyType, uint32_t argc, zval *argv, zval *return_value)
	{
		zend_string *reason = reasonArgument(argc, argv);
		if (UNEXPECTED(reason == NULL)) return;
		zv::Val result = unsealedKeyReason(unsealedKeyType, extraKeyType, reason);
		if (UNEXPECTED(result.isUndef())) return;
		result.intoReturnValue(return_value);
	}

	/* 'Unsealed array value type %s does not accept extra offset %s with value type %s' */
	static zv::Val unsealedValueReason(zval *unsealedValueType, zval *extraKeyType, zval *extraValueType, zend_string *reason)
	{
		zv::Val a = describeValue(unsealedValueType);
		if (UNEXPECTED(a.isUndef())) return zv::Val();
		zv::Val b = describeValue(extraKeyType);
		if (UNEXPECTED(b.isUndef())) return zv::Val();
		zv::Val c = describeValue(extraValueType);
		if (UNEXPECTED(c.isUndef())) return zv::Val();
		if (reason == NULL) {
			return zv::Val::adoptString(zend_strpprintf(0, "Unsealed array value type %s does not accept extra offset %s with value type %s.", ZSTR_VAL(zv::Ref(a.raw()).asString()), ZSTR_VAL(zv::Ref(b.raw()).asString()), ZSTR_VAL(zv::Ref(c.raw()).asString())));
		}
		return zv::Val::adoptString(zend_strpprintf(0, "Unsealed array value type %s does not accept extra offset %s with value type %s: %s", ZSTR_VAL(zv::Ref(a.raw()).asString()), ZSTR_VAL(zv::Ref(b.raw()).asString()), ZSTR_VAL(zv::Ref(c.raw()).asString()), ZSTR_VAL(reason)));
	}

	/* state0 = [unsealedValueType, extraKeyType, extraValueType] */
	static void unsealedValueReasonCallback(zval *captured, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		zend_string *reason = reasonArgument(argc, argv);
		if (UNEXPECTED(reason == NULL || Z_TYPE_P(captured) != IS_ARRAY)) return;
		zval *a = zend_hash_index_find(Z_ARRVAL_P(captured), 0);
		zval *b = zend_hash_index_find(Z_ARRVAL_P(captured), 1);
		zval *c = zend_hash_index_find(Z_ARRVAL_P(captured), 2);
		ZEND_ASSERT(a != NULL && b != NULL && c != NULL);
		zv::Val result = unsealedValueReason(a, b, c, reason);
		if (UNEXPECTED(result.isUndef())) return;
		result.intoReturnValue(return_value);
	}

	/* 'Unsealed array %s type %s does not accept unsealed array %s type %s' */
	static zv::Val unsealedPairReason(const char *kind, zval *ours, zval *theirs, zend_string *reason)
	{
		zv::Val a = describeValue(ours);
		if (UNEXPECTED(a.isUndef())) return zv::Val();
		zv::Val b = describeValue(theirs);
		if (UNEXPECTED(b.isUndef())) return zv::Val();
		if (reason == NULL) {
			return zv::Val::adoptString(zend_strpprintf(0, "Unsealed array %s type %s does not accept unsealed array %s type %s.", kind, ZSTR_VAL(zv::Ref(a.raw()).asString()), kind, ZSTR_VAL(zv::Ref(b.raw()).asString())));
		}
		return zv::Val::adoptString(zend_strpprintf(0, "Unsealed array %s type %s does not accept unsealed array %s type %s: %s", kind, ZSTR_VAL(zv::Ref(a.raw()).asString()), kind, ZSTR_VAL(zv::Ref(b.raw()).asString()), ZSTR_VAL(reason)));
	}

	static void unsealedKeyPairReasonCallback(zval *ours, zval *theirs, uint32_t argc, zval *argv, zval *return_value)
	{
		zend_string *reason = reasonArgument(argc, argv);
		if (UNEXPECTED(reason == NULL)) return;
		zv::Val result = unsealedPairReason("key", ours, theirs, reason);
		if (UNEXPECTED(result.isUndef())) return;
		result.intoReturnValue(return_value);
	}

	static void unsealedValuePairReasonCallback(zval *ours, zval *theirs, uint32_t argc, zval *argv, zval *return_value)
	{
		zend_string *reason = reasonArgument(argc, argv);
		if (UNEXPECTED(reason == NULL)) return;
		zv::Val result = unsealedPairReason("value", ours, theirs, reason);
		if (UNEXPECTED(result.isUndef())) return;
		result.intoReturnValue(return_value);
	}

	/* isSuperTypeOf()'s decorator: 'Offset %s: %s' with the key at the value level */
	static void offsetPrefixReasonCallback(zval *keyType, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		zend_string *reason = reasonArgument(argc, argv);
		if (UNEXPECTED(reason == NULL)) return;
		zv::Val keyDescription = describeValue(keyType);
		if (UNEXPECTED(keyDescription.isUndef())) return;
		ZVAL_STR(return_value, zend_strpprintf(0, "Offset %s: %s", ZSTR_VAL(zv::Ref(keyDescription.raw()).asString()), ZSTR_VAL(reason)));
	}

	/* the lazy reason: fn (): string => $this->sealedArrayShapesCannotBeIntersectedReason($type) */
	static void sealedReasonCallback(zval *thisObject, zval *type, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		if (UNEXPECTED(Z_TYPE_P(thisObject) != IS_OBJECT || Z_TYPE_P(type) != IS_OBJECT)) {
			zend_throw_error(NULL, "phpstan_turbo: the lazy reason lost its captured shapes");
			return;
		}
		zv::Val result = ConstantArrayType(Z_OBJ_P(thisObject)).sealedArrayShapesCannotBeIntersectedReason(type);
		if (UNEXPECTED(result.isUndef())) return;
		result.intoReturnValue(return_value);
	}

	/* IsSuperTypeOfResult::createNo(lazyReasons: [the sealed-shapes reason]) */
	zv::Val sealedShapesNo(zval *type) const
	{
		zv::Val closure = pt_carr_native_closure(sealedReasonCallback, thisZv(), type);
		if (UNEXPECTED(closure.isUndef())) return zv::Val();
		zv::Arr lazyReasons = zv::Arr::create(1);
		lazyReasons.push(std::move(closure));
		zval reasons, lazyReasonsRaw;
		ZVAL_EMPTY_ARRAY(&reasons);
		lazyReasonsRaw = lazyReasons.take();
		zval result;
		if (UNEXPECTED(!pt_result_object_create(&result, pt_ce_is_super_type_of_result, pt_trinary_singleton(PT_TRI_NO), &reasons, &lazyReasonsRaw))) {
			return zv::Val();
		}
		return zv::Val::adopt(result);
	}

	/* RecursionGuard::run()'s callback: the callable readings' certainties
	 * and'ed (maybe when a named method does not exist), no for none */
	static void isCallableCallback(zval *thisObject, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		(void) argc;
		(void) argv;
		if (UNEXPECTED(Z_TYPE_P(thisObject) != IS_OBJECT)) {
			zend_throw_error(NULL, "phpstan_turbo: the isCallable callback lost its shape");
			return;
		}
		bool hasNonExistentMethod = false;
		zv::Val typeAndMethods = ConstantArrayType(Z_OBJ_P(thisObject)).doFindTypeAndMethodNames(hasNonExistentMethod);
		if (UNEXPECTED(typeAndMethods.isUndef())) return;
		if (arrayCount(typeAndMethods.raw()) == 0) {
			ZVAL_COPY(return_value, pt_trinary_singleton(PT_TRI_NO));
			return;
		}

		zend_long result = PT_TRI_YES;
		for (zv::ArrayEntry entry : zv::ArrRef(typeAndMethods.raw())) {
			zval *typeAndMethod = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(typeAndMethod) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: ConstantArrayTypeAndMethod expected");
				return;
			}
			zend_long certainty = pt_type_call_trinary(Z_OBJ_P(typeAndMethod), PT_LC("getcertainty"), 0, NULL);
			if (UNEXPECTED(certainty < 0)) return;
			result &= certainty;
		}

		if (hasNonExistentMethod) {
			result &= PT_TRI_MAYBE;
		}

		ZVAL_COPY(return_value, pt_trinary_singleton(result));
	}

	/* generalize()'s template-argument callback: static fn (Type $type) => $type->generalize($precision) */
	static void generalizeCallback(zval *precision, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		if (UNEXPECTED(argc < 1 || Z_TYPE_P(&argv[0]) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: the generalize callback takes a %s", ptcls::type);
			return;
		}
		zv::Val result = pt_type_call(Z_OBJ_P(&argv[0]), PT_LC("generalize"), 1, precision);
		if (UNEXPECTED(result.isUndef())) return;
		result.intoReturnValue(return_value);
	}

	/* }}} */

	/* {{{ helpers */

	/* [$a, $b, $c] / [$a, $b, $c, $d] as owned arrays of borrowed values */
	static zv::Val tripleOf(zval *a, zval *b, zval *c)
	{
		zv::Arr triple = zv::Arr::create(3);
		triple.push(zv::Ref(a));
		triple.push(zv::Ref(b));
		triple.push(zv::Ref(c));
		return zv::Val(std::move(triple));
	}

	static zv::Val quadOf(zval *a, zval *b, zval *c, zval *d)
	{
		zv::Arr quad = zv::Arr::create(4);
		quad.push(zv::Ref(a));
		quad.push(zv::Ref(b));
		quad.push(zv::Ref(c));
		quad.push(zv::Ref(d));
		return zv::Val(std::move(quad));
	}

	/* !$result->yes() && count($result->reasons) === 0; false = pending
	 * exception */
	[[nodiscard]] static bool isNonYesWithoutReasons(zval *result, bool &out)
	{
		zend_long value = pt_type_result_trinary(result);
		if (UNEXPECTED(value < 0)) return false;
		if (value == PT_TRI_YES) {
			out = false;
			return true;
		}
		zv::Val reasons = resultReasons(result);
		if (UNEXPECTED(reasons.isUndef())) return false;
		out = arrayCount(reasons.raw()) == 0;
		return true;
	}

	/* the referenced classes of every Type in the array appended */
	static bool collectReferencedClasses(zv::Arr &into, zval *types)
	{
		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			zval *type = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: expected %s instances", ptcls::type);
				return false;
			}
			zv::Val classes = pt_type_call_array(Z_OBJ_P(type), PT_LC("getreferencedclasses"), 0, NULL);
			if (UNEXPECTED(classes.isUndef())) return false;
			for (zv::ArrayEntry classEntry : zv::ArrRef(classes.raw())) {
				into.push(classEntry.value());
			}
		}
		return true;
	}

	/* the referenced template types of every Type in the array appended */
	static bool collectReferencedTemplateTypes(zv::Arr &into, zval *types, zval *variance)
	{
		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			zval *type = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: expected %s instances", ptcls::type);
				return false;
			}
			zv::Val references = pt_type_call_array(Z_OBJ_P(type), PT_LC("getreferencedtemplatetypes"), 1, variance);
			if (UNEXPECTED(references.isUndef())) return false;
			for (zv::ArrayEntry referenceEntry : zv::ArrRef(references.raw())) {
				into.push(referenceEntry.value());
			}
		}
		return true;
	}

	/* array_slice($array, $offset, $length): the values, renumbered (a
	 * negative offset from the end) */
	static zv::Arr arraySlice(zval *array, zend_long offset, zend_long length)
	{
		zend_long count = arrayCount(array);
		if (offset < 0) {
			offset += count;
			if (offset < 0) {
				offset = 0;
			}
		}
		zv::Arr slice = zv::Arr::create(length > 0 ? (uint32_t) length : 0);
		zend_long position = 0;
		for (zv::ArrayEntry entry : zv::ArrRef(array)) {
			if (position >= offset && position < offset + length) {
				slice.push(entry.value());
			}
			position++;
		}
		return slice;
	}

	/* usort($indices, fn (int $a, int $b): int => $values[$a] <=> $values[$b]):
	 * the engine's own sort with the stable fallback usort() applies, the
	 * comparison being the spaceship on the key values (kept by index in
	 * the values table); false = pending exception */
	[[nodiscard]] static bool usortIndicesByValue(zv::Arr &indices, zv::Arr &values)
	{
		indices.separate();
		HashTable *previous = pt_carr_sort_values;
		pt_carr_sort_values = values.table();
		zend_hash_sort_ex(indices.table(), zend_sort, compareIndicesByValue, 1);
		pt_carr_sort_values = previous;
		return !EG(exception);
	}

	static HashTable *pt_carr_sort_values;

	static int compareIndicesByValue(Bucket *a, Bucket *b)
	{
		zval *va = zend_hash_index_find(pt_carr_sort_values, (zend_ulong) Z_LVAL(a->val));
		zval *vb = zend_hash_index_find(pt_carr_sort_values, (zend_ulong) Z_LVAL(b->val));
		int result = va != NULL && vb != NULL ? zend_compare(va, vb) : 0;
		if (result == 0) {
			/* usort()'s stable fallback: the original order */
			return Z_EXTRA(a->val) > Z_EXTRA(b->val) ? 1 : (Z_EXTRA(a->val) < Z_EXTRA(b->val) ? -1 : 0);
		}
		return result > 0 ? 1 : -1;
	}

	/* the last element of an array (a copy), null for none */
	static zv::Val lastValue(zval *array)
	{
		zv::Val last = zv::Val::null();
		for (zv::ArrayEntry entry : zv::ArrRef(array)) {
			last = zv::Val::copyOf(entry.value());
		}
		return last;
	}

	/* $a === $b for two zvals holding objects */
	static bool sameObject(zval *a, zval *b)
	{
		return Z_TYPE_P(a) == IS_OBJECT && Z_TYPE_P(b) == IS_OBJECT && Z_OBJ_P(a) == Z_OBJ_P(b);
	}

	/* sort(array_values(array_unique(array_merge($a, $b)))) over int lists */
	static zv::Arr mergedNextAutoIndexes(zval *a, zval *b)
	{
		zv::Arr merged = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(a)) + zend_hash_num_elements(Z_ARRVAL_P(b)));
		for (zval *source : { a, b }) {
			for (zv::ArrayEntry entry : zv::ArrRef(source)) {
				merged.push(entry.value());
			}
		}
		zv::Arr unique = uniqueLongs(merged.raw());
		sortLongs(unique);
		return unique;
	}

	/* [ConstantArrayTypeAndMethod::createUnknown()] */
	static zv::Val unknownTypeAndMethod()
	{
		zv::Val unknown = pt_type_call_static(PT_CLASS_CONSTANT_ARRAY_TYPE_AND_METHOD, PT_LC("createunknown"), 0, NULL);
		if (UNEXPECTED(unknown.isUndef())) return zv::Val();
		zv::Arr list = zv::Arr::create(1);
		list.push(std::move(unknown));
		return zv::Val(std::move(list));
	}

	/* TrinaryLogic::extremeIdentity() over recursiveHasOffsetValueType() of
	 * every type in the array; -1 = pending exception */
	[[nodiscard]] zend_long extremeIdentityOverTypes(zval *types) const
	{
		uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(types));
		zend_long *results = (zend_long *) safe_emalloc(count > 0 ? count : 1, sizeof(zend_long), 0);
		uint32_t n = 0;
		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			zval *innerType = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(innerType) != IS_OBJECT)) {
				efree(results);
				zend_type_error("phpstan_turbo: expected %s instances", ptcls::type);
				return -1;
			}
			zend_long value = recursiveHasOffsetValueType(innerType);
			if (UNEXPECTED(value < 0)) {
				efree(results);
				return -1;
			}
			results[n++] = value;
		}
		zend_long identity = trinaryExtremeIdentity(results, n);
		efree(results);
		return identity;
	}

	/* $typeMap = $typeMap->union($type->inferTemplateTypes($received));
	 * false = pending exception */
	[[nodiscard]] static bool unionInto(zv::Val &typeMap, zval *type, zval *received)
	{
		zv::Val inferred = pt_type_call(Z_OBJ_P(type), PT_LC("infertemplatetypes"), 1, received);
		if (UNEXPECTED(inferred.isUndef())) return false;
		if (UNEXPECTED(!zv::Ref(typeMap.raw()).isObject())) {
			zend_type_error("phpstan_turbo: TemplateTypeMap expected");
			return false;
		}
		typeMap = pt_type_call(Z_OBJ_P(typeMap.raw()), PT_LC("union"), 1, inferred.raw());
		return !typeMap.isUndef();
	}

	/* $this->unsetOffset($offsetType, true), never when that turns a
	 * definite list into a definite non-list; UNDEF = pending exception */
	zv::Val unsetPreservingListCertainty(zval *offsetType, zval *isListSlotValue) const
	{
		zv::Val unsetResult = thisUnsetOffset(offsetType, true);
		if (UNEXPECTED(unsetResult.isUndef())) return zv::Val();
		/* When the source was definitely a list but the post-unset shape
		 * definitely isn't (e.g. unsetting a non-optional leading key
		 * creates a hole), no value of $this could have lacked the removed
		 * key — the subtraction yields the empty set. */
		zend_long isListValue = trinaryOf(isListSlotValue);
		if (UNEXPECTED(isListValue < 0)) return zv::Val();
		if (isListValue == PT_TRI_YES) {
			zend_long resultIsList = pt_type_op_trinary(Z_OBJ_P(unsetResult.raw()), PT_OP_IS_LIST, 0, NULL);
			if (UNEXPECTED(resultIsList < 0)) return zv::Val();
			if (resultIsList == PT_TRI_NO) return neverType();
		}
		return unsetResult;
	}

	/* TypeCombinator::intersect($this, new NonEmptyArrayType()) */
	zv::Val intersectedWithNonEmpty() const
	{
		zv::Val nonEmpty = nonEmptyArray();
		if (UNEXPECTED(nonEmpty.isUndef())) return zv::Val();
		return combinator2(PT_LC("intersect"), thisZv(), nonEmpty.raw());
	}

	/* the [min, max] of truncateListToSize()'s size type; false = pending
	 * exception */
	[[nodiscard]] static bool extractTruncateListBoundsValues(zval *sizeType, NullableLong &min, NullableLong &max)
	{
		if (instanceof_function(Z_OBJCE_P(sizeType), pt_ce_constant_integer_type)) {
			zend_long value;
			if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(sizeType), value))) return false;
			min = NullableLong::of(value);
			max = NullableLong::of(value);
			return true;
		}
		if (instanceof_function(Z_OBJCE_P(sizeType), pt_ce_integer_range_type)) return pt_integer_range_bounds(Z_OBJ_P(sizeType), min, max);
		min = NullableLong::null();
		max = NullableLong::null();
		return true;
	}

	/* $builderData[] = [new ConstantIntegerType($i), $this->getOffsetValueType(...), $optional] */
	bool pushBuilderData(zv::Arr &builderData, zend_long i, bool optional) const
	{
		zv::Val offsetType = pt_type_new_constant_integer(i);
		if (UNEXPECTED(offsetType.isUndef())) return false;
		zv::Val valueType = thisGetOffsetValueType(offsetType.raw());
		if (UNEXPECTED(valueType.isUndef())) return false;
		zv::Arr triple = zv::Arr::create(3);
		triple.push(std::move(offsetType));
		triple.push(std::move(valueType));
		triple.push(zv::Val::boolean(optional));
		builderData.push(zv::Val(std::move(triple)));
		return true;
	}

	/* getFirst/LastIterableKey/ValueType(): the leading (or trailing) keys
	 * or values up to the first required key, plus the unsealed key type
	 * (mixed substituted) or value type of an unsealed shape, unioned;
	 * UNDEF = pending exception */
	zv::Val leadingUnion(bool keys, bool fromStart) const
	{
		zval *k = keyTypes();
		zval *types = k != NULL ? (keys ? k : valueTypes()) : NULL;
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Arr collected = zv::Arr::create(4);
		if (fromStart) {
			for (zv::ArrayEntry entry : zv::ArrRef(types)) {
				zend_long i = (zend_long) entry.indexKey();
				collected.push(entry.value());
				bool optional;
				if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return zv::Val();
				if (!optional) break;
			}
		} else {
			for (zend_long i = arrayCount(k) - 1; i >= 0; i--) {
				zval *type = arrayIndex(types, i, keys ? "keyTypes" : "valueTypes");
				if (UNEXPECTED(type == NULL)) return zv::Val();
				collected.push(zv::Ref(type));
				bool optional;
				if (UNEXPECTED(!thisIsOptionalKey(i, optional))) return zv::Val();
				if (!optional) break;
			}
		}

		zend_long unsealedness = thisIsUnsealed();
		if (UNEXPECTED(unsealedness < 0)) return zv::Val();
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
			if (keys) {
				zval *unsealedKey = unsealedKeyOf(unsealed);
				if (UNEXPECTED(unsealedKey == NULL)) return zv::Val();
				zv::Val substituted = substituteMixedUnsealedKey(unsealedKey);
				if (UNEXPECTED(substituted.isUndef())) return zv::Val();
				collected.push(std::move(substituted));
			} else {
				zval *unsealedKey, *unsealedValue;
				if (UNEXPECTED(!unsealedPair(unsealed, unsealedKey, unsealedValue))) return zv::Val();
				collected.push(zv::Ref(unsealedValue));
			}
		}

		return combinatorSpread(PT_LC("union"), collected.table());
	}

	/* $this->toBoolean()->$method() */
	zv::Val booleanTo(const char *lcname, size_t len) const
	{
		zv::Val boolean;
		if (EXPECTED(ownMethod(PT_LC("toboolean"), catToBoolean))) {
			boolean = toBoolean();
		} else {
			boolean = callType(self, PT_LC("toboolean"), 0, NULL);
		}
		if (UNEXPECTED(boolean.isUndef())) return zv::Val();
		return callType(Z_OBJ_P(boolean.raw()), lcname, len, 0, NULL);
	}

	/* $types[] = new Class(); false = pending exception */
	[[nodiscard]] static bool pushNew(zv::Arr &types, int classIdx)
	{
		zv::Val created = pt_type_new(classIdx, 0, NULL);
		if (UNEXPECTED(created.isUndef())) return false;
		types.push(std::move(created));
		return true;
	}

	/* the same for a shadowing class, through its exported constructor */
	static bool pushNew(zv::Arr &types, bool (*construct)(zval *))
	{
		zval raw;
		if (UNEXPECTED(!construct(&raw))) return false;
		types.push(zv::Val::adopt(raw));
		return true;
	}

	/* new IntersectionType([...$preserved, new Accessory()]) */
	static zv::Val intersectionWith(zv::Arr &preserved, int accessoryIdx)
	{
		zv::Arr types = zv::Arr::create(preserved.arrRef().size() + 1);
		for (zv::ArrayEntry entry : preserved.arrRef()) {
			types.push(entry.value());
		}
		if (UNEXPECTED(!pushNew(types, accessoryIdx))) return zv::Val();
		return intersection(std::move(types));
	}

	static zv::Val intersectionWith(zv::Arr &preserved, bool (*construct)(zval *))
	{
		zv::Arr types = zv::Arr::create(preserved.arrRef().size() + 1);
		for (zv::ArrayEntry entry : preserved.arrRef()) {
			types.push(entry.value());
		}
		if (UNEXPECTED(!pushNew(types, construct))) return zv::Val();
		return intersection(std::move(types));
	}

	/* $object->$name (a public property); UNDEF = pending exception */
	static zv::Val readProperty(zval *object, const char *name, size_t len)
	{
		if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: cannot read property %s of a non-object", name);
			return zv::Val();
		}
		/* the declared slot, or rv filled by a magic read (released only then) */
		zval rv;
		ZVAL_UNDEF(&rv);
		zval *value = zend_read_property(Z_OBJCE_P(object), Z_OBJ_P(object), name, len, 0, &rv);
		if (UNEXPECTED(value == NULL || EG(exception))) return zv::Val();
		zv::Val copy = zv::Val::copyOf(zv::Ref(value));
		if (value == &rv) {
			zval_ptr_dtor(&rv);
		}
		return copy;
	}

	/* isImplicitMixed() given the type's precise description already */
	static bool isImplicitMixedDescribed(zval *type, zval *description, bool &out)
	{
		bool isMixed;
		if (UNEXPECTED(!isInstance(type, pt_ce_mixed_type, isMixed))) return false;
		if (!isMixed || !zv::Ref(description).stringEquals("mixed")) {
			out = false;
			return true;
		}
		zv::Val explicitMixed = pt_type_call(Z_OBJ_P(type), PT_LC("isexplicitmixed"), 0, NULL);
		if (UNEXPECTED(explicitMixed.isUndef())) return false;
		out = !zend_is_true(explicitMixed.raw());
		return true;
	}

	/* ArrayShapeNode::KIND_LIST / KIND_ARRAY — the class's string constants */
	static zv::Val arrayShapeKind(bool asList)
	{
		zend_class_entry *ce = pt_class(PT_CLASS_ARRAY_SHAPE_NODE);
		if (UNEXPECTED(ce == NULL)) return zv::Val();
		const char *name = asList ? "KIND_LIST" : "KIND_ARRAY";
		zend_class_constant *constant = (zend_class_constant *) zend_hash_str_find_ptr(&ce->constants_table, name, strlen(name));
		if (UNEXPECTED(constant == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: %s::%s not found", ZSTR_VAL(ce->name), name);
			return zv::Val();
		}
		if (UNEXPECTED(Z_TYPE(constant->value) == IS_CONSTANT_AST && zval_update_constant_ex(&constant->value, ce) != SUCCESS)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(&constant->value));
	}

	/* ArrayShapeNode::createSealed($items, $kind) / ::createUnsealed($items,
	 * $unsealedType, $kind); UNDEF = pending exception */
	static zv::Val arrayShapeNode(bool sealed, zv::Arr &items, zval *unsealedNode, bool asList)
	{
		zv::Val kind = arrayShapeKind(asList);
		if (UNEXPECTED(kind.isUndef())) return zv::Val();
		zval args[3];
		ZVAL_COPY_VALUE(&args[0], items.raw());
		if (sealed) {
			ZVAL_COPY_VALUE(&args[1], kind.raw());
			return pt_type_call_static(PT_CLASS_ARRAY_SHAPE_NODE, PT_LC("createsealed"), 2, args);
		}
		ZVAL_COPY_VALUE(&args[1], unsealedNode);
		ZVAL_COPY_VALUE(&args[2], kind.raw());
		return pt_type_call_static(PT_CLASS_ARRAY_SHAPE_NODE, PT_LC("createunsealed"), 3, args);
	}

	/* clone $object; UNDEF = pending exception */
	static zv::Val cloneObject(zval *object)
	{
		if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: cannot clone a non-object");
			return zv::Val();
		}
		zend_object *cloned = Z_OBJ_HT_P(object)->clone_obj(Z_OBJ_P(object));
		if (UNEXPECTED(cloned == NULL || EG(exception))) {
			if (cloned != NULL) {
				OBJ_RELEASE(cloned);
			}
			return zv::Val();
		}
		zval result;
		ZVAL_OBJ(&result, cloned);
		return zv::Val::adopt(result);
	}

	/* the value/precise-level body of describe(): the items (values only
	 * when every key is its position and required), truncated past
	 * DESCRIBE_LIMIT at the value level, the unsealed pair after '...';
	 * UNDEF = pending exception */
	zv::Val describeValueLevel(zval *level, const char *arrayName, bool truncate) const
	{
		zval *k = keyTypes();
		zval *v = k != NULL ? valueTypes() : NULL;
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zv::Arr items = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(k)));
		zv::Arr values = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(k)));
		bool exportValuesOnly = true;
		for (zv::ArrayEntry entry : zv::ArrRef(k)) {
			zend_long i = (zend_long) entry.indexKey();
			zval *keyType = entry.value().deref().raw();
			zval *valueType = arrayIndexObject(v, i, "valueTypes");
			if (UNEXPECTED(valueType == NULL)) return zv::Val();
			zv::Val keyValueZv = keyValue(keyType);
			if (UNEXPECTED(keyValueZv.isUndef())) return zv::Val();
			if (Z_TYPE_P(keyValueZv.raw()) != IS_LONG || Z_LVAL_P(keyValueZv.raw()) != i) {
				exportValuesOnly = false;
			}

			bool isOptional;
			if (UNEXPECTED(!thisIsOptionalKey(i, isOptional))) return zv::Val();
			if (isOptional) {
				exportValuesOnly = false;
			}

			smart_str keyDescription = {NULL, 0};
			if (Z_TYPE_P(keyValueZv.raw()) == IS_STRING) {
				zend_string *key = Z_STR_P(keyValueZv.raw());
				if (memchr(ZSTR_VAL(key), '"', ZSTR_LEN(key)) != NULL) {
					smart_str_appendc(&keyDescription, '\'');
					smart_str_append(&keyDescription, key);
					smart_str_appendc(&keyDescription, '\'');
				} else if (memchr(ZSTR_VAL(key), '\'', ZSTR_LEN(key)) != NULL) {
					smart_str_appendc(&keyDescription, '"');
					smart_str_append(&keyDescription, key);
					smart_str_appendc(&keyDescription, '"');
				} else {
					bool valid;
					if (UNEXPECTED(!isValidIdentifier(key, valid))) {
						smart_str_free(&keyDescription);
						return zv::Val();
					}
					if (!valid) {
						smart_str_appendc(&keyDescription, '\'');
						smart_str_append(&keyDescription, key);
						smart_str_appendc(&keyDescription, '\'');
					} else {
						smart_str_append(&keyDescription, key);
					}
				}
			} else {
				zend_string *key = zval_get_string(keyValueZv.raw());
				smart_str_append(&keyDescription, key);
				zend_string_release(key);
			}

			zv::Val valueTypeDescription = describeOf(valueType, level);
			if (UNEXPECTED(valueTypeDescription.isUndef())) {
				smart_str_free(&keyDescription);
				return zv::Val();
			}
			smart_str item = {NULL, 0};
			smart_str_append_smart_str(&item, &keyDescription);
			smart_str_free(&keyDescription);
			if (isOptional) {
				smart_str_appendc(&item, '?');
			}
			smart_str_appendl(&item, ": ", 2);
			smart_str_append(&item, zv::Ref(valueTypeDescription.raw()).asString());
			smart_str_0(&item);
			items.push(zv::Val::adoptString(item.s));
			values.push(std::move(valueTypeDescription));
		}

		smart_str append = {NULL, 0};
		if (truncate && (zend_long) items.arrRef().size() > PT_CAT_DESCRIBE_LIMIT) {
			items = arraySlice(items.raw(), 0, PT_CAT_DESCRIBE_LIMIT);
			values = arraySlice(values.raw(), 0, PT_CAT_DESCRIBE_LIMIT);
			smart_str_appendl(&append, ", ...", 5);
		}

		zend_long unsealedness = thisIsUnsealed();
		zval *unsealed = unsealedness < 0 ? NULL : unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) {
			smart_str_free(&append);
			return zv::Val();
		}
		if (unsealedness == PT_TRI_YES && Z_TYPE_P(unsealed) != IS_NULL) {
			if (items.arrRef().size() > 0) {
				smart_str_appendl(&append, ", ", 2);
			}
			smart_str_appendl(&append, "...", 3);
			zval *unsealedKeyType, *unsealedValueType;
			if (UNEXPECTED(!unsealedPair(unsealed, unsealedKeyType, unsealedValueType))) {
				smart_str_free(&append);
				return zv::Val();
			}
			zv::Val keyDescription = describePrecise(unsealedKeyType);
			if (UNEXPECTED(keyDescription.isUndef())) {
				smart_str_free(&append);
				return zv::Val();
			}
			bool isMixedKeyType, isMixedItemType;
			if (UNEXPECTED(!isImplicitMixedDescribed(unsealedKeyType, keyDescription.raw(), isMixedKeyType) || !isImplicitMixed(unsealedValueType, isMixedItemType))) {
				smart_str_free(&append);
				return zv::Val();
			}
			bool listKey = false;
			if (!isMixedKeyType) {
				zend_long isListValue = thisIsList();
				if (UNEXPECTED(isListValue < 0)) {
					smart_str_free(&append);
					return zv::Val();
				}
				listKey = isListValue == PT_TRI_YES && zv::Ref(keyDescription.raw()).stringEquals("int<0, max>");
			}
			if (isMixedKeyType || listKey) {
				if (!isMixedItemType) {
					zv::Val valueDescription = describeOf(unsealedValueType, level);
					if (UNEXPECTED(valueDescription.isUndef())) {
						smart_str_free(&append);
						return zv::Val();
					}
					smart_str_appendc(&append, '<');
					smart_str_append(&append, zv::Ref(valueDescription.raw()).asString());
					smart_str_appendc(&append, '>');
				}
			} else {
				zv::Val keyLevelDescription = describeOf(unsealedKeyType, level);
				if (UNEXPECTED(keyLevelDescription.isUndef())) {
					smart_str_free(&append);
					return zv::Val();
				}
				zv::Val valueDescription = describeOf(unsealedValueType, level);
				if (UNEXPECTED(valueDescription.isUndef())) {
					smart_str_free(&append);
					return zv::Val();
				}
				smart_str_appendc(&append, '<');
				smart_str_append(&append, zv::Ref(keyLevelDescription.raw()).asString());
				smart_str_appendl(&append, ", ", 2);
				smart_str_append(&append, zv::Ref(valueDescription.raw()).asString());
				smart_str_appendc(&append, '>');
			}
		}

		/* sprintf('%s{%s%s}', $arrayName, implode(', ', $exportValuesOnly ? $values : $items), $append) */
		smart_str result = {NULL, 0};
		smart_str_appends(&result, arrayName);
		smart_str_appendc(&result, '{');
		bool first = true;
		for (zv::ArrayEntry entry : (exportValuesOnly ? values : items).arrRef()) {
			if (!first) {
				smart_str_appendl(&result, ", ", 2);
			}
			first = false;
			smart_str_append(&result, zv::Ref(entry.value().deref().raw()).asString());
		}
		smart_str_append_smart_str(&result, &append);
		smart_str_free(&append);
		smart_str_appendc(&result, '}');
		smart_str_0(&result);
		return zv::Val::adoptString(result.s);
	}

	/* }}} */
};

HashTable *ConstantArrayType::pt_carr_sort_values = nullptr;

} // namespace phpstanturbo

using phpstanturbo::ConstantArrayType;
using phpstanturbo::NullableLong;

bool pt_constant_array_type_new(zval *out, zval *keyTypes, zval *valueTypes, zval *nextAutoIndexes, zval *optionalKeys, zval *isList, zval *unsealed)
{
	return pt_val_into(ConstantArrayType::create(keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList, unsealed), out);
}

bool pt_constant_array_type_is_valid_identifier(zend_string *value, bool &out)
{
	return ConstantArrayType::isValidIdentifier(value, out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_RETURN_BOOL_OR_THROW(expr) \
	do { \
		bool pt_out__; \
		if (UNEXPECTED(!(expr))) { \
			RETURN_THROWS(); \
		} \
		RETURN_BOOL(pt_out__); \
	} while (0)

#define PT_THIS ConstantArrayType(Z_OBJ_P(ZEND_THIS))

/* the twin's `self` and `?array` return types */

/* (Type $type) → a Type */
static void pt_cat_one_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (ConstantArrayType::*method)(zval *) const)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(type));
}

/* () → a Type */
static void pt_cat_no_args(INTERNAL_FUNCTION_PARAMETERS, zv::Val (ConstantArrayType::*method)() const)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL((PT_THIS.*method)());
}

/* (callable $cb) → a Type */
static void pt_cat_callable(INTERNAL_FUNCTION_PARAMETERS, zv::Val (ConstantArrayType::*method)(zend_fcall_info *, zend_fcall_info_cache *) const)
{
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_FUNC(fci, fcc)
	ZEND_PARSE_PARAMETERS_END();
	PT_RETURN_VAL((PT_THIS.*method)(&fci, &fcc));
}

static void ZEND_FASTCALL catIsUnsealed(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY_OR_THROW(PT_THIS.isUnsealed());
}

static void ZEND_FASTCALL catGetUnsealedTypes(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getUnsealedTypes());
}

static void ZEND_FASTCALL catRecreate(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *keyTypes, *valueTypes, *nextAutoIndexes, *optionalKeys, *isList, *unsealed;
	if (!zp::parse<zp::Arr, zp::Arr, zp::Arr, zp::Arr, zp::ObjOrNull, zp::ArrOrNull>(execute_data, keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList, unsealed)) RETURN_THROWS();
	zval nullZv;
	ZVAL_NULL(&nullZv);
	if (isList != NULL && UNEXPECTED(!ConstantArrayType::checkNullableTrinary(isList, 5))) RETURN_THROWS();
	PT_RETURN_VAL(ConstantArrayType::recreate(keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList != NULL ? isList : &nullZv, unsealed != NULL ? unsealed : &nullZv));
}

static void ZEND_FASTCALL catGetIterableKeyType(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getIterableKeyType);
}

static void ZEND_FASTCALL catGetIterableValueType(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getIterableValueType);
}

static void ZEND_FASTCALL catGetKeyType(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getKeyType);
}

static void ZEND_FASTCALL catGetItemType(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getItemType);
}

static void ZEND_FASTCALL catGetKeyTypes(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getKeyTypes);
}

static void ZEND_FASTCALL catGetValueTypes(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getValueTypes);
}

static void ZEND_FASTCALL catIsOptionalKey(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_long i;
	if (!zp::parse<zp::Long>(execute_data, i)) RETURN_THROWS();
	PT_RETURN_BOOL_OR_THROW(PT_THIS.isOptionalKey(i, pt_out__));
}

static void ZEND_FASTCALL catFindTypeAndMethodNames(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::findTypeAndMethodNames);
}

static void ZEND_FASTCALL catHasOffsetValueType(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *offsetType;
	if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
	PT_RETURN_TRINARY_OR_THROW(PT_THIS.hasOffsetValueType(offsetType));
}

static void ZEND_FASTCALL catGetOffsetValueType(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getOffsetValueType);
}

static void ZEND_FASTCALL catUnsetOffset(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *offsetType;
	bool preserveListCertainty = false;
	if (!zp::parse<zp::Obj, zp::Opt<zp::Bool>>(execute_data, offsetType, preserveListCertainty)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.unsetOffset(offsetType, preserveListCertainty));
}

static void ZEND_FASTCALL catReverseArray(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::reverseArray);
}

static void ZEND_FASTCALL catSliceArray(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *offsetType, *lengthType, *preserveKeys;
	if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, offsetType, lengthType, preserveKeys)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.sliceArray(offsetType, lengthType, preserveKeys));
}

static void ZEND_FASTCALL catIsIterableAtLeastOnce(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY_OR_THROW(PT_THIS.isIterableAtLeastOnce());
}

static void ZEND_FASTCALL catGetArraySize(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getArraySize);
}

static void ZEND_FASTCALL catIsList(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::isList);
}

static void ZEND_FASTCALL catToBoolean(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::toBoolean);
}

static void ZEND_FASTCALL catGetValuesArray(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getValuesArray);
}

static void ZEND_FASTCALL catDescribe(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::describe);
}

static void ZEND_FASTCALL catTraverse(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_cat_callable(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::traverse);
}

void pt_register_constant_array_type()
{
	pt_carr_identifier_regex = zend_string_init_interned(PT_LC("~^(?:[\\\\]?+[a-z_\\x80-\\xFF][0-9a-z_\\x80-\\xFF-]*+)++$~si"), 1);

	reg::Class cls("PHPStan\\Type\\Constant\\ConstantArrayType");
	ptdecl::ConstantArrayType::declareClass(cls);
	/* the slots PT_CAT_PROP_*: the eight class-body properties first, the
	 * four promoted constructor properties after them */
	cls.privateTypedClassProperty("isList", ptcls::trinaryLogic, false);
	cls.privateTypedProperty("unsealed", MAY_BE_ARRAY | MAY_BE_NULL);
	cls.privateTypedPropertyDefaultNull("allArrays", MAY_BE_ARRAY | MAY_BE_NULL);
	cls.privateTypedClassPropertyDefaultNull("iterableKeyType", ptcls::type);
	cls.privateTypedClassPropertyDefaultNull("iterableValueType", ptcls::type);
	cls.privateTypedClassPropertyDefaultNull("keyTypesUnion", ptcls::type);
	cls.privateTypedPropertyDefaultNull("keyIndexMap", MAY_BE_ARRAY | MAY_BE_NULL);
	cls.privateTypedPropertyDefaultNull("optionalKeySet", MAY_BE_ARRAY | MAY_BE_NULL);
	cls.privateTypedProperty("keyTypes", MAY_BE_ARRAY);
	cls.privateTypedProperty("valueTypes", MAY_BE_ARRAY);
	cls.privateTypedProperty("nextAutoIndexes", MAY_BE_ARRAY);
	cls.privateTypedProperty("optionalKeys", MAY_BE_ARRAY);
	cls.privateClassConstantLong("DESCRIBE_LIMIT", PT_CAT_DESCRIBE_LIMIT);
	cls.privateClassConstantLong("CHUNK_FINITE_TYPES_LIMIT", PT_CAT_CHUNK_FINITE_TYPES_LIMIT);
	cls.privateClassConstantString("UNSEALED_ARRAY_SHAPES_LINK", PT_CAT_UNSEALED_ARRAY_SHAPES_LINK);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *keyTypes, *valueTypes, *nextAutoIndexes = NULL, *optionalKeys = NULL, *isList = NULL, *unsealed = NULL;
		if (!zp::parse<zp::Arr, zp::Arr, zp::Opt<zp::Arr>, zp::Opt<zp::Arr>, zp::Opt<zp::ObjOrNull>, zp::Opt<zp::ArrOrNull>>(execute_data, keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList, unsealed)) RETURN_THROWS();
		if (isList != NULL && UNEXPECTED(!ConstantArrayType::checkNullableTrinary(isList, 5))) RETURN_THROWS();
		if (UNEXPECTED(!PT_THIS.construct(keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList, unsealed))) RETURN_THROWS();
	});

	cls.method(sigs::isSealed, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::isSealed);
	});

	cls.method(sigs::isUnsealed, catIsUnsealed);
	cls.op<PT_OP_IS_UNSEALED, &ConstantArrayType::isUnsealed>();

	cls.method(sigs::getUnsealedTypes, catGetUnsealedTypes);

	cls.method(sigs::dropUnsealedTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::dropUnsealedTypes);
	});

	cls.method(sigs::recreate, catRecreate);

	cls.method(sigs::getConstantArrays, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getConstantArrays);
	});
	cls.op<PT_OP_GET_CONSTANT_ARRAYS, &ConstantArrayType::getConstantArrays>();

	cls.method(sigs::getReferencedClasses, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getReferencedClasses);
	});
	cls.op<PT_OP_GET_REFERENCED_CLASSES, &ConstantArrayType::getReferencedClasses>();

	cls.method(sigs::getIterableKeyType, catGetIterableKeyType);
	cls.op<PT_OP_GET_ITERABLE_KEY_TYPE, &ConstantArrayType::getIterableKeyType>();
	cls.method(sigs::getIterableValueType, catGetIterableValueType);
	cls.op<PT_OP_GET_ITERABLE_VALUE_TYPE, &ConstantArrayType::getIterableValueType>();
	cls.method(sigs::getKeyType, catGetKeyType);
	cls.method(sigs::getItemType, catGetItemType);
	cls.op<PT_OP_GET_ITEM_TYPE, &ConstantArrayType::getItemType>();

	cls.method(sigs::isConstantValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isConstantValue());
	});

	cls.method(sigs::getNextAutoIndexes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getNextAutoIndexes);
	});

	cls.method(sigs::getOptionalKeys, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getOptionalKeys);
	});
	cls.op<PT_OP_GET_OPTIONAL_KEYS, &ConstantArrayType::getOptionalKeys>();

	cls.method(sigs::getAllArrays, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getAllArrays);
	});

	cls.method(sigs::getKeyTypes, catGetKeyTypes);
	cls.op<PT_OP_GET_KEY_TYPES, &ConstantArrayType::getKeyTypes>();
	cls.method(sigs::getValueTypes, catGetValueTypes);
	cls.op<PT_OP_GET_VALUE_TYPES, &ConstantArrayType::getValueTypes>();
	cls.method(sigs::isOptionalKey, catIsOptionalKey);

	cls.method(sigs::sortKeys, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::sortKeys);
	});

	cls.method<&ConstantArrayType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return ConstantArrayType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::isSuperTypeOf);
	});
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &ConstantArrayType::isSuperTypeOf>();

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.looseCompare(type));
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::TypeObj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_BOOL_OR_THROW(PT_THIS.equals(type, pt_out__));
	});
	cls.op<PT_OP_EQUALS, &ConstantArrayType::equals>();

	cls.method(sigs::isCallable, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isCallable());
	});
	cls.op<PT_OP_IS_CALLABLE, &ConstantArrayType::isCallable>();

	cls.method<&ConstantArrayType::getCallableParametersAcceptors, zp::Obj>(sigs::getCallableParametersAcceptors);

	cls.method(sigs::findTypeAndMethodNames, catFindTypeAndMethodNames);

	cls.method(sigs::hasOffsetValueType, catHasOffsetValueType);
	cls.op<PT_OP_HAS_OFFSET_VALUE_TYPE, &ConstantArrayType::hasOffsetValueType>();
	cls.method(sigs::getOffsetValueType, catGetOffsetValueType);
	cls.op<PT_OP_GET_OFFSET_VALUE_TYPE, &ConstantArrayType::getOffsetValueType>();

	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		bool unionValues = true;
		if (!zp::parse<zp::ObjOrNull, zp::Obj, zp::Opt<zp::Bool>>(execute_data, offsetType, valueType, unionValues)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.setOffsetValueType(offsetType, valueType, unionValues));
	});

	cls.method<&ConstantArrayType::setExistingOffsetValueType, zp::Obj, zp::Obj>(sigs::setExistingOffsetValueType);

	cls.method(sigs::unsetOffset, catUnsetOffset);

	cls.method<&ConstantArrayType::chunkArray, zp::Obj, zp::Obj>(sigs::chunkArray);

	cls.method(sigs::fillKeysArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::fillKeysArray);
	});

	cls.method(sigs::flipArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::flipArray);
	});

	cls.method(sigs::intersectKeyArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::intersectKeyArray);
	});

	cls.method(sigs::popArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::popArray);
	});

	cls.method(sigs::reverseArray, catReverseArray);

	cls.method(sigs::searchArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *needleType, *strict = NULL;
		if (!zp::parse<zp::Obj, zp::Opt<zp::ObjOrNull>>(execute_data, needleType, strict)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.searchArray(needleType, strict));
	});

	cls.method(sigs::shiftArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::shiftArray);
	});

	cls.method(sigs::shuffleArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::shuffleArray);
	});

	cls.method(sigs::sliceArray, catSliceArray);

	cls.method<&ConstantArrayType::spliceArray, zp::Obj, zp::Obj, zp::Obj>(sigs::spliceArray);

	cls.method(sigs::truncateListToSize, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::truncateListToSize);
	});

	cls.method<&ConstantArrayType::extractTruncateListBounds, zp::Obj>(sigs::extractTruncateListBounds);

	cls.method(sigs::isIterableAtLeastOnce, catIsIterableAtLeastOnce);
	cls.op<PT_OP_IS_ITERABLE_AT_LEAST_ONCE, &ConstantArrayType::isIterableAtLeastOnce>();
	cls.method(sigs::getArraySize, catGetArraySize);

	cls.method(sigs::getFirstIterableKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getFirstIterableKeyType);
	});

	cls.method(sigs::getLastIterableKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getLastIterableKeyType);
	});

	cls.method(sigs::getFirstIterableValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getFirstIterableValueType);
	});

	cls.method(sigs::getLastIterableValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getLastIterableValueType);
	});

	cls.method(sigs::isConstantArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(ConstantArrayType::isConstantArray()));
	});
	cls.op(PT_OP_IS_CONSTANT_ARRAY, PT_OP_LAMBDA { return pt_op_trinary(ConstantArrayType::isConstantArray()); });

	cls.method(sigs::isList, catIsList);
	cls.op<PT_OP_IS_LIST, &ConstantArrayType::isList>();
	cls.method(sigs::toBoolean, catToBoolean);

	cls.method(sigs::toInteger, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::toInteger);
	});

	cls.method(sigs::toFloat, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::toFloat);
	});

	cls.method(sigs::generalize, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::generalize);
	});

	cls.method(sigs::generalizeValues, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::generalizeValues);
	});

	cls.method(sigs::getKeysArrayFiltered, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(PT_THIS.getKeysArrayFiltered());
	});

	cls.method(sigs::getKeysArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getKeysArray);
	});

	cls.method(sigs::getValuesArray, catGetValuesArray);

	cls.method(sigs::describe, catDescribe);
	cls.op<PT_OP_DESCRIBE, &ConstantArrayType::describe>();

	cls.method(sigs::inferTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::inferTemplateTypes);
	});

	cls.method(sigs::getReferencedTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getReferencedTemplateTypes);
	});
	cls.op<PT_OP_GET_REFERENCED_TEMPLATE_TYPES, &ConstantArrayType::getReferencedTemplateTypes>();

	cls.method(sigs::tryRemove, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::tryRemove);
	});

	cls.method(sigs::traverse, catTraverse);
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_with<ConstantArrayType>(self, argv); });

	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *right;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(right)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously(right, &fci, &fcc));
	});

	cls.method(sigs::isKeysSupersetOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherArray;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(otherArray, pt_ce_constant_array_type)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_BOOL_OR_THROW(PT_THIS.isKeysSupersetOf(otherArray, pt_out__));
	});

	cls.method(sigs::mergeWith, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherArray;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(otherArray, pt_ce_constant_array_type)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.mergeWith(otherArray));
	});

	cls.method(sigs::makeOffsetRequired, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::makeOffsetRequired);
	});

	cls.method(sigs::makeList, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::makeList);
	});

	cls.method(sigs::makeListMaybe, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::makeListMaybe);
	});

	cls.method(sigs::mapValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_callable(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::mapValueType);
	});

	cls.method(sigs::mapKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.mapKeyType());
	});

	cls.method(sigs::makeAllArrayKeysOptional, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::makeAllArrayKeysOptional);
	});

	cls.method(sigs::changeKeyCaseArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long caseValue = 0;
		bool caseIsNull = false;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_LONG_OR_NULL(caseValue, caseIsNull)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.changeKeyCaseArray(caseIsNull ? NullableLong::null() : NullableLong::of(caseValue)));
	});

	cls.method(sigs::filterArrayRemovingFalsey, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::filterArrayRemovingFalsey);
	});

	cls.method(sigs::toPhpDocNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::toPhpDocNode);
	});

	cls.method(sigs::isValidIdentifier, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *value;
		if (!zp::parse<zp::Str>(execute_data, value)) RETURN_THROWS();
		PT_RETURN_BOOL_OR_THROW(ConstantArrayType::isValidIdentifier(value, pt_out__));
	});

	cls.method(sigs::getFiniteTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cat_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantArrayType::getFiniteTypes);
	});

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_BOOL_OR_THROW(PT_THIS.hasTemplateOrLateResolvableType(pt_out__));
	});
	cls.op<PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, &ConstantArrayType::hasTemplateOrLateResolvableType>();

	/* `use ArrayTypeTrait { chunkArray as traitChunkArray; }`: the trait's
	 * chunkArray() under its alias (the trait's own name is overridden by
	 * the class's chunkArray() above) */
	cls.method("traitChunkArray", reg::Public, 2, { reg::obj("lengthType", ptcls::type), reg::obj("preserveKeys", ptcls::trinaryLogic) }, pt_carr_array_trait_chunk_array_handler(), &ptret::type);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::ConstantArrayType::registerTraits(cls);

	cls.shadow(&pt_ce_constant_array_type);
}

/* }}} */
