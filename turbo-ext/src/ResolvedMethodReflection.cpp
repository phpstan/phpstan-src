/*
 * PHPStanTurbo\ResolvedMethodReflection — native implementation of
 * PHPStan\Reflection\ResolvedMethodReflection.
 *
 * The method reflection a member prototype hands out (getTransformedMethod())
 * — what $scope->getMethodReflection() returns for nearly every method call:
 * the wrapped reflection (almost always a ChangedTypeMethodReflection) with
 * its variants, asserts and self-out type resolved against the called-on
 * type's template map, memoized in the twin's own slots; everything else is
 * delegated. The delegations reach a native ChangedTypeMethodReflection
 * without a frame and anything else through one cached method site per
 * member (pt_extended_method_reflection_call(), defined here, which native
 * callers such as the method call handler use for any method reflection).
 * The getAsserts() mapping callback is a native closure capturing $this, as
 * the twin's arrow function does.
 */

#include "support.h"
#include "generated/ResolvedMethodReflection.h"

namespace slots = ptdecl::ResolvedMethodReflection::slot;
namespace sigs = ptdecl::ResolvedMethodReflection::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"

zend_class_entry *pt_ce_resolved_method_reflection = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_rmr_map_types_site;

/* $assertions->mapTypes($callable) */
zv::Val assertionsMapTypes(zval *assertions, zval *callable)
{
	return pt_call_method_cached(pt_rmr_map_types_site, Z_OBJ_P(assertions), PT_LC("maptypes"), 1, callable);
}

/* }}} */

/* {{{ the members of any method reflection (pt_extended_method_reflection_call()) */

struct MemberName
{
	const char *lcname;
	size_t len;
};

const MemberName memberNames[PT_MR_MEMBER_COUNT] = {
	/* PT_MR_GET_NAME */ {PT_LC("getname")},
	/* PT_MR_GET_PROTOTYPE */ {PT_LC("getprototype")},
	/* PT_MR_GET_VARIANTS */ {PT_LC("getvariants")},
	/* PT_MR_GET_ONLY_VARIANT */ {PT_LC("getonlyvariant")},
	/* PT_MR_GET_NAMED_ARGUMENTS_VARIANTS */ {PT_LC("getnamedargumentsvariants")},
	/* PT_MR_GET_DECLARING_CLASS */ {PT_LC("getdeclaringclass")},
	/* PT_MR_IS_STATIC */ {PT_LC("isstatic")},
	/* PT_MR_IS_PRIVATE */ {PT_LC("isprivate")},
	/* PT_MR_IS_PUBLIC */ {PT_LC("ispublic")},
	/* PT_MR_GET_DOC_COMMENT */ {PT_LC("getdoccomment")},
	/* PT_MR_IS_DEPRECATED */ {PT_LC("isdeprecated")},
	/* PT_MR_GET_DEPRECATED_DESCRIPTION */ {PT_LC("getdeprecateddescription")},
	/* PT_MR_IS_FINAL */ {PT_LC("isfinal")},
	/* PT_MR_IS_FINAL_BY_KEYWORD */ {PT_LC("isfinalbykeyword")},
	/* PT_MR_IS_INTERNAL */ {PT_LC("isinternal")},
	/* PT_MR_IS_BUILTIN */ {PT_LC("isbuiltin")},
	/* PT_MR_GET_THROW_TYPE */ {PT_LC("getthrowtype")},
	/* PT_MR_HAS_SIDE_EFFECTS */ {PT_LC("hassideeffects")},
	/* PT_MR_IS_PURE */ {PT_LC("ispure")},
	/* PT_MR_GET_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETERS */ {PT_LC("getpureunlesscallableisimpureparameters")},
	/* PT_MR_GET_ASSERTS */ {PT_LC("getasserts")},
	/* PT_MR_ACCEPTS_NAMED_ARGUMENTS */ {PT_LC("acceptsnamedarguments")},
	/* PT_MR_GET_SELF_OUT_TYPE */ {PT_LC("getselfouttype")},
	/* PT_MR_RETURNS_BY_REFERENCE */ {PT_LC("returnsbyreference")},
	/* PT_MR_IS_ABSTRACT */ {PT_LC("isabstract")},
	/* PT_MR_GET_ATTRIBUTES */ {PT_LC("getattributes")},
	/* PT_MR_MUST_USE_RETURN_VALUE */ {PT_LC("mustusereturnvalue")},
	/* PT_MR_GET_RESOLVED_PHP_DOC */ {PT_LC("getresolvedphpdoc")},
};

