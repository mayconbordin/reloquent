<?php namespace Mayconbordin\Reloquent\Contracts;

/**
 * Interface PresenterInterface
 * @package Mayconbordin\Reloquent\Contracts
 * @author Anderson Andrade <@andersao>
 */
interface PresenterInterface
{
    /**
     * Prepare data to present
     *
     * @param $data
     * @return mixed
     */
    public function present($data);
}