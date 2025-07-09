<?php


namespace App\Services;

use App\Constants\AppConst;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class BaseService
{
    const TIME_STAMP = ['created_at', 'updated_at', 'deleted_at'];
    const EXCLUDE_PARAMS = ['page', 'limit', 'sort', 'order_by', 'sort_order'];
    /** @var $model Model */
    protected $model;

    protected $sort;

    protected $query;

    public function __construct()
    {
        $this->setModel();
        $this->query = $this->model->newQuery();
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public abstract function model();

    /**
     * Set Eloquent Model to instantiate
     *
     * @return void
     */
    private function setModel(): void
    {
        $newModel = App::make($this->model());

        if (!$newModel instanceof Model)
            throw new \RuntimeException("Class {$newModel} must be an instance of Illuminate\\Database\\Eloquent\\Model");

        $this->model = $newModel;
    }

    /**
     * @param bool $withDefaultFilter
     * @return Builder
     */
    private function query(bool $withDefaultFilter = false): Builder
    {
        return $this->buildBasicQuery(null, [], false, $withDefaultFilter);
    }

    /**
     * @param array|null $params
     * @param array $relations
     * @param bool $withTrashed
     * @param bool $withDefaultFilter
     * @return Builder
     */
    protected function buildBasicQuery(array $params = null, array $relations = [], bool $withTrashed = false, bool $withDefaultFilter = true): Builder
    {
        $query = $this->model->query();
        $params = $params ?: request()->toArray();

        if ($relations && count($relations)) {
            $query->with($relations);
        }
        if ($withTrashed && in_array(SoftDeletes::class, class_uses($this->model)) && method_exists($query, 'withTrashed')) {
            $query->withTrashed();
        }
        if (method_exists($this, 'prependQuery')) {
            $this->prependQuery($query);
        }
        if ($withDefaultFilter) {
            $this->addDefaultFilter($query, $params);
        }
        if (method_exists($this, 'appendQuery')) {
            $this->appendQuery($query);
        }
        if (method_exists($this, 'ownClasses')) {
            $this->ownClasses($query);
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param $params
     * @return Builder
     */
    protected function addDefaultFilter(Builder $query, $params = null): Builder
    {
        $params = $params ?: request()->toArray();
        foreach ($params as $column => $filter) {
            if (!in_array($column, self::EXCLUDE_PARAMS)) {
                $this->basicFilter($query, $column, $filter);
            }
        }
        $this->getSort($params);
        if ($this->sort) {
            foreach ($this->sort as $order => $direction) {
                $query->orderBy($order, $direction);
            }

        }

        return $query;
    }

    public function getSort($params)
    {
        $sort = $params['order_by'] ?? request()->input('order_by');
        $sortDirection = $params['sort_order'] ?? request()->input('sort_order');
        $sort = is_array($sort) ? $sort : [$sort];
        $sortDirection = is_array($sortDirection) ? $sortDirection : [$sortDirection];
        $this->sort = array_filter(array_combine($sort, $sortDirection));
    }

    /**
     * condtion = : column__equal
     * condition like: default
     * condition >=: column__from
     * condition <=: column__to.
     *
     * @param Builder $query
     * @param $key
     * param $value
     * @return void
     */
    protected function basicFilter(Builder $query, $key, $value)
    {
        if ($this->checkParamFilter($value)) {

            if (Str::endsWith($key, '__like')) {
                $col = Str::substr($key, 0, -6);
                $query->where($col, 'LIKE', '%' . $value . '%');
            } elseif (Str::endsWith($key, '__from')) {
                $col = Str::substr($key, 0, -6);
                $query->where($col, '>=', $value);
            } elseif (Str::endsWith($key, '__in')) {
                $col = Str::substr($key, 0, -4);
                if (is_array($value)) {
                    $query->whereIn($col, $value);
                }
            } elseif (Str::endsWith($key, '__to')) {
                $col = Str::substr($key, 0, -4);
                $query->where($col, '<=', $value);
            } elseif (Str::endsWith($key, '__equal')) {
                $col = Str::substr($key, 0, -7);
                if (is_array($value)) {
                    $query->whereIn($col, $value);
                } else {
                    $query->where($col, $value);
                }
            }
        }
    }

    /**
     * @param $value
     * @return bool
     */
    protected function checkParamFilter($value): bool
    {
        return $value != '' && $value != null;
    }

    /**
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function findAll(array $columns = ['*'])
    {
        return $this->query->get(is_array($columns) ? $columns : func_get_args());
    }

    /**
     * Retrieve the specified resource.
     *
     * @param int $id
     * @param array $relations
     * @param array $appends
     * @param array $hidden
     * @param bool $withTrashed
     * @return Model
     */
    public function show(int $id, array $relations = [], array $appends = [], array $hidden = [], bool $withTrashed = false): Model
    {
        $query = $this->model->query();
        if ($withTrashed) {
            $query->withTrashed();
        }
        return $query->with($relations)->findOrFail($id)->setAppends($appends)->makeHidden($hidden);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param array $attributes
     * @return Model|bool
     * @throws Exception
     */
    public function store(array $attributes)
    {
        $parent = $this->query->create($attributes);
        $relations = [];

        foreach (array_filter($attributes, [$this, 'isRelation']) as $key => $models) {
            if (method_exists($parent, $relation = Str::camel($key))) {
                $relations[] = $relation;
                $this->syncRelations($parent->$relation(), $models, false);
            }
        }
        if (count($relations)) {
            $parent->load($relations);
        }

        return $parent->push() ? $parent : false;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Model|int $parent
     * @param array $attributes
     * @return Model|bool
     *
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function update($parent, array $attributes)
    {
        if (is_integer($parent)) {
            $parent = $this->query->findOrFail($parent);
        }
        $parent->fill($attributes);
        $relations = [];

        foreach (array_filter($attributes, [$this, 'isRelation']) as $key => $models) {
            if (method_exists($parent, $relation = Str::camel($key))) {
                $relations[] = $relation;
                $this->syncRelations($parent->$relation(), $models);
            }
        }
        if (count($relations)) {
            $parent->load($relations);
        }

        return $parent->push() ? $parent : false;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Model|int $item
     * @param bool $force
     * @return bool
     *
     */
    public function destroy($item, bool $force = false): bool
    {
        if (is_integer($item)) {
            $item = $this->model->findOrFail($item);
        }
        return $item->{$force ? 'forceDelete' : 'delete'}();
    }

    /**
     * @param $id
     * @return bool
     */
    public function restore($id): bool
    {
        return $this->query->withTrashed()->findOrFail($id)->restore();
    }

    /**
     * @param array $attrs
     * @param array $columns
     * @param array $relations
     * @return Builder|Model|null|object
     */
    public function findBy(array $attrs, $columns = [], $relations = [])
    {
        return $this->model->query()->select($columns ?: '*')->where($attrs)
            ->with($relations)
            ->first();
    }

    /**
     * @param array $attributes
     * @param array $values
     * @return Builder|Model
     */
    public function firstOrCreate(array $attributes, array $values = [])
    {
        return $this->query->firstOrCreate($attributes, $values);
    }

    /**
     * @param array $attributes
     * @param array $values
     * @return Builder|Model
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        return $this->query->updateOrCreate($attributes, $values);
    }

    /**
     * @param $params
     * @param array $relations
     * @param bool $withTrashed
     * @param array $columns
     * @param bool $toPagination
     * @return array | Builder
     */
    public function paginate($params = null, array $relations = [], $columns = [], $toPagination = true)
    {
        $query = $this->buildBasicQuery($params, $relations);
        if (isset($params['val']) && isset($params['label'])) {
            $columns = [$params['val'], $params['label']];
        }
        if ($columns) {
            $query->select($columns);
        }
        if (!$toPagination) {
            return $query->get();
        } else {
            return $this->toPagination($query, $params);
        }
    }

    /**
     * @param Builder $query
     * @param null $params
     * @return array
     */
    public function toPagination(Builder $query, $params = null): array
    {
        $params = $params ?: request()->toArray();
        $page = intval(request()->input('page')) ? request()->input('page') : 1;
        $limit = intval(request()->input('limit')) ? request()->input('limit') : AppConst::DEFAULT_LIMIT;

        $dataCount = $query->count();
        $data = $query->forPage($page, $limit)->get();

        if ($dataCount == 0) {
            $maxPage = 0;
        } else {
            $maxPage = intdiv(($dataCount - 1), $limit) + 1;
        }
        $from = ($page - 1) * $limit + 1;
        $to = min($page * $limit, $dataCount);

        return [
            'data' => isset($params['val']) && isset($params['label']) ? $this->toDropdownTransform($data->toArray(), $params['val'], $params['label']) : $data,
            'total' => $dataCount,
            'last_page' => $maxPage,
            'from' => $from,
            'to' => $to,
            'current_page' => intval($page),
            'per_page' => (int)$limit,
        ];
    }

    /**
     * @param $data
     * @param $val
     * @param $label
     * @return array
     */
    private function toDropdownTransform($data, $val, $label)
    {
        $result = [];
        if ($pos = strrpos($val, '.')) {
            $val = substr($val, $pos + 1);
        }
        if ($pos = strrpos($label, '.')) {
            $label = substr($label, $pos + 1);
        }
        collect($data)->each(function ($item) use ($val, $label, &$result) {
            array_push($result, ['key' => $item[$val], 'label' => $item[$label]]);
        });
        unset($data);

        return $result;
    }

    /**
     * @param $value
     * @return bool
     */
    private function isRelation($value): bool
    {
        return is_array($value) || $value instanceof Model;
    }

    /**
     * @param Relation $relation
     * @param array | Model $conditions
     * @param bool $detaching
     * @return void
     * @throws Exception
     */
    private function syncRelations(Relation $relation, $conditions, bool $detaching = true): void
    {
        $conditions = is_array($conditions) ? $conditions : [$conditions];
        $relatedModels = [];
        foreach ($conditions as $condition) {
            if ($condition instanceof Model) {
                $relatedModels[] = $condition;
            } else if (is_array($condition)) {
                $relatedModels[] = $relation->firstOrCreate($condition);
            }
        }

        if ($relation instanceof BelongsToMany && method_exists($relation, 'sync')) {
            $relation->sync($this->parseIds($relatedModels), $detaching);
        } else if ($relation instanceof HasMany | $relation instanceof HasOne) {
            $relation->saveMany($relatedModels);
        } else {
            throw new Exception('Relation not supported');
        }
    }

    /**
     * @param array $models
     * @return array
     */
    private function parseIds(array $models): array
    {
        $ids = [];
        foreach ($models as $model) {
            $ids[] = $model instanceof Model ? $model->getKey() : $model;
        }

        return $ids;
    }


    public function filterOptions($request, $columns = ['key' => 'id', 'label' => 'name', 'other_cols' => []])
    {
        $key = $request->get('key');
        $perPage = $this->model->getPerPage();
        if ($key && is_array($key)) {
            $perPage = max($perPage, count($key));
        }

        return $this->searchOptionQuery($request, $columns)->get();
    }

    /**
     * @param $request
     * @param $columns
     * @param $closure
     * @param $selectArr
     * @param $baseQuery
     * @return mixed|null
     */
    public function searchOptionQuery($request, $columns = ['key' => 'id', 'label' => 'name', 'other_cols' => []], $closure = null, $selectArr = [], $baseQuery = null)
    {
        if(!$baseQuery) {
            $baseQuery = $this->model->query();
        }
        $key = $request->get('key');
        $label = $request->get('label');
        $selectAddId = Arr::get($request->all(), 'select_add_id', []);
        $selectArr = array_merge($selectArr, [$columns['key'], $columns['label']]);
        if (isset($columns['other_cols']) && count($columns['other_cols'])) {
            $selectArr = array_merge($selectArr, $columns['other_cols']);
        }
        if (isset($columns['add_cols']) && count($columns['add_cols'])) {
            $selectArr = array_merge($selectArr, $columns['add_cols']);
        }
        $selectArr = array_unique(array_filter($selectArr));
        $query = clone $baseQuery;
        $query->select($selectArr);
        $this->addDefaultFilter($query, request()->all());
        $query->orderBy($columns['key']);
        if (isset($label)) {
            $label = str_replace('%', '\%', $label);
            $query->where(function ($query) use ($columns, $label) {
                if ($columns['label']) {
                    $query->orWhere($columns['label'], 'LIKE',  "%$label%");
                }
                if (!empty($columns['key'])) {
                    $query->orWhere($columns['key'], 'LIKE',  "%$label%");
                }
                if (isset($columns['other_cols'])) {
                    foreach ($columns['other_cols'] as $col) {
                        if (strpos($col, ' as ') !== false) {
                            $col = trim(explode(' as ', $col)[0]);
                            $query->orWhere(DB::raw($col), 'LIKE', "%$label%");
                        } else {
                            $query->orWhere($col, 'LIKE',  "%$label%");
                        }
                    }
                }
                if (isset($columns['closure']) && $columns['closure']) {
                    $columns['closure']($query);
                }
            });
        }
        if ($selectAddId) {
            $query->orWhereIn($columns['key'], $selectAddId);
        }
        if ($closure) {
            $closure($query);
        }
        if ($key && is_array($key)) {
            $subQuery = clone $baseQuery;
            $subQuery->select($selectArr)->whereIn($columns['key'], $key);
            $this->addDefaultFilter($subQuery, request()->all());
            $query = $subQuery->union($query);
            $subQueryAll = clone $baseQuery;
            $subQueryAll = $subQueryAll->select($selectArr);
            $this->addDefaultFilter($subQueryAll, request()->all());
            $query->union($subQueryAll);
        } elseif ($key) {
            $subQuery = clone $baseQuery;
            $subQuery->select($selectArr)->where($columns['key'], $key);
            $this->addDefaultFilter($subQuery, request()->all());
            $query = $subQuery->union($query);
        }
        return $query;
    }


    /**
     * @param Builder $query
     * @return Builder
     *
     * Sử dụng khi append điều kiện truy vấn bổ sung vào query
     *
     */
    public function prependQuery(Builder $query)
    {
        return $query;
    }


    /**
     * @param Builder $query
     * @return Builder
     *
     * Sử dụng append các truy vấn bổ sung vào phía trước các điều kiện filter
     */
    public function appendQuery(Builder $query)
    {
        return $query;
    }
}