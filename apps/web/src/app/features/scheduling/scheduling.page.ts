import { ChangeDetectionStrategy, Component, computed, inject, OnInit, signal } from '@angular/core';
import { AuthService } from '../../core/auth/auth.service';
import { PatientDirectoryService, PatientSummary } from '../../core/patient/patient-directory.service';
import {
  Appointment,
  AppointmentType,
  SchedulingResource,
  SchedulingService,
  WaitlistEntry,
} from '../../core/scheduling/scheduling.service';
import { AhPageHeaderComponent } from '../../layout';
import { AhBadgeComponent, AhTableShellComponent } from '../../shared/ui';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [AhPageHeaderComponent, AhBadgeComponent, AhTableShellComponent],
  selector: 'ah-scheduling-page',
  template: `
    <ah-page-header
      title="Scheduling"
      subtitle="Book, reschedule, cancel, and waitlist patients against live healthcare resources."
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
        <h2 class="ah-card-title">2. Book appointment</h2>
        @if (selectedPatient(); as patient) {
          <p class="mt-1 text-xs text-slate-500">
            {{ patient.givenName }} {{ patient.familyName }} · {{ patient.registrationFacilityId }}
          </p>
          <form
            class="mt-3 grid gap-3 md:grid-cols-2"
            (submit)="book(appointmentType.value, resource.value, startsAt.value, timezone.value, reason.value); $event.preventDefault()"
          >
            <label class="grid gap-1 text-xs font-bold text-slate-600">
              Appointment type
              <select #appointmentType class="ah-input" required>
                <option value="">Select type</option>
                @for (type of appointmentTypesForPatient(); track type.id) {
                  <option [value]="type.id">{{ type.name }} · {{ type.durationMinutes }} min</option>
                }
              </select>
            </label>

            <label class="grid gap-1 text-xs font-bold text-slate-600">
              Resource
              <select #resource class="ah-input" required>
                <option value="">Select provider / resource</option>
                @for (item of resourcesForPatient(); track item.id) {
                  <option [value]="item.id">{{ item.name }} · {{ item.type }}</option>
                }
              </select>
            </label>

            <label class="grid gap-1 text-xs font-bold text-slate-600">
              Local start
              <input #startsAt class="ah-input" type="datetime-local" [value]="defaultStart" required />
            </label>

            <label class="grid gap-1 text-xs font-bold text-slate-600">
              Timezone
              <input #timezone class="ah-input" type="text" [value]="browserTimezone" required />
            </label>

            <label class="grid gap-1 text-xs font-bold text-slate-600 md:col-span-2">
              Reason (optional)
              <input #reason class="ah-input" type="text" placeholder="General consultation" />
            </label>

            <div class="md:col-span-2 flex items-center justify-between gap-3">
              <p class="text-xs text-slate-500">Privacy, facility scope, patient overlap, and resource conflicts are enforced by Laravel.</p>
              <button
                class="rounded-md bg-[var(--ah-color-brand-cobalt)] px-4 py-2 text-sm font-bold text-white disabled:opacity-60"
                type="submit"
                [disabled]="booking() || appointmentTypesForPatient().length === 0 || resourcesForPatient().length === 0"
              >
                {{ booking() ? 'Booking…' : 'Book appointment' }}
              </button>
            </div>
          </form>
        } @else {
          <p class="mt-3 text-sm text-slate-500">Select a patient before booking.</p>
        }
      </div>
    </section>

    <section class="ah-section">
      <div class="mb-2 flex items-center justify-between">
        <div>
          <h2 class="ah-card-title">Next 7 days</h2>
          <p class="mt-0.5 text-xs text-slate-500">Cancelled appointments remain visible as durable lifecycle evidence.</p>
        </div>
        <ah-badge>{{ appointments().length }} appointments</ah-badge>
      </div>

      <ah-table-shell>
        <table class="ah-table">
          <thead>
            <tr>
              <th>Patient</th>
              <th>Start</th>
              <th>Type</th>
              <th>Resources</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @for (appointment of appointments(); track appointment.id) {
              <tr>
                <td class="font-semibold text-slate-900">{{ appointment.patientDisplayName }}</td>
                <td>
                  <span class="block">{{ displayStart(appointment) }}</span>
                  <span class="text-xs text-slate-500">{{ appointment.timezone }}</span>
                </td>
                <td>{{ appointment.appointmentType.name }}</td>
                <td>{{ resourceNames(appointment) }}</td>
                <td>
                  @if (appointment.status === 'SCHEDULED') {
                    <ah-badge tone="success">SCHEDULED</ah-badge>
                  } @else {
                    <ah-badge>CANCELLED</ah-badge>
                    @if (appointment.cancellationReason) {
                      <span class="mt-1 block max-w-48 text-xs text-slate-500">{{ appointment.cancellationReason }}</span>
                    }
                  }
                </td>
                <td class="text-right">
                  <button class="ah-link font-semibold" type="button" (click)="selectAppointment(appointment)">Manage</button>
                </td>
              </tr>
            } @empty {
              <tr>
                <td colspan="6" class="py-6 text-center text-sm text-slate-500">No appointments in this window.</td>
              </tr>
            }
          </tbody>
        </table>
      </ah-table-shell>
    </section>

    @if (selectedAppointment(); as appointment) {
      <section class="ah-section rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="ah-card-title">Manage appointment</h2>
            <p class="mt-1 text-sm font-semibold text-slate-800">{{ appointment.patientDisplayName }} · {{ appointment.appointmentType.name }}</p>
            <p class="text-xs text-slate-500">Current: {{ displayStart(appointment) }} · {{ resourceNames(appointment) }}</p>
          </div>
          <button class="ah-link text-sm font-semibold" type="button" (click)="closeAppointment()">Close</button>
        </div>

        @if (appointment.status === 'SCHEDULED') {
          <div class="mt-4 grid gap-4 xl:grid-cols-2">
            <form
              class="rounded-md border border-slate-200 p-3"
              (submit)="rescheduleAppointment(appointment, rescheduleResource.value, rescheduleStart.value, rescheduleTimezone.value); $event.preventDefault()"
            >
              <h3 class="text-sm font-bold text-slate-900">Reschedule</h3>
              <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <label class="grid gap-1 text-xs font-bold text-slate-600">
                  Resource
                  <select #rescheduleResource class="ah-input" required>
                    @for (item of resourcesForFacility(appointment.facilityId); track item.id) {
                      <option [value]="item.id" [selected]="appointment.resources[0]?.id === item.id">{{ item.name }}</option>
                    }
                  </select>
                </label>
                <label class="grid gap-1 text-xs font-bold text-slate-600">
                  New local start
                  <input #rescheduleStart class="ah-input" type="datetime-local" [value]="defaultStart" required />
                </label>
                <label class="grid gap-1 text-xs font-bold text-slate-600 sm:col-span-2">
                  Timezone
                  <input #rescheduleTimezone class="ah-input" type="text" [value]="appointment.timezone" required />
                </label>
              </div>
              <button
                class="mt-3 rounded-md bg-[var(--ah-color-brand-cobalt)] px-3 py-2 text-sm font-bold text-white disabled:opacity-60"
                type="submit"
                [disabled]="lifecycleBusy()"
              >
                Reschedule
              </button>
            </form>

            <form
              class="rounded-md border border-red-200 bg-red-50/40 p-3"
              (submit)="cancelAppointment(appointment, cancellationReason.value); $event.preventDefault()"
            >
              <h3 class="text-sm font-bold text-red-900">Cancel appointment</h3>
              <label class="mt-3 grid gap-1 text-xs font-bold text-red-800">
                Cancellation reason
                <textarea #cancellationReason class="ah-input min-h-20" required placeholder="Patient requested cancellation"></textarea>
              </label>
              <button
                class="mt-3 rounded-md bg-red-700 px-3 py-2 text-sm font-bold text-white disabled:opacity-60"
                type="submit"
                [disabled]="lifecycleBusy()"
              >
                Cancel appointment
              </button>
            </form>
          </div>
        } @else {
          <p class="mt-3 text-sm text-slate-500">This appointment is cancelled and cannot be rescheduled or cancelled again.</p>
        }
      </section>
    }

    <section class="ah-section grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
      <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="ah-card-title">Waitlist</h2>
        <p class="mt-1 text-xs text-slate-500">Capture a preferred future window without creating a conflicting appointment.</p>
        @if (selectedPatient(); as patient) {
          <form
            class="mt-3 grid gap-3"
            (submit)="joinWaitlist(waitlistType.value, waitlistFrom.value, waitlistUntil.value, waitlistTimezone.value, waitlistReason.value); $event.preventDefault()"
          >
            <label class="grid gap-1 text-xs font-bold text-slate-600">
              Appointment type
              <select #waitlistType class="ah-input" required>
                <option value="">Select type</option>
                @for (type of appointmentTypesForPatient(); track type.id) {
                  <option [value]="type.id">{{ type.name }}</option>
                }
              </select>
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="grid gap-1 text-xs font-bold text-slate-600">
                Preferred from
                <input #waitlistFrom class="ah-input" type="datetime-local" [value]="defaultStart" required />
              </label>
              <label class="grid gap-1 text-xs font-bold text-slate-600">
                Preferred until
                <input #waitlistUntil class="ah-input" type="datetime-local" [value]="defaultWaitlistEnd" required />
              </label>
            </div>
            <label class="grid gap-1 text-xs font-bold text-slate-600">
              Timezone
              <input #waitlistTimezone class="ah-input" type="text" [value]="browserTimezone" required />
            </label>
            <label class="grid gap-1 text-xs font-bold text-slate-600">
              Preference / reason (optional)
              <input #waitlistReason class="ah-input" type="text" placeholder="Morning preferred" />
            </label>
            <button
              class="justify-self-start rounded-md bg-slate-800 px-3 py-2 text-sm font-bold text-white disabled:opacity-60"
              type="submit"
              [disabled]="waitlistBusy()"
            >
              Join waitlist for {{ patient.givenName }}
            </button>
          </form>
        } @else {
          <p class="mt-3 text-sm text-slate-500">Select a patient to join the waitlist.</p>
        }
      </div>

      <div>
        <div class="mb-2 flex items-center justify-between">
          <h2 class="ah-card-title">Current waitlist entries</h2>
          <ah-badge>{{ waitlistEntries().length }} entries</ah-badge>
        </div>
        <ah-table-shell>
          <table class="ah-table">
            <thead>
              <tr>
                <th>Patient</th>
                <th>Type</th>
                <th>Preferred window</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @for (entry of waitlistEntries(); track entry.id) {
                <tr>
                  <td class="font-semibold text-slate-900">{{ entry.patientDisplayName }}</td>
                  <td>{{ entry.appointmentType.name }}</td>
                  <td class="text-xs">{{ displayWaitlistWindow(entry) }}</td>
                  <td>
                    @if (entry.status === 'WAITING') {
                      <ah-badge tone="success">WAITING</ah-badge>
                    } @else {
                      <ah-badge>{{ entry.status }}</ah-badge>
                    }
                  </td>
                  <td class="text-right">
                    @if (entry.status === 'WAITING') {
                      <button class="ah-link font-semibold" type="button" (click)="cancelWaitlist(entry)">Cancel</button>
                    }
                  </td>
                </tr>
              } @empty {
                <tr>
                  <td colspan="5" class="py-6 text-center text-sm text-slate-500">No waitlist entries.</td>
                </tr>
              }
            </tbody>
          </table>
        </ah-table-shell>
      </div>
    </section>
  `,
})
export class SchedulingPage implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly patientDirectory = inject(PatientDirectoryService);
  private readonly scheduling = inject(SchedulingService);

  protected readonly patients = signal<readonly PatientSummary[]>([]);
  protected readonly selectedPatient = signal<PatientSummary | null>(null);
  protected readonly resources = signal<readonly SchedulingResource[]>([]);
  protected readonly appointmentTypes = signal<readonly AppointmentType[]>([]);
  protected readonly appointments = signal<readonly Appointment[]>([]);
  protected readonly waitlistEntries = signal<readonly WaitlistEntry[]>([]);
  protected readonly selectedAppointment = signal<Appointment | null>(null);
  protected readonly booking = signal(false);
  protected readonly lifecycleBusy = signal(false);
  protected readonly waitlistBusy = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly notice = signal<string | null>(null);

  protected readonly browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
  protected readonly defaultStart = this.futureAt(1, 10);
  protected readonly defaultWaitlistEnd = this.futureAt(7, 17);

  private idempotencyKey: string | null = null;
  private waitlistIdempotencyKey: string | null = null;

  private readonly membership = computed(
    () => this.auth.user()?.memberships.find((membership) => membership.status === 'ACTIVE') ?? null,
  );

  protected readonly resourcesForPatient = computed(() => {
    const patient = this.selectedPatient();
    return patient ? this.resourcesForFacility(patient.registrationFacilityId) : [];
  });

  protected readonly appointmentTypesForPatient = computed(() => {
    const patient = this.selectedPatient();
    return patient ? this.appointmentTypes().filter((type) => type.facilityId === patient.registrationFacilityId) : [];
  });

  ngOnInit(): void {
    this.loadCalendar();
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
      this.error.set('Enter at least two characters to search patients.');
      return;
    }

    const facilityId = membership.allFacilities ? null : membership.facilityIds[0] ?? null;
    this.patientDirectory.search(membership.organizationId, facilityId, trimmed).subscribe({
      next: (result) => {
        this.patients.set(result.items);
        this.error.set(null);
      },
      error: (error: unknown) => this.error.set(this.message(error, 'Patient search failed.')),
    });
  }

  protected selectPatient(patient: PatientSummary): void {
    this.selectedPatient.set(patient);
    this.notice.set(null);
    this.error.set(null);
    this.idempotencyKey = null;
    this.waitlistIdempotencyKey = null;
  }

  protected selectAppointment(appointment: Appointment): void {
    this.selectedAppointment.set(appointment);
    this.notice.set(null);
    this.error.set(null);
  }

  protected closeAppointment(): void {
    this.selectedAppointment.set(null);
  }

  protected book(
    appointmentTypeId: string,
    resourceId: string,
    startsAtLocal: string,
    timezone: string,
    reason: string,
  ): void {
    const membership = this.membership();
    const patient = this.selectedPatient();
    if (!membership || !patient) {
      this.error.set('Select an active workspace and patient first.');
      return;
    }
    if (!appointmentTypeId || !resourceId || !startsAtLocal || !timezone.trim()) {
      this.error.set('Appointment type, resource, start time, and timezone are required.');
      return;
    }

    this.booking.set(true);
    this.error.set(null);
    this.notice.set(null);
    this.idempotencyKey ??= this.newIdempotencyKey('appointment');

    this.scheduling.book({
      organizationId: membership.organizationId,
      facilityId: patient.registrationFacilityId,
      patientId: patient.id,
      appointmentTypeId,
      resourceIds: [resourceId],
      startsAtLocal,
      timezone: timezone.trim(),
      reason: reason.trim() || null,
      idempotencyKey: this.idempotencyKey,
    }).subscribe({
      next: (result) => {
        this.notice.set(result.replayed ? 'Existing appointment returned from retry protection.' : 'Appointment booked.');
        this.idempotencyKey = null;
        this.booking.set(false);
        this.refreshAppointments();
      },
      error: (error: unknown) => {
        this.error.set(this.message(error, 'Appointment booking failed.'));
        this.booking.set(false);
      },
    });
  }

  protected rescheduleAppointment(
    appointment: Appointment,
    resourceId: string,
    startsAtLocal: string,
    timezone: string,
  ): void {
    const membership = this.membership();
    if (!membership || !resourceId || !startsAtLocal || !timezone.trim()) {
      this.error.set('Resource, new start time, and timezone are required.');
      return;
    }

    this.lifecycleBusy.set(true);
    this.error.set(null);
    this.scheduling.reschedule({
      organizationId: membership.organizationId,
      facilityId: appointment.facilityId,
      patientId: appointment.patientId,
      appointmentId: appointment.id,
      resourceIds: [resourceId],
      startsAtLocal,
      timezone: timezone.trim(),
    }).subscribe({
      next: (updated) => {
        this.selectedAppointment.set(updated);
        this.notice.set('Appointment rescheduled. Conflict rules were re-evaluated.');
        this.lifecycleBusy.set(false);
        this.refreshAppointments();
      },
      error: (error: unknown) => {
        this.error.set(this.message(error, 'Appointment reschedule failed.'));
        this.lifecycleBusy.set(false);
      },
    });
  }

  protected cancelAppointment(appointment: Appointment, reason: string): void {
    const membership = this.membership();
    const normalizedReason = reason.trim();
    if (!membership || normalizedReason.length < 3) {
      this.error.set('Enter a cancellation reason.');
      return;
    }

    this.lifecycleBusy.set(true);
    this.error.set(null);
    this.scheduling.cancelAppointment({
      organizationId: membership.organizationId,
      facilityId: appointment.facilityId,
      patientId: appointment.patientId,
      appointmentId: appointment.id,
      reason: normalizedReason,
    }).subscribe({
      next: (updated) => {
        this.selectedAppointment.set(updated);
        this.notice.set('Appointment cancelled. The slot is now available for another booking.');
        this.lifecycleBusy.set(false);
        this.refreshAppointments();
      },
      error: (error: unknown) => {
        this.error.set(this.message(error, 'Appointment cancellation failed.'));
        this.lifecycleBusy.set(false);
      },
    });
  }

  protected joinWaitlist(
    appointmentTypeId: string,
    preferredFromLocal: string,
    preferredUntilLocal: string,
    timezone: string,
    reason: string,
  ): void {
    const membership = this.membership();
    const patient = this.selectedPatient();
    if (!membership || !patient) {
      this.error.set('Select a patient before joining the waitlist.');
      return;
    }
    if (!appointmentTypeId || !preferredFromLocal || !preferredUntilLocal || !timezone.trim()) {
      this.error.set('Appointment type, preferred window, and timezone are required.');
      return;
    }

    this.waitlistBusy.set(true);
    this.error.set(null);
    this.waitlistIdempotencyKey ??= this.newIdempotencyKey('waitlist');
    this.scheduling.joinWaitlist({
      organizationId: membership.organizationId,
      facilityId: patient.registrationFacilityId,
      patientId: patient.id,
      appointmentTypeId,
      preferredFromLocal,
      preferredUntilLocal,
      timezone: timezone.trim(),
      reason: reason.trim() || null,
      idempotencyKey: this.waitlistIdempotencyKey,
    }).subscribe({
      next: (result) => {
        this.notice.set(result.replayed ? 'Existing waitlist entry returned from retry protection.' : 'Patient added to waitlist.');
        this.waitlistIdempotencyKey = null;
        this.waitlistBusy.set(false);
        this.refreshWaitlist();
      },
      error: (error: unknown) => {
        this.error.set(this.message(error, 'Joining the waitlist failed.'));
        this.waitlistBusy.set(false);
      },
    });
  }

  protected cancelWaitlist(entry: WaitlistEntry): void {
    const membership = this.membership();
    if (!membership) {
      this.error.set('No active organization membership is available.');
      return;
    }

    this.waitlistBusy.set(true);
    this.error.set(null);
    this.scheduling.cancelWaitlist({
      organizationId: membership.organizationId,
      facilityId: entry.facilityId,
      patientId: entry.patientId,
      waitlistEntryId: entry.id,
      reason: 'Cancelled from scheduling workspace',
    }).subscribe({
      next: () => {
        this.notice.set('Waitlist entry cancelled.');
        this.waitlistBusy.set(false);
        this.refreshWaitlist();
      },
      error: (error: unknown) => {
        this.error.set(this.message(error, 'Waitlist cancellation failed.'));
        this.waitlistBusy.set(false);
      },
    });
  }

  protected mrn(patient: PatientSummary): string | null {
    return patient.identifiers.find((identifier) => identifier.type === 'MRN')?.value ?? null;
  }

  protected resourceNames(appointment: Appointment): string {
    return appointment.resources.map((resource) => resource.name).join(', ');
  }

  protected resourcesForFacility(facilityId: string): readonly SchedulingResource[] {
    return this.resources().filter((resource) => resource.facilityId === facilityId);
  }

  protected displayStart(appointment: Appointment): string {
    return this.displayTimestamp(appointment.startsAt, appointment.timezone);
  }

  protected displayWaitlistWindow(entry: WaitlistEntry): string {
    return `${this.displayTimestamp(entry.preferredFrom, entry.timezone)} → ${this.displayTimestamp(entry.preferredUntil, entry.timezone)}`;
  }

  private loadCalendar(): void {
    const membership = this.membership();
    if (!membership) {
      this.error.set('No active organization membership is available.');
      return;
    }

    const facilityId = membership.allFacilities ? null : membership.facilityIds[0] ?? null;
    this.scheduling.resources(membership.organizationId, facilityId).subscribe({
      next: (resources) => this.resources.set(resources),
      error: (error: unknown) => this.error.set(this.message(error, 'Scheduling resources failed to load.')),
    });
    this.scheduling.appointmentTypes(membership.organizationId, facilityId).subscribe({
      next: (types) => this.appointmentTypes.set(types),
      error: (error: unknown) => this.error.set(this.message(error, 'Appointment types failed to load.')),
    });
    this.refreshAppointments();
    this.refreshWaitlist();
  }

  private refreshAppointments(): void {
    const membership = this.membership();
    if (!membership) {
      return;
    }

    const facilityId = membership.allFacilities ? null : membership.facilityIds[0] ?? null;
    const from = new Date();
    from.setHours(0, 0, 0, 0);
    const to = new Date(from.getTime() + 7 * 24 * 60 * 60 * 1000);
    this.scheduling
      .appointments(membership.organizationId, facilityId, from.toISOString(), to.toISOString())
      .subscribe({
        next: (appointments) => this.appointments.set(appointments),
        error: (error: unknown) => this.error.set(this.message(error, 'Appointments failed to load.')),
      });
  }

  private refreshWaitlist(): void {
    const membership = this.membership();
    if (!membership) {
      return;
    }
    const facilityId = membership.allFacilities ? null : membership.facilityIds[0] ?? null;
    this.scheduling.waitlist(membership.organizationId, facilityId).subscribe({
      next: (entries) => this.waitlistEntries.set(entries),
      error: (error: unknown) => this.error.set(this.message(error, 'Waitlist failed to load.')),
    });
  }

  private newIdempotencyKey(prefix: string): string {
    if (globalThis.crypto?.randomUUID) {
      return `${prefix}-${globalThis.crypto.randomUUID()}`;
    }

    return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2)}`;
  }

  private futureAt(days: number, hour: number): string {
    const value = new Date(Date.now() + days * 24 * 60 * 60 * 1000);
    value.setHours(hour, 0, 0, 0);
    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');
    const hours = String(hour).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:00`;
  }

  private displayTimestamp(value: string, timezone: string): string {
    try {
      return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: timezone,
      }).format(new Date(value));
    } catch {
      return value;
    }
  }

  private message(error: unknown, fallback: string): string {
    return error instanceof Error ? error.message : fallback;
  }
}
