/*
 * PHPStanTurbo\ClosureType — native implementation of PHPStan\Type\ClosureType.
 *
 * Declared as PHPStan\Type\ClosureType itself at activation: not final (an
 * extension's PHP class may extend it — its constructor would call
 * parent::__construct(), so the constructor is a proper method),
 * implementing PHPStan\Type\TypeWithClassName and
 * PHPStan\Reflection\Callables\CallableParametersAcceptor. State is the
 * twin's private properties, declared typed property slots in the twin's
 * declaration order (the class-body properties first, the promoted
 * constructor properties after them), so the std object handlers do
 * GC/clone and a subclass's own properties follow them.
 *
 * Most of the object side delegates to `$this->objectType`, the ObjectType
 * of Closure the constructor builds. Every `$this->method()` the twin makes
 * (getParameters(), getReturnType(), isVariadic(), isPure(),
 * getImpurePoints(), describe(), getClassStringType(),
 * getUnresolvedMethodPrototype()) goes through the object's class entry — a
 * subclass may have overridden it — with a direct C++ call when the
 * object's method is the native one. The private isSuperTypeOfInternal(),
 * describeCallable() and inferTemplateTypesOnParametersAcceptor() are
 * direct C++ calls, as PHP never dispatches them; the bodies the twin
 * shares verbatim with CallableType are the pt_callable_* helpers in
 * TypeTraits.cpp. The private slot of another ClosureType
 * (`$type->isStatic`) is read directly, as the twin does from inside the
 * class.
 */

#include "TypeTraits.h"
#include "AnalyserValues.h"
#include "generated/ClosureType.h"

namespace slots = ptdecl::ClosureType::slot;
namespace sigs = ptdecl::ClosureType::sig;

zend_class_entry *pt_ce_closure_type = nullptr;

