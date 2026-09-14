/*
 * PHPStanTurbo\ArrayType — native implementation of PHPStan\Type\ArrayType.
 *
 * Declared as PHPStan\Type\ArrayType itself at activation: not final (the
 * PHP TemplateArrayType extends it, overriding withTypes() and the
 * TemplateTypeTrait methods), implementing PHPStan\Type\Type. State is the
 * twin's four private properties, declared typed property slots in the
 * twin's order — $keyType, the two memos $cachedIterableKeyType and
 * $isList, and the promoted $itemType — so the std object handlers do
 * GC/clone.
 *
 * Every `$this->method()` the twin makes goes through the object's class
 * entry — a subclass may have overridden it — with a direct C++ call when
 * the object is exactly an ArrayType; `new self(...)` is always this class.
 * The twin's `static fn` closures handed to TypeTraverser::map() are native
 * bodies behind pt_type_native_callback() (TypeTraits.cpp).
 */

#include "TypeTraits.h"
#include "generated/ArrayType.h"

namespace slots = ptdecl::ArrayType::slot;
namespace sigs = ptdecl::ArrayType::sig;

zend_class_entry *pt_ce_array_type = nullptr;

/* the twin's private const TRUNCATE_ACCESSORIES_LIMIT */
#define PT_AT_TRUNCATE_ACCESSORIES_LIMIT 8

/* ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT, read once per class entry */
static zend_class_entry *pt_array_count_limit_ce = nullptr;
static zend_long pt_array_count_limit = 0;

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
	return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, lcname, len, 2, args);
}

/* new ConstantArrayType([], []) */
static zv::Val emptyConstantArray()
{
	zval args[2];
	ZVAL_EMPTY_ARRAY(&args[0]);
	ZVAL_EMPTY_ARRAY(&args[1]);
	return pt_type_new(PT_CLASS_CONSTANT_ARRAY_TYPE, 2, args);
}

/* new IntersectionType($types) ($types consumed) */
static zv::Val intersection(zv::Arr types)
{
	return pt_type_new(PT_CLASS_INTERSECTION_TYPE, 1, types.raw());
}

/* new NonEmptyArrayType() / new AccessoryArrayListType() / new
 * IntegerType() / new StringType() / new ConstantIntegerType($v) /
 * new ConstantBooleanType($v) / IntegerRangeType::createAllGreaterThanOrEqualTo(0) */
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

static zv::Val constantBoolean(bool value)
{
	zval result;
	if (UNEXPECTED(!pt_constant_boolean_type_new(&result, value))) return zv::Val();
	return zv::Val::adopt(result);
}

static zv::Val nonNegativeIntegers()
{
	zval zero;
	ZVAL_LONG(&zero, 0);
	return pt_integer_range_create_all_greater_than_or_equal_to(&zero);
}

/* $object->method(...) that returns a Type: the result checked to be an
 * object (the engine's return check of the PHP twin); UNDEF = pending
 * exception */
static zv::Val callType(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	return pt_type_call_type(object, lcname, len, argc, argv);
}

/* Mirrors PHPStan\Type\ArrayType. State lives in the PHP object's slots. */
class ArrayType
{
public:
	explicit ArrayType(zend_object *self) : self(self) {}

	/* the `Type` parameter check of the twin's typed parameters for values
	 * reaching a `new self()` from a callback; false with a TypeError
	 * pending */
	static bool checkType(zval *value, const char *method, int argNumber, const char *parameter)
	{
		bool isType;
		if (UNEXPECTED(!isInstance(value, PT_CLASS_TYPE, isType))) return false;
		if (UNEXPECTED(!isType)) {
			zend_type_error("%s::%s(): Argument #%d ($%s) must be of type %s, %s given", ZSTR_VAL(pt_ce_array_type->name), method, argNumber, parameter, ptcls::type, zend_zval_value_name(value));
			return false;
		}
		return true;
	}

	/* __construct(Type $keyType, private Type $itemType): a BenevolentUnionType
	 * key describing as '(int|string)' / '(int|non-decimal-int-string)'
	 * becomes mixed, a StrictMixedType key (not a TemplateStrictMixedType)
	 * becomes (string|int)->toArrayKey(); false = pending exception */
	[[nodiscard]] bool construct(zval *keyTypeArg, zval *itemType)
	{
		zv::Val keyType = zv::Val::copyOf(zv::Ref(keyTypeArg));
		bool isBenevolent;
		if (UNEXPECTED(!isInstance(keyType.raw(), PT_CLASS_BENEVOLENT_UNION_TYPE, isBenevolent))) return false;
		if (isBenevolent) {
			zv::Val level = pt_type_call_static(PT_CLASS_VERBOSITY_LEVEL, PT_LC("value"), 0, NULL);
			if (UNEXPECTED(level.isUndef())) return false;
			zv::Val description = pt_type_call(Z_OBJ_P(keyType.raw()), PT_LC("describe"), 1, level.raw());
			if (UNEXPECTED(description.isUndef())) return false;
			zv::Ref d(description.raw());
			if (d.stringEquals("(int|string)") || d.stringEquals("(int|non-decimal-int-string)")) {
				keyType = pt_type_new_mixed_type();
				if (UNEXPECTED(keyType.isUndef())) return false;
			}
		}
		bool isStrictMixed;
		if (UNEXPECTED(!isInstance(keyType.raw(), pt_ce_strict_mixed_type, isStrictMixed))) return false;
		if (isStrictMixed) {
			bool isTemplateStrictMixed;
			if (UNEXPECTED(!isInstance(keyType.raw(), PT_CLASS_TEMPLATE_STRICT_MIXED_TYPE, isTemplateStrictMixed))) return false;
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
				keyType = callType(Z_OBJ_P(unionType.raw()), PT_LC("toarraykey"), 0, NULL);
				if (UNEXPECTED(keyType.isUndef())) return false;
			}
		}

