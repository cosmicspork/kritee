<?php

namespace App\Actions\Contracts;

/**
 * The application's single write surface.
 */
interface Action
{
    public function execute(ActionInput $input): ActionResult;
}
