import { ComponentFixture, TestBed } from '@angular/core/testing';
import { PatientContextBannerComponent } from './patient-context-banner';

describe('PatientContextBannerComponent', () => {
  let fixture: ComponentFixture<PatientContextBannerComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PatientContextBannerComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(PatientContextBannerComponent);
  });

  it('renders explicit patient identity when a patient is selected', () => {
    fixture.componentRef.setInput('patient', {
      id: 'patient-1',
      organizationId: 'org-1',
      registrationFacilityId: 'facility-1',
      displayName: 'Asha Mehta',
      dateOfBirth: '1988-04-18',
      sexAtBirth: 'FEMALE',
      mrn: 'DEMO-0001',
    });
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Asha Mehta');
    expect(fixture.nativeElement.textContent).toContain('MRN DEMO-0001');
  });

  it('renders no patient banner without selected context', () => {
    fixture.detectChanges();

    expect(fixture.nativeElement.querySelector('[aria-label="Current patient context"]')).toBeNull();
  });
});