		zv::ObjRef(self).propAtWrite(slots::keyType, std::move(keyType));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::keyType)) = 0; /* no longer IS_PROP_UNINIT */
		zv::ObjRef(self).propAtWrite(slots::itemType, zv::Val::copyOf(zv::Ref(itemType)));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::itemType)) = 0;
		return true;
	}

	/* new self($keyType, $itemType); UNDEF = pending exception */
	static zv::Val create(zval *keyType, zval *itemType)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_array_type) != SUCCESS)) return zv::Val();
		if (UNEXPECTED(!ArrayType(Z_OBJ(object)).construct(keyType, itemType))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

	/* $this->keyType / $this->itemType (borrowed); NULL with an Error
	 * pending when the constructor never ran
	 * (ReflectionClass::newInstanceWithoutConstructor()) — the twin's
	 * typed-property read raises the same */
	[[nodiscard]] zval *keyType() const { return slotOf(self, slots::keyType, "keyType"); }
	zval *itemType() const { return slotOf(self, slots::itemType, "itemType"); }

	zv::Val getKeyType() const
	{
		zval *t = keyType();
		return t == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(t));
	}

	zv::Val getItemType() const
	{
		zval *t = itemType();
		return t == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(t));
	}

	/* protected withTypes(): new self($keyType, $itemType) */
	static zv::Val withTypes(zval *keyType, zval *itemType) { return create(keyType, itemType); }

	/* array_merge($this->keyType->getReferencedClasses(), $this->getItemType()->getReferencedClasses()) */
	zv::Val getReferencedClasses() const
	{
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zv::Val a = pt_type_call_array(Z_OBJ_P(k), PT_LC("getreferencedclasses"), 0, NULL);
		if (UNEXPECTED(a.isUndef())) return zv::Val();
		zv::Val item = thisGetItemType();
		if (UNEXPECTED(item.isUndef())) return zv::Val();
		zv::Val b = pt_type_call_array(Z_OBJ_P(item.raw()), PT_LC("getreferencedclasses"), 0, NULL);
		if (UNEXPECTED(b.isUndef())) return zv::Val();
		return arrayMerge(a.raw(), b.raw());
	}

	static zv::Val getConstantArrays() { return zv::Val(zv::Arr::empty()); }

	/* the CompoundType callback; for a ConstantArrayType the key and value
	 * types accepted pairwise; for an ArrayType the item and key types
	 * accepted; no otherwise; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool compound;
		if (UNEXPECTED(!isInstance(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}

		bool isConstantArray;
		if (UNEXPECTED(!isInstance(type, PT_CLASS_CONSTANT_ARRAY_TYPE, isConstantArray))) return zv::Val();
		if (isConstantArray) {
			zv::Val result = pt_type_accepts_result(PT_TRI_YES);
			zval *thisKeyType = keyType();
			if (UNEXPECTED(result.isUndef() || thisKeyType == NULL)) return zv::Val();
			zv::Val itemType = thisGetItemType();
			if (UNEXPECTED(itemType.isUndef())) return zv::Val();
			zv::Val keyTypes = pt_type_call_array(Z_OBJ_P(type), PT_LC("getkeytypes"), 0, NULL);
			if (UNEXPECTED(keyTypes.isUndef())) return zv::Val();
			/* $type->getValueTypes()[$i] — the same array on every iteration */
			zv::Val valueTypes = pt_type_call_array(Z_OBJ_P(type), PT_LC("getvaluetypes"), 0, NULL);
			if (UNEXPECTED(valueTypes.isUndef())) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(keyTypes.raw())) {
				zval *keyType = entry.value().deref().raw();
				zv::Ref valueType = zv::ArrRef(valueTypes.raw()).findIndex(entry.indexKey());
				if (UNEXPECTED(entry.hasStringKey() || valueType.raw() == NULL || Z_TYPE_P(keyType) != IS_OBJECT || !valueType.deref().isObject())) {
					zend_type_error("phpstan_turbo: %s::getKeyTypes() and getValueTypes() must return parallel lists of %s", ZSTR_VAL(Z_OBJCE_P(type)->name), ptcls::type);
					return zv::Val();
				}
				zv::Args args{keyType, strictTypes};
				zv::Val acceptsKey = pt_type_call(Z_OBJ_P(thisKeyType), PT_LC("accepts"), 2, args);
				if (UNEXPECTED(acceptsKey.isUndef())) return zv::Val();
				ZVAL_COPY_VALUE(&args[0], valueType.deref().raw());
				zv::Val acceptsValue = pt_type_call(Z_OBJ_P(itemType.raw()), PT_LC("accepts"), 2, args);
				if (UNEXPECTED(acceptsValue.isUndef())) return zv::Val();
				result = pt_type_result_and(pt_type_result_and(std::move(result), acceptsKey.raw()), acceptsValue.raw());
				if (UNEXPECTED(result.isUndef())) return zv::Val();
			}
			return result;
		}

		if (instanceof_function(Z_OBJCE_P(type), pt_ce_array_type)) {
			zv::Val itemType = thisGetItemType();
			if (UNEXPECTED(itemType.isUndef())) return zv::Val();
			zv::Val thatItemType = pt_array_type_get_item_type(Z_OBJ_P(type));
			if (UNEXPECTED(thatItemType.isUndef())) return zv::Val();
			zv::Args args{thatItemType.raw(), strictTypes};
			zv::Val acceptsItem = pt_type_call(Z_OBJ_P(itemType.raw()), PT_LC("accepts"), 2, args);
			if (UNEXPECTED(acceptsItem.isUndef())) return zv::Val();
			/* $this->keyType->accepts($type->keyType, $strictTypes) — the
			 * private slots, on a subclass instance too */
			zval *thisKeyType = keyType();
			zval *thatKeyType = slotOf(Z_OBJ_P(type), slots::keyType, "keyType");
			if (UNEXPECTED(thisKeyType == NULL || thatKeyType == NULL)) return zv::Val();
			ZVAL_COPY_VALUE(&args[0], thatKeyType);
			zv::Val acceptsKey = pt_type_call(Z_OBJ_P(thisKeyType), PT_LC("accepts"), 2, args);
			if (UNEXPECTED(acceptsKey.isUndef())) return zv::Val();
			return pt_type_result_and(std::move(acceptsItem), acceptsKey.raw());
		}

		return pt_type_accepts_result(PT_TRI_NO);
	}

	/* for an ArrayType / ConstantArrayType the item types' verdict and'ed
	 * with the iterable key types' (maybe instead of no for a possibly-empty
	 * constant array); the CompoundType callback; no otherwise; UNDEF =
	 * pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool isConstantArray = false;
		bool isArray = instanceof_function(Z_OBJCE_P(type), pt_ce_array_type);
		if (!isArray && UNEXPECTED(!isInstance(type, PT_CLASS_CONSTANT_ARRAY_TYPE, isConstantArray))) return zv::Val();
		if (isArray || isConstantArray) {
			zv::Val itemType = thisGetItemType();
			if (UNEXPECTED(itemType.isUndef())) return zv::Val();
			zv::Val thatItemType = callType(Z_OBJ_P(type), PT_LC("getitemtype"), 0, NULL);
			if (UNEXPECTED(thatItemType.isUndef())) return zv::Val();
			zv::Val result = pt_type_call(Z_OBJ_P(itemType.raw()), PT_LC("issupertypeof"), 1, thatItemType.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zv::Val keyType = thisGetIterableKeyType();
			if (UNEXPECTED(keyType.isUndef())) return zv::Val();
			zv::Val thatKeyType = callType(Z_OBJ_P(type), PT_LC("getiterablekeytype"), 0, NULL);
			if (UNEXPECTED(thatKeyType.isUndef())) return zv::Val();
			zv::Val keyResult = pt_type_call(Z_OBJ_P(keyType.raw()), PT_LC("issupertypeof"), 1, thatKeyType.raw());
			if (UNEXPECTED(keyResult.isUndef())) return zv::Val();
			result = pt_type_result_and(std::move(result), keyResult.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zend_long value = pt_type_result_trinary(result.raw());
			if (UNEXPECTED(value < 0)) return zv::Val();
			if (value == PT_TRI_NO) {
				zend_long constantArray = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isconstantarray"), 0, NULL);
				if (UNEXPECTED(constantArray < 0)) return zv::Val();
				if (constantArray == PT_TRI_YES) {
					zend_long atLeastOnce = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isiterableatleastonce"), 0, NULL);
					if (UNEXPECTED(atLeastOnce < 0)) return zv::Val();
					if (atLeastOnce != PT_TRI_YES) {
						/* A possibly-empty constant array admits `[]`, a subtype
						 * of every array type, so the relationship is at worst
						 * `maybe`, never `no`. */
						return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
					}
				}
			}
			return result;
		}

		bool compound;
		if (UNEXPECTED(!isInstance(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, &selfZv);
		}

		return pt_type_is_super_type_of_result(PT_TRI_NO);
	}

	/* $type instanceof self && $this->getItemType()->equals($type->getIterableValueType()) && $this->keyType->equals($type->keyType);
	 * false = pending exception */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_array_type)) {
			out = false;
			return true;
		}
		zv::Val itemType = thisGetItemType();
		if (UNEXPECTED(itemType.isUndef())) return false;
		zv::Val thatValueType = callType(Z_OBJ_P(type), PT_LC("getiterablevaluetype"), 0, NULL);
		if (UNEXPECTED(thatValueType.isUndef())) return false;
		zv::Val itemsEqual = pt_type_call(Z_OBJ_P(itemType.raw()), PT_LC("equals"), 1, thatValueType.raw());
		if (UNEXPECTED(itemsEqual.isUndef())) return false;
		if (!zend_is_true(itemsEqual.raw())) {
			out = false;
			return true;
		}
		zval *thisKeyType = keyType();
		zval *thatKeyType = slotOf(Z_OBJ_P(type), slots::keyType, "keyType");
		if (UNEXPECTED(thisKeyType == NULL || thatKeyType == NULL)) return false;
		return pt_type_call_bool(Z_OBJ_P(thisKeyType), PT_LC("equals"), 1, thatKeyType, out);
	}

	/* $level->handle($valueHandler, $valueHandler, $preciseHandler): 'array'
	 * for implicit-mixed (and, below the precise level, never) key and item
	 * types, 'array<item>' for such a key type alone, 'array<key, item>'
	 * otherwise; UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		zval *k = keyType();
		zval *i = k != NULL ? itemType() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(i == NULL)) return zv::Val();
		bool isMixedKeyType, isMixedItemType;
		if (UNEXPECTED(!isImplicitMixed(k, isMixedKeyType) || !isImplicitMixed(i, isMixedItemType))) return zv::Val();
		pt_verbosity_case which;
		if (UNEXPECTED(!pt_type_verbosity_case(level, which))) return zv::Val();
		bool keyOmitted = isMixedKeyType;
		bool itemOmitted = isMixedItemType;
		if (which == PT_VERBOSITY_TYPE_ONLY || which == PT_VERBOSITY_VALUE) {
			bool isNever;
			if (!keyOmitted) {
				if (UNEXPECTED(!isInstance(k, pt_ce_never_type, isNever))) return zv::Val();
				keyOmitted = isNever;
			}
			if (keyOmitted && !itemOmitted) {
				if (UNEXPECTED(!isInstance(i, pt_ce_never_type, isNever))) return zv::Val();
				itemOmitted = isNever;
			}
		}
		if (keyOmitted) {
			if (itemOmitted) return zv::Val::string("array", sizeof("array") - 1);
			zv::Val item = describeOf(i, level);
			if (UNEXPECTED(item.isUndef())) return zv::Val();
			return zv::Val::adoptString(zend_strpprintf(0, "array<%s>", ZSTR_VAL(zv::Ref(item.raw()).asString())));
		}
		zv::Val key = describeOf(k, level);
		if (UNEXPECTED(key.isUndef())) return zv::Val();
		zv::Val item = describeOf(i, level);
		if (UNEXPECTED(item.isUndef())) return zv::Val();
		return zv::Val::adoptString(zend_strpprintf(0, "array<%s, %s>", ZSTR_VAL(zv::Ref(key.raw()).asString()), ZSTR_VAL(zv::Ref(item.raw()).asString())));
	}

	/* new self($this->keyType, $this->itemType->generalize(GeneralizePrecision::lessSpecific())) */
	zv::Val generalizeValues() const
	{
		zval *k = keyType();
		zval *i = k != NULL ? itemType() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(i == NULL)) return zv::Val();
		zv::Val precision = pt_type_call_static(PT_CLASS_GENERALIZE_PRECISION, PT_LC("lessspecific"), 0, NULL);
		if (UNEXPECTED(precision.isUndef())) return zv::Val();
		zv::Val generalized = callType(Z_OBJ_P(i), PT_LC("generalize"), 1, precision.raw());
		if (UNEXPECTED(generalized.isUndef())) return zv::Val();
		return create(k, generalized.raw());
	}

	/* $this->getKeysArray() */
	zv::Val getKeysArrayFiltered() const
	{
		if (EXPECTED(isExact())) return getKeysArray();
		return pt_type_call(self, PT_LC("getkeysarray"), 0, NULL);
	}

	/* TypeCombinator::intersect(new self(new IntegerType(), $this->getIterableKeyType()), new AccessoryArrayListType()) */
	zv::Val getKeysArray() const
	{
		zv::Val keyType = thisGetIterableKeyType();
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		return listOfIntersected(keyType.raw());
	}

	/* TypeCombinator::intersect(new self(new IntegerType(), $this->itemType), new AccessoryArrayListType()) */
	zv::Val getValuesArray() const
	{
		zval *i = itemType();
		if (UNEXPECTED(i == NULL)) return zv::Val();
		return listOfIntersected(i);
	}

	static zend_long isIterableAtLeastOnce() { return PT_TRI_MAYBE; }

	/* IntegerRangeType::fromInterval(0, null) */
	static zv::Val getArraySize() { return pt_integer_range_from_interval(NullableLong::of(0), NullableLong::null(), 0); }

	/* memoized in $cachedIterableKeyType: the key type, a MixedType (not a
	 * TemplateMixedType) or StrictMixedType replaced by (int|string) array
	 * keys, through UnsafeArrayStringKeyCastingTraverser::castKeyType();
	 * UNDEF = pending exception */
	zv::Val getIterableKeyType() const
	{
		zval *cached = OBJ_PROP_NUM(self, slots::cachedIterableKeyType);
		if (Z_TYPE_P(cached) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(cached));
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zv::Val keyType = zv::Val::copyOf(zv::Ref(k));
		bool isMixed;
		if (UNEXPECTED(!isInstance(keyType.raw(), pt_ce_mixed_type, isMixed))) return zv::Val();
		if (isMixed) {
			bool isTemplateMixed;
			if (UNEXPECTED(!isInstance(keyType.raw(), PT_CLASS_TEMPLATE_MIXED_TYPE, isTemplateMixed))) return zv::Val();
			if (!isTemplateMixed) {
				keyType = benevolentArrayKey();
				if (UNEXPECTED(keyType.isUndef())) return zv::Val();
			}
		}
		bool isStrictMixed;
		if (UNEXPECTED(!isInstance(keyType.raw(), pt_ce_strict_mixed_type, isStrictMixed))) return zv::Val();
		if (isStrictMixed) {
			keyType = benevolentArrayKey();
			if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		}
		zv::Val cast = pt_type_call_static(PT_CLASS_UNSAFE_ARRAY_STRING_KEY_CASTING_TRAVERSER, PT_LC("castkeytype"), 1, keyType.raw());
		if (UNEXPECTED(cast.isUndef())) return zv::Val();
		zv::ObjRef(self).propAtWrite(slots::cachedIterableKeyType, zv::Val::copyOf(zv::Ref(cast.raw())));
		return cast;
	}

	zv::Val getFirstIterableKeyType() const { return thisGetIterableKeyType(); }
	zv::Val getLastIterableKeyType() const { return thisGetIterableKeyType(); }
	zv::Val getIterableValueType() const { return thisGetItemType(); }
	zv::Val getFirstIterableValueType() const { return thisGetItemType(); }
	zv::Val getLastIterableValueType() const { return thisGetItemType(); }

	static zend_long isConstantArray() { return PT_TRI_NO; }

	/* memoized in $isList: no when int<0, max> is not a supertype of the key
	 * type or the key type is not a supertype of 0, maybe otherwise; -1 =
	 * pending exception */
	zend_long isList() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::isList);
		if (Z_TYPE_P(memo) == IS_OBJECT) return pt_type_trinary_value(memo);
		zv::Val range = getArraySize();
		if (UNEXPECTED(range.isUndef())) return -1;
		zv::Val keyType = thisGetKeyType();
		if (UNEXPECTED(keyType.isUndef())) return -1;
		zend_long rangeIsSuper = pt_type_call_result_trinary(Z_OBJ_P(range.raw()), PT_LC("issupertypeof"), 1, keyType.raw());
		if (UNEXPECTED(rangeIsSuper < 0)) return -1;
		zend_long value;
		if (rangeIsSuper == PT_TRI_NO) {
			value = PT_TRI_NO;
		} else {
			zv::Val zero = pt_type_new_constant_integer(0);
			if (UNEXPECTED(zero.isUndef())) return -1;
			keyType = thisGetKeyType();
			if (UNEXPECTED(keyType.isUndef())) return -1;
			zend_long keyIsSuper = pt_type_call_result_trinary(Z_OBJ_P(keyType.raw()), PT_LC("issupertypeof"), 1, zero.raw());
			if (UNEXPECTED(keyIsSuper < 0)) return -1;
			value = keyIsSuper == PT_TRI_NO ? PT_TRI_NO : PT_TRI_MAYBE;
		}
		zv::ObjRef(self).propAtWrite(slots::isList, pt_type_trinary(value));
		return value;
	}

	static zend_long isConstantValue() { return PT_TRI_NO; }

	/* new ConstantBooleanType(false) for an integer, new BooleanType()
	 * otherwise; UNDEF = pending exception */
	static zv::Val looseCompare(zval *type)
	{
		zend_long isInteger = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isinteger"), 0, NULL);
		if (UNEXPECTED(isInteger < 0)) return zv::Val();
		if (isInteger == PT_TRI_YES) return constantBoolean(false);
		return pt_val_of<pt_boolean_type_new>();
	}

	/* no when the offset's array key (narrowed by the allowed array keys
	 * when it has none) is outside the key type and is not a non-constant
	 * string, maybe otherwise; -1 = pending exception */
	[[nodiscard]] zend_long hasOffsetValueType(zval *offsetTypeArg) const
	{
		zv::Val offsetArrayKeyType = callType(Z_OBJ_P(offsetTypeArg), PT_LC("toarraykey"), 0, NULL);
		if (UNEXPECTED(offsetArrayKeyType.isUndef())) return -1;
		bool isError;
		if (UNEXPECTED(!isInstance(offsetArrayKeyType.raw(), PT_CLASS_ERROR_TYPE, isError))) return -1;
		if (isError) {
			zv::Val allowedArrayKeys = pt_type_call_static(PT_CLASS_ALLOWED_ARRAY_KEYS_TYPES, PT_LC("gettype"), 0, NULL);
			if (UNEXPECTED(allowedArrayKeys.isUndef())) return -1;
			zv::Val intersected = combinator2(PT_LC("intersect"), allowedArrayKeys.raw(), offsetTypeArg);
			if (UNEXPECTED(intersected.isUndef() || !zv::Ref(intersected.raw()).isObject())) return -1;
			offsetArrayKeyType = callType(Z_OBJ_P(intersected.raw()), PT_LC("toarraykey"), 0, NULL);
			if (UNEXPECTED(offsetArrayKeyType.isUndef())) return -1;
			bool isNever;
			if (UNEXPECTED(!isInstance(offsetArrayKeyType.raw(), pt_ce_never_type, isNever))) return -1;
			if (isNever) return PT_TRI_NO;
		}
		bool outside;
		if (UNEXPECTED(!offsetOutsideKeyType(offsetArrayKeyType.raw(), outside))) return -1;
		return outside ? PT_TRI_NO : PT_TRI_MAYBE;
	}

	/* new ErrorType() for an offset outside the key type, else the item
	 * type (mixed for an ErrorType item type); UNDEF = pending exception */
	zv::Val getOffsetValueType(zval *offsetTypeArg) const
	{
		zv::Val offsetType = callType(Z_OBJ_P(offsetTypeArg), PT_LC("toarraykey"), 0, NULL);
		if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
		bool outside;
		if (UNEXPECTED(!offsetOutsideKeyType(offsetType.raw(), outside))) return zv::Val();
		if (outside) return pt_type_new_error_type();
		zv::Val type = thisGetItemType();
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		bool isError;
		if (UNEXPECTED(!isInstance(type.raw(), PT_CLASS_ERROR_TYPE, isError))) return zv::Val();
		if (isError) return pt_type_new_mixed_type();
		return type;
	}

	/* the offset appended (a null offset is the next integer key: int for a
	 * non-integer key type, the constant keys plus one for constant integer
	 * keys, the integer members of a mixed key type otherwise); a constant
	 * offset covering the key type becomes an array shape, any other
	 * constant offset an array with a HasOffsetValueType accessory, and a
	 * non-constant one a non-empty array; offsetType NULL = the twin's
	 * null; UNDEF = pending exception */
	/* private unionKeyTypeWithConstantOffset(): a key union (not benevolent,
	 * not a template) that already holds the constant offset is handed back
	 * as it is — the array and its key union live on across the writes;
	 * TypeCombinator::union($keyType, $offsetType) otherwise; UNDEF = pending
	 * exception */
	static zv::Val unionKeyTypeWithConstantOffset(zval *k, zval *offsetType)
	{
		bool isUnion, isBenevolent;
		if (UNEXPECTED(!isInstance(k, PT_CLASS_UNION_TYPE, isUnion))) return zv::Val();
		if (UNEXPECTED(!isInstance(k, PT_CLASS_BENEVOLENT_UNION_TYPE, isBenevolent))) return zv::Val();
		if (isUnion && !isBenevolent) {
			zend_class_entry *templateType = pt_class(PT_CLASS_TEMPLATE_TYPE);
			if (UNEXPECTED(templateType == NULL && EG(exception))) return zv::Val();
			if (templateType == NULL || !instanceof_function(Z_OBJCE_P(k), templateType)) {
				zend_long covers = pt_type_call_result_trinary(Z_OBJ_P(k), PT_LC("issupertypeof"), 1, offsetType);
				if (UNEXPECTED(covers < 0)) return zv::Val();
				if (covers == PT_TRI_YES) return zv::Val::copyOf(zv::Ref(k));
			}
		}
		return combinator2(PT_LC("union"), k, offsetType);
	}

	zv::Val setOffsetValueType(zval *offsetTypeArg, zval *valueType, bool unionValues) const
	{
		zval *k = keyType();
		zval *i = k != NULL ? itemType() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(i == NULL)) return zv::Val();
		zv::Val offsetType;
		if (offsetTypeArg == NULL) {
			zend_long isKeyTypeInteger = pt_type_call_trinary(Z_OBJ_P(k), PT_LC("isinteger"), 0, NULL);
			if (UNEXPECTED(isKeyTypeInteger < 0)) return zv::Val();
			if (isKeyTypeInteger == PT_TRI_NO) {
				offsetType = integerType();
			} else if (isKeyTypeInteger == PT_TRI_YES) {
				zv::Val constantScalars = pt_type_call_array(Z_OBJ_P(k), PT_LC("getconstantscalartypes"), 0, NULL);
				if (UNEXPECTED(constantScalars.isUndef())) return zv::Val();
				uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(constantScalars.raw()));
				if (count > 0) {
					/* $offsetTypes = $constantScalars; foreach ($constantScalars as $constantScalar) $offsetTypes[] = new ConstantIntegerType($constantScalar->getValue() + 1), skipping PHP_INT_MAX */
					zv::Arr all = zv::Arr::create(count * 2);
					for (zv::ArrayEntry entry : zv::ArrRef(constantScalars.raw())) {
						all.push(entry.value());
					}
					for (zv::ArrayEntry entry : zv::ArrRef(constantScalars.raw())) {
						zval *constantScalar = entry.value().deref().raw();
						if (UNEXPECTED(Z_TYPE_P(constantScalar) != IS_OBJECT)) {
							zend_type_error("phpstan_turbo: getConstantScalarTypes() must return %s instances", ZSTR_VAL(pt_ce_constant_integer_type->name));
							return zv::Val();
						}
						zv::Val value = pt_type_call(Z_OBJ_P(constantScalar), PT_LC("getvalue"), 0, NULL);
						if (UNEXPECTED(value.isUndef())) return zv::Val();
						if (UNEXPECTED(Z_TYPE_P(value.raw()) != IS_LONG)) {
							pt_throw_should_not_happen();
							return zv::Val();
						}
						// an offset past PHP_INT_MAX cannot be assigned, so it's not a possible key
						if (Z_LVAL_P(value.raw()) == ZEND_LONG_MAX) continue;
						zv::Val next = pt_type_new_constant_integer(Z_LVAL_P(value.raw()) + 1);
						if (UNEXPECTED(next.isUndef())) return zv::Val();
						all.push(std::move(next));
					}
					offsetType = pt_type_call_static_spread(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), all.table());
				} else {
					offsetType = zv::Val::copyOf(zv::Ref(k));
				}
			} else {
				zv::Val integerTypes = collectIntegerTypes(k);
				if (UNEXPECTED(integerTypes.isUndef())) return zv::Val();
				if (zend_hash_num_elements(Z_ARRVAL_P(integerTypes.raw())) == 0) {
					offsetType = zv::Val::copyOf(zv::Ref(k));
				} else {
					offsetType = pt_type_call_static_spread(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), Z_ARRVAL_P(integerTypes.raw()));
				}
			}
		} else {
			offsetType = callType(Z_OBJ_P(offsetTypeArg), PT_LC("toarraykey"), 0, NULL);
		}
		if (UNEXPECTED(offsetType.isUndef() || !zv::Ref(offsetType.raw()).isObject())) return zv::Val();

		zend_class_entry *offsetCe = Z_OBJCE_P(offsetType.raw());
		if (instanceof_function(offsetCe, pt_ce_constant_string_type) || instanceof_function(offsetCe, pt_ce_constant_integer_type)) {
			zend_long covers = pt_type_call_result_trinary(Z_OBJ_P(offsetType.raw()), PT_LC("issupertypeof"), 1, k);
			if (UNEXPECTED(covers < 0)) return zv::Val();
			if (covers == PT_TRI_YES) {
				zv::Val builder = pt_type_call_static(PT_CLASS_CONSTANT_ARRAY_TYPE_BUILDER, PT_LC("createempty"), 0, NULL);
				if (UNEXPECTED(builder.isUndef())) return zv::Val();
				zv::Args args{offsetType.raw(), valueType};
				zv::Val set = pt_type_call(Z_OBJ_P(builder.raw()), PT_LC("setoffsetvaluetype"), 2, args);
				if (UNEXPECTED(set.isUndef())) return zv::Val();
				return pt_type_call(Z_OBJ_P(builder.raw()), PT_LC("getarray"), 0, NULL);
			}

			zv::Val newKeyType = unionKeyTypeWithConstantOffset(k, offsetType.raw());
			if (UNEXPECTED(newKeyType.isUndef())) return zv::Val();
			zv::Val newItemType = combinator2(PT_LC("union"), i, valueType);
			zv::Val arrayType = thisWithTypes(std::move(newKeyType), std::move(newItemType));
			if (UNEXPECTED(arrayType.isUndef())) return zv::Val();
			zval hasOffsetValue;
			if (UNEXPECTED(!pt_has_offset_value_type_new(&hasOffsetValue, offsetType.raw(), valueType))) return zv::Val();
			zv::Val nonEmpty = nonEmptyArray();
			if (UNEXPECTED(nonEmpty.isUndef())) return zv::Val();
			zv::Arr types = zv::Arr::create(3);
			types.push(std::move(arrayType));
			types.push(zv::Val::adopt(hasOffsetValue));
			types.push(std::move(nonEmpty));
			return intersection(std::move(types));
		}

		zv::Val newKeyType = combinator2(PT_LC("union"), k, offsetType.raw());
		zv::Val newItemType = unionValues ? combinator2(PT_LC("union"), i, valueType) : zv::Val::copyOf(zv::Ref(valueType));
		zv::Val arrayType = thisWithTypes(std::move(newKeyType), std::move(newItemType));
		if (UNEXPECTED(arrayType.isUndef())) return zv::Val();
		zv::Val nonEmpty = nonEmptyArray();
		if (UNEXPECTED(nonEmpty.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(arrayType));
		types.push(std::move(nonEmpty));
		return intersection(std::move(types));
	}

	/* for constant-array item and value types, the item type with each
	 * shape's keys set (and, for shapes with optional keys, also with them
	 * unset), unioned; else the item type unioned with the value type —
	 * always a new self; UNDEF = pending exception */
	zv::Val setExistingOffsetValueType(zval *offsetType, zval *valueType) const
	{
		(void) offsetType;
		zval *k = keyType();
		zval *i = k != NULL ? itemType() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(i == NULL)) return zv::Val();
		zend_long itemIsConstantArray = pt_type_call_trinary(Z_OBJ_P(i), PT_LC("isconstantarray"), 0, NULL);
		if (UNEXPECTED(itemIsConstantArray < 0)) return zv::Val();
		if (itemIsConstantArray == PT_TRI_YES) {
			zend_long valueIsConstantArray = pt_type_call_trinary(Z_OBJ_P(valueType), PT_LC("isconstantarray"), 0, NULL);
			if (UNEXPECTED(valueIsConstantArray < 0)) return zv::Val();
			if (valueIsConstantArray == PT_TRI_YES) {
				zv::Val itemConstantArrays = pt_type_call_array(Z_OBJ_P(i), PT_LC("getconstantarrays"), 0, NULL);
				if (UNEXPECTED(itemConstantArrays.isUndef())) return zv::Val();
				zv::Val constantArrays = pt_type_call_array(Z_OBJ_P(valueType), PT_LC("getconstantarrays"), 0, NULL);
				if (UNEXPECTED(constantArrays.isUndef())) return zv::Val();
				zv::Arr newItemTypes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(constantArrays.raw())) * 2);
				for (zv::ArrayEntry entry : zv::ArrRef(constantArrays.raw())) {
					zval *constArray = entry.value().deref().raw();
					if (UNEXPECTED(Z_TYPE_P(constArray) != IS_OBJECT)) {
						zend_type_error("phpstan_turbo: getConstantArrays() must return ConstantArrayType instances");
						return zv::Val();
					}
					/* A written shape with optional keys is not all-or-nothing:
					 * each optional key may or may not be present on its own,
					 * so it is written as optional (present keys keep their
					 * certainty, the value unions with what the key held)
					 * instead of once with every key required and once with
					 * every optional key unset. */
					zv::Val optionalKeys = pt_type_call_array(Z_OBJ_P(constArray), PT_LC("getoptionalkeys"), 0, NULL);
					if (UNEXPECTED(optionalKeys.isUndef())) return zv::Val();
					if (zend_hash_num_elements(Z_ARRVAL_P(optionalKeys.raw())) > 0 && zend_hash_num_elements(Z_ARRVAL_P(itemConstantArrays.raw())) == 1) {
						zval *itemConstantArray = zend_hash_index_find(Z_ARRVAL_P(itemConstantArrays.raw()), 0);
						if (UNEXPECTED(itemConstantArray == NULL || Z_TYPE_P(itemConstantArray) != IS_OBJECT)) {
							zend_type_error("phpstan_turbo: getConstantArrays() must return a list of ConstantArrayType instances");
							return zv::Val();
						}
						zv::Val builder = pt_type_call_static(PT_CLASS_CONSTANT_ARRAY_TYPE_BUILDER, PT_LC("createfromconstantarray"), 1, itemConstantArray);
						if (UNEXPECTED(builder.isUndef())) return zv::Val();
						zv::Val shapeKeyTypes = pt_type_call_array(Z_OBJ_P(constArray), PT_LC("getkeytypes"), 0, NULL);
						if (UNEXPECTED(shapeKeyTypes.isUndef())) return zv::Val();
						for (zv::ArrayEntry keyEntry : zv::ArrRef(shapeKeyTypes.raw())) {
							zval *keyType = keyEntry.value().deref().raw();
							if (UNEXPECTED(Z_TYPE_P(keyType) != IS_OBJECT || keyEntry.hasStringKey())) {
								zend_type_error("phpstan_turbo: getKeyTypes() must return a list of %s", ptcls::type);
								return zv::Val();
							}
							zv::Val offsetValue = callType(Z_OBJ_P(constArray), PT_LC("getoffsetvaluetype"), 1, keyType);
							if (UNEXPECTED(offsetValue.isUndef())) return zv::Val();
							zval index;
							ZVAL_LONG(&index, (zend_long) keyEntry.indexKey());
							zv::Val optional = pt_type_call(Z_OBJ_P(constArray), PT_LC("isoptionalkey"), 1, &index);
							if (UNEXPECTED(optional.isUndef())) return zv::Val();
							zv::Args setArgs{keyType, offsetValue.raw(), bool(zend_is_true(optional.raw()))};
							zv::Val set = pt_type_call(Z_OBJ_P(builder.raw()), PT_LC("setoffsetvaluetype"), 3, setArgs);
							if (UNEXPECTED(set.isUndef())) return zv::Val();
						}
						zv::Val shape = pt_type_call(Z_OBJ_P(builder.raw()), PT_LC("getarray"), 0, NULL);
						if (UNEXPECTED(shape.isUndef())) return zv::Val();
						zv::Val accessories = pt_type_call_static(PT_CLASS_TYPE_UTILS, PT_LC("getaccessorytypes"), 1, i);
						if (UNEXPECTED(accessories.isUndef())) return zv::Val();
						if (UNEXPECTED(Z_TYPE_P(accessories.raw()) != IS_ARRAY)) {
							zend_type_error("phpstan_turbo: TypeUtils::getAccessoryTypes() must return an array");
							return zv::Val();
						}
						zv::Arr intersectArgs = zv::Arr::create(1 + zend_hash_num_elements(Z_ARRVAL_P(accessories.raw())));
						intersectArgs.push(std::move(shape));
						for (zv::ArrayEntry accessoryEntry : zv::ArrRef(accessories.raw())) {
							intersectArgs.push(accessoryEntry.value());
						}
						zv::Val intersected = pt_type_call_static_spread(PT_CLASS_TYPE_COMBINATOR, PT_LC("intersect"), intersectArgs.table());
						if (UNEXPECTED(intersected.isUndef())) return zv::Val();
						newItemTypes.push(std::move(intersected));
						continue;
					}

					zv::Val newItemType = zv::Val::copyOf(zv::Ref(i));
					zv::Arr optionalKeyTypes = zv::Arr::create(4);
					zv::Val keyTypes = pt_type_call_array(Z_OBJ_P(constArray), PT_LC("getkeytypes"), 0, NULL);
					if (UNEXPECTED(keyTypes.isUndef())) return zv::Val();
					for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
						zval *keyType = keyEntry.value().deref().raw();
						if (UNEXPECTED(Z_TYPE_P(keyType) != IS_OBJECT || keyEntry.hasStringKey())) {
							zend_type_error("phpstan_turbo: getKeyTypes() must return a list of %s", ptcls::type);
							return zv::Val();
						}
						zv::Val offsetValue = callType(Z_OBJ_P(constArray), PT_LC("getoffsetvaluetype"), 1, keyType);
						if (UNEXPECTED(offsetValue.isUndef())) return zv::Val();
						zv::Args args{keyType, offsetValue.raw()};
						newItemType = callType(Z_OBJ_P(newItemType.raw()), PT_LC("setexistingoffsetvaluetype"), 2, args);
						if (UNEXPECTED(newItemType.isUndef())) return zv::Val();
						zval index;
						ZVAL_LONG(&index, (zend_long) keyEntry.indexKey());
						zv::Val optional = pt_type_call(Z_OBJ_P(constArray), PT_LC("isoptionalkey"), 1, &index);
						if (UNEXPECTED(optional.isUndef())) return zv::Val();
						if (!zend_is_true(optional.raw())) continue;
						optionalKeyTypes.push(zv::Ref(keyType));
					}
					newItemTypes.push(zv::Ref(newItemType.raw()));

					if (optionalKeyTypes.arrRef().size() == 0) continue;
					for (zv::ArrayEntry optionalEntry : optionalKeyTypes.arrRef()) {
						newItemType = callType(Z_OBJ_P(newItemType.raw()), PT_LC("unsetoffset"), 1, optionalEntry.value().raw());
						if (UNEXPECTED(newItemType.isUndef())) return zv::Val();
					}
					newItemTypes.push(std::move(newItemType));
				}
				zv::Val newItemType = pt_type_call_static_spread(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), newItemTypes.table());
				if (UNEXPECTED(newItemType.isUndef())) return zv::Val();
				/* $newItemType !== $this->itemType */
				if (!zv::Ref(newItemType.raw()).isObject() || Z_OBJ_P(newItemType.raw()) != Z_OBJ_P(i)) {
					if (UNEXPECTED(!checkType(newItemType.raw(), "__construct", 2, "itemType"))) return zv::Val();
					return create(k, newItemType.raw());
				}
			}
		}

		zv::Val unioned = combinator2(PT_LC("union"), i, valueType);
		if (UNEXPECTED(unioned.isUndef())) return zv::Val();
		if (UNEXPECTED(!checkType(unioned.raw(), "__construct", 2, "itemType"))) return zv::Val();
		return create(k, unioned.raw());
	}

	/* a constant offset possibly in the key type removed from it (the empty
	 * constant array when nothing remains), $this otherwise; UNDEF = pending
	 * exception */
	zv::Val unsetOffset(zval *offsetTypeArg) const
	{
		zv::Val offsetType = callType(Z_OBJ_P(offsetTypeArg), PT_LC("toarraykey"), 0, NULL);
		if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
		zend_class_entry *offsetCe = Z_OBJCE_P(offsetType.raw());
		if (instanceof_function(offsetCe, pt_ce_constant_integer_type) || instanceof_function(offsetCe, pt_ce_constant_string_type)) {
			zval *k = keyType();
			zval *i = k != NULL ? itemType() : NULL;
			if (UNEXPECTED(i == NULL)) return zv::Val();
			zend_long isSuper = pt_type_call_result_trinary(Z_OBJ_P(k), PT_LC("issupertypeof"), 1, offsetType.raw());
			if (UNEXPECTED(isSuper < 0)) return zv::Val();
			if (isSuper != PT_TRI_NO) {
				zv::Val keyType = combinator2(PT_LC("remove"), k, offsetType.raw());
				if (UNEXPECTED(keyType.isUndef())) return zv::Val();
				bool isNever;
				if (UNEXPECTED(!isInstance(keyType.raw(), pt_ce_never_type, isNever))) return zv::Val();
				if (isNever) return emptyConstantArray();
				if (UNEXPECTED(!checkType(keyType.raw(), "__construct", 1, "keyType"))) return zv::Val();
				return create(keyType.raw(), i);
			}
		}
		return thisValue();
	}

	/* new ArrayType($itemType->toString(), $valueType) for a non-integer
	 * item type (the ErrorType when it has no string form), new
	 * ArrayType($itemType, $valueType) otherwise; UNDEF = pending exception */
	zv::Val fillKeysArray(zval *valueType) const
	{
		zv::Val itemType = thisGetItemType();
		if (UNEXPECTED(itemType.isUndef())) return zv::Val();
		zend_long isInteger = pt_type_call_trinary(Z_OBJ_P(itemType.raw()), PT_LC("isinteger"), 0, NULL);
		if (UNEXPECTED(isInteger < 0)) return zv::Val();
		if (isInteger == PT_TRI_NO) {
			zv::Val stringKeyType = callType(Z_OBJ_P(itemType.raw()), PT_LC("tostring"), 0, NULL);
			if (UNEXPECTED(stringKeyType.isUndef())) return zv::Val();
			bool isError;
			if (UNEXPECTED(!isInstance(stringKeyType.raw(), PT_CLASS_ERROR_TYPE, isError))) return zv::Val();
			if (isError) return stringKeyType;
			zv::Val stringArrayKey = callType(Z_OBJ_P(stringKeyType.raw()), PT_LC("toarraykey"), 0, NULL);
			if (UNEXPECTED(stringArrayKey.isUndef())) return zv::Val();
			return create(stringArrayKey.raw(), valueType);
		}
		zv::Val itemArrayKey = callType(Z_OBJ_P(itemType.raw()), PT_LC("toarraykey"), 0, NULL);
		if (UNEXPECTED(itemArrayKey.isUndef())) return zv::Val();
		return create(itemArrayKey.raw(), valueType);
	}

	/* new self($this->getIterableValueType()->toArrayKey(), $this->getIterableKeyType()) */
	zv::Val flipArray() const
	{
		zv::Val valueType = thisGetIterableValueType();
		if (UNEXPECTED(valueType.isUndef())) return zv::Val();
		zv::Val keyType = callType(Z_OBJ_P(valueType.raw()), PT_LC("toarraykey"), 0, NULL);
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		zv::Val iterableKeyType = thisGetIterableKeyType();
		if (UNEXPECTED(iterableKeyType.isUndef())) return zv::Val();
		return create(keyType.raw(), iterableKeyType.raw());
	}

	/* the empty shape when the other key type excludes this one, $this
	 * when it covers it, the union of the per-shape intersections for
	 * other array shapes of known sealedness, else this array keyed by the
	 * other key type; UNDEF = pending exception */
	zv::Val intersectKeyArray(zval *otherArraysType) const
	{
		zv::Val otherKeyType = callType(Z_OBJ_P(otherArraysType), PT_LC("getiterablekeytype"), 0, NULL);
		if (UNEXPECTED(otherKeyType.isUndef())) return zv::Val();
		zv::Val keyType = thisGetIterableKeyType();
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		zend_long isKeySuperType = pt_type_call_result_trinary(Z_OBJ_P(otherKeyType.raw()), PT_LC("issupertypeof"), 1, keyType.raw());
		if (UNEXPECTED(isKeySuperType < 0)) return zv::Val();
		if (isKeySuperType == PT_TRI_NO) {
			zv::Val builder = pt_type_call_static(PT_CLASS_CONSTANT_ARRAY_TYPE_BUILDER, PT_LC("createempty"), 0, NULL);
			if (UNEXPECTED(builder.isUndef())) return zv::Val();
			return pt_type_call(Z_OBJ_P(builder.raw()), PT_LC("getarray"), 0, NULL);
		}
		if (isKeySuperType == PT_TRI_YES) return thisValue();

		zv::Val constantArrays = pt_type_call_array(Z_OBJ_P(otherArraysType), PT_LC("getconstantarrays"), 0, NULL);
		if (UNEXPECTED(constantArrays.isUndef())) return zv::Val();
		uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(constantArrays.raw()));
		if (count > 0) {
			/* When the other operand is one or more array shapes with a known
			 * sealedness, the result is a (possibly unsealed) array shape too:
			 * it can only contain the keys present in those shapes, each
			 * optional because the general first array may or may not have it. */
			bool allSealednessKnown = true;
			for (zv::ArrayEntry entry : zv::ArrRef(constantArrays.raw())) {
				zval *constantArray = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(constantArray) != IS_OBJECT)) {
					zend_type_error("phpstan_turbo: getConstantArrays() must return ConstantArrayType instances");
					return zv::Val();
				}
				zend_long unsealed = pt_type_call_trinary(Z_OBJ_P(constantArray), PT_LC("isunsealed"), 0, NULL);
				if (UNEXPECTED(unsealed < 0)) return zv::Val();
				if (unsealed == PT_TRI_MAYBE) {
					allSealednessKnown = false;
					break;
				}
			}
			if (allSealednessKnown) {
				zv::Arr results = zv::Arr::create(count);
				for (zv::ArrayEntry entry : zv::ArrRef(constantArrays.raw())) {
					zv::Val result = intersectConstantArrayShape(entry.value().deref().raw());
					if (UNEXPECTED(result.isUndef())) return zv::Val();
					results.push(std::move(result));
				}
				return pt_type_call_static_spread(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), results.table());
			}
		}

		return thisWithTypes(std::move(otherKeyType), thisGetIterableValueType());
	}

	/* private: the shape's keys that meet this key type, each optional with
	 * this value type, plus a narrowed unsealed part; UNDEF = pending
	 * exception */
	zv::Val intersectConstantArrayShape(zval *constantArray) const
	{
		zv::Val builder = pt_type_call_static(PT_CLASS_CONSTANT_ARRAY_TYPE_BUILDER, PT_LC("createempty"), 0, NULL);
		if (UNEXPECTED(builder.isUndef())) return zv::Val();
		zv::Val valueType = thisGetIterableValueType();
		zv::Val keyType = thisGetIterableKeyType();
		if (UNEXPECTED(valueType.isUndef() || keyType.isUndef())) return zv::Val();
		zv::Val keyTypes = pt_type_call_array(Z_OBJ_P(constantArray), PT_LC("getkeytypes"), 0, NULL);
		if (UNEXPECTED(keyTypes.isUndef())) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(keyTypes.raw())) {
			zval *shapeKeyType = entry.value().deref().raw();
			zv::Val intersected = combinator2(PT_LC("intersect"), shapeKeyType, keyType.raw());
			if (UNEXPECTED(intersected.isUndef())) return zv::Val();
			bool isNever;
			if (UNEXPECTED(!isInstance(intersected.raw(), pt_ce_never_type, isNever))) return zv::Val();
			if (isNever) continue;
			zv::Args args{shapeKeyType, valueType.raw(), true};
			zv::Val set = pt_type_call(Z_OBJ_P(builder.raw()), PT_LC("setoffsetvaluetype"), 3, args);
			if (UNEXPECTED(set.isUndef())) return zv::Val();
		}

		zv::Val unsealed = pt_type_call(Z_OBJ_P(constantArray), PT_LC("getunsealedtypes"), 0, NULL);
		if (UNEXPECTED(unsealed.isUndef())) return zv::Val();
		zend_long isUnsealed = pt_type_call_trinary(Z_OBJ_P(constantArray), PT_LC("isunsealed"), 0, NULL);
		if (UNEXPECTED(isUnsealed < 0)) return zv::Val();
		if (isUnsealed == PT_TRI_YES && !zv::Ref(unsealed.raw()).isNull()) {
			if (UNEXPECTED(!zv::Ref(unsealed.raw()).isArray())) {
				zend_type_error("phpstan_turbo: getUnsealedTypes() must return ?array");
				return zv::Val();
			}
			zv::Ref unsealedKey = zv::ArrRef(unsealed.raw()).findIndex(0);
			if (UNEXPECTED(unsealedKey.raw() == NULL || !unsealedKey.deref().isObject())) {
				zend_type_error("phpstan_turbo: getUnsealedTypes() must return a pair of %s", ptcls::type);
				return zv::Val();
			}
			zv::Val narrowedUnsealedKey = combinator2(PT_LC("intersect"), unsealedKey.deref().raw(), keyType.raw());
			if (UNEXPECTED(narrowedUnsealedKey.isUndef())) return zv::Val();
			bool isNever;
			if (UNEXPECTED(!isInstance(narrowedUnsealedKey.raw(), pt_ce_never_type, isNever))) return zv::Val();
			if (!isNever) {
				zv::Args args{narrowedUnsealedKey.raw(), valueType.raw()};
				zv::Val made = pt_type_call(Z_OBJ_P(builder.raw()), PT_LC("makeunsealed"), 2, args);
				if (UNEXPECTED(made.isUndef())) return zv::Val();
			}
		}

		return pt_type_call(Z_OBJ_P(builder.raw()), PT_LC("getarray"), 0, NULL);
	}

	zv::Val popArray() const { return thisValue(); }
	zv::Val reverseArray() const { return thisValue(); }

	/* false for a strict search of a needle outside the value type, the
	 * iterable key type or false otherwise; strict NULL = the twin's null;
	 * UNDEF = pending exception */
	zv::Val searchArray(zval *needleType, zval *strict) const
	{
		zend_long strictValue = PT_TRI_MAYBE;
		if (strict != NULL) {
			strictValue = pt_type_trinary_value(strict);
			if (UNEXPECTED(strictValue < 0)) return zv::Val();
		}
		if (strictValue == PT_TRI_YES) {
			zv::Val valueType = thisGetIterableValueType();
			if (UNEXPECTED(valueType.isUndef())) return zv::Val();
			zend_long isSuper = pt_type_call_result_trinary(Z_OBJ_P(valueType.raw()), PT_LC("issupertypeof"), 1, needleType);
			if (UNEXPECTED(isSuper < 0)) return zv::Val();
			if (isSuper == PT_TRI_NO) return constantBoolean(false);
		}
		zv::Val keyType = thisGetIterableKeyType();
		zv::Val falseType = constantBoolean(false);
		if (UNEXPECTED(keyType.isUndef() || falseType.isUndef())) return zv::Val();
		return combinator2(PT_LC("union"), keyType.raw(), falseType.raw());
	}

	zv::Val shiftArray() const { return thisValue(); }

	/* new IntersectionType([$this->withTypes(int<0, max>, $this->itemType), new AccessoryArrayListType()]) */
	zv::Val shuffleArray() const
	{
		zval *i = itemType();
		if (UNEXPECTED(i == NULL)) return zv::Val();
		return listIntersection(thisWithTypes(nonNegativeIntegers(), zv::Val::copyOf(zv::Ref(i))));
	}

	/* the empty constant array for a zero length, a list for dropped keys
	 * of an integer key type, $this otherwise; UNDEF = pending exception */
	zv::Val sliceArray(zval *offsetType, zval *lengthType, zval *preserveKeys) const
	{
		(void) offsetType;
		zend_long lengthIsZero;
		if (UNEXPECTED(!zeroIsSuperTypeOf(lengthType, lengthIsZero))) return zv::Val();
		if (lengthIsZero == PT_TRI_YES) return emptyConstantArray();
		zend_long preserve = pt_type_trinary_value(preserveKeys);
		if (UNEXPECTED(preserve < 0)) return zv::Val();
		if (preserve == PT_TRI_NO) {
			zval *k = keyType();
			zval *i = k != NULL ? itemType() : NULL;
			if (UNEXPECTED(i == NULL)) return zv::Val();
			zend_long keyIsInteger = pt_type_call_trinary(Z_OBJ_P(k), PT_LC("isinteger"), 0, NULL);
			if (UNEXPECTED(keyIsInteger < 0)) return zv::Val();
			if (keyIsInteger == PT_TRI_YES) return listIntersection(thisWithTypes(nonNegativeIntegers(), zv::Val::copyOf(zv::Ref(i))));
		}
		return thisValue();
	}

	/* the empty constant array when everything is replaced by nothing; else
	 * the key type with its integer members renumbered from 0, unioned with
	 * the replacement's keys, the values unioned — non-empty for a
	 * non-empty replacement, a list for an integer key type; UNDEF =
	 * pending exception */
	zv::Val spliceArray(zval *offsetType, zval *lengthType, zval *replacementType) const
	{
		zv::Val replacementArrayType = callType(Z_OBJ_P(replacementType), PT_LC("toarray"), 0, NULL);
		if (UNEXPECTED(replacementArrayType.isUndef())) return zv::Val();
		zend_long replacementAtLeastOnce = pt_type_call_trinary(Z_OBJ_P(replacementArrayType.raw()), PT_LC("isiterableatleastonce"), 0, NULL);
		if (UNEXPECTED(replacementAtLeastOnce < 0)) return zv::Val();

		zend_long offsetIsZero;
		if (UNEXPECTED(!zeroIsSuperTypeOf(offsetType, offsetIsZero))) return zv::Val();
		if (offsetIsZero == PT_TRI_YES) {
			zend_long lengthIsNull = pt_type_call_trinary(Z_OBJ_P(lengthType), PT_LC("isnull"), 0, NULL);
			if (UNEXPECTED(lengthIsNull < 0)) return zv::Val();
			if (lengthIsNull == PT_TRI_YES && replacementAtLeastOnce == PT_TRI_NO) return emptyConstantArray();
		}

		zv::Val existingArrayKeyType = thisGetIterableKeyType();
		if (UNEXPECTED(existingArrayKeyType.isUndef())) return zv::Val();
		zv::Val keyType = renumberIntegerKeys(existingArrayKeyType.raw());
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();

		zv::Val replacementKeys = callType(Z_OBJ_P(replacementArrayType.raw()), PT_LC("getkeysarray"), 0, NULL);
		if (UNEXPECTED(replacementKeys.isUndef())) return zv::Val();
		zv::Val replacementKeyType = callType(Z_OBJ_P(replacementKeys.raw()), PT_LC("getiterablekeytype"), 0, NULL);
		if (UNEXPECTED(replacementKeyType.isUndef())) return zv::Val();
		zv::Val newKeyType = combinator2(PT_LC("union"), keyType.raw(), replacementKeyType.raw());
		zv::Val valueType = thisGetIterableValueType();
		if (UNEXPECTED(newKeyType.isUndef() || valueType.isUndef())) return zv::Val();
		zv::Val replacementValueType = callType(Z_OBJ_P(replacementArrayType.raw()), PT_LC("getiterablevaluetype"), 0, NULL);
		if (UNEXPECTED(replacementValueType.isUndef())) return zv::Val();
		zv::Val newValueType = combinator2(PT_LC("union"), valueType.raw(), replacementValueType.raw());
		zv::Val arrayType = thisWithTypes(std::move(newKeyType), std::move(newValueType));
		if (UNEXPECTED(arrayType.isUndef())) return zv::Val();

		zv::Arr accessories = zv::Arr::create(3);
		if (replacementAtLeastOnce == PT_TRI_YES) {
			zv::Val nonEmpty = nonEmptyArray();
			if (UNEXPECTED(nonEmpty.isUndef())) return zv::Val();
			accessories.push(std::move(nonEmpty));
		}
		zend_long existingKeyIsInteger = pt_type_call_trinary(Z_OBJ_P(existingArrayKeyType.raw()), PT_LC("isinteger"), 0, NULL);
		if (UNEXPECTED(existingKeyIsInteger < 0)) return zv::Val();
		if (existingKeyIsInteger == PT_TRI_YES) {
			zv::Val list = accessoryList();
			if (UNEXPECTED(list.isUndef())) return zv::Val();
			accessories.push(std::move(list));
		}
		if (accessories.arrRef().size() > 0) {
			accessories.push(std::move(arrayType));
			return intersection(std::move(accessories));
		}
		return arrayType;
	}

	/* `ArrayType` doesn't carry list-ness on its own — that's an
	 * `AccessoryArrayListType` in an enclosing `IntersectionType`. */
	zv::Val makeListMaybe() const { return thisValue(); }

	/* the list narrowed to the size's bounds: a non-empty array unless the
	 * key type covers int<0, bound - 1> and the bound is below the builder's
	 * limit; a rebuilt shape for a bounded size, else the lower bound
	 * anchored with HasOffsetValueType accessories; UNDEF = pending
	 * exception */
	zv::Val truncateListToSize(zval *sizeType) const
	{
		/* [$min, $max] = ConstantArrayType::extractTruncateListBounds($sizeType) */
		zv::Val bounds = pt_type_call_static(PT_CLASS_CONSTANT_ARRAY_TYPE, PT_LC("extracttruncatelistbounds"), 1, sizeType);
		if (UNEXPECTED(bounds.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(bounds.raw()).isArray())) {
			zend_type_error("phpstan_turbo: ConstantArrayType::extractTruncateListBounds() must return array");
			return zv::Val();
		}
		zv::Ref minZv = zv::ArrRef(bounds.raw()).findIndex(0);
		zv::Ref maxZv = zv::ArrRef(bounds.raw()).findIndex(1);
		NullableLong min = minZv.raw() != NULL ? NullableLong::from(minZv.deref().raw()) : NullableLong::null();
		NullableLong max = maxZv.raw() != NULL ? NullableLong::from(maxZv.deref().raw()) : NullableLong::null();

		zend_long limit;
		if (UNEXPECTED(!arrayCountLimit(limit))) return zv::Val();

		/* `isList()` is deliberately NOT checked here — see the matching
		 * note on `ConstantArrayType::truncateListToSize`. The call site
		 * has already established outer list-ness. */
		bool fallBack = min.isNull || min.value >= limit;
		if (!fallBack) {
			/* IntegerRangeType::fromInterval(0, ($max ?? $min) - 1) */
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
			zend_long covers = pt_type_call_result_trinary(Z_OBJ_P(keyType.raw()), PT_LC("issupertypeof"), 1, range.raw());
			if (UNEXPECTED(covers < 0)) return zv::Val();
			fallBack = covers != PT_TRI_YES;
		}
		if (fallBack) return intersectedWithNonEmpty();

		if (!max.isNull) {
			/* Bounded range — `ArrayType` doesn't carry per-offset types, so
			 * rebuild via the same CAT builder logic as `ConstantArrayType`.
			 * The values come from `$this->getOffsetValueType()` (which on a
			 * general `ArrayType` collapses to the iterable value type). */
			zval span, limitZv;
			phpSub(max.value, min.value, &span);
			ZVAL_LONG(&limitZv, limit);
			if (zend_compare(&limitZv, &span) < 0) return intersectedWithNonEmpty();

			zv::Val builder = pt_type_call_static(PT_CLASS_CONSTANT_ARRAY_TYPE_BUILDER, PT_LC("createempty"), 0, NULL);
			if (UNEXPECTED(builder.isUndef())) return zv::Val();
			for (zend_long i = 0; i < max.value; i++) {
				zv::Val offsetType = pt_type_new_constant_integer(i);
				if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
				zv::Val valueType = thisGetOffsetValueType(offsetType.raw());
				if (UNEXPECTED(valueType.isUndef())) return zv::Val();
				zv::Args args{offsetType.raw(), valueType.raw(), bool(i >= min.value)};
				zv::Val set = pt_type_call(Z_OBJ_P(builder.raw()), PT_LC("setoffsetvaluetype"), 3, args);
				if (UNEXPECTED(set.isUndef())) return zv::Val();
			}

			zv::Val builtArray = pt_type_call(Z_OBJ_P(builder.raw()), PT_LC("getarray"), 0, NULL);
			if (UNEXPECTED(builtArray.isUndef())) return zv::Val();
			zv::Val isList = pt_type_call(Z_OBJ_P(builder.raw()), PT_LC("islist"), 0, NULL);
			if (UNEXPECTED(isList.isUndef())) return zv::Val();
			if (!zend_is_true(isList.raw())) {
				if (UNEXPECTED(!zv::Ref(builtArray.raw()).isObject())) {
					zend_type_error("phpstan_turbo: ConstantArrayTypeBuilder::getArray() must return %s", ptcls::type);
					return zv::Val();
				}
				zv::Val constantArrays = pt_type_call_array(Z_OBJ_P(builtArray.raw()), PT_LC("getconstantarrays"), 0, NULL);
				if (UNEXPECTED(constantArrays.isUndef())) return zv::Val();
				if (zend_hash_num_elements(Z_ARRVAL_P(constantArrays.raw())) == 1) {
					zv::Ref only = zv::ArrRef(constantArrays.raw()).findIndex(0);
					if (UNEXPECTED(only.raw() == NULL || !only.deref().isObject())) {
						zend_type_error("phpstan_turbo: getConstantArrays() must return a list of ConstantArrayType");
						return zv::Val();
					}
					builtArray = pt_type_call(Z_OBJ_P(only.deref().raw()), PT_LC("makelist"), 0, NULL);
					if (UNEXPECTED(builtArray.isUndef())) return zv::Val();
				}
			}
			return builtArray;
		}

		/* Unbounded max on a general `ArrayType` list: we can't enumerate the
		 * trailing entries, so anchor the lower bound with
		 * `HasOffsetValueType` accessories (skipping offset 0 — already
		 * implied by `NonEmptyArrayType`). */
		zv::Val nonEmpty = nonEmptyArray();
		zv::Val zero = pt_type_new_constant_integer(0);
		if (UNEXPECTED(nonEmpty.isUndef() || zero.isUndef())) return zv::Val();
		zv::Arr intersectionTypes = zv::Arr::create(4);
		intersectionTypes.push(thisValue());
		intersectionTypes.push(std::move(nonEmpty));
		zend_long added = 0;
		for (zend_long i = 0; i < min.value; i++) {
			zv::Val offsetType = pt_type_new_constant_integer(i);
			if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
			zend_long isZero = pt_type_call_result_trinary(Z_OBJ_P(zero.raw()), PT_LC("issupertypeof"), 1, offsetType.raw());
			if (UNEXPECTED(isZero < 0)) return zv::Val();
			if (isZero == PT_TRI_YES) continue;
			if (added > PT_AT_TRUNCATE_ACCESSORIES_LIMIT) break;
			zv::Val valueType = thisGetOffsetValueType(offsetType.raw());
			if (UNEXPECTED(valueType.isUndef())) return zv::Val();
			zval accessory;
			if (UNEXPECTED(!pt_has_offset_value_type_new(&accessory, offsetType.raw(), valueType.raw()))) return zv::Val();
			intersectionTypes.push(zv::Val::adopt(accessory));
			added++;
		}
		return pt_type_call_static_spread(PT_CLASS_TYPE_COMBINATOR, PT_LC("intersect"), intersectionTypes.table());
	}

	/* $this->withTypes($this->keyType, $cb($this->getItemType())) */
	zv::Val mapValueType(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zv::Val itemType = thisGetItemType();
		if (UNEXPECTED(itemType.isUndef())) return zv::Val();
		zval mapped;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, itemType.raw(), &mapped))) return zv::Val();
		return thisWithTypes(zv::Val::copyOf(zv::Ref(k)), zv::Val::adopt(mapped));
	}

	/* $this->withTypes($cb($this->keyType), $this->getItemType()) */
	zv::Val mapKeyType(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zval mapped;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, k, &mapped))) return zv::Val();
		return thisWithTypes(zv::Val::adopt(mapped), thisGetItemType());
	}

	/* `ArrayType` already models arbitrary key subsets. */
	zv::Val makeAllArrayKeysOptional() const { return thisValue(); }

	/* the key type with its constant strings case-folded and its string
	 * members re-accessorized for the case; UNDEF = pending exception */
	zv::Val changeKeyCaseArray(NullableLong caseArg) const
	{
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zv::Val caseZv = caseArg.toVal();
		zv::Val callback = pt_type_native_callback(changeKeyCaseCallback, caseZv.raw(), NULL);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		zval args[2];
		ZVAL_COPY_VALUE(&args[0], k);
		ZVAL_COPY_VALUE(&args[1], callback.raw());
		zv::Val newKeyType = pt_type_call_static(PT_CLASS_TYPE_TRAVERSER, PT_LC("map"), 2, args);
		return thisWithTypes(std::move(newKeyType), thisGetItemType());
	}

	/* the item type without the falsey types (the empty constant array
	 * when nothing remains); UNDEF = pending exception */
	zv::Val filterArrayRemovingFalsey() const
	{
		zv::Val falseyTypes = pt_type_call_static(PT_CLASS_STATIC_TYPE_FACTORY, PT_LC("falsey"), 0, NULL);
		if (UNEXPECTED(falseyTypes.isUndef())) return zv::Val();
		zv::Val itemType = thisGetItemType();
		if (UNEXPECTED(itemType.isUndef())) return zv::Val();
		zv::Val valueType = combinator2(PT_LC("remove"), itemType.raw(), falseyTypes.raw());
		if (UNEXPECTED(valueType.isUndef())) return zv::Val();
		bool isNever;
		if (UNEXPECTED(!isInstance(valueType.raw(), pt_ce_never_type, isNever))) return zv::Val();
		if (isNever) return emptyConstantArray();
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		return thisWithTypes(zv::Val::copyOf(zv::Ref(k)), std::move(valueType));
	}

	/* maybe unless the item type is no string, and even then maybe when
	 * string may be its supertype (a StrictMixedType item denies
	 * isString()); no otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isCallable() const
	{
		zval *i = itemType();
		if (UNEXPECTED(i == NULL)) return -1;
		zend_long isString = pt_type_call_trinary(Z_OBJ_P(i), PT_LC("isstring"), 0, NULL);
		if (UNEXPECTED(isString < 0)) return -1;
		if (isString != PT_TRI_NO) return PT_TRI_MAYBE;
		/* StrictMixedType denies isString() even though it is a supertype of
		 * string, so a value of that item type can still be the method name
		 * of a callable array. */
		zv::Val string = stringType();
		if (UNEXPECTED(string.isUndef())) return -1;
		zend_long isSuper = pt_type_call_result_trinary(Z_OBJ_P(string.raw()), PT_LC("issupertypeof"), 1, i);
		if (UNEXPECTED(isSuper < 0)) return -1;
		if (isSuper == PT_TRI_MAYBE) return PT_TRI_MAYBE;
		return PT_TRI_NO;
	}

	/* [new TrivialParametersAcceptor()] unless $this->isCallable()->no(),
	 * which throws; UNDEF = pending exception */
	zv::Val getCallableParametersAcceptors() const
	{
		zend_long callable;
		if (EXPECTED(isExact())) {
			callable = isCallable();
		} else {
			callable = pt_type_call_trinary(self, PT_LC("iscallable"), 0, NULL);
		}
		if (UNEXPECTED(callable < 0)) return zv::Val();
		if (callable == PT_TRI_NO) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zv::Val acceptor = pt_type_new(PT_CLASS_TRIVIAL_PARAMETERS_ACCEPTOR, 0, NULL);
		if (UNEXPECTED(acceptor.isUndef())) return zv::Val();
		zv::Arr acceptors = zv::Arr::create(1);
		acceptors.push(std::move(acceptor));
		return zv::Val(std::move(acceptors));
	}

	/* new UnionType([new ConstantIntegerType(0), new ConstantIntegerType(1)]) */
	static zv::Val toInteger()
	{
		zv::Val zero = pt_type_new_constant_integer(0);
		zv::Val one = pt_type_new_constant_integer(1);
		if (UNEXPECTED(zero.isUndef() || one.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(zero));
		types.push(std::move(one));
		return pt_type_new_union(std::move(types));
	}

	/* new UnionType([new ConstantFloatType(0.0), new ConstantFloatType(1.0)]) */
	static zv::Val toFloat()
	{
		zv::Val zero = pt_type_new_constant_float(0.0);
		zv::Val one = pt_type_new_constant_float(1.0);
		if (UNEXPECTED(zero.isUndef() || one.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(zero));
		types.push(std::move(one));
		return pt_type_new_union(std::move(types));
	}

	/* the union/intersection callback; the key and item maps unioned for
	 * an array; the empty map otherwise; UNDEF = pending exception */
	zv::Val inferTemplateTypes(zval *receivedType) const
	{
		bool isUnion, isIntersection = false;
		if (UNEXPECTED(!isInstance(receivedType, PT_CLASS_UNION_TYPE, isUnion))) return zv::Val();
		if (!isUnion && UNEXPECTED(!isInstance(receivedType, PT_CLASS_INTERSECTION_TYPE, isIntersection))) return zv::Val();
		if (isUnion || isIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(receivedType), PT_LC("infertemplatetypeson"), 1, &selfZv);
		}

		zend_long isArray = pt_type_call_trinary(Z_OBJ_P(receivedType), PT_LC("isarray"), 0, NULL);
		if (UNEXPECTED(isArray < 0)) return zv::Val();
		if (isArray == PT_TRI_YES) {
			zv::Val keyType = thisGetIterableKeyType();
			if (UNEXPECTED(keyType.isUndef())) return zv::Val();
			zv::Val receivedKeyType = callType(Z_OBJ_P(receivedType), PT_LC("getiterablekeytype"), 0, NULL);
			if (UNEXPECTED(receivedKeyType.isUndef())) return zv::Val();
			zv::Val keyTypeMap = pt_type_call(Z_OBJ_P(keyType.raw()), PT_LC("infertemplatetypes"), 1, receivedKeyType.raw());
			if (UNEXPECTED(keyTypeMap.isUndef())) return zv::Val();
			zv::Val itemType = thisGetItemType();
			if (UNEXPECTED(itemType.isUndef())) return zv::Val();
			zv::Val receivedValueType = callType(Z_OBJ_P(receivedType), PT_LC("getiterablevaluetype"), 0, NULL);
			if (UNEXPECTED(receivedValueType.isUndef())) return zv::Val();
			zv::Val itemTypeMap = pt_type_call(Z_OBJ_P(itemType.raw()), PT_LC("infertemplatetypes"), 1, receivedValueType.raw());
			if (UNEXPECTED(itemTypeMap.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(keyTypeMap.raw()).isObject())) {
				zend_type_error("phpstan_turbo: inferTemplateTypes() must return TemplateTypeMap");
				return zv::Val();
			}
			return pt_type_call(Z_OBJ_P(keyTypeMap.raw()), PT_LC("union"), 1, itemTypeMap.raw());
		}

		return pt_type_call_static(PT_CLASS_TEMPLATE_TYPE_MAP, PT_LC("createempty"), 0, NULL);
	}

	/* array_merge of the key and item types' referenced template types
	 * under the covariant composition of the position's variance */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const
	{
		zv::Val covariant = pt_type_call_static(PT_CLASS_TEMPLATE_TYPE_VARIANCE, PT_LC("createcovariant"), 0, NULL);
		if (UNEXPECTED(covariant.isUndef())) return zv::Val();
		zv::Val variance = pt_type_call(Z_OBJ_P(positionVariance), PT_LC("compose"), 1, covariant.raw());
		if (UNEXPECTED(variance.isUndef())) return zv::Val();
		zv::Val keyType = thisGetIterableKeyType();
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		zv::Val a = pt_type_call_array(Z_OBJ_P(keyType.raw()), PT_LC("getreferencedtemplatetypes"), 1, variance.raw());
		if (UNEXPECTED(a.isUndef())) return zv::Val();
		zv::Val itemType = thisGetItemType();
		if (UNEXPECTED(itemType.isUndef())) return zv::Val();
		zv::Val b = pt_type_call_array(Z_OBJ_P(itemType.raw()), PT_LC("getreferencedtemplatetypes"), 1, variance.raw());
		if (UNEXPECTED(b.isUndef())) return zv::Val();
		return arrayMerge(a.raw(), b.raw());
	}

	/* $this when $cb leaves both types, the empty constant array when it
	 * turns both into never, $this->withTypes() of the results otherwise;
	 * UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *k = keyType();
		zval *i = k != NULL ? itemType() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(i == NULL)) return zv::Val();
		zval newKeyType;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, k, &newKeyType))) return zv::Val();
		zv::Val keyType = zv::Val::adopt(newKeyType);
		zval newItemType;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, i, &newItemType))) return zv::Val();
		return traversed(std::move(keyType), zv::Val::adopt(newItemType));
	}

	/* IdentifierTypeNode('array') for implicit-mixed key and item types,
	 * array<item> for such a key type alone, array<key, item> otherwise;
	 * UNDEF = pending exception */
	zv::Val toPhpDocNode() const
	{
		zval *k = keyType();
		zval *i = k != NULL ? itemType() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(i == NULL)) return zv::Val();
		bool isMixedKeyType, isMixedItemType;
		if (UNEXPECTED(!isImplicitMixed(k, isMixedKeyType) || !isImplicitMixed(i, isMixedItemType))) return zv::Val();
		zv::Val name = zv::Val::string("array", sizeof("array") - 1);
		if (isMixedKeyType && isMixedItemType) return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
		zv::Val identifier = pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
		if (UNEXPECTED(identifier.isUndef())) return zv::Val();
		zv::Arr genericTypes = zv::Arr::create(2);
		if (!isMixedKeyType) {
			zv::Val keyNode = pt_type_call(Z_OBJ_P(k), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(keyNode.isUndef())) return zv::Val();
			genericTypes.push(std::move(keyNode));
		}
		zv::Val itemNode = pt_type_call(Z_OBJ_P(i), PT_LC("tophpdocnode"), 0, NULL);
		if (UNEXPECTED(itemNode.isUndef())) return zv::Val();
		genericTypes.push(std::move(itemNode));
		zv::Args args{identifier.raw(), genericTypes.raw()};
		return pt_type_new(PT_CLASS_GENERIC_TYPE_NODE, 2, args);
	}

	/* traverse() with $right's iterable key and value types as the
	 * callback's second arguments */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *k = keyType();
		zval *i = k != NULL ? itemType() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(i == NULL)) return zv::Val();
		zv::Val rightKey = pt_type_call(Z_OBJ_P(right), PT_LC("getiterablekeytype"), 0, NULL);
		if (UNEXPECTED(rightKey.isUndef())) return zv::Val();
		zv::Args args{k, rightKey.raw()};
		zval newKeyType;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, args, &newKeyType))) return zv::Val();
		zv::Val keyType = zv::Val::adopt(newKeyType);
		zv::Val rightValue = pt_type_call(Z_OBJ_P(right), PT_LC("getiterablevaluetype"), 0, NULL);
		if (UNEXPECTED(rightValue.isUndef())) return zv::Val();
		ZVAL_COPY_VALUE(&args[0], i);
		ZVAL_COPY_VALUE(&args[1], rightValue.raw());
		zval newItemType;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, args, &newItemType))) return zv::Val();
		return traversed(std::move(keyType), zv::Val::adopt(newItemType));
	}

	/* non-empty when the removed type covers the empty array; the empty
	 * constant array for a NonEmptyArrayType; the offset unset for a
	 * HasOffsetType, or a HasOffsetValueType whose value type covers the
	 * item type; null otherwise; UNDEF = pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		zv::Val empty = emptyConstantArray();
		if (UNEXPECTED(empty.isUndef())) return zv::Val();
		zend_long coversEmpty = pt_type_call_result_trinary(Z_OBJ_P(typeToRemove), PT_LC("issupertypeof"), 1, empty.raw());
		if (UNEXPECTED(coversEmpty < 0)) return zv::Val();
		if (coversEmpty == PT_TRI_YES) return intersectedWithNonEmpty();

		zend_class_entry *removeCe = Z_OBJCE_P(typeToRemove);
		if (instanceof_function(removeCe, pt_ce_non_empty_array_type)) return emptyConstantArray();

		if (instanceof_function(removeCe, pt_ce_has_offset_type)) {
			zv::Val offsetType = pt_has_offset_type_get_offset_type(Z_OBJ_P(typeToRemove));
			if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
			return thisUnsetOffset(offsetType.raw());
		}

		if (instanceof_function(removeCe, pt_ce_has_offset_value_type)) {
			zv::Val valueType = pt_has_offset_value_type_get_value_type(Z_OBJ_P(typeToRemove));
			if (UNEXPECTED(valueType.isUndef())) return zv::Val();
			zval *i = itemType();
			if (UNEXPECTED(i == NULL)) return zv::Val();
			zend_long covers = pt_type_call_result_trinary(Z_OBJ_P(valueType.raw()), PT_LC("issupertypeof"), 1, i);
			if (UNEXPECTED(covers < 0)) return zv::Val();
			if (covers == PT_TRI_YES) {
				zv::Val offsetType = pt_has_offset_value_type_get_offset_type(Z_OBJ_P(typeToRemove));
				if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
				return thisUnsetOffset(offsetType.raw());
			}
		}

		return zv::Val::null();
	}

	static zv::Val getFiniteTypes() { return zv::Val(zv::Arr::empty()); }

	/* $this->keyType->hasTemplateOrLateResolvableType() || $this->itemType->...;
	 * false = pending exception */
	[[nodiscard]] bool hasTemplateOrLateResolvableType(bool &out) const
	{
		zval *k = keyType();
		zval *i = k != NULL ? itemType() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(i == NULL)) return false;
		zv::Val keyHas = pt_type_call(Z_OBJ_P(k), PT_LC("hastemplateorlateresolvabletype"), 0, NULL);
		if (UNEXPECTED(keyHas.isUndef())) return false;
		if (zend_is_true(keyHas.raw())) {
			out = true;
			return true;
		}
		return pt_type_call_bool(Z_OBJ_P(i), PT_LC("hastemplateorlateresolvabletype"), 0, NULL, out);
	}

