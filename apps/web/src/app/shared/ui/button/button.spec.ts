import { TestBed } from '@angular/core/testing';
import { AhButtonComponent } from './button';

describe('AhButtonComponent', () => {
  it('applies the requested visual variant', async () => {
    await TestBed.configureTestingModule({ imports: [AhButtonComponent] }).compileComponents();

    const fixture = TestBed.createComponent(AhButtonComponent);
    fixture.componentRef.setInput('variant', 'danger');
    fixture.detectChanges();

    const button = fixture.nativeElement.querySelector('button') as HTMLButtonElement;
    expect(button.classList.contains('ah-btn-danger')).toBe(true);
  });

  it('does not emit clicks while disabled', async () => {
    await TestBed.configureTestingModule({ imports: [AhButtonComponent] }).compileComponents();

    const fixture = TestBed.createComponent(AhButtonComponent);
    let clickCount = 0;
    fixture.componentInstance.clicked.subscribe(() => clickCount++);
    fixture.componentRef.setInput('disabled', true);
    fixture.detectChanges();

    const button = fixture.nativeElement.querySelector('button') as HTMLButtonElement;
    button.click();

    expect(clickCount).toBe(0);
  });
});
