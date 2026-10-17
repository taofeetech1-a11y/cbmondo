<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('voters:secure-photos')]
#[Description('Move voter photos from public storage to authenticated private storage')]
class SecureVoterPhotos extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $count = 0;

        foreach ($public->allFiles('voters') as $path) {
            $stream = $public->readStream($path);
            if ($stream === false) {
                $this->error('Unable to read voter photo: '.$path);

                return self::FAILURE;
            }

            try {
                if (! $private->put($path, $stream)) {
                    $this->error('Unable to secure voter photo: '.$path);

                    return self::FAILURE;
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (! $public->delete($path)) {
                $this->error('Private copy saved, but public photo could not be removed: '.$path);

                return self::FAILURE;
            }
            $count++;
        }

        $this->info($count.' voter photos moved to private storage.');

        return self::SUCCESS;
    }
}
