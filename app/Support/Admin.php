<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * ADMIN_EMAIL / ADMIN_PASSWORD are always the one login. Called on deploy
 * (app:ensure-admin) and again on every sign-in attempt, so it works even if
 * the host skips the start command.
 */
class Admin
{
    public static function configured(): bool
    {
        return trim((string) config('app.admin.email')) !== '' && (string) config('app.admin.password') !== '';
    }

    /**
     * Why a sign-in failed, without any secret: lengths and shapes only.
     * Written to the log so a mismatch with the Railway variables shows up.
     */
    public static function explainFailure(string $typedEmail, string $typedPassword): array
    {
        $email = strtolower(trim((string) config('app.admin.email')));
        $pw = (string) config('app.admin.password');

        return [
            'email_matches_ADMIN_EMAIL' => $typedEmail === $email,
            'typed_password_length' => mb_strlen($typedPassword),
            'ADMIN_PASSWORD_length' => mb_strlen($pw),
            'ADMIN_PASSWORD_has_outer_spaces' => $pw !== trim($pw),
            'ADMIN_PASSWORD_wrapped_in_quotes' => (bool) preg_match('/^(["\']).*\1$/s', $pw),
        ];
    }

    /** @return string what happened, for logs */
    public static function sync(): string
    {
        $email = strtolower(trim((string) config('app.admin.email')));
        $password = (string) config('app.admin.password');

        if ($email === '' || $password === '') {
            return 'ADMIN_EMAIL / ADMIN_PASSWORD not set; nobody can sign in.';
        }

        $user = User::whereRaw('lower(email) = ?', [$email])->first();

        if (! $user) {
            $user = User::create(['name' => 'Admin', 'email' => $email, 'password' => Hash::make($password)]);
            $msg = "Login created for {$email}.";
        } elseif (! Hash::check($password, $user->password)) {
            $user->update(['password' => Hash::make($password)]);
            $msg = "Password updated for {$email}.";
        } else {
            $msg = "Login for {$email} is up to date.";
        }

        // Single-user app: drop accounts left over from an earlier ADMIN_EMAIL.
        User::whereKeyNot($user->id)->delete();

        return $msg;
    }
}
