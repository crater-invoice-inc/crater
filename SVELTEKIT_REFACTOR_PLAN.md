# Crater Invoice — SvelteKit + Tailwind + Prisma + PostgreSQL Refactor Plan

## 1. Current Architecture Analysis

### Current Stack
- **Backend**: Laravel 8 (PHP 7.4+/8.0), Sanctum auth, Bouncer RBAC
- **Frontend**: Vue 3 SPA (via Vite), Pinia stores, Vue Router, vue-i18n
- **CSS**: Tailwind CSS 3 (already in use)
- **Database**: MySQL/SQLite/PostgreSQL (via Eloquent ORM, 124 migration files)
- **Other**: DomPDF for PDF generation, Spatie Media Library, Spatie Backup, Intervention Image, Stripe.js

### Current Scale
| Component | Count |
|---|---|
| Laravel Controllers | 132 |
| Eloquent Models | 31 |
| Vue Components | 311 |
| Pinia Stores | 28 (25 admin + 8 customer + 3 shared) |
| JS/TS Files | 85 |
| Database Migrations | 124 |
| Test Files (Pest/PHPUnit) | 57 |

### Core Domain Models
`User`, `Customer`, `Company`, `Invoice`, `InvoiceItem`, `Estimate`, `EstimateItem`, `Payment`, `PaymentMethod`, `Expense`, `ExpenseCategory`, `Item`, `Unit`, `TaxType`, `Tax`, `RecurringInvoice`, `CustomField`, `CustomFieldValue`, `Currency`, `ExchangeRateProvider`, `ExchangeRateLog`, `Address`, `Country`, `Note`, `EmailLog`, `FileDisk`, `CompanySetting`, `UserSetting`, `Setting`, `Transaction`, `Module`

### Core Features
1. **Multi-company support** — users can own/belong to multiple companies
2. **Invoicing** — CRUD, PDF generation, email sending, status tracking, cloning, templates
3. **Recurring Invoices** — frequency-based auto-generation
4. **Estimates** — CRUD, PDF, email, convert-to-invoice, accept/reject
5. **Payments** — CRUD, PDF, email, payment methods
6. **Expenses** — CRUD, receipt uploads, categories
7. **Items/Products** — catalog with units, pricing
8. **Customers** — CRUD, per-customer stats/charts
9. **Tax System** — tax types, per-item and per-invoice taxes, compound taxes
10. **Custom Fields** — dynamic fields on invoices, estimates, customers, expenses, payments
11. **Reporting** — customer sales, item sales, expenses, tax summary, profit/loss (PDF reports)
12. **Customer Portal** — separate auth, view invoices/estimates/payments, accept estimates
13. **Settings** — company info, preferences, mail config, backups, file disks, roles/permissions
14. **Exchange Rates** — multiple providers, bulk update
15. **Modules/Plugins** — install/enable/disable marketplace modules
16. **Self-Update** — download, unzip, migrate from within the app
17. **Installation Wizard** — multi-step onboarding
18. **i18n** — internationalization via vue-i18n
19. **RBAC** — role-based access control via Bouncer

---

## 2. Target Architecture

### Target Stack
- **Framework**: SvelteKit (latest, SSR + SPA hybrid)
- **Language**: TypeScript throughout
- **CSS**: Tailwind CSS 4 (upgrade from 3, already partially in place)
- **ORM**: Prisma (replacing Eloquent)
- **Database**: PostgreSQL (replacing MySQL/SQLite support)
- **Auth**: Auth.js (SvelteKit adapter) or Lucia Auth
- **PDF**: puppeteer or @react-pdf equivalent (server-side)
- **File Storage**: S3-compatible via AWS SDK
- **Email**: Nodemailer or Resend
- **Validation**: Zod
- **State**: SvelteKit built-in (stores, load functions, form actions)

### Key Architectural Decisions
- **SSR-first**: Use SvelteKit server load functions and form actions instead of a separate API
- **API routes**: Maintain REST API endpoints under `/api/v1/` for mobile app compatibility
- **Monorepo**: Single SvelteKit project (no separate backend)
- **Multi-tenant**: Company scoping via middleware/hooks

