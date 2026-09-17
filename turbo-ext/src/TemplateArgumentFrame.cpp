/*
 * PHPStanTurbo\TemplateArgumentFrame — native implementation of
 * PHPStan\Analyser\Generics\TemplateArgumentFrame.
 *
 * The template inference context a scope carries: isObserving() is asked
 * ~650K times per self-analysis and returnTypeOfCall() ~145K times (by the
 * call handlers and the native MutatingScope). State lives in the twin's
 * three readonly promoted property slots, in its order. returnTypeOfCall()
 * asks the scope through MutatingScope's direct entries and the acceptor
 * (a PHP ResolvedFunctionVariant) by name; resolveUnconstrained()'s
 * traversal callback and resolveOrUnconstrained()'s resolver are native
 * callback holders (TypeTraits.h).
 */

#include "support.h"
#include "generated/TemplateArgumentFrame.h"

namespace slots = ptdecl::TemplateArgumentFrame::slot;
namespace sigs = ptdecl::TemplateArgumentFrame::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "AcceptorValues.h"

#include "zend_smart_str.h"

zend_class_entry *pt_ce_template_argument_frame = nullptr;

namespace {

/* the twin's SYNTHETIC_SITE_ATTRIBUTE / ORIGINAL_SITE_ATTRIBUTE values */
constexpr const char *pt_taf_synthetic_site = "templateArgumentSyntheticSite";
constexpr const char *pt_taf_original_site = "templateArgumentOriginalSite";
/* ORIGINAL_SITE_ATTRIBUTE as a permanent interned string */
zend_string *pt_taf_original_site_str = nullptr;

/* $object->method() by name; UNDEF = pending exception */
zv::Val call0(zval *object, const char *lcname, size_t len, const char *method)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(object), lcname, len, 0, NULL);
}

void resolveUnconstrainedCallback(zval *site, zval *captured, uint32_t argc, zval *argv, zval *return_value);
void resolveCallback(zval *frame, zval *state1, uint32_t argc, zval *argv, zval *return_value);

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\Generics\TemplateArgumentFrame. */
class TemplateArgumentFrame
{
public:
	explicit TemplateArgumentFrame(zend_object *self) : self(self) {}

	/* Mirrors returnTypeOfCall(); $allowUnresolved -1 for null */
	static zv::Val returnTypeOfCall(zval *acceptor, zval *scope, zval *site, int allowUnresolved)
	{
		zv::Val frame = pt_mutating_scope_get_current_template_argument_frame(Z_OBJ_P(scope));
		if (UNEXPECTED(frame.isUndef())) return zv::Val();
		zend_class_entry *resolvedFunctionVariantCe = Z_TYPE_P(frame.raw()) == IS_NULL ? NULL : pt_class_loaded(PT_CLASS_RESOLVED_FUNCTION_VARIANT);
		if (UNEXPECTED(EG(exception))) return zv::Val();
		if (resolvedFunctionVariantCe == NULL || !instanceof_function(Z_OBJCE_P(acceptor), resolvedFunctionVariantCe)) {
			return pt_parameters_acceptor_call(acceptor, PT_PA_GET_RETURN_TYPE);
		}
		zval *originalSite = pt_node_attribute(Z_OBJ_P(site), pt_taf_original_site_str);
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) return zv::Val();
		zval *callSite = originalSite != NULL && Z_TYPE_P(originalSite) == IS_OBJECT && instanceof_function(Z_OBJCE_P(originalSite), exprCe) ? originalSite : site;

