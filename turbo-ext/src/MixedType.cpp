/*
 * PHPStanTurbo\MixedType — native implementation of PHPStan\Type\MixedType.
 *
 * Declared as PHPStan\Type\MixedType itself at activation: not final (the
 * PHP ErrorType, TemplateMixedType and their subclasses extend it — their
 * constructors call parent::__construct(), so the constructor is a proper
 * method), implementing PHPStan\Type\CompoundType and
 * PHPStan\Type\SubtractableType. State is the twin's `private ?Type
 * $subtractedType` and the promoted `private bool $isExplicitMixed`,
 * declared typed property slots (IS_PROP_UNINIT until the constructor
 * writes them) in the twin's declaration order, so the std object handlers
 * do GC/clone and a PHP subclass's own properties follow them.
 *
 * Every `$this->method()` the twin makes goes through the object's class
 * entry — a subclass may have overridden it (ErrorType's describe(),
 * subtract() and getIterableValueType(), TemplateMixedType's
 * getClassStringType()) — with a direct C++ call when the object is exactly
 * a MixedType. The private slots of another MixedType
 * (`$type->subtractedType`) are read directly, as the twin does from inside
 * the class.
 *
 * The `static fn (Type $type): Type => $type` callbacks the prototype
 * reflections take are Closures over the static identity() of the internal
 * PHPStanTurbo\IdentityCallback class (an internal detail with no PHP twin,
 * like the generalize() holder in TypeTraits.cpp).
 */

#include "TypeTraits.h"
#include "generated/MixedType.h"

namespace slots = ptdecl::MixedType::slot;
namespace sigs = ptdecl::MixedType::sig;

#pragma GCC diagnostic push
#pragma GCC diagnostic ignored "-Wpragmas"
#pragma GCC diagnostic ignored "-Wunknown-warning-option"
#pragma GCC diagnostic ignored "-Wunused-parameter"
#pragma GCC diagnostic ignored "-Wignored-qualifiers"
#pragma GCC diagnostic ignored "-Wdeprecated-declarations"
#pragma GCC diagnostic ignored "-Wattributes"
#include "zend_closures.h" /* zend_create_closure */
#pragma GCC diagnostic pop

zend_class_entry *pt_ce_mixed_type = nullptr;

/* the identity callback class and its identity(), the function the
 * closures are created over */
static zend_class_entry *pt_ce_identity_callback = nullptr;
static zend_function *pt_identity_callback_fn = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\MixedType. State lives in the PHP object's
 * $subtractedType and $isExplicitMixed. */
class MixedType
{
public:
	explicit MixedType(zend_object *self) : self(self) {}

	/* __construct(private bool $isExplicitMixed = false, ?Type
	 * $subtractedType = null): a NeverType subtracted type is dropped;
	 * $subtractedType borrowed, NULL for null */
	void construct(bool isExplicitMixed, zval *subtractedType)
	{
		if (subtractedType != NULL && instanceof_function(Z_OBJCE_P(subtractedType), pt_ce_never_type)) {
			subtractedType = NULL;
		}
		zval *subtractedSlot = OBJ_PROP_NUM(self, slots::subtractedType);
		zval *explicitSlot = OBJ_PROP_NUM(self, slots::isExplicitMixed);
		/* the slot is overwritten in place: a repeated parent::__construct()
		 * call from a subclass would otherwise leak the first value */
		zval previous;
		ZVAL_COPY_VALUE(&previous, subtractedSlot);
		if (subtractedType == NULL) {
			ZVAL_NULL(subtractedSlot);
		} else {
			ZVAL_COPY(subtractedSlot, subtractedType);
		}
		ZVAL_BOOL(explicitSlot, isExplicitMixed);
		Z_PROP_FLAG_P(subtractedSlot) = 0; /* no longer IS_PROP_UNINIT */
		Z_PROP_FLAG_P(explicitSlot) = 0;
		if (Z_TYPE(previous) != IS_UNDEF) {
			zval_ptr_dtor(&previous);
		}
	}

