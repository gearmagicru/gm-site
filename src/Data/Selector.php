<?php
/**
 * Этот файл является частью пакета GM Site.
 * 
 * @link https://gearmagic.ru/framework/
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Site\Data;

use Gm;
use PDO;
use Gm\Db\Sql\Select;
use Gm\Db\Sql\Expression;
use Gm\Data\Model\BaseModel;

/**
 * Класс селектора, выборка данных страницы сайта.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Site\Data
 * @since 1.0
 */
class Selector extends BaseModel
{
    /**
     * Имя таблицы материала.
     *
     * @var string
     */
    public string $articleTable = '{{article}}';

    /**
     * Имя таблицы категории материала.
     *
     * @var string
     */
    public string $categoryTable = '{{article_category}}';

    /**
     * Подсчитывает количество материала по указанному условию.
     * 
     * Если параметр `$group` не указан, то возвратит количество, иначе массив пар.
     * Например:
     * ```php
     * [
     *     ['field' => 'id', 'count' => 1],
     *     // ...
     * ]
     * ```
     * Если указан ключ `$fetchKey`:
     * ```php
     * [
     *     'key' => ['field' => 'id', 'count' => 1],
     *     // ...
     * ]
     * ```
     * 
     * @param array|null $where Условие выполнения SQL-запроса (по умолчанию `null`).
     * @param string|array $group Поле по которому выполняется группировка. 
     *     Если значение `null`, группировка не будет выполняться (по умолчанию `null`).
     * @param string|null $fetchKey Ключ возвращаемого ассоциативного массива. Если `null`, 
     *     результатом будет индексированный массив записей (по умолчачнию `null`).
     *
     * @return int|array
     */
    public function articlesCount(array $where = null, ?string $group = null, ?string $fetchKey = null): int|array
    {
        if ($group)
            $columns = [
                $group  => $group,
                'count' => new Expression('COUNT(' . $group . ')')
            ];
        else
            $columns = ['count' => new Expression('COUNT(id)')];

        $select = new Select($this->articleTable);
        $select->columns($columns);

        if ($where) {
            $select->where($where);
        }
        if ($group) {
            $select->group($group);
        }

        /** @var \Gm\Db\Adapter\Driver\AbstractCommand $command */
        $command = $this
            ->getDb()
                ->createCommand($select);
        if ($group)
            return $command->queryAll($fetchKey);
        else {
            $row = $command->queryOne();
            return $row ? $row['count'] : 0;
        }
    }

    /**
     * Подсчитывает количество материала по указанному полю.
     * 
     * @param string $field Имя поля.
     * @param mixed $value Значение поля.
     *
     * @return int
     */
    public function articlesCountBy(string $field, mixed $value): int
    {
        return $this->articlesCount([$field => $value]);
    }

    /**
     * Возвращает материал по условию.
     * 
     * @param array $where Условие выполнения SQL-запроса.
     * @param array|null $columns Столбцы выборки материала. Если вы не укажете столбцы 
     *     для выборки, то по умолчанию будет установлено значение `['*']` или `null` (означающее "все столбцы"). 
     * @param int $fetchMode Вид получаемого выборкой материала {@see \Gm\Db\Adapter\Driver\AbstractCommand::$fetchMode} 
     *     (по умолчанию {@see PDO::FETCH_ASSOC}).
     *
     * @return mixed
     */
    public function article(array $where, ?array $columns = null, int $fetchMode = PDO::FETCH_ASSOC): mixed
    {
        $select = new Select($this->articleTable);
        $select->columns($columns ?: ['*']);

        if ($where) {
            $select->where($where);
        }

        /** @var \Gm\Db\Adapter\Driver\AbstractCommand $command */
        $command = $this
            ->getDb()
                ->createCommand($select);
        $command->fetchMode = $fetchMode;
        return $command->queryOne();
    }

