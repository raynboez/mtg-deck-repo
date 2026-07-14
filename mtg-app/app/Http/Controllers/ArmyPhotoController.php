<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Army;
use App\Models\ArmyPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ArmyPhotoController extends Controller
{
    /**
     * Get photos for an army
     */
    public function index(Army $army): JsonResponse
    {
        try {
            $photos = $army->photos()
                ->orderBy('is_primary', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
                
            $resp= $photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'army_id' => $photo->army_id,
                        'user_id' => $photo->user_id,
                        'photo_url' => $photo->photo_url,
                        'is_primary' => $photo->is_primary,
                        'created_at' => $photo->created_at->toISOString(),
                    ];
                });

            return response()->json(['photos' => $resp]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch photos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload a photo - Local Storage Version
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'army_id' => 'required|exists:armies,army_id',
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $army = Army::findOrFail($request->army_id);

            // Check authorization
            if ($army->user_id !== auth()->id() && !auth()->user()->is_admin) {
                return response()->json([
                    'error' => 'Not authorized to upload photos for this army'
                ], 403);
            }

            $file = $request->file('photo');
            
            // Generate a unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            // Store the file using the army_photos disk
            $path = $file->storeAs('', $filename, 'army_photos');
            
            // Generate the full URL
            $photoUrl = Storage::disk('army_photos')->url($filename);

            // Check if this is the first photo
            $photoCount = $army->photos()->count();
            $isPrimary = $photoCount === 0;

            // Save to database
            $photo = ArmyPhoto::create([
                'army_id' => $army->army_id,
                'user_id' => $army->user_id,
                'photo_url' => $photoUrl,
                'is_primary' => $isPrimary,
            ]);

            return response()->json([
                'message' => 'Photo uploaded successfully',
                'photo' => [
                    'id' => $photo->id,
                    'army_id' => $photo->army_id,
                    'user_id' => $photo->user_id,
                    'photo_url' => $photo->photo_url,
                    'is_primary' => $photo->is_primary,
                    'created_at' => $photo->created_at->toISOString(),
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to upload photo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a photo
     */
    public function destroy(ArmyPhoto $photo): JsonResponse
    {
        try {
            $army = $photo->army;

            // Check authorization
            if ($army->user_id !== auth()->id() && 
                $photo->user_id !== auth()->id() && 
                !auth()->user()->is_admin) {
                return response()->json([
                    'error' => 'Not authorized to delete this photo'
                ], 403);
            }

            // Extract filename from URL
            $filename = basename($photo->photo_url);
            
            // Delete file from storage
            if (Storage::disk('army_photos')->exists($filename)) {
                Storage::disk('army_photos')->delete($filename);
            }

            // Delete from database
            $photo->delete();

            // If deleted photo was primary, set another as primary
            if ($photo->is_primary) {
                $newPrimary = $army->photos()
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($newPrimary) {
                    $newPrimary->update(['is_primary' => true]);
                }
            }

            return response()->json([
                'message' => 'Photo deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete photo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set a photo as primary
     */
    public function setPrimary(ArmyPhoto $photo): JsonResponse
    {
        try {
            $army = $photo->army;

            // Check authorization
            if ($army->user_id !== auth()->id() && !auth()->user()->is_admin) {
                return response()->json([
                    'error' => 'Not authorized to set primary photo'
                ], 403);
            }

            // Remove primary from all photos for this army
            $army->photos()->update(['is_primary' => false]);

            // Set this photo as primary
            $photo->update(['is_primary' => true]);

            return response()->json([
                'message' => 'Primary photo updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to set primary photo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get photo by ID (for viewing single photo)
     */
    public function show(ArmyPhoto $photo): JsonResponse
    {
        try {
            return response()->json([
                'photo' => [
                    'id' => $photo->id,
                    'army_id' => $photo->army_id,
                    'user_id' => $photo->user_id,
                    'photo_url' => $photo->photo_url,
                    'is_primary' => $photo->is_primary,
                    'created_at' => $photo->created_at->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get photo',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
}