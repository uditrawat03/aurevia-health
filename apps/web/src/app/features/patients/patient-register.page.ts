import { ChangeDetectionStrategy, Component, computed, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';
import {
  PatientDirectoryService,
  PatientDuplicateCandidate,
  PatientRecord,
  RegisterPatientInput,
} from '../../core/patient/patient-directory.service';
import { PatientContextService } from '../../core/patient/patient-context.service';
import { WorkspaceDirectoryService, WorkspaceFacility } from '../../core/workspace/workspace-directory.service';
import { AhPageHeaderComponent } from '../../layout';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, AhPageHeaderComponent],
  selector: 'ah-patient-register-page',
  template: `
    <ah-page-header
      title="Register patient"
      subtitle="Create a synthetic patient through the live GraphQL registration boundary."
    >
      <a ahPageActions class="ah-link font-semibold" routerLink="/patients">Back to patients</a>
    </ah-page-header>

    <section class="ah-section grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
      <form
        class="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
        (submit)="register(facility.value, givenName.value, familyName.value, dob.value, sex.value, mrn.value); $event.preventDefault()"
      >
        <div class="grid gap-4 sm:grid-cols-2">
          <label class="grid gap-1.5 text-sm font-semibold text-slate-700">
            Given name
            <input #givenName class="ah-input" required />
          </label>
          <label class="grid gap-1.5 text-sm font-semibold text-slate-700">
            Family name
            <input #familyName class="ah-input" required />
          </label>
          <label class="grid gap-1.5 text-sm font-semibold text-slate-700">
            Date of birth
            <input #dob class="ah-input" type="date" required />
          </label>
          <label class="grid gap-1.5 text-sm font-semibold text-slate-700">
            Sex at birth
            <select #sex class="ah-select" required>
              <option value="FEMALE">Female</option>
              <option value="MALE">Male</option>
              <option value="INTERSEX">Intersex</option>
              <option value="UNKNOWN">Unknown</option>
            </select>
          </label>
          <label class="grid gap-1.5 text-sm font-semibold text-slate-700">
            Facility
            <select #facility class="ah-select" required>
              <option value="" disabled selected>Select facility</option>
              @for (item of facilities(); track item.id) {
                <option [value]="item.id">{{ item.name }} ({{ item.code }})</option>
              }
            </select>
          </label>
          <label class="grid gap-1.5 text-sm font-semibold text-slate-700">
            MRN
            <input #mrn class="ah-input" placeholder="DEMO-1001" required />
          </label>
        </div>

        @if (error()) {
          <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">
            {{ error() }}
          </div>
        }

        <div>
          <button
            class="rounded-md bg-[var(--ah-color-brand-cobalt)] px-4 py-2 text-sm font-bold text-white disabled:opacity-60"
            type="submit"
            [disabled]="loading() || facilities().length === 0"
          >
            {{ loading() ? 'Registering…' : 'Register patient' }}
          </button>
        </div>
      </form>

      <aside class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="font-bold text-slate-900">Registration result</h2>
        @if (registered(); as patient) {
          <div class="mt-3 rounded-lg bg-[var(--ah-color-tint-aqua)] p-3 text-sm text-slate-800">
            <div class="font-bold">{{ patient.givenName }} {{ patient.familyName }}</div>
            <div class="mt-1">Patient created successfully.</div>
            <a class="ah-link mt-2 inline-block font-semibold" routerLink="/privacy">Manage consent before opening record</a>
          </div>

          <div class="mt-4">
            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Duplicate candidates</div>
            @for (candidate of duplicates(); track candidate.patientId) {
              <div class="mt-2 rounded-md border border-amber-200 bg-amber-50 p-2 text-xs text-amber-900">
                {{ candidate.displayName }} · {{ candidate.dateOfBirth }} · {{ candidate.reason }} · {{ candidate.confidence }}%
              </div>
            } @empty {
              <p class="mt-2 text-sm text-slate-500">No duplicate candidates were returned.</p>
            }
          </div>
        } @else {
          <p class="mt-2 text-sm text-slate-500">
            Use only synthetic data. Duplicate candidates are shown here and are never auto-merged.
          </p>
        }
      </aside>
    </section>
  `,
})
export class PatientRegisterPage implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly patients = inject(PatientDirectoryService);
  private readonly patientContext = inject(PatientContextService);
  private readonly workspace = inject(WorkspaceDirectoryService);

  protected readonly facilities = signal<readonly WorkspaceFacility[]>([]);
  protected readonly registered = signal<PatientRecord | null>(null);
  protected readonly duplicates = signal<readonly PatientDuplicateCandidate[]>([]);
  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);

  private readonly membership = computed(
    () => this.auth.user()?.memberships.find((membership) => membership.status === 'ACTIVE') ?? null,
  );

  ngOnInit(): void {
    const membership = this.membership();
    if (!membership) {
      this.error.set('No active organization membership is available.');
      return;
    }

    if (!membership.allFacilities && membership.facilityIds.length > 0) {
      this.facilities.set(
        membership.facilityIds.map((id) => ({
          id,
          name: 'Authorized facility',
          code: id,
        })),
      );
      return;
    }

    this.workspace.organization(membership.organizationId).subscribe({
      next: (organization) => this.facilities.set(organization.facilities),
      error: (error: unknown) => {
        this.error.set(error instanceof Error ? error.message : 'Unable to load facilities.');
      },
    });
  }

  protected register(
    facilityId: string,
    givenName: string,
    familyName: string,
    dateOfBirth: string,
    sexAtBirth: string,
    mrn: string,
  ): void {
    const membership = this.membership();
    if (!membership) {
      this.error.set('No active organization membership is available.');
      return;
    }

    if (!facilityId || !givenName.trim() || !familyName.trim() || !dateOfBirth || !mrn.trim()) {
      this.error.set('Complete all required registration fields.');
      return;
    }

    const input: RegisterPatientInput = {
      organizationId: membership.organizationId,
      registrationFacilityId: facilityId,
      givenName: givenName.trim(),
      familyName: familyName.trim(),
      dateOfBirth,
      sexAtBirth: sexAtBirth as RegisterPatientInput['sexAtBirth'],
      mrn: mrn.trim(),
    };

    this.loading.set(true);
    this.error.set(null);

    this.patients.register(input).subscribe({
      next: (result) => {
        this.registered.set(result.patient);
        this.duplicates.set(result.duplicateCandidates);
        this.patientContext.select({
          id: result.patient.id,
          organizationId: result.patient.organizationId,
          registrationFacilityId: result.patient.registrationFacilityId,
          displayName: `${result.patient.givenName} ${result.patient.familyName}`,
          dateOfBirth: result.patient.dateOfBirth,
          sexAtBirth: result.patient.sexAtBirth,
          mrn: result.patient.identifiers.find((identifier) => identifier.type === 'MRN')?.value ?? null,
        });
        this.loading.set(false);
      },
      error: (error: unknown) => {
        this.error.set(error instanceof Error ? error.message : 'Patient registration failed.');
        this.loading.set(false);
      },
    });
  }
}
