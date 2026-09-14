/*
 * PHPStanTurbo\UnresolvedTemplateArgumentType — native implementation of
 * PHPStan\Type\Generic\UnresolvedTemplateArgumentType.
 *
 * State is the twin's three promoted constructor properties — `private Expr
 * $site`, `private TemplateType $templateType`, `private ?Type
 * $initialType` — in slots 0-2, declared typed property slots in the twin's
 * declaration order.
 *
 * The class is final, so getDelegate() and withInitialType() are direct C++
 * calls; every `$this->getDelegate()->x(...)` body is one forwarding
 * handler taking the method from its own frame. The static unwrapBare()
 * closure is a native callback. Another instance's private slots are read
 * directly, as the twin does from inside the class.
 */

#include "TypeTraits.h"
#include "generated/UnresolvedTemplateArgumentType.h"

namespace slots = ptdecl::UnresolvedTemplateArgumentType::slot;
namespace sigs = ptdecl::UnresolvedTemplateArgumentType::sig;

zend_class_entry *pt_ce_unresolved_template_argument_type = nullptr;

namespace ptcls {
inline constexpr const char *templateType = "PHPStan\\Type\\Generic\\TemplateType";
} // namespace ptcls

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\UnresolvedTemplateArgumentType. State lives
 * in the PHP object's slots. */
class UnresolvedTemplateArgumentType
{
public:
	explicit UnresolvedTemplateArgumentType(zend_object *self) : self(self) {}

	/* __construct(private Expr $site, private TemplateType $templateType,
	 * private ?Type $initialType): the promoted properties first (as the
	 * engine assigns them), then the body's guard against a marker as the
	 * initial type; every argument borrowed, $initialType NULL for null;
	 * false = pending exception */
	[[nodiscard]] bool construct(zval *site, zval *templateType, zval *initialType)
	{
		writeSlot(slots::site, site);
		writeSlot(slots::templateType, templateType);
		zval null = {};
		ZVAL_NULL(&null);
		writeSlot(slots::initialType, initialType != NULL ? initialType : &null);
		if (initialType != NULL && instanceof_function(Z_OBJCE_P(initialType), pt_ce_unresolved_template_argument_type)) {
			zval message;
			ZVAL_STRINGL(&message, "The initial type of an unresolved template argument is never itself unresolved.", sizeof("The initial type of an unresolved template argument is never itself unresolved.") - 1);
			zv::Val exception = pt_type_new(PT_CLASS_SHOULD_NOT_HAPPEN, 1, &message);
			zval_ptr_dtor(&message);
			if (UNEXPECTED(exception.isUndef())) return false;
			zval raw = exception.take();
			zend_throw_exception_object(&raw);
			return false;
		}
		return true;
	}

