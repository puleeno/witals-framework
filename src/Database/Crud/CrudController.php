<?php

declare(strict_types=1);

namespace Witals\Framework\Database\Crud;

use Witals\Framework\Http\AbstractController;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseProviderInterface;
use Witals\Framework\Database\Traits\Translatable;
use Witals\Framework\Seo\Traits\HasSeo;

/**
 * Base CRUD Controller for providing standardized data management.
 */
abstract class CrudController extends AbstractController
{
    use Translatable, HasSeo;

    protected DatabaseProviderInterface $dbal;
    protected string $table;
    protected array $translatableFields = [];
    protected bool $isSeoable = false;
    protected ?string $translationTable = null;
    protected ?string $translationForeignKey = null;

    public function __construct(DatabaseProviderInterface $dbal)
    {
        $this->dbal = $dbal;
    }

    /**
     * List all resources.
     */
    public function index(Request $request): Response
    {
        $query = $this->getInitialQuery($request);
        $this->applyFilters($query, $request);
        
        $items = $query->run()->fetchAll();
        $items = array_map([$this, 'processItem'], $items);

        return $this->json($items);
    }

    /**
     * Show a single resource.
     */
    public function show(Request $request, $id): Response
    {
        $query = $this->getInitialQuery($request);
        
        $alias = ($this->translationTable) ? 'p.' : '';
        $query->where($alias . 'id', $id);

        $item = $query->run()->fetch();

        if (!$item) {
            return $this->json(['error' => 'Not found'], 404);
        }

        return $this->json($this->processItem($item));
    }

    /**
     * Build the initial query with optional translation join.
     */
    protected function getInitialQuery(Request $request)
    {
        $locale = $request->getAttribute('locale') ?: app()->translator()->getLocale();
        $defaultLocale = config('app.locale', 'en');

        if ($this->translationTable) {
            if ($locale !== $defaultLocale) {
                $foreignKey = $this->translationForeignKey ?: (str_ends_with($this->table, 's') ? substr(str_replace('optilarity_', '', $this->table), 0, -1) : str_replace('optilarity_', '', $this->table)) . '_id';
                
                $fields = ['p.*'];
                foreach ($this->translatableFields as $field) {
                    $fields[] = "t.{$field} as {$field}_translated";
                }
                
                return $this->dbal->database()->select($fields)
                    ->from($this->table . ' as p')
                    ->leftJoin($this->translationTable . ' as t')
                    ->on('t.' . $foreignKey, 'p.id')
                    ->onWhere('t.language', '=', $locale);
            }

            return $this->dbal->database()->select('*')->from($this->table . ' as p');
        }

        return $this->dbal->database()->select('*')->from($this->table);
    }

    /**
     * Process an item (e.g., auto-translate fields).
     */
    protected function processItem(array $item): array
    {
        foreach ($this->translatableFields as $field) {
            $translatedKey = $field . '_translated';
            
            // 1. Check if separate table translation exists
            if (isset($item[$translatedKey]) && $item[$translatedKey] !== null && $item[$translatedKey] !== '') {
                $item[$field] = $item[$translatedKey];
            } 
            // 2. Fallback to JSON translation in the same row
            elseif (isset($item[$field])) {
                $item[$translatedKey] = $this->translate($item[$field]);
                $item[$field] = $item[$translatedKey];
            }
        }

        if ($this->isSeoable) {
            $item['seo'] = $this->getSeoData($item);
        }

        return $item;
    }

    /**
     * Apply default filters (can be overridden).
     */
    protected function applyFilters($query, Request $request): void
    {
        $alias = ($this->translationTable) ? 'p.' : '';
        
        if ($search = $request->query('search')) {
            $query->where($alias . 'name', 'LIKE', "%$search%");
        }
    }
}