	/* new self($isExplicitMixed, $subtractedType) — exactly the class, as
	 * the twin's `new self` / `new MixedType` sites spell it; UNDEF =
	 * pending exception */
	static zv::Val create(bool isExplicitMixed = false, zval *subtractedType = NULL)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_mixed_type) != SUCCESS)) return zv::Val();
		MixedType(Z_OBJ(object)).construct(isExplicitMixed, subtractedType);
		return zv::Val::adopt(object);
	}

	/* $this->subtractedType (borrowed, IS_NULL or IS_OBJECT); NULL with an
	 * Error pending when the constructor never ran
	 * (ReflectionClass::newInstanceWithoutConstructor()) — the twin's
	 * typed-property read raises the same */
	zval *subtractedType() const { return subtractedTypeOf(self); }

	/* $type->subtractedType of another MixedType instance */
	static zval *subtractedTypeOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::subtractedType);
		if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$subtractedType must not be accessed before initialization", ZSTR_VAL(pt_ce_mixed_type->name));
			return NULL;
		}
		return slot;
	}

	/* $this->isExplicitMixed; false with an Error pending when uninitialized */
	[[nodiscard]] bool isExplicitMixed(bool &out) const { return isExplicitMixedOf(self, out); }

	static bool isExplicitMixedOf(zend_object *object, bool &out)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::isExplicitMixed);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_TRUE && Z_TYPE_P(slot) != IS_FALSE)) {
			zend_throw_error(NULL, "Typed property %s::$isExplicitMixed must not be accessed before initialization", ZSTR_VAL(pt_ce_mixed_type->name));
			return false;
		}
		out = Z_TYPE_P(slot) == IS_TRUE;
		return true;
	}

	/* AcceptsResult::createYes() */
	static zv::Val accepts() { return pt_type_accepts_result(PT_TRI_YES); }

	/* yes/maybe by the explicitness and the subtracted types of the two
	 * mixed types; UNDEF = pending exception */
	zv::Val isSuperTypeOfMixed(zval *type) const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		bool explicitMixed = false;
		if (UNEXPECTED(!isExplicitMixed(explicitMixed))) return zv::Val();
		if (Z_TYPE_P(subtracted) == IS_NULL) return yesUnlessOnlyThisIsExplicit(explicitMixed, Z_OBJ_P(type));

		zval *typeSubtracted = subtractedTypeOf(Z_OBJ_P(type));
		if (UNEXPECTED(typeSubtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(typeSubtracted) == IS_NULL) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);

		zend_long isSuperType = isSuperTypeOfTrinary(typeSubtracted, subtracted);
		if (UNEXPECTED(isSuperType < 0)) return zv::Val();
		if (isSuperType == PT_TRI_YES) return yesUnlessOnlyThisIsExplicit(explicitMixed, Z_OBJ_P(type));

		return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
	}

	/* yes without a subtracted type or for a NeverType; against another
	 * MixedType by the subtracted types; otherwise the negated answer of the
	 * subtracted type, a no carrying the reason; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) == IS_NULL || instanceof_function(Z_OBJCE_P(type), pt_ce_never_type)) return pt_type_is_super_type_of_result(PT_TRI_YES);

		if (instanceof_function(Z_OBJCE_P(type), pt_ce_mixed_type)) {
			zval *typeSubtracted = subtractedTypeOf(Z_OBJ_P(type));
			if (UNEXPECTED(typeSubtracted == NULL)) return zv::Val();
			if (Z_TYPE_P(typeSubtracted) == IS_NULL) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
			zv::Val isSuperType = pt_type_call(Z_OBJ_P(typeSubtracted), PT_LC("issupertypeof"), 1, subtracted);
			if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
			zend_long value = pt_type_result_trinary(isSuperType.raw());
			if (UNEXPECTED(value < 0)) return zv::Val();
			if (value == PT_TRI_YES) return isSuperType;

			return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		}

		/* $this->subtractedType->isSuperTypeOf($type)->negate() */
		zv::Val isSuperType = pt_type_call(Z_OBJ_P(subtracted), PT_LC("issupertypeof"), 1, type);
		if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(isSuperType.raw()).isObject())) {
			zend_type_error("phpstan_turbo: isSuperTypeOf() must return %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
			return zv::Val();
		}
		zv::Val result = pt_type_call(Z_OBJ_P(isSuperType.raw()), PT_LC("negate"), 0, NULL);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zend_long value = pt_type_result_trinary(result.raw());
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (value == PT_TRI_NO) {
			/* IsSuperTypeOfResult::createNo([sprintf('Type %s has already been eliminated from %s.',
			 *   $this->subtractedType->describe(VerbosityLevel::precise()),
			 *   $this->describe(VerbosityLevel::typeOnly()))]) */
			zval subtractedDescriptionRaw;
			if (UNEXPECTED(!pt_type_describe_precise(subtracted, &subtractedDescriptionRaw))) return zv::Val();
			zv::Val subtractedDescription = zv::Val::adopt(subtractedDescriptionRaw);
			zv::Val typeOnly = pt_type_verbosity_level(PT_VERBOSITY_LEVEL_TYPE_ONLY);
			if (UNEXPECTED(typeOnly.isUndef())) return zv::Val();
			zv::Val ownDescription = isExact() ? describe(typeOnly.raw()) : pt_type_call(self, PT_LC("describe"), 1, typeOnly.raw());
			if (UNEXPECTED(ownDescription.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(subtractedDescription.raw()).isString() || !zv::Ref(ownDescription.raw()).isString())) {
				zend_type_error("phpstan_turbo: describe() must return string");
				return zv::Val();
			}
			smart_str reason = {NULL, 0};
			smart_str_appendl(&reason, "Type ", 5);
			smart_str_append(&reason, zv::Ref(subtractedDescription.raw()).asString());
			smart_str_appendl(&reason, " has already been eliminated from ", sizeof(" has already been eliminated from ") - 1);
			smart_str_append(&reason, zv::Ref(ownDescription.raw()).asString());
			smart_str_appendc(&reason, '.');
			smart_str_0(&reason);
			zv::Arr reasons = zv::Arr::create(1);
			reasons.push(zv::Val::adoptString(reason.s));
			return pt_type_call_static_ce(pt_ce_is_super_type_of_result, PT_LC("createno"), 1, reasons.raw());
		}

		return result;
	}

	/* new self($this->isExplicitMixed); UNDEF = pending exception */
	zv::Val withoutSubtractedType() const
	{
		bool explicitMixed = false;
		if (UNEXPECTED(!isExplicitMixed(explicitMixed))) return zv::Val();
		return create(explicitMixed);
	}

	/* new self($this->isExplicitMixed, TypeCombinator::remove($this->subtractedType,
	 * new ConstantArrayType([], []))) with a subtracted type, $this without */
	zv::Val unsetOffset() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Val emptyArray = emptyConstantArray();
			if (UNEXPECTED(emptyArray.isUndef())) return zv::Val();
			zv::Args args{subtracted, emptyArray.raw()};
			zv::Val remaining = pt_type_combinator_call(PT_LC("remove"), 2, args);
			if (UNEXPECTED(remaining.isUndef())) return zv::Val();
			bool explicitMixed = false;
			if (UNEXPECTED(!isExplicitMixed(explicitMixed))) return zv::Val();
			return create(explicitMixed, remaining.raw());
		}
		return thisValue();
	}

	/* $this->getKeysArray(); UNDEF = pending exception */
	zv::Val getKeysArrayFiltered() const
	{
		if (EXPECTED(isExact())) return getKeysArray();
		return pt_type_call(self, PT_LC("getkeysarray"), 0, NULL);
	}

	/* list<int|string> unless $this->isArray()->no(); UNDEF = pending exception */
	zv::Val getKeysArray() const
	{
		bool notArray;
		if (UNEXPECTED(!thisIsArrayNo(notArray))) return zv::Val();
		if (notArray) return errorType();
		zv::Arr types = zv::Arr::create(2);
		if (UNEXPECTED(!pushInteger(types) || !pushString(types))) return zv::Val();
		zv::Val keys = pt_type_new_union(std::move(types));
		if (UNEXPECTED(keys.isUndef())) return zv::Val();
		return listOf(std::move(keys));
	}

	/* list<mixed> unless $this->isArray()->no() — getValuesArray(),
	 * chunkArray() and shuffleArray(); UNDEF = pending exception */
	zv::Val listOfMixed() const
	{
		bool notArray;
		if (UNEXPECTED(!thisIsArrayNo(notArray))) return zv::Val();
		if (notArray) return errorType();
		zv::Val mixed = withoutSubtractedType();
		if (UNEXPECTED(mixed.isUndef())) return zv::Val();
		return listOf(std::move(mixed));
	}

	/* new ArrayType($this->getIterableValueType(), $valueType) unless
	 * $this->isArray()->no(); UNDEF = pending exception */
	zv::Val fillKeysArray(zval *valueType) const
	{
		bool notArray;
		if (UNEXPECTED(!thisIsArrayNo(notArray))) return zv::Val();
		if (notArray) return errorType();
		zv::Val iterableValueType = isExact() ? withoutSubtractedType() : pt_type_call(self, PT_LC("getiterablevaluetype"), 0, NULL);
		if (UNEXPECTED(iterableValueType.isUndef())) return zv::Val();
		zv::Val keyType = pt_type_call(Z_OBJ_P(iterableValueType.raw()), PT_LC("toarraykey"), 0, NULL);
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		return arrayType(std::move(keyType), zv::Val::copyOf(zv::Ref(valueType)));
	}

	/* array<mixed, mixed> unless $this->isArray()->no() — flipArray(),
	 * intersectKeyArray(), popArray(), reverseArray(), shiftArray(),
	 * sliceArray(), spliceArray(), changeKeyCaseArray() and
	 * filterArrayRemovingFalsey(); UNDEF = pending exception */
	zv::Val arrayOfMixed() const
	{
		bool notArray;
		if (UNEXPECTED(!thisIsArrayNo(notArray))) return zv::Val();
		if (notArray) return errorType();
		return mixedArray();
	}

	/* int|string|false unless $this->isArray()->no(); UNDEF = pending exception */
	zv::Val searchArray() const
	{
		bool notArray;
		if (UNEXPECTED(!thisIsArrayNo(notArray))) return zv::Val();
		if (notArray) return errorType();
		zv::Arr types = zv::Arr::create(3);
		if (UNEXPECTED(!pushInteger(types) || !pushString(types) || !pushConstantBoolean(types, false))) return zv::Val();
		return pt_type_new_union(std::move(types));
	}

	/* $this unless $this->isArray()->no(); UNDEF = pending exception */
	zv::Val truncateListToSize() const
	{
		bool notArray;
		if (UNEXPECTED(!thisIsArrayNo(notArray))) return zv::Val();
		if (notArray) return errorType();
		return thisValue();
	}

	/* mapValueType(): new ArrayType(new MixedType(e), $cb(new MixedType(e)));
	 * mapKeyType(): new ArrayType($cb(new MixedType(e)), new MixedType(e));
	 * unless $this->isArray()->no(); UNDEF = pending exception */
	zv::Val mapType(zend_fcall_info *fci, zend_fcall_info_cache *fcc, bool mapValue) const
	{
		bool notArray;
		if (UNEXPECTED(!thisIsArrayNo(notArray))) return zv::Val();
		if (notArray) return errorType();
		zv::Val untouched = withoutSubtractedType();
		if (UNEXPECTED(untouched.isUndef())) return zv::Val();
		zv::Val argument = withoutSubtractedType();
		if (UNEXPECTED(argument.isUndef())) return zv::Val();
		zval mapped;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, argument.raw(), &mapped))) return zv::Val();
		zv::Val mappedType = zv::Val::adopt(mapped);
		if (mapValue) return arrayType(std::move(untouched), std::move(mappedType));
		return arrayType(std::move(mappedType), std::move(untouched));
	}

	/* no when the subtracted type covers every callable, maybe otherwise;
	 * -1 = pending exception */
	[[nodiscard]] zend_long isCallable() const { return noWhenSubtractedCovers(callableTypeNew); }

	/* [new TrivialParametersAcceptor()] */
	static zv::Val getCallableParametersAcceptors()
	{
		zv::Val acceptor = pt_type_new(PT_CLASS_TRIVIAL_PARAMETERS_ACCEPTOR, 0, NULL);
		if (UNEXPECTED(acceptor.isUndef())) return zv::Val();
		zv::Arr acceptors = zv::Arr::create(1);
		acceptors.push(std::move(acceptor));
		return zv::Val(std::move(acceptors));
	}

	/* the same class (get_class($type) === static::class) with equal
	 * subtracted types; false with an exception pending on an uninitialized
	 * slot */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (Z_OBJCE_P(type) != self->ce) {
			out = false;
			return true;
		}
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return false;
		if (Z_TYPE_P(subtracted) == IS_NULL) {
			zval *typeSubtracted = subtractedTypeOf(Z_OBJ_P(type));
			if (UNEXPECTED(typeSubtracted == NULL)) return false;
			out = Z_TYPE_P(typeSubtracted) == IS_NULL;
			return true;
		}
		zval *typeSubtracted = subtractedTypeOf(Z_OBJ_P(type));
		if (UNEXPECTED(typeSubtracted == NULL)) return false;
		if (Z_TYPE_P(typeSubtracted) == IS_NULL) {
			out = false;
			return true;
		}
		return pt_type_call_bool(Z_OBJ_P(subtracted), PT_LC("equals"), 1, typeSubtracted, out);
	}

	/* yes for a MixedType that is no TemplateMixedType, no when the
	 * subtracted type covers $otherType, maybe otherwise; UNDEF = pending
	 * exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		if (instanceof_function(Z_OBJCE_P(otherType), pt_ce_mixed_type)) {
			bool isTemplateMixed;
			if (UNEXPECTED(!pt_type_instanceof(otherType, PT_CLASS_TEMPLATE_MIXED_TYPE, isTemplateMixed))) return zv::Val();
			if (!isTemplateMixed) return pt_type_is_super_type_of_result(PT_TRI_YES);
		}

		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zend_long isSuperType = isSuperTypeOfTrinary(subtracted, otherType);
			if (UNEXPECTED(isSuperType < 0)) return zv::Val();
			if (isSuperType == PT_TRI_YES) return pt_type_is_super_type_of_result(PT_TRI_NO);
		}

		return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
	}

	/* $this->isSuperTypeOf($acceptingType)->toAcceptsResult() when that is
	 * no, yes otherwise; UNDEF = pending exception */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		zv::Val isSuperType = isExact() ? isSuperTypeOf(acceptingType) : pt_type_call(self, PT_LC("issupertypeof"), 1, acceptingType);
		if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(isSuperType.raw()).isObject())) {
			zend_type_error("phpstan_turbo: isSuperTypeOf() must return %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
			return zv::Val();
		}
		zv::Val accepts = pt_type_call(Z_OBJ_P(isSuperType.raw()), PT_LC("toacceptsresult"), 0, NULL);
		if (UNEXPECTED(accepts.isUndef())) return zv::Val();
		zend_long value = pt_type_result_trinary(accepts.raw());
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (value == PT_TRI_NO) return accepts;
		return pt_type_accepts_result(PT_TRI_YES);
	}

	/* new self() */
	static zv::Val getTemplateType() { return create(); }

	/* no when the subtracted type covers every object, maybe otherwise —
	 * isObject() and isEnum(); -1 = pending exception */
	[[nodiscard]] zend_long isObject() const { return noWhenSubtractedCovers(objectWithoutClassNew); }

	/* new ClassStringType() */
	static zv::Val getClassStringType()
	{
		return pt_val_of<pt_class_string_type_new>();
	}

	/* $this->getUnresolved*Prototype($name, $scope)->getTransformedProperty()
	 * / ->getTransformedMethod(); UNDEF = pending exception */
	zv::Val transformedMember(const char *prototypeLcname, size_t prototypeLen, bool isMethod, zval *name, zval *scope) const
	{
		zv::Args args{name, scope};
		zv::Val prototype = pt_type_call(self, prototypeLcname, prototypeLen, 2, args);
		if (UNEXPECTED(prototype.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(prototype.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s() must return an object", prototypeLcname);
			return zv::Val();
		}
		if (isMethod) return pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("gettransformedmethod"), 0, NULL);
		return pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("gettransformedproperty"), 0, NULL);
	}

	/* new CallbackUnresolved{Property,Method}PrototypeReflection($member,
	 * $member->getDeclaringClass(), false, static fn (Type $type): Type => $type)
	 * over a Dummy{Property,Method}Reflection($name); UNDEF = pending
	 * exception */
	static zv::Val unresolvedPrototype(bool isMethod, zval *name)
	{
		zv::Val member = pt_type_new(isMethod ? PT_CLASS_DUMMY_METHOD_REFLECTION : PT_CLASS_DUMMY_PROPERTY_REFLECTION, 1, name);
		if (UNEXPECTED(member.isUndef())) return zv::Val();
		zv::Val declaringClass = pt_type_call(Z_OBJ_P(member.raw()), PT_LC("getdeclaringclass"), 0, NULL);
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		zv::Val callback = identityCallback();
		zv::Args args{member.raw(), declaringClass.raw(), false, callback.raw()};
		return pt_type_new(isMethod ? PT_CLASS_CALLBACK_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION : PT_CLASS_CALLBACK_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION, 4, args);
	}

	/* new DummyClassConstantReflection($constantName) */
	static zv::Val getConstant(zval *constantName) { return pt_type_new(PT_CLASS_DUMMY_CLASS_CONSTANT_REFLECTION, 1, constantName); }

	/* $level->handle(): 'mixed' for the type-only and value levels, with the
	 * subtracted type for the precise level, and '=explicit'/'=implicit' on
	 * top for the cache level; UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		pt_verbosity_case which;
		if (UNEXPECTED(!pt_type_verbosity_case(level, which))) return zv::Val();
		if (which == PT_VERBOSITY_TYPE_ONLY || which == PT_VERBOSITY_VALUE) return zv::Val::string("mixed", 5);

		/* 'mixed' . $this->describeSubtractedType($this->subtractedType, $level) */
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		zv::Val subtractedDescription;
		if (EXPECTED(pt_type_method_is(self, PT_LC("describesubtractedtype"), pt_type_trait_substractable_describe_subtracted_type))) {
			subtractedDescription = pt_type_describe_subtracted_type(subtracted, level);
		} else {
			zv::Args args{subtracted, level};
			subtractedDescription = pt_type_call(self, PT_LC("describesubtractedtype"), 2, args);
		}
		if (UNEXPECTED(subtractedDescription.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(subtractedDescription.raw()).isString())) {
			zend_type_error("phpstan_turbo: describeSubtractedType() must return string");
			return zv::Val();
		}
		smart_str description = {NULL, 0};
		smart_str_appendl(&description, "mixed", 5);
		smart_str_append(&description, zv::Ref(subtractedDescription.raw()).asString());
		if (which == PT_VERBOSITY_CACHE) {
			bool explicitMixed = false;
			if (UNEXPECTED(!isExplicitMixed(explicitMixed))) {
				smart_str_free(&description);
				return zv::Val();
			}
			if (explicitMixed) {
				smart_str_appendl(&description, "=explicit", 9);
			} else {
				smart_str_appendl(&description, "=implicit", 9);
			}
		}
		smart_str_0(&description);
		return zv::Val::adoptString(description.s);
	}

	/* new ConstantBooleanType(true) when the subtracted type covers every
	 * falsey value, new BooleanType() otherwise; UNDEF = pending exception */
	zv::Val toBoolean() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Val falsey = pt_type_call_static(PT_CLASS_STATIC_TYPE_FACTORY, PT_LC("falsey"), 0, NULL);
			if (UNEXPECTED(falsey.isUndef())) return zv::Val();
			zend_long isSuperType = isSuperTypeOfTrinary(subtracted, falsey.raw());
			if (UNEXPECTED(isSuperType < 0)) return zv::Val();
			if (isSuperType == PT_TRI_YES) return constantBoolean(true);
		}
		return booleanType();
	}

	/* TypeCombinator::union($this->toInteger(), $this->toFloat()); UNDEF =
	 * pending exception */
	zv::Val toNumber() const
	{
		zv::Val integer = isExact() ? toInteger() : pt_type_call(self, PT_LC("tointeger"), 0, NULL);
		if (UNEXPECTED(integer.isUndef())) return zv::Val();
		zv::Val floatType = isExact() ? toFloat() : pt_type_call(self, PT_LC("tofloat"), 0, NULL);
		if (UNEXPECTED(floatType.isUndef())) return zv::Val();
		zv::Args args{integer.raw(), floatType.raw()};
		return pt_type_combinator_call(PT_LC("union"), 2, args);
	}

	/* false when $this->isObject() is no, $this->getClassStringType() when
	 * yes, their union otherwise; UNDEF = pending exception */
	zv::Val toGetClassResultType() const
	{
		zend_long isObject = isExact() ? this->isObject() : pt_type_call_trinary(self, PT_LC("isobject"), 0, NULL);
		if (UNEXPECTED(isObject < 0)) return zv::Val();
		if (isObject == PT_TRI_NO) return constantBoolean(false);

		zv::Val classString = isExact() ? getClassStringType() : pt_type_call(self, PT_LC("getclassstringtype"), 0, NULL);
		if (UNEXPECTED(classString.isUndef())) return zv::Val();
		if (isObject == PT_TRI_YES) return classString;

		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(classString));
		if (UNEXPECTED(!pushConstantBoolean(types, false))) return zv::Val();
		return pt_type_new_union(std::move(types));
	}

	/* new ClassNameToObjectTypeResult(new MixedType(), false) */
	static zv::Val toObjectTypeForInstanceofCheck()
	{
		zv::Val mixed = create();
		if (UNEXPECTED(mixed.isUndef())) return zv::Val();
		return classNameToObjectTypeResult(std::move(mixed));
	}

	/* new ClassNameToObjectTypeResult(new UnionType([new ObjectWithoutClassType(),
	 * new ClassStringType()]), false) when strings are allowed, of an
	 * ObjectWithoutClassType alone otherwise; UNDEF = pending exception */
	static zv::Val toObjectTypeForIsACheck(bool allowString)
	{
		zv::Val objectWithoutClass = pt_type_new_object_without_class_type();
		if (UNEXPECTED(objectWithoutClass.isUndef())) return zv::Val();
		if (!allowString) return classNameToObjectTypeResult(std::move(objectWithoutClass));
		zv::Val classString = getClassStringType();
		if (UNEXPECTED(classString.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(objectWithoutClass));
		types.push(std::move(classString));
		zv::Val unionType = pt_type_new_union(std::move(types));
		if (UNEXPECTED(unionType.isUndef())) return zv::Val();
		return classNameToObjectTypeResult(std::move(unionType));
	}

	/* $this->toNumber()->toAbsoluteNumber(); UNDEF = pending exception */
	zv::Val toAbsoluteNumber() const
	{
		zv::Val number = isExact() ? toNumber() : pt_type_call(self, PT_LC("tonumber"), 0, NULL);
		if (UNEXPECTED(number.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(number.raw()).isObject())) {
			zend_type_error("phpstan_turbo: toNumber() must return %s", ptcls::type);
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(number.raw()), PT_LC("toabsolutenumber"), 0, NULL);
	}

	/* int<min, -1>|int<1, max> when the subtracted type covers everything
	 * that casts to 0 (the probe union is only built with a subtracted type
	 * to hold it against — without one the twin builds and drops it), int
	 * otherwise; UNDEF = pending exception */
	zv::Val toInteger() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			/* new UnionType([new NullType(), new ConstantBooleanType(false),
			 * new ConstantIntegerType(0), new ConstantArrayType([], []),
			 * new StringType(), new FloatType()]) */
			zv::Arr castsToZeroTypes = zv::Arr::create(6);
			zv::Val zero = pt_type_new_constant_integer(0);
			zv::Val emptyArray = emptyConstantArray();
			if (UNEXPECTED(zero.isUndef() || emptyArray.isUndef() || !pushNew(castsToZeroTypes, pt_null_type_new) || !pushConstantBoolean(castsToZeroTypes, false))) {
				return zv::Val();
			}
			castsToZeroTypes.push(std::move(zero));
			castsToZeroTypes.push(std::move(emptyArray));
			if (UNEXPECTED(!pushString(castsToZeroTypes) || !pushNew(castsToZeroTypes, pt_float_type_new))) return zv::Val();
			zv::Val castsToZero = pt_type_new_union(std::move(castsToZeroTypes));
			if (UNEXPECTED(castsToZero.isUndef())) return zv::Val();
			zend_long isSuperType = isSuperTypeOfTrinary(subtracted, castsToZero.raw());
			if (UNEXPECTED(isSuperType < 0)) return zv::Val();
			if (isSuperType == PT_TRI_YES) {
				zv::Val negative = pt_integer_range_from_interval(NullableLong::null(), NullableLong::of(-1), 0);
				if (UNEXPECTED(negative.isUndef())) return zv::Val();
				zv::Val positive = pt_integer_range_from_interval(NullableLong::of(1), NullableLong::null(), 0);
				if (UNEXPECTED(positive.isUndef())) return zv::Val();
				zv::Arr types = zv::Arr::create(2);
				types.push(std::move(negative));
				types.push(std::move(positive));
				return pt_type_new_union(std::move(types));
			}
		}

		return integerType();
	}

	/* new FloatType() */
	static zv::Val toFloat()
	{
		return pt_val_of<pt_float_type_new>();
	}

	/* non-empty-string (non-falsy-string when the subtracted type also
	 * covers what casts to '0') when the subtracted type covers everything
	 * that casts to '', string otherwise; UNDEF = pending exception */
	zv::Val toString() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			/* new UnionType([new NullType(), new ConstantBooleanType(false), new ConstantStringType('')]) */
			zv::Arr castsToEmptyStringTypes = zv::Arr::create(3);
			zv::Val emptyString = pt_type_new_constant_string("", 0);
			if (UNEXPECTED(emptyString.isUndef() || !pushNew(castsToEmptyStringTypes, pt_null_type_new) || !pushConstantBoolean(castsToEmptyStringTypes, false))) {
				return zv::Val();
			}
			castsToEmptyStringTypes.push(std::move(emptyString));
			zv::Val castsToEmptyString = pt_type_new_union(std::move(castsToEmptyStringTypes));
			if (UNEXPECTED(castsToEmptyString.isUndef())) return zv::Val();
			zend_long isSuperType = isSuperTypeOfTrinary(subtracted, castsToEmptyString.raw());
			if (UNEXPECTED(isSuperType < 0)) return zv::Val();
			if (isSuperType == PT_TRI_YES) {
				zv::Arr accessories = zv::Arr::create(3);
				if (UNEXPECTED(!pushString(accessories) || !pushNew(accessories, pt_accessory_non_empty_string_type_new))) return zv::Val();

				/* new UnionType([new ConstantFloatType(0.0), new ConstantStringType('0'), new ConstantIntegerType(0)]) */
				zv::Arr castsToZeroStringTypes = zv::Arr::create(3);
				zv::Val floatZero = pt_type_new_constant_float(0.0);
				zv::Val stringZero = pt_type_new_constant_string("0", 1);
				zv::Val intZero = pt_type_new_constant_integer(0);
				if (UNEXPECTED(floatZero.isUndef() || stringZero.isUndef() || intZero.isUndef())) return zv::Val();
				castsToZeroStringTypes.push(std::move(floatZero));
				castsToZeroStringTypes.push(std::move(stringZero));
				castsToZeroStringTypes.push(std::move(intZero));
				zv::Val castsToZeroString = pt_type_new_union(std::move(castsToZeroStringTypes));
				if (UNEXPECTED(castsToZeroString.isUndef())) return zv::Val();
				zend_long coversZeroString = isSuperTypeOfTrinary(subtracted, castsToZeroString.raw());
				if (UNEXPECTED(coversZeroString < 0)) return zv::Val();
				if (coversZeroString == PT_TRI_YES) {
					if (UNEXPECTED(!pushNew(accessories, pt_accessory_non_falsy_string_type_new))) return zv::Val();
				}
				return intersectionOf(std::move(accessories));
			}
		}

		return stringType();
	}

	/* $mixed = new self($this->isExplicitMixed); new ArrayType($mixed, $mixed) */
	zv::Val toArray() const
	{
		zv::Val mixed = withoutSubtractedType();
		if (UNEXPECTED(mixed.isUndef())) return zv::Val();
		zv::Val second = zv::Val::copyOf(zv::Ref(mixed.raw()));
		return arrayType(std::move(mixed), std::move(second));
	}

	/* new BenevolentUnionType([new IntegerType(), new StringType()]) */
	static zv::Val toArrayKey()
	{
		zv::Arr types = zv::Arr::create(2);
		if (UNEXPECTED(!pushInteger(types) || !pushString(types))) return zv::Val();
		return pt_union_benevolent_of(std::move(types));
	}

	/* no when the subtracted type covers every iterable, maybe otherwise;
	 * -1 = pending exception */
	[[nodiscard]] zend_long isIterable() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return -1;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			/* new IterableType(new MixedType(), new MixedType()) */
			zv::Val key = create();
			zv::Val value = create();
			if (UNEXPECTED(key.isUndef() || value.isUndef())) return -1;
			zval iterableRaw;
			if (UNEXPECTED(!pt_iterable_type_new(&iterableRaw, key.raw(), value.raw()))) return -1;
			zv::Val iterable = zv::Val::adopt(iterableRaw);
			zend_long isSuperType = isSuperTypeOfTrinary(subtracted, iterable.raw());
			if (UNEXPECTED(isSuperType < 0)) return -1;
			if (isSuperType == PT_TRI_YES) return PT_TRI_NO;
		}
		return PT_TRI_MAYBE;
	}

	/* $this->isIterable(); -1 = pending exception */
	[[nodiscard]] zend_long isIterableAtLeastOnce() const { return thisIsIterable(); }

	/* int<0, max> unless $this->isIterable()->no(); UNDEF = pending exception */
	zv::Val getArraySize() const
	{
		zend_long iterable = thisIsIterable();
		if (UNEXPECTED(iterable < 0)) return zv::Val();
		if (iterable == PT_TRI_NO) return errorType();
		return pt_integer_range_from_interval(NullableLong::of(0), NullableLong::null(), 0);
	}

	/* no when the subtracted type covers string, array and ArrayAccess,
	 * maybe otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isOffsetAccessible() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return -1;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			/* new UnionType([new StringType(), new ArrayType(new MixedType(), new MixedType()), new ObjectType(ArrayAccess::class)]) */
			zv::Arr types = zv::Arr::create(3);
			if (UNEXPECTED(!pushString(types))) return -1;
			zv::Val array = mixedArray(false);
			if (UNEXPECTED(array.isUndef())) return -1;
			types.push(std::move(array));
			zv::Val className = zv::Val::string("ArrayAccess", sizeof("ArrayAccess") - 1);
			zv::Val arrayAccess = pt_type_new_object_type(className.raw());
			if (UNEXPECTED(arrayAccess.isUndef())) return -1;
			types.push(std::move(arrayAccess));
			zv::Val offsetAccessibles = pt_type_new_union(std::move(types));
			if (UNEXPECTED(offsetAccessibles.isUndef())) return -1;
			zend_long isSuperType = isSuperTypeOfTrinary(subtracted, offsetAccessibles.raw());
			if (UNEXPECTED(isSuperType < 0)) return -1;
			if (isSuperType == PT_TRI_YES) return PT_TRI_NO;
		}
		return PT_TRI_MAYBE;
	}

	/* yes when the subtracted type covers every object, maybe otherwise;
	 * -1 = pending exception */
	[[nodiscard]] zend_long isOffsetAccessLegal() const
	{
		bool covered;
		if (UNEXPECTED(!subtractedCovers(objectWithoutClassNew, covered))) return -1;
		return covered ? PT_TRI_YES : PT_TRI_MAYBE;
	}

	/* no when $this->isOffsetAccessible() is no, maybe otherwise; -1 =
	 * pending exception */
	zend_long hasOffsetValueType() const
	{
		zend_long accessible = isExact() ? isOffsetAccessible() : pt_type_call_trinary(self, PT_LC("isoffsetaccessible"), 0, NULL);
		if (UNEXPECTED(accessible < 0)) return -1;
		return accessible == PT_TRI_NO ? PT_TRI_NO : PT_TRI_MAYBE;
	}

	/* never for a MixedType that is no TemplateType, else mixed without
	 * $type (unioned with what is already subtracted); UNDEF = pending
	 * exception */
	zv::Val subtract(zval *type) const
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_mixed_type)) {
			bool isTemplate;
			if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
			if (!isTemplate) return pt_type_new_never_type();
		}
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		zv::Val unioned;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Args args{subtracted, type};
			unioned = pt_type_combinator_call(PT_LC("union"), 2, args);
			if (UNEXPECTED(unioned.isUndef())) return zv::Val();
			type = unioned.raw();
		}

		bool explicitMixed = false;
		if (UNEXPECTED(!isExplicitMixed(explicitMixed))) return zv::Val();
		return create(explicitMixed, type);
	}

	/* new self($this->isExplicitMixed, $subtractedType); UNDEF = pending
	 * exception */
	zv::Val changeSubtractedType(zval *subtractedType) const
	{
		bool explicitMixed = false;
		if (UNEXPECTED(!isExplicitMixed(explicitMixed))) return zv::Val();
		return create(explicitMixed, subtractedType);
	}

	/* no when the subtracted type covers every array, maybe otherwise; -1 =
	 * pending exception */
	zend_long isArray() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return -1;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Val array = mixedArray(false);
			if (UNEXPECTED(array.isUndef())) return -1;
			zend_long isSuperType = isSuperTypeOfTrinary(subtracted, array.raw());
			if (UNEXPECTED(isSuperType < 0)) return -1;
			if (isSuperType == PT_TRI_YES) return PT_TRI_NO;
		}
		return PT_TRI_MAYBE;
	}

	/* $this->isArray(); -1 = pending exception */
	[[nodiscard]] zend_long isConstantArray() const
	{
		if (EXPECTED(isExact())) return isArray();
		return pt_type_call_trinary(self, PT_LC("isarray"), 0, NULL);
	}

	/* no when the subtracted type covers every oversized array, maybe
	 * otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isOversizedArray() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return -1;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			/* new IntersectionType([new ArrayType(new MixedType(), new MixedType()), new OversizedArrayType()]) */
			zv::Arr types = zv::Arr::create(2);
			zv::Val array = mixedArray(false);
			if (UNEXPECTED(array.isUndef())) return -1;
			types.push(std::move(array));
			if (UNEXPECTED(!pushNew(types, pt_oversized_array_type_new))) return -1;
			zv::Val oversizedArray = intersectionOf(std::move(types));
			if (UNEXPECTED(oversizedArray.isUndef())) return -1;
			zend_long isSuperType = isSuperTypeOfTrinary(subtracted, oversizedArray.raw());
			if (UNEXPECTED(isSuperType < 0)) return -1;
			if (isSuperType == PT_TRI_YES) return PT_TRI_NO;
		}
		return PT_TRI_MAYBE;
	}

	/* no when the subtracted type covers every list, maybe otherwise; -1 =
	 * pending exception */
	zend_long isList() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return -1;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Val mixed = create();
			if (UNEXPECTED(mixed.isUndef())) return -1;
			zv::Val list = listOf(std::move(mixed));
			if (UNEXPECTED(list.isUndef())) return -1;
			zend_long isSuperType = isSuperTypeOfTrinary(subtracted, list.raw());
			if (UNEXPECTED(isSuperType < 0)) return -1;
			if (isSuperType == PT_TRI_YES) return PT_TRI_NO;
		}
		return PT_TRI_MAYBE;
	}

	/* the is*() family: no when the subtracted type covers the probe type,
	 * maybe otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isNull() const { return noWhenSubtractedCovers(pt_null_type_new); }
	zend_long isFloat() const { return noWhenSubtractedCovers(pt_float_type_new); }
	zend_long isVoid() const { return noWhenSubtractedCovers(pt_void_type_new); }

	zend_long isTrue() const { return noWhenSubtractedCoversConstantBoolean(true); }
	zend_long isFalse() const { return noWhenSubtractedCoversConstantBoolean(false); }

	zend_long isBoolean() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return -1;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Val probe = booleanType();
			if (UNEXPECTED(probe.isUndef())) return -1;
			return noWhenSubtractedCoversProbe(subtracted, probe.raw());
		}
		return PT_TRI_MAYBE;
	}

	zend_long isInteger() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return -1;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Val probe = integerType();
			if (UNEXPECTED(probe.isUndef())) return -1;
			return noWhenSubtractedCoversProbe(subtracted, probe.raw());
		}
		return PT_TRI_MAYBE;
	}

	zend_long isString() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return -1;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Val probe = stringType();
			if (UNEXPECTED(probe.isUndef())) return -1;
			return noWhenSubtractedCoversProbe(subtracted, probe.raw());
		}
		return PT_TRI_MAYBE;
	}

	/* the string accessories: no when the subtracted type covers
	 * string&<accessory>, maybe otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isNumericString() const { return noWhenSubtractedCoversAccessoryString(pt_accessory_numeric_string_type_new); }
	zend_long isDecimalIntegerString() const { return noWhenSubtractedCoversAccessoryString(decimalIntegerString); }
	zend_long isNonEmptyString() const { return noWhenSubtractedCoversAccessoryString(pt_accessory_non_empty_string_type_new); }
	zend_long isNonFalsyString() const { return noWhenSubtractedCoversAccessoryString(pt_accessory_non_falsy_string_type_new); }
	zend_long isLiteralString() const { return noWhenSubtractedCoversAccessoryString(pt_accessory_literal_string_type_new); }
	zend_long isLowercaseString() const { return noWhenSubtractedCoversAccessoryString(pt_accessory_lowercase_string_type_new); }
	zend_long isUppercaseString() const { return noWhenSubtractedCoversAccessoryString(pt_accessory_uppercase_string_type_new); }

	/* no when the subtracted type covers string, or every class-string;
	 * maybe otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isClassString() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return -1;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Val string = stringType();
			if (UNEXPECTED(string.isUndef())) return -1;
			zend_long coversString = noWhenSubtractedCoversProbe(subtracted, string.raw());
			if (coversString != PT_TRI_MAYBE) return coversString;
			zv::Val classString = getClassStringType();
			if (UNEXPECTED(classString.isUndef())) return -1;
			return noWhenSubtractedCoversProbe(subtracted, classString.raw());
		}
		return PT_TRI_MAYBE;
	}

	/* object unless $this->isClassString() is no; UNDEF = pending exception */
	zv::Val getClassStringObjectType() const
	{
		zend_long classString = isExact() ? isClassString() : pt_type_call_trinary(self, PT_LC("isclassstring"), 0, NULL);
		if (UNEXPECTED(classString < 0)) return zv::Val();
		if (classString != PT_TRI_NO) return pt_type_new_object_without_class_type();
		return errorType();
	}

	/* object unless $this->isSuperTypeOf(object|class-string) is no; UNDEF
	 * = pending exception */
	zv::Val getObjectTypeOrClassStringObjectType() const
	{
		zv::Arr types = zv::Arr::create(2);
		if (UNEXPECTED(!pushNew(types, objectWithoutClassNew))) return zv::Val();
		zv::Val classString = getClassStringType();
		if (UNEXPECTED(classString.isUndef())) return zv::Val();
		types.push(std::move(classString));
		zv::Val objectOrClass = pt_type_new_union(std::move(types));
		if (UNEXPECTED(objectOrClass.isUndef())) return zv::Val();
		zv::Val isSuperType = isExact() ? isSuperTypeOf(objectOrClass.raw()) : pt_type_call(self, PT_LC("issupertypeof"), 1, objectOrClass.raw());
		if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
		zend_long value = pt_type_result_trinary(isSuperType.raw());
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (value != PT_TRI_NO) return pt_type_new_object_without_class_type();
		return errorType();
	}

	/* no when the subtracted type covers bool|float|int|string, maybe
	 * otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isScalar() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return -1;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Arr types = zv::Arr::create(4);
			zv::Val boolean = booleanType();
			if (UNEXPECTED(boolean.isUndef())) return -1;
			types.push(std::move(boolean));
			if (UNEXPECTED(!pushNew(types, pt_float_type_new) || !pushInteger(types) || !pushString(types))) return -1;
			zv::Val scalars = pt_type_new_union(std::move(types));
			if (UNEXPECTED(scalars.isUndef())) return -1;
			return noWhenSubtractedCoversProbe(subtracted, scalars.raw());
		}
		return PT_TRI_MAYBE;
	}

	/* new BooleanType() */
	static zv::Val looseCompare() { return booleanType(); }

	/* null when $this->isSuperTypeOf($typeToRemove) is no, else
	 * $this->subtract($typeToRemove); UNDEF = pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		zv::Val isSuperType = isExact() ? isSuperTypeOf(typeToRemove) : pt_type_call(self, PT_LC("issupertypeof"), 1, typeToRemove);
		if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
		zend_long value = pt_type_result_trinary(isSuperType.raw());
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (value == PT_TRI_NO) return zv::Val::null();
		if (EXPECTED(isExact())) return subtract(typeToRemove);
		return pt_type_call(self, PT_LC("subtract"), 1, typeToRemove);
	}

	/* new BenevolentUnionType([new FloatType(), new IntegerType()]) */
	static zv::Val exponentiate()
	{
		zv::Arr types = zv::Arr::create(2);
		if (UNEXPECTED(!pushNew(types, pt_float_type_new) || !pushInteger(types))) return zv::Val();
		return pt_union_benevolent_of(std::move(types));
	}

	/* new IdentifierTypeNode('mixed') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("mixed", 5);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

private:
	zend_object *self;

	/* exactly a MixedType, none of its methods overridden: $this-calls can
	 * go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_mixed_type; }

	/* new ObjectWithoutClassType() — the shadowing class, in the shape the
	 * probe and push overloads take */
	static bool objectWithoutClassNew(zval *out) { return pt_object_without_class_type_new(out); }
	/* new CallableType() — the shadowing class at the twin's defaults */
	static bool callableTypeNew(zval *out) { return pt_callable_type_new(out); }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* yes unless only $this is explicit (then maybe) — the tail the two
	 * explicitness branches of isSuperTypeOfMixed() share; UNDEF = pending
	 * exception */
	static zv::Val yesUnlessOnlyThisIsExplicit(bool explicitMixed, zend_object *type)
	{
		if (explicitMixed) {
			bool typeExplicit = false;
			if (UNEXPECTED(!isExplicitMixedOf(type, typeExplicit))) return zv::Val();
			if (typeExplicit) return pt_type_is_super_type_of_result(PT_TRI_YES);
			return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		}
		return pt_type_is_super_type_of_result(PT_TRI_YES);
	}

	/* $a->isSuperTypeOf($b)'s trinary; -1 = pending exception */
	[[nodiscard]] static zend_long isSuperTypeOfTrinary(zval *a, zval *b)
	{
		zv::Val result = pt_type_call(Z_OBJ_P(a), PT_LC("issupertypeof"), 1, b);
		if (UNEXPECTED(result.isUndef())) return -1;
		return pt_type_result_trinary(result.raw());
	}

	/* $this->subtractedType !== null && $this->subtractedType->isSuperTypeOf($probe)->yes();
	 * false = pending exception */
	[[nodiscard]] bool subtractedCovers(int probeClassIdx, bool &out) const
	{
		zv::Val probe = pt_type_new(probeClassIdx, 0, NULL);
		if (UNEXPECTED(probe.isUndef())) return false;
		return subtractedCoversProbe(std::move(probe), out);
	}

	/* the same for a shadowing probe class, through its exported constructor */
	bool subtractedCovers(bool (*construct)(zval *), bool &out) const
	{
		zval raw;
		if (UNEXPECTED(!construct(&raw))) return false;
		return subtractedCoversProbe(zv::Val::adopt(raw), out);
	}

	bool subtractedCoversProbe(zv::Val probe, bool &out) const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return false;
		if (Z_TYPE_P(subtracted) == IS_NULL) {
			out = false;
			return true;
		}
		zend_long isSuperType = isSuperTypeOfTrinary(subtracted, probe.raw());
		if (UNEXPECTED(isSuperType < 0)) return false;
		out = isSuperType == PT_TRI_YES;
		return true;
	}

	/* no when the subtracted type covers a `new Probe()`, maybe otherwise;
	 * -1 = pending exception */
	[[nodiscard]] zend_long noWhenSubtractedCovers(int probeClassIdx) const
	{
		bool covered;
		if (UNEXPECTED(!subtractedCovers(probeClassIdx, covered))) return -1;
		return covered ? PT_TRI_NO : PT_TRI_MAYBE;
	}

	zend_long noWhenSubtractedCovers(bool (*construct)(zval *)) const
	{
		bool covered;
		if (UNEXPECTED(!subtractedCovers(construct, covered))) return -1;
		return covered ? PT_TRI_NO : PT_TRI_MAYBE;
	}

	/* no when $subtracted->isSuperTypeOf($probe)->yes(), maybe otherwise;
	 * -1 = pending exception */
	[[nodiscard]] static zend_long noWhenSubtractedCoversProbe(zval *subtracted, zval *probe)
	{
		zend_long isSuperType = isSuperTypeOfTrinary(subtracted, probe);
		if (UNEXPECTED(isSuperType < 0)) return -1;
		return isSuperType == PT_TRI_YES ? PT_TRI_NO : PT_TRI_MAYBE;
	}

	zend_long noWhenSubtractedCoversConstantBoolean(bool value) const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return -1;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Val probe = constantBoolean(value);
			if (UNEXPECTED(probe.isUndef())) return -1;
			return noWhenSubtractedCoversProbe(subtracted, probe.raw());
		}
		return PT_TRI_MAYBE;
	}

	/* no when the subtracted type covers new IntersectionType([new
	 * StringType(), new Accessory()]) — the accessory through its shadowing
	 * class's exported constructor — maybe otherwise; -1 = pending
	 * exception */
	[[nodiscard]] zend_long noWhenSubtractedCoversAccessoryString(bool (*construct)(zval *)) const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return -1;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Arr types = zv::Arr::create(2);
			if (UNEXPECTED(!pushString(types) || !pushNew(types, construct))) return -1;
			zv::Val probe = intersectionOf(std::move(types));
			if (UNEXPECTED(probe.isUndef())) return -1;
			return noWhenSubtractedCoversProbe(subtracted, probe.raw());
		}
		return PT_TRI_MAYBE;
	}

	/* $this->isArray()->no() — through the object's class; false = pending
	 * exception */
	[[nodiscard]] bool thisIsArrayNo(bool &out) const
	{
		zend_long array = isExact() ? isArray() : pt_type_call_trinary(self, PT_LC("isarray"), 0, NULL);
		if (UNEXPECTED(array < 0)) return false;
		out = array == PT_TRI_NO;
		return true;
	}

	/* $this->isIterable() — through the object's class; -1 = pending
	 * exception */
	[[nodiscard]] zend_long thisIsIterable() const
	{
		if (EXPECTED(isExact())) return isIterable();
		return pt_type_call_trinary(self, PT_LC("isiterable"), 0, NULL);
	}

	static zv::Val errorType() { return pt_type_new_error_type(); }

	static zv::Val integerType()
	{
		return pt_val_of<pt_integer_type_new>();
	}

	static zv::Val stringType()
	{
		return pt_val_of<pt_string_type_new>();
	}

	static zv::Val booleanType()
	{
		return pt_val_of<pt_boolean_type_new>();
	}

	static zv::Val constantBoolean(bool value)
	{
		zval result;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&result, value))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* new ConstantArrayType([], []) */
	static zv::Val emptyConstantArray()
	{
		zval empty, result;
		ZVAL_EMPTY_ARRAY(&empty);
		if (UNEXPECTED(!pt_constant_array_type_new(&result, &empty, &empty))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* new <Shadowed>() through its exported constructor */
	static bool pushNew(zv::Arr &types, bool (*construct)(zval *))
	{
		zval raw;
		if (UNEXPECTED(!construct(&raw))) return false;
		types.push(zv::Val::adopt(raw));
		return true;
	}

	/* new AccessoryDecimalIntegerStringType() — the constructor's $inverse at
	 * its default */
	static bool decimalIntegerString(zval *out) { return pt_accessory_decimal_integer_string_type_new(out); }

	static bool pushInteger(zv::Arr &types)
	{
		zv::Val type = integerType();
		if (UNEXPECTED(type.isUndef())) return false;
		types.push(std::move(type));
		return true;
	}

	static bool pushString(zv::Arr &types)
	{
		zv::Val type = stringType();
		if (UNEXPECTED(type.isUndef())) return false;
		types.push(std::move(type));
		return true;
	}

	static bool pushConstantBoolean(zv::Arr &types, bool value)
	{
		zv::Val type = constantBoolean(value);
		if (UNEXPECTED(type.isUndef())) return false;
		types.push(std::move(type));
		return true;
	}

	/* new ArrayType($keyType, $valueType) — the shadowing class */
	static zv::Val arrayType(zv::Val keyType, zv::Val valueType)
	{
		if (UNEXPECTED(keyType.isUndef() || valueType.isUndef())) return zv::Val();
		zval result;
		if (UNEXPECTED(!pt_array_type_new(&result, keyType.raw(), valueType.raw()))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* new IntersectionType($types) */
	static zv::Val intersectionOf(zv::Arr types) { return pt_intersection_of(std::move(types)); }

	/* new IntersectionType([new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $valueType), new AccessoryArrayListType()]) */
	static zv::Val listOf(zv::Val valueType)
	{
		zval zero;
		ZVAL_LONG(&zero, 0);
		zv::Val array = arrayType(pt_integer_range_create_all_greater_than_or_equal_to(&zero), std::move(valueType));
		if (UNEXPECTED(array.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(array));
		if (UNEXPECTED(!pushNew(types, pt_accessory_array_list_type_new))) return zv::Val();
		return intersectionOf(std::move(types));
	}

	/* new ArrayType(new MixedType($e), new MixedType($e)) — two instances,
	 * as the twin spells it */
	static zv::Val mixedArray(bool explicitMixed)
	{
		return arrayType(create(explicitMixed), create(explicitMixed));
	}

	/* new ArrayType(new MixedType($this->isExplicitMixed), new MixedType($this->isExplicitMixed)) */
	zv::Val mixedArray() const
	{
		bool explicitMixed = false;
		if (UNEXPECTED(!isExplicitMixed(explicitMixed))) return zv::Val();
		return mixedArray(explicitMixed);
	}

	/* new ClassNameToObjectTypeResult($type, false) */
	static zv::Val classNameToObjectTypeResult(zv::Val type)
	{
		zv::Args args{type.raw(), false};
		return pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args);
	}

public:
	/* static fn (Type $type): Type => $type — a Closure over
	 * IdentityCallback::identity() (shared with the object family through
	 * pt_type_identity_callback()) */
	static zv::Val identityCallback()
	{
		zval closure;
		zend_create_closure(&closure, pt_identity_callback_fn, pt_ce_identity_callback, pt_ce_identity_callback, NULL);
		return zv::Val::adopt(closure);
	}
};

} // namespace phpstanturbo

using phpstanturbo::MixedType;
using phpstanturbo::NullableLong;

zv::Val pt_type_identity_callback()
{
	zval closure;
	zend_create_closure(&closure, pt_identity_callback_fn, pt_ce_identity_callback, pt_ce_identity_callback, NULL);
	return zv::Val::adopt(closure);
}

bool pt_mixed_type_new(zval *out, bool isExplicitMixed, zval *subtractedType)
{
	return pt_val_into(MixedType::create(isExplicitMixed, subtractedType), out);
}

void pt_mixed_type_construct(zend_object *self, bool isExplicitMixed, zval *subtractedType)
{
	MixedType(self).construct(isExplicitMixed, subtractedType);
}

zv::Val pt_mixed_type_describe(zend_object *self, zval *level)
{
	return MixedType(self).describe(level);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS MixedType(Z_OBJ_P(ZEND_THIS))

/* IdentityCallback::identity(Type $type): Type */
static void ZEND_FASTCALL identityCallbackIdentity(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	RETURN_COPY(type);
}

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL mtEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL mtNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL mtYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL mtYes1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_TRINARY(PT_TRI_YES);
}

/* new self($this->isExplicitMixed) */
static void ZEND_FASTCALL mtWithoutSubtractedType0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.withoutSubtractedType());
}

