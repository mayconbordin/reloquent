<?php namespace Mayconbordin\Reloquent\Presenter;

use Exception;
use Mayconbordin\Reloquent\Transformer\ModelTransformer;

/**
 * Class ModelFractalPresenter
 * @package Mayconbordin\Reloquent\Presenter
 * @author Anderson Andrade <@andersao>
 */
class ModelFractalPresenter extends FractalPresenter
{
    /**
     * Transformer
     *
     * @return ModelTransformer
     * @throws Exception
     */
    public function getTransformer()
    {
        if (!class_exists('League\Fractal\Manager')) {
            throw new Exception("Package required. Please install: 'composer require league/fractal' (0.12.*)");
        }

        return new ModelTransformer();
    }
}