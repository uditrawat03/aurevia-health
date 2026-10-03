# Coding Guidelines

This checklist wins when nearby code disagrees with it. Matching an existing violation is not a reason to repeat it.

## General
* [ ] No commented-out code
* [ ] No duplicated/copied code
* [ ] Checklist followed even when a nearby class does not follow it

**Notes:**

* If a second caller needs logic that is private on another class, move it to the owning model or service and call it. Do not copy it.

## Strictness & Type Safety
* [ ] Strict type mode declared on new classes
* [ ] Property types declared for all class properties
* [ ] Typehints declared for all method/function parameters
* [ ] Return types declared for all methods/functions
* [ ] No associative arrays as parameters or return types for public methods/functions

**Notes:**

* How a value is stored does not matter. JSON columns, metadata blobs, and tool payloads are converted to objects at the boundary. A public method takes and returns that object.
* An associative array is allowed only when an object is not justifiable. That exception must be stated, and the array shape (keys and value types) must be documented in PHPDoc. A PHPDoc comment is not a substitute for an object when an object is reasonable.

## Class Construction
* [ ] Implicit constructor parameter promotion used
## Dependency Inversion
* [ ] No `new` for Services or Repositories (`new MyService(...)`)
* [ ] No new static methods for behavior that has dependencies or that tests need to mock
* [ ] Dependencies are provided in a constructor, retrieved via a Facade, or resolved with `App::make()`
* [ ] Interface/abstraction referenced instead of concretion (where applicable)

**Notes:**

* Do not construct a Service or Repository with `new`. Use constructor injection or `App::make()`. A new dependency should be added in one place, not at every call site.
* Do not create static methods for application behavior. Static methods are hard to mock. A static method is acceptable only when it has no dependencies and tests can call it directly. Enum `tryFrom` / `tryFromString`, DTO `fromArray` / `create`, factories, and guards are in that category.
* Calling a pre-existing static is fine. Do not refactor one only to avoid the call.

## Single Responsibility/Separation of Concerns
* [ ] No business logic in controllers
* [ ] No data logic in Services
* [ ] No data/service logic in Resolvers
* [ ] No functions in POPOs
* [ ] Database and Eloquent queries exist only as instance methods on Models or Repositories

**Notes:**

* Do not chain Eloquent outside a model or repository. Filtering, sorting, or constraining a relation in a service is a query, even when the relation is already loaded.

  ```php
  // BAD — chained query in a service
  $project->scenes->reject(...)->sortBy(...);

  // GOOD — the chain lives on the model
  $project->orderedNarrativeScenes();
  ```

## GraphQL Application Boundary
* [ ] GraphQL is the default application API boundary for first-party product data and workflows
* [ ] Angular does not call domain REST endpoints directly
* [ ] Reads use GraphQL queries; state changes use GraphQL mutations
* [ ] Subscriptions are introduced only for workflows that require server-pushed updates
* [ ] GraphQL inputs and payloads are explicitly typed; generic JSON blobs require a documented exception
* [ ] Resolvers are thin application-boundary adapters and contain no business logic or Eloquent/query logic
* [ ] Authorization is enforced server-side for every protected query, mutation, object, and sensitive field
* [ ] Resolver/data-loading code prevents N+1 query behavior
* [ ] GraphQL errors are structured, do not leak sensitive internals, and preserve correlation IDs
* [ ] Schema changes are additive by default; breaking fields are deprecated before removal
* [ ] Retried mutations with external side effects are designed for idempotency
* [ ] Frontend GraphQL operations are centralized and typed; feature components do not construct ad hoc HTTP calls

**Notes:**

* The canonical first-party application endpoint is `/graphql`. Do not add `/api/v1/...` REST endpoints for ordinary Aurevia Health domain reads or writes.
* A resolver may validate/marshal GraphQL input, authorize access, call the appropriate application service/query object, and map the result to the GraphQL contract. It must not contain business rules, Eloquent chains, persistence queries, or copied service logic.
* The GraphQL schema is an application contract, not an Eloquent serialization layer. Do not expose database models automatically just because fields exist on a table.
* Use explicit GraphQL input objects, enums, object types, interfaces/unions where justified, and stable payload types. Avoid untyped `JSON` fields unless the domain value is genuinely schemaless and the exception is documented.
* Prevent N+1 access with repository/model query methods, eager-loading plans, batching/DataLoader patterns, or equivalent server-side techniques. Never fix N+1 by moving Eloquent chains into resolvers.
* Authentication may still use Laravel Sanctum's stateful cookie/session model. GraphQL does not replace CSRF/session bootstrap.
* Approved non-GraphQL HTTP boundaries are limited to protocol or infrastructure concerns such as:
  * health/readiness endpoints (for example `/up`);
  * Sanctum CSRF/session bootstrap where required;
  * OAuth/OIDC/SAML callbacks;
  * inbound webhooks;
  * signed object-storage upload/download handoffs;
  * FHIR, HL7, DICOM, EDI, partner REST, and other interoperability adapters;
  * operational endpoints such as metrics when explicitly required.
* An approved HTTP exception must be documented at the boundary and must not become a backdoor for ordinary application CRUD.
* The Angular application may use `HttpClient` inside the GraphQL transport/client layer or for an approved exception. Feature code must not call Laravel REST routes directly for domain data.
* Prefer generated or otherwise statically typed GraphQL operation types on the frontend so schema drift is caught during development/CI.

