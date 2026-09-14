/*
 * PHPStanTurbo\IterableType — native implementation of PHPStan\Type\IterableType.
 *
 * Declared as PHPStan\Type\IterableType itself at activation: not final
 * (the PHP TemplateIterableType extends it — its constructor calls
 * parent::__construct(), so the constructor is a proper method),
 * implementing PHPStan\Type\CompoundType. State is the twin's two promoted
 * `private Type $keyType` / `private Type $itemType`, declared typed
 * property slots in the twin's declaration order, so the std object
 * handlers do GC/clone and a subclass's own properties follow them.
 *
 * Every `$this->method()` the twin makes goes through the object's class
 * entry — a subclass may have overridden it (TemplateIterableType's
 * isSuperTypeOf(), traverse(), tryRemove(), ...) — with a direct C++ call
 * when the object's method is the native one. The private
 * isNestedTypeSuperTypeOf() is a direct C++ call, as PHP never dispatches
 * it. The private slots of another IterableType (`$type->keyType`,
 * `$type->itemType`) are read directly, as the twin does from inside the
 * class.
 */

#include "TypeTraits.h"
#include "generated/IterableType.h"

namespace slots = ptdecl::IterableType::slot;
namespace sigs = ptdecl::IterableType::sig;

zend_class_entry *pt_ce_iterable_type = nullptr;

