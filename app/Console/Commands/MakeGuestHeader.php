<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

// cp -r public/images/* storage/app/public/
// php artisan storage:link
class MakeGuestHeader extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:MakeGuestHeader';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $bigJpeg = imagecreatefromjpeg(storage_path('app/public/qq_big.jpg'));
        $order = 0;
        for($i=0;$i<12;++$i){
            for($j=0;$j<9;++$j){
                ++$order;
                $smallPng = imagecreatetruecolor(100,100);
                imagecopyresized ($smallPng,$bigJpeg,0,0,$i*100,$j*100,100,100,100,100);
                $dest = storage_path('app/public/guest_header_'.$order.'.png');
                imagepng($smallPng, $dest);
            }
        }        
    }
}