static void ZEND_FASTCALL mtWithoutSubtractedType1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(PT_THIS.withoutSubtractedType());
}

static void ZEND_FASTCALL mtWithoutSubtractedType2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	PT_RETURN_VAL(PT_THIS.withoutSubtractedType());
}

/* $this->isArray()->no() ? new ErrorType() : list<mixed> */
static void ZEND_FASTCALL mtListOfMixed0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.listOfMixed());
}

static void ZEND_FASTCALL mtListOfMixed2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	PT_RETURN_VAL(PT_THIS.listOfMixed());
}

/* $this->isArray()->no() ? new ErrorType() : array<mixed, mixed> */
static void ZEND_FASTCALL mtArrayOfMixed0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.arrayOfMixed());
}

static void ZEND_FASTCALL mtArrayOfMixed1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(PT_THIS.arrayOfMixed());
}

static void ZEND_FASTCALL mtArrayOfMixed3(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(3, 3);
	PT_RETURN_VAL(PT_THIS.arrayOfMixed());
}

/* $this */
static void ZEND_FASTCALL mtThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL mtThis1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

/* new ErrorType() */
static void ZEND_FASTCALL mtError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL mtError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

/* mapValueType() / mapKeyType(): (callable $cb) */
static void pt_mt_map(INTERNAL_FUNCTION_PARAMETERS, bool mapValue)
{
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_FUNC(fci, fcc)
	ZEND_PARSE_PARAMETERS_END();
	PT_RETURN_VAL(PT_THIS.mapType(&fci, &fcc, mapValue));
}

