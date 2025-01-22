<?php
/**
 * Этот файл является частью пакета GM Site.
 * 
 * @link https://gearmagic.ru/framework/
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Site\Data\Model;

use Gm;
use URLify;
use Gm\Db\Sql;
use Gm\Db\Sql\Select;
use Gm\Helper\Str;
use Gm\Helper\Url;
use Gm\Helper\Json;
use Gm\Db\ActiveRecord;
use Gm\Data\DataManager as DM;
use Gm\Filesystem\Filesystem as Fs;

/**
 * Модель данных материала (статьи) сайта.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Site\Data\Model
 * @since 1.0
 */
class Article extends ActiveRecord
{
    /**
     * @var string const URL-адрес материала статический.
     */
    public const SLUG_STATIC  = 1;

    /**
     * @var string URL-адрес материала динамический (содержит идентификатор материала).
     */
    public const SLUG_DYNAMIC = 2;

    /**
     * @var string URL-адрес материала не указан (главный материал категории сайта).
     */
    public const SLUG_HOME = 3;

    /**
     * @var string URL-адрес материала не указан (главный материал сайта).
     */
    public const SLUG_SELF = 4;

    /**
     * Текст после текста материала.
     * 
     * @var string
     */
    public string $textAfter = '';

    /**
     * Текст до текста материала.
     * 
     * @var string
     */
    public string $textBefore = '';

    /**
     * {@inheritdoc}
     */
    public function tableName(): string
    {
        return '{{article}}';
    }

    /**
     * {@inheritdoc}
     */
    public function primaryKey(): string
    {
        return 'id';
    }

    /**
     * {@inheritdoc}
     */
    public function maskedAttributes(): array
    {
        return [
            'id'                  => 'id',
            'typeId'              => 'type_id', // идентификатор материала
            'categoryId'          => 'category_id', // идентификатор категории материала
            'languageId'          => 'language_id', // идентификатор языка
            'index'               => 'index', // порядковый номер
            'hits'                => 'hits', // количество посещений
            'fixed'               => 'fixed', // материал в списке зафиксирован
            'template'            => 'template', // файл шаблона материала
            'pageTemplate'        => 'page_template', // файл шаблона страницы
            'mediaPaths'          => 'media_paths', // пути к медиа ресурсам
            'hasShortcode'        => 'has_shortcode', // в тексте указан шорткод
            // опубликовать статью:
            'publish'             => 'publish', // на сайте
            'publishOnMain'       => 'publish_on_main', // на главной странице сайта
            'publishInCategories' => 'publish_in_categories', // в разделах сайта
            'publishDate'         => 'publish_date', // дата и время публикации материала
            'header'              => 'header', // заголовок материала
            'image'               => 'image', // картинка
            'text'                => 'text', // текст материала
            'textPlain'           => 'text_plain', // текст без форматирования
            'announce'            => 'announce', // анонс материала
            'announcePlain'       => 'announce_plain', // анонс материала без форматирования
            'slug'                => 'slug', // ярлык (слаг)
            'slugHash'            => 'slug_hash', // хэш URL-пути
            'slugType'            => 'slug_type', // вид ярлыка (слага)
            'inSearch'            => 'in_search', // участвует в поиске
            'inSitemap'           => 'in_sitemap', // на карте сайта
            // карта сайта (Sitemap) для поисковых систем:
            'sitemapEnabled'      => 'sitemap_enabled', // включает материал
            'sitemapPriority'     => 'sitemap_priority', // приоритетность
            'sitemapFrequency'    => 'sitemap_frequency', // частота изменения
            // фид (лента новостей, анонсов статей в формате RSS, RDF, Atom):
            'feedEnabled'         => 'feed_enabled', //
            // метатег материала (title, description, keywords):
            'title'               => 'title', // метатег заголовка
            'keywords'            => 'meta_keywords', // метатег ключевых слов
            'description'         => 'meta_description', // метатег описания
            // директивы индексирования и показа контента:
            'robots'    => 'meta_robots', // метатег robots
            'googleBot' => 'meta_googlebot', // метатег googlebot
            'yandexBot' => 'meta_yandex', // метатег yandex
            'metaTags'  => 'meta_tags', // все остальные метатеги (JSON формат)
            'caching'   => 'caching', // кэшировать
            'fields'    => 'fields', // поля (JSON формат)
            'tags'      => 'tags', // метки через разделитель ","
            // атрибуты аудита записи
            'createdDate' => DM::AR_CREATED_DATE, // дата добавления
            'createdUser' => DM::AR_CREATED_USER, // добавлено пользователем
            'updatedDate' => DM::AR_UPDATED_DATE, // дата изменения
            'updatedUser' => DM::AR_UPDATED_USER, // изменено пользователем
            'lock'        => DM::AR_LOCK // заблокировано
        ];
    }

