<?php

namespace App\Models\Scopes;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\Builder;

class ProductLocationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Check ENV to determine whether to skip location filtering
        if (env('Global_SEARCH') === 'ENABLE') {
            return; // Don't apply location filter
        }

        // Get lat/long from session
        $lat = Session::get('latitude');
        $lng = Session::get('longitude');

        // If lat/lng missing, skip filtering
        if (!$lat || !$lng) return;

        Log::info('Applying location scope with lat: ' . $lat . ' and lng: ' . $lng);

        // Apply location-based filtering
        $builder->whereHas('restaurant', function ($q) use ($lat, $lng) {
            $q->where('is_banned', 'disable')
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
        });

        Log::info('Location scope applied successfully.');

    }
}