    /**
     * Возвращает материалы по условию.
     * 
     * @param array $where Условие выполнения SQL-запроса.
     * @param array|null $columns Столбцы выборки материала. Если вы не укажете столбцы 
     *     для выборки, то по умолчанию будет установлено значение `['*']` или `null` (означающее "все столбцы"). 
     * @param array|null $order Порядок сортировки. Если значение `null` сортировка 
     *     применятся не будет (по умолчанию `null`). Например: `['field' => 'ASC', ...]`.
     * @param array|int|null $limit Количество материала в списке. Если значение имеет тип:
     *     - `int`, количество материала;
     *     - `array`, количество материала и смещение относительно начала списка;
     *     - `null`, ограничения не будут применятся.
     * 
     * @param int $fetchMode Вид получаемого выборкой материала {@see \Gm\Db\Adapter\Driver\AbstractCommand::$fetchMode} 
     *     (по умолчанию {@see PDO::FETCH_ASSOC}).
     *
     * @return array
     */
    public function articles(
        array $where, 
        ?array $columns = null, 
        ?array $order = null, 
        array|int|null $limit = null, 
        int $fetchMode = PDO::FETCH_ASSOC
    ): array
    {
        $select = new Select($this->articleTable);
        $select->columns($columns ?: ['*']);

        if ($where) {
            $select->where($where);
        }

        if ($order) {
            $select->order($order);
        }

        if ($limit) {
            if (is_array($limit)) {
                $select->limit($limit[0]);
                $select->offset($limit[1]);
            } else
                $select->limit($limit);
        }

        $select->order(['id' => 'DESC']);

        /** @var \Gm\Db\Adapter\Driver\AbstractCommand $command */
        $command = $this
            ->getDb()
                ->createCommand($select);
        $command->fetchMode = $fetchMode;
        return $command->queryAll();
    }

    /**
     * Возвращает материал по указаному идентификатору.
     * 
     * @param int $id Идентификатор материала.
     * @param array|null $columns Столбцы выборки материала. Если вы не укажете столбцы 
     *     для выборки, то по умолчанию будет установлено значение `['*']` или `null` (означающее "все столбцы"). 
     * @param int $fetchMode Вид получаемого выборкой материала {@see \Gm\Db\Adapter\Driver\AbstractCommand::$fetchMode} 
     *     (по умолчанию {@see PDO::FETCH_ASSOC}).
     *
     * @return mixed
     */
    public function articleById(int $id, ?array $columns = null, int $fetchMode = PDO::FETCH_ASSOC): mixed
    {
        return $this->article(['id' => $id], $columns, $fetchMode);
    }

    /**
     * Возвращает материал по указаному ярлыку.
     * 
     * @param string $slug Ярлык материала.
     * @param array|null $columns Столбцы выборки материала. Если вы не укажете столбцы 
     *     для выборки, то по умолчанию будет установлено значение `['*']` или `null` (означающее "все столбцы"). 
     * @param int $fetchMode Вид получаемого выборкой материала {@see \Gm\Db\Adapter\Driver\AbstractCommand::$fetchMode} 
     *     (по умолчанию {@see PDO::FETCH_ASSOC}).
     *
     * @return mixed
     */
    public function articleBySlug(string $slug, ?array $columns = null, int $fetchMode = PDO::FETCH_ASSOC): mixed
    {
        return $this->article(['slug' => $slug], $columns, $fetchMode);
    }

    /**
     * Возвращает основной материал для указанной категории.
     * 
     * Основной материал применяется для главной страницы категории.
     * 
     * @param int $categoryId Идентификатор категории материала.
     * @param array $columns Столбцы выборки материала. Если вы не укажете столбцы 
     *     для выборки, то по умолчанию будет установлено значение `['*']` или `null` (означающее "все столбцы"). 
     * @param int $fetchMode Вид получаемого выборкой материала {@see \Gm\Db\Adapter\Driver\AbstractCommand::$fetchMode} 
     *     (по умолчанию {@see PDO::FETCH_ASSOC}).
     *
     * @return mixed
     */
    public function articleCategory(int $categoryId, ?array $columns = null, int $fetchMode = PDO::FETCH_ASSOC): mixed
    {
        return $this->article(
            [
                'category_id' => $categoryId,
                'slug_type'   => Model\Article::SLUG_HOME
            ], 
            $columns, 
            $fetchMode
        );
    }

