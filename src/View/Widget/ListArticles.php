<?php
/**
 * Виджет веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Site\View\Widget;

use Gm;
use Gm\Helper\Str;
use Gm\Helper\Url;
use Gm\Db\Sql\Select;
use Gm\Db\Sql\Expression;
use Gm\View\Widget\ListView;
use Gm\Site\Data\Model\Article;

/**
 * Виджет ListArticles (список материалов) предназначен для отображения материалов в 
 * виде списка.
 * 
 * Список включает в себя сортировку, фильтрацию и разбивку на страницы.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Site\View\Widget
 * @since 1.0
 */
class ListArticles extends ListView
{
    /**
     * @var string Режим "items" отключает запросы к виджету (сортировка, лимиты, пагинация).
     */
    public const MODE_ITEMS = 'items';

    /**
     * @var string Режим "list" включате только запрос пагинациия виджета.
     */
    public const MODE_LIST = 'list';

    /**
     * @var string Режим "full" включает запросы к виджету (сортировка, лимиты, пагинация).
     */
    public const MODE_FULL = 'full';

    /**
     * Уникальный идентификатор поставщика данных.
     * 
     * Применяется для {@see \Gm\Data\Provider\BaseProvider::$id}.
     * 
     * @var string
     */
    public string $providerId = '';

    /**
     * Язык материала.
     * 
     * Используется фильтром материалов в SQL-запросе.
     * Может иметь значения:
     * - `null`, все материалы у которых не установлен язык;
     * - `string`, тег языка, которые имеют страницы, например: 'ru-RU', 'en-GB' и т.д.;
     * - `true`, текущий язык сайта, которые имеют страницы;
     * - `false`, фильтром не использует язык.
     * 
     * @var bool|string|null
     */
    public bool|string|null $language = false;

    /**
     * Режим работы виджета.
     * 
     * @var string
     */
    public string $mode = self::MODE_FULL;

    /**
     * Имена столбцов (полей) таблицы материала в виде массива пар "псевдоним - имя поля".
     * 
     * @see Widget::getDataQuery()
     * 
     * @var array<string, string>
     */
    public array $columns = [
        'id'       => 'id',
        'title'    => 'title',
        'header'   => 'header',
        'image'    => 'image',
        'announce' => 'announce',
        'slug'     => 'slug',
        'slugType' => 'slug_type',
        'date'     => 'publish_date',
        'hits'     => 'hits',
        'language' => 'language_id',
        'fields'   => 'fields'
    ];

    /**
     * Параметры конфигурации пагинации элементов данных.
     * 
     * Устанавливает поставщику данных {@see Widget::$dataProvider} пагинацию. Если 
     * параметры конфигурации не указаны, то будет использовать параметры по умолчанию
     * {@see Widget::$defaultPagination}.
     * 
     * @see Widget:initDataProvider()
     *
     * @var array<string, mixed>
     */
    public array $pagination = [];

    /**
     * Параметры конфигурации пагинации элементов данных по умолчанию.
     * 
     * @see Widget:initDataProvider()
     *
     * @var array<string, mixed>
     */
    public array $defaultPagination = [
        'pageParam'    => 'page',
        'limitParam'   => 'limit',
        'defaultLimit' => 15,
        'limitFilter'  => [15, 30, 50, 100],
        'maxLimit'     => 100
    ];

    /**
     * Параметры конфигурации сортировщика элементов данных.
     * 
     * Устанавливает поставщику данных {@see Widget::$dataProvider} сортировщик. Если 
     * параметры конфигурации не указаны, то будет использовать параметры по умолчанию
     * {@see Widget::$defaultSort}.
     * 
     * @see Widget:initDataProvider()
     *
     * @var array<string, mixed>
     */
    public array $sort = [];

    /**
     * Параметры конфигурации сортировщика элементов данных по умолчанию.
     * 
     * @see Widget:initDataProvider()
     *
     * @var array<string, mixed>
     */
    public array $defaultSort = [
        'param'   => 'sort',
        'default' => 'date,a',
        'filter'  => [
            'date'   => 'publish_date',
            'header' => 'header'
        ]
    ];

    /**
     * Категория материала.
     * 
     * Используется фильтром материалов в SQL-запросе.
     * Может иметь значения:
     * - `null`, материалы без категории;
     * - `string`, если значние:
     * 1. 'all' - все материалы (с категорий и без неё);
     * 2. 'current' - текущая категория.
     * - `int`, идентификатор категории.
     * 
     * @var string|int|null
     */
    public string|int|null $category = null;

    /**
     * Исключить из списка текущий материал (статью).
     * 
     * Используется фильтром материалов в SQL-запросе.
     * 
     * Если на странице {@see \Gm\Site\Page} уже загружен материал {@see \Gm\Site\Page::$article}, 
     * то он будет исключён из списка.
     * 
     * @var bool
     */
    public bool $excludeCurrent = false;

