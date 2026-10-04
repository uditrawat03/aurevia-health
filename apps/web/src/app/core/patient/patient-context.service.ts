import { Injectable, signal } from '@angular/core';

export interface PatientContext {
  readonly id: string;
  readonly organizationId: string;
  readonly registrationFacilityId: string;
  readonly displayName: string;
  readonly dateOfBirth: string;
  readonly sexAtBirth: string;
  readonly mrn: string | null;
}

@Injectable({ providedIn: 'root' })
export class PatientContextService {
  private readonly selectedPatient = signal<PatientContext | null>(null);

  readonly patient = this.selectedPatient.asReadonly();

  select(patient: PatientContext): void {
    this.selectedPatient.set(patient);
  }

  clear(): void {
    this.selectedPatient.set(null);
  }
}
