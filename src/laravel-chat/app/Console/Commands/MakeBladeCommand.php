<?php

namespace App\Console\Commands;

use File;
use Illuminate\Console\Command;

class MakeBladeCommand extends Command {
    protected $signature = 'make:blade {name}';
    protected $description = 'Create a new blade file';

    public function __construct() {
        parent::__construct();
    }

    public function handle() {
        $stub = File::get(app_path() . '/Console/Commands/stubs/blade.stub');
        $blade = resource_path() . '/views/' . $this->argument('name') . '.blade.php';
        File::put($blade, $stub);
    }
}