		bool allow;
		if (allowUnresolved >= 0) {
			allow = allowUnresolved != 0;
		} else {
			bool nativeTypesPromoted;
			if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return zv::Val();
			allow = !nativeTypesPromoted;
		}
		return pt_resolved_function_variant_get_return_type_with_unresolved_template_arguments(acceptor, callSite, frame.raw(), allow);
	}

	/* __construct(private readonly ?self $parent, private readonly ?array
	 * $resolutions = null, private readonly array $siteStatementIndexes =
	 * []); NULL for null / []. false = pending exception (a repeated
	 * construction modifies readonly properties) */
	[[nodiscard]] bool construct(zval *parent, zval *resolutions, zval *siteStatementIndexes) const
	{
		if (UNEXPECTED(Z_TYPE_P(OBJ_PROP_NUM(self, slots::parent)) != IS_UNDEF)) {
			zend_throw_error(NULL, "Cannot modify readonly property %s::$parent", ZSTR_VAL(self->ce->name));
			return false;
		}
		zval value = {};
		if (parent != NULL) {
			pt_write_slot(self, slots::parent, parent);
		} else {
			ZVAL_NULL(&value);
			pt_write_slot(self, slots::parent, &value);
		}
		if (resolutions != NULL) {
			pt_write_slot(self, slots::resolutions, resolutions);
		} else {
			ZVAL_NULL(&value);
			pt_write_slot(self, slots::resolutions, &value);
		}
		if (siteStatementIndexes != NULL) {
			pt_write_slot(self, slots::siteStatementIndexes, siteStatementIndexes);
		} else {
			ZVAL_EMPTY_ARRAY(&value);
			pt_write_slot(self, slots::siteStatementIndexes, &value);
		}
		return true;
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *parent, zval *resolutions, zval *siteStatementIndexes)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_template_argument_frame) != SUCCESS)) return zv::Val();
		zv::Val frame = zv::Val::adopt(object);
		if (UNEXPECTED(!TemplateArgumentFrame(Z_OBJ_P(frame.raw())).construct(parent, resolutions, siteStatementIndexes))) return zv::Val();
		return frame;
	}

	/* Mirrors isObserving(); false = pending exception */
	[[nodiscard]] bool isObserving(bool &out) const
	{
		zval *resolutions = pt_typed_slot(self, slots::resolutions, self->ce, "resolutions");
		if (UNEXPECTED(resolutions == NULL)) return false;
		out = Z_TYPE_P(resolutions) == IS_NULL;
		return true;
	}

	/* Mirrors firstSiteStatementIndex(). */
	zv::Val firstSiteStatementIndex() const
	{
		zval *indexes = pt_typed_slot(self, slots::siteStatementIndexes, self->ce, "siteStatementIndexes");
		if (UNEXPECTED(indexes == NULL)) return zv::Val();
		zval first;
		ZVAL_UNDEF(&first);
		for (auto entry : zv::TableRef(Z_ARRVAL_P(indexes))) {
			zval index;
			keyOf(entry, index);
			if (Z_TYPE(first) != IS_UNDEF && zend_compare(&index, &first) >= 0) continue;

			ZVAL_COPY_VALUE(&first, &index);
		}
		if (Z_TYPE(first) == IS_UNDEF) return zv::Val::null();
		return zv::Val::copyOf(zv::Ref(&first));
	}

	/* Mirrors ownsSiteInStatement(): isset($this->siteStatementIndexes[$statementIndex]) */
	bool ownsSiteInStatement(zend_long statementIndex, bool &out) const
	{
		zval *indexes = OBJ_PROP_NUM(self, slots::siteStatementIndexes);
		zval *found = Z_TYPE_P(indexes) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(indexes), (zend_ulong) statementIndex) : NULL;
		out = found != NULL && Z_TYPE_P(found) != IS_NULL;
		return true;
	}

	/* Mirrors hasSiteAtOrAfter(). */
	bool hasSiteAtOrAfter(zend_long statementIndex, bool &out) const
	{
		zval *indexes = pt_typed_slot(self, slots::siteStatementIndexes, self->ce, "siteStatementIndexes");
		if (UNEXPECTED(indexes == NULL)) return false;
		zval threshold;
		ZVAL_LONG(&threshold, statementIndex);
		for (auto entry : zv::TableRef(Z_ARRVAL_P(indexes))) {
			zval index;
			keyOf(entry, index);
			if (zend_compare(&index, &threshold) >= 0) {
				out = true;
				return true;
			}
		}
		out = false;
		return true;
	}

	/* Mirrors resolveOrUnconstrained(). */
	zv::Val resolveOrUnconstrained(zval *site, zval *templateType) const
	{
		zv::Val name = call0(templateType, PT_LC("getname"), "getName");
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(name.raw()) != IS_STRING)) {
			zend_type_error("%s::resolve(): Argument #2 ($templateName) must be of type string, %s given", ZSTR_VAL(self->ce->name), zend_zval_value_name(name.raw()));
			return zv::Val();
		}
		zv::Val resolved = resolve(site, Z_STR_P(name.raw()));
		if (UNEXPECTED(resolved.isUndef())) return zv::Val();
		if (Z_TYPE_P(resolved.raw()) != IS_NULL) return resolved;

		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		zv::Val resolver = pt_type_native_callback(resolveCallback, &selfZv, NULL);
		if (UNEXPECTED(resolver.isUndef())) return zv::Val();
		return resolveUnconstrained(site, templateType, resolver.raw());
	}

	/* Mirrors resolveUnconstrained(). */
	static zv::Val resolveUnconstrained(zval *site, zval *templateType, zval *resolver)
	{
		zv::Val defaultType = call0(templateType, PT_LC("getdefault"), "getDefault");
		if (UNEXPECTED(defaultType.isUndef())) return zv::Val();
		if (Z_TYPE_P(defaultType.raw()) != IS_NULL) return defaultType;

		zv::Val bound = call0(templateType, PT_LC("getbound"), "getBound");
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(bound.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function hasTemplateOrLateResolvableType() on %s", zend_zval_value_name(bound.raw()));
			return zv::Val();
		}
		zv::Val hasTemplate = pt_type_op(Z_OBJ_P(bound.raw()), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
		if (UNEXPECTED(hasTemplate.isUndef())) return zv::Val();
		if (!zend_is_true(hasTemplate.raw())) return bound;

		zv::Val scope = call0(templateType, PT_LC("getscope"), "getScope");
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		/* use ($site, $scope, $resolve): the site in the first state slot,
		 * [$scope, $resolve] in the second */
		zv::Arr captured = zv::Arr::create(2);
		captured.push(zv::Ref(scope.raw()));
		captured.push(zv::Ref(resolver));
		zv::Val callback = pt_type_native_callback(resolveUnconstrainedCallback, site, captured.raw());
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		return pt_type_traverser_map_of(bound.raw(), callback.raw());
	}

	/* Mirrors resolve(): the resolution of the key on this frame, then on the
	 * parents; PHP null for none. UNDEF = pending exception */
	zv::Val resolve(zval *site, zend_string *templateName) const
	{
		smart_str key = {};
		smart_str_append_long(&key, (zend_long) Z_OBJ_HANDLE_P(site));
		smart_str_appendc(&key, '#');
		smart_str_append(&key, templateName);
		smart_str_0(&key);
		zv::Str keyStr = zv::Str::adopt(key.s);

		zend_object *frame = self;
		for (;;) {
			zval *resolutions = OBJ_PROP_NUM(frame, slots::resolutions);
			if (Z_TYPE_P(resolutions) == IS_ARRAY) {
				zval *found = zend_symtable_find(Z_ARRVAL_P(resolutions), keyStr.get());
				if (found != NULL && Z_TYPE_P(found) != IS_NULL) return zv::Val::copyOf(zv::Ref(found));
			}
			zval *parent = pt_typed_slot(frame, slots::parent, frame->ce, "parent");
			if (UNEXPECTED(parent == NULL)) return zv::Val();
			if (Z_TYPE_P(parent) == IS_NULL) return zv::Val::null();
			if (UNEXPECTED(Z_OBJCE_P(parent) != frame->ce)) {
				zval siteZv, nameZv;
				ZVAL_COPY_VALUE(&siteZv, site);
				ZVAL_STR(&nameZv, templateName);
				zv::Args argv{&siteZv, &nameZv};
				return pt_type_call(Z_OBJ_P(parent), PT_LC("resolve"), 2, argv);
			}
			frame = Z_OBJ_P(parent);
		}
	}

	/* Mirrors getResolutionCacheKeySuffix(). */
	zv::Val getResolutionCacheKeySuffix() const
	{
		zend_object *frame = self;
		for (;;) {
			zval *resolutions = pt_typed_slot(frame, slots::resolutions, frame->ce, "resolutions");
			if (UNEXPECTED(resolutions == NULL)) return zv::Val();
			if (Z_TYPE_P(resolutions) != IS_NULL) {
				return zv::Val::adoptString(zend_strpprintf(0, "|templateArguments:%u", frame->handle));
			}
			zval *parent = pt_typed_slot(frame, slots::parent, frame->ce, "parent");
			if (UNEXPECTED(parent == NULL)) return zv::Val();
			if (Z_TYPE_P(parent) == IS_NULL) return zv::Val::adoptString(ZSTR_EMPTY_ALLOC());
			frame = Z_OBJ_P(parent);
		}
	}

private:
	zend_object *self;

	/* an array entry's key as a zval (array_keys()) */
	static void keyOf(const zv::ArrayEntry &entry, zval &out)
	{
		zend_string *key = entry.stringKeyOrNull();
		if (key != NULL) {
			ZVAL_STR(&out, key);
		} else {
			ZVAL_LONG(&out, (zend_long) entry.indexKey());
		}
	}
};

} // namespace phpstanturbo

