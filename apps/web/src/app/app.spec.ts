import { TestBed } from '@angular/core/testing';
import { App } from './app';

describe('App', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [App],
    }).compileComponents();
  });

  it('should create the app', () => {
    const fixture = TestBed.createComponent(App);
    const app = fixture.componentInstance;
    expect(app).toBeTruthy();
  });

  it('should render the Aurevia Health application shell from reusable primitives', async () => {
    const fixture = TestBed.createComponent(App);

    await fixture.whenStable();

    const compiled = fixture.nativeElement as HTMLElement;

    expect(compiled.querySelector('h1')?.textContent).toContain('Clinical operations');
    expect(compiled.querySelector('ah-sidebar')).toBeTruthy();
    expect(compiled.querySelectorAll('ah-button').length).toBeGreaterThan(0);
    expect(compiled.querySelectorAll('[role="tab"]').length).toBeGreaterThan(0);
    expect(compiled.textContent).toContain('Aurevia Health');
  });
});