    /**
     * @param array $articleColumns (по умолчанию `['*']`)
     * @param array $categoryColumns
     * @param string|array|null $where (по умолчанию `null`)
     * @param array|null $order (по умолчанию `null`)
     * @param int $limit (по умолчанию '0')
     * 
     * @return Select
     */
    public function selectJoinCategories(
        array $articleColumns = ['*'], 
        array $categoryColumns = ['category_slug_path' => 'slug_path'], 
        string|array|null $where = null, 
        ?array $order = null, 
        int $limit = 0
    ): Select
    {
        $select = new Select();
        $select
            ->from(['article' => $this->tableName()])
            ->columns($articleColumns, true)
            ->join(
                ['category' => '{{article_category}}'],
                'category.id = article.category_id',
                $categoryColumns,
                Sql\Select::JOIN_LEFT
            );
        if ($where) {
            $select->where($where);
        }
        if ($order) {
            $select->order($order);
        }
        return $select;
    }

    /**
     * Проверяет, является ли ярлык материала статическим.
     *
     * @return bool
     */
    public function isStaticSlug(): bool
    {
        return $this->slugType == self::SLUG_STATIC;
    }

    /**
     * Проверяет, является ли ярлык материала динамическим.
     *
     * @return bool
     */
    public function isDynamicSlug(): bool
    {
        return $this->slugType == self::SLUG_DYNAMIC;
    }

    /**
     * Проверяет, является ли ярлык материала главным в разделе сайта.
     *
     * @return bool
     */
    public function isHomeSlug(): bool
    {
        return $this->slugType == self::SLUG_HOME;
    }

    /**
     * Проверяет, является ли ярлык материала главным в разделе сайта.
     *
     * @return bool
     */
    public function isSelfSlug(): bool
    {
        return $this->slugType == self::SLUG_SELF;
    }

    /**
     * Проверяет, опубликован ли материал.
     *
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->publish == 1;
    }

    /**
     * Возвращает загаловок ("title") материала.
     * 
     * @return string
     */
    public function getTitle(): string
    {
        $title = $this->attributes['title'] ?? '';
        return $title ?: $this->header;
    }

    /**
     * Возвращает ярлык материала.
     * 
     * @return string|null
     */
    public function getSlug(): ?string
    {
        $slug = $this->attributes['slug'];
        if ($slug) {
            if ($this->isDynamicSlug()) {
                return Str::idToStr($slug, $this->id);
            }
        }
        return $slug;
    }

    /**
     * Возвращает значения атрибутов "metaTags" и "robots".
     * 
     * Если значение атрибута "metaTags" имеет вид массива, то значение будет 
     * применяться из `metaTags['robots']`.
     * 
     * @see Article::$metaTags
     * @see Article::$robots
     * 
     * @return array<int, string>
     */
    public function getAllRobots(): array
    {
        $robots = [];
        // может быть в формате JSON, setMetaTags преобразует в array
        if (is_array($this->metaTags)) {
            $robots = $this->metaTags['robots'] ?? [];
        }
        if ($this->robots) {
            $robots[] = $this->robots;
        }
        return $robots;
    }

    /**
     * Возвращает значение атрибута "robots".
     * 
     * Поле в базе данных "meta_robots", может принимать значения: 'all', 'none',
     * 'index,follow', 'index,nofollow', 'noindex,nofollow', 'noindex', 'nofollow'.
     * 
     * @return string
     */
    public function getRobots(): string
    {
        $robots = $this->attributes['robots'] ?? '';
        return $robots ?: '';
    }

    /**
     * Устанавливает значение атрибуту "category".
     * 
     * @param int|string $value Идентификатор категории.
     * 
     * @return void
     */
    public function setCategory(int|string $value): void
    {
        $value = (int) $value;
        $this->attributes['categoryId'] = $value === 0 ? null : $value;
    }

