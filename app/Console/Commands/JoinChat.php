<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Service\UserService;
use App\Models\User;
use App\Models\Group;

class JoinChat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:join_chat {username}';

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
        $username = $this->argument('username');
        $objUser = User::getByUsername($username);
        $arrGroups = Group::getAllGroups();
        foreach ($arrGroups as $objGroup){
            UserService::joinGroup($objUser, $objGroup);
        }
        $arrUsers = User::getAllUsers();
        foreach ($arrUsers as $objOtherUser){
            if($objOtherUser->id != $objUser->id){
                UserService::addFriend($objUser, $objOtherUser);
            }
        }
    }
}
