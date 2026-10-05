import { ChangeDetectionStrategy, Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { forkJoin, Observable, switchMap } from 'rxjs';
import { AuthService } from '../../core/auth/auth.service';
import {
  ClinicalNote,
  ClinicalRecord,
  ClinicalRecordService,
} from '../../core/clinical/clinical-record.service';
import { Encounter, EncounterService } from '../../core/encounter/encounter.service';
import { PatientDirectoryService } from '../../core/patient/patient-directory.service';
import { PatientContextService } from '../../core/patient/patient-context.service';
import { AhPageHeaderComponent } from '../../layout';
import { PatientContextBannerComponent } from '../../shared/patient-context-banner/patient-context-banner';
import { AhCardComponent, AhTableShellComponent } from '../../shared/ui';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    FormsModule,
    RouterLink,
    AhPageHeaderComponent,
    PatientContextBannerComponent,
    AhCardComponent,
    AhTableShellComponent,
  ],
  selector: 'ah-clinical-record-page',
  template: `
    <ah-page-header
      title="Clinical record"
      subtitle="Problems, allergies, observations, clinical notes, provenance, and patient timeline."
    >
      <div ahPageActions class="flex items-center gap-3">
        <a class="ah-link font-semibold" [routerLink]="patientLink()">Patient record</a>
        <a class="ah-link font-semibold" routerLink="/encounters">Encounters</a>
      </div>
    </ah-page-header>

    <ah-patient-context-banner [patient]="patientContext.patient()" />

    @if (error()) {
      <div class="ah-section rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        {{ error() }}
      </div>
    }
    @if (success()) {
      <div class="ah-section rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
        {{ success() }}
      </div>
    }

    @if (loading()) {
      <div class="ah-section text-sm text-slate-600" role="status">Loading clinical record…</div>
    } @else if (record(); as currentRecord) {
      <section class="ah-section grid gap-3 lg:grid-cols-3">
        <ah-card title="Clinical context">
          @if (activeEncounter(); as encounter) {
            <dl class="grid gap-2 text-sm">
              <div><dt class="text-slate-500">Encounter</dt><dd class="font-mono text-xs">{{ encounter.id }}</dd></div>
              <div><dt class="text-slate-500">Type</dt><dd class="font-semibold">{{ encounter.type }}</dd></div>
              <div><dt class="text-slate-500">Status</dt><dd class="font-semibold text-emerald-700">{{ encounter.status }}</dd></div>
            </dl>
          } @else {
            <p class="text-sm text-amber-800">
              No in-progress encounter. Clinical writes are disabled until the patient has an IN_PROGRESS encounter.
            </p>
          }
        </ah-card>

        <ah-card title="Problems">
          <div class="mb-3 grid gap-2">
            <input
              class="rounded-md border border-slate-300 px-3 py-2 text-sm"
              [(ngModel)]="problemDisplay"
              aria-label="Problem description"
              placeholder="Problem description"
              [disabled]="!canWrite()"
            />
            <button
              class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
              type="button"
              [disabled]="!canWrite() || !problemDisplay.trim() || saving()"
              (click)="addProblem()"
            >
              Record problem
            </button>
          </div>
          @for (problem of currentRecord.problems; track problem.id) {
            <div class="mb-2 rounded-md border border-slate-200 px-3 py-2 text-sm">
              <div class="font-semibold text-slate-900">{{ problem.display }}</div>
              <div class="text-xs text-slate-500">{{ problem.status }} · {{ problem.recordedAt }}</div>
            </div>
          } @empty {
            <p class="text-sm text-slate-500">No problems recorded.</p>
          }
        </ah-card>

        <ah-card title="Allergies">
          <div class="mb-3 grid gap-2">
            <input
              class="rounded-md border border-slate-300 px-3 py-2 text-sm"
              [(ngModel)]="allergySubstance"
              aria-label="Allergy substance"
              placeholder="Substance"
              [disabled]="!canWrite()"
            />
            <input
              class="rounded-md border border-slate-300 px-3 py-2 text-sm"
              [(ngModel)]="allergyReaction"
              aria-label="Allergy reaction"
              placeholder="Reaction (optional)"
              [disabled]="!canWrite()"
            />
            <select
              class="rounded-md border border-slate-300 px-3 py-2 text-sm"
              [(ngModel)]="allergySeverity"
              aria-label="Allergy severity"
              [disabled]="!canWrite()"
            >
              <option value="UNKNOWN">Unknown severity</option>
              <option value="MILD">Mild</option>
              <option value="MODERATE">Moderate</option>
              <option value="SEVERE">Severe</option>
            </select>
            <button
              class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
              type="button"
              [disabled]="!canWrite() || !allergySubstance.trim() || saving()"
              (click)="addAllergy()"
            >
              Record allergy
            </button>
          </div>
          @for (allergy of currentRecord.allergies; track allergy.id) {
            <div class="mb-2 rounded-md border border-slate-200 px-3 py-2 text-sm">
              <div class="font-semibold text-slate-900">{{ allergy.substance }}</div>
              <div class="text-xs text-slate-500">
                {{ allergy.severity }} · {{ allergy.verificationStatus }}
                @if (allergy.reaction) { · {{ allergy.reaction }} }
              </div>
            </div>
          } @empty {
            <p class="text-sm text-slate-500">No allergies recorded.</p>
          }
        </ah-card>
      </section>

      <section class="ah-section grid gap-3 xl:grid-cols-2">
        <ah-card title="Vitals / observations">
          <div class="mb-4 grid gap-2 sm:grid-cols-2">
            <select
              class="rounded-md border border-slate-300 px-3 py-2 text-sm"
              [(ngModel)]="observationCode"
              aria-label="Observation type"
              [disabled]="!canWrite()"
              (ngModelChange)="setObservationPreset($event)"
            >
              <option value="HEART_RATE">Heart rate</option>
              <option value="SPO2">Oxygen saturation</option>
              <option value="BODY_TEMPERATURE">Body temperature</option>
              <option value="RESPIRATORY_RATE">Respiratory rate</option>
              <option value="SYSTOLIC_BP">Systolic blood pressure</option>
              <option value="DIASTOLIC_BP">Diastolic blood pressure</option>
              <option value="WEIGHT">Weight</option>
              <option value="HEIGHT">Height</option>
            </select>
            <input
              class="rounded-md border border-slate-300 px-3 py-2 text-sm"
              type="number"
              [(ngModel)]="observationValue"
              aria-label="Observation value"
              placeholder="Value"
              [disabled]="!canWrite()"
            />
            <input
              class="rounded-md border border-slate-300 px-3 py-2 text-sm"
              [(ngModel)]="observationUnit"
              aria-label="Observation unit"
              placeholder="Unit"
              [disabled]="!canWrite()"
            />
            <button
              class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
              type="button"
              [disabled]="!canWrite() || observationValue === null || saving()"
              (click)="addObservation()"
            >
              Record observation
            </button>
          </div>

          <ah-table-shell>
            <table class="ah-table">
              <thead><tr><th>Observation</th><th>Value</th><th>Effective</th></tr></thead>
              <tbody>
                @for (observation of currentRecord.observations; track observation.id) {
                  <tr>
                    <td class="font-semibold">{{ observation.display }}</td>
                    <td>{{ observation.valueNumeric ?? observation.valueText }} {{ observation.unit ?? '' }}</td>
                    <td>{{ observation.effectiveAt }}</td>
                  </tr>
                } @empty {
                  <tr><td colspan="3" class="py-5 text-center text-sm text-slate-500">No observations recorded.</td></tr>
                }
              </tbody>
            </table>
          </ah-table-shell>
        </ah-card>

        <ah-card title="Clinical note">
          <div class="grid gap-2">
            <input
              class="rounded-md border border-slate-300 px-3 py-2 text-sm"
              [(ngModel)]="noteTitle"
              aria-label="Clinical note title"
              placeholder="Note title"
              [disabled]="!canWrite()"
            />
            <textarea
              class="min-h-32 rounded-md border border-slate-300 px-3 py-2 text-sm"
              [(ngModel)]="noteBody"
              aria-label="Clinical note body"
              placeholder="Clinical note"
              [disabled]="!canWrite()"
            ></textarea>
            <div class="flex flex-wrap gap-2">
              <button
                class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                type="button"
                [disabled]="!canWrite() || !noteBody.trim() || saving()"
                (click)="saveDraft()"
              >
                {{ editingNoteId ? 'Update draft' : 'Create draft' }}
              </button>
              @if (editingNoteId) {
                <button
                  class="rounded-md border border-emerald-700 px-3 py-2 text-sm font-semibold text-emerald-800 disabled:opacity-50"
                  type="button"
                  [disabled]="saving()"
                  (click)="signDraft(editingNoteId)"
                >
                  Sign draft
                </button>
                <button
                  class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold"
                  type="button"
                  (click)="clearDraftEditor()"
                >
                  Cancel edit
                </button>
              }
            </div>
          </div>

          <div class="mt-4 grid gap-3">
            @for (note of currentRecord.notes; track note.id) {
              <article class="rounded-md border border-slate-200 p-3">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="font-semibold text-slate-900">{{ note.title || note.noteType }}</div>
                    <div class="text-xs text-slate-500">{{ note.status }} · {{ note.createdAt }}</div>
                  </div>
                  <div class="flex gap-2">
                    @if (note.status === 'DRAFT') {
                      <button class="ah-link text-xs font-semibold" type="button" (click)="editDraft(note)">Edit</button>
                      <button class="ah-link text-xs font-semibold" type="button" (click)="signDraft(note.id)">Sign</button>
                    } @else {
                      <button class="ah-link text-xs font-semibold" type="button" (click)="selectSignedNote(note)">
                        Amend
                      </button>
                    }
                  </div>
                </div>
                <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ note.body }}</p>
                @for (amendment of note.amendments; track amendment.id) {
                  <div class="mt-2 rounded bg-slate-50 px-3 py-2 text-sm">
                    <div class="text-xs font-bold text-slate-500">{{ amendment.type }}</div>
                    <div>{{ amendment.body }}</div>
                    @if (amendment.reason) { <div class="text-xs text-slate-500">Reason: {{ amendment.reason }}</div> }
                  </div>
                }
              </article>
            } @empty {
              <p class="text-sm text-slate-500">No clinical notes.</p>
            }
          </div>

          @if (amendingNoteId) {
            <div class="mt-4 grid gap-2 rounded-md border border-slate-200 bg-slate-50 p-3">
              <div class="text-sm font-semibold">Add signed-note amendment</div>
              <textarea
                class="min-h-24 rounded-md border border-slate-300 px-3 py-2 text-sm"
                [(ngModel)]="amendmentBody"
                aria-label="Signed-note amendment content"
                placeholder="Addendum or correction content"
              ></textarea>
              <input
                class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                [(ngModel)]="amendmentReason"
                aria-label="Signed-note correction reason"
                placeholder="Correction reason (required for correction)"
              />
              <div class="flex flex-wrap gap-2">
                <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold" type="button" (click)="addAmendment('ADDENDUM')">
                  Add addendum
                </button>
                <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold" type="button" (click)="addAmendment('CORRECTION')">
                  Add correction
                </button>
                <button class="ah-link text-sm font-semibold" type="button" (click)="clearAmendmentEditor()">Cancel</button>
              </div>
            </div>
          }
        </ah-card>
      </section>

      <section class="ah-section">
        <h2 class="ah-card-title mb-2">Patient timeline</h2>
        <ah-table-shell>
          <table class="ah-table">
            <thead><tr><th>When</th><th>Event</th><th>Resource</th><th>Encounter</th></tr></thead>
            <tbody>
              @for (event of currentRecord.timeline; track event.id) {
                <tr>
                  <td>{{ event.occurredAt }}</td>
                  <td class="font-semibold">{{ event.label }}</td>
                  <td>{{ event.resourceType }}</td>
                  <td class="font-mono text-xs">{{ event.encounterId || '—' }}</td>
                </tr>
              } @empty {
                <tr><td colspan="4" class="py-5 text-center text-sm text-slate-500">No timeline events.</td></tr>
              }
            </tbody>
          </table>
        </ah-table-shell>
      </section>
    }
  `,
})
export class ClinicalRecordPage implements OnInit {
  protected readonly patientContext = inject(PatientContextService);
  private readonly route = inject(ActivatedRoute);
  private readonly auth = inject(AuthService);
  private readonly patients = inject(PatientDirectoryService);
  private readonly encountersService = inject(EncounterService);
  private readonly clinical = inject(ClinicalRecordService);

