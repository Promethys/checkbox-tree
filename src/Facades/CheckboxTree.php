<?php

namespace Promethys\CheckboxTree\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Promethys\CheckboxTree\CheckboxTree
 */
class CheckboxTree extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Promethys\CheckboxTree\CheckboxTree::class;
    }
}
