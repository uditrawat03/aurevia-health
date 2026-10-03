import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  selector: 'ah-card',
  styles: `
    :host {
      display: block;
    }
  `,
  host: {
    '[class]': 'classes()',
  },
  template: `
    @if (title()) {
      <div class="ah-card-header">
        <div>
          <h2 class="ah-card-title">{{ title() }}</h2>
          @if (subtitle()) {
            <div class="mt-0.5 text-[0.75rem] text-slate-500">{{ subtitle() }}</div>
          }
        </div>
        <ng-content select="[ahCardActions]" />
      </div>
    }

    <div class="ah-card-body">
      <ng-content />
    </div>
  `,
})
export class AhCardComponent {
  readonly title = input<string>('');
  readonly subtitle = input<string>('');
  readonly hoverable = input(false);

  protected readonly classes = computed(() => (this.hoverable() ? 'ah-card ah-card-hover' : 'ah-card'));
}
