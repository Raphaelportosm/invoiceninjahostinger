<?php

namespace App\Models;

use App\Filters\QueryFilters;
use Hashids\Hashids;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    public function __call($method, $params)
    {
        $entity = strtolower(class_basename($this));

        if ($entity) {
            $configPath = "modules.relations.$entity.$method";

            if (config()->has($configPath)) {
                $function = config()->get($configPath);

				return call_user_func_array(array($this, $function[0]), $function[1]);
            }
        }

        return parent::__call($method, $params);
    }

}