  protected readonly record = signal<ClinicalRecord | null>(null);
  protected readonly encounters = signal<readonly Encounter[]>([]);
  protected readonly loading = signal(false);
  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly success = signal<string | null>(null);

  protected problemDisplay = '';
  protected allergySubstance = '';
  protected allergyReaction = '';
  protected allergySeverity: 'UNKNOWN' | 'MILD' | 'MODERATE' | 'SEVERE' = 'UNKNOWN';
  protected observationCode = 'HEART_RATE';
  protected observationDisplay = 'Heart rate';
  protected observationUnit = 'beats/min';
  protected observationValue: number | null = null;
  protected noteTitle = '';
  protected noteBody = '';
  protected editingNoteId: string | null = null;
  protected amendingNoteId: string | null = null;
  protected amendmentBody = '';
  protected amendmentReason = '';

  private readonly organizationId = computed(
    () => this.auth.user()?.memberships.find((membership) => membership.status === 'ACTIVE')?.organizationId ?? null,
  );

  protected readonly activeEncounter = computed(
    () => this.encounters().find((encounter) => encounter.status === 'IN_PROGRESS') ?? null,
  );

  protected readonly canWrite = computed(() => this.activeEncounter() !== null);

  protected readonly patientLink = computed(() => {
    const patientId = this.patientContext.patient()?.id;
    return patientId ? `/patients/${patientId}` : '/patients';
  });

