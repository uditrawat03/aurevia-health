import { ChangeDetectionStrategy, Component, computed, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';
import { PatientDirectoryService, PatientRecord } from '../../core/patient/patient-directory.service';
import { PatientContextService } from '../../core/patient/patient-context.service';
import { AhPageHeaderComponent } from '../../layout';
import { PatientContextBannerComponent } from '../../shared/patient-context-banner/patient-context-banner';
import { AhCardComponent, AhTableShellComponent } from '../../shared/ui';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    RouterLink,
    AhPageHeaderComponent,
    PatientContextBannerComponent,
    AhCardComponent,
    AhTableShellComponent,
  ],
  selector: 'ah-patient-detail-page',
  template: `
    <ah-page-header title="Patient record" subtitle="Live patient context loaded through the protected GraphQL read boundary.">
      <div ahPageActions class="flex items-center gap-3">
        <a class="ah-link font-semibold" routerLink="/patients">Back to patients</a>
        <a class="ah-link font-semibold" routerLink="/privacy">Privacy & consent</a>
      </div>
    </ah-page-header>

    <ah-patient-context-banner [patient]="patientContext.patient()" />

    @if (error()) {
      <div class="ah-section rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        {{ error() }}
      </div>
    }

    @if (patient(); as currentPatient) {
      <section class="ah-section grid gap-3 lg:grid-cols-3">
        <ah-card title="Demographics">
          <dl class="grid gap-2 text-sm">
            <div><dt class="text-slate-500">Name</dt><dd class="font-semibold text-slate-900">{{ currentPatient.givenName }} {{ currentPatient.familyName }}</dd></div>
            <div><dt class="text-slate-500">Date of birth</dt><dd>{{ currentPatient.dateOfBirth }}</dd></div>
            <div><dt class="text-slate-500">Sex at birth</dt><dd>{{ currentPatient.sexAtBirth }}</dd></div>
          </dl>
        </ah-card>

        <ah-card title="Identifiers">
          @for (identifier of currentPatient.identifiers; track identifier.system + identifier.value) {
            <div class="mb-2 text-sm">
              <div class="text-xs font-bold text-slate-500">{{ identifier.type }} · {{ identifier.system }}</div>
              <div class="font-mono text-slate-900">{{ identifier.value }}</div>
            </div>
          } @empty {
            <p class="text-sm text-slate-500">No identifiers.</p>
          }
        </ah-card>

        <ah-card title="Contacts">
          @for (contact of currentPatient.contacts; track contact.type + contact.value) {
            <div class="mb-2 text-sm">
              <span class="font-semibold">{{ contact.type }}</span> {{ contact.value }}
              @if (contact.preferred) { <span class="text-xs text-slate-500">preferred</span> }
            </div>
          } @empty {
            <p class="text-sm text-slate-500">No contacts.</p>
          }
        </ah-card>
      </section>

      <section class="ah-section">
        <h2 class="ah-card-title mb-2">Relationships</h2>
        <ah-table-shell>
          <table class="ah-table">
            <thead>
              <tr><th>Type</th><th>Name</th><th>Guardian</th><th>Emergency</th></tr>
            </thead>
            <tbody>
              @for (relationship of currentPatient.relationships; track relationship.type + relationship.name) {
                <tr>
                  <td>{{ relationship.type }}</td>
                  <td class="font-semibold">{{ relationship.name }}</td>
                  <td>{{ relationship.legalGuardian ? 'Yes' : 'No' }}</td>
                  <td>{{ relationship.emergencyContact ? 'Yes' : 'No' }}</td>
                </tr>
              } @empty {
                <tr><td colspan="4" class="py-5 text-center text-sm text-slate-500">No relationships recorded.</td></tr>
              }
            </tbody>
          </table>
        </ah-table-shell>
      </section>
    } @else if (loading()) {
      <div class="ah-section text-sm text-slate-600" role="status">Loading patient…</div>
    }
  `,
})
export class PatientDetailPage implements OnInit {
  protected readonly patientContext = inject(PatientContextService);
  private readonly route = inject(ActivatedRoute);
  private readonly auth = inject(AuthService);
  private readonly patients = inject(PatientDirectoryService);

  protected readonly patient = signal<PatientRecord | null>(null);
  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);

  private readonly organizationId = computed(
    () => this.auth.user()?.memberships.find((membership) => membership.status === 'ACTIVE')?.organizationId ?? null,
  );

  ngOnInit(): void {
    const patientId = this.route.snapshot.paramMap.get('patientId');
    const organizationId = this.organizationId();
    if (!patientId || !organizationId) {
      this.error.set('Patient or organization context is missing.');
      return;
    }

    this.loading.set(true);
    this.patients.patient(organizationId, patientId).subscribe({
      next: (patient) => {
        this.patient.set(patient);
        this.patientContext.select({
          id: patient.id,
          organizationId: patient.organizationId,
          registrationFacilityId: patient.registrationFacilityId,
          displayName: `${patient.givenName} ${patient.familyName}`,
          dateOfBirth: patient.dateOfBirth,
          sexAtBirth: patient.sexAtBirth,
          mrn: patient.identifiers.find((identifier) => identifier.type === 'MRN')?.value ?? null,
        });
        this.loading.set(false);
      },
      error: (error: unknown) => {
        this.error.set(error instanceof Error ? error.message : 'Patient could not be loaded.');
        this.loading.set(false);
      },
    });
  }
}
