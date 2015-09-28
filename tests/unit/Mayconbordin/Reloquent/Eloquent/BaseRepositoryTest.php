<?php

use Illuminate\Database\Eloquent\Model;
use Mayconbordin\Reloquent\Exceptions\RepositoryException;
use Mayconbordin\Reloquent\Eloquent\BaseRepository;
use Mayconbordin\Reloquent\Exceptions\NotFoundError;
use Codeception\TestCase\Test;
use Mockery as m;
use AspectMock\Test as t;

use Illuminate\Support\Facades\DB;

class TestRepository extends BaseRepository {
    public function model()
    {
        return 'App\Models\TestModel';
    }

    public function name()
    {
        return 'Test';
    }
}

class TestModel extends Model {
    public static $rules = [
        'name' => 'required'
    ];
}

class BaseRepositoryTest extends Test
{
    protected $app;

    /**
     * @var BaseRepository
     */
    protected $repository;

    /**
     * @var \Mockery\MockInterface
     */
    protected $model;

    /**
     * @var \Mockery\MockInterface
     */
    protected $validator;

    protected $modelCls = 'App\Models\TestModel';

    public function _before()
    {
        Illuminate\Support\Facades\Config::shouldReceive('get')->with('reloquent.pagination.per_page', m::any())->andReturn(15);
        Illuminate\Support\Facades\Config::shouldReceive('get')->with('reloquent.limit', m::any())->andReturn(20);
        Illuminate\Support\Facades\Config::shouldReceive('get')->with('reloquent.debug', m::any())->andReturn(false);
        Illuminate\Support\Facades\Lang::shouldReceive('get')->with(m::any(), m::any())->andReturn('');

        $this->model     = m::mock('\TestModel');
        $this->validator = m::mock('Illuminate\Validation\Factory');

        $this->app = m::mock('Illuminate\Container\Container');
        $this->app->shouldReceive('make')->with($this->modelCls)->andReturn($this->model);
        $this->app->shouldReceive('make')->with('validator')->andReturn($this->validator);

        $this->repository = new TestRepository($this->app); //m::mock(new TestRepository($this->app))->makePartial();
    }

    public function testCreate()
    {
        $attributes = ['name' => 'test'];

        $validation = m::mock('Illuminate\Validation\Validator');
        $validation->shouldReceive('fails')->once()->andReturn(false);

        $this->validator->shouldReceive('make')->once()->with($attributes, TestModel::$rules)->andReturn($validation);

        $this->model->shouldReceive('newInstance')->once()->with($attributes)->andReturn($this->model);
        $this->model->shouldReceive('save')->once();

        $this->repository->create($attributes);
    }

    public function testFindByTypeId()
    {
        $typeId = 1;

        $result = m::mock('\TestModel');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->with('type_id', '=', $typeId)->once()->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn($result);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findByTypeId($typeId);
    }

    public function testFindByName()
    {
        $name = "test";

        $result = m::mock('\TestModel');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->with('name', '=', $name)->once()->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn($result);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findByName($name);
    }

    public function testFindByNameWith()
    {
        $name = "test";

        $result = m::mock('\TestModel');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('with')->with(['childs', 'parent'])->once()->andReturnSelf();
        $query->shouldReceive('where')->with('name', '=', $name)->once()->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn($result);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findByNameWith($name, ['childs', 'parent']);
    }

    public function testFindByNameAndDescription()
    {
        $name = "test";
        $desc = "test_description";

        $result = m::mock('\TestModel');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->with('name', '=', $name)->once()->andReturnSelf();
        $query->shouldReceive('where')->with('description', '=', $desc)->once()->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn($result);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findByNameAndDescription($name, $desc);
    }

    public function testFindByNameAndDescriptionWith()
    {
        $name = "test";
        $desc = "test_description";

        $result = m::mock('\TestModel');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('with')->with(['childs', 'parent'])->once()->andReturnSelf();
        $query->shouldReceive('where')->with('name', '=', $name)->once()->andReturnSelf();
        $query->shouldReceive('where')->with('description', '=', $desc)->once()->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn($result);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findByNameAndDescriptionWith($name, $desc, ['childs', 'parent']);
    }

