import { ChangeDetectionStrategy, Component, computed, inject, OnInit, signal } from '@angular/core';
import { AuditDirectoryService, AuditEvent } from '../../core/audit/audit-directory.service';
import { AuthService } from '../../core/auth/auth.service';
import { AhPageHeaderComponent } from '../../layout';
import { AhBadgeComponent, AhTableShellComponent } from '../../shared/ui';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [AhPageHeaderComponent, AhBadgeComponent, AhTableShellComponent],
  selector: 'ah-audit-page',
  template: `
    <ah-page-header
      title="Audit evidence"
      subtitle="Newest structured authorization and patient-operation evidence for the active organization."
    />

    @if (error()) {
      <div class="ah-section rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        {{ error() }}
      </div>
    }

    <section class="ah-section">
      <div class="mb-2 flex items-center justify-between">
        <h2 class="ah-card-title">Recent events</h2>
        <ah-badge>{{ events().length }} events</ah-badge>
      </div>
      <ah-table-shell>
        <table class="ah-table">
          <thead>
            <tr>
              <th>Outcome</th>
              <th>Action</th>
              <th>Resource</th>
              <th>Patient</th>
              <th>Correlation</th>
              <th>Occurred</th>
            </tr>
          </thead>
          <tbody>
            @for (event of events(); track event.id) {
              <tr>
                <td>
                  <span
                    class="rounded px-1.5 py-0.5 text-xs font-bold"
                    [class.bg-green-50]="event.outcome === 'ALLOWED'"
                    [class.text-green-700]="event.outcome === 'ALLOWED'"
                    [class.bg-red-50]="event.outcome === 'DENIED'"
                    [class.text-red-700]="event.outcome === 'DENIED'"
                  >
                    {{ event.outcome }}
                  </span>
                </td>
                <td class="font-semibold">{{ event.action }}</td>
                <td>{{ event.resourceType }}</td>
                <td class="font-mono text-xs">{{ event.patientId ?? '—' }}</td>
                <td class="font-mono text-xs">{{ event.correlationId }}</td>
                <td class="text-xs">{{ event.occurredAt }}</td>
              </tr>
            } @empty {
              <tr>
                <td colspan="6" class="py-6 text-center text-sm text-slate-500">
                  {{ loading() ? 'Loading audit evidence…' : 'No audit evidence returned.' }}
                </td>
              </tr>
            }
          </tbody>
        </table>
      </ah-table-shell>
    </section>
  `,
})
export class AuditPage implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly audit = inject(AuditDirectoryService);

  protected readonly events = signal<readonly AuditEvent[]>([]);
  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);

  private readonly organizationId = computed(
    () => this.auth.user()?.memberships.find((membership) => membership.status === 'ACTIVE')?.organizationId ?? null,
  );

  ngOnInit(): void {
    const organizationId = this.organizationId();
    if (!organizationId) {
      this.error.set('No active organization membership is available.');
      return;
    }

    this.loading.set(true);
    this.audit.latest(organizationId).subscribe({
      next: (events) => {
        this.events.set(events);
        this.loading.set(false);
      },
      error: (error: unknown) => {
        this.error.set(error instanceof Error ? error.message : 'Audit evidence could not be loaded.');
        this.loading.set(false);
      },
    });
  }
}
