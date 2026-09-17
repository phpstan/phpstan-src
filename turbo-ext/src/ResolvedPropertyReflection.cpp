/*
 * PHPStanTurbo\ResolvedPropertyReflection — native implementation of
 * PHPStan\Reflection\ResolvedPropertyReflection.
 *
 * The property reflection a member prototype hands out (getTransformedProperty())
 * — what a property fetch's reflection is for nearly every fetch: the wrapped
 * reflection (almost always a ChangedTypePropertyReflection) with its readable
 * and writable types resolved against the called-on type's template map,
 * memoized in the twin's own slots; everything else is delegated. The
 * delegations reach the native ChangedTypePropertyReflection /
 * PhpPropertyReflection without a frame and anything else through one cached
 * method site per member: pt_extended_property_reflection_call(), defined
 * here, is that dispatch for any property reflection, which native callers
 * use.
 */

#include "support.h"
#include "generated/ResolvedPropertyReflection.h"

namespace slots = ptdecl::ResolvedPropertyReflection::slot;
namespace sigs = ptdecl::ResolvedPropertyReflection::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"

zend_class_entry *pt_ce_resolved_property_reflection = nullptr;

namespace {

/* {{{ the members of any property reflection (pt_extended_property_reflection_call()) */

struct MemberName
{
	const char *lcname;
	size_t len;
};

const MemberName memberNames[PT_PROP_MEMBER_COUNT] = {
	/* PT_PROP_GET_NAME */ {PT_LC("getname")},
	/* PT_PROP_GET_DECLARING_CLASS */ {PT_LC("getdeclaringclass")},
	/* PT_PROP_IS_STATIC */ {PT_LC("isstatic")},
	/* PT_PROP_IS_PRIVATE */ {PT_LC("isprivate")},
	/* PT_PROP_IS_PUBLIC */ {PT_LC("ispublic")},
	/* PT_PROP_GET_DOC_COMMENT */ {PT_LC("getdoccomment")},
	/* PT_PROP_GET_READABLE_TYPE */ {PT_LC("getreadabletype")},
	/* PT_PROP_GET_WRITABLE_TYPE */ {PT_LC("getwritabletype")},
	/* PT_PROP_CAN_CHANGE_TYPE_AFTER_ASSIGNMENT */ {PT_LC("canchangetypeafterassignment")},
	/* PT_PROP_IS_READABLE */ {PT_LC("isreadable")},
	/* PT_PROP_IS_WRITABLE */ {PT_LC("iswritable")},
	/* PT_PROP_IS_DEPRECATED */ {PT_LC("isdeprecated")},
	/* PT_PROP_GET_DEPRECATED_DESCRIPTION */ {PT_LC("getdeprecateddescription")},
	/* PT_PROP_IS_INTERNAL */ {PT_LC("isinternal")},
	/* PT_PROP_HAS_PHP_DOC_TYPE */ {PT_LC("hasphpdoctype")},
	/* PT_PROP_GET_PHP_DOC_TYPE */ {PT_LC("getphpdoctype")},
	/* PT_PROP_HAS_NATIVE_TYPE */ {PT_LC("hasnativetype")},
	/* PT_PROP_GET_NATIVE_TYPE */ {PT_LC("getnativetype")},
	/* PT_PROP_IS_ABSTRACT */ {PT_LC("isabstract")},
	/* PT_PROP_IS_FINAL_BY_KEYWORD */ {PT_LC("isfinalbykeyword")},
	/* PT_PROP_IS_FINAL */ {PT_LC("isfinal")},
	/* PT_PROP_IS_VIRTUAL */ {PT_LC("isvirtual")},
	/* PT_PROP_IS_PROTECTED_SET */ {PT_LC("isprotectedset")},
	/* PT_PROP_IS_PRIVATE_SET */ {PT_LC("isprivateset")},
	/* PT_PROP_GET_ATTRIBUTES */ {PT_LC("getattributes")},
	/* PT_PROP_IS_DUMMY */ {PT_LC("isdummy")},
};

pt_method_site memberSites[PT_PROP_MEMBER_COUNT];
pt_method_site pt_prr_has_hook_site;
pt_method_site pt_prr_get_hook_site;

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\ResolvedPropertyReflection; UNDEF = pending
 * exception. */
class ResolvedPropertyReflection
{
public:
	explicit ResolvedPropertyReflection(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *reflection, zval *templateTypeMap, zval *callSiteVarianceMap) const
	{
		pt_write_slot(self, slots::reflection, reflection);
		pt_write_slot(self, slots::templateTypeMap, templateTypeMap);
		pt_write_slot(self, slots::callSiteVarianceMap, callSiteVarianceMap);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *reflection, zval *templateTypeMap, zval *callSiteVarianceMap)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_resolved_property_reflection) != SUCCESS)) return zv::Val();
		ResolvedPropertyReflection(Z_OBJ(object)).construct(reflection, templateTypeMap, callSiteVarianceMap);
		return zv::Val::adopt(object);
	}

	zv::Val getName() const { return delegate(PT_PROP_GET_NAME); }
	zv::Val getOriginalReflection() const { return copy(slot(slots::reflection, "reflection")); }
	zv::Val getDeclaringClass() const { return delegate(PT_PROP_GET_DECLARING_CLASS); }
	zv::Val isStatic() const { return delegate(PT_PROP_IS_STATIC); }
	zv::Val isPrivate() const { return delegate(PT_PROP_IS_PRIVATE); }
	zv::Val isPublic() const { return delegate(PT_PROP_IS_PUBLIC); }
	zv::Val hasPhpDocType() const { return delegate(PT_PROP_HAS_PHP_DOC_TYPE); }
	zv::Val getPhpDocType() const { return delegate(PT_PROP_GET_PHP_DOC_TYPE); }
	zv::Val hasNativeType() const { return delegate(PT_PROP_HAS_NATIVE_TYPE); }
	zv::Val getNativeType() const { return delegate(PT_PROP_GET_NATIVE_TYPE); }

	/* Mirrors getReadableType(): the wrapped readable type resolved twice
	 * against the template map (covariant), memoized */
	zv::Val getReadableType() const { return resolvedType(slots::readableType, PT_PROP_GET_READABLE_TYPE, PT_TEMPLATE_TYPE_VARIANCE_COVARIANT); }

	/* Mirrors getWritableType(): the same, contravariant */
	zv::Val getWritableType() const { return resolvedType(slots::writableType, PT_PROP_GET_WRITABLE_TYPE, PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT); }

	zv::Val canChangeTypeAfterAssignment() const { return delegate(PT_PROP_CAN_CHANGE_TYPE_AFTER_ASSIGNMENT); }
	zv::Val isReadable() const { return delegate(PT_PROP_IS_READABLE); }
	zv::Val isWritable() const { return delegate(PT_PROP_IS_WRITABLE); }
	zv::Val getDocComment() const { return delegate(PT_PROP_GET_DOC_COMMENT); }
	zv::Val isDeprecated() const { return delegate(PT_PROP_IS_DEPRECATED); }
	zv::Val getDeprecatedDescription() const { return delegate(PT_PROP_GET_DEPRECATED_DESCRIPTION); }
	zv::Val isInternal() const { return delegate(PT_PROP_IS_INTERNAL); }
	zv::Val isAbstract() const { return delegate(PT_PROP_IS_ABSTRACT); }
	zv::Val isFinalByKeyword() const { return delegate(PT_PROP_IS_FINAL_BY_KEYWORD); }
	zv::Val isFinal() const { return delegate(PT_PROP_IS_FINAL); }
	zv::Val isVirtual() const { return delegate(PT_PROP_IS_VIRTUAL); }

	bool hasHook(zend_string *hookType, bool &out) const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		return EXPECTED(reflection != NULL) && pt_extended_property_reflection_has_hook(reflection, hookType, out);
	}

	/* Mirrors getHook(): new ResolvedMethodReflection($this->reflection->getHook($hookType), $this->templateTypeMap, $this->callSiteVarianceMap) */
	zv::Val getHook(zend_string *hookType) const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		zv::Val hook = pt_extended_property_reflection_get_hook(reflection, hookType);
		if (UNEXPECTED(hook.isUndef())) return zv::Val();
		zval *templateTypeMap = slot(slots::templateTypeMap, "templateTypeMap");
		if (UNEXPECTED(templateTypeMap == NULL)) return zv::Val();
		zval *callSiteVarianceMap = slot(slots::callSiteVarianceMap, "callSiteVarianceMap");
		if (UNEXPECTED(callSiteVarianceMap == NULL)) return zv::Val();
		return pt_resolved_method_reflection_new(hook.raw(), templateTypeMap, callSiteVarianceMap);
	}

	zv::Val isProtectedSet() const { return delegate(PT_PROP_IS_PROTECTED_SET); }
	zv::Val isPrivateSet() const { return delegate(PT_PROP_IS_PRIVATE_SET); }
	zv::Val getAttributes() const { return delegate(PT_PROP_GET_ATTRIBUTES); }
	zv::Val isDummy() const { return delegate(PT_PROP_IS_DUMMY); }