  ngOnInit(): void {
    const patientId = this.route.snapshot.paramMap.get('patientId');
    const organizationId = this.organizationId();
    if (!patientId || !organizationId) {
      this.error.set('Patient or organization context is missing.');
      return;
    }

    this.loading.set(true);
    this.patients.patient(organizationId, patientId).pipe(
      switchMap((patient) => {
        this.patientContext.select({
          id: patient.id,
          organizationId: patient.organizationId,
          registrationFacilityId: patient.registrationFacilityId,
          displayName: `${patient.givenName} ${patient.familyName}`,
          dateOfBirth: patient.dateOfBirth,
          sexAtBirth: patient.sexAtBirth,
          mrn: patient.identifiers.find((identifier) => identifier.type === 'MRN')?.value ?? null,
        });

        return forkJoin({
          encounters: this.encountersService.encounters(
            patient.organizationId,
            patient.registrationFacilityId,
            patient.id,
          ),
          record: this.clinical.record(
            patient.organizationId,
            patient.registrationFacilityId,
            patient.id,
          ),
        });
      }),
    ).subscribe({
      next: ({ encounters, record }) => {
        this.encounters.set(encounters);
        this.record.set(record);
        this.loading.set(false);
      },
      error: (error: unknown) => {
        this.error.set(error instanceof Error ? error.message : 'Clinical record could not be loaded.');
        this.loading.set(false);
      },
    });
  }

