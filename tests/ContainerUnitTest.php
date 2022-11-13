<?php

use Framework\Container\Container;
use Framework\Container\Contracts\SingletonContract;
use TestModule\Test;

class ContainerUnitTest
{
    public function __construct(
        private TestContainer $container = new TestContainer()
    ) {
        //
    }

    public function all(): void
    {
        $this->testBinding();
        $this->testSharing();
        $this->testClosureInjection();
        $this->testMethodInjection();
        $this->testContainerNew();
        $this->testMethodSharedInjection();
    }

    public function testBinding(): void
    {
        Test::printInfo('Тест привязок');

        Test::run(
            desc: 'Привязка класса к интерфейсу',
            test: function () {
                $this->container->bind(TestContract::class, TestDependency::class);
                Test::assertTrue($this->container->isBinded(TestContract::class));
                $this->container->unbind(TestContract::class);
            }
        );

        Test::run(
            desc: 'Привязка класса к абстрактному классу',
            test: function () {
                $this->container->bind(TestAbstract::class, TestDependency::class);
                Test::assertTrue($this->container->isBinded(TestAbstract::class));
                $this->container->unbind(TestAbstract::class);
            }
        );

        Test::run(
            desc: 'Привязка класса к интерфейсу, который он не реализует',
            test: function () {
                Test::assertException(function () {
                    $this->container->bind(TestContract::class, TestClass::class);
                });
            }
        );

        Test::run(
            desc: 'Привязка класса к абстрактному классу, который он не наследует',
            test: function () {
                Test::assertException(function () {
                    $this->container->bind(TestAbstract::class, TestClass::class);
                });
            }
        );
    }

    public function testSharing(): void
    {
        Test::printInfo('Тест добавления объектов в контейнер');

        Test::run(
            desc: 'Добавление объекта в контейнер по указанному интерфейсу',
            test: function () {
                $this->container->share(new TestDependency(), TestContract::class);
                Test::assertTrue($this->container->isShared(TestContract::class));
                $this->container->remove(TestContract::class);
            }
        );

        Test::run(
            desc: 'Добавление объекта в контейнер по его классу',
            test: function () {
                $this->container->share(new TestDependency());
                Test::assertTrue($this->container->isShared(TestDependency::class));
                $this->container->remove(TestDependency::class);
            }
        );
    }

    public function testClosureInjection(): void
    {
        Test::printInfo('Тест инъекции зависимостей для \Closure');

        Test::run(
            desc: 'Инъекция зависимостей',
            test: function () {
                $arg2 = 'second parameter';

                $injectionResult = $this->container->tap(function (TestDependency $testDependency, string $param2) use ($arg2) {
                    return $testDependency->status === $testDependency::STATUS_INIT && $param2 === $arg2;
                }, $arg2);

                Test::assertTrue($injectionResult);
            }
        );

        Test::run(
            desc: 'Не правильное описание параметров для внедрения зависимостей',
            test: function () {
                Test::assertException(function () {
                    $this->container->tap(function (string $param2, TestDependency $testDependency) {
                        //
                    }, null);
                });
            }
        );

        Test::run(
            desc: 'Передача большего количества аргументов в функцию во время внедрение зависимостей',
            test: function () {
                Test::assertException(function () {
                    $this->container->tap(function (TestDependency $testDependency, string $param2) {
                        //
                    }, null, null);
                });
            }
        );

        Test::run(
            desc: 'Передача меньшего количества аргументов в функцию во время внедрение зависимостей',
            test: function () {
                Test::assertException(function () {
                    $this->container->tap(function (TestDependency $testDependency, string $param2) {
                        //
                    });
                });
            }
        );
    }

    public function testMethodInjection(): void
    {
        Test::printInfo('Тест инъекции зависимостей для метода класса');

        Test::run(
            desc: 'Инъекция зависимостей',
            test: function () {
                $testClass = new TestClass();
                $injectionResult = $this->container->tap([$testClass, 'oneDependency']);

                Test::assertTrue($injectionResult);
            }
        );

        Test::run(
            desc: 'Вызов метода tap для вызова не статического метода при указании класса, а не объекта',
            test: function () {
                Test::assertException(function () {
                    $this->container->tap([TestClass::class, 'oneDependency']);
                });
            }
        );

        Test::run(
            desc: 'Инъекция зависимостей в статический метод класса',
            test: function () {
                $injectionResult = $this->container->tap([TestClass::class, 'oneDependencyStatic']);

                Test::assertTrue($injectionResult);
            }
        );
    }

    public function testContainerNew(): void
    {
        Test::printInfo('Тест создание нового экземпляра объекта');

        Test::run(
            desc: 'У класса в конструкторе описана инициализация полей, без передачи параметров',
            test: function () {
                Test::assertNonException(function () {
                    $this->container->new(TestDependency::class);
                });
            }
        );

        Test::run(
            desc: 'У класса в конструкторе описана инициализация полей, с передачей параметров',
            test: function () {
                Test::assertNonException(function () use (&$testDependency) {
                    $testDependency = $this->container->new(TestDependency::class, TestDependency::STATUS_MODIFIED);
                });
            }
        );

        Test::run(
            desc: 'Создание экземпляра синглтона дважды, и получение одного и того же объекта',
            test: function () {
                $this->container->bind(TestAbstract::class, TestSingleton::class);
                $this->container->new(TestAbstract::class, TestSingleton::STATUS_MODIFIED);
                $instance = $this->container->new(TestSingleton::class);

                Test::assertTrue($instance->status === TestSingleton::STATUS_MODIFIED);
            }
        );
    }

    public function testMethodSharedInjection(): void
    {
        Test::printInfo('Тест инъекции зависимостей с ипсользованием объектов из контейнера');

        Test::run(
            desc: 'Инъекция зависимостей в метод из списка доступных к внедрению объектов (Объекты были созданы ранее и могли быть изменены)',
            test: function () {
                $testDependency = new TestDependency(TestDependency::STATUS_MODIFIED);
                $this->container->share($testDependency);

                Test::assertTrue(
                    $this->container->tap([new TestClass(), 'modifiedDependency'])
                );
            }
        );
    }
}

class TestContainer extends Container
{
    public function isBinded(string $abstract): bool
    {
        return key_exists($abstract, $this->bindings);
    }

    public function unbind(string $abstract): void
    {
        unset($this->bindings[$abstract]);
    }

    public function isShared(string $class): bool
    {
        return key_exists($class, $this->shared);
    }

    public function remove(string $class): void
    {
        unset($this->shared[$class]);
    }
}

interface TestContract
{
    //
}

abstract class TestAbstract
{
    //
}

class TestDependency extends TestAbstract implements TestContract
{
    public const STATUS_INIT = 'Инициализирован';
    public const STATUS_MODIFIED = 'Изменен';

    public function __construct(
        public string $status = self::STATUS_INIT
    ) {
        //
    }
}

class TestSingleton extends TestDependency implements SingletonContract
{
    //
}

class TestClass
{
    public function oneDependency(TestDependency $testDependency): bool
    {
        return $testDependency->status === $testDependency::STATUS_INIT;
    }

    public static function oneDependencyStatic(TestDependency $testDependency): bool
    {
        return $testDependency->status === $testDependency::STATUS_INIT;
    }

    public static function modifiedDependency(TestDependency $testDependency): bool
    {
        return $testDependency->status === $testDependency::STATUS_MODIFIED;
    }
}
