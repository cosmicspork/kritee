<?php

namespace App\Actions\Contracts;

/**
 * The application's single write surface.
 *
 * Every mutation is an action that owns its unit of work — open the
 * transaction, call services, persist, dispatch events, return a result — and
 * is callable identically from HTTP, CLI, queued jobs, and (later) AI tools.
 * See docs/ARCHITECTURE.md.
 */
interface Action
{
    public function execute(ActionInput $input): ActionResult;
}
