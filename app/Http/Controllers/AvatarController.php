<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rule;

class AvatarController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'avatar' => [
                'required',
                File::image()
                    ->max(2 * 1024) // 2MB max
                    ->dimensions(Rule::dimensions()
                        ->maxWidth(2000)
                        ->maxHeight(2000)
                    ),
            ],
        ]);

        $user = Auth::user();

        // Delete old avatar if exists
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');

        // Update user record
        $user->update([
            'avatar_path' => $path,
        ]);

        return back()->with('status', 'Avatar updated successfully!');
    }

    public function destroy()
    {
        $user = Auth::user();

        if ($user->avatar_path) {
            // Delete file from storage
            Storage::disk('public')->delete($user->avatar_path);

            // Clear avatar_path from database
            $user->update([
                'avatar_path' => null,
            ]);
        }

        return back()->with('status', 'Avatar removed successfully!');
    }
}