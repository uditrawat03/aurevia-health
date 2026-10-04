import { ChangeDetectionStrategy, Component, computed, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';
import { PatientDirectoryService, PatientSummary } from '../../core/patient/patient-directory.service';
import { PatientContextService } from '../../core/patient/patient-context.service';
import { AhPageHeaderComponent } from '../../layout';
import { AhBadgeComponent, AhTableShellComponent } from '../../shared/ui';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, AhPageHeaderComponent, AhBadgeComponent, AhTableShellComponent],
  selector: 'ah-patients-page',
  template: `
    <ah-page-header
      title="Patients"
      subtitle="Live GraphQL patient search using the V1-M4 identity and MPI boundary."
    >
      <a
        ahPageActions
        class="rounded-md bg-[var(--ah-color-brand-cobalt)] px-3 py-2 text-sm font-bold text-white"
        routerLink="/patients/new"
      >
        New patient
      </a>
    </ah-page-header>

    <section class="ah-section rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
      <form class="flex flex-col gap-2 sm:flex-row" (submit)="search(searchBox.value); $event.preventDefault()">
        <input
          #searchBox
          class="ah-input flex-1"
          type="search"
          value="DEMO"
          placeholder="Name, MRN or identifier"
          aria-label="Patient search"
        />
        <button
          class="rounded-md bg-[var(--ah-color-brand-cobalt)] px-4 py-2 text-sm font-bold text-white disabled:opacity-60"
          type="submit"
          [disabled]="loading()"
        >
          {{ loading() ? 'Searching…' : 'Search' }}
        </button>
      </form>
      <p class="mt-2 text-xs text-slate-500">
        Local seed data can be found with <strong>DEMO</strong>, <strong>Asha</strong>, or <strong>Rahil</strong>.
      </p>
    </section>

    @if (error()) {
      <div class="ah-section rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        {{ error() }}
      </div>
    }

    <section class="ah-section">
      <div class="mb-2 flex items-center justify-between">
        <h2 class="ah-card-title">Search results</h2>
        <ah-badge>{{ total() }} found</ah-badge>
      </div>

      <ah-table-shell>
        <table class="ah-table">
          <thead>
            <tr>
              <th>Patient</th>
              <th>DOB</th>
              <th>Sex at birth</th>
              <th>MRN</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @for (patient of items(); track patient.id) {
              <tr>
                <td class="font-semibold text-slate-900">{{ patient.givenName }} {{ patient.familyName }}</td>
                <td>{{ patient.dateOfBirth }}</td>
                <td>{{ patient.sexAtBirth }}</td>
                <td class="font-mono text-xs">{{ mrn(patient) ?? '—' }}</td>
                <td class="text-right">
                  <div class="flex justify-end gap-3">
                    <a
                      class="ah-link font-semibold"
                      routerLink="/privacy"
                      (click)="select(patient)"
                    >
                      Privacy
                    </a>
                    <a
                      class="ah-link font-semibold"
                      [routerLink]="['/patients', patient.id]"
                      (click)="select(patient)"
                    >
                      Open
                    </a>
                  </div>
                </td>
              </tr>
            } @empty {
              <tr>
                <td colspan="5" class="py-6 text-center text-sm text-slate-500">
                  {{ loading() ? 'Loading patients…' : 'No patients found.' }}
                </td>
              </tr>
            }
          </tbody>
        </table>
      </ah-table-shell>
    </section>
  `,
})
export class PatientsPage implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly patients = inject(PatientDirectoryService);
  private readonly patientContext = inject(PatientContextService);

  protected readonly items = signal<readonly PatientSummary[]>([]);
  protected readonly total = signal(0);
  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);

  private readonly membership = computed(
    () => this.auth.user()?.memberships.find((membership) => membership.status === 'ACTIVE') ?? null,
  );

  ngOnInit(): void {
    this.search('DEMO');
  }

  protected search(query: string): void {
    const membership = this.membership();
    if (!membership) {
      this.error.set('No active organization membership is available.');
      return;
    }

    const trimmedQuery = query.trim();
    if (trimmedQuery.length < 2) {
      this.error.set('Enter at least two characters.');
      return;
    }

    const facilityId = membership.allFacilities ? null : membership.facilityIds[0] ?? null;
    if (!membership.allFacilities && facilityId === null) {
      this.error.set('No facility is selected for this membership.');
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    this.patients.search(membership.organizationId, facilityId, trimmedQuery).subscribe({
      next: (result) => {
        this.items.set(result.items);
        this.total.set(result.total);
        this.loading.set(false);
      },
      error: (error: unknown) => {
        this.error.set(error instanceof Error ? error.message : 'Patient search failed.');
        this.loading.set(false);
      },
    });
  }

  protected select(patient: PatientSummary): void {
    this.patientContext.select({
      id: patient.id,
      organizationId: patient.organizationId,
      registrationFacilityId: patient.registrationFacilityId,
      displayName: `${patient.givenName} ${patient.familyName}`,
      dateOfBirth: patient.dateOfBirth,
      sexAtBirth: patient.sexAtBirth,
      mrn: this.mrn(patient),
    });
  }

  protected mrn(patient: PatientSummary): string | null {
    return patient.identifiers.find((identifier) => identifier.type === 'MRN')?.value ?? null;
  }
}
