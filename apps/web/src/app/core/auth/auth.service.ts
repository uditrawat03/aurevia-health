import { HttpClient } from '@angular/common/http';
import { inject, Injectable, signal } from '@angular/core';
import { Observable, switchMap } from 'rxjs';
import { GraphqlClient } from '../graphql/graphql-client';

export interface OrganizationMembership {
  readonly organizationId: string;
  readonly role: 'OWNER' | 'ADMIN' | 'CLINICIAN' | 'STAFF' | 'VIEWER';
  readonly status: 'ACTIVE' | 'REVOKED';
  readonly allFacilities: boolean;
  readonly facilityIds: readonly string[];
}

export interface AuthenticatedUser {
  readonly id: string;
  readonly name: string;
  readonly email: string;
  readonly memberships: readonly OrganizationMembership[];
}

interface LoginData {
  readonly login: AuthenticatedUser;
}

interface LoginVariables {
  readonly input: {
    readonly email: string;
    readonly password: string;
  };
}

interface CurrentUserData {
  readonly me: AuthenticatedUser;
}

interface LogoutData {
  readonly logout: {
    readonly loggedOut: boolean;
  };
}

const LOGIN_MUTATION = `
  mutation Login($input: LoginInput!) {
    login(input: $input) {
      id
      name
      email
      memberships {
        organizationId
        role
        status
        allFacilities
        facilityIds
      }
    }
  }
`;

const CURRENT_USER_QUERY = `
  query CurrentUser {
    me {
      id
      name
      email
      memberships {
        organizationId
        role
        status
        allFacilities
        facilityIds
      }
    }
  }
`;

const LOGOUT_MUTATION = `
  mutation Logout {
    logout {
      loggedOut
    }
  }
`;

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly graphql = inject(GraphqlClient);
  private readonly userState = signal<AuthenticatedUser | null>(null);
  private readonly loadingState = signal(false);
  private readonly errorState = signal<string | null>(null);

  readonly user = this.userState.asReadonly();
  readonly loading = this.loadingState.asReadonly();
  readonly error = this.errorState.asReadonly();

  restore(): void {
    this.loadingState.set(true);
    this.prepareCsrf()
      .pipe(
        switchMap(() =>
          this.graphql.execute<CurrentUserData, Record<string, never>>(CURRENT_USER_QUERY, {}),
        ),
      )
      .subscribe({
        next: ({ me }) => {
          this.userState.set(me);
          this.errorState.set(null);
          this.loadingState.set(false);
        },
        error: () => {
          this.userState.set(null);
          this.errorState.set(null);
          this.loadingState.set(false);
        },
      });
  }

  login(email: string, password: string): void {
    this.loadingState.set(true);
    this.errorState.set(null);

    this.prepareCsrf()
      .pipe(
        switchMap(() =>
          this.graphql.execute<LoginData, LoginVariables>(LOGIN_MUTATION, {
            input: { email, password },
          }),
        ),
      )
      .subscribe({
        next: ({ login }) => {
          this.userState.set(login);
          this.loadingState.set(false);
        },
        error: () => {
          this.userState.set(null);
          this.errorState.set('Sign-in failed. Check your credentials and try again.');
          this.loadingState.set(false);
        },
      });
  }

  logout(): void {
    this.loadingState.set(true);
    this.errorState.set(null);

    this.prepareCsrf()
      .pipe(
        switchMap(() =>
          this.graphql.execute<LogoutData, Record<string, never>>(LOGOUT_MUTATION, {}),
        ),
      )
      .subscribe({
        next: ({ logout }) => {
          if (logout.loggedOut) {
            this.userState.set(null);
          }
          this.loadingState.set(false);
        },
        error: () => {
          this.errorState.set('Sign-out failed. Retry before leaving this workstation.');
          this.loadingState.set(false);
        },
      });
  }

  private prepareCsrf(): Observable<string> {
    return this.http.get('/sanctum/csrf-cookie', {
      responseType: 'text',
      withCredentials: true,
    });
  }
}
