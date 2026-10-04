import { signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { App } from './app';
import { routes } from './app.routes';
import { AuthenticatedUser, AuthService } from './core/auth/auth.service';

const authenticatedUser: AuthenticatedUser = {
  id: '1',
  name: 'Synthetic Clinician',
  email: 'clinician@example.test',
  memberships: [
    {
      organizationId: '01JTESTORG',
      role: 'CLINICIAN',
      status: 'ACTIVE',
      allFacilities: true,
      facilityIds: [],
    },
  ],
};

class AuthServiceStub {
  readonly user = signal<AuthenticatedUser | null>(authenticatedUser);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  restore(): void {}
  login(): void {}
  logout(): void {}
}

describe('App', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [App],
      providers: [
        provideRouter(routes),
        { provide: AuthService, useClass: AuthServiceStub },
      ],
    }).compileComponents();
  });

  it('should create the app', () => {
    const fixture = TestBed.createComponent(App);
    expect(fixture.componentInstance).toBeTruthy();
  });

  it('should render authenticated route navigation instead of static hash links', async () => {
    const fixture = TestBed.createComponent(App);
    fixture.detectChanges();
    await fixture.whenStable();

    const compiled = fixture.nativeElement as HTMLElement;
    const links = Array.from(compiled.querySelectorAll<HTMLAnchorElement>('ah-sidebar a'));

    expect(compiled.querySelector('ah-sidebar')).toBeTruthy();
    expect(compiled.textContent).toContain('Synthetic Clinician');
    expect(compiled.textContent).toContain('Privacy & consent');
    expect(links.some((link) => link.getAttribute('href') === '/patients')).toBe(true);
    expect(links.some((link) => link.getAttribute('href') === '/audit')).toBe(true);
    expect(compiled.textContent).toContain('Sign out');
  });
});
