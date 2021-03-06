<?php

namespace Klsandbox\BackupManagement\Console\Commands;

use Klsandbox\BackupManagement\Models\BackupRun;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class BackupStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'backup:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will run https://github.com/spatie/laravel-backup backup:run';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->comment('Backup process is started. Please wait until the process is finished.');

        global $file;

        $create = BackupRun::create([
            'output_text' => 'Backup is started',
        ]);

        \Artisan::call('backup:run', []);

        $path = \Config::get('laravel-backup.destination.path');

        $output = \Artisan::output();
        preg_match('~' . $path . '/[\w\.]+' . '~', $output, $matches);
        $file = $matches[0];

        $disk = \Storage::disk(config('backup-management.backup_filesystem'));

        $update = BackupRun::findById($create->id);
        $update->path_to_backup = $file;
        $update->file_size = $disk->size($file);
        $update->output_text = $output;
        $update->is_completed = 1;
        $update->save();

        $this->comment('Backup process is completed & saved to database.');
        $this->info('File path is ' . $file);
    }

    protected function getOptions()
    {
        return [
            ['ondemand', null, InputOption::VALUE_OPTIONAL, '(optional)', null],
        ];
    }
}
