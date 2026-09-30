/*
 * PHPStanTurbo\ClassConstantAccessType — native implementation of
 * PHPStan\Type\ClassConstantAccessType.
 *
 * State is the twin's three promoted `private Type $type` / `private string
 * $constantName` / `private ?Type $nativeType` in slots 0 to 2;
 * LateResolvableTypeTrait's `private ?Type $result` follows them, declared by the shared registrar in TypeTraits.cpp
 * that also supplies the trait's forwards; NonGeneralizableTypeTrait's
 * generalize() comes from its registrar.
 *
 * The one $this-call the twin makes (describe() through $this->resolve())
 * is the trait's body, run directly — the class is final. Another
 * instance's private slots are read directly, as the twin does from inside
 * the class.
 */

#include "TypeTraits.h"
#include "generated/ClassConstantAccessType.h"

namespace slots = ptdecl::ClassConstantAccessType::slot;
namespace sigs = ptdecl::ClassConstantAccessType::sig;

zend_class_entry *pt_ce_class_constant_access_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\ClassConstantAccessType. State lives in the PHP
 * object's slots. */
class ClassConstantAccessType
{
public:
	explicit ClassConstantAccessType(zend_object *self) : self(self) {}

	/* __construct(private Type $type, private string $constantName, private
	 * ?Type $nativeType = null); all borrowed, $nativeType NULL for the
	 * default */
	void construct(zval *type, zend_string *constantName, zval *nativeType)
	{
		writeSlot(slots::type, type);
		zval name;
		ZVAL_STR(&name, constantName);
		writeSlot(slots::constantName, &name);
		zval null;
		ZVAL_NULL(&null);
		writeSlot(slots::nativeType, nativeType != NULL ? nativeType : &null);
	}