---

## 3. Phased Migration Plan

### Phase 0: Project Scaffolding
**Goal**: Set up the new SvelteKit project structure alongside existing code

1. Initialize SvelteKit project in a new `sveltekit/` directory at repo root
   ```
   npx sv create sveltekit --template minimal --types ts
   ```
2. Install and configure dependencies:
   - `prisma`, `@prisma/client`
   - `tailwindcss`, `@tailwindcss/forms`, `@tailwindcss/typography`
   - `lucia` (auth library) + `@lucia-auth/adapter-prisma`
   - `zod` (validation)
   - `nodemailer` (email)
   - `puppeteer` (PDF generation)
   - `svelte-i18n` (internationalization)
   - `aws-sdk` or `@aws-sdk/client-s3` (file storage)
   - `chart.js` + `svelte-chartjs` (dashboard charts)
   - `stripe` (payments integration)
3. Configure Tailwind CSS with the existing design tokens from `tailwind.config.js`
4. Set up ESLint, Prettier, Vitest, Playwright
5. Set up Docker Compose for PostgreSQL dev database

**Directory structure**:
```
sveltekit/
├── prisma/
│   └── schema.prisma
├── src/
│   ├── lib/
│   │   ├── server/          # Server-only code
│   │   │   ├── auth.ts      # Lucia auth setup
│   │   │   ├── prisma.ts    # Prisma client singleton
│   │   │   ├── email.ts     # Email service
│   │   │   ├── pdf.ts       # PDF generation
│   │   │   └── storage.ts   # File storage (S3/local)
│   │   ├── components/      # Shared Svelte components
│   │   │   ├── ui/          # Base UI (buttons, inputs, modals, tables)
│   │   │   ├── invoice/     # Invoice-specific components
│   │   │   ├── estimate/    # Estimate-specific components
│   │   │   └── ...
│   │   ├── stores/          # Svelte stores (minimal, most state via load functions)
│   │   ├── utils/           # Shared utilities
│   │   ├── schemas/         # Zod validation schemas
│   │   └── i18n/            # i18n configuration and translations
│   ├── routes/
│   │   ├── (auth)/          # Auth layout group (login, forgot password, reset)
│   │   ├── (app)/           # Admin layout group (authenticated)
│   │   │   ├── dashboard/
│   │   │   ├── invoices/
│   │   │   ├── estimates/
│   │   │   ├── payments/
│   │   │   ├── expenses/
│   │   │   ├── customers/
│   │   │   ├── items/
│   │   │   ├── recurring-invoices/
│   │   │   ├── reports/
│   │   │   └── settings/
│   │   ├── (customer)/      # Customer portal layout group
│   │   │   ├── [slug]/
│   │   │   │   ├── dashboard/
│   │   │   │   ├── invoices/
│   │   │   │   ├── estimates/
│   │   │   │   └── payments/
│   │   ├── api/             # REST API for mobile compatibility
│   │   │   └── v1/
│   │   └── install/         # Installation wizard
│   ├── hooks.server.ts      # Auth + company scoping middleware
│   └── app.html
├── static/
├── tests/
├── tailwind.config.ts
├── svelte.config.js
├── vite.config.ts
└── package.json
```

---

### Phase 1: Database — Prisma Schema + PostgreSQL
**Goal**: Translate all 31 Eloquent models and 124 migrations into a single Prisma schema

**prisma/schema.prisma** key models (mapped from existing Laravel models):

