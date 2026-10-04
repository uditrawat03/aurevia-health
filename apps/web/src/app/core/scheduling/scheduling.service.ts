import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { GraphqlClient } from '../graphql/graphql-client';

export interface SchedulingResource {
  readonly id: string;
  readonly organizationId: string;
  readonly facilityId: string;
  readonly type: 'PROVIDER' | 'ROOM' | 'EQUIPMENT';
  readonly name: string;
  readonly code: string;
  readonly active: boolean;
}

export interface AppointmentType {
  readonly id: string;
  readonly organizationId: string;
  readonly facilityId: string;
  readonly code: string;
  readonly name: string;
  readonly durationMinutes: number;
  readonly active: boolean;
}

export interface Appointment {
  readonly id: string;
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly patientDisplayName: string;
  readonly appointmentType: AppointmentType;
  readonly resources: readonly SchedulingResource[];
  readonly status: 'SCHEDULED' | 'CANCELLED';
  readonly startsAt: string;
  readonly endsAt: string;
  readonly timezone: string;
  readonly reason: string | null;
  readonly cancelledAt: string | null;
  readonly cancellationReason: string | null;
  readonly createdAt: string;
}

export interface WaitlistEntry {
  readonly id: string;
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly patientDisplayName: string;
  readonly appointmentType: AppointmentType;
  readonly status: 'WAITING' | 'OFFERED' | 'BOOKED' | 'CANCELLED';
  readonly preferredFrom: string;
  readonly preferredUntil: string;
  readonly timezone: string;
  readonly reason: string | null;
  readonly cancelledAt: string | null;
  readonly cancellationReason: string | null;
  readonly createdAt: string;
}

export interface BookAppointmentInput {
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly appointmentTypeId: string;
  readonly resourceIds: readonly string[];
  readonly startsAtLocal: string;
  readonly timezone: string;
  readonly reason: string | null;
  readonly idempotencyKey: string;
}

export interface BookAppointmentResult {
  readonly appointment: Appointment;
  readonly replayed: boolean;
}

export interface RescheduleAppointmentInput {
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly appointmentId: string;
  readonly resourceIds: readonly string[];
  readonly startsAtLocal: string;
  readonly timezone: string;
}

export interface CancelAppointmentInput {
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly appointmentId: string;
  readonly reason: string;
}

export interface JoinWaitlistInput {
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly appointmentTypeId: string;
  readonly preferredFromLocal: string;
  readonly preferredUntilLocal: string;
  readonly timezone: string;
  readonly reason: string | null;
  readonly idempotencyKey: string;
}

export interface JoinWaitlistResult {
  readonly entry: WaitlistEntry;
  readonly replayed: boolean;
}

export interface CancelWaitlistInput {
  readonly organizationId: string;
  readonly facilityId: string;
  readonly patientId: string;
  readonly waitlistEntryId: string;
  readonly reason: string;
}

interface ScopeVariables {
  readonly input: {
    readonly organizationId: string;
    readonly facilityId: string | null;
  };
}

interface ResourcesData {
  readonly schedulingResources: readonly SchedulingResource[];
}

interface TypesData {
  readonly appointmentTypes: readonly AppointmentType[];
}

interface AppointmentVariables {
  readonly input: {
    readonly organizationId: string;
    readonly facilityId: string | null;
    readonly from: string;
    readonly to: string;
  };
}

interface AppointmentsData {
  readonly appointments: readonly Appointment[];
}

interface WaitlistData {
  readonly waitlistEntries: readonly WaitlistEntry[];
}

interface BookVariables {
  readonly input: BookAppointmentInput;
}

interface BookData {
  readonly bookAppointment: BookAppointmentResult;
}

interface RescheduleVariables {
  readonly input: RescheduleAppointmentInput;
}

interface RescheduleData {
  readonly rescheduleAppointment: Appointment;
}

interface CancelAppointmentVariables {
  readonly input: CancelAppointmentInput;
}

interface CancelAppointmentData {
  readonly cancelAppointment: Appointment;
}

interface JoinWaitlistVariables {
  readonly input: JoinWaitlistInput;
}

interface JoinWaitlistData {
  readonly joinWaitlist: JoinWaitlistResult;
}

interface CancelWaitlistVariables {
  readonly input: CancelWaitlistInput;
}

interface CancelWaitlistData {
  readonly cancelWaitlist: WaitlistEntry;
}

const RESOURCE_FIELDS = `
  id
  organizationId
  facilityId
  type
  name
  code
  active
`;

const APPOINTMENT_TYPE_FIELDS = `
  id
  organizationId
  facilityId
  code
  name
  durationMinutes
  active
`;

const APPOINTMENT_FIELDS = `
  id
  organizationId
  facilityId
  patientId
  patientDisplayName
  status
  startsAt
  endsAt
  timezone
  reason
  cancelledAt
  cancellationReason
  createdAt
  appointmentType {
    ${APPOINTMENT_TYPE_FIELDS}
  }
  resources {
    ${RESOURCE_FIELDS}
  }
`;

const WAITLIST_FIELDS = `
  id
  organizationId
  facilityId
  patientId
  patientDisplayName
  status
  preferredFrom
  preferredUntil
  timezone
  reason
  cancelledAt
  cancellationReason
  createdAt
  appointmentType {
    ${APPOINTMENT_TYPE_FIELDS}
  }
`;

