import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

export type AhBadgeTone = 'info' | 'success' | 'warning' | 'danger';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  selector: 'ah-badge',
  template: `<span [class]="classes()"><ng-content /></span>`,
})
export class AhBadgeComponent {
  readonly tone = input<AhBadgeTone>('info');

  protected readonly classes = computed(() => `ah-badge ah-badge-${this.tone()}`);
}