```prisma
generator client {
  provider = "prisma-client-js"
}

datasource db {
  provider = "postgresql"
  url      = env("DATABASE_URL")
}

model User {
  id              String    @id @default(cuid())
  name            String
  email           String    @unique
  password        String
  phone           String?
  role            String    @default("super admin")
  emailVerifiedAt DateTime?
  createdAt       DateTime  @default(now())
  updatedAt       DateTime  @updatedAt
  creatorId       String?

  creator         User?     @relation("UserCreator", fields: [creatorId], references: [id])
  createdUsers    User[]    @relation("UserCreator")

  companies       CompanyUser[]
  sessions        Session[]
  settings        UserSetting[]
  invoices        Invoice[]
  estimates       Estimate[]
  payments        Payment[]
  expenses        Expense[]
  items           Item[]
}

model Session {
  id        String   @id
  userId    String
  expiresAt DateTime
  user      User     @relation(fields: [userId], references: [id], onDelete: Cascade)
}

model Company {
  id        String   @id @default(cuid())
  name      String
  slug      String   @unique
  ownerId   String
  uniqueHash String  @unique @default(cuid())
  createdAt DateTime @default(now())
  updatedAt DateTime @updatedAt

  owner     User     @relation(fields: [ownerId], references: [id])
  users     CompanyUser[]
  customers Customer[]
  invoices  Invoice[]
  estimates Estimate[]
  payments  Payment[]
  expenses  Expense[]
  items     Item[]
  taxTypes  TaxType[]
  settings  CompanySetting[]
  // ... other relations
}

model Customer {
  id            String    @id @default(cuid())
  name          String
  email         String?
  phone         String?
  contactName   String?
  website       String?
  password      String?
  enablePortal  Boolean   @default(false)
  currencyId    String?
  companyId     String
  creatorId     String?
  prefix        String?
  createdAt     DateTime  @default(now())
  updatedAt     DateTime  @updatedAt

  company       Company   @relation(fields: [companyId], references: [id])
  currency      Currency? @relation(fields: [currencyId], references: [id])
  addresses     Address[]
  invoices      Invoice[]
  estimates     Estimate[]
  payments      Payment[]
  expenses      Expense[]
}

model Invoice {
  id                  String    @id @default(cuid())
  invoiceNumber       String
  invoiceDate         DateTime
  dueDate             DateTime?
  status              String    @default("DRAFT") // DRAFT, SENT, VIEWED, OVERDUE, COMPLETED
  paidStatus          String    @default("UNPAID") // UNPAID, PARTIALLY_PAID, PAID
  taxPerItem          String    @default("NO")
  discountType        String    @default("fixed")
  discountVal         Decimal   @default(0) @db.Decimal(15,2)
  discount            Decimal   @default(0) @db.Decimal(15,2)
  subTotal            Decimal   @db.Decimal(15,2)
  total               Decimal   @db.Decimal(15,2)
  dueAmount           Decimal   @db.Decimal(15,2)
  tax                 Decimal   @default(0) @db.Decimal(15,2)
  notes               String?   @db.Text
  uniqueHash          String    @unique @default(cuid())
  templateName        String    @default("invoice1")
  sequenceNumber      Int?
  baseDiscount        Decimal?  @db.Decimal(15,2)
  baseTotal           Decimal?  @db.Decimal(15,2)
  baseDueAmount       Decimal?  @db.Decimal(15,2)
  baseTax             Decimal?  @db.Decimal(15,2)
  baseSubTotal        Decimal?  @db.Decimal(15,2)
  exchangeRate        Decimal?  @db.Decimal(19,6)
  customerId          String
  companyId           String
  creatorId           String?
  currencyId          String?
  recurringInvoiceId  String?

  customer            Customer  @relation(fields: [customerId], references: [id])
  company             Company   @relation(fields: [companyId], references: [id])
  currency            Currency? @relation(fields: [currencyId], references: [id])
  items               InvoiceItem[]
  taxes               Tax[]
  payments            Payment[]
  emailLogs           EmailLog[]
  customFieldValues   CustomFieldValue[]
  createdAt           DateTime  @default(now())
  updatedAt           DateTime  @updatedAt
}

// ... (similarly for Estimate, Payment, Expense, Item, TaxType, etc.)
```

**Tasks**:
1. Create full `schema.prisma` covering all 31 models with proper relations, indexes, and constraints
2. Write a data migration script (`scripts/migrate-data.ts`) to import existing MySQL/SQLite data into PostgreSQL
3. Create seed data (`prisma/seed.ts`) for development
4. Verify all relationships match existing Eloquent model relations

---

### Phase 2: Authentication & Authorization
**Goal**: Replace Laravel Sanctum + Bouncer with Lucia Auth + custom RBAC