const RESOURCES_QUERY = `
  query SchedulingResources($input: SchedulingScopeInput!) {
    schedulingResources(input: $input) {
      ${RESOURCE_FIELDS}
    }
  }
`;

const TYPES_QUERY = `
  query AppointmentTypes($input: SchedulingScopeInput!) {
    appointmentTypes(input: $input) {
      ${APPOINTMENT_TYPE_FIELDS}
    }
  }
`;

const APPOINTMENTS_QUERY = `
  query SchedulingAppointments($input: AppointmentWindowInput!) {
    appointments(input: $input) {
      ${APPOINTMENT_FIELDS}
    }
  }
`;

const WAITLIST_QUERY = `
  query SchedulingWaitlist($input: SchedulingScopeInput!) {
    waitlistEntries(input: $input) {
      ${WAITLIST_FIELDS}
    }
  }
`;

const BOOK_APPOINTMENT_MUTATION = `
  mutation BookAppointment($input: BookAppointmentInput!) {
    bookAppointment(input: $input) {
      replayed
      appointment {
        ${APPOINTMENT_FIELDS}
      }
    }
  }
`;

const RESCHEDULE_APPOINTMENT_MUTATION = `
  mutation RescheduleAppointment($input: RescheduleAppointmentInput!) {
    rescheduleAppointment(input: $input) {
      ${APPOINTMENT_FIELDS}
    }
  }
`;

const CANCEL_APPOINTMENT_MUTATION = `
  mutation CancelAppointment($input: CancelAppointmentInput!) {
    cancelAppointment(input: $input) {
      ${APPOINTMENT_FIELDS}
    }
  }
`;

const JOIN_WAITLIST_MUTATION = `
  mutation JoinWaitlist($input: JoinWaitlistInput!) {
    joinWaitlist(input: $input) {
      replayed
      entry {
        ${WAITLIST_FIELDS}
      }
    }
  }
`;

const CANCEL_WAITLIST_MUTATION = `
  mutation CancelWaitlist($input: CancelWaitlistInput!) {
    cancelWaitlist(input: $input) {
      ${WAITLIST_FIELDS}
    }
  }
`;

@Injectable({ providedIn: 'root' })
export class SchedulingService {
  private readonly graphql = inject(GraphqlClient);

  resources(organizationId: string, facilityId: string | null): Observable<readonly SchedulingResource[]> {
    return this.graphql
      .execute<ResourcesData, ScopeVariables>(RESOURCES_QUERY, {
        input: { organizationId, facilityId },
      })
      .pipe(map(({ schedulingResources }) => schedulingResources));
  }

  appointmentTypes(organizationId: string, facilityId: string | null): Observable<readonly AppointmentType[]> {
    return this.graphql
      .execute<TypesData, ScopeVariables>(TYPES_QUERY, {
        input: { organizationId, facilityId },
      })
      .pipe(map(({ appointmentTypes }) => appointmentTypes));
  }

  appointments(
    organizationId: string,
    facilityId: string | null,
    from: string,
    to: string,
  ): Observable<readonly Appointment[]> {
    return this.graphql
      .execute<AppointmentsData, AppointmentVariables>(APPOINTMENTS_QUERY, {
        input: { organizationId, facilityId, from, to },
      })
      .pipe(map(({ appointments }) => appointments));
  }

  waitlist(organizationId: string, facilityId: string | null): Observable<readonly WaitlistEntry[]> {
    return this.graphql
      .execute<WaitlistData, ScopeVariables>(WAITLIST_QUERY, {
        input: { organizationId, facilityId },
      })
      .pipe(map(({ waitlistEntries }) => waitlistEntries));
  }

  book(input: BookAppointmentInput): Observable<BookAppointmentResult> {
    return this.graphql
      .execute<BookData, BookVariables>(BOOK_APPOINTMENT_MUTATION, { input })
      .pipe(map(({ bookAppointment }) => bookAppointment));
  }

  reschedule(input: RescheduleAppointmentInput): Observable<Appointment> {
    return this.graphql
      .execute<RescheduleData, RescheduleVariables>(RESCHEDULE_APPOINTMENT_MUTATION, { input })
      .pipe(map(({ rescheduleAppointment }) => rescheduleAppointment));
  }

  cancelAppointment(input: CancelAppointmentInput): Observable<Appointment> {
    return this.graphql
      .execute<CancelAppointmentData, CancelAppointmentVariables>(CANCEL_APPOINTMENT_MUTATION, { input })
      .pipe(map(({ cancelAppointment }) => cancelAppointment));
  }

  joinWaitlist(input: JoinWaitlistInput): Observable<JoinWaitlistResult> {
    return this.graphql
      .execute<JoinWaitlistData, JoinWaitlistVariables>(JOIN_WAITLIST_MUTATION, { input })
      .pipe(map(({ joinWaitlist }) => joinWaitlist));
  }

  cancelWaitlist(input: CancelWaitlistInput): Observable<WaitlistEntry> {
    return this.graphql
      .execute<CancelWaitlistData, CancelWaitlistVariables>(CANCEL_WAITLIST_MUTATION, { input })
      .pipe(map(({ cancelWaitlist }) => cancelWaitlist));
  }
}
