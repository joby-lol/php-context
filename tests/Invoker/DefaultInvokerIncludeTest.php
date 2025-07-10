<?php

namespace Joby\ContextInjection\Invoker;

use Joby\ContextInjection\Config\Config;
use Joby\ContextInjection\Config\DefaultConfig;
use Joby\ContextInjection\Container;
use Joby\ContextInjection\TestClasses\TestClassA;
use Joby\ContextInjection\TestClasses\TestClassB;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DefaultInvokerIncludeTest extends TestCase
{
    public function testBasicInclude(): void
    {
        $con = new Container();
        $con->register(TestClassA::class);
        $a = $con->get(TestClassA::class);
        $this->assertEquals(
            $a,
            $con->get(Invoker::class)->include(__DIR__ . '/include_tests/testBasicInclude.php')
        );
    }

    public function testNonFullyQualifiedClassName(): void
    {
        $con = new Container();
        $con->register(TestClassB::class);
        $b = $con->get(TestClassB::class);
        $this->assertEquals(
            $b,
            $con->get(Invoker::class)->include(__DIR__ . '/include_tests/testNonFullyQualifiedClassName.php')
        );
    }

    public function testCategoryName(): void
    {
        $con = new Container();
        $con->register(TestClassA::class, 'test_category');
        $a = $con->get(TestClassA::class, 'test_category');
        $this->assertEquals(
            $a,
            $con->get(Invoker::class)->include(__DIR__ . '/include_tests/testCategoryName.php')
        );
    }

    public function testConfigValue(): void
    {
        $con = new Container();
        $config = new DefaultConfig([], ['test_config_key' => 'test_value']);
        $con->register($config);

        $this->assertEquals(
            'test_value',
            $con->get(Invoker::class)->include(__DIR__ . '/include_tests/testConfigValue.php')
        );
    }

    public function testMultipleVariables(): void
    {
        $con = new Container();
        $con->register(TestClassA::class);
        $con->register(TestClassB::class);
        $config = new DefaultConfig([], ['test_config_key' => 'test_value']);
        $con->register($config);

        $a = $con->get(TestClassA::class);
        $b = $con->get(TestClassB::class);

        $result = $con->get(Invoker::class)->include(__DIR__ . '/include_tests/testMultipleVariables.php');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('test_a', $result);
        $this->assertArrayHasKey('test_b', $result);
        $this->assertArrayHasKey('test_value', $result);
        $this->assertEquals($a, $result['test_a']);
        $this->assertEquals($b, $result['test_b']);
        $this->assertEquals('test_value', $result['test_value']);
    }

    public function testNullableTypes(): void
    {
        $con = new Container();
        $con->get(Config::class)->set('nullable_string_key', null);
        $con->get(Config::class)->set('nullable_int_key', null);

        $result = $con->get(Invoker::class)->include(__DIR__ . '/include_tests/testNullableTypes.php');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('nullable_string', $result);
        $this->assertArrayHasKey('nullable_int', $result);
        $this->assertNull($result['nullable_string']);
        $this->assertNull($result['nullable_int']);

        // Test with non-null values
        $con->get(Config::class)->set('nullable_string_key', 'string_value');
        $con->get(Config::class)->set('nullable_int_key', 42);

        $result = $con->get(Invoker::class)->include(__DIR__ . '/include_tests/testNullableTypes.php');

        $this->assertEquals('string_value', $result['nullable_string']);
        $this->assertEquals(42, $result['nullable_int']);
    }

    public function testFileNotFound(): void
    {
        $con = new Container();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not read file');
        $con->get(Invoker::class)->include(__DIR__ . '/include_tests/non_existent_file.php');
    }

    public function testNoDocblock(): void
    {
        $con = new Container();
        $result = $con->get(Invoker::class)->include(__DIR__ . '/include_tests/testNoDocblock.php');
        $this->assertEquals('No docblock', $result);
    }

    public function testInvalidClassType(): void
    {
        $con = new Container();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not find use statement for class');
        $con->get(Invoker::class)->include(__DIR__ . '/include_tests/testInvalidClassType.php');
    }

    public function testArrayType(): void
    {
        $con = new Container();
        $con->get(Config::class)->set('test_array_key', ['item1', 'item2']);
        $result = $con->get(Invoker::class)->include(__DIR__ . '/include_tests/testArrayType.php');
        $this->assertIsArray($result);
        $this->assertEquals(['item1', 'item2'], $result);
    }

    public function testBoolType(): void
    {
        $con = new Container();
        $con->get(Config::class)->set('test_bool_key', true);
        $result = $con->get(Invoker::class)->include(__DIR__ . '/include_tests/testBoolType.php');
        $this->assertTrue($result);
    }

    public function testFloatType(): void
    {
        $con = new Container();
        $con->get(Config::class)->set('test_float_key', 3.14);
        $result = $con->get(Invoker::class)->include(__DIR__ . '/include_tests/testFloatType.php');
        $this->assertEquals(3.14, $result);
    }

    public function testIntType(): void
    {
        $con = new Container();
        $con->get(Config::class)->set('test_int_key', 42);
        $result = $con->get(Invoker::class)->include(__DIR__ . '/include_tests/testIntType.php');
        $this->assertEquals(42, $result);
    }
}