    /**
     * Возвращает материалы в указанной категории.
     * 
     * @param int|array $categoryId Идентификатор категори(й) материала.
     * @param array $columns Столбцы выборки материала. Если вы не укажете столбцы 
     *     для выборки, то по умолчанию будет установлено значение `['*']` или `null` (означающее "все столбцы"). 
     * @param array|null $order Порядок сортировки. Если значение `null` сортировка 
     *     применятся не будет (по умолчанию `null`). Например: `['field' => 'ASC', ...]`.
     * @param array|int|null $limit Количество материала в списке. Если значение имеет тип:
     *     - `int`, количество материала;
     *     - `array`, количество материала и смещение относительно начала списка;
     *     - `null`, ограничения не будут применятся.
     * @param int $fetchMode Вид получаемого выборкой материала {@see \Gm\Db\Adapter\Driver\AbstractCommand::$fetchMode} 
     *     (по умолчанию {@see PDO::FETCH_ASSOC}).
     *
     * @return array
     */
    public function articlesInCategory(
        int|array $categoryId, 
        ?array $columns = null, 
        ?array $order = null, 
        array|int|null $limit = null, 
        int $fetchMode = PDO::FETCH_ASSOC
    ): array
    {
        return $this->articles(['category_id' => $categoryId], $columns, $order, $limit, $fetchMode);
    }

    /**
     * Возвращает все основные материалы для указанных категорий.
     * 
     * @param int|array $categoryId Идентификатор категории(й) материала. Если 
     *     значение `null` категория учитываться не будет.
     * @param array $columns Столбцы выборки материала. Если вы не укажете столбцы 
     *     для выборки, то по умолчанию будет установлено значение `['*']` или `null` (означающее "все столбцы"). 
     * @param array|null $order Порядок сортировки. Если значение `null` сортировка 
     *     применятся не будет (по умолчанию `null`). Например: `['field' => 'ASC', ...]`.
     * @param array|int|null $limit Количество материала в списке. Если значение имеет тип:
     *     - `int`, количество материала;
     *     - `array`, количество материала и смещение относительно начала списка;
     *     - `null`, ограничения не будут применятся.
     * @param int $fetchMode Вид получаемого выборкой материала {@see \Gm\Db\Adapter\Driver\AbstractCommand::$fetchMode} 
     *     (по умолчанию {@see PDO::FETCH_ASSOC}).
     *
     * @return array
     */
    public function articlesCategory(
        int|array $categoryId, 
        ?array $columns = null, 
        ?array $order = null, 
        array|int|null $limit = null, 
        int $fetchMode = PDO::FETCH_ASSOC
    ): array
    {
        $where = ['slug_type' => Model\Article::SLUG_HOME];
        if ($categoryId)
            $where['category_id'] = $categoryId;
        else
            $where[] = 'category_id IS NOT NULL';
        return $this->articles($where, $columns, $order, $limit, $fetchMode);
    }

    /**
     * Возвращает материалы по указанному типу.
     * 
     * @param int|array $typeId Идентификатор типа(ов) материала.
     * @param array $columns Столбцы выборки материала. Если вы не укажете столбцы 
     *     для выборки, то по умолчанию будет установлено значение `['*']` или `null` (означающее "все столбцы"). 
     * @param array|null $order Порядок сортировки. Если значение `null` сортировка 
     *     применятся не будет (по умолчанию `null`). Например: `['field' => 'ASC', ...]`.
     * @param array|int|null $limit Количество материала в списке. Если значение имеет тип:
     *     - `int`, количество материала;
     *     - `array`, количество материала и смещение относительно начала списка;
     *     - `null`, ограничения не будут применятся.
     * @param int $fetchMode Вид получаемого выборкой материала {@see \Gm\Db\Adapter\Driver\AbstractCommand::$fetchMode} 
     *     (по умолчанию {@see PDO::FETCH_ASSOC}).
     *
     * @return array
     */
    public function articlesByType(
        int|array $typeId, 
        ?array $columns = null, 
        ?array $order = null, 
        array|int|null $limit = null, 
        int $fetchMode = PDO::FETCH_ASSOC
    ): array
    {
        return $this->articles(['type_id' => $typeId], $columns, $order, $limit, $fetchMode);
    }

    /**
     * Подсчитывает количество категорий материалов по указанному условию.
     * 
     * Если параметр `$group` не указан, то возвратит количество, иначе массив пар.
     * Например:
     * ```php
     * [
     *     ['field' => 'id', 'count' => 1],
     *     // ...
     * ]
     * ```
     * 
     * @param array|null $where Условие выполнения SQL-запроса (по умолчанию `null`).
     * @param string|null $group Поле по которому выполняется группировка. 
     *     Если значение `null`, группировка не будет выполняться (по умолчанию `null`).
     *
     * @return int|array
     */
    public function categoriesCount(?array $where = null, string $group = null): int|array
    {
        if ($group)
            $columns = [
                $group  => $group,
                'count' => new Expression('COUNT(' . $group . ')')
            ];
        else
            $columns = ['count' => new Expression('COUNT(id)')];

        $select = new Select($this->categoryTable);
        $select->columns($columns);

        if ($where) {
            $select->where($where);
        }
        if ($group) {
            $select->group($group);
        }

        /** @var \Gm\Db\Adapter\Driver\AbstractCommand $command */
        $command = $this
            ->getDb()
                ->createCommand($select);
        if ($group)
            return $command->queryAll();
        else {
            $row = $command->queryOne();
            return $row ? $row['count'] : 0;
        }
    }

