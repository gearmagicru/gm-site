<?php
/**
 * Этот файл является частью пакета GM Site.
 * 
 * @link https://gearmagic.ru/framework/
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Site;

use Gm;
use DateTimeZone;
use DateTimeInterface;
use Gm\Helper\Url;
use Gm\Stdlib\Service;
use Gm\Url\UrlRules;
use Gm\Url\UrlManager;
use Gm\I18n\Formatter;
use Gm\View\ClientScript;
use Gm\Site\Data\Selector;
use Gm\Site\Data\Model\Article;
use Gm\Site\Data\Model\ArticleCategory;
use Gm\Site\Data\Model\Breadcrumbs;

/**
 * Страница сайта.
 * 
 * Page - это служба приложения, доступ к которой можно получить через `Gm::$app->page`.
 * 
 * Управление виджетами (материала, категориями и т.п.), свойствами страницы сайта.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Site
 * @since 1.0
 */
class Page extends Service
{
    /**
     * {@inheritdoc}
     */
     protected bool $useUnifiedConfig = true;

    /**
     * Шаблон заголовка страницы по умолчанию.
     * 
     * Применяется если для текущего языка в {@see Page::$meta} нет параметра "titlePattern".
     * 
     * Пример: "%s - Заголовок страницы сайта".
     * Где параметр "%s" - заголовок материала.
     * 
     * @see Page::getTitle()
     * 
     * @var string
     */
    public string $titlePattern = '';

    /**
     * Заголовок страницы по умолчанию.
     * 
     * Применяется если для текущего языка в {@see Page::$meta} нет параметра "title".
     * 
     * @see Page::getTitle()
     * 
     * @var string
     */
    public string $title = '';

    /**
     * Описание страницы по умолчанию.
     * 
     * Применяется если для текущего языка в {@see Page::$meta} нет параметра "description".
     * Формирует мета-тег "<meta name="description"...".
     * 
     * @see Page::getDescription()
     * 
     * @var string
     */
    public string $description = '';

    /**
     * Ключевые слова страницы по умолчанию.
     * 
     * Применяется если для текущего языка в {@see Page::$meta} нет параметра "keywords".
     * Формирует мета-тег "<meta name="keywords"...".
     * 
     * @see Page::getKeywords()
     * 
     * @var string
     */
    public string $keywords    = '';

    /**
     * Автор страницы по умолчанию.
     * 
     * Применяется если для текущего языка в {@see Page::$meta} нет параметра "author".
     * Формирует мета-тег "<meta name="author"...".
     * 
     * @see Page::getAuthor()
     * 
     * @var string
     */
    public string $author = '';

    /**
     * Изображение (логотип) сайта для микроразметки социальных сетей.
     * 
     * Применяется если для текущего языка в {@see Page::$meta} нет параметра "image".
     * 
     *  @see Page::getImage()
     *
     * @var string
     */
    public string $image = '';

    /**
     * Индексации поисковыми роботами.
     * 
     * Формирует мета-тег "<meta name="robots"...".
     * 
     * @var string
     */
    public string $robots = '';

    /**
     * Использовать разметку Open Graph.
     * 
     * @var bool
     */
    public bool $useOpenGraph = true;

    /**
     * Использовать разметку Twitter Card.
     * 
     * @var bool
     */
    public bool $useTwitterCard = true;

    /**
     * Использовать разметку Schema.org.
     * 
     * @var bool
     */
    public bool $useSchemaOrg = true;

    /**
     * Использовать разметку VK.
     * 
     * @var bool
     */
    public bool $useVKSchema = true;

    /**
     * Текст "подписи" приложения при ответе пользователю.
     * 
     * @var string
     */
    public string $textPowered = '';

    /**
     * Использовать "подпись" приложения в заголовке (X-Powered-By) ответа пользователю.
     * 
     * @var bool
     */
    public bool $useHeaderPowered = true;

    /**
     * Использовать "подпись" приложения в мета-теге страницы.
     * 
     * @var bool
     */
    public bool $useMetaGenerator = true;

    /**
     * Активность сайта.
     * 
     * @var bool
     */
    public bool $active = true;