  protected addProblem(): void {
    const context = this.writeContext();
    if (!context) return;
    this.runWrite(
      this.clinical.recordProblem({ ...context, display: this.problemDisplay.trim(), status: 'ACTIVE' }),
      'Problem recorded.',
      () => { this.problemDisplay = ''; },
    );
  }

  protected addAllergy(): void {
    const context = this.writeContext();
    if (!context) return;
    this.runWrite(
      this.clinical.recordAllergy({
        ...context,
        substance: this.allergySubstance.trim(),
        reaction: this.allergyReaction.trim() || null,
        severity: this.allergySeverity,
        status: 'ACTIVE',
        verificationStatus: 'UNVERIFIED',
      }),
      'Allergy recorded.',
      () => {
        this.allergySubstance = '';
        this.allergyReaction = '';
        this.allergySeverity = 'UNKNOWN';
      },
    );
  }

  protected addObservation(): void {
    const context = this.writeContext();
    if (!context || this.observationValue === null) return;
    this.runWrite(
      this.clinical.recordObservation({
        ...context,
        codeSystem: 'urn:aurevia:core:vitals',
        code: this.observationCode,
        display: this.observationDisplay,
        valueNumeric: this.observationValue,
        unit: this.observationUnit,
      }),
      'Observation recorded.',
      () => { this.observationValue = null; },
    );
  }

  protected setObservationPreset(code: string): void {
    const presets: Record<string, { display: string; unit: string }> = {
      HEART_RATE: { display: 'Heart rate', unit: 'beats/min' },
      SPO2: { display: 'Oxygen saturation', unit: '%' },
      BODY_TEMPERATURE: { display: 'Body temperature', unit: '°C' },
      RESPIRATORY_RATE: { display: 'Respiratory rate', unit: 'breaths/min' },
      SYSTOLIC_BP: { display: 'Systolic blood pressure', unit: 'mmHg' },
      DIASTOLIC_BP: { display: 'Diastolic blood pressure', unit: 'mmHg' },
      WEIGHT: { display: 'Weight', unit: 'kg' },
      HEIGHT: { display: 'Height', unit: 'cm' },
    };
    const preset = presets[code];
    if (preset) {
      this.observationDisplay = preset.display;
      this.observationUnit = preset.unit;
    }
  }

