<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class LiveLocationController extends Controller
{
    public function show(Request $request): Response
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'label' => ['nullable', 'string', 'max:100'],
        ]);

        $label = $validated['label'] ?? 'Live Location';
        $timestamp = now()->format('Y-m-d H:i:s T');
        $mapsUrl = $this->buildGoogleMapsUrl($validated['lat'], $validated['lng']);
        $thumbnailUrl = URL::route('live-location.thumbnail', [
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
            'label' => $label,
            'ts' => now()->timestamp,
        ]);

        return response()->view('live-location', [
            'label' => $label,
            'timestamp' => $timestamp,
            'mapsUrl' => $mapsUrl,
            'thumbnailUrl' => $thumbnailUrl,
            'coordinates' => sprintf('%0.6f, %0.6f', $validated['lat'], $validated['lng']),
        ]);
    }

    public function thumbnail(Request $request): Response
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'label' => ['nullable', 'string', 'max:100'],
            'ts' => ['nullable'],
        ]);

        $label = $validated['label'] ?? 'Live Location';
        $timestamp = now()->format('Y-m-d H:i:s T');
        $coordinates = sprintf('%0.6f, %0.6f', $validated['lat'], $validated['lng']);
        $escapedLabel = e(Str::limit($label, 40));
        $escapedCoordinates = e($coordinates);
        $escapedTimestamp = e($timestamp);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#0f172a" />
      <stop offset="100%" stop-color="#1e3a8a" />
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#bg)" />
  <circle cx="180" cy="210" r="90" fill="#38bdf8" opacity="0.2" />
  <circle cx="180" cy="210" r="50" fill="#38bdf8" />
  <circle cx="180" cy="210" r="12" fill="#0f172a" />
  <text x="320" y="210" font-size="60" font-family="Arial, sans-serif" fill="#ffffff" font-weight="700">{$escapedLabel}</text>
  <text x="320" y="300" font-size="36" font-family="Arial, sans-serif" fill="#cbd5f5">{$escapedCoordinates}</text>
  <text x="320" y="380" font-size="30" font-family="Arial, sans-serif" fill="#94a3b8">Updated {$escapedTimestamp}</text>
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    private function buildGoogleMapsUrl(float $lat, float $lng): string
    {
        return sprintf('https://www.google.com/maps?q=%s,%s', $lat, $lng);
    }
}
