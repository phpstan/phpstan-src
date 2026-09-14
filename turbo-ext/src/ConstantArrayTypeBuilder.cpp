/*
 * PHPStanTurbo\ConstantArrayTypeBuilder — native implementation of
 * PHPStan\Type\Constant\ConstantArrayTypeBuilder.
 *
 * Declared as PHPStan\Type\Constant\ConstantArrayTypeBuilder itself at
 * activation: final, with the twin's private constructor behind its two
 * static factories. State is the twin's eleven private properties — the
 * five flags declared in the class body (with their defaults) followed by
 * the six promoted constructor parameters, declared typed slots in the
 * twin's declaration order — mutated in place exactly as the twin's
 * methods mutate them (an array slot shared with the ConstantArrayType the
 * builder was created from is separated on the first write, as PHP's
 * copy-on-write does).
 *
 * Every Type the twin instantiates is a shadowed class built through its
 * exported constructor, TypeCombinator runs through the native combinator's
 * entry points, and the constant keys' values are read the way the shadowed
 * constant types expose them. The other native classes drive a builder
 * through the exported pt_constant_array_type_builder_*() helpers — direct
 * C++ calls, no engine frames.
 */

#include "TypeTraits.h"
#include "generated/ConstantArrayTypeBuilder.h"

namespace slots = ptdecl::ConstantArrayTypeBuilder::slot;
namespace sigs = ptdecl::ConstantArrayTypeBuilder::sig;

zend_class_entry *pt_ce_constant_array_type_builder = nullptr;

/* the twin's constants */
#define PT_CATB_ARRAY_COUNT_LIMIT PT_CONSTANT_ARRAY_TYPE_BUILDER_ARRAY_COUNT_LIMIT
#define PT_CATB_CLOSURES_COUNT_LIMIT 32

/* the shadowed ConstantArrayType's slots createFromConstantArray() reads
 * for an instance of exactly that class, in its twin's declaration order
 * (ConstantArrayType.cpp's PT_CAT_PROP_*: the getters return the slots) */
#define PT_CATB_CAT_PROP_IS_LIST 0
#define PT_CATB_CAT_PROP_UNSEALED 1
#define PT_CATB_CAT_PROP_KEY_TYPES 8
#define PT_CATB_CAT_PROP_VALUE_TYPES 9
#define PT_CATB_CAT_PROP_NEXT_AUTO_INDEXES 10
#define PT_CATB_CAT_PROP_OPTIONAL_KEYS 11

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Constant\ConstantArrayTypeBuilder. State lives in
 * the PHP object's slots. */
class ConstantArrayTypeBuilder
{
public:
	explicit ConstantArrayTypeBuilder(zend_object *self) : self(self) {}

	/* the private constructor's body: the promoted slots, then
	 * $this->isNonEmpty = TrinaryLogic::createNo() (the flags keep their
	 * declared defaults); the arrays and the trinary borrowed, $unsealed
	 * IS_NULL or a pair */
	void construct(zval *keyTypes, zval *valueTypes, zval *nextAutoIndexes, zval *optionalKeys, zval *isList, zval *unsealed)
	{
		zv::ObjRef ref(self);
		ref.propAtWrite(slots::keyTypes, zv::Val::copyOf(zv::Ref(keyTypes)));
		ref.propAtWrite(slots::valueTypes, zv::Val::copyOf(zv::Ref(valueTypes)));
		ref.propAtWrite(slots::nextAutoIndexes, zv::Val::copyOf(zv::Ref(nextAutoIndexes)));
		ref.propAtWrite(slots::optionalKeys, zv::Val::copyOf(zv::Ref(optionalKeys)));
		ref.propAtWrite(slots::isList, zv::Val::copyOf(zv::Ref(isList)));
		ref.propAtWrite(slots::unsealed, zv::Val::copyOf(zv::Ref(unsealed)));
		ref.propAtWrite(slots::isNonEmpty, zv::Val::copyOf(zv::Ref(pt_trinary_singleton(PT_TRI_NO))));
	}

