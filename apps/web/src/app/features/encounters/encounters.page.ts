import { ChangeDetectionStrategy, Component, computed, inject, OnInit, signal } from '@angular/core';
import { AuthService } from '../../core/auth/auth.service';
import {
  Encounter,
  EncounterService,
  EncounterStatus,
  EncounterType,
} from '../../core/encounter/encounter.service';
import { PatientDirectoryService, PatientSummary } from '../../core/patient/patient-directory.service';
import { Appointment, SchedulingService } from '../../core/scheduling/scheduling.service';
import { AhPageHeaderComponent } from '../../layout';
import { AhBadgeComponent, AhTableShellComponent } from '../../shared/ui';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [AhPageHeaderComponent, AhBadgeComponent, AhTableShellComponent],
  selector: 'ah-encounters-page',
  template: `
    <ah-page-header
      title="Encounters"
      subtitle="Move scheduled or walk-in patients through an auditable outpatient encounter lifecycle."
    />

    @if (error()) {
      <div class="ah-section rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        {{ error() }}
      </div>
    }
    @if (notice()) {
      <div class="ah-section rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ notice() }}
      </div>
    }

    <section class="ah-section grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
      <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="ah-card-title">1. Select patient</h2>
        <form class="mt-3 flex gap-2" (submit)="searchPatients(patientSearch.value); $event.preventDefault()">
          <input #patientSearch class="ah-input min-w-0 flex-1" type="search" value="DEMO" aria-label="Patient search" />
          <button class="rounded-md bg-slate-800 px-3 py-2 text-sm font-bold text-white" type="submit">Search</button>
        </form>

        <div class="mt-3 grid gap-2">
          @for (patient of patients(); track patient.id) {
            <button
              class="rounded-md border px-3 py-2 text-left text-sm"
              [class.border-blue-400]="selectedPatient()?.id === patient.id"
              [class.bg-blue-50]="selectedPatient()?.id === patient.id"
              [class.border-slate-200]="selectedPatient()?.id !== patient.id"
              type="button"
              (click)="selectPatient(patient)"
            >
              <span class="font-semibold text-slate-900">{{ patient.givenName }} {{ patient.familyName }}</span>
              <span class="ml-2 text-xs text-slate-500">{{ mrn(patient) ?? 'No MRN' }}</span>
            </button>
          } @empty {
            <p class="text-sm text-slate-500">No matching patients.</p>
          }
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="ah-card-title">2. Create encounter</h2>
        @if (selectedPatient(); as patient) {
          <p class="mt-1 text-xs text-slate-500">
            {{ patient.givenName }} {{ patient.familyName }} · clinical treatment consent is enforced server-side.
          </p>
          <form
            class="mt-3 grid gap-3"
            (submit)="createEncounter(type.value, appointment.value); $event.preventDefault()"
          >
            <label class="grid gap-1 text-xs font-bold text-slate-600">
              Encounter type
              <select #type class="ah-input" required>
                <option value="OUTPATIENT">Outpatient</option>
                <option value="VIRTUAL">Virtual</option>
                <option value="EMERGENCY">Emergency</option>
                <option value="INPATIENT">Inpatient</option>
                <option value="OTHER">Other</option>
              </select>
            </label>

            <label class="grid gap-1 text-xs font-bold text-slate-600">
              Linked appointment (optional for walk-in)
              <select #appointment class="ah-input">
                <option value="">Walk-in / no appointment</option>
                @for (item of scheduledAppointments(); track item.id) {
                  <option [value]="item.id">{{ displayAppointment(item) }} · {{ item.appointmentType.name }}</option>
                }
              </select>
            </label>

            <div class="flex items-center justify-between gap-3">
              <p class="text-xs text-slate-500">New encounters begin as PLANNED. Use the lifecycle controls to record arrival and start of care.</p>
              <button
                class="rounded-md bg-[var(--ah-color-brand-cobalt)] px-4 py-2 text-sm font-bold text-white disabled:opacity-60"
                type="submit"
                [disabled]="busy()"
              >
                {{ busy() ? 'Creating…' : 'Create encounter' }}
              </button>
            </div>
          </form>
        } @else {
          <p class="mt-3 text-sm text-slate-500">Select a patient before creating an encounter.</p>
        }
      </div>
    </section>

    <section class="ah-section">
      <div class="mb-2 flex items-center justify-between">
        <div>
          <h2 class="ah-card-title">Patient encounters</h2>
          <p class="mt-0.5 text-xs text-slate-500">Invalid lifecycle jumps are rejected by Laravel even if a client attempts them directly.</p>
        </div>
        <ah-badge>{{ encounters().length }} encounters</ah-badge>
      </div>

      <ah-table-shell>
        <table class="ah-table">
          <thead>
            <tr>
              <th>Created</th>
              <th>Type</th>
              <th>Appointment</th>
              <th>Status</th>
              <th>Lifecycle</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @for (encounter of encounters(); track encounter.id) {
              <tr>
                <td>{{ displayTimestamp(encounter.createdAt) }}</td>
                <td>{{ encounter.type }}</td>
                <td class="font-mono text-xs">{{ shortId(encounter.appointmentId) }}</td>
                <td>
                  @if (encounter.status === 'COMPLETED') {
                    <ah-badge tone="success">COMPLETED</ah-badge>
                  } @else {
                    <ah-badge>{{ encounter.status }}</ah-badge>
                  }
                </td>
                <td>
                  <div class="flex flex-wrap gap-2">
                    @if (encounter.status === 'PLANNED') {
                      <button class="ah-link font-semibold" type="button" (click)="transition(encounter, 'ARRIVED')">Mark arrived</button>
                    }
                    @if (encounter.status === 'ARRIVED') {
                      <button class="ah-link font-semibold" type="button" (click)="transition(encounter, 'IN_PROGRESS')">Start</button>
                    }
                    @if (encounter.status === 'IN_PROGRESS') {
                      <button class="ah-link font-semibold" type="button" (click)="transition(encounter, 'COMPLETED')">Complete</button>
                    }
                    @if (canCancel(encounter)) {
                      <button class="font-semibold text-red-700" type="button" (click)="selectEncounter(encounter)">Cancel…</button>
                    }
                  </div>
                </td>
                <td class="text-right">
                  <button class="ah-link font-semibold" type="button" (click)="selectEncounter(encounter)">Timeline</button>
                </td>
              </tr>
            } @empty {
              <tr>
                <td colspan="6" class="py-6 text-center text-sm text-slate-500">
                  {{ selectedPatient() ? 'No encounters for this patient.' : 'Select a patient to load encounters.' }}
                </td>
              </tr>
            }
          </tbody>
        </table>
      </ah-table-shell>
    </section>

    @if (selectedEncounter(); as encounter) {
      <section class="ah-section grid gap-4 xl:grid-cols-[0.8fr_1.2fr]">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h2 class="ah-card-title">Encounter controls</h2>
              <p class="mt-1 text-sm font-semibold text-slate-800">{{ encounter.patientDisplayName }} · {{ encounter.status }}</p>
            </div>
            <button class="ah-link text-sm font-semibold" type="button" (click)="closeEncounter()">Close</button>
          </div>

          @if (canCancel(encounter)) {
            <form class="mt-4" (submit)="cancelEncounter(encounter, cancellationReason.value); $event.preventDefault()">
              <label class="grid gap-1 text-xs font-bold text-red-800">
                Cancellation reason
                <textarea #cancellationReason class="ah-input min-h-20" required placeholder="Patient left before care started"></textarea>
              </label>
              <button class="mt-3 rounded-md bg-red-700 px-3 py-2 text-sm font-bold text-white" type="submit" [disabled]="busy()">
                Cancel encounter
              </button>
            </form>
          } @else {
            <p class="mt-3 text-sm text-slate-500">This encounter is not cancellable in its current state.</p>
          }
        </div>

        <div>
          <h2 class="ah-card-title">Encounter timeline</h2>
          <div class="mt-2 grid gap-2">
            @for (event of encounter.events; track event.id) {
              <div class="rounded-md border border-slate-200 bg-white px-3 py-2 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                  <strong class="text-sm text-slate-900">{{ event.type }}</strong>
                  <span class="text-xs text-slate-500">{{ displayTimestamp(event.occurredAt) }}</span>
                </div>
                <p class="mt-1 text-xs text-slate-600">
                  {{ event.fromStatus ?? '—' }} → {{ event.toStatus }}
                  @if (event.reason) { · {{ event.reason }} }
                </p>
              </div>
            }
          </div>
        </div>
      </section>
    }
  `,
})
export class EncountersPage implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly patientsDirectory = inject(PatientDirectoryService);
  private readonly scheduling = inject(SchedulingService);
  private readonly encounterService = inject(EncounterService);

  protected readonly patients = signal<readonly PatientSummary[]>([]);
  protected readonly selectedPatient = signal<PatientSummary | null>(null);
  protected readonly scheduledAppointments = signal<readonly Appointment[]>([]);
  protected readonly encounters = signal<readonly Encounter[]>([]);
  protected readonly selectedEncounter = signal<Encounter | null>(null);
  protected readonly busy = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly notice = signal<string | null>(null);

  private readonly membership = computed(
    () => this.auth.user()?.memberships.find((membership) => membership.status === 'ACTIVE') ?? null,
  );

  ngOnInit(): void {
    this.searchPatients('DEMO');
  }

  protected searchPatients(query: string): void {
    const membership = this.membership();
    if (!membership) {
      this.error.set('No active organization membership is available.');
      return;
    }

    const trimmed = query.trim();
    if (trimmed.length < 2) {
      this.error.set('Enter at least two characters.');
      return;
    }

    const facilityId = membership.allFacilities ? null : membership.facilityIds[0] ?? null;
    this.error.set(null);
    this.patientsDirectory.search(membership.organizationId, facilityId, trimmed).subscribe({
      next: (result) => this.patients.set(result.items),
      error: (error: unknown) => this.error.set(this.message(error, 'Patient search failed.')),
    });
  }

  protected selectPatient(patient: PatientSummary): void {
    const membership = this.membership();
    if (!membership || (!membership.allFacilities && !membership.facilityIds.includes(patient.registrationFacilityId))) {
      this.error.set('This patient is outside your selected facility scope.');
      return;
    }

    this.selectedPatient.set(patient);
    this.selectedEncounter.set(null);
    this.notice.set(null);
    this.loadPatientWorkspace(patient);
  }

  protected createEncounter(typeValue: string, appointmentId: string): void {
    const patient = this.selectedPatient();
    const membership = this.membership();
    if (!patient || !membership) {
      this.error.set('Select a patient first.');
      return;
    }

    this.busy.set(true);
    this.error.set(null);
    this.encounterService.create({
      organizationId: membership.organizationId,
      facilityId: patient.registrationFacilityId,
      patientId: patient.id,
      departmentId: null,
      appointmentId: appointmentId || null,
      type: this.encounterType(typeValue),
    }).subscribe({
      next: (encounter) => {
        this.encounters.update((items) => [encounter, ...items]);
        this.selectedEncounter.set(encounter);
        this.notice.set('Encounter created as PLANNED.');
        this.busy.set(false);
      },
      error: (error: unknown) => {
        this.error.set(this.message(error, 'Encounter creation failed.'));
        this.busy.set(false);
      },
    });
  }

  protected transition(encounter: Encounter, toStatus: EncounterStatus): void {
    this.changeStatus(encounter, toStatus, null);
  }

  protected cancelEncounter(encounter: Encounter, reason: string): void {
    const trimmed = reason.trim();
    if (trimmed.length < 3) {
      this.error.set('Enter a cancellation reason.');
      return;
    }
    this.changeStatus(encounter, 'CANCELLED', trimmed);
  }

  protected selectEncounter(encounter: Encounter): void {
    this.selectedEncounter.set(encounter);
  }

  protected closeEncounter(): void {
    this.selectedEncounter.set(null);
  }

  protected canCancel(encounter: Encounter): boolean {
    return encounter.status === 'PLANNED' || encounter.status === 'ARRIVED';
  }

  protected mrn(patient: PatientSummary): string | null {
    return patient.identifiers.find((identifier) => identifier.type === 'MRN')?.value ?? null;
  }

  protected displayAppointment(appointment: Appointment): string {
    return new Date(appointment.startsAt).toLocaleString();
  }

  protected displayTimestamp(value: string): string {
    return new Date(value).toLocaleString();
  }

  protected shortId(value: string | null): string {
    return value ? `${value.slice(0, 8)}…` : 'Walk-in';
  }

  private loadPatientWorkspace(patient: PatientSummary): void {
    const membership = this.membership();
    if (!membership) {
      return;
    }

    const from = new Date();
    from.setDate(from.getDate() - 1);
    const to = new Date();
    to.setDate(to.getDate() + 14);

    this.scheduling.appointments(
      membership.organizationId,
      patient.registrationFacilityId,
      from.toISOString(),
      to.toISOString(),
    ).subscribe({
      next: (appointments) => this.scheduledAppointments.set(
        appointments.filter((item) => item.patientId === patient.id && item.status === 'SCHEDULED'),
      ),
      error: (error: unknown) => this.error.set(this.message(error, 'Appointment loading failed.')),
    });

    this.encounterService.encounters(
      membership.organizationId,
      patient.registrationFacilityId,
      patient.id,
    ).subscribe({
      next: (encounters) => this.encounters.set(encounters),
      error: (error: unknown) => this.error.set(this.message(error, 'Encounter loading failed.')),
    });
  }

  private changeStatus(encounter: Encounter, toStatus: EncounterStatus, reason: string | null): void {
    this.busy.set(true);
    this.error.set(null);
    this.encounterService.transition({
      organizationId: encounter.organizationId,
      facilityId: encounter.facilityId,
      patientId: encounter.patientId,
      encounterId: encounter.id,
      toStatus,
      reason,
    }).subscribe({
      next: (updated) => {
        this.encounters.update((items) => items.map((item) => item.id === updated.id ? updated : item));
        if (this.selectedEncounter()?.id === updated.id) {
          this.selectedEncounter.set(updated);
        }
        this.notice.set(`Encounter moved to ${updated.status}.`);
        this.busy.set(false);
      },
      error: (error: unknown) => {
        this.error.set(this.message(error, 'Encounter transition failed.'));
        this.busy.set(false);
      },
    });
  }

  private encounterType(value: string): EncounterType {
    if (value === 'OUTPATIENT' || value === 'EMERGENCY' || value === 'INPATIENT' || value === 'VIRTUAL' || value === 'OTHER') {
      return value;
    }

    return 'OUTPATIENT';
  }

  private message(error: unknown, fallback: string): string {
    return error instanceof Error ? error.message : fallback;
  }
}
