<?php namespace Mayconbordin\Reloquent\Presenter;

use Exception;
use Illuminate\Support\Facades\Config;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Container\Container as Application;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\SerializerAbstract;
use Mayconbordin\Reloquent\Contracts\PresenterInterface;

/**
 * Class FractalPresenter
 * @package Mayconbordin\Reloquent\Presenter
 * @author Anderson Andrade <@andersao>
 */
abstract class FractalPresenter implements PresenterInterface
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var string
     */
    protected $resourceKeyItem = null;

    /**
     * @var string
     */
    protected $resourceKeyCollection = null;

    /**
     * @var \League\Fractal\Manager
     */
    protected $fractal = null;

    /**
     * @var \League\Fractal\Resource\Collection
     */
    protected $resource = null;

    /**
     * @param Application $application
     * @throws Exception
     */
    public function __construct(Application $application)
    {
        $this->application = $application;

        if (!class_exists('League\Fractal\Manager') ){
            throw new Exception("Package required. Please install: 'composer require league/fractal' (0.12.*)");
        }

        $this->fractal  = new Manager();
        $this->parseIncludes();
        $this->setupSerializer();
    }

    /**
     * @return $this
     */
    protected function setupSerializer()
    {
        $serializer = $this->serializer();

        if ($serializer instanceof SerializerAbstract ) {
            $this->fractal->setSerializer(new $serializer());
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function parseIncludes()
    {
        /*$request        = $this->application->make('Illuminate\Http\Request');
        $paramIncludes  = Config::get('reloquent.fractal.params.include', 'include');

        if ($request->has($paramIncludes)) {
            $this->fractal->parseIncludes($request->get($paramIncludes));
        }*/

        return $this;
    }

    /**
     * Get Serializer
     *
     * @return SerializerAbstract
     */
    public function serializer()
    {
        $serializer = Config::get('reloquent.fractal.serializer', \League\Fractal\Serializer\DataArraySerializer::class);
        return new $serializer();
    }

    /**
     * Transformer
     *
     * @return \League\Fractal\TransformerAbstract
     */
    abstract public function getTransformer();

    /**
     * Prepare data to present
     *
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function present($data)
    {
        if (!class_exists('League\Fractal\Manager') ){
            throw new Exception("Package required. Please install: 'composer require league/fractal' (0.12.*)");
        }

        if ($data instanceof EloquentCollection) {
            $this->resource = $this->transformCollection($data);
        } elseif ($data instanceof AbstractPaginator) {
            $this->resource = $this->transformPaginator($data);
        } else {
            $this->resource = $this->transformItem($data);
        }

        return $this->fractal->createData($this->resource)->toArray();
    }

    /**
     * @param $data
     * @return Item
     */
    protected function transformItem($data)
    {
        return new Item($data, $this->getTransformer(), $this->resourceKeyItem);
    }

    /**
     * @param $data
     * @return \League\Fractal\Resource\Collection
     */
    protected function transformCollection($data)
    {
        return new Collection($data, $this->getTransformer(), $this->resourceKeyCollection);
    }

    /**
     * @param AbstractPaginator|LengthAwarePaginator|Paginator $paginator
     * @return \League\Fractal\Resource\Collection
     */
    protected function transformPaginator($paginator)
    {
        $collection = $paginator->getCollection();
        $resource = new Collection($collection, $this->getTransformer(), $this->resourceKeyCollection);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        return $resource;
    }
}