    /**
     * Устанавливает значение атрибуту "language".
     * 
     * @param string|int $value Идентификатор языка.
     * 
     * @return void
     */
    public function setLanguage(string|int $value): void
    {
        $value = (int) $value;
        $this->attributes['languageId'] = $value === 0 ? null : $value;
    }

    /**
     * Устанавливает значение атрибуту "fields".
     * 
     * @param string|array|null $value
     * 
     * @return void
     */
    public function setFields(string|array|null $value): void
    {
        if ($value) {
            $value = is_string($value) ? json_decode($value, true) : $value;
        }
        $this->attributes['fields'] = $value ?: [];
    }

    /**
     * Возращает значение для сохранения в поле "fields".
     * 
     * @return string
     */
    public function unFields(): string
    {
        return  json_encode((array) $this->fields);
    }

    /**
     * Устанавливает значение атрибуту "slugType".
     * 
     * @param string|int $value
     * 
     * @return void
     */
    public function setSlugType(string|int  $value): void
    {
        $this->attributes['slugType'] = (int) $value;
    }

    /**
     * Устанавливает значение атрибуту "slug".
     * 
     * @param string|null $value
     * 
     * @return void
     */
    public function setSlug(?string $value): void
    {
        if (empty($value)) {
            $value = $this->header;
        }

        // если вид ярлыка - материал на главной странице сайта или категории (раздела)
        if ($this->slugType == Article::SLUG_HOME)
            $slug = null;
        else
            $slug = $value ? URLify::filter($value, 255, '', true) : null;
        $this->attributes['slug'] = $slug;
        $this->attributes['slugHash'] = $slug ? md5($slug) : null;
    }

      /**
     * Устанавливает значение атрибутам: "announce", "announcePlain".
     * 
     * @param string|null $value
     * 
     * @return void
     */
    public function setAnnounce(?string $value): static
    {
        $this->attributes['announce'] = $value;
        $this->attributes['announcePlain'] = $value ? strip_tags($value) : null;
        return $this;
    }

    /**
     * Устанавливает значение атрибутам: "text", "textPlain".
     * 
     * @param string|null $value
     * 
     * @return void
     */
    public function setText(?string $value): static
    {
        $this->attributes['text'] = $value;
        $this->attributes['textPlain'] = $value ? strip_tags($value) : null;
        return $this;
    }

    /**
     * Устанавливает значение атрибуту "metaTags".
     * 
     * @param array|string|null $value Если значение `string`, то формат JSON.
     * 
     * @return void
     */
    public function setMetaTags(array|string|null $value): void
    {
        if ($value) {
            if (is_string($value)) {
                $value = Json::tryDecode($value);
            }
        } else
            $value = [];
        $this->attributes['metaTags'] = $value;
    }

    /**
     * Возращает значение для сохранения в поле "meta_tags".
     * 
     * @return string
     */
    public function unMetaTags(): string
    {
        return json_encode((array) $this->metaTags);
    }


    /**
     * Устанавливает значение атрибуту "metaRobots".
     * 
     * @param string|null $value
     * 
     * @return void
     */
    public function setMetaRobots(?string $value): void
    {
        $this->attributes['metaRobots'] = $value ?: null;
    }

    /**
     * Устанавливает значение атрибуту "metaYandex".
     * 
     * @param string|null $value
     * 
     * @return void
     */
    public function setMetaYandex(?string $value): void
    {
        $this->attributes['metaYandex'] = $value ?: null;
    }

    /**
     * Устанавливает значение атрибуту "metaGoogle".
     * 
     * @param string|null $value
     * 
     * @return void
     */
    public function setMetaGoogle(?string $value): void
    {
        $this->attributes['metaGoogle'] = $value ?: null;
    }

    /**
     * Возвращает материал по условию запроса.
     * 
     * @see Article::selectOne()
     * 
     * @param Select|Where|\Closure|string|array $condition Условие запроса.
     * 
     * @return ActiveRecord|null Активная запись при успешном запросе, иначе `null`.
     */
    public function getBy($condition): ?ActiveRecord
    {
        return $this->selectOne($condition);
    }

    /**
     * getMediaPath
     * 
     * @param string $alias
     * 
     * @return string|null
     */
    public function getMediaPath(string $alias): ?string
    {
        $paths = $this->mediaPathsToArray();
        return $paths ? ($paths[$alias] ?? null) : null;
    }