private:
	zend_object *self;

	zval *slot(uint32_t index, const char *name) const
	{
		return pt_typed_slot(self, index, self->ce, name);
	}

	static zv::Val copy(zval *slot)
	{
		return slot != NULL ? zv::Val::copyOf(zv::Ref(slot)) : zv::Val();
	}

	/* $this->reflection->method() */
	zv::Val delegate(pt_property_reflection_member member) const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		return pt_extended_property_reflection_call(reflection, member);
	}

	/* $type = $this->memo; if ($type !== null) return $type; $type =
	 * TemplateTypeHelper::resolveTemplateTypes($this->reflection->get…Type(),
	 * $this->templateTypeMap, $this->callSiteVarianceMap, <variance>); the same
	 * again over $type; $this->memo = $type */
	zv::Val resolvedType(uint32_t memoIndex, pt_property_reflection_member member, zend_long varianceValue) const
	{
		zval *memo = OBJ_PROP_NUM(self, memoIndex);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));

		zv::Val type = delegate(member);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		for (int pass = 0; pass < 2; pass++) {
			zval *templateTypeMap = slot(slots::templateTypeMap, "templateTypeMap");
			if (UNEXPECTED(templateTypeMap == NULL)) return zv::Val();
			zval *callSiteVarianceMap = slot(slots::callSiteVarianceMap, "callSiteVarianceMap");
			if (UNEXPECTED(callSiteVarianceMap == NULL)) return zv::Val();
			zval *variance = pt_template_type_variance_singleton(varianceValue);
			if (UNEXPECTED(variance == NULL)) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(type.raw()) != IS_OBJECT)) {
				zend_type_error("PHPStan\\Type\\Generic\\TemplateTypeHelper::resolveTemplateTypes(): Argument #1 ($type) must be of type PHPStan\\Type\\Type, %s given", zend_zval_value_name(type.raw()));
				return zv::Val();
			}
			type = pt_type_template_type_helper_resolve_template_types(type.raw(), templateTypeMap, callSiteVarianceMap, variance, false);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
		}
		pt_write_slot(self, memoIndex, type.raw());
		return type;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ResolvedPropertyReflection;

