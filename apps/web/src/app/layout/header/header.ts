import { ChangeDetectionStrategy, Component } from '@angular/core';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  selector: 'ah-header',
  template: `
    <header class="ah-topbar">
      <div class="flex min-w-0 items-center gap-2 text-[0.75rem] text-slate-500">
        <ng-content select="[ahHeaderContext]" />
      </div>

      <div class="flex items-center gap-1.5">
        <ng-content select="[ahHeaderActions]" />
      </div>
    </header>
  `,
})
export class AhHeaderComponent {}
