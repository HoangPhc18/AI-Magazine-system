<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WebsiteConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class WebsiteConfigController extends Controller
{
    protected WebsiteConfigService $configService;

    public function __construct(WebsiteConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Show the general settings form
     *
     * @return \Illuminate\View\View
     */
    public function showGeneralForm()
    {
        $settings = $this->configService->getByGroup(WebsiteConfigService::GROUP_GENERAL);
        return view('admin.website-config.general', [
            'settings' => $settings
        ]);
    }

    /**
     * Update general settings
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_description' => 'required|string|max:1000',
            'site_email' => 'required|email|max:255',
            'site_phone' => 'required|string|max:20',
            'site_address' => 'required|string|max:500',
            'logo' => 'nullable|image|max:2048',
            'favicon' => 'nullable|image|max:1024',
        ]);

        // Process uploads if present
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('public/uploads/website');
            $validated['logo'] = Storage::url($logoPath);
        } else {
            // Keep existing logo if no new upload
            $currentSettings = $this->configService->getByGroup(WebsiteConfigService::GROUP_GENERAL);
            $validated['logo'] = $currentSettings['logo'] ?? null;
        }

        if ($request->hasFile('favicon')) {
            $faviconPath = $request->file('favicon')->store('public/uploads/website');
            $validated['favicon'] = Storage::url($faviconPath);
        } else {
            // Keep existing favicon if no new upload
            $currentSettings = $this->configService->getByGroup(WebsiteConfigService::GROUP_GENERAL);
            $validated['favicon'] = $currentSettings['favicon'] ?? null;
        }

        $this->configService->updateGroup(
            WebsiteConfigService::GROUP_GENERAL,
            $validated
        );

        return redirect()->route('admin.website-config.general')
            ->with('success', 'Thông tin chung của website đã được cập nhật thành công!');
    }

    /**
     * Show the SEO settings form
     *
     * @return \Illuminate\View\View
     */
    public function showSeoForm()
    {
        $settings = $this->configService->getByGroup(WebsiteConfigService::GROUP_SEO);
        return view('admin.website-config.seo', [
            'settings' => $settings
        ]);
    }

    /**
     * Update SEO settings
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSeo(Request $request)
    {
        $validated = $request->validate([
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string|max:1000',
            'meta_keywords' => 'required|string|max:1000',
            'robots_txt' => 'nullable|string',
            'generate_sitemap' => 'boolean',
            'google_analytics_id' => 'nullable|string|max:50',
            'facebook_app_id' => 'nullable|string|max:100',
            'twitter_card_type' => 'nullable|string|in:summary,summary_large_image,app,player',
            'twitter_username' => 'nullable|string|max:50',
            'disable_indexing' => 'boolean',
        ]);

        // Convert checkbox values to boolean
        $validated['generate_sitemap'] = $request->has('generate_sitemap');
        $validated['disable_indexing'] = $request->has('disable_indexing');

        $this->configService->updateGroup(
            WebsiteConfigService::GROUP_SEO,
            $validated
        );

        if ($request->has('generate_robots')) {
            // Generate robots.txt file
            $robotsTxt = $validated['robots_txt'] ?? "User-agent: *\nAllow: /";
            $filePath = public_path('robots.txt');
            file_put_contents($filePath, $robotsTxt);
        }

        if ($request->has('generate_sitemap') && $validated['generate_sitemap']) {
            // Logic to generate sitemap would go here
            // In a real implementation, we would create a sitemap.xml file with all the site's URLs
        }

        return redirect()->route('admin.website-config.seo')
            ->with('success', 'Cấu hình SEO đã được cập nhật thành công!');
    }

    /**
     * Show the social media settings form
     *
     * @return \Illuminate\View\View
     */
    public function showSocialForm()
    {
        $settings = $this->configService->getByGroup(WebsiteConfigService::GROUP_SOCIAL);
        return view('admin.website-config.social', [
            'settings' => $settings
        ]);
    }

    /**
     * Update social media settings
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSocial(Request $request)
    {
        $validated = $request->validate([
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'pinterest_url' => 'nullable|url|max:255',
        ]);

        $this->configService->updateGroup(
            WebsiteConfigService::GROUP_SOCIAL,
            $validated
        );

        return redirect()->route('admin.website-config.social')
            ->with('success', 'Cấu hình mạng xã hội đã được cập nhật thành công!');
    }

    /**
     * Show the UI settings form
     *
     * @return \Illuminate\View\View
     */
    public function showUiForm()
    {
        $settings = $this->configService->getByGroup(WebsiteConfigService::GROUP_UI);
        return view('admin.website-config.ui', [
            'settings' => $settings
        ]);
    }

    /**
     * Update UI settings
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateUi(Request $request)
    {
        $validated = $request->validate([
            'primary_color' => 'required|string|max:20',
            'secondary_color' => 'required|string|max:20',
            'accent_color' => 'nullable|string|max:20',
            'text_color' => 'required|string|max:20',
            'heading_font' => 'required|string|max:100',
            'body_font' => 'required|string|max:100',
            'show_search_bar' => 'boolean',
            'show_back_to_top' => 'boolean',
            'articles_per_page' => 'required|integer|min:4|max:60',
            'featured_articles_count' => 'required|integer|min:1|max:12',
        ]);

        // Convert checkbox values to boolean
        $validated['show_search_bar'] = $request->has('show_search_bar');
        $validated['show_back_to_top'] = $request->has('show_back_to_top');

        $this->configService->updateGroup(
            WebsiteConfigService::GROUP_UI,
            $validated
        );

        // Generate CSS file if needed
        if ($request->has('generate_css')) {
            $this->generateCustomCss($validated);
        }

        return redirect()->route('admin.website-config.ui')
            ->with('success', 'Cấu hình giao diện đã được cập nhật thành công!');
    }

    /**
     * Show the metadata settings form
     *
     * @return \Illuminate\View\View
     */
    public function showMetadataForm()
    {
        $settings = $this->configService->getByGroup(WebsiteConfigService::GROUP_METADATA);
        return view('admin.website-config.metadata', [
            'settings' => $settings
        ]);
    }

    /**
     * Update metadata settings
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateMetadata(Request $request)
    {
        $validated = $request->validate([
            'head_title_format' => 'required|string|max:255',
            'head_separator' => 'required|string|max:20',
            'default_og_image' => 'nullable|image|max:2048',
            'charset' => 'required|string|max:20',
            'viewport' => 'required|string|max:100',
            'author' => 'nullable|string|max:100',
            'copyright' => 'nullable|string|max:255',
            'extra_meta_tags' => 'nullable|string',
            'custom_head_code' => 'nullable|string',
        ]);

        // Process uploads if present
        if ($request->hasFile('default_og_image')) {
            $imagePath = $request->file('default_og_image')->store('public/uploads/website');
            $validated['default_og_image'] = Storage::url($imagePath);
        } else {
            // Keep existing image if no new upload
            $currentSettings = $this->configService->getByGroup(WebsiteConfigService::GROUP_METADATA);
            $validated['default_og_image'] = $currentSettings['default_og_image'] ?? null;
        }

        $this->configService->updateGroup(
            WebsiteConfigService::GROUP_METADATA,
            $validated
        );

        return redirect()->route('admin.website-config.metadata')
            ->with('success', 'Cấu hình metadata đã được cập nhật thành công!');
    }

    /**
     * Generate custom CSS file
     *
     * @param array $uiSettings
     * @return void
     */
    private function generateCustomCss(array $uiSettings): void
    {
        $cssContent = ":root {\n";
        $cssContent .= "  --primary-color: " . ($uiSettings['primary_color'] ?? '#0ea5e9') . ";\n";
        $cssContent .= "  --secondary-color: " . ($uiSettings['secondary_color'] ?? '#64748b') . ";\n";
        $cssContent .= "  --accent-color: " . ($uiSettings['accent_color'] ?? '#f97316') . ";\n";
        $cssContent .= "  --text-color: " . ($uiSettings['text_color'] ?? '#1e293b') . ";\n";
        $cssContent .= "  --heading-font: " . ($uiSettings['heading_font'] ?? "'Plus Jakarta Sans', sans-serif") . ";\n";
        $cssContent .= "  --body-font: " . ($uiSettings['body_font'] ?? "'Plus Jakarta Sans', sans-serif") . ";\n";
        $cssContent .= "}\n\n";
        
        $cssContent .= "body {\n";
        $cssContent .= "  font-family: var(--body-font);\n";
        $cssContent .= "  color: var(--text-color);\n";
        $cssContent .= "}\n\n";
        
        $cssContent .= "h1, h2, h3, h4, h5, h6 {\n";
        $cssContent .= "  font-family: var(--heading-font);\n";
        $cssContent .= "}\n\n";
        
        $cssContent .= ".text-primary { color: var(--primary-color); }\n";
        $cssContent .= ".text-secondary { color: var(--secondary-color); }\n";
        $cssContent .= ".text-accent { color: var(--accent-color); }\n\n";
        
        $cssContent .= ".bg-primary { background-color: var(--primary-color); }\n";
        $cssContent .= ".bg-secondary { background-color: var(--secondary-color); }\n";
        $cssContent .= ".bg-accent { background-color: var(--accent-color); }\n";

        // Ensure directory exists
        $directory = public_path('css');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Save CSS file
        File::put(public_path('css/custom-variables.css'), $cssContent);
    }
} 