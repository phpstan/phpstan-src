/*
 * PHPStanTurbo\IntersectionType — native implementation of
 * PHPStan\Type\IntersectionType.
 *
 * Declared as PHPStan\Type\IntersectionType itself at activation: not final
 * (the PHP TemplateIntersectionType extends it — its constructor calls
 * parent::__construct(), so the constructor is a proper method),
 * implementing PHPStan\Type\CompoundType. State is the twin's seventeen
 * private properties, declared typed property slots in the twin's
 * declaration order — the sorted-types memo, the twelve TrinaryLogic memos,
 * the three per-offset/per-level caches, and the promoted $types after
 * them — so the std object handlers do GC/clone and a PHP subclass's own
 * properties follow them. The two traits the twin uses come from the
 * shared registrars in TypeTraits.cpp, run after the class's own methods
 * (the class body's tryRemove() wins over NonRemoveableTypeTrait's).
 *
 * Every `$this->method()` the twin makes goes through the object's class
 * entry — a subclass may have overridden it (TemplateIntersectionType's
 * equals(), describe() and accepts()) — with a direct C++ call when the
 * object is exactly an IntersectionType. The private
 * intersectResults()/intersectTypes() helpers take the twin's closures as
 * C++ callables: nothing can override a private method, so they never cross
 * into PHP per member. TypeUtils::toBenevolentUnion() is
 * pt_union_to_benevolent() (BenevolentUnionType.cpp).
 */

#include "TypeTraits.h"
#include "generated/IntersectionType.h"

namespace slots = ptdecl::IntersectionType::slot;
namespace sigs = ptdecl::IntersectionType::sig;

#include <algorithm>
#include <vector>

zend_class_entry *pt_ce_intersection_type = nullptr;

/* AcceptsResult.cpp: the result objects' slots */
#define PT_RESULT_PROP_RESULT 0
#define PT_RESULT_PROP_REASONS 1
zval *pt_result_array_slot(zend_object *object, uint32_t slot, const char *propertyName);

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

/* $object->method(...) that returns a Type: the result checked to be an
 * object (the engine's return check of the PHP twin); UNDEF = pending
 * exception */
static zv::Val callType(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	return pt_type_call_type(object, lcname, len, argc, argv);
}

/* $object->method() returning string; UNDEF = pending exception */
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

/* TypeCombinator::<method>($a, $b); UNDEF = pending exception */
static zv::Val combinator2(const char *lcname, size_t len, zval *a, zval *b)
{
	zv::Args args{a, b};
	return pt_type_combinator_call(lcname, len, 2, args);
}

/* TypeCombinator::intersect(...$types) over a PHP array of types */
static zv::Val combinatorIntersect(HashTable *types)
{
	return pt_type_combinator_call_spread(PT_LC("intersect"), types);
}

/* throw new ShouldNotHappenException($message) ($message NULL = the
 * default) */
static void throwShouldNotHappen(zval *message)
{
	if (message == NULL) {
		pt_throw_should_not_happen();
		return;
	}
	zv::Val exception = pt_type_new(PT_CLASS_SHOULD_NOT_HAPPEN, 1, message);
	if (UNEXPECTED(exception.isUndef())) return;
	zval raw = exception.take();
	zend_throw_exception_object(&raw);
}

/* throw new <class-map exception>(...$args) */
static void throwMapped(int classIdx, uint32_t argc, zval *argv)
{
	zv::Val exception = pt_type_new(classIdx, argc, argv);
	if (UNEXPECTED(exception.isUndef())) return;
	zval raw = exception.take();
	zend_throw_exception_object(&raw);
}

/* Class::NAME of a class-map class — a literal class constant, borrowed;
 * NULL = pending exception */
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

/* VerbosityLevel::<factory>() for a PT_VERBOSITY_LEVEL_* value (the
 * shadowing class's singleton, VerbosityLevel.cpp) */
static zv::Val verbosityLevel(zend_long value)
{
	return pt_type_verbosity_level(value);
}

/* a public property of an object (owned copy); UNDEF = pending exception */
static zv::Val readProperty(zval *object, const char *name, size_t len)
{
	zval rv;
	ZVAL_UNDEF(&rv);
	zval *value = zend_read_property(Z_OBJCE_P(object), Z_OBJ_P(object), name, len, 0, &rv);
	if (UNEXPECTED(value == NULL || EG(exception))) return zv::Val();
	zv::Val copy = zv::Val::copyOf(zv::Ref(value).deref());
	zval_ptr_dtor(&rv);
	return copy;
}

/* $type->describe($level), a string; UNDEF = pending exception */
static zv::Val describeOf(zval *type, zval *level)
{
	return callString(Z_OBJ_P(type), PT_LC("describe"), 1, level);
}

/* whether $type is a MixedType describing as plain 'mixed' at the precise
 * level and not explicit — the twin's $isMixedValueType test; false with
 * an exception pending */
static bool isPlainMixed(zval *type, bool &out)
{
	out = false;
	if (!zv::Ref(type).instanceOf(pt_ce_mixed_type)) return true;
	zv::Val precise = verbosityLevel(PT_VERBOSITY_LEVEL_PRECISE);
	if (UNEXPECTED(precise.isUndef())) return false;
	zv::Val description = describeOf(type, precise.raw());
	if (UNEXPECTED(description.isUndef())) return false;
	if (!zend_string_equals_literal(zv::Ref(description.raw()).asString(), "mixed")) return true;
	int explicitMixed = pt_type_call_is_true(Z_OBJ_P(type), PT_LC("isexplicitmixed"), 0, NULL);
	if (UNEXPECTED(explicitMixed < 0)) return false;
	out = explicitMixed == 0;
	return true;
}

/* the value a string $a <=> $b has after strcasecmp() found them equal
 * ignoring case: the engine's comparison of the two strings */
static int compareStrings(zend_string *a, zend_string *b)
{
	int cmp = strcasecmp(ZSTR_VAL(a), ZSTR_VAL(b));
	if (cmp != 0) return cmp;
	zval za, zb;
	ZVAL_STR(&za, a);
	ZVAL_STR(&zb, b);
	return zend_compare(&za, &zb);
}

/* new IntegerRangeType::fromInterval($min, $max) */
static zv::Val integerRange(NullableLong min, NullableLong max)
{
	return pt_integer_range_from_interval(min, max, 0);
}

/* new ConstantIntegerType($value) / new StringType() / new IntegerType() /
 * new BooleanType() / new ObjectWithoutClassType() / new ClassStringType() */
static zv::Val constantInteger(zend_long value) { return pt_type_new_constant_integer(value); }

static zv::Val stringType()
{
	return pt_val_of<pt_string_type_new>();
}

static zv::Val integerType()
{
	return pt_val_of<pt_integer_type_new>();
}

static zv::Val booleanType()
{
	return pt_val_of<pt_boolean_type_new>();
}

static zv::Val classStringType()
{
	return pt_val_of<pt_class_string_type_new>();
}

/* new IntersectionType([new StringType(), new AccessoryNonFalsyStringType()]) */
static zv::Val accessoryNonFalsyString()
{
	zv::Val accessory;
	if (UNEXPECTED(!pt_accessory_non_falsy_string_type_new(accessory.raw()))) return zv::Val();
	return accessory;
}

static zv::Val nonFalsyString()
{
	zv::Val string = stringType();
	zv::Val accessory = accessoryNonFalsyString();
	if (UNEXPECTED(string.isUndef() || accessory.isUndef())) return zv::Val();
	zv::Arr types = zv::Arr::create(2);
	types.push(std::move(string));
	types.push(std::move(accessory));
	return pt_intersection_of(std::move(types));
}

/* new UnionType([new ConstantIntegerType(0), new ConstantIntegerType(1)]) */
static zv::Val callableArrayOffsets()
{
	zv::Val zero = constantInteger(0);
	zv::Val one = constantInteger(1);
	if (UNEXPECTED(zero.isUndef() || one.isUndef())) return zv::Val();
	zv::Arr types = zv::Arr::create(2);
	types.push(std::move(zero));
	types.push(std::move(one));
	return pt_type_new_union(std::move(types));
}

/* new UnionType([$a, $b]) ($a and $b consumed) */
static zv::Val unionOf2(zv::Val a, zv::Val b)
{
	if (UNEXPECTED(a.isUndef() || b.isUndef())) return zv::Val();
	zv::Arr types = zv::Arr::create(2);
	types.push(std::move(a));
	types.push(std::move(b));
	return pt_type_new_union(std::move(types));
}

/* the offset type of a HasOffsetType / HasOffsetValueType member */
static zv::Val offsetTypeOf(zval *type)
{
	if (zv::Ref(type).instanceOf(pt_ce_has_offset_value_type)) return pt_has_offset_value_type_get_offset_type(Z_OBJ_P(type));
	return pt_has_offset_type_get_offset_type(Z_OBJ_P(type));
}

/* the member types the twin's `$type instanceof HasOffsetValueType ||
 * $type instanceof HasOffsetType` picks */
static bool isOffsetAccessory(zval *type)
{
	return zv::Ref(type).instanceOf(pt_ce_has_offset_value_type) || zv::Ref(type).instanceOf(pt_ce_has_offset_type);
}

/* $type instanceof <one of the seven string accessories>; false with an
 * exception pending */
static bool isStringAccessory(zval *type, bool &out)
{
	zend_class_entry *const classes[] = {
		pt_ce_accessory_non_empty_string_type,
		pt_ce_accessory_literal_string_type,
		pt_ce_accessory_numeric_string_type,
		pt_ce_accessory_non_falsy_string_type,
		pt_ce_accessory_lowercase_string_type,
		pt_ce_accessory_uppercase_string_type,
		pt_ce_accessory_decimal_integer_string_type,
	};
	for (zend_class_entry *ce : classes) {
		bool is;
		if (UNEXPECTED(!isInstance(type, ce, is))) return false;
		if (is) {
			out = true;
			return true;
		}
	}
	out = false;
	return true;
}

/* array_values(array_intersect_key(...$compare)): the entries of the first
 * map whose key every other map has, in the first map's order */
static zv::Val intersectKeysOf(std::vector<zv::Val> &compare)
{
	zv::Arr result = zv::Arr::create(0);
	if (compare.empty()) return zv::Val(std::move(result));
	for (zv::ArrayEntry entry : zv::ArrRef(compare[0].raw())) {
		bool inAll = true;
		for (size_t i = 1; i < compare.size(); i++) {
			zend_string *key = entry.stringKeyOrNull();
			zval *found = key != NULL ? zend_hash_find(zv::ArrRef(compare[i].raw()).table(), key) : zend_hash_index_find(zv::ArrRef(compare[i].raw()).table(), entry.indexKey());
			if (found == NULL) {
				inAll = false;
				break;
			}
		}
		if (inAll) {
			result.push(entry.value());
		}
	}
	return zv::Val(std::move(result));
}

/* Mirrors PHPStan\Type\IntersectionType. State lives in the PHP object's
 * slots. */
class IntersectionType
{
public:
	explicit IntersectionType(zend_object *self) : self(self) {}

