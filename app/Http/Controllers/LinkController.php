<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str; // For generating random strings
use Illuminate\Support\Facades\DB; // For database interaction (you might use Eloquent models directly)
use Carbon\Carbon; // For handling timestamps

class LinkController extends Controller
{
    /**
     * Handles the creation of a new shortened URL.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function shorten(Request $request)
    {

        header("Access-Control-Allow-Origin: *"); // or specify your domain
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN");
        // Validate the incoming request
        $request->validate([
            'original_url' => 'required|url|max:2048', // Ensure it's a valid URL and not too long
        ]);

        $originalUrl = $request->input('original_url');
        $shortCode = $this->generateUniqueShortCode();

        try {
            // Insert the new shortened link into the database
            // In a real Laravel app, you would typically use an Eloquent model (e.g., Link::create([...]))
            DB::table('shorten_url')->insert([
                'short_code' => $shortCode,
                'original_url' => $originalUrl,
                'created_at' => Carbon::now(),
                // 'expires_at' => Carbon::now()->addDays(30), // Example: Link expires in 30 days
                'clicks' => 0,
            ]);

            // Construct the full shortened URL
            // Using asset() or url() helper is better for generating full URLs
            $shortenedUrl = url('redirect/'.$shortCode); // This will generate the full URL like http://yourdomain.com/shortCode

            return response()->json([
                'shortened_url' => $shortenedUrl,
                'original_url' => $originalUrl
            ], 201); // 201 Created

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error shortening URL: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to shorten URL'], 500);
        }
    }

    /**
     * Redirects the user from a short code to its original URL.
     *
     * @param  string  $shortCode
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function redirect($shortCode)
    {
        
        // Find the original URL in the database using the short code
        // Again, an Eloquent model (e.g., Link::where('short_code', $shortCode)->first()) is preferred
        $link = DB::table('shorten_url')
                    ->where('short_code', $shortCode)
                    ->first();

        // Check if the link exists and is not expired (if expires_at is used)
        if (!$link) {
            return response()->json(['error' => 'Short URL not found'], 404);
        }

        // Optional: Check expiration
        // if ($link->expires_at && Carbon::parse($link->expires_at)->isPast()) {
        //     return response()->json(['error' => 'Short URL has expired'], 410); // 410 Gone
        // }

        try {
            // Increment the click count
            DB::table('shorten_url')
                ->where('id', $link->id)
                ->increment('clicks');

            // Redirect to the original URL
            // Using 302 Found (temporary redirect) as it's common for shorteners
            // You could use 301 Moved Permanently if the mapping is truly fixed
            return redirect()->away($link->original_url);

        } catch (\Exception $e) {
            \Log::error('Error redirecting URL or updating clicks: ' . $e->getMessage());
            // Even if click update fails, try to redirect
            return redirect()->away($link->original_url);
        }
    }

    /**
     * Generates a unique short code for the URL.
     *
     * @param int $length
     * @return string
     */
    protected function generateUniqueShortCode(int $length = 7): string
    {
        $maxAttempts = 10; // Max attempts to find a unique code
        for ($i = 0; $i < $maxAttempts; $i++) {
            $shortCode = Str::random($length); // Laravel's helper for random string

            // Check if the short code already exists in the database
            $exists = DB::table('shorten_url')
                        ->where('short_code', $shortCode)
                        ->exists();

            if (!$exists) {
                return $shortCode;
            }
        }

        // If after max attempts, a unique code isn't found, throw an error
        throw new \Exception("Could not generate a unique short code after multiple attempts.");
    }
}
