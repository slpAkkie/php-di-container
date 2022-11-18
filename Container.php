<?php

namespace Services\Container;

use Services\Container\Contracts\ContainerContract;
use Services\Container\Contracts\SingletonContract;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class Container implements ContainerContract
{
    /**
     * Список привязок интерфейсов и абстрактных классов
     * к классам с конкретной их реализацией.
     *
     * @var array<string>
     */
    protected array $bindings = [];

    /**
     * Список объектов, доступных к внедрению.
     *
     * @var array<object>
     */
    protected array $shared = [];

    /**
     * Список созданных синглтонов.
     *
     * @var array<object>
     */
    protected array $singletons = [];

    /**
     * Устанавливант привязку интерфейса или абстрактного класса с его конкретной реализацией.
     *
     * @param string $abstract Интерфейс или абстрактный класс к которому будет осуществляться привязка.
     * @param string $concrete Класс для привязки.
     * @return static
     */
    public function bind(string $abstract, string $concrete): static
    {
        if (!is_subclass_of($concrete, $abstract)) {
            throw new InvalidArgumentException(
                "Класс [{$concrete}] не является потомком и не реализует [{$abstract}]"
            );
        }

        $this->bindings[$abstract] = $concrete;

        return $this;
    }

    /**
     * Получить привязку для интерфейса или абстрактного класса.
     *
     * @param string $abstract Интерфейс или абстрактный класс дял которого нужно получить привязку.
     * @return string|null
     */
    protected function resolveAbstract(string $abstract): ?string
    {
        return key_exists($abstract, $this->bindings)
            ? $this->bindings[$abstract]
            : $abstract;
    }

    /**
     * Добавляет объект в контейнер по имени класса.
     * ? Нужно ли бросать ошибку, если объект этого класса (или реализуемого) уже есть в списке доступных к внедрению?
     *
     * @param object $object Объект, который нужно сделать доступным.
     * @param string|null $abstract Интерфейс или абстрактный класс, по которому должен быть доступен объект.
     *                              Если не указано, то будет класс самого объекта.
     * @return static
     */
    public function share(object $object, ?string $abstract = null): static
    {
        $this->shared[$abstract ?? $object::class] = $object;

        if (is_subclass_of($object, SingletonContract::class)) {
            $this->saveSingleton($object);
        }

        return $this;
    }

    /**
     * Запоминает синглтон.
     *
     * @param object $object
     * @return void
     */
    protected function saveSingleton(object $object): void
    {
        $this->singletons[$object::class] = $object;
    }

    /**
     * Получить синглтон, если он уже был создан, иначе null.
     *
     * @param string $singletonClass
     * @return object|null
     */
    protected function getSingleton(string $singletonClass): ?object
    {
        return key_exists($singletonClass, $this->singletons)
            ? $this->singletons[$singletonClass]
            : null;
    }

    /**
     * Получить объект из контейнера по привязанному интерфейсу или классу.
     *
     * @param string $abstract Интерфейс или класс, для которого нужно получить объект из контейнера.
     * @return object|null
     */
    public function get(string $abstract): ?object
    {
        return key_exists($abstract, $this->shared)
            ? $this->shared[$abstract]
            : null;
    }

    /**
     * Выполнить функцию/метод с внедрением зависимостей.
     *
     * @param callable $action Если необходимо вызвать функцию, то передать строку.
     *                 Если метод класса, то массив, где первый элемент это
     *                 объект, а второй строка с методом.
     * @param array<mixed> ...$args Аргументы для вызова.
     * @return mixed
     */
    public function tap(callable $action, ...$args): mixed
    {
        if (is_array($action)) {
            return $this->tapMethod($action[0], $action[1], $args);
        } else if (is_string($action) && count($action = explode('::', $action)) === 2) {
            return $this->tapMethod($action[0], $action[1], $args);
        }

        return $this->tapFunc($action, $args);
    }

    /**
     * Вызвать метод класса с внедрением зависимостей.
     *
     * @param object $object Объект у которого нужно вызвать метод.
     * @param string $method Имя метода.
     * @param array<mixed> $args Аргументы для вызова.
     * @return mixed
     */
    protected function tapMethod(object|string $object, string $method, array $args = []): mixed
    {
        $methodReflection = new ReflectionMethod($object, $method);
        $args = $this->collectArgs($methodReflection->getParameters(), $args);

        return $methodReflection->invoke($methodReflection->isStatic() ? null : $object, ...$args);
    }

    /**
     * Вызвать функцию с внедрением зависимостей.
     *
     * @param callable $function Имя функции для вызова.
     * @param array<mixed> $args Аргументы для вызова.
     * @return mixed
     */
    protected function tapFunc(callable $function, array $args = []): mixed
    {
        $functionReflection = new ReflectionFunction($function);
        $args = $this->collectArgs($functionReflection->getParameters(), $args);

        return $functionReflection->invoke(...$args);
    }

    /**
     * Собрать массив аргументов в позиционном порядке.
     *
     * @param array<ReflectionParameter> $parametersReflection Массив отражений параметров.
     * @param array<mixed> $inputArgs Входные аргументы, переданные для вызова.
     * @return array<mixed>
     * @throws InvalidArgumentException
     */
    protected function collectArgs(array $parametersReflection, array $inputArgs): array
    {
        $args = [];

        // Будем внедрять только первые параметры, которые не перекрываются переданными.
        $injectParameterCount = count($parametersReflection) - count($inputArgs);

        // Если количество свободных параметров меньше 0,
        // значит передали больше аргументов, чем принимает вызываемая функция (метод).
        if ($injectParameterCount < 0) {
            throw new InvalidArgumentException(
                'Количество переданных аргументов больше, чем принимает вызываемая функция (метод).'
            );
        }
        // Если количество свободных параметров 0, то и внедрять нечего.
        // Отдаем список входных аргументов.
        else if ($injectParameterCount === 0) {
            return $inputArgs;
        }

        // Итерируемся по параметрам.
        for ($i = 0; $i < $injectParameterCount; $i++) {
            $typeReflection = $parametersReflection[$i]->getType();

            // Если параметр описан не однозначно, то устанавливаем для него входной аргумент
            // и убираем этот аргумент из списка входных.
            if (!$typeReflection instanceof ReflectionNamedType) {
                throw new InvalidArgumentException(
                    'Параметр, который требует внедрения должен быть описан однозначно.'
                );
            }

            // В противном случае, если это не встроенный тип, мы можем попытаться найти объект нужного типа.
            if (!$typeReflection->isBuiltin()) {
                $args[] = $this->resolveType($typeReflection->getName());
            } else if ($typeReflection->allowsNull()) {
                $args[] = null;
            }
        }

        return [...$args, ...$inputArgs];
    }

    /**
     * Получить объект из контейнера или создать новый на основе типа.
     * При создании нового экземпляра, он не сохраняется в контейнере.
     *
     * @param string $typeToResolve Класс, для которого нужно получить объект.
     * @return mixed
     */
    protected function resolveType(string $typeToResolve): mixed
    {
        // Сначала проверяем, есть ли уже объект для внедрения у этого типа.
        $object = $this->get(
            $this->resolveAbstract($typeToResolve)
        );

        // Если объекта нет, то пытаемся создать для него новый объект.
        if (is_null($object)) {
            $object = $this->new($typeToResolve);
        }

        return $object;
    }

    /**
     * Создать новый экземпляр класса с внедрением зависимостей.
     *
     * @param string $typeToInstantiate Интерфейс или класс, для котрого нужно создать новый экземпляр.
     * @param array<mixed> ...$args Аргументы конструктора.
     * @return object
     * @throws InvalidArgumentException
     */
    public function new(string $typeToInstantiate, ...$args): object
    {
        $classReflection = new ReflectionClass($typeToInstantiate);

        // Если нам передали интерфейс или абстрактный класс, экземпляр корого нельзя создать
        if (!$classReflection->isInstantiable()) {
            // Попытаемся найти привязку.
            // Если привязки нет, то бросим исключение.
            $concrete = $this->resolveAbstract($typeToInstantiate);
            if (is_null($concrete)) {
                throw new InvalidArgumentException(
                    "Не возможно создать экземпляр класса [{$typeToInstantiate}]."
                );
            }

            // Иначе возьмем отражение этой привязки.
            $classReflection = new ReflectionClass($concrete);
        }

        $typeName = $classReflection->getName();
        $isSingleton = is_subclass_of($typeName, SingletonContract::class);

        // Если тип, который нужно создать является синглтоном,
        // то попытаемся найти уже созданный экземпляр.
        if ($isSingleton) {
            $instance = $this->getSingleton($typeName);
            if (!is_null($instance)) {
                return $instance;
            }
        }

        // Проверим наличие конструктора и, если он есть, соберем аргументы.
        $classConstructor = $classReflection->getConstructor();
        if (!is_null($classConstructor)) {
            $args = $this->collectArgs($classConstructor->getParameters(), $args);
        }

        // Вернем созданный экземпляр.
        $instance = $classReflection->newInstance(...$args);
        // Если тип, является синглтоном, то сохраним его в контейнер.
        if ($isSingleton) {
            $this->saveSingleton($instance);
        }

        return $instance;
    }
}