	/* new self([], [], [0], [], TrinaryLogic::createYes(), <null, or a pair
	 * of explicit nevers under bleeding edge>); UNDEF = pending exception */
	static zv::Val createEmpty()
	{
		zv::Val unsealed = zv::Val::null();
		bool bleedingEdge;
		if (UNEXPECTED(!isBleedingEdge(bleedingEdge))) return zv::Val();
		if (bleedingEdge) {
			zval neverRaw;
			if (UNEXPECTED(!pt_never_type_new(&neverRaw, true))) return zv::Val();
			zv::Val never = zv::Val::adopt(neverRaw);
			unsealed = zv::Val(pairOf(never.raw(), never.raw()));
		}
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		zv::Arr zero = zv::Arr::create(1);
		zero.push(zv::Val::integer(0));
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_constant_array_type_builder) != SUCCESS)) return zv::Val();
		ConstantArrayTypeBuilder(Z_OBJ(object)).construct(&empty, &empty, zero.raw(), &empty, pt_trinary_singleton(PT_TRI_YES), unsealed.raw());
		return zv::Val::adopt(object);
	}

	/* new self(<the array's key types, value types, next auto-indexes,
	 * optional keys, list-ness and unsealed pair>), non-empty as the array
	 * is, degraded when the array holds more keys than the limit; UNDEF =
	 * pending exception */
	static zv::Val createFromConstantArray(zval *startArrayType)
	{
		zend_object *array = Z_OBJ_P(startArrayType);
		zv::Val keyTypes = arrayPart(array, PT_CATB_CAT_PROP_KEY_TYPES, PT_LC("getkeytypes"), IS_ARRAY);
		if (UNEXPECTED(keyTypes.isUndef())) return zv::Val();
		zv::Val valueTypes = arrayPart(array, PT_CATB_CAT_PROP_VALUE_TYPES, PT_LC("getvaluetypes"), IS_ARRAY);
		if (UNEXPECTED(valueTypes.isUndef())) return zv::Val();
		zv::Val nextAutoIndexes = arrayPart(array, PT_CATB_CAT_PROP_NEXT_AUTO_INDEXES, PT_LC("getnextautoindexes"), IS_ARRAY);
		if (UNEXPECTED(nextAutoIndexes.isUndef())) return zv::Val();
		zv::Val optionalKeys = arrayPart(array, PT_CATB_CAT_PROP_OPTIONAL_KEYS, PT_LC("getoptionalkeys"), IS_ARRAY);
		if (UNEXPECTED(optionalKeys.isUndef())) return zv::Val();
		zv::Val isList = arrayPart(array, PT_CATB_CAT_PROP_IS_LIST, PT_LC("islist"), IS_OBJECT);
		if (UNEXPECTED(isList.isUndef())) return zv::Val();
		zv::Val unsealed = arrayPart(array, PT_CATB_CAT_PROP_UNSEALED, PT_LC("getunsealedtypes"), IS_NULL);
		if (UNEXPECTED(unsealed.isUndef())) return zv::Val();
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_constant_array_type_builder) != SUCCESS)) return zv::Val();
		zv::Val builder = zv::Val::adopt(object);
		ConstantArrayTypeBuilder handle(Z_OBJ(object));
		handle.construct(keyTypes.raw(), valueTypes.raw(), nextAutoIndexes.raw(), optionalKeys.raw(), isList.raw(), unsealed.raw());

		/* $builder->isNonEmpty = $startArrayType->isIterableAtLeastOnce() */
		zv::Val isNonEmpty = pt_type_op(array, PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
		if (UNEXPECTED(isNonEmpty.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(isNonEmpty.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s::isIterableAtLeastOnce() must return %s", ZSTR_VAL(array->ce->name), ptcls::trinaryLogic);
			return zv::Val();
		}
		zv::ObjRef(Z_OBJ(object)).propAtWrite(slots::isNonEmpty, std::move(isNonEmpty));

		if (zv::ArrRef(keyTypes.raw()).size() > PT_CATB_ARRAY_COUNT_LIMIT) {
			if (UNEXPECTED(!handle.degradeToGeneralArray(true))) return zv::Val();
		}

		return builder;
	}

	/* $this->unsealed = [$keyType, $valueType] */
	void makeUnsealed(zval *keyType, zval *valueType)
	{
		zv::ObjRef(self).propAtWrite(slots::unsealed, zv::Val(pairOf(keyType, valueType)));
	}

	/* the pair set when there is none or its key is an explicit never,
	 * unioned member-wise into the existing one otherwise; false = pending
	 * exception */
	[[nodiscard]] bool mergeUnsealed(zval *keyType, zval *valueType)
	{
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return false;
		if (Z_TYPE_P(unsealed) == IS_NULL) {
			makeUnsealed(keyType, valueType);
			return true;
		}
		zval *existingKey, *existingValue;
		if (UNEXPECTED(!pairParts(unsealed, existingKey, existingValue))) return false;
		bool explicitNever;
		if (UNEXPECTED(!isExplicitNever(existingKey, explicitNever))) return false;
		if (explicitNever) {
			makeUnsealed(keyType, valueType);
			return true;
		}
		zv::Val mergedKey = union2(existingKey, keyType);
		if (UNEXPECTED(mergedKey.isUndef())) return false;
		zv::Val mergedValue = union2(existingValue, valueType);
		if (UNEXPECTED(mergedValue.isUndef())) return false;
		zv::ObjRef(self).propAtWrite(slots::unsealed, zv::Val(pairOf(mergedKey.raw(), mergedValue.raw())));
		return true;
	}

	/* the twin's setOffsetValueType(?Type $offsetType, Type $valueType,
	 * bool $optional); $offsetTypeArg NULL or IS_NULL for null; false =
	 * pending exception */
	bool setOffsetValueType(zval *offsetTypeArg, zval *valueTypeArg, bool optional)
	{
		zv::Val offsetType;
		bool offsetIsNull = offsetTypeArg == NULL || Z_TYPE_P(offsetTypeArg) == IS_NULL;
		if (!offsetIsNull) {
			offsetType = callType(Z_OBJ_P(offsetTypeArg), PT_LC("toarraykey"), 0, NULL);
			if (UNEXPECTED(offsetType.isUndef())) return false;
		}
		/* the local $valueType the twin reassigns in one branch */
		zv::Val valueType = zv::Val::copyOf(zv::Ref(valueTypeArg));

		zval *nextAutoIndexes = arraySlot(slots::nextAutoIndexes, "nextAutoIndexes");
		if (UNEXPECTED(nextAutoIndexes == NULL)) return false;
		if (offsetIsNull && zend_hash_num_elements(Z_ARRVAL_P(nextAutoIndexes)) == 0) return true;

		if (!optional) {
			setTrinary(slots::isNonEmpty, PT_TRI_YES);
		}

		if (!flag(slots::degradeToGeneralArray)) {
			if (instanceof_function(Z_OBJCE_P(valueType.raw()), pt_ce_closure_type)
				&& Z_TYPE_P(OBJ_PROP_NUM(self, slots::degradeClosures)) != IS_FALSE
				&& !flag(slots::disableArrayDegradation)) {
				zval *valueTypes = arraySlot(slots::valueTypes, "valueTypes");
				if (UNEXPECTED(valueTypes == NULL)) return false;
				zend_long numClosures = 1;
				for (zv::ArrayEntry entry : zv::ArrRef(valueTypes)) {
					zv::Ref innerType = entry.value().deref();
					if (!innerType.instanceOf(pt_ce_closure_type)) continue;
					numClosures++;
				}
				if (numClosures >= PT_CATB_CLOSURES_COUNT_LIMIT) {
					setBool(slots::degradeClosures, true);
					setBool(slots::degradeToGeneralArray, true);
					setBool(slots::oversized, true);
				}
			}

			if (offsetIsNull) return appendAutoIndexed(valueType.raw(), optional);

			bool offsetIsConstantInteger = instanceof_function(Z_OBJCE_P(offsetType.raw()), pt_ce_constant_integer_type);
			if (offsetIsConstantInteger || instanceof_function(Z_OBJCE_P(offsetType.raw()), pt_ce_constant_string_type)) {
				return setConstantOffset(std::move(offsetType), offsetIsConstantInteger, std::move(valueType), optional);
			}

			/* $offsetType->toArrayKey()->getConstantScalarTypes() */
			zv::Val arrayKey = callType(Z_OBJ_P(offsetType.raw()), PT_LC("toarraykey"), 0, NULL);
			if (UNEXPECTED(arrayKey.isUndef())) return false;
			zv::Val scalarTypesVal = callArray(Z_OBJ_P(arrayKey.raw()), PT_LC("getconstantscalartypes"), 0, NULL);
			if (UNEXPECTED(scalarTypesVal.isUndef())) return false;
			zv::Arr scalarTypes = zv::Arr::adoptVal(std::move(scalarTypesVal));
			if (zv::ArrRef(scalarTypes.raw()).size() == 0) {
				zv::Val integerRanges = pt_type_utils_get_integer_ranges(offsetType.raw());
				if (UNEXPECTED(integerRanges.isUndef())) return false;
				if (zv::ArrRef(integerRanges.raw()).size() > 0) {
					for (zv::ArrayEntry rangeEntry : zv::ArrRef(integerRanges.raw())) {
						zv::Ref integerRange = rangeEntry.value().deref();
						if (UNEXPECTED(!integerRange.isObject())) {
							zend_type_error("phpstan_turbo: TypeUtils::getIntegerRanges() must return a list of IntegerRangeType");
							return false;
						}
						zv::Val finiteTypes = callArray(integerRange.asObject(), PT_LC("getfinitetypes"), 0, NULL);
						if (UNEXPECTED(finiteTypes.isUndef())) return false;
						if (zv::ArrRef(finiteTypes.raw()).size() == 0) break;
						for (zv::ArrayEntry finiteEntry : zv::ArrRef(finiteTypes.raw())) {
							scalarTypes.push(finiteEntry.value());
						}
					}
				}
			}
			uint32_t scalarCount = zv::ArrRef(scalarTypes.raw()).size();
			if (scalarCount > 0 && scalarCount < PT_CATB_ARRAY_COUNT_LIMIT) return setScalarOffsets(scalarTypes.raw(), valueType.raw(), optional);

			setTrinary(slots::isList, PT_TRI_NO);

			/* an unsealed builder folds the unknown offset into its extras
			 * instead of degrading (see the twin's comment) */
			zval *unsealed = unsealedSlot();
			if (UNEXPECTED(unsealed == NULL)) return false;
			if (Z_TYPE_P(unsealed) != IS_NULL) return foldIntoUnsealed(offsetType.raw(), valueType.raw());
		}

		if (offsetIsNull) {
			/* TypeCombinator::union(...array_map(fn ($index) => new ConstantIntegerType($index), $this->nextAutoIndexes)) */
			nextAutoIndexes = arraySlot(slots::nextAutoIndexes, "nextAutoIndexes");
			if (UNEXPECTED(nextAutoIndexes == NULL)) return false;
			zv::Arr indexTypes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(nextAutoIndexes)));
			for (zv::ArrayEntry entry : zv::ArrRef(nextAutoIndexes)) {
				zv::Ref index = entry.value().deref();
				if (UNEXPECTED(!index.isLong())) {
					zend_type_error("phpstan_turbo: %s::$nextAutoIndexes must hold ints", ZSTR_VAL(self->ce->name));
					return false;
				}
				zv::Val indexType = pt_type_new_constant_integer(index.asLong());
				if (UNEXPECTED(indexType.isUndef())) return false;
				indexTypes.push(std::move(indexType));
			}
			offsetType = pt_type_combinator_call_spread(PT_LC("union"), indexTypes.table());
			if (UNEXPECTED(offsetType.isUndef())) return false;
		} else {
			setTrinary(slots::isList, PT_TRI_NO);
		}

		if (UNEXPECTED(!pushKeyValue(offsetType.raw(), valueType.raw()))) return false;
		if (optional) {
			if (UNEXPECTED(!pushLastKeyOptional())) return false;
		}
		setBool(slots::degradeToGeneralArray, true);
		return true;
	}

	/* the private markNonListKey($optional) */
	void markNonListKey(bool optional)
	{
		if (optional) {
			andIsListMaybe();
		} else {
			setTrinary(slots::isList, PT_TRI_NO);
		}
	}

	/* false with a ShouldNotHappenException pending when degradation is
	 * disabled */
	bool degradeToGeneralArray(bool oversized)
	{
		if (flag(slots::disableArrayDegradation)) {
			pt_throw_should_not_happen();
			return false;
		}
		setBool(slots::degradeToGeneralArray, true);
		setBool(slots::oversized, flag(slots::oversized) || oversized);
		return true;
	}

	void disableClosureDegradation()
	{
		setBool(slots::degradeClosures, false);
	}

	void disableArrayDegradation()
	{
		setBool(slots::degradeToGeneralArray, false);
		setBool(slots::oversized, false);
		setBool(slots::disableArrayDegradation, true);
	}

	/* the built Type; UNDEF = pending exception */
	zv::Val getArray()
	{
		zval *keyTypes = arraySlot(slots::keyTypes, "keyTypes");
		if (UNEXPECTED(keyTypes == NULL)) return zv::Val();
		uint32_t keyTypesCount = zend_hash_num_elements(Z_ARRVAL_P(keyTypes));
		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return zv::Val();
		zend_long isNonEmpty = trinary(slots::isNonEmpty, "isNonEmpty");
		if (UNEXPECTED(isNonEmpty < 0)) return zv::Val();
		if (keyTypesCount == 0) {
			if (Z_TYPE_P(unsealed) != IS_NULL) {
				zval *unsealedKey, *unsealedValue;
				if (UNEXPECTED(!pairParts(unsealed, unsealedKey, unsealedValue))) return zv::Val();
				bool explicitNever;
				if (UNEXPECTED(!isExplicitNever(unsealedKey, explicitNever))) return zv::Val();
				if (!explicitNever) {
					zv::Val arrayType = arrayOf(unsealedKey, unsealedValue);
					if (UNEXPECTED(arrayType.isUndef())) return zv::Val();
					if (isNonEmpty == PT_TRI_YES) return intersectWithNonEmpty(arrayType.raw());
					return arrayType;
				}
			}
			/* new ConstantArrayType([], [], unsealed: $this->unsealed) */
			zval empty;
			ZVAL_EMPTY_ARRAY(&empty);
			zval raw;
			if (UNEXPECTED(!pt_constant_array_type_new(&raw, &empty, &empty, NULL, NULL, NULL, unsealed))) return zv::Val();
			return zv::Val::adopt(raw);
		}

		zval *valueTypes = arraySlot(slots::valueTypes, "valueTypes");
		if (UNEXPECTED(valueTypes == NULL)) return zv::Val();
		zval *optionalKeys = arraySlot(slots::optionalKeys, "optionalKeys");
		if (UNEXPECTED(optionalKeys == NULL)) return zv::Val();
		zval *isList = objectSlot(slots::isList, "isList");
		if (UNEXPECTED(isList == NULL)) return zv::Val();
		if (!flag(slots::degradeToGeneralArray)) {
			zval *nextAutoIndexes = arraySlot(slots::nextAutoIndexes, "nextAutoIndexes");
			if (UNEXPECTED(nextAutoIndexes == NULL)) return zv::Val();
			zval raw;
			if (UNEXPECTED(!pt_constant_array_type_new(&raw, keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList, unsealed))) return zv::Val();
			zv::Val array = zv::Val::adopt(raw);
			if (isNonEmpty == PT_TRI_YES) {
				zend_long atLeastOnce = pt_type_op_trinary(Z_OBJ_P(array.raw()), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
				if (UNEXPECTED(atLeastOnce < 0)) return zv::Val();
				if (atLeastOnce != PT_TRI_YES) return intersectWithNonEmpty(array.raw());
			}
			return array;
		}

		zv::Arr itemTypes;
		if (Z_TYPE_P(OBJ_PROP_NUM(self, slots::degradeClosures)) == IS_TRUE) {
			itemTypes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(valueTypes)) + 2);
			zval callableRaw;
			if (UNEXPECTED(!pt_callable_type_new(&callableRaw))) return zv::Val();
			itemTypes.push(zv::Val::adopt(callableRaw));
			for (zv::ArrayEntry entry : zv::ArrRef(valueTypes)) {
				zv::Ref valueType = entry.value().deref();
				if (valueType.instanceOf(pt_ce_closure_type)) continue;
				itemTypes.push(valueType);
			}
		} else {
			itemTypes = zv::Arr::copyOfTable(Z_ARRVAL_P(valueTypes));
		}

		zv::Arr keyTypesForArray = zv::Arr::copyOfTable(Z_ARRVAL_P(keyTypes));
		if (Z_TYPE_P(unsealed) != IS_NULL) {
			zval *unsealedKey, *unsealedValue;
			if (UNEXPECTED(!pairParts(unsealed, unsealedKey, unsealedValue))) return zv::Val();
			bool explicitNever;
			if (UNEXPECTED(!isExplicitNever(unsealedKey, explicitNever))) return zv::Val();
			if (!explicitNever) {
				keyTypesForArray.push(zv::Ref(unsealedKey));
				itemTypes.push(zv::Ref(unsealedValue));
			}
		}

		zv::Val keyUnion = pt_type_combinator_call_spread(PT_LC("union"), keyTypesForArray.table());
		if (UNEXPECTED(keyUnion.isUndef())) return zv::Val();
		zv::Val itemUnion = pt_type_combinator_call_spread(PT_LC("union"), itemTypes.table());
		if (UNEXPECTED(itemUnion.isUndef())) return zv::Val();
		zv::Val array = arrayOf(keyUnion.raw(), itemUnion.raw());
		if (UNEXPECTED(array.isUndef())) return zv::Val();

		zv::Arr types = zv::Arr::create(4);
		types.push(zv::Ref(array.raw()));
		uint32_t accessories = 0;
		zval raw;
		if (isNonEmpty == PT_TRI_YES || zend_hash_num_elements(Z_ARRVAL_P(optionalKeys)) < keyTypesCount) {
			if (UNEXPECTED(!pt_non_empty_array_type_new(&raw))) return zv::Val();
			types.push(zv::Val::adopt(raw));
			accessories++;
		}
		if (flag(slots::oversized)) {
			if (UNEXPECTED(!pt_oversized_array_type_new(&raw))) return zv::Val();
			types.push(zv::Val::adopt(raw));
			accessories++;
		}
		zend_long isListValue = pt_type_trinary_value(isList);
		if (UNEXPECTED(isListValue < 0)) return zv::Val();
		if (isListValue == PT_TRI_YES) {
			if (UNEXPECTED(!pt_accessory_array_list_type_new(&raw))) return zv::Val();
			types.push(zv::Val::adopt(raw));
			accessories++;
		}
		if (accessories == 0) return array;
		return pt_intersection_of(std::move(types));
	}

	/* $this->isList->yes(); -1 = pending exception */
	[[nodiscard]] zend_long isList()
	{
		zend_long value = trinary(slots::isList, "isList");
		if (UNEXPECTED(value < 0)) return -1;
		return value == PT_TRI_YES ? 1 : 0;
	}