## Documentation
* [ ] Justified associative-array shapes documented in PHPDoc
* [ ] No @param/@return in docblocks when the signature already types the value
* [ ] Conditions are named, including single comparisons
* [ ] No magic numbers or strings in application code (tests are exempt)

**Notes:**

* Name a condition so the variable says why the branch is taken. `if ($scenes !== [])` does not. `if ($hasScenes)` does. Leave the condition inline only when it is already a boolean property or method whose name states the meaning, such as `if ($project->hasUsers)`.
* A string or number literal in application code is magic unless it is:
  * an enum case
  * a class constant
  * a local variable, and only when that value does not belong on a class and is not shared across files
* Statuses, phases, reason codes, metadata keys, and limits belong on an enum or a class constant. Tests may use literals.

## Conventions
* [ ] Service naming convention followed (`{Description}Service`)
* [ ] Repository naming convention followed (`{Description}Repo`)
* [ ] PHP/Laravel Style Guide followed
## Testing
* [ ] All public methods covered
* [ ] Passing CI build

### Unit vs integration (mandatory)

| | **Unit** (`tests/Unit/`) | **Integration** (`tests/Integration/`) |
|---|---|---|
| Base class | `Tests\TestCase` | `Tests\IntegrationTestCase` |
| Database | **Never** — no factories, no `assertDatabaseHas`, no `DatabaseTransactions`, no `RefreshDatabase` | Yes — factories in `setUp` / per test |
| Dependencies | **Mock everything** (services, models, agents, facades where needed) | Real collaborators; mock only external boundaries when appropriate |
| Purpose | Isolated behavior of one class | Persistence, queries, relations, multi-component flows |

**Never use `RefreshDatabase`** anywhere (it runs `migrate:fresh`).

If a test needs the database, it is **not** a unit test — move it to `tests/Integration/` and extend `IntegrationTestCase` (which already uses `DatabaseTransactions`).

```php
// Unit — GOOD
namespace Tests\Unit\Jobs;
class SceneExecutionJobApiOnlyFailureTest extends TestCase { /* mocks only */ }

// Integration — GOOD
namespace Tests\Integration\Services\Audit;
class UserActivityLogServiceTest extends IntegrationTestCase { /* factories OK */ }

// Unit — BAD (belongs in Integration)
class UserActivityLogServiceTest extends TestCase
{
    use DatabaseTransactions; // forbidden in Unit/
    // User::factory()->create() — forbidden in Unit/
}
```

### Unit (`tests/Unit/`)
* [ ] Extend `Tests\TestCase` (or `PHPUnit\Framework\TestCase` when the Laravel app is not needed)
* [ ] **Mock all dependencies** — no real database, repositories, or Eloquent queries
* [ ] Code *behavior* is asserted (return values, side effects on mocks, merged state)
* [ ] Use in-memory model stubs (e.g. a test subclass with `update()` overridden) when the SUT mutates a model instance

### Integration (`tests/Integration/`)
* [ ] Extend `Tests\IntegrationTestCase` (includes `DatabaseTransactions` — do not add `RefreshDatabase`)
* [ ] Return types, return values, or return structures are asserted
* [ ] Real dependencies are exercised — database, query builders, factories, etc.
* [ ] Factories used to seed database data in `setUp` or per test
* [ ] Model query/relation/constraint tests belong here (e.g. `tests/Integration/Models/`), not under `Unit/` or `Feature/`
* [ ] For LLM workflow evals, prefer behavioral/tendency assertions over single-run deterministic prose outcomes (see `app/Agents/Workflows/Evals/README.md`)

### Feature (`tests/Feature/`)
* [ ] GraphQL queries/mutations and approved HTTP exceptions are tested through the application boundary
* [ ] Extend `IntegrationTestCase` (or equivalent) when the test needs the database
* [ ] Never use `RefreshDatabase`

# Common Infractions
- Angular feature code calling `/api/...` or other domain REST endpoints directly => move the operation to the typed GraphQL client layer and call `/graphql`
- New REST controller/route for ordinary application CRUD => model it as a GraphQL query or mutation; keep HTTP routes only for approved protocol/infrastructure exceptions
- Eloquent/query builder use in a GraphQL resolver => move persistence/query logic to a Model or Repository and keep the resolver thin
- GraphQL `JSON` used for a value that has a stable domain shape => define an explicit GraphQL input/object type
- Eloquent chain in a service or controller (`$project->scenes->reject()->sortBy()`) => move the chain into an instance method on the model and call that method
- Copied private helper => move the helper to its owner and call it; do not copy it because another class already did
- `new SomeService(...)` => inject the service, or resolve it with `App::make()`
- New static method for behavior with dependencies => inject that behavior; calling an existing dependency-free static (`tryFrom`, `fromArray`, a factory, a guard) is fine
- Associative array as a public parameter or return value => convert it to an object at the boundary, including values read from JSON; a justified exception documents the array shape in PHPDoc
- Unnamed condition, including a single comparison (`if ($scenes !== [])`) => name it (`if ($hasScenes)`); a boolean that already states the meaning (`if ($project->hasUsers)`) can stay inline
- Bare status, key, or limit literal in application code => use an enum or class constant; a one-off local variable is allowed only when the value does not belong on a class and is not shared across files; tests are exempt
- Unit test using factories or the database => **move to `tests/Integration/`** and extend `IntegrationTestCase`; unit tests must mock all persistence
- Model/relation test under `tests/Feature/` => move to `tests/Integration/Models/` and extend `IntegrationTestCase`
