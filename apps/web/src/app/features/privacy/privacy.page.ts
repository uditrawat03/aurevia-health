import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { PatientContextService } from '../../core/patient/patient-context.service';
import {
  BreakGlassAccess,
  ConsentDataCategory,
  ConsentPurpose,
  ConsentRecipientClass,
  PatientConsent,
  PrivacyDecision,
  PrivacyDirectoryService,
} from '../../core/privacy/privacy-directory.service';
import { AhPageHeaderComponent } from '../../layout';
import { PatientContextBannerComponent } from '../../shared/patient-context-banner/patient-context-banner';
import { AhBadgeComponent, AhCardComponent, AhTableShellComponent } from '../../shared/ui';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    RouterLink,
    AhPageHeaderComponent,
    PatientContextBannerComponent,
    AhBadgeComponent,
    AhCardComponent,
    AhTableShellComponent,
  ],
  selector: 'ah-privacy-page',
  template: `
    <ah-page-header
      title="Privacy & consent"
      subtitle="Explicit patient consent, revocation, policy decisions, and audited emergency access."
    >
      <a ahPageActions class="ah-link font-semibold" routerLink="/patients">Back to patients</a>
    </ah-page-header>

    @if (patientContext.patient(); as currentPatient) {
      <ah-patient-context-banner [patient]="currentPatient" />

      @if (error()) {
        <div class="ah-section rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
          {{ error() }}
        </div>
      }
      @if (message()) {
        <div class="ah-section rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
          {{ message() }}
        </div>
      }

      <section class="ah-section grid gap-3 lg:grid-cols-2">
        <ah-card title="Current treatment decision">
          @if (decision(); as currentDecision) {
            <div class="flex items-center gap-2">
              <ah-badge [tone]="currentDecision.allowed ? 'success' : 'danger'">
                {{ currentDecision.allowed ? 'ALLOWED' : 'DENIED' }}
              </ah-badge>
              <span class="text-sm font-semibold text-slate-700">{{ currentDecision.reason }}</span>
            </div>
            @if (currentDecision.breakGlassAccessId) {
              <p class="mt-2 text-xs text-slate-500">
                Break-glass record {{ currentDecision.breakGlassAccessId }} is currently authorizing this access.
              </p>
            }
          } @else {
            <p class="text-sm text-slate-500">{{ loading() ? 'Evaluating privacy policy…' : 'No policy decision loaded.' }}</p>
          }
        </ah-card>

        <ah-card title="Emergency break-glass">
          <p class="mb-3 text-sm text-slate-600">
            Emergency access is treatment-only, expires after 15 minutes, and requires a specific audited reason.
          </p>
          <label class="grid gap-1.5 text-sm font-semibold text-slate-700">
            Reason
            <textarea #breakGlassReason class="ah-input min-h-24 py-2" placeholder="Clinical emergency requiring immediate patient access"></textarea>
          </label>
          <button
            class="mt-3 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-bold text-amber-900 disabled:opacity-60"
            type="button"
            [disabled]="loading()"
            (click)="activateBreakGlass(breakGlassReason.value)"
          >
            Activate 15-minute break-glass
          </button>
          @if (breakGlass(); as access) {
            <div class="mt-3 rounded-md bg-amber-50 p-3 text-xs text-amber-900">
              Active until {{ access.expiresAt }}
            </div>
          }
        </ah-card>
      </section>

      <section class="ah-section rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="font-bold text-slate-900">Grant consent</h2>
        <p class="mt-1 text-sm text-slate-600">
          Consent is an additional privacy layer. It does not replace organization role or facility authorization.
        </p>

        <form
          class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5"
          (submit)="grant(category.value, purpose.value, recipient.value, scope.value, effectiveUntil.value); $event.preventDefault()"
        >
          <label class="grid gap-1.5 text-sm font-semibold text-slate-700">
            Data category
            <select #category class="ah-select">
              <option value="DEMOGRAPHICS">Demographics</option>
              <option value="CLINICAL">Clinical</option>
              <option value="BILLING">Billing</option>
              <option value="RESEARCH">Research</option>
            </select>
          </label>

          <label class="grid gap-1.5 text-sm font-semibold text-slate-700">
            Purpose
            <select #purpose class="ah-select">
              <option value="TREATMENT">Treatment</option>
              <option value="CARE_COORDINATION">Care coordination</option>
              <option value="OPERATIONS">Operations</option>
              <option value="BILLING">Billing</option>
              <option value="RESEARCH">Research</option>
            </select>
          </label>

          <label class="grid gap-1.5 text-sm font-semibold text-slate-700">
            Recipient
            <select #recipient class="ah-select">
              <option value="CARE_TEAM">Care team</option>
              <option value="ORGANIZATION_STAFF">Organization staff</option>
              <option value="EXTERNAL_PROVIDER">External provider</option>
              <option value="RESEARCH_TEAM">Research team</option>
            </select>
          </label>

          <label class="grid gap-1.5 text-sm font-semibold text-slate-700">
            Scope
            <select #scope class="ah-select">
              <option value="FACILITY">Current facility</option>
              <option value="ORGANIZATION">Organization-wide</option>
            </select>
          </label>

          <label class="grid gap-1.5 text-sm font-semibold text-slate-700">
            Effective until
            <input #effectiveUntil class="ah-input" type="datetime-local" />
          </label>

          <div class="md:col-span-2 xl:col-span-5">
            <button
              class="rounded-md bg-[var(--ah-color-brand-cobalt)] px-4 py-2 text-sm font-bold text-white disabled:opacity-60"
              type="submit"
              [disabled]="loading()"
            >
              Grant consent
            </button>
          </div>
        </form>
      </section>

      <section class="ah-section">
        <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 class="ah-card-title">Consent history</h2>
            <p class="mt-1 text-xs text-slate-500">Revocation preserves the original grant evidence.</p>
          </div>
          <label class="grid gap-1 text-xs font-semibold text-slate-600 sm:w-80">
            Revocation reason
            <input #revocationReason class="ah-input" placeholder="Patient withdrew this consent" />
          </label>
        </div>

        <ah-table-shell>
          <table class="ah-table">
            <thead>
              <tr>
                <th>Status</th>
                <th>Category</th>
                <th>Purpose</th>
                <th>Recipient</th>
                <th>Scope</th>
                <th>Effective</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @for (consent of consents(); track consent.id) {
                <tr>
                  <td>
                    <ah-badge [tone]="consent.isEffective ? 'success' : consent.status === 'REVOKED' ? 'danger' : 'warning'">
                      {{ consent.status }}
                    </ah-badge>
                  </td>
                  <td>{{ consent.dataCategory }}</td>
                  <td>{{ consent.purpose }}</td>
                  <td>{{ consent.recipientClass }}</td>
                  <td class="font-mono text-xs">{{ consent.facilityId ?? 'ORGANIZATION' }}</td>
                  <td class="text-xs">
                    {{ consent.effectiveFrom }}
                    @if (consent.effectiveUntil) { <div>to {{ consent.effectiveUntil }}</div> }
                    @if (consent.revocationReason) { <div class="mt-1 text-red-700">Revoked: {{ consent.revocationReason }}</div> }
                  </td>
                  <td class="text-right">
                    @if (consent.status === 'ACTIVE') {
                      <button
                        class="ah-link font-semibold"
                        type="button"
                        [disabled]="loading()"
                        (click)="revoke(consent, revocationReason.value)"
                      >
                        Revoke
                      </button>
                    }
                  </td>
                </tr>
              } @empty {
                <tr>
                  <td colspan="7" class="py-6 text-center text-sm text-slate-500">
                    {{ loading() ? 'Loading consent state…' : 'No consent records found.' }}
                  </td>
                </tr>
              }
            </tbody>
          </table>
        </ah-table-shell>
      </section>
    } @else {
      <section class="ah-section rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="font-bold text-slate-900">Select a patient first</h2>
        <p class="mt-1 text-sm text-slate-600">
          Use the patient directory Privacy action so consent decisions always have explicit patient context.
        </p>
        <a class="ah-link mt-3 inline-block font-semibold" routerLink="/patients">Open patient directory</a>
      </section>
    }
  `,
})
export class PrivacyPage implements OnInit {
  protected readonly patientContext = inject(PatientContextService);
  private readonly privacy = inject(PrivacyDirectoryService);