pt_method_site memberSites[PT_MR_MEMBER_COUNT];

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\ResolvedMethodReflection; UNDEF = pending
 * exception. */
class ResolvedMethodReflection
{
public:
	explicit ResolvedMethodReflection(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *reflection, zval *resolvedTemplateTypeMap, zval *callSiteVarianceMap) const
	{
		pt_write_slot(self, slots::reflection, reflection);
		pt_write_slot(self, slots::resolvedTemplateTypeMap, resolvedTemplateTypeMap);
		pt_write_slot(self, slots::callSiteVarianceMap, callSiteVarianceMap);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *reflection, zval *resolvedTemplateTypeMap, zval *callSiteVarianceMap)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_resolved_method_reflection) != SUCCESS)) return zv::Val();
		ResolvedMethodReflection(Z_OBJ(object)).construct(reflection, resolvedTemplateTypeMap, callSiteVarianceMap);
		return zv::Val::adopt(object);
	}

	zv::Val getName() const { return delegate(PT_MR_GET_NAME); }
	zv::Val getPrototype() const { return delegate(PT_MR_GET_PROTOTYPE); }

	/* Mirrors getVariants(). */
	zv::Val getVariants() const
	{
		zval *variants = slot(slots::variants, "variants");
		if (UNEXPECTED(variants == NULL)) return zv::Val();
		if (Z_TYPE_P(variants) != IS_NULL) return zv::Val::copyOf(zv::Ref(variants));

		zv::Val innerVariants = delegate(PT_MR_GET_VARIANTS);
		if (UNEXPECTED(innerVariants.isUndef())) return zv::Val();
		zv::Val resolved = resolveVariants(innerVariants.raw());
		if (UNEXPECTED(resolved.isUndef())) return zv::Val();
		pt_write_slot(self, slots::variants, resolved.raw());
		return resolved;
	}

	/* Mirrors getOnlyVariant(): $this->getVariants()[0]. */
	zv::Val getOnlyVariant() const
	{
		zv::Val variants = getVariants();
		if (UNEXPECTED(variants.isUndef())) return zv::Val();
		zval *variant = zend_hash_index_find(Z_ARRVAL_P(variants.raw()), 0);
		if (UNEXPECTED(variant == NULL)) {
			zend_error(E_WARNING, "Undefined array key 0");
			if (UNEXPECTED(EG(exception))) return zv::Val();
			zend_type_error("%s::getOnlyVariant(): Return value must be of type PHPStan\\Reflection\\ExtendedParametersAcceptor, null returned", ZSTR_VAL(self->ce->name));
			return zv::Val();
		}
		return zv::Val::copyOf(zv::Ref(variant));
	}

	/* Mirrors getNamedArgumentsVariants(). */
	zv::Val getNamedArgumentsVariants() const
	{
		zval *variants = slot(slots::namedArgumentVariants, "namedArgumentVariants");
		if (UNEXPECTED(variants == NULL)) return zv::Val();
		if (Z_TYPE_P(variants) != IS_NULL) return zv::Val::copyOf(zv::Ref(variants));

		zv::Val innerVariants = delegate(PT_MR_GET_NAMED_ARGUMENTS_VARIANTS);
		if (UNEXPECTED(innerVariants.isUndef())) return zv::Val();
		if (innerVariants.isNull()) return innerVariants;
		zv::Val resolved = resolveVariants(innerVariants.raw());
		if (UNEXPECTED(resolved.isUndef())) return zv::Val();
		pt_write_slot(self, slots::namedArgumentVariants, resolved.raw());
		return resolved;
	}

	/* Mirrors resolveVariants(): a ResolvedFunctionVariantWithOriginal over
	 * each variant, in a list. */
	zv::Val resolveVariants(zval *variants) const
	{
		HashTable *table = Z_ARRVAL_P(variants);
		zv::Arr result = zv::Arr::create(zend_hash_num_elements(table));
		for (auto entry : zv::TableRef(table)) {
			zval *resolvedTemplateTypeMap = slot(slots::resolvedTemplateTypeMap, "resolvedTemplateTypeMap");
			if (UNEXPECTED(resolvedTemplateTypeMap == NULL)) return zv::Val();
			zval *callSiteVarianceMap = slot(slots::callSiteVarianceMap, "callSiteVarianceMap");
			if (UNEXPECTED(callSiteVarianceMap == NULL)) return zv::Val();
			zval passedArgs;
			ZVAL_EMPTY_ARRAY(&passedArgs);
			zv::Args argv{entry.value().deref().raw(), resolvedTemplateTypeMap, callSiteVarianceMap, &passedArgs};
			zv::Val variant = pt_type_new(PT_CLASS_RESOLVED_FUNCTION_VARIANT_WITH_ORIGINAL, 4, argv);
			if (UNEXPECTED(variant.isUndef())) return zv::Val();
			result.push(std::move(variant));
		}
		return zv::Val(std::move(result));
	}

	zv::Val getDeclaringClass() const { return delegate(PT_MR_GET_DECLARING_CLASS); }
	zv::Val isStatic() const { return delegate(PT_MR_IS_STATIC); }
	zv::Val isPrivate() const { return delegate(PT_MR_IS_PRIVATE); }
	zv::Val isPublic() const { return delegate(PT_MR_IS_PUBLIC); }
	zv::Val getDocComment() const { return delegate(PT_MR_GET_DOC_COMMENT); }
	zv::Val isDeprecated() const { return delegate(PT_MR_IS_DEPRECATED); }
	zv::Val getDeprecatedDescription() const { return delegate(PT_MR_GET_DEPRECATED_DESCRIPTION); }
	zv::Val isFinal() const { return delegate(PT_MR_IS_FINAL); }
	zv::Val isFinalByKeyword() const { return delegate(PT_MR_IS_FINAL_BY_KEYWORD); }
	zv::Val isInternal() const { return delegate(PT_MR_IS_INTERNAL); }

	/* Mirrors isBuiltin(): a bool answer as the TrinaryLogic singleton. */
	zv::Val isBuiltin() const { return trinaryFromBool(delegate(PT_MR_IS_BUILTIN)); }

	zv::Val getThrowType() const { return delegate(PT_MR_GET_THROW_TYPE); }

	/* Mirrors hasSideEffects(): $this->hasSideEffects ??= ... */
	zv::Val hasSideEffects() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::hasSideEffects);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));
		zv::Val hasSideEffects = delegate(PT_MR_HAS_SIDE_EFFECTS);
		if (UNEXPECTED(hasSideEffects.isUndef())) return zv::Val();
		pt_write_slot(self, slots::hasSideEffects, hasSideEffects.raw());
		return hasSideEffects;
	}

	zv::Val isPure() const { return delegate(PT_MR_IS_PURE); }
	zv::Val getPureUnlessCallableIsImpureParameters() const { return delegate(PT_MR_GET_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETERS); }

	/* Mirrors getAsserts(): $this->asserts ??= the wrapped reflection's
	 * asserts with their types resolved against the template maps */
	zv::Val getAsserts() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::asserts);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));
		zv::Val asserts = delegate(PT_MR_GET_ASSERTS);
		if (UNEXPECTED(asserts.isUndef())) return zv::Val();
		zv::Val callback = pt_native_closure(&resolveAssertTypeBody, self);
		zv::Val mapped = assertionsMapTypes(asserts.raw(), callback.raw());
		if (UNEXPECTED(mapped.isUndef())) return zv::Val();
		pt_write_slot(self, slots::asserts, mapped.raw());
		return mapped;
	}

	zv::Val acceptsNamedArguments() const { return delegate(PT_MR_ACCEPTS_NAMED_ARGUMENTS); }

	/* Mirrors getSelfOutType(): memoized with false for "not resolved yet" */
	zv::Val getSelfOutType() const
	{
		zval *memo = slot(slots::selfOutType, "selfOutType");
		if (UNEXPECTED(memo == NULL)) return zv::Val();
		if (Z_TYPE_P(memo) != IS_FALSE) return zv::Val::copyOf(zv::Ref(memo));

		zv::Val selfOutType = delegate(PT_MR_GET_SELF_OUT_TYPE);
		if (UNEXPECTED(selfOutType.isUndef())) return zv::Val();
		if (!selfOutType.isNull()) {
			selfOutType = resolveTemplateTypes(selfOutType.raw());
			if (UNEXPECTED(selfOutType.isUndef())) return zv::Val();
		}
		pt_write_slot(self, slots::selfOutType, selfOutType.raw());
		return selfOutType;
	}

	zv::Val returnsByReference() const { return delegate(PT_MR_RETURNS_BY_REFERENCE); }

	/* Mirrors isAbstract(): a bool answer as the TrinaryLogic singleton. */
	zv::Val isAbstract() const { return trinaryFromBool(delegate(PT_MR_IS_ABSTRACT)); }

	zv::Val getAttributes() const { return delegate(PT_MR_GET_ATTRIBUTES); }
	zv::Val mustUseReturnValue() const { return delegate(PT_MR_MUST_USE_RETURN_VALUE); }
	zv::Val getResolvedPhpDoc() const { return delegate(PT_MR_GET_RESOLVED_PHP_DOC); }

