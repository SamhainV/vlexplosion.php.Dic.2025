<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;
use App\Core\Input;
use App\Core\LoginLimiter;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/vinyls');
        }
        $this->view('auth/login', ['error' => null]);
    }

    public function login(): void
    {
        $login = Input::text($_POST['login'] ?? null, 254);
        $password = Input::password($_POST['password'] ?? '');

        if ($login === '' || $password === '') {
            $this->view('auth/login', ['error' => 'Rellena usuario/email y contraseña.']);
            return;
        }

        $limiter = LoginLimiter::application();
        if (!$limiter->attempt($login, (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'))) {
            header('Retry-After: 900');
            throw new \App\Core\HttpException(429);
        }
        $user = User::findByUsernameOrEmail($login);
        // Synthetic bcrypt hash also makes unknown accounts perform password verification.
        $hash = $user['password'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $validPassword = password_verify($password, $hash);

        if (!$user || !$validPassword) {
            $this->view('auth/login', ['error' => 'Credenciales incorrectas.']);
            return;
        }

        $limiter->success($login);
        Auth::login($user);
        redirect('/vinyls');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
