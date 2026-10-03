import { Component, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { AhAppShellComponent, AhHeaderComponent, AhNavGroup, AhPageHeaderComponent, AhSidebarComponent } from './layout';
import {
  AhBadgeComponent,
  AhButtonComponent,
  AhCardComponent,
  AhFormFieldComponent,
  AhTableShellComponent,
  AhTabItem,
  AhTabsComponent,
} from './shared/ui';

@Component({
  imports: [
    RouterOutlet,
    AhAppShellComponent,
    AhSidebarComponent,
    AhHeaderComponent,
    AhPageHeaderComponent,
    AhButtonComponent,
    AhBadgeComponent,
    AhCardComponent,
    AhFormFieldComponent,
    AhTabsComponent,
    AhTableShellComponent,
  ],
  selector: 'app-root',
  styleUrl: './app.scss',
  templateUrl: './app.html',
})
export class App {
  protected readonly navGroups: readonly AhNavGroup[] = [
    {
      label: 'Workspace',
      items: [
        { label: 'Overview', icon: '▦', href: '#overview', active: true },
        { label: 'Patients', icon: '◉', href: '#patients' },
        { label: 'Scheduling', icon: '◷', href: '#scheduling' },
        { label: 'Encounters', icon: '✚', href: '#encounters' },
      ],
    },
    {
      label: 'Operations',
      items: [
        { label: 'Work queues', icon: '≡', href: '#work-queues' },
        { label: 'Interoperability', icon: '⌁', href: '#interoperability' },
        { label: 'Administration', icon: '⚙', href: '#administration' },
      ],
    },
  ];

  protected readonly overviewTabs: readonly AhTabItem[] = [
    { id: 'overview', label: 'Overview' },
    { id: 'activity', label: 'Activity' },
    { id: 'queues', label: 'Work queues', badge: 8 },
    { id: 'audit', label: 'Audit' },
  ];

  protected readonly activityTabs: readonly AhTabItem[] = [
    { id: 'all', label: 'All' },
    { id: 'clinical', label: 'Clinical' },
    { id: 'admin', label: 'Admin' },
  ];

  protected readonly activeOverviewTab = signal('overview');
  protected readonly activeActivityTab = signal('all');
}
