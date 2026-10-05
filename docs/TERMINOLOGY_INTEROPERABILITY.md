# Terminology and Interoperability Contracts

## Purpose

V1-M9 establishes the Aurevia Health terminology and interoperability boundary without turning FHIR or any country-specific terminology into the internal persistence model.

The milestone is intentionally a contract foundation. It does not create a production FHIR server, connect to an external terminology service, or persist partner exchange payloads.

## Design Rules

1. Clinical concepts are represented independently from any one terminology.
2. A concept may carry multiple codings.
3. Every coding crossing the M9 interoperability contract includes an explicit code-system version.
4. Code systems are identified by absolute URI or URN values rather than local enum names.
5. Known systems are discoverable through a registry, but the registry is not a closed whitelist.
6. Mappings are explicit extension points; Aurevia never guesses a missing translation.
7. FHIR remains an exchange representation behind an anti-corruption mapper.
8. Interoperability envelopes carry the request correlation identifier.

## Terminology Concept

The core terminology abstraction is:

```text
TerminologyConcept
├── text
└── codings[]
    ├── system
    ├── version
    ├── code
    ├── display
    └── userSelected
```

This supports one concept being represented in more than one code system without choosing one system as the platform's universal internal vocabulary.

The initial registry advertises:

| System URI | Name | Initial use |
|---|---|---|
| `http://snomed.info/sct` | SNOMED CT | clinical concepts |
| `http://loinc.org` | LOINC | observations and measurements |
| `http://hl7.org/fhir/sid/icd-10` | ICD-10 International | classification/reporting |
| `urn:aurevia:local` | Aurevia/local organization coding | organization-defined concepts |

These entries are recommendations and metadata, not a closed allow-list. A country profile, organization, or partner adapter may introduce another absolute terminology URI.

## Versioning

The M9 contract requires a non-empty `version` for every coding. This is stricter than the minimum FHIR `Coding` cardinality because Aurevia needs deterministic mapping and audit/reconciliation behavior across country and partner profiles.

The platform must not silently replace a supplied version with a current/latest terminology version.

## Mapping Contract

`TerminologyMappingContract` separates concept representation from translation.

The default implementation only selects a target-system coding that is already present on the concept. If the requested target coding is absent, the operation is rejected instead of inventing or guessing a clinical mapping.

Future mapping implementations may use governed mapping catalogs, country profiles, partner profiles, or external terminology services. Those implementations must preserve mapping version/effective-date provenance before production use.

## FHIR Boundary

`InteroperabilityMapper` is the anti-corruption contract between Aurevia domain concepts and exchange formats.

V1-M9 provides `FhirR4InteroperabilityMapper`, pinned to FHIR R4 `4.0.1`. It maps a terminology concept to the `code` `CodeableConcept` shape of these initial clinical resource skeletons:

- `Condition`
- `AllergyIntolerance`
- `Observation`

The mapper produces an exchange preview only. No Eloquent model or database table stores FHIR resources.

```text
Aurevia terminology concept
        ↓
InteroperabilityMapper
        ↓
FHIR R4 exchange representation
```

## Correlation

The existing `X-Correlation-ID` request value is reused as the interoperability correlation identifier. The GraphQL preview returns this value beside the exchange payload so future outbound/inbound adapters can propagate the same correlation model across queues and external systems.

## GraphQL Contract Preview

M9 adds three non-patient contract queries:

```graphql
query {
  terminologySystems {
    system
    name
    versionRequired
    scope
  }

  interoperabilityProfiles {
    code
    standard
    version
    mediaType
  }
}
```

A concept can be previewed at the FHIR boundary without persistence or external transmission:

```graphql
query Preview($input: InteroperabilityConceptPreviewInput!) {
  interoperabilityConceptPreview(input: $input) {
    correlationId
    standard
    standardVersion
    resourceType
    concept {
      text
      codings { system version code display userSelected }
    }
    payloadJson
  }
}
```

These queries contain no patient identifiers or clinical-record retrieval. A future patient export/import workflow must separately enforce authentication, authorization, privacy, audit, delivery state, retries, and reconciliation.

## Validation

The contract rejects:

- blank coding systems;
- coding systems that are not absolute URI/URN identifiers;
- blank terminology versions;
- blank codes/displays;
- duplicate `(system, version, code)` codings;
- more than one `userSelected` coding;
- excessive coding counts;
- requested target-system mappings that are not explicitly present/configured.

Expected contract rejections use the application's non-reportable business-rule exception so negative-path tests do not pollute application error logs.

## Verification

```powershell
docker exec aurevia-health-api php artisan lighthouse:validate-schema
docker exec aurevia-health-api php artisan test
docker exec aurevia-health-api composer audit

docker exec aurevia-health-web npm test -- --watch=false
docker exec aurevia-health-web npm run build

git diff --check
```

## Known Limitations / Deferred Work

V1-M9 deliberately does not include:

- a FHIR REST server;
- SMART on FHIR;
- FHIR persistence;
- HL7 v2 message transport;
- DICOM exchange;
- production terminology-server network calls;
- automatic SNOMED/LOINC/ICD translation;
- ConceptMap persistence;
- mapping effective-period storage;
- patient-level import/export delivery workflows;
- bulk export;
- partner credentials or message queues.

Those capabilities extend the contracts established here rather than changing the internal clinical aggregate into an exchange-standard schema.