    /**
     * mediaPathsToArray
     * 
     * @return array|null
     */
    public function mediaPathsToArray(): ?array
    {
        if ($this->mediaPaths) {
            if (is_string($this->mediaPaths)) {
                $mediaPaths = Json::decode($this->mediaPaths);
                return Json::error() ? null : $mediaPaths;
            } else
                return $this->mediaPaths ?: null;
        }
        return null;
    }

    /**
     * getMediaPathTemplateParams
     * 
     * @return array
     */
    public function getMediaPathTemplateParams(): array
    {
        $params = ['id' => $this->id ?: $this->getNextId()];
        return $params;
    }

    /**
     * Возвращает материал по указанному URL-пути и категории.
     * 
     * @see Article::selectOne()
     * 
     * @param string $slug Ярлык материала.
     * @param null|int $categoryId Идентификатор категории (раздела) материала. Если значение '0', 
     *     не будет учитываться (по умолчанию '0').
     * 
     * @return ActiveRecord|null Активная запись при успешном запросе, иначе `null`.
     */
    public function getBySlug(string $slug, ?int $categoryId = 0): ?ActiveRecord
    {
        $condition = ['slug' => $slug];
        if ($categoryId !== 0) {
            $condition['category_id'] = $categoryId;
        }
        return $this->selectOne($condition);
    }

    /**
     * Возвращает главный материал указанной категории (раздела).
     * 
     * @see Article::selectOne()
     * 
     * @param int|null $categoryId Идентификатор категории (раздела) материала. Если 
     *     категория не указана, вернёт главный материал сайта.
     * 
     * @return ActiveRecord|null Активная запись при успешном запросе, иначе `null`.
     */
    public function getByCategorySlugHome(?int $categoryId): ?ActiveRecord
    {
        $select = $this->select();
        $select
            ->where([
                'slug_type'   => self::SLUG_HOME,
                'category_id' => $categoryId
            ]);
        return  $this->selectOne($select);
    }

    /**
     * Возвращает материал по указанному идентификатору и категории (раздела) материала.
     * 
     * @see Article::selectOne()
     * 
     * @param int $articleId Идентификатор материала.
     * @param null|int $categoryId Идентификатор категории (раздела) материала 
     *     (по умолчанию `null`).
     * 
     * @return ActiveRecord|null Активная запись при успешном запросе, иначе `null`.
     */
    public function getByCategory(int $articleId, ?int $categoryId = null): ?ActiveRecord
    {
        return 
            $this->selectOne([
                'id'          => $articleId,
                'category_id' => $categoryId
            ]);
    }

    /**
     * Возвращает материал по его идентификатору.
     * 
     * @see Article::selectByPk()
     * 
     * @param int $articleId Идентификатор материала.
     * 
     * @return ActiveRecord|null Активная запись при успешном запросе, иначе `null`.
     */
    public function getById(int $articleId): ?ActiveRecord
    {
        return $this->selectByPk($articleId);
    }

    /**
     * Возвращает главный материал сайта.
     * 
     * @see Article::selectOne()
     *
     * @return ActiveRecord|null Активная запись при успешном запросе, иначе `null`.
     */
    public function getBySlugHome(): ?ActiveRecord
    {
        return $this->selectOne([
            'slug_type'   => self::SLUG_HOME,
            'category_id' => null
        ]);
    }

    /**
     * Возвращает материал по указанному имени файла в URL-адресе и категории.
     * 
     * @see Article::getById()
     * @see Article::getBySlug()
     * 
     * @param string $filename Имя файла в URL-адресе (включая URL-путь).
     * @param string|null $suffix Суффикс (расширение файла, например '.html').
     * @param int|null $categoryId Идентификатор категории материала (по умолчанию 0).
     * 
     * @return ActiveRecord|null Активная запись при успешном запросе, иначе `null`.
     */
    public function getByFilename(string $filename, ?string $suffix = null, ?int $categoryId = 0): ?ActiveRecord
    {
        $id = Str::idFromStr($filename);
        if ($id !== false)
            return $this->getById($id);
        else
            if ($suffix === null)
                return $this->getBySlug($filename, $categoryId);
            else
                return $this->getBySlug(basename($filename, $suffix), $categoryId);
    }