/* the handlers a $this-call is checked against before the direct path */
static void ZEND_FASTCALL cltGetParameters(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL cltGetReturnType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL cltIsVariadic(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL cltIsPure(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL cltGetImpurePoints(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL cltDescribe(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL cltGetClassStringType(INTERNAL_FUNCTION_PARAMETERS);

namespace phpstanturbo {

/* the constructor's arguments, every zval borrowed, NULL for null (a NULL
 * array is the default []) */
struct ClosureTypeArguments
{
	zval *parameters;
	zval *returnType;
	bool variadic;
	zval *templateTypeMap;
	zval *resolvedTemplateTypeMap;
	zval *callSiteVarianceMap;
	zval *templateTags;
	zval *throwPoints;
	zval *impurePoints;
	zval *invalidateExpressions;
	zval *usedVariables;
	zval *acceptsNamedArguments;
	zval *mustUseReturnValue;
	zval *assertions;
	zval *isStatic;
};

/* Mirrors PHPStan\Type\ClosureType. State lives in the PHP object's slots. */
class ClosureType
{
public:
	explicit ClosureType(zend_object *self) : self(self) {}

	/* __construct(?array $parameters = null, ?Type $returnType = null, bool
	 * $variadic = true, ?TemplateTypeMap $templateTypeMap = null,
	 * ?TemplateTypeMap $resolvedTemplateTypeMap = null,
	 * ?TemplateTypeVarianceMap $callSiteVarianceMap = null, array
	 * $templateTags = [], array $throwPoints = [], ?array $impurePoints =
	 * null, array $invalidateExpressions = [], array $usedVariables = [],
	 * ?TrinaryLogic $acceptsNamedArguments = null, ?TrinaryLogic
	 * $mustUseReturnValue = null, ?Assertions $assertions = null,
	 * ?TrinaryLogic $isStatic = null) */
	void construct(const ClosureTypeArguments &a)
	{
		/* the promoted properties are assigned before the body runs */
		zval boolean = {}, emptyArray = {};
		ZVAL_BOOL(&boolean, a.variadic);
		ZVAL_EMPTY_ARRAY(&emptyArray);
		writeSlot(slots::variadic, &boolean);
		writeSlot(slots::templateTags, a.templateTags != NULL ? a.templateTags : &emptyArray);
		writeSlot(slots::throwPoints, a.throwPoints != NULL ? a.throwPoints : &emptyArray);
		writeSlot(slots::invalidateExpressions, a.invalidateExpressions != NULL ? a.invalidateExpressions : &emptyArray);
		writeSlot(slots::usedVariables, a.usedVariables != NULL ? a.usedVariables : &emptyArray);

		writeSlot(slots::acceptsNamedArguments, a.acceptsNamedArguments != NULL ? a.acceptsNamedArguments : pt_trinary_singleton(PT_TRI_YES));
		writeSlot(slots::mustUseReturnValue, a.mustUseReturnValue != NULL ? a.mustUseReturnValue : pt_trinary_singleton(PT_TRI_MAYBE));
		/* $this->parameters = $parameters ?? [] */
		writeSlot(slots::parameters, a.parameters != NULL ? a.parameters : &emptyArray);
		/* $this->returnType = $returnType ?? new MixedType() */
		if (a.returnType != NULL) {
			writeSlot(slots::returnType, a.returnType);
		} else {
			zv::Val mixed = pt_type_new_mixed_type();
			if (UNEXPECTED(mixed.isUndef())) return;
			writeSlot(slots::returnType, mixed.raw());
		}
		ZVAL_BOOL(&boolean, a.parameters == NULL && a.returnType == NULL);
		writeSlot(slots::isCommonCallable, &boolean);
		/* $this->objectType = new ObjectType(Closure::class) */
		zend_string *closureName = zend_string_init("Closure", sizeof("Closure") - 1, 0);
		zval objectTypeRaw;
		bool created = pt_object_type_new(&objectTypeRaw, closureName);
		zend_string_release(closureName);
		if (UNEXPECTED(!created)) return;
		zv::Val objectType = zv::Val::adopt(objectTypeRaw);
		writeSlot(slots::objectType, objectType.raw());
		if (UNEXPECTED(!writeOrDefault(slots::templateTypeMap, a.templateTypeMap, pt_callable_template_type_map_empty))) return;
		if (UNEXPECTED(!writeOrDefault(slots::resolvedTemplateTypeMap, a.resolvedTemplateTypeMap, pt_callable_template_type_map_empty))) return;
		if (UNEXPECTED(!writeOrDefault(slots::callSiteVarianceMap, a.callSiteVarianceMap, pt_callable_template_type_variance_map_empty))) return;
		/* $this->impurePoints = $impurePoints ?? [new SimpleImpurePoint('functionCall', 'call to an unknown Closure', false)] */
		if (a.impurePoints != NULL) {
			writeSlot(slots::impurePoints, a.impurePoints);
		} else {
			zv::Val point = pt_callable_new_simple_impure_point(PT_LC("functionCall"), PT_LC("call to an unknown Closure"), false);
			if (UNEXPECTED(point.isUndef())) return;
			zv::Arr points = zv::Arr::create(1);
			points.push(std::move(point));
			writeSlot(slots::impurePoints, points.raw());
		}
		if (UNEXPECTED(!writeOrDefault(slots::assertions, a.assertions, pt_callable_assertions_empty))) return;
		writeSlot(slots::isStatic, a.isStatic != NULL ? a.isStatic : pt_trinary_singleton(PT_TRI_MAYBE));
	}

	/* new self(...) — exactly the class, as the twin's `new self` sites spell
	 * it; UNDEF = pending exception */
	static zv::Val create(const ClosureTypeArguments &a)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_closure_type) != SUCCESS)) return zv::Val();
		ClosureType(Z_OBJ(object)).construct(a);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

	/* createPure(): new self(parameters: null, returnType: null, variadic:
	 * true, templateTypeMap: null, resolvedTemplateTypeMap: null,
	 * callSiteVarianceMap: null, templateTags: [], throwPoints: [],
	 * impurePoints: []) */
	static zv::Val createPure()
	{
		ClosureTypeArguments a = {};
		a.variadic = true;
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		a.impurePoints = &emptyArray;
		return create(a);
	}

	/* the slots (borrowed); NULL with an Error pending when the constructor
	 * never ran */
	[[nodiscard]] zval *parameters() const { return slot(self, slots::parameters, "parameters"); }
	zval *returnType() const { return slot(self, slots::returnType, "returnType"); }
	zval *isCommonCallableSlot() const { return slot(self, slots::isCommonCallable, "isCommonCallable"); }
	zval *objectTypeSlot() const { return slot(self, slots::objectType, "objectType"); }
	zval *templateTypeMap() const { return slot(self, slots::templateTypeMap, "templateTypeMap"); }
	zval *resolvedTemplateTypeMap() const { return slot(self, slots::resolvedTemplateTypeMap, "resolvedTemplateTypeMap"); }
	zval *callSiteVarianceMap() const { return slot(self, slots::callSiteVarianceMap, "callSiteVarianceMap"); }
	zval *impurePoints() const { return slot(self, slots::impurePoints, "impurePoints"); }
	zval *acceptsNamedArguments() const { return slot(self, slots::acceptsNamedArguments, "acceptsNamedArguments"); }
	zval *mustUseReturnValue() const { return slot(self, slots::mustUseReturnValue, "mustUseReturnValue"); }
	zval *assertions() const { return slot(self, slots::assertions, "assertions"); }
	zval *isStatic() const { return slot(self, slots::isStatic, "isStatic"); }
	zval *variadicSlot() const { return slot(self, slots::variadic, "variadic"); }
	zval *templateTags() const { return slot(self, slots::templateTags, "templateTags"); }
	zval *throwPoints() const { return slot(self, slots::throwPoints, "throwPoints"); }
	zval *invalidateExpressions() const { return slot(self, slots::invalidateExpressions, "invalidateExpressions"); }
	zval *usedVariables() const { return slot(self, slots::usedVariables, "usedVariables"); }

	static zval *slot(zend_object *object, uint32_t index, const char *name) { return pt_typed_slot(object, index, pt_ce_closure_type, name); }

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

	/* the $this->objectType the twin delegates to; NULL with an Error
	 * pending */
	[[nodiscard]] zend_object *objectType() const
	{
		zval *p = objectTypeSlot();
		if (UNEXPECTED(p == NULL)) return NULL;
		if (UNEXPECTED(Z_TYPE_P(p) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: %s::$objectType must be an object", ZSTR_VAL(pt_ce_closure_type->name));
			return NULL;
		}
		return Z_OBJ_P(p);
	}

	/* $this->objectType->method(...$args); UNDEF = pending exception */
	zv::Val delegate(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zend_object *object = objectType();
		if (UNEXPECTED(object == NULL)) return zv::Val();
		return pt_type_call(object, lcname, len, argc, argv);
	}

	/* the same for a hot operation (TypeOps.h): the object type's direct
	 * entry when it is a native class */
	zv::Val delegateOp(pt_type_op_id op, uint32_t argc, zval *argv) const
	{
		zend_object *object = objectType();
		if (UNEXPECTED(object == NULL)) return zv::Val();
		return pt_type_op(object, op, argc, argv);
	}

	/* yes without impure points, no with a certain one, maybe otherwise;
	 * -1 = pending exception */
	[[nodiscard]] zend_long isPure() const
	{
		zv::Val points = thisImpurePoints();
		if (UNEXPECTED(points.isUndef())) return -1;
		if (UNEXPECTED(!zv::Ref(points.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getImpurePoints() must return an array");
			return -1;
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(points.raw())) == 0) return PT_TRI_YES;
		zend_long certainCount = 0;
		for (zv::ArrayEntry entry : zv::ArrRef(points.raw())) {
			if (UNEXPECTED(!entry.value().isObject())) {
				zend_throw_error(NULL, "Call to a member function isCertain() on %s", zend_zval_value_name(entry.value().raw()));
				return -1;
			}
			bool certain = false;
			if (UNEXPECTED(!pt_simple_impure_point_is_certain(entry.value().raw(), certain))) return -1;
			if (!certain) continue;
			certainCount++;
		}
		return certainCount > 0 ? PT_TRI_NO : PT_TRI_MAYBE;
	}

	/* the getReferencedClasses() body over the object type's classes */
	zv::Val getReferencedClasses() const
	{
		zv::Val objectClasses = delegate(PT_LC("getreferencedclasses"), 0, NULL);
		if (UNEXPECTED(objectClasses.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(objectClasses.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getReferencedClasses() must return an array");
			return zv::Val();
		}
		zval *p = parameters();
		zval *a = p != NULL ? assertions() : NULL;
		zval *r = a != NULL ? returnType() : NULL;
		if (UNEXPECTED(r == NULL)) return zv::Val();
		return pt_callable_referenced_classes(zv::Arr::adoptVal(std::move(objectClasses)), p, a, r);
	}

	/* the CompoundType callback; the object type's answer for anything but
	 * a ClosureType; else isSuperTypeOfInternal() treating mixed as any,
	 * as an AcceptsResult; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_closure_type)) {
			zv::Args args{type, strictTypes};
			return delegate(PT_LC("accepts"), 2, args);
		}
		zv::Val result = isSuperTypeOfInternal(type, true, strictTypes);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(result.raw()), PT_LC("toacceptsresult"), 0, NULL);
	}

	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);
		}
		return isSuperTypeOfInternal(type, false, true);
	}

	/* another ClosureType selected for the own parameter types compared
	 * through CallableTypeHelper; maybe for a plain Closure object; the
	 * object type's answer otherwise; UNDEF = pending exception */
	zv::Val isSuperTypeOfInternal(zval *type, bool treatMixedAsAny, bool strictTypes) const
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_closure_type)) {
			zv::Val parameters = thisParameters();
			if (UNEXPECTED(parameters.isUndef())) return zv::Val();
			zv::Val parameterTypes = pt_callable_parameter_types(parameters.raw());
			if (UNEXPECTED(parameterTypes.isUndef())) return zv::Val();
			zv::Arr single = zv::Arr::create(1);
			single.push(zv::Ref(type));
			zv::Val variant = pt_parameters_acceptor_selector_select_from_types(parameterTypes.raw(), single.raw(), false);
			if (UNEXPECTED(variant.isUndef())) return zv::Val();
			bool isCallableAcceptor;
			if (UNEXPECTED(!pt_type_instanceof(variant.raw(), PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR, isCallableAcceptor))) return zv::Val();
			if (!isCallableAcceptor) return pt_type_is_super_type_of_result(PT_TRI_NO);
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_callable_type_helper_is_parameters_acceptor_super_type_of(&selfZv, variant.raw(), treatMixedAsAny, strictTypes);
		}
		/* $type->getObjectClassNames() === [Closure::class] */
		zv::Val classNames = pt_type_op(Z_OBJ_P(type), PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
		if (UNEXPECTED(classNames.isUndef())) return zv::Val();
		if (zv::Ref(classNames.raw()).isArray() && zend_hash_num_elements(Z_ARRVAL_P(classNames.raw())) == 1) {
			zval *first = zend_hash_index_find(Z_ARRVAL_P(classNames.raw()), 0);
			if (first != NULL && zv::Ref(first).stringEquals("Closure")) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		}
		return delegate(PT_LC("issupertypeof"), 1, type);
	}

	/* another ClosureType with the same precise description, purity and
	 * staticness; false with an exception pending */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_closure_type)) {
			out = false;
			return true;
		}
		zv::Val precise = pt_type_verbosity_level(PT_VERBOSITY_LEVEL_PRECISE);
		if (UNEXPECTED(precise.isUndef())) return false;
		zv::Val ownDescription = thisDescribe(precise.raw());
		if (UNEXPECTED(ownDescription.isUndef())) return false;
		zv::Val theirDescription = pt_type_op(Z_OBJ_P(type), PT_OP_DESCRIBE, 1, precise.raw());
		if (UNEXPECTED(theirDescription.isUndef())) return false;
		if (!zend_is_identical(ownDescription.raw(), theirDescription.raw())) {
			out = false;
			return true;
		}
		zend_long ownPure = thisIsPure();
		if (UNEXPECTED(ownPure < 0)) return false;
		zend_long theirPure = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("ispure"), 0, NULL);
		if (UNEXPECTED(theirPure < 0)) return false;
		if (ownPure != theirPure) {
			out = false;
			return true;
		}
		zval *ownStatic = isStatic();
		zval *theirStatic = ownStatic != NULL ? slot(Z_OBJ_P(type), slots::isStatic, "isStatic") : NULL;
		if (UNEXPECTED(theirStatic == NULL)) return false;
		zend_long ownStaticValue = pt_type_trinary_value(ownStatic);
		zend_long theirStaticValue = ownStaticValue >= 0 ? pt_type_trinary_value(theirStatic) : -1;
		if (UNEXPECTED(theirStaticValue < 0)) return false;
		out = ownStaticValue == theirStaticValue;
		return true;
	}

	/* 'Closure' at the type-only level; 'static-'/'pure-' prefixed names for
	 * a common callable (the static prefix only from the precise level on
	 * for a described callable); UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		pt_verbosity_case which;
		if (UNEXPECTED(!pt_type_verbosity_case(level, which))) return zv::Val();
		if (which == PT_VERBOSITY_TYPE_ONLY) return zv::Val::string("Closure", sizeof("Closure") - 1);
		int common = isCommonCallable();
		if (UNEXPECTED(common < 0)) return zv::Val();
		if (which == PT_VERBOSITY_VALUE) {
			if (common == 1) return prefixedName();
			return describeCallable();
		}
		/* the precise and cache levels */
		zval *isStaticSlot = isStatic();
		if (UNEXPECTED(isStaticSlot == NULL)) return zv::Val();
		zend_long staticValue = pt_type_trinary_value(isStaticSlot);
		if (UNEXPECTED(staticValue < 0)) return zv::Val();
		if (common == 1) return prefixedName();
		zv::Val callable = describeCallable();
		if (UNEXPECTED(callable.isUndef())) return zv::Val();
		if (staticValue == PT_TRI_YES) return zv::Val::adoptString(zend_strpprintf(0, "static-%s", ZSTR_VAL(Z_STR_P(callable.raw()))));
		return callable;
	}

	/* $prefix . $name: 'static-' when static, 'pure-Closure' when pure,
	 * 'Closure' otherwise; UNDEF = pending exception */
	zv::Val prefixedName() const
	{
		zval *isStaticSlot = isStatic();
		if (UNEXPECTED(isStaticSlot == NULL)) return zv::Val();
		zend_long staticValue = pt_type_trinary_value(isStaticSlot);
		if (UNEXPECTED(staticValue < 0)) return zv::Val();
		zend_long pure = thisIsPure();
		if (UNEXPECTED(pure < 0)) return zv::Val();
		const char *name = pure == PT_TRI_YES ? "pure-Closure" : "Closure";
		if (staticValue == PT_TRI_YES) return zv::Val::adoptString(zend_strpprintf(0, "static-%s", name));
		return zv::Val::string(name, strlen(name));
	}

	/* the printed PHPDoc node of a copy whose parameters lost their names
	 * (unless an assertion refers to them); UNDEF = pending exception */
	zv::Val describeCallable() const
	{
		zv::Val printer = pt_type_new(PT_CLASS_PHPDOC_PRINTER, 0, NULL);
		if (UNEXPECTED(printer.isUndef())) return zv::Val();
		zval *a = assertions();
		zval *p = a != NULL ? parameters() : NULL;
		if (UNEXPECTED(p == NULL)) return zv::Val();
		zv::Val dummies = pt_callable_dummy_parameters(p, a);
		if (UNEXPECTED(dummies.isUndef())) return zv::Val();
		ClosureTypeArguments args;
		if (UNEXPECTED(!ownArguments(args))) return zv::Val();
		args.parameters = dummies.raw();
		zv::Val selfWithoutParameterNames = create(args);
		if (UNEXPECTED(selfWithoutParameterNames.isUndef())) return zv::Val();
		zv::Val node = ClosureType(Z_OBJ_P(selfWithoutParameterNames.raw())).toPhpDocNode();
		if (UNEXPECTED(node.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(printer.raw()).isObject())) {
			zend_type_error("phpstan_turbo: the printer must be an object");
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(printer.raw()), PT_LC("print"), 1, node.raw());
	}

	/* $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod() */
	zv::Val getMethod(zval *methodName, zval *scope) const
	{
		return pt_type_transformed_member(self, PT_LC("getunresolvedmethodprototype"), true, methodName, scope);
	}

	/* the object type's prototype, wrapped for `call` in a
	 * ClosureCallUnresolvedMethodPrototypeReflection over $this; UNDEF =
	 * pending exception */
	zv::Val getUnresolvedMethodPrototype(zval *methodName, zval *scope) const
	{
		zv::Args args{methodName, scope};
		if (zv::Ref(methodName).stringEquals("call")) {
			zv::Val prototype = delegate(PT_LC("getunresolvedmethodprototype"), 2, args);
			if (UNEXPECTED(prototype.isUndef())) return zv::Val();
			zv::Args wrapArgs{prototype.raw(), self};
			return pt_type_new(PT_CLASS_CLOSURE_CALL_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION, 2, wrapArgs);
		}
		return delegate(PT_LC("getunresolvedmethodprototype"), 2, args);
	}

	/* new ClassNameToObjectTypeResult($this, true) */
	zv::Val toObjectTypeForInstanceofCheck() const
	{
		zv::Args args{self, true};
		return pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args);
	}

	/* TypeCombinator::union($this, new CallableType()) */
	zv::Val toCoercedArgumentType() const
	{
		zval callableRaw;
		if (UNEXPECTED(!pt_callable_type_new(&callableRaw))) return zv::Val();
		zv::Val callable = zv::Val::adopt(callableRaw);
		zv::Args args{self, callable.raw()};
		return pt_type_combinator_call(PT_LC("union"), 2, args);
	}

	/* the union or intersection's inferTemplateTypesOn($this); nothing for
	 * a non-callable or anything but a ClosureType; else the inference over
	 * each of its acceptors; UNDEF = pending exception */
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
		if (callable == PT_TRI_NO || !instanceof_function(Z_OBJCE_P(receivedType), pt_ce_closure_type)) return pt_callable_template_type_map_empty();
		zv::Val scope = pt_callable_out_of_class_scope();
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zv::Val acceptors = pt_type_call(Z_OBJ_P(receivedType), PT_LC("getcallableparametersacceptors"), 1, scope.raw());
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
		ClosureTypeArguments args;
		if (UNEXPECTED(!ownArguments(args))) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(args.assertions) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: %s::$assertions must be an object", ZSTR_VAL(pt_ce_closure_type->name));
			return zv::Val();
		}
		zv::Val mappedAssertions = pt_assertions_map_types(args.assertions, &fci->function_name);
		if (UNEXPECTED(mappedAssertions.isUndef())) return zv::Val();
		args.parameters = traversedParameters.raw();
		args.returnType = traversedReturnType.raw();
		args.variadic = variadic == 1;
		args.assertions = mappedAssertions.raw();
		return create(args);
	}

	/* $this for a common callable, a $right that is no ClosureType or one
	 * with another parameter count; else new self over the parameters and
	 * return types traversed pairwise; UNDEF = pending exception */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		int common = isCommonCallable();
		if (UNEXPECTED(common < 0)) return zv::Val();
		if (common == 1) return thisValue();
		if (!instanceof_function(Z_OBJCE_P(right), pt_ce_closure_type)) return thisValue();
		zv::Val rightParameters = pt_type_call(Z_OBJ_P(right), PT_LC("getparameters"), 0, NULL);
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
		zv::Val rightReturnType = pt_type_call(Z_OBJ_P(right), PT_LC("getreturntype"), 0, NULL);
		if (UNEXPECTED(rightReturnType.isUndef())) return zv::Val();
		zv::Args typeArgs{returnType.raw(), rightReturnType.raw()};
		zval traversedReturnTypeRaw;
		if (UNEXPECTED(!pt_call_type_fci(fci, fcc, 2, typeArgs, &traversedReturnTypeRaw))) return zv::Val();
		zv::Val traversedReturnType = zv::Val::adopt(traversedReturnTypeRaw);
		int variadic = thisIsVariadic();
		if (UNEXPECTED(variadic < 0)) return zv::Val();
		ClosureTypeArguments args;
		if (UNEXPECTED(!ownArguments(args))) return zv::Val();
		args.parameters = traversedParameters.raw();
		args.returnType = traversedReturnType.raw();
		args.variadic = variadic == 1;
		return create(args);
	}

	/* IdentifierTypeNode('Closure' with its static-/pure- prefixes) for a
	 * common callable; the CallableTypeNode over the parameters, template
	 * tags and (conditional) return type otherwise; UNDEF = pending
	 * exception */
	zv::Val toPhpDocNode() const
	{
		int common = isCommonCallable();
		if (UNEXPECTED(common < 0)) return zv::Val();
		if (common == 1) {
			zv::Val name = prefixedName();
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
		}
		zval *p = parameters();
		zval *tags = p != NULL ? templateTags() : NULL;
		zval *a = tags != NULL ? assertions() : NULL;
		zval *r = a != NULL ? returnType() : NULL;
		if (UNEXPECTED(r == NULL)) return zv::Val();
		return pt_callable_type_node(PT_LC("Closure"), p, tags, a, r);
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
	 * path when the method is ClosureType's own; UNDEF = pending exception */
	zv::Val thisParameters() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getparameters"), cltGetParameters))) return slotValue(parameters());
		return pt_type_call(self, PT_LC("getparameters"), 0, NULL);
	}

	zv::Val thisReturnType() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getreturntype"), cltGetReturnType))) return slotValue(returnType());
		return pt_type_call(self, PT_LC("getreturntype"), 0, NULL);
	}

	/* $this->isVariadic(); -1 = pending exception */
	int thisIsVariadic() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("isvariadic"), cltIsVariadic))) {
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
		if (EXPECTED(pt_type_method_is(self, PT_LC("ispure"), cltIsPure))) return isPure();
		return pt_type_call_trinary(self, PT_LC("ispure"), 0, NULL);
	}

	zv::Val thisImpurePoints() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getimpurepoints"), cltGetImpurePoints))) return slotValue(impurePoints());
		return pt_type_call(self, PT_LC("getimpurepoints"), 0, NULL);
	}

	zv::Val thisDescribe(zval *level) const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("describe"), cltDescribe))) return describe(level);
		return pt_type_op(self, PT_OP_DESCRIBE, 1, level);
	}

	zv::Val thisClassStringType() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getclassstringtype"), cltGetClassStringType))) return delegate(PT_LC("getclassstringtype"), 0, NULL);
		return pt_type_call(self, PT_LC("getclassstringtype"), 0, NULL);
	}

	/* the getters the shared helpers take */
	static zv::Val getParametersOf(zend_object *object) { return ClosureType(object).thisParameters(); }
	static zv::Val getReturnTypeOf(zend_object *object) { return ClosureType(object).thisReturnType(); }

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

	/* the own slots as the constructor's arguments, for the twin's
	 * `new self($..., $this->templateTypeMap, ...)` copies (borrowed);
	 * false with an Error pending on an uninitialized slot */
	[[nodiscard]] bool ownArguments(ClosureTypeArguments &a) const
	{
		a.parameters = parameters();
		a.returnType = a.parameters != NULL ? returnType() : NULL;
		zval *variadic = a.returnType != NULL ? variadicSlot() : NULL;
		a.templateTypeMap = variadic != NULL ? templateTypeMap() : NULL;
		a.resolvedTemplateTypeMap = a.templateTypeMap != NULL ? resolvedTemplateTypeMap() : NULL;
		a.callSiteVarianceMap = a.resolvedTemplateTypeMap != NULL ? callSiteVarianceMap() : NULL;
		a.templateTags = a.callSiteVarianceMap != NULL ? templateTags() : NULL;
		a.throwPoints = a.templateTags != NULL ? throwPoints() : NULL;
		a.impurePoints = a.throwPoints != NULL ? impurePoints() : NULL;
		a.invalidateExpressions = a.impurePoints != NULL ? invalidateExpressions() : NULL;
		a.usedVariables = a.invalidateExpressions != NULL ? usedVariables() : NULL;
		a.acceptsNamedArguments = a.usedVariables != NULL ? acceptsNamedArguments() : NULL;
		a.mustUseReturnValue = a.acceptsNamedArguments != NULL ? mustUseReturnValue() : NULL;
		a.assertions = a.mustUseReturnValue != NULL ? assertions() : NULL;
		a.isStatic = a.assertions != NULL ? isStatic() : NULL;
		if (UNEXPECTED(a.isStatic == NULL)) return false;
		a.variadic = zend_is_true(variadic);
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClosureType;
using phpstanturbo::ClosureTypeArguments;

bool pt_closure_type_new(zval *out, zval *parameters, zval *returnType, bool variadic, zval *templateTypeMap, zval *resolvedTemplateTypeMap, zval *callSiteVarianceMap, zval *templateTags, zval *throwPoints, zval *impurePoints, zval *invalidateExpressions, zval *usedVariables, zval *acceptsNamedArguments, zval *mustUseReturnValue, zval *assertions, zval *isStatic)
{
	ClosureTypeArguments a = { parameters, returnType, variadic, templateTypeMap, resolvedTemplateTypeMap, callSiteVarianceMap, templateTags, throwPoints, impurePoints, invalidateExpressions, usedVariables, acceptsNamedArguments, mustUseReturnValue, assertions, isStatic };
	return pt_val_into(ClosureType::create(a), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ClosureType(Z_OBJ_P(ZEND_THIS))

/* a slot's value; throws when the constructor never ran */
static void cltReturnSlot(INTERNAL_FUNCTION_PARAMETERS, zval *slot)
{
	if (UNEXPECTED(slot == NULL)) RETURN_THROWS();
	RETURN_COPY(slot);
}

/* the delegating bodies: $this->objectType->method(...$args) with the
 * arguments passed through as received (one handler per arity; each
 * method is still declared exactly once, at its registration line) */
static void cltDelegate(INTERNAL_FUNCTION_PARAMETERS, const char *lcname, size_t len, uint32_t min, uint32_t max)
{
	PT_ARGS(min, max);
	uint32_t argc = ZEND_NUM_ARGS();
	zval *argv = argc > 0 ? ZEND_CALL_ARG(execute_data, 1) : NULL;
	PT_RETURN_VAL(PT_THIS.delegate(lcname, len, argc, argv));
}

static void ZEND_FASTCALL cltGetParameters(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.parameters());
}

static void ZEND_FASTCALL cltGetReturnType(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.returnType());
}

static void ZEND_FASTCALL cltIsVariadic(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.variadicSlot());
}