/* {{{ exported helpers: the shadowing class and the property-reflection dispatch */

zv::Val pt_resolved_property_reflection_new(zval *reflection, zval *templateTypeMap, zval *callSiteVarianceMap)
{
	return ResolvedPropertyReflection::create(reflection, templateTypeMap, callSiteVarianceMap);
}

zv::Val pt_resolved_property_reflection_call(zend_object *property, pt_property_reflection_member member)
{
	ResolvedPropertyReflection reflection(property);
	switch (member) {
		case PT_PROP_GET_NAME: return reflection.getName();
		case PT_PROP_GET_DECLARING_CLASS: return reflection.getDeclaringClass();
		case PT_PROP_IS_STATIC: return reflection.isStatic();
		case PT_PROP_IS_PRIVATE: return reflection.isPrivate();
		case PT_PROP_IS_PUBLIC: return reflection.isPublic();
		case PT_PROP_GET_DOC_COMMENT: return reflection.getDocComment();
		case PT_PROP_GET_READABLE_TYPE: return reflection.getReadableType();
		case PT_PROP_GET_WRITABLE_TYPE: return reflection.getWritableType();
		case PT_PROP_CAN_CHANGE_TYPE_AFTER_ASSIGNMENT: return reflection.canChangeTypeAfterAssignment();
		case PT_PROP_IS_READABLE: return reflection.isReadable();
		case PT_PROP_IS_WRITABLE: return reflection.isWritable();
		case PT_PROP_IS_DEPRECATED: return reflection.isDeprecated();
		case PT_PROP_GET_DEPRECATED_DESCRIPTION: return reflection.getDeprecatedDescription();
		case PT_PROP_IS_INTERNAL: return reflection.isInternal();
		case PT_PROP_HAS_PHP_DOC_TYPE: return reflection.hasPhpDocType();
		case PT_PROP_GET_PHP_DOC_TYPE: return reflection.getPhpDocType();
		case PT_PROP_HAS_NATIVE_TYPE: return reflection.hasNativeType();
		case PT_PROP_GET_NATIVE_TYPE: return reflection.getNativeType();
		case PT_PROP_IS_ABSTRACT: return reflection.isAbstract();
		case PT_PROP_IS_FINAL_BY_KEYWORD: return reflection.isFinalByKeyword();
		case PT_PROP_IS_FINAL: return reflection.isFinal();
		case PT_PROP_IS_VIRTUAL: return reflection.isVirtual();
		case PT_PROP_IS_PROTECTED_SET: return reflection.isProtectedSet();
		case PT_PROP_IS_PRIVATE_SET: return reflection.isPrivateSet();
		case PT_PROP_GET_ATTRIBUTES: return reflection.getAttributes();
		case PT_PROP_IS_DUMMY: return reflection.isDummy();
		case PT_PROP_MEMBER_COUNT: break;
	}
	ZEND_UNREACHABLE();
	return zv::Val();
}