	/* new self($type, $constantName, $nativeType); UNDEF = pending exception */
	static zv::Val create(zval *type, zend_string *constantName, zval *nativeType)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_class_constant_access_type) != SUCCESS)) return zv::Val();
		ClassConstantAccessType(Z_OBJ(object)).construct(type, constantName, nativeType);
		return zv::Val::adopt(object);
	}

	/* new self($this->type, $this->constantName, $nativeType) */
	zv::Val withNativeType(zval *nativeType) const
	{
		zval *t = type();
		zval *name = t != NULL ? constantName() : NULL;
		if (UNEXPECTED(name == NULL)) return zv::Val();
		return create(t, Z_STR_P(name), nativeType);
	}

	/* the slots (borrowed); NULL with an Error pending when the constructor
	 * never ran */
	[[nodiscard]] zval *type() const { return slot(self, slots::type, "type"); }
	zval *constantName() const { return slot(self, slots::constantName, "constantName"); }
	zval *nativeType() const { return slot(self, slots::nativeType, "nativeType"); }

	static zval *slot(zend_object *object, uint32_t index, const char *name) { return pt_typed_slot(object, index, pt_ce_class_constant_access_type, name); }

	/* $this->type->getReferencedClasses() */
	zv::Val getReferencedClasses() const { return callType(PT_LC("getreferencedclasses"), 0, NULL); }

	/* $this->type->getReferencedTemplateTypes($positionVariance) */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const { return callType(PT_LC("getreferencedtemplatetypes"), 1, positionVariance); }

	/* $type instanceof self && $this->constantName === $type->constantName
	 * && $this->type->equals($type->type); false with an exception pending */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_class_constant_access_type)) {
			out = false;
			return true;
		}
		zval *name = constantName();
		if (UNEXPECTED(name == NULL)) return false;
		zval *theirName = slot(Z_OBJ_P(type), slots::constantName, "constantName");
		if (UNEXPECTED(theirName == NULL)) return false;
		if (!zend_string_equals(Z_STR_P(name), Z_STR_P(theirName))) {
			out = false;
			return true;
		}
		zval *theirType = slot(Z_OBJ_P(type), slots::type, "type");
		if (UNEXPECTED(theirType == NULL)) return false;
		zv::Val equal = callType(PT_LC("equals"), 1, theirType);
		if (UNEXPECTED(equal.isUndef())) return false;
		if (!zend_is_true(equal.raw())) {
			out = false;
			return true;
		}
		/* the native types: identical when either is null, equals() otherwise */
		zval *native = nativeType();
		if (UNEXPECTED(native == NULL)) return false;
		zval *theirNative = slot(Z_OBJ_P(type), slots::nativeType, "nativeType");
		if (UNEXPECTED(theirNative == NULL)) return false;
		if (Z_TYPE_P(native) == IS_NULL || Z_TYPE_P(theirNative) == IS_NULL) {
			out = Z_TYPE_P(native) == Z_TYPE_P(theirNative);
			return true;
		}
		zv::Val nativeEqual = pt_type_call(Z_OBJ_P(native), PT_LC("equals"), 1, theirNative);
		if (UNEXPECTED(nativeEqual.isUndef())) return false;
		out = zend_is_true(nativeEqual.raw());
		return true;
	}

	/* $this->resolve()->describe($level) */
	zv::Val describe(zval *level) const
	{
		zv::Val resolved = pt_type_late_resolvable_resolve(self, pt_ce_class_constant_access_type);
		if (UNEXPECTED(resolved.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(resolved.raw()).isObject())) {
			zend_type_error("phpstan_turbo: resolve() must return %s", ptcls::type);
			return zv::Val();
		}
		return pt_type_op(Z_OBJ_P(resolved.raw()), PT_OP_DESCRIBE, 1, level);
	}

	/* !TypeUtils::containsTemplateType($this->type) && !$this->type instanceof StaticType;
	 * false = pending exception */
	[[nodiscard]] bool isResolvable(bool &out) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return false;
		bool contains;
		if (UNEXPECTED(!pt_type_utils_contains_template_type(t, contains))) return false;
		out = !contains && !(pt_ce_static_type != NULL && instanceof_function(Z_OBJCE_P(t), pt_ce_static_type));
		return true;
	}

	/* ClassConstantPatternResolver::resolve($this->type, $this->constantName,
	 * $this->nativeType); UNDEF = pending exception */
	zv::Val getResult() const
	{
		zval *t = type();
		zval *name = t != NULL ? constantName() : NULL; /* one Error at a time, as the twin's first read raises */
		zval *native = name != NULL ? nativeType() : NULL;
		if (UNEXPECTED(native == NULL)) return zv::Val();
		zval args[3];
		ZVAL_COPY_VALUE(&args[0], t);
		ZVAL_COPY_VALUE(&args[1], name);
		ZVAL_COPY_VALUE(&args[2], native);
		return pt_type_call_static(PT_CLASS_CLASS_CONSTANT_PATTERN_RESOLVER, PT_LC("resolve"), 3, args);
	}

	/* new self($cb($this->type), $this->constantName, $this->nativeType) when
	 * the callback changed the type, $this otherwise; UNDEF = pending
	 * exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return traversed(pt_type_traverse_call(fci, fcc, t));
	}

	/* $this for a $right of another class, else traverse() with $right's
	 * type as the callback's second argument */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		if (!instanceof_function(Z_OBJCE_P(right), pt_ce_class_constant_access_type)) return thisValue();
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zval *theirType = slot(Z_OBJ_P(right), slots::type, "type");
		if (UNEXPECTED(theirType == NULL)) return zv::Val();
		return traversed(pt_type_traverse_call(fci, fcc, t, theirType));
	}

	/* new ConstTypeNode(new ConstFetchNode($this->type instanceof TemplateType
	 * ? $this->type->getName() : 'static', $this->constantName)) */
	zv::Val toPhpDocNode() const
	{
		zval *t = type();
		zval *name = t != NULL ? constantName() : NULL;
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zend_class_entry *templateType = pt_class(PT_CLASS_TEMPLATE_TYPE);
		if (UNEXPECTED(templateType == NULL)) return zv::Val();
		zval args[2];
		if (instanceof_function(Z_OBJCE_P(t), templateType)) {
			zv::Val templateName = pt_type_call(Z_OBJ_P(t), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(templateName.isUndef())) return zv::Val();
			ZVAL_COPY(&args[0], templateName.raw());
		} else {
			ZVAL_STRINGL(&args[0], "static", sizeof("static") - 1);
		}
		ZVAL_COPY_VALUE(&args[1], name);
		zv::Val constFetch = pt_type_new(PT_CLASS_CONST_FETCH_NODE, 2, args);
		zval_ptr_dtor(&args[0]);
		if (UNEXPECTED(constFetch.isUndef())) return zv::Val();
		return pt_type_new(PT_CLASS_CONST_TYPE_NODE, 1, constFetch.raw());
	}

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }

	void writeSlot(uint32_t index, zval *value) { pt_write_slot(self, index, value); }

	/* $this->type->method(...$args); UNDEF = pending exception */
	zv::Val callType(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return pt_type_call(Z_OBJ_P(t), lcname, len, argc, argv);
	}

	/* the tail of traverse()/traverseSimultaneously(): `$this->type === $type
	 * ? $this : new self($type, $this->constantName, $this->nativeType)`
	 * (UNDEF in = UNDEF out) */
	zv::Val traversed(zv::Val type) const
	{
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zval *t = this->type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		if (pt_type_same_object(t, type.raw())) return thisValue();
		zval *name = constantName();
		zval *native = name != NULL ? nativeType() : NULL;
		if (UNEXPECTED(native == NULL)) return zv::Val();
		return create(type.raw(), Z_STR_P(name), Z_TYPE_P(native) == IS_NULL ? NULL : native);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClassConstantAccessType;

bool pt_class_constant_access_type_new(zval *out, zval *type, zend_string *constantName)
{
	return pt_val_into(ClassConstantAccessType::create(type, constantName, NULL), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ClassConstantAccessType(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_class_constant_access_type)
{
	reg::Class cls("PHPStan\\Type\\ClassConstantAccessType");
	ptdecl::ClassConstantAccessType::declareClass(cls);
	/* the slots must stay in this order (PT_CCA_PROP_*); the trait registrar
	 * declares $result after them */
	ptdecl::ClassConstantAccessType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *nativeType = NULL;
		zend_string *constantName;
		if (!zp::parse<zp::TypeObj, zp::Str, zp::Opt<zp::TypeObjOrNull>>(execute_data, type, constantName, nativeType)) RETURN_THROWS();
		PT_THIS.construct(type, constantName, nativeType);
	});

	cls.method<&ClassConstantAccessType::withNativeType, zp::TypeObj>(sigs::withNativeType);

	cls.method<&ClassConstantAccessType::getReferencedClasses>(sigs::getReferencedClasses);
	cls.op<PT_OP_GET_REFERENCED_CLASSES, &ClassConstantAccessType::getReferencedClasses>();

	cls.method<&ClassConstantAccessType::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);

	cls.method<&ClassConstantAccessType::equals, zp::TypeObj>(sigs::equals);

	cls.method<&ClassConstantAccessType::describe, zp::Obj>(sigs::describe);

	cls.method<&ClassConstantAccessType::isResolvable>(sigs::isResolvable);

	cls.method<&ClassConstantAccessType::getResult>(sigs::getResult);

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

	cls.method<&ClassConstantAccessType::toPhpDocNode>(sigs::toPhpDocNode);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::ClassConstantAccessType::registerTraits(cls);

	cls.shadow(&pt_ce_class_constant_access_type);
}

/* }}} */