    /**
     * Возвращает категорию материала по условию.
     * 
     * @param array|null $where Условие выполнения SQL-запроса.
     * @param array|null $columns Столбцы выборки материала. Если вы не укажете столбцы 
     *     для выборки, то по умолчанию будет установлено значение `['*']` или `null` (означающее "все столбцы"). 
     * @param int $fetchMode Вид получаемого выборкой материала {@see \Gm\Db\Adapter\Driver\AbstractCommand::$fetchMode} 
     *     (по умолчанию {@see PDO::FETCH_ASSOC}).
     *
     * @return mixed
     */
    public function category(?array $where, ?array $columns = null, int $fetchMode = PDO::FETCH_ASSOC): mixed
    {
        $select = new Select($this->categoryTable);
        $select->columns($columns ?: ['*']);

        if ($where) {
            $select->where($where);
        }

        /** @var \Gm\Db\Adapter\Driver\AbstractCommand $command */
        $command = $this
            ->getDb()
                ->createCommand($select);
        $command->fetchMode = $fetchMode;
        return $command->queryOne();
    }

    /**
     * Возвращает категорию материала по указаному идентификатору.
     * 
     * @param int $id Идентификатор категории материала.
     * @param array $columns Столбцы выборки материала. Если вы не укажете столбцы 
     *     для выборки, то по умолчанию будет установлено значение `['*']` или `null` (означающее "все столбцы"). 
     * @param int $fetchMode Вид получаемого выборкой материала {@see \Gm\Db\Adapter\Driver\AbstractCommand::$fetchMode} 
     *     (по умолчанию {@see PDO::FETCH_ASSOC}).
     *
     * @return mixed
     */
    public function categoryById(int $id, array $columns = null, int $fetchMode = PDO::FETCH_ASSOC): mixed
    {
        return $this->category(['id' => $id], $columns, $fetchMode);
    }

    /**
     * Возвращает категорию материала по указаному ярлыку.
     * 
     * @param string $slug Ярлык категории материала.
     * @param array $columns Столбцы выборки материала. Если вы не укажете столбцы 
     *     для выборки, то по умолчанию будет установлено значение `['*']` или `null` (означающее "все столбцы"). 
     * @param int $fetchMode Вид получаемого выборкой материала {@see \Gm\Db\Adapter\Driver\AbstractCommand::$fetchMode} 
     *     (по умолчанию {@see PDO::FETCH_ASSOC}).
     *
     * @return mixed
     */
    public function categoryBySlug(string $slug, ?array $columns = null, int $fetchMode = PDO::FETCH_ASSOC): mixed
    {
        return $this->category(['slug' => $slug], $columns, $fetchMode);
    }

    /**
     * Возвращает категорию материала по указаному пути.
     * 
     * @param string $slugPath Путь категории материала.
     * @param array $columns Столбцы выборки материала. Если вы не укажете столбцы 
     *     для выборки, то по умолчанию будет установлено значение `['*']` или `null` (означающее "все столбцы"). 
     * @param int $fetchMode Вид получаемого выборкой материала {@see \Gm\Db\Adapter\Driver\AbstractCommand::$fetchMode} 
     *     (по умолчанию {@see PDO::FETCH_ASSOC}).
     *
     * @return mixed
     */
    public function categoryBySlugPath(string $slugPath, ?array $columns = null, int $fetchMode = PDO::FETCH_ASSOC): mixed
    {
        return $this->category(['slug_path' => $slugPath], $columns, $fetchMode);
    }

