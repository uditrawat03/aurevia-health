import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { GraphqlClient } from '../graphql/graphql-client';

export interface WorkspaceFacility {
  readonly id: string;
  readonly name: string;
  readonly code: string;
}

export interface WorkspaceOrganization {
  readonly id: string;
  readonly name: string;
  readonly facilities: readonly WorkspaceFacility[];
}

interface OrganizationData {
  readonly organization: WorkspaceOrganization;
}

interface OrganizationVariables {
  readonly id: string;
}

const ORGANIZATION_QUERY = `
  query WorkspaceOrganization($id: ID!) {
    organization(id: $id) {
      id
      name
      facilities {
        id
        name
        code
      }
    }
  }
`;

@Injectable({ providedIn: 'root' })
export class WorkspaceDirectoryService {
  private readonly graphql = inject(GraphqlClient);

  organization(organizationId: string): Observable<WorkspaceOrganization> {
    return this.graphql
      .execute<OrganizationData, OrganizationVariables>(ORGANIZATION_QUERY, {
        id: organizationId,
      })
      .pipe(map(({ organization }) => organization));
  }
}
