<?php

namespace Promethys\CheckboxTree\Commands;

use Illuminate\Console\Command;

class CheckboxTreeCommand extends Command
{
    public $signature = 'checkbox-tree';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
