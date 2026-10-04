import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { PatientContext } from '../../core/patient/patient-context.service';

@Component({
  selector: 'ah-patient-context-banner',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    @if (patient(); as currentPatient) {
      <section
        class="flex flex-wrap items-center gap-x-4 gap-y-1 border-y border-slate-200 bg-white px-[var(--ah-page-gutter)] py-2 text-sm"
        aria-label="Current patient context"
      >
        <strong class="text-slate-900">{{ currentPatient.displayName }}</strong>
        <span class="text-slate-600">DOB {{ currentPatient.dateOfBirth }}</span>
        <span class="text-slate-600">{{ currentPatient.sexAtBirth }}</span>
        @if (currentPatient.mrn) {
          <span class="font-mono text-xs font-semibold text-slate-700">MRN {{ currentPatient.mrn }}</span>
        }
      </section>
    }
  `,
})
export class PatientContextBannerComponent {
  readonly patient = input<PatientContext | null>(null);
}
