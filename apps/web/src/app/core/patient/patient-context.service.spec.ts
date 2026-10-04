import { TestBed } from '@angular/core/testing';
import { PatientContextService } from './patient-context.service';

describe('PatientContextService', () => {
  it('selects and clears the current patient context', () => {
    const service = TestBed.inject(PatientContextService);

    service.select({
      id: 'patient-1',
      organizationId: 'org-1',
      registrationFacilityId: 'facility-1',
      displayName: 'Asha Mehta',
      dateOfBirth: '1988-04-18',
      sexAtBirth: 'FEMALE',
      mrn: 'DEMO-0001',
    });

    expect(service.patient()?.displayName).toBe('Asha Mehta');

    service.clear();
    expect(service.patient()).toBeNull();
  });
});
