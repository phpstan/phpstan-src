/*
 * PHPStanTurbo\ValueSet — native implementation of PHPStan\Type\ValueSet.
 *
 * Declared as PHPStan\Type\ValueSet itself at activation: final, no parent,
 * no interfaces. State is the twin's fifteen private typed properties, as
 * declared slots, so the std object handlers do GC/clone. The twin's
 * private constructor is not declared: the sets are made by the factories
 * only, natively; an uninitialized slot reads as the twin's default.
 *
 * The twin's private methods live only here, in ValueSetOps, method for
 * method. LooseComparisonHelper stays PHP: compareValues() and the
 * numeric-string parsers are called there.
 */

#include "TypeTraits.h"
#include "IetrSupport.h"
#include "generated/ValueSet.h"
#include "generated/NumberSet.h"

#include <cmath>
#include <limits>
#include <vector>

namespace slots = ptdecl::ValueSet::slot;
namespace sigs = ptdecl::ValueSet::sig;
namespace numberSlots = ptdecl::NumberSet::slot;

static zend_class_entry *pt_ce_value_set = nullptr;

namespace phpstanturbo {

using namespace phpstanturbo::ietr;

inline constexpr Method mGetEnumCases{"getEnumCases"};
inline constexpr Method mIsExplicit{"isExplicit"};
inline constexpr Method mUnion{"union"};
inline constexpr Method mIntersect{"intersect"};
inline constexpr Method mPower{"power"};

/* the fields of a set being made, owned; UNDEF reads as the twin's null / [] */
struct ValueSetFields
{
	bool unknown = false;
	bool null_ = false;
	bool false_ = false;
	bool true_ = false;
	zv::Val numbers;
	zv::Val strings;
	bool emptyArray = false;
	bool nonEmptyArray = false;
	zv::Val arrays;
	zv::Val objects;
	bool resources = false;
	bool invalid = false;
	bool benevolent = false;
	zv::Val never;
	zv::Val unknownType;
};

/* a borrowed view of a NumberSet's slots */
class NumbersView
{
public:
	explicit NumbersView(zval *numberSet) : o(Z_OBJ_P(numberSet)) {}

	HashTable *integerIntervals() const { return table(numberSlots::integerIntervals); }
	HashTable *floatIntervals() const { return table(numberSlots::floatIntervals); }
	bool nan() const { return Z_TYPE_P(OBJ_PROP_NUM(o, numberSlots::nan)) == IS_TRUE; }
	uint32_t integerCount() const { return zend_hash_num_elements(integerIntervals()); }
	uint32_t floatCount() const { return zend_hash_num_elements(floatIntervals()); }
	bool isEmpty() const { return integerCount() == 0 && floatCount() == 0 && !nan(); }

private:
	zend_object *o;

	HashTable *table(uint32_t slot) const
	{
		zval *value = OBJ_PROP_NUM(o, slot);
		return Z_TYPE_P(value) == IS_ARRAY ? Z_ARRVAL_P(value) : (HashTable *) &zend_empty_array;
	}
};

/* a borrowed view of a ValueSet's slots */
class ValueSetView
{
public:
	explicit ValueSetView(zend_object *o) : o(o) {}

	zend_object *object() const { return o; }
	bool unknown() const { return flag(slots::unknown); }
	bool null_() const { return flag(slots::null); }
	bool false_() const { return flag(slots::false_); }
	bool true_() const { return flag(slots::true_); }
	zval *numbers() const { return objectOrNull(slots::numbers); }
	/* the StringSet, or nullptr */
	zval *strings() const { return objectOrNull(slots::strings); }
	/* $this->strings?->getConstantStrings() ?? [], borrowed ConstantStringType objects */
	HashTable *constantStrings() const
	{
		zval *set = strings();
		return set == nullptr ? (HashTable *) &zend_empty_array : pt_string_set_constants(set);
	}
	/* $this->strings?->getType() */
	zval *stringType() const
	{
		zval *set = strings();
		return set == nullptr ? nullptr : pt_string_set_type(set);
	}
	bool emptyArray() const { return flag(slots::emptyArray); }
	bool nonEmptyArray() const { return flag(slots::nonEmptyArray); }
	zval *arrays() const { return objectOrNull(slots::arrays); }
	zval *objects() const { return objectOrNull(slots::objects); }
	bool resources() const { return flag(slots::resources); }
	bool invalid() const { return flag(slots::invalid); }
	bool benevolent() const { return flag(slots::benevolent); }
	zval *never() const { return objectOrNull(slots::never); }
	zval *unknownType() const { return objectOrNull(slots::unknownType); }

private:
	zend_object *o;

	bool flag(uint32_t slot) const { return Z_TYPE_P(OBJ_PROP_NUM(o, slot)) == IS_TRUE; }
	zval *objectOrNull(uint32_t slot) const
	{
		zval *value = OBJ_PROP_NUM(o, slot);
		return Z_TYPE_P(value) == IS_OBJECT ? value : nullptr;
	}
};

static void writeFlag(zend_object *o, uint32_t slot, bool value)
{
	zval *target = OBJ_PROP_NUM(o, slot);
	zval_ptr_dtor(target);
	ZVAL_BOOL(target, value);
	Z_PROP_FLAG_P(target) = 0;
}

static void writeValue(zend_object *o, uint32_t slot, zv::Val &value, bool array)
{
	zval *target = OBJ_PROP_NUM(o, slot);
	zval_ptr_dtor(target);
	if (value.isUndef() || Z_TYPE_P(value.raw()) == IS_NULL) {
		if (array) {
			ZVAL_EMPTY_ARRAY(target);
		} else {
			ZVAL_NULL(target);
		}
	} else {
		zval taken = value.take();
		ZVAL_COPY_VALUE(target, &taken);
	}
	Z_PROP_FLAG_P(target) = 0;
}

/* new self(...$fields); UNDEF = pending exception */
static zv::Val newValueSet(ValueSetFields &fields)
{
	zval object;
	if (UNEXPECTED(object_init_ex(&object, pt_ce_value_set) != SUCCESS)) return zv::Val();
	zend_object *o = Z_OBJ(object);
	writeFlag(o, slots::unknown, fields.unknown);
	writeFlag(o, slots::null, fields.null_);
	writeFlag(o, slots::false_, fields.false_);
	writeFlag(o, slots::true_, fields.true_);
	writeValue(o, slots::numbers, fields.numbers, false);
	writeValue(o, slots::strings, fields.strings, false);
	writeFlag(o, slots::emptyArray, fields.emptyArray);
	writeFlag(o, slots::nonEmptyArray, fields.nonEmptyArray);
	writeValue(o, slots::arrays, fields.arrays, false);
	writeValue(o, slots::objects, fields.objects, false);
	writeFlag(o, slots::resources, fields.resources);
	writeFlag(o, slots::invalid, fields.invalid);
	writeFlag(o, slots::benevolent, fields.benevolent);
	writeValue(o, slots::never, fields.never, false);
	writeValue(o, slots::unknownType, fields.unknownType, false);
	return zv::Val::adopt(object);
}

static zv::Val copyOrNull(zval *value)
{
	return value == nullptr ? zv::Val::null() : zv::Val::copyOf(zv::Ref(value));
}

/* an owned set, or the borrowed operand it stands for */
struct SetRef
{
	zv::Val owned;
	zend_object *object;

	ValueSetView view() const { return ValueSetView(object); }
};

/* Mirrors the private methods of the twin. */
class ValueSetOps
{
public:
	static constexpr zend_long EXACT_FLOAT_INTEGER_LIMIT = 9007199254740992LL;
	static constexpr zend_long EXACT_FLOAT_DIGITS = 17;
	static constexpr zend_long SHORT_FLOAT_DIGITS = 15;

	enum Op : uint8_t
	{
		OP_PLUS,
		OP_MINUS,
		OP_MUL,
		OP_DIV,
		OP_MOD,
		OP_POW,
		OP_SHIFT_LEFT,
		OP_SHIFT_RIGHT,
		OP_AND,
		OP_OR,
		OP_XOR,
	};

	/* {{{ factories */

	static zv::Val createUnknown(zval *type)
	{
		ValueSetFields f;
		f.unknown = true;
		f.unknownType = copyOrNull(type);
		return newValueSet(f);
	}

	static zv::Val createEmpty()
	{
		ValueSetFields f;
		return newValueSet(f);
	}

	static zv::Val createInvalid()
	{
		ValueSetFields f;
		f.unknown = true;
		f.invalid = true;
		return newValueSet(f);
	}

	static zv::Val createNever(zval *never)
	{
		ValueSetFields f;
		f.never = zv::Val::copyOf(zv::Ref(never));
		return newValueSet(f);
	}

	static zv::Val createNull()
	{
		ValueSetFields f;
		f.null_ = true;
		return newValueSet(f);
	}

	static zv::Val createBooleans(bool falseValue, bool trueValue)
	{
		ValueSetFields f;
		f.false_ = falseValue;
		f.true_ = trueValue;
		return newValueSet(f);
	}

	static zv::Val createNumbers(zv::Val numbers)
	{
		ValueSetFields f;
		f.numbers = std::move(numbers);
		return newValueSet(f);
	}

	static zv::Val createNumbersOf(const NumberSetData &data)
	{
		IETR_VAL(numbers, pt_number_set_new(data));
		return createNumbers(std::move(numbers));
	}

	static zv::Val createConstantString(zval *string)
	{
		ValueSetFields f;
		f.strings = pt_string_set_create_constant(string);
		if (UNEXPECTED(f.strings.isUndef())) return zv::Val();
		return newValueSet(f);
	}

	static zv::Val createStrings(zval *strings)
	{
		ValueSetFields f;
		f.strings = pt_string_set_create_type(strings);
		if (UNEXPECTED(f.strings.isUndef())) return zv::Val();
		return newValueSet(f);
	}

	static zv::Val createArrays(zval *arrays)
	{
		zend_long isIterableAtLeastOnce = triOn(arrays, mIsIterableAtLeastOnce);
		if (UNEXPECTED(isIterableAtLeastOnce < 0)) return zv::Val();
		ValueSetFields f;
		f.emptyArray = isIterableAtLeastOnce != PT_TRI_YES;
		f.nonEmptyArray = isIterableAtLeastOnce != PT_TRI_NO;
		f.arrays = zv::Val::copyOf(zv::Ref(arrays));
		return newValueSet(f);
	}

	static zv::Val createObjects(zval *objects)
	{
		ValueSetFields f;
		f.objects = zv::Val::copyOf(zv::Ref(objects));
		return newValueSet(f);
	}

	static zv::Val createResources()
	{
		ValueSetFields f;
		f.resources = true;
		return newValueSet(f);
	}

	static zv::Val toBenevolent(zend_object *self)
	{
		zend_object *clone = self->handlers->clone_obj(self);
		if (UNEXPECTED(EG(exception) != NULL)) {
			if (clone != NULL) OBJ_RELEASE(clone);
			return zv::Val();
		}
		writeFlag(clone, slots::benevolent, true);
		zval result;
		ZVAL_OBJ(&result, clone);
		return zv::Val::adopt(result);
	}

	/* }}} */

	/* {{{ queries */

	static bool numbersEmpty(zval *numbers)
	{
		return numbers == nullptr || NumbersView(numbers).isEmpty();
	}

	static bool isEmpty(ValueSetView s)
	{
		return !s.unknown()
			&& !s.null_()
			&& !s.false_()
			&& !s.true_()
			&& numbersEmpty(s.numbers())
			&& s.strings() == nullptr
			&& !s.emptyArray()
			&& !s.nonEmptyArray()
			&& s.objects() == nullptr
			&& !s.resources();
	}

	static bool isPlusable(ValueSetView s)
	{
		return !s.unknown() && !s.null_() && s.objects() == nullptr && !s.resources() && !isEmpty(s);
	}

	static bool isUnknown(ValueSetView s)
	{
		return s.unknown() && !s.invalid();
	}

	static bool mayBeString(ValueSetView s)
	{
		return s.unknown() || s.strings() != nullptr;
	}

	static bool mayBeArray(ValueSetView s)
	{
		return s.unknown() || s.emptyArray() || s.nonEmptyArray();
	}

	static bool mayBeNonArray(ValueSetView s)
	{
		return s.unknown()
			|| s.null_()
			|| s.false_()
			|| s.true_()
			|| s.numbers() != nullptr
			|| s.strings() != nullptr
			|| s.objects() != nullptr
			|| s.resources();
	}

	static bool mayBeNonNumber(ValueSetView s)
	{
		return s.unknown()
			|| s.null_()
			|| s.false_()
			|| s.true_()
			|| s.strings() != nullptr
			|| mayBeArray(s)
			|| s.objects() != nullptr
			|| s.resources();
	}

	static bool isOnlyOneIntegerInterval(ValueSetView s)
	{
		if (s.numbers() == nullptr) return false;
		NumbersView n(s.numbers());
		return n.integerCount() == 1 && n.floatCount() == 0 && !n.nan() && !mayBeNonNumber(s);
	}

	static bool isOnlyIntegers(ValueSetView s)
	{
		if (s.numbers() == nullptr) return false;
		NumbersView n(s.numbers());
		return n.floatCount() == 0 && !n.nan() && !mayBeNonNumber(s);
	}

	static bool isOnlyStrings(ValueSetView s)
	{
		return mayBeString(s)
			&& !s.unknown()
			&& !s.null_()
			&& !s.false_()
			&& !s.true_()
			&& s.numbers() == nullptr
			&& !mayBeArray(s)
			&& s.objects() == nullptr
			&& !s.resources();
	}

	static bool isOnlyNonEmptyArrays(ValueSetView s)
	{
		return s.nonEmptyArray() && !s.emptyArray() && !mayBeNonArray(s);
	}

