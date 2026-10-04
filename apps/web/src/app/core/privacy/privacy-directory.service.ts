import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { GraphqlClient } from '../graphql/graphql-client';

export type ConsentDataCategory = 'DEMOGRAPHICS' | 'CLINICAL' | 'BILLING' | 'RESEARCH';
export type ConsentPurpose = 'TREATMENT' | 'CARE_COORDINATION' | 'OPERATIONS' | 'BILLING' | 'RESEARCH';
export type ConsentRecipientClass = 'CARE_TEAM' | 'ORGANIZATION_STAFF' | 'EXTERNAL_PROVIDER' | 'RESEARCH_TEAM';

export interface PatientConsent {
  readonly id: string;
  readonly organizationId: string;
  readonly patientId: string;
  readonly facilityId: string | null;
  readonly dataCategory: ConsentDataCategory;
  readonly purpose: ConsentPurpose;
  readonly recipientClass: ConsentRecipientClass;
  readonly status: 'ACTIVE' | 'REVOKED';
  readonly grantedByUserId: string;
  readonly effectiveFrom: string;
  readonly effectiveUntil: string | null;
  readonly revokedAt: string | null;
  readonly revokedByUserId: string | null;
  readonly revocationReason: string | null;
  readonly createdAt: string;
  readonly isEffective: boolean;
}

export interface PrivacyDecision {
  readonly allowed: boolean;
  readonly reason: 'ACTIVE_CONSENT' | 'BREAK_GLASS' | 'NO_EFFECTIVE_CONSENT' | 'COUNTRY_POLICY_DENIED';
  readonly breakGlassAccessId: string | null;
}

export interface BreakGlassAccess {
  readonly id: string;
  readonly organizationId: string;
  readonly patientId: string;
  readonly facilityId: string;
  readonly actorUserId: string;
  readonly purpose: ConsentPurpose;
  readonly reason: string;
  readonly activatedAt: string;
  readonly expiresAt: string;
}

interface ConsentsData {
  readonly patientConsents: readonly PatientConsent[];
}

interface DecisionData {
  readonly patientPrivacyDecision: PrivacyDecision;
}

interface GrantConsentData {
  readonly grantPatientConsent: PatientConsent;
}

interface RevokeConsentData {
  readonly revokePatientConsent: PatientConsent;
}

interface BreakGlassData {
  readonly activateBreakGlass: BreakGlassAccess;
}

const CONSENT_FIELDS = `
  id
  organizationId
  patientId
  facilityId
  dataCategory
  purpose
  recipientClass
  status
  grantedByUserId
  effectiveFrom
  effectiveUntil
  revokedAt
  revokedByUserId
  revocationReason
  createdAt
  isEffective
`;

const PATIENT_CONSENTS_QUERY = `
  query PatientConsents($input: PatientConsentsInput!) {
    patientConsents(input: $input) {
      ${CONSENT_FIELDS}
    }
  }
`;

const PRIVACY_DECISION_QUERY = `
  query PatientPrivacyDecision($input: PatientPrivacyDecisionInput!) {
    patientPrivacyDecision(input: $input) {
      allowed
      reason
      breakGlassAccessId
    }
  }
`;

const GRANT_CONSENT_MUTATION = `
  mutation GrantPatientConsent($input: GrantPatientConsentInput!) {
    grantPatientConsent(input: $input) {
      ${CONSENT_FIELDS}
    }
  }
`;

const REVOKE_CONSENT_MUTATION = `
  mutation RevokePatientConsent($input: RevokePatientConsentInput!) {
    revokePatientConsent(input: $input) {
      ${CONSENT_FIELDS}
    }
  }
`;

const BREAK_GLASS_MUTATION = `
  mutation ActivateBreakGlass($input: ActivateBreakGlassInput!) {
    activateBreakGlass(input: $input) {
      id
      organizationId
      patientId
      facilityId
      actorUserId
      purpose
      reason
      activatedAt
      expiresAt
    }
  }
`;

@Injectable({ providedIn: 'root' })
export class PrivacyDirectoryService {
  private readonly graphql = inject(GraphqlClient);

  consents(organizationId: string, patientId: string): Observable<readonly PatientConsent[]> {
    return this.graphql
      .execute<ConsentsData, { input: { organizationId: string; patientId: string } }>(
        PATIENT_CONSENTS_QUERY,
        { input: { organizationId, patientId } },
      )
      .pipe(map(({ patientConsents }) => patientConsents));
  }

  decision(
    organizationId: string,
    patientId: string,
    dataCategory: ConsentDataCategory = 'DEMOGRAPHICS',
    purpose: ConsentPurpose = 'TREATMENT',
    recipientClass: ConsentRecipientClass = 'CARE_TEAM',
  ): Observable<PrivacyDecision> {
    return this.graphql
      .execute<
        DecisionData,
        {
          input: {
            organizationId: string;
            patientId: string;
            dataCategory: ConsentDataCategory;
            purpose: ConsentPurpose;
            recipientClass: ConsentRecipientClass;
          };
        }
      >(PRIVACY_DECISION_QUERY, {
        input: { organizationId, patientId, dataCategory, purpose, recipientClass },
      })
      .pipe(map(({ patientPrivacyDecision }) => patientPrivacyDecision));
  }

  grant(input: {
    organizationId: string;
    patientId: string;
    facilityId: string | null;
    dataCategory: ConsentDataCategory;
    purpose: ConsentPurpose;
    recipientClass: ConsentRecipientClass;
    effectiveUntil: string | null;
  }): Observable<PatientConsent> {
    return this.graphql
      .execute<GrantConsentData, { input: typeof input }>(GRANT_CONSENT_MUTATION, { input })
      .pipe(map(({ grantPatientConsent }) => grantPatientConsent));
  }

  revoke(input: {
    organizationId: string;
    patientId: string;
    consentId: string;
    reason: string;
  }): Observable<PatientConsent> {
    return this.graphql
      .execute<RevokeConsentData, { input: typeof input }>(REVOKE_CONSENT_MUTATION, { input })
      .pipe(map(({ revokePatientConsent }) => revokePatientConsent));
  }

  activateBreakGlass(input: {
    organizationId: string;
    patientId: string;
    purpose: ConsentPurpose;
    reason: string;
  }): Observable<BreakGlassAccess> {
    return this.graphql
      .execute<BreakGlassData, { input: typeof input }>(BREAK_GLASS_MUTATION, { input })
      .pipe(map(({ activateBreakGlass }) => activateBreakGlass));
  }
}
