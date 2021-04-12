<?php

namespace App\Services;

use App\Constants\Error;
use App\Exceptions\StoreFailException;
use App\Facades\PeopleService;
use App\Models\Point;
use Exception;
use Grimzy\LaravelMysqlSpatial\Types\Point as TypesPoint;
use Illuminate\Support\Facades\Log;

class PointService
{
    private const SRID_WGS84 = 4326;

    public function getWithPaginate($size = 15)
    {
        return Point::paginate($size);
    }

    public function getByPersonIdWithPaginate($personId, $size = 15)
    {
        return PeopleService::findOrFail($personId)->points()->paginate(15);
    }

    public function create($personId, $data)
    {
        $person = PeopleService::findOrFail($personId);

        try {
            $point = new Point();
            $point->people_id = $person->id;
            $point->datetime = $data['datetime'];
            $point->position = new TypesPoint($data['lat'], $data['long'], self::SRID_WGS84);
            $point->save();

            return $point;
        } catch (Exception $ex) {
            Log::error(__('Error :error fail to store point', [
                'error' => Error::POINT_STORE_FAIL,
            ]));

            throw new StoreFailException(__('Failed to store point'), Error::POINT_STORE_FAIL, $ex);
        }
    }

    public function findOrFail($id)
    {
        return Point::findOrFail($id);
    }

    public function getExposedByPointBuilder(Point $point)
    {
        return Point::where(function ($query) use ($point) {
            $query
                ->distance('position', $point->position, config('expose.radius'))
                ->dateTimeBetween($point->datetime, config('expose.minutes'))
                ->notId($point->id);
        });
    }

    public function getExposedByPointWithPaginate(Point $point, $size = 15)
    {
        return $this->getExposedByPointBuilder($point)->paginate($size);
    }
}