private:
	zend_object *self;

	/* {{{ setOffsetValueType()'s branches */

	/* $offsetType === null: the value unioned into every key the auto
	 * indexes name, appended under the greatest auto index, the auto
	 * indexes advanced */
	bool appendAutoIndexed(zval *valueType, bool optional)
	{
		zval *nextAutoIndexes = arraySlot(slots::nextAutoIndexes, "nextAutoIndexes");
		if (UNEXPECTED(nextAutoIndexes == NULL)) return false;
		zv::Arr newAutoIndexes = optional ? zv::Arr::copyOfTable(Z_ARRVAL_P(nextAutoIndexes)) : zv::Arr::create(0);
		bool hasOptional = false;
		zval *keyTypes = arraySlot(slots::keyTypes, "keyTypes");
		if (UNEXPECTED(keyTypes == NULL)) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(keyTypes)) {
			zv::Ref keyType = entry.value().deref();
			if (!keyType.instanceOf(pt_ce_constant_integer_type)) continue;
			zend_long keyValue;
			if (UNEXPECTED(!pt_constant_integer_get_value(keyType.asObject(), keyValue))) return false;
			nextAutoIndexes = arraySlot(slots::nextAutoIndexes, "nextAutoIndexes");
			if (UNEXPECTED(nextAutoIndexes == NULL)) return false;
			if (!inArrayLong(Z_ARRVAL_P(nextAutoIndexes), keyValue)) continue;
			zend_long i = (zend_long) entry.indexKey();
			if (UNEXPECTED(!unionIntoValue(i, valueType))) return false;
			if (!hasOptional && !optional) {
				if (UNEXPECTED(!removeOptionalKey(i))) return false;
			}
			if (keyValue != ZEND_LONG_MAX) {
				newAutoIndexes.push(zv::Val::integer(keyValue + 1));
			}
			hasOptional = true;
		}

		nextAutoIndexes = arraySlot(slots::nextAutoIndexes, "nextAutoIndexes");
		if (UNEXPECTED(nextAutoIndexes == NULL)) return false;
		zend_long min, max;
		if (UNEXPECTED(!minMax(Z_ARRVAL_P(nextAutoIndexes), min, max))) return false;
		zv::Val keyType = pt_type_new_constant_integer(max);
		if (UNEXPECTED(keyType.isUndef())) return false;
		if (UNEXPECTED(!pushKeyValue(keyType.raw(), valueType))) return false;
		if (max != ZEND_LONG_MAX) {
			newAutoIndexes.push(zv::Val::integer(max + 1));
		}
		zv::ObjRef(self).propAtWrite(slots::nextAutoIndexes, zv::Val(uniqueValues(newAutoIndexes.table())));

		if (optional || hasOptional) {
			if (UNEXPECTED(!pushLastKeyOptional())) return false;
		}
		return degradeWhenOverLimit();
	}

	/* a constant int/string offset: an existing key overwritten (unioned
	 * when optional), else appended with the list-ness and auto indexes
	 * updated; the Vals consumed */
	bool setConstantOffset(zv::Val offsetType, bool offsetIsConstantInteger, zv::Val valueType, bool optional)
	{
		zv::Val offsetValue = constantValue(offsetType.raw());
		if (UNEXPECTED(offsetValue.isUndef())) return false;
		zval *keyTypes = arraySlot(slots::keyTypes, "keyTypes");
		if (UNEXPECTED(keyTypes == NULL)) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(keyTypes)) {
			zv::Ref keyType = entry.value().deref();
			if (UNEXPECTED(!keyType.isObject())) {
				zend_type_error("phpstan_turbo: %s::$keyTypes must hold Types", ZSTR_VAL(self->ce->name));
				return false;
			}
			zv::Val keyValue = constantValue(keyType.raw());
			if (UNEXPECTED(keyValue.isUndef())) return false;
			if (!zend_is_identical(keyValue.raw(), offsetValue.raw())) continue;
			zend_long i = (zend_long) entry.indexKey();
			if (optional) {
				zval *existing = valueAt(i);
				if (UNEXPECTED(existing == NULL)) return false;
				valueType = union2(valueType.raw(), existing);
				if (UNEXPECTED(valueType.isUndef())) return false;
			}
			zval *valueTypes = arraySlot(slots::valueTypes, "valueTypes");
			if (UNEXPECTED(valueTypes == NULL)) return false;
			zv::ArrRef(valueTypes).setIndex((zend_ulong) i, zv::Ref(valueType.raw()));
			if (!optional) {
				if (UNEXPECTED(!removeOptionalKey(i))) return false;
				if (keyType.instanceOf(pt_ce_constant_integer_type)) {
					zend_long keyInteger;
					if (UNEXPECTED(!pt_constant_integer_get_value(keyType.asObject(), keyInteger))) return false;
					zval *nextAutoIndexes = arraySlot(slots::nextAutoIndexes, "nextAutoIndexes");
					if (UNEXPECTED(nextAutoIndexes == NULL)) return false;
					zv::Arr kept = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(nextAutoIndexes)));
					for (zv::ArrayEntry indexEntry : zv::ArrRef(nextAutoIndexes)) {
						zv::Ref index = indexEntry.value().deref();
						if (UNEXPECTED(!index.isLong())) {
							zend_type_error("phpstan_turbo: %s::$nextAutoIndexes must hold ints", ZSTR_VAL(self->ce->name));
							return false;
						}
						if (index.asLong() > keyInteger) {
							kept.push(index);
						}
					}
					zv::ObjRef(self).propAtWrite(slots::nextAutoIndexes, zv::Val(std::move(kept)));
				}
			}
			return true;
		}

		if (UNEXPECTED(!pushKeyValue(offsetType.raw(), valueType.raw()))) return false;

		if (offsetIsConstantInteger) {
			zval *nextAutoIndexes = arraySlot(slots::nextAutoIndexes, "nextAutoIndexes");
			if (UNEXPECTED(nextAutoIndexes == NULL)) return false;
			if (zend_hash_num_elements(Z_ARRVAL_P(nextAutoIndexes)) > 0) {
				zend_long min, max;
				if (UNEXPECTED(!minMax(Z_ARRVAL_P(nextAutoIndexes), min, max))) return false;
				zend_long offsetInteger;
				if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(offsetType.raw()), offsetInteger))) return false;
				if (offsetInteger >= 0) {
					if (offsetInteger > min) {
						if (offsetInteger <= max) {
							andIsListMaybe();
						} else {
							markNonListKey(optional);
						}
					}
				} else {
					markNonListKey(optional);
				}

				if (offsetInteger >= max) {
					if (offsetInteger == ZEND_LONG_MAX) {
						if (!optional) {
							zv::ObjRef(self).propAtWrite(slots::nextAutoIndexes, zv::Val(zv::Arr::empty()));
						}
					} else if (!optional) {
						zv::Arr single = zv::Arr::create(1);
						single.push(zv::Val::integer(offsetInteger + 1));
						zv::ObjRef(self).propAtWrite(slots::nextAutoIndexes, zv::Val(std::move(single)));
					} else {
						nextAutoIndexes = arraySlot(slots::nextAutoIndexes, "nextAutoIndexes");
						if (UNEXPECTED(nextAutoIndexes == NULL)) return false;
						zv::ArrRef(nextAutoIndexes).push(zv::Ref(zv::Val::integer(offsetInteger + 1).raw()));
					}
				}
			} else {
				markNonListKey(optional);
			}
		} else {
			markNonListKey(optional);
		}

		if (optional) {
			if (UNEXPECTED(!pushLastKeyOptional())) return false;
		}
		return degradeWhenOverLimit();
	}

	/* a small finite set of constant offsets: each matching key's value
	 * widened (replaced, for a required write to an optional key), the
	 * unmatched ones appended optional, the list-ness lost */
	bool setScalarOffsets(zval *scalarTypes, zval *valueType, bool optional)
	{
		zval *valueTypesSlot = arraySlot(slots::valueTypes, "valueTypes");
		if (UNEXPECTED(valueTypesSlot == NULL)) return false;
		zv::Arr valueTypes = zv::Arr::copyOfTable(Z_ARRVAL_P(valueTypesSlot));
		zv::Arr unmatchedScalars = zv::Arr::create(0);
		for (zv::ArrayEntry scalarEntry : zv::ArrRef(scalarTypes)) {
			zv::Ref scalarType = scalarEntry.value().deref();
			if (UNEXPECTED(!scalarType.isObject())) {
				zend_type_error("phpstan_turbo: getConstantScalarTypes() must return a list of ConstantScalarType");
				return false;
			}
			bool offsetMatch = false;
			zv::Val scalarValue = constantValue(scalarType.raw());
			if (UNEXPECTED(scalarValue.isUndef())) return false;
			zval *keyTypes = arraySlot(slots::keyTypes, "keyTypes");
			if (UNEXPECTED(keyTypes == NULL)) return false;
			for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes)) {
				zv::Ref keyType = keyEntry.value().deref();
				if (UNEXPECTED(!keyType.isObject())) {
					zend_type_error("phpstan_turbo: %s::$keyTypes must hold Types", ZSTR_VAL(self->ce->name));
					return false;
				}
				zv::Val keyValue = constantValue(keyType.raw());
				if (UNEXPECTED(keyValue.isUndef())) return false;
				if (!zend_is_identical(keyValue.raw(), scalarValue.raw())) continue;
				zend_long i = (zend_long) keyEntry.indexKey();
				zval *optionalKeys = arraySlot(slots::optionalKeys, "optionalKeys");
				if (UNEXPECTED(optionalKeys == NULL)) return false;
				if (!optional && inArrayLong(Z_ARRVAL_P(optionalKeys), i)) {
					valueTypes.arrRef().setIndex((zend_ulong) i, zv::Ref(valueType));
				} else {
					zval *existing = zend_hash_index_find(valueTypes.table(), (zend_ulong) i);
					if (UNEXPECTED(existing == NULL)) {
						zend_throw_error(NULL, "Undefined array key " ZEND_LONG_FMT, i);
						return false;
					}
					ZVAL_DEREF(existing);
					zv::Val merged = union2(existing, valueType);
					if (UNEXPECTED(merged.isUndef())) return false;
					valueTypes.arrRef().setIndex((zend_ulong) i, zv::Ref(merged.raw()));
				}
				offsetMatch = true;
			}
			if (offsetMatch) continue;
			unmatchedScalars.push(scalarType);
		}

		zv::ObjRef(self).propAtWrite(slots::valueTypes, zv::Val(std::move(valueTypes)));

		if (zv::ArrRef(unmatchedScalars.raw()).size() == 0) return true;

		for (zv::ArrayEntry entry : zv::ArrRef(unmatchedScalars.raw())) {
			zv::Ref scalarType = entry.value();
			if (UNEXPECTED(!pushKeyValue(scalarType.raw(), valueType) || !pushLastKeyOptional())) return false;
			if (!scalarType.instanceOf(pt_ce_constant_integer_type)) continue;
			zval *nextAutoIndexes = arraySlot(slots::nextAutoIndexes, "nextAutoIndexes");
			if (UNEXPECTED(nextAutoIndexes == NULL)) return false;
			if (zend_hash_num_elements(Z_ARRVAL_P(nextAutoIndexes)) == 0) continue;
			zend_long min, max;
			if (UNEXPECTED(!minMax(Z_ARRVAL_P(nextAutoIndexes), min, max))) return false;
			zend_long offsetInteger;
			if (UNEXPECTED(!pt_constant_integer_get_value(scalarType.asObject(), offsetInteger))) return false;
			if (offsetInteger < max) continue;
			if (offsetInteger == ZEND_LONG_MAX) continue;
			zv::ArrRef(nextAutoIndexes).push(zv::Ref(zv::Val::integer(offsetInteger + 1).raw()));
		}

		setTrinary(slots::isList, PT_TRI_NO);
		return degradeWhenOverLimit();
	}

	/* an unsealed builder meeting a non-constant offset: the keys it could
	 * hit widened, the residual offset unioned into the extras */
	bool foldIntoUnsealed(zval *offsetType, zval *valueType)
	{
		zv::Val residualOffset = zv::Val::copyOf(zv::Ref(offsetType));
		zval *keyTypes = arraySlot(slots::keyTypes, "keyTypes");
		if (UNEXPECTED(keyTypes == NULL)) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(keyTypes)) {
			zv::Ref keyType = entry.value().deref();
			if (UNEXPECTED(!keyType.isObject())) {
				zend_type_error("phpstan_turbo: %s::$keyTypes must hold Types", ZSTR_VAL(self->ce->name));
				return false;
			}
			zv::Val covers = pt_type_op(Z_OBJ_P(offsetType), PT_OP_IS_SUPER_TYPE_OF, 1, keyType.raw());
			if (UNEXPECTED(covers.isUndef())) return false;
			zend_long coversValue = pt_type_result_trinary(covers.raw());
			if (UNEXPECTED(coversValue < 0)) return false;
			if (coversValue == PT_TRI_NO) continue;
			if (UNEXPECTED(!unionIntoValue((zend_long) entry.indexKey(), valueType))) return false;
			residualOffset = pt_type_combinator_remove(residualOffset.raw(), keyType.raw());
			if (UNEXPECTED(residualOffset.isUndef())) return false;
		}

		if (zv::Ref(residualOffset.raw()).instanceOf(pt_ce_never_type)) return true;

		zval *unsealed = unsealedSlot();
		if (UNEXPECTED(unsealed == NULL)) return false;
		zval *existingKey, *existingValue;
		if (UNEXPECTED(!pairParts(unsealed, existingKey, existingValue))) return false;
		bool explicitNever;
		if (UNEXPECTED(!isExplicitNever(existingKey, explicitNever))) return false;
		if (explicitNever) {
			makeUnsealed(residualOffset.raw(), valueType);
			return true;
		}
		zv::Val mergedKey = union2(existingKey, residualOffset.raw());
		if (UNEXPECTED(mergedKey.isUndef())) return false;
		zv::Val mergedValue = union2(existingValue, valueType);
		if (UNEXPECTED(mergedValue.isUndef())) return false;
		zv::ObjRef(self).propAtWrite(slots::unsealed, zv::Val(pairOf(mergedKey.raw(), mergedValue.raw())));
		return true;
	}

	/* }}} */

	/* {{{ slot access */

	/* a bool flag slot */
	bool flag(uint32_t slot) const
	{
		return Z_TYPE_P(OBJ_PROP_NUM(self, slot)) == IS_TRUE;
	}

	void setBool(uint32_t slot, bool value)
	{
		zv::ObjRef(self).propAtWrite(slot, zv::Val::boolean(value));
	}

	/* the TrinaryLogic singleton for a value into a trinary slot */
	void setTrinary(uint32_t slot, zend_long value)
	{
		zv::ObjRef(self).propAtWrite(slot, zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value))));
	}

	/* the PT_TRI_* value of a trinary slot; -1 with an Error pending when
	 * uninitialized */
	[[nodiscard]] zend_long trinary(uint32_t slot, const char *name) const
	{
		zval *value = objectSlot(slot, name);
		if (UNEXPECTED(value == NULL)) return -1;
		return pt_type_trinary_value(value);
	}

	/* $this->isList = $this->isList->and(TrinaryLogic::createMaybe()) */
	void andIsListMaybe()
	{
		zend_long isList = trinary(slots::isList, "isList");
		if (UNEXPECTED(isList < 0)) return;
		setTrinary(slots::isList, isList & PT_TRI_MAYBE);
	}

	/* an array-typed slot (borrowed); NULL with an Error pending when
	 * uninitialized */
	[[nodiscard]] zval *arraySlot(uint32_t slot, const char *name) const
	{
		zval *value = OBJ_PROP_NUM(self, slot);
		if (UNEXPECTED(Z_TYPE_P(value) != IS_ARRAY)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(self->ce->name), name);
			return NULL;
		}
		return value;
	}

	zval *objectSlot(uint32_t slot, const char *name) const
	{
		zval *value = OBJ_PROP_NUM(self, slot);
		if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(self->ce->name), name);
			return NULL;
		}
		return value;
	}

	/* the `?array $unsealed` slot (IS_NULL or an array, borrowed) */
	zval *unsealedSlot() const
	{
		zval *value = OBJ_PROP_NUM(self, slots::unsealed);
		if (UNEXPECTED(Z_TYPE_P(value) != IS_ARRAY && Z_TYPE_P(value) != IS_NULL)) {
			zend_throw_error(NULL, "Typed property %s::$unsealed must not be accessed before initialization", ZSTR_VAL(self->ce->name));
			return NULL;
		}
		return value;
	}

	/* $this->valueTypes[$i] (borrowed); NULL with an Error pending when
	 * absent */
	[[nodiscard]] zval *valueAt(zend_long i) const
	{
		zval *valueTypes = arraySlot(slots::valueTypes, "valueTypes");
		if (UNEXPECTED(valueTypes == NULL)) return NULL;
		zval *existing = zend_hash_index_find(Z_ARRVAL_P(valueTypes), (zend_ulong) i);
		if (UNEXPECTED(existing == NULL)) {
			zend_throw_error(NULL, "Undefined array key " ZEND_LONG_FMT, i);
			return NULL;
		}
		ZVAL_DEREF(existing);
		return existing;
	}

	/* $this->valueTypes[$i] = TypeCombinator::union($this->valueTypes[$i], $valueType) */
	bool unionIntoValue(zend_long i, zval *valueType)
	{
		zval *existing = valueAt(i);
		if (UNEXPECTED(existing == NULL)) return false;
		zv::Val merged = union2(existing, valueType);
		if (UNEXPECTED(merged.isUndef())) return false;
		zval *valueTypes = arraySlot(slots::valueTypes, "valueTypes");
		if (UNEXPECTED(valueTypes == NULL)) return false;
		zv::ArrRef(valueTypes).setIndex((zend_ulong) i, zv::Ref(merged.raw()));
		return true;
	}

	/* $this->keyTypes[] = $keyType; $this->valueTypes[] = $valueType */
	bool pushKeyValue(zval *keyType, zval *valueType)
	{
		zval *keyTypes = arraySlot(slots::keyTypes, "keyTypes");
		if (UNEXPECTED(keyTypes == NULL)) return false;
		zv::ArrRef(keyTypes).push(zv::Ref(keyType));
		zval *valueTypes = arraySlot(slots::valueTypes, "valueTypes");
		if (UNEXPECTED(valueTypes == NULL)) return false;
		zv::ArrRef(valueTypes).push(zv::Ref(valueType));
		return true;
	}

	/* $this->optionalKeys[] = count($this->keyTypes) - 1 */
	bool pushLastKeyOptional()
	{
		zval *keyTypes = arraySlot(slots::keyTypes, "keyTypes");
		if (UNEXPECTED(keyTypes == NULL)) return false;
		zend_long last = (zend_long) zend_hash_num_elements(Z_ARRVAL_P(keyTypes)) - 1;
		zval *optionalKeys = arraySlot(slots::optionalKeys, "optionalKeys");
		if (UNEXPECTED(optionalKeys == NULL)) return false;
		zv::ArrRef(optionalKeys).push(zv::Ref(zv::Val::integer(last).raw()));
		return true;
	}

	/* $this->optionalKeys = array_values(array_filter($this->optionalKeys, fn ($index) => $index !== $i)) */
	bool removeOptionalKey(zend_long i)
	{
		zval *optionalKeys = arraySlot(slots::optionalKeys, "optionalKeys");
		if (UNEXPECTED(optionalKeys == NULL)) return false;
		zv::Arr kept = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(optionalKeys)));
		for (zv::ArrayEntry entry : zv::ArrRef(optionalKeys)) {
			zv::Ref index = entry.value().deref();
			if (index.isLong() && index.asLong() == i) continue;
			kept.push(index);
		}
		zv::ObjRef(self).propAtWrite(slots::optionalKeys, zv::Val(std::move(kept)));
		return true;
	}

	/* the degradation tail of the appending branches: the builder degrades
	 * (oversized) when it holds more keys than the limit and degradation
	 * is not disabled */
	bool degradeWhenOverLimit()
	{
		if (flag(slots::disableArrayDegradation)) return true;
		zval *keyTypes = arraySlot(slots::keyTypes, "keyTypes");
		if (UNEXPECTED(keyTypes == NULL)) return false;
		if (zend_hash_num_elements(Z_ARRVAL_P(keyTypes)) > PT_CATB_ARRAY_COUNT_LIMIT) {
			setBool(slots::degradeToGeneralArray, true);
			setBool(slots::oversized, true);
		}
		return true;
	}

	/* }}} */

	/* {{{ static helpers */

	/* a ConstantArrayType's part for createFromConstantArray(): the slot of
	 * an instance of exactly the shadowed class (its getters return the
	 * slots), the getter through the class entry otherwise; owned, checked
	 * against the type the twin's typed constructor parameter demands
	 * (IS_NULL: the nullable unsealed pair); UNDEF = pending exception */
	static zv::Val arrayPart(zend_object *array, uint32_t slot, const char *lcname, size_t len, int expectedType)
	{
		zv::Val part;
		if (EXPECTED(array->ce == pt_ce_constant_array_type) && Z_TYPE_P(OBJ_PROP_NUM(array, slot)) != IS_UNDEF) {
			part = zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(array, slot)));
		} else {
			part = pt_type_call(array, lcname, len, 0, NULL);
			if (UNEXPECTED(part.isUndef())) return zv::Val();
		}
		int actual = Z_TYPE_P(part.raw());
		bool ok = expectedType == IS_NULL ? (actual == IS_NULL || actual == IS_ARRAY) : actual == expectedType;
		if (UNEXPECTED(!ok)) {
			zend_type_error("phpstan_turbo: %s::%s() must return %s", ZSTR_VAL(array->ce->name), lcname, expectedType == IS_OBJECT ? ptcls::trinaryLogic : "an array");
			return zv::Val();
		}
		return part;
	}

	/* BleedingEdgeToggle::isBleedingEdge(): the private static the final
	 * class returns, read through the engine's static-property path under
	 * the class's own scope (as ConstantArrayType.cpp reads it); false =
	 * pending exception */
	static bool isBleedingEdge(bool &out)
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

	/* [$keyType, $valueType] */
	static zv::Arr pairOf(zval *keyType, zval *valueType)
	{
		zv::Arr pair = zv::Arr::create(2);
		pair.push(zv::Ref(keyType));
		pair.push(zv::Ref(valueType));
		return pair;
	}

	/* [$key, $value] = $pair (borrowed, dereferenced); false with an Error
	 * pending when the pair lacks an element */
	[[nodiscard]] static bool pairParts(zval *pair, zval *&key, zval *&value)
	{
		key = zend_hash_index_find(Z_ARRVAL_P(pair), 0);
		value = zend_hash_index_find(Z_ARRVAL_P(pair), 1);
		if (UNEXPECTED(key == NULL || value == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: the unsealed pair must hold a key and a value type");
			return false;
		}
		ZVAL_DEREF(key);
		ZVAL_DEREF(value);
		if (UNEXPECTED(Z_TYPE_P(key) != IS_OBJECT || Z_TYPE_P(value) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: the unsealed pair must hold Types");
			return false;
		}
		return true;
	}

	/* $type instanceof NeverType && $type->isExplicit(); false = pending
	 * exception */
	[[nodiscard]] static bool isExplicitNever(zval *type, bool &out)
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_never_type)) {
			out = false;
			return true;
		}
		return pt_never_type_is_explicit(Z_OBJ_P(type), out);
	}

	/* $type->getValue() of a constant type: the shadowed integer and string
	 * classes' values directly, the method otherwise; UNDEF = pending
	 * exception */
	static zv::Val constantValue(zval *type)
	{
		zend_object *object = Z_OBJ_P(type);
		if (instanceof_function(object->ce, pt_ce_constant_integer_type)) {
			zend_long value;
			if (UNEXPECTED(!pt_constant_integer_get_value(object, value))) return zv::Val();
			return zv::Val::integer(value);
		}
		if (instanceof_function(object->ce, pt_ce_constant_string_type)) return pt_constant_string_get_value(object);
		return pt_type_call(object, PT_LC("getvalue"), 0, NULL);
	}

	/* in_array($value, $ints, true) */
	static bool inArrayLong(HashTable *ints, zend_long value)
	{
		for (zv::ArrayEntry entry : zv::TableRef(ints)) {
			zv::Ref element = entry.value().deref();
			if (element.isLong() && element.asLong() == value) return true;
		}
		return false;
	}

	/* min($ints) and max($ints); false with a ValueError pending for an
	 * empty list */
	static bool minMax(HashTable *ints, zend_long &min, zend_long &max)
	{
		bool first = true;
		for (zv::ArrayEntry entry : zv::TableRef(ints)) {
			zv::Ref element = entry.value().deref();
			if (UNEXPECTED(!element.isLong())) {
				zend_type_error("phpstan_turbo: ConstantArrayTypeBuilder::$nextAutoIndexes must hold ints");
				return false;
			}
			zend_long value = element.asLong();
			if (first) {
				min = max = value;
				first = false;
				continue;
			}
			if (value < min) {
				min = value;
			}
			if (value > max) {
				max = value;
			}
		}
		if (UNEXPECTED(first)) {
			zend_value_error("max(): Argument #1 ($value) must contain at least one element");
			return false;
		}
		return true;
	}

	/* array_values(array_unique($ints)): the first occurrences, in order */
	static zv::Arr uniqueValues(HashTable *ints)
	{
		zv::Arr unique = zv::Arr::create(zend_hash_num_elements(ints));
		zv::ScratchTable seen(zend_hash_num_elements(ints));
		zval marker;
		ZVAL_TRUE(&marker);
		for (zv::ArrayEntry entry : zv::TableRef(ints)) {
			zv::Ref element = entry.value().deref();
			if (element.isLong()) {
				if (zend_hash_index_add(seen.table(), (zend_ulong) element.asLong(), &marker) == NULL) continue;
			}
			unique.push(element);
		}
		return unique;
	}

	/* new ArrayType($keyType, $itemType) (the shadowing class); UNDEF =
	 * pending exception */
	static zv::Val arrayOf(zval *keyType, zval *itemType)
	{
		zval raw;
		if (UNEXPECTED(!pt_array_type_new(&raw, keyType, itemType))) return zv::Val();
		return zv::Val::adopt(raw);
	}

	/* TypeCombinator::intersect($array, new NonEmptyArrayType()) */
	static zv::Val intersectWithNonEmpty(zval *array)
	{
		zval raw;
		if (UNEXPECTED(!pt_non_empty_array_type_new(&raw))) return zv::Val();
		zv::Val nonEmpty = zv::Val::adopt(raw);
		zv::Args args{array, nonEmpty.raw()};
		return pt_type_combinator_intersect(2, args);
	}

	/* TypeCombinator::union($a, $b) */
	static zv::Val union2(zval *a, zval *b)
	{
		zv::Args args{a, b};
		return pt_type_combinator_union(2, args);
	}

	/* $type->method(...$args) requiring a Type / an array result; UNDEF =
	 * pending exception */
	static zv::Val callType(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		zv::Val result = pt_type_call(object, lcname, len, argc, argv);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s::%s() must return %s", ZSTR_VAL(object->ce->name), lcname, ptcls::type);
			return zv::Val();
		}
		return result;
	}

	static zv::Val callArray(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		zv::Val result = pt_type_call(object, lcname, len, argc, argv);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isArray())) {
			zend_type_error("phpstan_turbo: %s::%s() must return an array", ZSTR_VAL(object->ce->name), lcname);
			return zv::Val();
		}
		return result;
	}

	/* }}} */
};

} // namespace phpstanturbo