    /**
     * Метаданные страницы (по умолчанию) для используемых языков сайта.
     * 
     * Пример:
     * ```php
     * [
     *     'ru-RU' => [
     *         'titlePattern' => '%s - Site name',
     *         'title'        => 'Site name',
     *         'author'       => 'Default Author',
     *         'description'  => 'Site description',
     *         'keywords'     => 'Site keywords',
     *         'image'        => 'https://domain/images/site-logo.jpg'
     *     ],
     *     // ...
     * ]
     * ```
     *
     * @var array
     */
    public array $meta = [];

    /**
     * Селектор данных страницы.
     * 
     * @see Page::configure()
     * 
     * @var Selector
     */
    public Selector $select;

    /**
     * Форматтер.
     * 
     * @see Page::configure()
     * 
     * @var Formatter
     */
    public Formatter $formatter;

    /**
     * Скрипты клиента.
     * 
     * @see Page::configure()
     * 
     * @var ClientScript
     */
    public ClientScript $script;

    /**
     * URL Менеджер.
     * 
     * @see Page::configure()
     * 
     * @var UrlManager
     */
    public UrlManager $urlManager;

    /**
     * UrlRules класс создаёт URL-адреса на основе правил.
     * 
     * @see Page::configure()
     * 
     * @var UrlRules
     */
    public UrlRules $urlRules;

    /**
     * Часовой пояс информации выводимой на странице.
     * 
     * Часовой пояс в котором будет хранится информации на сервере. 
     * Если часовой пояс не указан, то будет применятся часовой пояс приложения
     * {@see \Gm\Mvc\Application::$dataTimeZone}.
     * 
     * @var DateTimeZone
     */
    public DateTimeZone $dataTimeZone;

    /**
     * Материал сайта полученный из запроса.
     * 
     * @see Page::findArticle()
     * 
     * @var Article|false|null
     */
    protected Article|false|null $article = null;

    /**
     * Категория материала, полученная из запроса.
     * 
     * @see Page::findCategory()
     * 
     * @var ArticleCategory|false|null
     */
    protected ArticleCategory|false|null $category = null;

    /**
     * URL-адрес страницы.
     * 
     * @see Page::getUrl()
     * 
     * @var string
     */
    protected string $url;

    /**
     * Опубликована ли страница.
     * 
     * @see Page::isPublished()
     * 
     * @var bool
     */
    protected bool $isPublished;

    /**
     * Имя шаблона страницы материала.
     * 
     * @see Page::getTemplate()
     * 
     * @var string|null
     */
    protected ?string $template;

    /**
     * Элементы навигационной цепочки относительно текущей страницы сайта.
     * 
     * @see Page::getBreadcrumbs()
     * 
     * @var Breadcrumbs
     */
    protected Breadcrumbs $breadcrumbs;

