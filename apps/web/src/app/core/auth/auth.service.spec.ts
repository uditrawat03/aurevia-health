import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { AuthService } from './auth.service';

describe('AuthService', () => {
  let service: AuthService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(AuthService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    http.verify();
  });

  it('establishes the Sanctum CSRF cookie before the GraphQL login mutation', () => {
    service.login('clinician@example.test', 'password');

    const csrf = http.expectOne('/sanctum/csrf-cookie');
    expect(csrf.request.withCredentials).toBe(true);
    csrf.flush('');

    const login = http.expectOne('/graphql');
    expect(login.request.withCredentials).toBe(true);
    expect(login.request.body.query).toContain('mutation Login');
    expect(login.request.body.variables).toEqual({
      input: {
        email: 'clinician@example.test',
        password: 'password',
      },
    });
    login.flush({
      data: {
        login: {
          id: '1',
          name: 'Synthetic Clinician',
          email: 'clinician@example.test',
          memberships: [],
        },
      },
    });

    expect(service.user()?.email).toBe('clinician@example.test');
    expect(service.error()).toBeNull();
  });
});
