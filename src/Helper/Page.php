<?php
/**
 * Этот файл является частью пакета GM Framework.
 * 
 * @link https://gearmagic.ru/framework/
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Site\Helper;

use Gm;
use Gm\Db\Sql\Select;
use Gm\Stdlib\StaticClass;
use Gm\Db\Adapter\Driver\AbstractCommand;

/**
 * Вспомогательный класс Page, возвращает информацию о элементах сайта (
 * категориях, статьях, галереях изображений...) из базы данных по указанному запросу.
 * 
 * Для получения информации о элементах сайта, указывают опции запроса.
 * Опции запроса имееют вид:
 *    1) для строки: "columns[]=field1&columns[]=field2&id=1&order[]=field1,asc&..."
 *    2) для массива:
 *    [
 *        "columns" => ["field1", "field2"...] или ["*"],
 *        "id"      => "1",
 *        "order"   => ["field1" => "asc", "field2" => "desc"],
 *        ....
 *    ],
 *    где свойства опций:
 *    - "columns" имена полей,
 *    - "if" условие запроса,
 *    - "count" количество возвращаемых записей,
 *    - "order" порядок сортировки записей,
 *    - "offset" пропустить указанное количество записей и возвратить "count" записей.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Site\Helper
 * @since 2.0
 */
class Page extends StaticClass
{
    /**
     * Возвращает информацию о элементе (таблица статьи, категори...).
     * 
     * @param string $tableName Имя таблицы базы данных.
     * @param string|array $options Параметры запроса.
     * @param bool $result Если значение `true`, возвращает результат запроса, иначе команду 
     *     выполнения SQL инструкций.
     * 
     * @return array|AbstractCommand
     */
    public static function getElement(string $tableName, string|array $options, bool $result = true): array|AbstractCommand
    {
        if (is_string($options)) {
            $options = static::optionsToArray($options);
        }
        $options = static::prepareOptions($options);

        /** @var Select $select */
        $select = (new Select())
            ->from($tableName)
            ->columns($options['columns']);
        if ($options['if']) {
            $select->where($options['if']);
        }
        $select->limit(1);

        /** @var AbstractCommand $command */
        $command = Gm::$app->db->createCommand($select)->execute();
        return $result ? $command->queryOne() : $command;
    }

    /**
     * Возвращает информацию о элементах (таблица статьи, категори...).
     * 
     * @param string $tableName Имя таблицы базы данных.
     * @param string|array $options Параметры запроса.
     * @param bool $result Если значение `true`, возвращает результат запроса, иначе команду 
     *     выполнения SQL инструкций.
     * 
     * @return array|AbstractCommand
     */
    public static function getElements(string $tableName, array $options = [], bool $result = true): array|AbstractCommand
    {
        if (is_string($options)) {
            $options = static::optionsToArray($options);
        }
        $options = static::prepareOptions($options);

        /** @var Select $select */
        $select = (new Select())
            ->from($tableName)
            ->columns($options['columns']);
        if ($options['if'])
            $select->where($options['if']);
        if ($options['count'])
            $select->limit($options['count']);
        if ($options['offset'])
            $select->offset($options['offset']);
        if ($options['order'])
            $select->order($options['order']);

        /** @var AbstractCommand $command */
        $command = Gm::$app->db->createCommand($select)->query();
        return $result ? $command->fetchAll() : $command;
    }

    /**
     * Возвращает статью сайта.
     * 
     * @param string|array $options Параметры запроса.
     * @param bool $result Если значение `true`, возвращает результат запроса, иначе команду 
     *     выполнения SQL инструкций.
     * 
     * @return array|AbstractCommand
     */
    public static function getArticle(string|array $options, bool $result = true): array|AbstractCommand
    {
        return static::getElement('{{article}}', $options, $result);
    }

    /**
     * Возвращает статьи сайта.
     * 
     * @param string|array $options Параметры запроса.
     * @param bool $result Если значение `true`, возвращает результат запроса, иначе команду 
     *     выполнения SQL инструкций.
     * 
     * @return array|AbstractCommand
     */
    public static function getArticles(string|array $options = [], bool $result = true): array|AbstractCommand
    {
        return static::getElements('{{article}}', $options, $result);
    }

    /**
     * Возвращает категорию статьи сайта.
     * 
     * @param string|array $options Параметры запроса.
     * @param bool $result Если значение `true`, возвращает результат запроса, иначе команду 
     *     выполнения SQL инструкций.
     * 
     * @return array|AbstractCommand
     */
    public static function getCategory(string|array $options, bool $result = true): array|AbstractCommand
    {
        return static::getElement('{{article_category}}', $options, $result);
    }

    /**
     * Возвращает категории статей сайта.
     * 
     * @param string|array $options Параметры запроса.
     * @param bool $result Если значение `true`, возвращает результат запроса, иначе команду 
     *     выполнения SQL инструкций.
     * 
     * @return array|AbstractCommand
     */
    public static function getCategories(string|array $options = [], bool $result = true): array|AbstractCommand
    {
        return static::getElements('{{article_category}}', $options, $result);
    }

    /**
     * Возвращает статьи указанной категории сайта.
     * 
     * @param int $categoryId Идентификатор категории.
     * @param string|array $options Параметры запроса.
     * @param bool $result Если значение `true`, возвращает результат запроса, иначе команду 
     *     выполнения SQL инструкций.
     * 
     * @return array|AbstractCommand
     */
    public static function getCategoryArticles(int $categoryId, array $options = [], bool $result = true): array|AbstractCommand
    {
        if (is_string($options)) {
            $options = static::optionsToArray($options);
        }
        $options['if'] = $options['if'] ?? [];
        $options['if']['category_id'] = $categoryId;
        return static::getElements('{{article}}', $options, $result);
    }

    /**
     * Возвращает массив параметров запроса из строки.
     * 
     * @param string $options Параметры запроса.
     * 
     * @return array
     */
    public static function optionsToArray(string $options): array
    {
        parse_str($options, $query);
        $options = $query;
        unset($query['columns'], $query['count'],  $query['offset'], $query['order']);
        $options['if'] = $query;
        return $options;
    }

    /**
     * Подготавливает значения параметров запроса.
     * 
     * @param array $options Парамктры запроса.
     * 
     * @return array
     */
    public static function prepareOptions(array $options): array 
    {
        return [
            'columns' => $options['columns'] ?? ['*'],
            'if'      => $options['if'] ?? null,
            'count'   => $options['count'] ?? null,
            'offset'  => $options['offset'] ?? null,
            'order'   => $options['order'] ?? null
        ];
    }
}