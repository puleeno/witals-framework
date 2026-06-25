<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Container;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Container\Container;
use Witals\Framework\Container\Exceptions\BindingResolutionException;
use Witals\Framework\Container\Exceptions\ContainerException;
use Witals\Framework\Container\Exceptions\NotFoundException;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        Container::setInstance(null);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    public function testGetInstanceReturnsNullWhenNotSet(): void
    {
        $this->assertNull(Container::getInstance());
    }

    public function testSetInstanceAndGet(): void
    {
        $container = new Container();
        Container::setInstance($container);

        $this->assertSame($container, Container::getInstance());
    }

    public function testBindRegistersBinding(): void
    {
        $this->container->bind('interface', 'concrete');

        $this->assertTrue($this->container->has('interface'));
    }

    public function testBindWithNullConcreteUsesAbstract(): void
    {
        $this->container->bind('stdClass');

        $instance = $this->container->make('stdClass');

        $this->assertInstanceOf('stdClass', $instance);
    }

    public function testBindWithClosure(): void
    {
        $this->container->bind('test', function () {
            return new \stdClass();
        });

        $instance = $this->container->make('test');

        $this->assertInstanceOf('stdClass', $instance);
    }

    public function testBindNotSharedCreatesNewInstance(): void
    {
        $this->container->bind('stdClass');

        $instance1 = $this->container->make('stdClass');
        $instance2 = $this->container->make('stdClass');

        $this->assertNotSame($instance1, $instance2);
    }

    public function testSingletonCreatesSharedInstance(): void
    {
        $this->container->singleton('stdClass');

        $instance1 = $this->container->make('stdClass');
        $instance2 = $this->container->make('stdClass');

        $this->assertSame($instance1, $instance2);
    }

    public function testSingletonWithClosure(): void
    {
        $this->container->singleton('test', function () {
            return new \stdClass();
        });

        $instance1 = $this->container->make('test');
        $instance2 = $this->container->make('test');

        $this->assertSame($instance1, $instance2);
    }

    public function testSingletonWithObject(): void
    {
        $object = new \stdClass();
        $this->container->singleton('test', $object);

        $instance = $this->container->make('test');

        $this->assertSame($object, $instance);
    }

    public function testInstanceRegistersExistingInstance(): void
    {
        $object = new \stdClass();
        $this->container->instance('test', $object);

        $instance = $this->container->make('test');

        $this->assertSame($object, $instance);
    }

    public function testInstanceOverridesBinding(): void
    {
        $this->container->bind('stdClass');
        $object = new \stdClass();
        $this->container->instance('stdClass', $object);

        $instance = $this->container->make('stdClass');

        $this->assertSame($object, $instance);
    }

    public function testMakeResolvesClassWithNoConstructor(): void
    {
        $instance = $this->container->make('stdClass');

        $this->assertInstanceOf('stdClass', $instance);
    }

    public function testMakeResolvesClassWithConstructor(): void
    {
        $instance = $this->container->make(ContainerTestDependency::class);

        $this->assertInstanceOf(ContainerTestDependency::class, $instance);
    }

    public function testMakeResolvesNestedDependencies(): void
    {
        $instance = $this->container->make(ContainerTestNestedDependency::class);

        $this->assertInstanceOf(ContainerTestNestedDependency::class, $instance);
        $this->assertInstanceOf(ContainerTestDependency::class, $instance->dependency);
    }

    public function testMakeWithParameters(): void
    {
        $instance = $this->container->make(ContainerTestDependency::class, ['value' => 'test']);

        $this->assertSame('test', $instance->value);
    }

    public function testMakeWithIndexedParameters(): void
    {
        $instance = $this->container->make(ContainerTestDependency::class, ['test']);

        $this->assertSame('test', $instance->value);
    }

    public function testMakeThrowsExceptionForCircularDependency(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $this->container->make(ContainerTestCircularA::class);
    }

    public function testMakeThrowsExceptionForNonInstantiableClass(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('not instantiable');

        $this->container->make(ContainerTestAbstract::class);
    }

    public function testMakeThrowsExceptionForNonExistentClass(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('does not exist');

        $this->container->make('NonExistentClass');
    }

    public function testCallExecutesClosure(): void
    {
        $result = $this->container->call(function () {
            return 'test';
        });

        $this->assertSame('test', $result);
    }

    public function testCallResolvesDependencies(): void
    {
        $result = $this->container->call(function (ContainerTestDependency $dep) {
            return $dep;
        });

        $this->assertInstanceOf(ContainerTestDependency::class, $result);
    }

    public function testCallWithParameters(): void
    {
        $result = $this->container->call(function ($value) {
            return $value;
        }, ['value' => 'test']);

        $this->assertSame('test', $result);
    }

    public function testCallWithArrayCallable(): void
    {
        $object = new ContainerTestCallable();
        $result = $this->container->call([$object, 'method']);

        $this->assertSame('called', $result);
    }

    public function testCallWithInvokable(): void
    {
        $object = new ContainerTestInvokable();
        $result = $this->container->call($object);

        $this->assertSame('invoked', $result);
    }

    public function testExtendModifiesExistingInstance(): void
    {
        $this->container->instance('test', new \stdClass());
        $this->container->extend('test', function ($instance) {
            $instance->extended = true;
            return $instance;
        });

        $instance = $this->container->make('test');

        $this->assertTrue($instance->extended);
    }

    public function testExtendModifiesBinding(): void
    {
        $this->container->bind('test', function () {
            $obj = new \stdClass();
            $obj->value = 'original';
            return $obj;
        });

        $this->container->extend('test', function ($instance) {
            $instance->extended = true;
            return $instance;
        });

        $instance = $this->container->make('test');

        $this->assertTrue($instance->extended);
        $this->assertSame('original', $instance->value);
    }

    public function testExtendThrowsExceptionForNonExistentBinding(): void
    {
        $this->expectException(NotFoundException::class);

        $this->container->extend('non_existent', function ($instance) {
            return $instance;
        });
    }

    public function testRunScopeAppliesBindings(): void
    {
        $this->container->bind('original', function () {
            return 'original';
        });

        $result = $this->container->runScope([
            'scoped' => function () {
                return 'scoped';
            }
        ], function ($container) {
            return $container->make('scoped');
        });

        $this->assertSame('scoped', $result);
    }

    public function testRunScopeRestoresOriginalState(): void
    {
        $this->container->bind('test', function () {
            return 'original';
        });

        $this->container->runScope([
            'test' => function () {
                return 'scoped';
            }
        ], function ($container) {
            return $container->make('test');
        });

        $instance = $this->container->make('test');

        $this->assertSame('original', $instance);
    }

    public function testRunScopeWithObjectInstance(): void
    {
        $object = new \stdClass();
        $object->value = 'scoped';

        $result = $this->container->runScope([
            'test' => $object
        ], function ($container) {
            return $container->make('test');
        });

        $this->assertSame($object, $result);
    }

    public function testHasReturnsTrueForBound(): void
    {
        $this->container->bind('test', 'stdClass');

        $this->assertTrue($this->container->has('test'));
    }

    public function testHasReturnsTrueForInstance(): void
    {
        $this->container->instance('test', new \stdClass());

        $this->assertTrue($this->container->has('test'));
    }

    public function testHasReturnsFalseForNonExistent(): void
    {
        $this->assertFalse($this->container->has('non_existent'));
    }

    public function testForgetInstanceRemovesInstance(): void
    {
        $this->container->singleton('stdClass');
        $this->container->make('stdClass');
        $this->container->forgetInstance('stdClass');

        $this->assertFalse(isset($this->container->getInstances()['stdClass']));
    }

    public function testFlushClearsAll(): void
    {
        $this->container->bind('test1', 'stdClass');
        $this->container->singleton('test2', 'stdClass');
        $this->container->instance('test3', new \stdClass());

        $this->container->flush();

        $this->assertFalse($this->container->has('test1'));
        $this->assertFalse($this->container->has('test2'));
        $this->assertFalse($this->container->has('test3'));
    }

    public function testGetBindingsReturnsBindings(): void
    {
        $this->container->bind('test', 'stdClass');

        $bindings = $this->container->getBindings();

        $this->assertArrayHasKey('test', $bindings);
    }

    public function testGetInstancesReturnsInstances(): void
    {
        $this->container->instance('test', new \stdClass());

        $instances = $this->container->getInstances();

        $this->assertArrayHasKey('test', $instances);
    }

    public function testResolveAliasedBinding(): void
    {
        $this->container->bind('interface', 'stdClass');

        $instance = $this->container->make('interface');

        $this->assertInstanceOf('stdClass', $instance);
    }

    public function testDefaultParameterValue(): void
    {
        $instance = $this->container->make(ContainerTestDefaultParameter::class);

        $this->assertSame('default', $instance->value);
    }

    public function testDefaultParameterWhenResolutionFails(): void
    {
        $instance = $this->container->make(ContainerTestDefaultParameterWithDependency::class);

        $this->assertSame('default', $instance->value);
    }

    public function testCallWithExplicitParameters(): void
    {
        $result = $this->container->call(fn(string $name) => "Hello {$name}", ['name' => 'World']);
        $this->assertSame('Hello World', $result);
    }

    public function testCallWithIndexedParameters(): void
    {
        $result = $this->container->call(fn(string $a, string $b) => $a . $b, ['first', 'second']);
        $this->assertSame('firstsecond', $result);
    }

    public function testCallOnObjectMethod(): void
    {
        $obj = new ContainerTestMethod();
        $result = $this->container->call([$obj, 'greet'], ['name' => 'PHP']);
        $this->assertSame('Hello PHP', $result);
    }

    public function testExtendOnSingletonResolvedBeforeExtension(): void
    {
        $this->container->singleton('counter', fn() => new \stdClass());
        $first = $this->container->make('counter');
        $first->value = 1;

        $this->container->extend('counter', fn($instance, $c) => $instance);
        $second = $this->container->make('counter');
        $this->assertSame(1, $second->value);
    }

    public function testRunScopeRestoresBindings(): void
    {
        $this->container->bind('key', fn() => 'original');
        $this->container->runScope(['key' => fn() => 'scoped'], function () {
            $this->assertSame('scoped', $this->container->make('key'));
        });
        $this->assertSame('original', $this->container->make('key'));
    }

    public function testRunScopeDoesNotLeakBindings(): void
    {
        $this->container->bind('leak.a', fn() => 'original');
        $this->container->runScope(['leak.a' => fn() => 'scoped'], function () {
            $this->assertSame('scoped', $this->container->make('leak.a'));
        });
        $this->assertSame('original', $this->container->make('leak.a'));
    }

    public function testRunScopeIsolation(): void
    {
        $this->container->bind('isolated.key', fn() => 'root');
        $result = $this->container->runScope(['isolated.key' => fn() => 'scoped'], function () {
            return $this->container->make('isolated.key');
        });
        $this->assertSame('scoped', $result);
        $this->assertSame('root', $this->container->make('isolated.key'));
    }

    public function testForgetInstanceNonExistentDoesNotThrow(): void
    {
        $this->container->forgetInstance('nonexistent');
        $this->expectNotToPerformAssertions();
    }

    public function testFlushClearsAllState(): void
    {
        $this->container->bind('test', fn() => 'value');
        $this->container->instance('obj', new \stdClass());
        $this->container->make('test');

        $this->container->flush();

        $this->assertEmpty($this->container->getBindings());
        $this->assertEmpty($this->container->getInstances());
        $this->assertFalse($this->container->has('test'));
    }

    public function testResolveWithPrimitiveParameterIsSkipped(): void
    {
        $instance = $this->container->make(ContainerTestWithPrimitive::class, ['name' => 'test']);
        $this->assertSame('test', $instance->name);
    }
}

class ContainerTestMethod
{
    public function greet(string $name): string
    {
        return "Hello {$name}";
    }
}

class ContainerTestWithPrimitive
{
    public function __construct(public string $name)
    {
    }
}

class ContainerTestDependency
{
    public function __construct(public string $value = 'default')
    {
    }
}

class ContainerTestNestedDependency
{
    public function __construct(public ContainerTestDependency $dependency)
    {
    }
}

class ContainerTestCircularA
{
    public function __construct(ContainerTestCircularB $b)
    {
    }
}

class ContainerTestCircularB
{
    public function __construct(ContainerTestCircularA $a)
    {
    }
}

abstract class ContainerTestAbstract
{
}

class ContainerTestCallable
{
    public function method(): string
    {
        return 'called';
    }
}

class ContainerTestInvokable
{
    public function __invoke(): string
    {
        return 'invoked';
    }
}

class ContainerTestDefaultParameter
{
    public function __construct(public string $value = 'default')
    {
    }
}

class ContainerTestDefaultParameterWithDependency
{
    public function __construct(public string $value = 'default', ContainerTestNonExistent $dep = null)
    {
    }
}

class ContainerTestNonExistent
{
}
