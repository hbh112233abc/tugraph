<?php
namespace bingher\test;

use PHPUnit\Framework\TestCase;

/**
 * 单元测试基础类
 */
class Base extends TestCase
{

    /**
     * 方法调用
     *
     * @param mixed  $class      类名或类实例
     * @param string $methodName 方法名
     * @param array  $args       传参
     *
     * @return mixed
     */
    protected function call($class, string $methodName, array $args = [])
    {
        if (is_string($class)) {
            $class    = new \ReflectionClass($class);
            $instance = $class->newInstance();
        } else {
            $instance = $class;
        }

        $method = new \ReflectionMethod($instance, $methodName);
        $method->setAccessible(true);
        if (empty($args)) {
            return $method->invoke($instance);
        } else {
            return $method->invokeArgs($instance, $args);
        }
    }

    /**
     * 类属性
     *
     * @param mixed  $class 类名或类实例
     * @param string $key   属性键名
     * @param mixed  $value 属性值,默认null,如果null表示获取属性值
     *
     * @return mixed
     */
    protected function prop($class, string $key, $value = null)
    {
        if (is_string($class)) {
            $class    = new \ReflectionClass($class);
            $instance = $class->newInstance();
        } else {
            $instance = $class;
        }
        $prop = new \ReflectionProperty($class, $key);
        $prop->setAccessible(true);
        if (is_null($value)) {
            return $prop->getValue($instance);
        } else {
            return $prop->setValue($instance, $value);
        }
    }

    /**
     * 浏览器友好的变量输出
     * @param mixed $vars 要输出的变量
     * @return void
     */
    protected function dump(...$vars)
    {
        ob_start();
        var_dump(...$vars);

        $output = ob_get_clean();
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);

        if (PHP_SAPI == 'cli') {
            $output = PHP_EOL . $output . PHP_EOL;
        } else {
            if (!extension_loaded('xdebug')) {
                $output = htmlspecialchars($output, ENT_SUBSTITUTE);
            }
            $output = '<pre>' . $output . '</pre>';
        }

        echo $output;
    }
}