	/* __construct(private array $types): fewer than two members throw;
	 * false = pending exception */
	[[nodiscard]] bool construct(zval *typesArg)
	{
		zval *typesSlot = OBJ_PROP_NUM(self, slots::types);
		/* the slot is overwritten in place: a repeated parent::__construct()
		 * call from a subclass would otherwise leak the first value */
		zval previous;
		ZVAL_COPY_VALUE(&previous, typesSlot);
		ZVAL_COPY(typesSlot, typesArg);
		Z_PROP_FLAG_P(typesSlot) = 0; /* no longer IS_PROP_UNINIT */
		if (Z_TYPE(previous) != IS_UNDEF) {
			zval_ptr_dtor(&previous);
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(typesArg)) < 2) {
			throwCannotCreate(typesArg);
			return false;
		}
		return true;
	}

	/* new IntersectionType($types) ($types consumed); UNDEF = pending
	 * exception */
	static zv::Val create(zv::Val types)
	{
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_intersection_type) != SUCCESS)) return zv::Val();
		if (UNEXPECTED(!IntersectionType(Z_OBJ(object)).construct(types.raw()))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

	/* $this->types (borrowed); NULL with an Error pending when the
	 * constructor never ran, as the twin's typed-property read raises */
	[[nodiscard]] zval *types() const { return typesOf(self); }

	static zval *typesOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::types);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_ARRAY)) {
			zend_throw_error(NULL, "Typed property %s::$types must not be accessed before initialization", ZSTR_VAL(pt_ce_intersection_type->name));
			return NULL;
		}
		return slot;
	}

	zv::Val getTypes() const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(types));
	}

	/* $this->sortedTypesCache ??= UnionTypeHelper::sortTypes($this->types);
	 * an owned copy of the list; UNDEF = pending exception */
	zv::Val getSortedTypes() const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::sortedTypesCache);
		if (Z_TYPE_P(slot) == IS_NULL) {
			zval *types = this->types();
			if (UNEXPECTED(types == NULL)) return zv::Val();
			zv::Val sorted = pt_union_type_helper_sort_types(types);
			if (UNEXPECTED(sorted.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(sorted.raw()).isArray())) {
				zend_type_error("phpstan_turbo: UnionTypeHelper::sortTypes() must return array");
				return zv::Val();
			}
			slot = OBJ_PROP_NUM(self, slots::sortedTypesCache);
			zv::Ref(slot).assign(std::move(sorted));
		}
		return zv::Val::copyOf(zv::Ref(slot));
	}

	/* the intersection of $templateType->inferTemplateTypes() over every member */
	zv::Val inferTemplateTypesOn(zval *templateType) const
	{
		return mapIntersect(PT_LC("infertemplatetypes"), templateType, true);
	}

	/* the concatenations over every member */
	zv::Val getReferencedClasses() const { return concatOf(PT_LC("getreferencedclasses"), 0, NULL); }

	/* array_values(array_unique(the members' class names)) */
	zv::Val getObjectClassNames() const
	{
		zv::Val names = concatOf(PT_LC("getobjectclassnames"), 0, NULL);
		if (UNEXPECTED(names.isUndef())) return zv::Val();
		zv::ScratchTable seen(zv::ArrRef(names.raw()).size());
		zv::Arr values = zv::Arr::create(zv::ArrRef(names.raw()).size());
		for (zv::ArrayEntry entry : zv::ArrRef(names.raw())) {
			zv::Ref value = entry.value().deref();
			zend_string *tmp;
			zend_string *str = zval_get_tmp_string(value.raw(), &tmp);
			if (UNEXPECTED(EG(exception))) {
				zend_tmp_string_release(tmp);
				return zv::Val();
			}
			bool first = zend_hash_add_empty_element(seen.table(), str) != NULL;
			zend_tmp_string_release(tmp);
			if (!first) continue;
			values.push(value);
		}
		return zv::Val(std::move(values));
	}

	zv::Val getObjectClassReflections() const { return concatOf(PT_LC("getobjectclassreflections"), 0, NULL); }
	zv::Val getArrays() const { return concatOf(PT_LC("getarrays"), 0, NULL); }

	/* a callable array's shape built from its two offsets, else the
	 * members' constant arrays concatenated; UNDEF = pending exception */
	zv::Val getConstantArrays() const
	{
		int callableArray = isCallableArray();
		if (UNEXPECTED(callableArray < 0)) return zv::Val();
		if (callableArray == 1) {
			zv::Val builder = pt_constant_array_type_builder_create_empty();
			if (UNEXPECTED(builder.isUndef())) return zv::Val();
			for (zend_long i = 0; i < 2; i++) {
				zv::Val offset = constantInteger(i);
				if (UNEXPECTED(offset.isUndef())) return zv::Val();
				zv::Val valueType = thisGetOffsetValueType(offset.raw());
				if (UNEXPECTED(valueType.isUndef())) return zv::Val();
				zv::Args args{offset.raw(), valueType.raw()};
				zv::Val set = pt_type_call(Z_OBJ_P(builder.raw()), PT_LC("setoffsetvaluetype"), 2, args);
				if (UNEXPECTED(set.isUndef())) return zv::Val();
			}
			zv::Val constantArray = callType(Z_OBJ_P(builder.raw()), PT_LC("getarray"), 0, NULL);
			if (UNEXPECTED(constantArray.isUndef())) return zv::Val();
			bool isConstantArray;
			if (UNEXPECTED(!isInstance(constantArray.raw(), pt_ce_constant_array_type, isConstantArray))) return zv::Val();
			if (UNEXPECTED(!isConstantArray)) {
				throwShouldNotHappen(NULL);
				return zv::Val();
			}
			/* [$builder->getArray()] — asked a second time, as the twin does */
			zv::Val again = callType(Z_OBJ_P(builder.raw()), PT_LC("getarray"), 0, NULL);
			if (UNEXPECTED(again.isUndef())) return zv::Val();
			zv::Arr result = zv::Arr::create(1);
			result.push(std::move(again));
			return zv::Val(std::move(result));
		}
		return concatOf(PT_LC("getconstantarrays"), 0, NULL);
	}

	zv::Val getConstantStrings() const { return concatOf(PT_LC("getconstantstrings"), 0, NULL); }

	/* yes and'ed with every member's accepts(); a non-yes answer gains the
	 * list/non-empty reasons the members cannot phrase; UNDEF = pending
	 * exception */
	zv::Val accepts(zval *otherType, bool strictTypes) const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		zv::Val result = pt_type_accepts_result(PT_TRI_YES);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zval strictZv = {};
		ZVAL_BOOL(&strictZv, strictTypes);
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zv::Args args{otherType, &strictZv};
			zv::Val accepts = pt_type_call(entry.value().deref().asObject(), PT_LC("accepts"), 2, args);
			if (UNEXPECTED(accepts.isUndef())) return zv::Val();
			result = pt_type_result_and(std::move(result), accepts.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}

		zend_long value = pt_type_result_trinary(result.raw());
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (value != PT_TRI_YES) {
			zend_long isList = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("islist"), 0, NULL);
			if (UNEXPECTED(isList < 0)) return zv::Val();
			zv::Val reasons = resultReasons(result.raw());
			if (UNEXPECTED(reasons.isUndef())) return zv::Val();
			zv::Arr collected = zv::Arr::create(zv::ArrRef(reasons.raw()).size() + 2);
			for (zv::ArrayEntry entry : zv::ArrRef(reasons.raw())) {
				collected.push(entry.value());
			}
			zv::Args args{&selfZv, otherType};
			zv::Val verbosity = pt_type_verbosity_recommended(&selfZv, otherType);
			if (UNEXPECTED(verbosity.isUndef())) return zv::Val();
			zend_long thisIsList = thisTrinary(PT_LC("islist"), &IntersectionType::isList);
			if (UNEXPECTED(thisIsList < 0)) return zv::Val();
			bool added = false;
			if (thisIsList == PT_TRI_YES && isList != PT_TRI_YES) {
				zv::Val description = describeOf(otherType, verbosity.raw());
				if (UNEXPECTED(description.isUndef())) return zv::Val();
				smart_str reason = {NULL, 0};
				smart_str_append(&reason, zv::Ref(description.raw()).asString());
				smart_str_appendl(&reason, isList == PT_TRI_NO ? " is not a list." : " might not be a list.", isList == PT_TRI_NO ? sizeof(" is not a list.") - 1 : sizeof(" might not be a list.") - 1);
				smart_str_0(&reason);
				collected.push(zv::Val::adoptString(reason.s));
				added = true;
			}

			zend_long isNonEmpty = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("isiterableatleastonce"), 0, NULL);
			if (UNEXPECTED(isNonEmpty < 0)) return zv::Val();
			zend_long thisNonEmpty = thisTrinary(PT_LC("isiterableatleastonce"), &IntersectionType::isIterableAtLeastOnce);
			if (UNEXPECTED(thisNonEmpty < 0)) return zv::Val();
			if (thisNonEmpty == PT_TRI_YES && isNonEmpty != PT_TRI_YES) {
				zv::Val description = describeOf(otherType, verbosity.raw());
				if (UNEXPECTED(description.isUndef())) return zv::Val();
				smart_str reason = {NULL, 0};
				smart_str_append(&reason, zv::Ref(description.raw()).asString());
				smart_str_appendl(&reason, isNonEmpty == PT_TRI_NO ? " is empty." : " might be empty.", isNonEmpty == PT_TRI_NO ? sizeof(" is empty.") - 1 : sizeof(" might be empty.") - 1);
				smart_str_0(&reason);
				collected.push(zv::Val::adoptString(reason.s));
				added = true;
			}

			(void) added;
			if (zend_hash_num_elements(collected.table()) > 0) return withReasons(result.raw(), std::move(collected));
		}

		return result;
	}

	/* yes for an equal intersection or a never, else yes and'ed with every
	 * member's isSuperTypeOf(); UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *otherType) const
	{
		if (zv::Ref(otherType).instanceOf(pt_ce_intersection_type)) {
			int equal = thisEquals(otherType);
			if (UNEXPECTED(equal < 0)) return zv::Val();
			if (equal == 1) return pt_type_is_super_type_of_result(PT_TRI_YES);
		}
		if (zv::Ref(otherType).instanceOf(pt_ce_never_type)) return pt_type_is_super_type_of_result(PT_TRI_YES);

		zv::Val results = mapCall(PT_LC("issupertypeof"), 1, otherType);
		if (UNEXPECTED(results.isUndef())) return zv::Val();
		zv::Val yes = pt_type_is_super_type_of_result(PT_TRI_YES);
		if (UNEXPECTED(yes.isUndef())) return zv::Val();
		return pt_type_call_spread(Z_OBJ_P(yes.raw()), PT_LC("and"), zv::ArrRef(results.raw()).table());
	}

	/* $otherType->isSuperTypeOf($this) for a compound other type, else
	 * IsSuperTypeOfResult::lazyMaxMin() over the members (yes for an
	 * oversized array the other type may hold); UNDEF = pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		if (zv::Ref(otherType).instanceOf(pt_ce_intersection_type) || zv::Ref(otherType).instanceOf(pt_ce_union_type)) {
			bool isTemplate;
			if (UNEXPECTED(!isInstance(otherType, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
			if (!isTemplate) return pt_type_call(Z_OBJ_P(otherType), PT_LC("issupertypeof"), 1, &selfZv);
		}

		zv::Val result = lazyMaxMin(pt_ce_is_super_type_of_result, PT_LC("issupertypeof"), otherType, NULL);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zend_long value = pt_type_result_trinary(result.raw());
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (value != PT_TRI_NO) {
			zend_long oversized = thisTrinary(PT_LC("isoversizedarray"), &IntersectionType::isOversizedArray);
			if (UNEXPECTED(oversized < 0)) return zv::Val();
			if (oversized == PT_TRI_YES) {
				zend_long otherNonEmpty = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("isiterableatleastonce"), 0, NULL);
				if (UNEXPECTED(otherNonEmpty < 0)) return zv::Val();
				if (otherNonEmpty != PT_TRI_NO) return pt_type_is_super_type_of_result(PT_TRI_YES);
			}
		}
		return result;
	}

	/* AcceptsResult::lazyMaxMin() over $acceptingType->accepts() of the
	 * members, a yes re-checked against the holistic isSuperTypeOf() (the
	 * template members' eager yes distrusted), yes for an oversized array
	 * not refused; UNDEF = pending exception */
	zv::Val isAcceptedBy(zval *acceptingType, bool strictTypes) const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		zval strictZv = {};
		ZVAL_BOOL(&strictZv, strictTypes);
		zv::Val result = lazyMaxMin(pt_ce_accepts_result, PT_LC("accepts"), acceptingType, &strictZv);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zend_long value = pt_type_result_trinary(result.raw());
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (value == PT_TRI_YES) {
			zv::Val isSuperType = pt_type_call(Z_OBJ_P(acceptingType), PT_LC("issupertypeof"), 1, &selfZv);
			if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(isSuperType.raw()).isObject())) {
				zend_type_error("phpstan_turbo: isSuperTypeOf() must return %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
				return zv::Val();
			}
			zend_long superValue = pt_type_result_trinary(isSuperType.raw());
			if (UNEXPECTED(superValue < 0)) return zv::Val();
			if (superValue == PT_TRI_NO) return pt_type_call(Z_OBJ_P(isSuperType.raw()), PT_LC("toacceptsresult"), 0, NULL);
			if (superValue == PT_TRI_MAYBE) {
				zval *types = this->types();
				if (UNEXPECTED(types == NULL)) return zv::Val();
				zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
				for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
					zval *innerType = entry.value().deref().raw();
					bool isTemplate;
					if (UNEXPECTED(!isInstance(innerType, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
					if (!isTemplate) continue;
					zv::Args args{innerType, &strictZv};
					zend_long accepts = pt_type_call_result_trinary(Z_OBJ_P(acceptingType), PT_LC("accepts"), 2, args);
					if (UNEXPECTED(accepts < 0)) return zv::Val();
					if (accepts == PT_TRI_YES) return pt_type_call(Z_OBJ_P(isSuperType.raw()), PT_LC("toacceptsresult"), 0, NULL);
				}
			}
		}

		zend_long oversized = thisTrinary(PT_LC("isoversizedarray"), &IntersectionType::isOversizedArray);
		if (UNEXPECTED(oversized < 0)) return zv::Val();
		if (oversized == PT_TRI_YES && value != PT_TRI_NO) return pt_type_accepts_result(PT_TRI_YES);
		return result;
	}

	/* the same class (`$type instanceof static`) with the same member
	 * count, each member equal to one of the other's, consumed once; false
	 * with an exception pending */
	bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), self->ce)) {
			out = false;
			return true;
		}
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return false;
		zval *otherTypes = typesOf(Z_OBJ_P(type));
		if (UNEXPECTED(otherTypes == NULL)) return false;
		if (zend_hash_num_elements(Z_ARRVAL_P(types)) != zend_hash_num_elements(Z_ARRVAL_P(otherTypes))) {
			out = false;
			return true;
		}
		zv::Val otherTypesCopy = zv::Val::copyOf(zv::Ref(otherTypes));
		uint32_t otherCount = zend_hash_num_elements(Z_ARRVAL_P(otherTypesCopy.raw()));
		std::vector<zval *> others;
		std::vector<bool> used(otherCount, false);
		for (zv::ArrayEntry entry : zv::ArrRef(otherTypesCopy.raw())) {
			others.push_back(entry.value().deref().raw());
		}
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zend_object *innerType = entry.value().deref().asObject();
			bool match = false;
			for (size_t j = 0; j < others.size(); j++) {
				if (used[j]) continue;
				int equal = pt_type_call_is_true(innerType, PT_LC("equals"), 1, others[j]);
				if (UNEXPECTED(equal < 0)) return false;
				if (equal == 0) continue;
				match = true;
				used[j] = true;
				break;
			}
			if (!match) {
				out = false;
				return true;
			}
		}
		for (size_t j = 0; j < others.size(); j++) {
			if (!used[j]) {
				out = false;
				return true;
			}
		}
		out = true;
		return true;
	}

	/* describe(): the per-level cache; the type-only level describes the
	 * generalized non-accessory members, the value level the members with
	 * the accessories folded into their base types, the precise and cache
	 * levels every member; an owned string, UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		zv::Val levelValueZv = pt_type_call(Z_OBJ_P(level), PT_LC("getlevelvalue"), 0, NULL);
		if (UNEXPECTED(levelValueZv.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(levelValueZv.raw()).isLong())) {
			zend_type_error("phpstan_turbo: %s::getLevelValue() must return int", ZSTR_VAL(Z_OBJCE_P(level)->name));
			return zv::Val();
		}
		zend_long levelValue = zv::Ref(levelValueZv.raw()).asLong();
		zval *cache = OBJ_PROP_NUM(self, slots::cachedDescriptions);
		if (EXPECTED(Z_TYPE_P(cache) == IS_ARRAY)) {
			zval *cached = zend_hash_index_find(Z_ARRVAL_P(cache), levelValue);
			if (cached != NULL && Z_TYPE_P(cached) != IS_NULL) return zv::Val::copyOf(zv::Ref(cached));
		}

		pt_verbosity_case which;
		if (UNEXPECTED(!pt_type_verbosity_case(level, which))) return zv::Val();
		zv::Val description;
		if (which == PT_VERBOSITY_TYPE_ONLY) {
			description = describeType(level);
		} else if (which == PT_VERBOSITY_VALUE) {
			description = describeItself(level, true);
		} else {
			description = describeItself(level, false);
		}
		if (UNEXPECTED(description.isUndef())) return zv::Val();

		cache = OBJ_PROP_NUM(self, slots::cachedDescriptions);
		if (Z_TYPE_P(cache) != IS_ARRAY) {
			zv::Ref(cache).assign(zv::Val(zv::Arr::create(4)));
		}
		SEPARATE_ARRAY(cache);
		zval copy;
		ZVAL_COPY(&copy, description.raw());
		zend_hash_index_update(Z_ARRVAL_P(cache), levelValue, &copy);
		return description;
	}

	/* the type-only description: the non-accessory members generalized, a
	 * list shown as list<value>, sorted case-insensitively and joined with '&' */
	zv::Val describeType(zval *level) const
	{
		zend_long isListValue = thisTrinary(PT_LC("islist"), &IntersectionType::isList);
		if (UNEXPECTED(isListValue < 0)) return zv::Val();
		bool isList = isListValue == PT_TRI_YES;
		zv::Val sorted = getSortedTypes();
		if (UNEXPECTED(sorted.isUndef())) return zv::Val();
		zv::Val lessSpecific = pt_type_call_static(PT_CLASS_GENERALIZE_PRECISION, PT_LC("lessspecific"), 0, NULL);
		if (UNEXPECTED(lessSpecific.isUndef())) return zv::Val();
		std::vector<zv::Val> typeNames;
		zv::Val valueType;
		for (zv::ArrayEntry entry : zv::ArrRef(sorted.raw())) {
			zval *type = entry.value().deref().raw();
			if (isList) {
				bool isConstantArray;
				if (UNEXPECTED(!isInstance(type, pt_ce_constant_array_type, isConstantArray))) return zv::Val();
				if (zv::Ref(type).instanceOf(pt_ce_array_type) || isConstantArray) {
					valueType = callType(Z_OBJ_P(type), PT_LC("getiterablevaluetype"), 0, NULL);
					if (UNEXPECTED(valueType.isUndef())) return zv::Val();
					continue;
				}
				if (zv::Ref(type).instanceOf(pt_ce_non_empty_array_type)) continue;
			}
			bool isAccessory;
			if (UNEXPECTED(!isInstance(type, PT_CLASS_ACCESSORY_TYPE, isAccessory))) return zv::Val();
			if (isAccessory) continue;
			zv::Val generalized = callType(Z_OBJ_P(type), PT_LC("generalize"), 1, lessSpecific.raw());
			if (UNEXPECTED(generalized.isUndef())) return zv::Val();
			zv::Val name = describeOf(generalized.raw(), level);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			typeNames.push_back(std::move(name));
		}

		if (isList) {
			smart_str name = {NULL, 0};
			smart_str_appendl(&name, "list", 4);
			if (!valueType.isUndef()) {
				bool plainMixed;
				if (UNEXPECTED(!isPlainMixed(valueType.raw(), plainMixed))) {
					smart_str_free(&name);
					return zv::Val();
				}
				if (!plainMixed) {
					zv::Val inner = describeOf(valueType.raw(), level);
					if (UNEXPECTED(inner.isUndef())) {
						smart_str_free(&name);
						return zv::Val();
					}
					smart_str_appendc(&name, '<');
					smart_str_append(&name, zv::Ref(inner.raw()).asString());
					smart_str_appendc(&name, '>');
				}
			}
			smart_str_0(&name);
			typeNames.push_back(zv::Val::adoptString(name.s));
		}

		std::sort(typeNames.begin(), typeNames.end(), [](zv::Val &a, zv::Val &b) {
			return compareStrings(zv::Ref(a.raw()).asString(), zv::Ref(b.raw()).asString()) < 0;
		});
		return joinAmpersand(typeNames);
	}

	/* the value/precise description: the string accessories folded into
	 * the string member, the array members shown as (non-empty-)array/list
	 * with the array accessories absorbed, a common callable folded into
	 * callable-object/callable-string, the other accessories described
	 * themselves unless skipped; the members keep their sorted positions */
	zv::Val describeItself(zval *level, bool skipAccessoryTypes) const
	{
		pt_verbosity_case which;
		if (UNEXPECTED(!pt_type_verbosity_case(level, which))) return zv::Val();
		bool preciseOrCache = which == PT_VERBOSITY_PRECISE || which == PT_VERBOSITY_CACHE;

		zend_long isListValue = thisTrinary(PT_LC("islist"), &IntersectionType::isList);
		if (UNEXPECTED(isListValue < 0)) return zv::Val();
		zend_long isArrayValue = thisTrinary(PT_LC("isarray"), &IntersectionType::isArray);
		if (UNEXPECTED(isArrayValue < 0)) return zv::Val();
		zend_long nonEmptyValue = thisTrinary(PT_LC("isiterableatleastonce"), &IntersectionType::isIterableAtLeastOnce);
		if (UNEXPECTED(nonEmptyValue < 0)) return zv::Val();
		bool isList = isListValue == PT_TRI_YES;
		bool isArray = isArrayValue == PT_TRI_YES;
		bool isNonEmptyArray = nonEmptyValue == PT_TRI_YES;

		bool hasTemplateArray = false;
		if (isArray || isList) {
			zval *types = this->types();
			if (UNEXPECTED(types == NULL)) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(types)) {
				bool isTemplateArray;
				if (UNEXPECTED(!isInstance(entry.value().deref().raw(), pt_ce_template_array_type, isTemplateArray))) return zv::Val();
				if (isTemplateArray) {
					hasTemplateArray = true;
					break;
				}
			}
		}

		zv::Val sorted = getSortedTypes();
		if (UNEXPECTED(sorted.isUndef())) return zv::Val();
		uint32_t count = zv::ArrRef(sorted.raw()).size();
		std::vector<zval *> baseTypes(count, nullptr);
		std::vector<zval *> typesToDescribe(count, nullptr);
		std::vector<zv::Val> describedTypes(count);
		bool skipString = false;
		bool skipObject = false;
		bool nonEmptyStr = false;
		bool nonFalsyStr = false;
		uint32_t i = 0;
		for (zv::ArrayEntry entry : zv::ArrRef(sorted.raw())) {
			uint32_t index = i++;
			zval *type = entry.value().deref().raw();
			bool stringAccessory;
			if (UNEXPECTED(!isStringAccessory(type, stringAccessory))) return zv::Val();
			if (stringAccessory) {
				bool isCase;
				if (UNEXPECTED(!isInstance(type, pt_ce_accessory_lowercase_string_type, isCase))) return zv::Val();
				if (!isCase && UNEXPECTED(!isInstance(type, pt_ce_accessory_uppercase_string_type, isCase))) return zv::Val();
				if (isCase && !preciseOrCache) continue;
				bool isNonFalsy, isNonEmpty;
				if (UNEXPECTED(!isInstance(type, pt_ce_accessory_non_falsy_string_type, isNonFalsy) || !isInstance(type, pt_ce_accessory_non_empty_string_type, isNonEmpty))) {
					return zv::Val();
				}
				if (isNonFalsy) {
					nonFalsyStr = true;
				}
				if (isNonEmpty) {
					nonEmptyStr = true;
				}
				if (nonEmptyStr && nonFalsyStr) {
					/* prevent redundant 'non-empty-string&non-falsy-string' */
					for (uint32_t k = 0; k < count; k++) {
						if (typesToDescribe[k] == nullptr) continue;
						bool describedNonEmpty;
						if (UNEXPECTED(!isInstance(typesToDescribe[k], pt_ce_accessory_non_empty_string_type, describedNonEmpty))) return zv::Val();
						if (describedNonEmpty) {
							typesToDescribe[k] = nullptr;
						}
					}
				}
				typesToDescribe[index] = type;
				skipString = true;
				continue;
			}
			if (isList || isArray) {
				bool isTemplateArray;
				if (UNEXPECTED(!isInstance(type, pt_ce_template_array_type, isTemplateArray))) return zv::Val();
				if (isTemplateArray) {
					describedTypes[index] = describeOf(type, level);
					if (UNEXPECTED(describedTypes[index].isUndef())) return zv::Val();
					continue;
				}
				if (zv::Ref(type).instanceOf(pt_ce_array_type)) {
					zv::Val keyType = pt_array_type_get_key_type(Z_OBJ_P(type));
					if (UNEXPECTED(keyType.isUndef())) return zv::Val();
					zv::Val valueType = pt_array_type_get_item_type(Z_OBJ_P(type));
					if (UNEXPECTED(valueType.isUndef())) return zv::Val();
					smart_str described = {NULL, 0};
					if (isList) {
						bool plainMixedValue;
						if (UNEXPECTED(!isPlainMixed(valueType.raw(), plainMixedValue))) return zv::Val();
						if (isNonEmptyArray) {
							smart_str_appendl(&described, "non-empty-list", 14);
						} else {
							smart_str_appendl(&described, "list", 4);
						}
						if (!plainMixedValue) {
							zv::Val inner = describeOf(valueType.raw(), level);
							if (UNEXPECTED(inner.isUndef())) {
								smart_str_free(&described);
								return zv::Val();
							}
							smart_str_appendc(&described, '<');
							smart_str_append(&described, zv::Ref(inner.raw()).asString());
							smart_str_appendc(&described, '>');
						}
					} else {
						bool plainMixedKey, plainMixedValue;
						if (UNEXPECTED(!isPlainMixed(keyType.raw(), plainMixedKey) || !isPlainMixed(valueType.raw(), plainMixedValue))) return zv::Val();
						if (isNonEmptyArray) {
							smart_str_appendl(&described, "non-empty-array", 15);
						} else {
							smart_str_appendl(&described, "array", 5);
						}
						if (!plainMixedKey) {
							zv::Val innerKey = describeOf(keyType.raw(), level);
							if (UNEXPECTED(innerKey.isUndef())) {
								smart_str_free(&described);
								return zv::Val();
							}
							zv::Val innerValue = describeOf(valueType.raw(), level);
							if (UNEXPECTED(innerValue.isUndef())) {
								smart_str_free(&described);
								return zv::Val();
							}
							smart_str_appendc(&described, '<');
							smart_str_append(&described, zv::Ref(innerKey.raw()).asString());
							smart_str_appendl(&described, ", ", 2);
							smart_str_append(&described, zv::Ref(innerValue.raw()).asString());
							smart_str_appendc(&described, '>');
						} else if (!plainMixedValue) {
							zv::Val innerValue = describeOf(valueType.raw(), level);
							if (UNEXPECTED(innerValue.isUndef())) {
								smart_str_free(&described);
								return zv::Val();
							}
							smart_str_appendc(&described, '<');
							smart_str_append(&described, zv::Ref(innerValue.raw()).asString());
							smart_str_appendc(&described, '>');
						}
					}
					smart_str_0(&described);
					describedTypes[index] = zv::Val::adoptString(described.s);
					continue;
				}
				bool isConstantArray;
				if (UNEXPECTED(!isInstance(type, pt_ce_constant_array_type, isConstantArray))) return zv::Val();
				if (isConstantArray) {
					zv::Val description = describeOf(type, level);
					if (UNEXPECTED(description.isUndef())) return zv::Val();
					zend_string *s = zv::Ref(description.raw()).asString();
					bool startsWithList = ZSTR_LEN(s) >= 4 && memcmp(ZSTR_VAL(s), "list", 4) == 0;
					size_t kindLength = startsWithList ? 4 : 5;
					smart_str described = {NULL, 0};
					if (isNonEmptyArray) {
						zend_long typeNonEmpty = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isiterableatleastonce"), 0, NULL);
						if (UNEXPECTED(typeNonEmpty < 0)) return zv::Val();
						if (typeNonEmpty != PT_TRI_YES) {
							smart_str_appendl(&described, "non-empty-", 10);
						}
					}
					if (isList) {
						smart_str_appendl(&described, "list", 4);
					} else {
						smart_str_appendl(&described, "array", 5);
					}
					if (ZSTR_LEN(s) > kindLength) {
						smart_str_appendl(&described, ZSTR_VAL(s) + kindLength, ZSTR_LEN(s) - kindLength);
					}
					smart_str_0(&described);
					describedTypes[index] = zv::Val::adoptString(described.s);
					continue;
				}
				if (zv::Ref(type).instanceOf(pt_ce_non_empty_array_type) || zv::Ref(type).instanceOf(pt_ce_accessory_array_list_type)) {
					if (hasTemplateArray) {
						describedTypes[index] = describeOf(type, level);
						if (UNEXPECTED(describedTypes[index].isUndef())) return zv::Val();
					}
					continue;
				}
			}

			bool isCallable;
			if (UNEXPECTED(!isInstance(type, pt_ce_callable_type, isCallable))) return zv::Val();
			if (isCallable) {
				int common = pt_type_call_is_true(Z_OBJ_P(type), PT_LC("iscommoncallable"), 0, NULL);
				if (UNEXPECTED(common < 0)) return zv::Val();
				if (common == 1) {
					typesToDescribe[index] = type;
					skipObject = true;
					skipString = true;
					continue;
				}
			}

			bool isAccessory;
			if (UNEXPECTED(!isInstance(type, PT_CLASS_ACCESSORY_TYPE, isAccessory))) return zv::Val();
			if (!isAccessory) {
				baseTypes[index] = type;
				continue;
			}
			if (skipAccessoryTypes) continue;
			typesToDescribe[index] = type;
		}

		for (uint32_t k = 0; k < count; k++) {
			zval *type = baseTypes[k];
			if (type == nullptr) continue;
			zv::Val typeDescription = describeOf(type, level);
			if (UNEXPECTED(typeDescription.isUndef())) return zv::Val();
			zend_string *s = zv::Ref(typeDescription.raw()).asString();
			bool isObjectName = zend_string_equals_literal(s, "object");
			bool isStringName = zend_string_equals_literal(s, "string");
			bool skipped = (isObjectName && skipObject) || (isStringName && skipString);
			if ((isObjectName || isStringName) && skipped) {
				bool folded = false;
				for (uint32_t j = 0; j < count; j++) {
					zval *typeToDescribe = typesToDescribe[j];
					if (typeToDescribe == nullptr) continue;
					bool isCallable;
					if (UNEXPECTED(!isInstance(typeToDescribe, pt_ce_callable_type, isCallable))) return zv::Val();
					if (!isCallable) continue;
					int common = pt_type_call_is_true(Z_OBJ_P(typeToDescribe), PT_LC("iscommoncallable"), 0, NULL);
					if (UNEXPECTED(common < 0)) return zv::Val();
					if (common != 1) continue;
					smart_str described = {NULL, 0};
					smart_str_appendl(&described, "callable-", 9);
					smart_str_append(&described, s);
					smart_str_0(&described);
					describedTypes[k] = zv::Val::adoptString(described.s);
					typesToDescribe[j] = nullptr;
					folded = true;
					break;
				}
				if (folded) continue;
			}
			if (skipped) continue;
			describedTypes[k] = describeOf(type, level);
			if (UNEXPECTED(describedTypes[k].isUndef())) return zv::Val();
		}

		for (uint32_t k = 0; k < count; k++) {
			if (typesToDescribe[k] == nullptr) continue;
			describedTypes[k] = describeOf(typesToDescribe[k], level);
			if (UNEXPECTED(describedTypes[k].isUndef())) return zv::Val();
		}

		std::vector<zv::Val> ordered;
		for (uint32_t k = 0; k < count; k++) {
			if (!describedTypes[k].isUndef()) {
				ordered.push_back(std::move(describedTypes[k]));
			}
		}
		return joinAmpersand(ordered);
	}

	/* the intersectTypes()/intersectResults() families, one member call
	 * each */
	zv::Val getTemplateType(zval *ancestorClassName, zval *templateTypeName) const
	{
		zv::Args args{ancestorClassName, templateTypeName};
		return intersectCall(PT_LC("gettemplatetype"), 2, args);
	}

	zend_long isObject() const { return intersectResultsCall(PT_LC("isobject"), 0, NULL); }
	zv::Val getClassStringType() const { return intersectCall(PT_LC("getclassstringtype"), 0, NULL); }
	zend_long isEnum() const { return intersectResultsCall(PT_LC("isenum"), 0, NULL); }
	zend_long canAccessProperties() const { return intersectResultsCall(PT_LC("canaccessproperties"), 0, NULL); }
	zend_long hasProperty(zval *propertyName) const { return intersectResultsCall(PT_LC("hasproperty"), 1, propertyName); }

	/* getUnresolved*Prototype(): the prototypes of the members having the
	 * member, fetched on $this; none throws the Missing*FromReflectionException,
	 * one is returned as is, more are combined; UNDEF = pending exception */
	zv::Val unresolvedPrototype(const char *hasLcname, size_t hasLen, const char *prototypeLcname, size_t prototypeLen, const char *withLcname, size_t withLen, int exceptionClass, int combinedClass, bool isMethod, zval *name, zval *scope) const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr prototypes = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zend_object *type = entry.value().deref().asObject();
			zend_long has = pt_type_call_trinary(type, hasLcname, hasLen, 1, name);
			if (UNEXPECTED(has < 0)) return zv::Val();
			if (has != PT_TRI_YES) continue;
			zv::Args args{name, scope};
			zv::Val prototype = callType(type, prototypeLcname, prototypeLen, 2, args);
			if (UNEXPECTED(prototype.isUndef())) return zv::Val();
			zv::Val onType = pt_type_call(Z_OBJ_P(prototype.raw()), withLcname, withLen, 1, &selfZv);
			if (UNEXPECTED(onType.isUndef())) return zv::Val();
			prototypes.push(std::move(onType));
		}
		uint32_t found = zend_hash_num_elements(prototypes.table());
		if (found == 0) {
			zv::Val typeOnly = verbosityLevel(PT_VERBOSITY_LEVEL_TYPE_ONLY);
			if (UNEXPECTED(typeOnly.isUndef())) return zv::Val();
			zv::Val description = thisDescribe(typeOnly.raw());
			if (UNEXPECTED(description.isUndef())) return zv::Val();
			zv::Args args{description.raw(), name};
			throwMapped(exceptionClass, 2, args);
			return zv::Val();
		}
		if (found == 1) return zv::Val::copyOf(prototypes.arrRef().findIndex(0));
		if (isMethod) {
			zv::Args args{name, prototypes.raw()};
			return pt_type_new(combinedClass, 2, args);
		}
		return pt_type_new(combinedClass, 1, prototypes.raw());
	}

	zv::Val getUnresolvedPropertyPrototype(zval *propertyName, zval *scope) const
	{
		return unresolvedPrototype(PT_LC("hasproperty"), PT_LC("getunresolvedpropertyprototype"), PT_LC("withfechedontype"), PT_CLASS_MISSING_PROPERTY_FROM_REFLECTION_EXCEPTION, PT_CLASS_INTERSECTION_TYPE_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION, false, propertyName, scope);
	}

	zend_long hasInstanceProperty(zval *propertyName) const { return intersectResultsCall(PT_LC("hasinstanceproperty"), 1, propertyName); }

	zv::Val getUnresolvedInstancePropertyPrototype(zval *propertyName, zval *scope) const
	{
		return unresolvedPrototype(PT_LC("hasinstanceproperty"), PT_LC("getunresolvedinstancepropertyprototype"), PT_LC("withfechedontype"), PT_CLASS_MISSING_PROPERTY_FROM_REFLECTION_EXCEPTION, PT_CLASS_INTERSECTION_TYPE_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION, false, propertyName, scope);
	}

	zend_long hasStaticProperty(zval *propertyName) const { return intersectResultsCall(PT_LC("hasstaticproperty"), 1, propertyName); }

	zv::Val getUnresolvedStaticPropertyPrototype(zval *propertyName, zval *scope) const
	{
		return unresolvedPrototype(PT_LC("hasstaticproperty"), PT_LC("getunresolvedstaticpropertyprototype"), PT_LC("withfechedontype"), PT_CLASS_MISSING_PROPERTY_FROM_REFLECTION_EXCEPTION, PT_CLASS_INTERSECTION_TYPE_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION, false, propertyName, scope);
	}

	zend_long canCallMethods() const { return intersectResultsCall(PT_LC("cancallmethods"), 0, NULL); }
	zend_long hasMethod(zval *methodName) const { return intersectResultsCall(PT_LC("hasmethod"), 1, methodName); }

	zv::Val getUnresolvedMethodPrototype(zval *methodName, zval *scope) const
	{
		return unresolvedPrototype(PT_LC("hasmethod"), PT_LC("getunresolvedmethodprototype"), PT_LC("withcalledontype"), PT_CLASS_MISSING_METHOD_FROM_REFLECTION_EXCEPTION, PT_CLASS_INTERSECTION_TYPE_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION, true, methodName, scope);
	}

	zend_long canAccessConstants() const { return intersectResultsCall(PT_LC("canaccessconstants"), 0, NULL); }
	zend_long hasConstant(zval *constantName) const { return intersectResultsCall(PT_LC("hasconstant"), 1, constantName); }

	/* the constant of the first member having it; none throws; UNDEF =
	 * pending exception */
	zv::Val getConstant(zval *constantName) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zend_object *type = entry.value().deref().asObject();
			zend_long has = pt_type_call_trinary(type, PT_LC("hasconstant"), 1, constantName);
			if (UNEXPECTED(has < 0)) return zv::Val();
			if (has == PT_TRI_YES) return pt_type_call(type, PT_LC("getconstant"), 1, constantName);
		}
		zv::Val typeOnly = verbosityLevel(PT_VERBOSITY_LEVEL_TYPE_ONLY);
		if (UNEXPECTED(typeOnly.isUndef())) return zv::Val();
		zv::Val description = thisDescribe(typeOnly.raw());
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		zv::Args args{description.raw(), constantName};
		throwMapped(PT_CLASS_MISSING_CONSTANT_FROM_REFLECTION_EXCEPTION, 2, args);
		return zv::Val();
	}

	zend_long isIterable() const { return intersectResultsCall(PT_LC("isiterable"), 0, NULL); }

	/* yes for a callable array, else the memoized answer over the members
	 * that may be iterable; -1 = pending exception */
	[[nodiscard]] zend_long isIterableAtLeastOnce() const
	{
		int callableArray = isCallableArray();
		if (UNEXPECTED(callableArray < 0)) return -1;
		if (callableArray == 1) return PT_TRI_YES;
		zval *slot = OBJ_PROP_NUM(self, slots::isIterableAtLeastOnce);
		if (Z_TYPE_P(slot) == IS_OBJECT) return pt_type_trinary_value(slot);
		zend_long value = intersectResults([](zval *type) { return pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isiterableatleastonce"), 0, NULL); }, [](zval *type, bool &keep) {
			zend_long iterable = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isiterable"), 0, NULL);
			if (UNEXPECTED(iterable < 0)) return false;
			keep = iterable != PT_TRI_NO;
			return true;
		});
		return memoize(slots::isIterableAtLeastOnce, value);
	}

	/* 2 for a callable array, else the members' sizes intersected with
	 * int<count of the known offsets, max>; UNDEF = pending exception */
	zv::Val getArraySize() const
	{
		int callableArray = isCallableArray();
		if (UNEXPECTED(callableArray < 0)) return zv::Val();
		if (callableArray == 1) return constantInteger(2);
		zv::Val arraySize = intersectCall(PT_LC("getarraysize"), 0, NULL);
		if (UNEXPECTED(arraySize.isUndef())) return zv::Val();
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr knownOffsets = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zval *type = entry.value().deref().raw();
			if (!isOffsetAccessory(type)) continue;
			zv::Val offsetType = offsetTypeOf(type);
			if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
			zv::Val value = pt_type_call(Z_OBJ_P(offsetType.raw()), PT_LC("getvalue"), 0, NULL);
			if (UNEXPECTED(value.isUndef())) return zv::Val();
			if (UNEXPECTED(!setKey(knownOffsets, value.raw()))) return zv::Val();
		}
		zend_long isList = thisTrinary(PT_LC("islist"), &IntersectionType::isList);
		if (UNEXPECTED(isList < 0)) return zv::Val();
		if (isList == PT_TRI_YES) {
			zend_long nonEmpty = thisTrinary(PT_LC("isiterableatleastonce"), &IntersectionType::isIterableAtLeastOnce);
			if (UNEXPECTED(nonEmpty < 0)) return zv::Val();
			if (nonEmpty == PT_TRI_YES) {
				zval zero;
				ZVAL_LONG(&zero, 0);
				if (UNEXPECTED(!setKey(knownOffsets, &zero))) return zv::Val();
			}
		}
		uint32_t known = zend_hash_num_elements(knownOffsets.table());
		if (known != 0) {
			zv::Val range = integerRange(NullableLong::of((zend_long) known), NullableLong::null());
			if (UNEXPECTED(range.isUndef())) return zv::Val();
			return combinator2(PT_LC("intersect"), arraySize.raw(), range.raw());
		}
		return arraySize;
	}

	/* 0|1 for a callable array, else the members' key types intersected */
	zv::Val getIterableKeyType() const
	{
		int callableArray = isCallableArray();
		if (UNEXPECTED(callableArray < 0)) return zv::Val();
		if (callableArray == 1) return callableArrayOffsets();
		return intersectCall(PT_LC("getiterablekeytype"), 0, NULL);
	}

	zv::Val getFirstIterableKeyType() const { return intersectCall(PT_LC("getiterablekeytype"), 0, NULL); }

	/* the members' value types intersected, narrowed to object|non-falsy-string
	 * for a callable array */
	zv::Val getIterableValueType() const
	{
		zv::Val result = intersectCall(PT_LC("getiterablevaluetype"), 0, NULL);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		int callableArray = isCallableArray();
		if (UNEXPECTED(callableArray < 0)) return zv::Val();
		if (callableArray == 1) {
			zv::Val narrowed = unionOf2(pt_type_new_object_without_class_type(), nonFalsyString());
			if (UNEXPECTED(narrowed.isUndef())) return zv::Val();
			return combinator2(PT_LC("intersect"), result.raw(), narrowed.raw());
		}
		return result;
	}

	zv::Val getFirstIterableValueType() const { return intersectCall(PT_LC("getiterablevaluetype"), 0, NULL); }

	/* the memoized is*() family */
	zend_long isArray() const { return memoizedCall(slots::isArray, PT_LC("isarray")); }

	zend_long isConstantArray() const
	{
		int callableArray = isCallableArray();
		if (UNEXPECTED(callableArray < 0)) return -1;
		if (callableArray == 1) return PT_TRI_YES;
		return memoizedCall(slots::isConstantArray, PT_LC("isconstantarray"));
	}

	zend_long isOversizedArray() const { return memoizedCall(slots::isOversizedArray, PT_LC("isoversizedarray")); }

	zend_long isList() const
	{
		int callableArray = isCallableArray();
		if (UNEXPECTED(callableArray < 0)) return -1;
		if (callableArray == 1) return PT_TRI_YES;
		return memoizedCall(slots::isList, PT_LC("islist"));
	}

	zend_long isString() const { return memoizedCall(slots::isString, PT_LC("isstring")); }
	zend_long isNumericString() const { return intersectResultsCall(PT_LC("isnumericstring"), 0, NULL); }
	zend_long isDecimalIntegerString() const { return intersectResultsCall(PT_LC("isdecimalintegerstring"), 0, NULL); }

	/* yes for a callable string, else the members' answers */
	zend_long isNonEmptyString() const
	{
		zend_long callable = thisTrinary(PT_LC("iscallable"), &IntersectionType::isCallable);
		if (UNEXPECTED(callable < 0)) return -1;
		if (callable == PT_TRI_YES) {
			zend_long string = thisTrinary(PT_LC("isstring"), &IntersectionType::isString);
			if (UNEXPECTED(string < 0)) return -1;
			if (string == PT_TRI_YES) return PT_TRI_YES;
		}
		return intersectResultsCall(PT_LC("isnonemptystring"), 0, NULL);
	}

	zend_long isNonFalsyString() const { return intersectResultsCall(PT_LC("isnonfalsystring"), 0, NULL); }
	zend_long isLiteralString() const { return intersectResultsCall(PT_LC("isliteralstring"), 0, NULL); }
	zend_long isLowercaseString() const { return intersectResultsCall(PT_LC("islowercasestring"), 0, NULL); }
	zend_long isUppercaseString() const { return intersectResultsCall(PT_LC("isuppercasestring"), 0, NULL); }
	zend_long isClassString() const { return intersectResultsCall(PT_LC("isclassstring"), 0, NULL); }
	zv::Val getClassStringObjectType() const { return intersectCall(PT_LC("getclassstringobjecttype"), 0, NULL); }
	zv::Val getObjectTypeOrClassStringObjectType() const { return intersectCall(PT_LC("getobjecttypeorclassstringobjecttype"), 0, NULL); }
	zend_long isScalar() const { return intersectResultsCall(PT_LC("isscalar"), 0, NULL); }

	/* intersectResults(looseCompare()->toTrinaryLogic())->toBooleanType() */
	zv::Val looseCompare(zval *type, zval *phpVersion) const
	{
		zv::Args args{type, phpVersion};
		zend_long value = intersectResults([&](zval *innerType) -> zend_long {
			zv::Val boolean = pt_type_call(Z_OBJ_P(innerType), PT_LC("loosecompare"), 2, args);
			if (UNEXPECTED(boolean.isUndef())) return -1;
			if (UNEXPECTED(!zv::Ref(boolean.raw()).isObject())) {
				zend_type_error("phpstan_turbo: looseCompare() must return %s", ZSTR_VAL(pt_ce_boolean_type->name));
				return -1;
			}
			return pt_type_call_trinary(Z_OBJ_P(boolean.raw()), PT_LC("totrinarylogic"), 0, NULL);
		});
		return booleanTypeOf(value);
	}

	zend_long isOffsetAccessible() const { return memoizedCall(slots::isOffsetAccessible, PT_LC("isoffsetaccessible")); }
	zend_long isOffsetAccessLegal() const { return intersectResultsCall(PT_LC("isoffsetaccesslegal"), 0, NULL); }

	/* the per-offset cache in front of doHasOffsetValueType(), keyed by the
	 * offset's cache description; -1 = pending exception */
	[[nodiscard]] zend_long hasOffsetValueType(zval *offsetType) const
	{
		zv::Val cacheKey = cacheKeyOf(offsetType);
		if (UNEXPECTED(cacheKey.isUndef())) return -1;
		zval *cache = OBJ_PROP_NUM(self, slots::cachedHasOffsetValueType);
		if (Z_TYPE_P(cache) == IS_ARRAY) {
			zval *cached = zend_symtable_find(Z_ARRVAL_P(cache), zv::Ref(cacheKey.raw()).asString());
			if (cached != NULL && Z_TYPE_P(cached) == IS_OBJECT) return pt_type_trinary_value(cached);
		}
		zend_long value = doHasOffsetValueType(offsetType);
		if (UNEXPECTED(value < 0)) return -1;
		cache = OBJ_PROP_NUM(self, slots::cachedHasOffsetValueType);
		if (Z_TYPE_P(cache) != IS_ARRAY) {
			zv::Ref(cache).assign(zv::Val(zv::Arr::create(4)));
		}
		SEPARATE_ARRAY(cache);
		zval trinary = pt_type_trinary(value).take();
		zend_symtable_update(Z_ARRVAL_P(cache), zv::Ref(cacheKey.raw()).asString(), &trinary);
		return value;
	}

	/* a callable array by its two offsets, a list by the known offsets
	 * (negative ones never, the sized/known ones surely), else the members'
	 * answers; -1 = pending exception */
	[[nodiscard]] zend_long doHasOffsetValueType(zval *offsetType) const
	{
		int callableArray = isCallableArray();
		if (UNEXPECTED(callableArray < 0)) return -1;
		if (callableArray == 1) {
			zv::Val arrayKeyOffsetType = callType(Z_OBJ_P(offsetType), PT_LC("toarraykey"), 0, NULL);
			if (UNEXPECTED(arrayKeyOffsetType.isUndef())) return -1;
			zv::Val callableArrayOffsetType = callableArrayOffsets();
			if (UNEXPECTED(callableArrayOffsetType.isUndef())) return -1;
			return pt_type_call_result_trinary(Z_OBJ_P(callableArrayOffsetType.raw()), PT_LC("issupertypeof"), 1, arrayKeyOffsetType.raw());
		}

		zend_long isList = thisTrinary(PT_LC("islist"), &IntersectionType::isList);
		if (UNEXPECTED(isList < 0)) return -1;
		if (isList == PT_TRI_YES) {
			zv::Val arrayKeyOffsetType = callType(Z_OBJ_P(offsetType), PT_LC("toarraykey"), 0, NULL);
			if (UNEXPECTED(arrayKeyOffsetType.isUndef())) return -1;
			zv::Val negative = integerRange(NullableLong::null(), NullableLong::of(-1));
			if (UNEXPECTED(negative.isUndef())) return -1;
			zend_long negativeSuper = pt_type_call_result_trinary(Z_OBJ_P(negative.raw()), PT_LC("issupertypeof"), 1, arrayKeyOffsetType.raw());
			if (UNEXPECTED(negativeSuper < 0)) return -1;
			if (negativeSuper == PT_TRI_YES) return PT_TRI_NO;

			zv::Val size = thisType(PT_LC("getarraysize"), &IntersectionType::getArraySize);
			if (UNEXPECTED(size.isUndef())) return -1;
			zv::Val knownOffsets;
			if (zv::Ref(size.raw()).instanceOf(pt_ce_integer_range_type)) {
				NullableLong min, max;
				if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(size.raw()), min, max))) return -1;
				if (!min.isNull) {
					knownOffsets = integerRange(NullableLong::of(0), NullableLong::of(min.value - 1));
					if (UNEXPECTED(knownOffsets.isUndef())) return -1;
				}
			}
			if (knownOffsets.isUndef() && zv::Ref(size.raw()).instanceOf(pt_ce_constant_integer_type)) {
				zend_long value;
				if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(size.raw()), value))) return -1;
				knownOffsets = integerRange(NullableLong::of(0), NullableLong::of(value - 1));
				if (UNEXPECTED(knownOffsets.isUndef())) return -1;
			}
			if (knownOffsets.isUndef()) {
				zend_long nonEmpty = thisTrinary(PT_LC("isiterableatleastonce"), &IntersectionType::isIterableAtLeastOnce);
				if (UNEXPECTED(nonEmpty < 0)) return -1;
				if (nonEmpty == PT_TRI_YES) {
					knownOffsets = constantInteger(0);
					if (UNEXPECTED(knownOffsets.isUndef())) return -1;
				}
			}
			if (!knownOffsets.isUndef()) {
				zend_long known = pt_type_call_result_trinary(Z_OBJ_P(knownOffsets.raw()), PT_LC("issupertypeof"), 1, arrayKeyOffsetType.raw());
				if (UNEXPECTED(known < 0)) return -1;
				if (known == PT_TRI_YES) return PT_TRI_YES;
			}

			zval *types = this->types();
			if (UNEXPECTED(types == NULL)) return -1;
			zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
			for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
				zval *type = entry.value().deref().raw();
				if (!isOffsetAccessory(type)) continue;
				zv::Val offset = offsetTypeOf(type);
				if (UNEXPECTED(offset.isUndef())) return -1;
				zv::Val values = pt_type_call_array(Z_OBJ_P(offset.raw()), PT_LC("getconstantscalarvalues"), 0, NULL);
				if (UNEXPECTED(values.isUndef())) return -1;
				for (zv::ArrayEntry valueEntry : zv::ArrRef(values.raw())) {
					zv::Ref value = valueEntry.value().deref();
					if (!value.isLong()) continue;
					zv::Val range = integerRange(NullableLong::of(0), NullableLong::of(value.asLong()));
					if (UNEXPECTED(range.isUndef())) return -1;
					zend_long covered = pt_type_call_result_trinary(Z_OBJ_P(range.raw()), PT_LC("issupertypeof"), 1, arrayKeyOffsetType.raw());
					if (UNEXPECTED(covered < 0)) return -1;
					if (covered == PT_TRI_YES) return PT_TRI_YES;
				}
			}
		}

		return intersectResultsCall(PT_LC("hasoffsetvaluetype"), 1, offsetType);
	}

	/* the per-offset cache in front of doGetOffsetValueType() */
	zv::Val getOffsetValueType(zval *offsetType) const
	{
		zv::Val cacheKey = cacheKeyOf(offsetType);
		if (UNEXPECTED(cacheKey.isUndef())) return zv::Val();
		zval *cache = OBJ_PROP_NUM(self, slots::cachedGetOffsetValueType);
		if (Z_TYPE_P(cache) == IS_ARRAY) {
			zval *cached = zend_symtable_find(Z_ARRVAL_P(cache), zv::Ref(cacheKey.raw()).asString());
			if (cached != NULL && Z_TYPE_P(cached) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(cached));
		}
		zv::Val result = doGetOffsetValueType(offsetType);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		cache = OBJ_PROP_NUM(self, slots::cachedGetOffsetValueType);
		if (Z_TYPE_P(cache) != IS_ARRAY) {
			zv::Ref(cache).assign(zv::Val(zv::Arr::create(4)));
		}
		SEPARATE_ARRAY(cache);
		zval copy;
		ZVAL_COPY(&copy, result.raw());
		zend_symtable_update(Z_ARRVAL_P(cache), zv::Ref(cacheKey.raw()).asString(), &copy);
		return result;
	}

	/* the members' offset value types intersected: benevolent for an
	 * oversized array, narrowed by the offset for a callable array */
	zv::Val doGetOffsetValueType(zval *offsetType) const
	{
		zv::Val result = intersectCall(PT_LC("getoffsetvaluetype"), 1, offsetType);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zend_long oversized = thisTrinary(PT_LC("isoversizedarray"), &IntersectionType::isOversizedArray);
		if (UNEXPECTED(oversized < 0)) return zv::Val();
		if (oversized == PT_TRI_YES) return pt_union_to_benevolent(result.raw());
		int callableArray = isCallableArray();
		if (UNEXPECTED(callableArray < 0)) return zv::Val();
		if (callableArray == 1) {
			zv::Val arrayKeyOffsetType = callType(Z_OBJ_P(offsetType), PT_LC("toarraykey"), 0, NULL);
			if (UNEXPECTED(arrayKeyOffsetType.isUndef())) return zv::Val();
			zv::Val narrowedType;
			zend_long isZero = constantIsSuperTypeOf(0, arrayKeyOffsetType.raw());
			if (UNEXPECTED(isZero < 0)) return zv::Val();
			if (isZero == PT_TRI_YES) {
				narrowedType = unionOf2(classStringType(), pt_type_new_object_without_class_type());
			} else {
				zend_long isOne = constantIsSuperTypeOf(1, arrayKeyOffsetType.raw());
				if (UNEXPECTED(isOne < 0)) return zv::Val();
				if (isOne == PT_TRI_YES) {
					narrowedType = nonFalsyString();
				} else {
					narrowedType = unionOf2(nonFalsyString(), pt_type_new_object_without_class_type());
				}
			}
			if (UNEXPECTED(narrowedType.isUndef())) return zv::Val();
			return combinator2(PT_LC("intersect"), result.raw(), narrowedType.raw());
		}
		return result;
	}

	/* setOffsetValueType(): an oversized array keeps its shape (a known key
	 * widens the array instead of adding an offset accessory), a list stays
	 * a list when the offset is known to be in range; UNDEF = pending
	 * exception */
	zv::Val setOffsetValueType(zval *offsetType, zval *valueType, bool unionValues) const
	{
		zval args[3];
		if (offsetType == NULL) {
			ZVAL_NULL(&args[0]);
		} else {
			ZVAL_COPY_VALUE(&args[0], offsetType);
		}
		ZVAL_COPY_VALUE(&args[1], valueType);
		ZVAL_BOOL(&args[2], unionValues);

		zend_long oversized = thisTrinary(PT_LC("isoversizedarray"), &IntersectionType::isOversizedArray);
		if (UNEXPECTED(oversized < 0)) return zv::Val();
		if (oversized == PT_TRI_YES) {
			return intersectTypes([&](zval *type) -> zv::Val {
				/* avoid new HasOffsetValueType being intersected with oversized array */
				if (!zv::Ref(type).instanceOf(pt_ce_array_type)) return callType(Z_OBJ_P(type), PT_LC("setoffsetvaluetype"), 3, args);
				if (offsetType == NULL || (!zv::Ref(offsetType).instanceOf(pt_ce_constant_string_type) && !zv::Ref(offsetType).instanceOf(pt_ce_constant_integer_type))) {
					return callType(Z_OBJ_P(type), PT_LC("setoffsetvaluetype"), 3, args);
				}
				zv::Val keyType = pt_array_type_get_key_type(Z_OBJ_P(type));
				if (UNEXPECTED(keyType.isUndef())) return zv::Val();
				zend_long covers = pt_type_call_result_trinary(Z_OBJ_P(offsetType), PT_LC("issupertypeof"), 1, keyType.raw());
				if (UNEXPECTED(covers < 0)) return zv::Val();
				if (covers != PT_TRI_YES) return callType(Z_OBJ_P(type), PT_LC("setoffsetvaluetype"), 3, args);
				zv::Val itemType = pt_array_type_get_item_type(Z_OBJ_P(type));
				if (UNEXPECTED(itemType.isUndef())) return zv::Val();
				zv::Val newKeyType = combinator2(PT_LC("union"), keyType.raw(), offsetType);
				if (UNEXPECTED(newKeyType.isUndef())) return zv::Val();
				zv::Val newItemType = combinator2(PT_LC("union"), itemType.raw(), valueType);
				if (UNEXPECTED(newItemType.isUndef())) return zv::Val();
				zval arrayRaw;
				if (UNEXPECTED(!pt_array_type_new(&arrayRaw, newKeyType.raw(), newItemType.raw()))) return zv::Val();
				zval nonEmptyRaw;
				if (UNEXPECTED(!pt_non_empty_array_type_new(&nonEmptyRaw))) {
					zval_ptr_dtor(&arrayRaw);
					return zv::Val();
				}
				zv::Arr types = zv::Arr::create(2);
				types.push(zv::Val::adopt(arrayRaw));
				types.push(zv::Val::adopt(nonEmptyRaw));
				return pt_intersection_of(std::move(types));
			});
		}

		zv::Val result = intersectCall(PT_LC("setoffsetvaluetype"), 3, args);
		if (UNEXPECTED(result.isUndef())) return zv::Val();

		if (offsetType != NULL) {
			zend_long isList = thisTrinary(PT_LC("islist"), &IntersectionType::isList);
			if (UNEXPECTED(isList < 0)) return zv::Val();
			if (isList == PT_TRI_YES) {
				zend_long resultIsList = pt_type_call_trinary(Z_OBJ_P(result.raw()), PT_LC("islist"), 0, NULL);
				if (UNEXPECTED(resultIsList < 0)) return zv::Val();
				if (resultIsList != PT_TRI_YES) {
					bool keepList = false;
					zend_long nonEmpty = thisTrinary(PT_LC("isiterableatleastonce"), &IntersectionType::isIterableAtLeastOnce);
					if (UNEXPECTED(nonEmpty < 0)) return zv::Val();
					if (nonEmpty == PT_TRI_YES) {
						zend_long isOne = constantIsSuperTypeOf(1, offsetType);
						if (UNEXPECTED(isOne < 0)) return zv::Val();
						keepList = isOne == PT_TRI_YES;
					}
					if (keepList) {
						result = intersectWithNew(result.raw(), pt_accessory_array_list_type_new);
						if (UNEXPECTED(result.isUndef())) return zv::Val();
					} else {
						zval *types = this->types();
						if (UNEXPECTED(types == NULL)) return zv::Val();
						zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
						bool done = false;
						for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
							zval *type = entry.value().deref().raw();
							if (!isOffsetAccessory(type)) continue;
							zv::Val offset = offsetTypeOf(type);
							if (UNEXPECTED(offset.isUndef())) return zv::Val();
							zv::Val values = pt_type_call_array(Z_OBJ_P(offset.raw()), PT_LC("getconstantscalarvalues"), 0, NULL);
							if (UNEXPECTED(values.isUndef())) return zv::Val();
							for (zv::ArrayEntry valueEntry : zv::ArrRef(values.raw())) {
								zv::Ref value = valueEntry.value().deref();
								if (!value.isLong()) continue;
								zv::Val range = integerRange(NullableLong::of(0), NullableLong::of(value.asLong() + 1));
								if (UNEXPECTED(range.isUndef())) return zv::Val();
								zend_long covered = pt_type_call_result_trinary(Z_OBJ_P(range.raw()), PT_LC("issupertypeof"), 1, offsetType);
								if (UNEXPECTED(covered < 0)) return zv::Val();
								if (covered == PT_TRI_YES) {
									result = intersectWithNew(result.raw(), pt_accessory_array_list_type_new);
									if (UNEXPECTED(result.isUndef())) return zv::Val();
									done = true;
									break;
								}
							}
							if (done) break;
						}
					}
				}
			}
		}

		zend_long isList = thisTrinary(PT_LC("islist"), &IntersectionType::isList);
		if (UNEXPECTED(isList < 0)) return zv::Val();
		if (isList == PT_TRI_YES && offsetType != NULL) {
			zv::Val arrayKey = callType(Z_OBJ_P(offsetType), PT_LC("toarraykey"), 0, NULL);
			if (UNEXPECTED(arrayKey.isUndef())) return zv::Val();
			zend_long isInteger = pt_type_call_trinary(Z_OBJ_P(arrayKey.raw()), PT_LC("isinteger"), 0, NULL);
			if (UNEXPECTED(isInteger < 0)) return zv::Val();
			if (isInteger == PT_TRI_YES) {
				zv::Val iterableValueType = thisType(PT_LC("getiterablevaluetype"), &IntersectionType::getIterableValueType);
				if (UNEXPECTED(iterableValueType.isUndef())) return zv::Val();
				zend_long valueIsArray = pt_type_call_trinary(Z_OBJ_P(iterableValueType.raw()), PT_LC("isarray"), 0, NULL);
				if (UNEXPECTED(valueIsArray < 0)) return zv::Val();
				if (valueIsArray == PT_TRI_YES) {
					result = intersectWithNew(result.raw(), pt_accessory_array_list_type_new);
					if (UNEXPECTED(result.isUndef())) return zv::Val();
				}
			}
		}

		return result;
	}

	zv::Val setExistingOffsetValueType(zval *offsetType, zval *valueType) const
	{
		zv::Args args{offsetType, valueType};
		return intersectCall(PT_LC("setexistingoffsetvaluetype"), 2, args);
	}

	zv::Val unsetOffset(zval *offsetType) const { return intersectCall(PT_LC("unsetoffset"), 1, offsetType); }

	zv::Val getKeysArrayFiltered(zval *filterValueType, zval *strict) const
	{
		zv::Args args{filterValueType, strict};
		return intersectCall(PT_LC("getkeysarrayfiltered"), 2, args);
	}

	zv::Val getKeysArray() const { return intersectCall(PT_LC("getkeysarray"), 0, NULL); }

	/* $this for a list, else the members' value arrays intersected */
	zv::Val getValuesArray() const
	{
		zend_long isList = thisTrinary(PT_LC("islist"), &IntersectionType::isList);
		if (UNEXPECTED(isList < 0)) return zv::Val();
		if (isList == PT_TRI_YES) return thisValue();
		return intersectCall(PT_LC("getvaluesarray"), 0, NULL);
	}

	zv::Val chunkArray(zval *lengthType, zval *preserveKeys) const
	{
		zv::Args args{lengthType, preserveKeys};
		return intersectCall(PT_LC("chunkarray"), 2, args);
	}

	zv::Val fillKeysArray(zval *valueType) const { return intersectCall(PT_LC("fillkeysarray"), 1, valueType); }
	zv::Val flipArray() const { return intersectCall(PT_LC("fliparray"), 0, NULL); }
	zv::Val intersectKeyArray(zval *otherArraysType) const { return intersectCallPreservingTemplates(PT_LC("intersectkeyarray"), 1, otherArraysType); }

	/* a list's known offsets shifted down by one on pop; UNDEF = pending
	 * exception */
	zv::Val popArray() const
	{
		zend_long isList = thisTrinary(PT_LC("islist"), &IntersectionType::isList);
		if (UNEXPECTED(isList < 0)) return zv::Val();
		if (isList == PT_TRI_YES) return listMembers(PT_LC("poparray"), false);
		return intersectCallPreservingTemplates(PT_LC("poparray"), 0, NULL);
	}

	zv::Val reverseArray(zval *preserveKeys) const { return intersectCallPreservingTemplates(PT_LC("reversearray"), 1, preserveKeys); }

	zv::Val searchArray(zval *needleType, zval *strict) const
	{
		zval args[2];
		ZVAL_COPY_VALUE(&args[0], needleType);
		if (strict == NULL) {
			ZVAL_NULL(&args[1]);
		} else {
			ZVAL_COPY_VALUE(&args[1], strict);
		}
		return intersectCall(PT_LC("searcharray"), 2, args);
	}

	/* a list's known offsets moved down by one on shift */
	zv::Val shiftArray() const
	{
		zend_long isList = thisTrinary(PT_LC("islist"), &IntersectionType::isList);
		if (UNEXPECTED(isList < 0)) return zv::Val();
		if (isList == PT_TRI_YES) return listMembers(PT_LC("shiftarray"), true);
		return intersectCallPreservingTemplates(PT_LC("shiftarray"), 0, NULL);
	}

	zv::Val shuffleArray() const
	{
		zend_long isList = thisTrinary(PT_LC("islist"), &IntersectionType::isList);
		if (UNEXPECTED(isList < 0)) return zv::Val();
		if (isList == PT_TRI_YES) return intersectCallPreservingTemplates(PT_LC("shufflearray"), 0, NULL);
		return intersectCall(PT_LC("shufflearray"), 0, NULL);
	}

	/* the slice of a non-empty list from 0 with a positive length stays
	 * non-empty */
	zv::Val sliceArray(zval *offsetType, zval *lengthType, zval *preserveKeys) const
	{
		zv::Args args{offsetType, lengthType, preserveKeys};
		zv::Val result = intersectCallPreservingTemplates(PT_LC("slicearray"), 3, args);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zend_long isList = thisTrinary(PT_LC("islist"), &IntersectionType::isList);
		if (UNEXPECTED(isList < 0)) return zv::Val();
		if (isList != PT_TRI_YES) return result;
		zend_long nonEmpty = thisTrinary(PT_LC("isiterableatleastonce"), &IntersectionType::isIterableAtLeastOnce);
		if (UNEXPECTED(nonEmpty < 0)) return zv::Val();
		if (nonEmpty != PT_TRI_YES) return result;
		zend_long fromZero = constantIsSuperTypeOf(0, offsetType);
		if (UNEXPECTED(fromZero < 0)) return zv::Val();
		if (fromZero != PT_TRI_YES) return result;
		zv::Val positive = integerRange(NullableLong::of(1), NullableLong::null());
		if (UNEXPECTED(positive.isUndef())) return zv::Val();
		zend_long positiveLength = pt_type_call_result_trinary(Z_OBJ_P(positive.raw()), PT_LC("issupertypeof"), 1, lengthType);
		if (UNEXPECTED(positiveLength < 0)) return zv::Val();
		if (positiveLength != PT_TRI_YES) return result;
		return intersectWithNew(result.raw(), pt_non_empty_array_type_new);
	}

	zv::Val spliceArray(zval *offsetType, zval *lengthType, zval *replacementType) const
	{
		zv::Args args{offsetType, lengthType, replacementType};
		return intersectCallPreservingTemplates(PT_LC("splicearray"), 3, args);
	}

	zv::Val truncateListToSize(zval *sizeType) const { return intersectCallPreservingTemplates(PT_LC("truncatelisttosize"), 1, sizeType); }
	zv::Val makeListMaybe() const { return intersectCall(PT_LC("makelistmaybe"), 0, NULL); }
	zv::Val mapValueType(zval *cb) const { return intersectCallPreservingTemplates(PT_LC("mapvaluetype"), 1, cb); }
	zv::Val mapKeyType(zval *cb) const { return intersectCallPreservingTemplates(PT_LC("mapkeytype"), 1, cb); }
	zv::Val makeAllArrayKeysOptional() const { return intersectCall(PT_LC("makeallarraykeysoptional"), 0, NULL); }
	zv::Val changeKeyCaseArray(zval *caseArg) const { return intersectCallPreservingTemplates(PT_LC("changekeycasearray"), 1, caseArg); }
	zv::Val filterArrayRemovingFalsey() const { return intersectCallPreservingTemplates(PT_LC("filterarrayremovingfalsey"), 0, NULL); }

	/* the enum cases every member has, keyed by class and case */
	zv::Val getEnumCases() const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		std::vector<zv::Val> compare;
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zv::Val cases = pt_type_call_array(entry.value().deref().asObject(), PT_LC("getenumcases"), 0, NULL);
			if (UNEXPECTED(cases.isUndef())) return zv::Val();
			zv::Arr oneType = zv::Arr::create(zv::ArrRef(cases.raw()).size());
			for (zv::ArrayEntry caseEntry : zv::ArrRef(cases.raw())) {
				zval *enumCase = caseEntry.value().deref().raw();
				zv::Val key = enumCaseKey(enumCase);
				if (UNEXPECTED(key.isUndef())) return zv::Val();
				oneType.set(zv::Ref(key.raw()).asString(), zv::Val::copyOf(zv::Ref(enumCase)));
			}
			compare.push_back(zv::Val(std::move(oneType)));
		}
		return intersectKeysOf(compare);
	}

	/* the single member answering with a case object, null when none or
	 * more do */
	zv::Val getEnumCaseObject() const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Val singleCase;
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zv::Val caseObject = pt_type_call(entry.value().deref().asObject(), PT_LC("getenumcaseobject"), 0, NULL);
			if (UNEXPECTED(caseObject.isUndef())) return zv::Val();
			if (zv::Ref(caseObject.raw()).isNull()) continue;
			if (!singleCase.isUndef()) return zv::Val::null();
			singleCase = std::move(caseObject);
		}
		if (singleCase.isUndef()) return zv::Val::null();
		return singleCase;
	}

	zend_long isCallable() const { return memoizedCall(slots::isCallable, PT_LC("iscallable")); }

	/* the combinations of the surely callable members' acceptors combined;
	 * a trivial acceptor when none is surely callable; no callable at all
	 * throws; UNDEF = pending exception */
	zv::Val getCallableParametersAcceptors(zval *scope) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr yesAcceptors = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zend_object *type = entry.value().deref().asObject();
			zend_long callable = pt_type_call_trinary(type, PT_LC("iscallable"), 0, NULL);
			if (UNEXPECTED(callable < 0)) return zv::Val();
			if (callable != PT_TRI_YES) continue;
			zv::Val acceptors = pt_type_call_array(type, PT_LC("getcallableparametersacceptors"), 1, scope);
			if (UNEXPECTED(acceptors.isUndef())) return zv::Val();
			yesAcceptors.push(std::move(acceptors));
		}
		if (zend_hash_num_elements(yesAcceptors.table()) == 0) {
			zend_long callable = thisTrinary(PT_LC("iscallable"), &IntersectionType::isCallable);
			if (UNEXPECTED(callable < 0)) return zv::Val();
			if (callable == PT_TRI_NO) {
				throwShouldNotHappen(NULL);
				return zv::Val();
			}
			zv::Val acceptor = pt_type_new(PT_CLASS_TRIVIAL_PARAMETERS_ACCEPTOR, 0, NULL);
			if (UNEXPECTED(acceptor.isUndef())) return zv::Val();
			zv::Arr acceptors = zv::Arr::create(1);
			acceptors.push(std::move(acceptor));
			return zv::Val(std::move(acceptors));
		}
		zv::Val combinations = pt_combinations_helper_combinations(yesAcceptors.raw());
		if (UNEXPECTED(combinations.isUndef())) return zv::Val();
		zv::Arr result = zv::Arr::create(zv::ArrRef(combinations.raw()).size());
		for (zv::ArrayEntry entry : zv::ArrRef(combinations.raw())) {
			zv::Val combined = pt_type_call_static(PT_CLASS_PARAMETERS_ACCEPTOR_SELECTOR, PT_LC("combineacceptors"), 1, entry.value().deref().raw());
			if (UNEXPECTED(combined.isUndef())) return zv::Val();
			bool isCallableAcceptor;
			if (UNEXPECTED(!isInstance(combined.raw(), PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR, isCallableAcceptor))) return zv::Val();
			if (UNEXPECTED(!isCallableAcceptor)) {
				throwShouldNotHappen(NULL);
				return zv::Val();
			}
			result.push(std::move(combined));
		}
		return zv::Val(std::move(result));
	}

	zend_long isCloneable() const { return intersectResultsCall(PT_LC("iscloneable"), 0, NULL); }

	zend_long isSmallerThan(zval *otherType, zval *phpVersion) const
	{
		zv::Args args{otherType, phpVersion};
		return intersectResultsCall(PT_LC("issmallerthan"), 2, args);
	}

	zend_long isSmallerThanOrEqual(zval *otherType, zval *phpVersion) const
	{
		zv::Args args{otherType, phpVersion};
		return intersectResultsCall(PT_LC("issmallerthanorequal"), 2, args);
	}

	zend_long isNull() const { return intersectResultsCall(PT_LC("isnull"), 0, NULL); }
	zend_long isConstantValue() const { return intersectResultsCall(PT_LC("isconstantvalue"), 0, NULL); }
	zend_long isConstantScalarValue() const { return memoizedCall(slots::isConstantScalarValue, PT_LC("isconstantscalarvalue")); }
	zv::Val getConstantScalarTypes() const { return concatOf(PT_LC("getconstantscalartypes"), 0, NULL); }
	zv::Val getConstantScalarValues() const { return concatOf(PT_LC("getconstantscalarvalues"), 0, NULL); }
	zend_long isTrue() const { return intersectResultsCall(PT_LC("istrue"), 0, NULL); }
	zend_long isFalse() const { return intersectResultsCall(PT_LC("isfalse"), 0, NULL); }
	zend_long isBoolean() const { return memoizedCall(slots::isBoolean, PT_LC("isboolean")); }
	zend_long isFloat() const { return memoizedCall(slots::isFloat, PT_LC("isfloat")); }
	zend_long isInteger() const { return memoizedCall(slots::isInteger, PT_LC("isinteger")); }

	/* $otherType->isSmallerThan($type, $phpVersion) per member */
	zend_long isGreaterThan(zval *otherType, zval *phpVersion) const
	{
		return intersectResults([&](zval *type) -> zend_long {
			zv::Args args{type, phpVersion};
			return pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("issmallerthan"), 2, args);
		});
	}

	zend_long isGreaterThanOrEqual(zval *otherType, zval *phpVersion) const
	{
		return intersectResults([&](zval *type) -> zend_long {
			zv::Args args{type, phpVersion};
			return pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("issmallerthanorequal"), 2, args);
		});
	}

	zv::Val getSmallerType(zval *phpVersion) const { return intersectCall(PT_LC("getsmallertype"), 1, phpVersion); }
	zv::Val getSmallerOrEqualType(zval *phpVersion) const { return intersectCall(PT_LC("getsmallerorequaltype"), 1, phpVersion); }
	zv::Val getGreaterType(zval *phpVersion) const { return intersectCall(PT_LC("getgreatertype"), 1, phpVersion); }
	zv::Val getGreaterOrEqualType(zval *phpVersion) const { return intersectCall(PT_LC("getgreaterorequaltype"), 1, phpVersion); }

	/* the members' booleans intersected, a plain BooleanType when that is
	 * no BooleanType */
	zv::Val toBoolean() const
	{
		zv::Val type = intersectCall(PT_LC("toboolean"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (!zv::Ref(type.raw()).instanceOf(pt_ce_boolean_type)) return booleanType();
		return type;
	}

	zv::Val toNumber() const { return intersectCall(PT_LC("tonumber"), 0, NULL); }
	zv::Val toBitwiseNotType() const { return intersectCall(PT_LC("tobitwisenottype"), 0, NULL); }
	zv::Val toGetClassResultType() const { return intersectCall(PT_LC("togetclassresulttype"), 0, NULL); }
	zv::Val toClassConstantType(zval *reflectionProvider) const { return intersectCall(PT_LC("toclassconstanttype"), 1, reflectionProvider); }

	/* new ClassNameToObjectTypeResult(TypeCombinator::intersect(...$types),
	 * $uncertainty) over the members' results; UNDEF = pending exception */
	zv::Val objectTypeForCheck(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr resultTypes = zv::Arr::create(zv::ArrRef(typesCopy.raw()).size());
		bool uncertainty = false;
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zv::Val result = callType(entry.value().deref().asObject(), lcname, len, argc, argv);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zv::Val resultType = readProperty(result.raw(), PT_LC("type"));
			if (UNEXPECTED(resultType.isUndef())) return zv::Val();
			resultTypes.push(std::move(resultType));
			zv::Val resultUncertainty = readProperty(result.raw(), PT_LC("uncertainty"));
			if (UNEXPECTED(resultUncertainty.isUndef())) return zv::Val();
			if (!zend_is_true(resultUncertainty.raw())) continue;
			uncertainty = true;
		}
		zv::Val intersected = combinatorIntersect(resultTypes.table());
		if (UNEXPECTED(intersected.isUndef())) return zv::Val();
		zv::Args args{intersected.raw(), uncertainty};
		return pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args);
	}

	zv::Val toObjectTypeForInstanceofCheck() const { return objectTypeForCheck(PT_LC("toobjecttypeforinstanceofcheck"), 0, NULL); }

	zv::Val toObjectTypeForIsACheck(zval *objectOrClassType, bool allowString, bool allowSameClass) const
	{
		zv::Args args{objectOrClassType, allowString, allowSameClass};
		return objectTypeForCheck(PT_LC("toobjecttypeforisacheck"), 3, args);
	}

	zv::Val toAbsoluteNumber() const { return intersectCall(PT_LC("toabsolutenumber"), 0, NULL); }
	zv::Val toString() const { return intersectCall(PT_LC("tostring"), 0, NULL); }
	zv::Val toInteger() const { return intersectCall(PT_LC("tointeger"), 0, NULL); }
	zv::Val toFloat() const { return intersectCall(PT_LC("tofloat"), 0, NULL); }
	zv::Val toArray() const { return intersectCall(PT_LC("toarray"), 0, NULL); }

	/* int for a decimal-int string, int|$this for a numeric string, $this
	 * for a string, else the members' array keys intersected */
	zv::Val toArrayKey() const
	{
		zend_long decimal = thisTrinary(PT_LC("isdecimalintegerstring"), &IntersectionType::isDecimalIntegerString);
		if (UNEXPECTED(decimal < 0)) return zv::Val();
		if (decimal == PT_TRI_YES) return integerType();
		zend_long numeric = thisTrinary(PT_LC("isnumericstring"), &IntersectionType::isNumericString);
		if (UNEXPECTED(numeric < 0)) return zv::Val();
		if (numeric == PT_TRI_YES) {
			zv::Val integer = integerType();
			if (UNEXPECTED(integer.isUndef())) return zv::Val();
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return combinator2(PT_LC("union"), integer.raw(), &selfZv);
		}
		zend_long string = thisTrinary(PT_LC("isstring"), &IntersectionType::isString);
		if (UNEXPECTED(string < 0)) return zv::Val();
		if (string == PT_TRI_YES) return thisValue();
		return intersectCall(PT_LC("toarraykey"), 0, NULL);
	}

	zv::Val toCoercedArgumentType(bool strictTypes) const
	{
		zval strict;
		ZVAL_BOOL(&strict, strictTypes);
		return intersectCall(PT_LC("tocoercedargumenttype"), 1, &strict);
	}

	/* the intersection of every member's inferTemplateTypes($receivedType) */
	zv::Val inferTemplateTypes(zval *receivedType) const
	{
		return mapIntersect(PT_LC("infertemplatetypes"), receivedType, false);
	}

	zv::Val getReferencedTemplateTypes(zval *positionVariance) const { return concatOf(PT_LC("getreferencedtemplatetypes"), 1, positionVariance); }

	/* TypeCombinator::intersect() folded over $cb of every member when any
	 * changed, $this otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr newTypes = zv::Arr::create(zv::ArrRef(typesCopy.raw()).size());
		bool changed = false;
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zval *type = entry.value().deref().raw();
			zval arg;
			ZVAL_COPY_VALUE(&arg, type);
			zval newType;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, &arg, &newType))) return zv::Val();
			if (Z_TYPE(newType) != IS_OBJECT || Z_OBJ(newType) != Z_OBJ_P(type)) {
				changed = true;
			}
			newTypes.push(zv::Val::adopt(newType));
		}
		if (changed) return foldIntersect(newTypes.arrRef());
		return thisValue();
	}

	/* for two arrays: every member's key and value types mapped through
	 * $cb against the right's, the changed members rebuilt through
	 * TypeTraverser::map() and intersected; $this otherwise; UNDEF =
	 * pending exception */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zend_long thisIsArray = thisTrinary(PT_LC("isarray"), &IntersectionType::isArray);
		if (UNEXPECTED(thisIsArray < 0)) return zv::Val();
		if (thisIsArray != PT_TRI_YES) return thisValue();
		zend_long rightIsArray = pt_type_call_trinary(Z_OBJ_P(right), PT_LC("isarray"), 0, NULL);
		if (UNEXPECTED(rightIsArray < 0)) return zv::Val();
		if (rightIsArray != PT_TRI_YES) return thisValue();

		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr newTypes = zv::Arr::create(zv::ArrRef(typesCopy.raw()).size());
		bool changed = false;
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zval *innerType = entry.value().deref().raw();
			zv::Val innerKeyType = callType(Z_OBJ_P(innerType), PT_LC("getiterablekeytype"), 0, NULL);
			if (UNEXPECTED(innerKeyType.isUndef())) return zv::Val();
			zv::Val rightKeyType = callType(Z_OBJ_P(right), PT_LC("getiterablekeytype"), 0, NULL);
			if (UNEXPECTED(rightKeyType.isUndef())) return zv::Val();
			zv::Args keyArgs{innerKeyType.raw(), rightKeyType.raw()};
			zval newKeyRaw;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, keyArgs, &newKeyRaw))) return zv::Val();
			zv::Val newKeyType = zv::Val::adopt(newKeyRaw);
			zv::Val innerValueType = callType(Z_OBJ_P(innerType), PT_LC("getiterablevaluetype"), 0, NULL);
			if (UNEXPECTED(innerValueType.isUndef())) return zv::Val();
			zv::Val rightValueType = callType(Z_OBJ_P(right), PT_LC("getiterablevaluetype"), 0, NULL);
			if (UNEXPECTED(rightValueType.isUndef())) return zv::Val();
			zv::Args valueArgs{innerValueType.raw(), rightValueType.raw()};
			zval newValueRaw;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, valueArgs, &newValueRaw))) return zv::Val();
			zv::Val newValueType = zv::Val::adopt(newValueRaw);
			/* $newKeyType === $innerType->getIterableKeyType() && $newValueType === $innerType->getIterableValueType() */
			zv::Val keyAgain = callType(Z_OBJ_P(innerType), PT_LC("getiterablekeytype"), 0, NULL);
			if (UNEXPECTED(keyAgain.isUndef())) return zv::Val();
			bool same = Z_TYPE_P(newKeyType.raw()) == IS_OBJECT && Z_OBJ_P(newKeyType.raw()) == Z_OBJ_P(keyAgain.raw());
			if (same) {
				zv::Val valueAgain = callType(Z_OBJ_P(innerType), PT_LC("getiterablevaluetype"), 0, NULL);
				if (UNEXPECTED(valueAgain.isUndef())) return zv::Val();
				same = Z_TYPE_P(newValueType.raw()) == IS_OBJECT && Z_OBJ_P(newValueType.raw()) == Z_OBJ_P(valueAgain.raw());
			}
			if (same) {
				newTypes.push(zv::Ref(innerType));
				continue;
			}
			changed = true;
			zv::Arr state = zv::Arr::create(3);
			state.push(zv::Ref(innerType));
			state.push(std::move(newKeyType));
			state.push(std::move(newValueType));
			zv::Val callback = pt_type_native_callback(replaceKeyValueCallback, state.raw(), NULL);
			if (UNEXPECTED(callback.isUndef())) return zv::Val();
			zv::Val mapped = pt_type_traverser_map_of(innerType, callback.raw());
			if (UNEXPECTED(mapped.isUndef())) return zv::Val();
			newTypes.push(std::move(mapped));
		}
		if (!changed) return thisValue();
		return foldIntersect(newTypes.arrRef());
	}

	/* the TypeTraverser::map() callback of traverseSimultaneously(): the
	 * member's key/value types replaced, everything else traversed; state0
	 * = [$innerType, $newKeyType, $newValueType] */
	static void replaceKeyValueCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		if (UNEXPECTED(argc != 2 || Z_TYPE_P(state0) != IS_ARRAY)) {
			zend_type_error("phpstan_turbo: the TypeTraverser callback expects a type and the traverse callable");
			return;
		}
		zval *innerType = zend_hash_index_find(Z_ARRVAL_P(state0), 0);
		zval *newKeyType = zend_hash_index_find(Z_ARRVAL_P(state0), 1);
		zval *newValueType = zend_hash_index_find(Z_ARRVAL_P(state0), 2);
		if (UNEXPECTED(innerType == NULL || newKeyType == NULL || newValueType == NULL)) {
			zend_type_error("phpstan_turbo: the TypeTraverser callback holder is malformed");
			return;
		}
		zval *type = &argv[0];
		zv::Val keyType = callType(Z_OBJ_P(innerType), PT_LC("getiterablekeytype"), 0, NULL);
		if (UNEXPECTED(keyType.isUndef())) return;
		if (Z_TYPE_P(type) == IS_OBJECT && Z_OBJ_P(type) == Z_OBJ_P(keyType.raw())) {
			ZVAL_COPY(return_value, newKeyType);
			return;
		}
		zv::Val valueType = callType(Z_OBJ_P(innerType), PT_LC("getiterablevaluetype"), 0, NULL);
		if (UNEXPECTED(valueType.isUndef())) return;
		if (Z_TYPE_P(type) == IS_OBJECT && Z_OBJ_P(type) == Z_OBJ_P(valueType.raw())) {
			ZVAL_COPY(return_value, newValueType);
			return;
		}
		zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
		if (UNEXPECTED(traversed.isUndef())) return;
		traversed.intoReturnValue(return_value);
	}

	/* TypeCombinator::remove($type, $typeToRemove) per member, intersected */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		return intersectTypes([&](zval *type) -> zv::Val {
			return combinator2(PT_LC("remove"), type, typeToRemove);
		});
	}

	zv::Val exponentiate(zval *exponent) const { return intersectCall(PT_LC("exponentiate"), 1, exponent); }

	/* the finite types every member has, keyed by enum case or type-only
	 * description, [] beyond CALCULATE_SCALARS_LIMIT */
	zv::Val getFiniteTypes() const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Val typeOnly;
		std::vector<zv::Val> compare;
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zv::Val finiteTypes = pt_type_call_array(entry.value().deref().asObject(), PT_LC("getfinitetypes"), 0, NULL);
			if (UNEXPECTED(finiteTypes.isUndef())) return zv::Val();
			zv::Arr oneType = zv::Arr::create(zv::ArrRef(finiteTypes.raw()).size());
			for (zv::ArrayEntry finiteEntry : zv::ArrRef(finiteTypes.raw())) {
				zval *finiteType = finiteEntry.value().deref().raw();
				bool isEnumCase;
				if (UNEXPECTED(!pt_type_instanceof_ce(finiteType, pt_ce_enum_case_object_type, isEnumCase))) return zv::Val();
				zv::Val key;
				if (isEnumCase) {
					key = enumCaseKey(finiteType);
				} else {
					if (typeOnly.isUndef()) {
						typeOnly = verbosityLevel(PT_VERBOSITY_LEVEL_TYPE_ONLY);
						if (UNEXPECTED(typeOnly.isUndef())) return zv::Val();
					}
					key = describeOf(finiteType, typeOnly.raw());
				}
				if (UNEXPECTED(key.isUndef())) return zv::Val();
				oneType.set(zv::Ref(key.raw()).asString(), zv::Val::copyOf(zv::Ref(finiteType)));
			}
			compare.push_back(zv::Val(std::move(oneType)));
		}
		zv::Val result = intersectKeysOf(compare);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zval *limit = classConstant(PT_CLASS_INITIALIZER_EXPR_TYPE_RESOLVER, PT_LC("CALCULATE_SCALARS_LIMIT"));
		if (UNEXPECTED(limit == NULL)) return zv::Val();
		if ((zend_long) zv::ArrRef(result.raw()).size() > zval_get_long(limit)) return zv::Val(zv::Arr::empty());
		return result;
	}

	/* whether any member has one; -1 = pending exception */
	int hasTemplateOrLateResolvableType() const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return -1;
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			int has = pt_type_call_is_true(entry.value().deref().asObject(), PT_LC("hastemplateorlateresolvabletype"), 0, NULL);
			if (UNEXPECTED(has < 0)) return -1;
			if (has == 0) continue;
			return 1;
		}
		return 0;
	}

	/* toPhpDocNode(): the string accessories folded into the string member,
	 * the array members as (non-empty-)array/list generic nodes with the
	 * array accessories absorbed (a constant array's shape re-kinded), the
	 * other accessories' nodes unless empty; one node alone, none throws,
	 * more an IntersectionTypeNode; UNDEF = pending exception */
	zv::Val toPhpDocNode() const
	{
		zend_long isListValue = thisTrinary(PT_LC("islist"), &IntersectionType::isList);
		if (UNEXPECTED(isListValue < 0)) return zv::Val();
		zend_long isArrayValue = thisTrinary(PT_LC("isarray"), &IntersectionType::isArray);
		if (UNEXPECTED(isArrayValue < 0)) return zv::Val();
		zend_long nonEmptyValue = thisTrinary(PT_LC("isiterableatleastonce"), &IntersectionType::isIterableAtLeastOnce);
		if (UNEXPECTED(nonEmptyValue < 0)) return zv::Val();
		bool isList = isListValue == PT_TRI_YES;
		bool isArray = isArrayValue == PT_TRI_YES;
		bool isNonEmptyArray = nonEmptyValue == PT_TRI_YES;

		zv::Val sorted = getSortedTypes();
		if (UNEXPECTED(sorted.isUndef())) return zv::Val();
		uint32_t count = zv::ArrRef(sorted.raw()).size();
		std::vector<zval *> baseTypes(count, nullptr);
		std::vector<zval *> typesToDescribe(count, nullptr);
		std::vector<zv::Val> describedTypes(count);
		bool skipString = false;
		bool nonEmptyStr = false;
		bool nonFalsyStr = false;
		uint32_t i = 0;
		for (zv::ArrayEntry entry : zv::ArrRef(sorted.raw())) {
			uint32_t index = i++;
			zval *type = entry.value().deref().raw();
			bool stringAccessory;
			if (UNEXPECTED(!isStringAccessory(type, stringAccessory))) return zv::Val();
			if (stringAccessory) {
				bool isNonFalsy, isNonEmpty;
				if (UNEXPECTED(!isInstance(type, pt_ce_accessory_non_falsy_string_type, isNonFalsy) || !isInstance(type, pt_ce_accessory_non_empty_string_type, isNonEmpty))) {
					return zv::Val();
				}
				if (isNonFalsy) {
					nonFalsyStr = true;
				}
				if (isNonEmpty) {
					nonEmptyStr = true;
				}
				if (nonEmptyStr && nonFalsyStr) {
					for (uint32_t k = 0; k < count; k++) {
						if (typesToDescribe[k] == nullptr) continue;
						bool describedNonEmpty;
						if (UNEXPECTED(!isInstance(typesToDescribe[k], pt_ce_accessory_non_empty_string_type, describedNonEmpty))) return zv::Val();
						if (describedNonEmpty) {
							typesToDescribe[k] = nullptr;
						}
					}
				}
				typesToDescribe[index] = type;
				skipString = true;
				continue;
			}

			if (isList || isArray) {
				if (zv::Ref(type).instanceOf(pt_ce_array_type)) {
					zv::Val keyType = pt_array_type_get_key_type(Z_OBJ_P(type));
					if (UNEXPECTED(keyType.isUndef())) return zv::Val();
					zv::Val valueType = pt_array_type_get_item_type(Z_OBJ_P(type));
					if (UNEXPECTED(valueType.isUndef())) return zv::Val();
					if (isList) {
						bool plainMixedValue;
						if (UNEXPECTED(!isPlainMixed(valueType.raw(), plainMixedValue))) return zv::Val();
						zv::Val identifier = identifierNode(isNonEmptyArray ? "non-empty-list" : "list");
						if (UNEXPECTED(identifier.isUndef())) return zv::Val();
						if (!plainMixedValue) {
							zv::Arr genericTypes = zv::Arr::create(1);
							zv::Val valueNode = pt_type_call(Z_OBJ_P(valueType.raw()), PT_LC("tophpdocnode"), 0, NULL);
							if (UNEXPECTED(valueNode.isUndef())) return zv::Val();
							genericTypes.push(std::move(valueNode));
							describedTypes[index] = genericNode(std::move(identifier), std::move(genericTypes));
						} else {
							describedTypes[index] = std::move(identifier);
						}
					} else {
						bool plainMixedKey, plainMixedValue;
						if (UNEXPECTED(!isPlainMixed(keyType.raw(), plainMixedKey) || !isPlainMixed(valueType.raw(), plainMixedValue))) return zv::Val();
						zv::Val identifier = identifierNode(isNonEmptyArray ? "non-empty-array" : "array");
						if (UNEXPECTED(identifier.isUndef())) return zv::Val();
						if (!plainMixedKey) {
							zv::Arr genericTypes = zv::Arr::create(2);
							zv::Val keyNode = pt_type_call(Z_OBJ_P(keyType.raw()), PT_LC("tophpdocnode"), 0, NULL);
							if (UNEXPECTED(keyNode.isUndef())) return zv::Val();
							zv::Val valueNode = pt_type_call(Z_OBJ_P(valueType.raw()), PT_LC("tophpdocnode"), 0, NULL);
							if (UNEXPECTED(valueNode.isUndef())) return zv::Val();
							genericTypes.push(std::move(keyNode));
							genericTypes.push(std::move(valueNode));
							describedTypes[index] = genericNode(std::move(identifier), std::move(genericTypes));
						} else if (!plainMixedValue) {
							zv::Arr genericTypes = zv::Arr::create(1);
							zv::Val valueNode = pt_type_call(Z_OBJ_P(valueType.raw()), PT_LC("tophpdocnode"), 0, NULL);
							if (UNEXPECTED(valueNode.isUndef())) return zv::Val();
							genericTypes.push(std::move(valueNode));
							describedTypes[index] = genericNode(std::move(identifier), std::move(genericTypes));
						} else {
							describedTypes[index] = std::move(identifier);
						}
					}
					if (UNEXPECTED(describedTypes[index].isUndef())) return zv::Val();
					continue;
				}
				bool isConstantArray;
				if (UNEXPECTED(!isInstance(type, pt_ce_constant_array_type, isConstantArray))) return zv::Val();
				if (isConstantArray) {
					zv::Val node = pt_type_call(Z_OBJ_P(type), PT_LC("tophpdocnode"), 0, NULL);
					if (UNEXPECTED(node.isUndef())) return zv::Val();
					bool isShape;
					if (UNEXPECTED(!isInstance(node.raw(), PT_CLASS_ARRAY_SHAPE_NODE, isShape))) return zv::Val();
					if (isShape) {
						zv::Val kind = readProperty(node.raw(), PT_LC("kind"));
						if (UNEXPECTED(kind.isUndef())) return zv::Val();
						zval *newKind = kind.raw();
						bool typeNonEmptyKnown = false;
						bool typeNonEmpty = false;
						if (isNonEmptyArray) {
							zend_long value = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isiterableatleastonce"), 0, NULL);
							if (UNEXPECTED(value < 0)) return zv::Val();
							typeNonEmptyKnown = true;
							typeNonEmpty = value == PT_TRI_YES;
						}
						(void) typeNonEmptyKnown;
						if (isList) {
							newKind = classConstant(PT_CLASS_ARRAY_SHAPE_NODE, isNonEmptyArray && !typeNonEmpty ? "KIND_NON_EMPTY_LIST" : "KIND_LIST", isNonEmptyArray && !typeNonEmpty ? sizeof("KIND_NON_EMPTY_LIST") - 1 : sizeof("KIND_LIST") - 1);
						} else if (isNonEmptyArray && !typeNonEmpty) {
							newKind = classConstant(PT_CLASS_ARRAY_SHAPE_NODE, PT_LC("KIND_NON_EMPTY_ARRAY"));
						}
						if (UNEXPECTED(newKind == NULL)) return zv::Val();
						if (!(Z_TYPE_P(newKind) == IS_STRING && Z_TYPE_P(kind.raw()) == IS_STRING && zend_string_equals(Z_STR_P(newKind), Z_STR_P(kind.raw())))) {
							zv::Val sealed = readProperty(node.raw(), PT_LC("sealed"));
							if (UNEXPECTED(sealed.isUndef())) return zv::Val();
							zv::Val items = readProperty(node.raw(), PT_LC("items"));
							if (UNEXPECTED(items.isUndef())) return zv::Val();
							if (zend_is_true(sealed.raw())) {
								zv::Args args{items.raw(), newKind};
								node = pt_type_call_static(PT_CLASS_ARRAY_SHAPE_NODE, PT_LC("createsealed"), 2, args);
							} else {
								zv::Val unsealedType = readProperty(node.raw(), PT_LC("unsealedType"));
								if (UNEXPECTED(unsealedType.isUndef())) return zv::Val();
								zv::Args args{items.raw(), unsealedType.raw(), newKind};
								node = pt_type_call_static(PT_CLASS_ARRAY_SHAPE_NODE, PT_LC("createunsealed"), 3, args);
							}
							if (UNEXPECTED(node.isUndef())) return zv::Val();
						}
						describedTypes[index] = std::move(node);
						continue;
					}
				}
				if (zv::Ref(type).instanceOf(pt_ce_non_empty_array_type) || zv::Ref(type).instanceOf(pt_ce_accessory_array_list_type)) continue;
			}

			bool isAccessory;
			if (UNEXPECTED(!isInstance(type, PT_CLASS_ACCESSORY_TYPE, isAccessory))) return zv::Val();
			if (!isAccessory) {
				baseTypes[index] = type;
				continue;
			}
			zv::Val accessoryNode = pt_type_call(Z_OBJ_P(type), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(accessoryNode.isUndef())) return zv::Val();
			bool isIdentifier;
			if (UNEXPECTED(!isInstance(accessoryNode.raw(), PT_CLASS_IDENTIFIER_TYPE_NODE, isIdentifier))) return zv::Val();
			if (isIdentifier) {
				zv::Val name = readProperty(accessoryNode.raw(), PT_LC("name"));
				if (UNEXPECTED(name.isUndef())) return zv::Val();
				if (Z_TYPE_P(name.raw()) == IS_STRING && Z_STRLEN_P(name.raw()) == 0) continue;
			}
			typesToDescribe[index] = type;
		}

		for (uint32_t k = 0; k < count; k++) {
			zval *type = baseTypes[k];
			if (type == nullptr) continue;
			zv::Val typeNode = pt_type_call(Z_OBJ_P(type), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(typeNode.isUndef())) return zv::Val();
			bool isGeneric;
			if (UNEXPECTED(!isInstance(typeNode.raw(), PT_CLASS_GENERIC_TYPE_NODE, isGeneric))) return zv::Val();
			if (isGeneric) {
				zv::Val identifier = readProperty(typeNode.raw(), PT_LC("type"));
				if (UNEXPECTED(identifier.isUndef())) return zv::Val();
				if (UNEXPECTED(!zv::Ref(identifier.raw()).isObject())) {
					zend_type_error("phpstan_turbo: GenericTypeNode::$type must be an IdentifierTypeNode");
					return zv::Val();
				}
				zv::Val identifierName = readProperty(identifier.raw(), PT_LC("name"));
				if (UNEXPECTED(identifierName.isUndef())) return zv::Val();
				if (Z_TYPE_P(identifierName.raw()) == IS_STRING && zend_string_equals_literal(Z_STR_P(identifierName.raw()), "array")) {
					bool nonEmpty = false;
					const char *typeName = "array";
					for (uint32_t j = 0; j < count; j++) {
						zval *typeToDescribe = typesToDescribe[j];
						if (typeToDescribe == nullptr) continue;
						if (zv::Ref(typeToDescribe).instanceOf(pt_ce_accessory_array_list_type)) {
							typeName = "list";
							zv::Val genericTypes = readProperty(typeNode.raw(), PT_LC("genericTypes"));
							if (UNEXPECTED(genericTypes.isUndef())) return zv::Val();
							if (UNEXPECTED(!zv::Ref(genericTypes.raw()).isArray())) {
								zend_type_error("phpstan_turbo: GenericTypeNode::$genericTypes must be an array");
								return zv::Val();
							}
							if (zv::ArrRef(genericTypes.raw()).size() > 1) {
								/* array_shift($typeNode->genericTypes) — on the node's own property */
								zv::Arr shifted = zv::Arr::create(zv::ArrRef(genericTypes.raw()).size() - 1);
								bool first = true;
								for (zv::ArrayEntry genericEntry : zv::ArrRef(genericTypes.raw())) {
									if (first) {
										first = false;
										continue;
									}
									if (genericEntry.stringKeyOrNull() != NULL) {
										shifted.set(genericEntry.stringKeyOrNull(), zv::Val::copyOf(genericEntry.value()));
									} else {
										shifted.push(genericEntry.value());
									}
								}
								zend_string *propertyName = zend_string_init(PT_LC("genericTypes"), 0);
								zv::ObjRef(typeNode.raw()).propWrite(propertyName, shifted.arrRef());
								zend_string_release(propertyName);
								if (UNEXPECTED(EG(exception))) return zv::Val();
							}
						} else if (zv::Ref(typeToDescribe).instanceOf(pt_ce_non_empty_array_type)) {
							nonEmpty = true;
						} else {
							continue;
						}
						typesToDescribe[j] = nullptr;
					}
					smart_str name = {NULL, 0};
					if (nonEmpty) {
						smart_str_appendl(&name, "non-empty-", 10);
					}
					smart_str_appends(&name, typeName);
					smart_str_0(&name);
					zv::Val nameZv = zv::Val::adoptString(name.s);
					zv::Val newIdentifier = pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, nameZv.raw());
					if (UNEXPECTED(newIdentifier.isUndef())) return zv::Val();
					zv::Val genericTypes = readProperty(typeNode.raw(), PT_LC("genericTypes"));
					if (UNEXPECTED(genericTypes.isUndef())) return zv::Val();
					zv::Args args{newIdentifier.raw(), genericTypes.raw()};
					describedTypes[k] = pt_type_new(PT_CLASS_GENERIC_TYPE_NODE, 2, args);
					if (UNEXPECTED(describedTypes[k].isUndef())) return zv::Val();
					continue;
				}
			}

			bool isIdentifier;
			if (UNEXPECTED(!isInstance(typeNode.raw(), PT_CLASS_IDENTIFIER_TYPE_NODE, isIdentifier))) return zv::Val();
			if (isIdentifier) {
				zv::Val name = readProperty(typeNode.raw(), PT_LC("name"));
				if (UNEXPECTED(name.isUndef())) return zv::Val();
				if (skipString && Z_TYPE_P(name.raw()) == IS_STRING && zend_string_equals_literal(Z_STR_P(name.raw()), "string")) continue;
			}
			describedTypes[k] = std::move(typeNode);
		}

		for (uint32_t k = 0; k < count; k++) {
			if (typesToDescribe[k] == nullptr) continue;
			describedTypes[k] = pt_type_call(Z_OBJ_P(typesToDescribe[k]), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(describedTypes[k].isUndef())) return zv::Val();
		}

		zv::Arr nodes = zv::Arr::create(count);
		for (uint32_t k = 0; k < count; k++) {
			if (!describedTypes[k].isUndef()) {
				nodes.push(std::move(describedTypes[k]));
			}
		}
		uint32_t described = zend_hash_num_elements(nodes.table());
		if (described == 1) return zv::Val::copyOf(nodes.arrRef().findIndex(0));
		if (described == 0) {
			zv::Val precise = verbosityLevel(PT_VERBOSITY_LEVEL_PRECISE);
			if (UNEXPECTED(precise.isUndef())) return zv::Val();
			zval *types = this->types();
			if (UNEXPECTED(types == NULL)) return zv::Val();
			smart_str message = {NULL, 0};
			smart_str_appendl(&message, "Intersection consists of ", sizeof("Intersection consists of ") - 1);
			bool first = true;
			for (zv::ArrayEntry entry : zv::ArrRef(types)) {
				zv::Val description = describeOf(entry.value().deref().raw(), precise.raw());
				if (UNEXPECTED(description.isUndef())) {
					smart_str_free(&message);
					return zv::Val();
				}
				if (!first) {
					smart_str_appendc(&message, '&');
				}
				first = false;
				smart_str_append(&message, zv::Ref(description.raw()).asString());
			}
			smart_str_appendl(&message, " but there should be at least one base type.", sizeof(" but there should be at least one base type.") - 1);
			smart_str_0(&message);
			zv::Val messageZv = zv::Val::adoptString(message.s);
			throwShouldNotHappen(messageZv.raw());
			return zv::Val();
		}
		return pt_type_new(PT_CLASS_INTERSECTION_TYPE_NODE, 1, nodes.raw());
	}