private:
	zend_object *self;

	/* exactly an ArrayType, none of its methods overridden: $this-calls can
	 * go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_array_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* $object->$name — a private Type slot declared here, read directly on a
	 * subclass too (as `$type->keyType` does); NULL = pending exception */
	[[nodiscard]] static zval *slotOf(zend_object *object, uint32_t slot, const char *name)
	{
		zval *value = OBJ_PROP_NUM(object, slot);
		if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_array_type->name), name);
			return NULL;
		}
		return value;
	}

	/* the $this-calls the twin makes — through the object's class */
	zv::Val thisGetKeyType() const
	{
		return isExact() ? getKeyType() : callType(self, PT_LC("getkeytype"), 0, NULL);
	}

	zv::Val thisGetItemType() const
	{
		return isExact() ? getItemType() : callType(self, PT_LC("getitemtype"), 0, NULL);
	}

	zv::Val thisGetIterableKeyType() const
	{
		return isExact() ? getIterableKeyType() : callType(self, PT_LC("getiterablekeytype"), 0, NULL);
	}

	zv::Val thisGetIterableValueType() const
	{
		return isExact() ? getIterableValueType() : callType(self, PT_LC("getiterablevaluetype"), 0, NULL);
	}

	zv::Val thisGetOffsetValueType(zval *offsetType) const
	{
		return isExact() ? getOffsetValueType(offsetType) : callType(self, PT_LC("getoffsetvaluetype"), 1, offsetType);
	}

	zv::Val thisUnsetOffset(zval *offsetType) const
	{
		return isExact() ? unsetOffset(offsetType) : callType(self, PT_LC("unsetoffset"), 1, offsetType);
	}

	/* $this->withTypes($keyType, $itemType) — this class's `new self()`
	 * when exact (with the typed parameters' checks), a subclass's override
	 * otherwise; UNDEF = pending exception */
	zv::Val thisWithTypes(zv::Val keyType, zv::Val itemType) const
	{
		if (UNEXPECTED(keyType.isUndef() || itemType.isUndef())) return zv::Val();
		if (EXPECTED(isExact())) {
			if (UNEXPECTED(!checkType(keyType.raw(), "withTypes", 1, "keyType") || !checkType(itemType.raw(), "withTypes", 2, "itemType"))) return zv::Val();
			return withTypes(keyType.raw(), itemType.raw());
		}
		zv::Args args{keyType.raw(), itemType.raw()};
		return callType(self, PT_LC("withtypes"), 2, args);
	}

	/* the tail of traverse()/traverseSimultaneously() */
	zv::Val traversed(zv::Val keyType, zv::Val itemType) const
	{
		zval *k = this->keyType();
		zval *i = k != NULL ? this->itemType() : NULL;
		if (UNEXPECTED(i == NULL)) return zv::Val();
		bool keySame = zv::Ref(keyType.raw()).isObject() && Z_OBJ_P(keyType.raw()) == Z_OBJ_P(k);
		bool itemSame = zv::Ref(itemType.raw()).isObject() && Z_OBJ_P(itemType.raw()) == Z_OBJ_P(i);
		if (!keySame || !itemSame) {
			bool keyNever, itemNever = false;
			if (UNEXPECTED(!isInstance(keyType.raw(), pt_ce_never_type, keyNever))) return zv::Val();
			if (keyNever && UNEXPECTED(!isInstance(itemType.raw(), pt_ce_never_type, itemNever))) return zv::Val();
			if (keyNever && itemNever) return emptyConstantArray();
			return thisWithTypes(std::move(keyType), std::move(itemType));
		}
		return thisValue();
	}

	/* $type->describe($level) as an owned string; UNDEF = pending exception */
	static zv::Val describeOf(zval *type, zval *level)
	{
		zv::Val description = pt_type_call(Z_OBJ_P(type), PT_LC("describe"), 1, level);
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(description.raw()).isString())) {
			zend_type_error("phpstan_turbo: describe() must return string");
			return zv::Val();
		}
		return description;
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
		zval description;
		if (UNEXPECTED(!pt_type_describe_precise(type, &description))) return false;
		bool plain = zv::Ref(&description).stringEquals("mixed");
		zval_ptr_dtor(&description);
		if (!plain) {
			out = false;
			return true;
		}
		zv::Val explicitMixed = pt_type_call(Z_OBJ_P(type), PT_LC("isexplicitmixed"), 0, NULL);
		if (UNEXPECTED(explicitMixed.isUndef())) return false;
		out = !zend_is_true(explicitMixed.raw());
		return true;
	}

	/* array_merge($a, $b): string keys kept, integer keys renumbered */
	static zv::Val arrayMerge(zval *a, zval *b)
	{
		zv::Arr merged = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(a)) + zend_hash_num_elements(Z_ARRVAL_P(b)));
		for (zval *source : { a, b }) {
			for (zv::ArrayEntry entry : zv::ArrRef(source)) {
				if (entry.hasStringKey()) {
					merged.set(entry.stringKey(), zv::Val::copyOf(entry.value()));
				} else {
					merged.push(entry.value());
				}
			}
		}
		return zv::Val(std::move(merged));
	}

	/* TypeCombinator::intersect(new self(new IntegerType(), $itemType), new AccessoryArrayListType()) */
	static zv::Val listOfIntersected(zval *itemType)
	{
		zv::Val integer = integerType();
		if (UNEXPECTED(integer.isUndef())) return zv::Val();
		zv::Val arrayType = create(integer.raw(), itemType);
		zv::Val list = accessoryList();
		if (UNEXPECTED(arrayType.isUndef() || list.isUndef())) return zv::Val();
		return combinator2(PT_LC("intersect"), arrayType.raw(), list.raw());
	}

	/* new IntersectionType([$arrayType, new AccessoryArrayListType()]) */
	static zv::Val listIntersection(zv::Val arrayType)
	{
		zv::Val list = accessoryList();
		if (UNEXPECTED(arrayType.isUndef() || list.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(arrayType));
		types.push(std::move(list));
		return intersection(std::move(types));
	}

	/* TypeCombinator::intersect($this, new NonEmptyArrayType()) */
	zv::Val intersectedWithNonEmpty() const
	{
		zv::Val nonEmpty = nonEmptyArray();
		if (UNEXPECTED(nonEmpty.isUndef())) return zv::Val();
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		return combinator2(PT_LC("intersect"), &selfZv, nonEmpty.raw());
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
		zv::Val benevolent = pt_type_new(PT_CLASS_BENEVOLENT_UNION_TYPE, 1, types.raw());
		if (UNEXPECTED(benevolent.isUndef())) return zv::Val();
		return callType(Z_OBJ_P(benevolent.raw()), PT_LC("toarraykey"), 0, NULL);
	}

	/* $a - $b through the engine (a float past the int range) */
	static void phpSub(zend_long a, zend_long b, zval *out)
	{
		zval x, y;
		ZVAL_LONG(&x, a);
		ZVAL_LONG(&y, b);
		sub_function(out, &x, &y);
	}

	/* ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT; false = pending exception */
	[[nodiscard]] static bool arrayCountLimit(zend_long &out)
	{
		zend_class_entry *ce = pt_class(PT_CLASS_CONSTANT_ARRAY_TYPE_BUILDER);
		if (UNEXPECTED(ce == NULL)) return false;
		if (UNEXPECTED(ce != pt_array_count_limit_ce)) {
			zend_class_constant *constant = (zend_class_constant *) zend_hash_str_find_ptr(&ce->constants_table, PT_LC("ARRAY_COUNT_LIMIT"));
			if (UNEXPECTED(constant == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: %s::ARRAY_COUNT_LIMIT not found", ZSTR_VAL(ce->name));
				return false;
			}
			if (UNEXPECTED(Z_TYPE(constant->value) == IS_CONSTANT_AST && zval_update_constant_ex(&constant->value, ce) != SUCCESS)) return false;
			if (UNEXPECTED(Z_TYPE(constant->value) != IS_LONG)) {
				zend_type_error("phpstan_turbo: %s::ARRAY_COUNT_LIMIT must be an int", ZSTR_VAL(ce->name));
				return false;
			}
			pt_array_count_limit = Z_LVAL(constant->value);
			pt_array_count_limit_ce = ce;
		}
		out = pt_array_count_limit;
		return true;
	}

	/* the shared tail of hasOffsetValueType()/getOffsetValueType():
	 * $this->getKeyType()->isSuperTypeOf($offsetType)->no()
	 * && ($offsetType->isString()->no() || !$offsetType->isConstantScalarValue()->no());
	 * false = pending exception */
	[[nodiscard]] bool offsetOutsideKeyType(zval *offsetType, bool &out) const
	{
		zv::Val keyType = thisGetKeyType();
		if (UNEXPECTED(keyType.isUndef())) return false;
		zend_long isSuper = pt_type_call_result_trinary(Z_OBJ_P(keyType.raw()), PT_LC("issupertypeof"), 1, offsetType);
		if (UNEXPECTED(isSuper < 0)) return false;
		if (isSuper != PT_TRI_NO) {
			out = false;
			return true;
		}
		zend_long isString = pt_type_call_trinary(Z_OBJ_P(offsetType), PT_LC("isstring"), 0, NULL);
		if (UNEXPECTED(isString < 0)) return false;
		if (isString == PT_TRI_NO) {
			out = true;
			return true;
		}
		zend_long isConstantScalar = pt_type_call_trinary(Z_OBJ_P(offsetType), PT_LC("isconstantscalarvalue"), 0, NULL);
		if (UNEXPECTED(isConstantScalar < 0)) return false;
		out = isConstantScalar != PT_TRI_NO;
		return true;
	}

	/* (new ConstantIntegerType(0))->isSuperTypeOf($type); false = pending
	 * exception */
	[[nodiscard]] static bool zeroIsSuperTypeOf(zval *type, zend_long &out)
	{
		zv::Val zero = pt_type_new_constant_integer(0);
		if (UNEXPECTED(zero.isUndef())) return false;
		out = pt_type_call_result_trinary(Z_OBJ_P(zero.raw()), PT_LC("issupertypeof"), 1, type);
		return out >= 0;
	}

	/* the head every TypeTraverser::map() callback of the twin shares:
	 * `if ($type instanceof UnionType) return $traverse($type);` —
	 * true with return_value set (or an exception pending) when handled */
	static bool traverseUnion(uint32_t argc, zval *argv, zval *return_value, bool &handled)
	{
		if (UNEXPECTED(argc < 2 || Z_TYPE_P(&argv[0]) != IS_OBJECT)) {
			zend_wrong_parameters_count_error(2, 2);
			handled = true;
			return false;
		}
		bool isUnion;
		if (UNEXPECTED(!isInstance(&argv[0], PT_CLASS_UNION_TYPE, isUnion))) {
			handled = true;
			return false;
		}
		if (!isUnion) {
			handled = false;
			return true;
		}
		handled = true;
		zv::Val traversed = pt_type_call_callable(&argv[1], 1, &argv[0]);
		if (UNEXPECTED(traversed.isUndef())) return false;
		traversed.intoReturnValue(return_value);
		return true;
	}

	/* setOffsetValueType()'s callback: collects the integer types of the
	 * key type into the holder's first state slot (`use (&$integerTypes)`),
	 * returning $type */
	static void collectIntegerTypesCallback(zval *integerTypes, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		bool handled;
		if (!traverseUnion(argc, argv, return_value, handled) || handled) return;
		zend_long isInteger = pt_type_call_trinary(Z_OBJ_P(&argv[0]), PT_LC("isinteger"), 0, NULL);
		if (UNEXPECTED(isInteger < 0)) return;
		if (isInteger == PT_TRI_YES) {
			if (Z_TYPE_P(integerTypes) != IS_ARRAY) {
				ZVAL_EMPTY_ARRAY(integerTypes);
			}
			zv::ArrRef(integerTypes).push(zv::Ref(&argv[0]));
		}
		ZVAL_COPY(return_value, &argv[0]);
	}

	/* TypeTraverser::map($keyType, collect) → the collected integer types
	 * (an array); UNDEF = pending exception */
	static zv::Val collectIntegerTypes(zval *keyType)
	{
		zv::Arr empty = zv::Arr::empty();
		zv::Val callback = pt_type_native_callback(collectIntegerTypesCallback, empty.raw(), NULL);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		zval args[2];
		ZVAL_COPY_VALUE(&args[0], keyType);
		ZVAL_COPY_VALUE(&args[1], callback.raw());
		zv::Val mapped = pt_type_call_static(PT_CLASS_TYPE_TRAVERSER, PT_LC("map"), 2, args);
		if (UNEXPECTED(mapped.isUndef())) return zv::Val();
		zval *collected = pt_type_native_callback_state(callback.raw(), 0);
		if (Z_TYPE_P(collected) != IS_ARRAY) return zv::Val(zv::Arr::empty());
		return zv::Val::copyOf(zv::Ref(collected));
	}

	/* spliceArray()'s callback: an integer type becomes int<0, max>, any
	 * other leaf stays */
	static void renumberIntegerKeysCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state0;
		(void) state1;
		bool handled;
		if (!traverseUnion(argc, argv, return_value, handled) || handled) return;
		zend_long isInteger = pt_type_call_trinary(Z_OBJ_P(&argv[0]), PT_LC("isinteger"), 0, NULL);
		if (UNEXPECTED(isInteger < 0)) return;
		if (isInteger == PT_TRI_YES) {
			zv::Val range = nonNegativeIntegers();
			if (UNEXPECTED(range.isUndef())) return;
			range.intoReturnValue(return_value);
			return;
		}
		ZVAL_COPY(return_value, &argv[0]);
	}

	static zv::Val renumberIntegerKeys(zval *keyType)
	{
		zv::Val callback = pt_type_native_callback(renumberIntegerKeysCallback, NULL, NULL);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		zval args[2];
		ZVAL_COPY_VALUE(&args[0], keyType);
		ZVAL_COPY_VALUE(&args[1], callback.raw());
		return pt_type_call_static(PT_CLASS_TYPE_TRAVERSER, PT_LC("map"), 2, args);
	}

	/* private static foldConstantStringKeyCase(ConstantStringType $type, ?int $case):
	 * the lower- or upper-cased constant, or the union of both for an
	 * unknown case; UNDEF = pending exception */
	static zv::Val foldConstantStringKeyCase(zval *type, NullableLong caseArg)
	{
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(type), pt_ce_constant_string_type))) {
			zend_type_error("%s::foldConstantStringKeyCase(): Argument #1 ($type) must be of type %s, %s given", ZSTR_VAL(pt_ce_array_type->name), ZSTR_VAL(pt_ce_constant_string_type->name), zend_zval_value_name(type));
			return zv::Val();
		}
		zv::Val value = pt_constant_string_get_value(Z_OBJ_P(type));
		if (UNEXPECTED(value.isUndef())) return zv::Val();
		zend_string *v = zv::Ref(value.raw()).asString();
		if (!caseArg.isNull && caseArg.value == 0) { /* CASE_LOWER */
			return constantStringOf(zend_string_tolower(v));
		}
		if (!caseArg.isNull && caseArg.value == 1) { /* CASE_UPPER */
			return constantStringOf(zend_string_toupper(v));
		}
		zv::Val lower = constantStringOf(zend_string_tolower(v));
		zv::Val upper = constantStringOf(zend_string_toupper(v));
		if (UNEXPECTED(lower.isUndef() || upper.isUndef())) return zv::Val();
		return combinator2(PT_LC("union"), lower.raw(), upper.raw());
	}

	/* new ConstantStringType($owned) */
	static zv::Val constantStringOf(zend_string *owned)
	{
		zval result;
		bool created = pt_constant_string_type_new(&result, owned);
		zend_string_release(owned);
		if (UNEXPECTED(!created)) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* changeKeyCaseArray()'s callback (the case in the holder's first state
	 * slot): constant strings folded, a string type re-accessorized, any
	 * other leaf kept */
	static void changeKeyCaseCallback(zval *caseZv, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		bool handled;
		if (!traverseUnion(argc, argv, return_value, handled) || handled) return;
		NullableLong caseArg = NullableLong::from(caseZv);
		zval *type = &argv[0];
		zv::Val constantStrings = pt_type_call_array(Z_OBJ_P(type), PT_LC("getconstantstrings"), 0, NULL);
		if (UNEXPECTED(constantStrings.isUndef())) return;
		uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(constantStrings.raw()));
		if (count > 0) {
			zv::Arr folded = zv::Arr::create(count);
			for (zv::ArrayEntry entry : zv::ArrRef(constantStrings.raw())) {
				zv::Val one = foldConstantStringKeyCase(entry.value().deref().raw(), caseArg);
				if (UNEXPECTED(one.isUndef())) return;
				folded.push(std::move(one));
			}
			zv::Val unionType = pt_type_call_static_spread(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), folded.table());
			if (UNEXPECTED(unionType.isUndef())) return;
			unionType.intoReturnValue(return_value);
			return;
		}

		zend_long isString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isstring"), 0, NULL);
		if (UNEXPECTED(isString < 0)) return;
		if (isString == PT_TRI_YES) {
			zv::Val string = stringType();
			if (UNEXPECTED(string.isUndef())) return;
			zv::Arr types = zv::Arr::create(4);
			types.push(std::move(string));
			zend_long nonFalsy = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnonfalsystring"), 0, NULL);
			if (UNEXPECTED(nonFalsy < 0)) return;
			if (nonFalsy == PT_TRI_YES) {
				if (UNEXPECTED(!pushNew(types, pt_accessory_non_falsy_string_type_new))) return;
			} else {
				zend_long nonEmpty = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnonemptystring"), 0, NULL);
				if (UNEXPECTED(nonEmpty < 0)) return;
				if (nonEmpty == PT_TRI_YES && UNEXPECTED(!pushNew(types, pt_accessory_non_empty_string_type_new))) return;
			}
			zend_long numeric = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnumericstring"), 0, NULL);
			if (UNEXPECTED(numeric < 0)) return;
			if (numeric == PT_TRI_YES && UNEXPECTED(!pushNew(types, pt_accessory_numeric_string_type_new))) return;
			if (!caseArg.isNull && caseArg.value == 0) { /* CASE_LOWER */
				if (UNEXPECTED(!pushNew(types, pt_accessory_lowercase_string_type_new))) return;
			} else if (!caseArg.isNull && caseArg.value == 1) { /* CASE_UPPER */
				if (UNEXPECTED(!pushNew(types, pt_accessory_uppercase_string_type_new))) return;
			}
			if (types.arrRef().size() == 1) {
				zv::Ref only = types.arrRef().findIndex(0);
				ZVAL_COPY(return_value, only.raw());
				return;
			}
			zv::Val result = intersection(std::move(types));
			if (UNEXPECTED(result.isUndef())) return;
			result.intoReturnValue(return_value);
			return;
		}

		ZVAL_COPY(return_value, type);
	}

	/* $types[] = new Class(); false = pending exception */
	/* new <Shadowed>() through its exported constructor */
	[[nodiscard]] static bool pushNew(zv::Arr &types, bool (*construct)(zval *))
	{
		zval raw;
		if (UNEXPECTED(!construct(&raw))) return false;
		types.push(zv::Val::adopt(raw));
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ArrayType;
using phpstanturbo::NullableLong;

bool pt_array_type_new(zval *out, zval *keyType, zval *itemType)
{
	if (UNEXPECTED(!ArrayType::checkType(keyType, "__construct", 1, "keyType") || !ArrayType::checkType(itemType, "__construct", 2, "itemType"))) return false;
	return pt_val_into(ArrayType::create(keyType, itemType), out);
}

zv::Val pt_array_type_get_key_type(zend_object *object)
{
	if (EXPECTED(object->ce == pt_ce_array_type)) return ArrayType(object).getKeyType();
	return pt_type_call(object, PT_LC("getkeytype"), 0, NULL);
}

zv::Val pt_array_type_get_item_type(zend_object *object)
{
	if (EXPECTED(object->ce == pt_ce_array_type)) return ArrayType(object).getItemType();
	return pt_type_call(object, PT_LC("getitemtype"), 0, NULL);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ArrayType(Z_OBJ_P(ZEND_THIS))

/* the twin's `self` return type (withTypes(), generalizeValues()) */

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL atEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL atNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL atMaybe0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

static void ZEND_FASTCALL atThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL atThis1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

/* $this->getIterableKeyType() — through the object's class */
static void ZEND_FASTCALL atThisGetIterableKeyType0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getFirstIterableKeyType());
}

