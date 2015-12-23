<?php namespace Mayconbordin\Reloquent\Transformer;

use League\Fractal\TransformerAbstract;
use Mayconbordin\Reloquent\Contracts\Transformable;

/**
 * Class ModelTransformer
 * @package Mayconbordin\Reloquent\Transformer
 * @author Anderson Andrade <@andersao>
 */
class ModelTransformer extends TransformerAbstract
{
    public function transform(Transformable $model)
    {
        return $model->transform();
    }
}