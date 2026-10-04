import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { GraphqlClient } from '../graphql/graphql-client';

export type EncounterType = 'OUTPATIENT' | 'EMERGENCY' | 'INPATIENT' | 'VIRTUAL' | 'OTHER';
export type EncounterStatus = 'PLANNED' | 'ARRIVED' | 'IN_PROGRESS' | 'COMPLETED' | 'CANCELLED';

export interface EncounterEvent {
  readonly id: string;
  readonly type: 'CREATED' | 'ARRIVED' | 'STARTED' | 'COMPLETED' | 'CANCELLED';
  readonly fromStatus: EncounterStatus | null;
  readonly toStatus: EncounterStatus;
  readonly reason: string | null;
  readonly actorUserId: string;
  readonly occurredAt: string;
}

export interface Encounter {
  readonly id: string;
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly patientDisplayName: string;
  readonly departmentId: string | null;
  readonly appointmentId: string | null;
  readonly type: EncounterType;
  readonly status: EncounterStatus;
  readonly arrivedAt: string | null;
  readonly startedAt: string | null;
  readonly endedAt: string | null;
  readonly cancelledAt: string | null;
  readonly cancellationReason: string | null;
  readonly createdByUserId: string;
  readonly createdAt: string;
  readonly events: readonly EncounterEvent[];
}

export interface CreateEncounterInput {
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly departmentId: string | null;
  readonly appointmentId: string | null;
  readonly type: EncounterType;
}

export interface TransitionEncounterInput {
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly encounterId: string;
  readonly toStatus: EncounterStatus;
  readonly reason: string | null;
}

interface EncounterListData {
  readonly encounters: readonly Encounter[];
}

interface EncounterListVariables {
  readonly input: {
    readonly organizationId: string;
    readonly facilityId: string;
    readonly patientId: string;
  };
}

interface CreateEncounterData {
  readonly createEncounter: Encounter;
}

interface CreateEncounterVariables {
  readonly input: CreateEncounterInput;
}

interface TransitionEncounterData {
  readonly transitionEncounter: Encounter;
}

interface TransitionEncounterVariables {
  readonly input: TransitionEncounterInput;
}

const ENCOUNTER_FIELDS = `
  id
  organizationId
  facilityId
  patientId
  patientDisplayName
  departmentId
  appointmentId
  type
  status
  arrivedAt
  startedAt
  endedAt
  cancelledAt
  cancellationReason
  createdByUserId
  createdAt
  events {
    id
    type
    fromStatus
    toStatus
    reason
    actorUserId
    occurredAt
  }
`;

const ENCOUNTERS_QUERY = `
  query PatientEncounters($input: PatientEncountersInput!) {
    encounters(input: $input) {
      ${ENCOUNTER_FIELDS}
    }
  }
`;

const CREATE_ENCOUNTER_MUTATION = `
  mutation CreateEncounter($input: CreateEncounterInput!) {
    createEncounter(input: $input) {
      ${ENCOUNTER_FIELDS}
    }
  }
`;

const TRANSITION_ENCOUNTER_MUTATION = `
  mutation TransitionEncounter($input: TransitionEncounterInput!) {
    transitionEncounter(input: $input) {
      ${ENCOUNTER_FIELDS}
    }
  }
`;

@Injectable({ providedIn: 'root' })
export class EncounterService {
  private readonly graphql = inject(GraphqlClient);

  encounters(
    organizationId: string,
    facilityId: string,
    patientId: string,
  ): Observable<readonly Encounter[]> {
    return this.graphql
      .execute<EncounterListData, EncounterListVariables>(ENCOUNTERS_QUERY, {
        input: { organizationId, facilityId, patientId },
      })
      .pipe(map(({ encounters }) => encounters));
  }

  create(input: CreateEncounterInput): Observable<Encounter> {
    return this.graphql
      .execute<CreateEncounterData, CreateEncounterVariables>(CREATE_ENCOUNTER_MUTATION, { input })
      .pipe(map(({ createEncounter }) => createEncounter));
  }

  transition(input: TransitionEncounterInput): Observable<Encounter> {
    return this.graphql
      .execute<TransitionEncounterData, TransitionEncounterVariables>(TRANSITION_ENCOUNTER_MUTATION, { input })
      .pipe(map(({ transitionEncounter }) => transitionEncounter));
  }
}
