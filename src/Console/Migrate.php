<?php

namespace Xypp\Collector\Console;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Xypp\Collector\Condition;

class Migrate extends Command
{
    /**
     * @var string
     */
    protected $signature = 'collector:migrate';

    /**
     * @var string
     */
    protected $description = 'Migrate data from forum-quest 1.x.';
    private ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }
    public function handle()
    {
        $conditions = Condition::all();

        $this->withProgressBar(
            $this->connection->table("quest_condition")->get(),
            function ($data) use ($conditions) {
                $model = $conditions->where("name", $data->name)->where("user_id", $data->user_id)->first();
                if (!$model) {
                    $model = new Condition();
                    $model->name = $data->name;
                    $model->user_id = $data->user_id;
                }
                $model->accumulation = $data->accumulation;
                $model->updated_at = $data->updated_at;
                $model->created_at = $data->created_at;
                $model->value = $data->value;
                $model->save();
            }
        );
    }
}