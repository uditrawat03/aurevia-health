import { ChangeDetectionStrategy, Component, input } from '@angular/core';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  selector: 'ah-page-header',
  template: `
    <section class="ah-page-header">
      <div>
        <h1 class="ah-page-title">{{ title() }}</h1>
        @if (subtitle()) {
          <p class="ah-page-subtitle">{{ subtitle() }}</p>
        }
      </div>

      <div class="flex items-center gap-1.5">
        <ng-content select="[ahPageActions]" />
      </div>
    </section>
  `,
})
export class AhPageHeaderComponent {
  readonly title = input.required<string>();
  readonly subtitle = input<string>('');
}
