<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;

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
        $login = trim($_POST['login'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($login === '' || $password === '') {
            $this->view('auth/login', ['error' => 'Rellena usuario/email y contraseña.']);
            return;
        }

        $user = User::findByUsernameOrEmail($login);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->view('auth/login', ['error' => 'Credenciales incorrectas.']);
            return;
        }

        Auth::login($user);
        redirect('/vinyls');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
