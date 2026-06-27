<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DistrictResource;
use App\Http\Resources\RegionResource;
use App\Models\Region;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RegionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return RegionResource::collection(
            Region::orderBy('sort')->get()
        );
    }

    public function districts(Region $region): AnonymousResourceCollection
    {
        return DistrictResource::collection(
            $region->districts()->orderBy('sort')->get()
        );
    }
}
