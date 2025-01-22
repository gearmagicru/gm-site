<?php
/**
 * Этот файл является частью пакета GM Site.
 * 
 * @link https://gearmagic.ru/framework/
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Site\Url;

use Gm;
use Gm\Site\Page;

/**
 * UrlRules класс создаёт URL-адрес на основе правил.
 * 
 * UrlRules - это служба приложения, доступ к которой можно получить через `Gm::$app->urlRules`.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Site\Url
 * @since 2.0
 */
class UrlRules extends \Gm\Url\UrlRules
{
    /**
     * {@inheritdoc}
     */
    public array $rules = [
        /**
         * Простые
         *    - <scheme>://<host>/?p=<article-id>
         *    - <scheme>://<host>/?c=<category-id>
         */
        'Plain' => [],
        /**
         * Дата (месяц) с названием статьи
         *    - <scheme>://<host>/<date(YYYY/mm)>/<article-name>
         *    - <scheme>://<host>/<category-name>
         */
        'MonthArticleName' => [],
        /**
         * Дата с названием статьи
         *    - <scheme>://<host>/<date(YYYY/mm/dd)>/<article-name>
         *    - <scheme>://<host>/<category-name>
         */
        'DateArticleName' => [],
        /**
         * Идентификатор статьи с указателем
         *    - <scheme>://<host>/article/<article-id>
         *    - <scheme>://<host>/category/<category-id>
         */
        'DirectArticleId' => [],
        /**
         * Название статьи с указателем
         *    - <scheme>://<host>/article/<article-name>
         *    - <scheme>://<host>/category/<category-name>
         */
        'DirectArticleName' => [],
        /**
         * Название статьи (.html) с указателем
         *    - <scheme>://<host>/article/<article-name>.html
         *    - <scheme>://<host>/category/<category-name>
         */
        'DirectArticleNameExt' => ['useFilename' => true],
        /**
         * Название категории и статьи.
         *    - <scheme>://<host>/<category-name>/<article-name>
         *    - <scheme>://<host>/<category-name>
         */
        'CategoryAndArticleName' => [],
        /**
         * Название категории и статьи (.html).
         *    - <scheme>://<host>/<category-name>/<article-name>.html
         *    - <scheme>://<host>/<category-name>
         */
        'CategoryAndArticleNameExt' => ['useFilename' => true],
        /**
         * Название статьи (.html).
         *    - <scheme>://<host>/<article-name>.html
         *    - <scheme>://<host>/<category-name>
         */
        'ArticleNameExt' => ['useFilename' => true]
    ];

    /**
     * Страница сайта.
     * 
     * @see UrlRules::getPage()
     * 
     * @var Page
     */
    public Page $page;

    /**
     * Возвращает страницу сайта.
     * 
     * @return Page
     */
    public function getPage(): Page
    {
        if (isset($this->page)) {
            return $this->page;
        }
        return $this->page = Gm::$services->getAs('page');
    }

    /**
     * Вносит изменения в компоненты URL-адреса согласно правилу "CategoryAndArticleNameExt".
     * 
     * @param array<string, mixed> $components Компоненты URL-адреса.
     * @param array<string, mixed> $options Парметры правила.
     * 
     * @return void
     */
    public function ruleCategoryAndArticleNameExt(array &$components, array $options): void
    {
        $suffix = $options['suffix'] ?? '.html';

        $basename = $components['basename'] ?? '';
        if ($basename) {
            if ($basename === 'index')
                $components['basename'] = '';
            else
                $components['basename'] = $basename . $suffix;
        }
    }

    /**
     * Разбирает (парсит) URL-адрес согласно правилу "CategoryAndArticleNameExt" и 
     * определяет материал и его категорию.
     * 
     * @param array<string, mixed> $options Парметры правила.
     * 
     * @return void
     */
    public function parseCategoryAndArticleNameExt(array $options): void
    {
        $page = $this->getPage();
        $url  = $page->urlManager;
        $article      = null;
        $category     = null;
        $pageArticle  = $page->createArticle();
        $pageCategory = $page->createCategory();
        // опции
        $suffix = $options['suffix'] ?? '.html';

        // если указан маршрут: "route/"
        if ($url->route) {
            $category = $pageCategory->getBySlugPath($url->route);
            // если указан файл в маршруте: "route/filename.html"
            if ($url->filename) {
                if ($category)
                    $article = $pageArticle->getByFilename($url->filename, $suffix, (int) $category->id);
            // иначе главный пост категории: "route/"
            } else {
                if ($category)
                    $article = $pageArticle->getByCategorySlugHome((int) $category->id);
            }
            // если нет (главного) поста по указанному маршруту,
            // нет смысла использовать категорию
            if (!$article)
                $category = null;
        // если указан маршрут: "filename.html" или ""
        } else { 
            if ($url->filename) {
                $article = $pageArticle->getByFilename($url->filename, $suffix, null);
            // если главная страница
            } else {
                $article = $pageArticle->getBySlugHome();
            }
        }
        $page->setArticle($article);
        $page->setCategory($category);
    }
}
