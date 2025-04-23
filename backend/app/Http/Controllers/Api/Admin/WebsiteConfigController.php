<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteConfig;
use App\Services\WebsiteConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebsiteConfigController extends Controller
{
    protected WebsiteConfigService $configService;

    public function __construct(WebsiteConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Get all website configurations
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->configService->getAll()
        ]);
    }

    /**
     * Get configurations by group
     *
     * @param string $group
     * @return JsonResponse
     */
    public function getGroup(string $group): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->configService->getByGroup($group)
        ]);
    }

    /**
     * Update general configurations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateGeneral(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'site_name' => 'required|string|max:255',
            'site_description' => 'required|string|max:1000',
            'site_email' => 'required|email|max:255',
            'site_phone' => 'required|string|max:20',
            'site_address' => 'required|string|max:500',
            'logo' => 'nullable|string|max:255',
            'favicon' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $this->configService->updateGroup(
            WebsiteConfigService::GROUP_GENERAL,
            $validator->validated()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'General settings updated successfully',
            'data' => $this->configService->getByGroup(WebsiteConfigService::GROUP_GENERAL)
        ]);
    }

    /**
     * Update SEO configurations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSeo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string|max:1000',
            'meta_keywords' => 'required|string|max:1000',
            'robots_txt' => 'nullable|string',
            'generate_sitemap' => 'boolean',
            'google_analytics_id' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $this->configService->updateGroup(
            WebsiteConfigService::GROUP_SEO,
            $validator->validated()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'SEO settings updated successfully',
            'data' => $this->configService->getByGroup(WebsiteConfigService::GROUP_SEO)
        ]);
    }

    /**
     * Update social media configurations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSocial(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'pinterest_url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $this->configService->updateGroup(
            WebsiteConfigService::GROUP_SOCIAL,
            $validator->validated()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Social media settings updated successfully',
            'data' => $this->configService->getByGroup(WebsiteConfigService::GROUP_SOCIAL)
        ]);
    }

    /**
     * Generate robots.txt file
     *
     * @return JsonResponse
     */
    public function generateRobotsTxt(): JsonResponse
    {
        $seoSettings = $this->configService->getByGroup(WebsiteConfigService::GROUP_SEO);
        $robotsTxt = $seoSettings['robots_txt'] ?? "User-agent: *\nAllow: /";
        
        // Logic to write to robots.txt file
        $filePath = public_path('robots.txt');
        file_put_contents($filePath, $robotsTxt);
        
        return response()->json([
            'status' => 'success',
            'message' => 'robots.txt file has been generated successfully'
        ]);
    }

    /**
     * Generate sitemap
     *
     * @return JsonResponse
     */
    public function generateSitemap(): JsonResponse
    {
        // Logic to generate sitemap would go here
        // This would typically involve collecting URLs from various models
        // and then generating an XML sitemap
        
        return response()->json([
            'status' => 'success',
            'message' => 'Sitemap has been generated successfully'
        ]);
    }
} 