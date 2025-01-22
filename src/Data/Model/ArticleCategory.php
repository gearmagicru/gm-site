<?php
/**
 * Этот файл является частью пакета GM Site.
 * 
 * @link https://gearmagic.ru/framework/
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Site\Data\Model;

use Gm\Helper\Url;
use Gm\Db\ActiveRecord;
use Gm\NestedSet\Nodes;

/**
 * Категория материала.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Site\Data\Model
 * @since 1.0
 */
class ArticleCategory extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public function tableName(): string
    {
        return '{{article_category}}';
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
            'id'        => 'id', // идентификатор
            'name'      => 'name', // название
            'publish'   => 'publish', // опубликовать
            'slugPath'  => 'slug_path', // путь
            'slugHash'  => 'slug_hash', // хэш пути
            'ns_left'   => 'ns_left', // граница дерева слева
            'ns_right'  => 'ns_right', // граница дерева справа
            'ns_parent' => 'ns_parent' // идент. родительского узла
        ];
    }

    /**
     * Возвращает загаловок страницы.
     * 
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title !== null ? $this->title : '';
    }

    /**
     * Возвращает категорию материала по указанному пути.
     * 
     * @see ArticleCategory::selectOne()
     * 
     * @param string $slugPath Путь категории.
     * 
     * @return ActiveRecord|null Активная запись при успешном запросе, иначе `null`.
     */
    public function getBySlugPath(string $slugPath): ?ActiveRecord
    {
        return $this->selectOne(['slug_path' => $slugPath]);
    }

    /**
     * Возвращает категорию материала по указанному идентификатору.
     * 
     * @param int $categoryId Идентификатор категории материала.
     * 
     * @return ActiveRecord|null Активная запись при успешном запросе, иначе `null`.
     */
    public function getById(int $categoryId): ?ActiveRecord
    {
        return $this->selectByPk($categoryId);
    }

    /**
     * Проверяет, опубликована ли категория.
     *
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->publish > 0;
    }

    /**
     * Множество категорий.
     * 
     * @see ArticleCategory::getNodes()
     * 
     * @var Nodes
     */
    protected Nodes $nodes;

    /**
     * Возвращает Множество категорий.
     * 
     * @return Nodes
     */
    public function getNodes(): Nodes
    {
        if (!isset($this->nodes)) {
            $this->nodes = new Nodes([
                'tableName'    => $this->tableName(),
                'parentColumn' => 'ns_parent'
            ]);
        }
        return $this->nodes;
    }

    /**
     * Проверяет, имеет ли текущая категорию родительскую.
     *
     * @return bool
     */
    public function hasNodeParent(): bool
    {
        return !empty($this->ns_parent);
    }

   /**
     * Возвращает родительскую категорию для указанной или текущей категории.
     *
     * @param int|null $id Идентификатор категории для которой необходимо найти 
     *     родительскую категорию. Если значение `null`, поиск для текущей категории
     *     (по умолчанию `null`).
     * @param int $depth Глубина (уровень) поиска родительской категории 
     *     (по умолчанию `1`).
     *
     * @return array|null Атрибуты родительской категории в виде пар "ключ - значение". 
     *     Иначе значение `null`, если для указанного узла нет родительского.
     */
    public function getParent(int $id = null, int $depth = 1): ?array
    {
        /** @var \Gm\NestedSet\Nodes $nodes */
        $nodes = $this->getNodes();
        return $nodes->getParent($id ?: $this->getAttributes(), $depth);
    }

   /**
     * Возвращает все родительские категории для указанной или текущей категории.
     *
     * @param int|null $id Идентификатор категории для которой необходимо найти 
     *     родительские категории. Если значение `null`, поиск для текущей категории
     *     (по умолчанию `null`).
     *
     * @return array|null Атрибуты родительских категорий в виде пар "ключ - значение".
     */
    public function getParents(int $id = null): ?array
    {
        /** @var \Gm\NestedSet\Nodes $nodes */
        $nodes = $this->getNodes();
        return $nodes->getParent($id ?: $this->getAttributes(), null);
    }

    /**
     * Возвращает URL-адрес категории материала.
     * 
     * @return string
     */
    public function getUrl(array $params = []): string
    {
        $params[0] = $this->slugPath;
        return Url::to($params);
    }
}