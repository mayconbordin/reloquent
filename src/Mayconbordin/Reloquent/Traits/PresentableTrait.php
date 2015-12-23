<?php namespace Mayconbordin\Reloquent\Traits;

use Illuminate\Support\Arr;
use Mayconbordin\Reloquent\Contracts\PresenterInterface;

/**
 * Class PresentableTrait
 * @package Mayconbordin\Reloquent\Traits
 *  * @author Anderson Andrade <@andersao>

 */
trait PresentableTrait
{
    /**
     * @var PresenterInterface
     */
    protected $presenter = null;

    /**
     * @param PresenterInterface $presenter
     * @return $this
     */
    public function setPresenter(PresenterInterface $presenter)
    {
        $this->presenter = $presenter;
        return $this;
    }

    /**
     * @return $this|mixed
     */
    public function presenter()
    {
        if($this->hasPresenter())
        {
            return $this->presenter->present($this);
        }

        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function present($key, $default = null)
    {
        if ($this->hasPresenter())
        {
            $data = $this->presenter()['data'];
            return Arr::get($data, $key, $default);
        }

        return $default;
    }

    /**
     * @return bool
     */
    protected function hasPresenter()
    {
        return isset($this->presenter) && $this->presenter instanceof PresenterInterface;
    }
}