/* getProperty() & co.: (string $name, ClassMemberAccessAnswerer $scope) →
 * the transformed member of the prototype */
static void pt_mt_transformed_member(INTERNAL_FUNCTION_PARAMETERS, const char *prototypeLcname, size_t prototypeLen, bool isMethod)
{
	zval *name, *scope;
	if (!zp::parse<zp::Zval, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.transformedMember(prototypeLcname, prototypeLen, isMethod, name, scope));
}

/* getUnresolvedPropertyPrototype() & co.: (string $name, ClassMemberAccessAnswerer $scope) */
static void pt_mt_unresolved_prototype(INTERNAL_FUNCTION_PARAMETERS, bool isMethod)
{
	zend_string *name;
	zval *scope;
	if (!zp::parse<zp::Str, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	PT_RETURN_VAL(MixedType::unresolvedPrototype(isMethod, &nameZv));
}

/* the is*() family: no arguments, a TrinaryLogic */
static void pt_mt_trinary(INTERNAL_FUNCTION_PARAMETERS, zend_long (MixedType::*method)() const)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY_OR_THROW((PT_THIS.*method)());
}

void pt_register_mixed_type()
{
	/* the identity callback holder: registered under a builder name other
	 * than `cls` on purpose — the side-by-side parity scan pairs
	 * `cls.method(...)` lines with the twin's methods, and identity() has
	 * none */
	reg::Class holder("PHPStanTurbo\\IdentityCallback");
	holder.method("identity", reg::PublicStatic, 1, { reg::obj("type", ptcls::type) }, identityCallbackIdentity, &ptret::type);
	pt_ce_identity_callback = holder.register_();
	pt_ce_identity_callback->ce_flags |= ZEND_ACC_FINAL;
	pt_identity_callback_fn = (zend_function *) zend_hash_str_find_ptr(&pt_ce_identity_callback->function_table, PT_LC("identity"));
	ZEND_ASSERT(pt_identity_callback_fn != NULL);

	reg::Class cls("PHPStan\\Type\\MixedType");
	ptdecl::MixedType::declareClass(cls);
	/* "subtractedType" and "isExplicitMixed" must stay the first two
	 * declared properties (slots::subtractedType, slots::isExplicitMixed) */
	ptdecl::MixedType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool isExplicitMixed = false;
		zval *subtractedType = NULL;
		if (!zp::parse<zp::Opt<zp::Bool>, zp::Opt<zp::ObjOrNull>>(execute_data, isExplicitMixed, subtractedType)) RETURN_THROWS();
		PT_THIS.construct(isExplicitMixed, subtractedType);
	});

	cls.method(sigs::getReferencedClasses, mtEmptyArray0);
	cls.method(sigs::getObjectClassNames, mtEmptyArray0);
	cls.method(sigs::getObjectClassReflections, mtEmptyArray0);
	cls.method(sigs::getArrays, mtEmptyArray0);
	cls.method(sigs::getConstantArrays, mtEmptyArray0);
	cls.method(sigs::getConstantStrings, mtEmptyArray0);

	cls.method(sigs::accepts, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(MixedType::accepts());
	});

	cls.method(sigs::isSuperTypeOfMixed, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(type, pt_ce_mixed_type)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.isSuperTypeOfMixed(type));
	});

	cls.method<&MixedType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 3);
		PT_RETURN_VAL(PT_THIS.withoutSubtractedType());
	});
	cls.method(sigs::setExistingOffsetValueType, mtWithoutSubtractedType2);

	cls.method(sigs::unsetOffset, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.unsetOffset());
	});

	cls.method(sigs::getKeysArrayFiltered, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(PT_THIS.getKeysArrayFiltered());
	});

	cls.method<&MixedType::getKeysArray>(sigs::getKeysArray);

	cls.method(sigs::getValuesArray, mtListOfMixed0);
	cls.method(sigs::chunkArray, mtListOfMixed2);

	cls.method<&MixedType::fillKeysArray, zp::Obj>(sigs::fillKeysArray);

	cls.method(sigs::flipArray, mtArrayOfMixed0);
	cls.method(sigs::intersectKeyArray, mtArrayOfMixed1);
	cls.method(sigs::popArray, mtArrayOfMixed0);
	cls.method(sigs::reverseArray, mtArrayOfMixed1);

	cls.method(sigs::searchArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 2);
		PT_RETURN_VAL(PT_THIS.searchArray());
	});

	cls.method(sigs::shiftArray, mtArrayOfMixed0);
	cls.method(sigs::shuffleArray, mtListOfMixed0);
	cls.method(sigs::sliceArray, mtArrayOfMixed3);
	cls.method(sigs::spliceArray, mtArrayOfMixed3);

	cls.method(sigs::truncateListToSize, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.truncateListToSize());
	});

	cls.method(sigs::makeListMaybe, mtThis0);

	cls.method(sigs::mapValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_map(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
	});

	cls.method(sigs::mapKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_map(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});

	cls.method(sigs::makeAllArrayKeysOptional, mtThis0);
	cls.method(sigs::changeKeyCaseArray, mtArrayOfMixed1);
	cls.method(sigs::filterArrayRemovingFalsey, mtArrayOfMixed0);

	cls.method(sigs::isCallable, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isCallable);
	});

	cls.method(sigs::getEnumCases, mtEmptyArray0);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});

	cls.method(sigs::getCallableParametersAcceptors, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(MixedType::getCallableParametersAcceptors());
	});

	cls.method<&MixedType::equals, zp::Obj>(sigs::equals);

	cls.method<&MixedType::isSubTypeOf, zp::Obj>(sigs::isSubTypeOf);

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method(sigs::getTemplateType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(MixedType::getTemplateType());
	});

	cls.method(sigs::isObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isObject);
	});

	cls.method<&MixedType::getClassStringType>(sigs::getClassStringType);

	cls.method(sigs::isEnum, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isObject);
	});

	cls.method(sigs::canAccessProperties, mtYes0);
	cls.method(sigs::hasProperty, mtYes1);
	cls.method(sigs::getProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedpropertyprototype"), false);
	});
	cls.method(sigs::getUnresolvedPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});
	cls.method(sigs::hasInstanceProperty, mtYes1);
	cls.method(sigs::getInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedinstancepropertyprototype"), false);
	});
	cls.method(sigs::getUnresolvedInstancePropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});
	cls.method(sigs::hasStaticProperty, mtYes1);
	cls.method(sigs::getStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedstaticpropertyprototype"), false);
	});
	cls.method(sigs::getUnresolvedStaticPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});
	cls.method(sigs::canCallMethods, mtYes0);
	cls.method(sigs::hasMethod, mtYes1);
	cls.method(sigs::getMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedmethodprototype"), true);
	});
	cls.method(sigs::getUnresolvedMethodPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
	});
	cls.method(sigs::canAccessConstants, mtYes0);
	cls.method(sigs::hasConstant, mtYes1);

	cls.method(sigs::getConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *constantName;
		if (!zp::parse<zp::Str>(execute_data, constantName)) RETURN_THROWS();
		zval nameZv;
		ZVAL_STR(&nameZv, constantName);
		PT_RETURN_VAL(MixedType::getConstant(&nameZv));
	});

	cls.method(sigs::isCloneable, mtYes0);

	cls.method<&MixedType::describe, zp::Obj>(sigs::describe);

	cls.method<&MixedType::toBoolean>(sigs::toBoolean);

	cls.method<&MixedType::toNumber>(sigs::toNumber);

	cls.method(sigs::toBitwiseNotType, mtError0);

	cls.method<&MixedType::toGetClassResultType>(sigs::toGetClassResultType);

	cls.method(sigs::toClassConstantType, mtError1);

	cls.method<&MixedType::toObjectTypeForInstanceofCheck>(sigs::toObjectTypeForInstanceofCheck);

	cls.method(sigs::toObjectTypeForIsACheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objectOrClassType;
		bool allowString, allowSameClass;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Bool>(execute_data, objectOrClassType, allowString, allowSameClass)) RETURN_THROWS();
		PT_RETURN_VAL(MixedType::toObjectTypeForIsACheck(allowString));
	});

	cls.method<&MixedType::toAbsoluteNumber>(sigs::toAbsoluteNumber);

	cls.method<&MixedType::toInteger>(sigs::toInteger);

	cls.method<&MixedType::toFloat>(sigs::toFloat);

	cls.method<&MixedType::toString>(sigs::toString);

	cls.method<&MixedType::toArray>(sigs::toArray);

	cls.method<&MixedType::toArrayKey>(sigs::toArrayKey);

	cls.method(sigs::toCoercedArgumentType, mtThis1);

	cls.method(sigs::isIterable, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isIterable);
	});

	cls.method(sigs::isIterableAtLeastOnce, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isIterableAtLeastOnce);
	});

	cls.method<&MixedType::getArraySize>(sigs::getArraySize);

	cls.method(sigs::getIterableKeyType, mtWithoutSubtractedType0);
	cls.method(sigs::getFirstIterableKeyType, mtWithoutSubtractedType0);
	cls.method(sigs::getLastIterableKeyType, mtWithoutSubtractedType0);
	cls.method(sigs::getIterableValueType, mtWithoutSubtractedType0);
	cls.method(sigs::getFirstIterableValueType, mtWithoutSubtractedType0);
	cls.method(sigs::getLastIterableValueType, mtWithoutSubtractedType0);

	cls.method(sigs::isOffsetAccessible, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isOffsetAccessible);
	});

	cls.method(sigs::isOffsetAccessLegal, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isOffsetAccessLegal);
	});

	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.hasOffsetValueType());
	});

	cls.method(sigs::getOffsetValueType, mtWithoutSubtractedType1);

	cls.method<&MixedType::isExplicitMixed>(sigs::isExplicitMixed);

	cls.method<&MixedType::subtract, zp::Obj>(sigs::subtract);

	cls.method(sigs::getTypeWithoutSubtractedType, mtWithoutSubtractedType0);

	cls.method<&MixedType::changeSubtractedType, zp::ObjOrNull>(sigs::changeSubtractedType);

	cls.method(sigs::getSubtractedType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *subtracted = PT_THIS.subtractedType();
		if (UNEXPECTED(subtracted == NULL)) RETURN_THROWS();
		RETURN_COPY(subtracted);
	});

	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
	});

	cls.method(sigs::isArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isArray);
	});
	cls.method(sigs::isConstantArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isConstantArray);
	});
	cls.method(sigs::isOversizedArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isOversizedArray);
	});
	cls.method(sigs::isList, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isList);
	});
	cls.method(sigs::isNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isNull);
	});
	cls.method(sigs::isConstantValue, mtNo0);
	cls.method(sigs::isConstantScalarValue, mtNo0);
	cls.method(sigs::getConstantScalarTypes, mtEmptyArray0);
	cls.method(sigs::getConstantScalarValues, mtEmptyArray0);
	cls.method(sigs::isTrue, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isTrue);
	});
	cls.method(sigs::isFalse, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isFalse);
	});
	cls.method(sigs::isBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isBoolean);
	});
	cls.method(sigs::isFloat, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isFloat);
	});
	cls.method(sigs::isInteger, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isInteger);
	});
	cls.method(sigs::isString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isString);
	});
	cls.method(sigs::isNumericString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isNumericString);
	});
	cls.method(sigs::isDecimalIntegerString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isDecimalIntegerString);
	});
	cls.method(sigs::isNonEmptyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isNonEmptyString);
	});
	cls.method(sigs::isNonFalsyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isNonFalsyString);
	});
	cls.method(sigs::isLiteralString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isLiteralString);
	});
	cls.method(sigs::isLowercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isLowercaseString);
	});
	cls.method(sigs::isUppercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isUppercaseString);
	});
	cls.method(sigs::isClassString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isClassString);
	});

	cls.method<&MixedType::getClassStringObjectType>(sigs::getClassStringObjectType);

	cls.method<&MixedType::getObjectTypeOrClassStringObjectType>(sigs::getObjectTypeOrClassStringObjectType);

	cls.method(sigs::isVoid, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isVoid);
	});
	cls.method(sigs::isScalar, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_mt_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &MixedType::isScalar);
	});

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(MixedType::looseCompare());
	});

	cls.method<&MixedType::tryRemove, zp::Obj>(sigs::tryRemove);

	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(MixedType::exponentiate());
	});

	cls.method(sigs::getFiniteTypes, mtEmptyArray0);

	cls.method<&MixedType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares (isCallable, getCallableParametersAcceptors,
	 * ...) */
	ptdecl::MixedType::registerTraits(cls);

	cls.shadow(&pt_ce_mixed_type);
}

/* }}} */

/* {{{ shared with the object family (TypeTraits.h) */

/* }}} */
