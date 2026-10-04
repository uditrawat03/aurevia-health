import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AhPageHeaderComponent } from '../../layout';
import { AhBadgeComponent, AhCardComponent } from '../../shared/ui';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, AhPageHeaderComponent, AhBadgeComponent, AhCardComponent],
  selector: 'ah-overview-page',
  template: `
    <ah-page-header
      title="Clinical operations"
      subtitle="Use the live workspace routes to exercise the backend foundations already implemented."
    />

    <section class="ah-section ah-grid ah-grid-3" aria-label="Foundation status">
      <ah-card>
        <div class="ah-stat-label">Identity & authorization</div>
        <div class="mt-2"><ah-badge tone="success">Available</ah-badge></div>
        <p class="mt-2 text-sm text-slate-600">Session auth, organization roles, and facility scope are active.</p>
      </ah-card>

      <ah-card>
        <div class="ah-stat-label">Patient identity / MPI</div>
        <div class="mt-2"><ah-badge tone="success">Available</ah-badge></div>
        <p class="mt-2 text-sm text-slate-600">Search seeded patients, register patients, and inspect patient context.</p>
      </ah-card>

      <ah-card>
        <div class="ah-stat-label">Consent & privacy</div>
        <div class="mt-2"><ah-badge tone="success">Available</ah-badge></div>
        <p class="mt-2 text-sm text-slate-600">Consent, revocation, privacy decisions, and audited break-glass access are wired.</p>
      </ah-card>
    </section>

    <section class="ah-section grid gap-3 lg:grid-cols-3">
      <a class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm hover:border-slate-300" routerLink="/patients">
        <div class="font-bold text-slate-900">Patients</div>
        <div class="mt-1 text-sm text-slate-600">Search DEMO MRNs and open patient details.</div>
      </a>
      <a class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm hover:border-slate-300" routerLink="/patients/new">
        <div class="font-bold text-slate-900">Register patient</div>
        <div class="mt-1 text-sm text-slate-600">Exercise the V1-M4 registration and duplicate-candidate flow.</div>
      </a>
      <a class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm hover:border-slate-300" routerLink="/audit">
        <div class="font-bold text-slate-900">Audit evidence</div>
        <div class="mt-1 text-sm text-slate-600">Inspect organization-scoped authorization and patient events.</div>
      </a>
    </section>
  `,
})
export class OverviewPage {}
