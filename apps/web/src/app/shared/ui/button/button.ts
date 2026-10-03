import { ChangeDetectionStrategy, Component, computed, input, output } from '@angular/core';

export type AhButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger';
export type AhButtonSize = 'sm' | 'md';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  selector: 'ah-button',
  template: `
    <button
      [attr.aria-busy]="loading() ? 'true' : null"
      [attr.aria-label]="ariaLabel()"
      [class]="classes()"
      [disabled]="disabled() || loading()"
      [type]="type()"
      (click)="handleClick($event)"
    >
      <ng-content />
    </button>
  `,
})
export class AhButtonComponent {
  readonly variant = input<AhButtonVariant>('secondary');
  readonly size = input<AhButtonSize>('md');
  readonly type = input<'button' | 'submit' | 'reset'>('button');
  readonly disabled = input(false);
  readonly loading = input(false);
  readonly ariaLabel = input<string | null>(null);
  readonly clicked = output<MouseEvent>();

  protected readonly classes = computed(() => {
    const classes = ['ah-btn', `ah-btn-${this.variant()}`];

    if (this.size() === 'sm') {
      classes.push('ah-btn-sm');
    }

    return classes.join(' ');
  });

  protected handleClick(event: MouseEvent): void {
    if (this.disabled() || this.loading()) {
      event.preventDefault();
      return;
    }

    this.clicked.emit(event);
  }
}