using phpstanturbo::ConstantArrayTypeBuilder;

/* {{{ exported helpers */

/* the twin's `ConstantArrayType $startArrayType` parameter check; false
 * with a TypeError pending */
static bool pt_catb_check_constant_array(zval *array)
{
	if (EXPECTED(Z_TYPE_P(array) == IS_OBJECT && instanceof_function(Z_OBJCE_P(array), pt_ce_constant_array_type))) return true;
	zend_argument_type_error(1, "must be of type %s, %s given", ZSTR_VAL(pt_ce_constant_array_type->name), zend_zval_value_name(array));
	return false;
}

/* the twin's `Type $keyType` / `Type $valueType` / `?Type $offsetType`
 * parameter checks (argument $argNum; NULL or a null zval passes as the
 * nullable parameter's null); false with a TypeError pending */
static bool pt_catb_check_type(zval *type, uint32_t argNum)
{
	if (type == NULL || Z_TYPE_P(type) == IS_NULL) return true;
	bool isType;
	if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TYPE, isType))) return false;
	if (EXPECTED(isType)) return true;
	zend_argument_type_error(argNum, "must be of type %s, %s given", ptcls::type, zend_zval_value_name(type));
	return false;
}

/* whether $builder is exactly the native class (a direct C++ call), else
 * an object the method is called on (the PHP twin declared next to the
 * native class in the differential tests); false with a TypeError pending
 * for a non-object */
