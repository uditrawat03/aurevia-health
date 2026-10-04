import { isPlatformBrowser } from '@angular/common';
import { Component, computed, inject, OnInit, PLATFORM_ID } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { AuthService } from './core/auth/auth.service';
import { AhAppShellComponent, AhHeaderComponent, AhNavGroup, AhSidebarComponent } from './layout';
import { AhButtonComponent } from './shared/ui';

@Component({
  imports: [
    RouterOutlet,
    AhAppShellComponent,
    AhSidebarComponent,
    AhHeaderComponent,
    AhButtonComponent,
  ],
  selector: 'app-root',
  styleUrl: './app.scss',
  templateUrl: './app.html',
})
export class App implements OnInit {
  protected readonly auth = inject(AuthService);
  private readonly platformId = inject(PLATFORM_ID);

  protected readonly navGroups: readonly AhNavGroup[] = [
    {
      label: 'Workspace',
      items: [
        { label: 'Overview', icon: '▦', route: '/overview', exact: true },
        { label: 'Patients', icon: '◉', route: '/patients' },
        { label: 'Privacy & consent', icon: '◈', route: '/privacy' },
        { label: 'Scheduling', icon: '◷', route: '/scheduling' },
        { label: 'Encounters', icon: '✚', route: '/encounters' },
      ],
    },
    {
      label: 'Operations',
      items: [
        { label: 'Audit', icon: '◎', route: '/audit' },
        { label: 'Work queues', icon: '≡', route: '/work-queues' },
        { label: 'Interoperability', icon: '⌁', route: '/interoperability' },
        { label: 'Administration', icon: '⚙', route: '/administration' },
      ],
    },
  ];

  protected readonly userInitials = computed(() => {
    const name = this.auth.user()?.name.trim() ?? '';
    const initials = name
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part[0]?.toUpperCase() ?? '')
      .join('');

    return initials || 'AH';
  });

  ngOnInit(): void {
    if (isPlatformBrowser(this.platformId)) {
      this.auth.restore();
    }
  }

  protected login(email: string, password: string): void {
    this.auth.login(email, password);
  }

  protected logout(): void {
    this.auth.logout();
  }
}
