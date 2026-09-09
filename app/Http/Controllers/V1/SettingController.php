<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use App\Services\CreditScoreService;
use App\Traits\ActivityLogTrait;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Setting Index', only: ['index', 'show', 'list']),
            new Middleware('permission:Setting Update', only: ['update']),
        ];
    }

    /**
     * Display all settings.
     */
    public function index()
    {
        try {
            $settings = Setting::orderBy('group')->orderBy('key')->get();

            return response()->json([
                'status'  => 'success',
                'message' => 'Settings retrieved successfully',
                'data'    => $settings,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve settings',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a flat key => value map of all settings, cast to their type.
     */
    public function list()
    {
        try {
            $settings = Setting::pluck('key')->mapWithKeys(function ($key) {
                return [$key => Setting::get($key)];
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Settings list retrieved successfully',
                'data'    => $settings,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve settings list',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified setting.
     */
    public function show(string $key)
    {
        try {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Setting not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Setting retrieved successfully',
                'data'    => $setting,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve setting',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified setting's value.
     */
    public function update(UpdateSettingRequest $request, string $key)
    {
        try {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Setting not found',
                ], 404);
            }

            $value = $request->validated()['value'];
            $setting->update(['value' => Setting::serialize($value)]);
            Cache::forget("setting:{$key}");

            // CreditScoreService is a singleton and memoises its settings
            // snapshot, so anything it scored later in this same request would
            // otherwise still be using the value that was just replaced.
            app(CreditScoreService::class)->forgetConfig();

            $this->logActivity('UPDATE', 'Setting', "Updated setting: {$key}", [
                'key'   => $key,
                'value' => $value,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Setting updated successfully',
                'data'    => $setting->fresh(),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update setting',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