  protected readonly consents = signal<readonly PatientConsent[]>([]);
  protected readonly decision = signal<PrivacyDecision | null>(null);
  protected readonly breakGlass = signal<BreakGlassAccess | null>(null);
  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly message = signal<string | null>(null);

  ngOnInit(): void {
    this.reload();
  }

  protected grant(
    dataCategory: string,
    purpose: string,
    recipientClass: string,
    scope: string,
    effectiveUntil: string,
  ): void {
    const patient = this.patientContext.patient();
    if (!patient) {
      this.error.set('Select a patient before granting consent.');
      return;
    }

    let effectiveUntilIso: string | null = null;
    if (effectiveUntil) {
      const parsed = new Date(effectiveUntil);
      if (Number.isNaN(parsed.valueOf())) {
        this.error.set('Effective-until is invalid.');
        return;
      }
      effectiveUntilIso = parsed.toISOString();
    }

    this.loading.set(true);
    this.error.set(null);
    this.message.set(null);

    this.privacy.grant({
      organizationId: patient.organizationId,
      patientId: patient.id,
      facilityId: scope === 'ORGANIZATION' ? null : patient.registrationFacilityId,
      dataCategory: dataCategory as ConsentDataCategory,
      purpose: purpose as ConsentPurpose,
      recipientClass: recipientClass as ConsentRecipientClass,
      effectiveUntil: effectiveUntilIso,
    }).subscribe({
      next: () => {
        this.message.set('Consent granted and audit evidence recorded.');
        this.reload();
      },
      error: (error: unknown) => this.fail(error, 'Consent could not be granted.'),
    });
  }

