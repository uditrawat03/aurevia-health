import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { GraphqlClient } from '../graphql/graphql-client';

export interface PatientIdentifier {
  readonly type: string;
  readonly system: string;
  readonly value: string;
}

export interface PatientContact {
  readonly type: string;
  readonly value: string;
  readonly preferred: boolean;
}

export interface PatientAddress {
  readonly use: string;
  readonly line1: string;
  readonly line2: string | null;
  readonly city: string;
  readonly region: string | null;
  readonly postalCode: string | null;
  readonly countryCode: string;
  readonly preferred: boolean;
}

export interface PatientRelationship {
  readonly type: string;
  readonly name: string;
  readonly phone: string | null;
  readonly email: string | null;
  readonly legalGuardian: boolean;
  readonly emergencyContact: boolean;
}

export interface PatientSummary {
  readonly id: string;
  readonly organizationId: string;
  readonly registrationFacilityId: string;
  readonly givenName: string;
  readonly middleName: string | null;
  readonly familyName: string;
  readonly preferredName: string | null;
  readonly dateOfBirth: string;
  readonly sexAtBirth: string;
  readonly identifiers: readonly PatientIdentifier[];
}

export interface PatientRecord extends PatientSummary {
  readonly contacts: readonly PatientContact[];
  readonly addresses: readonly PatientAddress[];
  readonly relationships: readonly PatientRelationship[];
}

export interface PatientSearchResult {
  readonly items: readonly PatientSummary[];
  readonly total: number;
}

export interface PatientDuplicateCandidate {
  readonly patientId: string;
  readonly displayName: string;
  readonly dateOfBirth: string;
  readonly confidence: number;
  readonly reason: string;
}

export interface RegisterPatientInput {
  readonly organizationId: string;
  readonly registrationFacilityId: string;
  readonly givenName: string;
  readonly familyName: string;
  readonly dateOfBirth: string;
  readonly sexAtBirth: 'FEMALE' | 'MALE' | 'INTERSEX' | 'UNKNOWN';
  readonly mrn: string;
}

export interface RegisterPatientResult {
  readonly patient: PatientRecord;
  readonly duplicateCandidates: readonly PatientDuplicateCandidate[];
}

interface PatientSearchData {
  readonly patients: PatientSearchResult;
}

interface PatientSearchVariables {
  readonly input: {
    readonly organizationId: string;
    readonly facilityId: string | null;
    readonly query: string;
    readonly limit: number;
  };
}

interface PatientData {
  readonly patient: PatientRecord;
}

interface PatientVariables {
  readonly organizationId: string;
  readonly id: string;
}

interface RegisterPatientData {
  readonly registerPatient: RegisterPatientResult;
}

interface RegisterPatientVariables {
  readonly input: {
    readonly organizationId: string;
    readonly registrationFacilityId: string;
    readonly givenName: string;
    readonly familyName: string;
    readonly dateOfBirth: string;
    readonly sexAtBirth: string;
    readonly identifiers: readonly {
      readonly type: string;
      readonly system: string;
      readonly value: string;
    }[];
    readonly contacts: readonly never[];
    readonly addresses: readonly never[];
    readonly relationships: readonly never[];
  };
}

const PATIENT_SEARCH_FIELDS = `
  id
  organizationId
  registrationFacilityId
  givenName
  middleName
  familyName
  preferredName
  dateOfBirth
  sexAtBirth
  identifiers {
    type
    system
    value
  }
`;

const PATIENT_FIELDS = `
  id
  organizationId
  registrationFacilityId
  givenName
  middleName
  familyName
  preferredName
  dateOfBirth
  sexAtBirth
  identifiers {
    type
    system
    value
  }
  contacts {
    type
    value
    preferred
  }
  addresses {
    use
    line1
    line2
    city
    region
    postalCode
    countryCode
    preferred
  }
  relationships {
    type
    name
    phone
    email
    legalGuardian
    emergencyContact
  }
`;

const PATIENT_SEARCH_QUERY = `
  query PatientDirectory($input: PatientSearchInput!) {
    patients(input: $input) {
      total
      items {
        ${PATIENT_SEARCH_FIELDS}
      }
    }
  }
`;

const PATIENT_QUERY = `
  query PatientRecord($organizationId: ID!, $id: ID!) {
    patient(organizationId: $organizationId, id: $id) {
      ${PATIENT_FIELDS}
    }
  }
`;

const REGISTER_PATIENT_MUTATION = `
  mutation RegisterPatientFromWeb($input: RegisterPatientInput!) {
    registerPatient(input: $input) {
      patient {
        ${PATIENT_FIELDS}
      }
      duplicateCandidates {
        patientId
        displayName
        dateOfBirth
        confidence
        reason
      }
    }
  }
`;

@Injectable({ providedIn: 'root' })
export class PatientDirectoryService {
  private readonly graphql = inject(GraphqlClient);

  search(
    organizationId: string,
    facilityId: string | null,
    query: string,
  ): Observable<PatientSearchResult> {
    return this.graphql
      .execute<PatientSearchData, PatientSearchVariables>(PATIENT_SEARCH_QUERY, {
        input: {
          organizationId,
          facilityId,
          query,
          limit: 20,
        },
      })
      .pipe(map(({ patients }) => patients));
  }

  patient(organizationId: string, patientId: string): Observable<PatientRecord> {
    return this.graphql
      .execute<PatientData, PatientVariables>(PATIENT_QUERY, {
        organizationId,
        id: patientId,
      })
      .pipe(map(({ patient }) => patient));
  }

  register(input: RegisterPatientInput): Observable<RegisterPatientResult> {
    return this.graphql
      .execute<RegisterPatientData, RegisterPatientVariables>(REGISTER_PATIENT_MUTATION, {
        input: {
          organizationId: input.organizationId,
          registrationFacilityId: input.registrationFacilityId,
          givenName: input.givenName,
          familyName: input.familyName,
          dateOfBirth: input.dateOfBirth,
          sexAtBirth: input.sexAtBirth,
          identifiers: [
            {
              type: 'MRN',
              system: 'aurevia-demo-mrn',
              value: input.mrn,
            },
          ],
          contacts: [],
          addresses: [],
          relationships: [],
        },
      })
      .pipe(map(({ registerPatient }) => registerPatient));
  }
}