    /**
     * Возвращает категории материалы по условию.
     * 
     * @param null|array $where Условие выполнения SQL-запроса.
     * @param array $columns Столбцы выборки материала. Если вы не укажете столбцы 
     *     для выборки, то по умолчанию будет установлено значение `['*']` или `null` (означающее "все столбцы"). 
     * @param null|array $order Порядок сортировки. Если значение `null` сортировка 
     *     применятся не будет (по умолчанию `null`). Например: `['field' => 'ASC', ...]`.
     * @param array|int|null $limit Количество материала в списке. Если значение имеет тип:
     *     - `int`, количество материала;
     *     - `array`, количество материала и смещение относительно начала списка;
     *     - `null`, ограничения не будут применятся.
     * 
     * @param int $fetchMode Вид получаемого выборкой материала {@see \Gm\Db\Adapter\Driver\AbstractCommand::$fetchMode} 
     *     (по умолчанию {@see PDO::FETCH_ASSOC}).
     *
     * @return array
     */
    public function categories(
        array $where, 
        ?array $columns = null, 
        ?array $order = null, 
        array|int|null $limit = null, 
        int $fetchMode = PDO::FETCH_ASSOC
    ): array
    {
        $select = new Select($this->categoryTable);
        $select->columns($columns ?: ['*']);

        if ($where) {
            $select->where($where);
        }

        if ($order) {
            $select->order($order);
        }

        if ($limit) {
            if (is_array($limit)) {
                $select->limit($limit[0]);
                $select->offset($limit[1]);
            } else
                $select->limit($limit);
        }

        $select->order(['id' => 'DESC']);

        /** @var \Gm\Db\Adapter\Driver\AbstractCommand $command */
        $command = $this
            ->getDb()
                ->createCommand($select);
        $command->fetchMode = $fetchMode;
        return $command->queryAll();
    }

    /**
     * Возвращает количество материала в категориях.
     * 
     * @param null|array $where Условие выполнения SQL-запроса.
     * @param array $columns Столбцы выборки категории материала. Если вы не укажете столбцы 
     *     для выборки, то по умолчанию будет установлено значение `['*']` или `null` (означающее "все столбцы"). 
     * @param null|array $order Порядок сортировки. Если значение `null` сортировка 
     *     применятся не будет (по умолчанию `null`). Например: `['field' => 'ASC', ...]`.
     * @param array|int|null $limit Количество категорий материала в списке. Если значение имеет тип:
     *     - `int`, количество материала;
     *     - `array`, количество материала и смещение относительно начала списка;
     *     - `null`, ограничения не будут применятся.
     * @param int $fetchMode Вид получаемого выборкой материала: `PDO::FETCH_ASSOC`, 
     *     `PDO::FETCH_BOTH`, `PDO::FETCH_OBJ` {@see \Gm\Db\Adapter\Driver\AbstractCommand::$fetchMode} 
     *     (по умолчанию {@see PDO::FETCH_ASSOC}).
     *
     * @return array
     */
    public function countArticlesInCategory(
        array $where = [], 
        ?array $columns = null, 
        ?array $order = null, 
        array|int|null $limit = null, 
        int $fetchMode = PDO::FETCH_ASSOC
    ): array
    {
        $columns = ['count' => 'index', '*'];
        $select = new Select($this->categoryTable);
        $select->columns($columns ?: ['*']);

        if ($where) {
            $select->where($where);
        }

        if ($order) {
            $select->order($order);
        }

        if ($limit) {
            if (is_array($limit)) {
                $select->limit($limit[0]);
                $select->offset($limit[1]);
            } else
                $select->limit($limit);
        }

        /** @var \Gm\Db\Adapter\Driver\AbstractCommand $command */
        $command = $this
            ->getDb()
                ->createCommand($select);
        $command->fetchMode = $fetchMode;
        $rows = $command->queryAll();

        if ($rows) {
            $result = [];
            /**
             * @var array $counts `['id' => ['category_id' => 'id', 'count' => 1], ...]`
             */
            $counts = $this->articlesCount(['category_id' => 1], 'category_id', 'category_id');
            foreach ($rows as $row) {
                switch ($fetchMode) {
                    case PDO::FETCH_ASSOC: 
                        $row['count'] = $counts[$row['id']]['count'] ?? 0;
                        break;

                    case PDO::FETCH_BOTH: 
                        $row->count = $counts[$row->id]['count'] ?? 0;
                        break;

                    case PDO::FETCH_OBJ: 
                        $row->count = $counts[$row->id]['count'] ?? 0;
                        break;
                }
                $result[] = $row;
            }
            return $result;
        }
        return $rows;
    }
}