zv::Val pt_extended_property_reflection_call(zval *property, pt_property_reflection_member member)
{
	if (UNEXPECTED(Z_TYPE_P(property) != IS_OBJECT)) {
		const MemberName &name = memberNames[member];
		zend_throw_error(NULL, "Call to a member function %s() on %s", name.lcname, zend_zval_value_name(property));
		return zv::Val();
	}
	zend_object *object = Z_OBJ_P(property);
	if (EXPECTED(object->ce == pt_ce_resolved_property_reflection)) return pt_resolved_property_reflection_call(object, member);
	if (object->ce == pt_ce_changed_type_property_reflection) return pt_changed_type_property_reflection_call(object, member);
	if (object->ce == pt_ce_php_property_reflection) return pt_php_property_reflection_call(object, member);
	const MemberName &name = memberNames[member];
	return pt_call_method_cached(memberSites[member], object, name.lcname, name.len, 0, NULL);
}

bool pt_extended_property_reflection_bool(zval *property, pt_property_reflection_member member, bool &out)
{
	zv::Val result = pt_extended_property_reflection_call(property, member);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

bool pt_extended_property_reflection_has_hook(zval *property, zend_string *hookType, bool &out)
{
	if (EXPECTED(Z_TYPE_P(property) == IS_OBJECT)) {
		zend_object *object = Z_OBJ_P(property);
		if (object->ce == pt_ce_resolved_property_reflection) return ResolvedPropertyReflection(object).hasHook(hookType, out);
		if (object->ce == pt_ce_changed_type_property_reflection) return pt_changed_type_property_reflection_has_hook(object, hookType, out);
		if (object->ce == pt_ce_php_property_reflection) return pt_php_property_reflection_has_hook(object, hookType, out);
		zval hookTypeZv;
		ZVAL_STR(&hookTypeZv, hookType);
		zv::Val result = pt_call_method_cached(pt_prr_has_hook_site, object, PT_LC("hashook"), 1, &hookTypeZv);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}
	zend_throw_error(NULL, "Call to a member function hasHook() on %s", zend_zval_value_name(property));
	return false;
}

zv::Val pt_extended_property_reflection_get_hook(zval *property, zend_string *hookType)
{
	if (EXPECTED(Z_TYPE_P(property) == IS_OBJECT)) {
		zend_object *object = Z_OBJ_P(property);
		if (object->ce == pt_ce_resolved_property_reflection) return ResolvedPropertyReflection(object).getHook(hookType);
		if (object->ce == pt_ce_changed_type_property_reflection) return pt_changed_type_property_reflection_get_hook(object, hookType);
		if (object->ce == pt_ce_php_property_reflection) return pt_php_property_reflection_get_hook(object, hookType);
		zval hookTypeZv;
		ZVAL_STR(&hookTypeZv, hookType);
		return pt_call_method_cached(pt_prr_get_hook_site, object, PT_LC("gethook"), 1, &hookTypeZv);
	}
	zend_throw_error(NULL, "Call to a member function getHook() on %s", zend_zval_value_name(property));
	return zv::Val();
}

namespace {

const MemberName classMemberNames[PT_CMR_MEMBER_COUNT] = {
	/* PT_CMR_GET_DECLARING_CLASS */ {PT_LC("getdeclaringclass")},
	/* PT_CMR_IS_STATIC */ {PT_LC("isstatic")},
	/* PT_CMR_IS_PRIVATE */ {PT_LC("isprivate")},
	/* PT_CMR_IS_PUBLIC */ {PT_LC("ispublic")},
	/* PT_CMR_GET_DOC_COMMENT */ {PT_LC("getdoccomment")},
};

const pt_property_reflection_member classMemberPropertyMembers[PT_CMR_MEMBER_COUNT] = { PT_PROP_GET_DECLARING_CLASS, PT_PROP_IS_STATIC, PT_PROP_IS_PRIVATE, PT_PROP_IS_PUBLIC, PT_PROP_GET_DOC_COMMENT };
const pt_method_reflection_member classMemberMethodMembers[PT_CMR_MEMBER_COUNT] = { PT_MR_GET_DECLARING_CLASS, PT_MR_IS_STATIC, PT_MR_IS_PRIVATE, PT_MR_IS_PUBLIC, PT_MR_GET_DOC_COMMENT };

pt_method_site classMemberSites[PT_CMR_MEMBER_COUNT];

} // namespace

zv::Val pt_class_member_reflection_call(zval *member, pt_class_member_reflection_member which)
{
	if (EXPECTED(Z_TYPE_P(member) == IS_OBJECT)) {
		zend_class_entry *ce = Z_OBJCE_P(member);
		if (ce == pt_ce_resolved_property_reflection || ce == pt_ce_changed_type_property_reflection || ce == pt_ce_php_property_reflection) {
			return pt_extended_property_reflection_call(member, classMemberPropertyMembers[which]);
		}
		if (ce == pt_ce_resolved_method_reflection || ce == pt_ce_changed_type_method_reflection || pt_is_php_method_reflection(ce)) {
			return pt_extended_method_reflection_call(member, classMemberMethodMembers[which]);
		}
		const MemberName &name = classMemberNames[which];
		return pt_call_method_cached(classMemberSites[which], Z_OBJ_P(member), name.lcname, name.len, 0, NULL);
	}
	zend_throw_error(NULL, "Call to a member function %s() on %s", classMemberNames[which].lcname, zend_zval_value_name(member));
	return zv::Val();
}

bool pt_class_member_reflection_bool(zval *member, pt_class_member_reflection_member which, bool &out)
{
	zv::Val result = pt_class_member_reflection_call(member, which);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

zv::Val pt_resolved_property_reflection_get_original_reflection(zend_object *property)
{
	return ResolvedPropertyReflection(property).getOriginalReflection();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_resolved_property_reflection()
{
	reg::Class cls("PHPStan\\Reflection\\ResolvedPropertyReflection");
	ptdecl::ResolvedPropertyReflection::declareClass(cls);
	ptdecl::ResolvedPropertyReflection::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflection, *templateTypeMap, *callSiteVarianceMap;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, reflection, templateTypeMap, callSiteVarianceMap)) RETURN_THROWS();
		ResolvedPropertyReflection(Z_OBJ_P(ZEND_THIS)).construct(reflection, templateTypeMap, callSiteVarianceMap);
	});

	cls.method<&ResolvedPropertyReflection::getName>(sigs::getName);
	cls.op<PT_OP_GET_NAME, &ResolvedPropertyReflection::getName>();
	cls.method<&ResolvedPropertyReflection::getOriginalReflection>(sigs::getOriginalReflection);
	cls.method<&ResolvedPropertyReflection::getDeclaringClass>(sigs::getDeclaringClass);
	cls.method<&ResolvedPropertyReflection::isStatic>(sigs::isStatic);
	cls.method<&ResolvedPropertyReflection::isPrivate>(sigs::isPrivate);
	cls.method<&ResolvedPropertyReflection::isPublic>(sigs::isPublic);
	cls.op<PT_OP_IS_PUBLIC, &ResolvedPropertyReflection::isPublic>();
	cls.method<&ResolvedPropertyReflection::hasPhpDocType>(sigs::hasPhpDocType);
	cls.method<&ResolvedPropertyReflection::getPhpDocType>(sigs::getPhpDocType);
	cls.method<&ResolvedPropertyReflection::hasNativeType>(sigs::hasNativeType);
	cls.method<&ResolvedPropertyReflection::getNativeType>(sigs::getNativeType);
	cls.method<&ResolvedPropertyReflection::getReadableType>(sigs::getReadableType);
	cls.method<&ResolvedPropertyReflection::getWritableType>(sigs::getWritableType);
	cls.method<&ResolvedPropertyReflection::canChangeTypeAfterAssignment>(sigs::canChangeTypeAfterAssignment);
	cls.method<&ResolvedPropertyReflection::isReadable>(sigs::isReadable);
	cls.method<&ResolvedPropertyReflection::isWritable>(sigs::isWritable);
	cls.method<&ResolvedPropertyReflection::getDocComment>(sigs::getDocComment);
	cls.method<&ResolvedPropertyReflection::isDeprecated>(sigs::isDeprecated);
	cls.method<&ResolvedPropertyReflection::getDeprecatedDescription>(sigs::getDeprecatedDescription);
	cls.method<&ResolvedPropertyReflection::isInternal>(sigs::isInternal);
	cls.method<&ResolvedPropertyReflection::isAbstract>(sigs::isAbstract);
	cls.method<&ResolvedPropertyReflection::isFinalByKeyword>(sigs::isFinalByKeyword);
	cls.op<PT_OP_IS_FINAL_BY_KEYWORD, &ResolvedPropertyReflection::isFinalByKeyword>();
	cls.method<&ResolvedPropertyReflection::isFinal>(sigs::isFinal);
	cls.op<PT_OP_IS_FINAL, &ResolvedPropertyReflection::isFinal>();
	cls.method<&ResolvedPropertyReflection::isVirtual>(sigs::isVirtual);
	cls.method<&ResolvedPropertyReflection::hasHook, zp::Str>(sigs::hasHook);
	cls.method<&ResolvedPropertyReflection::getHook, zp::Str>(sigs::getHook);
	cls.method<&ResolvedPropertyReflection::isProtectedSet>(sigs::isProtectedSet);
	cls.method<&ResolvedPropertyReflection::isPrivateSet>(sigs::isPrivateSet);
	cls.method<&ResolvedPropertyReflection::getAttributes>(sigs::getAttributes);
	cls.method<&ResolvedPropertyReflection::isDummy>(sigs::isDummy);

	cls.shadow(&pt_ce_resolved_property_reflection);
}

/* }}} */
