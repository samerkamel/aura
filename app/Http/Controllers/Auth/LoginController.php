<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Login Controller
 *
 * Handles user authentication with Vuexy UI integration
 *
 * @author GitHub Copilot
 */
class LoginController extends Controller
{
  /**
   * Show the login form
   *
   * @return \Illuminate\View\View
   */
  public function showLoginForm()
  {
    // If user is already authenticated, redirect to dashboard
    if (Auth::check()) {
      return redirect()->route('dashboard-analytics');
    }

    $pageConfigs = ['myLayout' => 'blank'];
    return view('content.authentications.auth-login-basic', ['pageConfigs' => $pageConfigs]);
  }

  /**
   * Handle login request
   *
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\RedirectResponse
   * @throws \Illuminate\Validation\ValidationException
   */
  public function login(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'email' => 'required|email',
      'password' => 'required|string|min:6',
    ]);

    if ($validator->fails()) {
      throw new ValidationException($validator);
    }

    $credentials = $request->only('email', 'password');
    $remember = $request->boolean('remember');

    if (Auth::attempt($credentials, $remember)) {
      $request->session()->regenerate();

      return redirect()->intended(route('dashboard-analytics'))
        ->with('success', 'Welcome back!');
    }

    return back()->withErrors([
      'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
  }

  /**
   * Handle logout request
   *
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function logout(Request $request)
  {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login')
      ->with('success', 'You have been logged out successfully.');
  }
}
