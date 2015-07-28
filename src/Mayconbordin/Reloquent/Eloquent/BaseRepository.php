<?php namespace Mayconbordin\Reloquent\Eloquent;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Debug\Dumper;
use Mayconbordin\Reloquent\Exceptions\NotFoundError;
use Mayconbordin\Reloquent\Exceptions\RepositoryException;
use Mayconbordin\Reloquent\Exceptions\ValidationError;
use Mayconbordin\Reloquent\Contracts\BaseRepositoryContract;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\Factory as Validator;
use Illuminate\Container\Container as Application;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

abstract class BaseRepository implements BaseRepositoryContract
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';

    const RESULT_SINGLE = 1;
    const RESULT_ALL    = 2;


    /**
     * The Eloquent Model
     *
     * @var Model
     */
    protected $model;

    /**
     * The validator factory instance.
     *
     * @var Validator
     */
    protected $validator;

    /**
     * @var Application
     */
    protected $application;

    /**
     * The models for with.
     *
     * @var array
     */
    protected $with = array();

    /**
     * List of relation names, used when removing an object.
     * @var array
     */
    protected $relations = array();

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;

        $this->makeModel();
        $this->makeValidator();
    }

    /**
     * @param array $attributes
     * @return mixed
     * @throws ValidationError
     */
    public function create(array $attributes)
    {
        $validator = $this->validate($attributes);

        if ($validator->fails()) {
            throw ValidationError::fromValidator($validator);
        }

        $related    = $this->fetchRelatedAttributes($attributes);
        $attributes = $this->transformAttributes($attributes);

        $model = $this->model->newInstance($attributes);

        $this->saveModel($model, $related);

        $this->resetModel();

        return $this->parserResult($model);
    }

    public function update(array $attributes, $id)
    {
        $validator = $this->validate($attributes, self::ACTION_UPDATE);

        if ($validator->fails()) {
            throw ValidationError::fromValidator($validator);
        }

        $related    = $this->fetchRelatedAttributes($attributes);
        $attributes = $this->transformAttributes($attributes);

        $model = $this->find($id);
        $model->fill($attributes);

        $this->saveModel($model, $related, self::ACTION_UPDATE);

        $this->resetModel();

        return $this->parserResult($model);
    }

    public function all($columns = array('*'), $orderCol = null, $orderDir = 'asc')
    {
        $query = $this->model;

        if ($orderCol != null) {
            $query = $query->orderBy($orderCol, $orderDir);
        }

        $model = $query->get($columns);

        $this->resetModel();

        return $this->parserResult($model);
    }

    public function exists($id)
    {
        $model = DB::table($this->model->getTable())->where($this->model->getKeyName(), $id)->first();
        $this->resetModel();
        return !is_null($model);
    }

    /**
     * Find data by id
     * @param $id
     * @param array $columns
     * @return Model
     * @throws NotFoundError
     */
    public function find($id, $columns = array('*'))
    {
        $model = $this->model->find($id, $columns);

        if ($model == null) {
            throw new NotFoundError($this->getMessage('not_found'));
        }

        $this->resetModel();
        return $this->parserResult($model);
    }

    /**
     * Find data by field and value
     *
     * @param string $field
     * @param mixed  $value
     * @param string $operator
     * @param array  $columns
     * @return mixed
     */
    public function findByField($field, $value = null, $operator = '=', $columns = array('*'))
    {
        $model = $this->model->where($field, $operator, $value)->first($columns);
        $this->resetModel();
        return $this->parserResult($model);
    }

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     * @return mixed
     */
    public function findWhere(array $where, $columns = array('*'))
    {
        $model = $this->createQuery($where)->first($columns);
        $this->resetModel();
        return $this->parserResult($model);
    }

    /**
     * Find data by field and value
     *
     * @param string $field
     * @param mixed $value
     * @param string $operator
     * @param array $columns
     * @return mixed
     */
    public function findAllByField($field, $value = null, $operator = '=', $columns = array('*'))
    {
        $model = $this->model->where($field, $operator, $value)->get($columns);
        $this->resetModel();
        return $this->parserResult($model);
    }

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     * @return mixed
     */
    public function findAllWhere(array $where, $columns = array('*'))
    {
        $model = $this->createQuery($where)->get($columns);
        $this->resetModel();
        return $this->parserResult($model);
    }

    /**
     * Find data by field and value with pagination.
     *
     * @param string $field
     * @param mixed  $value
     * @param string $operator
     * @param int    $perPage
     * @param array  $columns
     * @return mixed
     */
    public function findAllByFieldPaginated($field, $value = null, $operator = '=', $perPage = 15, $columns = array('*'))
    {
        $model = $this->model->where($field, $operator, $value)->paginate($perPage, $columns);
        $this->resetModel();
        return $this->parserResult($model);
    }

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param int   $perPage
     * @param array $columns
     * @return mixed
     */
    public function findAllWherePaginated(array $where, $perPage = 15, $columns = array('*'))
    {
        $model = $this->createQuery($where)->paginate($perPage, $columns);
        $this->resetModel();
        return $this->parserResult($model);
    }

    /**
     * Retrieve all data of repository, paginated
     * @param int $limit
     * @param array $columns
     * @param string|null $orderCol
     * @param string $orderDir
     * @return mixed
     */
    public function paginate($limit = 15, $columns = array('*'), $orderCol = null, $orderDir = 'asc')
    {
        $query = $this->model;

        if ($orderCol != null) {
            $query = $query->orderBy($orderCol, $orderDir);
        }

        $results = $this->model->paginate($limit, $columns);
        $this->resetModel();
        return $this->parserResult($results);
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return call_user_func_array([$this->model, 'count'], []);
    }

    public function delete($id)
    {
        $model = $this->find($id);

        DB::beginTransaction();

        try {
            $this->deleteRelated($model);
            $model->delete();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage(), ['trace' => $e->getTrace(), 'exception' => $e]);
            throw new ServerError($this->getMessage('remove_error'));
        }

        DB::commit();
    }

    /**
     * @param $ids
     * @return mixed
     */
    public function destroy(array $ids)
    {
        return $this->model->destroy($ids);
    }

    /**
     * @param $observer
     * @return mixed
     */
    public function observe($observer)
    {
        return call_user_func_array([$this->model, 'observe'], [$observer]);
    }

    /**
     * Load relations
     *
     * @param array|string $relations
     * @return $this
     */
    public function with($relations)
    {
        $this->model = $this->model->with($relations);
        return $this;
    }

    protected function orderBy($query, $field, $order)
    {
        $order = is_null($order) ? 'asc' : $order;

        if (!in_array($order, ['asc', 'desc'])) {
            throw new \InvalidArgumentException("Valid values for orderBy are 'asc' and 'desc'");
        }

        if ($field != null) {
            $query->order->orderBy($field, $order);
        }
    }

    /**
     * @param null $query
     * @param string $action
     * @return array
     */
    public function rules($query = null, $action)
    {
        $model = $this->model;

        // get rules from the model if set
        if ($action == self::ACTION_UPDATE && isset($model::$rules_update)) {
            $rules = $model::$rules_update;
        } else if (isset($model::$rules)) {
            $rules = $model::$rules;
        } else {
            $rules = [];
        }

        if(empty($rules) || !is_array($rules)) {
            return [];
        }

        // if the query is empty
        if (!$query) {
            // return all of the rules
            // array filter clears empty, null, false values
            return array_filter($rules);
        }

        // return the relevant rules
        return array_filter(array_only($rules, $query));
    }

    /**
     * @param array $data
     * @param string $action
     * @param null $rules
     * @param bool $custom
     * @return \Illuminate\Validation\Validator
     */
    public function validate(array $data, $action = self::ACTION_CREATE, $rules = null, $custom = false)
    {
        if (!$custom) {
            $rules = $this->rules($rules, $action);
        }

        return $this->validator->make($data, $rules);
    }

    /**
     * Parse a single object.
     *
     * @param Model|null $result
     * @return mixed
     * @throws NotFoundError
     */
    public function parserResult($result)
    {
        if ($result == null) {
            throw new NotFoundError($this->getMessage('not_found'));
        }

        return $result;
    }

    /**
     * Parse a collection of objects.
     *
     * @param array|Collection|Paginator $results
     * @return mixed
     */
    public function parserResults($results)
    {
        return $results;
    }

    /**
     * Transform the array of attributes for the create and update methods.
     * @param array $attributes
     * @return array
     */
    public function transformAttributes(array $attributes)
    {
        return $attributes;
    }

    /**
     * @return string
     */
    public abstract function model();

    /**
     * @return string
     */
    public abstract function name();

    /**
     * @return int The default limit for results
     */
    public function limit()
    {
        return Config::get('reloquent.limit', 20);
    }

    /**
     * @return int The default number of results per page
     */
    public function perPage()
    {
        return Config::get('reloquent.pagination.per_page', 15);
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return Config::get('reloquent.debug', true);
    }

    public function resetModel()
    {
        $this->makeModel();
    }

    protected function getMessage($key)
    {
        return Lang::get('reloquent::messages.'.$key, ['entity' => $this->name()]);
    }

    /**
     * @return Model
     * @throws RepositoryException
     */
    protected function makeModel()
    {
        $model = $this->application->make($this->model());

        if (!$model instanceof Model) {
            throw new RepositoryException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * Get an instance of the validator.
     */
    protected function makeValidator()
    {
        $this->validator = $this->application->make('validator');
    }

    /**
     * Extracts the related attributes into an associative array.
     *
     * @param array $attributes The list of attributes received on create or update.
     * @return array
     */
    protected function fetchRelatedAttributes(array $attributes)
    {
        $related = [];

        foreach ($this->relations as $relation) {
            if (array_key_exists($relation, $attributes) === true) {
                $value = array_pull($attributes, $relation, null);
                if ($value != null) {
                    $related[$relation] = $value;
                }
            }
        }

        return $related;
    }

    /**
     * Saves the relations of a model.
     *
     * @param Model  $model   The model to be saved
     * @param array  $related List of relations fetched from attributes
     * @param string $cls     The class name of the relation being saved.
     * @param string $action  (Default: 'create') The type of action being performed: 'create' or 'update'.
     */
    protected function saveRelated(Model $model, array $related, $cls, $action = self::ACTION_CREATE)
    {
        foreach ($related as $relation => $value) {
            $rel = $model->$relation();

            if ($cls === 'Illuminate\Database\Eloquent\Relations\BelongsTo' && $rel instanceof BelongsTo) {
                $rel->associate($value);
            } else if ($cls === 'Illuminate\Database\Eloquent\Relations\BelongsToMany' && $rel instanceof BelongsToMany) {
                if ($action == self::ACTION_UPDATE) {
                    $rel->detach();
                }
                $rel->attach($value);
            }
        }
    }

    protected function deleteRelated(Model $model)
    {
        foreach ($this->relations as $relation) {
            $rel = $model->$relation();

            if ($rel instanceof HasOneOrMany) {
                $rel->delete();
            } else if ($rel instanceof BelongsToMany) {
                $rel->detach();
            }
        }
    }

    /**
     * Save the model and its relations.
     *
     * @param Model  $model   The model to be saved
     * @param array  $related List of relations fetched from attributes
     * @param string $action  (Default: 'create') The type of action being performed: 'create' or 'update'.
     */
    protected function saveModel(Model $model, $related = [], $action = self::ACTION_CREATE)
    {
        // Make the associations
        $this->saveRelated($model, $related, 'Illuminate\Database\Eloquent\Relations\BelongsTo', $action);

        // Save the model
        $model->save();

        // Save the m:n relations
        $this->saveRelated($model, $related, 'Illuminate\Database\Eloquent\Relations\BelongsToMany', $action);
    }


    /**
     * Get a new query that searches by attributes.
     *
     * @param  array  $where
     * @param  string $operator   Default: '='
     *
     * @return Builder
     */
    public function createQuery(array $where, $orderBy = [], $limit = null, $operator = '=')
    {
        $query = $this->model->newQuery();

        foreach ($where as $field => $value) {
            if (is_array($value) && sizeof($value) == 3) {
                list($field, $condition, $val) = $value;

                if ($condition == 'in') {
                    $query->whereIn($field, $val);
                } else {
                    $query->where($field, $condition, $val);
                }
            } else if (is_array($value) && sizeof($value) == 4) {
                list($operator, $field, $condition, $val) = $value;

                if ($operator == 'or') {
                    $query->orWhere($field, $condition, $val);
                } else if ($operator == 'and') {
                    $query->where($field, $condition, $val);
                }
            } else {
                $query->where($field, $operator, $value);
            }
        }

        foreach ($orderBy as $value) {
            list($field, $order) = $value;
            $query->orderBy($field, $order);
        }

        if ($limit != null) {
            $query->limit($limit);
        }

        return $query;
    }

    /**
     * Builds a query based on field names, operators and parameters.
     *
     * @param int $resultType
     * @param array $fields A list of field names.
     * @param array $operators A list of operators ('and' or 'or').
     * @param array $parameters A list of values to match the field names.
     * @param string $operator The operator to be used.
     * @return Collection|Model|null
     * @throws RepositoryException
     */
    protected function buildQuery($resultType, array $fields, array $operators, array $parameters, $operator = '=')
    {
        $where    = [];
        $orderBy  = [];
        $limit    = null;
        $paginate = null;

        for ($i=0, $j=0; $i<sizeof($operators); $i++) {
            if (in_array($operators[$i], ['and', 'or']))
            {
                if (!isset($parameters[$j])) {
                    throw new RepositoryException("Missing parameter, check your method call.");
                }

                $where[] = [$operators[$i], $fields[$i], $operator, $parameters[$j++]];
            }

            else if (in_array($operators[$i], ['in']))
            {
                if (!isset($parameters[$j])) {
                    throw new RepositoryException("Missing parameter, check your method call.");
                }

                $where[] = [$fields[$i], $operators[$i], $parameters[$j++]];
            }

            else if (in_array($operators[$i], ['orderby']))
            {
                $orderBy[] = [$fields[$i], 'asc'];
            }

            else if (in_array($operators[$i], ['orderbydesc']))
            {
                $orderBy[] = [$fields[$i], 'desc'];
            }

            else if (in_array($operators[$i], ['limit']))
            {
                $limit = isset($parameters[$j]) ? $parameters[$j++] : $this->limit();
            }

            else if (in_array($operators[$i], ['paginated']))
            {
                $paginate = isset($parameters[$j]) ? $parameters[$j++] : $this->perPage();
            }
        }

        if ($resultType == self::RESULT_SINGLE)
        {
            return $this->createQuery($where, $orderBy, $limit)->first();
        }

        else if ($resultType == self::RESULT_ALL)
        {
            if ($paginate == null) {
                return $this->createQuery($where, $orderBy, $limit)->get();
            } else {
                return $this->createQuery($where, $orderBy, $limit)->paginate($paginate);
            }
        }

        return null;
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     * @throws RepositoryException
     */
    public function __call($method, $parameters)
    {
        if (!preg_match('/^(find)(AllBy|By)?(.*)(Limit|OrderByDesc|OrderBy|Paginated)?$/i', $method, $matches)) {
            throw new RepositoryException("Method name $method is not valid");
        }

        preg_match_all('/(Limit|OrderByDesc|OrderBy|Paginated|And|Or|In)+/', $matches[3], $parts);

        $partsSize = sizeof($parts[0]);
        if (array_search('Limit', $parts[0]) !== false) $partsSize--;
        if (array_search('Paginated', $parts[0]) !== false) $partsSize--;

        $action = $matches[1] . $matches[2];
        $fields = $this->parseFields($matches[3]);

        $operators = array_map('strtolower', ((sizeof($fields) > $partsSize) ? array_merge(['And'], $parts[0]) : $parts[0]));

        $this->debug(['action' => $action, 'fields' => $fields, 'operators' => $operators, 'parameters' => $parameters, 'matches' => $matches]);

        switch ($action) {
            case 'findAllBy':
                $results = $this->buildQuery(self::RESULT_ALL, $fields, $operators, $parameters);
                $this->resetModel();
                return $this->parserResult($results);
            case 'findBy':
                $result = $this->buildQuery(self::RESULT_SINGLE, $fields, $operators, $parameters);
                $this->resetModel();
                return $this->parserResult($result);
            default:
                throw new RepositoryException("Method name $method is not valid.");
        }
    }

    protected function debug()
    {
        if (!$this->isDebug()) return;

        array_map(function ($x) {
            (new Dumper())->dump($x);
        }, func_get_args());
    }

    protected function parseFields($fieldsStr)
    {
        $items = preg_split('/(Limit|OrderByDesc|OrderBy|Paginated|And|Or|In)/', $fieldsStr);

        $fields = array_filter(array_map(function($item) {
            return strtolower(trim(preg_replace("/(Limit|OrderByDesc|OrderBy|Paginated|And|Or|In)+/", "", preg_replace("/[A-Z]/", "_$0", $item)), '_'));
        }, $items), function($item) {
            return ($item != null && strlen($item) > 0);
        });

        return array_merge([], $fields);
    }
}