	/* new self($site, $templateType, $initialType); UNDEF = pending exception */
	static zv::Val create(zval *site, zval *templateType, zval *initialType)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_unresolved_template_argument_type) != SUCCESS)) return zv::Val();
		if (UNEXPECTED(!UnresolvedTemplateArgumentType(Z_OBJ(object)).construct(site, templateType, initialType))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

	/* the slots (borrowed; $initialType IS_NULL or an object); NULL with an
	 * Error pending when the constructor never ran */
	zval *site() const { return slot(self, slots::site, "site"); }
	zval *templateType() const { return slot(self, slots::templateType, "templateType"); }
	zval *initialType() const { return slot(self, slots::initialType, "initialType"); }

	static zval *slot(zend_object *object, uint32_t index, const char *name) { return pt_typed_slot(object, index, pt_ce_unresolved_template_argument_type, name); }

	zv::Val getSite() const { return copyOfSlot(site()); }
	zv::Val getTemplate() const { return copyOfSlot(templateType()); }
	zv::Val getInitialType() const { return copyOfSlot(initialType()); }

	/* $this->templateType->getName() */
	zv::Val getTemplateName() const
	{
		zval *t = templateType();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return pt_type_call(Z_OBJ_P(t), PT_LC("getname"), 0, NULL);
	}

	/* $this->initialType ?? $this->templateType->getDefault() ??
	 * $this->templateType->getBound(); UNDEF = pending exception. The `??`
	 * reads $initialType with isset() semantics — an uninitialized slot
	 * counts as null, and the Error the twin raises then is
	 * $templateType's */
	zv::Val getDelegate() const
	{
		zval *initial = OBJ_PROP_NUM(self, slots::initialType);
		if (Z_TYPE_P(initial) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(initial));
		zval *t = templateType();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zv::Val defaultType = pt_type_call(Z_OBJ_P(t), PT_LC("getdefault"), 0, NULL);
		if (UNEXPECTED(defaultType.isUndef())) return zv::Val();
		if (zv::Ref(defaultType.raw()).isObject()) return defaultType;
		zv::Val bound = pt_type_call(Z_OBJ_P(t), PT_LC("getbound"), 0, NULL);
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(bound.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getBound() must return %s", ptcls::type);
			return zv::Val();
		}
		return bound;
	}

	/* new self($this->site, $this->templateType, $initialType) ($initialType
	 * NULL for null) */
	zv::Val withInitialType(zval *newInitialType) const
	{
		zval *s = site();
		zval *t = s != NULL ? templateType() : NULL;
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return create(s, t, newInitialType);
	}

	/* new self($site, $templateType, $this->initialType) */
	zv::Val withSite(zval *newSite, zval *newTemplateType) const
	{
		zval *initial = initialType();
		if (UNEXPECTED(initial == NULL)) return zv::Val();
		return create(newSite, newTemplateType, Z_TYPE_P(initial) == IS_OBJECT ? initial : NULL);
	}

	/* unwrapBare(): a marker's delegate unwrapped again; an object type, or
	 * one without template or late-resolvable types and no bare marker
	 * in it, as is; else TypeTraverser::map() replacing the bare markers
	 * by their unwrapped delegates and keeping objects; UNDEF = pending
	 * exception */
	static zv::Val unwrapBare(zval *type)
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_unresolved_template_argument_type)) {
			zv::Val delegate = UnresolvedTemplateArgumentType(Z_OBJ_P(type)).getDelegate();
			if (UNEXPECTED(delegate.isUndef())) return zv::Val();
			return unwrapBare(delegate.raw());
		}
		zend_long isObject = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isobject"), 0, NULL);
		if (UNEXPECTED(isObject < 0)) return zv::Val();
		if (isObject == PT_TRI_YES) return zv::Val::copyOf(zv::Ref(type));
		zv::Val has = pt_type_call(Z_OBJ_P(type), PT_LC("hastemplateorlateresolvabletype"), 0, NULL);
		if (UNEXPECTED(has.isUndef())) return zv::Val();
		if (!zend_is_true(has.raw())) {
			bool containsBare;
			if (UNEXPECTED(!containsBareMarkerShallow(type, containsBare))) return zv::Val();
			if (!containsBare) return zv::Val::copyOf(zv::Ref(type));
		}
		zv::Val callback = pt_type_native_callback(unwrapBareCallback, NULL, NULL);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		return pt_type_traverser_map_of(type, callback.raw());
	}

	/* $type instanceof self && $type->site === $this->site &&
	 * $type->templateType->getName() === $this->templateType->getName();
	 * false with an exception pending */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_unresolved_template_argument_type)) {
			out = false;
			return true;
		}
		zval *theirSite = slot(Z_OBJ_P(type), slots::site, "site");
		if (UNEXPECTED(theirSite == NULL)) return false;
		zval *s = site();
		if (UNEXPECTED(s == NULL)) return false;
		if (!pt_type_same_object(theirSite, s)) {
			out = false;
			return true;
		}
		zv::Val theirName = UnresolvedTemplateArgumentType(Z_OBJ_P(type)).getTemplateName();
		if (UNEXPECTED(theirName.isUndef())) return false;
		zv::Val name = getTemplateName();
		if (UNEXPECTED(name.isUndef())) return false;
		out = zend_is_identical(theirName.raw(), name.raw());
		return true;
	}

	/* 'unresolved#<site id>(<delegate>)' at the cache level,
	 * 'unresolved(<delegate>)' otherwise; UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		zend_long levelValue;
		if (UNEXPECTED(!pt_verbosity_level_value_of(level, levelValue))) return zv::Val();
		zval *s = NULL;
		if (levelValue == PT_VERBOSITY_LEVEL_CACHE) {
			s = site();
			if (UNEXPECTED(s == NULL)) return zv::Val();
		}
		zv::Val delegate = getDelegate();
		if (UNEXPECTED(delegate.isUndef())) return zv::Val();
		zv::Val description = pt_type_call(Z_OBJ_P(delegate.raw()), PT_LC("describe"), 1, level);
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(description.raw()).isString())) {
			zend_type_error("phpstan_turbo: describe() must return a string");
			return zv::Val();
		}
		if (s != NULL) {
			/* spl_object_id($this->site) */
			return zv::Val::adoptString(zend_strpprintf(0, "unresolved#" ZEND_LONG_FMT "(%s)", (zend_long) Z_OBJ_HANDLE_P(s), ZSTR_VAL(Z_STR_P(description.raw()))));
		}
		return zv::Val::adoptString(zend_strpprintf(0, "unresolved(%s)", ZSTR_VAL(Z_STR_P(description.raw()))));
	}

	/* $this->getDelegate()->method(...$args); UNDEF = pending exception */
	zv::Val delegate(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zv::Val target = getDelegate();
		if (UNEXPECTED(target.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(target.raw()), lcname, len, argc, argv);
	}

	/* the same with the method named by the forwarding method's own frame */
	zv::Val delegateNamed(zend_string *name, uint32_t argc, zval *argv) const
	{
		zv::Val target = getDelegate();
		if (UNEXPECTED(target.isUndef())) return zv::Val();
		zend_object *object = Z_OBJ_P(target.raw());
		zend_function *fn = (zend_function *) zend_hash_find_ptr_lc(&object->ce->function_table, name);
		if (UNEXPECTED(fn == NULL)) {
			zend_throw_error(NULL, "Call to undefined method %s::%s()", ZSTR_VAL(object->ce->name), ZSTR_VAL(name));
			return zv::Val();
		}
		zval ret;
		zend_call_known_function(fn, object, object->ce, &ret, argc, argv, NULL);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&ret);
			return zv::Val();
		}
		return zv::Val::adopt(ret);
	}

	/* $acceptingType->accepts($this->getDelegate(), $strictTypes) */
	zv::Val isAcceptedBy(zval *acceptingType, bool strictTypes) const
	{
		zv::Val target = getDelegate();
		if (UNEXPECTED(target.isUndef())) return zv::Val();
		zv::Args args{target.raw(), strictTypes};
		return pt_type_call(Z_OBJ_P(acceptingType), PT_LC("accepts"), 2, args);
	}

	/* $otherType->isSuperTypeOf($this->getDelegate()) */
	zv::Val isSubTypeOf(zval *otherType) const { return reversed(otherType, PT_LC("issupertypeof"), NULL); }

	/* $otherType->isSmallerThan($this->getDelegate(), $phpVersion) */
	zv::Val isGreaterThan(zval *otherType, zval *phpVersion) const { return reversed(otherType, PT_LC("issmallerthan"), phpVersion); }

	/* $otherType->isSmallerThanOrEqual($this->getDelegate(), $phpVersion) */
	zv::Val isGreaterThanOrEqual(zval *otherType, zval *phpVersion) const { return reversed(otherType, PT_LC("issmallerthanorequal"), phpVersion); }

	/* $this without an initial type or when the callback kept it, else the
	 * marker over the callback's result unwrapped; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const { return traverseWith(NULL, fci, fcc); }

	/* the same with $right as the callback's second argument */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const { return traverseWith(right, fci, fcc); }

	/* $this without an initial type, else the marker over the initial type
	 * generalized */
	zv::Val generalize(zval *precision) const
	{
		zval *initial = initialType();
		if (UNEXPECTED(initial == NULL)) return zv::Val();
		if (Z_TYPE_P(initial) != IS_OBJECT) return thisValue();
		zv::Val generalized = pt_type_call(Z_OBJ_P(initial), PT_LC("generalize"), 1, precision);
		if (UNEXPECTED(generalized.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(generalized.raw()).isObject())) {
			zend_type_error("phpstan_turbo: generalize() must return %s", ptcls::type);
			return zv::Val();
		}
		return withInitialType(generalized.raw());
	}

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }

	static zv::Val copyOfSlot(zval *p)
	{
		if (UNEXPECTED(p == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(p));
	}

	void writeSlot(uint32_t index, zval *value) { pt_write_slot(self, index, value); }

	/* $otherType->method($this->getDelegate()[, $phpVersion]) */
	zv::Val reversed(zval *otherType, const char *lcname, size_t len, zval *phpVersion) const
	{
		zv::Val target = getDelegate();
		if (UNEXPECTED(target.isUndef())) return zv::Val();
		zval args[2];
		ZVAL_COPY_VALUE(&args[0], target.raw());
		if (phpVersion != NULL) {
			ZVAL_COPY_VALUE(&args[1], phpVersion);
		}
		return pt_type_call(Z_OBJ_P(otherType), lcname, len, phpVersion != NULL ? 2 : 1, args);
	}

	/* a bare marker among a union's members, or as the value or key type
	 * of a non-object iterable; false = pending exception */
	[[nodiscard]] static bool containsBareMarkerShallow(zval *type, bool &out)
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_union_type)) {
			zv::Val members = pt_union_type_get_types(Z_OBJ_P(type));
			if (UNEXPECTED(members.isUndef())) return false;
			if (UNEXPECTED(!zv::Ref(members.raw()).isArray())) {
				zend_type_error("phpstan_turbo: getTypes() must return an array");
				return false;
			}
			for (zv::ArrayEntry entry : zv::ArrRef(members.raw())) {
				if (entry.value().deref().instanceOf(pt_ce_unresolved_template_argument_type)) {
					out = true;
					return true;
				}
			}
		}
		zend_long isIterable = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isiterable"), 0, NULL);
		if (UNEXPECTED(isIterable < 0)) return false;
		if (isIterable != PT_TRI_YES) {
			out = false;
			return true;
		}
		zend_long isObject = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isobject"), 0, NULL);
		if (UNEXPECTED(isObject < 0)) return false;
		if (isObject == PT_TRI_YES) {
			out = false;
			return true;
		}
		zv::Val valueType = pt_type_call(Z_OBJ_P(type), PT_LC("getiterablevaluetype"), 0, NULL);
		if (UNEXPECTED(valueType.isUndef())) return false;
		if (zv::Ref(valueType.raw()).instanceOf(pt_ce_unresolved_template_argument_type)) {
			out = true;
			return true;
		}
		zv::Val keyType = pt_type_call(Z_OBJ_P(type), PT_LC("getiterablekeytype"), 0, NULL);
		if (UNEXPECTED(keyType.isUndef())) return false;
		out = zv::Ref(keyType.raw()).instanceOf(pt_ce_unresolved_template_argument_type);
		return true;
	}

	/* unwrapBare()'s `static function (Type $type, callable $traverse)`: a
	 * marker becomes its unwrapped delegate, an object type stays, anything
	 * else is traversed */
	static void unwrapBareCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state0;
		(void) state1;
		if (UNEXPECTED(argc != 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
			zend_throw_error(NULL, "phpstan_turbo: TypeTraverser::map() must call back with a Type and the traverse callable");
			return;
		}
		if (instanceof_function(Z_OBJCE(argv[0]), pt_ce_unresolved_template_argument_type)) {
			zv::Val delegate = UnresolvedTemplateArgumentType(Z_OBJ(argv[0])).getDelegate();
			if (UNEXPECTED(delegate.isUndef())) return;
			zv::Val unwrapped = unwrapBare(delegate.raw());
			if (UNEXPECTED(unwrapped.isUndef())) return;
			unwrapped.intoReturnValue(return_value);
			return;
		}
		zend_long isObject = pt_type_call_trinary(Z_OBJ(argv[0]), PT_LC("isobject"), 0, NULL);
		if (UNEXPECTED(isObject < 0)) return;
		if (isObject == PT_TRI_YES) {
			ZVAL_COPY(return_value, &argv[0]);
			return;
		}
		zval traversed;
		if (UNEXPECTED(!pt_type_traverser_traverse(&traversed, &argv[1], &argv[0]))) return;
		ZVAL_COPY_VALUE(return_value, &traversed);
	}

	/* traverse() / traverseSimultaneously() ($right NULL for the former) */
	zv::Val traverseWith(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *initial = initialType();
		if (UNEXPECTED(initial == NULL)) return zv::Val();
		if (Z_TYPE_P(initial) != IS_OBJECT) return thisValue();
		zv::Val newInitialType = pt_type_traverse_call(fci, fcc, initial, right);
		if (UNEXPECTED(newInitialType.isUndef())) return zv::Val();
		if (pt_type_same_object(newInitialType.raw(), initial)) return thisValue();
		zv::Val unwrapped = unwrapBare(newInitialType.raw());
		if (UNEXPECTED(unwrapped.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(unwrapped.raw()).isObject())) {
			zend_type_error("phpstan_turbo: unwrapBare() must return %s", ptcls::type);
			return zv::Val();
		}
		return withInitialType(unwrapped.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::UnresolvedTemplateArgumentType;

bool pt_unresolved_template_argument_type_new(zval *out, zval *site, zval *templateType, zval *initialType)
{
	return pt_val_into(UnresolvedTemplateArgumentType::create(site, templateType, initialType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS UnresolvedTemplateArgumentType(Z_OBJ_P(ZEND_THIS))

/* `return $this->getDelegate()->x(...$args)` — the body of every forwarding
 * method, for every arity: the method is the frame's own, the arguments
 * the frame's (counted as the twin counts them, their types checked by the
 * delegate's method as the twin's typed parameters would) */
static void ZEND_FASTCALL utaDelegate(INTERNAL_FUNCTION_PARAMETERS)
{
	const zend_function *fn = EX(func);
	uint32_t argc = ZEND_NUM_ARGS();
	if (UNEXPECTED(argc < fn->common.required_num_args || argc > fn->common.num_args)) {
		zend_wrong_parameters_count_error(fn->common.required_num_args, fn->common.num_args);
		RETURN_THROWS();
	}
	PT_RETURN_VAL(PT_THIS.delegateNamed(fn->common.function_name, argc, argc > 0 ? ZEND_CALL_ARG(execute_data, 1) : NULL));
}

void pt_register_unresolved_template_argument_type()
{

	reg::Class cls("PHPStan\\Type\\Generic\\UnresolvedTemplateArgumentType");
	ptdecl::UnresolvedTemplateArgumentType::declareClass(cls);
	/* the slots must stay in this order (PT_UTA_PROP_*) */
	ptdecl::UnresolvedTemplateArgumentType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *site, *templateType, *initialType;
		if (!zp::parse<zp::Obj, zp::Obj, zp::ObjOrNull>(execute_data, site, templateType, initialType)) RETURN_THROWS();
		if (UNEXPECTED(!PT_THIS.construct(site, templateType, initialType))) RETURN_THROWS();
	});

	cls.method<&UnresolvedTemplateArgumentType::getSite>(sigs::getSite);
	cls.method<&UnresolvedTemplateArgumentType::getTemplateName>(sigs::getTemplateName);
	cls.method<&UnresolvedTemplateArgumentType::getTemplate>(sigs::getTemplate);
	cls.method<&UnresolvedTemplateArgumentType::getInitialType>(sigs::getInitialType);
	cls.method<&UnresolvedTemplateArgumentType::getDelegate>(sigs::getDelegate);

	cls.method<&UnresolvedTemplateArgumentType::withInitialType, zp::ObjOrNull>(sigs::withInitialType);

	cls.method<&UnresolvedTemplateArgumentType::withSite, zp::Obj, zp::Obj>(sigs::withSite);

	cls.method<&UnresolvedTemplateArgumentType::unwrapBare, zp::Obj>(sigs::unwrapBare);

	cls.method<&UnresolvedTemplateArgumentType::equals, zp::Obj>(sigs::equals);

	cls.method<&UnresolvedTemplateArgumentType::describe, zp::Obj>(sigs::describe);

	cls.method(sigs::accepts, utaDelegate);
	cls.method(sigs::isSuperTypeOf, utaDelegate);

	cls.method<&UnresolvedTemplateArgumentType::isAcceptedBy, zp::Obj, zp::Bool>(sigs::isAcceptedBy);

	cls.method<&UnresolvedTemplateArgumentType::isSubTypeOf, zp::Obj>(sigs::isSubTypeOf);

	cls.method<&UnresolvedTemplateArgumentType::isGreaterThan, zp::Obj, zp::Obj>(sigs::isGreaterThan);

	cls.method<&UnresolvedTemplateArgumentType::isGreaterThanOrEqual, zp::Obj, zp::Obj>(sigs::isGreaterThanOrEqual);

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});

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

	cls.method<&UnresolvedTemplateArgumentType::generalize, zp::Obj>(sigs::generalize);

	cls.method(sigs::tryRemove, utaDelegate);
	cls.method(sigs::toCoercedArgumentType, utaDelegate);
	cls.method(sigs::hasTemplateOrLateResolvableType, utaDelegate);
	cls.method(sigs::toPhpDocNode, utaDelegate);
	cls.method(sigs::getReferencedClasses, utaDelegate);
	cls.method(sigs::getObjectClassNames, utaDelegate);
	cls.method(sigs::getObjectClassReflections, utaDelegate);
	cls.method(sigs::getClassStringType, utaDelegate);
	cls.method(sigs::getClassStringObjectType, utaDelegate);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, utaDelegate);
	cls.method(sigs::isObject, utaDelegate);
	cls.method(sigs::isEnum, utaDelegate);
	cls.method(sigs::getArrays, utaDelegate);
	cls.method(sigs::getConstantArrays, utaDelegate);
	cls.method(sigs::getConstantStrings, utaDelegate);
	cls.method(sigs::canAccessProperties, utaDelegate);
	cls.method(sigs::hasProperty, utaDelegate);
	cls.method(sigs::getProperty, utaDelegate);
	cls.method(sigs::getUnresolvedPropertyPrototype, utaDelegate);
	cls.method(sigs::hasInstanceProperty, utaDelegate);
	cls.method(sigs::getInstanceProperty, utaDelegate);
	cls.method(sigs::getUnresolvedInstancePropertyPrototype, utaDelegate);
	cls.method(sigs::hasStaticProperty, utaDelegate);
	cls.method(sigs::getStaticProperty, utaDelegate);
	cls.method(sigs::getUnresolvedStaticPropertyPrototype, utaDelegate);
	cls.method(sigs::canCallMethods, utaDelegate);
	cls.method(sigs::hasMethod, utaDelegate);
	cls.method(sigs::getMethod, utaDelegate);
	cls.method(sigs::getUnresolvedMethodPrototype, utaDelegate);
	cls.method(sigs::canAccessConstants, utaDelegate);
	cls.method(sigs::hasConstant, utaDelegate);
	cls.method(sigs::getConstant, utaDelegate);
	cls.method(sigs::isIterable, utaDelegate);
	cls.method(sigs::isIterableAtLeastOnce, utaDelegate);
	cls.method(sigs::getArraySize, utaDelegate);
	cls.method(sigs::getIterableKeyType, utaDelegate);
	cls.method(sigs::getFirstIterableKeyType, utaDelegate);
	cls.method(sigs::getLastIterableKeyType, utaDelegate);
	cls.method(sigs::getIterableValueType, utaDelegate);
	cls.method(sigs::getFirstIterableValueType, utaDelegate);
	cls.method(sigs::getLastIterableValueType, utaDelegate);
	cls.method(sigs::isArray, utaDelegate);
	cls.method(sigs::isConstantArray, utaDelegate);
	cls.method(sigs::isOversizedArray, utaDelegate);
	cls.method(sigs::isList, utaDelegate);
	cls.method(sigs::isOffsetAccessible, utaDelegate);
	cls.method(sigs::isOffsetAccessLegal, utaDelegate);
	cls.method(sigs::hasOffsetValueType, utaDelegate);
	cls.method(sigs::getOffsetValueType, utaDelegate);
	cls.method(sigs::setOffsetValueType, utaDelegate);
	cls.method(sigs::setExistingOffsetValueType, utaDelegate);
	cls.method(sigs::unsetOffset, utaDelegate);
	cls.method(sigs::getKeysArrayFiltered, utaDelegate);
	cls.method(sigs::getKeysArray, utaDelegate);
	cls.method(sigs::getValuesArray, utaDelegate);
	cls.method(sigs::chunkArray, utaDelegate);
	cls.method(sigs::fillKeysArray, utaDelegate);
	cls.method(sigs::flipArray, utaDelegate);
	cls.method(sigs::intersectKeyArray, utaDelegate);
	cls.method(sigs::popArray, utaDelegate);
	cls.method(sigs::reverseArray, utaDelegate);
	cls.method(sigs::searchArray, utaDelegate);
	cls.method(sigs::shiftArray, utaDelegate);
	cls.method(sigs::shuffleArray, utaDelegate);
	cls.method(sigs::sliceArray, utaDelegate);
	cls.method(sigs::spliceArray, utaDelegate);
	cls.method(sigs::truncateListToSize, utaDelegate);
	cls.method(sigs::makeListMaybe, utaDelegate);
	cls.method(sigs::mapValueType, utaDelegate);
	cls.method(sigs::mapKeyType, utaDelegate);
	cls.method(sigs::makeAllArrayKeysOptional, utaDelegate);
	cls.method(sigs::changeKeyCaseArray, utaDelegate);
	cls.method(sigs::filterArrayRemovingFalsey, utaDelegate);
	cls.method(sigs::getEnumCases, utaDelegate);
	cls.method(sigs::getEnumCaseObject, utaDelegate);
	cls.method(sigs::getFiniteTypes, utaDelegate);
	cls.method(sigs::exponentiate, utaDelegate);
	cls.method(sigs::isCallable, utaDelegate);
	cls.method(sigs::getCallableParametersAcceptors, utaDelegate);
	cls.method(sigs::isCloneable, utaDelegate);
	cls.method(sigs::toBoolean, utaDelegate);
	cls.method(sigs::toNumber, utaDelegate);
	cls.method(sigs::toBitwiseNotType, utaDelegate);
	cls.method(sigs::toGetClassResultType, utaDelegate);
	cls.method(sigs::toClassConstantType, utaDelegate);
	cls.method(sigs::toObjectTypeForInstanceofCheck, utaDelegate);
	cls.method(sigs::toObjectTypeForIsACheck, utaDelegate);
	cls.method(sigs::toInteger, utaDelegate);
	cls.method(sigs::toFloat, utaDelegate);
	cls.method(sigs::toString, utaDelegate);
	cls.method(sigs::toArray, utaDelegate);
	cls.method(sigs::toArrayKey, utaDelegate);
	cls.method(sigs::isSmallerThan, utaDelegate);
	cls.method(sigs::isSmallerThanOrEqual, utaDelegate);
	cls.method(sigs::isConstantValue, utaDelegate);
	cls.method(sigs::isConstantScalarValue, utaDelegate);
	cls.method(sigs::getConstantScalarTypes, utaDelegate);
	cls.method(sigs::getConstantScalarValues, utaDelegate);
	cls.method(sigs::isNull, utaDelegate);
	cls.method(sigs::isTrue, utaDelegate);
	cls.method(sigs::isFalse, utaDelegate);
	cls.method(sigs::isBoolean, utaDelegate);
	cls.method(sigs::isFloat, utaDelegate);
	cls.method(sigs::isInteger, utaDelegate);
	cls.method(sigs::isString, utaDelegate);
	cls.method(sigs::isNumericString, utaDelegate);
	cls.method(sigs::isDecimalIntegerString, utaDelegate);
	cls.method(sigs::isNonEmptyString, utaDelegate);
	cls.method(sigs::isNonFalsyString, utaDelegate);
	cls.method(sigs::isLiteralString, utaDelegate);
	cls.method(sigs::isLowercaseString, utaDelegate);
	cls.method(sigs::isUppercaseString, utaDelegate);
	cls.method(sigs::isClassString, utaDelegate);
	cls.method(sigs::isVoid, utaDelegate);
	cls.method(sigs::isScalar, utaDelegate);
	cls.method(sigs::looseCompare, utaDelegate);
	cls.method(sigs::getSmallerType, utaDelegate);
	cls.method(sigs::getSmallerOrEqualType, utaDelegate);
	cls.method(sigs::getGreaterType, utaDelegate);
	cls.method(sigs::getGreaterOrEqualType, utaDelegate);
	cls.method(sigs::getTemplateType, utaDelegate);
	cls.method(sigs::inferTemplateTypes, utaDelegate);
	cls.method(sigs::getReferencedTemplateTypes, utaDelegate);
	cls.method(sigs::toAbsoluteNumber, utaDelegate);

	cls.shadow(&pt_ce_unresolved_template_argument_type);
}

/* }}} */