    public function testFindByNameOrDescription()
    {
        $name = "test";
        $desc = "test_description";

        $result = m::mock('\TestModel');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->with('name', '=', $name)->once()->andReturnSelf();
        $query->shouldReceive('orWhere')->with('description', '=', $desc)->once()->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn($result);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findByNameOrDescription($name, $desc);
    }

    public function testFindAllByTypeIdOrTypeId()
    {
        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->with('type_id', '=', 1)->once()->andReturnSelf();
        $query->shouldReceive('orWhere')->with('type_id', '=', 2)->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($results);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findAllByTypeIdOrTypeId(1, 2);
    }

    public function testFindAllByTypeOrderBy()
    {
        $type = "test";

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->with('type', '=', $type)->once()->andReturnSelf();
        $query->shouldReceive('orderBy')->with('name', 'asc')->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($results);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findAllByTypeOrderByName($type);
    }

    public function testFindAllByTypeWith()
    {
        $type = "test";

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('with')->with(['childs'])->once()->andReturnSelf();
        $query->shouldReceive('where')->with('type', '=', $type)->once()->andReturnSelf();
        //$query->shouldReceive('orderBy')->with('name', 'asc')->once();
        $query->shouldReceive('get')->once()->andReturn($results);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);

        $this->repository->findAllByTypeWith($type, ['childs']);
    }

    public function testFindAllByTypeOrderByWith()
    {
        $type = "test";

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('with')->with(['childs'])->once()->andReturnSelf();
        $query->shouldReceive('where')->with('type', '=', $type)->once()->andReturnSelf();
        $query->shouldReceive('orderBy')->with('name', 'asc')->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($results);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);

        $this->repository->findAllByTypeOrderByNameWith($type, ['childs']);
    }

    public function testFindAllByTypeOrderByLimitWith()
    {
        $type = "test";

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('with')->with(['childs'])->once()->andReturnSelf();
        $query->shouldReceive('where')->with('type', '=', $type)->once()->andReturnSelf();
        $query->shouldReceive('orderBy')->with('name', 'asc')->once()->andReturnSelf();
        $query->shouldReceive('limit')->with(10)->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($results);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);

        $this->repository->findAllByTypeOrderByNameLimit_With($type, 10, ['childs']);
    }

    public function testFindAllByTypeOrderByLimit()
    {
        $type = "test";

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->with('type', '=', $type)->once()->andReturnSelf();
        $query->shouldReceive('orderBy')->with('name', 'asc')->once()->andReturnSelf();
        $query->shouldReceive('limit')->with(20)->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($results);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findAllByTypeOrderByNameLimit($type, 20);
    }

    public function testFindAllByTypeAndFlagOrderByLimit()
    {
        $type = "test";
        $flag = 1;

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->with('type', '=', $type)->once()->andReturnSelf();
        $query->shouldReceive('where')->with('flag', '=', $flag)->once()->andReturnSelf();
        $query->shouldReceive('orderBy')->with('name', 'asc')->once()->andReturnSelf();
        $query->shouldReceive('limit')->with(20)->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($results);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findAllByTypeAndFlagOrderByNameLimit($type, $flag, 20);
    }

    public function testFindAllByTypeOrderByDescLimit()
    {
        $type = "test";

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->with('type', '=', $type)->once()->andReturnSelf();
        $query->shouldReceive('orderBy')->with('name', 'desc')->once()->andReturnSelf();
        $query->shouldReceive('limit')->with(20)->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($results);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findAllByTypeOrderByDescNameLimit($type, 20);
    }

    public function testFindAllByTypeOrFlagOrderByLimit()
    {
        $type = "test";
        $flag = 1;

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->with('type', '=', $type)->once()->andReturnSelf();
        $query->shouldReceive('orWhere')->with('flag', '=', $flag)->once()->andReturnSelf();
        $query->shouldReceive('orderBy')->with('name', 'asc')->once()->andReturnSelf();
        $query->shouldReceive('limit')->with(20)->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($results);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findAllByTypeOrFlagOrderByNameLimit($type, $flag, 20);
    }

    public function testFindAllByTypeOrFlagOrderByLimitMissing()
    {
        $type = "test";
        $flag = 1;

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->with('type', '=', $type)->once()->andReturnSelf();
        $query->shouldReceive('orWhere')->with('flag', '=', $flag)->once()->andReturnSelf();
        $query->shouldReceive('orderBy')->with('name', 'asc')->once()->andReturnSelf();
        $query->shouldReceive('limit')->with($this->repository->limit())->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($results);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);

        $this->repository->findAllByTypeOrFlagOrderByNameLimit($type, $flag);
    }

    public function testFindAllByTypeMissingArgument()
    {
        try {
            $this->repository->findAllByType();
            $this->fail("Should have thrown exption for missing argument");
        } catch (RepositoryException $e) {}
    }

    public function testFindAllByTypeOrderByDescPaginated()
    {
        $type = "test";
        $perPage = 20;

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->with('type', '=', $type)->once()->andReturnSelf();
        $query->shouldReceive('orderBy')->with('name', 'desc')->once()->andReturnSelf();
        $query->shouldReceive('paginate')->once()->with($perPage)->andReturn($results);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findAllByTypeOrderByDescNamePaginated($type, $perPage);
    }

    public function testFindAllByTypeOrderByDescPaginatedMissing()
    {
        $type = "test";

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->with('type', '=', $type)->once()->andReturnSelf();
        $query->shouldReceive('orderBy')->with('name', 'desc')->once()->andReturnSelf();
        $query->shouldReceive('paginate')->once()->with($this->repository->perPage())->andReturn($results);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findAllByTypeOrderByDescNamePaginated($type);
    }

    public function testFindAllInType()
    {
        $types = [1, 2, 3, 4];

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('whereIn')->with('type_id', $types)->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($results);

        $this->model->shouldReceive('newQuery')->once()->andReturn($query);


        $this->repository->findAllByInTypeId($types);
    }

    public function testUpdate()
    {
        $id = 1;
        $attributes = ['name' => 'test'];

        $validation = m::mock('Illuminate\Validation\Validator');
        $validation->shouldReceive('fails')->once()->andReturn(false);

        $this->validator->shouldReceive('make')->once()->with($attributes, TestModel::$rules)->andReturn($validation);

        $this->model->shouldReceive('find')->once()->with($id, array('*'))->andReturn($this->model);
        $this->model->shouldReceive('fill')->once()->with($attributes);
        $this->model->shouldReceive('save')->once();

        $this->repository->update($attributes, $id);
    }

    public function testAll()
    {
        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->all();

        $this->assertEquals($results, $r);
    }

    public function testAllOrderBy()
    {
        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('orderBy')->once()->with('name', 'desc')->andReturn($this->model);
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->all('name:desc');

        $this->assertEquals($results, $r);
    }

    public function testAllOrderByWith()
    {
        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('with')->once()->with(['childs', 'parent'])->andReturnSelf();
        $this->model->shouldReceive('orderBy')->once()->with('name', 'desc')->andReturnSelf();
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->all('name:desc', ['childs', 'parent']);

        $this->assertEquals($results, $r);
    }

    public function testAllOrderByAlt()
    {
        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('orderBy')->once()->with('name', 'desc')->andReturn($this->model);
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->all(['name:desc']);

        $this->assertEquals($results, $r);
    }

    public function testAllOrderByMultipleFields()
    {
        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('orderBy')->once()->with('name', 'desc')->andReturn($this->model);
        $this->model->shouldReceive('orderBy')->once()->with('date', 'asc')->andReturn($this->model);
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->all(['name:desc', 'date']);

        $this->assertEquals($results, $r);
    }

    public function testFindAllByField()
    {
        $name = 'test';

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('where')->once()->with('name', '=', $name)->andReturnSelf();
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->findAllByField('name', $name);

        $this->assertEquals($results, $r);
    }

    public function testFindAllByFieldOrderBy()
    {
        $name = 'test';

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('orderBy')->once()->with('name', 'desc')->andReturnSelf();
        $this->model->shouldReceive('where')->once()->with('name', '=', $name)->andReturnSelf();
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->findAllByField('name', $name, '=', 'name:desc');

        $this->assertEquals($results, $r);
    }

    public function testFindAllByFieldWith()
    {
        $name = 'test';

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('with')->once()->with(['childs', 'parent'])->andReturnSelf();
        $this->model->shouldReceive('where')->once()->with('name', '=', $name)->andReturnSelf();
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->findAllByField('name', $name, '=', null, ['childs', 'parent']);

        $this->assertEquals($results, $r);
    }

    public function testFindAllByFieldOrderByWith()
    {
        $name = 'test';

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('with')->once()->with(['childs', 'parent'])->andReturnSelf();
        $this->model->shouldReceive('orderBy')->once()->with('name', 'desc')->andReturnSelf();
        $this->model->shouldReceive('where')->once()->with('name', '=', $name)->andReturnSelf();
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->findAllByField('name', $name, '=', 'name:desc', ['childs', 'parent']);

        $this->assertEquals($results, $r);
    }

    public function testFindAllByFieldOrderByWithLimit()
    {
        $name = 'test';

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('with')->once()->with(['childs', 'parent'])->andReturnSelf();
        $this->model->shouldReceive('orderBy')->once()->with('name', 'desc')->andReturnSelf();
        $this->model->shouldReceive('where')->once()->with('name', '=', $name)->andReturnSelf();
        $this->model->shouldReceive('limit')->once()->with(10)->andReturnSelf();
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->findAllByField('name', $name, '=', 'name:desc', ['childs', 'parent'], 10);

        $this->assertEquals($results, $r);
    }

    public function testFindAllWhere()
    {
        $name = 'test';

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('newQuery')->once()->andReturnSelf();
        $this->model->shouldReceive('where')->once()->with('name', '=', $name)->andReturnSelf();
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->findAllWhere(['name' => $name]);

        $this->assertEquals($results, $r);
    }

    public function testFindAllWhereOrderBy()
    {
        $name = 'test';

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('newQuery')->once()->andReturnSelf();
        $this->model->shouldReceive('where')->once()->with('name', '=', $name)->andReturnSelf();
        $this->model->shouldReceive('orderBy')->once()->with('name', 'desc')->andReturnSelf();
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->findAllWhere(['name' => $name], 'name:desc');

        $this->assertEquals($results, $r);
    }

    public function testFindAllWhereOrderByWith()
    {
        $name = 'test';

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('newQuery')->once()->andReturnSelf();
        $this->model->shouldReceive('with')->once()->with(['parent'])->andReturnSelf();
        $this->model->shouldReceive('where')->once()->with('name', '=', $name)->andReturnSelf();
        $this->model->shouldReceive('orderBy')->once()->with('name', 'desc')->andReturnSelf();
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->findAllWhere(['name' => $name], 'name:desc', ['parent']);

        $this->assertEquals($results, $r);
    }

    public function testFindAllWhereOrderByWithLimit()
    {
        $name = 'test';

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('newQuery')->once()->andReturnSelf();
        $this->model->shouldReceive('with')->once()->with(['parent'])->andReturnSelf();
        $this->model->shouldReceive('where')->once()->with('name', '=', $name)->andReturnSelf();
        $this->model->shouldReceive('orderBy')->once()->with('name', 'desc')->andReturnSelf();
        $this->model->shouldReceive('limit')->once()->with(10)->andReturnSelf();
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->findAllWhere(['name' => $name], 'name:desc', ['parent'], 10);

        $this->assertEquals($results, $r);
    }

    public function testFindAllWhereMultiple()
    {
        $name = 'test';
        $typeId = 2;

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('newQuery')->once()->andReturnSelf();
        $this->model->shouldReceive('where')->once()->with('name', '=', $name)->andReturnSelf();
        $this->model->shouldReceive('where')->once()->with('type_id', '=', $typeId)->andReturnSelf();
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->findAllWhere(['name' => $name, 'type_id' => $typeId]);

        $this->assertEquals($results, $r);
    }

    public function testFindAllWhereComplex()
    {
        $name = 'test';
        $typeId = 2;

        $results = m::mock('Illuminate\Database\Eloquent\Collection');

        $this->model->shouldReceive('newQuery')->once()->andReturnSelf();
        $this->model->shouldReceive('where')->once()->with('name', '!=', $name)->andReturnSelf();
        $this->model->shouldReceive('orWhere')->once()->with('type_id', '=', $typeId)->andReturnSelf();
        $this->model->shouldReceive('get')->once()->with(['*'])->andReturn($results);

        $r = $this->repository->findAllWhere(['name' => ['!=', $name], 'type_id' => ['or', 'type_id', '=', $typeId]]);

        $this->assertEquals($results, $r);
    }

    public function testExists()
    {
        DB::shouldReceive('table')->with('test')->andReturnSelf();
        DB::shouldReceive('where')->with('id', 1)->andReturnSelf();
        DB::shouldReceive('first')->andReturn($this->model);

        $this->model->shouldReceive('getTable')->once()->andReturn('test');
        $this->model->shouldReceive('getKeyName')->once()->andReturn('id');

        $result = $this->repository->exists(1);
        $this->assertTrue($result);
    }

    public function testFind()
    {
        $this->model->shouldReceive('find')->once()->with(1, ['*'])->andReturn($this->model);

        $result = $this->repository->find(1);

        $this->assertEquals($this->model, $result);
    }

    public function testFindWith()
    {
        $this->model->shouldReceive('find')->once()->with(1, ['*'])->andReturnSelf();
        $this->model->shouldReceive('with')->once()->with(['childs', 'parent'])->andReturnSelf();

        $result = $this->repository->find(1, ['childs', 'parent']);

        $this->assertEquals($this->model, $result);
    }

    public function testFindNotFound()
    {
        $this->model->shouldReceive('find')->once()->with(1, ['*'])->andReturn(null);

        try {
            $this->repository->find(1);
            $this->fail("Should receive exception for not found item");
        } catch (NotFoundError $e) {}
    }

    public function testFindByField()
    {
        $this->model->shouldReceive('where')->once()->with('name', '=', 'test')->andReturnSelf();
        $this->model->shouldReceive('first')->once()->with(['*'])->andReturn($this->model);
        $result = $this->repository->findByField('name', 'test');
        $this->assertEquals($this->model, $result);

        $this->model->shouldReceive('where')->once()->with('name', 'LIKE', 't%')->andReturnSelf();
        $this->model->shouldReceive('first')->once()->with(['*'])->andReturn($this->model);
        $result = $this->repository->findByField('name', 't%', 'LIKE');
        $this->assertEquals($this->model, $result);
    }

    public function testFindWhere()
    {
        $this->model->shouldReceive('where')->once()->with('name', '=', 'test')->andReturnSelf();
        $this->model->shouldReceive('first')->once()->with(['*'])->andReturn($this->model);

        $result = $this->repository->findWhere(['name' => 'test']);
        $this->assertEquals($this->model, $result);


        $this->model->shouldReceive('where')->once()->with('name', 'LIKE', 't%')->andReturnSelf();
        $this->model->shouldReceive('first')->once()->with(['*'])->andReturn($this->model);

        $result = $this->repository->findWhere(['name' => ['LIKE', 't%']]);
        $this->assertEquals($this->model, $result);


        $this->model->shouldReceive('where')->once()->with('name', '=', 'test')->andReturnSelf();
        $this->model->shouldReceive('whereIn')->once()->with('parent_id', [1, 2, 3], 'and', false)->andReturnSelf();
        $this->model->shouldReceive('first')->once()->with(['*'])->andReturn($this->model);

        $result = $this->repository->findWhere(['name' => ['test'], 'parent_id' => ['in', [1, 2, 3]]]);
        $this->assertEquals($this->model, $result);

    }

    public function testFindWhereComplex()
    {
        $this->model->shouldReceive('where')->once()->with('name', 'LIKE', 't%')->andReturnSelf();
        $this->model->shouldReceive('orWhere')->once()->with('title', 'LIKE', 'a%')->andReturnSelf();
        $this->model->shouldReceive('first')->once()->with(['*'])->andReturn($this->model);

        $result = $this->repository->findWhere(['name' => ['LIKE', 't%'], 'title' => ['or', 'LIKE', 'a%']]);
        $this->assertEquals($this->model, $result);
    }
}