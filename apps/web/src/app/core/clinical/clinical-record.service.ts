import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { GraphqlClient } from '../graphql/graphql-client';

export type ProblemStatus = 'ACTIVE' | 'INACTIVE' | 'RESOLVED';
export type AllergySeverity = 'UNKNOWN' | 'MILD' | 'MODERATE' | 'SEVERE';
export type AllergyStatus = 'ACTIVE' | 'INACTIVE' | 'RESOLVED';
export type AllergyVerificationStatus = 'UNVERIFIED' | 'CONFIRMED' | 'REFUTED' | 'ENTERED_IN_ERROR';
export type ClinicalNoteStatus = 'DRAFT' | 'SIGNED';
export type ClinicalNoteAmendmentType = 'ADDENDUM' | 'CORRECTION';

export interface ClinicalProblem {
  readonly id: string;
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly encounterId: string;
  readonly codeSystem: string | null;
  readonly code: string | null;
  readonly display: string;
  readonly status: ProblemStatus;
  readonly onsetDate: string | null;
  readonly resolvedAt: string | null;
  readonly recordedByUserId: string;
  readonly recordedAt: string;
}

export interface PatientAllergy {
  readonly id: string;
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly encounterId: string;
  readonly codeSystem: string | null;
  readonly code: string | null;
  readonly substance: string;
  readonly reaction: string | null;
  readonly severity: AllergySeverity;
  readonly status: AllergyStatus;
  readonly verificationStatus: AllergyVerificationStatus;
  readonly recordedByUserId: string;
  readonly recordedAt: string;
}

export interface ClinicalObservation {
  readonly id: string;
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly encounterId: string;
  readonly codeSystem: string | null;
  readonly code: string;
  readonly display: string;
  readonly valueNumeric: number | null;
  readonly valueText: string | null;
  readonly unit: string | null;
  readonly effectiveAt: string;
  readonly recordedByUserId: string;
  readonly recordedAt: string;
}

export interface ClinicalNoteAmendment {
  readonly id: string;
  readonly type: ClinicalNoteAmendmentType;
  readonly body: string;
  readonly reason: string | null;
  readonly authorUserId: string;
  readonly createdAt: string;
}

export interface ClinicalNote {
  readonly id: string;
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly encounterId: string;
  readonly noteType: string;
  readonly title: string | null;
  readonly body: string;
  readonly status: ClinicalNoteStatus;
  readonly authorUserId: string;
  readonly signedByUserId: string | null;
  readonly signedAt: string | null;
  readonly createdAt: string;
  readonly updatedAt: string;
  readonly amendments: readonly ClinicalNoteAmendment[];
}

export interface PatientTimelineEvent {
  readonly id: string;
  readonly type: string;
  readonly label: string;
  readonly resourceType: string;
  readonly resourceId: string;
  readonly encounterId: string | null;
  readonly actorUserId: string;
  readonly occurredAt: string;
}

export interface ClinicalRecord {
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly problems: readonly ClinicalProblem[];
  readonly allergies: readonly PatientAllergy[];
  readonly observations: readonly ClinicalObservation[];
  readonly notes: readonly ClinicalNote[];
  readonly timeline: readonly PatientTimelineEvent[];
}

interface ClinicalContextInput {
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
}

interface EncounterClinicalContextInput extends ClinicalContextInput {
  readonly encounterId: string;
}

export interface RecordProblemInput extends EncounterClinicalContextInput {
  readonly codeSystem?: string | null;
  readonly code?: string | null;
  readonly display: string;
  readonly status?: ProblemStatus;
  readonly onsetDate?: string | null;
}

export interface RecordAllergyInput extends EncounterClinicalContextInput {
  readonly codeSystem?: string | null;
  readonly code?: string | null;
  readonly substance: string;
  readonly reaction?: string | null;
  readonly severity?: AllergySeverity;
  readonly status?: AllergyStatus;
  readonly verificationStatus?: AllergyVerificationStatus;
}

export interface RecordObservationInput extends EncounterClinicalContextInput {
  readonly codeSystem?: string | null;
  readonly code: string;
  readonly display: string;
  readonly valueNumeric?: number | null;
  readonly valueText?: string | null;
  readonly unit?: string | null;
  readonly effectiveAt?: string | null;
}

export interface CreateClinicalNoteInput extends EncounterClinicalContextInput {
  readonly noteType: string;
  readonly title?: string | null;
  readonly body: string;
}

interface ClinicalRecordQueryData {
  readonly clinicalRecord: ClinicalRecord;
}

interface ProblemMutationData {
  readonly recordProblem: ClinicalProblem;
}

interface AllergyMutationData {
  readonly recordAllergy: PatientAllergy;
}

interface ObservationMutationData {
  readonly recordObservation: ClinicalObservation;
}

interface CreateNoteMutationData {
  readonly createClinicalNote: ClinicalNote;
}

interface UpdateNoteMutationData {
  readonly updateClinicalNoteDraft: ClinicalNote;
}

interface SignNoteMutationData {
  readonly signClinicalNote: ClinicalNote;
}

interface AmendNoteMutationData {
  readonly addClinicalNoteAmendment: ClinicalNote;
}

const PROBLEM_FIELDS = `
  id organizationId facilityId patientId encounterId codeSystem code display status onsetDate resolvedAt
  recordedByUserId recordedAt
`;

const ALLERGY_FIELDS = `
  id organizationId facilityId patientId encounterId codeSystem code substance reaction severity status
  verificationStatus recordedByUserId recordedAt
`;

