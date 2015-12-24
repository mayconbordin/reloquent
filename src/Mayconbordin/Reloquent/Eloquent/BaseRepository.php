<?php namespace Mayconbordin\Reloquent\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Debug\Dumper;
use Mayconbordin\Reloquent\Contracts\Presentable;
use Mayconbordin\Reloquent\Contracts\PresenterInterface;
use Mayconbordin\Reloquent\Exceptions\NotFoundError;
use Mayconbordin\Reloquent\Exceptions\RepositoryException;
use Mayconbordin\Reloquent\Exceptions\ValidationError;
use Mayconbordin\Reloquent\Exceptions\ServerError;
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
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

abstract class BaseRepository implements BaseRepositoryContract
{
    const RESULT_SINGLE = 1;
    const RESULT_ALL    = 2;

    protected static $logger;

    /**
     * The Eloquent Model
     *
     * @var Model
     */
    protected $model;

    /**
     * @var PresenterInterface
     */
    protected $presenter;

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
     * List of relation names, used when removing an object.
     * @var array
     */
    protected $relations = array();

    /**
     * @var bool
     */
    protected $skipPresenter = true;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;

        $this->makeModel();
        $this->makeValidator();
        $this->makePresenter();

        if ($this->isDebug()) {
            DB::enableQueryLog();
        }
    }

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

        return $this->parseResult($model);
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

        return $this->parseResult($model);
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

    public function destroy(array $ids)
    {
        return $this->model->destroy($ids);
    }

    public function exists($id)
    {
        $model = DB::table($this->model->getTable())->where($this->model->getKeyName(), $id)->first();
        $this->resetModel();
        return !is_null($model);
    }

    public function count()
    {
        return $this->model->count();
    }

    public function find($id, $with = null, $columns = array('*'))
    {
        $query = $this->model;
        $query = $this->applyWith($query, $with);
        $model = $query->find($id, $columns);

        if ($model == null) {
            throw new NotFoundError($this->getMessage('not_found'));
        }

        $this->resetModel();
        return $this->parseResult($model);
    }

    public function findByField($field, $value = null, $operator = '=', $with = null, $columns = array('*'))
    {
        $query = $this->model;
        $query = $this->applyWith($query, $with);
        $model = $query->where($field, $operator, $value)->first($columns);

        $this->resetModel();
        return $this->parseResult($model);
    }

    public function findWhere(array $where, $with = null, $columns = array('*'))
    {
        $query = $this->model;
        $query = $this->applyWhere($query, $where);
        $query = $this->applyWith($query, $with);
        $model = $query->first($columns);

        $this->resetModel();
        return $this->parseResult($model);
    }

    public function all($orderBy = null, $with = null, $limit = null, $columns = array('*'))
    {
        $query  = $this->model;
        $query  = $this->applyAllClauses($query, $orderBy, $with, $limit);
        $models = $query->get($columns);

        $this->resetModel();
        return $this->parseResult($models);
    }

    public function findAllByField($field, $value = null, $operator = '=', $orderBy = null, $with = null, $limit = null, $columns = array('*'))
    {
        $query  = $this->model->where($field, $operator, $value);
        $query  = $this->applyAllClauses($query, $orderBy, $with, $limit);
        $models = $query->get($columns);

        $this->resetModel();
        return $this->parseResult($models);
    }

    public function findAllWhere(array $where, $orderBy = null, $with = null, $limit = null, $columns = array('*'))
    {
        $query  = $this->createQuery($where);
        $query  = $this->applyAllClauses($query, $orderBy, $with, $limit);
        $models = $query->get($columns);

        $this->resetModel();
        return $this->parseResult($models);
    }

    public function findAllByFieldPaginated($field, $value = null, $operator = '=', $perPage = null, $orderBy = null, $with = null, $columns = array('*'))
    {
        $query  = $this->model->where($field, $operator, $value);
        $query  = $this->applyAllClauses($query, $orderBy, $with);
        $models = $query->paginate($this->perPage($perPage), $columns);

        $this->resetModel();
        return $this->parseResult($models);
    }

    public function findAllWherePaginated(array $where, $perPage = null, $orderBy = null, $with = null, $columns = array('*'))
    {
        $query  = $this->createQuery($where);
        $query  = $this->applyAllClauses($query, $orderBy, $with);
        $models = $query->paginate($this->perPage($perPage), $columns);

        $this->resetModel();
        return $this->parseResult($models);
    }

    public function paginate($perPage = null, $orderBy = null, $with = null, $columns = array('*'))
    {
        $query  = $this->model;
        $query  = $this->applyAllClauses($query, $orderBy, $with);
        $models = $query->paginate($this->perPage($perPage), $columns);

        $this->resetModel();
        return $this->parseResult($models);
    }

    public function observe($observer)
    {
        return call_user_func_array([$this->model, 'observe'], [$observer]);
    }

    /**
     * Apply all clauses to an existing query.
     *
     * @param Model|Builder $query
     * @param string|array $orderBy
     * @param string|array $with
     * @param int $limit
     * @return Builder|Model|\Illuminate\Database\Query\Builder
     */
    protected function applyAllClauses($query, $orderBy = null, $with = null, $limit = null)
    {
        $query = $this->applyOrderBy($query, $orderBy);
        $query = $this->applyWith($query, $with);
        $query = $this->applyLimit($query, $limit);
        return $query;
    }

    /**
     * Apply a with to an existing query
     *
     * @param Model|Builder $query
     * @param array|string $relations
     * @return Builder|Model
     */
    protected function applyWith($query, $relations = null)
    {
        if ($relations != null) {
            $query = $query->with($relations);
        }

        return $query;
    }

    /**
     * Apply a limit to an existing query
     *
     * @param \Illuminate\Database\Query\Builder|Builder $query
     * @param int $limit
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    protected function applyLimit($query, $limit = null)
    {
        if ($limit != null && is_numeric($limit)) {
            $query = $query->limit($limit);
        }

        return $query;
    }

    /**
     * Apply an order by to an existing query.
     *
     * @param \Illuminate\Database\Query\Builder|Builder $query
     * @param string|array $orderBy
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    protected function applyOrderBy($query, $orderBy = null)
    {
        if ($orderBy == null) return $query;

        if (is_string($orderBy)) {
            if (strlen($orderBy) == 0) return $query;
            $orderBy = [$orderBy];
        }

        if (is_array($orderBy) && sizeof($orderBy) > 0) {
            foreach ($orderBy as $value) {
                $values = explode(':', $value);

                $field = trim($values[0]);
                $order = isset($values[1]) ? trim($values[1]) : 'asc';

                if (!in_array($order, ['asc', 'desc'])) {
                    throw new \InvalidArgumentException("Valid values for orderBy are 'asc' and 'desc'");
                }

                if ($field != null) {
                    $query = $query->orderBy($field, $order);
                }
            }
        }

        return $query;
    }

    /**
     * @param \Illuminate\Database\Query\Builder|Builder $query
     * @param array $where
     * @return Builder|\Illuminate\Database\Query\Builder
     * @throws RepositoryException
     */
    protected function applyWhere($query, array $where = [])
    {
        $statements = ['and', 'or', 'in', 'orIn', 'notIn', 'orNotIn', 'between', 'orBetween', 'notBetween', 'orNotBetween'];

        foreach ($where as $field => $rawValue) {
            $operator  = '=';
            $statement = 'and';

            $isOr  = stripos($statement, 'or') !== false;
            $isNot = stripos($statement, 'not') !== false;

            if (is_array($rawValue)) {
                if (sizeof($rawValue) == 1) {
                    list($value) = $rawValue;
                } else if (sizeof($rawValue) == 2) {
                    list($operator, $value) = $rawValue;
                } else if (sizeof($rawValue) == 3) {
                    list($statement, $operator, $value) = $rawValue;
                }

                if (in_array($operator, $statements)) {
                    $statement = $operator;
                }
            } else {
                $value = $rawValue;
            }

            if ($statement == 'and') {
                $query = $query->where($field, $operator, $value);
            }

            else if ($statement == 'or') {
                $query = $query->orWhere($field, $operator, $value);
            }

            else if (in_array($statement, ['in', 'orIn', 'notIn', 'orNotIn'])) {
                $query = $query->whereIn($field, $value, $isOr ? 'or' : 'and', $isNot);
            }

            else if (in_array($statement, ['between', 'orBetween', 'notBetween', 'orNotBetween'])) {
                $query = $query->whereIn($field, $value, $isOr ? 'or' : 'and', $isNot);
            }

            else {
                throw new RepositoryException("The statement '$statement' is not supported.'");
            }
        }

        return $query;
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

    public function validate(array $data, $action = self::ACTION_CREATE, $rules = null, $custom = false)
    {
        if (!$custom) {
            $rules = $this->rules($rules, $action);
        }

        return $this->validator->make($data, $rules);
    }

    /**
     * Parse a collection of objects.
     *
     * @param Model|array|Paginator|Collection $result
     * @return mixed
     * @throws NotFoundError
     */
    public function parseResult($result)
    {
        $this->logSqlQueries();

        if ($result == null) {
            throw new NotFoundError($this->getMessage('not_found'));
        }

        if ($this->presenter instanceof PresenterInterface) {
            if ($result instanceof Collection || $result instanceof LengthAwarePaginator) {
                $result->each(function($model){
                    if ($model instanceof Presentable) {
                        $model->setPresenter($this->presenter);
                    }
                    return $model;
                });
            } elseif ($result instanceof Presentable) {
                $result = $result->setPresenter($this->presenter);
            }

            if (!$this->skipPresenter) {
                return $this->presenter->present($result);
            }
        }

        return $result;
    }

    /**
     * Transform the array of attributes for the create and update methods.
     *
     * @param array $attributes
     * @return array
     */
    public function transformAttributes(array $attributes)
    {
        return $attributes;
    }

    /**
     * Specify the class name of the Model.
     *
     * @return string
     */
    public abstract function model();

    /**
     * Specify the singular name of the model.
     *
     * @return string
     */
    public abstract function name();

    /**
     * Specify the class name of the presenter.
     *
     * @return string
     */
    public function presenter()
    {
        return null;
    }

    /**
     * Set Presenter
     *
     * @param $presenter
     * @return $this
     */
    public function setPresenter($presenter)
    {
        $this->makePresenter($presenter);
        return $this;
    }

    /**
     * Skip Presenter Wrapper
     *
     * @param bool $status
     * @return $this
     */
    public function skipPresenter($status = true)
    {
        $this->skipPresenter = $status;
        return $this;
    }

    /**
     * @return int The default limit for results
     */
    public function limit()
    {
        return Config::get('reloquent.limit', 20);
    }

    /**
     * @param int|null $perPage
     * @return int The default number of results per page
     */
    public function perPage($perPage = null)
    {
        if (!is_int($perPage)) {
            $perPage = Config::get('reloquent.pagination.per_page', 15);
        }

        return $perPage;
    }

    /**
     * @return bool If the debug options is enabled
     */
    public function isDebug()
    {
        return Config::get('reloquent.debug', true);
    }

    /**
     * Get a message string.
     *
     * @param string $key
     * @return string
     */
    protected function getMessage($key)
    {
        return Lang::get('reloquent::messages.'.$key, ['entity' => $this->name()]);
    }

    /**
     * Make and get an instance of the model.
     *
     * @return Model
     * @throws RepositoryException If the model class is not an instance of an Eloquent Model
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
     * Resets the model instance.
     *
     * @throws RepositoryException
     */
    public function resetModel()
    {
        $this->makeModel();
    }

    /**
     * Make and get an instance of the validator.
     *
     * @return Validator
     */
    protected function makeValidator()
    {
        $this->validator = $this->application->make('validator');
        return $this->validator;
    }

    /**
     * Make and get an instance of the presenter.
     *
     * @param PresenterInterface|string|null $presenter
     * @return PresenterInterface
     * @throws RepositoryException
     */
    public function makePresenter($presenter = null)
    {
        $presenter = !is_null($presenter) ? $presenter : $this->presenter();

        if (!is_null($presenter)) {
            $this->presenter = is_string($presenter) ? $this->application->make($presenter) : $presenter;

            if (!$this->presenter instanceof PresenterInterface) {
                throw new RepositoryException("Class {$presenter} must be an instance of Mayconbordin\\Reloquent\\Contractss\\PresenterInterface");
            }

            return $this->presenter;
        }

        return null;
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

    /**
     * Deletes the relations of a model.
     *
     * @param Model $model
     */
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
     * Create a query on Eloquent\QueryBuilder.
     *
     * @param array $where
     * @param array $orderBy
     * @param int|null $limit
     * @param array|null $with
     * @param string $defaultOperator
     * @return $this|Builder|static
     */
    protected function createQuery(array $where, $orderBy = [], $limit = null, $with = null, $defaultOperator = '=')
    {
        $query = $this->model->newQuery();

        if ($with != null) {
            $query = $query->with($with);
        }

        foreach ($where as $field => $value) {
            if (is_array($value) && sizeof($value) == 2) {
                list($condition, $val) = $value;

                if ($condition == 'in') {
                    $query = $query->whereIn($field, $val);
                } else {
                    $query = $query->where($field, $condition, $val);
                }
            } else if (is_array($value) && sizeof($value) == 3) {
                list($field, $condition, $val) = $value;

                if ($condition == 'in') {
                    $query = $query->whereIn($field, $val);
                } else {
                    $query = $query->where($field, $condition, $val);
                }
            } else if (is_array($value) && sizeof($value) == 4) {
                list($operator, $field, $condition, $val) = $value;

                if ($operator == 'or') {
                    $query = $query->orWhere($field, $condition, $val);
                } else if ($operator == 'and') {
                    $query = $query->where($field, $condition, $val);
                }
            } else {
                $query = $query->where($field, $defaultOperator, $value);
            }
        }

        foreach ($orderBy as $value) {
            list($field, $order) = $value;
            $query = $query->orderBy($field, $order);
        }

        if ($limit != null) {
            $query = $query->limit($limit);
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
        $with     = null;

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

            else if (in_array($operators[$i], ['with']))
            {
                $with = isset($parameters[$j]) ? $parameters[$j++] : (isset($this->relations) ? $this->relations : null);
            }
        }

        if ($resultType == self::RESULT_SINGLE)
        {
            return $this->createQuery($where, $orderBy, $limit, $with)->first();
        }

        else if ($resultType == self::RESULT_ALL)
        {
            if ($paginate == null) {
                return $this->createQuery($where, $orderBy, $limit, $with)->get();
            } else {
                return $this->createQuery($where, $orderBy, $limit, $with)->paginate($paginate);
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
        if (!preg_match('/^(find)(AllBy|By)?(.*)(Limit|OrderByDesc|OrderBy|Paginated|With)?$/i', $method, $matches)) {
            throw new RepositoryException("Method name $method is not valid");
        }

        preg_match_all('/(Limit|OrderByDesc|OrderBy|Paginated|With|And|Or|In)+/', $matches[3], $parts);

        $partsSize = sizeof($parts[0]);
        foreach (['Limit', 'Paginated', 'With'] as $op) {
            if (array_search($op, $parts[0]) !== false)
                $partsSize--;
        }

        $action = $matches[1] . $matches[2];
        $fields = $this->parseFields($matches[3]);

        $operators = array_map('strtolower', ((sizeof($fields) > $partsSize) ? array_merge(['And'], $parts[0]) : $parts[0]));

        $this->debug(['action' => $action, 'fields' => $fields, 'operators' => $operators, 'parameters' => $parameters, 'matches' => $matches, 'parts' => $parts]);

        switch ($action) {
            case 'findAllBy':
                $results = $this->buildQuery(self::RESULT_ALL, $fields, $operators, $parameters);
                $this->resetModel();
                return $this->parseResult($results);
            case 'findBy':
                $result = $this->buildQuery(self::RESULT_SINGLE, $fields, $operators, $parameters);
                $this->resetModel();
                return $this->parseResult($result);
            default:
                throw new RepositoryException("Method name $method is not valid.");
        }
    }

    /**
     * Log all executed queries into the log file.
     */
    protected function logSqlQueries()
    {
        if (!$this->isDebug()) return;

        $queries = DB::getQueryLog();

        foreach ($queries as $query) {
            $this->getLogger()->addInfo(json_encode($query));
        }
    }

    /**
     * Get the SQL logger.
     *
     * @return Logger
     */
    protected function getLogger()
    {
        if (self::$logger == null) {
            self::$logger = new Logger('Reloquent Logs');
            self::$logger->pushHandler(new StreamHandler(Config::get('reloquent.log_file'), Logger::INFO));
        }

        return self::$logger;
    }

    /**
     * Debug dumper of variables.
     */
    protected function debug()
    {
        if (!$this->isDebug()) return;

        array_map(function ($x) {
            (new Dumper())->dump($x);
        }, func_get_args());
    }

    /**
     * Parse and return the list of field names from the method name.
     *
     * @param string $fieldsStr
     * @return array
     */
    protected function parseFields($fieldsStr)
    {
        $items = preg_split('/(Limit|OrderByDesc|OrderBy|Paginated|With|And|Or|In)/', $fieldsStr);

        $fields = array_filter(array_map(function($item) {
            return strtolower(trim(preg_replace("/(Limit|OrderByDesc|OrderBy|Paginated|With|And|Or|In)+/", "", preg_replace("/[A-Z]/", "_$0", $item)), '_'));
        }, $items), function($item) {
            return ($item != null && strlen($item) > 0);
        });

        return array_merge([], $fields);
    }
}