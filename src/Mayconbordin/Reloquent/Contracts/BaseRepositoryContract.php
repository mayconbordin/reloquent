<?php namespace Mayconbordin\Reloquent\Contracts;

use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryContract {
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
     * @param array $columns
     * @param string|null $orderCol
     * @param string $orderDir
     * @return mixed
     */
    public function all($columns = array('*'), $orderCol = null, $orderDir = 'asc');

    /**
     * @param mixed $id
     * @return mixed
     */
    public function exists($id);

    /**
     * Find data by id
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
     * @return mixed
     */
    public function findByField($field, $value = null, $operator = '=', $columns = array('*'));

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     * @return mixed
     */
    public function findWhere(array $where, $columns = array('*'));

    /**
     * Find data by field and value
     *
     * @param string $field
     * @param mixed $value
     * @param string $operator
     * @param array $columns
     * @return mixed
     */
    public function findAllByField($field, $value = null, $operator = '=', $columns = array('*'));

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     * @return mixed
     */
    public function findAllWhere(array $where , $columns = array('*'));

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
    public function findAllByFieldPaginated($field, $value = null, $operator = '=', $perPage = 15, $columns = array('*'));

    /**
     * Find data by multiple fields with pagination.
     *
     * @param array $where
     * @param int   $perPage
     * @param array $columns
     * @return mixed
     */
    public function findAllWherePaginated(array $where, $perPage = 15, $columns = array('*'));

    /**
     * Retrieve all data of repository, paginated
     * @param int $limit
     * @param array $columns
     * @param string|null $orderCol
     * @param string $orderDir
     * @return mixed
     */
    public function paginate($limit = 15, $columns = array('*'), $orderCol = null, $orderDir = 'asc');

    /**
     * @return mixed
     */
    public function count();

    /**
     * @param mixed $id
     * @return mixed
     */
    public function delete($id);

    /**
     * @param $ids
     * @return mixed
     */
    public function destroy(array $ids);

    /**
     * @param $observer
     * @return mixed
     */
    public function observe($observer);

    /**
     * Load relations
     *
     * @param array|string $relations
     * @return $this
     */
    public function with($relations);

    /**
     * @param array $data
     * @param null $rules
     * @param bool $custom
     * @return \Illuminate\Validation\Validator
     */
    public function validate(array $data, $rules = null, $custom = false);
    /**
     * Get a new query that searches by attributes.
     *
     * @param  array  $where
     * @param  string $operator   Default: '='
     *
     * @return Builder
     */
    public function createQuery(array $where, $operator = '=');
}