<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
class LpaAuthController extends Controller {
 public function login(){ return view('lpa.login'); }
 public function authenticate(Request $r){
  $credentials=$r->validate(['email'=>['required','email'],'password'=>['required','string']]);
  $key='lpa-login:'.strtolower($credentials['email']).'|'.$r->ip();
  if(RateLimiter::tooManyAttempts($key,5)) throw ValidationException::withMessages(['email'=>'Demasiados intentos. Intenta más tarde.']);
  if(!Auth::attempt($credentials)){RateLimiter::hit($key,60);throw ValidationException::withMessages(['email'=>'Credenciales incorrectas.']);}
  RateLimiter::clear($key);$r->session()->regenerate();return redirect()->intended(route('lpa.dashboard'));
 }
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('login');}
}