  protected saveDraft(): void {
    const context = this.writeContext();
    if (!context || !this.noteBody.trim()) return;

    const operation = this.editingNoteId
      ? this.clinical.updateDraft({
          ...context,
          noteId: this.editingNoteId,
          title: this.noteTitle.trim() || null,
          body: this.noteBody.trim(),
        })
      : this.clinical.createNote({
          ...context,
          noteType: 'PROGRESS',
          title: this.noteTitle.trim() || null,
          body: this.noteBody.trim(),
        });

    this.runWrite(operation, this.editingNoteId ? 'Draft updated.' : 'Draft created.', () => this.clearDraftEditor());
  }

  protected editDraft(note: ClinicalNote): void {
    this.editingNoteId = note.id;
    this.noteTitle = note.title ?? '';
    this.noteBody = note.body;
    this.success.set(null);
    this.error.set(null);
  }

  protected signDraft(noteId: string): void {
    const context = this.writeContext();
    if (!context) return;
    this.runWrite(
      this.clinical.sign({ ...context, noteId }),
      'Clinical note signed. Signed content is now immutable.',
      () => this.clearDraftEditor(),
    );
  }

  protected selectSignedNote(note: ClinicalNote): void {
    this.amendingNoteId = note.id;
    this.amendmentBody = '';
    this.amendmentReason = '';
  }

  protected addAmendment(type: 'ADDENDUM' | 'CORRECTION'): void {
    const patient = this.patientContext.patient();
    const note = this.record()?.notes.find((candidate) => candidate.id === this.amendingNoteId);
    if (!patient || !note || !this.amendmentBody.trim()) return;
    if (type === 'CORRECTION' && !this.amendmentReason.trim()) {
      this.error.set('A correction requires a reason.');
      return;
    }

    this.runWrite(
      this.clinical.amend({
        organizationId: patient.organizationId,
        facilityId: patient.registrationFacilityId,
        patientId: patient.id,
        encounterId: note.encounterId,
        noteId: note.id,
        type,
        body: this.amendmentBody.trim(),
        reason: this.amendmentReason.trim() || null,
      }),
      type === 'CORRECTION' ? 'Correction appended.' : 'Addendum appended.',
      () => this.clearAmendmentEditor(),
    );
  }

  protected clearDraftEditor(): void {
    this.editingNoteId = null;
    this.noteTitle = '';
    this.noteBody = '';
  }

  protected clearAmendmentEditor(): void {
    this.amendingNoteId = null;
    this.amendmentBody = '';
    this.amendmentReason = '';
  }

  private writeContext(): {
    organizationId: string;
    facilityId: string;
    patientId: string;
    encounterId: string;
  } | null {
    const patient = this.patientContext.patient();
    const encounter = this.activeEncounter();
    if (!patient || !encounter) {
      this.error.set('An in-progress encounter is required for clinical documentation.');
      return null;
    }

    return {
      organizationId: patient.organizationId,
      facilityId: patient.registrationFacilityId,
      patientId: patient.id,
      encounterId: encounter.id,
    };
  }

  private runWrite<T>(
    operation: Observable<T>,
    successMessage: string,
    reset?: () => void,
  ): void {
    this.saving.set(true);
    this.error.set(null);
    this.success.set(null);
    operation.subscribe({
      next: () => {
        reset?.();
        this.success.set(successMessage);
        this.saving.set(false);
        this.refreshRecord();
      },
      error: (error: unknown) => {
        this.error.set(error instanceof Error ? error.message : 'Clinical change could not be saved.');
        this.saving.set(false);
      },
    });
  }

  private refreshRecord(): void {
    const patient = this.patientContext.patient();
    if (!patient) return;

    this.clinical.record(
      patient.organizationId,
      patient.registrationFacilityId,
      patient.id,
    ).subscribe({
      next: (record) => this.record.set(record),
      error: (error: unknown) =>
        this.error.set(error instanceof Error ? error.message : 'Clinical record could not be refreshed.'),
    });
  }
}
