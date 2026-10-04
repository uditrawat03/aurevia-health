import { Routes } from '@angular/router';
import { AuditPage } from './features/audit/audit.page';
import { ClinicalRecordPage } from './features/clinical/clinical-record.page';
import { EncountersPage } from './features/encounters/encounters.page';
import { OverviewPage } from './features/overview/overview.page';
import { PatientDetailPage } from './features/patients/patient-detail.page';
import { PatientRegisterPage } from './features/patients/patient-register.page';
import { PatientsPage } from './features/patients/patients.page';
import { PlaceholderPage } from './features/placeholder/placeholder.page';
import { PrivacyPage } from './features/privacy/privacy.page';
import { SchedulingPage } from './features/scheduling/scheduling.page';

export const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: 'overview' },
  { path: 'overview', component: OverviewPage },
  { path: 'patients', component: PatientsPage },
  { path: 'patients/new', component: PatientRegisterPage },
  { path: 'patients/:patientId', component: PatientDetailPage },
  { path: 'patients/:patientId/clinical', component: ClinicalRecordPage },
  { path: 'privacy', component: PrivacyPage },
  { path: 'audit', component: AuditPage },
  { path: 'scheduling', component: SchedulingPage },
  { path: 'encounters', component: EncountersPage },
  {
    path: 'work-queues',
    component: PlaceholderPage,
    data: {
      title: 'Work queues',
      description: 'Operational queues will be attached to domain workflows as those workflows are implemented.',
    },
  },
  {
    path: 'interoperability',
    component: PlaceholderPage,
    data: {
      title: 'Interoperability',
      description: 'Interoperability contracts become functional in V1-M9.',
    },
  },
  {
    path: 'administration',
    component: PlaceholderPage,
    data: {
      title: 'Administration',
      description: 'Administration navigation is reserved for organization, identity, and platform controls.',
    },
  },
  { path: '**', redirectTo: 'overview' },
];
