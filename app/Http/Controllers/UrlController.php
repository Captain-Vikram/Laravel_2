<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Url;
use App\Models\UrlStat;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UrlController extends Controller
{
    /**
     * Display the URL shortening form.
     */
    public function index()
    {
        // Get the latest URLs (show to everyone or only to auth users)
        $urls = Url::latest()->take(10)->get();
        
        // Or if you want to show only to authenticated users:
        // $urls = auth()->check() ? auth()->user()->urls()->latest()->get() : collect();
        
        return view('urls.index', compact('urls'));
    }

    /**
     * Store a newly created shortened URL in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'original_url' => 'required|url',
            'custom_code' => 'nullable|alpha_num|min:3|max:15|unique:urls,short_code',
            'expiration' => 'nullable|integer|min:1',
        ]);

        // Generate a short code (use custom if provided)
        $shortCode = $validatedData['custom_code'] ?? $this->generateUniqueShortCode();

        // Handle expiration date if provided
        $expiresAt = null;
        if (!empty($validatedData['expiration'])) {
            $expiresAt = Carbon::now()->addDays($validatedData['expiration']);
        }

        $url = Url::create([
            'original_url' => $validatedData['original_url'],
            'short_code' => $shortCode,
            'expires_at' => $expiresAt,
        ]);

        return redirect()->route('urls.index')
            ->with('success', 'URL shortened successfully')
            ->with('shortCode', $url->short_code);  // Just the code, not a URL object
    }

    /**
     * Redirect to the original URL.
     */
    public function redirect($shortCode)
    {
        try {
            $url = Url::where('short_code', $shortCode)
                ->where(function($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', Carbon::now());
                })
                ->firstOrFail();

            // Track this visit
            UrlStat::create([
                'url_id' => $url->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referrer' => request()->header('referer'),
                'country' => $this->getCountryFromIP(request()->ip()),
                'device_type' => $this->getDeviceType(request()->userAgent())
            ]);

            // Increment the click count
            $url->increment('clicks');

            return redirect()->away($url->original_url);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('urls.index')->with('error', 'This link is invalid or has expired.');
        }
    }

    private function getCountryFromIP($ip)
    {
        // Simple placeholder - in production you might use a geolocation service
        return 'Unknown';
    }

    private function getDeviceType($userAgent)
    {
        // Simple detection - in production you'd use a more sophisticated method
        if (str_contains(strtolower($userAgent), 'mobile')) {
            return 'Mobile';
        } elseif (str_contains(strtolower($userAgent), 'tablet')) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }

    /**
     * Show statistics for a shortened URL.
     */
    public function stats($shortCode)
    {
        try {
            $url = Url::where('short_code', $shortCode)->firstOrFail();
            return view('urls.stats', compact('url'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('urls.index')->with('error', 'URL not found.');
        }
    }

    /**
     * API endpoint for shortening URLs
     */
    public function apiShorten(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'custom_code' => 'nullable|alpha_num|min:3|max:15|unique:urls,short_code',
        ]);

        $shortCode = $request->custom_code ?? $this->generateUniqueShortCode();
        
        $expiresAt = null;
        if ($request->expiration) {
            $expiresAt = Carbon::now()->addDays($request->expiration);
        }

        $url = Url::create([
            'original_url' => $request->url,
            'short_code' => $shortCode,
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'success' => true,
            'short_url' => url($shortCode),
            'original_url' => $url->original_url,
            'short_code' => $shortCode,
            'expires_at' => $expiresAt
        ]);
    }

    /**
     * Get URL information by code
     */
    public function getUrl(Request $request, $code)
    {
        $url = Url::where('short_code', $code)->first();
        
        if (!$url) {
            return response()->json(['error' => 'URL not found'], 404);
        }

        return response()->json([
            'short_code' => $url->short_code,
            'original_url' => $url->original_url,
            'clicks' => $url->clicks,
            'created_at' => $url->created_at,
            'expires_at' => $url->expires_at
        ]);
    }

    /**
     * Get statistics for a URL
     */
    public function getUrlStats(Request $request, $code)
    {
        $url = Url::where('short_code', $code)->first();
        
        if (!$url) {
            return response()->json(['error' => 'URL not found'], 404);
        }
        
        $stats = UrlStat::where('url_id', $url->id)->get();
        $clickCount = $stats->count();
        
        return response()->json([
            'short_code' => $url->short_code,
            'original_url' => $url->original_url,
            'clicks' => $clickCount,
            'created_at' => $url->created_at,
            'stats' => $stats
        ]);
    }

    /**
     * Delete a shortened URL
     */
    public function deleteUrl(Request $request, $code)
    {
        $url = Url::where('short_code', $code)->first();
        
        if (!$url) {
            return response()->json(['error' => 'URL not found'], 404);
        }
        
        // Delete related stats first to avoid foreign key constraints
        UrlStat::where('url_id', $url->id)->delete();
        $url->delete();
        
        return response()->json(['message' => 'URL deleted successfully']);
    }

    /**
     * Generate a unique short code
     */
    private function generateUniqueShortCode($length = 6)
    {
        do {
            $shortCode = Str::random($length);
            $exists = Url::where('short_code', $shortCode)->exists();
        } while ($exists);

        return $shortCode;
    }

    /**
     * Display the user's dashboard with their URLs.
     */
    public function dashboard()
    {
        $urls = Url::where('user_id', auth()->id())->latest()->paginate(10);
        return view('dashboard', compact('urls'));
    }

    /**
     * Delete a shortened URL
     */
    public function destroy($code)
    {
        $url = Url::where('short_code', $code)->firstOrFail();
        $url->delete();
        return redirect()->back()->with('success', 'URL deleted successfully');
    }
}
