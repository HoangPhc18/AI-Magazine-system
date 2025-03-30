<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\AiSetting\UpdateRequest;
use App\Http\Resources\AiSettingResource;
use App\Models\AiSetting;
use Illuminate\Http\JsonResponse;

class AiSettingController extends Controller
{
    public function index(): AiSettingResource
    {
        return new AiSettingResource(AiSetting::first());
    }

    public function update(UpdateRequest $request): JsonResponse
    {
        $setting = AiSetting::first();
        $setting->update($request->validated());

        return response()->json([
            'message' => 'AI settings updated successfully',
            'settings' => new AiSettingResource($setting)
        ]);
    }
}
