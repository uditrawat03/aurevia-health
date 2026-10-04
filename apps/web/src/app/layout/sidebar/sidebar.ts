import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';

export interface AhNavItem {
  readonly label: string;
  readonly icon: string;
  readonly route: string;
  readonly exact?: boolean;
}

export interface AhNavGroup {
  readonly label: string;
  readonly items: readonly AhNavItem[];
}

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, RouterLinkActive],
  selector: 'ah-sidebar',
  template: `
    <aside class="ah-sidebar" aria-label="Primary navigation">
      <div class="ah-sidebar-brand">
        <div class="ah-brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 3v18M3 12h18" />
          </svg>
        </div>
        <div>
          <div class="text-[0.875rem] font-bold tracking-[-0.02em] text-slate-900">{{ productName() }}</div>
          <div class="text-[0.6875rem] font-medium text-slate-500">{{ workspaceName() }}</div>
        </div>
      </div>

      <nav class="ah-sidebar-nav">
        @for (group of groups(); track group.label) {
          <div class="ah-nav-label">{{ group.label }}</div>
          @for (item of group.items; track item.label) {
            <a
              class="ah-nav-link"
              [routerLink]="item.route"
              routerLinkActive="is-active"
              [routerLinkActiveOptions]="{ exact: item.exact ?? false }"
              ariaCurrentWhenActive="page"
            >
              <span class="ah-nav-icon" aria-hidden="true">{{ item.icon }}</span>
              {{ item.label }}
            </a>
          }
        }
      </nav>

      <div class="border-t border-slate-200 p-2.5">
        <div class="ah-nav-link">
          <span class="grid h-6 w-6 place-items-center rounded-full bg-slate-200 text-[0.625rem] font-bold text-slate-700">
            {{ userInitials() }}
          </span>
          <span class="min-w-0">
            <span class="block truncate text-[0.75rem] font-semibold text-slate-800">{{ userName() }}</span>
            <span class="block truncate text-[0.6875rem] font-medium text-slate-500">{{ userContext() }}</span>
          </span>
        </div>
      </div>
    </aside>
  `,
})
export class AhSidebarComponent {
  readonly groups = input.required<readonly AhNavGroup[]>();
  readonly productName = input<string>('Aurevia Health');
  readonly workspaceName = input<string>('Clinical workspace');
  readonly userInitials = input<string>('UR');
  readonly userName = input<string>('Development User');
  readonly userContext = input<string>('Local environment');
}
