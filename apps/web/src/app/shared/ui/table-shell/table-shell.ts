import { ChangeDetectionStrategy, Component } from '@angular/core';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  selector: 'ah-table-shell',
  template: `
    <div class="ah-table-wrap">
      <ng-content />
    </div>
  `,
})
export class AhTableShellComponent {}
