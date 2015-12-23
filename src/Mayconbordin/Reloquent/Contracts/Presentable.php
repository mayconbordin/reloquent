<?php namespace Mayconbordin\Reloquent\Contracts;

/**
 * Interface Presentable
 * @package Mayconbordin\Reloquent\Contracts
 * @author Anderson Andrade <@andersao>
 */
interface Presentable
{
    /**
     * @param PresenterInterface $presenter
     * @return mixed
     */
    public function setPresenter(PresenterInterface $presenter);

    /**
     * @return mixed
     */
    public function presenter();
}