	/* PT_TRI_*; -1 = pending exception */
	static zend_long isArray(ValueSetView s)
	{
		if (s.unknown()) {
			if (s.unknownType() == nullptr) return PT_TRI_MAYBE;
			return triOn(s.unknownType(), mIsArray);
		}
		if (mayBeArray(s) && mayBeNonArray(s)) return PT_TRI_MAYBE;
		return mayBeArray(s) ? PT_TRI_YES : PT_TRI_NO;
	}

	/* }}} */

	/* {{{ union() / intersect() */

	static zv::Val unionTypes(zval *left, zval *right)
	{
		if (left == nullptr || right == nullptr) return copyOrNull(left != nullptr ? left : right);
		return union2(left, right);
	}

	static zv::Val union_(ValueSetView a, ValueSetView b)
	{
		if (a.unknown() || b.unknown()) {
			ValueSetFields f;
			f.unknown = true;
			f.invalid = a.invalid() || b.invalid();
			f.benevolent = a.benevolent() || b.benevolent();
			if (a.unknown() && b.unknown() && a.unknownType() != nullptr && b.unknownType() != nullptr) {
				f.unknownType = union2(a.unknownType(), b.unknownType());
				if (UNEXPECTED(f.unknownType.isUndef())) return zv::Val();
			}
			return newValueSet(f);
		}

		ValueSetFields f;
		f.null_ = a.null_() || b.null_();
		f.false_ = a.false_() || b.false_();
		f.true_ = a.true_() || b.true_();
		if (a.numbers() == nullptr || b.numbers() == nullptr) {
			f.numbers = copyOrNull(a.numbers() != nullptr ? a.numbers() : b.numbers());
		} else {
			f.numbers = callOn(a.numbers(), mUnion, 1, b.numbers());
			if (UNEXPECTED(f.numbers.isUndef())) return zv::Val();
		}
		if (a.strings() == nullptr || b.strings() == nullptr) {
			f.strings = copyOrNull(a.strings() != nullptr ? a.strings() : b.strings());
		} else {
			f.strings = pt_string_set_union(a.strings(), b.strings());
			if (UNEXPECTED(f.strings.isUndef())) return zv::Val();
		}
		f.emptyArray = a.emptyArray() || b.emptyArray();
		f.nonEmptyArray = a.nonEmptyArray() || b.nonEmptyArray();
		f.arrays = unionTypes(a.arrays(), b.arrays());
		if (UNEXPECTED(f.arrays.isUndef())) return zv::Val();
		f.objects = unionTypes(a.objects(), b.objects());
		if (UNEXPECTED(f.objects.isUndef())) return zv::Val();
		f.resources = a.resources() || b.resources();
		f.benevolent = a.benevolent() || b.benevolent();
		f.never = copyOrNull(a.never() != nullptr ? a.never() : b.never());
		return newValueSet(f);
	}

	static zv::Val intersectTypes(zval *left, zval *right)
	{
		if (left == nullptr || right == nullptr) return zv::Val::null();
		zv::Args argv{left, right};
		return pt_type_combinator_intersect(2, argv);
	}

	static zv::Val intersect(ValueSetView a, ValueSetView b)
	{
		if (a.unknown()) return objectValue(b);
		if (b.unknown()) return objectValue(a);

		ValueSetFields f;
		f.null_ = a.null_() && b.null_();
		f.false_ = a.false_() && b.false_();
		f.true_ = a.true_() && b.true_();
		if (a.numbers() != nullptr && b.numbers() != nullptr) {
			f.numbers = callOn(a.numbers(), mIntersect, 1, b.numbers());
			if (UNEXPECTED(f.numbers.isUndef())) return zv::Val();
		}
		if (a.strings() != nullptr && b.strings() != nullptr) {
			IETR_VAL(strings, pt_string_set_intersect(a.strings(), b.strings()));
			if (!pt_string_set_is_empty(strings.raw())) f.strings = std::move(strings);
		}
		f.emptyArray = a.emptyArray() && b.emptyArray();
		f.nonEmptyArray = a.nonEmptyArray() && b.nonEmptyArray();
		f.arrays = intersectTypes(a.arrays(), b.arrays());
		if (UNEXPECTED(f.arrays.isUndef())) return zv::Val();
		f.objects = intersectTypes(a.objects(), b.objects());
		if (UNEXPECTED(f.objects.isUndef())) return zv::Val();
		f.resources = a.resources() && b.resources();
		f.benevolent = a.benevolent() || b.benevolent();
		f.never = copyOrNull(a.never() != nullptr ? a.never() : b.never());
		return newValueSet(f);
	}

	/* }}} */

	/* {{{ type construction */

	static zv::Val objectWithoutClassType()
	{
		zval z;
		return adopted(pt_object_without_class_type_new(&z), z);
	}

	static zv::Val resourceType()
	{
		zval z;
		return adopted(pt_resource_type_new(&z), z);
	}

	static zv::Val emptyConstantArrayType()
	{
		zval keys, values, z;
		ZVAL_EMPTY_ARRAY(&keys);
		ZVAL_EMPTY_ARRAY(&values);
		return adopted(pt_constant_array_type_new(&z, &keys, &values), z);
	}

	static zv::Val mixedArrayType()
	{
		IETR_VAL(keyType, newMixedType());
		IETR_VAL(itemType, newMixedType());
		return newArrayType(keyType.raw(), itemType.raw());
	}

	static zv::Val nonEmptyArrayType()
	{
		zval z;
		return adopted(pt_non_empty_array_type_new(&z), z);
	}

	static zv::Val constantStringLiteral(const char *value, size_t len)
	{
		zend_string *string = zend_string_init(value, len, 0);
		zv::Val type = newConstantStringType(string);
		zend_string_release(string);
		return type;
	}

	/* createNumericStringType() */
	static zv::Val createNumericStringType()
	{
		IETR_VAL(stringType, newStringType());
		zval accessory;
		if (UNEXPECTED(!pt_accessory_numeric_string_type_new(&accessory))) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(stringType));
		types.push(zv::Val::adopt(accessory));
		return newIntersectionType(types);
	}

	/* new BenevolentUnionType / UnionType of the types */
	static zv::Val newUnionOf(bool benevolent, std::initializer_list<zv::Val *> members)
	{
		zv::Arr types = zv::Arr::create((uint32_t) members.size());
		for (zv::Val *member : members) {
			if (UNEXPECTED(member->isUndef())) return zv::Val();
			types.push(std::move(*member));
		}
		return benevolent ? newBenevolentUnionType(types) : newUnionType(types);
	}

	static zv::Val versionId(zval *phpVersion, zend_long &out)
	{
		zv::Val id = pt_php_version_get_version_id(phpVersion);
		if (UNEXPECTED(id.isUndef())) return zv::Val();
		out = zval_get_long(id.raw());
		return id;
	}

