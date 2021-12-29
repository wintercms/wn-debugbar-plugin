<?php namespace Winter\Debugbar\Collectors;

use Winter\Storm\Database\Model;
use Illuminate\Contracts\Events\Dispatcher;
use Barryvdh\Debugbar\DataCollector\ModelsCollector as BaseModelsCollector;

class ModelsCollector extends BaseModelsCollector
{
    public $models = [];
    public $count = 0;

    /**
     * @param Dispatcher $events
     */
    public function __construct(Dispatcher $events)
    {
        Model::extend(function ($model) {
            $model->bindEvent('model.afterFetch', function () use ($model) {
                $class = get_class($model);
                $this->models[$class] = ($this->models[$class] ?? 0) + 1;
                $this->count++;
            });
        });
    }
}
