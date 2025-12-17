<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\Builder;

class RestaurantLocationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (env('Global_SEARCH') === 'ENABLE') {
            return; // No filtering needed
        }

        $lat = Session::get('latitude');
        $lng = Session::get('longitude');

        if (!$lat || !$lng) return;

        $builder->where('is_banned', 'disable')
            ->where('admin_approval', 'enable')
            ->whereRaw("(
                6371 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )
            ) <= max_delivery_distance", [$lat, $lng, $lat]);
             
    }
}