static void ZEND_FASTCALL cltIsPure(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	zend_long value = PT_THIS.isPure();
	if (UNEXPECTED(value < 0)) RETURN_THROWS();
	PT_RETURN_TRINARY(value);
}

static void ZEND_FASTCALL cltGetImpurePoints(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.impurePoints());
}

static void ZEND_FASTCALL cltDescribe(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *level;
	if (!zp::parse<zp::Obj>(execute_data, level)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.describe(level));
}

static void ZEND_FASTCALL cltGetClassStringType(INTERNAL_FUNCTION_PARAMETERS)
{
	cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getclassstringtype"), 0, 0);
}

static void ZEND_FASTCALL cltTrinaryNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL cltTrinaryYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL cltError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL cltEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

/* getProperty() & co.: (string $name, ClassMemberAccessAnswerer $scope) →
 * $this->objectType->method($name, $scope) */
static void cltDelegateMember(INTERNAL_FUNCTION_PARAMETERS, const char *lcname, size_t len)
{
	cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, lcname, len, 2, 2);
}

void pt_register_closure_type()
{
	static const reg::Arg parametersArg = reg::withDefault(pt_callable_nullable_array_arg("parameters"), "null");
	static const reg::Arg impurePointsArg = reg::withDefault(pt_callable_nullable_array_arg("impurePoints"), "null");

	reg::Class cls("PHPStan\\Type\\ClosureType");
	ptdecl::ClosureType::declareClass(cls);
	/* the slots must stay in this order (PT_CLT_PROP_*) */
	ptdecl::ClosureType::declareProperties(cls);

	cls.method("__construct", reg::Public, 0, {
		parametersArg,
		reg::withDefault(reg::obj("returnType", ptcls::type, true), "null"),
		reg::withDefault(reg::boolArg("variadic"), "true"),
		reg::withDefault(reg::obj("templateTypeMap", "PHPStan\\Type\\Generic\\TemplateTypeMap", true), "null"),
		reg::withDefault(reg::obj("resolvedTemplateTypeMap", "PHPStan\\Type\\Generic\\TemplateTypeMap", true), "null"),
		reg::withDefault(reg::obj("callSiteVarianceMap", "PHPStan\\Type\\Generic\\TemplateTypeVarianceMap", true), "null"),
		reg::withDefault(reg::arrayArg("templateTags"), "[]"),
		reg::withDefault(reg::arrayArg("throwPoints"), "[]"),
		impurePointsArg,
		reg::withDefault(reg::arrayArg("invalidateExpressions"), "[]"),
		reg::withDefault(reg::arrayArg("usedVariables"), "[]"),
		reg::withDefault(reg::obj("acceptsNamedArguments", ptcls::trinaryLogic, true), "null"),
		reg::withDefault(reg::obj("mustUseReturnValue", ptcls::trinaryLogic, true), "null"),
		reg::withDefault(reg::obj("assertions", "PHPStan\\Reflection\\Assertions", true), "null"),
		reg::withDefault(reg::obj("isStatic", ptcls::trinaryLogic, true), "null"),
	}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ClosureTypeArguments a = {};
		a.variadic = true;
		ZEND_PARSE_PARAMETERS_START(0, 15)
			Z_PARAM_OPTIONAL
			Z_PARAM_ARRAY_OR_NULL(a.parameters)
			Z_PARAM_OBJECT_OR_NULL(a.returnType)
			Z_PARAM_BOOL(a.variadic)
			Z_PARAM_OBJECT_OR_NULL(a.templateTypeMap)
			Z_PARAM_OBJECT_OR_NULL(a.resolvedTemplateTypeMap)
			Z_PARAM_OBJECT_OR_NULL(a.callSiteVarianceMap)
			Z_PARAM_ARRAY(a.templateTags)
			Z_PARAM_ARRAY(a.throwPoints)
			Z_PARAM_ARRAY_OR_NULL(a.impurePoints)
			Z_PARAM_ARRAY(a.invalidateExpressions)
			Z_PARAM_ARRAY(a.usedVariables)
			Z_PARAM_OBJECT_OR_NULL(a.acceptsNamedArguments)
			Z_PARAM_OBJECT_OR_NULL(a.mustUseReturnValue)
			Z_PARAM_OBJECT_OR_NULL(a.assertions)
			Z_PARAM_OBJECT_OR_NULL(a.isStatic)
		ZEND_PARSE_PARAMETERS_END();
		PT_THIS.construct(a);
	});

	cls.method(sigs::getAsserts, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.assertions());
	});
	cls.method(sigs::getTemplateTags, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.templateTags());
	});
	cls.method<&ClosureType::createPure>(sigs::createPure);
	cls.method(sigs::isPure, cltIsPure);

	cls.method(sigs::getClassName, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getclassname"), 0, 0);
	});
	cls.method(sigs::getClassReflection, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getclassreflection"), 0, 0);
	});
	cls.op(PT_OP_GET_CLASS_REFLECTION, PT_OP_LAMBDA { return ClosureType(self).delegateOp(PT_OP_GET_CLASS_REFLECTION, argc, argv); });
	cls.method(sigs::getAncestorWithClassName, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getancestorwithclassname"), 1, 1);
	});
	cls.op(PT_OP_GET_ANCESTOR_WITH_CLASS_NAME, PT_OP_LAMBDA { return ClosureType(self).delegateOp(PT_OP_GET_ANCESTOR_WITH_CLASS_NAME, argc, argv); });

	cls.method<&ClosureType::getReferencedClasses>(sigs::getReferencedClasses);
	cls.op<PT_OP_GET_REFERENCED_CLASSES, &ClosureType::getReferencedClasses>();
	cls.method(sigs::getObjectClassNames, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getobjectclassnames"), 0, 0);
	});
	cls.op(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return ClosureType(self).delegateOp(PT_OP_GET_OBJECT_CLASS_NAMES, argc, argv); });
	cls.method(sigs::getObjectClassReflections, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getobjectclassreflections"), 0, 0);
	});
	cls.op(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, PT_OP_LAMBDA { return ClosureType(self).delegateOp(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, argc, argv); });

	cls.method<&ClosureType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return ClosureType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method<&ClosureType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &ClosureType::isSuperTypeOf>();

	cls.method<&ClosureType::equals, zp::TypeObj>(sigs::equals);
	cls.op<PT_OP_EQUALS, &ClosureType::equals>();

	cls.method(sigs::describe, cltDescribe);
	cls.op<PT_OP_DESCRIBE, &ClosureType::describe>();

	cls.method(sigs::isOffsetAccessLegal, cltTrinaryNo0);
	cls.method(sigs::isObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isobject"), 0, 0);
	});
	cls.method(sigs::getClassStringType, cltGetClassStringType);
	cls.method(sigs::isEnum, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isenum"), 0, 0);
	});
	cls.method(sigs::getTemplateType, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("gettemplatetype"), 2, 2);
	});
	cls.method(sigs::canAccessProperties, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("canaccessproperties"), 0, 0);
	});
	cls.method(sigs::hasProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("hasproperty"), 1, 1);
	});
	cls.method(sigs::getProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegateMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getproperty"));
	});
	cls.method(sigs::getUnresolvedPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegateMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedpropertyprototype"));
	});
	cls.method(sigs::hasInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("hasinstanceproperty"), 1, 1);
	});
	cls.op(PT_OP_HAS_INSTANCE_PROPERTY, PT_OP_LAMBDA { return ClosureType(self).delegateOp(PT_OP_HAS_INSTANCE_PROPERTY, argc, argv); });
	cls.method(sigs::getInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegateMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getinstanceproperty"));
	});
	cls.method(sigs::getUnresolvedInstancePropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegateMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedinstancepropertyprototype"));
	});
	cls.op(PT_OP_GET_UNRESOLVED_INSTANCE_PROPERTY_PROTOTYPE, PT_OP_LAMBDA { return ClosureType(self).delegateOp(PT_OP_GET_UNRESOLVED_INSTANCE_PROPERTY_PROTOTYPE, argc, argv); });
	cls.method(sigs::hasStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("hasstaticproperty"), 1, 1);
	});
	cls.method(sigs::getStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegateMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getstaticproperty"));
	});
	cls.method(sigs::getUnresolvedStaticPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegateMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedstaticpropertyprototype"));
	});
	cls.method(sigs::canCallMethods, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("cancallmethods"), 0, 0);
	});
	cls.method(sigs::hasMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("hasmethod"), 1, 1);
	});
	cls.op(PT_OP_HAS_METHOD, PT_OP_LAMBDA { return ClosureType(self).delegateOp(PT_OP_HAS_METHOD, argc, argv); });

	cls.method<&ClosureType::getMethod, zp::Zval, zp::Obj>(sigs::getMethod);

	cls.method(sigs::getUnresolvedMethodPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *methodName;
		zval *scope;
		if (!zp::parse<zp::Str, zp::Obj>(execute_data, methodName, scope)) RETURN_THROWS();
		zval nameZv;
		ZVAL_STR(&nameZv, methodName);
		PT_RETURN_VAL(PT_THIS.getUnresolvedMethodPrototype(&nameZv, scope));
	});
	cls.op<PT_OP_GET_UNRESOLVED_METHOD_PROTOTYPE, &ClosureType::getUnresolvedMethodPrototype>();

	cls.method(sigs::canAccessConstants, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("canaccessconstants"), 0, 0);
	});
	cls.method(sigs::hasConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("hasconstant"), 1, 1);
	});
	cls.method(sigs::getConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getconstant"), 1, 1);
	});
	cls.method(sigs::getConstantStrings, cltEmptyArray0);

	cls.method(sigs::isIterable, cltTrinaryNo0);
	cls.method(sigs::isIterableAtLeastOnce, cltTrinaryNo0);
	cls.op(PT_OP_IS_ITERABLE_AT_LEAST_ONCE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isCallable, cltTrinaryYes0);
	cls.op(PT_OP_IS_CALLABLE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_YES); });
	cls.method(sigs::getEnumCases, cltEmptyArray0);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});
	cls.op(PT_OP_GET_ENUM_CASE_OBJECT, PT_OP_LAMBDA { return zv::Val::null(); });
	cls.method(sigs::isCommonCallable, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.isCommonCallableSlot());
	});
	cls.method(sigs::getCallableParametersAcceptors, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(pt_callable_self_list(Z_OBJ_P(ZEND_THIS)));
	});
	cls.method(sigs::getThrowPoints, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.throwPoints());
	});
	cls.method(sigs::getImpurePoints, cltGetImpurePoints);
	cls.method(sigs::getInvalidateExpressions, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.invalidateExpressions());
	});
	cls.method(sigs::getUsedVariables, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.usedVariables());
	});
	cls.method(sigs::acceptsNamedArguments, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.acceptsNamedArguments());
	});
	cls.method(sigs::mustUseReturnValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.mustUseReturnValue());
	});
	cls.method(sigs::isStaticClosure, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.isStatic());
	});
	cls.method(sigs::isCloneable, cltTrinaryYes0);

	cls.method(sigs::toBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ConstantBooleanType(true) — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&result, true))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});
	cls.method(sigs::toNumber, cltError0);
	cls.method(sigs::toBitwiseNotType, cltError0);
	cls.method(sigs::toGetClassResultType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* $this->getClassStringType() — through the object's class */
		PT_RETURN_VAL(PT_THIS.thisClassStringType());
	});
	cls.method(sigs::toClassConstantType, [](INTERNAL_FUNCTION_PARAMETERS) {
		cltDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("toclassconstanttype"), 1, 1);
	});
	cls.method<&ClosureType::toObjectTypeForInstanceofCheck>(sigs::toObjectTypeForInstanceofCheck);
	cls.method(sigs::toObjectTypeForIsACheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objectOrClassType;
		bool allowString, allowSameClass;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Bool>(execute_data, objectOrClassType, allowString, allowSameClass)) RETURN_THROWS();
		PT_RETURN_VAL(pt_type_object_type_for_is_a_check(allowString));
	});
	cls.method(sigs::toAbsoluteNumber, cltError0);
	cls.method(sigs::toInteger, cltError0);
	cls.method(sigs::toFloat, cltError0);
	cls.method(sigs::toString, cltError0);
	cls.method(sigs::toArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ConstantArrayType([new ConstantIntegerType(0)], [$this], [1], isList: TrinaryLogic::createYes()) */
		PT_RETURN_VAL(pt_type_string_accessory_to_array(Z_OBJ_P(ZEND_THIS)));
	});
	cls.method(sigs::toArrayKey, cltError0);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return pt_type_new_error_type(); });
	cls.method(sigs::toCoercedArgumentType, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool strictTypes;
		if (!zp::parse<zp::Bool>(execute_data, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.toCoercedArgumentType());
	});

	cls.method(sigs::getTemplateTypeMap, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.templateTypeMap());
	});
	cls.method(sigs::getResolvedTemplateTypeMap, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.resolvedTemplateTypeMap());
	});
	cls.method(sigs::getCallSiteVarianceMap, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		cltReturnSlot(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_THIS.callSiteVarianceMap());
	});
	cls.method(sigs::getParameters, cltGetParameters);
	cls.method(sigs::isVariadic, cltIsVariadic);
	cls.method(sigs::getReturnType, cltGetReturnType);

	cls.method<&ClosureType::inferTemplateTypes, zp::Obj>(sigs::inferTemplateTypes);

	cls.method<&ClosureType::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);
	cls.op<PT_OP_GET_REFERENCED_TEMPLATE_TYPES, &ClosureType::getReferencedTemplateTypes>();

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_with<ClosureType>(self, argv); });

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

	cls.method(sigs::isNull, cltTrinaryNo0);
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isConstantValue, cltTrinaryNo0);
	cls.method(sigs::isConstantScalarValue, cltTrinaryNo0);
	cls.op(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getConstantScalarTypes, cltEmptyArray0);
	cls.method(sigs::getConstantScalarValues, cltEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::isTrue, cltTrinaryNo0);
	cls.method(sigs::isFalse, cltTrinaryNo0);
	cls.method(sigs::isBoolean, cltTrinaryNo0);
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isFloat, cltTrinaryNo0);
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isInteger, cltTrinaryNo0);
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isString, cltTrinaryNo0);
	cls.op(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isNumericString, cltTrinaryNo0);
	cls.method(sigs::isDecimalIntegerString, cltTrinaryNo0);
	cls.method(sigs::isNonEmptyString, cltTrinaryNo0);
	cls.method(sigs::isNonFalsyString, cltTrinaryNo0);
	cls.method(sigs::isLiteralString, cltTrinaryNo0);
	cls.method(sigs::isLowercaseString, cltTrinaryNo0);
	cls.method(sigs::isClassString, cltTrinaryNo0);
	cls.method(sigs::isUppercaseString, cltTrinaryNo0);
	cls.method(sigs::getClassStringObjectType, cltError0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
	});
	cls.method(sigs::isVoid, cltTrinaryNo0);
	cls.op(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isScalar, cltTrinaryNo0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* new BooleanType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(pt_type_new_error_type());
	});
	cls.method(sigs::getFiniteTypes, cltEmptyArray0);

	cls.method<&ClosureType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method<&ClosureType::hasTemplateOrLateResolvableType>(sigs::hasTemplateOrLateResolvableType);
	cls.op<PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, &ClosureType::hasTemplateOrLateResolvableType>();

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::ClosureType::registerTraits(cls);

	cls.shadow(&pt_ce_closure_type);
}

/* }}} */