    /**
     * {@inheritdoc}
     */
    public function initDataProvider(): void
    {
        /** @var array $provider Параметры провайдера */
        $provider = $this->dataProvider ?: [];

        if (is_array($provider))  {
            // идентификатор поставщика данных
            if ($this->providerId !== null) {
                $provider['id'] = $this->providerId;
            }

            // сортировка элементов
            if ($this->sort)
                $sort = array_merge($this->defaultSort, $this->sort);
            else
                $sort = $this->defaultSort;

            // пагинация элементов
            if ($this->pagination)
                $pagination = array_merge($this->defaultPagination, $this->pagination);
            else
                $pagination = $this->defaultPagination;

            // режим отключате параметры запроса
            if ($this->mode === self::MODE_ITEMS) {
                $sort['param'] = false;
                $pagination['pageParam'] = false;
                $pagination['limitParam'] = false;
            } else
            // режим только пагинации
            if ($this->mode === self::MODE_LIST) {
                $sort['param'] = false;
                $pagination['limitParam'] = false;
            }
    
            // общее количество элементов
            if ($pagination['pageParam'] !== false) {
                $pagination['totalCount'] = $this->getDataCount();
            }

            $this->dataProvider = array_merge($provider, [
                'class'        => '\Gm\Data\Provider\QueryProvider',
                'query'        => $this->getDataQuery(),
                'pagination'   => $pagination,
                'sort'         => $sort,
                'processItems' => [$this, 'processItems'],
            ]);
        }

        parent::initDataProvider();
    }

    /**
     * Предварительная обработка элементов перед их выводом.
     * 
     * @param array $items Элементы данных.
     * 
     * @return array
     */
    public function processItems(array $items): array
    {
        /** @var \Gm\Language\AvailableLanguage $available Установленные языки */
        $available = Gm::$app->language->available;
        /** @var int $languageCode Текущий код языка */
        $languageCode = Gm::$app->language->getDefault()['code'];

        $result = [];
        foreach ($items as $item) {
            // тип статьи
            $slugType = (int) ($item['slugType'] ?? 0);

            // URL-адрес статьи
            if ($slugType == Article::SLUG_DYNAMIC) {
                $slug = Str::idToStr($item['slug'], $item['id']);
            } else {
                $slug = $item['slug'];
            }
            $urlParams = [$item['slugPathCategory'], 'basename' => $slug];

            // если указан язык статьи и он не текущий
            if ($item['language'] && $item['language'] != $languageCode) {
                /** @var array|null Язык статьи */
                $language = $available->getBy($item['language'], 'code');
                if ($language)
                    $urlParams['langSlug'] = $language['slug'];
            }

            $item['url'] = Url::to($urlParams);
            $result[] = $item;
        }
        return $result;
    }

    /**
     * @see Widget::getDataFilter()
     * 
     * @var array
     */
    protected array $filter;

    /**
     * Возвращает условия выбора статей для SQL-оператора WHERE.
     * 
     * Применяется при получении общего количества записей и формирования SQL-запроса.
     * 
     * @see Widget::getDataCount()
     * @see Widget::getDataQuery()
     * 
     * @return array
     */
    public function getDataFilter(): array
    {
        if (isset($this->filter)) {
            return $this->filter;
        }

        // статья опубликована
        $filter =  ['article.publish' => 1];

        // если странице не установлен язык
        if ($this->language === null) {
            $filter[] = 'article.language_id IS NULL';
        } else
        // если установленн тег язык (ru-RU, en-GB...)
        if (is_string($this->language)) {
            $language = Gm::$app->language->available->getBy($this->language, 'tag');
            if ($language) {
                $filter['article.language_id'] = $language['code'];
            }
        } else
        // если текущий языка сайта
        if ($this->language === true) {
            $language = Gm::$app->language->getDefault();
            $filter['article.language_id'] = $language['code'];
        }

        // чтобы в списке не было главного материала категории
        $filter[] = 'article.slug_type<>' . Article::SLUG_HOME;

        // если указан категория
        if ($this->category) {
            if ($this->category === 'current') {
                if ($categoryId = Gm::$app->page->getCategoryId()) {
                    $filter['article.category_id'] = $categoryId;
                }
            } else
                $filter['article.category_id'] = $this->category;
        }

        // исключить материал страницы из списка
        if ($this->excludeCurrent) {
            if ($articleId = Gm::$app->page->getArticleId()) {
                $filter[] = 'article.id<>'. $articleId;
            }
        }

        return $this->filter = $filter;
    }

    /**
     * @var Select|null|null
     */
    protected ?Select $_query = null;

    /**
     * @return Select
     */
    protected function getQuery(): Select
    {
        if ($this->_query === null) {
            $this->_query = $this->prepareQuery();
        }
        return $this->_query;
    }

    /**
     * @return Select
     */
    protected function prepareQuery(): Select
    {
        return (new Select())
        ->from(['article' => '{{article}}'])
        ->where($this->getDataFilter())
        ->join(
            ['category' => '{{article_category}}'], 'article.category_id=category.id',
            ['slugPathCategory' => 'slug_path'],
            'left'
        );
    }

    /**
     * Возвращает общее количество записей.
     * 
     * @return int
     * 
     * @throws \Gm\Db\Adapter\Exception\CommandException Ошибка выполнения SQL-запроса
     */
    public function getDataCount(): int
    {
        /** @var Select $query */
        $query = $this->prepareQuery();
        $query->columns(['count' => new Expression('COUNT(*)')]);

        /** @var \Gm\Db\Adapter\Driver\AbstractCommand $command */
        $command = Gm::$app->db->createCommand($query);
        /** @var array|null $row */
        $row = $command->queryOne();
        return $row ? $row['count'] : 0;
    }

    /**
     * Возвращает объект запроса.
     * 
     * @return Select|\Gm\Db\ActiveRecord
     */
    public function getDataQuery()
    {
        /** @var Select $query */
        $query = $this->prepareQuery();
        $query->columns($this->columns);
        return $query;
    }
}