private:
	zend_object *self;

	/* exactly an IntersectionType, none of its methods overridden: $this-calls
	 * can go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_intersection_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* $this->method() returning TrinaryLogic through the object's class
	 * entry; -1 = pending exception */
	[[nodiscard]] zend_long thisTrinary(const char *lcname, size_t len, zend_long (IntersectionType::*method)() const) const
	{
		if (EXPECTED(isExact())) return (this->*method)();
		return pt_type_call_trinary(self, lcname, len, 0, NULL);
	}

	/* $this->method() returning a Type, the same way */
	zv::Val thisType(const char *lcname, size_t len, zv::Val (IntersectionType::*method)() const) const
	{
		if (EXPECTED(isExact())) return (this->*method)();
		return callType(self, lcname, len, 0, NULL);
	}

	/* $this->getOffsetValueType($offsetType) through the object's class entry */
	zv::Val thisGetOffsetValueType(zval *offsetType) const
	{
		if (EXPECTED(isExact())) return getOffsetValueType(offsetType);
		return callType(self, PT_LC("getoffsetvaluetype"), 1, offsetType);
	}

	/* $this->equals($type) through the object's class entry; -1 = pending
	 * exception */
	int thisEquals(zval *type) const
	{
		if (EXPECTED(isExact())) {
			bool equal;
			if (UNEXPECTED(!equals(type, equal))) return -1;
			return equal ? 1 : 0;
		}
		return pt_type_call_is_true(self, PT_LC("equals"), 1, type);
	}

	/* $this->describe($level) through the object's class entry */
	zv::Val thisDescribe(zval *level) const
	{
		if (EXPECTED(isExact())) return describe(level);
		return callString(self, PT_LC("describe"), 1, level);
	}

	/* $this->isCallable()->yes() && $this->isArray()->yes(); -1 = pending
	 * exception */
	int isCallableArray() const
	{
		zend_long callable = thisTrinary(PT_LC("iscallable"), &IntersectionType::isCallable);
		if (UNEXPECTED(callable < 0)) return -1;
		if (callable != PT_TRI_YES) return 0;
		zend_long array = thisTrinary(PT_LC("isarray"), &IntersectionType::isArray);
		if (UNEXPECTED(array < 0)) return -1;
		return array == PT_TRI_YES ? 1 : 0;
	}

	/* intersectResults(): TrinaryLogic::lazyMaxMin() over the members
	 * (those the filter keeps): the first yes, else the and-fold; no for no
	 * member; -1 = pending exception */
	template <typename F>
	zend_long intersectResults(F getResult) const
	{
		return intersectResults(getResult, (bool (*)(zval *, bool &)) nullptr);
	}

	template <typename F>
	zend_long intersectResults(F getResult, bool (*filter)(zval *, bool &)) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return -1;
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr filtered = zv::Arr::create(0);
		if (filter != nullptr) {
			for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
				bool keep;
				if (UNEXPECTED(!filter(entry.value().deref().raw(), keep))) return -1;
				if (keep) {
					filtered.push(entry.value());
				}
			}
			typesCopy = zv::Val(std::move(filtered));
		}
		if (zv::ArrRef(typesCopy.raw()).size() == 0) return PT_TRI_NO;
		zend_long min = PT_TRI_YES;
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zend_long result = getResult(entry.value().deref().raw());
			if (UNEXPECTED(result < 0)) return -1;
			if (result == PT_TRI_YES) return PT_TRI_YES;
			min &= result;
		}
		return min;
	}

	/* intersectResults(fn (Type $type) => $type->method(...$args)) */
	zend_long intersectResultsCall(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		return intersectResults([&](zval *type) { return pt_type_call_trinary(Z_OBJ_P(type), lcname, len, argc, argv); });
	}

	/* intersectTypes(): $getType over every member (all of them first, as
	 * array_map does), folded with TypeCombinator::intersect(); UNDEF =
	 * pending exception */
	template <typename F>
	zv::Val intersectTypes(F getType) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr operands = zv::Arr::create(zv::ArrRef(typesCopy.raw()).size());
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zv::Val operand = getType(entry.value().deref().raw());
			if (UNEXPECTED(operand.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(operand.raw()).isObject())) {
				zend_type_error("phpstan_turbo: the intersectTypes() callback must return %s, %s returned", ptcls::type, zend_zval_value_name(operand.raw()));
				return zv::Val();
			}
			operands.push(std::move(operand));
		}
		return foldIntersect(operands.arrRef());
	}

	/* $result = $operands[0]; TypeCombinator::intersect($result, $operands[$i]) for the rest */
	static zv::Val foldIntersect(zv::ArrRef operands)
	{
		zv::Val result;
		for (zv::ArrayEntry entry : operands) {
			if (result.isUndef()) {
				result = zv::Val::copyOf(entry.value());
				continue;
			}
			result = combinator2(PT_LC("intersect"), result.raw(), entry.value().deref().raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}
		if (UNEXPECTED(result.isUndef())) {
			zend_throw_error(NULL, "phpstan_turbo: an intersection has no members");
			return zv::Val();
		}
		return result;
	}

	/* intersectTypes(fn (Type $type) => $type->method(...$args)) */
	zv::Val intersectCall(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		return intersectTypes([&](zval *type) { return callType(Z_OBJ_P(type), lcname, len, argc, argv); });
	}

	/* intersectTypesPreserveTemplateType(): a TemplateType member stays as
	 * it is */
	zv::Val intersectCallPreservingTemplates(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		return intersectTypes([&](zval *type) -> zv::Val {
			bool isTemplate;
			if (UNEXPECTED(!isInstance(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
			if (isTemplate) return zv::Val::copyOf(zv::Ref(type));
			return callType(Z_OBJ_P(type), lcname, len, argc, argv);
		});
	}

	/* popArray()/shiftArray() on a list: the template members kept, a
	 * HasOffsetValueType/HasOffsetType at a constant offset n >= 1 moved to
	 * n - 1 (its value kept only on shift), the rest popped/shifted; all
	 * intersected; UNDEF = pending exception */
	zv::Val listMembers(const char *lcname, size_t len, bool keepValue) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr members = zv::Arr::create(zv::ArrRef(typesCopy.raw()).size());
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zval *type = entry.value().deref().raw();
			bool isTemplate;
			if (UNEXPECTED(!isInstance(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
			if (isTemplate) {
				members.push(zv::Ref(type));
				continue;
			}
			bool isHasOffsetValue = zv::Ref(type).instanceOf(pt_ce_has_offset_value_type);
			if (isHasOffsetValue || zv::Ref(type).instanceOf(pt_ce_has_offset_type)) {
				zv::Val offsetType = offsetTypeOf(type);
				if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
				if (zv::Ref(offsetType.raw()).instanceOf(pt_ce_constant_integer_type)) {
					zend_long value;
					if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(offsetType.raw()), value))) return zv::Val();
					if (value >= 1) {
						zv::Val newOffset = constantInteger(value - 1);
						if (UNEXPECTED(newOffset.isUndef())) return zv::Val();
						zval raw;
						if (keepValue && isHasOffsetValue) {
							zv::Val valueType = pt_has_offset_value_type_get_value_type(Z_OBJ_P(type));
							if (UNEXPECTED(valueType.isUndef())) return zv::Val();
							if (UNEXPECTED(!pt_has_offset_value_type_new(&raw, newOffset.raw(), valueType.raw()))) return zv::Val();
						} else if (UNEXPECTED(!pt_has_offset_type_new(&raw, newOffset.raw()))) {
							return zv::Val();
						}
						members.push(zv::Val::adopt(raw));
					}
				}
				continue;
			}
			zv::Val member = callType(Z_OBJ_P(type), lcname, len, 0, NULL);
			if (UNEXPECTED(member.isUndef())) return zv::Val();
			members.push(std::move(member));
		}
		return combinatorIntersect(members.table());
	}

	/* array_map(fn (Type $innerType) => $innerType->method(...$args), $this->types) */
	zv::Val mapCall(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr results = zv::Arr::create(zv::ArrRef(typesCopy.raw()).size());
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zv::Val result = pt_type_call(entry.value().deref().asObject(), lcname, len, argc, argv);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			results.push(std::move(result));
		}
		return zv::Val(std::move(results));
	}

	/* TemplateTypeMap::createEmpty()->intersect(...) over the members:
	 * $type->method($argument), or $argument->method($type) when reversed */
	zv::Val mapIntersect(const char *lcname, size_t len, zval *argument, bool reversed) const
	{
		zv::Val map = pt_type_template_type_map_empty();
		if (UNEXPECTED(map.isUndef())) return zv::Val();
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zval *type = entry.value().deref().raw();
			zv::Val inferred = reversed ? pt_type_call(Z_OBJ_P(argument), lcname, len, 1, type) : pt_type_call(Z_OBJ_P(type), lcname, len, 1, argument);
			if (UNEXPECTED(inferred.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(map.raw()).isObject())) {
				zend_type_error("phpstan_turbo: inferTemplateTypes() must return TemplateTypeMap");
				return zv::Val();
			}
			map = pt_type_call(Z_OBJ_P(map.raw()), PT_LC("intersect"), 1, inferred.raw());
			if (UNEXPECTED(map.isUndef())) return zv::Val();
		}
		return map;
	}

	/* the concatenation of an array-valued member call over every member */
	zv::Val concatOf(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr values = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zv::Val innerValues = pt_type_call_array(entry.value().deref().asObject(), lcname, len, argc, argv);
			if (UNEXPECTED(innerValues.isUndef())) return zv::Val();
			for (zv::ArrayEntry value : zv::ArrRef(innerValues.raw())) {
				values.push(value.value());
			}
		}
		return zv::Val(std::move(values));
	}

	/* the lazyMaxMin() callback: $receiver->method($innerType, $extra?);
	 * state0 = [$receiver, method, $extra|null] */
	static void lazyMaxMinCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		if (UNEXPECTED(argc != 1 || Z_TYPE_P(state0) != IS_ARRAY)) {
			zend_type_error("phpstan_turbo: the lazyMaxMin() callback expects one type");
			return;
		}
		zval *receiver = zend_hash_index_find(Z_ARRVAL_P(state0), 0);
		zval *method = zend_hash_index_find(Z_ARRVAL_P(state0), 1);
		zval *extra = zend_hash_index_find(Z_ARRVAL_P(state0), 2);
		if (UNEXPECTED(receiver == NULL || method == NULL || extra == NULL || Z_TYPE_P(receiver) != IS_OBJECT || Z_TYPE_P(method) != IS_STRING)) {
			zend_type_error("phpstan_turbo: the lazyMaxMin() callback holder is malformed");
			return;
		}
		zval args[2];
		ZVAL_COPY_VALUE(&args[0], &argv[0]);
		uint32_t count = 1;
		if (Z_TYPE_P(extra) != IS_NULL) {
			ZVAL_COPY_VALUE(&args[1], extra);
			count = 2;
		}
		zv::Val result = pt_type_call(Z_OBJ_P(receiver), Z_STRVAL_P(method), Z_STRLEN_P(method), count, args);
		if (UNEXPECTED(result.isUndef())) return;
		result.intoReturnValue(return_value);
	}

	/* <ResultClass>::lazyMaxMin($this->types, fn (Type $innerType) =>
	 * $receiver->method($innerType, $extra?)); UNDEF = pending exception */
	zv::Val lazyMaxMin(zend_class_entry *resultClass, const char *lcname, size_t len, zval *receiver, zval *extra) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Arr state = zv::Arr::create(3);
		state.push(zv::Ref(receiver));
		state.push(zv::Val::string(lcname, len));
		if (extra != NULL) {
			state.push(zv::Ref(extra));
		} else {
			state.push(zv::Val::null());
		}
		zv::Val callback = pt_type_native_callback(lazyMaxMinCallback, state.raw(), NULL);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		zv::Args args{types, callback.raw()};
		return pt_type_call_static_ce(resultClass, PT_LC("lazymaxmin"), 2, args);
	}

	/* the memoized TrinaryLogic slots: the slot when set, else the value
	 * stored as the singleton; -1 = pending exception */
	[[nodiscard]] zend_long memoize(uint32_t slotIndex, zend_long value) const
	{
		if (UNEXPECTED(value < 0)) return -1;
		zval *slot = OBJ_PROP_NUM(self, slotIndex);
		zv::Ref(slot).assign(pt_type_trinary(value));
		return value;
	}

	zend_long memoizedCall(uint32_t slotIndex, const char *lcname, size_t len) const
	{
		zval *slot = OBJ_PROP_NUM(self, slotIndex);
		if (Z_TYPE_P(slot) == IS_OBJECT) return pt_type_trinary_value(slot);
		return memoize(slotIndex, intersectResultsCall(lcname, len, 0, NULL));
	}

	/* $offsetType->describe(VerbosityLevel::cache()) */
	static zv::Val cacheKeyOf(zval *offsetType)
	{
		zv::Val cache = verbosityLevel(PT_VERBOSITY_LEVEL_CACHE);
		if (UNEXPECTED(cache.isUndef())) return zv::Val();
		return describeOf(offsetType, cache.raw());
	}

	/* (new ConstantIntegerType($value))->isSuperTypeOf($type); -1 =
	 * pending exception */
	static zend_long constantIsSuperTypeOf(zend_long value, zval *type)
	{
		zv::Val constant = constantInteger(value);
		if (UNEXPECTED(constant.isUndef())) return -1;
		return pt_type_call_result_trinary(Z_OBJ_P(constant.raw()), PT_LC("issupertypeof"), 1, type);
	}

	/* TypeCombinator::intersect($result, new Accessory()) */
	static zv::Val intersectWithNew(zval *result, bool (*construct)(zval *))
	{
		zval raw;
		if (UNEXPECTED(!construct(&raw))) return zv::Val();
		zv::Val accessory = zv::Val::adopt(raw);
		return combinator2(PT_LC("intersect"), result, accessory.raw());
	}

	/* $enumCase->getClassName() . '::' . $enumCase->getEnumCaseName() */
	static zv::Val enumCaseKey(zval *enumCase)
	{
		zv::Val className = callString(Z_OBJ_P(enumCase), PT_LC("getclassname"), 0, NULL);
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zv::Val caseName = callString(Z_OBJ_P(enumCase), PT_LC("getenumcasename"), 0, NULL);
		if (UNEXPECTED(caseName.isUndef())) return zv::Val();
		smart_str key = {NULL, 0};
		smart_str_append(&key, zv::Ref(className.raw()).asString());
		smart_str_appendl(&key, "::", 2);
		smart_str_append(&key, zv::Ref(caseName.raw()).asString());
		smart_str_0(&key);
		return zv::Val::adoptString(key.s);
	}

	/* $known[$value] = true for an int|string value; false with a
	 * TypeError pending for anything else */
	static bool setKey(zv::Arr &known, zval *value)
	{
		if (Z_TYPE_P(value) == IS_LONG) {
			known.separate();
			zval t;
			ZVAL_TRUE(&t);
			zend_hash_index_update(known.table(), Z_LVAL_P(value), &t);
			return true;
		}
		if (Z_TYPE_P(value) == IS_STRING) {
			known.set(Z_STR_P(value), zv::Val::boolean(true));
			return true;
		}
		zend_type_error("phpstan_turbo: an offset value must be int|string, %s given", zend_zval_value_name(value));
		return false;
	}

	/* implode('&', $names) */
	static zv::Val joinAmpersand(std::vector<zv::Val> &names)
	{
		smart_str result = {NULL, 0};
		bool first = true;
		for (zv::Val &name : names) {
			if (!first) {
				smart_str_appendc(&result, '&');
			}
			first = false;
			smart_str_append(&result, zv::Ref(name.raw()).asString());
		}
		smart_str_0(&result);
		if (result.s == NULL) return zv::Val::string("", 0);
		return zv::Val::adoptString(result.s);
	}

	/* new IdentifierTypeNode($name) / new GenericTypeNode($identifier, $genericTypes) */
	static zv::Val identifierNode(const char *name)
	{
		zv::Val nameZv = zv::Val::string(name, strlen(name));
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, nameZv.raw());
	}

	static zv::Val genericNode(zv::Val identifier, zv::Arr genericTypes)
	{
		zv::Args args{identifier.raw(), genericTypes.raw()};
		return pt_type_new(PT_CLASS_GENERIC_TYPE_NODE, 2, args);
	}

	/* ->reasons of a result object (owned copy); UNDEF = pending exception */
	static zv::Val resultReasons(zval *result)
	{
		if (EXPECTED(Z_TYPE_P(result) == IS_OBJECT && Z_OBJCE_P(result) == pt_ce_accepts_result)) {
			zval *reasons = pt_result_array_slot(Z_OBJ_P(result), PT_RESULT_PROP_REASONS, "reasons");
			if (UNEXPECTED(reasons == NULL)) return zv::Val();
			return zv::Val::copyOf(zv::Ref(reasons));
		}
		if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: expected %s, %s given", ZSTR_VAL(pt_ce_accepts_result->name), zend_zval_value_name(result));
			return zv::Val();
		}
		zv::Val reasons = readProperty(result, PT_LC("reasons"));
		if (UNEXPECTED(reasons.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(reasons.raw()).isArray())) {
			zend_type_error("phpstan_turbo: ->reasons must be an array");
			return zv::Val();
		}
		return reasons;
	}

	/* new AcceptsResult($result->result, $reasons) ($reasons consumed) */
	static zv::Val withReasons(zval *result, zv::Arr reasons)
	{
		zv::Val trinary;
		if (EXPECTED(Z_TYPE_P(result) == IS_OBJECT && Z_OBJCE_P(result) == pt_ce_accepts_result)) {
			trinary = zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(result), PT_RESULT_PROP_RESULT)));
		} else {
			trinary = readProperty(result, PT_LC("result"));
			if (UNEXPECTED(trinary.isUndef())) return zv::Val();
		}
		zval created;
		zval reasonsRaw = reasons.take();
		if (UNEXPECTED(!pt_accepts_result_create(&created, trinary.raw(), &reasonsRaw))) return zv::Val();
		return zv::Val::adopt(created);
	}

	/* new BooleanType() for maybe, new ConstantBooleanType(yes) otherwise
	 * — TrinaryLogic::toBooleanType(); UNDEF = pending exception */
	static zv::Val booleanTypeOf(zend_long value)
	{
		if (UNEXPECTED(value < 0)) return zv::Val();
		zval result;
		if (value == PT_TRI_MAYBE) {
			if (UNEXPECTED(!pt_boolean_type_new(&result))) return zv::Val();
		} else if (UNEXPECTED(!pt_constant_boolean_type_new(&result, value == PT_TRI_YES))) {
			return zv::Val();
		}
		return zv::Val::adopt(result);
	}

	/* the constructor's ShouldNotHappenException: 'Cannot create <class>
	 * with: <the members described at the value level>' */
	static void throwCannotCreate(zval *types)
	{
		zv::Val value = verbosityLevel(PT_VERBOSITY_LEVEL_VALUE);
		if (UNEXPECTED(value.isUndef())) return;
		smart_str message = {NULL, 0};
		smart_str_appendl(&message, "Cannot create ", 14);
		smart_str_append(&message, pt_ce_intersection_type->name);
		smart_str_appendl(&message, " with: ", 7);
		bool first = true;
		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			zval *type = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
				smart_str_free(&message);
				zend_type_error("phpstan_turbo: %s::__construct(): every member must be a %s, %s given", ZSTR_VAL(pt_ce_intersection_type->name), ptcls::type, zend_zval_value_name(type));
				return;
			}
			zv::Val description = describeOf(type, value.raw());
			if (UNEXPECTED(description.isUndef())) {
				smart_str_free(&message);
				return;
			}
			if (!first) {
				smart_str_appendl(&message, ", ", 2);
			}
			first = false;
			smart_str_append(&message, zv::Ref(description.raw()).asString());
		}
		smart_str_0(&message);
		zv::Val messageZv = zv::Val::adoptString(message.s);
		throwShouldNotHappen(messageZv.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::IntersectionType;
using phpstanturbo::NullableLong;

/* {{{ shared with the other ports (TypeTraits.h) */

zv::Val pt_intersection_of(zv::Arr types)
{
	return IntersectionType::create(zv::Val(std::move(types)));
}

bool pt_intersection_type_new(zval *out, zval *types)
{
	if (UNEXPECTED(Z_TYPE_P(types) != IS_ARRAY)) {
		zend_type_error("%s::__construct(): Argument #1 ($types) must be of type array, %s given", ZSTR_VAL(pt_ce_intersection_type->name), zend_zval_value_name(types));
		return false;
	}
	return pt_val_into(IntersectionType::create(zv::Val::copyOf(zv::Ref(types))), out);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS IntersectionType(Z_OBJ_P(ZEND_THIS))

/* (): TrinaryLogic — the intersectResults() family */
static void pt_it_trinary0(INTERNAL_FUNCTION_PARAMETERS, zend_long (IntersectionType::*method)() const)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY_OR_THROW((PT_THIS.*method)());
}

/* (string $name): TrinaryLogic */
static void pt_it_trinary_string(INTERNAL_FUNCTION_PARAMETERS, zend_long (IntersectionType::*method)(zval *) const)
{
	zend_string *name;
	if (!zp::parse<zp::Str>(execute_data, name)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	PT_RETURN_TRINARY_OR_THROW((PT_THIS.*method)(&nameZv));
}

/* (Type $type, PhpVersion $phpVersion): TrinaryLogic */
static void pt_it_trinary_type_version(INTERNAL_FUNCTION_PARAMETERS, zend_long (IntersectionType::*method)(zval *, zval *) const)
{
	zval *type, *phpVersion;
	if (!zp::parse<zp::Obj, zp::Obj>(execute_data, type, phpVersion)) RETURN_THROWS();
	PT_RETURN_TRINARY_OR_THROW((PT_THIS.*method)(type, phpVersion));
}

/* (): Type / (): array — the intersectTypes() family */
static void pt_it_value0(INTERNAL_FUNCTION_PARAMETERS, zv::Val (IntersectionType::*method)() const)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL((PT_THIS.*method)());
}

/* (object $arg): Type */
static void pt_it_value_object(INTERNAL_FUNCTION_PARAMETERS, zv::Val (IntersectionType::*method)(zval *) const)
{
	zval *arg;
	if (!zp::parse<zp::Obj>(execute_data, arg)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(arg));
}

/* (object $a, object $b): Type */
static void pt_it_value_two_objects(INTERNAL_FUNCTION_PARAMETERS, zv::Val (IntersectionType::*method)(zval *, zval *) const)
{
	zval *a, *b;
	if (!zp::parse<zp::Obj, zp::Obj>(execute_data, a, b)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(a, b));
}

/* (object $a, object $b, object $c): Type */
static void pt_it_value_three_objects(INTERNAL_FUNCTION_PARAMETERS, zv::Val (IntersectionType::*method)(zval *, zval *, zval *) const)
{
	zval *a, *b, *c;
	if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, a, b, c)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(a, b, c));
}

