/*
 * PHPStanTurbo\CallableType — native implementation of PHPStan\Type\CallableType.
 *
 * Declared as PHPStan\Type\CallableType itself at activation: not final (an
 * extension's PHP class may extend it — its constructor would call
 * parent::__construct(), so the constructor is a proper method),
 * implementing PHPStan\Type\CompoundType and
 * PHPStan\Reflection\Callables\CallableParametersAcceptor. State is the
 * twin's private properties, declared typed property slots in the twin's
 * declaration order (the class-body properties first, the promoted
 * constructor properties after them), so the std object handlers do
 * GC/clone and a subclass's own properties follow them.
 *
 * Every `$this->method()` the twin makes (getParameters(), getReturnType(),
 * isVariadic(), isPure(), isSubTypeOf()) goes through the object's class
 * entry — a subclass may have overridden it — with a direct C++ call when
 * the object's method is the native one. The private
 * isSuperTypeOfInternal() and inferTemplateTypesOnParametersAcceptor() are
 * direct C++ calls, as PHP never dispatches them; the bodies the twin
 * shares verbatim with ClosureType (the DummyParameter list of describe(),
 * the CallableTypeNode of toPhpDocNode(), the template inference and
 * traversal of the parameters) are the pt_callable_* helpers in
 * TypeTraits.cpp. The private slots of another CallableType
 * (`$type->parameters`, ...) are read directly, as the twin does from
 * inside the class.
 *
 * Not mirrored verbatim: the twin's function-static `$scope` of
 * isSuperTypeOfInternal() (one OutOfClassScope kept for the process) is a
 * fresh OutOfClassScope per call — the scope has no state, so the answers
 * are identical.
 */

#include "TypeTraits.h"
#include "generated/CallableType.h"

namespace slots = ptdecl::CallableType::slot;
namespace sigs = ptdecl::CallableType::sig;

zend_class_entry *pt_ce_callable_type = nullptr;

