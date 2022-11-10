<?php

namespace Framework\Container\Contracts;

interface ContainerContract
{

    /**
     * Устанавливант привязку интерфейса или абстрактного класса с его конкретной реализацией.
     *
     * @param string $abstract Интерфейс или абстрактный класс к которому будет осуществляться привязка.
     * @param string $concrete Класс для привязки.
     * @return static
     */
    public function bind(string $abstract, string $concrete): static;

    /**
     * Получить привязку для интерфейса или абстрактного класса.
     *
     * @param string $abstract Интерфейс или абстрактный класс дял которого нужно получить привязку.
     * @return string|null
     */
    public function resolveAbstract(string $abstract): ?string;

    /**
     * Добавляет объект в контейнер по имени класса.
     *
     * @param object $object Объект, который нужно сделать доступным.
     * @param string|null $abstract Интерфейс или абстрактный класс, по которому должен быть доступен объект.
     *                              Если не указано, то будет класс самого объекта.
     * @return static
     */
    public function share(object $object): static;

    /**
     * Получить объект из контейнера по привязанному интерфейсу или классу.
     *
     * @param string $abstract Интерфейс или класс, для которого нужно получить объект из контейнера.
     * @return object|null
     */
    public function get(string $abstract): ?object;

    /**
     * Выполнить функцию/метод с внедрением зависимостей.
     *
     * @param string|\Closure|array<mixed> $action Если необходимо вызвать функцию, то передать строку.
     *                                             Если метод класса, то массив, где первый элемент это
     *                                             объект, а второй строка с методом.
     * @param array<mixed> ...$args Аргументы для вызова.
     * @return mixed
     */
    public function tap(string|array $action, ...$args): mixed;

    /**
     * Создать новый экземпляр класса с внедрением зависимостей.
     *
     * @param string $typeToInstantiate Интерфейс или класс, для котрого нужно создать новый экземпляр.
     * @param array<mixed> ...$args Аргументы конструктора.
     * @return object
     * @throws InvalidArgumentException
     */
    public function new(string $typeToInstantiate, ...$args): object;
}
