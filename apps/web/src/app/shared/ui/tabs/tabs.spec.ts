import { TestBed } from '@angular/core/testing';
import { AhTabsComponent, AhTabItem } from './tabs';

describe('AhTabsComponent', () => {
  const items: readonly AhTabItem[] = [
    { id: 'overview', label: 'Overview' },
    { id: 'activity', label: 'Activity', badge: 3 },
  ];

  it('marks the active tab and emits a changed selection', async () => {
    await TestBed.configureTestingModule({ imports: [AhTabsComponent] }).compileComponents();

    const fixture = TestBed.createComponent(AhTabsComponent);
    fixture.componentRef.setInput('items', items);
    fixture.componentRef.setInput('activeId', 'overview');

    let selected = '';
    fixture.componentInstance.activeIdChange.subscribe((id) => (selected = id));
    fixture.detectChanges();

    const tabs = fixture.nativeElement.querySelectorAll('[role="tab"]') as NodeListOf<HTMLButtonElement>;
    expect(tabs[0].getAttribute('aria-selected')).toBe('true');
    expect(tabs[0].tabIndex).toBe(0);
    expect(tabs[1].tabIndex).toBe(-1);

    tabs[0].dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true }));
    expect(selected).toBe('activity');

    selected = '';
    tabs[1].click();
    expect(selected).toBe('activity');
  });
});