/* the handlers a $this-call is checked against before the direct path */
static void ZEND_FASTCALL ctGetParameters(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL ctGetReturnType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL ctIsVariadic(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL ctIsPure(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL ctIsSubTypeOf(INTERNAL_FUNCTION_PARAMETERS);

namespace phpstanturbo {

/* Mirrors PHPStan\Type\CallableType. State lives in the PHP object's slots. */
class CallableType
{
public:
	explicit CallableType(zend_object *self) : self(self) {}

	/* __construct(?array $parameters = null, ?Type $returnType = null, bool
	 * $variadic = true, ?TemplateTypeMap $templateTypeMap = null,
	 * ?TemplateTypeMap $resolvedTemplateTypeMap = null, array $templateTags
	 * = [], ?TrinaryLogic $isPure = null, ?Assertions $assertions = null);
	 * every zval borrowed, NULL for null (a NULL $templateTags is the
	 * default []) */
	void construct(zval *parameters, zval *returnType, bool variadic, zval *templateTypeMap, zval *resolvedTemplateTypeMap, zval *templateTags, zval *isPure, zval *assertions)
	{
		/* the promoted properties are assigned before the body runs */
		zval boolean = {};
		ZVAL_BOOL(&boolean, variadic);
		writeSlot(slots::variadic, &boolean);
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		writeSlot(slots::templateTags, templateTags != NULL ? templateTags : &emptyArray);

		/* $this->parameters = $parameters ?? [] */
		writeSlot(slots::parameters, parameters != NULL ? parameters : &emptyArray);
		/* $this->returnType = $returnType ?? new MixedType() */
		if (returnType != NULL) {
			writeSlot(slots::returnType, returnType);
		} else {
			zv::Val mixed = pt_type_new_mixed_type();
			if (UNEXPECTED(mixed.isUndef())) return;
			writeSlot(slots::returnType, mixed.raw());
		}
		ZVAL_BOOL(&boolean, parameters == NULL && returnType == NULL);
		writeSlot(slots::isCommonCallable, &boolean);
		if (UNEXPECTED(!writeOrDefault(slots::templateTypeMap, templateTypeMap, pt_callable_template_type_map_empty))) return;
		if (UNEXPECTED(!writeOrDefault(slots::resolvedTemplateTypeMap, resolvedTemplateTypeMap, pt_callable_template_type_map_empty))) return;
		writeSlot(slots::isPure, isPure != NULL ? isPure : pt_trinary_singleton(PT_TRI_MAYBE));
		if (UNEXPECTED(!writeOrDefault(slots::assertions, assertions, pt_callable_assertions_empty))) return;
	}

	/* new self(...) — exactly the class, as the twin's `new self` sites spell
	 * it; UNDEF = pending exception */
	static zv::Val create(zval *parameters, zval *returnType, bool variadic, zval *templateTypeMap, zval *resolvedTemplateTypeMap, zval *templateTags, zval *isPure, zval *assertions)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_callable_type) != SUCCESS)) return zv::Val();
		CallableType(Z_OBJ(object)).construct(parameters, returnType, variadic, templateTypeMap, resolvedTemplateTypeMap, templateTags, isPure, assertions);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

	/* the slots (borrowed); NULL with an Error pending when the constructor
	 * never ran */
	[[nodiscard]] zval *parameters() const { return slot(self, slots::parameters, "parameters"); }
	zval *returnType() const { return slot(self, slots::returnType, "returnType"); }
	zval *isCommonCallableSlot() const { return slot(self, slots::isCommonCallable, "isCommonCallable"); }
	zval *templateTypeMap() const { return slot(self, slots::templateTypeMap, "templateTypeMap"); }
	zval *resolvedTemplateTypeMap() const { return slot(self, slots::resolvedTemplateTypeMap, "resolvedTemplateTypeMap"); }
	zval *isPureSlot() const { return slot(self, slots::isPure, "isPure"); }
	zval *assertions() const { return slot(self, slots::assertions, "assertions"); }
	zval *variadicSlot() const { return slot(self, slots::variadic, "variadic"); }
	zval *templateTags() const { return slot(self, slots::templateTags, "templateTags"); }

	static zval *slot(zend_object *object, uint32_t index, const char *name) { return pt_typed_slot(object, index, pt_ce_callable_type, name); }

	/* a slot's value as an owned copy; UNDEF = pending exception */
	zv::Val slotValue(zval *p) const
	{
		if (UNEXPECTED(p == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(p));
	}

	/* $this->isCommonCallable; -1 with an Error pending */
	int isCommonCallable() const
	{
		zval *p = isCommonCallableSlot();
		if (UNEXPECTED(p == NULL)) return -1;
		return zend_is_true(p) ? 1 : 0;
	}

	/* the getReferencedClasses() body over an empty $classes */
	zv::Val getReferencedClasses() const
	{
		zval *p = parameters();
		zval *a = p != NULL ? assertions() : NULL;
		zval *r = a != NULL ? returnType() : NULL;
		if (UNEXPECTED(r == NULL)) return zv::Val();
		return pt_callable_referenced_classes(zv::Arr::create(0), p, a, r);
	}

	/* the CompoundType callback for a compound type that is no
	 * CallableType; else isSuperTypeOfInternal() treating mixed as any, as
	 * an AcceptsResult; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool compound;
		if (UNEXPECTED(!compoundButNotSelf(type, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		zv::Val result = isSuperTypeOfInternal(type, true, strictTypes);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(result.raw()), PT_LC("toacceptsresult"), 0, NULL);
	}

	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!compoundButNotSelf(type, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);
		}
		return isSuperTypeOfInternal(type, false, true);
	}

	/* no for a non-callable; for a common callable its callability held to
	 * the variants' purity when pure; else the variants selected for the
	 * own parameter types compared through CallableTypeHelper, or-ed;
	 * UNDEF = pending exception */
	zv::Val isSuperTypeOfInternal(zval *type, bool treatMixedAsAny, bool strictTypes) const
	{
		zv::Val callable = pt_type_op(Z_OBJ_P(type), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(callable.isUndef())) return zv::Val();
		zv::Val isCallable = pt_callable_is_super_type_of_result_of(callable.raw());
		if (UNEXPECTED(isCallable.isUndef())) return zv::Val();
		zend_long callableValue = pt_type_result_trinary(isCallable.raw());
		if (UNEXPECTED(callableValue < 0)) return zv::Val();
		if (callableValue == PT_TRI_NO) return isCallable;
		zv::Val scope = pt_callable_out_of_class_scope();
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		int common = isCommonCallable();
		if (UNEXPECTED(common < 0)) return zv::Val();
		if (common == 1) {
			zend_long pure = thisIsPure();
			if (UNEXPECTED(pure < 0)) return zv::Val();
			if (pure == PT_TRI_YES) {
				zv::Val variants = acceptorsOf(type, scope.raw());
				if (UNEXPECTED(variants.isUndef())) return zv::Val();
				zend_long typePure = PT_TRI_YES;
				for (zv::ArrayEntry entry : zv::ArrRef(variants.raw())) {
					if (UNEXPECTED(!entry.value().isObject())) {
						zend_type_error("phpstan_turbo: a callable parameters acceptor must be an object");
						return zv::Val();
					}
					zend_long variantPure = pt_type_call_trinary(entry.value().asObject(), PT_LC("ispure"), 0, NULL);
					if (UNEXPECTED(variantPure < 0)) return zv::Val();
					/* TrinaryLogic::and(): the minimum */
					typePure = variantPure < typePure ? variantPure : typePure;
				}
				zv::Val pureResult = pt_type_new_is_super_type_of_result(typePure);
				if (UNEXPECTED(pureResult.isUndef())) return zv::Val();
				return pt_type_op(Z_OBJ_P(isCallable.raw()), PT_OP_AND, 1, pureResult.raw());
			}
			return isCallable;
		}
		zv::Val parameters = thisParameters();
		if (UNEXPECTED(parameters.isUndef())) return zv::Val();
		zv::Val parameterTypes = pt_callable_parameter_types(parameters.raw());
		if (UNEXPECTED(parameterTypes.isUndef())) return zv::Val();
		zv::Val variants = acceptorsOf(type, scope.raw());
		if (UNEXPECTED(variants.isUndef())) return zv::Val();
		zv::Val variantsResult;
		for (zv::ArrayEntry entry : zv::ArrRef(variants.raw())) {
			/* $variant = ParametersAcceptorSelector::selectFromTypes($parameterTypes, [$variant], false) */
			zv::Arr single = zv::Arr::create(1);
			single.push(entry.value());
			zv::Val variant = pt_parameters_acceptor_selector_select_from_types(parameterTypes.raw(), single.raw(), false);
			if (UNEXPECTED(variant.isUndef())) return zv::Val();
			bool isCallableAcceptor;
			if (UNEXPECTED(!pt_type_instanceof(variant.raw(), PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR, isCallableAcceptor))) return zv::Val();
			if (!isCallableAcceptor) return pt_type_is_super_type_of_result(PT_TRI_NO);
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			zv::Val isSuperType = pt_callable_type_helper_is_parameters_acceptor_super_type_of(&selfZv, variant.raw(), treatMixedAsAny, strictTypes);
			if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
			if (variantsResult.isUndef()) {
				variantsResult = std::move(isSuperType);
			} else {
				if (UNEXPECTED(!zv::Ref(variantsResult.raw()).isObject())) {
					zend_type_error("phpstan_turbo: isParametersAcceptorSuperTypeOf() must return an object");
					return zv::Val();
				}
				variantsResult = pt_type_call(Z_OBJ_P(variantsResult.raw()), PT_LC("or"), 1, isSuperType.raw());
				if (UNEXPECTED(variantsResult.isUndef())) return zv::Val();
			}
		}
		if (variantsResult.isUndef()) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		return pt_type_op(Z_OBJ_P(isCallable.raw()), PT_OP_AND, 1, variantsResult.raw());
	}

	/* a union or intersection's answer over $this; else the other type's
	 * callability held to maybe unless it is a CallableType; UNDEF =
	 * pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		bool isIntersection, isUnion;
		if (UNEXPECTED(!pt_type_instanceof_ce(otherType, pt_ce_intersection_type, isIntersection) || !pt_type_instanceof_ce(otherType, pt_ce_union_type, isUnion))) {
			return zv::Val();
		}
		if (isIntersection || isUnion) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(otherType), PT_OP_IS_SUPER_TYPE_OF, 1, &selfZv);
		}
		zend_long callable = pt_type_op_trinary(Z_OBJ_P(otherType), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(callable < 0)) return zv::Val();
		/* ->and($otherType instanceof self ? yes : maybe): the minimum */
		zend_long limit = instanceof_function(Z_OBJCE_P(otherType), pt_ce_callable_type) ? PT_TRI_YES : PT_TRI_MAYBE;
		return pt_type_new_is_super_type_of_result(callable < limit ? callable : limit);
	}

	/* $this->isSubTypeOf($acceptingType)->toAcceptsResult() */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		zv::Val result = thisIsSubTypeOf(acceptingType);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: isSubTypeOf() must return an object");
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(result.raw()), PT_LC("toacceptsresult"), 0, NULL);
	}

	/* another CallableType of the same commonness, variadicity, purity
	 * instance, equal return types, assertions, parameters (their
	 * optionality, by-reference, variadicity, types and default values —
	 * the first default value decides, as the twin returns on it),
	 * template maps and template tags; false with an exception pending */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_callable_type)) {
			out = false;
			return true;
		}
		zend_object *other = Z_OBJ_P(type);
		int common = isCommonCallable();
		if (UNEXPECTED(common < 0)) return false;
		zval *otherCommonSlot = slot(other, slots::isCommonCallable, "isCommonCallable");
		if (UNEXPECTED(otherCommonSlot == NULL)) return false;
		if ((common == 1) != zend_is_true(otherCommonSlot)) {
			out = false;
			return true;
		}
		zval *variadic = variadicSlot();
		zval *otherVariadic = variadic != NULL ? slot(other, slots::variadic, "variadic") : NULL;
		if (UNEXPECTED(otherVariadic == NULL)) return false;
		if (zend_is_true(variadic) != zend_is_true(otherVariadic)) {
			out = false;
			return true;
		}
		/* $this->isPure !== $type->isPure — instance identity */
		zval *pure = isPureSlot();
		zval *otherPure = pure != NULL ? slot(other, slots::isPure, "isPure") : NULL;
		if (UNEXPECTED(otherPure == NULL)) return false;
		if (Z_TYPE_P(pure) != Z_TYPE_P(otherPure) || (Z_TYPE_P(pure) == IS_OBJECT && Z_OBJ_P(pure) != Z_OBJ_P(otherPure))) {
			out = false;
			return true;
		}
		zval *r = returnType();
		zval *otherReturnType = r != NULL ? slot(other, slots::returnType, "returnType") : NULL;
		if (UNEXPECTED(otherReturnType == NULL)) return false;
		int returnTypesEqual = typesEqual(r, otherReturnType);
		if (UNEXPECTED(returnTypesEqual < 0)) return false;
		if (returnTypesEqual == 0) {
			out = false;
			return true;
		}
		/* CallableAssertionsHelper::assertionsEqual($this->assertions, $type->assertions) */
		zval *a = assertions();
		zval *otherAssertions = a != NULL ? slot(other, slots::assertions, "assertions") : NULL;
		if (UNEXPECTED(otherAssertions == NULL)) return false;
		zv::Args assertArgs{a, otherAssertions};
		zv::Val assertionsEqual = pt_type_call_static(PT_CLASS_CALLABLE_ASSERTIONS_HELPER, PT_LC("assertionsequal"), 2, assertArgs);
		if (UNEXPECTED(assertionsEqual.isUndef())) return false;
		if (!zend_is_true(assertionsEqual.raw())) {
			out = false;
			return true;
		}
		zval *p = parameters();
		zval *otherParameters = p != NULL ? slot(other, slots::parameters, "parameters") : NULL;
		if (UNEXPECTED(otherParameters == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(p) != IS_ARRAY || Z_TYPE_P(otherParameters) != IS_ARRAY)) {
			zend_type_error("phpstan_turbo: %s::$parameters must be array", ZSTR_VAL(pt_ce_callable_type->name));
			return false;
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(p)) != zend_hash_num_elements(Z_ARRVAL_P(otherParameters))) {
			out = false;
			return true;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(p)) {
			int decided = parameterEquals(entry, otherParameters, out);
			if (UNEXPECTED(decided < 0)) return false;
			if (decided == 1) return true;
		}
		zval *ownMaps[2] = { templateTypeMap(), resolvedTemplateTypeMap() };
		zval *otherMaps[2] = { slot(other, slots::templateTypeMap, "templateTypeMap"), slot(other, slots::resolvedTemplateTypeMap, "resolvedTemplateTypeMap") };
		if (UNEXPECTED(ownMaps[0] == NULL || ownMaps[1] == NULL || otherMaps[0] == NULL || otherMaps[1] == NULL)) return false;
		for (int i = 0; i < 2; i++) {
			int decided = templateTypeMapsEqual(ownMaps[i], otherMaps[i], out);
			if (UNEXPECTED(decided < 0)) return false;
			if (decided == 1) return true;
		}
		zval *tags = templateTags();
		zval *otherTags = tags != NULL ? slot(other, slots::templateTags, "templateTags") : NULL;
		if (UNEXPECTED(otherTags == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(tags) != IS_ARRAY || Z_TYPE_P(otherTags) != IS_ARRAY)) {
			zend_type_error("phpstan_turbo: %s::$templateTags must be array", ZSTR_VAL(pt_ce_callable_type->name));
			return false;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(tags)) {
			int decided = templateTagEquals(entry, otherTags, out);
			if (UNEXPECTED(decided < 0)) return false;
			if (decided == 1) return true;
		}
		out = true;
		return true;
	}

	/* 'callable' at the type-only level; the printed PHPDoc node of a copy
	 * whose parameters lost their names (unless an assertion refers to
	 * them) otherwise; UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		pt_verbosity_case which;
		if (UNEXPECTED(!pt_type_verbosity_case(level, which))) return zv::Val();
		if (which == PT_VERBOSITY_TYPE_ONLY) return zv::Val::string("callable", sizeof("callable") - 1);
		zv::Val printer = pt_type_new(PT_CLASS_PHPDOC_PRINTER, 0, NULL);
		if (UNEXPECTED(printer.isUndef())) return zv::Val();
		zval *a = assertions();
		zval *p = a != NULL ? parameters() : NULL;
		if (UNEXPECTED(p == NULL)) return zv::Val();
		zv::Val dummies = pt_callable_dummy_parameters(p, a);
		if (UNEXPECTED(dummies.isUndef())) return zv::Val();
		zval *r = returnType();
		zval *variadic = r != NULL ? variadicSlot() : NULL;
		zval *map = variadic != NULL ? templateTypeMap() : NULL;
		zval *resolved = map != NULL ? resolvedTemplateTypeMap() : NULL;
		zval *tags = resolved != NULL ? templateTags() : NULL;
		zval *pure = tags != NULL ? isPureSlot() : NULL;
		if (UNEXPECTED(pure == NULL)) return zv::Val();
		zv::Val selfWithoutParameterNames = create(dummies.raw(), r, zend_is_true(variadic), map, resolved, tags, pure, a);
		if (UNEXPECTED(selfWithoutParameterNames.isUndef())) return zv::Val();
		zv::Val node = CallableType(Z_OBJ_P(selfWithoutParameterNames.raw())).toPhpDocNode();
		if (UNEXPECTED(node.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(printer.raw()), PT_LC("print"), 1, node.raw());
	}

	/* [SimpleThrowPoint::createImplicit()] */
	static zv::Val getThrowPoints()
	{
		zv::Val implicit = pt_type_call_static(PT_CLASS_SIMPLE_THROW_POINT, PT_LC("createimplicit"), 0, NULL);
		if (UNEXPECTED(implicit.isUndef())) return zv::Val();
		zv::Arr points = zv::Arr::create(1);
		points.push(std::move(implicit));
		return zv::Val(std::move(points));
	}

	/* none when pure, one 'functionCall' point certain when impure */
	zv::Val getImpurePoints() const
	{
		zend_long pure = thisIsPure();
		if (UNEXPECTED(pure < 0)) return zv::Val();
		if (pure == PT_TRI_YES) return zv::Val(zv::Arr::empty());
		zv::Val point = pt_callable_new_simple_impure_point(PT_LC("functionCall"), PT_LC("call to a callable"), pure == PT_TRI_NO);
		if (UNEXPECTED(point.isUndef())) return zv::Val();
		zv::Arr points = zv::Arr::create(1);
		points.push(std::move(point));
		return zv::Val(std::move(points));
	}

	/* new ArrayType(new MixedType(), new MixedType()) */
	static zv::Val toArray()
	{
		zv::Val key = pt_type_new_mixed_type();
		if (UNEXPECTED(key.isUndef())) return zv::Val();
		zv::Val item = pt_type_new_mixed_type();
		if (UNEXPECTED(item.isUndef())) return zv::Val();
		zval raw;
		if (UNEXPECTED(!pt_array_type_new(&raw, key.raw(), item.raw()))) return zv::Val();
		return zv::Val::adopt(raw);
	}

	/* TypeCombinator::union($this, non-empty-string, array<mixed, mixed>
	 * (explicit), Closure) */
	zv::Val toCoercedArgumentType() const
	{
		zv::Val nonEmptyString = pt_type_new_string_with_accessory(pt_accessory_non_empty_string_type_new);
		if (UNEXPECTED(nonEmptyString.isUndef())) return zv::Val();
		zval keyRaw, itemRaw;
		if (UNEXPECTED(!pt_mixed_type_new(&keyRaw, true))) return zv::Val();
		zv::Val key = zv::Val::adopt(keyRaw);
		if (UNEXPECTED(!pt_mixed_type_new(&itemRaw, true))) return zv::Val();
		zv::Val item = zv::Val::adopt(itemRaw);
		zval arrayRaw;
		if (UNEXPECTED(!pt_array_type_new(&arrayRaw, key.raw(), item.raw()))) return zv::Val();
		zv::Val array = zv::Val::adopt(arrayRaw);
		zend_string *closureName = zend_string_init("Closure", sizeof("Closure") - 1, 0);
		zval closureRaw;
		bool created = pt_object_type_new(&closureRaw, closureName);
		zend_string_release(closureName);
		if (UNEXPECTED(!created)) return zv::Val();
		zv::Val closure = zv::Val::adopt(closureRaw);
		zv::Args args{self, nonEmptyString.raw(), array.raw(), closure.raw()};
		return pt_type_combinator_call(PT_LC("union"), 4, args);
	}

	/* the union or intersection's inferTemplateTypesOn($this); nothing for
	 * a non-callable; else the inference over each of its acceptors;
	 * UNDEF = pending exception */
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
		zend_long callable = pt_type_op_trinary(Z_OBJ_P(receivedType), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(callable < 0)) return zv::Val();
		if (callable != PT_TRI_YES) return pt_callable_template_type_map_empty();
		zv::Val scope = pt_callable_out_of_class_scope();
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zv::Val acceptors = acceptorsOf(receivedType, scope.raw());
		if (UNEXPECTED(acceptors.isUndef())) return zv::Val();
		return pt_callable_infer_template_types_on_acceptors(self, getParametersOf, getReturnTypeOf, acceptors.raw());
	}

	zv::Val getReferencedTemplateTypes(zval *positionVariance) const
	{
		zval *a = assertions();
		if (UNEXPECTED(a == NULL)) return zv::Val();
		return pt_callable_referenced_template_types(self, getReturnTypeOf, getParametersOf, a, positionVariance);
	}

	/* $this for a common callable; else new self over the traversed
	 * parameters, return type and assertions; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		int common = isCommonCallable();
		if (UNEXPECTED(common < 0)) return zv::Val();
		if (common == 1) return thisValue();
		zv::Val parameters = thisParameters();
		if (UNEXPECTED(parameters.isUndef())) return zv::Val();
		zv::Val traversedParameters = pt_callable_traverse_parameters(parameters.raw(), fci, fcc);
		if (UNEXPECTED(traversedParameters.isUndef())) return zv::Val();
		zv::Val returnType = thisReturnType();
		if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		zval traversedReturnTypeRaw;
		if (UNEXPECTED(!pt_call_type_fci(fci, fcc, 1, returnType.raw(), &traversedReturnTypeRaw))) return zv::Val();
		zv::Val traversedReturnType = zv::Val::adopt(traversedReturnTypeRaw);
		int variadic = thisIsVariadic();
		if (UNEXPECTED(variadic < 0)) return zv::Val();
		zval *map = templateTypeMap();
		zval *resolved = map != NULL ? resolvedTemplateTypeMap() : NULL;
		zval *tags = resolved != NULL ? templateTags() : NULL;
		zval *pure = tags != NULL ? isPureSlot() : NULL;
		zval *a = pure != NULL ? assertions() : NULL;
		if (UNEXPECTED(a == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(a) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: %s::$assertions must be an object", ZSTR_VAL(pt_ce_callable_type->name));
			return zv::Val();
		}
		zv::Val mappedAssertions = pt_assertions_map_types(a, &fci->function_name);
		if (UNEXPECTED(mappedAssertions.isUndef())) return zv::Val();
		return create(traversedParameters.raw(), traversedReturnType.raw(), variadic == 1, map, resolved, tags, pure, mappedAssertions.raw());
	}

	/* $this for a common callable, a non-callable $right, one with other
	 * than one acceptor or another parameter count; else new self over the
	 * parameters and return types traversed pairwise; UNDEF = pending
	 * exception */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		int common = isCommonCallable();
		if (UNEXPECTED(common < 0)) return zv::Val();
		if (common == 1) return thisValue();
		zend_long callable = pt_type_op_trinary(Z_OBJ_P(right), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(callable < 0)) return zv::Val();
		if (callable != PT_TRI_YES) return thisValue();
		zv::Val scope = pt_callable_out_of_class_scope();
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zv::Val rightAcceptors = acceptorsOf(right, scope.raw());
		if (UNEXPECTED(rightAcceptors.isUndef())) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(rightAcceptors.raw())) != 1) return thisValue();
		zval *rightAcceptor = zend_hash_index_find(Z_ARRVAL_P(rightAcceptors.raw()), 0);
		if (UNEXPECTED(rightAcceptor == NULL || Z_TYPE_P(rightAcceptor) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getParameters() on %s", rightAcceptor == NULL ? "null" : zend_zval_value_name(rightAcceptor));
			return zv::Val();
		}
		zv::Val rightParameters = pt_type_call(Z_OBJ_P(rightAcceptor), PT_LC("getparameters"), 0, NULL);
		if (UNEXPECTED(rightParameters.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(rightParameters.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getParameters() must return an array");
			return zv::Val();
		}
		zv::Val parameters = thisParameters();
		if (UNEXPECTED(parameters.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(parameters.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getParameters() must return an array");
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(parameters.raw())) != zend_hash_num_elements(Z_ARRVAL_P(rightParameters.raw()))) return thisValue();
		zv::Val ownParameters = thisParameters();
		if (UNEXPECTED(ownParameters.isUndef())) return zv::Val();
		zv::Val traversedParameters = pt_callable_traverse_parameters_simultaneously(ownParameters.raw(), rightParameters.raw(), fci, fcc);
		if (UNEXPECTED(traversedParameters.isUndef())) return zv::Val();
		zv::Val returnType = thisReturnType();
		if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		zv::Val rightReturnType = pt_type_call(Z_OBJ_P(rightAcceptor), PT_LC("getreturntype"), 0, NULL);
		if (UNEXPECTED(rightReturnType.isUndef())) return zv::Val();
		zv::Args args{returnType.raw(), rightReturnType.raw()};
		zval traversedReturnTypeRaw;
		if (UNEXPECTED(!pt_call_type_fci(fci, fcc, 2, args, &traversedReturnTypeRaw))) return zv::Val();
		zv::Val traversedReturnType = zv::Val::adopt(traversedReturnTypeRaw);
		int variadic = thisIsVariadic();
		if (UNEXPECTED(variadic < 0)) return zv::Val();
		zval *map = templateTypeMap();
		zval *resolved = map != NULL ? resolvedTemplateTypeMap() : NULL;
		zval *tags = resolved != NULL ? templateTags() : NULL;
		zval *pure = tags != NULL ? isPureSlot() : NULL;
		zval *a = pure != NULL ? assertions() : NULL;
		if (UNEXPECTED(a == NULL)) return zv::Val();
		return create(traversedParameters.raw(), traversedReturnType.raw(), variadic == 1, map, resolved, tags, pure, a);
	}

	/* IdentifierTypeNode('callable' / 'pure-callable') for a common
	 * callable; the CallableTypeNode over the parameters, template tags and
	 * (conditional) return type otherwise; UNDEF = pending exception */
	zv::Val toPhpDocNode() const
	{
		int common = isCommonCallable();
		if (UNEXPECTED(common < 0)) return zv::Val();
		if (common == 1) {
			zend_long pure = thisIsPure();
			if (UNEXPECTED(pure < 0)) return zv::Val();
			return pure == PT_TRI_YES ? pt_type_new_identifier_type_node(PT_LC("pure-callable")) : pt_type_new_identifier_type_node(PT_LC("callable"));
		}
		zval *p = parameters();
		zval *tags = p != NULL ? templateTags() : NULL;
		zval *a = tags != NULL ? assertions() : NULL;
		zval *r = a != NULL ? returnType() : NULL;
		zval *pure = r != NULL ? isPureSlot() : NULL;
		if (UNEXPECTED(pure == NULL)) return zv::Val();
		/* $this->isPure->yes() — the slot, not the method */
		zend_long pureValue = pt_type_trinary_value(pure);
		if (UNEXPECTED(pureValue < 0)) return zv::Val();
		if (pureValue == PT_TRI_YES) return pt_callable_type_node(PT_LC("pure-callable"), p, tags, a, r);
		return pt_callable_type_node(PT_LC("callable"), p, tags, a, r);
	}

	/* false = pending exception */
	[[nodiscard]] bool hasTemplateOrLateResolvableType(bool &out) const
	{
		zval *p = parameters();
		zval *a = p != NULL ? assertions() : NULL;
		if (UNEXPECTED(a == NULL)) return false;
		bool has;
		if (UNEXPECTED(!pt_callable_parameters_or_asserts_have_template(p, a, has))) return false;
		if (has) {
			out = true;
			return true;
		}
		zv::Val returnType = thisReturnType();
		if (UNEXPECTED(returnType.isUndef())) return false;
		if (UNEXPECTED(!zv::Ref(returnType.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getReturnType() must return an object");
			return false;
		}
		return pt_type_op_bool(Z_OBJ_P(returnType.raw()), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL, out);
	}

	/* the $this-calls a subclass may answer differently, with the direct
	 * path when the method is CallableType's own; UNDEF = pending exception */
	zv::Val thisParameters() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getparameters"), ctGetParameters))) return slotValue(parameters());
		return pt_type_call(self, PT_LC("getparameters"), 0, NULL);
	}

	zv::Val thisReturnType() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getreturntype"), ctGetReturnType))) return slotValue(returnType());
		return pt_type_call(self, PT_LC("getreturntype"), 0, NULL);
	}

	/* $this->isVariadic(); -1 = pending exception */
	int thisIsVariadic() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("isvariadic"), ctIsVariadic))) {
			zval *v = variadicSlot();
			if (UNEXPECTED(v == NULL)) return -1;
			return zend_is_true(v) ? 1 : 0;
		}
		zv::Val result = pt_type_call(self, PT_LC("isvariadic"), 0, NULL);
		if (UNEXPECTED(result.isUndef())) return -1;
		return zend_is_true(result.raw()) ? 1 : 0;
	}

	/* $this->isPure() as a PT_TRI_* value; -1 = pending exception */
	[[nodiscard]] zend_long thisIsPure() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("ispure"), ctIsPure))) {
			zval *pure = isPureSlot();
			if (UNEXPECTED(pure == NULL)) return -1;
			return pt_type_trinary_value(pure);
		}
		return pt_type_call_trinary(self, PT_LC("ispure"), 0, NULL);
	}

	zv::Val thisIsSubTypeOf(zval *otherType) const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("issubtypeof"), ctIsSubTypeOf))) return isSubTypeOf(otherType);
		return pt_type_op(self, PT_OP_IS_SUB_TYPE_OF, 1, otherType);
	}

	/* the getters the shared helpers take */
	static zv::Val getParametersOf(zend_object *object) { return CallableType(object).thisParameters(); }
	static zv::Val getReturnTypeOf(zend_object *object) { return CallableType(object).thisReturnType(); }

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }

	void writeSlot(uint32_t index, zval *value) { pt_write_slot(self, index, value); }

	/* $this->slot = $value ?? <factory>(); false = pending exception */
	[[nodiscard]] bool writeOrDefault(uint32_t index, zval *value, zv::Val (*factory)())
	{
		if (value != NULL) {
			writeSlot(index, value);
			return true;
		}
		zv::Val created = factory();
		if (UNEXPECTED(created.isUndef())) return false;
		writeSlot(index, created.raw());
		return true;
	}

	/* $type instanceof CompoundType && !$type instanceof self; false =
	 * pending exception */
	static bool compoundButNotSelf(zval *type, bool &out)
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return false;
		out = compound && !instanceof_function(Z_OBJCE_P(type), pt_ce_callable_type);
		return true;
	}

	/* $type->getCallableParametersAcceptors($scope) as an owned array;
	 * UNDEF = pending exception */
	static zv::Val acceptorsOf(zval *type, zval *scope)
	{
		zv::Val acceptors = pt_type_call(Z_OBJ_P(type), PT_LC("getcallableparametersacceptors"), 1, scope);
		if (UNEXPECTED(acceptors.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(acceptors.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getCallableParametersAcceptors() must return an array");
			return zv::Val();
		}
		return acceptors;
	}

	/* $a->equals($b) on two types; -1 = pending exception */
	static int typesEqual(zval *a, zval *b)
	{
		if (UNEXPECTED(Z_TYPE_P(a) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: expected a type, %s given", zend_zval_value_name(a));
			return -1;
		}
		zv::Val equal = pt_type_op(Z_OBJ_P(a), PT_OP_EQUALS, 1, b);
		if (UNEXPECTED(equal.isUndef())) return -1;
		return zend_is_true(equal.raw()) ? 1 : 0;
	}

	/* the object an element of the twin's arrays must be; NULL with an
	 * Error pending, as the twin's member call on it raises */
	static zend_object *elementObject(zval *element, const char *method)
	{
		if (UNEXPECTED(element == NULL || Z_TYPE_P(element) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", method, element == NULL ? "null" : zend_zval_value_name(element));
			return NULL;
		}
		return Z_OBJ_P(element);
	}

	/* one parameter of equals(): 1 with `out` set when the comparison
	 * decides the answer (a difference, or the first default value's
	 * equality — the twin returns on it), 0 to go on, -1 = pending
	 * exception */
	static int parameterEquals(zv::ArrayEntry entry, zval *otherParameters, bool &out)
	{
		zend_object *parameter = elementObject(entry.value().raw(), "isOptional");
		if (UNEXPECTED(parameter == NULL)) return -1;
		zval *otherRaw = entry.hasStringKey() ? zend_symtable_find(Z_ARRVAL_P(otherParameters), entry.stringKey()) : zend_hash_index_find(Z_ARRVAL_P(otherParameters), entry.indexKey());
		zend_object *otherParameter = elementObject(otherRaw, "isOptional");
		if (UNEXPECTED(otherParameter == NULL)) return -1;
		int optional = pt_type_call_is_true(parameter, PT_LC("isoptional"), 0, NULL);
		int otherOptional = optional >= 0 ? pt_type_call_is_true(otherParameter, PT_LC("isoptional"), 0, NULL) : -1;
		if (UNEXPECTED(otherOptional < 0)) return -1;
		if (optional != otherOptional) {
			out = false;
			return 1;
		}
		zv::Val passedByReference = pt_type_call(parameter, PT_LC("passedbyreference"), 0, NULL);
		if (UNEXPECTED(passedByReference.isUndef())) return -1;
		zv::Val otherPassedByReference = pt_type_call(otherParameter, PT_LC("passedbyreference"), 0, NULL);
		if (UNEXPECTED(otherPassedByReference.isUndef())) return -1;
		if (UNEXPECTED(!zv::Ref(passedByReference.raw()).isObject())) {
			zend_type_error("phpstan_turbo: passedByReference() must return an object");
			return -1;
		}
		int byReferenceEqual = pt_type_call_is_true(Z_OBJ_P(passedByReference.raw()), PT_LC("equals"), 1, otherPassedByReference.raw());
		if (UNEXPECTED(byReferenceEqual < 0)) return -1;
		if (byReferenceEqual == 0) {
			out = false;
			return 1;
		}
		int variadic = pt_type_call_is_true(parameter, PT_LC("isvariadic"), 0, NULL);
		int otherVariadic = variadic >= 0 ? pt_type_call_is_true(otherParameter, PT_LC("isvariadic"), 0, NULL) : -1;
		if (UNEXPECTED(otherVariadic < 0)) return -1;
		if (variadic != otherVariadic) {
			out = false;
			return 1;
		}
		zv::Val type = pt_type_call(parameter, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return -1;
		zv::Val otherType = pt_type_call(otherParameter, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(otherType.isUndef())) return -1;
		int typeEqual = typesEqual(type.raw(), otherType.raw());
		if (UNEXPECTED(typeEqual < 0)) return -1;
		if (typeEqual == 0) {
			out = false;
			return 1;
		}
		zv::Val defaultValue = pt_type_call(parameter, PT_LC("getdefaultvalue"), 0, NULL);
		if (UNEXPECTED(defaultValue.isUndef())) return -1;
		zv::Val otherDefaultValue = pt_type_call(otherParameter, PT_LC("getdefaultvalue"), 0, NULL);
		if (UNEXPECTED(otherDefaultValue.isUndef())) return -1;
		if (!defaultValue.isNull()) {
			if (otherDefaultValue.isNull()) {
				out = false;
				return 1;
			}
			/* return $parameter->getDefaultValue()->equals($otherParameter->getDefaultValue()) */
			int defaultsEqual = typesEqual(defaultValue.raw(), otherDefaultValue.raw());
			if (UNEXPECTED(defaultsEqual < 0)) return -1;
			out = defaultsEqual == 1;
			return 1;
		}
		if (!otherDefaultValue.isNull()) {
			out = false;
			return 1;
		}
		return 0;
	}

	/* one (own, other) template type map pair of equals(): the same count
	 * and every own type found and equal in the other; 1 with `out` false
	 * on a difference, 0 to go on, -1 = pending exception */
	static int templateTypeMapsEqual(zval *map, zval *otherMap, bool &out)
	{
		if (UNEXPECTED(Z_TYPE_P(map) != IS_OBJECT || Z_TYPE_P(otherMap) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: a template type map must be an object");
			return -1;
		}
		zv::Val count = pt_type_call(Z_OBJ_P(map), PT_LC("count"), 0, NULL);
		if (UNEXPECTED(count.isUndef())) return -1;
		zv::Val otherCount = pt_type_call(Z_OBJ_P(otherMap), PT_LC("count"), 0, NULL);
		if (UNEXPECTED(otherCount.isUndef())) return -1;
		if (zval_get_long(count.raw()) != zval_get_long(otherCount.raw())) {
			out = false;
			return 1;
		}
		zv::Val types = pt_type_call(Z_OBJ_P(map), PT_LC("gettypes"), 0, NULL);
		if (UNEXPECTED(types.isUndef())) return -1;
		if (UNEXPECTED(!zv::Ref(types.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getTypes() must return an array");
			return -1;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zv::Val typeName = entry.hasStringKey() ? zv::Val::string(entry.stringKey()) : zv::Val::adoptString(zend_long_to_str((zend_long) entry.indexKey()));
			zv::Val otherTemplateType = pt_type_call(Z_OBJ_P(otherMap), PT_LC("gettype"), 1, typeName.raw());
			if (UNEXPECTED(otherTemplateType.isUndef())) return -1;
			if (otherTemplateType.isNull()) {
				out = false;
				return 1;
			}
			int equal = typesEqual(entry.value().raw(), otherTemplateType.raw());
			if (UNEXPECTED(equal < 0)) return -1;
			if (equal == 0) {
				out = false;
				return 1;
			}
		}
		return 0;
	}

	/* one template tag of equals(): present in the other with the same
	 * name, equal bound and variance, and equal default values (the first
	 * default decides, as the twin returns on it); 1 with `out` set when
	 * decided, 0 to go on, -1 = pending exception */
	static int templateTagEquals(zv::ArrayEntry entry, zval *otherTags, bool &out)
	{
		zval *otherRaw = entry.hasStringKey() ? zend_symtable_find(Z_ARRVAL_P(otherTags), entry.stringKey()) : zend_hash_index_find(Z_ARRVAL_P(otherTags), entry.indexKey());
		if (otherRaw == NULL) {
			out = false;
			return 1;
		}
		zend_object *tag = elementObject(entry.value().raw(), "getName");
		zend_object *otherTag = tag != NULL ? elementObject(otherRaw, "getName") : NULL;
		if (UNEXPECTED(otherTag == NULL)) return -1;
		zv::Val name = pt_type_call(tag, PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(name.isUndef())) return -1;
		zv::Val otherName = pt_type_call(otherTag, PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(otherName.isUndef())) return -1;
		if (!zend_is_identical(name.raw(), otherName.raw())) {
			out = false;
			return 1;
		}
		zv::Val bound = pt_type_call(tag, PT_LC("getbound"), 0, NULL);
		if (UNEXPECTED(bound.isUndef())) return -1;
		zv::Val otherBound = pt_type_call(otherTag, PT_LC("getbound"), 0, NULL);
		if (UNEXPECTED(otherBound.isUndef())) return -1;
		int boundsEqual = typesEqual(bound.raw(), otherBound.raw());
		if (UNEXPECTED(boundsEqual < 0)) return -1;
		if (boundsEqual == 0) {
			out = false;
			return 1;
		}
		zv::Val variance = pt_type_call(tag, PT_LC("getvariance"), 0, NULL);
		if (UNEXPECTED(variance.isUndef())) return -1;
		zv::Val otherVariance = pt_type_call(otherTag, PT_LC("getvariance"), 0, NULL);
		if (UNEXPECTED(otherVariance.isUndef())) return -1;
		int variancesEqual = typesEqual(variance.raw(), otherVariance.raw());
		if (UNEXPECTED(variancesEqual < 0)) return -1;
		if (variancesEqual == 0) {
			out = false;
			return 1;
		}
		zv::Val defaultValue = pt_type_call(tag, PT_LC("getdefault"), 0, NULL);
		if (UNEXPECTED(defaultValue.isUndef())) return -1;
		zv::Val otherDefaultValue = pt_type_call(otherTag, PT_LC("getdefault"), 0, NULL);
		if (UNEXPECTED(otherDefaultValue.isUndef())) return -1;
		if (!defaultValue.isNull()) {
			if (otherDefaultValue.isNull()) {
				out = false;
				return 1;
			}
			int defaultsEqual = typesEqual(defaultValue.raw(), otherDefaultValue.raw());
			if (UNEXPECTED(defaultsEqual < 0)) return -1;
			out = defaultsEqual == 1;
			return 1;
		}
		if (!otherDefaultValue.isNull()) {
			out = false;
			return 1;
		}
		return 0;
	}
};

} // namespace phpstanturbo

using phpstanturbo::CallableType;

bool pt_callable_type_new(zval *out, zval *parameters, zval *returnType, bool variadic, zval *templateTypeMap, zval *resolvedTemplateTypeMap, zval *templateTags, zval *isPure, zval *assertions)
{
	return pt_val_into(CallableType::create(parameters, returnType, variadic, templateTypeMap, resolvedTemplateTypeMap, templateTags, isPure, assertions), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS CallableType(Z_OBJ_P(ZEND_THIS))

/* a slot's value; throws when the constructor never ran */
static void ctReturnSlot(INTERNAL_FUNCTION_PARAMETERS, zval *slot)
{
	if (UNEXPECTED(slot == NULL)) RETURN_THROWS();
	RETURN_COPY(slot);
}

static void ZEND_FASTCALL ctGetParameters(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	ctReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.parameters());
}

static void ZEND_FASTCALL ctGetReturnType(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	ctReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.returnType());
}

static void ZEND_FASTCALL ctIsVariadic(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	ctReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.variadicSlot());
}

static void ZEND_FASTCALL ctIsPure(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	ctReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.isPureSlot());
}

static void ZEND_FASTCALL ctIsSubTypeOf(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *otherType;
	if (!zp::parse<zp::Obj>(execute_data, otherType)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.isSubTypeOf(otherType));
}

static void ZEND_FASTCALL ctTrinaryNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL ctTrinaryMaybe0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

static void ZEND_FASTCALL ctTrinaryYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL ctError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL ctEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL ctObjectWithoutClass0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_object_without_class_type());
}

PT_MINIT_REGISTRATION(pt_register_callable_type)
{
	static const reg::Arg parametersArg = reg::withDefault(pt_callable_nullable_array_arg("parameters"), "null");
	static const reg::Arg templateTagsArg = reg::withDefault(reg::arrayArg("templateTags"), "[]");

	reg::Class cls("PHPStan\\Type\\CallableType");
	ptdecl::CallableType::declareClass(cls);
	/* the slots must stay in this order (PT_CT_PROP_*) */
	ptdecl::CallableType::declareProperties(cls);

	cls.method("__construct", reg::Public, 0, {
		parametersArg,
		reg::withDefault(reg::obj("returnType", ptcls::type, true), "null"),
		reg::withDefault(reg::boolArg("variadic"), "true"),
		reg::withDefault(reg::obj("templateTypeMap", "PHPStan\\Type\\Generic\\TemplateTypeMap", true), "null"),
		reg::withDefault(reg::obj("resolvedTemplateTypeMap", "PHPStan\\Type\\Generic\\TemplateTypeMap", true), "null"),
		templateTagsArg,
		reg::withDefault(reg::obj("isPure", ptcls::trinaryLogic, true), "null"),
		reg::withDefault(reg::obj("assertions", "PHPStan\\Reflection\\Assertions", true), "null"),
	}, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *parameters = NULL, *returnType = NULL, *templateTypeMap = NULL, *resolvedTemplateTypeMap = NULL, *templateTags = NULL, *isPure = NULL, *assertions = NULL;
		bool variadic = true;
		ZEND_PARSE_PARAMETERS_START(0, 8)
			Z_PARAM_OPTIONAL
			Z_PARAM_ARRAY_OR_NULL(parameters)
			Z_PARAM_OBJECT_OR_NULL(returnType)
			Z_PARAM_BOOL(variadic)
			Z_PARAM_OBJECT_OR_NULL(templateTypeMap)
			Z_PARAM_OBJECT_OR_NULL(resolvedTemplateTypeMap)
			Z_PARAM_ARRAY(templateTags)
			Z_PARAM_OBJECT_OR_NULL(isPure)
			Z_PARAM_OBJECT_OR_NULL(assertions)
		ZEND_PARSE_PARAMETERS_END();
		PT_THIS.construct(parameters, returnType, variadic, templateTypeMap, resolvedTemplateTypeMap, templateTags, isPure, assertions);
	});

	cls.method(sigs::getTemplateTags, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ctReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.templateTags());
	});
	cls.method(sigs::isPure, ctIsPure);

	cls.method<&CallableType::getReferencedClasses>(sigs::getReferencedClasses);
	cls.op<PT_OP_GET_REFERENCED_CLASSES, &CallableType::getReferencedClasses>();
	cls.method(sigs::getObjectClassNames, ctEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassReflections, ctEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getConstantStrings, ctEmptyArray0);

	cls.method<&CallableType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return CallableType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method<&CallableType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &CallableType::isSuperTypeOf>();

	cls.method(sigs::isSubTypeOf, ctIsSubTypeOf);
	cls.op<PT_OP_IS_SUB_TYPE_OF, &CallableType::isSubTypeOf>();

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method<&CallableType::equals, zp::TypeObj>(sigs::equals);
	cls.op<PT_OP_EQUALS, &CallableType::equals>();

	cls.method<&CallableType::describe, zp::Obj>(sigs::describe);
	cls.op<PT_OP_DESCRIBE, &CallableType::describe>();

	cls.method(sigs::isCallable, ctTrinaryYes0);
	cls.op(PT_OP_IS_CALLABLE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_YES); });
	cls.method(sigs::getCallableParametersAcceptors, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(pt_callable_self_list(Z_OBJ_P(ZEND_THIS)));
	});
	cls.method<&CallableType::getThrowPoints>(sigs::getThrowPoints);
	cls.method<&CallableType::getImpurePoints>(sigs::getImpurePoints);
	cls.method(sigs::getInvalidateExpressions, ctEmptyArray0);
	cls.method(sigs::getUsedVariables, ctEmptyArray0);
	cls.method(sigs::acceptsNamedArguments, ctTrinaryYes0);
	cls.method(sigs::mustUseReturnValue, ctTrinaryMaybe0);
	cls.method(sigs::getAsserts, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ctReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.assertions());
	});
	cls.method(sigs::isStaticClosure, ctTrinaryMaybe0);

	cls.method(sigs::toNumber, ctError0);
	cls.method(sigs::toBitwiseNotType, ctError0);
	cls.method(sigs::toAbsoluteNumber, ctError0);
	cls.method(sigs::toString, ctError0);
	cls.method(sigs::toInteger, ctError0);
	cls.method(sigs::toFloat, ctError0);
	cls.method<&CallableType::toArray>(sigs::toArray);
	cls.method(sigs::toArrayKey, ctError0);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return pt_type_new_error_type(); });
	cls.method(sigs::toCoercedArgumentType, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool strictTypes;
		if (!zp::parse<zp::Bool>(execute_data, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.toCoercedArgumentType());
	});

	cls.method(sigs::getTemplateTypeMap, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ctReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.templateTypeMap());
	});
	cls.method(sigs::getResolvedTemplateTypeMap, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ctReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.resolvedTemplateTypeMap());
	});
	cls.method(sigs::getCallSiteVarianceMap, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(pt_callable_template_type_variance_map_empty());
	});
	cls.method(sigs::getParameters, ctGetParameters);
	cls.method(sigs::isVariadic, ctIsVariadic);
	cls.method(sigs::getReturnType, ctGetReturnType);

	cls.method<&CallableType::inferTemplateTypes, zp::Obj>(sigs::inferTemplateTypes);

	cls.method<&CallableType::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);
	cls.op<PT_OP_GET_REFERENCED_TEMPLATE_TYPES, &CallableType::getReferencedTemplateTypes>();

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_with<CallableType>(self, argv); });

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

	cls.method(sigs::isOversizedArray, ctTrinaryNo0);
	cls.method(sigs::isNull, ctTrinaryNo0);
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isConstantValue, ctTrinaryNo0);
	cls.method(sigs::isConstantScalarValue, ctTrinaryNo0);
	cls.op(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getConstantScalarTypes, ctEmptyArray0);
	cls.method(sigs::getConstantScalarValues, ctEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::isTrue, ctTrinaryNo0);
	cls.method(sigs::isFalse, ctTrinaryNo0);
	cls.method(sigs::isBoolean, ctTrinaryNo0);
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isFloat, ctTrinaryNo0);
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isInteger, ctTrinaryNo0);
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isString, ctTrinaryMaybe0);
	cls.op(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.method(sigs::isNumericString, ctTrinaryNo0);
	cls.method(sigs::isDecimalIntegerString, ctTrinaryNo0);
	cls.method(sigs::isNonEmptyString, ctTrinaryMaybe0);
	cls.method(sigs::isNonFalsyString, ctTrinaryMaybe0);
	cls.method(sigs::isLiteralString, ctTrinaryMaybe0);
	cls.method(sigs::isLowercaseString, ctTrinaryMaybe0);
	cls.method(sigs::isClassString, ctTrinaryMaybe0);
	cls.method(sigs::isUppercaseString, ctTrinaryMaybe0);
	cls.method(sigs::getClassStringObjectType, ctObjectWithoutClass0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, ctObjectWithoutClass0);
	cls.method(sigs::isVoid, ctTrinaryNo0);
	cls.op(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isScalar, ctTrinaryMaybe0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* new BooleanType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::getEnumCases, ctEmptyArray0);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});
	cls.op(PT_OP_GET_ENUM_CASE_OBJECT, PT_OP_LAMBDA { return zv::Val::null(); });
	cls.method(sigs::isCommonCallable, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ctReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.isCommonCallableSlot());
	});
	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(pt_type_new_error_type());
	});
	cls.method(sigs::getFiniteTypes, ctEmptyArray0);

	cls.method<&CallableType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method<&CallableType::hasTemplateOrLateResolvableType>(sigs::hasTemplateOrLateResolvableType);
	cls.op<PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, &CallableType::hasTemplateOrLateResolvableType>();

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::CallableType::registerTraits(cls);

	cls.shadow(&pt_ce_callable_type);
}

/* }}} */