private:
	zend_object *self;

	zval *slot(uint32_t index, const char *name) const
	{
		return pt_typed_slot(self, index, self->ce, name);
	}

	/* $this->reflection->method() */
	zv::Val delegate(pt_method_reflection_member member) const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		return pt_extended_method_reflection_call(reflection, member);
	}

	/* is_bool($x) ? TrinaryLogic::createFromBoolean($x) : $x */
	static zv::Val trinaryFromBool(zv::Val value)
	{
		if (value.isUndef()) return value;
		if (Z_TYPE_P(value.raw()) == IS_TRUE) return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(PT_TRI_YES)));
		if (Z_TYPE_P(value.raw()) == IS_FALSE) return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(PT_TRI_NO)));
		return value;
	}

	/* TemplateTypeHelper::resolveTemplateTypes($type, $this->resolvedTemplateTypeMap,
	 * $this->callSiteVarianceMap, TemplateTypeVariance::createInvariant()) */
	zv::Val resolveTemplateTypes(zval *type) const
	{
		zval *resolvedTemplateTypeMap = slot(slots::resolvedTemplateTypeMap, "resolvedTemplateTypeMap");
		if (UNEXPECTED(resolvedTemplateTypeMap == NULL)) return zv::Val();
		zval *callSiteVarianceMap = slot(slots::callSiteVarianceMap, "callSiteVarianceMap");
		if (UNEXPECTED(callSiteVarianceMap == NULL)) return zv::Val();
		zval *invariant = pt_template_type_variance_singleton(PT_TEMPLATE_TYPE_VARIANCE_INVARIANT);
		if (UNEXPECTED(invariant == NULL)) return zv::Val();
		return pt_type_template_type_helper_resolve_template_types(type, resolvedTemplateTypeMap, callSiteVarianceMap, invariant, false);
	}

	/* fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes($type, ...)
	 * — captures: $this */
	static void resolveAssertTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Reflection\\ResolvedMethodReflection::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		zend_class_entry *typeCe = pt_class(PT_CLASS_TYPE);
		if (UNEXPECTED(typeCe == NULL)) return;
		if (UNEXPECTED(Z_TYPE(argv[0]) != IS_OBJECT || !instanceof_function(Z_OBJCE(argv[0]), typeCe))) {
			zend_type_error("PHPStan\\Reflection\\ResolvedMethodReflection::{closure}(): Argument #1 ($type) must be of type PHPStan\\Type\\Type, %s given", zend_zval_value_name(&argv[0]));
			return;
		}
		zv::Val type = ResolvedMethodReflection(Z_OBJ(captures[0])).resolveTemplateTypes(&argv[0]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ResolvedMethodReflection;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_resolved_method_reflection_new(zval *reflection, zval *resolvedTemplateTypeMap, zval *callSiteVarianceMap)
{
	return ResolvedMethodReflection::create(reflection, resolvedTemplateTypeMap, callSiteVarianceMap);
}

zv::Val pt_resolved_method_reflection_call(zend_object *method, pt_method_reflection_member member)
{
	ResolvedMethodReflection reflection(method);
	switch (member) {
		case PT_MR_GET_NAME: return reflection.getName();
		case PT_MR_GET_PROTOTYPE: return reflection.getPrototype();
		case PT_MR_GET_VARIANTS: return reflection.getVariants();
		case PT_MR_GET_ONLY_VARIANT: return reflection.getOnlyVariant();
		case PT_MR_GET_NAMED_ARGUMENTS_VARIANTS: return reflection.getNamedArgumentsVariants();
		case PT_MR_GET_DECLARING_CLASS: return reflection.getDeclaringClass();
		case PT_MR_IS_STATIC: return reflection.isStatic();
		case PT_MR_IS_PRIVATE: return reflection.isPrivate();
		case PT_MR_IS_PUBLIC: return reflection.isPublic();
		case PT_MR_GET_DOC_COMMENT: return reflection.getDocComment();
		case PT_MR_IS_DEPRECATED: return reflection.isDeprecated();
		case PT_MR_GET_DEPRECATED_DESCRIPTION: return reflection.getDeprecatedDescription();
		case PT_MR_IS_FINAL: return reflection.isFinal();
		case PT_MR_IS_FINAL_BY_KEYWORD: return reflection.isFinalByKeyword();
		case PT_MR_IS_INTERNAL: return reflection.isInternal();
		case PT_MR_IS_BUILTIN: return reflection.isBuiltin();
		case PT_MR_GET_THROW_TYPE: return reflection.getThrowType();
		case PT_MR_HAS_SIDE_EFFECTS: return reflection.hasSideEffects();
		case PT_MR_IS_PURE: return reflection.isPure();
		case PT_MR_GET_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETERS: return reflection.getPureUnlessCallableIsImpureParameters();
		case PT_MR_GET_ASSERTS: return reflection.getAsserts();
		case PT_MR_ACCEPTS_NAMED_ARGUMENTS: return reflection.acceptsNamedArguments();
		case PT_MR_GET_SELF_OUT_TYPE: return reflection.getSelfOutType();
		case PT_MR_RETURNS_BY_REFERENCE: return reflection.returnsByReference();
		case PT_MR_IS_ABSTRACT: return reflection.isAbstract();
		case PT_MR_GET_ATTRIBUTES: return reflection.getAttributes();
		case PT_MR_MUST_USE_RETURN_VALUE: return reflection.mustUseReturnValue();
		case PT_MR_GET_RESOLVED_PHP_DOC: return reflection.getResolvedPhpDoc();
		case PT_MR_MEMBER_COUNT: break;
	}
	ZEND_UNREACHABLE();
	return zv::Val();
}

zv::Val pt_extended_method_reflection_call(zval *method, pt_method_reflection_member member)
{
	zend_object *object = Z_OBJ_P(method);
	if (EXPECTED(object->ce == pt_ce_resolved_method_reflection)) return pt_resolved_method_reflection_call(object, member);
	if (object->ce == pt_ce_changed_type_method_reflection) return pt_changed_type_method_reflection_call(object, member);
	const MemberName &name = memberNames[member];
	return pt_call_method_cached(memberSites[member], object, name.lcname, name.len, 0, NULL);
}

zend_long pt_extended_method_reflection_trinary(zval *method, pt_method_reflection_member member)
{
	zv::Val result = pt_extended_method_reflection_call(method, member);
	if (UNEXPECTED(result.isUndef())) return -1;
	return pt_type_trinary_value(result.raw());
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_resolved_method_reflection()
{
	reg::Class cls("PHPStan\\Reflection\\ResolvedMethodReflection");
	ptdecl::ResolvedMethodReflection::declareClass(cls);
	ptdecl::ResolvedMethodReflection::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflection, *resolvedTemplateTypeMap, *callSiteVarianceMap;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, reflection, resolvedTemplateTypeMap, callSiteVarianceMap)) RETURN_THROWS();
		ResolvedMethodReflection(Z_OBJ_P(ZEND_THIS)).construct(reflection, resolvedTemplateTypeMap, callSiteVarianceMap);
	});

	cls.method<&ResolvedMethodReflection::getName>(sigs::getName);
	cls.method<&ResolvedMethodReflection::getPrototype>(sigs::getPrototype);
	cls.method<&ResolvedMethodReflection::getVariants>(sigs::getVariants);
	cls.method<&ResolvedMethodReflection::getOnlyVariant>(sigs::getOnlyVariant);
	cls.method<&ResolvedMethodReflection::getNamedArgumentsVariants>(sigs::getNamedArgumentsVariants);
	cls.method<&ResolvedMethodReflection::resolveVariants, zp::Arr>(sigs::resolveVariants);
	cls.method<&ResolvedMethodReflection::getDeclaringClass>(sigs::getDeclaringClass);
	cls.method<&ResolvedMethodReflection::isStatic>(sigs::isStatic);
	cls.method<&ResolvedMethodReflection::isPrivate>(sigs::isPrivate);
	cls.method<&ResolvedMethodReflection::isPublic>(sigs::isPublic);
	cls.method<&ResolvedMethodReflection::getDocComment>(sigs::getDocComment);
	cls.method<&ResolvedMethodReflection::isDeprecated>(sigs::isDeprecated);
	cls.method<&ResolvedMethodReflection::getDeprecatedDescription>(sigs::getDeprecatedDescription);
	cls.method<&ResolvedMethodReflection::isFinal>(sigs::isFinal);
	cls.method<&ResolvedMethodReflection::isFinalByKeyword>(sigs::isFinalByKeyword);
	cls.method<&ResolvedMethodReflection::isInternal>(sigs::isInternal);
	cls.method<&ResolvedMethodReflection::isBuiltin>(sigs::isBuiltin);
	cls.method<&ResolvedMethodReflection::getThrowType>(sigs::getThrowType);
	cls.method<&ResolvedMethodReflection::hasSideEffects>(sigs::hasSideEffects);
	cls.method<&ResolvedMethodReflection::isPure>(sigs::isPure);
	cls.method<&ResolvedMethodReflection::getPureUnlessCallableIsImpureParameters>(sigs::getPureUnlessCallableIsImpureParameters);
	cls.method<&ResolvedMethodReflection::getAsserts>(sigs::getAsserts);
	cls.method<&ResolvedMethodReflection::acceptsNamedArguments>(sigs::acceptsNamedArguments);
	cls.method<&ResolvedMethodReflection::getSelfOutType>(sigs::getSelfOutType);
	cls.method<&ResolvedMethodReflection::returnsByReference>(sigs::returnsByReference);
	cls.method<&ResolvedMethodReflection::isAbstract>(sigs::isAbstract);
	cls.method<&ResolvedMethodReflection::getAttributes>(sigs::getAttributes);
	cls.method<&ResolvedMethodReflection::mustUseReturnValue>(sigs::mustUseReturnValue);
	cls.method<&ResolvedMethodReflection::getResolvedPhpDoc>(sigs::getResolvedPhpDoc);

	cls.shadow(&pt_ce_resolved_method_reflection);
}

/* }}} */
