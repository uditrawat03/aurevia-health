import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { GraphqlClient } from '../graphql/graphql-client';

export interface AuditEvent {
  readonly id: string;
  readonly actorUserId: string | null;
  readonly facilityId: string | null;
  readonly patientId: string | null;
  readonly resourceType: string;
  readonly resourceId: string | null;
  readonly action: string;
  readonly outcome: 'ALLOWED' | 'DENIED';
  readonly correlationId: string;
  readonly occurredAt: string;
}

interface AuditEventsData {
  readonly auditEvents: readonly AuditEvent[];
}

interface AuditEventsVariables {
  readonly input: {
    readonly organizationId: string;
    readonly limit: number;
  };
}

const AUDIT_EVENTS_QUERY = `
  query WorkspaceAudit($input: AuditEventsInput!) {
    auditEvents(input: $input) {
      id
      actorUserId
      facilityId
      patientId
      resourceType
      resourceId
      action
      outcome
      correlationId
      occurredAt
    }
  }
`;

@Injectable({ providedIn: 'root' })
export class AuditDirectoryService {
  private readonly graphql = inject(GraphqlClient);

  latest(organizationId: string): Observable<readonly AuditEvent[]> {
    return this.graphql
      .execute<AuditEventsData, AuditEventsVariables>(AUDIT_EVENTS_QUERY, {
        input: {
          organizationId,
          limit: 50,
        },
      })
      .pipe(map(({ auditEvents }) => auditEvents));
  }
}
