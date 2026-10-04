<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domains\Identity\Data\AuthenticatedUserData;
use App\Domains\Identity\Data\LoginCredentialsData;
use App\Domains\Identity\Data\LogoutResultData;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LogicException;

final readonly class SessionAuthenticationService
{
    public function __construct(
        private AuthFactory $auth,
        private Request $request,
        private OrganizationAuthorizationService $authorization,
    ) {}

    public function login(LoginCredentialsData $credentials): AuthenticatedUserData
    {
        $guard = $this->statefulGuard();
        $authenticated = $guard->attempt([
            'email' => mb_strtolower(trim($credentials->email)),
            'password' => $credentials->password,
        ]);

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'credentials' => ['The provided credentials are invalid.'],
            ]);
        }

        $this->request->session()->regenerate();

        return $this->authorization->currentUser();
    }

    public function logout(): LogoutResultData
    {
        $guard = $this->statefulGuard();
        $guard->logout();
        $this->request->session()->invalidate();
        $this->request->session()->regenerateToken();

        return new LogoutResultData(loggedOut: true);
    }

    private function statefulGuard(): StatefulGuard
    {
        $guard = $this->auth->guard('web');
        if (! $guard instanceof StatefulGuard) {
            throw new LogicException('The web authentication guard must be stateful.');
        }

        return $guard;
    }
}