  protected revoke(consent: PatientConsent, reason: string): void {
    const patient = this.patientContext.patient();
    if (!patient) {
      this.error.set('Select a patient before revoking consent.');
      return;
    }

    if (reason.trim().length < 3) {
      this.error.set('Enter a revocation reason before revoking consent.');
      return;
    }

    this.loading.set(true);
    this.error.set(null);
    this.message.set(null);

    this.privacy.revoke({
      organizationId: patient.organizationId,
      patientId: patient.id,
      consentId: consent.id,
      reason: reason.trim(),
    }).subscribe({
      next: () => {
        this.message.set('Consent revoked. The original grant evidence remains preserved.');
        this.reload();
      },
      error: (error: unknown) => this.fail(error, 'Consent could not be revoked.'),
    });
  }

  protected activateBreakGlass(reason: string): void {
    const patient = this.patientContext.patient();
    if (!patient) {
      this.error.set('Select a patient before activating break-glass access.');
      return;
    }

    if (reason.trim().length < 12) {
      this.error.set('Break-glass requires a specific reason of at least 12 characters.');
      return;
    }

    this.loading.set(true);
    this.error.set(null);
    this.message.set(null);

    this.privacy.activateBreakGlass({
      organizationId: patient.organizationId,
      patientId: patient.id,
      purpose: 'TREATMENT',
      reason: reason.trim(),
    }).subscribe({
      next: (access) => {
        this.breakGlass.set(access);
        this.message.set('Break-glass access activated and audited.');
        this.reload();
      },
      error: (error: unknown) => this.fail(error, 'Break-glass access could not be activated.'),
    });
  }

  private reload(): void {
    const patient = this.patientContext.patient();
    if (!patient) {
      this.loading.set(false);
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    this.privacy.consents(patient.organizationId, patient.id).subscribe({
      next: (consents) => {
        this.consents.set(consents);
        this.loadDecision(patient.organizationId, patient.id);
      },
      error: (error: unknown) => this.fail(error, 'Consent state could not be loaded.'),
    });
  }

  private loadDecision(organizationId: string, patientId: string): void {
    this.privacy.decision(organizationId, patientId).subscribe({
      next: (decision) => {
        this.decision.set(decision);
        this.loading.set(false);
      },
      error: (error: unknown) => this.fail(error, 'Privacy decision could not be evaluated.'),
    });
  }

  private fail(error: unknown, fallback: string): void {
    this.error.set(error instanceof Error ? error.message : fallback);
    this.loading.set(false);
  }
}