using phpstanturbo::TemplateArgumentFrame;

namespace {

/* fn (Expr $site, string $templateName): ?Type => $this->resolve($site, $templateName) */
void resolveCallback(zval *frame, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state1;
	if (UNEXPECTED(argc < 2 || Z_TYPE(argv[0]) != IS_OBJECT || Z_TYPE(argv[1]) != IS_STRING)) {
		zend_type_error("TemplateArgumentFrame resolver: expected (Expr $site, string $templateName)");
		return;
	}
	zv::Val resolved = TemplateArgumentFrame(Z_OBJ_P(frame)).resolve(&argv[0], Z_STR(argv[1]));
	if (UNEXPECTED(resolved.isUndef())) return;
	resolved.intoReturnValue(return_value);
}

/* static function (Type $type, callable $traverse) use ($site, $scope, $resolve): Type
 * — $site in the first state slot, [$scope, $resolve] in the second */
void resolveUnconstrainedCallback(zval *site, zval *captured, uint32_t argc, zval *argv, zval *return_value)
{
	if (UNEXPECTED(argc < 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
		zend_type_error("TemplateArgumentFrame traversal: expected (Type $type, callable $traverse)");
		return;
	}
	zval *type = &argv[0];
	zval *traverse = &argv[1];
	zval *scope = zend_hash_index_find(Z_ARRVAL_P(captured), 0);
	zval *resolver = zend_hash_index_find(Z_ARRVAL_P(captured), 1);
	zend_class_entry *templateTypeCe = pt_class_loaded(PT_CLASS_TEMPLATE_TYPE);
	if (UNEXPECTED(EG(exception))) return;
	if (templateTypeCe != NULL && instanceof_function(Z_OBJCE_P(type), templateTypeCe)) {
		zv::Val typeScope = pt_type_call(Z_OBJ_P(type), PT_LC("getscope"), 0, NULL);
		if (UNEXPECTED(typeScope.isUndef())) return;
		if (UNEXPECTED(Z_TYPE_P(typeScope.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function equals() on %s", zend_zval_value_name(typeScope.raw()));
			return;
		}
		zv::Val sameScope = pt_type_call(Z_OBJ_P(typeScope.raw()), PT_LC("equals"), 1, scope);
		if (UNEXPECTED(sameScope.isUndef())) return;
		if (zend_is_true(sameScope.raw())) {
			/* $resolve($site, $type->getName()) ?? $type->getDefault() ?? $traverse($type->getBound()) */
			zv::Val name = pt_type_call(Z_OBJ_P(type), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(name.isUndef())) return;
			zv::Args resolveArgv{site, name.raw()};
			zv::Val resolved = pt_type_call_callable(resolver, 2, resolveArgv);
			if (UNEXPECTED(resolved.isUndef())) return;
			if (Z_TYPE_P(resolved.raw()) != IS_NULL) {
				resolved.intoReturnValue(return_value);
				return;
			}
			zv::Val defaultType = pt_type_call(Z_OBJ_P(type), PT_LC("getdefault"), 0, NULL);
			if (UNEXPECTED(defaultType.isUndef())) return;
			if (Z_TYPE_P(defaultType.raw()) != IS_NULL) {
				defaultType.intoReturnValue(return_value);
				return;
			}
			zv::Val bound = pt_type_call(Z_OBJ_P(type), PT_LC("getbound"), 0, NULL);
			if (UNEXPECTED(bound.isUndef())) return;
			zv::Val traversed = pt_type_call_callable(traverse, 1, bound.raw());
			if (UNEXPECTED(traversed.isUndef())) return;
			traversed.intoReturnValue(return_value);
			return;
		}
	}
	zv::Val traversed = pt_type_call_callable(traverse, 1, type);
	if (UNEXPECTED(traversed.isUndef())) return;
	traversed.intoReturnValue(return_value);
}

} // namespace

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_template_argument_frame_return_type_of_call(zval *acceptor, zval *scope, zval *site, int allowUnresolved)
{
	return TemplateArgumentFrame::returnTypeOfCall(acceptor, scope, site, allowUnresolved);
}

zv::Val pt_template_argument_frame_new(zval *parent, zval *resolutions, zval *siteStatementIndexes)
{
	return TemplateArgumentFrame::create(parent != NULL && Z_TYPE_P(parent) == IS_NULL ? NULL : parent, resolutions != NULL && Z_TYPE_P(resolutions) == IS_NULL ? NULL : resolutions, siteStatementIndexes);
}

/* the twin is final: the native class entry answers natively, anything
 * else (the PHP twin declared next to the native class in the differential
 * tests) through the method */
zv::Val pt_template_argument_frame_resolve(zval *frame, zval *site, zend_string *templateName)
{
	if (EXPECTED(Z_OBJCE_P(frame) == pt_ce_template_argument_frame)) return TemplateArgumentFrame(Z_OBJ_P(frame)).resolve(site, templateName);
	zval nameZv;
	ZVAL_STR(&nameZv, templateName);
	zv::Args argv{site, &nameZv};
	return pt_type_call(Z_OBJ_P(frame), PT_LC("resolve"), 2, argv);
}

zv::Val pt_template_argument_frame_resolve_or_unconstrained(zval *frame, zval *site, zval *templateType)
{
	if (EXPECTED(Z_OBJCE_P(frame) == pt_ce_template_argument_frame)) return TemplateArgumentFrame(Z_OBJ_P(frame)).resolveOrUnconstrained(site, templateType);
	zv::Args argv{site, templateType};
	return pt_type_call(Z_OBJ_P(frame), PT_LC("resolveorunconstrained"), 2, argv);
}

zv::Val pt_template_argument_frame_resolution_cache_key_suffix(zval *frame)
{
	if (EXPECTED(Z_OBJCE_P(frame) == pt_ce_template_argument_frame)) return TemplateArgumentFrame(Z_OBJ_P(frame)).getResolutionCacheKeySuffix();
	return pt_type_call(Z_OBJ_P(frame), PT_LC("getresolutioncachekeysuffix"), 0, NULL);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_template_argument_frame()
{
	pt_taf_original_site_str = zend_string_init_interned(pt_taf_original_site, strlen(pt_taf_original_site), 1);

	reg::Class cls("PHPStan\\Analyser\\Generics\\TemplateArgumentFrame");
	ptdecl::TemplateArgumentFrame::declareClass(cls);
	cls.publicClassConstantString("SYNTHETIC_SITE_ATTRIBUTE", pt_taf_synthetic_site);
	cls.publicClassConstantString("ORIGINAL_SITE_ATTRIBUTE", pt_taf_original_site);
	ptdecl::TemplateArgumentFrame::declareProperties(cls);

	cls.method(sigs::returnTypeOfCall, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptor, *scope, *site;
		bool allowUnresolved = false, allowUnresolvedIsNull = true;
		ZEND_PARSE_PARAMETERS_START(3, 4)
			Z_PARAM_OBJECT(acceptor)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(site)
			Z_PARAM_OPTIONAL
			Z_PARAM_BOOL_OR_NULL(allowUnresolved, allowUnresolvedIsNull)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TemplateArgumentFrame::returnTypeOfCall(acceptor, scope, site, allowUnresolvedIsNull ? -1 : (allowUnresolved ? 1 : 0)));
	});

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *parent, *resolutions = NULL, *siteStatementIndexes = NULL;
		ZEND_PARSE_PARAMETERS_START(1, 3)
			Z_PARAM_OBJECT_OR_NULL(parent)
			Z_PARAM_OPTIONAL
			Z_PARAM_ARRAY_OR_NULL(resolutions)
			Z_PARAM_ARRAY(siteStatementIndexes)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!TemplateArgumentFrame(Z_OBJ_P(ZEND_THIS)).construct(parent, resolutions, siteStatementIndexes))) RETURN_THROWS();
	});

	cls.method<&TemplateArgumentFrame::isObserving>(sigs::isObserving);

	cls.method<&TemplateArgumentFrame::firstSiteStatementIndex>(sigs::firstSiteStatementIndex);

	cls.method<&TemplateArgumentFrame::ownsSiteInStatement, zp::Long>(sigs::ownsSiteInStatement);

	cls.method<&TemplateArgumentFrame::hasSiteAtOrAfter, zp::Long>(sigs::hasSiteAtOrAfter);

	cls.method<&TemplateArgumentFrame::resolveOrUnconstrained, zp::Obj, zp::Obj>(sigs::resolveOrUnconstrained);

	cls.method(sigs::resolveUnconstrained, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *site, *templateType, *resolver;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Zval>(execute_data, site, templateType, resolver)) RETURN_THROWS();
		if (UNEXPECTED(!zend_is_callable(resolver, 0, NULL))) {
			zend_argument_type_error(3, "must be of type callable, %s given", zend_zval_value_name(resolver));
			RETURN_THROWS();
		}
		PT_RETURN_VAL(TemplateArgumentFrame::resolveUnconstrained(site, templateType, resolver));
	});

	cls.method(sigs::resolve, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *site;
		zend_string *templateName;
		if (!zp::parse<zp::Obj, zp::Str>(execute_data, site, templateName)) RETURN_THROWS();
		PT_RETURN_VAL(TemplateArgumentFrame(Z_OBJ_P(ZEND_THIS)).resolve(site, templateName));
	});

	cls.method<&TemplateArgumentFrame::getResolutionCacheKeySuffix>(sigs::getResolutionCacheKeySuffix);

	cls.shadow(&pt_ce_template_argument_frame);
}

/* }}} */