1. Set up Lucia Auth with Prisma adapter
2. Implement login/logout/forgot-password/reset-password flows
3. Port the Bouncer role/permission system:
   - Create `Role`, `Ability`, `RoleAbility`, `UserAbility` Prisma models
   - Build a middleware in `hooks.server.ts` that:
     - Validates session
     - Loads current user + company
     - Checks permissions per route
4. Implement customer portal auth (separate session/cookie namespace)
5. Port the API token auth for mobile apps (using bearer tokens stored in DB)

---

### Phase 3: Core Server Services
**Goal**: Port all Laravel backend services to SvelteKit server-side code

#### 3a. PDF Generation
- Replace `barryvdh/laravel-dompdf` with Puppeteer or `@react-pdf/renderer`
- Port all 3 invoice templates, estimate templates, payment templates
- Create `/api/v1/invoices/[id]/pdf`, `/api/v1/estimates/[id]/pdf`, `/api/v1/payments/[id]/pdf`

#### 3b. Email Service
- Replace Laravel Mail with Nodemailer
- Support SMTP, Mailgun, SES (matching current config options)
- Port email templates for: invoice send, estimate send, payment send, password reset
- Create `EmailLog` tracking

#### 3c. File Storage
- Replace Laravel Filesystem with `@aws-sdk/client-s3` + local filesystem fallback
- Support: local, S3, DigitalOcean Spaces, Dropbox (matching current `FileDisk` model)
- Port receipt upload, company logo upload, avatar upload

#### 3d. Cron / Scheduled Jobs
- Replace Laravel scheduler with a SvelteKit API endpoint + external cron trigger
- Port recurring invoice generation logic
- Port exchange rate auto-updates

#### 3e. Multi-Company Scoping
- Port the `company` middleware that scopes all queries to the active company
- Implement company switching logic
- Ensure all Prisma queries include `companyId` filter

---

### Phase 4: UI Component Library (Svelte + Tailwind)
**Goal**: Port the 311 Vue components to Svelte, starting with the base component library

#### 4a. Base UI Components (port from `resources/scripts/components/base/`)
Map Vue base components to Svelte:
| Vue Component | Svelte Component |
|---|---|
| `BaseButton.vue` | `$lib/components/ui/Button.svelte` |
| `BaseInput.vue` | `$lib/components/ui/Input.svelte` |
| `BaseTextarea.vue` | `$lib/components/ui/Textarea.svelte` |
| `BaseCheckbox.vue` | `$lib/components/ui/Checkbox.svelte` |
| `BaseRadio.vue` | `$lib/components/ui/Radio.svelte` |
| `BaseLabel.vue` | `$lib/components/ui/Label.svelte` |
| `BaseSelect.vue` | `$lib/components/ui/Select.svelte` |
| `BaseMultiselect.vue` | `$lib/components/ui/Multiselect.svelte` |
| `BaseModal.vue` | `$lib/components/ui/Modal.svelte` |
| `BaseDropdown.vue` | `$lib/components/ui/Dropdown.svelte` |
| `BaseTable.vue` | `$lib/components/ui/Table.svelte` |
| `BaseBadge.vue` | `$lib/components/ui/Badge.svelte` |
| `BaseSwitch.vue` | `$lib/components/ui/Switch.svelte` |
| `BaseFilterWrapper.vue` | `$lib/components/ui/FilterWrapper.svelte` |
| `BaseDivider.vue` | `$lib/components/ui/Divider.svelte` |
| `BaseCustomInput.vue` | `$lib/components/ui/CustomInput.svelte` |
| `BaseContentPlaceholders.vue` | `$lib/components/ui/Skeleton.svelte` |
| `BasePage.vue` | `$lib/components/ui/Page.svelte` |

#### 4b. Layout Components
- Admin layout: sidebar, header, company switcher
- Customer portal layout: sidebar, header, footer
- Auth layout (login pages)
- Installation wizard layout

#### 4c. Shared Feature Components
- Invoice/estimate line item editor (`CreateItems`, `CreateItemRow`, `CreateItemRowTax`)
- Tax selector popup
- Note selector
- Template selector
- Send email modal
- Global search bar
- Notification system
- Chart components (line chart, dashboard stats)

---

