import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';

interface GraphqlError {
  readonly message: string;
}

interface GraphqlEnvelope<TData> {
  readonly data?: TData;
  readonly errors?: readonly GraphqlError[];
}

@Injectable({ providedIn: 'root' })
export class GraphqlClient {
  private readonly http = inject(HttpClient);

  execute<TData, TVariables extends object>(
    query: string,
    variables: TVariables,
  ): Observable<TData> {
    return this.http
      .post<GraphqlEnvelope<TData>>(
        '/graphql',
        { query, variables },
        { withCredentials: true },
      )
      .pipe(
        map((response) => {
          const error = response.errors?.[0];
          if (error) {
            throw new Error(error.message || 'The GraphQL request failed.');
          }

          if (!response.data) {
            throw new Error('The GraphQL response did not contain data.');
          }

          return response.data;
        }),
      );
  }
}