static bool pt_catb_is_native(zval *builder, bool &native)
{
	if (UNEXPECTED(Z_TYPE_P(builder) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: expected a ConstantArrayTypeBuilder, %s given", zend_zval_value_name(builder));
		return false;
	}
	native = Z_OBJCE_P(builder) == pt_ce_constant_array_type_builder;
	return true;
}

zv::Val pt_constant_array_type_builder_create_empty()
{
	return ConstantArrayTypeBuilder::createEmpty();
}

zv::Val pt_constant_array_type_builder_create_from_constant_array(zval *array)
{
	if (UNEXPECTED(!pt_catb_check_constant_array(array))) return zv::Val();
	return ConstantArrayTypeBuilder::createFromConstantArray(array);
}

bool pt_constant_array_type_builder_set_offset_value_type(zval *builder, zval *offsetType, zval *valueType, bool optional)
{
	bool native;
	if (UNEXPECTED(!pt_catb_is_native(builder, native))) return false;
	if (EXPECTED(native)) return ConstantArrayTypeBuilder(Z_OBJ_P(builder)).setOffsetValueType(offsetType, valueType, optional);
	zval args[3];
	if (offsetType == NULL) {
		ZVAL_NULL(&args[0]);
	} else {
		ZVAL_COPY_VALUE(&args[0], offsetType);
	}
	ZVAL_COPY_VALUE(&args[1], valueType);
	ZVAL_BOOL(&args[2], optional);
	zv::Val result = pt_type_call(Z_OBJ_P(builder), PT_LC("setoffsetvaluetype"), 3, args);
	return !result.isUndef();
}

bool pt_constant_array_type_builder_make_unsealed(zval *builder, zval *keyType, zval *valueType)
{
	bool native;
	if (UNEXPECTED(!pt_catb_is_native(builder, native))) return false;
	if (EXPECTED(native)) {
		ConstantArrayTypeBuilder(Z_OBJ_P(builder)).makeUnsealed(keyType, valueType);
		return true;
	}
	zv::Args args{keyType, valueType};
	zv::Val result = pt_type_call(Z_OBJ_P(builder), PT_LC("makeunsealed"), 2, args);
	return !result.isUndef();
}

bool pt_constant_array_type_builder_degrade_to_general_array(zval *builder, bool oversized)
{
	bool native;
	if (UNEXPECTED(!pt_catb_is_native(builder, native))) return false;
	if (EXPECTED(native)) return ConstantArrayTypeBuilder(Z_OBJ_P(builder)).degradeToGeneralArray(oversized);
	zval arg;
	ZVAL_BOOL(&arg, oversized);
	zv::Val result = pt_type_call(Z_OBJ_P(builder), PT_LC("degradetogeneralarray"), 1, &arg);
	return !result.isUndef();
}

zv::Val pt_constant_array_type_builder_get_array(zval *builder)
{
	bool native;
	if (UNEXPECTED(!pt_catb_is_native(builder, native))) return zv::Val();
	if (EXPECTED(native)) return ConstantArrayTypeBuilder(Z_OBJ_P(builder)).getArray();
	return pt_type_call(Z_OBJ_P(builder), PT_LC("getarray"), 0, NULL);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ConstantArrayTypeBuilder(Z_OBJ_P(ZEND_THIS))

void pt_register_constant_array_type_builder()
{

	reg::Class cls("PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder");
	ptdecl::ConstantArrayTypeBuilder::declareClass(cls);
	cls.classConstantLong("ARRAY_COUNT_LIMIT", PT_CATB_ARRAY_COUNT_LIMIT);
	cls.privateClassConstantLong("CLOSURES_COUNT_LIMIT", PT_CATB_CLOSURES_COUNT_LIMIT);
	/* the slots must stay in this order (PT_CATB_PROP_*): the class-body
	 * flags, then the promoted constructor parameters */
	cls.privateTypedBoolProperty("degradeToGeneralArray", false);
	cls.privateTypedBoolProperty("disableArrayDegradation", false);
	cls.privateTypedPropertyDefaultNull("degradeClosures", MAY_BE_BOOL);
	cls.privateTypedBoolProperty("oversized", false);
	cls.privateTypedClassProperty("isNonEmpty", ptcls::trinaryLogic, false);
	cls.privateTypedProperty("keyTypes", MAY_BE_ARRAY);
	cls.privateTypedProperty("valueTypes", MAY_BE_ARRAY);
	cls.privateTypedProperty("nextAutoIndexes", MAY_BE_ARRAY);
	cls.privateTypedProperty("optionalKeys", MAY_BE_ARRAY);
	cls.privateTypedClassProperty("isList", ptcls::trinaryLogic, false);
	cls.privateTypedProperty("unsealed", MAY_BE_ARRAY | MAY_BE_NULL);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *keyTypes, *valueTypes, *nextAutoIndexes, *optionalKeys, *isList, *unsealed;
		if (!zp::parse<zp::Arr, zp::Arr, zp::Arr, zp::Arr, zp::Obj, zp::ArrOrNull>(execute_data, keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList, unsealed)) RETURN_THROWS();
		zval nullUnsealed;
		if (unsealed == NULL) {
			ZVAL_NULL(&nullUnsealed);
			unsealed = &nullUnsealed;
		}
		PT_THIS.construct(keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList, unsealed);
	});

	cls.method<&ConstantArrayTypeBuilder::createEmpty>(sigs::createEmpty);

	cls.method(sigs::createFromConstantArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *startArrayType;
		if (!zp::parse<zp::Obj>(execute_data, startArrayType)) RETURN_THROWS();
		PT_RETURN_VAL(pt_constant_array_type_builder_create_from_constant_array(startArrayType));
	});

	cls.method("makeUnsealed", reg::Public, 2, { reg::obj("keyType", ptcls::type), reg::obj("valueType", ptcls::type) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *keyType, *valueType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, keyType, valueType)) RETURN_THROWS();
		if (UNEXPECTED(!pt_catb_check_type(keyType, 1) || !pt_catb_check_type(valueType, 2))) RETURN_THROWS();
		PT_THIS.makeUnsealed(keyType, valueType);
	});

	cls.method("mergeUnsealed", reg::Public, 2, { reg::obj("keyType", ptcls::type), reg::obj("valueType", ptcls::type) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *keyType, *valueType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, keyType, valueType)) RETURN_THROWS();
		if (UNEXPECTED(!pt_catb_check_type(keyType, 1) || !pt_catb_check_type(valueType, 2))) RETURN_THROWS();
		if (UNEXPECTED(!PT_THIS.mergeUnsealed(keyType, valueType))) RETURN_THROWS();
	});

	cls.method("setOffsetValueType", reg::Public, 2, { reg::obj("offsetType", ptcls::type, true), reg::obj("valueType", ptcls::type), reg::withDefault(reg::boolArg("optional"), "false") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		bool optional = false;
		if (!zp::parse<zp::ObjOrNull, zp::Obj, zp::Opt<zp::Bool>>(execute_data, offsetType, valueType, optional)) RETURN_THROWS();
		if (UNEXPECTED(!pt_catb_check_type(offsetType, 1) || !pt_catb_check_type(valueType, 2))) RETURN_THROWS();
		if (UNEXPECTED(!PT_THIS.setOffsetValueType(offsetType, valueType, optional))) RETURN_THROWS();
	});

	cls.method<&ConstantArrayTypeBuilder::markNonListKey, zp::Bool>("markNonListKey", reg::Private, { reg::boolArg("optional") });

	cls.method("degradeToGeneralArray", reg::Public, 0, { reg::withDefault(reg::boolArg("oversized"), "false") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool oversized = false;
		if (!zp::parse<zp::Opt<zp::Bool>>(execute_data, oversized)) RETURN_THROWS();
		if (UNEXPECTED(!PT_THIS.degradeToGeneralArray(oversized))) RETURN_THROWS();
	});

	cls.method<&ConstantArrayTypeBuilder::disableClosureDegradation>("disableClosureDegradation", reg::Public, {});

	cls.method<&ConstantArrayTypeBuilder::disableArrayDegradation>("disableArrayDegradation", reg::Public, {});

	cls.method<&ConstantArrayTypeBuilder::getArray>(sigs::getArray);

	cls.method(sigs::isList, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zend_long isList = PT_THIS.isList();
		if (UNEXPECTED(isList < 0)) RETURN_THROWS();
		RETURN_BOOL(isList == 1);
	});

	cls.shadow(&pt_ce_constant_array_type_builder);
}

/* }}} */