const OBSERVATION_FIELDS = `
  id organizationId facilityId patientId encounterId codeSystem code display valueNumeric valueText unit
  effectiveAt recordedByUserId recordedAt
`;

const NOTE_FIELDS = `
  id organizationId facilityId patientId encounterId noteType title body status authorUserId signedByUserId
  signedAt createdAt updatedAt
  amendments { id type body reason authorUserId createdAt }
`;

const CLINICAL_RECORD_QUERY = `
  query ClinicalRecord($input: PatientClinicalRecordInput!) {
    clinicalRecord(input: $input) {
      organizationId facilityId patientId
      problems { ${PROBLEM_FIELDS} }
      allergies { ${ALLERGY_FIELDS} }
      observations { ${OBSERVATION_FIELDS} }
      notes { ${NOTE_FIELDS} }
      timeline { id type label resourceType resourceId encounterId actorUserId occurredAt }
    }
  }
`;

const RECORD_PROBLEM_MUTATION = `
  mutation RecordProblem($input: RecordProblemInput!) {
    recordProblem(input: $input) { ${PROBLEM_FIELDS} }
  }
`;

const RECORD_ALLERGY_MUTATION = `
  mutation RecordAllergy($input: RecordAllergyInput!) {
    recordAllergy(input: $input) { ${ALLERGY_FIELDS} }
  }
`;

const RECORD_OBSERVATION_MUTATION = `
  mutation RecordObservation($input: RecordObservationInput!) {
    recordObservation(input: $input) { ${OBSERVATION_FIELDS} }
  }
`;

const CREATE_NOTE_MUTATION = `
  mutation CreateClinicalNote($input: CreateClinicalNoteInput!) {
    createClinicalNote(input: $input) { ${NOTE_FIELDS} }
  }
`;

const UPDATE_NOTE_MUTATION = `
  mutation UpdateClinicalNoteDraft($input: UpdateClinicalNoteDraftInput!) {
    updateClinicalNoteDraft(input: $input) { ${NOTE_FIELDS} }
  }
`;

const SIGN_NOTE_MUTATION = `
  mutation SignClinicalNote($input: SignClinicalNoteInput!) {
    signClinicalNote(input: $input) { ${NOTE_FIELDS} }
  }
`;

const AMEND_NOTE_MUTATION = `
  mutation AddClinicalNoteAmendment($input: AddClinicalNoteAmendmentInput!) {
    addClinicalNoteAmendment(input: $input) { ${NOTE_FIELDS} }
  }
`;

@Injectable({ providedIn: 'root' })
export class ClinicalRecordService {
  private readonly graphql = inject(GraphqlClient);

  record(organizationId: string, facilityId: string, patientId: string): Observable<ClinicalRecord> {
    return this.graphql
      .execute<ClinicalRecordQueryData, { input: ClinicalContextInput }>(CLINICAL_RECORD_QUERY, {
        input: { organizationId, facilityId, patientId },
      })
      .pipe(map(({ clinicalRecord }) => clinicalRecord));
  }

  recordProblem(input: RecordProblemInput): Observable<ClinicalProblem> {
    return this.graphql
      .execute<ProblemMutationData, { input: RecordProblemInput }>(RECORD_PROBLEM_MUTATION, { input })
      .pipe(map(({ recordProblem }) => recordProblem));
  }

  recordAllergy(input: RecordAllergyInput): Observable<PatientAllergy> {
    return this.graphql
      .execute<AllergyMutationData, { input: RecordAllergyInput }>(RECORD_ALLERGY_MUTATION, { input })
      .pipe(map(({ recordAllergy }) => recordAllergy));
  }

  recordObservation(input: RecordObservationInput): Observable<ClinicalObservation> {
    return this.graphql
      .execute<ObservationMutationData, { input: RecordObservationInput }>(RECORD_OBSERVATION_MUTATION, { input })
      .pipe(map(({ recordObservation }) => recordObservation));
  }

  createNote(input: CreateClinicalNoteInput): Observable<ClinicalNote> {
    return this.graphql
      .execute<CreateNoteMutationData, { input: CreateClinicalNoteInput }>(CREATE_NOTE_MUTATION, { input })
      .pipe(map(({ createClinicalNote }) => createClinicalNote));
  }

  updateDraft(
    input: EncounterClinicalContextInput & { readonly noteId: string; readonly title?: string | null; readonly body: string },
  ): Observable<ClinicalNote> {
    return this.graphql
      .execute<UpdateNoteMutationData, { input: typeof input }>(UPDATE_NOTE_MUTATION, { input })
      .pipe(map(({ updateClinicalNoteDraft }) => updateClinicalNoteDraft));
  }

  sign(input: EncounterClinicalContextInput & { readonly noteId: string }): Observable<ClinicalNote> {
    return this.graphql
      .execute<SignNoteMutationData, { input: typeof input }>(SIGN_NOTE_MUTATION, { input })
      .pipe(map(({ signClinicalNote }) => signClinicalNote));
  }

  amend(
    input: EncounterClinicalContextInput & {
      readonly noteId: string;
      readonly type: ClinicalNoteAmendmentType;
      readonly body: string;
      readonly reason?: string | null;
    },
  ): Observable<ClinicalNote> {
    return this.graphql
      .execute<AmendNoteMutationData, { input: typeof input }>(AMEND_NOTE_MUTATION, { input })
      .pipe(map(({ addClinicalNoteAmendment }) => addClinicalNoteAmendment));
  }
}
