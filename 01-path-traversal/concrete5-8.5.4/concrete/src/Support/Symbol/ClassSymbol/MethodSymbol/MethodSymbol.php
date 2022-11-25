<?php
namespace Concrete\Core\Support\Symbol\ClassSymbol\MethodSymbol;

use Concrete\Core\Support\Symbol\ClassSymbol\ClassSymbol;

class MethodSymbol
{
    /**
     * @var \ReflectionMethod
     */
    protected $reflectionMethod;

    /**
     * @var ClassSymbol
     */
    protected $classSymbol;

    /**
     * The method handle.
     *
     * @var string
     */
    protected $handle;

    /**
     * The parameters.
     *
     * @var \ReflectionParameter[]
     */
    protected $parameters = array();

    /**
     * The docblock.
     *
     * @var string
     */
    protected $comment;

    /**
     * @param \ReflectionMethod $method
     */
    public function __construct(ClassSymbol $class, \ReflectionMethod $method)
    {
        $this->classSymbol = $class;
        $this->reflectionMethod = $method;
        $this->parameters = $method->getParameters();
        $this->handle = $method->getName();
    }

    /**
     * Render the Method.
     *
     * @param string $eol
     * @param string $padding
     *
     * @return string
     */
    public function render($eol = "\n", $padding = '    ')
    {
        $method = $this->reflectionMethod;
        if ($method->isPrivate() || substr($method->getName(), 0, 2) === '__' || $method->isAbstract()) {
            return '';
        }
        $rendered = '';
        $comment = $method->getDocComment();
        if ($comment !== false) {
            $comment = trim($comment);
            if ($comment !== '') {
                $rendered .= str_replace($eol . '*', $eol . ' *', implode($eol, array_map('trim', explode("\n", $comment)))) . $eol;
            }
        }
        $visibility = \Reflection::getModifierNames($method->getModifiers());
        $isStatic = $method->isStatic();
        if ((!$isStatic) && $this->classSymbol->isFacade()) {
            $visibility[] = 'static';
        }
        $rendered .= implode(' ', array_unique($visibility)) . ' function ' . $this->handle . '(';
        $params = array();
        $calling_params = array();
        foreach ($this->parameters as $parameter) {
            $param = '';

            if ($parameter->isArray()) {
                $param .= 'array ';
            } /*else if ($parameter->isCallable()) { // This should be enabled for php 5.4
                $param .= 'callable ';
            } */ else {
     try {
         if (is_object($parameter->getClass())) {
             $param .= $parameter->getClass()->getName() . ' ';
         }
     } catch (\ReflectionException $e) {
         $class = $this->reflectionMethod->getDeclaringClass()->getName();
         echo "Invalid type hint in {$class}::{$this->handle}\n";
     }
 }
            if ($parameter->isPassedByReference()) {
                $param .= "&";
            }
            $param .= '$' . $parameter->getName();

            if ($parameter->isOptional()) {
                $defaultValue = null;
                if (method_exists($parameter, 'getDefaultValueConstantName')) {
                    $defaultValue = $parameter->getDefaultValueConstantName();
                }
                if ($defaultValue) {
                    // Strip out wrong namespaces.
                    if (preg_match('/.\\\\(\\w+)$/', $defaultValue, $matches) && defined($matches[1])) {
                        $defaultValue = $matches[1];
                    }
                } else {
                    $v = $parameter->getDefaultValue();
                    switch (gettype($v)) {
                        case 'boolean':
                        case 'integer':
                        case 'double':
                        case 'NULL':
                            $defaultValue = json_encode($v);
                            break;
                        case 'string':
                            $defaultValue = '"' . addslashes($v) . '"';
                            break;
                        case 'array':
                            if (count($v)) {
                                $defaultValue = trim(var_export($v, true));
                            } else {
                                $defaultValue = 'array()';
                            }
                            break;
                        case 'object':
                        case 'resource':
                        default:
                            $defaultValue = trim(var_export($v, true));
                            break;
                    }
                }
                $param .= ' = ' . $defaultValue;
            }

            $params[] = $param;
            $calling_params[] = '$' . $parameter->getName();
        }
        $rendered .= implode(', ', $params) . "){$eol}{{$eol}";
        $class_name = $method->getDeclaringClass()->getName();
        $rendered .= $padding . 'return ';
        if ($isStatic) {
            $rendered .= $class_name . '::' . $method->getName();
        } elseif ($this->classSymbol->isFacade()) {
            $rendered .= 'static::$instance->' . $method->getName();
        } else {
            $rendered .= 'parent::' . $method->getName();
        }
        $rendered .= '(' . implode(', ', $calling_params) . ');' . $eol;
        $rendered .= '}' . $eol;

        return $rendered;
    }
}