	/* createNumberLikeObjectType() */
	static zv::Val createNumberLikeObjectType(zval *phpVersion)
	{
		zend_long id;
		if (UNEXPECTED(versionId(phpVersion, id).isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(3);
		IETR_VAL(gmp, newObjectTypeLiteral(PT_LC("GMP")));
		types.push(std::move(gmp));
		IETR_VAL(simpleXml, newObjectTypeLiteral(PT_LC("SimpleXMLElement")));
		types.push(std::move(simpleXml));
		if (id >= 80400) {
			IETR_VAL(number, newObjectTypeLiteral(PT_LC("BcMath\\Number")));
			types.push(std::move(number));
		}
		return unionOf(types);
	}

	/* createStringableObjectType() */
	static zv::Val createStringableObjectType(zval *phpVersion)
	{
		zend_long id;
		if (UNEXPECTED(versionId(phpVersion, id).isUndef())) return zv::Val();
		if (id < 80000) return objectWithoutClassType();
		IETR_VAL(stringable, newObjectTypeLiteral(PT_LC("Stringable")));
		IETR_VAL(gmp, newObjectTypeLiteral(PT_LC("GMP")));
		return union2(stringable.raw(), gmp.raw());
	}

	/* createTypeOfNumbers() */
	static zv::Val createTypeOfNumbers(const NumberSetData &numbers)
	{
		zv::Arr types = zv::Arr::create(4);
		for (const NumberSetData::IntegerInterval &interval : numbers.integerIntervals) {
			IETR_VAL(rangeType, fromInterval(interval.min, interval.max));
			types.push(std::move(rangeType));
		}
		bool nan = numbers.nan;
		for (const NumberSetData::FloatInterval &interval : numbers.floatIntervals) {
			if (nan && interval.min == -HUGE_VAL && interval.max == HUGE_VAL) {
				IETR_VAL(floatType, newFloatType());
				types.push(std::move(floatType));
				nan = false;
				continue;
			}
			IETR_VAL(floatType, interval.min == interval.max ? newConstantFloatType(interval.min) : pt_float_range_from_interval(interval.min, interval.max));
			types.push(std::move(floatType));
		}
		if (nan) {
			IETR_VAL(nanType, newConstantFloatType(std::numeric_limits<double>::quiet_NaN()));
			types.push(std::move(nanType));
		}
		return unionOf(types);
	}

	/* }}} */

	/* {{{ numbers */

	static bool readNumbers(zval *numberSet, NumberSetData &out)
	{
		return pt_number_set_read(Z_OBJ_P(numberSet), out);
	}

	static NumberSetData anyInteger()
	{
		NumberSetData data;
		data.integerIntervals.push_back({NullableLong::null(), NullableLong::null()});
		return data;
	}

	static bool isOnlyFloatNumbers(const NumberSetData &numbers)
	{
		return numbers.integerIntervals.empty() && (!numbers.floatIntervals.empty() || numbers.nan);
	}

	static bool isOnlyFloats(ValueSetView s)
	{
		if (s.numbers() == nullptr || mayBeNonNumber(s)) return false;
		NumbersView n(s.numbers());
		return n.integerCount() == 0 && (n.floatCount() > 0 || n.nan());
	}

	static bool isOnlyInteger(const NumberSetData &numbers, zend_long value)
	{
		return numbers.integerIntervals.size() == 1
			&& !numbers.integerIntervals[0].min.isNull
			&& !numbers.integerIntervals[0].max.isNull
			&& numbers.integerIntervals[0].min.value == value
			&& numbers.integerIntervals[0].max.value == value
			&& numbers.floatIntervals.empty()
			&& !numbers.nan;
	}

	static bool sameIntegerInterval(const NumberSetData::IntegerInterval &a, const NumberSetData::IntegerInterval &b)
	{
		return a.min.isNull == b.min.isNull && (a.min.isNull || a.min.value == b.min.value)
			&& a.max.isNull == b.max.isNull && (a.max.isNull || a.max.value == b.max.value);
	}

	/* $type->toNumberSet() read; has = false for null; false = pending exception */
	static bool typeNumbers(zval *type, NumberSetData &out, bool &has)
	{
		return pt_type_to_number_set(type, out, has);
	}

	/* $type->toNumber()->toNumberSet() (or ->toInteger()) */
	static bool convertedNumbers(zval *type, const Method &conversion, NumberSetData &out, bool &has)
	{
		zv::Val converted = callOn(type, conversion);
		if (UNEXPECTED(converted.isUndef())) return false;
		return typeNumbers(converted.raw(), out, has);
	}

	/* toNumberSetOfNumber(); has = false for null */
	static bool toNumberSetOfNumber(ValueSetView s, NumberSetData &out, bool &has)
	{
		has = false;
		if (s.null_() || s.false_()) out.integerIntervals.push_back({NullableLong::of(0), NullableLong::of(0)});
		if (s.true_()) out.integerIntervals.push_back({NullableLong::of(1), NullableLong::of(1)});
		if (s.numbers() != nullptr && UNEXPECTED(!readNumbers(s.numbers(), out))) return false;
		zval *entry;
		ZEND_HASH_FOREACH_VAL(s.constantStrings(), entry) {
			bool hasNumbers;
			if (UNEXPECTED(!convertedNumbers(entry, mToNumber, out, hasNumbers))) return false;
			if (!hasNumbers) return true;
		} ZEND_HASH_FOREACH_END();
		if (s.stringType() != nullptr) {
			bool hasNumbers;
			if (UNEXPECTED(!convertedNumbers(s.stringType(), mToNumber, out, hasNumbers))) return false;
			if (!hasNumbers) return true;
		}
		has = true;
		return true;
	}

	/* toIntegerNumbers() */
	static bool toIntegerNumbers(const NumberSetData &numbers, NumberSetData &out)
	{
		out.integerIntervals.insert(out.integerIntervals.end(), numbers.integerIntervals.begin(), numbers.integerIntervals.end());
		std::vector<zv::Val> floatTypes;
		for (const NumberSetData::FloatInterval &interval : numbers.floatIntervals) {
			zv::Val floatType = interval.min == interval.max ? newConstantFloatType(interval.min) : pt_float_range_from_interval(interval.min, interval.max);
			if (UNEXPECTED(floatType.isUndef())) return false;
			floatTypes.push_back(std::move(floatType));
		}
		if (numbers.nan) {
			zv::Val nanType = newConstantFloatType(std::numeric_limits<double>::quiet_NaN());
			if (UNEXPECTED(nanType.isUndef())) return false;
			floatTypes.push_back(std::move(nanType));
		}
		for (zv::Val &floatType : floatTypes) {
			NumberSetData integers;
			bool has;
			if (UNEXPECTED(!convertedNumbers(floatType.raw(), mToInteger, integers, has))) return false;
			if (!has) integers = anyInteger();
			out.integerIntervals.insert(out.integerIntervals.end(), integers.integerIntervals.begin(), integers.integerIntervals.end());
			out.floatIntervals.insert(out.floatIntervals.end(), integers.floatIntervals.begin(), integers.floatIntervals.end());
			out.nan = out.nan || integers.nan;
		}
		return true;
	}

	/* toOperandNumbers(); has = false for null */
	static bool toOperandNumbers(ValueSetView s, bool withoutArrays, bool integers, NumberSetData &out, bool &has)
	{
		has = false;
		if (s.unknown()) {
			out.integerIntervals.push_back({NullableLong::null(), NullableLong::null()});
			if (!integers) {
				out.floatIntervals.push_back({-HUGE_VAL, HUGE_VAL});
				out.nan = true;
			}
			has = true;
			return true;
		}
		if (((!withoutArrays && (s.emptyArray() || s.nonEmptyArray())) || s.resources()) && !s.benevolent()) return true;

		std::vector<NumberSetData> sets;
		if (s.null_() || s.false_()) {
			NumberSetData zero;
			zero.integerIntervals.push_back({NullableLong::of(0), NullableLong::of(0)});
			sets.push_back(std::move(zero));
		}
		if (s.true_()) {
			NumberSetData one;
			one.integerIntervals.push_back({NullableLong::of(1), NullableLong::of(1)});
			sets.push_back(std::move(one));
		}
		if (s.numbers() != nullptr) {
			NumberSetData numbers;
			if (UNEXPECTED(!readNumbers(s.numbers(), numbers))) return false;
			if (integers) {
				NumberSetData integerNumbers;
				if (UNEXPECTED(!toIntegerNumbers(numbers, integerNumbers))) return false;
				sets.push_back(std::move(integerNumbers));
			} else {
				sets.push_back(std::move(numbers));
			}
		}

		std::vector<zv::Val> types;
		zval *entry;
		ZEND_HASH_FOREACH_VAL(s.constantStrings(), entry) {
			types.push_back(zv::Val::copyOf(zv::Ref(entry)));
		} ZEND_HASH_FOREACH_END();
		if (s.stringType() != nullptr) types.push_back(zv::Val::copyOf(zv::Ref(s.stringType())));
		if (s.objects() != nullptr) types.push_back(zv::Val::copyOf(zv::Ref(s.objects())));
		for (zv::Val &type : types) {
			NumberSetData numbers;
			bool hasNumbers;
			if (UNEXPECTED(!convertedNumbers(type.raw(), mToNumber, numbers, hasNumbers))) return false;
			if (!hasNumbers) {
				if (s.benevolent()) continue;
				return true;
			}
			if (integers) {
				NumberSetData integerNumbers;
				bool hasIntegers;
				if (UNEXPECTED(!convertedNumbers(type.raw(), mToInteger, integerNumbers, hasIntegers))) return false;
				sets.push_back(hasIntegers ? std::move(integerNumbers) : anyInteger());
			} else {
				sets.push_back(std::move(numbers));
			}
		}

		if (sets.empty()) return true;

		// a value of two kinds that read as the same number is one member
		for (const NumberSetData &set : sets) {
			for (const NumberSetData::IntegerInterval &interval : set.integerIntervals) {
				bool seen = false;
				for (const NumberSetData::IntegerInterval &known : out.integerIntervals) {
					if (sameIntegerInterval(known, interval)) {
						seen = true;
						break;
					}
				}
				if (!seen) out.integerIntervals.push_back(interval);
			}
			for (const NumberSetData::FloatInterval &interval : set.floatIntervals) {
				bool seen = false;
				for (const NumberSetData::FloatInterval &known : out.floatIntervals) {
					if (known.min == interval.min && known.max == interval.max) {
						seen = true;
						break;
					}
				}
				if (!seen) out.floatIntervals.push_back(interval);
			}
			out.nan = out.nan || set.nan;
		}
		has = true;
		return true;
	}

	/* getIntegerNumbers(): the NumberSet itself, or nullptr */
	static zval *getIntegerNumbers(ValueSetView s)
	{
		zval *numbers = s.numbers();
		if (numbers == nullptr) return nullptr;
		NumbersView n(numbers);
		if (n.integerCount() == 0 || n.floatCount() > 0 || n.nan() || mayBeNonNumber(s)) return nullptr;
		return numbers;
	}

	/* }}} */

	/* {{{ constants */

	/* getConstantNumber(): IS_LONG / IS_DOUBLE, IS_NULL for none; UNDEF = pending exception */
	static zv::Val getConstantNumber(zval *numberType)
	{
		IETR_VAL(values, callOn(numberType, mGetConstantScalarValues));
		HashTable *ht = Z_ARRVAL_P(values.raw());
		if (zend_hash_num_elements(ht) != 1) return zv::Val::null();
		zval *value = zend_hash_index_find(ht, 0);
		if (value == NULL || (Z_TYPE_P(value) != IS_LONG && Z_TYPE_P(value) != IS_DOUBLE)) return zv::Val::null();
		return zv::Val::copyOf(zv::Ref(value));
	}

	static zv::Val getConstantInteger(zval *integerType)
	{
		IETR_VAL(values, callOn(integerType, mGetConstantScalarValues));
		HashTable *ht = Z_ARRVAL_P(values.raw());
		if (zend_hash_num_elements(ht) != 1) return zv::Val::null();
		zval *value = zend_hash_index_find(ht, 0);
		if (value == NULL || Z_TYPE_P(value) != IS_LONG) return zv::Val::null();
		return zv::Val::copyOf(zv::Ref(value));
	}

	/* in_array($number, [0, 0.0], true) */
	static bool isZero(zval *number)
	{
		return (Z_TYPE_P(number) == IS_LONG && Z_LVAL_P(number) == 0) || (Z_TYPE_P(number) == IS_DOUBLE && Z_DVAL_P(number) == 0.0);
	}

	/* getFiniteScalarTypes(); finite = false for null; false = pending exception */
	static bool getFiniteScalarTypes(ValueSetView s, bool expandIntegerIntervals, std::vector<zv::Val> &types, bool &finite)
	{
		finite = false;
		if (s.unknown() || s.stringType() != nullptr || s.emptyArray() || s.nonEmptyArray() || s.objects() != nullptr || s.resources()) return true;

		if (s.null_()) {
			zv::Val nullType = newNullType();
			if (UNEXPECTED(nullType.isUndef())) return false;
			types.push_back(std::move(nullType));
		}
		if (s.false_()) {
			zv::Val falseType = newConstantBooleanType(false);
			if (UNEXPECTED(falseType.isUndef())) return false;
			types.push_back(std::move(falseType));
		}
		if (s.true_()) {
			zv::Val trueType = newConstantBooleanType(true);
			if (UNEXPECTED(trueType.isUndef())) return false;
			types.push_back(std::move(trueType));
		}
		if (s.numbers() != nullptr) {
			NumberSetData numbers;
			if (UNEXPECTED(!readNumbers(s.numbers(), numbers))) return false;
			for (const NumberSetData::IntegerInterval &interval : numbers.integerIntervals) {
				if (interval.min.isNull || interval.max.isNull) return true;
				zend_long min = interval.min.value;
				zend_long max = interval.max.value;
				if (min != max) {
					zend_long width;
					bool overflows = __builtin_sub_overflow(max, min, &width);
					// only a range on its own is listed, as IntegerRangeType::getFiniteTypes() lists it
					if (!expandIntegerIntervals || overflows || width > PT_IETR_CALCULATE_SCALARS_LIMIT || !isOnlyOneIntegerInterval(s)) return true;
				}
				for (zend_long i = 0; i <= max - min; i++) {
					zv::Val constantType = newConstantIntegerType(min + i);
					if (UNEXPECTED(constantType.isUndef())) return false;
					types.push_back(std::move(constantType));
				}
			}
			for (const NumberSetData::FloatInterval &interval : numbers.floatIntervals) {
				if (interval.min != interval.max) return true;
				zv::Val constantType = newConstantFloatType(interval.min);
				if (UNEXPECTED(constantType.isUndef())) return false;
				types.push_back(std::move(constantType));
			}
			if (numbers.nan) {
				zv::Val nanType = newConstantFloatType(std::numeric_limits<double>::quiet_NaN());
				if (UNEXPECTED(nanType.isUndef())) return false;
				types.push_back(std::move(nanType));
			}
		}
		zval *entry;
		ZEND_HASH_FOREACH_VAL(s.constantStrings(), entry) {
			types.push_back(zv::Val::copyOf(zv::Ref(entry)));
		} ZEND_HASH_FOREACH_END();

		finite = true;
		return true;
	}

	/* is_numeric($value) of a string */
	static bool isNumeric(zend_string *value)
	{
		return is_numeric_string(ZSTR_VAL(value), ZSTR_LEN(value), NULL, NULL, 0) != 0;
	}

	/* pow(): IS_NULL for an error; UNDEF = pending exception */
	static zv::Val pow(zval *base, zval *exponent)
	{
		if ((Z_TYPE_P(base) == IS_STRING && !isNumeric(Z_STR_P(base))) || (Z_TYPE_P(exponent) == IS_STRING && !isNumeric(Z_STR_P(exponent)))) {
			return zv::Val::null();
		}

		// PHP 8.4 deprecates 0 ** -1, which still gives INF; the twin silences it with @
		int errorReporting = EG(error_reporting);
		EG(error_reporting) &= (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE);
		zv::Val result = phpOp(pow_function, base, exponent);
		EG(error_reporting) = errorReporting;
		return result;
	}

	/* foldPower(): IS_NULL for an error */
	static zv::Val foldPower(zval *base, zval *exponent)
	{
		IETR_VAL(result, pow(base, exponent));
		if (Z_TYPE_P(result.raw()) == IS_NULL) return zv::Val::null();

		IETR_VAL(resultType, typeFromValue(result.raw()));

		// a zero float stands for both zeros (0.0 === -0.0), and a negative odd exponent tells them apart
		if (Z_TYPE_P(base) == IS_DOUBLE && Z_DVAL_P(base) == 0.0) {
			zval otherBase;
			ZVAL_DOUBLE(&otherBase, -Z_DVAL_P(base));
			IETR_VAL(otherZeroResult, pow(&otherBase, exponent));
			if (Z_TYPE_P(otherZeroResult.raw()) == IS_DOUBLE && !identical(otherZeroResult.raw(), result.raw())) {
				IETR_VAL(otherType, newConstantFloatType(Z_DVAL_P(otherZeroResult.raw())));
				return union2(resultType.raw(), otherType.raw());
			}
		}

		return resultType;
	}

	/* foldValues(): IS_NULL for an error */
	static zv::Val foldValues(Op op, zval *left, zval *right)
	{
		if (op == OP_POW) {
			IETR_VAL(leftValue, callOn(left, mGetValue));
			IETR_VAL(rightValue, callOn(right, mGetValue));
			return foldPower(leftValue.raw(), rightValue.raw());
		}

		if (op == OP_AND || op == OP_OR || op == OP_XOR) {
			zend_long leftIsString = triOn(left, mIsString);
			if (UNEXPECTED(leftIsString < 0)) return zv::Val();
			if (leftIsString == PT_TRI_YES) {
				zend_long rightIsString = triOn(right, mIsString);
				if (UNEXPECTED(rightIsString < 0)) return zv::Val();
				if (rightIsString == PT_TRI_YES) {
					IETR_VAL(leftValue, callOn(left, mGetValue));
					IETR_VAL(rightValue, callOn(right, mGetValue));
					pt_ietr_binary_op fn = op == OP_AND ? bitwise_and_function : (op == OP_OR ? bitwise_or_function : bitwise_xor_function);
					IETR_VAL(value, phpOp(fn, leftValue.raw(), rightValue.raw()));
					return newConstantStringType(Z_STR_P(value.raw()));
				}
			}
		}

		IETR_VAL(leftNumberType, callOn(left, mToNumber));
		IETR_VAL(leftNumber, getConstantNumber(leftNumberType.raw()));
		IETR_VAL(rightNumberType, callOn(right, mToNumber));
		IETR_VAL(rightNumber, getConstantNumber(rightNumberType.raw()));
		if (Z_TYPE_P(leftNumber.raw()) == IS_NULL || Z_TYPE_P(rightNumber.raw()) == IS_NULL) return zv::Val::null();

		if (op == OP_PLUS || op == OP_MINUS || op == OP_MUL || op == OP_DIV) {
			if (op == OP_DIV && isZero(rightNumber.raw())) return zv::Val::null();
			pt_ietr_binary_op fn = op == OP_PLUS ? add_function : (op == OP_MINUS ? sub_function : (op == OP_MUL ? mul_function : div_function));
			IETR_VAL(value, phpOp(fn, leftNumber.raw(), rightNumber.raw()));
			return typeFromValue(value.raw());
		}

		// the integer operators read numeric strings out of the int range as the nearest
		// integer, like an (int) cast, unlike the float toNumber() gives
		IETR_VAL(leftIntegerType, callOn(left, mToInteger));
		IETR_VAL(leftInteger, getConstantInteger(leftIntegerType.raw()));
		IETR_VAL(rightIntegerType, callOn(right, mToInteger));
		IETR_VAL(rightInteger, getConstantInteger(rightIntegerType.raw()));
		if (Z_TYPE_P(leftInteger.raw()) == IS_NULL || Z_TYPE_P(rightInteger.raw()) == IS_NULL) return zv::Val::null();
		zend_long rightValue = Z_LVAL_P(rightInteger.raw());

		pt_ietr_binary_op fn;
		switch (op) {
			case OP_MOD:
				if (rightValue == 0) return zv::Val::null();
				fn = mod_function;
				break;
			case OP_SHIFT_LEFT:
				if (rightValue < 0) return zv::Val::null();
				fn = shift_left_function;
				break;
			case OP_SHIFT_RIGHT:
				if (rightValue < 0) return zv::Val::null();
				fn = shift_right_function;
				break;
			case OP_AND:
				fn = bitwise_and_function;
				break;
			case OP_OR:
				fn = bitwise_or_function;
				break;
			default:
				fn = bitwise_xor_function;
				break;
		}
		IETR_VAL(value, phpOp(fn, leftInteger.raw(), rightInteger.raw()));
		return newConstantIntegerType(Z_LVAL_P(value.raw()));
	}

	/* fold(): IS_NULL when the operands are not few enough constants */
	static zv::Val fold(Op op, ValueSetView left, ValueSetView right)
	{
		// the bitwise operators list small integer ranges too
		bool expandIntegerIntervals = op == OP_AND || op == OP_OR || op == OP_XOR;
		std::vector<zv::Val> leftTypes, rightTypes;
		bool leftFinite, rightFinite;
		if (UNEXPECTED(!getFiniteScalarTypes(left, expandIntegerIntervals, leftTypes, leftFinite))) return zv::Val();
		if (UNEXPECTED(!getFiniteScalarTypes(right, expandIntegerIntervals, rightTypes, rightFinite))) return zv::Val();
		if (
			!leftFinite
			|| !rightFinite
			|| leftTypes.empty()
			|| rightTypes.empty()
			|| (zend_long) leftTypes.size() * (zend_long) rightTypes.size() > PT_IETR_CALCULATE_SCALARS_LIMIT
		) {
			return zv::Val::null();
		}

		zv::Arr resultTypes = zv::Arr::create((uint32_t) (leftTypes.size() * rightTypes.size()));
		for (zv::Val &leftType : leftTypes) {
			for (zv::Val &rightType : rightTypes) {
				IETR_VAL(resultType, foldValues(op, leftType.raw(), rightType.raw()));
				if (Z_TYPE_P(resultType.raw()) == IS_NULL) return newErrorType();
				resultTypes.push(std::move(resultType));
			}
		}

		return unionOf(resultTypes);
	}

	/* }}} */

	/* {{{ the operators */

	/* getResultOfOperands(): IS_NULL for none */
	static zv::Val getResultOfOperands(ValueSetView left, ValueSetView right)
	{
		if (isEmpty(left) || isEmpty(right)) {
			// keep an explicit never, it ends the execution
			for (zval *never : {left.never(), right.never()}) {
				if (never == nullptr) continue;
				IETR_VAL(isExplicit, callOn(never, mIsExplicit));
				if (Z_TYPE_P(isExplicit.raw()) == IS_TRUE) return zv::Val::copyOf(zv::Ref(never));
			}
			return newNeverType();
		}

		if (left.invalid() || right.invalid()) return newErrorType();

		return zv::Val::null();
	}

	static zv::Val invalidOrFold(Op op, ValueSetView left, ValueSetView right)
	{
		if (left.invalid() || right.invalid()) return newErrorType();
		return fold(op, left, right);
	}

	/* getResultOfOperands() ?? fold() */
	static zv::Val resultOrFold(Op op, ValueSetView left, ValueSetView right)
	{
		IETR_VAL(result, getResultOfOperands(left, right));
		if (Z_TYPE_P(result.raw()) != IS_NULL) return result;
		return fold(op, left, right);
	}

	/* getZeroDivisorError(): IS_NULL for none */
	static zv::Val getZeroDivisorError(ValueSetView s)
	{
		std::vector<zv::Val> types;
		bool finite;
		if (UNEXPECTED(!getFiniteScalarTypes(s, false, types, finite))) return zv::Val();
		if (!finite) return zv::Val::null();
		for (zv::Val &type : types) {
			IETR_VAL(numberType, callOn(type.raw(), mToNumber));
			IETR_VAL(number, getConstantNumber(numberType.raw()));
			if (isZero(number.raw())) return newErrorType();
		}
		return zv::Val::null();
	}

	static zv::Val toResult(ValueSetView left, ValueSetView right, zv::Val result)
	{
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (left.benevolent() || left.unknown() || right.benevolent() || right.unknown()) return toBenevolentUnion(result.raw());
		return result;
	}

	/* generalize() */
	static zv::Val generalize(ValueSetView s)
	{
		bool hasBooleans = s.false_() || s.true_();
		bool hasStrings = s.strings() != nullptr;
		bool hasNumbers = !numbersEmpty(s.numbers());
		if (!hasBooleans && !hasStrings && !s.null_() && hasNumbers) {
			NumbersView n(s.numbers());
			if (n.floatCount() == 0 && !n.nan()) return createNumbersOf(anyInteger());
			if (n.integerCount() == 0) {
				NumberSetData anyFloat;
				anyFloat.floatIntervals.push_back({-HUGE_VAL, HUGE_VAL});
				anyFloat.nan = true;
				return createNumbersOf(anyFloat);
			}
		}
		if (!hasBooleans && !hasNumbers && !s.null_() && hasStrings) {
			IETR_VAL(stringType, newStringType());
			return createStrings(stringType.raw());
		}
		if (!hasBooleans && !hasNumbers && !hasStrings && s.null_()) return createNull();

		return createInvalid();
	}

	/* generalizeTooManyConstants(); false = pending exception */
	static bool generalizeTooManyConstants(ValueSetView left, ValueSetView right, SetRef &leftOut, SetRef &rightOut)
	{
		leftOut.object = left.object();
		rightOut.object = right.object();
		std::vector<zv::Val> leftTypes, rightTypes;
		bool leftFinite, rightFinite;
		if (UNEXPECTED(!getFiniteScalarTypes(left, false, leftTypes, leftFinite))) return false;
		if (UNEXPECTED(!getFiniteScalarTypes(right, false, rightTypes, rightFinite))) return false;
		if (
			!leftFinite
			|| !rightFinite
			|| leftTypes.empty()
			|| rightTypes.empty()
			|| (zend_long) leftTypes.size() * (zend_long) rightTypes.size() <= PT_IETR_CALCULATE_SCALARS_LIMIT
		) {
			return true;
		}

		leftOut.owned = generalize(left);
		if (UNEXPECTED(leftOut.owned.isUndef())) return false;
		rightOut.owned = generalize(right);
		if (UNEXPECTED(rightOut.owned.isUndef())) return false;
		leftOut.object = Z_OBJ_P(leftOut.owned.raw());
		rightOut.object = Z_OBJ_P(rightOut.owned.raw());
		return true;
	}

	/* toNumberType(): IS_NULL for an error */
	static zv::Val toNumberType(ValueSetView s)
	{
		if (s.unknown()) {
			IETR_VAL(integerType, newIntegerType());
			IETR_VAL(floatType, newFloatType());
			return union2(integerType.raw(), floatType.raw());
		}
		if (isEmpty(s)) {
			if (s.never() != nullptr) return zv::Val::copyOf(zv::Ref(s.never()));
			return newNeverType();
		}

		zv::Arr types = zv::Arr::create(4);
		if (s.null_() || s.false_()) {
			IETR_VAL(zero, newConstantIntegerType(0));
			types.push(std::move(zero));
		}
		if (s.true_()) {
			IETR_VAL(one, newConstantIntegerType(1));
			types.push(std::move(one));
		}
		if (!numbersEmpty(s.numbers())) {
			NumberSetData numbers;
			if (UNEXPECTED(!readNumbers(s.numbers(), numbers))) return zv::Val();
			IETR_VAL(numbersType, createTypeOfNumbers(numbers));
			types.push(std::move(numbersType));
		}

		std::vector<zv::Val> otherTypes;
		zval *entry;
		ZEND_HASH_FOREACH_VAL(s.constantStrings(), entry) {
			otherTypes.push_back(zv::Val::copyOf(zv::Ref(entry)));
		} ZEND_HASH_FOREACH_END();
		for (zval *type : {s.stringType(), s.arrays(), s.objects()}) {
			if (type == nullptr) continue;
			otherTypes.push_back(zv::Val::copyOf(zv::Ref(type)));
		}
		if (s.resources()) {
			IETR_VAL(resource, resourceType());
			otherTypes.push_back(std::move(resource));
		}
		for (zv::Val &type : otherTypes) {
			IETR_VAL(numberType, callOn(type.raw(), mToNumber));
			if (isA(numberType.raw(), pt_ce_error_type)) {
				if (s.benevolent()) continue;
				return zv::Val::null();
			}
			types.push(std::move(numberType));
		}

		if (zend_hash_num_elements(types.table()) == 0) return zv::Val::null();

		return unionOf(types);
	}

	/* calculateCommonMath() of + - * / << >> */
	static zv::Val calculateCommonMath(Op op, ValueSetView left, ValueSetView right, zval *phpVersion)
	{
		IETR_VAL(leftNumberType, toNumberType(left));
		IETR_VAL(rightNumberType, toNumberType(right));
		bool leftHasType = Z_TYPE_P(leftNumberType.raw()) != IS_NULL;
		bool rightHasType = Z_TYPE_P(rightNumberType.raw()) != IS_NULL;
		bool isBenevolent = left.benevolent()
			|| right.benevolent()
			|| (leftHasType && isA(leftNumberType.raw(), pt_ce_benevolent_union_type))
			|| (rightHasType && isA(rightNumberType.raw(), pt_ce_benevolent_union_type));

		if (!left.unknown() && !right.unknown() && leftHasType && rightHasType) {
			NumberSetData leftNumbers, rightNumbers;
			bool leftHas, rightHas;
			if (UNEXPECTED(!typeNumbers(leftNumberType.raw(), leftNumbers, leftHas))) return zv::Val();
			if (UNEXPECTED(!typeNumbers(rightNumberType.raw(), rightNumbers, rightHas))) return zv::Val();
			if (leftHas && rightHas) {
				IetrBinaryKind kind;
				switch (op) {
					case OP_PLUS: kind = IETR_PLUS; break;
					case OP_MINUS: kind = IETR_MINUS; break;
					case OP_MUL: kind = IETR_MUL; break;
					case OP_DIV: kind = IETR_DIV; break;
					case OP_SHIFT_LEFT: kind = IETR_SHIFT_LEFT; break;
					default: kind = IETR_SHIFT_RIGHT; break;
				}
				IETR_VAL(result, pt_number_set_calculate(kind, leftNumbers, rightNumbers, phpVersion));
				return isBenevolent ? toBenevolentUnion(result.raw()) : std::move(result);
			}
		}

		zend_long leftIsArray = isArray(left);
		if (UNEXPECTED(leftIsArray < 0)) return zv::Val();
		if (leftIsArray == PT_TRI_YES) return newErrorType();
		zend_long rightIsArray = isArray(right);
		if (UNEXPECTED(rightIsArray < 0)) return zv::Val();
		if (rightIsArray == PT_TRI_YES) return newErrorType();
		if (!leftHasType || !rightHasType) return newErrorType();
		bool leftIsNever = isA(leftNumberType.raw(), pt_ce_never_type);
		bool rightIsNever = isA(rightNumberType.raw(), pt_ce_never_type);
		if (leftIsNever || rightIsNever) {
			// keep an explicit never, it ends the execution
			for (zval *numberType : {leftNumberType.raw(), rightNumberType.raw()}) {
				if (!isA(numberType, pt_ce_never_type)) continue;
				IETR_VAL(isExplicit, callOn(numberType, mIsExplicit));
				if (Z_TYPE_P(isExplicit.raw()) == IS_TRUE) return zv::Val::copyOf(zv::Ref(numberType));
			}
			return newNeverType();
		}

		zend_long leftIsFloat = triOn(leftNumberType.raw(), mIsFloat);
		if (UNEXPECTED(leftIsFloat < 0)) return zv::Val();
		zend_long rightIsFloat = leftIsFloat == PT_TRI_YES ? PT_TRI_YES : triOn(rightNumberType.raw(), mIsFloat);
		if (UNEXPECTED(rightIsFloat < 0)) return zv::Val();
		if (leftIsFloat == PT_TRI_YES || rightIsFloat == PT_TRI_YES) {
			if (op == OP_SHIFT_LEFT || op == OP_SHIFT_RIGHT) return newIntegerType();
			return newFloatType();
		}

		IETR_VAL(resultType, union2(leftNumberType.raw(), rightNumberType.raw()));
		bool isUnknownOperand = left.unknown() || right.unknown();
		if (op == OP_DIV) {
			bool benevolent = isUnknownOperand;
			if (!benevolent) {
				zend_long isInteger = triOn(resultType.raw(), mIsInteger);
				if (UNEXPECTED(isInteger < 0)) return zv::Val();
				benevolent = isInteger == PT_TRI_YES;
			}
			return newIntFloatUnion(benevolent);
		}

		if (isUnknownOperand || left.benevolent() || right.benevolent()) return toBenevolentUnion(resultType.raw());

		return resultType;
	}

	static zv::Val arithmeticAdd(ValueSetView left, ValueSetView right, zval *phpVersion)
	{
		IETR_VAL(result, resultOrFold(OP_PLUS, left, right));
		if (Z_TYPE_P(result.raw()) != IS_NULL) return result;

		SetRef generalizedLeft, generalizedRight;
		if (UNEXPECTED(!generalizeTooManyConstants(left, right, generalizedLeft, generalizedRight))) return zv::Val();
		if (generalizedLeft.object != left.object()) return arithmeticAdd(generalizedLeft.view(), generalizedRight.view(), phpVersion);

		zend_long leftIsArray = isArray(left);
		if (UNEXPECTED(leftIsArray < 0)) return zv::Val();
		zend_long rightIsArray = isArray(right);
		if (UNEXPECTED(rightIsArray < 0)) return zv::Val();
		if (left.unknown() && right.unknown()) {
			IETR_VAL(floatType, newFloatType());
			IETR_VAL(integerType, newIntegerType());
			if (leftIsArray == PT_TRI_NO && rightIsArray == PT_TRI_NO) return newUnionOf(true, {&floatType, &integerType});
			IETR_VAL(arrayType, mixedArrayType());
			return newUnionOf(true, {&floatType, &integerType, &arrayType});
		}
		if ((leftIsArray == PT_TRI_YES && rightIsArray == PT_TRI_NO) || (leftIsArray == PT_TRI_NO && rightIsArray == PT_TRI_YES)) return newErrorType();
		if ((leftIsArray == PT_TRI_YES && rightIsArray == PT_TRI_MAYBE) || (leftIsArray == PT_TRI_MAYBE && rightIsArray == PT_TRI_YES)) {
			IETR_VAL(resultType, mixedArrayType());
			if (isOnlyNonEmptyArrays(left) || isOnlyNonEmptyArrays(right)) {
				IETR_VAL(nonEmpty, nonEmptyArrayType());
				zv::Args argv{resultType.raw(), nonEmpty.raw()};
				return pt_type_combinator_intersect(2, argv);
			}
			return resultType;
		}
		return calculateCommonMath(OP_PLUS, left, right, phpVersion);
	}

	/* arithmeticSubtract() / arithmeticMultiply() */
	static zv::Val subtractOrMultiply(Op op, ValueSetView left, ValueSetView right, zval *phpVersion)
	{
		IETR_VAL(result, invalidOrFold(op, left, right));
		if (Z_TYPE_P(result.raw()) != IS_NULL) return result;

		SetRef generalizedLeft, generalizedRight;
		if (UNEXPECTED(!generalizeTooManyConstants(left, right, generalizedLeft, generalizedRight))) return zv::Val();
		return calculateCommonMath(op, generalizedLeft.view(), generalizedRight.view(), phpVersion);
	}

	static zv::Val arithmeticDivide(ValueSetView left, ValueSetView right, zval *phpVersion)
	{
		IETR_VAL(result, invalidOrFold(OP_DIV, left, right));
		if (Z_TYPE_P(result.raw()) == IS_NULL) {
			SetRef generalizedLeft, generalizedRight;
			if (UNEXPECTED(!generalizeTooManyConstants(left, right, generalizedLeft, generalizedRight))) return zv::Val();
			result = getZeroDivisorError(generalizedRight.view());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			if (Z_TYPE_P(result.raw()) == IS_NULL) {
				result = calculateCommonMath(OP_DIV, generalizedLeft.view(), generalizedRight.view(), phpVersion);
				if (UNEXPECTED(result.isUndef())) return zv::Val();
			}
		}
		if (!isOnlyIntegers(left) || !isOnlyIntegers(right)) return result;

		IETR_VAL(moduloType, arithmeticModulo(left, right, phpVersion));
		zend_long isInteger = triOn(moduloType.raw(), mIsInteger);
		if (UNEXPECTED(isInteger < 0)) return zv::Val();
		if (isInteger != PT_TRI_YES) return result;
		IETR_VAL(zero, newConstantIntegerType(0));
		zend_long isZeroModulo = triOn(zero.raw(), mIsSuperTypeOf, 1, moduloType.raw());
		if (UNEXPECTED(isZeroModulo < 0)) return zv::Val();
		if (isZeroModulo != PT_TRI_YES) return result;

		// PHP_INT_MIN / -1 divides without a remainder but still overflows to a float
		IETR_VAL(floatType, newFloatType());
		IETR_VAL(withoutFloat, pt_type_combinator_remove(result.raw(), floatType.raw()));
		return isA(withoutFloat.raw(), pt_ce_never_type) ? std::move(result) : std::move(withoutFloat);
	}

	static zv::Val arithmeticIntDivide(ValueSetView left, ValueSetView right)
	{
		zval *leftNumbers = getIntegerNumbers(left);
		zval *rightNumbers = getIntegerNumbers(right);
		if (leftNumbers == nullptr || rightNumbers == nullptr) return newErrorType();

		NumberSetData leftData, rightData;
		if (UNEXPECTED(!readNumbers(leftNumbers, leftData) || !readNumbers(rightNumbers, rightData))) return zv::Val();
		return pt_number_set_int_divide(leftData, rightData);
	}

	static zv::Val arithmeticModulo(ValueSetView left, ValueSetView right, zval *phpVersion)
	{
		IETR_VAL(result, resultOrFold(OP_MOD, left, right));
		if (Z_TYPE_P(result.raw()) != IS_NULL) return result;

		SetRef generalizedLeft, generalizedRight;
		if (UNEXPECTED(!generalizeTooManyConstants(left, right, generalizedLeft, generalizedRight))) return zv::Val();
		if (generalizedLeft.object != left.object()) return arithmeticModulo(generalizedLeft.view(), generalizedRight.view(), phpVersion);

		NumberSetData leftNumbers, rightNumbers;
		bool leftHas, rightHas;
		if (UNEXPECTED(!toOperandNumbers(left, false, true, leftNumbers, leftHas))) return zv::Val();
		if (UNEXPECTED(!toOperandNumbers(right, false, true, rightNumbers, rightHas))) return zv::Val();
		if (!leftHas || !rightHas) return newErrorType();

		IETR_VAL(zeroDivisorError, getZeroDivisorError(right));
		if (Z_TYPE_P(zeroDivisorError.raw()) != IS_NULL) return zeroDivisorError;

		return toResult(left, right, pt_number_set_modulo(leftNumbers, rightNumbers));
	}

	/* getMembers() */
	static bool getMembers(ValueSetView s, std::vector<zv::Val> &members)
	{
		if (s.null_()) {
			zv::Val member = createNull();
			if (UNEXPECTED(member.isUndef())) return false;
			members.push_back(std::move(member));
		}
		if (s.false_() || s.true_()) {
			zv::Val member = createBooleans(s.false_(), s.true_());
			if (UNEXPECTED(member.isUndef())) return false;
			members.push_back(std::move(member));
		}
		if (s.numbers() != nullptr) {
			NumberSetData numbers;
			if (UNEXPECTED(!readNumbers(s.numbers(), numbers))) return false;
			bool nan = numbers.nan;
			for (const NumberSetData::IntegerInterval &interval : numbers.integerIntervals) {
				NumberSetData member;
				member.integerIntervals.push_back(interval);
				zv::Val memberSet = createNumbersOf(member);
				if (UNEXPECTED(memberSet.isUndef())) return false;
				members.push_back(std::move(memberSet));
			}
			for (const NumberSetData::FloatInterval &interval : numbers.floatIntervals) {
				bool withNan = nan && interval.min == -HUGE_VAL && interval.max == HUGE_VAL;
				nan = nan && !withNan;
				NumberSetData member;
				member.floatIntervals.push_back(interval);
				member.nan = withNan;
				zv::Val memberSet = createNumbersOf(member);
				if (UNEXPECTED(memberSet.isUndef())) return false;
				members.push_back(std::move(memberSet));
			}
			if (nan) {
				NumberSetData member;
				member.nan = true;
				zv::Val memberSet = createNumbersOf(member);
				if (UNEXPECTED(memberSet.isUndef())) return false;
				members.push_back(std::move(memberSet));
			}
		}
		zval *entry;
		ZEND_HASH_FOREACH_VAL(s.constantStrings(), entry) {
			zv::Val member = createConstantString(entry);
			if (UNEXPECTED(member.isUndef())) return false;
			members.push_back(std::move(member));
		} ZEND_HASH_FOREACH_END();
		if (s.stringType() != nullptr) {
			zv::Val member = createStrings(s.stringType());
			if (UNEXPECTED(member.isUndef())) return false;
			members.push_back(std::move(member));
		}
		return true;
	}

	static zv::Val powerMember(ValueSetView base, ValueSetView exponent, zval *phpVersion)
	{
		zval *entry;
		ZEND_HASH_FOREACH_VAL(exponent.constantStrings(), entry) {
			IETR_VAL(value, constantStringValue(entry));
			if (!isNumeric(Z_STR_P(value.raw()))) return newErrorType();
		} ZEND_HASH_FOREACH_END();

		std::vector<zv::Val> baseTypes;
		bool baseFinite;
		if (UNEXPECTED(!getFiniteScalarTypes(base, false, baseTypes, baseFinite))) return zv::Val();
		if (baseFinite && baseTypes.size() == 1) {
			std::vector<zv::Val> exponentTypes;
			bool exponentFinite;
			if (UNEXPECTED(!getFiniteScalarTypes(exponent, false, exponentTypes, exponentFinite))) return zv::Val();
			if (exponentFinite && exponentTypes.size() == 1) {
				IETR_VAL(baseValue, callOn(baseTypes[0].raw(), mGetValue));
				IETR_VAL(exponentValue, callOn(exponentTypes[0].raw(), mGetValue));
				IETR_VAL(folded, foldPower(baseValue.raw(), exponentValue.raw()));
				if (Z_TYPE_P(folded.raw()) == IS_NULL) return newErrorType();
				return folded;
			}

			IETR_VAL(baseValue, callOn(baseTypes[0].raw(), mGetValue));
			if (Z_TYPE_P(baseValue.raw()) == IS_STRING && !isNumeric(Z_STR_P(baseValue.raw()))) return newErrorType();
		}

		NumberSetData baseNumbers, exponentNumbers;
		bool hasBaseNumbers, hasExponentNumbers;
		if (UNEXPECTED(!toNumberSetOfNumber(base, baseNumbers, hasBaseNumbers))) return zv::Val();
		if (UNEXPECTED(!toNumberSetOfNumber(exponent, exponentNumbers, hasExponentNumbers))) return zv::Val();
		if (hasBaseNumbers && hasExponentNumbers) {
			IETR_VAL(baseSet, pt_number_set_new(baseNumbers));
			IETR_VAL(exponentSet, pt_number_set_new(exponentNumbers));
			zv::Args argv{exponentSet.raw(), phpVersion};
			IETR_VAL(result, callOn(baseSet.raw(), mPower, 2, argv));
			if (Z_TYPE_P(result.raw()) != IS_NULL) return result;
		}

		// a float operand makes the result a float
		bool isFloatResult = isOnlyFloats(base) || (hasExponentNumbers && isOnlyFloatNumbers(exponentNumbers));
		if (hasExponentNumbers && isOnlyInteger(exponentNumbers, 0)) {
			if (isFloatResult) return newConstantFloatType(1.0);
			if (hasBaseNumbers && !baseNumbers.integerIntervals.empty() && baseNumbers.floatIntervals.empty() && !baseNumbers.nan) return newConstantIntegerType(1);

			IETR_VAL(one, newConstantIntegerType(1));
			IETR_VAL(oneFloat, newConstantFloatType(1.0));
			return newUnionOf(false, {&one, &oneFloat});
		}

		if (isFloatResult) return newFloatType();

		if (hasExponentNumbers && isOnlyInteger(exponentNumbers, 1) && base.numbers() != nullptr && !mayBeNonNumber(base)) {
			NumberSetData numbers;
			if (UNEXPECTED(!readNumbers(base.numbers(), numbers))) return zv::Val();
			return createTypeOfNumbers(numbers);
		}

		return newIntFloatUnion(true, true);
	}

	static zv::Val arithmeticPower(ValueSetView base, ValueSetView exponent, zval *phpVersion)
	{
		if (isEmpty(exponent)) return newNeverType();
		if (
			exponent.unknown()
			|| exponent.emptyArray()
			|| exponent.nonEmptyArray()
			|| exponent.objects() != nullptr
			|| exponent.resources()
		) {
			return newErrorType();
		}

		std::vector<zv::Val> members;
		if (UNEXPECTED(!getMembers(exponent, members))) return zv::Val();
		zv::Arr resultTypes = zv::Arr::create((uint32_t) members.size());
		for (zv::Val &member : members) {
			IETR_VAL(resultType, powerMember(base, ValueSetView(Z_OBJ_P(member.raw())), phpVersion));
			resultTypes.push(std::move(resultType));
		}

		return unionOf(resultTypes);
	}

	/* {{{ stringConcatenate() */

	/* Mirrors stringConcatenate(). */
	static zv::Val stringConcatenate(ValueSetView left, ValueSetView right, zval *phpVersion)
	{
		IETR_VAL(leftStrings, toStringSet(left));
		IETR_VAL(rightStrings, toStringSet(right));
		if (Z_TYPE_P(leftStrings.raw()) == IS_NULL || Z_TYPE_P(rightStrings.raw()) == IS_NULL) return newErrorType();
		if (isEmpty(left) || isEmpty(right)) {
			zval *never = left.never() != nullptr ? left.never() : right.never();
			if (never != nullptr) return zv::Val::copyOf(zv::Ref(never));
			return newNeverType();
		}

		return pt_string_set_concatenate(leftStrings.raw(), rightStrings.raw(), phpVersion);
	}

	/* Mirrors toStringSet(): a StringSet, or PHP null */
	static zv::Val toStringSet(ValueSetView s)
	{
		if (s.invalid() || s.emptyArray() || s.nonEmptyArray()) return zv::Val::null();
		if (s.unknown()) {
			zv::Val mixed;
			zval *unknownType = s.unknownType();
			if (unknownType == nullptr) {
				mixed = newMixedType();
				if (UNEXPECTED(mixed.isUndef())) return zv::Val();
				unknownType = mixed.raw();
			}
			IETR_VAL(strings, callOn(unknownType, mToString));
			return toStringSetOfType(strings.raw());
		}

		zv::Val strings = s.strings() != nullptr ? zv::Val::copyOf(zv::Ref(s.strings())) : pt_string_set_create_empty();
		if (UNEXPECTED(strings.isUndef())) return zv::Val();
		auto unionWith = [&strings](zv::Val other) -> bool {
			if (UNEXPECTED(other.isUndef())) return false;
			zv::Val united = pt_string_set_union(strings.raw(), other.raw());
			if (UNEXPECTED(united.isUndef())) return false;
			strings = std::move(united);
			return true;
		};
		if (s.null_() || s.false_()) {
			IETR_VAL(empty, constantStringLiteral("", 0));
			if (UNEXPECTED(!unionWith(pt_string_set_create_constant(empty.raw())))) return zv::Val();
		}
		if (s.true_()) {
			IETR_VAL(one, constantStringLiteral("1", 1));
			if (UNEXPECTED(!unionWith(pt_string_set_create_constant(one.raw())))) return zv::Val();
		}
		if (!numbersEmpty(s.numbers())) {
			NumberSetData numbers;
			if (UNEXPECTED(!readNumbers(s.numbers(), numbers))) return zv::Val();
			IETR_VAL(numbersType, createTypeOfNumbers(numbers));
			IETR_VAL(numberStringType, callOn(numbersType.raw(), mToString));
			IETR_VAL(numberStrings, toStringSetOfType(numberStringType.raw()));
			if (Z_TYPE_P(numberStrings.raw()) == IS_NULL) return numberStrings;
			if (UNEXPECTED(!unionWith(pt_string_set_to_number_strings(numberStrings.raw(), isOnlyNonNegativeIntegers(numbers))))) return zv::Val();
		}
		if (s.objects() != nullptr) {
			IETR_VAL(objectStringType, callOn(s.objects(), mToString));
			IETR_VAL(objectStrings, toStringSetOfType(objectStringType.raw()));
			if (Z_TYPE_P(objectStrings.raw()) == IS_NULL) return objectStrings;
			if (UNEXPECTED(!unionWith(std::move(objectStrings)))) return zv::Val();
		}
		if (s.resources()) {
			IETR_VAL(stringType, newStringType());
			if (UNEXPECTED(!unionWith(pt_string_set_create_type(stringType.raw())))) return zv::Val();
		}

		return strings;
	}

	/* Mirrors toStringSetOfType(): a StringSet, or PHP null */
	static zv::Val toStringSetOfType(zval *strings)
	{
		if (isA(strings, pt_ce_error_type)) return zv::Val::null();
		IETR_VAL(valueSet, pt_type_call(Z_OBJ_P(strings), PT_LC("tovalueset"), 0, NULL));
		zval *set = ValueSetView(Z_OBJ_P(valueSet.raw())).strings();
		if (set == nullptr) return pt_string_set_create_empty();
		return zv::Val::copyOf(zv::Ref(set));
	}

	static bool isOnlyNonNegativeIntegers(const NumberSetData &numbers)
	{
		for (const NumberSetData::IntegerInterval &interval : numbers.integerIntervals) {
			if (interval.min.isNull || interval.min.value < 0) return false;
		}
		return numbers.floatIntervals.empty() && !numbers.nan;
	}

	/* }}} */

	static zv::Val arithmeticNegate(ValueSetView s, zval *phpVersion)
	{
		if (isEmpty(s)) {
			if (s.never() != nullptr) return zv::Val::copyOf(zv::Ref(s.never()));
			return newNeverType();
		}

		NumberSetData numbers;
		bool has = false;
		if (!s.invalid() && UNEXPECTED(!toOperandNumbers(s, false, false, numbers, has))) return zv::Val();
		if (!has) return newErrorType();

		IETR_VAL(negated, pt_number_set_negate(numbers, phpVersion));
		return s.benevolent() ? toBenevolentUnion(negated.raw()) : std::move(negated);
	}

	static zv::Val calculateShift(Op op, ValueSetView left, ValueSetView right, zval *phpVersion)
	{
		IETR_VAL(result, resultOrFold(op, left, right));
		if (Z_TYPE_P(result.raw()) != IS_NULL) return result;

		SetRef generalizedLeft, generalizedRight;
		if (UNEXPECTED(!generalizeTooManyConstants(left, right, generalizedLeft, generalizedRight))) return zv::Val();
		IETR_VAL(leftNumberType, toNumberType(generalizedLeft.view()));
		if (Z_TYPE_P(leftNumberType.raw()) == IS_NULL) return newErrorType();
		IETR_VAL(rightNumberType, toNumberType(generalizedRight.view()));
		if (Z_TYPE_P(rightNumberType.raw()) == IS_NULL) return newErrorType();

		return calculateCommonMath(op, generalizedLeft.view(), generalizedRight.view(), phpVersion);
	}

	static zv::Val calculateBitwise(Op op, ValueSetView left, ValueSetView right)
	{
		IETR_VAL(result, resultOrFold(op, left, right));
		if (Z_TYPE_P(result.raw()) != IS_NULL) return result;

		bool leftOnlyStrings = isOnlyStrings(left);
		bool rightOnlyStrings = isOnlyStrings(right);
		if ((leftOnlyStrings || left.unknown()) && (rightOnlyStrings || right.unknown())) {
			if (leftOnlyStrings && rightOnlyStrings) return newStringType();

			// with an unknown operand that is not a string, the string is a number
			IETR_VAL(integerType, newIntegerType());
			IETR_VAL(stringType, newStringType());
			return newUnionOf(true, {&integerType, &stringType});
		}
		if (mayBeString(left) && !leftOnlyStrings && mayBeString(right) && !rightOnlyStrings) return newErrorType();

		NumberSetData leftNumbers, rightNumbers;
		bool leftHas, rightHas;
		if (UNEXPECTED(!toOperandNumbers(left, false, false, leftNumbers, leftHas))) return zv::Val();
		if (UNEXPECTED(!toOperandNumbers(right, false, false, rightNumbers, rightHas))) return zv::Val();
		if (!leftHas || !rightHas) return newErrorType();

		IetrBinaryKind kind = op == OP_AND ? IETR_BITWISE_AND : (op == OP_OR ? IETR_BITWISE_OR : IETR_BITWISE_XOR);
		return toResult(left, right, pt_number_set_bitwise(kind, leftNumbers, rightNumbers));
	}

	/* }}} */

	/* {{{ loose equality */

	static zv::Val looseComparisonHelper(const char *lcMethod, size_t len, uint32_t argc, zval *argv)
	{
		return pt_type_call_static(PT_CLASS_LOOSE_COMPARISON_HELPER, lcMethod, len, argc, argv);
	}

	/* getNumberString() */
	static zv::Val getNumberString(zval *number)
	{
		if (Z_TYPE_P(number) == IS_LONG) return zv::Val::adoptString(zend_long_to_str(Z_LVAL_P(number)));
		double value = Z_DVAL_P(number);
		if (std::isinf(value)) return value > 0 ? zv::Val::string(PT_LC("INF")) : zv::Val::string(PT_LC("-INF"));

		static zend_function *sprintfFunction = NULL;
		for (zend_long digits : {SHORT_FLOAT_DIGITS, EXACT_FLOAT_DIGITS}) {
			zend_string *format = zend_strpprintf(0, "%%.%dG", (int) digits);
			zval argv[2];
			ZVAL_STR(&argv[0], format);
			ZVAL_DOUBLE(&argv[1], value);
			zv::Val string = callInternalFunction(sprintfFunction, PT_LC("sprintf"), 2, argv);
			zend_string_release(format);
			if (UNEXPECTED(string.isUndef())) return zv::Val();
			if (digits == EXACT_FLOAT_DIGITS || zend_strtod(Z_STRVAL_P(string.raw()), NULL) == value) return string;
		}
		return zv::Val();
	}

	/* getLooselyEqualNumber(): IS_NULL for none */
	static zv::Val getLooselyEqualNumber(zval *value, zval *phpVersion)
	{
		if (Z_TYPE_P(value) == IS_NULL) return zv::Val::integer(0);
		if (Z_TYPE_P(value) == IS_TRUE || Z_TYPE_P(value) == IS_FALSE || (Z_TYPE_P(value) == IS_DOUBLE && std::isnan(Z_DVAL_P(value)))) return zv::Val::null();
		if (Z_TYPE_P(value) != IS_STRING) return zv::Val::copyOf(zv::Ref(value));

		zv::Args parseArgs{value, phpVersion};
		IETR_VAL(number, looseComparisonHelper(PT_LC("parsenumericstring"), 2, parseArgs));
		if (Z_TYPE_P(number.raw()) == IS_ARRAY) {
			zval *parsed = zend_hash_index_find(Z_ARRVAL_P(number.raw()), 0);
			return zv::Val::copyOf(zv::Ref(parsed));
		}
		bool casts;
		if (UNEXPECTED(!pt_php_version_answer(phpVersion, PT_PHP_VERSION_CASTS_NUMBERS_TO_STRINGS_ON_LOOSE_COMPARISON, casts))) return zv::Val();
		if (!casts) {
			// the number the string starts with
			IETR_VAL(prefix, looseComparisonHelper(PT_LC("parsenumericprefix"), 1, value));
			if (Z_TYPE_P(prefix.raw()) == IS_NULL) return zv::Val::integer(0);
			return prefix;
		}

		// a number compares with a non-numeric string as its own string
		for (double infinity : {HUGE_VAL, -HUGE_VAL}) {
			zval infinityValue;
			ZVAL_DOUBLE(&infinityValue, infinity);
			IETR_VAL(string, getNumberString(&infinityValue));
			if (zend_string_equals(Z_STR_P(string.raw()), Z_STR_P(value))) return zv::Val::adopt(infinityValue);
		}

		return zv::Val::null();
	}

	/* getTypeLooselyEqualTo() */
	static zv::Val getTypeLooselyEqualTo(zval *value, zval *phpVersion)
	{
		// null compares with a string as with '', with anything else by truthiness, like the booleans
		zv::Arr types = zv::Arr::create(8);
		bool truthy = zend_is_true(value);
		IETR_VAL(booleanType, newConstantBooleanType(truthy));
		types.push(std::move(booleanType));
		if (Z_TYPE_P(value) == IS_STRING ? Z_STRLEN_P(value) == 0 : !truthy) {
			IETR_VAL(nullType, newNullType());
			types.push(std::move(nullType));
		}
		if (Z_TYPE_P(value) == IS_NULL) {
			// and with an array by its emptiness
			IETR_VAL(emptyArray, emptyConstantArrayType());
			types.push(std::move(emptyArray));
		}

		IETR_VAL(number, getLooselyEqualNumber(value, phpVersion));
		bool hasNumber = Z_TYPE_P(number.raw()) != IS_NULL;
		if (hasNumber) {
			// both zeros
			double floatNumber = zval_get_double(number.raw());
			IETR_VAL(floatType, pt_float_range_from_interval(floatNumber, floatNumber));
			types.push(std::move(floatType));
			if (Z_TYPE_P(number.raw()) == IS_LONG) {
				IETR_VAL(integerType, newConstantIntegerType(Z_LVAL_P(number.raw())));
				types.push(std::move(integerType));
			} else if (std::floor(floatNumber) == floatNumber && std::fabs(floatNumber) <= (double) EXACT_FLOAT_INTEGER_LIMIT) {
				IETR_VAL(integerType, newConstantIntegerType((zend_long) floatNumber));
				types.push(std::move(integerType));
			}
		}

		// the string itself, the number's own string, and the strings that are falsy
		std::vector<zv::Val> strings;
		strings.push_back(zv::Val::string(PT_LC("")));
		strings.push_back(zv::Val::string(PT_LC("0")));
		if (hasNumber) {
			IETR_VAL(numberString, getNumberString(number.raw()));
			strings.push_back(std::move(numberString));
		}
		if (Z_TYPE_P(value) == IS_STRING) strings.push_back(zv::Val::copyOf(zv::Ref(value)));
		for (zv::Val &string : strings) {
			zv::Args compareArgs{value, string.raw(), phpVersion};
			IETR_VAL(comparison, looseComparisonHelper(PT_LC("comparevalues"), 3, compareArgs));
			if (Z_TYPE_P(comparison.raw()) != IS_LONG || Z_LVAL_P(comparison.raw()) != 0) continue;

			IETR_VAL(stringType, newConstantStringType(Z_STR_P(string.raw())));
			types.push(std::move(stringType));
		}

		return unionOf(types);
	}

	/* getIntegerTypeInInterval() */
	static zv::Val getIntegerTypeInInterval(double min, double max)
	{
		if (min > max || min > (double) ZEND_LONG_MAX || max < (double) ZEND_LONG_MIN) return newNeverType();

		constexpr double limit = (double) EXACT_FLOAT_INTEGER_LIMIT;
		NullableLong integerMin, integerMax;
		if (min < -limit) {
			integerMin = NullableLong::null();
		} else if (min > limit) {
			integerMin = NullableLong::of(EXACT_FLOAT_INTEGER_LIMIT);
		} else {
			integerMin = NullableLong::of((zend_long) std::ceil(min));
		}

		if (max > limit) {
			integerMax = NullableLong::null();
		} else if (max < -limit) {
			integerMax = NullableLong::of(-EXACT_FLOAT_INTEGER_LIMIT);
		} else {
			integerMax = NullableLong::of((zend_long) std::floor(max));
		}

		return fromInterval(integerMin, integerMax);
	}

	/* createNumberType() */
	static zv::Val createNumberType(zval *number)
	{
		double floatNumber = zval_get_double(number);
		IETR_VAL(integerType, Z_TYPE_P(number) == IS_LONG ? newConstantIntegerType(Z_LVAL_P(number)) : getIntegerTypeInInterval(floatNumber, floatNumber));
		IETR_VAL(floatType, pt_float_range_from_interval(floatNumber, floatNumber));
		return union2(integerType.raw(), floatType.raw());
	}

	/* $number >= 1 && floor($number) === (float) $number */
	static bool isResourceId(zval *number)
	{
		if (Z_TYPE_P(number) == IS_LONG) return Z_LVAL_P(number) >= 1;
		double value = Z_DVAL_P(number);
		return value >= 1.0 && std::floor(value) == value;
	}

	/* getBooleanAndNullPartnerType() */
	static zv::Val getBooleanAndNullPartnerType(bool canBeZero, bool canBeNonZero)
	{
		zv::Arr types = zv::Arr::create(3);
		if (canBeZero) {
			IETR_VAL(nullType, newNullType());
			types.push(std::move(nullType));
			IETR_VAL(falseType, newConstantBooleanType(false));
			types.push(std::move(falseType));
		}
		if (canBeNonZero) {
			IETR_VAL(trueType, newConstantBooleanType(true));
			types.push(std::move(trueType));
		}
		return unionOf(types);
	}

	static bool canBeZero(const NumberSetData &numbers)
	{
		for (const NumberSetData::IntegerInterval &interval : numbers.integerIntervals) {
			zend_long min = interval.min.isNull ? ZEND_LONG_MIN : interval.min.value;
			zend_long max = interval.max.isNull ? ZEND_LONG_MAX : interval.max.value;
			if (min <= 0 && max >= 0) return true;
		}
		for (const NumberSetData::FloatInterval &interval : numbers.floatIntervals) {
			if (interval.min <= 0.0 && interval.max >= 0.0) return true;
		}
		return false;
	}

	static bool canBeNonZero(const NumberSetData &numbers)
	{
		if (numbers.nan) return true;
		for (const NumberSetData::IntegerInterval &interval : numbers.integerIntervals) {
			if (interval.min.isNull || interval.max.isNull || interval.min.value != interval.max.value || interval.min.value != 0) return true;
		}
		for (const NumberSetData::FloatInterval &interval : numbers.floatIntervals) {
			if (interval.min != interval.max || interval.min != 0.0) return true;
		}
		return false;
	}

	/* getNumberPartnerType() */
	static zv::Val getNumberPartnerType(const NumberSetData &numbers, zval *phpVersion)
	{
		// an integer and a float are equal when the integer converted to float is the float
		zv::Arr integerTypes = zv::Arr::create(4);
		std::vector<NumberSetData::FloatInterval> floatIntervals;
		for (const NumberSetData::IntegerInterval &interval : numbers.integerIntervals) {
			IETR_VAL(rangeType, fromInterval(interval.min, interval.max));
			integerTypes.push(std::move(rangeType));
			floatIntervals.push_back({
				(double) (interval.min.isNull ? ZEND_LONG_MIN : interval.min.value),
				(double) (interval.max.isNull ? ZEND_LONG_MAX : interval.max.value),
			});
		}
		for (const NumberSetData::FloatInterval &interval : numbers.floatIntervals) {
			IETR_VAL(integerType, getIntegerTypeInInterval(interval.min, interval.max));
			integerTypes.push(std::move(integerType));
			floatIntervals.push_back(interval);
		}
		if (floatIntervals.empty()) return newNeverType();

		IETR_VAL(integerType, unionOf(integerTypes));
		zv::Arr types = zv::Arr::create(8);
		types.push(zv::Ref(integerType.raw()));
		IETR_VAL(numericString, createNumericStringType());
		types.push(std::move(numericString));
		bool containsOne = false;
		bool containsInfinity = false;
		bool containsNegativeInfinity = false;
		for (const NumberSetData::FloatInterval &interval : floatIntervals) {
			IETR_VAL(rangeType, pt_float_range_from_interval(interval.min, interval.max));
			types.push(std::move(rangeType));
			containsOne = containsOne || (interval.min <= 1.0 && interval.max >= 1.0);
			containsInfinity = containsInfinity || interval.max == HUGE_VAL;
			containsNegativeInfinity = containsNegativeInfinity || interval.min == -HUGE_VAL;
		}

		// an object compares as 1, unless it is number-like
		IETR_VAL(objectType, containsOne ? objectWithoutClassType() : createNumberLikeObjectType(phpVersion));
		types.push(std::move(objectType));
		IETR_VAL(positive, fromInterval(NullableLong::of(1), NullableLong::null()));
		zv::Args intersectArgs{integerType.raw(), positive.raw()};
		IETR_VAL(positiveIntegers, pt_type_combinator_intersect(2, intersectArgs));
		zend_long isInteger = triOn(positiveIntegers.raw(), mIsInteger);
		if (UNEXPECTED(isInteger < 0)) return zv::Val();
		if (isInteger != PT_TRI_NO) {
			// as its integer ID
			IETR_VAL(resource, resourceType());
			types.push(std::move(resource));
		}
		bool casts;
		if (UNEXPECTED(!pt_php_version_answer(phpVersion, PT_PHP_VERSION_CASTS_NUMBERS_TO_STRINGS_ON_LOOSE_COMPARISON, casts))) return zv::Val();
		if (!casts) {
			// as the number the string starts with
			IETR_VAL(stringType, newStringType());
			types.push(std::move(stringType));
		} else {
			// as its own string
			if (containsInfinity) {
				IETR_VAL(infinity, constantStringLiteral(PT_LC("INF")));
				types.push(std::move(infinity));
			}
			if (containsNegativeInfinity) {
				IETR_VAL(negativeInfinity, constantStringLiteral(PT_LC("-INF")));
				types.push(std::move(negativeInfinity));
			}
		}

		return unionOf(types);
	}

	/* getConstantStringPartnerType() */
	static zv::Val getConstantStringPartnerType(zval *value, zval *phpVersion)
	{
		zend_string *string = Z_STR_P(value);
		zv::Arr types = zv::Arr::create(8);
		IETR_VAL(constantType, newConstantStringType(string));
		types.push(std::move(constantType));
		IETR_VAL(stringable, createStringableObjectType(phpVersion));
		types.push(std::move(stringable));
		bool truthy = ZSTR_LEN(string) != 0 && !zend_string_equals_literal(string, "0");
		IETR_VAL(booleanAndNull, getBooleanAndNullPartnerType(false, truthy));
		types.push(std::move(booleanAndNull));
		IETR_VAL(looselyEqual, getTypeLooselyEqualTo(value, phpVersion));
		types.push(std::move(looselyEqual));

		zv::Args parseArgs{value, phpVersion};
		IETR_VAL(parsed, looseComparisonHelper(PT_LC("parsenumericstring"), 2, parseArgs));
		zv::Val number;
		if (Z_TYPE_P(parsed.raw()) == IS_ARRAY) {
			number = zv::Val::copyOf(zv::Ref(zend_hash_index_find(Z_ARRVAL_P(parsed.raw()), 0)));
			IETR_VAL(numericString, createNumericStringType());
			types.push(std::move(numericString));
		} else {
			bool casts;
			if (UNEXPECTED(!pt_php_version_answer(phpVersion, PT_PHP_VERSION_CASTS_NUMBERS_TO_STRINGS_ON_LOOSE_COMPARISON, casts))) return zv::Val();
			if (!casts) {
				number = looseComparisonHelper(PT_LC("parsenumericprefix"), 1, value);
				if (UNEXPECTED(number.isUndef())) return zv::Val();
				if (Z_TYPE_P(number.raw()) == IS_NULL) number = zv::Val::integer(0);
			}
		}
		if (!number.isNull()) {
			IETR_VAL(numberType, createNumberType(number.raw()));
			types.push(std::move(numberType));
			if (isResourceId(number.raw())) {
				IETR_VAL(resource, resourceType());
				types.push(std::move(resource));
			}
		}

		return unionOf(types);
	}

	/* getStringPartnerType() */
	static zv::Val getStringPartnerType(zval *strings, zval *phpVersion)
	{
		// a string compares with null as with '', and with a bool by its truthiness
		zv::Arr booleanAndNullTypes = zv::Arr::create(3);
		IETR_VAL(trueType, newConstantBooleanType(true));
		booleanAndNullTypes.push(std::move(trueType));
		zend_long isNonEmpty = triOn(strings, mIsNonEmptyString);
		if (UNEXPECTED(isNonEmpty < 0)) return zv::Val();
		if (isNonEmpty != PT_TRI_YES) {
			IETR_VAL(nullType, newNullType());
			booleanAndNullTypes.push(std::move(nullType));
		}
		zend_long isNonFalsy = triOn(strings, mIsNonFalsyString);
		if (UNEXPECTED(isNonFalsy < 0)) return zv::Val();
		if (isNonFalsy != PT_TRI_YES) {
			IETR_VAL(falseType, newConstantBooleanType(false));
			booleanAndNullTypes.push(std::move(falseType));
		}
		IETR_VAL(booleanAndNull, unionOf(booleanAndNullTypes));

		zend_long isNumeric = triOn(strings, mIsNumericString);
		if (UNEXPECTED(isNumeric < 0)) return zv::Val();
		zv::Arr types = zv::Arr::create(7);
		if (isNumeric == PT_TRI_YES) {
			// a numeric string compares with a non-numeric one byte by byte, so they differ
			IETR_VAL(integerType, newIntegerType());
			types.push(std::move(integerType));
			IETR_VAL(floatRange, pt_float_range_from_interval(-HUGE_VAL, HUGE_VAL));
			types.push(std::move(floatRange));
			IETR_VAL(numericString, createNumericStringType());
			types.push(std::move(numericString));
		} else {
			// a non-numeric string equals only itself
			types.push(zv::Ref(strings));
			IETR_VAL(numericString, isNumeric == PT_TRI_NO ? newNeverType() : createNumericStringType());
			types.push(std::move(numericString));
			IETR_VAL(integerType, newIntegerType());
			types.push(std::move(integerType));
			IETR_VAL(floatType, newFloatType());
			types.push(std::move(floatType));
		}
		IETR_VAL(stringable, createStringableObjectType(phpVersion));
		types.push(std::move(stringable));
		IETR_VAL(resource, resourceType());
		types.push(std::move(resource));
		types.push(std::move(booleanAndNull));

		return unionOf(types);
	}

	/* getArrayPartnerType() */
	static zv::Val getArrayPartnerType(bool empty, bool nonEmpty)
	{
		if (!nonEmpty) return emptyArrayPartner();

		IETR_VAL(arrayType, mixedArrayType());
		if (!empty) {
			IETR_VAL(trueType, newConstantBooleanType(true));
			IETR_VAL(nonEmptyType, nonEmptyArrayType());
			zv::Args argv{arrayType.raw(), nonEmptyType.raw()};
			IETR_VAL(nonEmptyArray, pt_type_combinator_intersect(2, argv));
			return union2(trueType.raw(), nonEmptyArray.raw());
		}

		IETR_VAL(booleanType, newBooleanType());
		IETR_VAL(nullType, newNullType());
		zv::Arr types = zv::Arr::create(3);
		types.push(std::move(arrayType));
		types.push(std::move(booleanType));
		types.push(std::move(nullType));
		return unionOf(types);
	}

	/* union(new NullType(), new ConstantBooleanType(false), new ConstantArrayType([], [])) */
	static zv::Val emptyArrayPartner()
	{
		zv::Arr types = zv::Arr::create(3);
		IETR_VAL(nullType, newNullType());
		types.push(std::move(nullType));
		IETR_VAL(falseType, newConstantBooleanType(false));
		types.push(std::move(falseType));
		IETR_VAL(emptyArray, emptyConstantArrayType());
		types.push(std::move(emptyArray));
		return unionOf(types);
	}

	static zv::Val getPossiblyLooselyEqualType(ValueSetView s, zval *phpVersion)
	{
		if (s.unknown() || s.resources()) return newMixedType();

		zv::Arr types = zv::Arr::create(8);
		if (s.null_()) {
			zval nullValue;
			ZVAL_NULL(&nullValue);
			IETR_VAL(type, getTypeLooselyEqualTo(&nullValue, phpVersion));
			types.push(std::move(type));
		}
		if (s.true_()) {
			IETR_VAL(truthy, pt_static_type_factory_truthy());
			types.push(std::move(truthy));
		}
		if (s.false_()) {
			IETR_VAL(falsey, pt_static_type_factory_falsey());
			types.push(std::move(falsey));
			IETR_VAL(numberLike, createNumberLikeObjectType(phpVersion));
			types.push(std::move(numberLike));
		}
		if (s.numbers() != nullptr) {
			NumberSetData numbers;
			if (UNEXPECTED(!readNumbers(s.numbers(), numbers))) return zv::Val();
			IETR_VAL(numberPartner, getNumberPartnerType(numbers, phpVersion));
			types.push(std::move(numberPartner));
			IETR_VAL(booleanAndNull, getBooleanAndNullPartnerType(canBeZero(numbers), canBeNonZero(numbers)));
			types.push(std::move(booleanAndNull));
		}
		zval *entry;
		ZEND_HASH_FOREACH_VAL(s.constantStrings(), entry) {
			IETR_VAL(value, constantStringValue(entry));
			IETR_VAL(partner, getConstantStringPartnerType(value.raw(), phpVersion));
			types.push(std::move(partner));
		} ZEND_HASH_FOREACH_END();
		if (s.stringType() != nullptr) {
			IETR_VAL(partner, getStringPartnerType(s.stringType(), phpVersion));
			types.push(std::move(partner));
		}
		if (s.emptyArray() || s.nonEmptyArray()) {
			IETR_VAL(partner, getArrayPartnerType(s.emptyArray(), s.nonEmptyArray()));
			types.push(std::move(partner));
		}
		if (s.objects() != nullptr) {
			zend_long isEnum = triOn(s.objects(), mIsEnum);
			if (UNEXPECTED(isEnum < 0)) return zv::Val();
			if (isEnum != PT_TRI_YES) return newMixedType();

			// an enum case equals only itself, and true
			types.push(zv::Ref(s.objects()));
			IETR_VAL(trueType, newConstantBooleanType(true));
			types.push(std::move(trueType));
		}

		return unionOf(types);
	}

	static zv::Val getAlwaysLooselyEqualType(ValueSetView s, zval *phpVersion)
	{
		if (s.unknown() || s.stringType() != nullptr || s.nonEmptyArray() || s.resources()) return newNeverType();

		std::vector<zv::Val> types;
		if (s.null_()) {
			zval nullValue;
			ZVAL_NULL(&nullValue);
			IETR_VAL(type, getTypeLooselyEqualTo(&nullValue, phpVersion));
			types.push_back(std::move(type));
		}
		if (s.true_()) {
			IETR_VAL(falsey, pt_static_type_factory_falsey());
			IETR_VAL(numberLike, createNumberLikeObjectType(phpVersion));
			IETR_VAL(subtracted, union2(falsey.raw(), numberLike.raw()));
			zval mixed;
			if (UNEXPECTED(!pt_mixed_type_new(&mixed, false, subtracted.raw()))) return zv::Val();
			types.push_back(zv::Val::adopt(mixed));
		}
		if (s.false_()) {
			IETR_VAL(falsey, pt_static_type_factory_falsey());
			types.push_back(std::move(falsey));
		}
		if (s.numbers() != nullptr) {
			NumberSetData numbers;
			if (UNEXPECTED(!readNumbers(s.numbers(), numbers))) return zv::Val();
			for (const NumberSetData::IntegerInterval &interval : numbers.integerIntervals) {
				if (interval.min.isNull || interval.max.isNull || interval.min.value != interval.max.value) return newNeverType();
			}
			for (const NumberSetData::FloatInterval &interval : numbers.floatIntervals) {
				if (interval.min != interval.max) return newNeverType();
			}
			for (const NumberSetData::IntegerInterval &interval : numbers.integerIntervals) {
				zval number;
				ZVAL_LONG(&number, interval.min.value);
				IETR_VAL(type, getTypeLooselyEqualTo(&number, phpVersion));
				types.push_back(std::move(type));
			}
			for (const NumberSetData::FloatInterval &interval : numbers.floatIntervals) {
				zval number;
				ZVAL_DOUBLE(&number, interval.min);
				IETR_VAL(type, getTypeLooselyEqualTo(&number, phpVersion));
				types.push_back(std::move(type));
			}
			if (numbers.nan) {
				zval number;
				ZVAL_DOUBLE(&number, std::numeric_limits<double>::quiet_NaN());
				IETR_VAL(type, getTypeLooselyEqualTo(&number, phpVersion));
				types.push_back(std::move(type));
			}
		}
		zval *entry;
		ZEND_HASH_FOREACH_VAL(s.constantStrings(), entry) {
			IETR_VAL(value, constantStringValue(entry));
			IETR_VAL(type, getTypeLooselyEqualTo(value.raw(), phpVersion));
			types.push_back(std::move(type));
		} ZEND_HASH_FOREACH_END();
		if (s.emptyArray()) {
			IETR_VAL(partner, emptyArrayPartner());
			types.push_back(std::move(partner));
		}
		if (s.objects() != nullptr) {
			zend_long isEnum = triOn(s.objects(), mIsEnum);
			if (UNEXPECTED(isEnum < 0)) return zv::Val();
			if (isEnum != PT_TRI_YES) return newNeverType();
			IETR_VAL(enumCases, callOn(s.objects(), mGetEnumCases));
			HashTable *cases = Z_ARRVAL_P(enumCases.raw());
			if (zend_hash_num_elements(cases) != 1) return newNeverType();

			IETR_VAL(trueType, newConstantBooleanType(true));
			IETR_VAL(type, union2(zend_hash_index_find(cases, 0), trueType.raw()));
			types.push_back(std::move(type));
		}

		// one value at a time: intersecting all the unions at once multiplies them out
		zv::Val alwaysEqualType;
		for (zv::Val &type : types) {
			if (alwaysEqualType.isUndef()) {
				alwaysEqualType = std::move(type);
			} else {
				zv::Args argv{alwaysEqualType.raw(), type.raw()};
				alwaysEqualType = pt_type_combinator_intersect(2, argv);
				if (UNEXPECTED(alwaysEqualType.isUndef())) return zv::Val();
			}
			if (isA(alwaysEqualType.raw(), pt_ce_never_type)) break;
		}

		if (alwaysEqualType.isUndef()) return newNeverType();
		return alwaysEqualType;
	}

	/* }}} */

private:
	static zv::Val objectValue(ValueSetView s)
	{
		zval object;
		ZVAL_OBJ_COPY(&object, s.object());
		return zv::Val::adopt(object);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ValueSetOps;
using phpstanturbo::ValueSetView;

zv::Val pt_value_set_create(const char *lcFactory, size_t len, uint32_t argc, zval *argv)
{
	return pt_type_call_static_ce(pt_ce_value_set, lcFactory, len, argc, argv);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_SELF ValueSetView(Z_OBJ_P(ZEND_THIS))

/* an argument of the twin's `self` / class-typed parameter: the TypeError
 * of its strict-types call for any other value */
static bool requireInstance(zval *value, zend_class_entry *ce, uint32_t position, const char *name)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce))) return true;
	zend_type_error("%s::%s(): Argument #%u ($%s) must be of type %s, %s given", ZSTR_VAL(pt_ce_value_set->name), get_active_function_name(), position, name, ZSTR_VAL(ce->name), zend_zval_value_name(value));
	return false;
}

/* (self $right, PhpVersion $phpVersion) operators */
template <zv::Val (*Operator)(ValueSetView, ValueSetView, zval *)>
static void pt_vs_operator(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *right, *phpVersion;
	if (!zp::parse<zp::Obj, zp::Obj>(execute_data, right, phpVersion)) RETURN_THROWS();
	if (UNEXPECTED(!requireInstance(right, pt_ce_value_set, 1, "right"))) RETURN_THROWS();
	PT_RETURN_VAL(Operator(PT_SELF, ValueSetView(Z_OBJ_P(right)), phpVersion));
}

static zv::Val vsSubtract(ValueSetView left, ValueSetView right, zval *phpVersion)
{
	return ValueSetOps::subtractOrMultiply(ValueSetOps::OP_MINUS, left, right, phpVersion);
}

static zv::Val vsMultiply(ValueSetView left, ValueSetView right, zval *phpVersion)
{
	return ValueSetOps::subtractOrMultiply(ValueSetOps::OP_MUL, left, right, phpVersion);
}

static zv::Val vsIntDivide(ValueSetView left, ValueSetView right, zval *)
{
	return ValueSetOps::arithmeticIntDivide(left, right);
}

template <ValueSetOps::Op Op>
static zv::Val vsBitwise(ValueSetView left, ValueSetView right, zval *)
{
	return ValueSetOps::calculateBitwise(Op, left, right);
}

template <ValueSetOps::Op Op>
static zv::Val vsShift(ValueSetView left, ValueSetView right, zval *phpVersion)
{
	return ValueSetOps::calculateShift(Op, left, right, phpVersion);
}

PT_MINIT_REGISTRATION(pt_register_value_set)
{
	reg::Class cls("PHPStan\\Type\\ValueSet");
	ptdecl::ValueSet::declareClass(cls);
	cls.privateClassConstantLong("EXACT_FLOAT_INTEGER_LIMIT", ValueSetOps::EXACT_FLOAT_INTEGER_LIMIT);
	cls.privateClassConstantLong("EXACT_FLOAT_DIGITS", ValueSetOps::EXACT_FLOAT_DIGITS);
	cls.privateClassConstantLong("SHORT_FLOAT_DIGITS", ValueSetOps::SHORT_FLOAT_DIGITS);
	/* the fifteen properties must stay the declared ones in this order (slots::*) */
	ptdecl::ValueSet::declareProperties(cls);

	cls.method(sigs::createUnknown, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type = NULL;
		ZEND_PARSE_PARAMETERS_START(0, 1)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(type)
		ZEND_PARSE_PARAMETERS_END();
		if (type != NULL && UNEXPECTED(!requireInstance(type, pt_ce_mixed_type, 1, "type"))) RETURN_THROWS();
		PT_RETURN_VAL(ValueSetOps::createUnknown(type));
	});
	cls.method(sigs::isPlusable, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(ValueSetOps::isPlusable(PT_SELF));
	});
	cls.method(sigs::isUnknown, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(ValueSetOps::isUnknown(PT_SELF));
	});
	cls.method(sigs::createEmpty, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(ValueSetOps::createEmpty());
	});
	cls.method(sigs::createInvalid, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(ValueSetOps::createInvalid());
	});
	cls.method(sigs::createNever, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *never;
		if (!zp::parse<zp::Obj>(execute_data, never)) RETURN_THROWS();
		if (UNEXPECTED(!requireInstance(never, pt_ce_never_type, 1, "never"))) RETURN_THROWS();
		PT_RETURN_VAL(ValueSetOps::createNever(never));
	});
	cls.method(sigs::createNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(ValueSetOps::createNull());
	});
	cls.method(sigs::createBooleans, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool falseValue, trueValue;
		if (!zp::parse<zp::Bool, zp::Bool>(execute_data, falseValue, trueValue)) RETURN_THROWS();
		PT_RETURN_VAL(ValueSetOps::createBooleans(falseValue, trueValue));
	});
	cls.method(sigs::createNumbers, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *numbers;
		if (!zp::parse<zp::Obj>(execute_data, numbers)) RETURN_THROWS();
		if (UNEXPECTED(!requireInstance(numbers, pt_number_set_class(), 1, "numbers"))) RETURN_THROWS();
		PT_RETURN_VAL(ValueSetOps::createNumbers(zv::Val::copyOf(zv::Ref(numbers))));
	});
	cls.method(sigs::createConstantString, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *string;
		if (!zp::parse<zp::Obj>(execute_data, string)) RETURN_THROWS();
		if (UNEXPECTED(!requireInstance(string, pt_ce_constant_string_type, 1, "string"))) RETURN_THROWS();
		PT_RETURN_VAL(ValueSetOps::createConstantString(string));
	});
	cls.method(sigs::createStrings, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *strings;
		if (!zp::parse<zp::TypeObj>(execute_data, strings)) RETURN_THROWS();
		PT_RETURN_VAL(ValueSetOps::createStrings(strings));
	});
	cls.method(sigs::createArrays, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *arrays;
		if (!zp::parse<zp::TypeObj>(execute_data, arrays)) RETURN_THROWS();
		PT_RETURN_VAL(ValueSetOps::createArrays(arrays));
	});
	cls.method(sigs::createObjects, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objects;
		if (!zp::parse<zp::TypeObj>(execute_data, objects)) RETURN_THROWS();
		PT_RETURN_VAL(ValueSetOps::createObjects(objects));
	});
	cls.method(sigs::createResources, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(ValueSetOps::createResources());
	});
	cls.method(sigs::toBenevolent, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(ValueSetOps::toBenevolent(Z_OBJ_P(ZEND_THIS)));
	});
	cls.method(sigs::isEmpty, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(ValueSetOps::isEmpty(PT_SELF));
	});
	cls.method(sigs::union_, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		if (!zp::parse<zp::Obj>(execute_data, other)) RETURN_THROWS();
		if (UNEXPECTED(!requireInstance(other, pt_ce_value_set, 1, "other"))) RETURN_THROWS();
		PT_RETURN_VAL(ValueSetOps::union_(PT_SELF, ValueSetView(Z_OBJ_P(other))));
	});
	cls.method(sigs::intersect, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		if (!zp::parse<zp::Obj>(execute_data, other)) RETURN_THROWS();
		if (UNEXPECTED(!requireInstance(other, pt_ce_value_set, 1, "other"))) RETURN_THROWS();
		PT_RETURN_VAL(ValueSetOps::intersect(PT_SELF, ValueSetView(Z_OBJ_P(other))));
	});
	cls.method(sigs::getPossiblyLooselyEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *phpVersion;
		if (!zp::parse<zp::Obj>(execute_data, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(ValueSetOps::getPossiblyLooselyEqualType(PT_SELF, phpVersion));
	});
	cls.method(sigs::getAlwaysLooselyEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *phpVersion;
		if (!zp::parse<zp::Obj>(execute_data, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(ValueSetOps::getAlwaysLooselyEqualType(PT_SELF, phpVersion));
	});
	cls.method(sigs::arithmeticAdd, pt_vs_operator<ValueSetOps::arithmeticAdd>);
	cls.method(sigs::arithmeticSubtract, pt_vs_operator<vsSubtract>);
	cls.method(sigs::arithmeticMultiply, pt_vs_operator<vsMultiply>);
	cls.method(sigs::arithmeticDivide, pt_vs_operator<ValueSetOps::arithmeticDivide>);
	cls.method(sigs::arithmeticIntDivide, pt_vs_operator<vsIntDivide>);
	cls.method(sigs::arithmeticModulo, pt_vs_operator<ValueSetOps::arithmeticModulo>);
	cls.method(sigs::arithmeticPower, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *exponent, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, exponent, phpVersion)) RETURN_THROWS();
		if (UNEXPECTED(!requireInstance(exponent, pt_ce_value_set, 1, "exponent"))) RETURN_THROWS();
		PT_RETURN_VAL(ValueSetOps::arithmeticPower(PT_SELF, ValueSetView(Z_OBJ_P(exponent)), phpVersion));
	});
	cls.method(sigs::arithmeticNegate, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *phpVersion;
		if (!zp::parse<zp::Obj>(execute_data, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(ValueSetOps::arithmeticNegate(PT_SELF, phpVersion));
	});
	cls.method(sigs::bitwiseAnd, pt_vs_operator<vsBitwise<ValueSetOps::OP_AND>>);
	cls.method(sigs::bitwiseOr, pt_vs_operator<vsBitwise<ValueSetOps::OP_OR>>);
	cls.method(sigs::bitwiseXor, pt_vs_operator<vsBitwise<ValueSetOps::OP_XOR>>);
	cls.method(sigs::bitwiseShiftLeft, pt_vs_operator<vsShift<ValueSetOps::OP_SHIFT_LEFT>>);
	cls.method(sigs::bitwiseShiftRight, pt_vs_operator<vsShift<ValueSetOps::OP_SHIFT_RIGHT>>);
	cls.method(sigs::stringConcatenate, pt_vs_operator<ValueSetOps::stringConcatenate>);

	cls.shadow(&pt_ce_value_set);
}

/* }}} */
