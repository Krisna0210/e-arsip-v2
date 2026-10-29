<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class Login extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;
    public $showPassword = false;

    public function authenticate()
    {
        $this->validate([
            'email' => 'required',
            'password' => 'required',
        ], [
            'email.required' => 'Email Karyawan wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $throttleKey = Str::transliterate(Str::lower($this->email).'|'.request()->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.");
            return;
        }

        $isEmail = filter_var($this->email, FILTER_VALIDATE_EMAIL);
        $credentials = $isEmail
            ? ['email' => $this->email, 'password' => $this->password, 'status' => 'active']
            : ['username' => $this->email, 'password' => $this->password, 'status' => 'active'];

        if (Auth::attempt($credentials, $this->remember)) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($throttleKey);

        $field = $isEmail ? 'email' : 'username';
        $user = User::where($field, $this->email)->first();
        if ($user && $user->status === 'inactive' && Hash::check($this->password, $user->password)) {
            $this->addError('email', 'Akun Anda dinonaktifkan oleh administrator. Silakan hubungi pengelola sistem.');
            return;
        }

        $this->addError('email', 'Kredensial yang diberikan tidak cocok dengan data kami.');
    }

    public function togglePassword()
    {
        $this->showPassword = !$this->showPassword;
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