    /**
     * Возвращает первый метериал в указанной категории.
     * 
     * @param int|null $categoryId Идентификатор категории материала (по умолчанию `null`).
     * 
     * @return ActiveRecord|null
     */
    public function getFirst(?int $categoryId = null): ?ActiveRecord
    {
        $where = [];
        if ($categoryId) {
            // не главная страница категории метериала
            $where[] = '`slug_type`<>' . self::SLUG_HOME;
            $where['category_id'] = $categoryId;
        }
        return $this->selectOne($this->select(['*'], $where, ['publish_date' => 'ASC']));
    }

    /**
     * Возвращает последний метериал в указанной категории.
     * 
     * @param int|null $categoryId Идентификатор категории материала (по умолчанию `null`).
     * 
     * @return ActiveRecord|null
     */
    public function getLast(?int $categoryId = null): ?ActiveRecord
    {
        $where = [];
        if ($categoryId) {
            // не главная страница категории метериала
            $where[] = '`slug_type`<>' . self::SLUG_HOME;
            $where['category_id'] = $categoryId;
        }
        return $this->selectOne($this->select(['*'], $where, ['publish_date' => 'DESC']));
    }

    /**
     * @see Article::getUrl()
     * 
     * @var ArticleCategory
     */
    private ArticleCategory $_category;

    /**
     * Возвращает URL-адрес материала.
     * 
     * @param array<string, mixed> $params Параметры (компоненты) {@see \Gm\Url\UrlManager::buildUrl()} 
     * используются при создании URL-адреса.
     * 
     * @return string
     */
    public function getUrl(array $params = []): string
    {
        $params[0] = '';
        $params['basename'] = $this->slug;

        if ($this->categoryId) {
            if (!isset($this->_category)) {
                $this->_category = new ArticleCategory();
            }

            /** @var ArticleCategory|null $category */
            $category = $this->_category->getById($this->categoryId);
            if ($category) {
                $params[0] = $category->slugPath;
            }
        }
    
        if ($this->languageId) {
            /** @var null|string $languageSlug */
            $languageSlug = Gm::$app->language->available->getBy($this->languageId, 'slug');
            if ($languageSlug) {
                $params['langSlug'] = $languageSlug;
            }
        }
        return Url::to($params);
    }


    public static function makeUrl(
        int $articleId,
        int $slugType = 1, 
        ?string $slug = null,
        ?string $categorySlug = null,
        string|int|null $language = null
    ): string
    {
        if ($slugType === Article::SLUG_DYNAMIC && $slug) {
            $slug = Str::idToStr($slug, $articleId);
        }

        if ($language) {
            if (is_int($language)) {
                /** @var null|string $language */
                $language = Gm::$app->language->getSlugByCode($language);
            }
        }
        return Url::to([$categorySlug, 'basename' => $slug, 'langSlug' => $language]);
    }

    /**
     * Возвращает элементы навигационной цепочки относительно текущего материала.
     *
     * @return array
     */
    public function getBreadcrumbs(): array
    {
        if (empty($this->attributes)) {
            return [];
        }

        $links = [];
        // если у материала есть категория
        if ($this->categoryId) {
            /** @var \Gm\Site\Data\Model\ArticleCategory|null $category Категория из запроса */
            $category = Gm::$app->page->getCategory();

            // если нет категории в запросе или она не соответствует материалу
            if (empty($category) || $category->id != $this->categoryId) {
                /** @var \Gm\Site\Data\Model\ArticleCategory $category */
                $category = Gm::$app->page->createCategory();
                /** @var \Gm\Site\Data\Model\ArticleCategory|null $category */
                $category = $category->getById($this->categoryId);
            }

            // если материал имеет категорию
            if ($category) {
                $links[] = [
                    'label' => $category->name,
                    'url'   => $category->slugPath
                ];
                // простое определение существования родителя категории
                if ($category->hasNodeParent()) {
                    $parents = $category->getParents();
                    if ($parents) {
                        foreach ($parents as $parent) {
                            array_unshift($links, [
                                'label' => $parent['name'],
                                'url'   => $parent['slug_path']
                            ]);
                        }
                    }
                }
            }
        }

        // если материал является главным для категории
        if ($this->isHomeSlug()) {
            array_pop($links);
        }
        $links[] = ['label' => $this->header];
        return $this->breadcrumbs = $links;
    }

    /**
     * Возвращает значение поля материала.
     * 
     * @param string $name Имя поля.
     * @param mixed $default Значение по умолчанию.
     * 
     * @return mixed
     */
    public function getField(string $name, mixed $default = null): mixed
    {
        static $fields;

        if ($fields === null) {
            $fields = $this->fields;
        }
        return $fields[$name] ?? $default;
    }