    /**
     * {@inheritdoc}
     */
    public function configure(array $config): void
    {
        parent::configure($config);

        if (!isset($this->script)) {
            $this->script = Gm::$services->getAs('clientScript');
        }
        if (!isset($this->select)) {
            $this->select = $this->getSelector();
        }
        if (!isset($this->formatter)) {
            $this->formatter =  Gm::$services->getAs('formatter');
        }
        if (!isset($this->urlManager)) {
            $this->urlManager =  Gm::$services->getAs('urlManager');
        }
        if (!isset($this->urlRules)) {
            $this->urlRules = Gm::$services->getAs('urlRules');
        }
        if (!isset($this->dataTimeZone)) {
            $this->dataTimeZone = Gm::$app->dataTimeZone;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectName(): string
    {
        return 'page';
    }

    /**
     * Возвращает селектор данных страницы.
     * 
     * @return Selector
     */
    public function getSelector(): Selector
    {
        if (isset($this->select)) {
            return $this->select;
        }
        return $this->select = new Selector();
    }

    /**
     * Создаёт материал сайта.
     * 
     * @param null|array $data Атрибуты материала.
     * 
     * @return Article
     */
    public function createArticle(?array $data = null): Article
    {
        $article = new Article();

        if ($data !== null) {
            $article->load($data);
        }
        return $article;
    }

    /**
     * Создаёт категорию материала.
     * 
     * @return ArticleCategory
     */
    public function createCategory(): ArticleCategory
    {
        return new ArticleCategory();
    }

    /**
     * Возвращает материал.
     *
     * @return Article|false|null
     */
    public function getArticle(): Article|false|null
    {
        return $this->article;
    }

    /**
     * Возвращает категорию материала.
     *
     * @return ArticleCategory|false|null
     */
    public function getCategory(): ArticleCategory|false|null
    {
        return $this->category;
    }

    /**
     * Возвращает материал сайта, ярлык (слаг) которой определён из URL-адреса.
     * 
     * Ярлык (слаг) определяется из правил {@see \Gm\Url\UrlRules::run()}.
     * 
     * @return Article|false|null Возвращает значение `false`, если материал не найден.
     */
    public function findArticle(): Article|false|null
    {
        if ($this->article === null) {
            $this->urlRules->run();
        }
        return $this->article;
    }

    /**
     * Возвращает категорию материал, ярлык (слаг) которой определён из URL-адреса.
     * 
     * Ярлык (слаг) определяется из правил {@see \Gm\Url\UrlRules::run()}.
     * 
     * @return ArticleCategory|false|null Возвращает значение `false`, если категория 
     *     не найдена.
     */
    public function findCategory(): ArticleCategory|false|null
    {
        if ($this->category === null) {
            $this->urlRules->run();
        }
        return $this->category;
    }

    /**
     * Возвращает материал сайта, ярлык (слаг) которой определён из URL-адреса.
     * 
     * Синоним {@see Page::findArticle()}.
     * 
     * @return  Article|false|null Возвращает значение `false`, если материал не найден.
     */
    public function find(): Article|false|null
    {
        if ($this->article === null) {
            $this->urlRules->run();
        }
        return $this->article;
    }

    /**
     * Устанавливает сайту материал.
     * 
     * @param Article|false|null $article Материал сайта. Если значение `false`, 
     *     материал не найден.
     * 
     * @return Article|false|null
     */
    public function setArticle( Article|false|null $article): Article|false|null
    {
        if ($article === null) {
            return $this->article = false;
        }
        return $this->article = $article;
    }

    /**
     * Устанавливает категорию материала.
     * 
     * @param ArticleCategory|false|null $category Категория материала. Если значение 
     *     `false`, то категория не найдена.
     * 
     * @return ArticleCategory|false|null
     */
    public function setCategory(ArticleCategory|false|null $category): ArticleCategory|false|null
    {
        if ($category === null) {
            return $this->category = false;
        }
        return $this->category = $category;
    }

    /**
     * Возвращает URL-адрес страницы не учитывая параметры запроса.
     * 
     * Определяет из ярлыка (слага) материала и её категории (если она есть).
     * Если материал не найден, возвращает URL-адрес текущей страницы.
     * 
     * @return string
     */
    public function getUrl(): string
    {
        if (!isset($this->url)) {
            if ($this->article) {
                $this->url = Url::to([
                    $this->category ? $this->category->slugPath : null,
                    'basename' => $this->article->getSlug()
                ]);
            } else
                $this->url = Url::to([$this->urlManager->requestUri]);
        }
        return $this->url;
    }

    /**
     * Проверяет, опубликована ли страница.
     * 
     * Станица опубликована в том случаи, если опубликован материал и его категория 
     * (если она есть).
     * 
     * @return bool
     */
    public function isPublished(): bool
    {
        if (!isset($this->isPublished)) {
            $article = $this->findArticle();
            if ($article) {
                $category = $this->findCategory();
                if ($category)
                    $this->isPublished = $category->isPublished() && $article->isPublished();
                else
                    $this->isPublished = $article->isPublished();
            } else
                $this->isPublished = false;
        }
        return $this->isPublished;
    }

    /**
     * Вызывает событие у слушателя приложении.
     * 
     * @param string $name Название события.
     * @param array $args Параметры передаваемые событием.
     * 
     * @return void
     */
    public function doEvent(string $name, array $args = []): void
    {
        Gm::$app->doEvent($name, $args);
    }

    /**
     * Выполняет регистрацию (добавление параметров) мета-информации страницы.
     * 
     * Мета-информацию указывают в свойствах материала или страницы.
     * 
     * @return void
     */
    public function registerMeta(): void
    {
        /** @var Article|false|null $article */
        $article = $this->article;

        // метаинформация для формирования микроразметки
        $metaContent = [
            'site'        => $this->getTitle(),
            'robots'      => $this->getRobots(),
            'url'         => $this->getUrl(),
            //'author'      => $this->getAuthor(),
            'keywords'    => $this->getKeywords(),
            'description' => $this->getDescription(),
            'tag'         => $this->getKeywords(),
            'image'       => $this->getImage()
        ];

        $meta = ['meta'];
        //  если используется микроразметка "Open Graph"
        if ($this->useOpenGraph) {
            $meta['openGraph'] = [];
        }
        //  если используется микроразметка "Twitter Card"
        if ($this->useTwitterCard) {
            if (!isset($meta['openGraph'])) {
                $meta['openGraph'] = [];
            }
            $meta['openGraph'][] = 'twitter';
        }
        // если используется микроразметка "Schema.org"
        if ($this->useSchemaOrg) {
            if (!isset($meta['openGraph'])) {
                $meta['openGraph'] = [];
            }
            $meta['openGraph'][] = 'schemaOrg';
        }
        // если используется микроразметка "VK"
        if ($this->useVKSchema) {
            if (!isset($meta['openGraph'])) {
                $meta['openGraph'] = [];
            }
            $meta['openGraph'][] = 'vk';
        }

        $this->script->title = $this->getTitle();
        // регистрация меты микроразметок
        $this->script->registerMeta($metaContent, $meta);

        // событие приложения о регистрации метаданных
        $this->doEvent('page:onRegisterMeta', [$this, $article]);
    }

    /**
     * Выполняет регистрацию системных скриптов страницы.
     * 
     * @return void
     */
    public function registerScripts(): void
    {
        $this->script->appendPackage('gm', [
            'position' => 'head',
            'vendor' => true,
            'js'     => ['gm.js'  => ['/gm/js/gm.min.js']],
            'css'    => ['gm.css' => ['/gm/css/gm.min.css']]
        ]);

        // если приминяется разметка представления
        if (Gm::$app->isViewMarkup()) {
            $article = $this->getArticle();
            $articleId = (int) ($article ? $article->id : 0);
            $typeId = (int) ($article ? $article->typeId : 0);
            $categoryId = (int) ($article ? $article->categoryId : 0);

            $this->script->js->registerScript('markup', 
                'Gm.onReady(Gm.Markup.create({articleId:' . $articleId . ', atypeId:' . $typeId . ', categoryId:' . $categoryId . '}));', 
                'end'
            );
            // событие приложения о регистрации метаданных
            $this->doEvent('page:createMarkup', [$this]);
        }
    }

    /**
     * Возвращает ключевые слова страницы.
     * 
     * @see Page::$keywords
     * 
     * @param null|string $languageTag Тег языка, например: "ru-RU", "en-GB"... (по умолчанию `null`) .
     * 
     * @return string|null
     */
    public function getKeywords(?string $languageTag = null): ?string
    {
        $article  = $this->find();
        $keywords = $article ? $article->keywords : '';

        if (empty($keywords)) {
            if ($languageTag === null) {
                $languageTag = Gm::$app->language->tag;
            }

            $keywords = $this->meta[$languageTag]['keywords'] ?? $this->keywords;
        }
        return $keywords;
    }

    /**
     * Возвращает описание страницы.
     * 
     * @see Page::$description
     * 
     * @param null|string $languageTag Тег языка, например: "ru-RU", "en-GB"... (по умолчанию `null`) .
     * 
     * @return string|null
     */
    public function getDescription(?string $languageTag = null): ?string
    {
        $article = $this->find();
        $desc    = $article ? $article->description : '';

        if (empty($desc)) {
            if ($languageTag === null) {
                $languageTag = Gm::$app->language->tag;
            }

            $desc = $this->meta[$languageTag]['description'] ?? $this->description;
        }
        return $desc;
    }

    /**
     * Возвращает изображение (логотип) для соцситей.
     * 
     * @see Page::$image
     * 
     * @param null|string $languageTag Тег языка, например: "ru-RU", "en-GB"... (по умолчанию `null`) .
     * 
     * @return string
     */
    public function getImage(?string $languageTag = null): string
    {
        $article = $this->find();
        $image   = $article ? $article->image : '';

        if (empty($image)) {
            if ($languageTag === null) {
                $languageTag = Gm::$app->language->tag;
            }

            $image = $this->meta[$languageTag]['image'] ?? $this->image;
        }
        return $image;
    }

    /**
     * Возвращает индексацию поисковыми роботами.
     * 
     * @see Page::$robots
     * 
     * @param null|string $languageTag Тег языка, например: "ru-RU", "en-GB"... (по умолчанию `null`) .
     * 
     * @return array
     */
    public function getRobots(?string $languageTag = null): array
    {
        $article = $this->find();
        if ($article) {
            /** @var string $robots */
            $robots = $article->getRobots();
            /** @var array $allRobots включает $robots */
            $allRobots = $article->getAllRobots();

            // если не указано в статье, тогда определяем из настроек "Информация о сайте"
            if (empty($robots)) {
                if ($languageTag === null) {
                    $languageTag = Gm::$app->language->tag;
                }
                // мета для указанного языка
                $value = $this->meta[$languageTag]['robots'] ?? $this->robots;
                if ($value)
                    $allRobots[] = $value;
            }
            return $allRobots;
        }
        return [];
    }

    /**
     * Возвращает заголовок страницы.
     * 
     * Определяется из заголовка материала или свойства {@see \Gm\Page\Page::$title}.
     * 
     * @param null|string $languageTag Тег языка, например: "ru-RU", "en-GB"... (по умолчанию `null`) .
     * 
     * @return string
     */
    public function getTitle(?string $languageTag = null): string
    {
        // если главная страница
        if ($this->isHome())
            return $this->title;
        else {
            if ($languageTag === null) {
                $languageTag = Gm::$app->language->tag;
            }

            $article = $this->find();
            if ($article)
                $title = $article->title;
            else 
                $title = $this->meta[$languageTag]['title'] ?? $this->title;

            $titlePattern = $this->meta[$languageTag]['titlePattern'] ?? $this->titlePattern;
            return $titlePattern ? sprintf($titlePattern, $title) : $title;
        }
    }

    /**
     * Устанавливает заголовок странице.
     * 
     * @see Page::$title
     * 
     * @param string $title Заголовок.
     * @param null|string $languageTag Тег языка, например: "ru-RU", "en-GB"... (по умолчанию `null`).
     * 
     * @return $this
     */
    public function setTitle(string $title, ?string $languageTag = null): static
    {
        if ($languageTag === null) {
            $languageTag = Gm::$app->language->tag;
        }
        $this->meta[$languageTag]['title'] = $title;

        $titlePattern = $this->meta[$languageTag]['titlePattern'] ?? $this->titlePattern;
        $this->script->title = $titlePattern ? sprintf($titlePattern, $title) : $title;
        return $this;
    }

    /**
     * @see Page::isHome()
     * 
     * @var bool
     */
    private bool $isHome;

    /**
     * Возвращает метаданные страницы (по умолчанию).
     * 
     * @see Page::$meta
     * 
     * @param null|string $languageTag Тег языка, например: "ru-RU", "en-GB"... 
     *     Если значение `null`, то результатом будут значения указанные по умолчанию.
     *
     * @return array
     */
    public function getDefaultMeta(?string $languageTag = null): array
    {
        if ($languageTag === null) {
            $languageTag = Gm::$app->language->tag;
        }
        if (isset($this->meta[$languageTag]))
            return $this->meta[$languageTag];
        else
            return [
            'titlePattern' => $this->titlePattern,
            'title'        => $this->title,
            'author'       => $this->author,
            'keywords'     => $this->keywords,
            'description'  => $this->description,
            'image'        => $this->image,
            'robots'       => $this->robots
        ];
    }

    /**
     * Проверяет, является ли текущая страница сайта главной.
     * 
     * @see \Gm\Url\UrlManager::isHome()
     * 
     * @return bool
     */
    public function isHome(): bool
    {
        if (!isset($this->isHome)) {
            $this->isHome = $this->urlManager->isHome();
        }
        return $this->isHome;
    }

    /**
     * Возвращает идентификатор категории материала.
     * 
     * @return int|null Возвращает `null` если категория материала отсутствует.
     */
    public function getCategoryId(): ?int
    {
        return $this->category ? (int) $this->category->id : null;
    }

    /**
     * Возвращает идентификатор материала.
     * 
     * @return int|null Возвращает `null` если материал отсутствует.
     */
    public function getArticleId(): ?int
    {
        return $this->article ? (int) $this->article->id : null;
    }

    /**
     * Возвращает атрибут (параметр) категории материала.
     *
     * @param string $name Имя атрибута (параметра).
     * 
     * @return mixed Если значение `null`, возможно указанный параметр отсутствует.
     */
    public function getCategoryParam(string $name): mixed
    {
        if ($this->category) {
            if ($this->category->hasAttribute($name)) {
                return $this->category->getAttribute($name);
            }

            // исключение для категории
            if ($name === 'header') {
                return $this->category->name;
            }
        }

        if ($this->article && $this->article->isHomeSlug()) {
            return $this->article->getAttribute($name);
        }
        return null;
    }

    /**
     * Возвращает атрибут (параметр) материала.
     * 
     * @param string $name Имя атрибута (параметра).
     * 
     * @return mixed Возвращает `null` если материал или атрибут отсутствует.
     */
    public function getArticleParam(string $name): mixed
    {
        return $this->article ? $this->article->getAttribute($name) : null;
    }

    /**
     * Возвращает элементы навигационной цепочки сайта.
     *
     * @return Breadcrumbs
     */
    public function getBreadcrumbs(): Breadcrumbs
    {
        if (!isset($this->breadcrumbs)) {
            $this->breadcrumbs = new Breadcrumbs();
        }
        return $this->breadcrumbs;
    }

    /**
     * Возвращает имя шаблона страницы материала.
     * 
     * @return string|null Возвращает `null` если материал отсутствует или не указан шаблон.
     */
    public function getTemplate(): ?string
    {
        if (!isset($this->template)) {
            /** @var Article|false $article */
            $article = $this->findArticle();
            $this->template = $article ? $article->pageTemplate : null;
        }
        return $this->template;
    }

    /**
     * Возвращает заголовок материала.
     * 
     * @return string|null
     */
    public function getHeader(): ?string
    {
        /** @var Article|false $article */
        $article = $this->findArticle();
        return $article ? $article->header : null;
    }

    /**
     * Создаёт URL-адрес категории материала.
     * 
     * Создаёт URL-адрес с указанием параметров (компонентов URL-адреса).
     * 
     * @see \Gm\Helper\Url::to()
     * @see \Gm\Url\UrlManager::createUrl()
     * 
     * @param array|string $params Параметры категории материала.
     * 
     * @return string
     */
    public function categoryLink(array|string $params): string
    {
        if (is_string($params)) {
            $url = [$params];
        } else {
            if (!isset($params['slug_path'])) return '';

            $url = [$params['slug_path']];
        }
        return Url::to($url);
    }

    /**
     * Форматирует значение в дату и время из часового пояса в указанный.
     * 
     * @see \Gm\I18n\Formatter::toDateTimeZone()
     * 
     * @param DateTimeInterface|string|int $value Значение для форматирования. 
     *    Поддерживаются следующие типы значений:
     *    - integer, представлено как UNIX timestamp;  
     *    - string, может использоваться для создания DateTime объекта {@link https://www.php.net/manual/ru/datetime.formats.php};  
     *    - PHP DateTime объект {@see https://www.php.net/manual/ru/class.datetime.php};  
     *    - PHP DateTimeImmutable объект {@see https://www.php.net/manual/ru/class.datetimeimmutable.php}.
     * @param string $format Формат преобразования значения (по умолчанию `null`). Если 
     *    формат `null`, используется текущий формат {@see Formatter::$dateFormat}.  
     *    Формат может иметь значения 'short', 'medium', 'long' или 'full'.  
     *    Также, может быть пользовательский формат, указанный в ICU {@link http://userguide.icu-project.org/formatparse/datetime}.  
     *    В качестве альтернативы может быть строка с префиксом 'php:', представляющая 
     *    формат даты PHP {@link https://www.php.net/manual/ru/datetime.formats.date.php}.
     * @param bool $normalize Если значение `true`, значение даты пройдет нормализацию и 
     *    форматирование (с использованием PHP intl), иначе форматирование с помощью 
     *    {@link https://www.php.net/manual/ru/function.date} (по умолчанию `true`).
     * 
     * @return string
     */
    public function formatDate(DateTimeInterface|string|int $value, ?string $format = null, bool $normalize = true): string
    {
        return $value ? $this->formatter->toDateTimeZone($value, $format, $normalize, $this->dataTimeZone) : '';
    }
}
