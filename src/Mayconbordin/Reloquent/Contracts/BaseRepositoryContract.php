<?php namespace Mayconbordin\Reloquent\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Mayconbordin\Reloquent\Exceptions\NotFoundError;
use Mayconbordin\Reloquent\Exceptions\ServerError;

interface BaseRepositoryContract {
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';

    /**
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes);

    /**
     * @param array $attributes
     * @param $id
     * @return Model
     */
    public function update(array $attributes, $id);

    /**
     * Verifies if the record with the given id exists.
     *
     * @param mixed $id
     * @return boolean
     */
    public function exists($id);

    /**
     * Returns the number of existing records.
     * @return int
     */
    public function count();

    /**
     * Deletes a single record with given id. If the relations of the model have been declared, they will also be deleted.
     * The whole operation will take place within a transaction.
     *
     * @param mixed $id
     * @return void
     * @throws NotFoundError In case the record with the given id doesn't exists
     * @throws ServerError In case the operation fails
     */
    public function delete($id);

    /**
     * Deletes all records in the list of ids.
     *
     * @param $ids
     * @return mixed
     */
    public function destroy(array $ids);

    /**
     * Find data by id.
     *
     * @param mixed $id
     * @param array $columns
     * @return Model
     * @throws NotFoundError
     */
    public function find($id, $columns = array('*'));

    /**
     * Find data by field and value
     *
     * @param string $field
     * @param mixed  $value
     * @param string $operator
     * @param array  $columns
     * @return Model
     *  @throws NotFoundError
     */
    public function findByField($field, $value = null, $operator = '=', $columns = array('*'));

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     * @return Model
     *  @throws NotFoundError
     */
    public function findWhere(array $where, $columns = array('*'));

    /**
     * Get all the existing records.
     *
     * @param array $columns
     * @param array|string $orderBy
     * @param array|string $with
     * @return Collection
     */
    public function all($orderBy = null, $with = null, $columns = array('*'));

    /**
     * Find data by field and value
     *
     * @param string $field
     * @param mixed $value
     * @param string $operator
     * @param string|array $orderBy
     * @param string|array $with
     * @param int $limit
     * @param array $columns
     * @return Collection
     */
    public function findAllByField($field, $value = null, $operator = '=', $orderBy = null, $with = null, $limit = null, $columns = array('*'));

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param string|array $orderBy
     * @param string|array $with
     * @param int $limit
     * @param array $columns
     * @return Collection
     */
    public function findAllWhere(array $where, $orderBy = null, $with = null, $limit = null, $columns = array('*'));

    /**
     * Find data by field and value with pagination.
     *
     * @param string $field
     * @param mixed $value
     * @param string $operator
     * @param int $perPage
     * @param string|array $orderBy
     * @param string|array $with
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function findAllByFieldPaginated($field, $value = null, $operator = '=', $perPage = 15, $orderBy = null, $with = null, $columns = array('*'));

    /**
     * Find data by multiple fields with pagination.
     *
     * @param array $where
     * @param int $perPage
     * @param string|array $orderBy
     * @param string|array $with
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function findAllWherePaginated(array $where, $perPage = 15, $orderBy = null, $with = null, $columns = array('*'));

    /**
     * Retrieve all data of repository, paginated.
     *
     * @param int $perPage
     * @param string|array $orderBy
     * @param string|array $with
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate($perPage = 15, $orderBy = null, $with = null, $columns = array('*'));

    /**
     * @param $observer
     * @return mixed
     */
    public function observe($observer);

    /**
     * Validates an associative array of attributes against a set o validation rules declared in the model class.
     *
     * @param array $data
     * @param string $action If the validation is for a create or update action.
     * @param null|array $rules The list of fields to be validated, anything else is ignored. Or the rules itself, if the $custom parameter is true.
     * @param bool $custom If true, will use the $rules parameter to validate the data attributes instead of the rules from the model.
     * @return \Illuminate\Validation\Validator
     */
    public function validate(array $data, $action = self::ACTION_CREATE, $rules = null, $custom = false);
}