### Phase 5: Admin Pages (Route-by-Route Migration)
**Goal**: Port all admin views as SvelteKit routes

Each page uses SvelteKit `+page.server.ts` for data loading and form actions.

#### 5a. Dashboard
- `/(app)/dashboard/+page.svelte` — stats cards, chart, recent tables
- Server load: aggregate queries for revenue, invoices, estimates, customers

#### 5b. Customers
- `/(app)/customers/+page.svelte` — list with filters, pagination, bulk delete
- `/(app)/customers/create/+page.svelte` — create form with address, custom fields
- `/(app)/customers/[id]/+page.svelte` — view with stats, chart, related invoices

#### 5c. Items
- `/(app)/items/+page.svelte` — list, search, bulk delete
- `/(app)/items/create/+page.svelte` — create/edit form with units, taxes

#### 5d. Invoices
- `/(app)/invoices/+page.svelte` — list with status filters, date range, customer filter
- `/(app)/invoices/create/+page.svelte` — complex form: line items, taxes, discounts, template
- `/(app)/invoices/[id]/+page.svelte` — view with status actions, send, clone, PDF
- Form actions: create, update, delete, send, clone, change-status

#### 5e. Recurring Invoices
- `/(app)/recurring-invoices/+page.svelte` — list
- `/(app)/recurring-invoices/create/+page.svelte` — create with frequency config
- `/(app)/recurring-invoices/[id]/+page.svelte` — view with generated invoices list

#### 5f. Estimates
- Same pattern as invoices: list, create, view
- Additional: convert-to-invoice action

#### 5g. Expenses
- `/(app)/expenses/+page.svelte` — list with category/customer filters
- `/(app)/expenses/create/+page.svelte` — form with receipt upload, category select

#### 5h. Payments
- Same pattern: list, create, view with payment methods

#### 5i. Reports
- `/(app)/reports/+page.svelte` — report type selector
- Sub-pages: customer sales, item sales, expenses, tax summary, profit/loss
- Each generates a downloadable PDF

#### 5j. Settings
- `/(app)/settings/+layout.svelte` — settings sidebar navigation
- Sub-pages for: account, company info, preferences, customization (invoice/estimate/payment number formats), notifications, tax types, payment modes, expense categories, custom fields, notes, mail config, file disks, backups, roles, exchange rates, update

#### 5k. Users
- `/(app)/users/+page.svelte` — list users
- `/(app)/users/create/+page.svelte` — create/edit with role assignment

---

### Phase 6: Customer Portal
**Goal**: Port the customer-facing portal

- `/(customer)/[slug]/login/+page.svelte`
- `/(customer)/[slug]/dashboard/+page.svelte`
- `/(customer)/[slug]/invoices/+page.svelte` and `[id]/+page.svelte`
- `/(customer)/[slug]/estimates/+page.svelte` and `[id]/+page.svelte` (with accept action)
- `/(customer)/[slug]/payments/+page.svelte` and `[id]/+page.svelte`
- `/(customer)/[slug]/settings/+page.svelte` — profile update

Customer portal uses separate auth guard and themed layout.

---

### Phase 7: REST API (Mobile Compatibility)
**Goal**: Maintain API parity for the existing mobile app

Port all `/api/v1/` endpoints as SvelteKit API routes (`+server.ts` files):

```
src/routes/api/v1/
├── auth/
│   ├── login/+server.ts
│   ├── logout/+server.ts
│   └── password/
├── bootstrap/+server.ts
├── customers/
│   ├── +server.ts          (GET list, POST create, POST delete)
│   └── [id]/+server.ts     (GET, PUT, DELETE)
├── invoices/
│   ├── +server.ts
│   ├── [id]/+server.ts
│   ├── [id]/send/+server.ts
│   ├── [id]/clone/+server.ts
│   ├── [id]/status/+server.ts
│   └── templates/+server.ts
├── estimates/              (same pattern)
├── payments/               (same pattern)
├── expenses/               (same pattern)
├── items/                  (same pattern)
├── recurring-invoices/     (same pattern)
├── settings/               (company, user, mail)
├── reports/                (each report type)
└── ...
```

---

