<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect($provider)
    {
        if (!in_array($provider, ['google'])) {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        if (!in_array($provider, ['google'])) {
            abort(404);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
            
            // Check if user already exists with this social provider
            $user = User::where('google_id', $socialUser->getId())->first();
            
            if ($user) {
                // User exists with this Google ID, log them in
                Auth::login($user);
                return redirect()->intended('/dashboard');
            }
            
            // Check if user exists with this email
            $existingUser = User::where('email', $socialUser->getEmail())->first();
            
            if ($existingUser) {
                // User exists with this email but no Google ID, link the accounts
                $existingUser->update([
                    'google_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
                
                Auth::login($existingUser);
                return redirect()->intended('/dashboard');
            }
            
            // Create new user
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'google_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
                'password' => null, // No password for social login users
                'email_verified_at' => now(), // Auto-verify email for social login
            ]);
            
            Auth::login($user);
            return redirect()->intended('/dashboard');
            
        } catch (\Exception $e) {
            return redirect('/login')->withErrors([
                'email' => 'Something went wrong with ' . ucfirst($provider) . ' authentication.',
            ]);
        }
    }
}