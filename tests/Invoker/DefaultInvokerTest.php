<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Joby\ContextInjection\Invoker;

use Joby\ContextInjection\Container;
use Joby\ContextInjection\TestClasses\TestClass_requires_A_and_B;
use Joby\ContextInjection\TestClasses\TestClassA;
use Joby\ContextInjection\TestClasses\TestClassB;
use PHPUnit\Framework\TestCase;

class DefaultInvokerTest extends TestCase
{
    public function testEmptyInstantiation(): void
    {
        // Note that we really only test really basic instantiation here,
        // because more complex instantiation with dependencies is tested in
        // ContextTest.
        $con = new Container();
        $inv = new DefaultInvoker($con);
        $this->assertInstanceOf(
            TestClassA::class,
            $inv->instantiate(TestClassA::class)
        );
        $this->assertInstanceOf(
            TestClassB::class,
            $inv->instantiate(TestClassB::class)
        );
    }

    public function testEmptyExecution(): void
    {
        $con = new Container();
        $inv = new DefaultInvoker($con);
        $this->assertEquals(
            'Hello, world!',
            $inv->execute(function () {
                return 'Hello, world!';
            })
        );
        $this->assertEquals(
            'Hello, world!',
            $inv->execute(testFunction(...))
        );
        $this->assertEquals(
            'TestClassA static string',
            $inv->execute(TestClassA::getStaticString(...))
        );
        $this->assertEquals(
            'TestClassA instance string',
            $inv->execute((new TestClassA())->getInstanceString(...))
        );
    }

    public function testExecutionWithDependencies(): void
    {
        $con = new Container();
        $inv = new DefaultInvoker($con);
        $a = new TestClassA();
        $b = new TestClassB();
        $con->register($a);
        $con->register($b);
        $this->assertEquals(
            $a,
            $inv->execute(function (TestClassA $a): TestClassA {
                return $a;
            })
        );
        $this->assertEquals(
            $b,
            $inv->execute(function (TestClassB $b): TestClassB {
                return $b;
            })
        );
        // now change the context to use new instances, and it should return those
        $a2 = new TestClassA();
        $b2 = new TestClassB();
        $con->register($a2);
        $con->register($b2);
        $this->assertEquals(
            $a2,
            $inv->execute(function (TestClassA $a): TestClassA {
                return $a;
            })
        );
        $this->assertEquals(
            $b2,
            $inv->execute(function (TestClassB $b): TestClassB {
                return $b;
            })
        );
        $this->assertNotEquals(
            $a,
            $inv->execute(function (TestClassA $a): TestClassA {
                return $a;
            })
        );
        $this->assertNotEquals(
            $b,
            $inv->execute(function (TestClassB $b): TestClassB {
                return $b;
            })
        );
    }

    public function testInstantiationWithDependencies(): void
    {
        $con = new Container();
        $inv = new DefaultInvoker($con);
        $a = new TestClassA();
        $b = new TestClassB();
        $con->register($a);
        $con->register($b);
        $c = $inv->instantiate(TestClass_requires_A_and_B::class);
        $this->assertInstanceOf(
            TestClass_requires_A_and_B::class,
            $c
        );
        // check that the dependencies were injected correctly
        $this->assertEquals($a, $c->a);
        $this->assertEquals($b, $c->b);
    }
}

function testFunction(): string
{
    return 'Hello, world!';
}