/* (callable $cb): Type — the callable handed on to the members as is */
static void pt_it_value_callable(INTERNAL_FUNCTION_PARAMETERS, zv::Val (IntersectionType::*method)(zval *) const)
{
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_FUNC(fci, fcc)
	ZEND_PARSE_PARAMETERS_END();
	PT_RETURN_VAL((PT_THIS.*method)(ZEND_CALL_ARG(execute_data, 1)));
}

/* getProperty() & co.: (string $name, ClassMemberAccessAnswerer $scope) →
 * the transformed member of the prototype, through the object's class */
static void pt_it_transformed_member(INTERNAL_FUNCTION_PARAMETERS, const char *prototypeLcname, size_t prototypeLen, bool isMethod)
{
	zval *name, *scope;
	if (!zp::parse<zp::Zval, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	PT_RETURN_VAL(pt_type_transformed_member(Z_OBJ_P(ZEND_THIS), prototypeLcname, prototypeLen, isMethod, name, scope));
}

/* getUnresolved*Prototype(): (string $name, ClassMemberAccessAnswerer $scope) */
static void pt_it_unresolved_prototype(INTERNAL_FUNCTION_PARAMETERS, zv::Val (IntersectionType::*method)(zval *, zval *) const)
{
	zend_string *name;
	zval *scope;
	if (!zp::parse<zp::Str, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	PT_RETURN_VAL((PT_THIS.*method)(&nameZv, scope));
}

static void ZEND_FASTCALL itNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_COPY(pt_trinary_singleton(PT_TRI_NO));
}

void pt_register_intersection_type()
{
	reg::Class cls("PHPStan\\Type\\IntersectionType");
	ptdecl::IntersectionType::declareClass(cls);
	/* the slots PT_IT_PROP_*: the sixteen explicit properties first, the
	 * promoted $types after them */
	cls.privateTypedPropertyDefaultNull("sortedTypesCache", MAY_BE_ARRAY);
	cls.privateTypedClassPropertyDefaultNull("isBoolean", ptcls::trinaryLogic);
	cls.privateTypedClassPropertyDefaultNull("isFloat", ptcls::trinaryLogic);
	cls.privateTypedClassPropertyDefaultNull("isInteger", ptcls::trinaryLogic);
	cls.privateTypedClassPropertyDefaultNull("isString", ptcls::trinaryLogic);
	cls.privateTypedClassPropertyDefaultNull("isArray", ptcls::trinaryLogic);
	cls.privateTypedClassPropertyDefaultNull("isList", ptcls::trinaryLogic);
	cls.privateTypedClassPropertyDefaultNull("isConstantArray", ptcls::trinaryLogic);
	cls.privateTypedClassPropertyDefaultNull("isOversizedArray", ptcls::trinaryLogic);
	cls.privateTypedClassPropertyDefaultNull("isOffsetAccessible", ptcls::trinaryLogic);
	cls.privateTypedClassPropertyDefaultNull("isIterableAtLeastOnce", ptcls::trinaryLogic);
	cls.privateTypedClassPropertyDefaultNull("isConstantScalarValue", ptcls::trinaryLogic);
	cls.privateTypedClassPropertyDefaultNull("isCallable", ptcls::trinaryLogic);
	cls.privateTypedArrayPropertyDefaultEmpty("cachedGetOffsetValueType");
	cls.privateTypedArrayPropertyDefaultEmpty("cachedHasOffsetValueType");
	cls.privateTypedArrayPropertyDefaultEmpty("cachedDescriptions");
	cls.privateTypedProperty("types", MAY_BE_ARRAY);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		if (!zp::parse<zp::Arr>(execute_data, types)) RETURN_THROWS();
		if (UNEXPECTED(!PT_THIS.construct(types))) RETURN_THROWS();
	});

	cls.method(sigs::getTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getTypes);
	});

	cls.method(sigs::inferTemplateTypesOn, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::inferTemplateTypesOn);
	});

	cls.method(sigs::getReferencedClasses, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getReferencedClasses);
	});
	cls.method(sigs::getObjectClassNames, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getObjectClassNames);
	});
	cls.method(sigs::getObjectClassReflections, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getObjectClassReflections);
	});
	cls.method(sigs::getArrays, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getArrays);
	});
	cls.method(sigs::getConstantArrays, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getConstantArrays);
	});
	cls.method(sigs::getConstantStrings, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getConstantStrings);
	});

	cls.method<&IntersectionType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isSuperTypeOf);
	});
	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isSubTypeOf);
	});
	cls.method<&IntersectionType::isAcceptedBy, zp::Obj, zp::Bool>(sigs::isAcceptedBy);

	cls.method<&IntersectionType::equals, zp::Obj>(sigs::equals);

	cls.method<&IntersectionType::describe, zp::Obj>(sigs::describe);

	cls.method(sigs::getTemplateType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *ancestorClassName, *templateTypeName;
		if (!zp::parse<zp::Str, zp::Str>(execute_data, ancestorClassName, templateTypeName)) RETURN_THROWS();
		zval a, b;
		ZVAL_STR(&a, ancestorClassName);
		ZVAL_STR(&b, templateTypeName);
		PT_RETURN_VAL(PT_THIS.getTemplateType(&a, &b));
	});

	cls.method(sigs::isObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isObject);
	});
	cls.method(sigs::getClassStringType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getClassStringType);
	});
	cls.method(sigs::isEnum, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isEnum);
	});
	cls.method(sigs::canAccessProperties, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::canAccessProperties);
	});
	cls.method(sigs::hasProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary_string(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::hasProperty);
	});
	cls.method(sigs::getProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedpropertyprototype"), false);
	});
	cls.method(sigs::getUnresolvedPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getUnresolvedPropertyPrototype);
	});
	cls.method(sigs::hasInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary_string(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::hasInstanceProperty);
	});
	cls.method(sigs::getInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedinstancepropertyprototype"), false);
	});
	cls.method(sigs::getUnresolvedInstancePropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getUnresolvedInstancePropertyPrototype);
	});
	cls.method(sigs::hasStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary_string(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::hasStaticProperty);
	});
	cls.method(sigs::getStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedstaticpropertyprototype"), false);
	});
	cls.method(sigs::getUnresolvedStaticPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getUnresolvedStaticPropertyPrototype);
	});
	cls.method(sigs::canCallMethods, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::canCallMethods);
	});
	cls.method(sigs::hasMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary_string(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::hasMethod);
	});
	cls.method(sigs::getMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedmethodprototype"), true);
	});
	cls.method(sigs::getUnresolvedMethodPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getUnresolvedMethodPrototype);
	});
	cls.method(sigs::canAccessConstants, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::canAccessConstants);
	});
	cls.method(sigs::hasConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary_string(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::hasConstant);
	});
	cls.method(sigs::getConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *constantName;
		if (!zp::parse<zp::Str>(execute_data, constantName)) RETURN_THROWS();
		zval nameZv;
		ZVAL_STR(&nameZv, constantName);
		PT_RETURN_VAL(PT_THIS.getConstant(&nameZv));
	});

	cls.method(sigs::isIterable, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isIterable);
	});
	cls.method(sigs::isIterableAtLeastOnce, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isIterableAtLeastOnce);
	});
	cls.method(sigs::getArraySize, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getArraySize);
	});
	cls.method(sigs::getIterableKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getIterableKeyType);
	});
	cls.method(sigs::getFirstIterableKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getFirstIterableKeyType);
	});
	cls.method(sigs::getLastIterableKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getFirstIterableKeyType);
	});
	cls.method(sigs::getIterableValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getIterableValueType);
	});
	cls.method(sigs::getFirstIterableValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getFirstIterableValueType);
	});
	cls.method(sigs::getLastIterableValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getFirstIterableValueType);
	});

	cls.method(sigs::isArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isArray);
	});
	cls.method(sigs::isConstantArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isConstantArray);
	});
	cls.method(sigs::isOversizedArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isOversizedArray);
	});
	cls.method(sigs::isList, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isList);
	});
	cls.method(sigs::isString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isString);
	});
	cls.method(sigs::isNumericString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isNumericString);
	});
	cls.method(sigs::isDecimalIntegerString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isDecimalIntegerString);
	});
	cls.method(sigs::isNonEmptyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isNonEmptyString);
	});
	cls.method(sigs::isNonFalsyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isNonFalsyString);
	});
	cls.method(sigs::isLiteralString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isLiteralString);
	});
	cls.method(sigs::isLowercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isLowercaseString);
	});
	cls.method(sigs::isUppercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isUppercaseString);
	});
	cls.method(sigs::isClassString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isClassString);
	});
	cls.method(sigs::getClassStringObjectType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getClassStringObjectType);
	});
	cls.method(sigs::getObjectTypeOrClassStringObjectType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getObjectTypeOrClassStringObjectType);
	});
	cls.method(sigs::isVoid, itNo0);
	cls.method(sigs::isScalar, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isScalar);
	});

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_two_objects(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::looseCompare);
	});

	cls.method(sigs::isOffsetAccessible, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isOffsetAccessible);
	});
	cls.method(sigs::isOffsetAccessLegal, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isOffsetAccessLegal);
	});
	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.hasOffsetValueType(offsetType));
	});
	cls.method(sigs::getOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getOffsetValueType);
	});
	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		bool unionValues = true;
		if (!zp::parse<zp::ObjOrNull, zp::Obj, zp::Opt<zp::Bool>>(execute_data, offsetType, valueType, unionValues)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.setOffsetValueType(offsetType, valueType, unionValues));
	});
	cls.method(sigs::setExistingOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_two_objects(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::setExistingOffsetValueType);
	});
	cls.method(sigs::unsetOffset, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::unsetOffset);
	});
	cls.method(sigs::getKeysArrayFiltered, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_two_objects(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getKeysArrayFiltered);
	});
	cls.method(sigs::getKeysArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getKeysArray);
	});
	cls.method(sigs::getValuesArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getValuesArray);
	});
	cls.method(sigs::chunkArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_two_objects(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::chunkArray);
	});
	cls.method(sigs::fillKeysArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::fillKeysArray);
	});
	cls.method(sigs::flipArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::flipArray);
	});
	cls.method(sigs::intersectKeyArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::intersectKeyArray);
	});
	cls.method(sigs::popArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::popArray);
	});
	cls.method(sigs::reverseArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::reverseArray);
	});
	cls.method(sigs::searchArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *needleType, *strict = NULL;
		if (!zp::parse<zp::Obj, zp::Opt<zp::ObjOrNull>>(execute_data, needleType, strict)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.searchArray(needleType, strict));
	});
	cls.method(sigs::shiftArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::shiftArray);
	});
	cls.method(sigs::shuffleArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::shuffleArray);
	});
	cls.method(sigs::sliceArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_three_objects(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::sliceArray);
	});
	cls.method(sigs::spliceArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_three_objects(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::spliceArray);
	});
	cls.method(sigs::truncateListToSize, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::truncateListToSize);
	});
	cls.method(sigs::makeListMaybe, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::makeListMaybe);
	});
	cls.method(sigs::mapValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_callable(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::mapValueType);
	});
	cls.method(sigs::mapKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_callable(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::mapKeyType);
	});
	cls.method(sigs::makeAllArrayKeysOptional, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::makeAllArrayKeysOptional);
	});
	cls.method(sigs::changeKeyCaseArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long caseArg = 0;
		bool caseIsNull = true;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_LONG_OR_NULL(caseArg, caseIsNull)
		ZEND_PARSE_PARAMETERS_END();
		zval caseZv;
		if (caseIsNull) {
			ZVAL_NULL(&caseZv);
		} else {
			ZVAL_LONG(&caseZv, caseArg);
		}
		PT_RETURN_VAL(PT_THIS.changeKeyCaseArray(&caseZv));
	});
	cls.method(sigs::filterArrayRemovingFalsey, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::filterArrayRemovingFalsey);
	});

	cls.method(sigs::getEnumCases, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getEnumCases);
	});
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getEnumCaseObject);
	});

	cls.method(sigs::isCallable, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isCallable);
	});
	cls.method(sigs::getCallableParametersAcceptors, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getCallableParametersAcceptors);
	});
	cls.method(sigs::isCloneable, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isCloneable);
	});

	cls.method(sigs::isSmallerThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary_type_version(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isSmallerThan);
	});
	cls.method(sigs::isSmallerThanOrEqual, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary_type_version(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isSmallerThanOrEqual);
	});

	cls.method(sigs::isNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isNull);
	});
	cls.method(sigs::isConstantValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isConstantValue);
	});
	cls.method(sigs::isConstantScalarValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isConstantScalarValue);
	});
	cls.method(sigs::getConstantScalarTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getConstantScalarTypes);
	});
	cls.method(sigs::getConstantScalarValues, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getConstantScalarValues);
	});
	cls.method(sigs::isTrue, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isTrue);
	});
	cls.method(sigs::isFalse, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isFalse);
	});
	cls.method(sigs::isBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isBoolean);
	});
	cls.method(sigs::isFloat, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isFloat);
	});
	cls.method(sigs::isInteger, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isInteger);
	});
	cls.method(sigs::isGreaterThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary_type_version(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isGreaterThan);
	});
	cls.method(sigs::isGreaterThanOrEqual, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_trinary_type_version(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::isGreaterThanOrEqual);
	});

	cls.method(sigs::getSmallerType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getSmallerType);
	});
	cls.method(sigs::getSmallerOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getSmallerOrEqualType);
	});
	cls.method(sigs::getGreaterType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getGreaterType);
	});
	cls.method(sigs::getGreaterOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getGreaterOrEqualType);
	});

	cls.method(sigs::toBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::toBoolean);
	});
	cls.method(sigs::toNumber, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::toNumber);
	});
	cls.method(sigs::toBitwiseNotType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::toBitwiseNotType);
	});
	cls.method(sigs::toGetClassResultType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::toGetClassResultType);
	});
	cls.method(sigs::toClassConstantType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::toClassConstantType);
	});
	cls.method(sigs::toObjectTypeForInstanceofCheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::toObjectTypeForInstanceofCheck);
	});
	cls.method<&IntersectionType::toObjectTypeForIsACheck, zp::Obj, zp::Bool, zp::Bool>(sigs::toObjectTypeForIsACheck);
	cls.method(sigs::toAbsoluteNumber, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::toAbsoluteNumber);
	});
	cls.method(sigs::toString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::toString);
	});
	cls.method(sigs::toInteger, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::toInteger);
	});
	cls.method(sigs::toFloat, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::toFloat);
	});
	cls.method(sigs::toArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::toArray);
	});
	cls.method(sigs::toArrayKey, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::toArrayKey);
	});
	cls.method<&IntersectionType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::inferTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::inferTemplateTypes);
	});
	cls.method(sigs::getReferencedTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getReferencedTemplateTypes);
	});

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});
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

	cls.method(sigs::tryRemove, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::tryRemove);
	});
	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::exponentiate);
	});
	cls.method(sigs::getFiniteTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::getFiniteTypes);
	});

	cls.method(sigs::toPhpDocNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_it_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntersectionType::toPhpDocNode);
	});
	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		int has = PT_THIS.hasTemplateOrLateResolvableType();
		if (UNEXPECTED(has < 0)) RETURN_THROWS();
		RETURN_BOOL(has == 1);
	});

	/* the traits, in the twin's `use` order: the class body above wins over
	 * every name it declares (tryRemove) */
	ptdecl::IntersectionType::registerTraits(cls);

	cls.shadow(&pt_ce_intersection_type);
}

/* }}} */
