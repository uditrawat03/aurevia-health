import { ChangeDetectionStrategy, Component } from '@angular/core';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  selector: 'ah-app-shell',
  styles: `
    :host {
      display: block;
    }
  `,
  template: `
    <div class="ah-shell">
      <ng-content select="ah-sidebar" />

      <div class="ah-main">
        <ng-content select="ah-header" />
        <main class="ah-content">
          <ng-content />
        </main>
      </div>
    </div>
  `,
})
export class AhAppShellComponent {}