/* the handlers a $this-call is checked against before the direct path */
static void ZEND_FASTCALL itGetItemType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL itGetIterableKeyType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL itGetIterableValueType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL itToArrayOrTraversable(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL itIsSubTypeOf(INTERNAL_FUNCTION_PARAMETERS);

namespace phpstanturbo {

/* Mirrors PHPStan\Type\IterableType. State lives in the PHP object's slots. */
class IterableType
{
public:
	explicit IterableType(zend_object *self) : self(self) {}

	/* __construct(private Type $keyType, private Type $itemType); both
	 * borrowed */
	void construct(zval *keyType, zval *itemType)
	{
		writeSlot(slots::keyType, keyType);
		writeSlot(slots::itemType, itemType);
	}

	/* new self($keyType, $itemType) — exactly the class, as the twin's
	 * `new self` sites spell it; UNDEF = pending exception */
	static zv::Val create(zval *keyType, zval *itemType)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_iterable_type) != SUCCESS)) return zv::Val();
		IterableType(Z_OBJ(object)).construct(keyType, itemType);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

	/* the slots (borrowed); NULL with an Error pending when the constructor
	 * never ran */
	[[nodiscard]] zval *keyType() const { return slot(self, slots::keyType, "keyType"); }
	zval *itemType() const { return slot(self, slots::itemType, "itemType"); }

	static zval *slot(zend_object *object, uint32_t index, const char *name) { return pt_typed_slot(object, index, pt_ce_iterable_type, name); }

	zv::Val getKeyType() const
	{
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(k));
	}

	zv::Val getItemType() const
	{
		zval *i = itemType();
		if (UNEXPECTED(i == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(i));
	}

	/* array_merge($this->keyType->getReferencedClasses(), $this->getItemType()->getReferencedClasses()) */
	zv::Val getReferencedClasses() const
	{
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zv::Val keyClasses = callArray(k, PT_LC("getreferencedclasses"), 0, NULL);
		if (UNEXPECTED(keyClasses.isUndef())) return zv::Val();
		zv::Val item = thisItemType();
		if (UNEXPECTED(item.isUndef())) return zv::Val();
		zv::Arr classes = zv::Arr::adoptVal(std::move(keyClasses));
		zv::Val itemClasses = callArray(item.raw(), PT_LC("getreferencedclasses"), 0, NULL);
		if (UNEXPECTED(itemClasses.isUndef() || !pt_callable_array_merge_into(classes, itemClasses.raw()))) return zv::Val();
		return zv::Val(std::move(classes));
	}

	/* yes for an empty constant array; for an iterable the value and key
	 * types' answers combined; the CompoundType callback; no otherwise;
	 * UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool emptyConstantArray;
		if (UNEXPECTED(!isEmptyConstantArray(type, emptyConstantArray))) return zv::Val();
		if (emptyConstantArray) return pt_type_accepts_result(PT_TRI_YES);
		zend_long iterable = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isiterable"), 0, NULL);
		if (UNEXPECTED(iterable < 0)) return zv::Val();
		if (iterable == PT_TRI_YES) {
			zv::Val ownValue = thisIterableValueType();
			if (UNEXPECTED(ownValue.isUndef())) return zv::Val();
			zv::Val theirValue = pt_type_op(Z_OBJ_P(type), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
			if (UNEXPECTED(theirValue.isUndef())) return zv::Val();
			zv::Args args{theirValue.raw(), strictTypes};
			zv::Val result = callObject(ownValue.raw(), PT_LC("accepts"), 2, args);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zv::Val ownKey = thisIterableKeyType();
			if (UNEXPECTED(ownKey.isUndef())) return zv::Val();
			zv::Val theirKey = pt_type_op(Z_OBJ_P(type), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
			if (UNEXPECTED(theirKey.isUndef())) return zv::Val();
			ZVAL_COPY_VALUE(&args[0], theirKey.raw());
			zv::Val keyResult = callObject(ownKey.raw(), PT_LC("accepts"), 2, args);
			if (UNEXPECTED(keyResult.isUndef())) return zv::Val();
			return pt_type_result_and(std::move(result), keyResult.raw());
		}
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		return pt_type_accepts_result(PT_TRI_NO);
	}

	/* the CompoundType callback; else the type's iterability combined with
	 * the value and key types' answers; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);
		}
		return isSuperTypeOfNested(type, false);
	}

	/* the same with the nested mixed types compared by explicitness */
	zv::Val isSuperTypeOfMixed(zval *type) const { return isSuperTypeOfNested(type, true); }

	/* (new IsSuperTypeOfResult($type->isIterable(), []))
	 *   ->and(<value types>)->and(<key types>), the nested comparisons
	 * isSuperTypeOf() or isNestedTypeSuperTypeOf(); UNDEF = pending exception */
	zv::Val isSuperTypeOfNested(zval *type, bool nested) const
	{
		zv::Val iterable = pt_type_call(Z_OBJ_P(type), PT_LC("isiterable"), 0, NULL);
		if (UNEXPECTED(iterable.isUndef())) return zv::Val();
		zv::Val result = pt_callable_is_super_type_of_result_of(iterable.raw());
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zv::Val ownValue = thisIterableValueType();
		if (UNEXPECTED(ownValue.isUndef())) return zv::Val();
		zv::Val theirValue = pt_type_op(Z_OBJ_P(type), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
		if (UNEXPECTED(theirValue.isUndef())) return zv::Val();
		zv::Val valueResult = nested ? isNestedTypeSuperTypeOf(ownValue.raw(), theirValue.raw()) : callObject(ownValue.raw(), PT_LC("issupertypeof"), 1, theirValue.raw());
		if (UNEXPECTED(valueResult.isUndef())) return zv::Val();
		result = pt_type_op(Z_OBJ_P(result.raw()), PT_OP_AND, 1, valueResult.raw());
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zv::Val ownKey = thisIterableKeyType();
		if (UNEXPECTED(ownKey.isUndef())) return zv::Val();
		zv::Val theirKey = pt_type_op(Z_OBJ_P(type), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
		if (UNEXPECTED(theirKey.isUndef())) return zv::Val();
		zv::Val keyResult = nested ? isNestedTypeSuperTypeOf(ownKey.raw(), theirKey.raw()) : callObject(ownKey.raw(), PT_LC("issupertypeof"), 1, theirKey.raw());
		if (UNEXPECTED(keyResult.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: and() must return an object");
			return zv::Val();
		}
		return pt_type_op(Z_OBJ_P(result.raw()), PT_OP_AND, 1, keyResult.raw());
	}

	/* $a->isSuperTypeOf($b) unless both are plain MixedTypes: then yes
	 * except for an explicit $a over an implicit $b (maybe); UNDEF = pending
	 * exception */
	static zv::Val isNestedTypeSuperTypeOf(zval *a, zval *b)
	{
		bool aMixed, bMixed;
		if (UNEXPECTED(!pt_type_instanceof_ce(a, pt_ce_mixed_type, aMixed) || !pt_type_instanceof_ce(b, pt_ce_mixed_type, bMixed))) return zv::Val();
		if (!aMixed || !bMixed) return callObject(a, PT_LC("issupertypeof"), 1, b);
		bool aTemplate, bTemplate;
		if (UNEXPECTED(!pt_type_instanceof_ce(a, pt_ce_template_mixed_type, aTemplate) || !pt_type_instanceof_ce(b, pt_ce_template_mixed_type, bTemplate))) {
			return zv::Val();
		}
		if (aTemplate || bTemplate) return callObject(a, PT_LC("issupertypeof"), 1, b);
		zv::Val aExplicit = pt_type_call(Z_OBJ_P(a), PT_LC("isexplicitmixed"), 0, NULL);
		if (UNEXPECTED(aExplicit.isUndef())) return zv::Val();
		if (zend_is_true(aExplicit.raw())) {
			zv::Val bExplicit = pt_type_call(Z_OBJ_P(b), PT_LC("isexplicitmixed"), 0, NULL);
			if (UNEXPECTED(bExplicit.isUndef())) return zv::Val();
			if (zend_is_true(bExplicit.raw())) return pt_type_is_super_type_of_result(PT_TRI_YES);
			return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		}
		return pt_type_is_super_type_of_result(PT_TRI_YES);
	}

	/* for a union or intersection its answer over the array-or-Traversable
	 * form; else yes (another IterableType) or maybe, held to maybe for an
	 * empty constant array, combined with the other type's iterability and
	 * its value and key types over the own; UNDEF = pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		bool isIntersection, isUnion;
		if (UNEXPECTED(!pt_type_instanceof_ce(otherType, pt_ce_intersection_type, isIntersection) || !pt_type_instanceof_ce(otherType, pt_ce_union_type, isUnion))) {
			return zv::Val();
		}
		if (isIntersection || isUnion) {
			zv::Val arrayOrTraversable = thisToArrayOrTraversable();
			if (UNEXPECTED(arrayOrTraversable.isUndef())) return zv::Val();
			return pt_type_op(Z_OBJ_P(otherType), PT_OP_IS_SUPER_TYPE_OF, 1, arrayOrTraversable.raw());
		}
		zv::Val limit = pt_type_is_super_type_of_result(instanceof_function(Z_OBJCE_P(otherType), pt_ce_iterable_type) ? PT_TRI_YES : PT_TRI_MAYBE);
		if (UNEXPECTED(limit.isUndef())) return zv::Val();
		bool emptyConstantArray;
		if (UNEXPECTED(!isEmptyConstantArray(otherType, emptyConstantArray))) return zv::Val();
		if (emptyConstantArray) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		zv::Val iterable = pt_type_call(Z_OBJ_P(otherType), PT_LC("isiterable"), 0, NULL);
		if (UNEXPECTED(iterable.isUndef())) return zv::Val();
		zv::Val iterableResult = pt_callable_is_super_type_of_result_of(iterable.raw());
		if (UNEXPECTED(iterableResult.isUndef())) return zv::Val();
		zval *i = itemType();
		if (UNEXPECTED(i == NULL)) return zv::Val();
		zv::Val theirValue = pt_type_op(Z_OBJ_P(otherType), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
		if (UNEXPECTED(theirValue.isUndef())) return zv::Val();
		zv::Val valueResult = callObject(theirValue.raw(), PT_LC("issupertypeof"), 1, i);
		if (UNEXPECTED(valueResult.isUndef())) return zv::Val();
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zv::Val theirKey = pt_type_op(Z_OBJ_P(otherType), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
		if (UNEXPECTED(theirKey.isUndef())) return zv::Val();
		zv::Val keyResult = callObject(theirKey.raw(), PT_LC("issupertypeof"), 1, k);
		if (UNEXPECTED(keyResult.isUndef())) return zv::Val();
		zv::Args args{iterableResult.raw(), valueResult.raw(), keyResult.raw()};
		return pt_type_op(Z_OBJ_P(limit.raw()), PT_OP_AND, 3, args);
	}

	/* $this->isSubTypeOf($acceptingType)->toAcceptsResult() */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		zv::Val result = thisIsSubTypeOf(acceptingType);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		return callObject(result.raw(), PT_LC("toacceptsresult"), 0, NULL);
	}

	/* the same class (get_class($type) === static::class) with equal key and
	 * item types; false with an exception pending */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (Z_OBJCE_P(type) != self->ce) {
			out = false;
			return true;
		}
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return false;
		zval *theirKey = slot(Z_OBJ_P(type), slots::keyType, "keyType");
		if (UNEXPECTED(theirKey == NULL)) return false;
		zv::Val keysEqual = callAny(k, PT_LC("equals"), 1, theirKey);
		if (UNEXPECTED(keysEqual.isUndef())) return false;
		if (!zend_is_true(keysEqual.raw())) {
			out = false;
			return true;
		}
		zval *i = itemType();
		if (UNEXPECTED(i == NULL)) return false;
		zval *theirItem = slot(Z_OBJ_P(type), slots::itemType, "itemType");
		if (UNEXPECTED(theirItem == NULL)) return false;
		zv::Val itemsEqual = callAny(i, PT_LC("equals"), 1, theirItem);
		if (UNEXPECTED(itemsEqual.isUndef())) return false;
		out = zend_is_true(itemsEqual.raw());
		return true;
	}

	/* 'iterable' for mixed key and item types, 'iterable<item>' for a mixed
	 * key type alone, 'iterable<key, item>' otherwise; UNDEF = pending
	 * exception */
	zv::Val describe(zval *level) const
	{
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		bool isMixedKeyType;
		if (UNEXPECTED(!isPlainMixed(k, isMixedKeyType))) return zv::Val();
		zval *i = itemType();
		if (UNEXPECTED(i == NULL)) return zv::Val();
		bool isMixedItemType;
		if (UNEXPECTED(!isPlainMixed(i, isMixedItemType))) return zv::Val();
		if (isMixedKeyType) {
			if (isMixedItemType) return zv::Val::string("iterable", sizeof("iterable") - 1);
			zv::Val item = callString(i, PT_LC("describe"), 1, level);
			if (UNEXPECTED(item.isUndef())) return zv::Val();
			return zv::Val::adoptString(zend_strpprintf(0, "iterable<%s>", ZSTR_VAL(Z_STR_P(item.raw()))));
		}
		zv::Val key = callString(k, PT_LC("describe"), 1, level);
		if (UNEXPECTED(key.isUndef())) return zv::Val();
		zv::Val item = callString(i, PT_LC("describe"), 1, level);
		if (UNEXPECTED(item.isUndef())) return zv::Val();
		return zv::Val::adoptString(zend_strpprintf(0, "iterable<%s, %s>", ZSTR_VAL(Z_STR_P(key.raw())), ZSTR_VAL(Z_STR_P(item.raw()))));
	}

	/* no when the key type is no supertype of the offset, maybe otherwise;
	 * -1 = pending exception */
	[[nodiscard]] zend_long hasOffsetValueType(zval *offsetType) const
	{
		zv::Val ownKey = thisIterableKeyType();
		if (UNEXPECTED(ownKey.isUndef())) return -1;
		zv::Val result = callObject(ownKey.raw(), PT_LC("issupertypeof"), 1, offsetType);
		if (UNEXPECTED(result.isUndef())) return -1;
		zend_long value = pt_type_result_trinary(result.raw());
		if (UNEXPECTED(value < 0)) return -1;
		return value == PT_TRI_NO ? PT_TRI_NO : PT_TRI_MAYBE;
	}

	/* new ArrayType($this->keyType, $this->getItemType()) */
	zv::Val toArray() const
	{
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zv::Val item = thisItemType();
		if (UNEXPECTED(item.isUndef())) return zv::Val();
		return arrayOf(k, item.raw());
	}

	/* new UnionType([new ArrayType($k, $i), new GenericObjectType(Traversable::class, [$k, $i])]) */
	zv::Val toArrayOrTraversable() const
	{
		zval *k = keyType();
		zval *i = k != NULL ? itemType() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(i == NULL)) return zv::Val();
		zv::Val array = arrayOf(k, i);
		if (UNEXPECTED(array.isUndef())) return zv::Val();
		zv::Val traversable = genericTraversable(k, i);
		if (UNEXPECTED(traversable.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(array));
		types.push(std::move(traversable));
		return pt_type_new_union(std::move(types));
	}

	/* TypeCombinator::union($this, new ArrayType(TypeCombinator::intersect($this->keyType->toArrayKey(),
	 * new UnionType([new IntegerType(), new StringType()])), $this->itemType),
	 * new GenericObjectType(Traversable::class, [$this->keyType, $this->itemType])) */
	zv::Val toCoercedArgumentType() const
	{
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zv::Val arrayKey = pt_type_op(Z_OBJ_P(k), PT_OP_TO_ARRAY_KEY, 0, NULL);
		if (UNEXPECTED(arrayKey.isUndef())) return zv::Val();
		zval integerRaw, stringRaw;
		if (UNEXPECTED(!pt_integer_type_new(&integerRaw))) return zv::Val();
		zv::Val integer = zv::Val::adopt(integerRaw);
		if (UNEXPECTED(!pt_string_type_new(&stringRaw))) return zv::Val();
		zv::Val string = zv::Val::adopt(stringRaw);
		zv::Arr keyTypes = zv::Arr::create(2);
		keyTypes.push(std::move(integer));
		keyTypes.push(std::move(string));
		zv::Val keyUnion = pt_type_new_union(std::move(keyTypes));
		if (UNEXPECTED(keyUnion.isUndef())) return zv::Val();
		zv::Args intersectArgs{arrayKey.raw(), keyUnion.raw()};
		zv::Val coercedKey = pt_type_combinator_call(PT_LC("intersect"), 2, intersectArgs);
		if (UNEXPECTED(coercedKey.isUndef())) return zv::Val();
		zval *i = itemType();
		if (UNEXPECTED(i == NULL)) return zv::Val();
		zv::Val array = arrayOf(coercedKey.raw(), i);
		if (UNEXPECTED(array.isUndef())) return zv::Val();
		zv::Val traversable = genericTraversable(k, i);
		if (UNEXPECTED(traversable.isUndef())) return zv::Val();
		zv::Args args{self, array.raw(), traversable.raw()};
		return pt_type_combinator_call(PT_LC("union"), 3, args);
	}

	/* IntegerRangeType::fromInterval(0, null) */
	static zv::Val getArraySize()
	{
		return pt_integer_range_from_interval(NullableLong::of(0), NullableLong::null(), 0);
	}

	/* the union or intersection's inferTemplateTypesOn($this); nothing for
	 * a non-iterable or never; else the key type's inference over the
	 * received key type unioned with the value types'; UNDEF = pending
	 * exception */
	zv::Val inferTemplateTypes(zval *receivedType) const
	{
		bool isUnion, isIntersection;
		if (UNEXPECTED(!pt_type_instanceof_ce(receivedType, pt_ce_union_type, isUnion) || !pt_type_instanceof_ce(receivedType, pt_ce_intersection_type, isIntersection))) {
			return zv::Val();
		}
		if (isUnion || isIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(receivedType), PT_LC("infertemplatetypeson"), 1, &selfZv);
		}
		zend_long iterable = pt_type_call_trinary(Z_OBJ_P(receivedType), PT_LC("isiterable"), 0, NULL);
		if (UNEXPECTED(iterable < 0)) return zv::Val();
		if (iterable != PT_TRI_YES || instanceof_function(Z_OBJCE_P(receivedType), pt_ce_never_type)) return pt_callable_template_type_map_empty();
		zv::Val ownKey = thisIterableKeyType();
		if (UNEXPECTED(ownKey.isUndef())) return zv::Val();
		zv::Val theirKey = pt_type_op(Z_OBJ_P(receivedType), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
		if (UNEXPECTED(theirKey.isUndef())) return zv::Val();
		zv::Val keyTypeMap = callObject(ownKey.raw(), PT_LC("infertemplatetypes"), 1, theirKey.raw());
		if (UNEXPECTED(keyTypeMap.isUndef())) return zv::Val();
		zv::Val ownValue = thisIterableValueType();
		if (UNEXPECTED(ownValue.isUndef())) return zv::Val();
		zv::Val theirValue = pt_type_op(Z_OBJ_P(receivedType), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
		if (UNEXPECTED(theirValue.isUndef())) return zv::Val();
		zv::Val valueTypeMap = callObject(ownValue.raw(), PT_LC("infertemplatetypes"), 1, theirValue.raw());
		if (UNEXPECTED(valueTypeMap.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(keyTypeMap.raw()), PT_LC("union"), 1, valueTypeMap.raw());
	}

	/* array_merge($this->getIterableKeyType()->getReferencedTemplateTypes($variance),
	 * $this->getIterableValueType()->getReferencedTemplateTypes($variance)),
	 * $variance the position composed with covariance */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const
	{
		zv::Val covariant = pt_type_template_type_variance(PT_TEMPLATE_TYPE_VARIANCE_COVARIANT);
		if (UNEXPECTED(covariant.isUndef())) return zv::Val();
		zv::Val variance = pt_type_call(Z_OBJ_P(positionVariance), PT_LC("compose"), 1, covariant.raw());
		if (UNEXPECTED(variance.isUndef())) return zv::Val();
		zv::Val ownKey = thisIterableKeyType();
		if (UNEXPECTED(ownKey.isUndef())) return zv::Val();
		zv::Val keyReferences = callArray(ownKey.raw(), PT_LC("getreferencedtemplatetypes"), 1, variance.raw());
		if (UNEXPECTED(keyReferences.isUndef())) return zv::Val();
		zv::Val ownValue = thisIterableValueType();
		if (UNEXPECTED(ownValue.isUndef())) return zv::Val();
		zv::Val valueReferences = callArray(ownValue.raw(), PT_LC("getreferencedtemplatetypes"), 1, variance.raw());
		if (UNEXPECTED(valueReferences.isUndef())) return zv::Val();
		zv::Arr references = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(keyReferences.raw())) + zend_hash_num_elements(Z_ARRVAL_P(valueReferences.raw())));
		if (UNEXPECTED(!pt_callable_array_merge_into(references, keyReferences.raw()) || !pt_callable_array_merge_into(references, valueReferences.raw()))) {
			return zv::Val();
		}
		return zv::Val(std::move(references));
	}

	/* new self($cb($this->keyType), $cb($this->itemType)) when the callback
	 * changed either, $this otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zval newKeyType;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, k, &newKeyType))) return zv::Val();
		zv::Val keyType = zv::Val::adopt(newKeyType);
		zval *i = itemType();
		if (UNEXPECTED(i == NULL)) return zv::Val();
		zval newItemType;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, i, &newItemType))) return zv::Val();
		return traversed(std::move(keyType), zv::Val::adopt(newItemType));
	}

	/* traverse() with $right's iterable key and value types as the
	 * callback's second arguments */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		zv::Val rightKey = pt_type_op(Z_OBJ_P(right), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
		if (UNEXPECTED(rightKey.isUndef())) return zv::Val();
		zv::Args args{k, rightKey.raw()};
		zval newKeyType;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, args, &newKeyType))) return zv::Val();
		zv::Val keyType = zv::Val::adopt(newKeyType);
		zval *i = itemType();
		if (UNEXPECTED(i == NULL)) return zv::Val();
		zv::Val rightValue = pt_type_op(Z_OBJ_P(right), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
		if (UNEXPECTED(rightValue.isUndef())) return zv::Val();
		ZVAL_COPY_VALUE(&args[0], i);
		ZVAL_COPY_VALUE(&args[1], rightValue.raw());
		zval newItemType;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, args, &newItemType))) return zv::Val();
		return traversed(std::move(keyType), zv::Val::adopt(newItemType));
	}

	/* the tail of traverse()/traverseSimultaneously(): `$keyType !== $this->keyType
	 * || $itemType !== $this->itemType` */
	zv::Val traversed(zv::Val keyType, zv::Val itemType) const
	{
		zval *k = this->keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		bool keySame = zv::Ref(keyType.raw()).isObject() && Z_OBJ_P(keyType.raw()) == Z_OBJ_P(k);
		if (!keySame) return create(keyType.raw(), itemType.raw());
		zval *i = this->itemType();
		if (UNEXPECTED(i == NULL)) return zv::Val();
		bool itemSame = zv::Ref(itemType.raw()).isObject() && Z_OBJ_P(itemType.raw()) == Z_OBJ_P(i);
		if (!itemSame) return create(keyType.raw(), itemType.raw());
		return thisValue();
	}

	/* the Traversable half when the removed type covers every array, the
	 * array half when it covers every Traversable, null otherwise; UNDEF =
	 * pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		zv::Val mixedKey = pt_type_new_mixed_type();
		if (UNEXPECTED(mixedKey.isUndef())) return zv::Val();
		zv::Val mixedItem = pt_type_new_mixed_type();
		if (UNEXPECTED(mixedItem.isUndef())) return zv::Val();
		zv::Val arrayType = arrayOf(mixedKey.raw(), mixedItem.raw());
		if (UNEXPECTED(arrayType.isUndef())) return zv::Val();
		zend_long coversArrays = isSuperTypeOfYes(typeToRemove, arrayType.raw());
		if (UNEXPECTED(coversArrays < 0)) return zv::Val();
		if (coversArrays == 1) {
			zv::Val ownKey = thisIterableKeyType();
			if (UNEXPECTED(ownKey.isUndef())) return zv::Val();
			zv::Val ownValue = thisIterableValueType();
			if (UNEXPECTED(ownValue.isUndef())) return zv::Val();
			return genericTraversable(ownKey.raw(), ownValue.raw());
		}
		zval traversableRaw;
		zend_string *traversableName = zend_string_init("Traversable", sizeof("Traversable") - 1, 0);
		bool created = pt_object_type_new(&traversableRaw, traversableName);
		zend_string_release(traversableName);
		if (UNEXPECTED(!created)) return zv::Val();
		zv::Val traversableType = zv::Val::adopt(traversableRaw);
		zend_long coversTraversables = isSuperTypeOfYes(typeToRemove, traversableType.raw());
		if (UNEXPECTED(coversTraversables < 0)) return zv::Val();
		if (coversTraversables == 1) {
			zv::Val ownKey = thisIterableKeyType();
			if (UNEXPECTED(ownKey.isUndef())) return zv::Val();
			zv::Val ownValue = thisIterableValueType();
			if (UNEXPECTED(ownValue.isUndef())) return zv::Val();
			return arrayOf(ownKey.raw(), ownValue.raw());
		}
		return zv::Val::null();
	}

	/* IdentifierTypeNode('iterable') for mixed key and item types,
	 * iterable<item> for a mixed key type alone, iterable<key, item>
	 * otherwise; UNDEF = pending exception */
	zv::Val toPhpDocNode() const
	{
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		bool isMixedKeyType;
		if (UNEXPECTED(!isPlainMixed(k, isMixedKeyType))) return zv::Val();
		zval *i = itemType();
		if (UNEXPECTED(i == NULL)) return zv::Val();
		bool isMixedItemType;
		if (UNEXPECTED(!isPlainMixed(i, isMixedItemType))) return zv::Val();
		if (isMixedKeyType && isMixedItemType) return pt_type_new_identifier_type_node(PT_LC("iterable"));
		zv::Val identifier = pt_type_new_identifier_type_node(PT_LC("iterable"));
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

	/* $this->keyType->hasTemplateOrLateResolvableType() || $this->itemType->...;
	 * false = pending exception */
	[[nodiscard]] bool hasTemplateOrLateResolvableType(bool &out) const
	{
		zval *k = keyType();
		if (UNEXPECTED(k == NULL)) return false;
		zv::Val key = pt_type_op(Z_OBJ_P(k), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
		if (UNEXPECTED(key.isUndef())) return false;
		if (zend_is_true(key.raw())) {
			out = true;
			return true;
		}
		zval *i = itemType();
		if (UNEXPECTED(i == NULL)) return false;
		return pt_type_op_bool(Z_OBJ_P(i), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL, out);
	}

	/* the $this-calls a subclass may answer differently, with the direct
	 * path when the method is IterableType's own; UNDEF = pending exception */
	zv::Val thisItemType() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getitemtype"), itGetItemType))) return getItemType();
		return pt_type_call(self, PT_LC("getitemtype"), 0, NULL);
	}

	zv::Val thisIterableKeyType() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getiterablekeytype"), itGetIterableKeyType))) return getKeyType();
		return pt_type_op(self, PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
	}

	zv::Val thisIterableValueType() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getiterablevaluetype"), itGetIterableValueType))) return thisItemType();
		return pt_type_op(self, PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
	}

	zv::Val thisToArrayOrTraversable() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("toarrayortraversable"), itToArrayOrTraversable))) return toArrayOrTraversable();
		return pt_type_call(self, PT_LC("toarrayortraversable"), 0, NULL);
	}

	zv::Val thisIsSubTypeOf(zval *otherType) const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("issubtypeof"), itIsSubTypeOf))) return isSubTypeOf(otherType);
		return pt_type_op(self, PT_OP_IS_SUB_TYPE_OF, 1, otherType);
	}

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }

	void writeSlot(uint32_t index, zval *value) { pt_write_slot(self, index, value); }

	/* $type->method(...$args) on a type slot; UNDEF = pending exception */
	static zv::Val callAny(zval *type, const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: expected an object, %s given", zend_zval_value_name(type));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(type), lcname, len, argc, argv);
	}

	/* $type->method(...$args) requiring an object / array / string result;
	 * UNDEF = pending exception */
	static zv::Val callObject(zval *type, const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: expected an object, %s given", zend_zval_value_name(type));
			return zv::Val();
		}
		zv::Val result = pt_type_call(Z_OBJ_P(type), lcname, len, argc, argv);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s() must return an object", lcname);
			return zv::Val();
		}
		return result;
	}

	static zv::Val callArray(zval *type, const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: expected an object, %s given", zend_zval_value_name(type));
			return zv::Val();
		}
		zv::Val result = pt_type_call(Z_OBJ_P(type), lcname, len, argc, argv);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isArray())) {
			zend_type_error("phpstan_turbo: %s() must return an array", lcname);
			return zv::Val();
		}
		return result;
	}

	static zv::Val callString(zval *type, const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		zv::Val result = pt_type_call(Z_OBJ_P(type), lcname, len, argc, argv);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isString())) {
			zend_type_error("phpstan_turbo: %s() must return a string", lcname);
			return zv::Val();
		}
		return result;
	}

	/* $type->isConstantArray()->yes() && $type->isIterableAtLeastOnce()->no();
	 * false = pending exception */
	[[nodiscard]] static bool isEmptyConstantArray(zval *type, bool &out)
	{
		zend_long constantArray = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
		if (UNEXPECTED(constantArray < 0)) return false;
		if (constantArray != PT_TRI_YES) {
			out = false;
			return true;
		}
		zend_long atLeastOnce = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
		if (UNEXPECTED(atLeastOnce < 0)) return false;
		out = atLeastOnce == PT_TRI_NO;
		return true;
	}

	/* $type instanceof MixedType && $type->describe(VerbosityLevel::precise()) === 'mixed';
	 * false = pending exception */
	[[nodiscard]] static bool isPlainMixed(zval *type, bool &out)
	{
		bool mixed;
		if (UNEXPECTED(!pt_type_instanceof_ce(type, pt_ce_mixed_type, mixed))) return false;
		if (!mixed) {
			out = false;
			return true;
		}
		zval description;
		if (UNEXPECTED(!pt_type_describe_precise(type, &description))) return false;
		out = zv::Ref(&description).stringEquals("mixed");
		zval_ptr_dtor(&description);
		return true;
	}

	/* $typeToRemove->isSuperTypeOf($probe)->yes(); -1 = pending exception */
	[[nodiscard]] static zend_long isSuperTypeOfYes(zval *typeToRemove, zval *probe)
	{
		zv::Val result = callObject(typeToRemove, PT_LC("issupertypeof"), 1, probe);
		if (UNEXPECTED(result.isUndef())) return -1;
		zend_long value = pt_type_result_trinary(result.raw());
		if (UNEXPECTED(value < 0)) return -1;
		return value == PT_TRI_YES ? 1 : 0;
	}

	/* new ArrayType($keyType, $itemType) (the shadowing class) */
	static zv::Val arrayOf(zval *keyType, zval *itemType)
	{
		zval raw;
		if (UNEXPECTED(!pt_array_type_new(&raw, keyType, itemType))) return zv::Val();
		return zv::Val::adopt(raw);
	}

	/* new GenericObjectType(Traversable::class, [$keyType, $itemType]) */
	static zv::Val genericTraversable(zval *keyType, zval *itemType)
	{
		zv::Arr types = zv::Arr::create(2);
		types.push(zv::Ref(keyType));
		types.push(zv::Ref(itemType));
		zend_string *traversable = zend_string_init("Traversable", sizeof("Traversable") - 1, 0);
		zval raw;
		bool created = pt_generic_object_type_new(&raw, traversable, types.raw(), NULL, NULL, NULL);
		zend_string_release(traversable);
		if (UNEXPECTED(!created)) return zv::Val();
		return zv::Val::adopt(raw);
	}
};

} // namespace phpstanturbo