/* $this->getItemType() — through the object's class */
static void ZEND_FASTCALL atThisGetItemType0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getIterableValueType());
}

/* (Type $offsetType, Type $valueType) → a Type */
static void pt_at_two_types(INTERNAL_FUNCTION_PARAMETERS, zv::Val (ArrayType::*method)(zval *, zval *) const)
{
	zval *a, *b;
	if (!zp::parse<zp::Obj, zp::Obj>(execute_data, a, b)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(a, b));
}

/* (Type $type) → a Type */
static void pt_at_one_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (ArrayType::*method)(zval *) const)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(type));
}

void pt_register_array_type()
{
	reg::Class cls("PHPStan\\Type\\ArrayType");
	ptdecl::ArrayType::declareClass(cls);
	/* the slots PT_AT_PROP_*: the three class-body properties first, the
	 * promoted $itemType after them */
	cls.privateTypedClassProperty("keyType", ptcls::type, false);
	cls.privateTypedClassPropertyDefaultNull("cachedIterableKeyType", ptcls::type);
	cls.privateTypedClassPropertyDefaultNull("isList", ptcls::trinaryLogic);
	cls.privateTypedClassProperty("itemType", ptcls::type, false);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *keyType, *itemType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, keyType, itemType)) RETURN_THROWS();
		if (UNEXPECTED(!ArrayType::checkType(keyType, "__construct", 1, "keyType") || !ArrayType::checkType(itemType, "__construct", 2, "itemType"))) {
			RETURN_THROWS();
		}
		if (UNEXPECTED(!PT_THIS.construct(keyType, itemType))) RETURN_THROWS();
	});

	cls.method<&ArrayType::getKeyType>(sigs::getKeyType);

	cls.method<&ArrayType::getItemType>(sigs::getItemType);

	cls.method(sigs::withTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *keyType, *itemType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, keyType, itemType)) RETURN_THROWS();
		if (UNEXPECTED(!ArrayType::checkType(keyType, "withTypes", 1, "keyType") || !ArrayType::checkType(itemType, "withTypes", 2, "itemType"))) {
			RETURN_THROWS();
		}
		PT_RETURN_VAL(ArrayType::withTypes(keyType, itemType));
	});

	cls.method<&ArrayType::getReferencedClasses>(sigs::getReferencedClasses);

	cls.method(sigs::getConstantArrays, atEmptyArray0);

	cls.method<&ArrayType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.method<&ArrayType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method<&ArrayType::equals, zp::Obj>(sigs::equals);

	cls.method<&ArrayType::describe, zp::Obj>(sigs::describe);

	cls.method<&ArrayType::generalizeValues>(sigs::generalizeValues);

	cls.method(sigs::getKeysArrayFiltered, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(PT_THIS.getKeysArrayFiltered());
	});

	cls.method<&ArrayType::getKeysArray>(sigs::getKeysArray);

	cls.method<&ArrayType::getValuesArray>(sigs::getValuesArray);

	cls.method(sigs::isIterableAtLeastOnce, atMaybe0);

	cls.method<&ArrayType::getArraySize>(sigs::getArraySize);

	cls.method<&ArrayType::getIterableKeyType>(sigs::getIterableKeyType);
	cls.method(sigs::getFirstIterableKeyType, atThisGetIterableKeyType0);
	cls.method(sigs::getLastIterableKeyType, atThisGetIterableKeyType0);
	cls.method(sigs::getIterableValueType, atThisGetItemType0);
	cls.method(sigs::getFirstIterableValueType, atThisGetItemType0);
	cls.method(sigs::getLastIterableValueType, atThisGetItemType0);

	cls.method(sigs::isConstantArray, atNo0);

	cls.method(sigs::isList, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isList());
	});

	cls.method(sigs::isConstantValue, atNo0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(ArrayType::looseCompare(type));
	});

	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.hasOffsetValueType(offsetType));
	});

	cls.method(sigs::getOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_at_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ArrayType::getOffsetValueType);
	});

	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		bool unionValues = true;
		if (!zp::parse<zp::ObjOrNull, zp::Obj, zp::Opt<zp::Bool>>(execute_data, offsetType, valueType, unionValues)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.setOffsetValueType(offsetType, valueType, unionValues));
	});

	cls.method(sigs::setExistingOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_at_two_types(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ArrayType::setExistingOffsetValueType);
	});

	cls.method(sigs::unsetOffset, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_at_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ArrayType::unsetOffset);
	});

	cls.method(sigs::fillKeysArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_at_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ArrayType::fillKeysArray);
	});

	cls.method<&ArrayType::flipArray>(sigs::flipArray);

	cls.method(sigs::intersectKeyArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_at_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ArrayType::intersectKeyArray);
	});

	cls.method(sigs::popArray, atThis0);
	cls.method(sigs::reverseArray, atThis1);

	cls.method(sigs::searchArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *needleType, *strict = NULL;
		if (!zp::parse<zp::Obj, zp::Opt<zp::ObjOrNull>>(execute_data, needleType, strict)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.searchArray(needleType, strict));
	});

	cls.method(sigs::shiftArray, atThis0);

	cls.method<&ArrayType::shuffleArray>(sigs::shuffleArray);

	cls.method<&ArrayType::sliceArray, zp::Obj, zp::Obj, zp::Obj>(sigs::sliceArray);

	cls.method<&ArrayType::spliceArray, zp::Obj, zp::Obj, zp::Obj>(sigs::spliceArray);

	cls.method(sigs::makeListMaybe, atThis0);

	cls.method(sigs::truncateListToSize, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_at_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ArrayType::truncateListToSize);
	});

	cls.method(sigs::mapValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.mapValueType(&fci, &fcc));
	});

	cls.method(sigs::mapKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.mapKeyType(&fci, &fcc));
	});

	cls.method(sigs::makeAllArrayKeysOptional, atThis0);

	cls.method(sigs::changeKeyCaseArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long caseValue = 0;
		bool caseIsNull = false;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_LONG_OR_NULL(caseValue, caseIsNull)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.changeKeyCaseArray(caseIsNull ? NullableLong::null() : NullableLong::of(caseValue)));
	});

	cls.method<&ArrayType::filterArrayRemovingFalsey>(sigs::filterArrayRemovingFalsey);

	cls.method(sigs::isCallable, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isCallable());
	});

	cls.method(sigs::getCallableParametersAcceptors, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.getCallableParametersAcceptors());
	});

	cls.method<&ArrayType::toInteger>(sigs::toInteger);

	cls.method<&ArrayType::toFloat>(sigs::toFloat);

	cls.method<&ArrayType::inferTemplateTypes, zp::Obj>(sigs::inferTemplateTypes);

	cls.method<&ArrayType::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});

	cls.method<&ArrayType::toPhpDocNode>(sigs::toPhpDocNode);

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
		pt_at_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ArrayType::tryRemove);
	});

	cls.method(sigs::getFiniteTypes, atEmptyArray0);

	cls.method<&ArrayType::hasTemplateOrLateResolvableType>(sigs::hasTemplateOrLateResolvableType);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares (isCallable, getCallableParametersAcceptors,
	 * ...) */
	ptdecl::ArrayType::registerTraits(cls);

	cls.shadow(&pt_ce_array_type);
}

/* }}} */
