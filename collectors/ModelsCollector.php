<?php namespace Winter\Debugbar\Collectors;

use DebugBar\DataCollector\ObjectCountCollector;
use Winter\Storm\Database\Model;

class ModelsCollector extends ObjectCountCollector
{
    public function __construct()
    {
        parent::__construct('models');

        Model::extend(function ($model) {
            $model->bindEvent('model.afterFetch', function () use ($model) {
                $this->countClass($model);
            });
        });
    }
}