using phpstanturbo::IterableType;

bool pt_iterable_type_new(zval *out, zval *keyType, zval *itemType)
{
	return pt_val_into(IterableType::create(keyType, itemType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS IterableType(Z_OBJ_P(ZEND_THIS))

static void ZEND_FASTCALL itGetKeyType(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getKeyType());
}

static void ZEND_FASTCALL itGetItemType(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getItemType());
}

static void ZEND_FASTCALL itGetIterableKeyType(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getKeyType());
}

/* getIterableValueType() & co.: $this->getItemType() — through the
 * object's class */
static void ZEND_FASTCALL itGetIterableValueType(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.thisItemType());
}

static void ZEND_FASTCALL itToArrayOrTraversable(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.toArrayOrTraversable());
}

static void ZEND_FASTCALL itIsSubTypeOf(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *otherType;
	if (!zp::parse<zp::Obj>(execute_data, otherType)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.isSubTypeOf(otherType));
}

static void ZEND_FASTCALL itIsSuperTypeOf(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.isSuperTypeOf(type));
}

static void ZEND_FASTCALL itIsSuperTypeOfMixed(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.isSuperTypeOfMixed(type));
}

static void ZEND_FASTCALL itTrinaryNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL itError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL itEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

void pt_register_iterable_type()
{

	reg::Class cls("PHPStan\\Type\\IterableType");
	ptdecl::IterableType::declareClass(cls);
	/* the slots must stay in this order (PT_IT_PROP_*) */
	ptdecl::IterableType::declareProperties(cls);

	cls.method<&IterableType::construct, zp::Obj, zp::Obj>(sigs::__construct);

	cls.method(sigs::getKeyType, itGetKeyType);
	cls.method(sigs::getItemType, itGetItemType);

	cls.method<&IterableType::getReferencedClasses>(sigs::getReferencedClasses);
	cls.method(sigs::getObjectClassNames, itEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassReflections, itEmptyArray0);
	cls.method(sigs::getConstantStrings, itEmptyArray0);

	cls.method<&IterableType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return IterableType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method(sigs::isSuperTypeOf, itIsSuperTypeOf);
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &IterableType::isSuperTypeOf>();
	cls.method(sigs::isSuperTypeOfMixed, itIsSuperTypeOfMixed);
	cls.method(sigs::isSubTypeOf, itIsSubTypeOf);
	cls.op<PT_OP_IS_SUB_TYPE_OF, &IterableType::isSubTypeOf>();

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method<&IterableType::equals, zp::Obj>(sigs::equals);
	cls.op<PT_OP_EQUALS, &IterableType::equals>();

	cls.method<&IterableType::describe, zp::Obj>(sigs::describe);
	cls.op<PT_OP_DESCRIBE, &IterableType::describe>();

	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		zend_long value = PT_THIS.hasOffsetValueType(offsetType);
		if (UNEXPECTED(value < 0)) RETURN_THROWS();
		PT_RETURN_TRINARY(value);
	});

	cls.method(sigs::toNumber, itError0);
	cls.method(sigs::toBitwiseNotType, itError0);
	cls.method(sigs::toAbsoluteNumber, itError0);
	cls.method(sigs::toString, itError0);
	cls.method(sigs::toInteger, itError0);
	cls.method(sigs::toFloat, itError0);

	cls.method<&IterableType::toArray>(sigs::toArray);
	cls.method(sigs::toArrayOrTraversable, itToArrayOrTraversable);
	cls.method(sigs::toArrayKey, itError0);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return pt_type_new_error_type(); });

	cls.method(sigs::toCoercedArgumentType, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool strictTypes;
		if (!zp::parse<zp::Bool>(execute_data, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.toCoercedArgumentType());
	});

	cls.method(sigs::isIterable, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY(PT_TRI_YES);
	});
	cls.method(sigs::isIterableAtLeastOnce, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY(PT_TRI_MAYBE);
	});
	cls.op(PT_OP_IS_ITERABLE_AT_LEAST_ONCE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.method<&IterableType::getArraySize>(sigs::getArraySize);
	cls.method(sigs::getIterableKeyType, itGetIterableKeyType);
	cls.op<PT_OP_GET_ITERABLE_KEY_TYPE, &IterableType::getKeyType>();
	cls.method(sigs::getFirstIterableKeyType, itGetIterableKeyType);
	cls.method(sigs::getLastIterableKeyType, itGetIterableKeyType);
	cls.method(sigs::getIterableValueType, itGetIterableValueType);
	cls.op<PT_OP_GET_ITERABLE_VALUE_TYPE, &IterableType::thisItemType>();
	cls.method(sigs::getFirstIterableValueType, itGetIterableValueType);
	cls.method(sigs::getLastIterableValueType, itGetIterableValueType);

	cls.method(sigs::isNull, itTrinaryNo0);
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isConstantValue, itTrinaryNo0);
	cls.method(sigs::isConstantScalarValue, itTrinaryNo0);
	cls.op(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getConstantScalarTypes, itEmptyArray0);
	cls.method(sigs::getConstantScalarValues, itEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::isTrue, itTrinaryNo0);
	cls.method(sigs::isFalse, itTrinaryNo0);
	cls.method(sigs::isBoolean, itTrinaryNo0);
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isFloat, itTrinaryNo0);
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isInteger, itTrinaryNo0);
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isString, itTrinaryNo0);
	cls.op(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isNumericString, itTrinaryNo0);
	cls.method(sigs::isDecimalIntegerString, itTrinaryNo0);
	cls.method(sigs::isNonEmptyString, itTrinaryNo0);
	cls.method(sigs::isNonFalsyString, itTrinaryNo0);
	cls.method(sigs::isLiteralString, itTrinaryNo0);
	cls.method(sigs::isLowercaseString, itTrinaryNo0);
	cls.method(sigs::isClassString, itTrinaryNo0);
	cls.method(sigs::isUppercaseString, itTrinaryNo0);
	cls.method(sigs::getClassStringObjectType, itError0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(pt_type_new_object_without_class_type());
	});
	cls.method(sigs::isVoid, itTrinaryNo0);
	cls.op(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isScalar, itTrinaryNo0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* new BooleanType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::getEnumCases, itEmptyArray0);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});

	cls.method<&IterableType::inferTemplateTypes, zp::Obj>(sigs::inferTemplateTypes);

	cls.method<&IterableType::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);
	cls.op<PT_OP_GET_REFERENCED_TEMPLATE_TYPES, &IterableType::getReferencedTemplateTypes>();

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_with<IterableType>(self, argv); });

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

	cls.method<&IterableType::tryRemove, zp::Obj>(sigs::tryRemove);

	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(pt_type_new_error_type());
	});
	cls.method(sigs::getFiniteTypes, itEmptyArray0);

	cls.method<&IterableType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method<&IterableType::hasTemplateOrLateResolvableType>(sigs::hasTemplateOrLateResolvableType);
	cls.op<PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, &IterableType::hasTemplateOrLateResolvableType>();

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::IterableType::registerTraits(cls);

	cls.shadow(&pt_ce_iterable_type);
}

/* }}} */
