import { signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { AuthenticatedUser, AuthService } from './core/auth/auth.service';
import { App } from './app';

const authenticatedUser: AuthenticatedUser = {
  id: '1',
  name: 'Synthetic Clinician',
  email: 'clinician@example.test',
  memberships: [],
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
      providers: [{ provide: AuthService, useClass: AuthServiceStub }],
    }).compileComponents();
  });

  it('should create the app', () => {
    const fixture = TestBed.createComponent(App);
    const app = fixture.componentInstance;
    expect(app).toBeTruthy();
  });

  it('should render the authenticated Aurevia Health application shell', async () => {
    const fixture = TestBed.createComponent(App);
    fixture.detectChanges();
    await fixture.whenStable();

    const compiled = fixture.nativeElement as HTMLElement;

    expect(compiled.querySelector('h1')?.textContent).toContain('Clinical operations');
    expect(compiled.querySelector('ah-sidebar')).toBeTruthy();
    expect(compiled.textContent).toContain('Synthetic Clinician');
    expect(compiled.textContent).toContain('Sign out');
  });
});