### Phase 8: Installation Wizard & Onboarding
**Goal**: Port the 8-step installation wizard

- `/install/+page.svelte` — step controller
- Steps: requirements check, permissions, database config, domain verify, email config, account creation, company info, preferences
- This is significantly simpler in SvelteKit — most steps become form actions

---

### Phase 9: Internationalization (i18n)
**Goal**: Port vue-i18n translations to svelte-i18n

1. Copy all JSON locale files from `resources/scripts/locales/`
2. Set up `svelte-i18n` with lazy loading per locale
3. Replace all `$t('key')` calls in templates with `$_('key')`

---

### Phase 10: Module/Plugin System
**Goal**: Design a new module system for SvelteKit

- Replace Laravel's module system with a hook-based plugin architecture
- Modules register: routes, navigation items, Prisma schema extensions
- Use SvelteKit's `handle` hook chain for module middleware
- Provide a module API for registering custom fields, menu items, and pages

---

### Phase 11: Testing
**Goal**: Comprehensive test coverage

1. **Unit tests** (Vitest): Prisma service functions, validation schemas, utility functions
2. **Component tests** (Vitest + @testing-library/svelte): all UI components
3. **Integration tests** (Vitest): API routes, form actions, auth flows
4. **E2E tests** (Playwright): critical flows — login, create invoice, send invoice, customer portal
5. Port existing 57 Pest/PHPUnit tests to equivalent Vitest/Playwright tests

---

### Phase 12: DevOps & Deployment
**Goal**: Production-ready deployment config

1. Update `Dockerfile` for Node.js (replace PHP-FPM)
2. Update `docker-compose.yml`: Node.js app + PostgreSQL + Redis (for sessions)
3. Add health check endpoint
4. Configure environment variables mapping
5. Set up database backup strategy (pg_dump)
6. Add CI/CD pipeline (GitHub Actions): lint, typecheck, test, build, deploy

---

## 4. Migration Strategy

### Data Migration Path
1. Run existing Laravel app with current database
2. Use `scripts/migrate-data.ts` to:
   - Connect to source MySQL/SQLite database
   - Transform and insert into PostgreSQL via Prisma
   - Verify data integrity with row count checks
   - Handle: password hashes (bcrypt — compatible), file paths, JSON fields

### Parallel Running Period
- Keep Laravel app running at `/legacy/` during migration
- New SvelteKit app at `/`
- Share the same PostgreSQL database (read-only from Laravel side)
- Gradually redirect traffic

### Breaking Changes to Track
- URL structure changes (Vue SPA hash routes → SvelteKit file routes)
- API response format standardization (wrap in consistent `{data, meta}` envelope)
- Session management (Sanctum tokens → Lucia sessions)
- File upload paths may change

---

## 5. Estimated Scope per Phase

| Phase | Description | Key Files |
|---|---|---|
| 0 | Scaffolding | ~15 config files |
| 1 | Prisma Schema | 1 schema file + seed + migration script |
| 2 | Auth | ~8 files (auth lib, login/register pages, hooks) |
| 3 | Server Services | ~12 files (PDF, email, storage, cron, company scope) |
| 4 | UI Components | ~50 Svelte components |
| 5 | Admin Pages | ~60 route files (page + server) |
| 6 | Customer Portal | ~15 route files |
| 7 | REST API | ~40 server endpoint files |
| 8 | Install Wizard | ~10 route files |
| 9 | i18n | locale files + config |
| 10 | Module System | ~5 core files + docs |
| 11 | Testing | ~80 test files |
| 12 | DevOps | ~10 config files |

---

## 6. Risk Mitigation

1. **PDF template fidelity** — Test PDF output against existing DomPDF output early (Phase 3a)
2. **Data migration integrity** — Write verification queries comparing row counts and checksums
3. **Mobile app compatibility** — Maintain exact API response shapes in Phase 7
4. **Performance** — SvelteKit SSR should improve initial load; add Redis caching for dashboard aggregations
5. **Module ecosystem** — Existing Laravel modules won't work; provide clear migration guide for module authors
6. **Exchange rates / cron** — Ensure the cron endpoint is secured and tested in Phase 3d