    /**
     * Добавить текст до текста материала.
     * 
     * @see Article::$textBefore
     * 
     * @param string $text Добавляемый текст.
     * 
     * @return $this
     */
    public function addTextBefore(string $text): static
    {
        $this->textBefore = $this->textBefore ?: '';
        $this->textBefore .= $text;
        return $this;
    }

    /**
     * Добавить текст после текста материала.
     * 
     * @see Article::$textAfter
     * 
     * @param string $text Добавляемый текст.
     * 
     * @return $this
     */
    public function addTextAfter(string $text): static
    {
        $this->textAfter = $this->textAfter ?: '';
        $this->textAfter .= $text;
        return $this;
    }

    /**
     * Удаляет вложение (файлы) материала.
     * 
     * @param bool $includeDir Удалить папку материала (по умолчанию `true`).
     * 
     * @return int|false Возвращает значение `false` если ошибка удаления, иначе, 
     *     количетсво удалённых файлов.
     */
    public function deleteAttachment(bool $includeDir = true): int|false
    {
        $count = 0; // счётчик файлов
        $success = true; // успех удаления

        /** @var array $paths Локальные пути материала соответст-е диалогу */
        $paths = $this->mediaPathsToArray();
        if ($paths) {
            Fs::$throwException = false;

            /** @var \Gm\Backend\References\MediaDialog\Model\MediaDialog $mediaDialog */
            $mediaDialog = Gm::getEModel('MediaDialog', 'gm.be.references.media_dialogs');
            $aliases = $mediaDialog->getAliasesWithPaths();
            foreach ($paths as $alias => $localPath) {
                if (isset($aliases[$alias])) {
                    // например: 'public/uploads/img' . DS . '24/08/21/6'
                    $path = $aliases[$alias] . DS . $localPath;
                    // если каталог материала существует
                    if (file_exists($path)) {
                        if (!Fs::deleteDirectory($path, !$includeDir, $count)) {
                            Gm::error('Unable to delete directory "' . $path . '"');
                            $success = false;
                        }
                    }
                }
            }
        }
        return $success ? $count : false;
    }

    /**
     * Удаляет вложения (файлы) указанных материалов.
     * 
     * @param array|string|null $where Условие выборки материала у которого есть вложение.
     *     Например: `['id' => [1, 2, 3, 4]]`.
     * @param bool $includeDir Удалить папку материалов (по умолчанию `true`).
     * 
     * @return int|false Возвращает значение `false` если ошибка удаления, иначе, 
     *     количетсво удалённых файлов.
     */
    public function deleteAttachments(array|string|null $where, bool $includeDir = true): int|false
    {
        $count = 0; // счётчик файлов
        $success = true; // успех удаления
        Fs::$throwException = false;

        /** @var \Gm\Backend\References\MediaDialog\Model\MediaDialog $mediaDialog */
        $mediaDialog = Gm::getEModel('MediaDialog', 'gm.be.references.media_dialogs');
        $aliases = $mediaDialog->getAliasesWithPaths();

        /** @var array $articles Удаляемый материал */
        $articles = $this->fetchAll(null, ['*'], $where);
        foreach ($articles as $article) {
            $mediaPaths = $article['media_paths'] ?? null;

            if ($mediaPaths) {
                $paths = Json::decode($mediaPaths);
                if ($error = Json::error()) {
                    Gm::error($error . ' JSON: ' . $this->mediaPaths);
                    continue;
                }

                foreach ($paths as $alias => $localPath) {
                    if (isset($aliases[$alias])) {
                        // например: 'public/uploads/img' . DS . '24/08/21/6'
                        $path = $aliases[$alias] . DS . $localPath;
                        // если каталог материала существует
                        if (file_exists($path)) {
                            if (!Fs::deleteDirectory($path, !$includeDir, $count)) {
                                Gm::error('Unable to delete directory "' . $path . '"');
                                $success = false;
                            }
                        }
                    }
                }
            } // $mediaPaths
        }
        return $success ? $count : false;
    }

    /**
     * Проверяет, установлен ли флаг - материал имеет шорткод.
     * 
     * @return bool
     */
    public function hasShortcode(): bool
    {
        return (int) $this->hasShortcode > 0;
    }
}
